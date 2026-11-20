<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

$activeMenu = 'analisis';
$pageTitle = 'Analisis Eksekutif Pengumpulan ZIS';
$pageDescription = 'Dashboard analitik komprehensif On Balance Sheet, Off Balance Sheet, dan SKZ untuk presentasi pimpinan';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Analisis Data ZIS</li>';

$conn = db();
ensure_kinerja_tables($conn);
ensure_skz_table($conn);

// --- Advanced Filtering Logic ---
$filter_type = $_GET['filter_type'] ?? 'year';
$year = (int)($_GET['year'] ?? date('Y'));
if ($year < 2000 || $year > 2100) $year = (int)date('Y');
$month = $_GET['month'] ?? date('Y-m');
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

$where_on = "";
$where_off = "";
$where_skz = "";
$params = [];
$types = "";
$target_year = $year;

if ($filter_type === 'month') {
    $where_on = "WHERE DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = ?";
    $where_off = "WHERE DATE_FORMAT(ob.tanggal_transaksi, '%Y-%m') = ?";
    $where_skz = "WHERE DATE_FORMAT(s.tanggal_surat, '%Y-%m') = ?";
    $params[] = $month;
    $types = "s";
    $tsMonth = strtotime($month . '-01');
    $label_period = month_name((int)date('n', $tsMonth)) . ' ' . date('Y', $tsMonth);
    $target_year = (int)date('Y', $tsMonth);
} elseif ($filter_type === 'range') {
    $where_on = "WHERE t.tanggal_transaksi BETWEEN ? AND ?";
    $where_off = "WHERE ob.tanggal_transaksi BETWEEN ? AND ?";
    $where_skz = "WHERE s.tanggal_surat BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types = "ss";
    $label_period = date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date));
    $target_year = (int)date('Y', strtotime($end_date));
} else {
    $where_on = "WHERE YEAR(t.tanggal_transaksi) = ?";
    $where_off = "WHERE YEAR(ob.tanggal_transaksi) = ?";
    $where_skz = "WHERE YEAR(s.tanggal_surat) = ?";
    $params[] = (string)$year;
    $types = "s";
    $label_period = "Tahun " . $year;
    $target_year = $year;
}

$activeDanaMode = get_dana_mode();
$danaCond = get_dana_mode_sql_condition($activeDanaMode, 't', $conn);
$where_on_all_penerimaan = $where_on;
$where_on_pengumpulan = $where_on . " AND " . get_dana_mode_sql_condition('pengumpulan', 't', $conn);
$where_on_titipan = $where_on . " AND " . get_titipan_penyaluran_sql_condition('t', $conn);

if ($danaCond !== '1=1') {
    $where_on .= " AND " . $danaCond;
}

