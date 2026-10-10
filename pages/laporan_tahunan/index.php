<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'laporan_tahunan';
$pageTitle = 'Laporan Tahunan';
$pageDescription = 'Rekapitulasi penerimaan bulanan per Muzaki/UPZ';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Laporan Tahunan</li>';
$conn = db();

// --- Standardized Filter Parameters ---
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

// Helper to extract array from request
if (!function_exists('get_filter_arr')) {
    function get_filter_arr($name, $method = 'GET') {
        $src = ($method === 'POST') ? $_POST : $_GET;
        $val = $src[$name] ?? [];
        if (!is_array($val)) {
            $val = $val !== '' ? [$val] : [];
        }
        return array_filter(array_map('intval', $val), function($v) { return $v != 0; });
    }
}

$id_jenis_arr = get_filter_arr('id_jenis');
$id_upz_arr = get_filter_arr('id_upz');
$id_sub_jenis_arr = get_filter_arr('id_sub_jenis');
$id_metode_arr = get_filter_arr('id_metode');
$id_sumber_arr = get_filter_arr('id_sumber');
$id_kategori_arr = get_filter_arr('id_kategori');

// --- Where Clause Construction ---
$where = ["t.tanggal_transaksi >= ?", "t.tanggal_transaksi <= ?"];
$types = "ss";
$params = [$start_date, $end_date];

if (!empty($id_jenis_arr)) {
    $in_placeholder = implode(',', array_fill(0, count($id_jenis_arr), '?'));
    $where[] = "t.id_jenis_pembayaran IN ($in_placeholder)";
    $types .= str_repeat('i', count($id_jenis_arr));
    foreach ($id_jenis_arr as $v) $params[] = $v;
}

if (!empty($id_upz_arr)) {
    $in_placeholder = implode(',', array_fill(0, count($id_upz_arr), '?'));
    $where[] = "t.id_upz IN ($in_placeholder)";
    $types .= str_repeat('i', count($id_upz_arr));
    foreach ($id_upz_arr as $v) $params[] = $v;
}

if (!empty($id_sub_jenis_arr)) {
    $in_placeholder = implode(',', array_fill(0, count($id_sub_jenis_arr), '?'));
    $where[] = "t.id_sub_jenis_pembayaran IN ($in_placeholder)";
    $types .= str_repeat('i', count($id_sub_jenis_arr));
    foreach ($id_sub_jenis_arr as $v) $params[] = $v;
}

if (!empty($id_metode_arr)) {
    $in_placeholder = implode(',', array_fill(0, count($id_metode_arr), '?'));
    $where[] = "t.id_metode_pembayaran IN ($in_placeholder)";
    $types .= str_repeat('i', count($id_metode_arr));
    foreach ($id_metode_arr as $v) $params[] = $v;
}

if (!empty($id_sumber_arr)) {
    $sumber_ids = [];
    $has_tunai = false;
    foreach ($id_sumber_arr as $v) {
        if ($v === -1) {
            $has_tunai = true;
        } elseif ($v > 0) {
            $sumber_ids[] = $v;
        }
    }
    
    $sumber_conditions = [];
    if (!empty($sumber_ids)) {
        $in_placeholder = implode(',', array_fill(0, count($sumber_ids), '?'));
        $sumber_conditions[] = "t.id_sumber_rekening IN ($in_placeholder)";
        $types .= str_repeat('i', count($sumber_ids));
        foreach ($sumber_ids as $v) {
            $params[] = $v;
        }
    }
    if ($has_tunai) {
        $sumber_conditions[] = "(t.id_sumber_rekening IS NULL OR t.id_sumber_rekening = 0)";
    }
    if (!empty($sumber_conditions)) {
        $where[] = "(" . implode(" OR ", $sumber_conditions) . ")";
    }
}

if (!empty($id_kategori_arr)) {
    $cat_conditions = [];
    foreach ($id_kategori_arr as $kat_id) {
        $id_str = (int)$kat_id;
        $cat_conditions[] = "(t.id_kategori_dana LIKE '%[$id_str,%' OR t.id_kategori_dana LIKE '%,$id_str,%' OR t.id_kategori_dana LIKE '%,$id_str]%' OR t.id_kategori_dana = '[$id_str]')";
    }
    $where[] = "(" . implode(" OR ", $cat_conditions) . ")";
}

$where_sql = "WHERE " . implode(' AND ', $where);

