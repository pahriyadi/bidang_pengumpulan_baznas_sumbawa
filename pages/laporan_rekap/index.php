<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'laporan_rekap';
$pageTitle = 'Rekapitulasi Bulanan';
$pageDescription = 'Ringkasan penerimaan ZIS per bulan';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Rekap Bulanan</li>';
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

// --- Data Fetching ---
$sql = "
    SELECT 
        DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') as bulan,
        t.id_jenis_pembayaran as id_jenis,
        SUM(t.jumlah_pembayaran) as total
    FROM transaksi t
    $where_sql
    GROUP BY bulan, t.id_jenis_pembayaran
    ORDER BY bulan DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$dbResult = $stmt->get_result();

$monthlyData = [];
while ($row = $dbResult->fetch_assoc()) {
    $b = $row['bulan'];
    if (!isset($monthlyData[$b])) {
        $monthlyData[$b] = ['total_all' => 0, 'jenis' => []];
        foreach ($jenisList as $j) $monthlyData[$b]['jenis'][$j['id']] = 0;
    }
    $monthlyData[$b]['jenis'][$row['id_jenis']] += (float)$row['total'];
    $monthlyData[$b]['total_all'] += (float)$row['total'];
}

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
            <div class="card-body py-2 px-3 bg-light d-flex justify-content-between align-items-center">
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

                    <button type="submit" name="format" value="csv" class="btn btn-sm btn-info mr-1" data-toggle="tooltip" title="Export CSV">
                        <i class="fas fa-file-csv mr-1"></i>Export CSV
                    </button>
                    <button type="submit" name="format" value="excel" class="btn btn-sm btn-success mr-1" data-toggle="tooltip" title="Export Excel">
                        <i class="fas fa-file-excel mr-1"></i>Export Excel
                    </button>
                    <button type="submit" name="format" value="pdf" class="btn btn-sm btn-danger mr-1" data-toggle="tooltip" title="Cetak PDF">
                        <i class="fas fa-file-pdf mr-1"></i>Cetak PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-dark" onclick="openWebCopyModal()" data-toggle="tooltip" title="Salin HTML untuk Website Utama">
                        <i class="fas fa-code mr-1"></i>Salin untuk Web
                    </button>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-table mr-2"></i>Rekapitulasi Bulanan</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="rekapTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead>
                            <tr class="bg-gray-light">
                                <th style="vertical-align: middle;">Bulan</th>
                                <?php foreach ($jenisList as $j): ?>
                                    <th class="text-right" style="vertical-align: middle;"><?= htmlspecialchars($j['nama_jenis']) ?></th>
                                <?php endforeach; ?>
                                <th class="text-right" style="vertical-align: middle;">Jumlah Total</th>
                                <th class="text-center" width="10%" style="vertical-align: middle;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthlyData)): ?>
                                <tr>
                                    <td colspan="<?= count($jenisList) + 3 ?>" class="text-center py-4 text-muted">Tidak ada data transaksi untuk kriteria filter ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthlyData as $bulan => $data): 
                                    $monthNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                                    $parts = explode('-', $bulan);
                                    $bulanReadable = $monthNames[$parts[1]] . ' ' . $parts[0];
                                    $startDay = $bulan . '-01';
                                    $endDay = date('Y-m-t', strtotime($startDay));
                                    
                                    // Build dynamic query string for drilling down with all active filters
                                    $detailParams = [
                                        'start_date' => $startDay,
                                        'end_date' => $endDay
                                    ];
                                    $queryString = http_build_query($detailParams);
                                    foreach ($id_jenis_arr as $val) $queryString .= '&id_jenis[]=' . $val;
                                    foreach ($id_upz_arr as $val) $queryString .= '&id_upz[]=' . $val;
                                    foreach ($id_sub_jenis_arr as $val) $queryString .= '&id_sub_jenis[]=' . $val;
                                    foreach ($id_metode_arr as $val) $queryString .= '&id_metode[]=' . $val;
                                    foreach ($id_sumber_arr as $val) $queryString .= '&id_sumber[]=' . $val;
                                    foreach ($id_kategori_arr as $kat_id) $queryString .= '&id_kategori[]=' . $kat_id;
                                ?>
                                    <tr>
                                        <td><strong><?= $bulanReadable ?></strong></td>
                                        <?php foreach ($jenisList as $j): ?>
                                            <td class="text-right">Rp <?= number_format($data['jenis'][$j['id']], 0, ',', '.') ?>,-</td>
                                        <?php endforeach; ?>
                                        <td class="text-right text-bold text-success">Rp <?= number_format($data['total_all'], 0, ',', '.') ?>,-</td>
                                        <td class="text-center">
                                            <a href="<?= url('pages/laporan/index.php?' . $queryString) ?>" class="btn btn-info btn-xs shadow-sm" data-toggle="tooltip" title="Lihat Detail Transaksi terfilter untuk bulan ini">
                                                <i class="fas fa-search-dollar mr-1"></i>Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($monthlyData)): ?>
                        <tfoot>
                            <tr class="bg-gray-light font-weight-bold">
                                <td class="text-center">TOTAL KESELURUHAN</td>
                                <?php 
                                    $gColTotals = []; $gTotalAll = 0;
                                    foreach ($jenisList as $j) $gColTotals[$j['id']] = 0;
                                    foreach ($monthlyData as $b => $data) {
                                        foreach ($jenisList as $j) $gColTotals[$j['id']] += $data['jenis'][$j['id']];
                                        $gTotalAll += $data['total_all'];
                                    }
                                    foreach ($jenisList as $j): 
                                ?>
                                    <td class="text-right">Rp <?= number_format($gColTotals[$j['id']], 0, ',', '.') ?>,-</td>
                                <?php endforeach; ?>
                                <td class="text-right bg-warning text-dark">Rp <?= number_format($gTotalAll, 0, ',', '.') ?>,-</td>
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