function fetch_analytics(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

// =========================================================================
// 1. RINGKASAN EKSEKUTIF (ON BALANCE, OFF BALANCE, SKZ, TARGET RKAT)
// =========================================================================
$sumOnRow = fetch_analytics($conn, "
    SELECT
        COUNT(*) AS total_trx,
        COALESCE(SUM(t.jumlah_pembayaran), 0) AS total_nominal,
        COALESCE(AVG(t.jumlah_pembayaran), 0) AS avg_nominal,
        COUNT(DISTINCT t.id_muzaki) AS unique_muzaki,
        COUNT(DISTINCT t.id_upz) AS unique_upz
    FROM transaksi t
    {$where_on}
", $types, $params)[0] ?? [];

$totalOnTrx = (int)($sumOnRow['total_trx'] ?? 0);
$totalOnNominal = (float)($sumOnRow['total_nominal'] ?? 0);
$avgOnNominal = (float)($sumOnRow['avg_nominal'] ?? 0);
$uniqueMuzakiOn = (int)($sumOnRow['unique_muzaki'] ?? 0);
$uniqueUpzOn = (int)($sumOnRow['unique_upz'] ?? 0);

// Penerimaan vs Pengumpulan vs Titipan breakdown on the same period
$sumPenerimaanAll = (float)(fetch_analytics($conn, "SELECT COALESCE(SUM(t.jumlah_pembayaran),0) AS tot FROM transaksi t {$where_on_all_penerimaan}", $types, $params)[0]['tot'] ?? 0);
$sumPengumpulanMurni = (float)(fetch_analytics($conn, "SELECT COALESCE(SUM(t.jumlah_pembayaran),0) AS tot FROM transaksi t {$where_on_pengumpulan}", $types, $params)[0]['tot'] ?? 0);
$sumTitipanPenyaluran = (float)(fetch_analytics($conn, "SELECT COALESCE(SUM(t.jumlah_pembayaran),0) AS tot FROM transaksi t {$where_on_titipan}", $types, $params)[0]['tot'] ?? 0);

// Off Balance summary
$sumOffRow = fetch_analytics($conn, "
    SELECT
        COUNT(*) AS total_lap,
        COALESCE(SUM(ob.nominal), 0) AS total_nominal,
        COALESCE(SUM(ob.jumlah_muzaki), 0) AS total_muzaki,
        COALESCE(SUM(ob.jumlah_mustahik), 0) AS total_mustahik
    FROM transaksi_off_balance ob
    {$where_off}
", $types, $params)[0] ?? [];

$totalOffLap = (int)($sumOffRow['total_lap'] ?? 0);
$totalOffNominal = (float)($sumOffRow['total_nominal'] ?? 0);
$totalOffMuzaki = (int)($sumOffRow['total_muzaki'] ?? 0);
$totalOffMustahik = (int)($sumOffRow['total_mustahik'] ?? 0);

// SKZ & BSZIS summary
$sumSkzRow = fetch_analytics($conn, "
    SELECT
        COUNT(*) AS total_skz,
        COALESCE(SUM(s.nilai_kontrak), 0) AS total_kontrak,
        COALESCE(SUM(s.estimasi_keuntungan), 0) AS total_laba,
        COALESCE(SUM(s.nilai_zis), 0) AS total_zis
    FROM surat_ketetapan_zakat s
    {$where_skz}
", $types, $params)[0] ?? [];

$totalSkzCount = (int)($sumSkzRow['total_skz'] ?? 0);
$totalSkzKontrak = (float)($sumSkzRow['total_kontrak'] ?? 0);
$totalSkzZis = (float)($sumSkzRow['total_zis'] ?? 0);

// Target RKAT for target_year
$tgtRow = fetch_analytics($conn, "SELECT nominal_target, nominal_target_off_balance FROM target_pengumpulan_tahunan WHERE tahun = ? LIMIT 1", "i", [$target_year])[0] ?? [];
$targetOnRkat = (float)($tgtRow['nominal_target'] ?? 0);
$targetOffRkat = (float)($tgtRow['nominal_target_off_balance'] ?? 0);
$targetTotalRkat = $targetOnRkat + $targetOffRkat;

$totalGabunganDaerah = $totalOnNominal + $totalOffNominal;
$pctOnRkat = $targetOnRkat > 0 ? round(($totalOnNominal / $targetOnRkat) * 100, 1) : null;
$pctOffRkat = $targetOffRkat > 0 ? round(($totalOffNominal / $targetOffRkat) * 100, 1) : null;
$pctTotalRkat = $targetTotalRkat > 0 ? round(($totalGabunganDaerah / $targetTotalRkat) * 100, 1) : null;

// =========================================================================
// 2. TREN BULANAN MULTI-SERIES (ON BALANCE, OFF BALANCE, & PER JENIS DANA)
// =========================================================================
$monthlyOn = array_fill(1, 12, 0.0);
$trendOnRaw = fetch_analytics($conn, "SELECT MONTH(t.tanggal_transaksi) AS bln, SUM(t.jumlah_pembayaran) AS total FROM transaksi t {$where_on} GROUP BY MONTH(t.tanggal_transaksi)", $types, $params);
foreach ($trendOnRaw as $r) $monthlyOn[(int)$r['bln']] = (float)$r['total'];

$monthlyOff = array_fill(1, 12, 0.0);
$trendOffRaw = fetch_analytics($conn, "SELECT MONTH(ob.tanggal_transaksi) AS bln, SUM(ob.nominal) AS total FROM transaksi_off_balance ob {$where_off} GROUP BY MONTH(ob.tanggal_transaksi)", $types, $params);
foreach ($trendOffRaw as $r) $monthlyOff[(int)$r['bln']] = (float)$r['total'];

// Monthly stacked per Jenis Pembayaran (On Balance)
$jenisListMaster = get_jenis_pembayaran_list();
$monthlyJenisStacked = [];
foreach ($jenisListMaster as $jm) {
    $monthlyJenisStacked[$jm['nama_jenis']] = array_fill(1, 12, 0.0);
}
$trendJenisRaw = fetch_analytics($conn, "
    SELECT jp.nama_jenis, MONTH(t.tanggal_transaksi) AS bln, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    {$where_on}
    GROUP BY jp.id, jp.nama_jenis, MONTH(t.tanggal_transaksi)
", $types, $params);
foreach ($trendJenisRaw as $r) {
    $jn = $r['nama_jenis'];
    if (!isset($monthlyJenisStacked[$jn])) {
        $monthlyJenisStacked[$jn] = array_fill(1, 12, 0.0);
    }
    $monthlyJenisStacked[$jn][(int)$r['bln']] = (float)$r['total'];
}

// =========================================================================
// 3. PERBANDINGAN TAHUNAN (YOY - 5 TAHUN TERAKHIR)
// =========================================================================
$yoyCond = get_dana_mode_sql_condition($activeDanaMode, 't', $conn);
$yoyOnRaw = fetch_analytics($conn, "
    SELECT YEAR(t.tanggal_transaksi) AS th, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    WHERE {$yoyCond} AND YEAR(t.tanggal_transaksi) >= YEAR(CURDATE()) - 4
    GROUP BY YEAR(t.tanggal_transaksi)
    ORDER BY th ASC
");
$yoyOffRaw = fetch_analytics($conn, "
    SELECT YEAR(ob.tanggal_transaksi) AS th, SUM(ob.nominal) AS total
    FROM transaksi_off_balance ob
    WHERE YEAR(ob.tanggal_transaksi) >= YEAR(CURDATE()) - 4
    GROUP BY YEAR(ob.tanggal_transaksi)
    ORDER BY th ASC
");
$yoyYearsMap = [];
for ($y = (int)date('Y') - 4; $y <= (int)date('Y'); $y++) {
    $yoyYearsMap[$y] = ['on' => 0.0, 'off' => 0.0];
}
foreach ($yoyOnRaw as $r) {
    $th = (int)$r['th'];
    if (isset($yoyYearsMap[$th])) $yoyYearsMap[$th]['on'] = (float)$r['total'];
}
foreach ($yoyOffRaw as $r) {
    $th = (int)$r['th'];
    if (isset($yoyYearsMap[$th])) $yoyYearsMap[$th]['off'] = (float)$r['total'];
}

// =========================================================================
// 4. KOMPOSISI JENIS, SUB-JENIS, METODE, KATEGORI DANA & BANK
// =========================================================================
$jenisData = fetch_analytics($conn, "
    SELECT jp.nama_jenis, COUNT(*) AS trx_count, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    {$where_on}
    GROUP BY jp.id, jp.nama_jenis
    ORDER BY total DESC
", $types, $params);

$subJenisData = fetch_analytics($conn, "
    SELECT
        COALESCE(jp.nama_jenis, 'Lainnya') AS nama_jenis,
        COALESCE(NULLIF(sjp.nama_sub_jenis, ''), 'Umum / Tanpa Sub-Jenis') AS nama_sub_jenis,
        COUNT(*) AS trx_count,
        SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    {$where_on}
    GROUP BY t.id_jenis_pembayaran, t.id_sub_jenis_pembayaran, jp.nama_jenis, sjp.nama_sub_jenis
    ORDER BY total DESC
    LIMIT 12
", $types, $params);

$metodeData = fetch_analytics($conn, "
    SELECT mp.nama_metode, COUNT(*) AS trx_count, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
    {$where_on}
    GROUP BY mp.id, mp.nama_metode
    ORDER BY total DESC
", $types, $params);

$bankData = fetch_analytics($conn, "
    SELECT COALESCE(sr.nama_sumber, 'TUNAI / KAS') AS nama_sumber, COUNT(*) AS trx_count, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
    {$where_on}
    GROUP BY t.id_sumber_rekening, sr.nama_sumber
    ORDER BY total DESC
", $types, $params);

// Kategori Dana
$katDataRaw = fetch_analytics($conn, "SELECT t.id_kategori_dana, t.jumlah_pembayaran FROM transaksi t {$where_on}", $types, $params);
$kategoriList = get_kategori_dana_list();
$kategoriStats = [];
foreach ($kategoriList as $k) {
    $kategoriStats[$k['id']] = ['nama' => $k['nama_kategori'], 'total' => 0.0, 'count' => 0];
}
foreach ($katDataRaw as $r) {
    $ids = parse_kategori_dana_ids($r['id_kategori_dana']);
    foreach ($ids as $id) {
        if (isset($kategoriStats[$id])) {
            $kategoriStats[$id]['total'] += (float)$r['jumlah_pembayaran'];
            $kategoriStats[$id]['count']++;
        }
    }
}
uasort($kategoriStats, function($a, $b) { return $b['total'] <=> $a['total']; });

// =========================================================================
// 5. TOP 10 UPZ, TOP 10 MUZAKI, & SEKTORAL UPZ
// =========================================================================
$topUPZ = fetch_analytics($conn, "
    SELECT u.nama_upz, COALESCE(ku.nama_kategori, 'Umum') AS kategori_upz, COUNT(*) AS trx_count, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    JOIN upz u ON t.id_upz = u.id
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
    {$where_on}
    GROUP BY u.id, u.nama_upz, ku.nama_kategori
    ORDER BY total DESC
    LIMIT 10
", $types, $params);

$topMuzaki = fetch_analytics($conn, "
    SELECT m.nama_muzaki, COALESCE(u.nama_upz, 'Muzaki Individu') AS nama_upz, COUNT(*) AS trx_count, SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON COALESCE(t.id_upz, m.id_upz_sekarang) = u.id
    {$where_on}
    GROUP BY m.id, m.nama_muzaki, u.nama_upz
    ORDER BY total DESC
    LIMIT 10
", $types, $params);

$kategoriUPZData = fetch_analytics($conn, "
    SELECT
        COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') AS nama_kategori,
        COUNT(*) AS trx_count,
        SUM(t.jumlah_pembayaran) AS total
    FROM transaksi t
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN kategori_upz ku_m ON upz_m.id_kategori_upz = ku_m.id
    {$where_on}
    GROUP BY COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ')
    ORDER BY total DESC
", $types, $params);

// =========================================================================
// 6. HIGHLIGHT NARASI OTOMATIS UNTUK PRESENTASI PIMPINAN
// =========================================================================
$peakMonthNum = 1;
$peakMonthVal = 0.0;
$activeMonthsCount = 0;
foreach ($monthlyOn as $mNum => $mVal) {
    if ($mVal > 0) $activeMonthsCount++;
    if ($mVal > $peakMonthVal) {
        $peakMonthVal = $mVal;
        $peakMonthNum = $mNum;
    }
}
$avgPerActiveMonth = $activeMonthsCount > 0 ? ($totalOnNominal / $activeMonthsCount) : 0.0;
$topJenisName = $jenisData[0]['nama_jenis'] ?? 'Belum ada data';
$topJenisVal = (float)($jenisData[0]['total'] ?? 0);
$topJenisPct = $totalOnNominal > 0 ? round(($topJenisVal / $totalOnNominal) * 100, 1) : 0;

$topSektorName = $kategoriUPZData[0]['nama_kategori'] ?? 'Belum ada data';
$topSektorVal = (float)($kategoriUPZData[0]['total'] ?? 0);
$topSektorPct = $totalOnNominal > 0 ? round(($topSektorVal / $totalOnNominal) * 100, 1) : 0;

$topUpzName = $topUPZ[0]['nama_upz'] ?? 'Belum ada data';
$topUpzVal = (float)($topUPZ[0]['total'] ?? 0);

include __DIR__ . '/../../inc/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
/* Print & Executive Presentation Styles */
.exec-kpi-card {
    border: 1px solid #b8b8b8;
    border-radius: 6px;
    padding: 14px 16px;
    background: #ffffff;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.exec-kpi-card .kpi-label {
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    color: #64748b;
}
.exec-kpi-card .kpi-value {
    font-size: clamp(1.15rem, 1.45vw, 1.5rem);
    font-weight: 800;
    margin: 4px 0;
    white-space: nowrap;
}
.exec-kpi-card .kpi-sub {
    font-size: 0.78rem;
    color: #6c757d;
    border-top: 1px dashed #d1d5db;
    padding-top: 6px;
    margin-top: 6px;
}
.insight-pill {
    border: 1px solid #b8b8b8;
    border-left: 4px solid #0d9f4f;
    border-radius: 6px;
    padding: 10px 14px;
    background: #ffffff;
    height: 100%;
}
body.dark-mode .exec-kpi-card,
body.dark-mode .insight-pill {
    background: #1f2328 !important;
    border-color: #374151 !important;
    color: #e5e7eb !important;
}
body.dark-mode .exec-kpi-card .kpi-sub {
    border-top-color: #374151 !important;
    color: #9ca3af !important;
}
@media print {
    .main-sidebar, .main-header, .main-footer, .no-print {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        background: #ffffff !important;
    }
    .card {
        break-inside: avoid;
    }
}
</style>

<!-- Filter & Action Toolbar (No-Print) -->
<div class="row mb-3 no-print">
    <div class="col-md-12">
        <div class="card shadow-none mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-body py-2 px-3">
                <form method="GET" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0">
                            <label class="small font-weight-bold mb-1"><i class="fas fa-filter mr-1 text-success"></i>Cakupan Waktu</label>
                            <select name="filter_type" id="filter_type" class="form-control form-control-sm" onchange="toggleFilterInputs()">
                                <option value="year" <?= $filter_type === 'year' ? 'selected' : '' ?>>Tahun Penuh</option>
                                <option value="month" <?= $filter_type === 'month' ? 'selected' : '' ?>>Bulan Spesifik</option>
                                <option value="range" <?= $filter_type === 'range' ? 'selected' : '' ?>>Rentang Tanggal</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0 filter-input" id="input_year" style="<?= $filter_type !== 'year' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold mb-1">Pilih Tahun</label>
                            <select name="year" class="form-control form-control-sm">
                                <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0 filter-input" id="input_month" style="<?= $filter_type !== 'month' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold mb-1">Pilih Bulan</label>
                            <input type="month" name="month" class="form-control form-control-sm" value="<?= clean($month) ?>">
                        </div>

                        <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0 filter-input" id="input_start" style="<?= $filter_type !== 'range' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold mb-1">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= clean($start_date) ?>">
                        </div>
                        <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0 filter-input" id="input_end" style="<?= $filter_type !== 'range' ? 'display:none' : '' ?>">
                            <label class="small font-weight-bold mb-1">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= clean($end_date) ?>">
                        </div>

                        <div class="col-lg-2 col-md-3 col-6 mb-2 mb-lg-0">
                            <button type="submit" class="btn btn-outline-success btn-sm btn-block">
                                <i class="fas fa-sync-alt mr-1"></i>Terapkan Analisis
                            </button>
                        </div>

                        <div class="col-lg-4 col-md-12 ml-auto text-lg-right mt-2 mt-lg-0">
                            <span class="badge badge-secondary p-2 mr-1">
                                <i class="fas fa-calendar-check mr-1 text-success"></i>Periode: <?= clean($label_period) ?>
                            </span>
                            <a href="<?= url('pages/analisis/perbandingan.php') ?>" class="btn btn-outline-info btn-sm mr-1">
                                <i class="fas fa-balance-scale mr-1"></i>Perbandingan
                            </a>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                                <i class="fas fa-print mr-1"></i>Cetak Presentasi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Ringkasan Total Kinerja Daerah (On + Off Balance) -->
<div class="card shadow-none mb-3" style="border: 1px solid #b8b8b8;">
    <div class="card-body py-2 px-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="small text-muted">
                <i class="fas fa-calendar-check mr-1 text-success"></i>Cakupan Analisis: <strong><?= clean($label_period) ?></strong>
                <span class="mx-2">|</span>
                Basis On Balance: <strong><?= $activeDanaMode === 'pengumpulan' ? 'Pengumpulan Murni (Tanpa Titipan Penyaluran)' : 'Total Penerimaan (Semua Dana)' ?></strong>
            </div>
            <div class="d-flex align-items-center mt-1 mt-md-0">
                <span class="small text-muted mr-2">Total Kinerja Pengumpulan Daerah (On + Off Balance):</span>
                <span class="h5 font-weight-bold text-success mb-0"><?= format_currency($totalGabunganDaerah) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- 4 Kartu KPI Eksekutif Utama -->
<div class="row">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="exec-kpi-card">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label"><i class="fas fa-university mr-1 text-success"></i>1. On Balance Sheet</span>
                    <span class="badge badge-success"><?= number_format($totalOnTrx, 0, ',', '.') ?> Trx</span>
                </div>
                <div class="kpi-value text-success" title="<?= format_currency($totalOnNominal) ?>"><?= format_currency($totalOnNominal) ?></div>
                <div class="small text-muted">
                    Target RKAT <?= $target_year ?>: <strong><?= $targetOnRkat > 0 ? format_currency($targetOnRkat) . ' (' . $pctOnRkat . '%)' : 'Belum Diatur' ?></strong>
                </div>
            </div>
            <div class="kpi-sub">
                <div class="d-flex justify-content-between">
                    <span>Total Penerimaan:</span>
                    <strong><?= format_currency($sumPenerimaanAll) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Pengumpulan Murni:</span>
                    <strong><?= format_currency($sumPengumpulanMurni) ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Titipan Penyaluran:</span>
                    <strong><?= format_currency($sumTitipanPenyaluran) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="exec-kpi-card">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label"><i class="fas fa-clipboard-list mr-1 text-primary"></i>2. Off Balance Sheet</span>
                    <span class="badge badge-info"><?= number_format($totalOffLap, 0, ',', '.') ?> BA</span>
                </div>
                <div class="kpi-value text-primary" title="<?= format_currency($totalOffNominal) ?>"><?= format_currency($totalOffNominal) ?></div>
                <div class="small text-muted">
                    Target Off <?= $target_year ?>: <strong><?= $targetOffRkat > 0 ? format_currency($targetOffRkat) . ' (' . $pctOffRkat . '%)' : 'Belum Diatur' ?></strong>
                </div>
            </div>
            <div class="kpi-sub">
                <div class="d-flex justify-content-between">
                    <span>Muzaki Tercatat:</span>
                    <strong><?= number_format($totalOffMuzaki, 0, ',', '.') ?> Jiwa</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Mustahik Terbantu:</span>
                    <strong><?= number_format($totalOffMustahik, 0, ',', '.') ?> Jiwa</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Sumber:</span>
                    <strong>UPZ / Masjid / Desa</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="exec-kpi-card">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label"><i class="fas fa-bullseye mr-1 text-warning"></i>3. Total Gabungan RKAT</span>
                    <span class="badge badge-warning"><?= $pctTotalRkat !== null ? $pctTotalRkat . '% RKAT' : 'Gabungan' ?></span>
                </div>
                <div class="kpi-value" title="<?= format_currency($totalGabunganDaerah) ?>"><?= format_currency($totalGabunganDaerah) ?></div>
                <div class="small text-muted">
                    Total Target RKAT: <strong><?= $targetTotalRkat > 0 ? format_currency($targetTotalRkat) : 'Belum Diatur' ?></strong>
                </div>
            </div>
            <div class="kpi-sub">
                <?php
                $shareOn = $totalGabunganDaerah > 0 ? round(($totalOnNominal / $totalGabunganDaerah) * 100, 1) : 0;
                $shareOff = $totalGabunganDaerah > 0 ? round(($totalOffNominal / $totalGabunganDaerah) * 100, 1) : 0;
                ?>
                <div class="d-flex justify-content-between">
                    <span>Porsi On Balance:</span>
                    <strong><?= $shareOn ?>%</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Porsi Off Balance:</span>
                    <strong><?= $shareOff ?>%</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Rata-rata Bulanan On:</span>
                    <strong><?= format_currency($avgPerActiveMonth) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-3">
        <div class="exec-kpi-card">
            <div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label"><i class="fas fa-file-contract mr-1 text-info"></i>4. SKZ &amp; Partisipasi</span>
                    <span class="badge badge-secondary"><?= number_format($totalSkzCount, 0, ',', '.') ?> SKZ</span>
                </div>
                <div class="kpi-value text-info" title="<?= format_currency($totalSkzZis) ?>"><?= format_currency($totalSkzZis) ?></div>
                <div class="small text-muted">
                    Nilai Kontrak Proyek: <strong><?= format_currency($totalSkzKontrak) ?></strong>
                </div>
            </div>
            <div class="kpi-sub">
                <div class="d-flex justify-content-between">
                    <span>Muzaki Aktif (On):</span>
                    <strong><?= number_format($uniqueMuzakiOn, 0, ',', '.') ?> Muzaki</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>UPZ Penyetor Aktif:</span>
                    <strong><?= number_format($uniqueUpzOn, 0, ',', '.') ?> UPZ</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Rata-rata / Transaksi:</span>
                    <strong><?= format_currency($avgOnNominal) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sorotan Insight Otomatis untuk Presentasi Pimpinan -->
<div class="row mb-3">
    <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
        <div class="insight-pill">
            <div class="small text-muted font-weight-bold"><i class="fas fa-trophy mr-1 text-success"></i>Bulan Tertinggi (Peak Month)</div>
            <div class="font-weight-bold mt-1"><?= $peakMonthVal > 0 ? month_name($peakMonthNum) . ' (' . format_currency($peakMonthVal) . ')' : 'Belum ada transaksi' ?></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
        <div class="insight-pill">
            <div class="small text-muted font-weight-bold"><i class="fas fa-coins mr-1 text-success"></i>Sumber Jenis Dana Dominan</div>
            <div class="font-weight-bold mt-1"><?= clean($topJenisName) ?> (<?= $topJenisPct ?>% / <?= format_currency($topJenisVal) ?>)</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
        <div class="insight-pill">
            <div class="small text-muted font-weight-bold"><i class="fas fa-sitemap mr-1 text-success"></i>Sektor UPZ Kontributor Utama</div>
            <div class="font-weight-bold mt-1"><?= clean($topSektorName) ?> (<?= $topSektorPct ?>% / <?= format_currency($topSektorVal) ?>)</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="insight-pill">
            <div class="small text-muted font-weight-bold"><i class="fas fa-building mr-1 text-success"></i>UPZ Peringkat #1</div>
            <div class="font-weight-bold mt-1 text-truncate" title="<?= clean($topUpzName) ?>"><?= clean($topUpzName) ?> (<?= format_currency($topUpzVal) ?>)</div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 1: Tren Bulanan On vs Off Balance & Tren Tahunan (YoY) -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-chart-line mr-2 text-success"></i>Tren Pengumpulan Bulanan (On Balance vs Off Balance Sheet)
                </h3>
                <button class="btn btn-xs btn-outline-success no-print" onclick="toggleDetails('trendDetails')">
                    <i class="fas fa-table mr-1"></i>Tabel Bulanan
                </button>
            </div>
            <div class="card-body">
                <div id="trendApexChart" style="min-height: 320px;"></div>
                <div id="trendDetails" class="mt-3 table-responsive" style="display: none;">
                    <table class="table table-sm table-bordered table-hover small mb-0">
                        <thead class="bg-gray-light">
                            <tr>
                                <th>Bulan</th>
                                <th class="text-right">On Balance Sheet</th>
                                <th class="text-right">Off Balance Sheet</th>
                                <th class="text-right">Total Gabungan</th>
                                <th class="text-center">% Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($m = 1; $m <= 12; $m++):
                                $vOn = $monthlyOn[$m];
                                $vOff = $monthlyOff[$m];
                                $vTot = $vOn + $vOff;
                                $pctM = $totalGabunganDaerah > 0 ? round(($vTot / $totalGabunganDaerah) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><strong><?= month_name($m) ?></strong></td>
                                <td class="text-right text-success font-weight-bold"><?= format_currency($vOn) ?></td>
                                <td class="text-right text-primary font-weight-bold"><?= format_currency($vOff) ?></td>
                                <td class="text-right font-weight-bold"><?= format_currency($vTot) ?></td>
                                <td class="text-center"><?= $pctM ?>%</td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Perbandingan Tahunan (YoY 5 Tahun Terakhir) -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-chart-bar mr-2 text-success"></i>Pertumbuhan 5 Tahun Terakhir (YoY)
                </h3>
            </div>
            <div class="card-body">
                <div id="yoyApexChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 2: Komposisi Bulanan per Jenis Dana (Stacked) & Distribusi Sub-Jenis Dana -->
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-layer-group mr-2 text-success"></i>Dinamika Bulanan per Jenis Dana (Zakat, Infak/Sedekah, DSKL, CSR)
                </h3>
            </div>
            <div class="card-body">
                <div id="stackedJenisMonthlyChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-sitemap mr-2 text-success"></i>Top Sub-Jenis Dana Terbesar
                </h3>
                <button class="btn btn-xs btn-outline-secondary no-print" onclick="toggleDetails('subJenisTableWrap')">Rincian</button>
            </div>
            <div class="card-body">
                <div id="subJenisBarChart" style="min-height: 320px;"></div>
                <div id="subJenisTableWrap" class="mt-3 table-responsive" style="display: none;">
                    <table class="table table-sm table-bordered small mb-0">
                        <thead class="bg-gray-light">
                            <tr>
                                <th>Jenis &amp; Sub-Jenis</th>
                                <th class="text-center">Trx</th>
                                <th class="text-right">Nominal</th>
                                <th class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subJenisData as $sj):
                                $pctSj = $totalOnNominal > 0 ? round(((float)$sj['total'] / $totalOnNominal) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td><strong><?= clean($sj['nama_sub_jenis']) ?></strong> <small class="text-muted">(<?= clean($sj['nama_jenis']) ?>)</small></td>
                                <td class="text-center"><?= number_format((int)$sj['trx_count'], 0, ',', '.') ?></td>
                                <td class="text-right font-weight-bold"><?= format_currency($sj['total']) ?></td>
                                <td class="text-center"><?= $pctSj ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 3: 3 Donut Komposisi (Jenis Pembayaran, Metode Pembayaran, Kategori Dana) -->
<div class="row">
    <!-- Jenis Pembayaran -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-tags mr-2 text-success"></i>Proporsi Jenis Dana</h3>
            </div>
            <div class="card-body">
                <div id="jenisApexChart" style="min-height: 250px;"></div>
                <table class="table table-sm table-borderless small mt-2 mb-0" style="border-top: 1px solid #b8b8b8;">
                    <?php foreach ($jenisData as $j):
                        $pJ = $totalOnNominal > 0 ? round(((float)$j['total'] / $totalOnNominal) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?= clean($j['nama_jenis']) ?></strong> <span class="text-muted">(<?= number_format((int)$j['trx_count']) ?> trx)</span></td>
                        <td class="text-right font-weight-bold"><?= format_currency($j['total']) ?></td>
                        <td class="text-right" width="16%"><span class="badge badge-success"><?= $pJ ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Metode Pembayaran -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-wallet mr-2 text-success"></i>Metode Pembayaran</h3>
            </div>
            <div class="card-body">
                <div id="metodeApexChart" style="min-height: 250px;"></div>
                <table class="table table-sm table-borderless small mt-2 mb-0" style="border-top: 1px solid #b8b8b8;">
                    <?php foreach ($metodeData as $m):
                        $pM = $totalOnNominal > 0 ? round(((float)$m['total'] / $totalOnNominal) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><strong><?= clean($m['nama_metode']) ?></strong> <span class="text-muted">(<?= number_format((int)$m['trx_count']) ?> trx)</span></td>
                        <td class="text-right font-weight-bold"><?= format_currency($m['total']) ?></td>
                        <td class="text-right" width="16%"><span class="badge badge-info"><?= $pM ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Kategori Dana -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex align-items-center" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-folder-open mr-2 text-success"></i>Distribusi Kategori Dana</h3>
            </div>
            <div class="card-body">
                <div id="kategoriApexChart" style="min-height: 250px;"></div>
                <div style="max-height: 165px; overflow-y: auto; border-top: 1px solid #b8b8b8;" class="mt-2">
                    <table class="table table-sm table-borderless small mb-0">
                        <?php
                        $sumKatAll = array_sum(array_column($kategoriStats, 'total'));
                        foreach ($kategoriStats as $k):
                            if ($k['total'] <= 0) continue;
                            $pK = $sumKatAll > 0 ? round(($k['total'] / $sumKatAll) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong><?= clean($k['nama']) ?></strong></td>
                            <td class="text-right font-weight-bold text-nowrap"><?= format_currency($k['total']) ?></td>
                            <td class="text-right" width="16%"><span class="badge badge-secondary"><?= $pK ?>%</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 4: Komposisi Sektoral UPZ & Distribusi Sumber Rekening Bank -->
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-chart-pie mr-2 text-success"></i>Kontribusi Pengumpulan per Kategori Sektoral UPZ</h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div id="upzKategoriApexChart" style="min-height: 300px;"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover small mb-0">
                                <thead>
                                    <tr style="border-bottom: 1px solid #b8b8b8;">
                                        <th>Sektor UPZ</th>
                                        <th class="text-center">Trx</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $upzKategoriTotal = array_sum(array_column($kategoriUPZData, 'total'));
                                    foreach ($kategoriUPZData as $ku):
                                        $pctKu = $upzKategoriTotal > 0 ? round(((float)$ku['total'] / $upzKategoriTotal) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?= clean($ku['nama_kategori']) ?></strong></td>
                                        <td class="text-center"><?= number_format((int)$ku['trx_count']) ?></td>
                                        <td class="text-right font-weight-bold"><?= format_currency($ku['total']) ?></td>
                                        <td class="text-right"><span class="badge badge-success"><?= $pctKu ?>%</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-university mr-2 text-success"></i>Distribusi Sumber Rekening / Bank</h3>
            </div>
            <div class="card-body">
                <div id="bankApexChart" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- BARIS GRAFIK 5: Peringkat Top 10 UPZ & Top 10 Muzaki Kontributor Terbesar -->
<div class="row">
    <!-- Top 10 UPZ -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-award mr-2 text-success"></i>Top 10 UPZ / Instansi Kontributor Terbesar</h3>
            </div>
            <div class="card-body">
                <div id="upzApexChart" style="min-height: 290px;"></div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered table-hover small mb-0">
                        <thead class="bg-gray-light">
                            <tr>
                                <th width="7%" class="text-center">#</th>
                                <th>Nama UPZ / Instansi</th>
                                <th>Sektor</th>
                                <th class="text-center">Trx</th>
                                <th class="text-right">Total Setoran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topUPZ)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data UPZ pada periode ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topUPZ as $idx => $u): ?>
                                <tr>
                                    <td class="text-center font-weight-bold"><?= $idx + 1 ?></td>
                                    <td><strong><?= clean($u['nama_upz']) ?></strong></td>
                                    <td><span class="badge badge-secondary"><?= clean($u['kategori_upz']) ?></span></td>
                                    <td class="text-center"><?= number_format((int)$u['trx_count']) ?></td>
                                    <td class="text-right font-weight-bold text-success"><?= format_currency($u['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top 10 Muzaki -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-user-tie mr-2 text-success"></i>Top 10 Muzaki Kontributor Terbesar</h3>
            </div>
            <div class="card-body">
                <div id="muzakiApexChart" style="min-height: 290px;"></div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered table-hover small mb-0">
                        <thead class="bg-gray-light">
                            <tr>
                                <th width="7%" class="text-center">#</th>
                                <th>Nama Muzaki</th>
                                <th>Asal UPZ / Kategori</th>
                                <th class="text-center">Trx</th>
                                <th class="text-right">Total ZIS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($topMuzaki)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data Muzaki pada periode ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topMuzaki as $idx => $mz): ?>
                                <tr>
                                    <td class="text-center font-weight-bold"><?= $idx + 1 ?></td>
                                    <td><strong><?= clean($mz['nama_muzaki']) ?></strong></td>
                                    <td><small class="text-muted"><?= clean($mz['nama_upz']) ?></small></td>
                                    <td class="text-center"><?= number_format((int)$mz['trx_count']) ?></td>
                                    <td class="text-right font-weight-bold text-primary"><?= format_currency($mz['total']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDetails(id) {
    $('#' + id).slideToggle(250);
}

function toggleFilterInputs() {
    const type = $('#filter_type').val();
    $('.filter-input').hide();
    if (type === 'year') $('#input_year').show();
    else if (type === 'month') $('#input_month').show();
    else if (type === 'range') {
        $('#input_start').show();
        $('#input_end').show();
    }
}

$(function () {
    toggleFilterInputs();

    var chartPalette = ['#0d9f4f', '#2563eb', '#f59e0b', '#0891b2', '#64748b', '#7c3aed', '#dc2626', '#059669'];
    var fmtRp = function (val) {
        return "Rp " + Math.round(val || 0).toLocaleString('id-ID');
    };

    // 1. Trend Bulanan (On Balance vs Off Balance)
    var trendOptions = {
        series: [
            { name: 'On Balance Sheet', type: 'area', data: <?= json_encode(array_values($monthlyOn)) ?> },
            { name: 'Off Balance Sheet', type: 'column', data: <?= json_encode(array_values($monthlyOff)) ?> }
        ],
        chart: {
            height: 320,
            type: 'line',
            toolbar: { show: true },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#0d9f4f', '#2563eb'],
        stroke: {
            curve: 'smooth',
            width: [3, 0]
        },
        fill: {
            type: ['gradient', 'solid'],
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.32,
                opacityTo: 0.04
            }
        },
        plotOptions: {
            bar: { columnWidth: '42%', borderRadius: 3 }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            axisBorder: { show: true, color: '#b8b8b8' }
        },
        yaxis: {
            labels: { formatter: fmtRp }
        },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#trendApexChart"), trendOptions).render();

    // 2. YoY 5 Tahun Terakhir
    <?php
    $yoyLabels = array_keys($yoyYearsMap);
    $yoyOnSeries = array_column($yoyYearsMap, 'on');
    $yoyOffSeries = array_column($yoyYearsMap, 'off');
    ?>
    var yoyOptions = {
        series: [
            { name: 'On Balance', data: <?= json_encode($yoyOnSeries) ?> },
            { name: 'Off Balance', data: <?= json_encode($yoyOffSeries) ?> }
        ],
        chart: {
            type: 'bar',
            height: 320,
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#0d9f4f', '#2563eb'],
        plotOptions: {
            bar: { horizontal: false, columnWidth: '52%', borderRadius: 3 }
        },
        dataLabels: { enabled: false },
        xaxis: {
            categories: <?= json_encode($yoyLabels) ?>,
            axisBorder: { show: true, color: '#b8b8b8' }
        },
        yaxis: { labels: { formatter: fmtRp } },
        legend: { position: 'top' },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#yoyApexChart"), yoyOptions).render();

    // 3. Stacked Bulanan per Jenis Dana
    <?php
    $stackedSeries = [];
    foreach ($monthlyJenisStacked as $jName => $mArr) {
        $stackedSeries[] = [
            'name' => $jName,
            'data' => array_values($mArr)
        ];
    }
    ?>
    var stackedJenisOptions = {
        series: <?= json_encode($stackedSeries) ?>,
        chart: {
            type: 'bar',
            height: 320,
            stacked: true,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: chartPalette,
        plotOptions: {
            bar: { horizontal: false, columnWidth: '52%', borderRadius: 3 }
        },
        dataLabels: { enabled: false },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            axisBorder: { show: true, color: '#b8b8b8' }
        },
        yaxis: { labels: { formatter: fmtRp } },
        legend: { position: 'top', horizontalAlign: 'right' },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#stackedJenisMonthlyChart"), stackedJenisOptions).render();

    // 4. Top Sub-Jenis Dana Horizontal Bar
    var subJenisOptions = {
        series: [{
            name: 'Realisasi',
            data: <?= json_encode(array_map('floatval', array_column($subJenisData, 'total'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: { horizontal: true, borderRadius: 3, barHeight: '65%' }
        },
        colors: ['#0891b2'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: <?= json_encode(array_column($subJenisData, 'nama_sub_jenis')) ?>,
            labels: { formatter: fmtRp }
        },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#subJenisBarChart"), subJenisOptions).render();

    // 5. Donut Jenis Pembayaran
    var jenisOptions = {
        series: <?= json_encode(array_map('floatval', array_column($jenisData, 'total'))) ?>,
        chart: { height: 250, type: 'donut', fontFamily: 'Inter, sans-serif' },
        colors: chartPalette,
        labels: <?= json_encode(array_column($jenisData, 'nama_jenis')) ?>,
        plotOptions: { pie: { donut: { size: '65%' } } },
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#jenisApexChart"), jenisOptions).render();

    // 6. Donut Metode Pembayaran
    var metodeOptions = {
        series: <?= json_encode(array_map('floatval', array_column($metodeData, 'total'))) ?>,
        chart: { height: 250, type: 'donut', fontFamily: 'Inter, sans-serif' },
        colors: ['#f59e0b', '#0d9f4f', '#2563eb', '#64748b'],
        labels: <?= json_encode(array_column($metodeData, 'nama_metode')) ?>,
        plotOptions: { pie: { donut: { size: '65%' } } },
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#metodeApexChart"), metodeOptions).render();

    // 7. Donut Kategori Dana
    <?php
    $katFiltered = array_filter($kategoriStats, function($item) { return $item['total'] > 0; });
    $katLabels = array_values(array_column($katFiltered, 'nama'));
    $katTotals = array_values(array_map('floatval', array_column($katFiltered, 'total')));
    ?>
    var kategoriOptions = {
        series: <?= json_encode($katTotals) ?>,
        chart: { height: 250, type: 'donut', fontFamily: 'Inter, sans-serif' },
        colors: chartPalette,
        labels: <?= json_encode($katLabels) ?>,
        plotOptions: { pie: { donut: { size: '65%' } } },
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#kategoriApexChart"), kategoriOptions).render();

    // 8. Donut Sektoral UPZ
    var upzKategoriOptions = {
        series: <?= json_encode(array_map('floatval', array_column($kategoriUPZData, 'total'))) ?>,
        chart: { height: 300, type: 'donut', fontFamily: 'Inter, sans-serif' },
        colors: chartPalette,
        labels: <?= json_encode(array_column($kategoriUPZData, 'nama_kategori')) ?>,
        plotOptions: {
            pie: {
                donut: {
                    size: '64%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Sektoral',
                            formatter: function () {
                                return fmtRp(<?= json_encode((float)$upzKategoriTotal) ?>);
                            }
                        }
                    }
                }
            }
        },
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: function (val) { return val.toFixed(1) + "%"; }
        },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#upzKategoriApexChart"), upzKategoriOptions).render();

    // 9. Sumber Rekening / Bank
    var bankOptions = {
        series: [{
            name: 'Total',
            data: <?= json_encode(array_map('floatval', array_column($bankData, 'total'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 290,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: { borderRadius: 3, horizontal: true, barHeight: '60%' }
        },
        colors: ['#334155'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: <?= json_encode(array_column($bankData, 'nama_sumber')) ?>,
            labels: { formatter: fmtRp }
        },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#bankApexChart"), bankOptions).render();

    // 10. Top 10 UPZ Chart
    var upzOptions = {
        series: [{
            name: 'Total Pengumpulan',
            data: <?= json_encode(array_map('floatval', array_column($topUPZ, 'total'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: { borderRadius: 3, horizontal: true, barHeight: '65%' }
        },
        colors: ['#0d9f4f'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: <?= json_encode(array_column($topUPZ, 'nama_upz')) ?>,
            labels: { formatter: fmtRp }
        },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#upzApexChart"), upzOptions).render();

    // 11. Top 10 Muzaki Chart
    var muzakiOptions = {
        series: [{
            name: 'Total ZIS',
            data: <?= json_encode(array_map('floatval', array_column($topMuzaki, 'total'))) ?>
        }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        plotOptions: {
            bar: { borderRadius: 3, horizontal: true, barHeight: '65%' }
        },
        colors: ['#2563eb'],
        dataLabels: { enabled: false },
        xaxis: {
            categories: <?= json_encode(array_column($topMuzaki, 'nama_muzaki')) ?>,
            labels: { formatter: fmtRp }
        },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        tooltip: { y: { formatter: fmtRp } }
    };
    new ApexCharts(document.querySelector("#muzakiApexChart"), muzakiOptions).render();
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
