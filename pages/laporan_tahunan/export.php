<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    die("Error: CSRF Token tidak valid.");
}

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db();
$format = $_POST['format'] ?? 'csv';

// --- Standardized Filter Parameters ---
$start_date = $_POST['start_date'] ?? date('Y-01-01');
$end_date = $_POST['end_date'] ?? date('Y-12-31');

// Helper to extract array from request
if (!function_exists('get_filter_arr')) {
    function get_filter_arr($name, $method = 'POST') {
        $src = ($method === 'POST') ? $_POST : $_GET;
        $val = $src[$name] ?? [];
        if (!is_array($val)) {
            $val = $val !== '' ? [$val] : [];
        }
        return array_filter(array_map('intval', $val), function($v) { return $v != 0; });
    }
}

$id_jenis_arr = get_filter_arr('id_jenis', 'POST');
$id_upz_arr = get_filter_arr('id_upz', 'POST');
$id_sub_jenis_arr = get_filter_arr('id_sub_jenis', 'POST');
$id_metode_arr = get_filter_arr('id_metode', 'POST');
$id_sumber_arr = get_filter_arr('id_sumber', 'POST');
$id_kategori_arr = get_filter_arr('id_kategori', 'POST');

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

$period_text = date('d/m/Y', strtotime($start_date)) . " s/d " . date('d/m/Y', strtotime($end_date));

// --- Data Fetching ---
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
        $reportData[$name] = array_fill(1, 12, 0); $reportData[$name]['row_total'] = 0;
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

// --- Dropdowns & Helper Lists ---
$jenisList = get_jenis_pembayaran_list();
$kategoriList = get_kategori_dana_list();

// --- Fetch Grouped Data for Rekapitulasi Kategori UPZ per Jenis Pembayaran (Same Filters) ---
$sqlRecapUPZ = "
    SELECT 
        COALESCE(ku.nama_kategori, ku_m.nama_kategori, 'Muzaki Individu / Non-UPZ') as kategori_upz_name,
        t.id_jenis_pembayaran,
        SUM(t.jumlah_pembayaran) as total
    FROM transaksi t
    LEFT JOIN muzaki m ON t.id_muzaki = m.id
    LEFT JOIN upz u ON t.id_upz = u.id
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
    LEFT JOIN upz upz_m ON m.id_upz_sekarang = upz_m.id
    LEFT JOIN kategori_upz ku_m ON upz_m.id_kategori_upz = ku_m.id
    $where_sql
    GROUP BY kategori_upz_name, t.id_jenis_pembayaran
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
foreach ($groupedByKategori as $katName => $unused) {
    $recapKategoriJenis[$katName] = [];
    foreach ($jenisList as $j) {
        $recapKategoriJenis[$katName][$j['id']] = 0;
    }
    $recapKategoriJenis[$katName]['total'] = 0;
}

