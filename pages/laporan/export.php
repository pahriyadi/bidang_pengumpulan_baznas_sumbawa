<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    die("Error: CSRF Token tidak valid.");
}

// Include Dompdf
require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db();

// Filter parameters (passed from index.php form)
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-t');
$format = $_POST['export_format'] ?? 'csv';

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
$id_kategori_upz_arr = get_filter_arr('id_kategori_upz', 'POST');

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

// Queries for Kategori Dana Mapping
$kategoriList = get_kategori_dana_list();
$kategoriMap = [];
foreach ($kategoriList as $k) {
    $kategoriMap[$k['id']] = $k['nama_kategori'];
}

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
    ORDER BY t.tanggal_transaksi ASC, t.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();

$jenisList = get_jenis_pembayaran_list();
$columnTotals = [];
foreach ($jenisList as $j) {
    $columnTotals[$j['id']] = 0;
}

$transactions = [];
$total_nominal = 0;
while ($row = $data->fetch_assoc()) {
    $total_nominal += (float)$row['jumlah_pembayaran'];
    if (isset($columnTotals[$row['id_jenis_pembayaran']])) {
        $columnTotals[$row['id_jenis_pembayaran']] += (float)$row['jumlah_pembayaran'];
    }
    // Parse categories
    $katIds = parse_kategori_dana_ids($row['id_kategori_dana']);
    $katNames = [];
    foreach ($katIds as $kid) {
        if (isset($kategoriMap[$kid])) {
            $katNames[] = $kategoriMap[$kid];
        }
    }
    $row['kategori_dana_str'] = implode(' | ', $katNames);
    $transactions[] = $row;
}
$stmt->close();

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_ZIS_' . $start_date . '_' . $end_date . '.csv');
    $output = fopen('php://output', 'w');
    $csvHeaders = ['No', 'Tanggal', 'Nama Muzaki / UPZ'];
    foreach ($jenisList as $j) {
        $csvHeaders[] = $j['nama_jenis'];
    }
    $csvHeaders[] = 'Jumlah (Rp)';
    fputcsv($output, $csvHeaders);
    
    $no = 1;
    foreach ($transactions as $t) {
        $row = [
            $no++,
            date('d-m-Y', strtotime($t['tanggal_transaksi'])),
            $t['nama_muzaki']
        ];
        
        foreach ($jenisList as $j) {
            $row[] = ($t['id_jenis_pembayaran'] == $j['id']) ? $t['jumlah_pembayaran'] : '-';
        }
        $row[] = $t['jumlah_pembayaran'];
        fputcsv($output, $row);
    }
    // Total Row
    $totalRow = ['', '', 'TOTAL'];
    foreach ($jenisList as $j) {
        $totalRow[] = $columnTotals[$j['id']];
    }
    $totalRow[] = $total_nominal;
    fputcsv($output, $totalRow);
    fclose($output);
    exit;
}

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_ZIS_" . $start_date . "_" . $end_date . ".xls");
    
    $colSpan = 4 + count($jenisList);
    echo '<table border="1">';
    echo '<tr><th colspan="'.$colSpan.'" style="font-size:16px;">Laporan Transaksi Pengumpulan ZIS BAZNAS</th></tr>';
    echo '<tr><th colspan="'.$colSpan.'">Periode: ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)) . '</th></tr>';
    echo '<tr>';
    echo '<th>No</th><th>Tanggal</th><th>Nama Muzaki/UPZ</th>';
    foreach ($jenisList as $j) {
        echo '<th>'.htmlspecialchars($j['nama_jenis']).'</th>';
    }
    echo '<th>Jumlah (Rp)</th>';
    echo '</tr>';
    
    $no = 1;
    foreach ($transactions as $t) {
        $muzaki_upz = htmlspecialchars($t['nama_muzaki'] . ($t['nama_upz'] ? ' ('.$t['nama_upz'].')' : ''));
        echo '<tr>';
        echo '<td>'.$no++.'</td>';
        echo '<td>'.date('d-m-Y', strtotime($t['tanggal_transaksi'])).'</td>';
        echo '<td>'.$muzaki_upz.'</td>';
        foreach ($jenisList as $j) {
            $val = ($t['id_jenis_pembayaran'] == $j['id']) ? $t['jumlah_pembayaran'] : '-';
            echo '<td align="right">'.$val.'</td>';
        }
        echo '<td align="right">'.$t['jumlah_pembayaran'].'</td>';
        echo '</tr>';
    }
    
    echo '<tr><th colspan="3" align="right">TOTAL</th>';
    foreach ($jenisList as $j) {
        echo '<th align="right"><strong>'.$columnTotals[$j['id']].'</strong></th>';
    }
    echo '<th align="right"><strong>'.$total_nominal.'</strong></th></tr>';
    echo '</table>';
    exit;
}