// --- Dropdowns Data ---
$jenisList = get_jenis_pembayaran_list();
$upzList = get_upz_list();
$subJenisList = $conn->query("SELECT * FROM sub_jenis_pembayaran")->fetch_all(MYSQLI_ASSOC);
$metodeList = $conn->query("SELECT * FROM metode_pembayaran")->fetch_all(MYSQLI_ASSOC);
$sumberList = $conn->query("SELECT * FROM sumber_rekening")->fetch_all(MYSQLI_ASSOC);
$kategoriList = get_kategori_dana_list();

// --- Data Fetching & Pivoting ---
$sql = "SELECT 
            COALESCE(m.nama_muzaki, u.nama_upz, 'UMUM') as nama_entitas,
            COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') as kategori_upz_name,
            MONTH(t.tanggal_transaksi) as bulan,
            SUM(t.jumlah_pembayaran) as total
        FROM transaksi t
        LEFT JOIN muzaki m ON t.id_muzaki = m.id
        LEFT JOIN upz u ON t.id_upz = u.id
        LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
        LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
        LEFT JOIN kategori_upz ku_m ON upz_m.id_kategori_upz = ku_m.id
        $where_sql
        GROUP BY nama_entitas, kategori_upz_name, bulan
        ORDER BY 
            CASE WHEN COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') = 'Muzaki Individu / Non-UPZ' THEN 0 ELSE 1 END ASC,
            COALESCE(ku.urutan, ku_m.urutan, 9999) ASC,
            kategori_upz_name ASC, 
            nama_entitas ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$raw_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$reportData = [];
$monthTotals = array_fill(1, 12, 0);
$grandTotal = 0;

$groupedByKategori = [];
$kategoriMonthTotals = [];
$kategoriGrandTotals = [];

foreach ($raw_data as $row) {
    $name = $row['nama_entitas'];
    $kategori = $row['kategori_upz_name'];
    $month = (int)$row['bulan'];
    $amount = (float)$row['total'];
    
    if (!isset($reportData[$name])) {
        $reportData[$name] = array_fill(1, 12, 0);
        $reportData[$name]['row_total'] = 0;
    }
    $reportData[$name][$month] += $amount;
    $reportData[$name]['row_total'] += $amount;
    $monthTotals[$month] += $amount;
    $grandTotal += $amount;
    
    if (!isset($groupedByKategori[$kategori])) {
        $groupedByKategori[$kategori] = [];
        $kategoriMonthTotals[$kategori] = array_fill(1, 12, 0);
        $kategoriGrandTotals[$kategori] = 0;
    }
    if (!isset($groupedByKategori[$kategori][$name])) {
        $groupedByKategori[$kategori][$name] = array_fill(1, 12, 0);
        $groupedByKategori[$kategori][$name]['row_total'] = 0;
    }
    $groupedByKategori[$kategori][$name][$month] += $amount;
    $groupedByKategori[$kategori][$name]['row_total'] += $amount;
    $kategoriMonthTotals[$kategori][$month] += $amount;
    $kategoriGrandTotals[$kategori] += $amount;
}

// --- Determine Active Months with Transactions ---
$activeMonths = [];
for ($m = 1; $m <= 12; $m++) {
    if ($monthTotals[$m] > 0) {
        $activeMonths[] = $m;
    }
}
if (empty($activeMonths)) {
    $startMonth = (int)date('m', strtotime($start_date));
    $endMonth = (int)date('m', strtotime($end_date));
    for ($m = $startMonth; $m <= $endMonth; $m++) {
        $activeMonths[] = $m;
    }
}

// --- Fetch Grouped Data for Rekapitulasi Kategori UPZ per Jenis Pembayaran (Same Filters) ---
$sqlRecapUPZ = "
    SELECT 
        COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') as kategori_upz_name,
        COALESCE(ku.id, ku_m.id, -1) as id_kategori_upz,
        t.id_jenis_pembayaran,
        SUM(t.jumlah_pembayaran) as total
    FROM transaksi t
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN kategori_upz ku_m ON upz_m.id_kategori_upz = ku_m.id
    $where_sql
    GROUP BY kategori_upz_name, id_kategori_upz, t.id_jenis_pembayaran
    ORDER BY 
        CASE WHEN COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') = 'Muzaki Individu / Non-UPZ' THEN 0 ELSE 1 END ASC,
        COALESCE(ku.urutan, ku_m.urutan, 9999) ASC,
        kategori_upz_name ASC
";

