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

$kategoriList = get_kategori_dana_list();
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

$filename = "Laporan_Kat_Dana_" . date('Ymd_His');

// --- CSV FORMAT ---
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='. $filename . '.csv');
    $output = fopen('php://output', 'w');
    $headers = ['SUMBER DANA'];
    foreach ($kategoriList as $k) $headers[] = strtoupper($k['nama_kategori']);
    fputcsv($output, $headers);
    foreach ($groupedData as $jenis => $subs) {
        fputcsv($output, [strtoupper($jenis)]);
        foreach ($subs as $sub_id => $d) {
            $row = ['   ' . $d['nama']];
            foreach ($kategoriList as $k) $row[] = $d['kategori'][$k['id']];
            fputcsv($output, $row);
        }
    }
    $totals = ['TOTAL KESELURUHAN'];
    foreach ($kategoriList as $k) $totals[] = $gColTotals[$k['id']];
    fputcsv($output, $totals);
    fclose($output); exit;
}

// --- HTML Build for Excel & PDF ---
$html = '
<style>
    body { font-family: "Helvetica", Arial, sans-serif; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ccc; padding: 6px; }
    th { background-color: #047857; color: white; text-align: center; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
</style>
<div style="text-align: center; margin-bottom: 20px;">
    <h2 style="margin: 0; color: #047857;">Laporan Kategori Detail Dana</h2>
    <p style="margin: 5px 0;">Periode: ' . $period_text . '</p>
</div>
<table>
    <thead>
        <tr>
            <th>SUMBER DANA</th>';
foreach ($kategoriList as $k) $html .= '<th>' . strtoupper(htmlspecialchars($k['nama_kategori'])) . '</th>';
$html .= '</tr>
    </thead>
    <tbody>';

if (empty($groupedData)) {
    $html .= '<tr><td colspan="'.(count($kategoriList)+1).'" class="text-center">Data kosong.</td></tr>';
} else {
    foreach ($groupedData as $jenis => $subs) {
        $html .= '<tr style="background-color: #f2f2f2; font-weight: bold;"><td>' . strtoupper($jenis) . '</td>';
        foreach ($kategoriList as $k) {
            $subsum = 0; foreach ($subs as $s) $subsum += $s['kategori'][$k['id']];
            $html .= '<td class="text-right">Rp ' . number_format($subsum, 0, ',', '.') . '</td>';
        }
        $html .= '</tr>';
        foreach ($subs as $sub_id => $d) {
            $html .= '<tr><td style="padding-left: 20px;">' . htmlspecialchars($d['nama']) . '</td>';
            foreach ($kategoriList as $k) $html .= '<td class="text-right">Rp ' . number_format($d['kategori'][$k['id']], 0, ',', '.') . '</td>';
            $html .= '</tr>';
        }
    }
}
$html .= '</tbody><tfoot><tr style="background: #eee; font-weight: bold;"><td class="text-right">TOTAL KESELURUHAN</td>';
foreach ($kategoriList as $k) $html .= '<td class="text-right">Rp ' . number_format($gColTotals[$k['id']], 0, ',', '.') . '</td>';
$html .= '</tr></tfoot></table>';

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=" . $filename . ".xls");
    echo $html; exit;
}

if ($format === 'pdf') {
    $options = new Options(); $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options); $dompdf->setPaper('A4', 'landscape');
    $pdfWrapper = '<html><head><title>Laporan Kategori</title></head><body>
        <table style="width: 100%; border-bottom: 2px solid #047857; padding-bottom: 10px; margin-bottom: 20px;">
            <tr>
                <td style="width: 15%;"><img src="' . base_url('assets/img/logo_baznas.png') . '" width="100" /></td>
                <td style="width: 85%; text-align: center;">
                    <h1 style="color: #047857; margin: 0; font-size: 20px;">BADAN AMIL ZAKAT NASIONAL</h1>
                    <p style="margin: 0; font-size: 14px;">KABUPATEN SUMBAWA</p>
                </td>
            </tr>
        </table>' . $html . '</body></html>';
    $dompdf->loadHtml($pdfWrapper); $dompdf->render();
    $dompdf->stream($filename . '.pdf', ["Attachment" => false]); exit;
}
