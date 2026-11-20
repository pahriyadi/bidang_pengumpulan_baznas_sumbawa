<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    die("Error: CSRF Token tidak valid.");
}

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db();
ensure_skz_table($conn);

if (!function_exists('get_filter_str_arr')) {
    function get_filter_str_arr($name, $method = 'POST') {
        $src = ($method === 'POST') ? $_POST : $_GET;
        $val = $src[$name] ?? [];
        if (!is_array($val)) {
            $val = $val !== '' ? [$val] : [];
        }
        return array_values(array_filter(array_map('trim', $val), function($v) { return $v !== ''; }));
    }
}

$start_date = $_POST['start_date'] ?? date('Y-01-01');
$end_date = $_POST['end_date'] ?? date('Y-12-31');
$format = $_POST['export_format'] ?? 'excel';

$dinas_arr = get_filter_str_arr('dinas', 'POST');
$badan_usaha_arr = get_filter_str_arr('badan_usaha', 'POST');
$objek_arr = get_filter_str_arr('objek_zis', 'POST');
$via_arr = get_filter_str_arr('via_pembayaran', 'POST');
$petugas_arr = get_filter_str_arr('petugas', 'POST');
$keyword = trim($_POST['keyword'] ?? '');

$where = ["s.tanggal_surat >= ?", "s.tanggal_surat <= ?"];
$types = "ss";
$params = [$start_date, $end_date];

if (!empty($dinas_arr)) {
    $in_p = implode(',', array_fill(0, count($dinas_arr), '?'));
    $where[] = "s.dinas_instansi IN ($in_p)";
    $types .= str_repeat('s', count($dinas_arr));
    foreach ($dinas_arr as $v) $params[] = $v;
}
if (!empty($badan_usaha_arr)) {
    $in_p = implode(',', array_fill(0, count($badan_usaha_arr), '?'));
    $where[] = "s.nama_badan_usaha IN ($in_p)";
    $types .= str_repeat('s', count($badan_usaha_arr));
    foreach ($badan_usaha_arr as $v) $params[] = $v;
}
if (!empty($objek_arr)) {
    $in_p = implode(',', array_fill(0, count($objek_arr), '?'));
    $where[] = "UPPER(s.objek_zis) IN ($in_p)";
    $types .= str_repeat('s', count($objek_arr));
    foreach ($objek_arr as $v) $params[] = strtoupper($v);
}
if (!empty($via_arr)) {
    $in_p = implode(',', array_fill(0, count($via_arr), '?'));
    $where[] = "UPPER(s.via_pembayaran) IN ($in_p)";
    $types .= str_repeat('s', count($via_arr));
    foreach ($via_arr as $v) $params[] = strtoupper($v);
}
if (!empty($petugas_arr)) {
    $in_p = implode(',', array_fill(0, count($petugas_arr), '?'));
    $where[] = "s.nama_petugas IN ($in_p)";
    $types .= str_repeat('s', count($petugas_arr));
    foreach ($petugas_arr as $v) $params[] = $v;
}
if ($keyword !== '') {
    $where[] = "(s.nomor_surat LIKE ? OR s.paket_pekerjaan LIKE ? OR s.nama_penyetor LIKE ? OR s.nama_badan_usaha LIKE ? OR s.dinas_instansi LIKE ?)";
    $types .= "sssss";
    $kwWild = '%' . $keyword . '%';
    for ($k = 0; $k < 5; $k++) $params[] = $kwWild;
}

$where_sql = implode(" AND ", $where);

$sql = "
    SELECT s.*
    FROM surat_ketetapan_zakat s
    WHERE {$where_sql}
    ORDER BY s.tanggal_surat ASC, s.nomor_urut ASC, s.id ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$total_kontrak = 0.0;
$total_laba = 0.0;
$total_zis = 0.0;
$groupedDinas = [];
$groupedObjekVia = [];

