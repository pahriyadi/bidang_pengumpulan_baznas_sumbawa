<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'laporan';
$pageTitle = 'Laporan Transaksi ZIS';
$pageDescription = 'Filter, cetak, dan ekspor data transaksi pengumpulan ZIS';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Laporan & Export</li>';

$conn = db();

// Filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

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
}

$id_kategori_upz_arr = get_filter_arr('id_kategori_upz');
if (!empty($id_kategori_upz_arr)) {
    $cat_upz_conditions = [];
    $normal_ids = [];
    $has_fallback = false;
    foreach ($id_kategori_upz_arr as $v) {
        if ($v === -1) {
            $has_fallback = true;
        } else {
            $normal_ids[] = (int)$v;
        }
    }
    
    $sub_conds = [];
    if (!empty($normal_ids)) {
        $in_placeholder = implode(',', $normal_ids);
        $sub_conds[] = "COALESCE(u.id_kategori_upz, upz_m.id_kategori_upz, 0) IN ($in_placeholder)";
    }
    if ($has_fallback) {
        $sub_conds[] = "(u.id_kategori_upz IS NULL AND upz_m.id_kategori_upz IS NULL)";
    }
    if (!empty($sub_conds)) {
        $where[] = "(" . implode(" OR ", $sub_conds) . ")";
    }
}

$where_sql = implode(' AND ', $where);

// Queries for dropdowns
$jenisList = get_jenis_pembayaran_list();
$upzList = get_upz_list();
$subJenisList = $conn->query("SELECT * FROM sub_jenis_pembayaran")->fetch_all(MYSQLI_ASSOC);
$metodeList = $conn->query("SELECT * FROM metode_pembayaran")->fetch_all(MYSQLI_ASSOC);
$sumberList = $conn->query("SELECT * FROM sumber_rekening")->fetch_all(MYSQLI_ASSOC);
$kategoriList = get_kategori_dana_list();

// Fetch filtered data safely
$sql = "
    SELECT t.*,
        COALESCE(m.nama_muzaki, u.nama_upz, 'UMUM') as nama_muzaki, 
        u.nama_upz, jp.nama_jenis, sjp.nama_sub_jenis,
        mp.nama_metode, sr.nama_sumber
    FROM transaksi t
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
    LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
    WHERE $where_sql
    ORDER BY t.tanggal_transaksi DESC, t.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();

$transactions = [];
$columnTotals = [];
foreach ($jenisList as $j) {
    $columnTotals[$j['id']] = 0;
}
$total_nominal = 0;

while ($row = $data->fetch_assoc()) {
    $total_nominal += (float)$row['jumlah_pembayaran'];
    if (isset($columnTotals[$row['id_jenis_pembayaran']])) {
        $columnTotals[$row['id_jenis_pembayaran']] += (float)$row['jumlah_pembayaran'];
    }
    $transactions[] = $row;
}
$stmt->close();

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
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    WHERE $where_sql
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
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
    WHERE $where_sql
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

// --- Fetch Grouped Data for Rincian Pengumpulan per Kategori UPZ (Same Filters) ---
$sqlRincianUPZ = "
    SELECT 
        COALESCE(m.nama_muzaki, u.nama_upz, 'UMUM') as nama_entitas,
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
    WHERE $where_sql
    GROUP BY nama_entitas, kategori_upz_name, id_kategori_upz, t.id_jenis_pembayaran
    ORDER BY 
        CASE WHEN COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') = 'Muzaki Individu / Non-UPZ' THEN 0 ELSE 1 END ASC,
        COALESCE(ku.urutan, ku_m.urutan, 9999) ASC,
        kategori_upz_name ASC, 
        nama_entitas ASC
";

$stmtRincianUPZ = $conn->prepare($sqlRincianUPZ);
$stmtRincianUPZ->bind_param($types, ...$params);
$stmtRincianUPZ->execute();
$rincianUPZRaw = $stmtRincianUPZ->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRincianUPZ->close();

