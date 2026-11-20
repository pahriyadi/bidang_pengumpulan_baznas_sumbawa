<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

$activeMenu = 'skz_laporan';
$pageTitle = 'Laporan SKZ & BSZIS';
$pageDescription = 'Rekapitulasi Surat Ketetapan Zakat (SKZ) & Bukti Setoran ZIS (BSZIS) Rekanan Proyek Dinas/Instansi';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item"><a href="' . url('pages/skz/index.php') . '">SKZ & BSZIS</a></li><li class="breadcrumb-item active">Laporan SKZ & BSZIS</li>';

$conn = db();
ensure_skz_table($conn);

// --- Standardized Filter Parameters ---
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

if (!function_exists('get_filter_str_arr')) {
    function get_filter_str_arr($name, $method = 'GET') {
        $src = ($method === 'POST') ? $_POST : $_GET;
        $val = $src[$name] ?? [];
        if (!is_array($val)) {
            $val = $val !== '' ? [$val] : [];
        }
        return array_values(array_filter(array_map('trim', $val), function($v) { return $v !== ''; }));
    }
}

$dinas_arr = get_filter_str_arr('dinas');
$badan_usaha_arr = get_filter_str_arr('badan_usaha');
$objek_arr = get_filter_str_arr('objek_zis');
$via_arr = get_filter_str_arr('via_pembayaran');
$petugas_arr = get_filter_str_arr('petugas');
$keyword = trim($_GET['keyword'] ?? '');

$where = ["s.tanggal_surat >= ?", "s.tanggal_surat <= ?"];
$types = "ss";
$params = [$start_date, $end_date];

if (!empty($dinas_arr)) {
    $in_p = implode(',', array_fill(0, count($dinas_arr), '?'));
    $where[] = "s.dinas_instansi IN ($in_p)";
    $types .= str_repeat('s', count($dinas_arr));
    foreach ($dinas_arr as $v) $params[] = $v;
}
if (!empty($badan_usaha_arr)) {
    $in_p = implode(',', array_fill(0, count($badan_usaha_arr), '?'));
    $where[] = "s.nama_badan_usaha IN ($in_p)";
    $types .= str_repeat('s', count($badan_usaha_arr));
    foreach ($badan_usaha_arr as $v) $params[] = $v;
}
if (!empty($objek_arr)) {
    $in_p = implode(',', array_fill(0, count($objek_arr), '?'));
    $where[] = "UPPER(s.objek_zis) IN ($in_p)";
    $types .= str_repeat('s', count($objek_arr));
    foreach ($objek_arr as $v) $params[] = strtoupper($v);
}
if (!empty($via_arr)) {
    $in_p = implode(',', array_fill(0, count($via_arr), '?'));
    $where[] = "UPPER(s.via_pembayaran) IN ($in_p)";
    $types .= str_repeat('s', count($via_arr));
    foreach ($via_arr as $v) $params[] = strtoupper($v);
}
if (!empty($petugas_arr)) {
    $in_p = implode(',', array_fill(0, count($petugas_arr), '?'));
    $where[] = "s.nama_petugas IN ($in_p)";
    $types .= str_repeat('s', count($petugas_arr));
    foreach ($petugas_arr as $v) $params[] = $v;
}
if ($keyword !== '') {
    $where[] = "(s.nomor_surat LIKE ? OR s.paket_pekerjaan LIKE ? OR s.nama_penyetor LIKE ? OR s.nama_badan_usaha LIKE ? OR s.dinas_instansi LIKE ?)";
    $types .= "sssss";
    $kwWild = '%' . $keyword . '%';
    for ($k = 0; $k < 5; $k++) $params[] = $kwWild;
}

$where_sql = implode(' AND ', $where);

// --- Dropdown Options (Merged from Master Tables + Existing SKZ Records) ---
$upzMaster = get_upz_list();
$jenisMaster = get_jenis_pembayaran_list();
$metodeMaster = get_metode_pembayaran_list();

