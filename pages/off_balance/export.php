<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_auth();

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    die("Error: CSRF Token tidak valid.");
}

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db();
ensure_kinerja_tables($conn);

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

$format = $_POST['export_format'] ?? 'excel';
$export_mode = $_POST['export_mode'] ?? 'full'; // 'full' or 'sumber_dana'

// Support both date range (start_date/end_date from laporan.php) and tahun/bulan (from index.php)
if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
} else {
    $tahun = (int)($_POST['tahun'] ?? date('Y'));
    $bulan = (int)($_POST['bulan'] ?? 0);
    if ($bulan >= 1 && $bulan <= 12) {
        $start_date = sprintf('%04d-%02d-01', $tahun, $bulan);
        $end_date = date('Y-m-t', strtotime($start_date));
    } else {
        $start_date = sprintf('%04d-01-01', $tahun);
        $end_date = sprintf('%04d-12-31', $tahun);
    }
}

$id_jenis_arr = get_filter_arr('id_jenis', 'POST');
$id_sub_jenis_arr = get_filter_arr('id_sub_jenis', 'POST');
$id_upz_arr = get_filter_arr('id_upz', 'POST');
$id_kategori_upz_arr = get_filter_arr('id_kategori_upz', 'POST');

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
        if ($v === -1) $has_fallback = true;
        else $normal_ids[] = (int)$v;
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
$keyword = trim($_POST['keyword'] ?? '');
if ($keyword !== '') {
    $where[] = "(ob.no_berita_acara LIKE ? OR ob.nama_pelapor LIKE ? OR u.nama_upz LIKE ? OR ob.keterangan LIKE ?)";
    $types .= "ssss";
    $kwWild = '%' . $keyword . '%';
    for ($k = 0; $k < 4; $k++) $params[] = $kwWild;
}

$where_sql = implode(" AND ", $where);
$jenisList = get_jenis_pembayaran_list();

$sql = "
    SELECT ob.*,
        u.nama_upz,
        COALESCE(ku.nama_kategori, 'Masyarakat / Lembaga Non-UPZ') AS kategori_upz_nama,
        COALESCE(NULLIF(u.nama_upz, ''), NULLIF(ob.nama_pelapor, ''), 'Masyarakat / Umum') AS pelapor_display,
        jp.nama_jenis,
        sjp.nama_sub_jenis
    FROM transaksi_off_balance ob
    LEFT JOIN upz u ON ob.id_upz = u.id
    LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
    LEFT JOIN jenis_pembayaran jp ON ob.id_jenis_pembayaran = jp.id
    LEFT JOIN sub_jenis_pembayaran sjp ON ob.id_sub_jenis_pembayaran = sjp.id
    WHERE {$where_sql}
    ORDER BY ob.tanggal_transaksi ASC, ob.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
$columnTotals = [];
foreach ($jenisList as $j) {
    $columnTotals[$j['id']] = 0.0;
}
$total_nominal = 0.0;
$total_muzaki = 0;
$total_mustahik = 0;
$groupedSumberDana = [];