$stmtRecapUPZ = $conn->prepare($sqlRecapUPZ);
$stmtRecapUPZ->bind_param($types, ...$params);
$stmtRecapUPZ->execute();
$recapDataRaw = $stmtRecapUPZ->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRecapUPZ->close();

$recapKategoriJenis = [];
$kategoriUPZIdMap = [];

foreach ($groupedByKategori as $katName => $unused) {
    $recapKategoriJenis[$katName] = [];
    foreach ($jenisList as $j) {
        $recapKategoriJenis[$katName][$j['id']] = 0;
    }
    $recapKategoriJenis[$katName]['total'] = 0;
}

foreach ($recapDataRaw as $row) {
    $kategori = $row['kategori_upz_name'];
    $kategori_id = (int)$row['id_kategori_upz'];
    $jenis_id = (int)$row['id_jenis_pembayaran'];
    $amount = (float)$row['total'];
    
    $kategoriUPZIdMap[$kategori] = $kategori_id;
    
    if (!isset($recapKategoriJenis[$kategori])) {
        $recapKategoriJenis[$kategori] = [];
        foreach ($jenisList as $j) {
            $recapKategoriJenis[$kategori][$j['id']] = 0;
        }
        $recapKategoriJenis[$kategori]['total'] = 0;
    }
    $recapKategoriJenis[$kategori][$jenis_id] = $amount;
    $recapKategoriJenis[$kategori]['total'] += $amount;
}

// --- Fetch Grouped Data for Rekapitulasi Kategori Dana (Same Filters) ---
$sqlKat = "
    SELECT 
        t.id_jenis_pembayaran as id_jenis,
        t.id_sub_jenis_pembayaran,
        jp.nama_jenis,
        sjp.nama_sub_jenis,
        t.id_kategori_dana,
        t.jumlah_pembayaran
    FROM transaksi t
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    $where_sql
    ORDER BY jp.id ASC, sjp.id ASC
";

$stmtKat = $conn->prepare($sqlKat);
$stmtKat->bind_param($types, ...$params);
$stmtKat->execute();
$data_res = $stmtKat->get_result();

$groupedData = [];
$gColTotals = []; $gTotalAll = 0;
foreach ($kategoriList as $k) $gColTotals[$k['id']] = 0;

while ($row = $data_res->fetch_assoc()) {
    $jenis = $row['nama_jenis'] ?? 'LAINNYA';
    $sub_id = (int)$row['id_sub_jenis_pembayaran'];
    $sub_nama = $row['nama_sub_jenis'] ?? 'Umum';
    if (!isset($groupedData[$jenis])) $groupedData[$jenis] = [];
    if (!isset($groupedData[$jenis][$sub_id])) {
        $groupedData[$jenis][$sub_id] = [
            'id_jenis' => (int)$row['id_jenis'],
            'nama' => $sub_nama, 
            'kategori' => [], 
            'total_all' => 0
        ];
        foreach ($kategoriList as $k) $groupedData[$jenis][$sub_id]['kategori'][$k['id']] = 0;
    }
    $amount = (float)$row['jumlah_pembayaran'];
    $groupedData[$jenis][$sub_id]['total_all'] += $amount;
    $gTotalAll += $amount;
    $katIds = parse_kategori_dana_ids($row['id_kategori_dana']);
    foreach ($kategoriList as $k) {
        if (in_array($k['id'], $katIds)) {
            $groupedData[$jenis][$sub_id]['kategori'][$k['id']] += $amount;
            $gColTotals[$k['id']] += $amount;
        }
    }
}
$stmtKat->close();

// --- Fetch Grouped Data for Rekapitulasi per Sumber Rekening (Same Filters) ---
$sqlRekening = "
    SELECT 
        t.id_sumber_rekening,
        COALESCE(sr.nama_sumber, 'TUNAI / KAS') as nama_rekening,
        t.id_jenis_pembayaran as id_jenis,
        t.id_sub_jenis_pembayaran,
        jp.nama_jenis,
        sjp.nama_sub_jenis,
        t.id_kategori_dana,
        t.jumlah_pembayaran
    FROM transaksi t
    LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    $where_sql
    ORDER BY sr.nama_sumber ASC, jp.id ASC, sjp.id ASC
";

$stmtRek = $conn->prepare($sqlRekening);
$stmtRek->bind_param($types, ...$params);
$stmtRek->execute();
$data_res_rek = $stmtRek->get_result();

$groupedRekening = [];
$gRekColTotals = [];
foreach ($kategoriList as $k) $gRekColTotals[$k['id']] = 0;