foreach ($recapDataRaw as $row) {
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

$filename = "Laporan_Tahunan_" . date('Ymd_His');

// --- CSV FORMAT ---
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    $output = fopen('php://output', 'w');
    
    // 1. Muzaki/UPZ breakdown
    $header = ['NO', 'NAMA MUZAKI/UPZ'];
    foreach ($activeMonths as $m) $header[] = strtoupper(substr(month_name($m), 0, 3));
    $header[] = 'JUMLAH';
    fputcsv($output, $header);
    $no = 1;
    foreach ($reportData as $name => $data) {
        $row = [$no++, $name];
        foreach ($activeMonths as $m) $row[] = $data[$m];
        $row[] = $data['row_total'];
        fputcsv($output, $row);
    }
    // Total row
    $totRow = ['', 'TOTAL KESELURUHAN'];
    foreach ($activeMonths as $m) $totRow[] = $monthTotals[$m];
    $totRow[] = $grandTotal;
    fputcsv($output, $totRow);
    
    // Add spacer rows
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 1B. Muzaki/UPZ breakdown by Category
    fputcsv($output, ['RINCIAN PENGUMPULAN PER KATEGORI UPZ']);
    foreach ($groupedByKategori as $katName => $entities) {
        fputcsv($output, ['KATEGORI: ' . strtoupper($katName)]);
        $subHeader = ['NO', 'NAMA MUZAKI/UPZ'];
        foreach ($activeMonths as $m) $subHeader[] = strtoupper(substr(month_name($m), 0, 3));
        $subHeader[] = 'JUMLAH';
        fputcsv($output, $subHeader);
        
        $subNo = 1;
        foreach ($entities as $eName => $eData) {
            $row = [$subNo++, $eName];
            foreach ($activeMonths as $m) $row[] = $eData[$m];
            $row[] = $eData['row_total'];
            fputcsv($output, $row);
        }
        
        $subTotRow = ['', 'SUBTOTAL KATEGORI ' . strtoupper($katName)];
        foreach ($activeMonths as $m) $subTotRow[] = $kategoriMonthTotals[$katName][$m];
        $subTotRow[] = $kategoriGrandTotals[$katName];
        fputcsv($output, $subTotRow);
        fputcsv($output, []); // spacer row
    }
    
    // Add spacer rows
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 1C. Rekapitulasi Sektoral UPZ per Jenis Pembayaran
    fputcsv($output, ['REKAPITULASI SEKTORAL UPZ PER JENIS PEMBAYARAN']);
    $subHeaderSektoral = ['Kategori Sektoral UPZ'];
    foreach ($jenisList as $j) $subHeaderSektoral[] = strtoupper($j['nama_jenis']);
    $subHeaderSektoral[] = 'TOTAL';
    fputcsv($output, $subHeaderSektoral);
    
    $colTotalsSektoral = [];
    foreach ($jenisList as $j) $colTotalsSektoral[$j['id']] = 0;
    $grandTotalRecapSektoral = 0;
    
    foreach ($recapKategoriJenis as $katName => $data) {
        $row = [$katName];
        foreach ($jenisList as $j) {
            $val = $data[$j['id']];
            $row[] = $val;
            $colTotalsSektoral[$j['id']] += $val;
        }
        $row[] = $data['total'];
        $grandTotalRecapSektoral += $data['total'];
        fputcsv($output, $row);
    }
    
    $totRowSektoral = ['TOTAL KESELURUHAN'];
    foreach ($jenisList as $j) $totRowSektoral[] = $colTotalsSektoral[$j['id']];
    $totRowSektoral[] = $grandTotalRecapSektoral;
    fputcsv($output, $totRowSektoral);

    // Add spacer rows
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 2. Rekapitulasi per Sumber Rekening
    fputcsv($output, ['REKAPITULASI PER SUMBER REKENING']);
    $subHeaderRek = ['Sumber Rekening & Sub-Jenis'];
    foreach ($kategoriList as $k) $subHeaderRek[] = $k['nama_kategori'];
    fputcsv($output, $subHeaderRek);
    foreach ($groupedRekening as $rekening => $accData) {
        $subs = $accData['subs'];
        $subsum_kategori = [];
        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
        foreach ($subs as $sub_id => $d) {
            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
        }
        
        $row = [strtoupper($rekening)];
        foreach ($kategoriList as $k) $row[] = $subsum_kategori[$k['id']];
        fputcsv($output, $row);
        
        foreach ($subs as $sub_id => $data) {
            $subRow = ['  ' . $data['label']];
            foreach ($kategoriList as $k) $subRow[] = $data['kategori'][$k['id']];
            fputcsv($output, $subRow);
        }
    }
    $totRowRek = ['TOTAL KESELURUHAN'];
    foreach ($kategoriList as $k) $totRowRek[] = $gRekColTotals[$k['id']];
    fputcsv($output, $totRowRek);
    
    // Add spacer rows
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 3. Rekapitulasi Kategori Dana
    fputcsv($output, ['REKAPITULASI KATEGORI DANA']);
    $subHeaderKat = ['Sumber Dana (Jenis & Sub-Jenis)'];
    foreach ($kategoriList as $k) $subHeaderKat[] = $k['nama_kategori'];
    fputcsv($output, $subHeaderKat);
    foreach ($groupedData as $jenis => $subs) {
        $subsum_kategori = []; 
        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
        foreach ($subs as $sub_id => $d) {
            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
        }
        
        $row = [strtoupper($jenis)];
        foreach ($kategoriList as $k) $row[] = $subsum_kategori[$k['id']];
        fputcsv($output, $row);
        
        foreach ($subs as $sub_id => $data) {
            $subRow = ['  ' . $data['nama']];
            foreach ($kategoriList as $k) $subRow[] = $data['kategori'][$k['id']];
            fputcsv($output, $subRow);
        }
    }
    $totRowKat = ['TOTAL KESELURUHAN'];
    foreach ($kategoriList as $k) $totRowKat[] = $gColTotals[$k['id']];
    fputcsv($output, $totRowKat);

    fclose($output); exit;
}