$groupedRincianByKategori = [];
$kategoriSektoralTotals = [];
$kategoriSektoralGrand = [];
$kategoriUPZIdMap = [];

foreach ($rincianUPZRaw as $row) {
    $name = $row['nama_entitas'];
    $kategori = $row['kategori_upz_name'];
    $kategori_id = (int)$row['id_kategori_upz'];
    $jenis_id = (int)$row['id_jenis_pembayaran'];
    $amount = (float)$row['total'];
    
    $kategoriUPZIdMap[$kategori] = $kategori_id;
    
    if (!isset($groupedRincianByKategori[$kategori])) {
        $groupedRincianByKategori[$kategori] = [];
        $kategoriSektoralTotals[$kategori] = [];
        foreach ($jenisList as $j) {
            $kategoriSektoralTotals[$kategori][$j['id']] = 0;
        }
        $kategoriSektoralGrand[$kategori] = 0;
    }
    if (!isset($groupedRincianByKategori[$kategori][$name])) {
        $groupedRincianByKategori[$kategori][$name] = [];
        foreach ($jenisList as $j) {
            $groupedRincianByKategori[$kategori][$name][$j['id']] = 0;
        }
        $groupedRincianByKategori[$kategori][$name]['total'] = 0;
    }
    $groupedRincianByKategori[$kategori][$name][$jenis_id] = $amount;
    $groupedRincianByKategori[$kategori][$name]['total'] += $amount;
    
    $kategoriSektoralTotals[$kategori][$jenis_id] += $amount;
    $kategoriSektoralGrand[$kategori] += $amount;
}

$recapKategoriJenis = [];
foreach ($groupedRincianByKategori as $katName => $unused) {
    $recapKategoriJenis[$katName] = [];
    foreach ($jenisList as $j) {
        $recapKategoriJenis[$katName][$j['id']] = 0;
    }
    $recapKategoriJenis[$katName]['total'] = 0;
}

foreach ($rincianUPZRaw as $row) {
    $kategori = $row['kategori_upz_name'];
    $jenis_id = (int)$row['id_jenis_pembayaran'];
    $amount = (float)$row['total'];
    
    if (!isset($recapKategoriJenis[$kategori])) {
        $recapKategoriJenis[$kategori] = [];
        foreach ($jenisList as $j) {
            $recapKategoriJenis[$kategori][$j['id']] = 0;
        }
        $recapKategoriJenis[$kategori]['total'] = 0;
    }
    $recapKategoriJenis[$kategori][$jenis_id] += $amount;
    $recapKategoriJenis[$kategori]['total'] += $amount;
}

include __DIR__ . '/../../inc/header.php';
?>