while ($r = $res->fetch_assoc()) {
    $kontrak = (float)$r['nilai_kontrak'];
    $laba = (float)$r['estimasi_keuntungan'];
    $zis = (float)$r['nilai_zis'];

    $total_kontrak += $kontrak;
    $total_laba += $laba;
    $total_zis += $zis;

    $dinas = trim($r['dinas_instansi']) ?: 'Tanpa Dinas / Instansi';
    $bu = trim($r['nama_badan_usaha']) ?: 'Tanpa Nama Badan Usaha';
    $objek = strtoupper(trim($r['objek_zis']) ?: 'ZAKAT');
    $via = strtoupper(trim($r['via_pembayaran']) ?: 'TUNAI');

    if (!isset($groupedDinas[$dinas])) {
        $groupedDinas[$dinas] = [
            'jumlah_skz' => 0,
            'rekanan_set' => [],
            'total_kontrak' => 0.0,
            'total_laba' => 0.0,
            'total_zis' => 0.0
        ];
    }
    $groupedDinas[$dinas]['jumlah_skz']++;
    $groupedDinas[$dinas]['rekanan_set'][$bu] = true;
    $groupedDinas[$dinas]['total_kontrak'] += $kontrak;
    $groupedDinas[$dinas]['total_laba'] += $laba;
    $groupedDinas[$dinas]['total_zis'] += $zis;

    if (!isset($groupedObjekVia[$objek])) {
        $groupedObjekVia[$objek] = [
            'jumlah_skz' => 0,
            'total_zis' => 0.0,
            'vias' => []
        ];
    }
    $groupedObjekVia[$objek]['jumlah_skz']++;
    $groupedObjekVia[$objek]['total_zis'] += $zis;

    if (!isset($groupedObjekVia[$objek]['vias'][$via])) {
        $groupedObjekVia[$objek]['vias'][$via] = [
            'jumlah_skz' => 0,
            'total_zis' => 0.0
        ];
    }
    $groupedObjekVia[$objek]['vias'][$via]['jumlah_skz']++;
    $groupedObjekVia[$objek]['vias'][$via]['total_zis'] += $zis;

    $rows[] = $r;
}
$stmt->close();

uasort($groupedDinas, function($a, $b) { return $b['total_zis'] <=> $a['total_zis']; });

