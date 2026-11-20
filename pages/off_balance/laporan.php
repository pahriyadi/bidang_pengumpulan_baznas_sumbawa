<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_auth();

$activeMenu = 'off_balance_laporan';
$pageTitle = 'Laporan & Rekapitulasi Off Balance Sheet';
$pageDescription = 'Rekapitulasi pengumpulan & penyaluran ZIS Off Balance Sheet per Sumber Jenis Dana, Sub-Jenis Dana, dan Kategori Sektoral UPZ';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item"><a href="' . url('pages/off_balance/index.php') . '">Off Balance Sheet</a></li><li class="breadcrumb-item active">Laporan Off Balance</li>';

$conn = db();
ensure_kinerja_tables($conn);

// --- Standardized Filter Parameters ---
$start_date = $_GET['start_date'] ?? date('Y-01-01');
$end_date = $_GET['end_date'] ?? date('Y-12-31');

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
$id_sub_jenis_arr = get_filter_arr('id_sub_jenis');
$id_upz_arr = get_filter_arr('id_upz');
$id_kategori_upz_arr = get_filter_arr('id_kategori_upz');
$keyword = trim($_GET['keyword'] ?? '');

$where = ["ob.tanggal_transaksi >= ?", "ob.tanggal_transaksi <= ?"];
$types = "ss";
$params = [$start_date, $end_date];

if (!empty($id_jenis_arr)) {
    $in_p = implode(',', array_fill(0, count($id_jenis_arr), '?'));
    $where[] = "ob.id_jenis_pembayaran IN ($in_p)";
    $types .= str_repeat('i', count($id_jenis_arr));
    foreach ($id_jenis_arr as $v) $params[] = $v;
}

if (!empty($id_sub_jenis_arr)) {
    $in_p = implode(',', array_fill(0, count($id_sub_jenis_arr), '?'));
    $where[] = "ob.id_sub_jenis_pembayaran IN ($in_p)";
    $types .= str_repeat('i', count($id_sub_jenis_arr));
    foreach ($id_sub_jenis_arr as $v) $params[] = $v;
}

if (!empty($id_upz_arr)) {
    $in_p = implode(',', array_fill(0, count($id_upz_arr), '?'));
    $where[] = "ob.id_upz IN ($in_p)";
    $types .= str_repeat('i', count($id_upz_arr));
    foreach ($id_upz_arr as $v) $params[] = $v;
}

if (!empty($id_kategori_upz_arr)) {
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
        $in_p = implode(',', $normal_ids);
        $sub_conds[] = "COALESCE(u.id_kategori_upz, 0) IN ($in_p)";
    }
    if ($has_fallback) {
        $sub_conds[] = "(u.id_kategori_upz IS NULL)";
    }
    if (!empty($sub_conds)) {
        $where[] = "(" . implode(" OR ", $sub_conds) . ")";
    }
}

if ($keyword !== '') {
    $where[] = "(ob.no_berita_acara LIKE ? OR ob.nama_pelapor LIKE ? OR u.nama_upz LIKE ? OR ob.keterangan LIKE ?)";
    $types .= "ssss";
    $kwWild = '%' . $keyword . '%';
    for ($k = 0; $k < 4; $k++) $params[] = $kwWild;
}

$where_sql = implode(' AND ', $where);

// --- Dropdowns Data ---
$jenisList = get_jenis_pembayaran_list();
$upzList = get_upz_list();
$subJenisList = $conn->query("SELECT * FROM sub_jenis_pembayaran ORDER BY id_jenis_pembayaran ASC, nama_sub_jenis ASC")->fetch_all(MYSQLI_ASSOC);
$kategoriUpzList = $conn->query("SELECT * FROM kategori_upz ORDER BY urutan ASC, nama_kategori ASC")->fetch_all(MYSQLI_ASSOC);

// 1. Fetch All Filtered Off Balance Transactions
$sql = "
    SELECT ob.*,
        u.nama_upz,
        COALESCE(ku.nama_kategori, 'Masyarakat / Lembaga Non-UPZ') AS kategori_upz_name,
        COALESCE(ku.id, -1) AS id_kategori_upz,
        COALESCE(NULLIF(u.nama_upz, ''), NULLIF(ob.nama_pelapor, ''), 'Masyarakat / Umum') AS pelapor_display,
        jp.nama_jenis,
        sjp.nama_sub_jenis
    FROM transaksi_off_balance ob
    LEFT JOIN upz u ON ob.id_upz = u.id
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
    LEFT JOIN jenis_pembayaran jp ON ob.id_jenis_pembayaran = jp.id
    LEFT JOIN sub_jenis_pembayaran sjp ON ob.id_sub_jenis_pembayaran = sjp.id
    WHERE {$where_sql}
    ORDER BY ob.tanggal_transaksi DESC, ob.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$transactions = [];
