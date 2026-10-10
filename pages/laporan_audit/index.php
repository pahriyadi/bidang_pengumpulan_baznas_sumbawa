<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'laporan_audit';
$pageTitle = 'Laporan Audit (Pemeriksaan Keuangan)';
$pageDescription = 'Laporan khusus audit akuntansi keuangan BAZNAS dengan pemetaan dinamis CSR & DSKL';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Laporan Audit</li>';

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

$id_upz_arr = get_filter_arr('id_upz');
$id_metode_arr = get_filter_arr('id_metode');
$id_sumber_arr = get_filter_arr('id_sumber');
$id_kategori_arr = get_filter_arr('id_kategori');
$id_kategori_upz_arr = get_filter_arr('id_kategori_upz');

// Fetch all sub-jenis grouped by parent jenis
$allSubJenis = $conn->query("
    SELECT sjp.*, jp.nama_jenis 
    FROM sub_jenis_pembayaran sjp
    JOIN jenis_pembayaran jp ON sjp.id_jenis_pembayaran = jp.id
    ORDER BY jp.id ASC, sjp.nama_sub_jenis ASC
")->fetch_all(MYSQLI_ASSOC);

$subJenisGrouped = [];
foreach ($allSubJenis as $s) {
    $parent = (int)$s['id_jenis_pembayaran'];
    if (!isset($subJenisGrouped[$parent])) {
        $subJenisGrouped[$parent] = [];
    }
    $subJenisGrouped[$parent][] = [
        'id' => (int)$s['id'],
        'nama' => $s['nama_sub_jenis']
    ];
}

// Retrieve active mapping rules from request: rules[source_sub_id] = target_sub_id
$rules = [];
if (isset($_GET['rules']) && is_array($_GET['rules'])) {
    foreach ($_GET['rules'] as $src_id => $tgt_id) {
        if ((int)$src_id > 0 && (int)$tgt_id > 0) {
            $rules[(int)$src_id] = (int)$tgt_id;
        }
    }
}

// Build helper for parent jenis mapping
$subJenisParentMap = [];
foreach ($allSubJenis as $s) {
    $subJenisParentMap[(int)$s['id']] = (int)$s['id_jenis_pembayaran'];
}

// Build SQL where clause
$where = ["t.tanggal_transaksi >= ?", "t.tanggal_transaksi <= ?"];
$types = "ss";
$params = [$start_date, $end_date];

if (!empty($id_upz_arr)) {
    $in_placeholder = implode(',', array_fill(0, count($id_upz_arr), '?'));
    $where[] = "t.id_upz IN ($in_placeholder)";
    $types .= str_repeat('i', count($id_upz_arr));
    foreach ($id_upz_arr as $v) $params[] = $v;
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
$upzList = get_upz_list();
$metodeList = $conn->query("SELECT * FROM metode_pembayaran")->fetch_all(MYSQLI_ASSOC);
$sumberList = $conn->query("SELECT * FROM sumber_rekening")->fetch_all(MYSQLI_ASSOC);
$kategoriList = get_kategori_dana_list();
$kategoriUPZDropdown = $conn->query("SELECT * FROM kategori_upz ORDER BY urutan ASC, id ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch filtered transactions
$sql = "
    SELECT t.*,
        COALESCE(m.nama_muzaki, u.nama_upz, 'UMUM') as nama_muzaki, 
        u.nama_upz, sjp.nama_sub_jenis,
        mp.nama_metode, sr.nama_sumber
    FROM transaksi t
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
    LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
    LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
    WHERE $where_sql
    ORDER BY t.tanggal_transaksi DESC, t.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$raw_data = $stmt->get_result();

$transactions = [];
$columnTotals = [6 => 0, 7 => 0]; // 6 = Zakat, 7 = Infak
$total_nominal = 0;

while ($row = $raw_data->fetch_assoc()) {
    $amount = (float)$row['jumlah_pembayaran'];
    $total_nominal += $amount;
    
    $sub_id = (int)$row['id_sub_jenis_pembayaran'];
    $original_jenis = (int)$row['id_jenis_pembayaran'];
    
    $mapped_jenis = $original_jenis;
    $mapped_sub = $sub_id;
    
    // Apply rules
    if (isset($rules[$sub_id])) {
        $mapped_sub = $rules[$sub_id];
        $mapped_jenis = $subJenisParentMap[$mapped_sub] ?? 7;
    } else {
        if ($original_jenis == 8 || $original_jenis == 11) {
            $mapped_jenis = 7;
        }
    }
    
    if ($mapped_jenis != 6 && $mapped_jenis != 7) {
        $mapped_jenis = 7;
    }
    
    $row['mapped_jenis_id'] = $mapped_jenis;
    $row['mapped_sub_id'] = $mapped_sub;
    
    // Find mapped name
    if ($mapped_sub != $sub_id) {
        $mapped_name = '';
        foreach ($allSubJenis as $sj) {
            if ($sj['id'] == $mapped_sub) {
                $mapped_name = $sj['nama_sub_jenis'];
                break;
            }
        }
        $row['nama_sub_jenis'] = $mapped_name ?: $row['nama_sub_jenis'];
    }
    
    $columnTotals[$mapped_jenis] += $amount;
    $transactions[] = $row;
}
$stmt->close();

// --- Process Grouped Summaries in PHP ---
$groupedRincianByKategori = [];
$kategoriSektoralTotals = [];
$kategoriSektoralGrand = [];
$recapKategoriJenis = [];

// Define Audit Standard Groupings
$auditGroups = [
    6 => [
        'Zakat Maal Pribadi' => [10, 11],
        'Zakat Maal Badan/Perusahaan' => [12],
        'Zakat Fitrah' => [13, 14],
        'Penerimaan Lain-Lain' => [15, 16, 17]
    ],
    7 => [
        'Infak dan Sedekah Dengan Pembatasan' => [18],
        'Infak dan Sedekah Tanpa Pembatasan' => [19, 20, 21, 22, 23]
    ]
];

function get_audit_group_name($jenis_id, $sub_id, $auditGroups) {
    if (isset($auditGroups[$jenis_id])) {
        foreach ($auditGroups[$jenis_id] as $group_name => $sub_ids) {
            if (in_array($sub_id, $sub_ids)) {
                return $group_name;
            }
        }
        return ($jenis_id == 6) ? 'Penerimaan Lain-Lain' : 'Infak dan Sedekah Tanpa Pembatasan';
    }
    return ($jenis_id == 6) ? 'Penerimaan Lain-Lain' : 'Infak dan Sedekah Tanpa Pembatasan';
}

// Pre-initialize Kategori Dana in strict order
$groupedData = [
    'Zakat' => [
        'Zakat Maal Pribadi' => ['id_jenis' => 6, 'nama' => 'Zakat Maal Pribadi', 'kategori' => [], 'total_all' => 0],
        'Zakat Maal Badan/Perusahaan' => ['id_jenis' => 6, 'nama' => 'Zakat Maal Badan/Perusahaan', 'kategori' => [], 'total_all' => 0],
        'Zakat Fitrah' => ['id_jenis' => 6, 'nama' => 'Zakat Fitrah', 'kategori' => [], 'total_all' => 0],
        'Penerimaan Lain-Lain' => ['id_jenis' => 6, 'nama' => 'Penerimaan Lain-Lain', 'kategori' => [], 'total_all' => 0]
    ],
    'Infak dan sedekah' => [
        'Infak dan Sedekah Dengan Pembatasan' => ['id_jenis' => 7, 'nama' => 'Infak dan Sedekah Dengan Pembatasan', 'kategori' => [], 'total_all' => 0],
        'Infak dan Sedekah Tanpa Pembatasan' => ['id_jenis' => 7, 'nama' => 'Infak dan Sedekah Tanpa Pembatasan', 'kategori' => [], 'total_all' => 0]
    ]
];

$gColTotals = []; // Kategori Dana column totals
$gTotalAll = 0;
foreach ($kategoriList as $k) {
    $gColTotals[$k['id']] = 0;
    foreach ($groupedData as $jenis => &$groups) {
        foreach ($groups as $group_name => &$data) {
            $data['kategori'][$k['id']] = 0;
        }
    }
}
unset($groups); unset($data);

$groupedRekening = []; // Sumber Rekening
$gRekColTotals = [];
foreach ($kategoriList as $k) $gRekColTotals[$k['id']] = 0;

// Fetch Kategori UPZ names and orders
$kategoriUPZList = $conn->query("SELECT * FROM kategori_upz ORDER BY urutan ASC, id ASC")->fetch_all(MYSQLI_ASSOC);
$kategoriUPZOrder = [];
foreach ($kategoriUPZList as $ku) {
    $kategoriUPZOrder[$ku['nama_kategori']] = $ku['urutan'];
}

// Fetch Kategori UPZ mapping for all UPZ
$upzKategoriMap = [];
$upzQuery = $conn->query("SELECT u.id, ku.nama_kategori, ku.id as id_kat FROM upz u LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id");
while ($uq = $upzQuery->fetch_assoc()) {
    $upzKategoriMap[$uq['id']] = [
        'nama' => $uq['nama_kategori'] ?? 'Muzaki Individu / Non-UPZ',
        'id_kat' => $uq['id_kat'] ?? -1
    ];
}

// Muzaki's UPZ mapping
$muzakiUPZMap = [];
$muzakiQuery = $conn->query("SELECT m.id, u.id_kategori_upz, ku.nama_kategori, ku.id as id_kat FROM muzaki m LEFT JOIN upz u ON m.id_upz_sekarang = u.id LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id");
while ($mq = $muzakiQuery->fetch_assoc()) {
    $muzakiUPZMap[$mq['id']] = [
        'nama' => $mq['nama_kategori'] ?? 'Muzaki Individu / Non-UPZ',
        'id_kat' => $mq['id_kat'] ?? -1
    ];
}

foreach ($transactions as $t) {
    $amount = (float)$t['jumlah_pembayaran'];
    $mapped_jenis = $t['mapped_jenis_id'];
    $sub_id = (int)$t['mapped_sub_id'];
    $jenis_nama = ($mapped_jenis == 6) ? 'Zakat' : 'Infak dan sedekah';
    
    // Get Audit Group
    $group_name = get_audit_group_name($mapped_jenis, $sub_id, $auditGroups);
    
    // Determine Kategori UPZ Name for this transaction
    $katName = 'Muzaki Individu / Non-UPZ';
    if ($t['id_upz'] > 0 && isset($upzKategoriMap[$t['id_upz']])) {
        $katName = $upzKategoriMap[$t['id_upz']]['nama'];
    } elseif ($t['id_muzaki'] > 0 && isset($muzakiUPZMap[$t['id_muzaki']])) {
        $katName = $muzakiUPZMap[$t['id_muzaki']]['nama'];
    }
    
    // 1. Grouped Rincian per Kategori UPZ
    if (!isset($groupedRincianByKategori[$katName])) {
        $groupedRincianByKategori[$katName] = [];
        $kategoriSektoralTotals[$katName] = [6 => 0, 7 => 0];
        $kategoriSektoralGrand[$katName] = 0;
    }
    $entName = $t['nama_muzaki'];
    if (!isset($groupedRincianByKategori[$katName][$entName])) {
        $groupedRincianByKategori[$katName][$entName] = [6 => 0, 7 => 0, 'total' => 0];
    }
    $groupedRincianByKategori[$katName][$entName][$mapped_jenis] += $amount;
    $groupedRincianByKategori[$katName][$entName]['total'] += $amount;
    
    $kategoriSektoralTotals[$katName][$mapped_jenis] += $amount;
    $kategoriSektoralGrand[$katName] += $amount;
    
    // 2. Recap Kategori Jenis
    if (!isset($recapKategoriJenis[$katName])) {
        $recapKategoriJenis[$katName] = [6 => 0, 7 => 0, 'total' => 0];
    }
    $recapKategoriJenis[$katName][$mapped_jenis] += $amount;
    $recapKategoriJenis[$katName]['total'] += $amount;
    
    // 3. Kategori Dana Summary Table (Audit Grouping)
    $groupedData[$jenis_nama][$group_name]['total_all'] += $amount;
    $gTotalAll += $amount;
    $katIds = parse_kategori_dana_ids($t['id_kategori_dana']);
    foreach ($kategoriList as $k) {
        if (in_array($k['id'], $katIds)) {
            $groupedData[$jenis_nama][$group_name]['kategori'][$k['id']] += $amount;
            $gColTotals[$k['id']] += $amount;
        }
    }
    
    // 4. Sumber Rekening Summary Table (Audit Grouping)
    $rekening = $t['nama_sumber'] ?? 'TUNAI / KAS';
    $rekening_id = (int)$t['id_sumber_rekening'];
    $row_label = $jenis_nama . ' - ' . $group_name;
    
    if (!isset($groupedRekening[$rekening])) {
        $groupedRekening[$rekening] = [
            'id' => $rekening_id === 0 ? -1 : $rekening_id,
            'subs' => []
        ];
    }
    
    if (empty($groupedRekening[$rekening]['subs'])) {
        $groupedRekening[$rekening]['subs'] = [
            'Zakat - Zakat Maal Pribadi' => ['id_jenis' => 6, 'label' => 'Zakat - Zakat Maal Pribadi', 'kategori' => [], 'total_all' => 0],
            'Zakat - Zakat Maal Badan/Perusahaan' => ['id_jenis' => 6, 'label' => 'Zakat - Zakat Maal Badan/Perusahaan', 'kategori' => [], 'total_all' => 0],
            'Zakat - Zakat Fitrah' => ['id_jenis' => 6, 'label' => 'Zakat - Zakat Fitrah', 'kategori' => [], 'total_all' => 0],
            'Zakat - Penerimaan Lain-Lain' => ['id_jenis' => 6, 'label' => 'Zakat - Penerimaan Lain-Lain', 'kategori' => [], 'total_all' => 0],
            'Infak dan sedekah - Infak dan Sedekah Dengan Pembatasan' => ['id_jenis' => 7, 'label' => 'Infak dan sedekah - Infak dan Sedekah Dengan Pembatasan', 'kategori' => [], 'total_all' => 0],
            'Infak dan sedekah - Infak dan Sedekah Tanpa Pembatasan' => ['id_jenis' => 7, 'label' => 'Infak dan sedekah - Infak dan Sedekah Tanpa Pembatasan', 'kategori' => [], 'total_all' => 0]
        ];
        foreach ($groupedRekening[$rekening]['subs'] as &$s_data) {
            foreach ($kategoriList as $k) {
                $s_data['kategori'][$k['id']] = 0;
            }
        }
        unset($s_data);
    }
    
    $groupedRekening[$rekening]['subs'][$row_label]['total_all'] += $amount;
    foreach ($kategoriList as $k) {
        if (in_array($k['id'], $katIds)) {
            $groupedRekening[$rekening]['subs'][$row_label]['kategori'][$k['id']] += $amount;
            $gRekColTotals[$k['id']] += $amount;
        }
    }
}

// Sort arrays based on Kategori UPZ order
uksort($groupedRincianByKategori, function($a, $b) use ($kategoriUPZOrder) {
    if ($a === 'Muzaki Individu / Non-UPZ') return -1;
    if ($b === 'Muzaki Individu / Non-UPZ') return 1;
    $oa = $kategoriUPZOrder[$a] ?? 9999;
    $ob = $kategoriUPZOrder[$b] ?? 9999;
    return $oa <=> $ob;
});
uksort($recapKategoriJenis, function($a, $b) use ($kategoriUPZOrder) {
    if ($a === 'Muzaki Individu / Non-UPZ') return -1;
    if ($b === 'Muzaki Individu / Non-UPZ') return 1;
    $oa = $kategoriUPZOrder[$a] ?? 9999;
    $ob = $kategoriUPZOrder[$b] ?? 9999;
    return $oa <=> $ob;
});

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <!-- Filter Card -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-filter mr-2"></i>Filter Laporan Audit</h3>
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET" action="index.php">
                    <!-- Hidden rules array inputs -->
                    <?php foreach ($rules as $src_id => $tgt_id): ?>
                    <input type="hidden" name="rules[<?= $src_id ?>]" value="<?= $tgt_id ?>">
                    <?php endforeach; ?>

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
                                <label>Muzaki dari UPZ</label>
                                <select class="form-control select2" name="id_upz[]" multiple="multiple" data-placeholder="Semua UPZ / Individu">
                                    <?php foreach ($upzList as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= in_array($u['id'], $id_upz_arr) ? 'selected' : '' ?>><?= clean($u['nama_upz']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Kategori Sektoral UPZ</label>
                                <select class="form-control select2" name="id_kategori_upz[]" multiple="multiple" data-placeholder="Semua Kategori Sektoral">
                                    <option value="-1" <?= in_array(-1, $id_kategori_upz_arr) ? 'selected' : '' ?>>Muzaki Individu / Non-UPZ</option>
                                    <?php foreach ($kategoriUPZDropdown as $ku): ?>
                                    <option value="<?= $ku['id'] ?>" <?= in_array($ku['id'], $id_kategori_upz_arr) ? 'selected' : '' ?>><?= clean($ku['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kategori Dana</label>
                                <select class="form-control select2" name="id_kategori[]" multiple="multiple" data-placeholder="Semua Kategori Dana">
                                    <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= in_array($k['id'], $id_kategori_arr) ? 'selected' : '' ?>><?= clean($k['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Metode Pembayaran</label>
                                <select class="form-control select2" name="id_metode[]" multiple="multiple" data-placeholder="Semua Metode">
                                    <?php foreach ($metodeList as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= in_array($m['id'], $id_metode_arr) ? 'selected' : '' ?>><?= clean($m['nama_metode']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
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

                    <!-- Pemetaan CSR & DSKL Rule-based Section -->
                    <div class="card card-outline card-secondary mt-3 flat-card border shadow-none" style="border: 1px solid #b8b8b8 !important;">
                        <div class="card-header bg-light">
                            <h5 class="card-title font-weight-bold mb-0 text-dark"><i class="fas fa-random mr-2"></i>Aturan Pemetaan Klasifikasi Audit (CSR & DSKL)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Active Rules List -->
                                <div class="col-md-6 border-right">
                                    <label class="text-secondary">Aturan Pemetaan Aktif:</label>
                                    <div id="rulesList" class="border p-2 bg-light rounded" style="min-height: 140px; font-size: 0.8rem; border-color:#b8b8b8 !important;">
                                        <?php if (empty($rules)): ?>
                                            <div class="text-muted p-4 text-center">Belum ada aturan pemetaan khusus. Secara default, seluruh dana CSR & DSKL dipetakan ke <strong>Infak & Sedekah</strong>.</div>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush mb-0 bg-transparent">
                                                <?php foreach ($rules as $src_id => $tgt_id): 
                                                    // Find names
                                                    $src_name = ''; $src_type = '';
                                                    $tgt_name = ''; $tgt_type = '';
                                                    foreach ($allSubJenis as $sj) {
                                                        if ($sj['id'] == $src_id) {
                                                            $src_name = $sj['nama_sub_jenis'];
                                                            $src_type = $sj['nama_jenis'];
                                                        }
                                                        if ($sj['id'] == $tgt_id) {
                                                            $tgt_name = $sj['nama_sub_jenis'];
                                                            $tgt_type = $sj['nama_jenis'];
                                                        }
                                                    }
                                                ?>
                                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center py-1 px-2 border-bottom">
                                                        <div>
                                                            <span class="badge badge-secondary"><?= htmlspecialchars($src_type) ?></span> <strong><?= htmlspecialchars($src_name) ?></strong> 
                                                            <i class="fas fa-arrow-right mx-2 text-muted"></i> 
                                                            <span class="badge badge-success"><?= htmlspecialchars($tgt_type) ?></span> <strong><?= htmlspecialchars($tgt_name) ?></strong>
                                                        </div>
                                                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRule(<?= $src_id ?>)">
                                                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                                                        </button>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Add Rule Form -->
                                <div class="col-md-6">
                                    <label class="text-secondary">Tambah Aturan Pemetaan Baru:</label>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="small text-muted mb-1">1. Jenis Asal (CSR / DSKL)</label>
                                            <select id="src_type" class="form-control form-control-sm" style="border-color:#b8b8b8;">
                                                <option value="">-- Pilih Jenis --</option>
                                                <option value="11">CSR</option>
                                                <option value="8">DSKL</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="small text-muted mb-1">2. Sub Kategori Asal</label>
                                            <select id="src_sub" class="form-control form-control-sm" style="border-color:#b8b8b8;" disabled>
                                                <option value="">-- Pilih Sub Kategori --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="small text-muted mb-1">3. Pindahkan Ke (Zakat / Infak)</label>
                                            <select id="tgt_type" class="form-control form-control-sm" style="border-color:#b8b8b8;">
                                                <option value="">-- Pilih Tujuan --</option>
                                                <option value="6">ZAKAT</option>
                                                <option value="7">INFAK & SEDEKAH</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label class="small text-muted mb-1">4. Sub Kategori Tujuan</label>
                                            <select id="tgt_sub" class="form-control form-control-sm" style="border-color:#b8b8b8;" disabled>
                                                <option value="">-- Pilih Sub Tujuan --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 text-right mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-success px-4" onclick="addRule()">
                                                <i class="fas fa-plus mr-1"></i> Tambahkan ke Pemetaan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
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
        <div class="card flat-card mb-3 border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-body py-2 px-3 bg-transparent d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-dark font-weight-bold"><i class="fas fa-poll mr-2"></i>Hasil Laporan Audit</h5>
                <form id="exportForm" method="POST" action="export.php" target="_blank" class="form-inline mb-0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="start_date" value="<?= $start_date ?>">
                    <input type="hidden" name="end_date" value="<?= $end_date ?>">
                    <?php foreach ($id_upz_arr as $upz_id): ?>
                    <input type="hidden" name="id_upz[]" value="<?= htmlspecialchars($upz_id) ?>">
                    <?php endforeach; ?>
                    <?php foreach ($id_kategori_upz_arr as $kupz_id): ?>
                    <input type="hidden" name="id_kategori_upz[]" value="<?= htmlspecialchars($kupz_id) ?>">
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
                    
                    <?php foreach ($rules as $src_id => $tgt_id): ?>
                    <input type="hidden" name="rules[<?= $src_id ?>]" value="<?= $tgt_id ?>">
                    <?php endforeach; ?>
                    
                    <button type="submit" name="export_format" value="csv" class="btn btn-sm btn-outline-info mr-1">
                        <i class="fas fa-file-csv mr-1"></i>CSV
                    </button>
                    <button type="submit" name="export_format" value="excel" class="btn btn-sm btn-outline-success mr-1">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </button>
                    <button type="submit" name="export_format" value="pdf" class="btn btn-sm btn-outline-danger mr-1">
                        <i class="fas fa-file-pdf mr-1"></i>PDF
                    </button>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card flat-card shadow-none border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-body">
                <div class="alert alert-info alert-excel mb-4 shadow-none" style="background-color:rgba(23,162,184,0.05); color:#17a2b8; border:1px solid #17a2b8;">
                    <h5><i class="icon fas fa-info-circle"></i> Ringkasan Audit</h5>
                    Total Transaksi: <strong><?= count($transactions) ?></strong> data<br>
                    Total Penerimaan Ter-Audit (Rp): <strong class="currency-display h4 mb-0"><?= format_currency($total_nominal) ?></strong>
                </div>

                <div class="table-responsive">
                    <table id="dataTable" class="table table-bordered table-hover table-excel" style="font-size: 0.85rem;">
                        <thead>
                            <tr class="bg-light">
                                <th width="3%">No</th>
                                <th>Tanggal</th>
                                <th>Nama Muzaki/UPZ</th>
                                <th>Sub Jenis Pembayaran</th>
                                <th class="text-right">Zakat (Rp)</th>
                                <th class="text-right">Infak & Sedekah (Rp)</th>
                                <th class="text-right">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <?php if (count($transactions) > 0): ?>
                        <tfoot>
                            <tr class="bg-gray-light font-weight-bold">
                                <th colspan="4" class="text-right">TOTAL</th>
                                <th class="text-right"><?= format_currency($columnTotals[6]) ?></th>
                                <th class="text-right"><?= format_currency($columnTotals[7]) ?></th>
                                <th class="text-right" style="background: #ffc107;"><strong><?= format_currency($total_nominal) ?></strong></th>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rincian Pengumpulan per Kategori UPZ Card -->
        <div class="card flat-card shadow-none mt-4 border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-success mb-0"><i class="fas fa-sitemap mr-2"></i>Rincian Pengumpulan per Kategori UPZ (Format Audit)</h3>
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
                                            <th width="20%" class="text-right">ZAKAT (Rp)</th>
                                            <th width="20%" class="text-right">INFAK & SEDEKAH (Rp)</th>
                                            <th width="20%" style="vertical-align: middle;" class="text-right">JUMLAH (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $subNo = 1; foreach($entities as $eName => $eData): ?>
                                            <tr>
                                                <td class="text-center"><?= $subNo++ ?></td>
                                                <td><strong><?= htmlspecialchars($eName) ?></strong></td>
                                                <td class="text-right"><?= $eData[6] > 0 ? 'Rp ' . number_format($eData[6], 0, ',', '.') : '-' ?></td>
                                                <td class="text-right"><?= $eData[7] > 0 ? 'Rp ' . number_format($eData[7], 0, ',', '.') : '-' ?></td>
                                                <td class="text-right font-weight-bold bg-light">Rp <?= number_format($eData['total'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light font-weight-bold text-right">
                                        <tr>
                                            <td colspan="2" class="text-center">SUBTOTAL KATEGORI</td>
                                            <td>Rp <?= number_format($kategoriSektoralTotals[$katName][6], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($kategoriSektoralTotals[$katName][7], 0, ',', '.') ?></td>
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
                        <i class="fas fa-chart-bar mr-2"></i>Rekapitulasi Sektoral UPZ per Jenis Pembayaran (Audit)
                    </h5>
                    <div class="table-responsive">
                        <table id="sektoralRecapTable" class="table table-bordered table-hover mb-0" style="font-size: 0.85rem;">
                            <thead class="bg-gray-light">
                                <tr class="text-center">
                                    <th>KATEGORI SEKTORAL UPZ</th>
                                    <th width="20%" class="text-right">ZAKAT (Rp)</th>
                                    <th width="20%" class="text-right">INFAK & SEDEKAH (Rp)</th>
                                    <th width="20%" class="text-right">TOTAL (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recapKategoriJenis)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Data kosong untuk filter ini.</td></tr>
                                <?php else: ?>
                                    <?php 
                                    $colTotals = [6 => 0, 7 => 0];
                                    $grandTotalRecap = 0;
                                    
                                    foreach ($recapKategoriJenis as $katName => $data): 
                                        $grandTotalRecap += $data['total'];
                                        $colTotals[6] += $data[6];
                                        $colTotals[7] += $data[7];
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($katName) ?></strong></td>
                                        <td class="text-right">Rp <?= number_format($data[6], 0, ',', '.') ?></td>
                                        <td class="text-right">Rp <?= number_format($data[7], 0, ',', '.') ?></td>
                                        <td class="text-right font-weight-bold bg-light">Rp <?= number_format($data['total'], 0, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($recapKategoriJenis)): ?>
                            <tfoot class="bg-gray-light font-weight-bold text-right">
                                <tr>
                                    <td class="text-center">TOTAL KESELURUHAN</td>
                                    <td>Rp <?= number_format($colTotals[6], 0, ',', '.') ?></td>
                                    <td>Rp <?= number_format($colTotals[7], 0, ',', '.') ?></td>
                                    <td class="bg-warning text-dark">Rp <?= number_format($grandTotalRecap, 0, ',', '.') ?></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rekapitulasi per Sumber Rekening Card -->
        <div class="card flat-card shadow-none mt-4 border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-university mr-2"></i>Rekapitulasi per Sumber Rekening (Audit)</h3>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedRekening)): ?>
                                <tr><td colspan="<?= count($kategoriList) + 1 ?>" class="text-center py-4 text-muted">Data kosong untuk filter ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedRekening as $rekening => $accData): ?>
                                    <?php 
                                        $subs = $accData['subs'];
                                        $subsum_kategori = [];
                                        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
                                        foreach ($subs as $label => $d) {
                                            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
                                        }
                                    ?>
                                    <tr class="table-info font-weight-bold">
                                        <td><?= strtoupper($rekening) ?></td>
                                        <?php foreach ($kategoriList as $k): ?>
                                            <td class="text-right">Rp <?= number_format($subsum_kategori[$k['id']], 0, ',', '.') ?>,-</td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php foreach ($subs as $label => $data): ?>
                                        <?php if ($data['total_all'] > 0): ?>
                                        <tr>
                                            <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($data['label']) ?></td>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <td class="text-right">Rp <?= number_format($data['kategori'][$k['id']], 0, ',', '.') ?>,-</td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endif; ?>
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
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rekapitulasi Kategori Dana Card -->
        <div class="card flat-card shadow-none mt-4 border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-table mr-2"></i>Rekapitulasi Kategori Dana (Audit)</h3>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($groupedData)): ?>
                                <tr><td colspan="<?= count($kategoriList) + 1 ?>" class="text-center py-4 text-muted">Data kosong untuk filter ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($groupedData as $jenis => $subs): ?>
                                    <?php 
                                        $subsum_kategori = []; 
                                        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
                                        foreach ($subs as $group_name => $d) {
                                            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
                                        }
                                    ?>
                                    <tr class="table-secondary font-weight-bold">
                                        <td><?= strtoupper($jenis) ?></td>
                                        <?php foreach ($kategoriList as $k): ?>
                                            <td class="text-right">Rp <?= number_format($subsum_kategori[$k['id']], 0, ',', '.') ?>,-</td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php foreach ($subs as $group_name => $data): ?>
                                        <tr>
                                            <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($data['nama']) ?></td>
                                            <?php foreach ($kategoriList as $k): ?>
                                                <td class="text-right">Rp <?= number_format($data['kategori'][$k['id']], 0, ',', '.') ?>,-</td>
                                            <?php endforeach; ?>
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
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../../inc/footer.php'; ?>

<script>
const reportTransactions = <?= json_encode($transactions) ?>;
const reportStartDate = '<?= date('d/m/Y', strtotime($start_date)) ?>';
const reportEndDate = '<?= date('d/m/Y', strtotime($end_date)) ?>';
const subJenisGrouped = <?= json_encode($subJenisGrouped) ?>;

// Dependency dropdowns logic for adding rules
$('#src_type').on('change', function() {
    const parentId = $(this).val();
    const subSelect = $('#src_sub');
    subSelect.empty().append('<option value="">-- Pilih Sub Kategori --</option>');
    
    if (parentId && subJenisGrouped[parentId]) {
        subJenisGrouped[parentId].forEach(item => {
            subSelect.append($('<option>').val(item.id).text(item.nama));
        });
        subSelect.prop('disabled', false);
    } else {
        subSelect.prop('disabled', true);
    }
});

$('#tgt_type').on('change', function() {
    const parentId = $(this).val();
    const subSelect = $('#tgt_sub');
    subSelect.empty().append('<option value="">-- Pilih Sub Kategori Tujuan --</option>');
    
    if (parentId && subJenisGrouped[parentId]) {
        subJenisGrouped[parentId].forEach(item => {
            subSelect.append($('<option>').val(item.id).text(item.nama));
        });
        subSelect.prop('disabled', false);
    } else {
        subSelect.prop('disabled', true);
    }
});

function addRule() {
    const srcSub = $('#src_sub').val();
    const tgtSub = $('#tgt_sub').val();
    if (!srcSub || !tgtSub) {
        alert('Mohon lengkapi pilihan sub kategori asal dan sub kategori tujuan.');
        return;
    }
    
    // Add dynamic hidden input to filterForm
    $('<input>').attr({
        type: 'hidden',
        name: 'rules[' + srcSub + ']',
        value: tgtSub
    }).appendTo('#filterForm');
    
    // Auto submit to refresh page with new rule
    $('#filterForm').submit();
}

function removeRule(srcId) {
    // Remove rule input from filterForm
    $('#filterForm input[name="rules[' + srcId + ']"]').remove();
    // Auto submit to refresh page
    $('#filterForm').submit();
}

$(function() {
    const dataSet = reportTransactions.map((row, idx) => {
        const formatCurrency = (val) => {
            return 'Rp ' + parseInt(val).toLocaleString('id-ID');
        };
        
        const parts = row.tanggal_transaksi.split('-');
        const dateFormatted = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : row.tanggal_transaksi;
        
        const isZakat = row.mapped_jenis_id == 6;
        
        return [
            idx + 1,
            dateFormatted,
            '<strong>' + (row.nama_muzaki || '-') + '</strong>',
            htmlspecialchars(row.nama_sub_jenis || '-'),
            isZakat ? '<div class="text-right">' + formatCurrency(row.jumlah_pembayaran) + '</div>' : '<div class="text-right">-</div>',
            !isZakat ? '<div class="text-right">' + formatCurrency(row.jumlah_pembayaran) + '</div>' : '<div class="text-right">-</div>',
            '<div class="text-right"><strong>' + formatCurrency(row.jumlah_pembayaran) + '</strong></div>'
        ];
    });

    function htmlspecialchars(string) {
        if (!string) return '';
        return string.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

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

    // Synchronize all filter choices to the export form dynamically on submit
    $('#exportForm').on('submit', function() {
        $(this).find('input[name="start_date"], input[name="end_date"]').remove();
        $(this).find('input[name^="id_upz"]').remove();
        $(this).find('input[name^="id_kategori_upz"]').remove();
        $(this).find('input[name^="id_kategori"]').remove();
        $(this).find('input[name^="id_metode"]').remove();
        $(this).find('input[name^="id_sumber"]').remove();
        $(this).find('input[name^="rules"]').remove();
        
        // Append current values from filterForm
        $('<input>').attr({ type: 'hidden', name: 'start_date', value: $('#start_date').val() }).appendTo(this);
        $('<input>').attr({ type: 'hidden', name: 'end_date', value: $('#end_date').val() }).appendTo(this);
        
        $('#filterForm').serializeArray().forEach(item => {
            if (item.name !== 'start_date' && item.name !== 'end_date') {
                $('<input>').attr({
                    type: 'hidden',
                    name: item.name,
                    value: item.value
                }).appendTo(this);
            }
        });
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