while ($row = $data_res_rek->fetch_assoc()) {
    $rekening = $row['nama_rekening'] ?? 'TUNAI / KAS';
    $rekening_id = (int)$row['id_sumber_rekening'];
    $sub_id = (int)$row['id_sub_jenis_pembayaran'];
    $jenis_nama = $row['nama_jenis'] ?? 'LAINNYA';
    $sub_nama = $row['nama_sub_jenis'] ?? 'Umum';
    $row_label = $jenis_nama . ' - ' . $sub_nama;
    
    if (!isset($groupedRekening[$rekening])) {
        $groupedRekening[$rekening] = [
            'id' => $rekening_id === 0 ? -1 : $rekening_id,
            'subs' => []
        ];
    }
    if (!isset($groupedRekening[$rekening]['subs'][$sub_id])) {
        $groupedRekening[$rekening]['subs'][$sub_id] = [
            'id_jenis' => (int)$row['id_jenis'],
            'label' => $row_label, 
            'kategori' => [], 
            'total_all' => 0
        ];
        foreach ($kategoriList as $k) {
            $groupedRekening[$rekening]['subs'][$sub_id]['kategori'][$k['id']] = 0;
        }
    }
    
    $amount = (float)$row['jumlah_pembayaran'];
    $groupedRekening[$rekening]['subs'][$sub_id]['total_all'] += $amount;
    
    $katIds = parse_kategori_dana_ids($row['id_kategori_dana']);
    foreach ($kategoriList as $k) {
        if (in_array($k['id'], $katIds)) {
            $groupedRekening[$rekening]['subs'][$sub_id]['kategori'][$k['id']] += $amount;
            $gRekColTotals[$k['id']] += $amount;
        }
    }
}
$stmtRek->close();

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <!-- Filter Card - STANDARD PATTERN -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-filter mr-2"></i>Filter Laporan</h3>
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET" action="index.php">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Dari Tanggal</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" value="<?= $start_date ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" class="form-control" name="end_date" id="end_date" value="<?= $end_date ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Jenis Pembayaran</label>
                                <select class="form-control select2" name="id_jenis[]" multiple="multiple" data-placeholder="Semua Jenis">
                                    <?php foreach ($jenisList as $j): ?>
                                    <option value="<?= $j['id'] ?>" <?= in_array($j['id'], $id_jenis_arr) ? 'selected' : '' ?>><?= htmlspecialchars($j['nama_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Muzaki dari UPZ</label>
                                <select class="form-control select2" name="id_upz[]" multiple="multiple" data-placeholder="Semua UPZ / Individu">
                                    <?php foreach ($upzList as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= in_array($u['id'], $id_upz_arr) ? 'selected' : '' ?>><?= htmlspecialchars($u['nama_upz']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sub Jenis Pembayaran</label>
                                <select class="form-control select2" name="id_sub_jenis[]" multiple="multiple" data-placeholder="Semua Sub Jenis">
                                    <?php foreach ($subJenisList as $sj): ?>
                                    <option value="<?= $sj['id'] ?>" <?= in_array($sj['id'], $id_sub_jenis_arr) ? 'selected' : '' ?>><?= htmlspecialchars($sj['nama_sub_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Kategori Dana</label>
                                <select class="form-control select2" name="id_kategori[]" multiple="multiple" data-placeholder="Semua Kategori">
                                    <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= in_array($k['id'], $id_kategori_arr) ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Metode Pembayaran</label>
                                <select class="form-control select2" name="id_metode[]" multiple="multiple" data-placeholder="Semua Metode">
                                    <?php foreach ($metodeList as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= in_array($m['id'], $id_metode_arr) ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_metode']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sumber Rekening</label>
                                <select class="form-control select2" name="id_sumber[]" multiple="multiple" data-placeholder="Semua Sumber">
                                    <option value="-1" <?= in_array(-1, $id_sumber_arr) ? 'selected' : '' ?>>TUNAI / KAS (Tanpa Rekening)</option>
                                    <?php foreach ($sumberList as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $id_sumber_arr) ? 'selected' : '' ?>><?= htmlspecialchars($s['nama_sumber']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6 mb-2">
                            <strong>Rentang Cepat:</strong>
                            <div class="btn-group btn-group-sm ml-2">
                                <button type="button" class="btn btn-outline-info" onclick="setRange('this_month')">Bulan Ini</button>
                                <button type="button" class="btn btn-outline-info" onclick="setRange('last_month')">Bulan Lalu</button>
                                <button type="button" class="btn btn-outline-info" onclick="setRange('this_quarter')">Triwulan Ini</button>
                                <button type="button" class="btn btn-outline-info" onclick="setRange('this_year')">Tahun Ini</button>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" class="btn btn-success mr-1 shadow-sm px-4"><i class="fas fa-search mr-1"></i>Tampilkan</button>
                            <a href="index.php" class="btn btn-secondary shadow-sm px-4"><i class="fas fa-undo mr-1"></i>Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Toolbar Export - STANDARD PATTERN -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-2 px-3 bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark font-weight-bold"><i class="fas fa-poll mr-2"></i>Hasil Laporan</h5>
                <form id="exportForm" action="export.php" method="POST" target="_blank" class="form-inline mb-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="start_date" value="<?= $start_date ?>">
                    <input type="hidden" name="end_date" value="<?= $end_date ?>">
                    <?php foreach ($id_jenis_arr as $jenis_id): ?>
                    <input type="hidden" name="id_jenis[]" value="<?= htmlspecialchars($jenis_id) ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_upz_arr as $upz_id): ?>
                    <input type="hidden" name="id_upz[]" value="<?= htmlspecialchars($upz_id) ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_sub_jenis_arr as $sub_jenis_id): ?>
                    <input type="hidden" name="id_sub_jenis[]" value="<?= htmlspecialchars($sub_jenis_id) ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_kategori_arr as $kat_id): ?>
                    <input type="hidden" name="id_kategori[]" value="<?= htmlspecialchars($kat_id) ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_metode_arr as $metode_id): ?>
                    <input type="hidden" name="id_metode[]" value="<?= htmlspecialchars($metode_id) ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_sumber_arr as $sumber_id): ?>
                    <input type="hidden" name="id_sumber[]" value="<?= htmlspecialchars($sumber_id) ?>">
                    <?php endforeach; ?>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-success dropdown-toggle shadow-sm px-3" type="button" data-toggle="dropdown">
                            <i class="fas fa-download mr-1"></i>Unduh Laporan
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                            <h6 class="dropdown-header">Pilih Format</h6>
                            <a class="dropdown-item export-btn" href="#" data-format="pdf"><i class="fas fa-file-pdf text-danger mr-2"></i>PDF (Professional)</a>
                            <a class="dropdown-item export-btn" href="#" data-format="excel"><i class="fas fa-file-excel text-success mr-2"></i>Excel (XLS)</a>
                            <a class="dropdown-item export-btn" href="#" data-format="csv"><i class="fas fa-file-csv text-primary mr-2"></i>CSV (Data Mentah)</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-table mr-2"></i>Laporan Tahunan - Rincian Muzaki/UPZ</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="annualTable" style="font-size: 0.75rem;">
                        <thead class="bg-gray-light">
                            <tr class="text-center">
                                <th width="3%" style="vertical-align: middle;">NO</th>
                                <th style="vertical-align: middle;">NAMA MUZAKI/UPZ</th>
                                <?php foreach ($activeMonths as $m): ?>
                                    <th width="7%"><?= strtoupper(substr(month_name($m), 0, 3)) ?></th>
                                <?php endforeach; ?>
                                <th width="10%" style="vertical-align: middle;">JUMLAH</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportData)): ?>
                                <tr><td colspan="<?= 3 + count($activeMonths) ?>" class="text-center py-4 text-muted">Tidak ada data untuk kriteria filter ini.</td></tr>
                            <?php else: ?>
                                <?php $no = 1; foreach($reportData as $name => $data): ?>
                                    <tr>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
                                        <?php foreach ($activeMonths as $m): ?>
                                            <td class="text-right"><?= $data[$m] > 0 ? number_format($data[$m], 0, ',', '.') : '-' ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-right font-weight-bold bg-light"><?= number_format($data['row_total'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-light font-weight-bold text-right">
                            <tr>
                                <td colspan="2" class="text-center">TOTAL KESELURUHAN</td>
                                <?php foreach ($activeMonths as $m): ?>
                                    <td><?= number_format($monthTotals[$m], 0, ',', '.') ?></td>
                                <?php endforeach; ?>
                                <td class="bg-warning text-dark"><?= number_format($grandTotal, 0, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Grouped by Kategori Table -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-success"><i class="fas fa-sitemap mr-2"></i>Laporan Tahunan - Rincian Pengumpulan per Kategori UPZ</h3>
            </div>
            <div class="card-body">
                <?php if (empty($groupedByKategori)): ?>
                    <p class="text-center py-4 text-muted">Tidak ada data untuk kriteria filter ini.</p>
                <?php else: ?>
                    <?php foreach ($groupedByKategori as $katName => $entities): ?>
                        <div class="mb-4">
                            <h5 class="font-weight-bold text-dark mb-2">
                                <span class="badge badge-outline-success p-2" style="border:1px solid #0d9f4f; color:#0d9f4f; background-color:rgba(13,159,79,0.05); font-size:0.9rem;">
                                    Kategori Sektoral: <?= htmlspecialchars($katName) ?>
                                </span>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" style="font-size: 0.75rem;">
                                    <thead class="bg-gray-light">
                                        <tr class="text-center">
                                            <th width="3%" style="vertical-align: middle;">NO</th>
                                            <th style="vertical-align: middle;">NAMA MUZAKI/UPZ</th>
                                            <?php foreach ($activeMonths as $m): ?>
                                                <th width="7%"><?= strtoupper(substr(month_name($m), 0, 3)) ?></th>
                                            <?php endforeach; ?>
                                            <th width="10%" style="vertical-align: middle;">JUMLAH</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $subNo = 1; foreach($entities as $eName => $eData): ?>
                                            <tr>
                                                <td class="text-center"><?= $subNo++ ?></td>
                                                <td><strong><?= htmlspecialchars($eName) ?></strong></td>
                                                <?php foreach ($activeMonths as $m): ?>
                                                    <td class="text-right"><?= $eData[$m] > 0 ? number_format($eData[$m], 0, ',', '.') : '-' ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right font-weight-bold bg-light"><?= number_format($eData['row_total'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light font-weight-bold text-right">
                                        <tr>
                                            <td colspan="2" class="text-center">SUBTOTAL KATEGORI</td>
                                            <?php foreach ($activeMonths as $m): ?>
                                                <td><?= number_format($kategoriMonthTotals[$katName][$m], 0, ',', '.') ?></td>
                                            <?php endforeach; ?>
                                            <td class="bg-warning text-dark"><?= number_format($kategoriGrandTotals[$katName], 0, ',', '.') ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Rekapitulasi Sektoral UPZ per Jenis Pembayaran (Satu Kesatuan Di Sini) -->
                    <hr class="my-5" style="border-top: 2px dashed #b8b8b8;">
                    <h5 class="font-weight-bold text-success mb-3">
                        <i class="fas fa-chart-bar mr-2"></i>Rekapitulasi Sektoral UPZ per Jenis Pembayaran
                    </h5>
                    <div class="table-responsive">
                        <table id="sektoralRecapTable" class="table table-bordered table-hover mb-0" style="font-size: 0.85rem;">
                            <thead class="bg-gray-light">
                                <tr class="text-center">
                                    <th>KATEGORI SEKTORAL UPZ</th>
                                    <?php foreach ($jenisList as $j): ?>
                                        <th width="15%" class="text-right"><?= strtoupper(htmlspecialchars($j['nama_jenis'])) ?></th>
                                    <?php endforeach; ?>
                                    <th width="15%" class="text-right">TOTAL</th>
                                    <th class="text-center" width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recapKategoriJenis)): ?>
                                    <tr><td colspan="<?= count($jenisList) + 3 ?>" class="text-center py-4 text-muted">Data kosong untuk filter ini.</td></tr>
                                <?php else: ?>
                                    <?php 
                                    $colTotals = [];
                                    foreach ($jenisList as $j) $colTotals[$j['id']] = 0;
                                    $grandTotalRecap = 0;
                                    
                                    foreach ($recapKategoriJenis as $katName => $data): 
                                        $grandTotalRecap += $data['total'];
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($katName) ?></strong></td>
                                        <?php foreach ($jenisList as $j): 
                                            $val = $data[$j['id']];
                                            $colTotals[$j['id']] += $val;
                                        ?>
                                            <td class="text-right">Rp <?= number_format($val, 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-right font-weight-bold bg-light">Rp <?= number_format($data['total'], 0, ',', '.') ?></td>
                                        <td class="text-center">
                                            <?php 
                                                $kat_id = $kategoriUPZIdMap[$katName] ?? -1;
                                                $detailParams = [
                                                    'start_date' => $start_date,
                                                    'end_date' => $end_date,
                                                    'id_kategori_upz[]' => $kat_id
                                                ];
                                                $queryString = http_build_query($detailParams);
                                                foreach ($id_jenis_arr as $val) $queryString .= '&id_jenis[]=' . $val;
                                                foreach ($id_upz_arr as $val) $queryString .= '&id_upz[]=' . $val;
                                                foreach ($id_sub_jenis_arr as $val) $queryString .= '&id_sub_jenis[]=' . $val;
                                                foreach ($id_metode_arr as $val) $queryString .= '&id_metode[]=' . $val;
                                                foreach ($id_sumber_arr as $val) $queryString .= '&id_sumber[]=' . $val;
                                                foreach ($id_kategori_arr as $val) $queryString .= '&id_kategori[]=' . $val;
                                            ?>
                                            <a href="<?= url('pages/laporan/index.php?' . $queryString) ?>" class="btn btn-info btn-xs shadow-sm">
                                                <i class="fas fa-search-dollar mr-1"></i>Detail
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($recapKategoriJenis)): ?>
                            <tfoot class="bg-gray-light font-weight-bold text-right">
                                <tr>
                                    <td class="text-center">TOTAL KESELURUHAN</td>
                                    <?php foreach ($jenisList as $j): ?>
                                        <td>Rp <?= number_format($colTotals[$j['id']], 0, ',', '.') ?></td>
                                    <?php endforeach; ?>
                                    <td class="bg-warning text-dark">Rp <?= number_format($grandTotalRecap, 0, ',', '.') ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sumber Rekening Summary Table -->
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-university mr-2"></i>Rekapitulasi per Sumber Rekening</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="rekeningSummaryTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead class="bg-gray-light">
                            <tr>
                                <th>Sumber Rekening & Sub-Jenis</th>
                                <?php foreach ($kategoriList as $k): ?>
                                    <th class="text-right"><?= htmlspecialchars($k['nama_kategori']) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedRekening)): ?>
                                <tr><td colspan="<?= count($kategoriList) + 2 ?>" class="text-center py-4 text-muted">Data kosong untuk filter ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedRekening as $rekening => $accData): ?>
                                    <?php 
                                        $rekening_id = $accData['id'];
                                        $subs = $accData['subs'];
                                        
                                        $subsum_kategori = [];
                                        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
                                        foreach ($subs as $sub_id => $d) {
                                            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
                                        }
                                    ?>
                                    <tr class="table-info font-weight-bold">
                                        <td><?= strtoupper($rekening) ?></td>
                                        <?php foreach ($kategoriList as $k): ?>
                                            <td class="text-right">Rp <?= number_format($subsum_kategori[$k['id']], 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                    <?php foreach ($subs as $sub_id => $data): ?>
                                        <tr>
                                            <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($data['label']) ?></td>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <td class="text-right">Rp <?= number_format($data['kategori'][$k['id']], 0, ',', '.') ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-center">
                                                <?php 
                                                    $detailParams = [
                                                        'start_date' => $start_date,
                                                        'end_date' => $end_date,
                                                        'id_jenis[]' => $data['id_jenis'],
                                                        'id_sub_jenis[]' => $sub_id
                                                    ];
                                                    if ($rekening_id > 0 || $rekening_id === -1) {
                                                        $detailParams['id_sumber[]'] = $rekening_id;
                                                    }
                                                    $queryString = http_build_query($detailParams);
                                                    foreach ($id_upz_arr as $val) $queryString .= '&id_upz[]=' . $val;
                                                    foreach ($id_metode_arr as $val) $queryString .= '&id_metode[]=' . $val;
                                                    if (!isset($detailParams['id_sumber[]'])) {
                                                        foreach ($id_sumber_arr as $val) $queryString .= '&id_sumber[]=' . $val;
                                                    }
                                                    foreach ($id_kategori_arr as $kat_id) {
                                                        $queryString .= '&id_kategori[]=' . $kat_id;
                                                    }
                                                ?>
                                                <a href="<?= url('pages/laporan/index.php?' . $queryString) ?>" class="btn btn-info btn-xs">
                                                    <i class="fas fa-search-dollar mr-1"></i>Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($groupedRekening)): ?>
                        <tfoot class="bg-gray-light font-weight-bold">
                            <tr>
                                <td class="text-right">TOTAL KESELURUHAN</td>
                                <?php foreach ($kategoriList as $k): ?>
                                    <td class="text-right">Rp <?= number_format($gRekColTotals[$k['id']], 0, ',', '.') ?></td>
                                <?php endforeach; ?>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kategori Dana Table -->
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-table mr-2"></i>Rekapitulasi Kategori Dana</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="kategoriTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead class="bg-gray-light">
                            <tr>
                                <th>Sumber Dana (Jenis & Sub-Jenis)</th>
                                <?php foreach ($kategoriList as $k): ?>
                                    <th class="text-right"><?= htmlspecialchars($k['nama_kategori']) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedData)): ?>
                                <tr><td colspan="<?= count($kategoriList) + 2 ?>" class="text-center py-4 text-muted">Data kosong untuk filter ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedData as $jenis => $subs): ?>
                                    <?php 
                                        $subsum_kategori = []; 
                                        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
                                        foreach ($subs as $sub_id => $d) {
                                            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
                                        }
                                    ?>
                                    <tr class="table-secondary font-weight-bold">
                                        <td><?= strtoupper($jenis) ?></td>
                                        <?php foreach ($kategoriList as $k): ?>
                                            <td class="text-right">Rp <?= number_format($subsum_kategori[$k['id']], 0, ',', '.') ?></td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                    <?php foreach ($subs as $sub_id => $data): ?>
                                        <tr>
                                            <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($data['nama']) ?></td>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <td class="text-right">Rp <?= number_format($data['kategori'][$k['id']], 0, ',', '.') ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-center">
                                                <?php 
                                                    $detailParams = [
                                                        'start_date' => $start_date,
                                                        'end_date' => $end_date,
                                                        'id_jenis[]' => $data['id_jenis'],
                                                        'id_sub_jenis[]' => $sub_id
                                                    ];
                                                    $queryString = http_build_query($detailParams);
                                                    foreach ($id_upz_arr as $val) $queryString .= '&id_upz[]=' . $val;
                                                    foreach ($id_metode_arr as $val) $queryString .= '&id_metode[]=' . $val;
                                                    foreach ($id_sumber_arr as $val) $queryString .= '&id_sumber[]=' . $val;
                                                    foreach ($id_kategori_arr as $kat_id) {
                                                        $queryString .= '&id_kategori[]=' . $kat_id;
                                                    }
                                                ?>
                                                <a href="<?= url('pages/laporan/index.php?' . $queryString) ?>" class="btn btn-info btn-xs">
                                                    <i class="fas fa-search-dollar mr-1"></i>Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($groupedData)): ?>
                        <tfoot class="bg-gray-light font-weight-bold">
                            <tr>
                                <td class="text-right">TOTAL KESELURUHAN</td>
                                <?php foreach ($kategoriList as $k): ?>
                                    <td class="text-right">Rp <?= number_format($gColTotals[$k['id']], 0, ',', '.') ?></td>
                                <?php endforeach; ?>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
            </div>
        </div>