$columnTotals = [];
foreach ($jenisList as $j) {
    $columnTotals[$j['id']] = 0.0;
}
$total_nominal = 0.0;
$total_muzaki = 0;
$total_mustahik = 0;

// Hierarchical structure: Sumber Jenis Dana -> Sub-Jenis Dana
$groupedSumberDana = [];
// Hierarchical structure: Kategori UPZ -> Entitas Pelapor -> Jenis Pembayaran
$groupedRincianUPZ = [];
$kategoriSektoralTotals = [];
$kategoriSektoralGrand = [];
$kategoriSektoralMuzaki = [];
$kategoriSektoralMustahik = [];
$kategoriUPZIdMap = [];

while ($row = $res->fetch_assoc()) {
    $amt = (float)$row['nominal'];
    $muz = (int)$row['jumlah_muzaki'];
    $mus = (int)$row['jumlah_mustahik'];
    $jid = (int)$row['id_jenis_pembayaran'];
    $sid = (int)($row['id_sub_jenis_pembayaran'] ?? 0);
    $jenis_nama = $row['nama_jenis'] ?? 'LAINNYA';
    $sub_nama = $row['nama_sub_jenis'] ?: 'Umum / Tanpa Sub-Jenis';

    $total_nominal += $amt;
    $total_muzaki += $muz;
    $total_mustahik += $mus;

    if (isset($columnTotals[$jid])) {
        $columnTotals[$jid] += $amt;
    }

    // Group 1: Sumber Jenis Dana & Sub-Jenis Dana
    if (!isset($groupedSumberDana[$jenis_nama])) {
        $groupedSumberDana[$jenis_nama] = [
            'id_jenis' => $jid,
            'total_laporan' => 0,
            'total_muzaki' => 0,
            'total_mustahik' => 0,
            'total_nominal' => 0.0,
            'subs' => []
        ];
    }
    $groupedSumberDana[$jenis_nama]['total_laporan']++;
    $groupedSumberDana[$jenis_nama]['total_muzaki'] += $muz;
    $groupedSumberDana[$jenis_nama]['total_mustahik'] += $mus;
    $groupedSumberDana[$jenis_nama]['total_nominal'] += $amt;

    if (!isset($groupedSumberDana[$jenis_nama]['subs'][$sid])) {
        $groupedSumberDana[$jenis_nama]['subs'][$sid] = [
            'id_jenis' => $jid,
            'id_sub' => $sid,
            'nama_sub' => $sub_nama,
            'jumlah_laporan' => 0,
            'jumlah_muzaki' => 0,
            'jumlah_mustahik' => 0,
            'nominal' => 0.0
        ];
    }
    $groupedSumberDana[$jenis_nama]['subs'][$sid]['jumlah_laporan']++;
    $groupedSumberDana[$jenis_nama]['subs'][$sid]['jumlah_muzaki'] += $muz;
    $groupedSumberDana[$jenis_nama]['subs'][$sid]['jumlah_mustahik'] += $mus;
    $groupedSumberDana[$jenis_nama]['subs'][$sid]['nominal'] += $amt;

    // Group 2: Kategori Sektoral UPZ
    $katName = $row['kategori_upz_name'];
    $katId = (int)$row['id_kategori_upz'];
    $entName = $row['pelapor_display'];
    $kategoriUPZIdMap[$katName] = $katId;

    if (!isset($groupedRincianUPZ[$katName])) {
        $groupedRincianUPZ[$katName] = [];
        $kategoriSektoralTotals[$katName] = [];
        foreach ($jenisList as $j) {
            $kategoriSektoralTotals[$katName][$j['id']] = 0.0;
        }
        $kategoriSektoralGrand[$katName] = 0.0;
        $kategoriSektoralMuzaki[$katName] = 0;
        $kategoriSektoralMustahik[$katName] = 0;
    }
    if (!isset($groupedRincianUPZ[$katName][$entName])) {
        $groupedRincianUPZ[$katName][$entName] = [
            'muzaki' => 0,
            'mustahik' => 0,
            'total' => 0.0,
            'jenis' => []
        ];
        foreach ($jenisList as $j) {
            $groupedRincianUPZ[$katName][$entName]['jenis'][$j['id']] = 0.0;
        }
    }
    $groupedRincianUPZ[$katName][$entName]['muzaki'] += $muz;
    $groupedRincianUPZ[$katName][$entName]['mustahik'] += $mus;
    $groupedRincianUPZ[$katName][$entName]['total'] += $amt;
    if (isset($groupedRincianUPZ[$katName][$entName]['jenis'][$jid])) {
        $groupedRincianUPZ[$katName][$entName]['jenis'][$jid] += $amt;
    }

    if (isset($kategoriSektoralTotals[$katName][$jid])) {
        $kategoriSektoralTotals[$katName][$jid] += $amt;
    }
    $kategoriSektoralGrand[$katName] += $amt;
    $kategoriSektoralMuzaki[$katName] += $muz;
    $kategoriSektoralMustahik[$katName] += $mus;

    $transactions[] = $row;
}
$stmt->close();

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <!-- Filter Card - STANDARD PATTERN -->
        <div class="card card-outline card-success shadow-none mb-3" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-filter mr-2 text-success"></i>Filter Laporan Off Balance Sheet</h3>
                <div class="card-tools mt-2 mt-md-0">
                    <a href="<?= url('pages/off_balance/index.php') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-clipboard-list mr-1"></i>Kelola Data Off Balance
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET" action="laporan.php">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Dari Tanggal</label>
                                <input type="date" class="form-control" name="start_date" id="start_date" value="<?= clean($start_date) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" class="form-control" name="end_date" id="end_date" value="<?= clean($end_date) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sumber Jenis Dana</label>
                                <select class="form-control select2" name="id_jenis[]" multiple="multiple" data-placeholder="Semua Jenis Dana">
                                    <?php foreach ($jenisList as $j): ?>
                                    <option value="<?= (int)$j['id'] ?>" <?= in_array((int)$j['id'], $id_jenis_arr) ? 'selected' : '' ?>><?= clean($j['nama_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sub-Jenis Dana</label>
                                <select class="form-control select2" name="id_sub_jenis[]" multiple="multiple" data-placeholder="Semua Sub-Jenis Dana">
                                    <?php foreach ($subJenisList as $sj): ?>
                                    <option value="<?= (int)$sj['id'] ?>" <?= in_array((int)$sj['id'], $id_sub_jenis_arr) ? 'selected' : '' ?>><?= clean($sj['nama_sub_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>UPZ Pelapor</label>
                                <select class="form-control select2" name="id_upz[]" multiple="multiple" data-placeholder="Semua UPZ / Lembaga">
                                    <?php foreach ($upzList as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>" <?= in_array((int)$u['id'], $id_upz_arr) ? 'selected' : '' ?>><?= clean($u['nama_upz']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kategori Sektoral UPZ</label>
                                <select class="form-control select2" name="id_kategori_upz[]" multiple="multiple" data-placeholder="Semua Kategori UPZ">
                                    <option value="-1" <?= in_array(-1, $id_kategori_upz_arr) ? 'selected' : '' ?>>Masyarakat / Lembaga Non-UPZ</option>
                                    <?php foreach ($kategoriUpzList as $ku): ?>
                                    <option value="<?= (int)$ku['id'] ?>" <?= in_array((int)$ku['id'], $id_kategori_upz_arr) ? 'selected' : '' ?>><?= clean($ku['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cari No. Berita Acara / Pelapor / Keterangan</label>
                                <input type="text" class="form-control" name="keyword" value="<?= clean($keyword) ?>" placeholder="Ketik kata kunci...">
                            </div>
                        </div>
                    </div>
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

        <!-- Toolbar Export Utama -->
        <div class="card flat-card mb-3" style="border: 1px solid #b8b8b8;">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-poll mr-2 text-success"></i>Hasil Laporan Off Balance Sheet</h5>
                <form id="exportForm" method="POST" action="export.php" target="_blank" class="form-inline mb-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="start_date" value="<?= clean($start_date) ?>">
                    <input type="hidden" name="end_date" value="<?= clean($end_date) ?>">
                    <input type="hidden" name="keyword" value="<?= clean($keyword) ?>">
                    <input type="hidden" name="export_mode" id="export_mode_input" value="full">
                    <?php foreach ($id_jenis_arr as $v): ?>
                        <input type="hidden" name="id_jenis[]" value="<?= (int)$v ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_sub_jenis_arr as $v): ?>
                        <input type="hidden" name="id_sub_jenis[]" value="<?= (int)$v ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_upz_arr as $v): ?>
                        <input type="hidden" name="id_upz[]" value="<?= (int)$v ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_kategori_upz_arr as $v): ?>
                        <input type="hidden" name="id_kategori_upz[]" value="<?= (int)$v ?>">
                    <?php endforeach; ?>

                    <button type="submit" name="export_format" value="csv" onclick="$('#export_mode_input').val('full')" class="btn btn-sm btn-outline-info mr-1">
                        <i class="fas fa-file-csv mr-1"></i>CSV Lengkap
                    </button>
                    <button type="submit" name="export_format" value="excel" onclick="$('#export_mode_input').val('full')" class="btn btn-sm btn-outline-success mr-1">
                        <i class="fas fa-file-excel mr-1"></i>Excel Lengkap
                    </button>
                    <button type="submit" name="export_format" value="pdf" onclick="$('#export_mode_input').val('full')" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-file-pdf mr-1"></i>Cetak PDF Lengkap
                    </button>
                </form>
            </div>
        </div>

        <!-- Ringkasan & Tabel Rincian Transaksi Off Balance per Jenis Dana -->
        <div class="card flat-card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-body">
                <div class="alert alert-info alert-excel mb-4 shadow-none">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-1"><i class="icon fas fa-clipboard-check"></i> Ringkasan Off Balance Sheet (Periode <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?>)</h5>
                            Total Laporan / Berita Acara: <strong><?= number_format(count($transactions), 0, ',', '.') ?></strong> data &nbsp;|&nbsp;
                            Total Muzaki: <strong><?= number_format($total_muzaki, 0, ',', '.') ?></strong> Jiwa &nbsp;|&nbsp;
                            Total Mustahik: <strong><?= number_format($total_mustahik, 0, ',', '.') ?></strong> Jiwa<br>
                            Total Realisasi Off Balance Sheet: <strong class="currency-display h4 mb-0 text-success"><?= format_currency($total_nominal) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="dataTable" class="table table-bordered table-hover table-excel" style="font-size: 0.85rem;">
                        <thead>
                            <tr class="bg-light">
                                <th width="3%">No</th>
                                <th width="9%">Tanggal</th>
                                <th>UPZ / Lembaga Pelapor</th>
                                <th width="10%">No. BA</th>
                                <th width="7%" class="text-center">Muzaki</th>
                                <th width="7%" class="text-center">Mustahik</th>
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
                                <th colspan="4" class="text-right">TOTAL KESELURUHAN</th>
                                <th class="text-center"><?= number_format($total_muzaki, 0, ',', '.') ?></th>
                                <th class="text-center"><?= number_format($total_mustahik, 0, ',', '.') ?></th>
                                <?php foreach ($jenisList as $j): ?>
                                    <th class="text-right"><?= format_currency($columnTotals[$j['id']]) ?></th>
                                <?php endforeach; ?>
                                <th class="text-right" style="background: rgba(13, 159, 79, 0.14);"><strong><?= format_currency($total_nominal) ?></strong></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABEL UTAMA: Rekapitulasi per Sumber Jenis Dana & Sub-Jenis Dana (Off Balance Sheet) -->
        <div id="rekap-sumber-dana"></div>
        <div class="card flat-card shadow-none mt-4" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0">
                    <i class="fas fa-layer-group mr-2 text-success"></i>Rekapitulasi Off Balance Sheet per Sumber Jenis Dana &amp; Sub-Jenis Dana
                </h3>
                <div class="btn-group mt-2 mt-md-0">
                    <button type="button" onclick="exportSumberDana('csv')" class="btn btn-xs btn-outline-info">
                        <i class="fas fa-file-csv mr-1"></i>CSV Sumber Dana
                    </button>
                    <button type="button" onclick="exportSumberDana('excel')" class="btn btn-xs btn-outline-success">
                        <i class="fas fa-file-excel mr-1"></i>Excel Sumber Dana
                    </button>
                    <button type="button" onclick="exportSumberDana('pdf')" class="btn btn-xs btn-outline-danger">
                        <i class="fas fa-file-pdf mr-1"></i>PDF Sumber Dana
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="sumberDanaTable" class="table table-bordered table-hover table-excel mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-gray-light">
                            <tr>
                                <th>Sumber Dana (Jenis &amp; Sub-Jenis Pembayaran)</th>
                                <th width="11%" class="text-center">Jml Laporan</th>
                                <th width="12%" class="text-center">Muzaki (Jiwa)</th>
                                <th width="12%" class="text-center">Mustahik (Jiwa)</th>
                                <th width="18%" class="text-right">Realisasi Off Balance (Rp)</th>
                                <th width="10%" class="text-center">% Proporsi</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedSumberDana)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data Off Balance Sheet untuk filter ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedSumberDana as $jenisNama => $jData):
                                    $pctJenis = $total_nominal > 0 ? round(($jData['total_nominal'] / $total_nominal) * 100, 1) : 0;
                                ?>
                                    <tr class="table-secondary font-weight-bold">
                                        <td><i class="fas fa-folder-open mr-2 text-success"></i><?= strtoupper(htmlspecialchars($jenisNama)) ?></td>
                                        <td class="text-center"><?= number_format($jData['total_laporan'], 0, ',', '.') ?></td>
                                        <td class="text-center"><?= number_format($jData['total_muzaki'], 0, ',', '.') ?></td>
                                        <td class="text-center"><?= number_format($jData['total_mustahik'], 0, ',', '.') ?></td>
                                        <td class="text-right">Rp <?= number_format($jData['total_nominal'], 0, ',', '.') ?>,-</td>
                                        <td class="text-center"><span class="badge badge-success"><?= $pctJenis ?>%</span></td>
                                        <td class="text-center">
                                            <?php
                                            $qJenis = http_build_query([
                                                'start_date' => $start_date,
                                                'end_date' => $end_date,
                                                'id_jenis' => [$jData['id_jenis']]
                                            ]);
                                            ?>
                                            <a href="<?= url('pages/off_balance/laporan.php?' . $qJenis) ?>" class="btn btn-outline-info btn-xs">
                                                <i class="fas fa-filter mr-1"></i>Filter
                                            </a>
                                        </td>
                                    </tr>
                                    <?php foreach ($jData['subs'] as $sid => $sData):
                                        $pctSub = $total_nominal > 0 ? round(($sData['nominal'] / $total_nominal) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td style="padding-left: 30px;">
                                                <i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($sData['nama_sub']) ?>
                                            </td>
                                            <td class="text-center"><?= number_format($sData['jumlah_laporan'], 0, ',', '.') ?></td>
                                            <td class="text-center"><?= number_format($sData['jumlah_muzaki'], 0, ',', '.') ?></td>
                                            <td class="text-center"><?= number_format($sData['jumlah_mustahik'], 0, ',', '.') ?></td>
                                            <td class="text-right font-weight-bold">Rp <?= number_format($sData['nominal'], 0, ',', '.') ?>,-</td>
                                            <td class="text-center"><?= $pctSub ?>%</td>
                                            <td class="text-center">
                                                <?php
                                                $detailParams = [
                                                    'start_date' => $start_date,
                                                    'end_date' => $end_date,
                                                    'id_jenis' => [$sData['id_jenis']]
                                                ];
                                                if ($sid > 0) {
                                                    $detailParams['id_sub_jenis'] = [$sid];
                                                }
                                                ?>
                                                <a href="<?= url('pages/off_balance/laporan.php?' . http_build_query($detailParams)) ?>" class="btn btn-outline-info btn-xs">
                                                    <i class="fas fa-search-dollar mr-1"></i>Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($groupedSumberDana)): ?>
                        <tfoot class="bg-gray-light font-weight-bold">
                            <tr>
                                <td class="text-right">TOTAL KESELURUHAN OFF BALANCE SHEET</td>
                                <td class="text-center"><?= number_format(count($transactions), 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($total_muzaki, 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($total_mustahik, 0, ',', '.') ?></td>
                                <td class="text-right text-success">Rp <?= number_format($total_nominal, 0, ',', '.') ?>,-</td>
                                <td class="text-center">100%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rincian & Rekapitulasi Off Balance Sheet per Kategori Sektoral UPZ -->
        <div class="card flat-card shadow-none mt-4" style="border: 1px solid #b8b8b8;">
            <div class="card-header py-3" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold text-success mb-0">
                    <i class="fas fa-sitemap mr-2"></i>Rincian &amp; Rekapitulasi Off Balance Sheet per Kategori Sektoral UPZ
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($groupedRincianUPZ)): ?>
                    <p class="text-center py-4 text-muted mb-0">Tidak ada data untuk kriteria filter ini.</p>
                <?php else: ?>
                    <?php foreach ($groupedRincianUPZ as $katName => $entities): ?>
                        <div class="mb-4">
                            <h5 class="font-weight-bold mb-2">
                                <span class="badge badge-success p-2" style="font-size: 0.88rem;">
                                    Kategori Sektoral: <?= htmlspecialchars($katName) ?>
                                </span>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" style="font-size: 0.78rem;">
                                    <thead class="bg-gray-light">
                                        <tr class="text-center">
                                            <th width="3%">NO</th>
                                            <th>NAMA UPZ / LEMBAGA PELAPOR</th>
                                            <th width="8%">MUZAKI</th>
                                            <th width="8%">MUSTAHIK</th>
                                            <?php foreach ($jenisList as $j): ?>
                                                <th width="13%" class="text-right"><?= strtoupper(htmlspecialchars($j['nama_jenis'])) ?></th>
                                            <?php endforeach; ?>
                                            <th width="14%" class="text-right">JUMLAH</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $subNo = 1; foreach ($entities as $eName => $eData): ?>
                                            <tr>
                                                <td class="text-center"><?= $subNo++ ?></td>
                                                <td><strong><?= htmlspecialchars($eName) ?></strong></td>
                                                <td class="text-center"><?= number_format($eData['muzaki'], 0, ',', '.') ?></td>
                                                <td class="text-center"><?= number_format($eData['mustahik'], 0, ',', '.') ?></td>
                                                <?php foreach ($jenisList as $j):
                                                    $val = $eData['jenis'][$j['id']] ?? 0;
                                                ?>
                                                    <td class="text-right"><?= $val > 0 ? 'Rp ' . number_format($val, 0, ',', '.') : '-' ?></td>
                                                <?php endforeach; ?>
                                                <td class="text-right font-weight-bold">Rp <?= number_format($eData['total'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-gray-light font-weight-bold text-right">
                                        <tr>
                                            <td colspan="2" class="text-center">SUBTOTAL <?= strtoupper(htmlspecialchars($katName)) ?></td>
                                            <td class="text-center"><?= number_format($kategoriSektoralMuzaki[$katName], 0, ',', '.') ?></td>
                                            <td class="text-center"><?= number_format($kategoriSektoralMustahik[$katName], 0, ',', '.') ?></td>
                                            <?php foreach ($jenisList as $j): ?>
                                                <td>Rp <?= number_format($kategoriSektoralTotals[$katName][$j['id']], 0, ',', '.') ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-success">Rp <?= number_format($kategoriSektoralGrand[$katName], 0, ',', '.') ?></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const reportTransactions = <?= json_encode($transactions) ?>;
const reportJenisList = <?= json_encode($jenisList) ?>;

$(function () {
    const dataSet = reportTransactions.map((row, idx) => {
        const formatCurrency = (val) => 'Rp ' + (parseInt(val, 10) || 0).toLocaleString('id-ID');
        const parts = (row.tanggal_transaksi || '').split('-');
        const dateFormatted = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : row.tanggal_transaksi;

        let pelaporHtml = '<strong>' + (row.pelapor_display || '-') + '</strong>';
        if (row.nama_sub_jenis) {
            pelaporHtml += '<br><small class="text-muted">' + row.nama_jenis + ' &mdash; ' + row.nama_sub_jenis + '</small>';
        }

        const cols = [
            idx + 1,
            dateFormatted,
            pelaporHtml,
            row.no_berita_acara || '-',
            '<div class="text-center">' + (parseInt(row.jumlah_muzaki, 10) || 0).toLocaleString('id-ID') + '</div>',
            '<div class="text-center">' + (parseInt(row.jumlah_mustahik, 10) || 0).toLocaleString('id-ID') + '</div>'
        ];

        reportJenisList.forEach(j => {
            if (row.id_jenis_pembayaran == j.id) {
                cols.push('<div class="text-right">' + formatCurrency(row.nominal) + '</div>');
            } else {
                cols.push('<div class="text-right">-</div>');
            }
        });

        cols.push('<div class="text-right"><strong>' + formatCurrency(row.nominal) + '</strong></div>');
        return cols;
    });

    $('#dataTable').DataTable({
        data: dataSet,
        deferRender: true,
        order: [],
        pageLength: 25,
        language: {
            emptyTable: "Tidak ada data Off Balance Sheet pada periode dan kriteria tersebut.",
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

    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});

function exportSumberDana(fmt) {
    $('#export_mode_input').val('sumber_dana');
    const form = $('#exportForm');
    form.find('input[name="export_format_hidden"]').remove();
    const btn = $('<input type="hidden" name="export_format">').val(fmt);
    form.append(btn);
    form.submit();
    btn.remove();
}

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