while ($r = $res->fetch_assoc()) {
    $amt = (float)$r['nominal'];
    $muz = (int)$r['jumlah_muzaki'];
    $mus = (int)$r['jumlah_mustahik'];
    $jid = (int)$r['id_jenis_pembayaran'];
    $sid = (int)($r['id_sub_jenis_pembayaran'] ?? 0);
    $jenis_nama = $r['nama_jenis'] ?? 'LAINNYA';
    $sub_nama = $r['nama_sub_jenis'] ?: 'Umum / Tanpa Sub-Jenis';

    $total_nominal += $amt;
    $total_muzaki += $muz;
    $total_mustahik += $mus;
    if (isset($columnTotals[$jid])) {
        $columnTotals[$jid] += $amt;
    }

    if (!isset($groupedSumberDana[$jenis_nama])) {
        $groupedSumberDana[$jenis_nama] = [
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

    $rows[] = $r;
}
$stmt->close();

$periodeLabel = date('d/m/Y', strtotime($start_date)) . ' s/d ' . date('d/m/Y', strtotime($end_date));
$fileSuffix = $start_date . '_' . $end_date;

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Off_Balance_' . ($export_mode === 'sumber_dana' ? 'Sumber_Dana_' : '') . $fileSuffix . '.csv');
    $out = fopen('php://output', 'w');

    if ($export_mode !== 'sumber_dana') {
        $csvHeaders = ['No', 'Tanggal', 'UPZ / Lembaga Pelapor', 'No. Berita Acara', 'Muzaki (Jiwa)', 'Mustahik (Jiwa)'];
        foreach ($jenisList as $j) {
            $csvHeaders[] = $j['nama_jenis'];
        }
        $csvHeaders[] = 'Jumlah (Rp)';
        $csvHeaders[] = 'Keterangan';
        fputcsv($out, $csvHeaders);

        $no = 1;
        foreach ($rows as $r) {
            $line = [
                $no++,
                date('d-m-Y', strtotime($r['tanggal_transaksi'])),
                $r['pelapor_display'],
                $r['no_berita_acara'] ?: '-',
                (int)$r['jumlah_muzaki'],
                (int)$r['jumlah_mustahik']
            ];
            foreach ($jenisList as $j) {
                $line[] = ($r['id_jenis_pembayaran'] == $j['id']) ? (float)$r['nominal'] : '-';
            }
            $line[] = (float)$r['nominal'];
            $line[] = $r['keterangan'] ?: '-';
            fputcsv($out, $line);
        }
        $totLine = ['', '', '', 'TOTAL', $total_muzaki, $total_mustahik];
        foreach ($jenisList as $j) {
            $totLine[] = $columnTotals[$j['id']];
        }
        $totLine[] = $total_nominal;
        $totLine[] = '';
        fputcsv($out, $totLine);
        fputcsv($out, []);
    }

    // Rekapitulasi per Sumber Jenis Dana & Sub-Jenis Dana
    fputcsv($out, ['REKAPITULASI OFF BALANCE SHEET PER SUMBER JENIS DANA & SUB-JENIS DANA']);
    fputcsv($out, ['Sumber Dana (Jenis & Sub-Jenis)', 'Jumlah Laporan', 'Muzaki (Jiwa)', 'Mustahik (Jiwa)', 'Realisasi Off Balance (Rp)', '% Proporsi']);
    foreach ($groupedSumberDana as $jenisNama => $jData) {
        $pctJ = $total_nominal > 0 ? round(($jData['total_nominal'] / $total_nominal) * 100, 1) : 0;
        fputcsv($out, [strtoupper($jenisNama), $jData['total_laporan'], $jData['total_muzaki'], $jData['total_mustahik'], $jData['total_nominal'], $pctJ . '%']);
        foreach ($jData['subs'] as $sData) {
            $pctS = $total_nominal > 0 ? round(($sData['nominal'] / $total_nominal) * 100, 1) : 0;
            fputcsv($out, ['   - ' . $sData['nama_sub'], $sData['jumlah_laporan'], $sData['jumlah_muzaki'], $sData['jumlah_mustahik'], $sData['nominal'], $pctS . '%']);
        }
    }
    fputcsv($out, ['TOTAL KESELURUHAN', count($rows), $total_muzaki, $total_mustahik, $total_nominal, '100%']);
    fclose($out);
    exit;
}

if ($format === 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Laporan_Off_Balance_" . ($export_mode === 'sumber_dana' ? 'Sumber_Dana_' : '') . $fileSuffix . ".xls");

    $colSpan = 7 + count($jenisList);
    if ($export_mode !== 'sumber_dana') {
        echo '<table border="1">';
        echo '<tr><th colspan="' . $colSpan . '" style="font-size:15px;">LAPORAN PENCATATAN ZIS OFF BALANCE SHEET - BAZNAS KABUPATEN SUMBAWA</th></tr>';
        echo '<tr><th colspan="' . $colSpan . '">Periode: ' . htmlspecialchars($periodeLabel) . '</th></tr>';
        echo '<tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>UPZ / Lembaga Pelapor</th>
                <th>No. Berita Acara</th>
                <th>Muzaki (Jiwa)</th>
                <th>Mustahik (Jiwa)</th>';
        foreach ($jenisList as $j) {
            echo '<th>' . htmlspecialchars($j['nama_jenis']) . '</th>';
        }
        echo '<th>Jumlah (Rp)</th></tr>';

        $no = 1;
        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td align="center">' . $no++ . '</td>';
            echo '<td align="center">' . date('d-m-Y', strtotime($r['tanggal_transaksi'])) . '</td>';
            echo '<td>' . htmlspecialchars($r['pelapor_display']) . '</td>';
            echo '<td>' . htmlspecialchars($r['no_berita_acara'] ?: '-') . '</td>';
            echo '<td align="center">' . (int)$r['jumlah_muzaki'] . '</td>';
            echo '<td align="center">' . (int)$r['jumlah_mustahik'] . '</td>';
            foreach ($jenisList as $j) {
                $val = ($r['id_jenis_pembayaran'] == $j['id']) ? (float)$r['nominal'] : '-';
                echo '<td align="right">' . $val . '</td>';
            }
            echo '<td align="right">' . (float)$r['nominal'] . '</td>';
            echo '</tr>';
        }
        echo '<tr>
                <th colspan="4" align="right">TOTAL KESELURUHAN</th>
                <th align="center">' . $total_muzaki . '</th>
                <th align="center">' . $total_mustahik . '</th>';
        foreach ($jenisList as $j) {
            echo '<th align="right">' . $columnTotals[$j['id']] . '</th>';
        }
        echo '<th align="right">' . $total_nominal . '</th></tr>';
        echo '</table><br><br>';
    }

    // Tabel Rekapitulasi per Sumber Jenis Dana & Sub-Jenis Dana
    echo '<table border="1">';
    echo '<tr><th colspan="6" style="font-size:14px;">REKAPITULASI OFF BALANCE SHEET PER SUMBER JENIS DANA &amp; SUB-JENIS DANA</th></tr>';
    echo '<tr><th colspan="6">Periode: ' . htmlspecialchars($periodeLabel) . '</th></tr>';
    echo '<tr>
            <th>Sumber Dana (Jenis &amp; Sub-Jenis Pembayaran)</th>
            <th>Jumlah Laporan</th>
            <th>Muzaki (Jiwa)</th>
            <th>Mustahik (Jiwa)</th>
            <th>Realisasi Off Balance (Rp)</th>
            <th>% Proporsi</th>
          </tr>';
    foreach ($groupedSumberDana as $jenisNama => $jData) {
        $pctJ = $total_nominal > 0 ? round(($jData['total_nominal'] / $total_nominal) * 100, 1) : 0;
        echo '<tr style="background-color:#e2e8f0; font-weight:bold;">
                <td>' . strtoupper(htmlspecialchars($jenisNama)) . '</td>
                <td align="center">' . $jData['total_laporan'] . '</td>
                <td align="center">' . $jData['total_muzaki'] . '</td>
                <td align="center">' . $jData['total_mustahik'] . '</td>
                <td align="right">' . $jData['total_nominal'] . '</td>
                <td align="center">' . $pctJ . '%</td>
              </tr>';
        foreach ($jData['subs'] as $sData) {
            $pctS = $total_nominal > 0 ? round(($sData['nominal'] / $total_nominal) * 100, 1) : 0;
            echo '<tr>
                    <td style="padding-left:20px;">&mdash; ' . htmlspecialchars($sData['nama_sub']) . '</td>
                    <td align="center">' . $sData['jumlah_laporan'] . '</td>
                    <td align="center">' . $sData['jumlah_muzaki'] . '</td>
                    <td align="center">' . $sData['jumlah_mustahik'] . '</td>
                    <td align="right">' . $sData['nominal'] . '</td>
                    <td align="center">' . $pctS . '%</td>
                  </tr>';
        }
    }
    echo '<tr style="font-weight:bold;">
            <th align="right">TOTAL KESELURUHAN</th>
            <th align="center">' . count($rows) . '</th>
            <th align="center">' . $total_muzaki . '</th>
            <th align="center">' . $total_mustahik . '</th>
            <th align="right">' . $total_nominal . '</th>
            <th align="center">100%</th>
          </tr>';
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
        <title>Laporan Off Balance Sheet</title>
        <style>
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9.5px; color: #1e293b; margin: 0; padding: 0; }
            h2, h3, h4, p { margin: 0; padding: 0; text-align: center; }
            .header-tbl { width: 100%; border-bottom: 2px solid #0d9f4f; margin-bottom: 15px; padding-bottom: 10px; }
            .content-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; margin-bottom: 18px; }
            .content-tbl th, .content-tbl td { border: 0.5pt solid #cbd5e1; padding: 5px 5px; word-wrap: break-word; }
            .content-tbl th { background-color: #f1f5f9; color: #334155; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 8.5px; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .section-title { text-align: left; font-size: 11px; font-weight: bold; color: #0d9f4f; margin-top: 12px; margin-bottom: 4px; }
        </style>
    </head>
    <body>
        <table class="header-tbl">
            <tr>
                <td width="100%">
                    <h2 style="color: #0d9f4f;"><?= htmlspecialchars(strtoupper($settings['nama_baznas'] ?? 'BADAN AMIL ZAKAT NASIONAL (BAZNAS) KABUPATEN SUMBAWA')) ?></h2>
                    <h3 style="margin-top: 3px;">Laporan Pencatatan Pengumpulan &amp; Penyaluran ZIS Off Balance Sheet</h3>
                    <p style="margin-top: 4px;">Periode: <strong><?= htmlspecialchars($periodeLabel) ?></strong></p>
                </td>
            </tr>
        </table>

        <?php if ($export_mode !== 'sumber_dana'): ?>
        <div class="section-title">A. Rincian Pencatatan Off Balance Sheet per Jenis Dana</div>
        <table class="content-tbl">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th width="8%">Tanggal</th>
                    <th>UPZ / Lembaga Pelapor</th>
                    <th width="10%">No. BA</th>
                    <th width="6%">Muzaki</th>
                    <th width="6%">Mustahik</th>
                    <?php foreach ($jenisList as $j): ?>
                        <th class="text-right"><?= htmlspecialchars($j['nama_jenis']) ?></th>
                    <?php endforeach; ?>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="<?= 7 + count($jenisList) ?>" class="text-center">Belum ada data Off Balance Sheet pada periode ini.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal_transaksi'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['pelapor_display']) ?></strong>
                            <?= !empty($r['nama_sub_jenis']) ? '<br><small>(' . htmlspecialchars($r['nama_sub_jenis']) . ')</small>' : '' ?>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($r['no_berita_acara'] ?: '-') ?></td>
                        <td class="text-center"><?= number_format((int)$r['jumlah_muzaki'], 0, ',', '.') ?></td>
                        <td class="text-center"><?= number_format((int)$r['jumlah_mustahik'], 0, ',', '.') ?></td>
                        <?php foreach ($jenisList as $j): ?>
                            <td class="text-right">
                                <?php if ($r['id_jenis_pembayaran'] == $j['id']): ?>
                                    Rp <?= number_format((float)$r['nominal'], 0, ',', '.') ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="text-right"><strong>Rp <?= number_format((float)$r['nominal'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">TOTAL KESELURUHAN</th>
                    <th class="text-center"><?= number_format($total_muzaki, 0, ',', '.') ?></th>
                    <th class="text-center"><?= number_format($total_mustahik, 0, ',', '.') ?></th>
                    <?php foreach ($jenisList as $j): ?>
                        <th class="text-right">Rp <?= number_format($columnTotals[$j['id']], 0, ',', '.') ?></th>
                    <?php endforeach; ?>
                    <th class="text-right">Rp <?= number_format($total_nominal, 0, ',', '.') ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
        <?php endif; ?>

        <div class="section-title"><?= $export_mode === 'sumber_dana' ? 'Rekapitulasi' : 'B. Rekapitulasi' ?> Off Balance Sheet per Sumber Jenis Dana &amp; Sub-Jenis Dana</div>
        <table class="content-tbl">
            <thead>
                <tr>
                    <th>Sumber Dana (Jenis &amp; Sub-Jenis Pembayaran)</th>
                    <th width="12%">Jumlah Laporan</th>
                    <th width="12%">Muzaki (Jiwa)</th>
                    <th width="12%">Mustahik (Jiwa)</th>
                    <th width="20%">Realisasi Off Balance (Rp)</th>
                    <th width="10%">% Proporsi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groupedSumberDana)): ?>
                    <tr><td colspan="6" class="text-center">Data kosong.</td></tr>
                <?php else: ?>
                    <?php foreach ($groupedSumberDana as $jenisNama => $jData):
                        $pctJ = $total_nominal > 0 ? round(($jData['total_nominal'] / $total_nominal) * 100, 1) : 0;
                    ?>
                        <tr style="background-color: #e2e8f0; font-weight: bold;">
                            <td><?= strtoupper(htmlspecialchars($jenisNama)) ?></td>
                            <td class="text-center"><?= number_format($jData['total_laporan'], 0, ',', '.') ?></td>
                            <td class="text-center"><?= number_format($jData['total_muzaki'], 0, ',', '.') ?></td>
                            <td class="text-center"><?= number_format($jData['total_mustahik'], 0, ',', '.') ?></td>
                            <td class="text-right">Rp <?= number_format($jData['total_nominal'], 0, ',', '.') ?>,-</td>
                            <td class="text-center"><?= $pctJ ?>%</td>
                        </tr>
                        <?php foreach ($jData['subs'] as $sData):
                            $pctS = $total_nominal > 0 ? round(($sData['nominal'] / $total_nominal) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td style="padding-left: 18px;">&mdash; <?= htmlspecialchars($sData['nama_sub']) ?></td>
                                <td class="text-center"><?= number_format($sData['jumlah_laporan'], 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($sData['jumlah_muzaki'], 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($sData['jumlah_mustahik'], 0, ',', '.') ?></td>
                                <td class="text-right">Rp <?= number_format($sData['nominal'], 0, ',', '.') ?>,-</td>
                                <td class="text-center"><?= $pctS ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($groupedSumberDana)): ?>
            <tfoot>
                <tr>
                    <th class="text-right">TOTAL KESELURUHAN OFF BALANCE SHEET</th>
                    <th class="text-center"><?= number_format(count($rows), 0, ',', '.') ?></th>
                    <th class="text-center"><?= number_format($total_muzaki, 0, ',', '.') ?></th>
                    <th class="text-center"><?= number_format($total_mustahik, 0, ',', '.') ?></th>
                    <th class="text-right">Rp <?= number_format($total_nominal, 0, ',', '.') ?>,-</th>
                    <th class="text-center">100%</th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <table style="width: 100%; margin-top: 25px;">
            <tr>
                <td width="60%"></td>
                <td width="40%" class="text-center">
                    <?= htmlspecialchars($settings['kota_baznas'] ?? 'Sumbawa Besar') ?>, <?= date('d') . ' ' . month_name((int)date('n')) . ' ' . date('Y') ?><br>
                    <strong>Bidang Pengumpulan ZIS</strong><br><br><br><br><br>
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
    $dompdf->stream('Laporan_Off_Balance_' . ($export_mode === 'sumber_dana' ? 'Sumber_Dana_' : '') . $fileSuffix . '.pdf', ['Attachment' => false]);
    exit;
}