// --- HTML Template for Excel/PDF ---
$html = '
<style>
    body { font-family: "Helvetica", Arial, sans-serif; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 25px; }
    th, td { border: 1px solid #ccc; padding: 5px; }
    th { background-color: #f4f4f4; text-align: center; font-weight: bold; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .bg-light { background-color: #f9f9f9; }
    .font-weight-bold { font-weight: bold; }
    .table-info { background-color: #d1ecf1; }
    .table-secondary { background-color: #e2e3e5; }
    .title-sec { margin-top: 30px; margin-bottom: 5px; border-left: 4px solid #0d9f4f; padding-left: 8px; font-weight: bold; font-size: 13px; color: #333; }
</style>
<div style="text-align: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #0d9f4f; text-transform: uppercase;">Laporan Tahunan Penerimaan ZIS</h2>
    <p style="margin: 5px 0;">Periode: ' . $period_text . '</p>
</div>

<div class="title-sec">1. Rincian Penerimaan Bulanan per Muzaki/UPZ</div>
<table>
    <thead>
        <tr>
            <th>NO</th>
            <th>NAMA MUZAKI/UPZ</th>';
foreach ($activeMonths as $m) $html .= '<th>' . strtoupper(substr(month_name($m), 0, 3)) . '</th>';
$html .= '  <th>JUMLAH</th>
        </tr>
    </thead>
    <tbody>';

if (empty($reportData)) {
    $html .= '<tr><td colspan="' . (3 + count($activeMonths)) . '" class="text-center">Data kosong.</td></tr>';
} else {
    $no = 1;
    foreach ($reportData as $name => $data) {
        $html .= '<tr>
            <td class="text-center">' . $no++ . '</td>
            <td><strong>' . htmlspecialchars($name) . '</strong></td>';
        foreach ($activeMonths as $m) {
            $val = $data[$m] > 0 ? number_format($data[$m], 0, ',', '.') : '-';
            $html .= '<td class="text-right">' . $val . '</td>';
        }
        $html .= '<td class="text-right bg-light"><strong>' . number_format($data['row_total'], 0, ',', '.') . '</strong></td></tr>';
    }
}
$html .= '</tbody><tfoot><tr style="background: #eee; font-weight: bold;"><td colspan="2" class="text-center">TOTAL KESELURUHAN</td>';
foreach ($activeMonths as $m) $html .= '<td class="text-right">' . number_format($monthTotals[$m], 0, ',', '.') . '</td>';
$html .= '<td class="text-right" style="background-color: #ffc107;">' . number_format($grandTotal, 0, ',', '.') . '</td></tr></tfoot></table>';

// 1B. Rincian Pengumpulan per Kategori UPZ
$html .= '<div class="title-sec">1B. Rincian Pengumpulan per Kategori UPZ</div>';
if (empty($groupedByKategori)) {
    $html .= '<p style="text-align: center; color: #666; padding: 10px;">Data kosong.</p>';
} else {
    foreach ($groupedByKategori as $katName => $entities) {
        $html .= '<h4 style="margin: 15px 0 5px 0; color: #0d9f4f; font-size: 11px;">KATEGORI SEKTORAL: ' . htmlspecialchars($katName) . '</h4>';
        $html .= '<table>
            <thead>
                <tr>
                    <th style="width: 5%;">NO</th>
                    <th>NAMA MUZAKI/UPZ</th>';
        foreach ($activeMonths as $m) $html .= '<th style="width: 7%;">' . strtoupper(substr(month_name($m), 0, 3)) . '</th>';
        $html .= '  <th style="width: 10%;">JUMLAH</th>
                </tr>
            </thead>
            <tbody>';
        
        $subNo = 1;
        foreach ($entities as $eName => $eData) {
            $html .= '<tr>
                <td class="text-center">' . $subNo++ . '</td>
                <td><strong>' . htmlspecialchars($eName) . '</strong></td>';
            foreach ($activeMonths as $m) {
                $val = $eData[$m] > 0 ? number_format($eData[$m], 0, ',', '.') : '-';
                $html .= '<td class="text-right">' . $val . '</td>';
            }
            $html .= '<td class="text-right bg-light"><strong>' . number_format($eData['row_total'], 0, ',', '.') . '</strong></td></tr>';
        }
        
        $html .= '</tbody><tfoot><tr style="background: #eee; font-weight: bold;"><td colspan="2" class="text-center">SUBTOTAL KATEGORI</td>';
        foreach ($activeMonths as $m) $html .= '<td class="text-right">' . number_format($kategoriMonthTotals[$katName][$m], 0, ',', '.') . '</td>';
        $html .= '<td class="text-right" style="background-color: #ffc107; color: #212529;">' . number_format($kategoriGrandTotals[$katName], 0, ',', '.') . '</td></tr></tfoot></table>';
    }
}

// Rekapitulasi Sektoral UPZ per Jenis Pembayaran (part of 1B)
$html .= '<div style="margin-top: 30px; margin-bottom: 10px; border-bottom: 2px dashed #b8b8b8; padding-bottom: 5px; font-weight: bold; font-size: 12px; color: #0d9f4f;">
    Rekapitulasi Sektoral UPZ per Jenis Pembayaran
</div>';
$html .= '<table>
    <thead>
        <tr style="background-color: #f4f4f4;">
            <th>Kategori Sektoral UPZ</th>';
foreach ($jenisList as $j) {
    $html .= '<th class="text-right">' . strtoupper(htmlspecialchars($j['nama_jenis'])) . '</th>';
}
$html .= '  <th class="text-right" style="width: 15%;">TOTAL</th>
        </tr>
    </thead>
    <tbody>';

if (empty($recapKategoriJenis)) {
    $html .= '<tr><td colspan="' . (count($jenisList) + 2) . '" class="text-center">Data kosong.</td></tr>';
} else {
    $colTotalsSektoral = [];
    foreach ($jenisList as $j) $colTotalsSektoral[$j['id']] = 0;
    $grandTotalRecapSektoral = 0;
    
    foreach ($recapKategoriJenis as $katName => $data) {
        $grandTotalRecapSektoral += $data['total'];
        $html .= '<tr>
            <td><strong>' . htmlspecialchars($katName) . '</strong></td>';
        foreach ($jenisList as $j) {
            $val = $data[$j['id']];
            $colTotalsSektoral[$j['id']] += $val;
            $html .= '<td class="text-right">Rp ' . number_format($val, 0, ',', '.') . '</td>';
        }
        $html .= '<td class="text-right bg-light"><strong>Rp ' . number_format($data['total'], 0, ',', '.') . '</strong></td></tr>';
    }
}
$html .= '</tbody>';
if (!empty($recapKategoriJenis)) {
    $html .= '<tfoot><tr style="background: #eee; font-weight: bold;"><td class="text-center">TOTAL KESELURUHAN</td>';
    foreach ($jenisList as $j) {
        $html .= '<td class="text-right">Rp ' . number_format($colTotalsSektoral[$j['id']], 0, ',', '.') . '</td>';
    }
    $html .= '<td class="text-right" style="background-color: #ffc107; color: #212529;">Rp ' . number_format($grandTotalRecapSektoral, 0, ',', '.') . '</td></tr></tfoot>';
}
$html .= '</table>';

// 2. Rekapitulasi per Sumber Rekening
$html .= '<div class="title-sec">2. Rekapitulasi per Sumber Rekening</div>';
$html .= '<table>
    <thead>
        <tr style="background-color: #f4f4f4;">
            <th>Sumber Rekening & Sub-Jenis</th>';
foreach ($kategoriList as $k) {
    $html .= '<th class="text-right">' . htmlspecialchars($k['nama_kategori']) . '</th>';
}
$html .= '</tr>
    </thead>
    <tbody>';
if (empty($groupedRekening)) {
    $html .= '<tr><td colspan="' . (count($kategoriList) + 1) . '" class="text-center">Data kosong.</td></tr>';
} else {
    foreach ($groupedRekening as $rekening => $accData) {
        $subs = $accData['subs'];
        $subsum_kategori = [];
        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
        foreach ($subs as $sub_id => $d) {
            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
        }
        $html .= '<tr class="table-info font-weight-bold" style="background-color: #d1ecf1;">
            <td>' . strtoupper($rekening) . '</td>';
        foreach ($kategoriList as $k) {
            $html .= '<td class="text-right">Rp ' . number_format($subsum_kategori[$k['id']], 0, ',', '.') . '</td>';
        }
        $html .= '</tr>';
        foreach ($subs as $sub_id => $data) {
            $html .= '<tr>
                <td style="padding-left: 20px;">- ' . htmlspecialchars($data['label']) . '</td>';
            foreach ($kategoriList as $k) {
                $html .= '<td class="text-right">Rp ' . number_format($data['kategori'][$k['id']], 0, ',', '.') . '</td>';
            }
            $html .= '</tr>';
        }
    }
}
$html .= '</tbody>';
if (!empty($groupedRekening)) {
    $html .= '<tfoot><tr style="background: #eee; font-weight: bold;"><td class="text-right">TOTAL KESELURUHAN</td>';
    foreach ($kategoriList as $k) {
        $html .= '<td class="text-right">Rp ' . number_format($gRekColTotals[$k['id']], 0, ',', '.') . '</td>';
    }
    $html .= '</tr></tfoot>';
}
$html .= '</table>';

// 3. Rekapitulasi Kategori Dana
$html .= '<div class="title-sec">3. Rekapitulasi Kategori Dana</div>';
$html .= '<table>
    <thead>
        <tr style="background-color: #f4f4f4;">
            <th>Sumber Dana (Jenis & Sub-Jenis)</th>';
foreach ($kategoriList as $k) {
    $html .= '<th class="text-right">' . htmlspecialchars($k['nama_kategori']) . '</th>';
}
$html .= '</tr>
    </thead>
    <tbody>';
if (empty($groupedData)) {
    $html .= '<tr><td colspan="' . (count($kategoriList) + 1) . '" class="text-center">Data kosong.</td></tr>';
} else {
    foreach ($groupedData as $jenis => $subs) {
        $subsum_kategori = [];
        foreach ($kategoriList as $k) $subsum_kategori[$k['id']] = 0;
        foreach ($subs as $sub_id => $d) {
            foreach ($kategoriList as $k) $subsum_kategori[$k['id']] += $d['kategori'][$k['id']];
        }
        $html .= '<tr class="table-secondary font-weight-bold" style="background-color: #e2e3e5;">
            <td>' . strtoupper($jenis) . '</td>';
        foreach ($kategoriList as $k) {
            $html .= '<td class="text-right">Rp ' . number_format($subsum_kategori[$k['id']], 0, ',', '.') . '</td>';
        }
        $html .= '</tr>';
        foreach ($subs as $sub_id => $data) {
            $html .= '<tr>
                <td style="padding-left: 20px;">- ' . htmlspecialchars($data['nama']) . '</td>';
            foreach ($kategoriList as $k) {
                $html .= '<td class="text-right">Rp ' . number_format($data['kategori'][$k['id']], 0, ',', '.') . '</td>';
            }
            $html .= '</tr>';
        }
    }
}
$html .= '</tbody>';
if (!empty($groupedData)) {
    $html .= '<tfoot><tr style="background: #eee; font-weight: bold;"><td class="text-right">TOTAL KESELURUHAN</td>';
    foreach ($kategoriList as $k) {
        $html .= '<td class="text-right">Rp ' . number_format($gColTotals[$k['id']], 0, ',', '.') . '</td>';
    }
    $html .= '</tr></tfoot>';
}
$html .= '</table>';




if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . $filename . ".xls");
    echo $html; exit;
}

if ($format === 'pdf') {
    $options = new Options(); $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options); $dompdf->setPaper('A4', 'landscape');
    $pdfWrapper = '<html><head><title>Laporan Tahunan</title></head><body>
        <table style="width: 100%; border-bottom: 2px solid #0d9f4f; padding-bottom: 10px; margin-bottom: 20px;">
            <tr>
                <td style="width: 15%;"><img src="' . base_url('assets/img/logo_baznas.png') . '" width="100" /></td>
                <td style="width: 85%; text-align: center;">
                    <h1 style="color: #0d9f4f; margin: 0; font-size: 20px;">BADAN AMIL ZAKAT NASIONAL</h1>
                    <p style="margin: 0; font-size: 14px;">KABUPATEN SUMBAWA</p>
                </td>
            </tr>
        </table>' . $html . '</body></html>';
    $dompdf->loadHtml($pdfWrapper); $dompdf->render();
    $dompdf->stream($filename . '.pdf', ["Attachment" => false]); exit;
}