$periodeLabel = date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date));
$fileSuffix = $start_date . '_' . $end_date;

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_SKZ_BSZIS_' . $fileSuffix . '.csv');
    $out = fopen('php://output', 'w');

    fputcsv($out, ['No', 'Tanggal Surat', 'Nomor Surat', 'Nama Badan Usaha', 'Dinas / Instansi', 'Paket Pekerjaan', 'Nilai Kontrak (Rp)', '% Laba', 'Estimasi Keuntungan (Rp)', '% ZIS', 'Nilai ZIS (Rp)', 'Objek ZIS', 'Via Pembayaran', 'Nama Penyetor', 'Nama Petugas']);
    $no = 1;
    foreach ($rows as $r) {
        fputcsv($out, [
            $no++,
            date('d-m-Y', strtotime($r['tanggal_surat'])),
            $r['nomor_surat'],
            $r['nama_badan_usaha'],
            $r['dinas_instansi'],
            $r['paket_pekerjaan'],
            (float)$r['nilai_kontrak'],
            (float)$r['persentase_keuntungan'],
            (float)$r['estimasi_keuntungan'],
            (float)$r['persentase_zis'],
            (float)$r['nilai_zis'],
            $r['objek_zis'],
            $r['via_pembayaran'],
            $r['nama_penyetor'],
            $r['nama_petugas']
        ]);
    }
    fputcsv($out, ['', '', '', '', '', 'TOTAL KESELURUHAN', $total_kontrak, '', $total_laba, '', $total_zis, '', '', '', '']);
    fputcsv($out, []);

    fputcsv($out, ['REKAPITULASI SKZ & BSZIS PER DINAS / INSTANSI']);
    fputcsv($out, ['No', 'Dinas / Instansi', 'Jumlah SKZ', 'Jumlah Rekanan', 'Total Nilai Kontrak (Rp)', 'Total Estimasi Laba (Rp)', 'Total Nilai ZIS (Rp)']);
    $noD = 1;
    foreach ($groupedDinas as $dNama => $dData) {
        fputcsv($out, [$noD++, $dNama, $dData['jumlah_skz'], count($dData['rekanan_set']), $dData['total_kontrak'], $dData['total_laba'], $dData['total_zis']]);
    }
    fclose($out);
    exit;
}

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_SKZ_BSZIS_" . $fileSuffix . ".xls");

    echo '<table border="1">';
    echo '<tr><th colspan="12" style="font-size:15px;">LAPORAN SURAT KETETAPAN ZAKAT (SKZ) &amp; BUKTI SETORAN ZIS (BSZIS) - BAZNAS KABUPATEN SUMBAWA</th></tr>';
    echo '<tr><th colspan="12">Periode: ' . htmlspecialchars($periodeLabel) . '</th></tr>';
    echo '<tr>
            <th>No</th>
            <th>Tanggal Surat</th>
            <th>Nomor Surat</th>
            <th>Nama Badan Usaha</th>
            <th>Dinas / Instansi</th>
            <th>Paket Pekerjaan</th>
            <th>Nilai Kontrak (Rp)</th>
            <th>Estimasi Keuntungan (Rp)</th>
            <th>Nilai ZIS (Rp)</th>
            <th>Objek ZIS</th>
            <th>Via Pembayaran</th>
            <th>Nama Penyetor</th>
          </tr>';
    $no = 1;
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td align="center">' . $no++ . '</td>';
        echo '<td align="center">' . date('d-m-Y', strtotime($r['tanggal_surat'])) . '</td>';
        echo '<td>' . htmlspecialchars($r['nomor_surat']) . '</td>';
        echo '<td>' . htmlspecialchars($r['nama_badan_usaha']) . '</td>';
        echo '<td>' . htmlspecialchars($r['dinas_instansi']) . '</td>';
        echo '<td>' . htmlspecialchars($r['paket_pekerjaan']) . '</td>';
        echo '<td align="right">' . (float)$r['nilai_kontrak'] . '</td>';
        echo '<td align="right">' . (float)$r['estimasi_keuntungan'] . '</td>';
        echo '<td align="right">' . (float)$r['nilai_zis'] . '</td>';
        echo '<td align="center">' . htmlspecialchars($r['objek_zis']) . '</td>';
        echo '<td align="center">' . htmlspecialchars($r['via_pembayaran']) . '</td>';
        echo '<td>' . htmlspecialchars($r['nama_penyetor']) . '</td>';
        echo '</tr>';
    }
    echo '<tr style="font-weight:bold;">
            <th colspan="6" align="right">TOTAL KESELURUHAN</th>
            <th align="right">' . $total_kontrak . '</th>
            <th align="right">' . $total_laba . '</th>
            <th align="right">' . $total_zis . '</th>
            <th colspan="3"></th>
          </tr>';
    echo '</table><br><br>';

    echo '<table border="1">';
    echo '<tr><th colspan="6" style="font-size:14px;">REKAPITULASI SKZ &amp; BSZIS PER DINAS / INSTANSI</th></tr>';
    echo '<tr>
            <th>No</th>
            <th>Dinas / Instansi</th>
            <th>Jumlah SKZ</th>
            <th>Jumlah Rekanan</th>
            <th>Total Nilai Kontrak (Rp)</th>
            <th>Total Nilai ZIS (Rp)</th>
          </tr>';
    $noD = 1;
    foreach ($groupedDinas as $dNama => $dData) {
        echo '<tr>
                <td align="center">' . $noD++ . '</td>
                <td>' . htmlspecialchars($dNama) . '</td>
                <td align="center">' . $dData['jumlah_skz'] . '</td>
                <td align="center">' . count($dData['rekanan_set']) . '</td>
                <td align="right">' . $dData['total_kontrak'] . '</td>
                <td align="right">' . $dData['total_zis'] . '</td>
              </tr>';
    }
    echo '</table>';
    exit;
}