<!-- Modal Salin untuk Web -->
<div class="modal fade" id="webCopyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content text-left">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-code mr-2"></i>Salin Kode HTML untuk Website Utama</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="font-weight-bold mb-2">Pratinjau Tampilan:</h6>
                        <div id="web-preview-container" class="border p-2 bg-white" style="max-height: 450px; overflow-y: auto;"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-2">Kode HTML:</h6>
                        <textarea id="web-code-area" class="form-control" rows="18" style="font-family: monospace; font-size: 11px; background: #f8f9fa;" readonly></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-success px-4" onclick="copyWebCode()">
                    <i class="fas fa-copy mr-1"></i>Salin Kode
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#rekapTable').DataTable({
        order: [],
        pageLength: 25,
        colReorder: true
    });
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});

const reportData = <?= json_encode($monthlyData) ?>;
const jenisList = <?= json_encode($jenisList) ?>;

function openWebCopyModal() {
    let tableHtml = `<table style="width:100%; border-collapse:collapse; font-family:Arial, sans-serif; font-size:14px; margin-bottom:20px; border:1px solid #dee2e6;">
    <thead>
        <tr style="background-color:#0d9f4f; color:#ffffff;">
            <th style="padding:12px; border:1px solid #dee2e6; text-align:left;">Bulan</th>`;
    
    jenisList.forEach(j => {
        tableHtml += `<th style="padding:12px; border:1px solid #dee2e6; text-align:right;">${j.nama_jenis}</th>`;
    });
    
    tableHtml += `<th style="padding:12px; border:1px solid #dee2e6; text-align:right;">Jumlah Total</th>
        </tr>
    </thead>
    <tbody>`;

    if (Object.keys(reportData).length === 0) {
        tableHtml += `<tr><td colspan="${jenisList.length + 2}" style="padding:12px; border:1px solid #dee2e6; text-align:center;">Tidak ada data.</td></tr>`;
    } else {
        const monthNames = { '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April', '05': 'Mei', '06': 'Juni', '07': 'Juli', '08' : 'Agustus', '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember' };
        let grandTotals = {};
        jenisList.forEach(j => grandTotals[j.id] = 0);
        let absoluteTotal = 0;
        const sortedMonths = Object.keys(reportData).sort().reverse();

        sortedMonths.forEach(bulan => {
            const data = reportData[bulan];
            const parts = bulan.split('-');
            const bulanLabel = monthNames[parts[1]] + ' ' + parts[0];
            tableHtml += `<tr><td style="padding:10px; border:1px solid #dee2e6;"><strong>${bulanLabel}</strong></td>`;
            jenisList.forEach(j => {
                const val = data.jenis[j.id] || 0;
                tableHtml += `<td style="padding:10px; border:1px solid #dee2e6; text-align:right;">Rp ${val.toLocaleString('id-ID')}</td>`;
                grandTotals[j.id] += val;
            });
            tableHtml += `<td style="padding:10px; border:1px solid #dee2e6; text-align:right; font-weight:bold; background-color:#f8f9fa;">Rp ${data.total_all.toLocaleString('id-ID')}</td>
            </tr>`;
            absoluteTotal += data.total_all;
        });

        tableHtml += `<tr style="background-color:#f1f3f5; font-weight:bold;">
            <td style="padding:12px; border:1px solid #dee2e6; text-align:right;">TOTAL KESELURUHAN</td>`;
        jenisList.forEach(j => { tableHtml += `<td style="padding:12px; border:1px solid #dee2e6; text-align:right;">Rp ${grandTotals[j.id].toLocaleString('id-ID')}</td>`; });
        tableHtml += `<td style="padding:12px; border:1px solid #dee2e6; text-align:right; color:#0d9f4f;">Rp ${absoluteTotal.toLocaleString('id-ID')}</td></tr>`;
    }
    tableHtml += `</tbody></table>`;
    $('#web-preview-container').html(tableHtml);
    $('#web-code-area').val(tableHtml);
    $('#webCopyModal').modal('show');
}

function copyWebCode() {
    const copyText = document.getElementById("web-code-area");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    Toast.fire({ icon: 'success', title: 'Kode HTML berhasil disalin!' });
}

function setRange(type) {
    const today = new Date();
    let start = ''; let end = '';
    const formatDate = (date) => { const d = String(date.getDate()).padStart(2, '0'); const m = String(date.getMonth() + 1).padStart(2, '0'); const y = date.getFullYear(); return `${y}-${m}-${d}`; };

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
    if (start && end) { $('#start_date').val(start); $('#end_date').val(end); }
}
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