$dinasSet = [];
$badanUsahaSet = [];
foreach ($upzMaster as $u) {
    $nm = trim($u['nama_upz'] ?? '');
    if ($nm !== '') {
        $dinasSet[$nm] = true;
        $badanUsahaSet[$nm] = true;
    }
}
$rDinas = $conn->query("SELECT DISTINCT dinas_instansi FROM surat_ketetapan_zakat WHERE dinas_instansi IS NOT NULL AND dinas_instansi != '' ORDER BY dinas_instansi ASC");
if ($rDinas) {
    while ($d = $rDinas->fetch_assoc()) $dinasSet[trim($d['dinas_instansi'])] = true;
}
$rBu = $conn->query("SELECT DISTINCT nama_badan_usaha FROM surat_ketetapan_zakat WHERE nama_badan_usaha IS NOT NULL AND nama_badan_usaha != '' ORDER BY nama_badan_usaha ASC");
if ($rBu) {
    while ($b = $rBu->fetch_assoc()) $badanUsahaSet[trim($b['nama_badan_usaha'])] = true;
}
$dinasOptions = array_keys($dinasSet);
sort($dinasOptions);
$badanUsahaOptions = array_keys($badanUsahaSet);
sort($badanUsahaOptions);

$objekSet = ['ZAKAT' => true, 'INFAK' => true, 'SEDEKAH' => true];
foreach ($jenisMaster as $j) {
    $nm = strtoupper(trim($j['nama_jenis'] ?? ''));
    if ($nm !== '') $objekSet[$nm] = true;
}
$rObj = $conn->query("SELECT DISTINCT objek_zis FROM surat_ketetapan_zakat WHERE objek_zis IS NOT NULL AND objek_zis != ''");
if ($rObj) {
    while ($o = $rObj->fetch_assoc()) $objekSet[strtoupper(trim($o['objek_zis']))] = true;
}
$objekOptions = array_keys($objekSet);

$viaSet = ['TUNAI' => true];
foreach ($metodeMaster as $m) {
    $nm = strtoupper(trim($m['nama_metode'] ?? ''));
    if ($nm !== '') $viaSet[$nm] = true;
}
$rVia = $conn->query("SELECT DISTINCT via_pembayaran FROM surat_ketetapan_zakat WHERE via_pembayaran IS NOT NULL AND via_pembayaran != ''");
if ($rVia) {
    while ($v = $rVia->fetch_assoc()) $viaSet[strtoupper(trim($v['via_pembayaran']))] = true;
}
$viaOptions = array_keys($viaSet);

$petugasSet = [];
$rPetugas = $conn->query("SELECT DISTINCT nama_petugas FROM surat_ketetapan_zakat WHERE nama_petugas IS NOT NULL AND nama_petugas != '' ORDER BY nama_petugas ASC");
if ($rPetugas) {
    while ($pt = $rPetugas->fetch_assoc()) $petugasSet[trim($pt['nama_petugas'])] = true;
}
$rUsers = $conn->query("SELECT full_name FROM users WHERE full_name IS NOT NULL AND full_name != '' ORDER BY full_name ASC");
if ($rUsers) {
    while ($u = $rUsers->fetch_assoc()) $petugasSet[trim($u['full_name'])] = true;
}
$petugasOptions = array_keys($petugasSet);
sort($petugasOptions);

// --- Fetch Filtered SKZ Records ---
$sql = "
    SELECT s.*
    FROM surat_ketetapan_zakat s
    WHERE {$where_sql}
    ORDER BY s.tanggal_surat DESC, s.nomor_urut DESC, s.id DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$skzRows = [];
$total_kontrak = 0.0;
$total_estimasi_laba = 0.0;
$total_zis = 0.0;

// Aggregations
$groupedDinas = [];
$groupedObjekVia = [];
$groupedBadanUsaha = [];

