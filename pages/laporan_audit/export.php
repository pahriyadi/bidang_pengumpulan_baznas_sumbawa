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

// Filter parameters
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

$id_upz_arr = get_filter_arr('id_upz', 'POST');
$id_kategori_upz_arr = get_filter_arr('id_kategori_upz', 'POST');
$id_kategori_arr = get_filter_arr('id_kategori', 'POST');
$id_metode_arr = get_filter_arr('id_metode', 'POST');
$id_sumber_arr = get_filter_arr('id_sumber', 'POST');

// Fetch all sub-jenis grouped by parent jenis
$allSubJenis = $conn->query("
    SELECT sjp.*, jp.nama_jenis 
    FROM sub_jenis_pembayaran sjp
    JOIN jenis_pembayaran jp ON sjp.id_jenis_pembayaran = jp.id
    ORDER BY jp.id ASC, sjp.nama_sub_jenis ASC
")->fetch_all(MYSQLI_ASSOC);

// Retrieve active mapping rules from POST request: rules[source_sub_id] = target_sub_id
$rules = [];
if (isset($_POST['rules']) && is_array($_POST['rules'])) {
    foreach ($_POST['rules'] as $src_id => $tgt_id) {
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
    ORDER BY t.tanggal_transaksi ASC, t.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$raw_data = $stmt->get_result();

$transactions = [];
$columnTotals = [6 => 0, 7 => 0];
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

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Audit_' . $start_date . '_' . $end_date . '.csv');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['LAPORAN TRANSAKSI AUDIT (PEMERIKSAAN KEUANGAN)']);
    fputcsv($output, ['Periode: ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date))]);
    fputcsv($output, []);
    
    fputcsv($output, ['No', 'Tanggal', 'Nama Muzaki / UPZ', 'Sub Jenis Pembayaran', 'Zakat (Rp)', 'Infak & Sedekah (Rp)', 'Jumlah (Rp)']);
    $no = 1;
    foreach ($transactions as $t) {
        $isZakat = $t['mapped_jenis_id'] == 6;
        fputcsv($output, [
            $no++,
            date('d-m-Y', strtotime($t['tanggal_transaksi'])),
            $t['nama_muzaki'],
            $t['nama_sub_jenis'],
            $isZakat ? $t['jumlah_pembayaran'] : 0,
            !isZakat ? $t['jumlah_pembayaran'] : 0,
            $t['jumlah_pembayaran']
        ]);
    }
    fputcsv($output, ['', '', 'TOTAL', '', $columnTotals[6], $columnTotals[7], $total_nominal]);
    fputcsv($output, []);
    fputcsv($output, []);
    
    fputcsv($output, ['REKAPITULASI SEKTORAL UPZ PER JENIS PEMBAYARAN (AUDIT)']);
    fputcsv($output, ['Kategori Sektoral UPZ', 'Zakat (Rp)', 'Infak & Sedekah (Rp)', 'Total (Rp)']);
    
    $grandTotalRecap = 0;
    $colTotals = [6 => 0, 7 => 0];
    foreach ($recapKategoriJenis as $katName => $data) {
        fputcsv($output, [
            $katName,
            $data[6],
            $data[7],
            $data['total']
        ]);
        $colTotals[6] += $data[6];
        $colTotals[7] += $data[7];
        $grandTotalRecap += $data['total'];
    }
    fputcsv($output, ['TOTAL KESELURUHAN', $colTotals[6], $colTotals[7], $grandTotalRecap]);
    
    fclose($output);
    exit;
}

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Audit_" . $start_date . "_" . $end_date . ".xls");
    
    echo '<table border="1">';
    echo '<tr><th colspan="7" style="font-size:16px;">Laporan Transaksi Audit Pemeriksaan Keuangan</th></tr>';
    echo '<tr><th colspan="7">Periode: ' . date('d-m-Y', strtotime($start_date)) . ' s/d ' . date('d-m-Y', strtotime($end_date)) . '</th></tr>';
    echo '<tr>';
    echo '<th>No</th><th>Tanggal</th><th>Nama Muzaki/UPZ</th><th>Sub Jenis Pembayaran</th><th>Zakat (Rp)</th><th>Infak & Sedekah (Rp)</th><th>Jumlah (Rp)</th>';
    echo '</tr>';
    
    $no = 1;
    foreach ($transactions as $t) {
        $isZakat = $t['mapped_jenis_id'] == 6;
        echo '<tr>';
        echo '<td>'.$no++.'</td>';
        echo '<td>'.date('d-m-Y', strtotime($t['tanggal_transaksi'])).'</td>';
        echo '<td>'.htmlspecialchars($t['nama_muzaki']).'</td>';
        echo '<td>'.htmlspecialchars($t['nama_sub_jenis']).'</td>';
        echo '<td align="right">'.($isZakat ? $t['jumlah_pembayaran'] : 0).'</td>';
        echo '<td align="right">'.(!isZakat ? $t['jumlah_pembayaran'] : 0).'</td>';
        echo '<td align="right">'.$t['jumlah_pembayaran'].'</td>';
        echo '</tr>';
    }
    
    echo '<tr><th colspan="4" align="right">TOTAL</th>';
    echo '<th align="right">'.$columnTotals[6].'</th>';
    echo '<th align="right">'.$columnTotals[7].'</th>';
    echo '<th align="right">'.$total_nominal.'</th></tr>';
    echo '</table>';
    
    echo '<br><br>';
    echo '<table border="1">';
    echo '<tr><th colspan="4" style="font-size:14px; background-color:#d4edda;">Rekapitulasi Sektoral UPZ per Jenis Pembayaran (Audit)</th></tr>';
    echo '<tr style="background-color:#f8f9fa;"><th>Kategori Sektoral UPZ</th><th>Zakat (Rp)</th><th>Infak & Sedekah (Rp)</th><th>Total (Rp)</th></tr>';
    
    $grandTotalRecap = 0;
    $colTotals = [6 => 0, 7 => 0];
    foreach ($recapKategoriJenis as $katName => $data) {
        echo '<tr>';
        echo '<td><strong>'.htmlspecialchars($katName).'</strong></td>';
        echo '<td align="right">'.$data[6].'</td>';
        echo '<td align="right">'.$data[7].'</td>';
        echo '<td align="right">'.$data['total'].'</td>';
        echo '</tr>';
        $colTotals[6] += $data[6];
        $colTotals[7] += $data[7];
        $grandTotalRecap += $data['total'];
    }
    echo '<tr style="background-color:#eee; font-weight:bold;"><td>TOTAL KESELURUHAN</td><td align="right">'.$colTotals[6].'</td><td align="right">'.$colTotals[7].'</td><td align="right">'.$grandTotalRecap.'</td></tr>';
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
        <title>Laporan Audit Pemeriksaan Keuangan</title>
        <style>
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #1e293b; margin: 0; padding: 0; }
            h2, h3, h4 { margin: 0; padding: 0; text-align: center; }
            .header-tbl { width: 100%; border-bottom: 2px solid #0d9f4f; margin-bottom: 20px; padding-bottom: 10px; }
            .header-text { text-align: center; }
            .content-tbl { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
            .content-tbl th, .content-tbl td { border: 0.5pt solid #cbd5e1; padding: 6px 4px; word-wrap: break-word; }
            .content-tbl th { background-color: #f1f5f9; color: #334155; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8.5px; }
            .content-tbl tr:nth-child(even) { background-color: #f8fafc; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .section-title { font-size: 11px; font-weight: bold; color: #0d9f4f; margin-top: 25px; margin-bottom: 5px; border-left: 3px solid #0d9f4f; padding-left: 5px; }
        </style>
    </head>
    <body>
        <table class="header-tbl">
            <tr>
                <td width="100%" class="header-text">
                    <h2 style="color: #0d9f4f;">BADAN AMIL ZAKAT NASIONAL (BAZNAS)</h2>
                    <h3>Laporan Audit Pemeriksaan Keuangan</h3>
                    <p style="margin: 3px 0 0 0;">Periode: <strong><?= date('d/m/Y', strtotime($start_date)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($end_date)) ?></strong></p>
                </td>
            </tr>
        </table>
        
        <div class="section-title">1. Rincian Transaksi Ter-Audit</div>
        <table class="content-tbl">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="10%">Tanggal</th>
                    <th width="33%">Nama Muzaki / UPZ</th>
                    <th width="23%">Sub Jenis</th>
                    <th width="15%" class="text-right">Zakat (Rp)</th>
                    <th width="15%" class="text-right">Infak (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) == 0): ?>
                <tr><td colspan="6" class="text-center">Data kosong.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($transactions as $t): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($t['tanggal_transaksi'])) ?></td>
                        <td><strong><?= htmlspecialchars($t['nama_muzaki'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($t['nama_sub_jenis'] ?? '-') ?></td>
                        <td class="text-right"><?= $t['mapped_jenis_id'] == 6 ? 'Rp ' . number_format($t['jumlah_pembayaran'], 0, ',', '.') : '-' ?></td>
                        <td class="text-right"><?= $t['mapped_jenis_id'] == 7 ? 'Rp ' . number_format($t['jumlah_pembayaran'], 0, ',', '.') : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (count($transactions) > 0): ?>
            <tfoot>
                <tr style="background-color: #eee; font-weight: bold;">
                    <td colspan="4" class="text-center">TOTAL KESELURUHAN</td>
                    <td class="text-right">Rp <?= number_format($columnTotals[6], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($columnTotals[7], 0, ',', '.') ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <div class="section-title">2. Rekapitulasi Sektoral UPZ (Format Audit)</div>
        <table class="content-tbl" style="width: 70%; margin-left: 0;">
            <thead>
                <tr>
                    <th>Kategori Sektoral UPZ</th>
                    <th width="25%" class="text-right">Zakat (Rp)</th>
                    <th width="25%" class="text-right">Infak (Rp)</th>
                    <th width="25%" class="text-right">Total (Rp)</th>
                </tr>
            </thead>
            <tbody>
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
                    <td class="text-right" style="font-weight: bold; background-color: #f8fafc;">Rp <?= number_format($data['total'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot style="background-color: #eee; font-weight: bold;">
                <tr>
                    <td class="text-center">TOTAL KESELURUHAN</td>
                    <td class="text-right">Rp <?= number_format($colTotals[6], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($colTotals[7], 0, ',', '.') ?></td>
                    <td class="text-right">Rp <?= number_format($grandTotalRecap, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>

        <table style="width: 100%; margin-top: 40px;">
            <tr>
                <td width="60%"></td>
                <td width="40%" style="text-align: center;">
                    Sumbawa, <?= date('d') . ' ' . month_name(date('n')) . ' ' . date('Y') ?><br>
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
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('Laporan_Audit_BAZNAS_' . $start_date . '_' . $end_date . '.pdf', array("Attachment" => false));
    exit;
}
?>
