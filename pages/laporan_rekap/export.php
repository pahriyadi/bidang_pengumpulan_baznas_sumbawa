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

// Metadata for Title/Header
$period_text = date('d/m/Y', strtotime($start_date)) . " s/d " . date('d/m/Y', strtotime($end_date));

// Fetch Data for Aggregation
$jenisList = get_jenis_pembayaran_list();
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
$result = $stmt->get_result();

$monthlyData = [];
while ($row = $result->fetch_assoc()) {
    $b = $row['bulan'];
    if (!isset($monthlyData[$b])) {
        $monthlyData[$b] = ['total_all' => 0, 'jenis' => []];
        foreach ($jenisList as $j) $monthlyData[$b]['jenis'][$j['id']] = 0;
    }
    $monthlyData[$b]['jenis'][$row['id_jenis']] += (float)$row['total'];
    $monthlyData[$b]['total_all'] += (float)$row['total'];
}

$monthNames = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];

// --- CSV FORMAT ---
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Rekap_Bulanan_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    $headers = ['Bulan'];
    foreach ($jenisList as $j) $headers[] = $j['nama_jenis'];
    $headers[] = 'Jumlah Total';
    fputcsv($output, $headers);
    
    $gColTotals = []; $gTotalAll = 0;
    foreach ($jenisList as $j) $gColTotals[$j['id']] = 0;
    foreach ($monthlyData as $bulan => $data) {
        $parts = explode('-', $bulan); $row = [$monthNames[$parts[1]] . ' ' . $parts[0]];
        foreach ($jenisList as $j) { $row[] = $data['jenis'][$j['id']]; $gColTotals[$j['id']] += $data['jenis'][$j['id']]; }
        $row[] = $data['total_all']; $gTotalAll += $data['total_all'];
        fputcsv($output, $row);
    }
    $totals = ['TOTAL KESELURUHAN'];
    foreach ($jenisList as $j) $totals[] = $gColTotals[$j['id']];
    $totals[] = $gTotalAll;
    fputcsv($output, $totals);
    fclose($output); exit;
}

// --- HTML for Excel & PDF ---
$html = '
<style>
    body { font-family: "Helvetica", Arial, sans-serif; font-size: 13px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    th { background-color: #047857; color: white; text-align: center; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .bold { font-weight: bold; }
</style>
<h2 style="text-align: center; margin-bottom: 5px; color: #047857;">Rekapitulasi Transaksi ZIS Per Bulan</h2>
<p style="text-align: center; margin-top: 0; margin-bottom: 20px;">Periode: ' . $period_text . '</p>
<table>
    <thead>
        <tr>
            <th>Bulan</th>';
foreach ($jenisList as $j) $html .= '<th>' . htmlspecialchars($j['nama_jenis']) . '</th>';
$html .= '  <th>Jumlah Total</th></tr>
    </thead>
    <tbody>';

$gColTotals = []; $gTotalAll = 0;
foreach ($jenisList as $j) $gColTotals[$j['id']] = 0;

if (empty($monthlyData)) {
    $colSpan = count($jenisList) + 2; $html .= '<tr><td colspan="'.$colSpan.'" class="text-center">Data kosong.</td></tr>';
} else {
    foreach ($monthlyData as $bulan => $data) {
        $parts = explode('-', $bulan); $html .= '<tr><td>' . $monthNames[$parts[1]] . ' ' . $parts[0] . '</td>';
        foreach ($jenisList as $j) { $html .= '<td class="text-right">' . number_format($data['jenis'][$j['id']], 0, ',', '.') . '</td>'; $gColTotals[$j['id']] += $data['jenis'][$j['id']]; }
        $html .= '<td class="text-right bold">' . number_format($data['total_all'], 0, ',', '.') . '</td>'; $gTotalAll += $data['total_all'];
        $html .= '</tr>';
    }
}
$html .= '</tbody><tfoot><tr style="background: #eee; font-weight: bold;"><td class="text-center">TOTAL</td>';
foreach ($jenisList as $j) $html .= '<td class="text-right">' . number_format($gColTotals[$j['id']], 0, ',', '.') . '</td>';
$html .= '<td class="text-right" style="background: #ffc107;">' . number_format($gTotalAll, 0, ',', '.') . '</td></tr></tfoot></table>';

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Rekap_Bulanan_" . date('Ymd_His') . ".xls");
    echo $html; exit;
}

if ($format === 'pdf') {
    $options = new Options(); $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options); $dompdf->setPaper('A4', 'landscape');
    $pdfWrapper = '<html><head><title>Rekap Bulanan</title></head><body>
        <table style="border: none; width: 100%; border-bottom: 2px solid #047857; padding-bottom: 10px; margin-bottom: 20px;">
            <tr>
                <td style="border: none; width: 15%;"><img src="' . base_url('assets/img/logo_baznas.png') . '" width="100" /></td>
                <td style="border: none; width: 85%; text-align: center;">
                    <h1 style="color: #047857; margin:0; font-size: 22px;">BADAN AMIL ZAKAT NASIONAL</h1>
                    <p style="margin: 0; font-size: 14px;">KABUPATEN SUMBAWA</p>
                </td>
            </tr>
        </table>' . $html . '</body></html>';
    $dompdf->loadHtml($pdfWrapper); $dompdf->render();
    $dompdf->stream('Rekap_Bulanan_' . date('His') . '.pdf', ["Attachment" => false]); exit;
}
