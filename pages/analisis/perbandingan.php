<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'analisis_perbandingan';
$pageTitle = 'Analisis Perbandingan & Strategi Target';
$pageDescription = 'Analisis perbandingan pengumpulan antar periode dan rekomendasi strategi pencapaian';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Analisis Perbandingan</li>';
$conn = db();

// Period A (Reference) & Period B (Comparison)
$periodA = $_GET['period_a'] ?? date('Y-m', strtotime('-1 month'));
$periodB = $_GET['period_b'] ?? date('Y-m');
$growthFactor = (float)($_GET['growth_factor'] ?? 1.1);

// Helper for labels
function format_period($period) {
    return date('F Y', strtotime($period . '-01'));
}

// Short Amount Formatter
function short_amount($amount) {
    if ($amount >= 1000000000) return round($amount / 1000000000, 2) . ' M';
    if ($amount >= 1000000) return round($amount / 1000000, 1) . ' Jt';
    return number_format($amount, 0, ',', '.');
}

// 1. Core Summary Metrics
function get_period_stats($conn, $period) {
    $stmt = $conn->prepare("SELECT SUM(jumlah_pembayaran) as total, COUNT(id) as count, COUNT(DISTINCT id_muzaki) as unique_muzaki FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?");
    $stmt->bind_param('s', $period);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return [
        'total' => (float)($res['total'] ?? 0),
        'count' => (int)($res['count'] ?? 0),
        'unique_muzaki' => (int)($res['unique_muzaki'] ?? 0),
        'avg' => $res['count'] > 0 ? (float)$res['total'] / $res['count'] : 0
    ];
}

$statsA = get_period_stats($conn, $periodA);
$statsB = get_period_stats($conn, $periodB);
$growthValue = $statsB['total'] - $statsA['total'];
$growthPct = $statsA['total'] > 0 ? (($statsB['total'] - $statsA['total']) / $statsA['total']) * 100 : 0;
$suggestedTarget = $statsA['total'] * $growthFactor;

// 2. Lost Muzaki
$lostMuzakiSql = "
    SELECT m.id, m.nama_muzaki, u.nama_upz, 
           (SELECT COALESCE(SUM(jumlah_pembayaran), 0) FROM transaksi WHERE id_muzaki = m.id AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?) as last_amount
    FROM muzaki m
    LEFT JOIN upz u ON m.id_upz_sekarang = u.id
    WHERE m.id IN (SELECT id_muzaki FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?)
      AND m.id NOT IN (SELECT id_muzaki FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?)
    ORDER BY last_amount DESC
";
$stmtLostM = $conn->prepare($lostMuzakiSql);
$stmtLostM->bind_param('sss', $periodA, $periodA, $periodB);
$stmtLostM->execute();
$lostMuzaki = $stmtLostM->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Lost UPZ
$lostUpzSql = "
    SELECT u.id, u.nama_upz, 
           (SELECT COALESCE(SUM(jumlah_pembayaran), 0) FROM transaksi WHERE id_upz = u.id AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?) as last_amount
    FROM upz u
    WHERE u.id IN (SELECT id_upz FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?)
      AND u.id NOT IN (SELECT id_upz FROM transaksi WHERE DATE_FORMAT(tanggal_transaksi, '%Y-%m') = ?)
    ORDER BY last_amount DESC