if ($format === 'pdf') {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan Transaksi ZIS</title>
        <style>
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 0; }
            h2, h3, h4 { margin: 0; padding: 0; text-align: center; }
            .header-tbl { width: 100%; border-bottom: 2px solid #0d9f4f; margin-bottom: 25px; padding-bottom: 15px; }
            .header-text { text-align: center; }
            .content-tbl { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
            .content-tbl th, .content-tbl td { border: 0.5pt solid #e2e8f0; padding: 8px 5px; word-wrap: break-word; }
            .content-tbl th { background-color: #f8fafc; color: #475569; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9px; }
            .content-tbl tr:nth-child(even) { background-color: #f1f5f9; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .footer-tbl { width: 100%; margin-top: 40px; }
            .signature-box { text-align: center; width: 40%; }
            .currency { font-family: 'Courier', monospace; font-weight: bold; }
        </style>
    </head>
    <body>
        <table class="header-tbl">
            <tr>
                <!-- Asumsi logo ada di asset -->
                <td width="15%" style="text-align: right;"></td>
                <td width="70%" class="header-text">
                    <h2 style="color: #16a34a;">BADAN AMIL ZAKAT NASIONAL (BAZNAS)</h2>
                    <h3>Laporan Transaksi Pengumpulan ZIS</h3>
                    <p style="margin-top: 5px;">Periode: <strong><?= date('d/m/Y', strtotime($start_date)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($end_date)) ?></strong></p>
                </td>
                <td width="15%"></td>
            </tr>
        </table>
        
        <table class="content-tbl">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th>Tanggal</th>
                    <th>Nama Muzaki / UPZ</th>
                    <?php foreach ($jenisList as $j): ?>
                        <th class="text-right"><?= htmlspecialchars($j['nama_jenis']) ?></th>
                    <?php endforeach; ?>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) == 0): ?>
                <tr><td colspan="<?= 4 + count($jenisList) ?>" class="text-center">Data kosong.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($transactions as $t): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($t['tanggal_transaksi'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($t['nama_muzaki'] ?? '-') ?></strong>
                        </td>
                        <?php foreach ($jenisList as $j): ?>
                            <td class="text-right">
                                <?php if ($t['id_jenis_pembayaran'] == $j['id']): ?>
                                    Rp <?= number_format($t['jumlah_pembayaran'], 0, ',', '.') ?>,-
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-right">Rp <?= number_format($t['jumlah_pembayaran'], 0, ',', '.') ?>,-</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (count($transactions) > 0): ?>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">TOTAL KESELURUHAN</th>
                    <?php foreach ($jenisList as $j): ?>
                        <th class="text-right">Rp <?= number_format($columnTotals[$j['id']], 0, ',', '.') ?>,-</th>
                    <?php endforeach; ?>
                    <th class="text-right">Rp <?= number_format($total_nominal, 0, ',', '.') ?>,-</th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <table style="width: 100%; margin-top: 40px;">
            <tr>
                <td width="60%"></td>
                <td width="40%" style="text-align: center;">
                    <?= clean($settings['kota_baznas'] ?? 'Sumbawa') ?>, <?= date('d') . ' ' . month_name(date('n')) . ' ' . date('Y') ?><br>
                    <strong>Bagian Pengumpulan ZIS</strong><br><br><br><br><br>
                    (_______________________)
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('Laporan_ZIS_BAZNAS_' . $start_date . '_' . $end_date . '.pdf', array("Attachment" => false));
    exit;
}