if ($format === 'pdf') {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $settings = get_all_settings();

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Laporan SKZ & BSZIS</title>
        <style>
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #1e293b; margin: 0; padding: 0; }
            h2, h3, p { margin: 0; padding: 0; text-align: center; }
            .header-tbl { width: 100%; border-bottom: 2px solid #0d9f4f; margin-bottom: 14px; padding-bottom: 10px; }
            .content-tbl { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 16px; }
            .content-tbl th, .content-tbl td { border: 0.5pt solid #cbd5e1; padding: 5px 4px; word-wrap: break-word; }
            .content-tbl th { background-color: #f1f5f9; color: #334155; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8px; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .section-title { text-align: left; font-size: 10.5px; font-weight: bold; color: #0d9f4f; margin-top: 10px; margin-bottom: 4px; }
        </style>
    </head>
    <body>
        <table class="header-tbl">
            <tr>
                <td width="100%">
                    <h2 style="color: #0d9f4f;"><?= htmlspecialchars(strtoupper($settings['nama_baznas'] ?? 'BADAN AMIL ZAKAT NASIONAL (BAZNAS) KABUPATEN SUMBAWA')) ?></h2>
                    <h3 style="margin-top: 3px;">Laporan Surat Ketetapan Zakat (SKZ) &amp; Bukti Setoran ZIS (BSZIS) Rekanan Proyek</h3>
                    <p style="margin-top: 4px;">Periode: <strong><?= htmlspecialchars($periodeLabel) ?></strong></p>
                </td>
            </tr>
        </table>

        <div class="section-title">A. Rincian Penerbitan SKZ &amp; BSZIS</div>
        <table class="content-tbl">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th width="7%">Tanggal</th>
                    <th width="14%">Nomor Surat</th>
                    <th width="16%">Badan Usaha</th>
                    <th width="13%">Dinas / Instansi</th>
                    <th>Paket Pekerjaan</th>
                    <th width="11%">Nilai Kontrak</th>
                    <th width="10%">Est. Laba</th>
                    <th width="10%">Nilai ZIS</th>
                    <th width="8%">Objek / Via</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center">Tidak ada data SKZ &amp; BSZIS pada periode ini.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal_surat'])) ?></td>
                        <td><strong><?= htmlspecialchars($r['nomor_surat']) ?></strong></td>
                        <td><strong><?= htmlspecialchars($r['nama_badan_usaha']) ?></strong></td>
                        <td><?= htmlspecialchars($r['dinas_instansi']) ?></td>
                        <td><?= htmlspecialchars($r['paket_pekerjaan']) ?></td>
                        <td class="text-right">Rp <?= number_format((float)$r['nilai_kontrak'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format((float)$r['estimasi_keuntungan'], 0, ',', '.') ?></td>
                        <td class="text-right"><strong>Rp <?= number_format((float)$r['nilai_zis'], 0, ',', '.') ?></strong></td>
                        <td class="text-center"><?= htmlspecialchars($r['objek_zis']) ?><br><small><?= htmlspecialchars($r['via_pembayaran']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-right">TOTAL KESELURUHAN</th>
                    <th class="text-right">Rp <?= number_format($total_kontrak, 0, ',', '.') ?></th>
                    <th class="text-right">Rp <?= number_format($total_laba, 0, ',', '.') ?></th>
                    <th class="text-right">Rp <?= number_format($total_zis, 0, ',', '.') ?></th>
                    <th></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <div class="section-title">B. Rekapitulasi per Dinas / Instansi</div>
        <table class="content-tbl">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Dinas / Instansi</th>
                    <th width="10%">Jumlah SKZ</th>
                    <th width="12%">Jumlah Rekanan</th>
                    <th width="20%">Total Nilai Kontrak</th>
                    <th width="18%">Total Estimasi Laba</th>
                    <th width="18%">Total Nilai ZIS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedDinas)): ?>
                    <tr><td colspan="7" class="text-center">Data kosong.</td></tr>
                <?php else: ?>
                    <?php $noD = 1; foreach ($groupedDinas as $dNama => $dData): ?>
                    <tr>
                        <td class="text-center"><?= $noD++ ?></td>
                        <td><strong><?= htmlspecialchars($dNama) ?></strong></td>
                        <td class="text-center"><?= number_format($dData['jumlah_skz'], 0, ',', '.') ?></td>
                        <td class="text-center"><?= number_format(count($dData['rekanan_set']), 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($dData['total_kontrak'], 0, ',', '.') ?></td>
                        <td class="text-right">Rp <?= number_format($dData['total_laba'], 0, ',', '.') ?></td>
                        <td class="text-right"><strong>Rp <?= number_format($dData['total_zis'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 25px;">
            <tr>
                <td width="60%"></td>
                <td width="40%" class="text-center">
                    <?= htmlspecialchars(get_setting('skz_kota', 'Sumbawa Besar')) ?>, <?= date('d') . ' ' . month_name((int)date('n')) . ' ' . date('Y') ?><br>
                    <strong><?= htmlspecialchars(get_setting('skz_jabatan_pimpinan', 'Ketua BAZNAS Kabupaten Sumbawa')) ?></strong><br><br><br><br><br>
                    <strong><u><?= htmlspecialchars(get_setting('skz_nama_pimpinan', 'Syukri Rahmat, S.Ag., M.M Inov')) ?></u></strong>
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
    $dompdf->stream('Laporan_SKZ_BSZIS_' . $fileSuffix . '.pdf', ['Attachment' => false]);
    exit;
}