while ($row = $res->fetch_assoc()) {
    $kontrak = (float)$row['nilai_kontrak'];
    $laba = (float)$row['estimasi_keuntungan'];
    $zis = (float)$row['nilai_zis'];

    $total_kontrak += $kontrak;
    $total_estimasi_laba += $laba;
    $total_zis += $zis;

    $dinas = trim($row['dinas_instansi']) ?: 'Tanpa Dinas / Instansi';
    $bu = trim($row['nama_badan_usaha']) ?: 'Tanpa Nama Badan Usaha';
    $objek = strtoupper(trim($row['objek_zis']) ?: 'ZAKAT');
    $via = strtoupper(trim($row['via_pembayaran']) ?: 'TUNAI');

    // 1. Group by Dinas / Instansi
    if (!isset($groupedDinas[$dinas])) {
        $groupedDinas[$dinas] = [
            'jumlah_skz' => 0,
            'rekanan_set' => [],
            'total_kontrak' => 0.0,
            'total_laba' => 0.0,
            'total_zis' => 0.0
        ];
    }
    $groupedDinas[$dinas]['jumlah_skz']++;
    $groupedDinas[$dinas]['rekanan_set'][$bu] = true;
    $groupedDinas[$dinas]['total_kontrak'] += $kontrak;
    $groupedDinas[$dinas]['total_laba'] += $laba;
    $groupedDinas[$dinas]['total_zis'] += $zis;

    // 2. Group by Objek ZIS & Via Pembayaran
    if (!isset($groupedObjekVia[$objek])) {
        $groupedObjekVia[$objek] = [
            'jumlah_skz' => 0,
            'total_kontrak' => 0.0,
            'total_zis' => 0.0,
            'vias' => []
        ];
    }
    $groupedObjekVia[$objek]['jumlah_skz']++;
    $groupedObjekVia[$objek]['total_kontrak'] += $kontrak;
    $groupedObjekVia[$objek]['total_zis'] += $zis;

    if (!isset($groupedObjekVia[$objek]['vias'][$via])) {
        $groupedObjekVia[$objek]['vias'][$via] = [
            'jumlah_skz' => 0,
            'total_kontrak' => 0.0,
            'total_zis' => 0.0
        ];
    }
    $groupedObjekVia[$objek]['vias'][$via]['jumlah_skz']++;
    $groupedObjekVia[$objek]['vias'][$via]['total_kontrak'] += $kontrak;
    $groupedObjekVia[$objek]['vias'][$via]['total_zis'] += $zis;

    // 3. Group by Badan Usaha / Rekanan
    if (!isset($groupedBadanUsaha[$bu])) {
        $groupedBadanUsaha[$bu] = [
            'jumlah_skz' => 0,
            'dinas_set' => [],
            'total_kontrak' => 0.0,
            'total_laba' => 0.0,
            'total_zis' => 0.0
        ];
    }
    $groupedBadanUsaha[$bu]['jumlah_skz']++;
    $groupedBadanUsaha[$bu]['dinas_set'][$dinas] = true;
    $groupedBadanUsaha[$bu]['total_kontrak'] += $kontrak;
    $groupedBadanUsaha[$bu]['total_laba'] += $laba;
    $groupedBadanUsaha[$bu]['total_zis'] += $zis;

    $skzRows[] = $row;
}
$stmt->close();

// Sort aggregations by total_zis DESC
uasort($groupedDinas, function($a, $b) { return $b['total_zis'] <=> $a['total_zis']; });
uasort($groupedBadanUsaha, function($a, $b) { return $b['total_zis'] <=> $a['total_zis']; });

include __DIR__ . '/../../inc/header.php';
?>

