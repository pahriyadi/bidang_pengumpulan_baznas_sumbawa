<?php
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/kinerja_db.php';
require_once __DIR__ . '/config/dokumen_db.php';
require_auth();

$activeMenu = 'dashboard';
$pageTitle = 'Dashboard';
$pageDescription = 'Ringkasan aktivitas pengumpulan Zakat, Infak, dan Sedekah';

$conn = db();
ensure_kinerja_tables($conn);
ensure_dokumen_tables($conn);

// Stats - Combined for performance
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM muzaki) as totalMuzaki,
        (SELECT COUNT(*) FROM upz) as totalUPZ,
        (SELECT COUNT(*) FROM users) as totalUsers,
        COUNT(*) as totalTransaksi,
        COALESCE(SUM(jumlah_pembayaran), 0) as totalPembayaran
    FROM transaksi
")->fetch_assoc();

$totalMuzaki = $stats['totalMuzaki'] ?? 0;
$totalTransaksi = $stats['totalTransaksi'] ?? 0;
$totalPembayaran = $stats['totalPembayaran'] ?? 0;
$totalUPZ = $stats['totalUPZ'] ?? 0;
$totalUsers = $stats['totalUsers'] ?? 0;

// Monthly stats for current year
$currentYear = (int) date('Y');

// Monthly grand total (for area total line)
$monthlyStats = $conn->query("
    SELECT MONTH(tanggal_transaksi) as bulan, SUM(jumlah_pembayaran) as total
    FROM transaksi WHERE YEAR(tanggal_transaksi) = {$currentYear}
    GROUP BY MONTH(tanggal_transaksi) ORDER BY bulan
");

// Monthly breakdown per jenis pembayaran
$monthlyJenisStats = $conn->query("
    SELECT jp.nama_jenis, MONTH(t.tanggal_transaksi) as bulan,
           COALESCE(SUM(t.jumlah_pembayaran), 0) as total
    FROM transaksi t
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    WHERE YEAR(t.tanggal_transaksi) = {$currentYear}
    GROUP BY t.id_jenis_pembayaran, jp.nama_jenis, MONTH(t.tanggal_transaksi)
    ORDER BY jp.nama_jenis, bulan
");

// Stats by jenis pembayaran (for donut chart on right side - keeping for reference)
$jenisStats = $conn->query("
    SELECT jp.nama_jenis, COALESCE(SUM(t.jumlah_pembayaran), 0) as total
    FROM transaksi t LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    GROUP BY t.id_jenis_pembayaran, jp.nama_jenis ORDER BY total DESC
");

// Recent transactions
$recentTransaksi = $conn->query("
    SELECT t.*, 
        COALESCE(m.nama_muzaki, u.nama_upz, 'UMUM') as nama_muzaki, 
        jp.nama_jenis, mp.nama_metode
    FROM transaksi t
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
    ORDER BY t.tanggal_transaksi DESC, t.id DESC LIMIT 10
");

// Prepare chart data
$monthLabels = [];
$monthData = []; // grand total per month
for ($i = 1; $i <= 12; $i++) {
    $monthLabels[] = date('M', mktime(0, 0, 0, $i, 1));
    $monthData[$i] = 0;
}
if ($monthlyStats) {
    while ($row = $monthlyStats->fetch_assoc()) {
        $monthData[(int)$row['bulan']] = (float)$row['total'];
    }
}
$monthData = array_values($monthData);

// Per-jenis monthly series
$jenisSeries = []; // [ 'Zakat' => [0,0,...12 values], 'Infak' => [...] ]
if ($monthlyJenisStats) {
    while ($row = $monthlyJenisStats->fetch_assoc()) {
        $nama = $row['nama_jenis'] ?? 'Lainnya';
        if (!isset($jenisSeries[$nama])) {
            $jenisSeries[$nama] = array_fill(0, 12, 0);
        }
        $jenisSeries[$nama][(int)$row['bulan'] - 1] = (float)$row['total'];
    }
}

$jenisLabels = [];
$jenisData = [];
if ($jenisStats) {
    while ($row = $jenisStats->fetch_assoc()) {
        $jenisLabels[] = $row['nama_jenis'] ?? 'Tidak Diketahui';
        $jenisData[] = (float) $row['total'];
    }
}

$kinerjaTahun = $currentYear;
$kinerjaTarget = 0.0;
$kPenerimaan = kinerja_sql_and_transaksi_penerimaan($conn, 't');
$kinerjaCapaian = (float) ($conn->query("SELECT COALESCE(SUM(t.jumlah_pembayaran),0) AS t FROM transaksi t WHERE YEAR(t.tanggal_transaksi) = {$kinerjaTahun} {$kPenerimaan}")->fetch_assoc()['t'] ?? 0);
$kt = $conn->prepare('SELECT nominal_target FROM target_pengumpulan_tahunan WHERE tahun = ? LIMIT 1');
$kt->bind_param('i', $kinerjaTahun);
$kt->execute();
$ktr = $kt->get_result()->fetch_assoc();
$kt->close();
if ($ktr) {
    $kinerjaTarget = (float) $ktr['nominal_target'];
}
$kinerjaPct = $kinerjaTarget > 0 ? min(100, round(($kinerjaCapaian / $kinerjaTarget) * 100, 1)) : null;

include __DIR__ . '/inc/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


<!-- Stats Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?= number_format($totalTransaksi) ?></h3>
                <p>Total Transaksi</p>
            </div>
            <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            <a href="<?= url('pages/transaksi/index.php') ?>" class="small-box-footer">Selengkapnya <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp <?= number_format($totalPembayaran, 0, ',', '.') ?></h3>
                <p>Total Pembayaran</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            <a href="<?= url('pages/transaksi/index.php') ?>" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= number_format($totalMuzaki) ?></h3>
                <p>Total Muzaki</p>
            </div>
            <div class="icon"><i class="fas fa-user-tie"></i></div>
            <a href="<?= url('pages/muzaki/index.php') ?>" class="small-box-footer">Kelola Data <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= number_format($totalUPZ) ?></h3>
                <p>Total UPZ</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
            <a href="<?= url('pages/upz/index.php') ?>" class="small-box-footer">Kelola Data <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Target vs capaian tahun berjalan -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-danger shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Kinerja Pengumpulan Tahun
                    <?= $kinerjaTahun ?></h3>
                <div class="card-tools">
                    <a href="<?= url('pages/kinerja/index.php?tahun=' . $kinerjaTahun) ?>"
                        class="btn btn-sm btn-primary">
                        <i class="fas fa-cog mr-1"></i>Kelola Target
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <p class="text-muted mb-1 small">Target tahunan</p>
                        <h4 class="mb-0">
                            <?= $kinerjaTarget > 0 ? format_currency($kinerjaTarget) : '<span class="text-muted">Belum diatur</span>' ?>
                        </h4>
                    </div>
                    <div class="col-md-4">
                        <p class="text-muted mb-1 small">Capaian (kategori <strong>Penerimaan</strong>)</p>
                        <h4 class="mb-0 text-success"><?= format_currency($kinerjaCapaian) ?></h4>
                    </div>
                    <div class="col-md-4">
                        <?php if ($kinerjaPct !== null): ?>
                            <p class="text-muted mb-1 small">Pencapaian</p>
                            <div class="progress progress-lg">
                                <div class="progress-bar <?= $kinerjaCapaian >= $kinerjaTarget ? 'bg-success' : 'bg-info' ?>"
                                    style="width: <?= $kinerjaPct ?>%"><?= $kinerjaPct ?>%</div>
                            </div>
                            <?php if ($kinerjaCapaian >= $kinerjaTarget): ?>
                                <small class="text-success font-weight-bold">Target tercapai</small>
                            <?php else: ?>
                                <small class="text-muted">Sisa <?= format_currency($kinerjaTarget - $kinerjaCapaian) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Atur target di halaman Kinerja Pengumpulan untuk melihat
                                persentase.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ANALISIS RINGKAS -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm" style="border-left: 3px solid #0d9f4f;">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-9">
                        <h5 class="font-weight-bold mb-1"><i class="fas fa-chart-pie mr-2 text-success"></i>Analisis
                            Strategis Pengumpulan</h5>
                        <p class="text-muted mb-0 small">Pantau komposisi jenis dana, efektivitas metode pembayaran, dan
                            peringkat UPZ secara mendetail.</p>
                    </div>
                    <div class="col-md-3 text-right">
                        <a href="<?= url('pages/analisis/index.php') ?>" class="btn btn-outline-success">
                            <i class="fas fa-external-link-alt mr-1"></i>Buka Analisis Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-chart-line mr-2 text-success"></i>Tren Transaksi Bulanan (<?= $currentYear ?>)</h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-center">
                <div id="monthlyApexChart" style="min-height: 350px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-bullseye mr-2 text-success"></i>Target vs Realisasi</h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <div id="targetApexChart" style="min-height: 250px; width: 100%;"></div>
                <div class="text-center mt-2">
                    <p class="text-muted mb-1 small">Target Kerja: <strong><?= $kinerjaTarget > 0 ? format_currency($kinerjaTarget) : 'Belum diatur' ?></strong></p>
                    <p class="text-success mb-0 font-weight-bold">Realisasi Capaian: <strong><?= format_currency($kinerjaCapaian) ?></strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header bg-white" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-clock mr-2 text-success"></i>Transaksi Terbaru</h3>
                <div class="card-tools">
                    <a href="<?= url('pages/transaksi/index.php') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-list mr-1"></i>Lihat Semua
                    </a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Muzaki</th>
                            <th>Jenis</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentTransaksi && $recentTransaksi->num_rows > 0): ?>
                            <?php while ($row = $recentTransaksi->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal_transaksi'])) ?></td>
                                    <td><?= clean($row['nama_muzaki'] ?? '-') ?></td>
                                    <td><span class="badge badge-primary"><?= clean($row['nama_jenis'] ?? '-') ?></span></td>
                                    <td><span class="badge badge-secondary"><?= clean($row['nama_metode'] ?? '-') ?></span></td>
                                    <td class="text-right"><strong
                                            class="currency-display"><?= format_currency($row['jumlah_pembayaran']) ?></strong>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4"><i
                                        class="fas fa-inbox fa-2x mb-2 d-block"></i>Belum ada transaksi</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-user-shield"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pengguna Sistem</span>
                <span class="info-box-number"><?= $totalUsers ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-tags"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Jenis Pembayaran</span>
                <span class="info-box-number"><?= count(get_jenis_pembayaran_list()) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-credit-card"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Metode Pembayaran</span>
                <span class="info-box-number"><?= count(get_metode_pembayaran_list()) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-id-badge"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Status Muzaki</span>
                <span class="info-box-number"><?= count(get_status_muzaki_list()) ?></span>
            </div>
        </div>
    </div>
</div>

<script>
    // ApexCharts: Tren Bulanan per Jenis Pembayaran + Area Total
    <?php
    // Build JS series array: stacked columns per jenis + area line for total
    $jenisSeriesJS = [];
    $jenisPalette = ['#0d9f4f', '#076e34', '#ffc107', '#17a2b8', '#64748b', '#334155', '#94a3b8'];
    $colorIdx = 0;
    foreach ($jenisSeries as $nama => $values) {
        $jenisSeriesJS[] = [
            'name'   => $nama,
            'type'   => 'column',
            'data'   => $values,
            'color'  => $jenisPalette[$colorIdx % count($jenisPalette)]
        ];
        $colorIdx++;
    }
    // Add area line for grand total
    $jenisSeriesJS[] = [
        'name'  => 'Total Keseluruhan',
        'type'  => 'line',
        'data'  => $monthData,
        'color' => '#0d9f4f'
    ];
    ?>
    var monthlyOptions = {
        series: <?= json_encode(array_values($jenisSeriesJS)) ?>,
        chart: {
            height: 350,
            type: 'line',
            stacked: false,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        stroke: {
            width: <?php
                $widths = array_fill(0, count($jenisSeries), 0);
                $widths[] = 3; // area line
                echo json_encode($widths);
            ?>,
            curve: 'smooth'
        },
        fill: {
            opacity: <?php
                $opacities = array_fill(0, count($jenisSeries), 0.85);
                $opacities[] = 0; // line no fill
                echo json_encode($opacities);
            ?>
        },
        plotOptions: {
            bar: {
                columnWidth: '55%',
                borderRadius: 2
            }
        },
        labels: <?= json_encode($monthLabels) ?>,
        xaxis: {
            type: 'category',
            axisBorder: { show: true, color: '#b8b8b8' },
            axisTicks: { show: true, color: '#b8b8b8' }
        },
        yaxis: {
            labels: {
                formatter: function (value) {
                    return "Rp " + value.toLocaleString('id-ID');
                }
            }
        },
        grid: {
            borderColor: '#e5e5e5',
            strokeDashArray: 4
        },
        tooltip: {
            shared: true,
            intersect: false,
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

    var monthlyChart = new ApexCharts(document.querySelector("#monthlyApexChart"), monthlyOptions);
    monthlyChart.render();

    // ApexCharts: Target vs Realisasi (Radial Bar / Gauge)
    var pct = <?= $kinerjaPct !== null ? $kinerjaPct : 0 ?>;
    var targetOptions = {
        series: [pct],
        chart: {
            height: 280,
            type: 'radialBar',
            fontFamily: 'Inter, sans-serif'
        },
        colors: [pct >= 100 ? '#0d9f4f' : '#28a745'],
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                dataLabels: {
                    name: {
                        fontSize: '14px',
                        color: '#6c757d',
                        offsetY: 85
                    },
                    value: {
                        offsetY: 45,
                        fontSize: '30px',
                        fontWeight: 'bold',
                        color: '#212529',
                        formatter: function (val) {
                            return val + "%";
                        }
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                shadeIntensity: 0.15,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 50, 65, 91, 100]
            },
        },
        stroke: {
            dashArray: 4
        },
        labels: ['Pencapaian'],
    };

    var targetChart = new ApexCharts(document.querySelector("#targetApexChart"), targetOptions);
    targetChart.render();
</script>

<?php include __DIR__ . '/inc/footer.php'; ?>