<div class="row">
    <div class="col-md-12">
        <!-- Filter Card -->
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
                                    <option value="<?= $j['id'] ?>" <?= in_array($j['id'], $id_jenis_arr) ? 'selected' : '' ?>><?= clean($j['nama_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Muzaki dari UPZ</label>
                                <select class="form-control select2" name="id_upz[]" multiple="multiple" data-placeholder="Semua UPZ / Individu">
                                    <?php foreach ($upzList as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= in_array($u['id'], $id_upz_arr) ? 'selected' : '' ?>><?= clean($u['nama_upz']) ?></option>
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
                                    <option value="<?= $sj['id'] ?>" <?= in_array($sj['id'], $id_sub_jenis_arr) ? 'selected' : '' ?>><?= clean($sj['nama_sub_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Kategori Dana</label>
                                <select class="form-control select2" name="id_kategori[]" multiple="multiple" data-placeholder="Semua Kategori">
                                    <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= in_array($k['id'], $id_kategori_arr) ? 'selected' : '' ?>><?= clean($k['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Metode Pembayaran</label>
                                <select class="form-control select2" name="id_metode[]" multiple="multiple" data-placeholder="Semua Metode">
                                    <?php foreach ($metodeList as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= in_array($m['id'], $id_metode_arr) ? 'selected' : '' ?>><?= clean($m['nama_metode']) ?></option>
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
                                    <option value="<?= $s['id'] ?>" <?= in_array($s['id'], $id_sumber_arr) ? 'selected' : '' ?>><?= clean($s['nama_sumber']) ?></option>
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

        <!-- Toolbar Export -->
        <div class="card flat-card mb-3">
            <div class="card-body py-2 px-3 bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark font-weight-bold"><i class="fas fa-poll mr-2"></i>Hasil Laporan</h5>
                <form id="exportForm" method="POST" action="export.php" target="_blank" class="form-inline mb-0">
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
                    
                    <button type="submit" name="export_format" value="csv" class="btn btn-sm btn-info mr-1">
                        <i class="fas fa-file-csv mr-1"></i>CSV
                    </button>
                    <button type="submit" name="export_format" value="excel" class="btn btn-sm btn-success mr-1">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                    <button type="submit" name="export_format" value="pdf" class="btn btn-sm btn-danger mr-1">
                        <i class="fas fa-file-pdf mr-1"></i>PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-dark" onclick="openEnhancedWebCopyModal()">
                        <i class="fas fa-code mr-1"></i>Salin untuk Web
                    </button>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card flat-card shadow-none">
            <div class="card-body">
                <div class="alert alert-info alert-excel mb-4 shadow-none">
                    <h5><i class="icon fas fa-info-circle"></i> Ringkasan</h5>
                    Total Transaksi: <strong><?= count($transactions) ?></strong> data<br>
                    Total Penerimaan (Rp): <strong class="currency-display h4 mb-0"><?= format_currency($total_nominal) ?></strong>
                </div>

                <div class="table-responsive">
                    <table id="dataTable" class="table table-bordered table-hover table-excel" style="font-size: 0.85rem;">
                        <thead>
                            <tr class="bg-light">
                                <th width="3%">No</th>
                                <th>Tanggal</th>
                                <th>Nama Muzaki/UPZ</th>
                                <?php foreach ($jenisList as $j): ?>
                                    <th class="text-right"><?= clean($j['nama_jenis']) ?></th>
                                <?php endforeach; ?>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <?php if (count($transactions) > 0): ?>
                        <tfoot>
                            <tr class="bg-gray-light font-weight-bold">
                                <th colspan="3" class="text-right">TOTAL</th>
                                <?php foreach ($jenisList as $j): ?>
                                <th class="text-right"><?= format_currency($columnTotals[$j['id']]) ?></th>
                                <?php endforeach; ?>
                                <th class="text-right" style="background: #ffc107;"><strong><?= format_currency($total_nominal) ?></strong></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rincian Pengumpulan per Kategori UPZ Card -->
        <div class="card flat-card shadow-none mt-4">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-success mb-0"><i class="fas fa-sitemap mr-2"></i>Rincian Pengumpulan per Kategori UPZ</h3>
            </div>
            <div class="card-body">
                <?php if (empty($groupedRincianByKategori)): ?>
                    <p class="text-center py-4 text-muted mb-0">Tidak ada data untuk kriteria filter ini.</p>
                <?php else: ?>
                    <?php foreach ($groupedRincianByKategori as $katName => $entities): ?>
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
                                            <?php foreach ($jenisList as $j): ?>
                                                <th width="15%" class="text-right"><?= strtoupper(htmlspecialchars($j['nama_jenis'])) ?></th>
                                            <?php endforeach; ?>
                                            <th width="15%" style="vertical-align: middle;" class="text-right">JUMLAH</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $subNo = 1; foreach($entities as $eName => $eData): ?>
                                            <tr>
                                                <td class="text-center"><?= $subNo++ ?></td>
                                                <td><strong><?= htmlspecialchars($eName) ?></strong></td>
                                                <?php foreach ($jenisList as $j): 
                                                    $val = $eData[$j['id']];
                                                ?>
                                                    <td class="text-right"><?= $val > 0 ? 'Rp ' . number_format($val, 0, ',', '.') : '-' ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right font-weight-bold bg-light">Rp <?= number_format($eData['total'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light font-weight-bold text-right">
                                        <tr>
                                            <td colspan="2" class="text-center">SUBTOTAL KATEGORI</td>
                                            <?php foreach ($jenisList as $j): ?>
                                                <td>Rp <?= number_format($kategoriSektoralTotals[$katName][$j['id']], 0, ',', '.') ?></td>
                                            <?php endforeach; ?>
                                            <td class="bg-warning text-dark">Rp <?= number_format($kategoriSektoralGrand[$katName], 0, ',', '.') ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Rekapitulasi Sektoral UPZ per Jenis Pembayaran -->
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

        <div id="rekap-rekening"></div>
        <!-- Sumber Rekening Summary Table -->
        <div class="card flat-card shadow-none mt-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-university mr-2"></i>Rekapitulasi per Sumber Rekening</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="rekeningSummaryTable" class="table table-bordered table-hover table-excel" style="font-size: 0.85rem;">
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
                                            <td class="text-right">Rp <?= number_format($subsum_kategori[$k['id']], 0, ',', '.') ?>,-</td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                    <?php foreach ($subs as $sub_id => $data): ?>
                                        <tr>
                                            <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($data['label']) ?></td>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <td class="text-right">Rp <?= number_format($data['kategori'][$k['id']], 0, ',', '.') ?>,-</td>
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
                                                <a href="<?= url('pages/laporan/index.php?' . $queryString) ?>" class="btn btn-info btn-xs shadow-sm">
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
                                    <td class="text-right">Rp <?= number_format($gRekColTotals[$k['id']], 0, ',', '.') ?>,-</td>
                                <?php endforeach; ?>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <div id="rekap-kategori"></div>
        <!-- Kategori Dana Table -->
        <div class="card flat-card shadow-none mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-table mr-2"></i>Rekapitulasi Kategori Dana</h3>
                <form id="exportFormKategori" action="<?= url('pages/laporan_kategori/export.php') ?>" method="POST" target="_blank" class="form-inline mb-0 ml-auto">
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

                    <div class="btn-group">
                        <button type="submit" name="format" value="csv" class="btn btn-xs btn-info" data-toggle="tooltip" title="Export CSV">
                            <i class="fas fa-file-csv mr-1"></i>CSV
                        </button>
                        <button type="submit" name="format" value="excel" class="btn btn-xs btn-success" data-toggle="tooltip" title="Export Excel">
                            <i class="fas fa-file-excel mr-1"></i>Excel
                        </button>
                        <button type="submit" name="format" value="pdf" class="btn btn-xs btn-danger" data-toggle="tooltip" title="Cetak PDF">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="kategoriTable" class="table table-bordered table-hover table-excel" style="font-size: 0.85rem;">
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
                                            <td class="text-right">Rp <?= number_format($subsum_kategori[$k['id']], 0, ',', '.') ?>,-</td>
                                        <?php endforeach; ?>
                                        <td></td>
                                    </tr>
                                    <?php foreach ($subs as $sub_id => $data): ?>
                                        <tr>
                                            <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($data['nama']) ?></td>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <td class="text-right">Rp <?= number_format($data['kategori'][$k['id']], 0, ',', '.') ?>,-</td>
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
                                                <a href="<?= url('pages/laporan/index.php?' . $queryString) ?>" class="btn btn-info btn-xs shadow-sm">
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
                                    <td class="text-right">Rp <?= number_format($gColTotals[$k['id']], 0, ',', '.') ?>,-</td>
                                <?php endforeach; ?>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Salin untuk Web (Enhanced) -->
<div class="modal fade" id="webCopyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content text-left">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-code mr-2"></i>Salin Paket Laporan untuk Website Utama</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body bg-light">
                <div class="alert alert-warning py-2 mb-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Laporan ini juga menyertakan <strong>Dashboard Dashboard</strong> dan <strong>Grafik Distribusi</strong> visual yang cocok untuk dipasang di website.
                </div>
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="font-weight-bold mb-2">Pratinjau Tampilan Web:</h6>
                        <div id="web-preview-container" class="border shadow-sm p-4 bg-white" style="max-height: 550px; overflow-y: auto;"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-2">Kode HTML (Salin ke Summernote Mode 'Code View'):</h6>
                        <textarea id="web-code-area" class="form-control" rows="22" style="font-family: 'Courier New', monospace; font-size: 11px; background: #2b2b2b; color: #a9b7c6;" readonly></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success px-4 shadow-sm" onclick="copyEnhancedWebCode()">
                    <i class="fas fa-copy mr-1"></i>Salin Semua Kode
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../inc/footer.php'; ?>

<script>
const reportTransactions = <?= json_encode($transactions) ?>;
const reportJenisList = <?= json_encode($jenisList) ?>;
const reportStartDate = '<?= date('d/m/Y', strtotime($start_date)) ?>';
const reportEndDate = '<?= date('d/m/Y', strtotime($end_date)) ?>';

$(function() {
    const dataSet = reportTransactions.map((row, idx) => {
        const formatCurrency = (val) => {
            return 'Rp ' + parseInt(val).toLocaleString('id-ID');
        };
        
        const parts = row.tanggal_transaksi.split('-');
        const dateFormatted = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : row.tanggal_transaksi;
        
        const cols = [
            idx + 1,
            dateFormatted,
            '<strong>' + (row.nama_muzaki || '-') + '</strong>'
        ];
        
        reportJenisList.forEach(j => {
            if (row.id_jenis_pembayaran == j.id) {
                cols.push('<div class="text-right">' + formatCurrency(row.jumlah_pembayaran) + '</div>');
            } else {
                cols.push('<div class="text-right">-</div>');
            }
        });
        
        cols.push('<div class="text-right"><strong>' + formatCurrency(row.jumlah_pembayaran) + '</strong></div>');
        
        return cols;
    });

    try {
        $('#dataTable').DataTable({
            data: dataSet,
            deferRender: true,
            order: [],
            pageLength: 25,
            colReorder: true,
            dom: '<"row"<"col-sm-12 col-md-6"f><"col-sm-12 col-md-6"l>>rtip',
            language: {
                emptyTable: "Tidak ada transaksi pada periode dan kriteria tersebut.",
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
    } catch (e) {
        console.warn("DataTable initialization failed: ", e);
    }
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});

function openEnhancedWebCopyModal() {
    // 1. Calculations
    let totalNominal = 0;
    let counts = reportTransactions.length;
    let jenisTotals = {};
    reportJenisList.forEach(j => jenisTotals[j.id] = 0);

    reportTransactions.forEach(t => {
        const amt = parseFloat(t.jumlah_pembayaran);
        totalNominal += amt;
        if (jenisTotals[t.id_jenis_pembayaran] !== undefined) {
            jenisTotals[t.id_jenis_pembayaran] += amt;
        }
    });

    const average = counts > 0 ? (totalNominal / counts) : 0;

    // 2. Build HTML
    let html = `<div style="font-family: 'Segoe UI', Arial, sans-serif; color: #333; margin: 0 auto; max-width: 1000px; line-height: 1.6;">
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0d9f4f; padding-bottom: 15px;">
            <h2 style="margin: 0; color: #0d9f4f; text-transform: uppercase; font-size: 24px;">Laporan Ringkasan Penerimaan ZIS</h2>
            <p style="margin: 5px 0; color: #666;">Periode: ${reportStartDate} s/d ${reportEndDate}</p>
        </div>

        <!-- Dashboard Cards -->
        <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; justify-content: center;">
            <div style="flex: 1; min-width: 200px; background: #0d9f4f; color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.9; margin-bottom: 5px;">Total Penerimaan</div>
                <div style="font-size: 20px; font-weight: bold;">Rp ${totalNominal.toLocaleString('id-ID')}</div>
            </div>
            <div style="flex: 1; min-width: 200px; background: #ffc107; color: #333; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.8; margin-bottom: 5px;">Jumlah Muzaki</div>
                <div style="font-size: 20px; font-weight: bold;">${counts.toLocaleString('id-ID')} Orang</div>
            </div>
            <div style="flex: 1; min-width: 200px; background: #17a2b8; color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 12px; text-transform: uppercase; opacity: 0.9; margin-bottom: 5px;">Rata-rata</div>
                <div style="font-size: 20px; font-weight: bold;">Rp ${average.toLocaleString('id-ID')}</div>
            </div>
        </div>

        <!-- Graphics (CSS Bar Chart) -->
        <div style="margin-bottom: 40px; background: #fdfdfd; padding: 25px; border: 1px solid #eee; border-radius: 10px;">
            <h4 style="margin-top: 0; margin-bottom: 20px; border-left: 5px solid #0d9f4f; padding-left: 10px;">Distribusi Dana Berdasarkan Kategori</h4>
            <div style="padding: 10px 0;">`;

    reportJenisList.forEach(j => {
        const val = jenisTotals[j.id];
        const percentage = totalNominal > 0 ? (val / totalNominal * 100) : 0;
        if (val > 0) {
            html += `<div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; font-weight: 600;">
                    <span>${j.nama_jenis}</span>
                    <span>Rp ${val.toLocaleString('id-ID')} (${percentage.toFixed(1)}%)</span>
                </div>
                <div style="height: 12px; background: #e9ecef; border-radius: 10px; overflow: hidden;">
                    <div style="width: ${percentage}%; height: 100%; background: #0d9f4f; border-radius: 10px;"></div>
                </div>
            </div>`;
        }
    });

    html += `   </div>
        </div>

        <!-- Transaction Table -->
        <div style="overflow-x: auto;">
            <h4 style="margin-top: 0; margin-bottom: 15px; border-left: 5px solid #ffc107; padding-left: 10px;">Rincian Transaksi Terkini</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid #ddd;">
                <thead>
                    <tr style="background: #0d9f4f; color: white;">
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Tgl</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Nama Muzaki/UPZ</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Kategori</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Nominal</th>
                    </tr>
                </thead>
                <tbody>`;

    if (reportTransactions.length === 0) {
        html += `<tr><td colspan="4" style="padding: 20px; text-align: center; color: #999;">Tidak ada data transaksi.</td></tr>`;
    } else {
        // Only show first 50 to keep the web page light, or show all if specifically filtered
        const displayList = reportTransactions.length > 100 ? reportTransactions.slice(0, 50) : reportTransactions;
        displayList.forEach(t => {
            html += `<tr>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${new Date(t.tanggal_transaksi).toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit'})}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${t.nama_muzaki}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${t.nama_jenis}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">Rp ${parseFloat(t.jumlah_pembayaran).toLocaleString('id-ID')}</td>
            </tr>`;
        });
        if (reportTransactions.length > 50) {
            html += `<tr style="background: #fdfdfd;"><td colspan="4" style="padding: 10px; text-align: center; font-style: italic; color: #666;">... dan ${(reportTransactions.length - 50)} data lainnya dapat dilihat di kantor BAZNAS Sumbawa.</td></tr>`;
        }
    }

    html += `</tbody>
            </table>
        </div>
        <div style="text-align: right; margin-top: 20px; font-size: 11px; color: #888;">
            Dicetak otomatis oleh SIM Pengumpulan BAZNAS Sumbawa pada ${new Date().toLocaleString('id-ID')}
        </div>
    </div>`;

    $('#web-preview-container').html(html);
    $('#web-code-area').val(html);
    $('#webCopyModal').modal('show');
}

function copyEnhancedWebCode() {
    const copyText = document.getElementById("web-code-area");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    Toast.fire({ icon: 'success', title: 'Paket Laporan Web berhasil disalin!' });
}

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