<!-- 4 Kartu Ringkasan SKZ & BSZIS -->
<div class="row dashboard-stats-row">
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-success h-100 mb-0">
            <div class="inner">
                <h3 class="stat-currency-val" title="<?= format_currency($total_zis) ?>"><?= format_currency($total_zis) ?></h3>
                <p>Total Nilai Ketetapan ZIS</p>
            </div>
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
            <span class="small-box-footer">Dari <?= number_format(count($skzRows), 0, ',', '.') ?> Dokumen SKZ &amp; BSZIS</span>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-primary h-100 mb-0">
            <div class="inner">
                <h3 class="stat-currency-val" title="<?= format_currency($total_kontrak) ?>"><?= format_currency($total_kontrak) ?></h3>
                <p>Total Nilai Kontrak Proyek</p>
            </div>
            <div class="icon"><i class="fas fa-file-contract"></i></div>
            <span class="small-box-footer">Est. Keuntungan: <?= format_currency($total_estimasi_laba) ?></span>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-info h-100 mb-0">
            <div class="inner">
                <h3><?= number_format(count($groupedDinas), 0, ',', '.') ?> <small style="font-size: 0.55em; font-weight: normal;">Instansi</small></h3>
                <p>Dinas / Instansi Pemberi Kerja</p>
            </div>
            <div class="icon"><i class="fas fa-landmark"></i></div>
            <a href="#rekap-dinas" class="small-box-footer">Lihat Rekap per Dinas <i class="fas fa-arrow-circle-right ml-1"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-warning h-100 mb-0">
            <div class="inner">
                <h3><?= number_format(count($groupedBadanUsaha), 0, ',', '.') ?> <small style="font-size: 0.55em; font-weight: normal;">Rekanan</small></h3>
                <p>Badan Usaha / CV / PT</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
            <a href="#rekap-rekanan" class="small-box-footer">Lihat Rekap per Rekanan <i class="fas fa-arrow-circle-right ml-1"></i></a>
        </div>
    </div>
</div>