";
$stmtLostU = $conn->prepare($lostUpzSql);
$stmtLostU->bind_param('sss', $periodA, $periodA, $periodB);
$stmtLostU->execute();
$lostUpz = $stmtLostU->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Category Shift
$categoryShift = $conn->query("
    SELECT jp.nama_jenis,
           (SELECT COALESCE(SUM(jumlah_pembayaran), 0) FROM transaksi WHERE id_jenis_pembayaran = jp.id AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = '$periodA') as total_A,
           (SELECT COALESCE(SUM(jumlah_pembayaran), 0) FROM transaksi WHERE id_jenis_pembayaran = jp.id AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = '$periodB') as total_B
    FROM jenis_pembayaran jp
    HAVING total_A > 0 OR total_B > 0
    ORDER BY total_B DESC
")->fetch_all(MYSQLI_ASSOC);

// --- AI STRATEGIC TIPS ENGINE ---
$aiTips = [];
if ($growthPct < 0) {
    $aiTips[] = ["icon" => "fa-exclamation-circle", "color" => "text-danger", "text" => "Terdeteksi penurunan pengumpulan sebesar <strong>" . round(abs($growthPct), 1) . "%</strong>. Segera lakukan audit pada UPZ dengan performa terendah."];
} else {
    $aiTips[] = ["icon" => "fa-rocket", "color" => "text-success", "text" => "Performa tumbuh positif. Pertahankan momentum dengan memberikan apresiasi kepada Muzaki Loyal."];
}

if (count($lostMuzaki) > 0) {
    $aiTips[] = ["icon" => "fa-user-clock", "color" => "text-warning", "text" => "Ada <strong>" . count($lostMuzaki) . " Muzaki</strong> potensial yang belum membayar. Fokuskan 70% tenaga lapangan untuk silaturahmi ke daftar ini."];
}

$worstCategory = null; $maxDrop = 0;
foreach ($categoryShift as $c) {
    $diff = (float)$c['total_B'] - (float)$c['total_A'];
    if ($diff < $maxDrop) { $maxDrop = $diff; $worstCategory = $c['nama_jenis']; }
}
if ($worstCategory) {
    $aiTips[] = ["icon" => "fa-chart-line", "color" => "text-info", "text" => "Kategori <strong>$worstCategory</strong> mengalami penurunan nominal terbesar. Pertimbangkan kampanye khusus untuk jenis ZIS ini."];
}

if ($statsB['total'] < $suggestedTarget) {
    $gap = $suggestedTarget - $statsB['total'];
    $aiTips[] = ["icon" => "fa-bullseye", "color" => "text-success", "text" => "Masih ada selisih <strong>" . format_currency($gap) . "</strong> untuk mencapai target pertumbuhan. Hubungi TOP Muzaki Pasif untuk menutup gap ini."];
}

include __DIR__ . '/../../inc/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
    .card-one { margin-bottom: 1.5rem; }
    .ai-card { background-color: #ffffff !important; border-left: 4px solid #0d9f4f !important; }
    .metric-row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 0px; }
    .metric-item { flex: 1; min-width: 200px; background: #ffffff !important; padding: 15px; border-radius: 4px; border: 1px solid #b8b8b8 !important; }
    .table-clean thead th { background: #ffffff !important; font-size: 0.75rem; color: #333; text-transform: uppercase; padding: 15px; border-bottom: 1px solid #b8b8b8 !important; }
    .table-clean td { padding: 15px; vertical-align: middle; border-top: 1px solid #b8b8b8 !important; }
</style>

<!-- 1. FILTER PANEL (ONE COLUMN) -->
<div class="card card-one shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-body p-4">
        <form method="GET">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="font-weight-bold small text-muted">BULAN ACUAN (A)</label>
                    <input type="month" name="period_a" class="form-control" value="<?= $periodA ?>" style="border: 1px solid #b8b8b8;">
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="font-weight-bold small text-muted">BULAN ANALISIS (B)</label>
                    <input type="month" name="period_b" class="form-control" value="<?= $periodB ?>" style="border: 1px solid #b8b8b8;">
                </div>
                <div class="col-md-2 mb-3 mb-md-0">
                    <label class="font-weight-bold small text-muted">TARGET (%)</label>
                    <select name="growth_factor" class="form-control" style="border: 1px solid #b8b8b8;">
                        <option value="1.0" <?= $growthFactor == 1.0 ? 'selected' : '' ?>>100%</option>
                        <option value="1.1" <?= $growthFactor == 1.1 ? 'selected' : '' ?>>110%</option>
                        <option value="1.2" <?= $growthFactor == 1.2 ? 'selected' : '' ?>>120%</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-bolt mr-2"></i> ANALISIS</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 2. AI STRATEGIC TIPS -->
<div class="card card-one ai-card shadow-none" style="border: 1px solid #b8b8b8; border-left: 4px solid #0d9f4f !important;">
    <div class="card-body">
        <h5 class="font-weight-bold mb-4 text-success"><i class="fas fa-robot mr-2"></i> ANALISIS CERDAS & REKOMENDASI STRATEGIS</h5>
        <div class="row">
            <?php foreach ($aiTips as $tip): ?>
            <div class="col-md-12 mb-3">
                <div class="d-flex align-items-start">
                    <div class="mr-3 mt-1"><i class="fas <?= $tip['icon'] ?> fa-lg <?= $tip['color'] ?>"></i></div>
                    <div class="text-dark"><?= $tip['text'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 3. SUMMARY METRICS (ONE COLUMN) -->
<div class="card card-one shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-body">
        <h6 class="font-weight-bold text-muted mb-4 text-uppercase small">Ringkasan Performa Periode</h6>
        <div class="metric-row">
            <div class="metric-item">
                <small class="text-muted d-block"><?= format_period($periodA) ?></small>
                <h4 class="font-weight-bold mb-0">Rp <?= short_amount($statsA['total']) ?></h4>
            </div>
            <div class="metric-item" style="border-color: #0d9f4f !important;">
                <small class="text-muted d-block"><?= format_period($periodB) ?></small>
                <h4 class="font-weight-bold mb-0">Rp <?= short_amount($statsB['total']) ?></h4>
            </div>
            <div class="metric-item">
                <small class="text-muted d-block">Pertumbuhan</small>
                <h4 class="font-weight-bold mb-0 <?= $growthValue >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= $growthValue >= 0 ? '+' : '' ?><?= round($growthPct, 1) ?>%
                </h4>
            </div>
            <div class="metric-item" style="border-color: #0d9f4f !important;">
                <small class="text-success d-block font-weight-bold">Estimasi Target</small>
                <h4 class="font-weight-bold mb-0 text-success">Rp <?= short_amount($suggestedTarget) ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- 4. COMPARISON CHART (NEW SUPPORTING DATA) -->
<div class="card card-one shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-header bg-white pt-4 px-4" style="border-bottom: 1px solid #b8b8b8;">
        <h5 class="font-weight-bold mb-0 text-success"><i class="fas fa-chart-bar mr-2"></i> Perbandingan Pengumpulan per Jenis ZIS</h5>
    </div>
    <div class="card-body">
        <div id="compareApexChart" style="min-height: 350px;"></div>
    </div>
</div>

<!-- 5. CORE TABLE: LOST MUZAKI -->
<div class="card card-one shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-header bg-white pt-4 px-4" style="border-bottom: 1px solid #b8b8b8;">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="font-weight-bold mb-0"><i class="fas fa-user-times text-danger mr-2"></i> DAFTAR MUZAKI PASIF (BELUM MEMBAYAR)</h5>
            <span class="badge badge-danger p-2 px-3 rounded-pill"><?= count($lostMuzaki) ?> Muzaki</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-clean table-hover mb-0">
                <thead>
                    <tr>
                        <th width="50" class="text-center">#</th>
                        <th>Identitas Muzaki</th>
                        <th>UPZ</th>
                        <th class="text-right">Nominal Terakhir (A)</th>
                        <th class="text-center">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach ($lostMuzaki as $m): ?>
                    <tr>
                        <td class="text-center text-muted small"><?= $no++ ?></td>
                        <td>
                            <div class="font-weight-bold text-dark" style="font-size: 1.05rem;"><?= clean($m['nama_muzaki']) ?></div>
                            <small class="text-muted">ID: #<?= $m['id'] ?></small>
                        </td>
                        <td><span class="text-muted small"><i class="fas fa-building mr-1 text-success"></i> <?= clean($m['nama_upz'] ?? 'Individu / Umum') ?></span></td>
                        <td class="text-right font-weight-bold text-danger" style="font-size: 1.05rem;"><?= format_currency($m['last_amount']) ?></td>
                        <td class="text-center">
                            <a href="<?= url('pages/muzaki/index.php?search=' . urlencode($m['nama_muzaki'])) ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                                <i class="fas fa-eye mr-1"></i> Tindak Lanjut
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($lostMuzaki)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Semua muzaki bulan lalu telah aktif kembali.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row">
    <!-- 6. LOST UPZ -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white border-0 pt-4 px-4" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="font-weight-bold mb-0 text-success"><i class="fas fa-university mr-2"></i> UPZ BELUM MENYETOR PERIODE INI</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-clean table-hover mb-0">
                    <thead>
                        <tr style="border-bottom: 1px solid #b8b8b8;">
                            <th>Nama UPZ</th>
                            <th class="text-right">Nominal Setoran Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lostUpz as $u): ?>
                        <tr>
                            <td class="font-weight-bold text-dark"><?= clean($u['nama_upz']) ?></td>
                            <td class="text-right text-danger font-weight-bold"><?= format_currency($u['last_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lostUpz)): ?>
                        <tr><td colspan="2" class="text-center py-4 text-muted small">Semua UPZ telah menyetor kembali.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- 7. CATEGORY SHIFT -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white border-0 pt-4 px-4" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="font-weight-bold mb-0 text-success"><i class="fas fa-chart-pie mr-2"></i> PERTUMBUHAN PER JENIS ZIS</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-clean table-hover mb-0">
                    <thead>
                        <tr style="border-bottom: 1px solid #b8b8b8;">
                            <th>Jenis Pembayaran</th>
                            <th class="text-right">Pertumbuhan (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoryShift as $c): 
                            $diff = (float)$c['total_B'] - (float)$c['total_A'];
                            $pct = $c['total_A'] > 0 ? ($diff / $c['total_A']) * 100 : ($diff > 0 ? 100 : 0);
                        ?>
                        <tr>
                            <td class="font-weight-bold text-dark"><?= clean($c['nama_jenis']) ?></td>
                            <td class="text-right">
                                <span class="badge <?= $diff >= 0 ? 'badge-success' : 'badge-danger' ?> p-2 px-3">
                                    <i class="fas fa-arrow-<?= $diff >= 0 ? 'up' : 'down' ?> mr-1"></i> <?= abs(round($pct, 1)) ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var compareOptions = {
        series: [{
            name: '<?= format_period($periodA) ?>',
            data: <?= json_encode(array_map('floatval', array_column($categoryShift, 'total_A'))) ?>
        }, {
            name: '<?= format_period($periodB) ?>',
            data: <?= json_encode(array_map('floatval', array_column($categoryShift, 'total_B'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%',
                borderRadius: 4
            },
        },
        colors: ['#b8b8b8', '#0d9f4f'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: <?= json_encode(array_column($categoryShift, 'nama_jenis')) ?>,
            axisBorder: { show: true, color: '#b8b8b8' }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        fill: {
            opacity: 1
        },
        grid: {
            borderColor: '#e5e5e5',
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right'
        }
    };

    var compareChart = new ApexCharts(document.querySelector("#compareApexChart"), compareOptions);
    compareChart.render();
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
