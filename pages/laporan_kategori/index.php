<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$redirectUrl = url('pages/laporan/index.php' . ($queryString ? '?' . $queryString : '') . '#rekap-rekening');
header('Location: ' . $redirectUrl);
exit;

$activeMenu = 'laporan_kategori';
$pageTitle = 'Laporan Kategori Detail Dana';
$pageDescription = 'Detail transaksi berdasarkan Kategori Dana (Penerimaan/Pengumpulan)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Laporan Kategori</li>';
$conn = db();

// --- Standardized Filter Parameters ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$id_jenis = (int)($_GET['id_jenis'] ?? 0);
$id_upz = (int)($_GET['id_upz'] ?? 0);
$id_sub_jenis = (int)($_GET['id_sub_jenis'] ?? 0);
$id_metode = (int)($_GET['id_metode'] ?? 0);
$id_sumber = (int)($_GET['id_sumber'] ?? 0);
$id_kategori_arr = $_GET['id_kategori'] ?? [];
if (!is_array($id_kategori_arr)) {
    $id_kategori_arr = (int)$id_kategori_arr > 0 ? [(int)$id_kategori_arr] : [];
}
$id_kategori_arr = array_filter($id_kategori_arr, function($v) { return (int)$v > 0; });

// --- Where Clause Construction ---
$where = ["t.tanggal_transaksi >= ?", "t.tanggal_transaksi <= ?"];
$types = "ss";
$params = [$start_date, $end_date];

if ($id_jenis > 0) { $where[] = "t.id_jenis_pembayaran = ?"; $params[] = $id_jenis; $types .= "i"; }
if ($id_upz > 0) { $where[] = "t.id_upz = ?"; $params[] = $id_upz; $types .= "i"; }
if ($id_sub_jenis > 0) { $where[] = "t.id_sub_jenis_pembayaran = ?"; $params[] = $id_sub_jenis; $types .= "i"; }
if ($id_metode > 0) { $where[] = "t.id_metode_pembayaran = ?"; $params[] = $id_metode; $types .= "i"; }
if ($id_sumber > 0) { $where[] = "t.id_sumber_rekening = ?"; $params[] = $id_sumber; $types .= "i"; }
// Note: id_kategori filter in Laporan Kategori is usually to filter the rows shown OR filter the transactions summarized.
// Given the pattern, we'll use it to filter the transactions summarized.
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

// --- Data Fetching & Aggregation ---
$sql = "
    SELECT 
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

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data_res = $stmt->get_result();

$groupedData = [];
$gColTotals = []; $gTotalAll = 0;
foreach ($kategoriList as $k) $gColTotals[$k['id']] = 0;

while ($row = $data_res->fetch_assoc()) {
    $jenis = $row['nama_jenis'] ?? 'LAINNYA';
    $sub_id = (int)$row['id_sub_jenis_pembayaran'];
    $sub_nama = $row['nama_sub_jenis'] ?? 'Umum';
    if (!isset($groupedData[$jenis])) $groupedData[$jenis] = [];
    if (!isset($groupedData[$jenis][$sub_id])) {
        $groupedData[$jenis][$sub_id] = ['nama' => $sub_nama, 'kategori' => [], 'total_all' => 0];
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
$stmt->close();

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
                                <select class="form-control select2" name="id_jenis">
                                    <option value="0">Pilih Semua</option>
                                    <?php foreach ($jenisList as $j): ?>
                                    <option value="<?= $j['id'] ?>" <?= $id_jenis == $j['id'] ? 'selected' : '' ?>><?= htmlspecialchars($j['nama_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Muzaki dari UPZ</label>
                                <select class="form-control select2" name="id_upz">
                                    <option value="0">Pilih Semua UPZ / Individu Lgsg</option>
                                    <?php foreach ($upzList as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $id_upz == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nama_upz']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sub Jenis Pembayaran</label>
                                <select class="form-control select2" name="id_sub_jenis">
                                    <option value="0">Semua Sub Jenis</option>
                                    <?php foreach ($subJenisList as $sj): ?>
                                    <option value="<?= $sj['id'] ?>" <?= $id_sub_jenis == $sj['id'] ? 'selected' : '' ?>><?= htmlspecialchars($sj['nama_sub_jenis']) ?></option>
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
                                <select class="form-control select2" name="id_metode">
                                    <option value="0">Semua Metode</option>
                                    <?php foreach ($metodeList as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $id_metode == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_metode']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sumber Rekening</label>
                                <select class="form-control select2" name="id_sumber">
                                    <option value="0">Semua Sumber</option>
                                    <?php foreach ($sumberList as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $id_sumber == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nama_sumber']) ?></option>
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
            <div class="card-body py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark font-weight-bold"><i class="fas fa-poll mr-2"></i>Hasil Laporan</h5>
                <form id="exportForm" action="export.php" method="POST" target="_blank" class="form-inline mb-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="start_date" value="<?= $start_date ?>">
                    <input type="hidden" name="end_date" value="<?= $end_date ?>">
                    <input type="hidden" name="id_jenis" value="<?= $id_jenis ?>">
                    <input type="hidden" name="id_upz" value="<?= $id_upz ?>">
                    <input type="hidden" name="id_sub_jenis" value="<?= $id_sub_jenis ?>">
                    <?php foreach ($id_kategori_arr as $kat_id): ?>
                    <input type="hidden" name="id_kategori[]" value="<?= htmlspecialchars($kat_id) ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="id_metode" value="<?= $id_metode ?>">
                    <input type="hidden" name="id_sumber" value="<?= $id_sumber ?>">

                    <button type="submit" name="format" value="csv" class="btn btn-sm btn-info mr-1" data-toggle="tooltip" title="Export CSV">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button type="submit" name="format" value="excel" class="btn btn-sm btn-success mr-1" data-toggle="tooltip" title="Export Excel">
                        <i class="fas fa-file-excel mr-1"></i>Export Excel
                    </button>
                    <button type="submit" name="format" value="pdf" class="btn btn-sm btn-danger mr-1" data-toggle="tooltip" title="Cetak PDF">
                        <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
                    </button>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-table mr-2"></i>Rekapitulasi Kategori Dana</h3>
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
                                        $subsum_kategori = []; $subsum_total = 0;
                                        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
                                        foreach ($subs as $sub_id => $d) {
                                            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
                                            $subsum_total += $d['total_all'];
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
                                                        'id_jenis' => $id_jenis,
                                                        'id_upz' => $id_upz,
                                                        'id_sub_jenis' => $sub_id,
                                                        'id_metode' => $id_metode,
                                                        'id_sumber' => $id_sumber
                                                    ];
                                                    $queryString = http_build_query($detailParams);
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

<script>
$(function() {
    $('#kategoriTable').DataTable({ ordering: false, paging: false, dom: 't' });
    $('.select2').select2({ theme: 'bootstrap4' });
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