<!-- Filter Card - STANDARD PATTERN -->
<div class="card card-outline card-success shadow-none mb-3" style="border: 1px solid #b8b8b8;">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
        <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-filter mr-2 text-success"></i>Filter Laporan SKZ &amp; BSZIS</h3>
        <div class="card-tools mt-2 mt-md-0">
            <a href="<?= url('pages/skz/index.php') ?>" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-list mr-1"></i>Daftar SKZ &amp; BSZIS
            </a>
            <a href="<?= url('pages/skz/form.php') ?>" class="btn btn-outline-success btn-sm">
                <i class="fas fa-plus mr-1"></i>Buat SKZ Baru
            </a>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="laporan.php">
            <!-- Baris 1: 4 Kolom Utama -->
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dari Tanggal Surat</label>
                        <input type="date" class="form-control" name="start_date" id="start_date" value="<?= clean($start_date) ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Sampai Tanggal Surat</label>
                        <input type="date" class="form-control" name="end_date" id="end_date" value="<?= clean($end_date) ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Dinas / Instansi Pemberi Kerja</label>
                        <select class="form-control select2" name="dinas[]" multiple="multiple" data-placeholder="Semua Dinas / Instansi">
                            <?php foreach ($dinasOptions as $dn): ?>
                                <option value="<?= clean($dn) ?>" <?= in_array($dn, $dinas_arr, true) ? 'selected' : '' ?>><?= clean($dn) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Badan Usaha / Rekanan (CV/PT)</label>
                        <select class="form-control select2" name="badan_usaha[]" multiple="multiple" data-placeholder="Semua Badan Usaha">
                            <?php foreach ($badanUsahaOptions as $bu): ?>
                                <option value="<?= clean($bu) ?>" <?= in_array($bu, $badan_usaha_arr, true) ? 'selected' : '' ?>><?= clean($bu) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Baris 2: 4 Kolom Filter Spesifik SKZ & BSZIS -->
            <div class="row mt-1">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Objek ZIS</label>
                        <select class="form-control select2" name="objek_zis[]" multiple="multiple" data-placeholder="Semua Objek ZIS">
                            <?php foreach ($objekOptions as $ob): ?>
                                <option value="<?= clean($ob) ?>" <?= in_array($ob, $objek_arr, true) ? 'selected' : '' ?>><?= clean($ob) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Metode Penyetoran (Via)</label>
                        <select class="form-control select2" name="via_pembayaran[]" multiple="multiple" data-placeholder="Semua Via Pembayaran">
                            <?php foreach ($viaOptions as $vp): ?>
                                <option value="<?= clean($vp) ?>" <?= in_array($vp, $via_arr, true) ? 'selected' : '' ?>><?= clean($vp) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Petugas Amil Pengesah</label>
                        <select class="form-control select2" name="petugas[]" multiple="multiple" data-placeholder="Semua Petugas Amil">
                            <?php foreach ($petugasOptions as $pt): ?>
                                <option value="<?= clean($pt) ?>" <?= in_array($pt, $petugas_arr, true) ? 'selected' : '' ?>><?= clean($pt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Cari No. Surat / Paket Pekerjaan / Penyetor</label>
                        <input type="text" class="form-control" name="keyword" value="<?= clean($keyword) ?>" placeholder="Ketik kata kunci...">
                    </div>
                </div>
            </div>

            <!-- Baris 3: Rentang Cepat & Tombol Aksi -->
            <div class="row mt-2 pt-2" style="border-top: 1px dashed #b8b8b8;">
                <div class="col-md-7 mb-2 mb-md-0 d-flex align-items-center flex-wrap">
                    <strong class="mr-2">Rentang Cepat:</strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-info" onclick="setRange('this_month')">Bulan Ini</button>
                        <button type="button" class="btn btn-outline-info" onclick="setRange('last_month')">Bulan Lalu</button>
                        <button type="button" class="btn btn-outline-info" onclick="setRange('this_quarter')">Triwulan Ini</button>
                        <button type="button" class="btn btn-outline-info" onclick="setRange('this_year')">Tahun Ini</button>
                    </div>
                </div>
                <div class="col-md-5 text-md-right">
                    <button type="submit" class="btn btn-outline-success mr-1 px-4"><i class="fas fa-search mr-1"></i>Tampilkan</button>
                    <a href="laporan.php" class="btn btn-outline-secondary px-4"><i class="fas fa-undo mr-1"></i>Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Toolbar Export -->
<div class="card flat-card mb-3" style="border: 1px solid #b8b8b8;">
    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-poll mr-2 text-success"></i>Hasil Laporan SKZ &amp; BSZIS</h5>
        <form id="exportForm" method="POST" action="export.php" target="_blank" class="form-inline mb-0">
            <?= csrf_field() ?>
            <input type="hidden" name="start_date" value="<?= clean($start_date) ?>">
            <input type="hidden" name="end_date" value="<?= clean($end_date) ?>">
            <input type="hidden" name="keyword" value="<?= clean($keyword) ?>">
            <?php foreach ($dinas_arr as $v): ?>
                <input type="hidden" name="dinas[]" value="<?= clean($v) ?>">
            <?php endforeach; ?>
            <?php foreach ($badan_usaha_arr as $v): ?>
                <input type="hidden" name="badan_usaha[]" value="<?= clean($v) ?>">
            <?php endforeach; ?>
            <?php foreach ($objek_arr as $v): ?>
                <input type="hidden" name="objek_zis[]" value="<?= clean($v) ?>">
            <?php endforeach; ?>
            <?php foreach ($via_arr as $v): ?>
                <input type="hidden" name="via_pembayaran[]" value="<?= clean($v) ?>">
            <?php endforeach; ?>
            <?php foreach ($petugas_arr as $v): ?>
                <input type="hidden" name="petugas[]" value="<?= clean($v) ?>">
            <?php endforeach; ?>

            <button type="submit" name="export_format" value="csv" class="btn btn-sm btn-outline-info mr-1">
                <i class="fas fa-file-csv mr-1"></i>Export CSV
            </button>
            <button type="submit" name="export_format" value="excel" class="btn btn-sm btn-outline-success mr-1">
                <i class="fas fa-file-excel mr-1"></i>Export Excel
            </button>
            <button type="submit" name="export_format" value="pdf" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
            </button>
        </form>
    </div>
</div>

<!-- 1. Tabel Rincian Dokumen SKZ & BSZIS -->
<div class="card flat-card shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
        <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-file-invoice-dollar mr-2 text-success"></i>1. Rincian Surat Ketetapan Zakat (SKZ) &amp; Bukti Setoran ZIS (BSZIS)</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info alert-excel mb-4 shadow-none">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="mb-1"><i class="icon fas fa-info-circle"></i> Ringkasan SKZ &amp; BSZIS (<?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?>)</h5>
                    Total Dokumen SKZ: <strong><?= number_format(count($skzRows), 0, ',', '.') ?></strong> surat &nbsp;|&nbsp;
                    Total Nilai Kontrak: <strong><?= format_currency($total_kontrak) ?></strong> &nbsp;|&nbsp;
                    Total Estimasi Keuntungan: <strong><?= format_currency($total_estimasi_laba) ?></strong><br>
                    Total Nilai Ketetapan ZIS: <strong class="currency-display h4 mb-0 text-success"><?= format_currency($total_zis) ?></strong>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="dataTable" class="table table-bordered table-hover table-excel" style="font-size: 0.84rem;">
                <thead>
                    <tr class="bg-light">
                        <th width="3%">No</th>
                        <th width="9%">Tanggal</th>
                        <th width="15%">Nomor Surat</th>
                        <th>Badan Usaha &amp; Paket Pekerjaan</th>
                        <th width="14%">Dinas / Instansi</th>
                        <th width="12%" class="text-right">Nilai Kontrak</th>
                        <th width="11%" class="text-right">Est. Keuntungan</th>
                        <th width="11%" class="text-right">Nilai ZIS</th>
                        <th width="10%" class="text-center">Objek &amp; Via</th>
                        <th width="8%" class="text-center">Cetak</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <?php if (!empty($skzRows)): ?>
                <tfoot>
                    <tr class="bg-gray-light font-weight-bold">
                        <th colspan="5" class="text-right">TOTAL KESELURUHAN</th>
                        <th class="text-right"><?= format_currency($total_kontrak) ?></th>
                        <th class="text-right"><?= format_currency($total_estimasi_laba) ?></th>
                        <th class="text-right text-success" style="background: rgba(13, 159, 79, 0.14);"><?= format_currency($total_zis) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- 2. Rekapitulasi per Objek ZIS & Via Pembayaran + Rekapitulasi per Dinas/Instansi -->
<div class="row mt-4">
    <!-- Rekapitulasi per Objek ZIS & Via Pembayaran -->
    <div class="col-lg-5 mb-4">
        <div class="card flat-card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-layer-group mr-2 text-success"></i>2. Rekap per Objek ZIS &amp; Via Pembayaran</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" style="font-size: 0.84rem;">
                        <thead class="bg-gray-light">
                            <tr>
                                <th>Objek ZIS &amp; Via Pembayaran</th>
                                <th width="15%" class="text-center">Jml SKZ</th>
                                <th width="34%" class="text-right">Nilai ZIS (Rp)</th>
                                <th width="14%" class="text-center">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedObjekVia)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Data kosong.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedObjekVia as $objNama => $oData):
                                    $pctObj = $total_zis > 0 ? round(($oData['total_zis'] / $total_zis) * 100, 1) : 0;
                                ?>
                                    <tr class="table-secondary font-weight-bold">
                                        <td><i class="fas fa-folder-open mr-2 text-success"></i><?= htmlspecialchars($objNama) ?></td>
                                        <td class="text-center"><?= number_format($oData['jumlah_skz'], 0, ',', '.') ?></td>
                                        <td class="text-right">Rp <?= number_format($oData['total_zis'], 0, ',', '.') ?></td>
                                        <td class="text-center"><span class="badge badge-success"><?= $pctObj ?>%</span></td>
                                    </tr>
                                    <?php foreach ($oData['vias'] as $viaNama => $vData):
                                        $pctVia = $total_zis > 0 ? round(($vData['total_zis'] / $total_zis) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td style="padding-left: 26px;"><i class="fas fa-angle-right mr-2 text-muted"></i>Via: <?= htmlspecialchars($viaNama) ?></td>
                                            <td class="text-center"><?= number_format($vData['jumlah_skz'], 0, ',', '.') ?></td>
                                            <td class="text-right font-weight-bold">Rp <?= number_format($vData['total_zis'], 0, ',', '.') ?></td>
                                            <td class="text-center"><?= $pctVia ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($groupedObjekVia)): ?>
                        <tfoot class="bg-gray-light font-weight-bold">
                            <tr>
                                <td class="text-right">TOTAL</td>
                                <td class="text-center"><?= number_format(count($skzRows), 0, ',', '.') ?></td>
                                <td class="text-right text-success">Rp <?= number_format($total_zis, 0, ',', '.') ?></td>
                                <td class="text-center">100%</td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekapitulasi per Dinas / Instansi -->
    <div class="col-lg-7 mb-4" id="rekap-dinas">
        <div class="card flat-card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-landmark mr-2 text-success"></i>3. Rekapitulasi per Dinas / Instansi</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" style="font-size: 0.84rem;">
                        <thead class="bg-gray-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th>Dinas / Instansi</th>
                                <th width="10%" class="text-center">SKZ</th>
                                <th width="11%" class="text-center">Rekanan</th>
                                <th width="23%" class="text-right">Total Kontrak</th>
                                <th width="22%" class="text-right">Total ZIS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedDinas)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Data kosong.</td></tr>
                            <?php else: ?>
                                <?php $noD = 1; foreach ($groupedDinas as $dNama => $dData): ?>
                                <tr>
                                    <td class="text-center"><?= $noD++ ?></td>
                                    <td>
                                        <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&dinas[]=<?= urlencode($dNama) ?>" class="font-weight-bold">
                                            <?= htmlspecialchars($dNama) ?>
                                        </a>
                                    </td>
                                    <td class="text-center"><?= number_format($dData['jumlah_skz'], 0, ',', '.') ?></td>
                                    <td class="text-center"><?= number_format(count($dData['rekanan_set']), 0, ',', '.') ?></td>
                                    <td class="text-right">Rp <?= number_format($dData['total_kontrak'], 0, ',', '.') ?></td>
                                    <td class="text-right font-weight-bold text-success">Rp <?= number_format($dData['total_zis'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($groupedDinas)): ?>
                        <tfoot class="bg-gray-light font-weight-bold">
                            <tr>
                                <td colspan="2" class="text-right">TOTAL KESELURUHAN</td>
                                <td class="text-center"><?= number_format(count($skzRows), 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format(count($groupedBadanUsaha), 0, ',', '.') ?></td>
                                <td class="text-right">Rp <?= number_format($total_kontrak, 0, ',', '.') ?></td>
                                <td class="text-right text-success">Rp <?= number_format($total_zis, 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Rekapitulasi per Badan Usaha / Rekanan -->
<div class="card flat-card shadow-none mb-4" id="rekap-rekanan" style="border: 1px solid #b8b8b8;">
    <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
        <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-building mr-2 text-success"></i>4. Rekapitulasi per Badan Usaha / Rekanan Proyek</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="rekananTable" class="table table-bordered table-hover" style="font-size: 0.84rem;">
                <thead class="bg-gray-light">
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th>Nama Badan Usaha / CV / PT</th>
                        <th>Dinas / Instansi Terkait</th>
                        <th width="9%" class="text-center">Jml Paket</th>
                        <th width="16%" class="text-right">Total Nilai Kontrak</th>
                        <th width="15%" class="text-right">Total Est. Keuntungan</th>
                        <th width="15%" class="text-right">Total Nilai ZIS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $noBu = 1; foreach ($groupedBadanUsaha as $buNama => $buData): ?>
                    <tr>
                        <td class="text-center"><?= $noBu++ ?></td>
                        <td>
                            <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&badan_usaha[]=<?= urlencode($buNama) ?>" class="font-weight-bold">
                                <?= htmlspecialchars($buNama) ?>
                            </a>
                        </td>
                        <td>
                            <?php foreach (array_keys($buData['dinas_set']) as $dnName): ?>
                                <span class="badge badge-secondary mr-1"><?= htmlspecialchars($dnName) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td class="text-center"><?= number_format($buData['jumlah_skz'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($buData['total_kontrak'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($buData['total_laba'], 0, ',', '.') ?></td>
                        <td class="text-right font-weight-bold text-success">Rp <?= number_format($buData['total_zis'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const skzRecords = <?= json_encode($skzRows) ?>;
const baseSkzUrl = '<?= url('pages/skz/') ?>';

$(function () {
    const dataSet = skzRecords.map((row, idx) => {
        const fmtRp = (val) => 'Rp ' + (parseInt(val, 10) || 0).toLocaleString('id-ID');
        const parts = (row.tanggal_surat || '').split('-');
        const dateFormatted = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : row.tanggal_surat;

        let buHtml = '<strong>' + (row.nama_badan_usaha || '-') + '</strong>';
        if (row.paket_pekerjaan) {
            buHtml += '<br><small class="text-muted"><i class="fas fa-briefcase mr-1"></i>' + row.paket_pekerjaan + '</small>';
        }
        if (row.nama_penyetor) {
            buHtml += '<br><small class="text-muted"><i class="fas fa-user mr-1"></i>Penyetor: ' + row.nama_penyetor + '</small>';
        }

        const pctLaba = parseFloat(row.persentase_keuntungan) || 0;
        const pctZis = parseFloat(row.persentase_zis) || 0;

        const cetakBtns = `
            <div class="btn-group">
                <a href="${baseSkzUrl}cetak_skz.php?id=${row.id}" target="_blank" class="btn btn-outline-success btn-xs" title="Cetak SKZ">SKZ</a>
                <a href="${baseSkzUrl}cetak_bszis.php?id=${row.id}" target="_blank" class="btn btn-outline-primary btn-xs" title="Cetak BSZIS">BSZIS</a>
            </div>
        `;

        return [
            idx + 1,
            dateFormatted,
            '<strong>' + (row.nomor_surat || '-') + '</strong>',
            buHtml,
            '<span class="badge badge-secondary">' + (row.dinas_instansi || '-') + '</span>',
            '<div class="text-right">' + fmtRp(row.nilai_kontrak) + '</div>',
            '<div class="text-right">' + fmtRp(row.estimasi_keuntungan) + '<br><small class="text-muted">(' + pctLaba + '%)</small></div>',
            '<div class="text-right"><strong class="text-success">' + fmtRp(row.nilai_zis) + '</strong><br><small class="text-muted">(' + pctZis + '%)</small></div>',
            '<div class="text-center"><span class="badge badge-success">' + (row.objek_zis || '-') + '</span><br><small class="text-muted">' + (row.via_pembayaran || '-') + '</small></div>',
            '<div class="text-center">' + cetakBtns + '</div>'
        ];
    });

    $('#dataTable').DataTable({
        data: dataSet,
        deferRender: true,
        order: [],
        pageLength: 25,
        language: {
            emptyTable: "Tidak ada dokumen SKZ & BSZIS pada periode dan kriteria tersebut.",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Berikutnya",
                previous: "Sebelumnya"
            }
        }
    });

    $('#rekananTable').DataTable({
        pageLength: 10,
        order: [[6, 'desc']],
        language: {
            emptyTable: "Belum ada data rekanan.",
            search: "Cari Rekanan:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ rekanan"
        }
    });

    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});

function setRange(type) {
    const today = new Date();
    let start = '';
    let end = '';
    const formatDate = (date) => {
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        return `${y}-${m}-${d}`;
    };
    if (type === 'this_month') {
        start = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
        end = formatDate(new Date(today.getFullYear(), today.getMonth() + 1, 0));
    } else if (type === 'last_month') {
        start = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
        end = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
    } else if (type === 'this_quarter') {
        const q = Math.floor(today.getMonth() / 3);
        start = formatDate(new Date(today.getFullYear(), q * 3, 1));
        end = formatDate(new Date(today.getFullYear(), q * 3 + 3, 0));
    } else if (type === 'this_year') {
        start = formatDate(new Date(today.getFullYear(), 0, 1));
        end = formatDate(new Date(today.getFullYear(), 11, 31));
    }
    if (start && end) {
        $('#start_date').val(start);
        $('#end_date').val(end);
    }
}
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