<script>
$(function() {
    $("#annualTable").DataTable({
        "pageLength": 50,
        "colReorder": true,
        "order": []
    });
    $(".select2").select2({ theme: "bootstrap4", width: "100%" });

    $(".export-btn").on("click", function(e) {
        e.preventDefault();
        const format = $(this).data("format");
        const form = $("#exportForm");
        $('<input>').attr({type: 'hidden', name: 'format', value: format}).appendTo(form);
        form.submit();
        form.find('input[name="format"]').remove();
    });
});

function setRange(type) {
    const today = new Date();
    let start = ''; let end = '';
    const formatDate = (date) => { const d = String(date.getDate()).padStart(2, '0'); const m = String(date.getMonth() + 1).padStart(2, '0'); const y = date.getFullYear(); return `${y}-${m}-${d}`; };
    if (type === 'this_month') { start = formatDate(new Date(today.getFullYear(), today.getMonth(), 1)); end = formatDate(new Date(today.getFullYear(), today.getMonth() + 1, 0)); }
    else if (type === 'last_month') { start = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1)); end = formatDate(new Date(today.getFullYear(), today.getMonth(), 0)); }
    else if (type === 'this_quarter') { const q = Math.floor(today.getMonth() / 3); start = formatDate(new Date(today.getFullYear(), q * 3, 1)); end = formatDate(new Date(today.getFullYear(), q * 3 + 3, 0)); }
    else if (type === 'this_year') { start = formatDate(new Date(today.getFullYear(), 0, 1)); end = formatDate(new Date(today.getFullYear(), 11, 31)); }
    if (start && end) { $('#start_date').val(start); $('#end_date').val(end); }
}
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
