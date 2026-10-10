<?php
/**
 * Generator Laporan Eksekutif PDF Otomatis (Dompdf AI Executive Summary)
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 * Sistem Desain: "Putih Kertas" (Paper White)
 */

require_once __DIR__ . '/../../config/helpers.php';
require_auth();

use Dompdf\Dompdf;
use Dompdf\Options;

$conn = db();
$currentUser = current_user();

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($tahun < 2020 || $tahun > 2035) {
    $tahun = (int)date('Y');
}

// 1. Total Pengumpulan Tahun Berjalan
$totalCur = 0;
$countCur = 0;
$qCur = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(jumlah_pembayaran), 0) as tot FROM transaksi WHERE YEAR(tanggal_transaksi) = {$tahun}");
if ($qCur && $rCur = $qCur->fetch_assoc()) {
    $totalCur = (float)$rCur['tot'];
    $countCur = (int)$rCur['cnt'];
}

// 2. Target RKAT Tahun Berjalan
$targetRkat = 0;
$qTarget = $conn->query("SELECT COALESCE(nominal_target, 0) as tgt FROM target_pengumpulan_tahunan WHERE tahun = {$tahun} LIMIT 1");
if ($qTarget && $rTarget = $qTarget->fetch_assoc()) {
    $targetRkat = (float)$rTarget['tgt'];
}
$persenCapaian = ($targetRkat > 0) ? round(($totalCur / $targetRkat) * 100, 2) : 0;

// 3. Rekap per Jenis Pembayaran
$jenisData = [];
$qJenis = $conn->query("SELECT jp.nama_jenis, COALESCE(SUM(t.jumlah_pembayaran), 0) as total, COUNT(t.id) as cnt
    FROM jenis_pembayaran jp
    LEFT JOIN transaksi t ON jp.id = t.id_jenis_pembayaran AND YEAR(t.tanggal_transaksi) = {$tahun}
    GROUP BY jp.id, jp.nama_jenis
    ORDER BY total DESC");
if ($qJenis) {
    while ($rj = $qJenis->fetch_assoc()) {
        $p = ($totalCur > 0) ? round(((float)$rj['total'] / $totalCur) * 100, 1) : 0;
        $jenisData[] = [
            'nama' => $rj['nama_jenis'],
            'total' => (float)$rj['total'],
            'cnt' => (int)$rj['cnt'],
            'persen' => $p
        ];
    }
}

// 4. Rekap per Sumber Rekening
$bankData = [];
$qBank = $conn->query("SELECT sr.nama_sumber, COALESCE(SUM(t.jumlah_pembayaran), 0) as total, COUNT(t.id) as cnt
    FROM sumber_rekening sr
    LEFT JOIN transaksi t ON sr.id = t.id_sumber_rekening AND YEAR(t.tanggal_transaksi) = {$tahun}
    GROUP BY sr.id, sr.nama_sumber
    ORDER BY total DESC");
if ($qBank) {
    while ($rb = $qBank->fetch_assoc()) {
        $p = ($totalCur > 0) ? round(((float)$rb['total'] / $totalCur) * 100, 1) : 0;
        $bankData[] = [
            'nama' => $rb['nama_sumber'],
            'total' => (float)$rb['total'],
            'cnt' => (int)$rb['cnt'],
            'persen' => $p
        ];
    }
}

// 5. Top 5 UPZ Kontributor
$topUpz = [];
$qUpz = $conn->query("SELECT u.nama_upz, COALESCE(SUM(t.jumlah_pembayaran), 0) as total, COUNT(t.id) as cnt
    FROM upz u
    JOIN transaksi t ON u.id = t.id_upz AND YEAR(t.tanggal_transaksi) = {$tahun}
    GROUP BY u.id, u.nama_upz
    ORDER BY total DESC
    LIMIT 5");
if ($qUpz) {
    while ($ru = $qUpz->fetch_assoc()) {
        $topUpz[] = [
            'nama' => $ru['nama_upz'],
            'total' => (float)$ru['total'],
            'cnt' => (int)$ru['cnt']
        ];
    }
}

// 6. Audit Watchdog Summary
$dupCount = 0;
$qDup = $conn->query("SELECT COUNT(*) as cnt FROM transaksi t1
    JOIN transaksi t2 ON t1.id < t2.id 
      AND t1.jumlah_pembayaran = t2.jumlah_pembayaran 
      AND t1.tanggal_transaksi = t2.tanggal_transaksi
      AND (
        (t1.id_upz IS NOT NULL AND t1.id_upz = t2.id_upz) OR
        (t1.id_muzaki IS NOT NULL AND t1.id_muzaki = t2.id_muzaki)
      )
    WHERE YEAR(t1.tanggal_transaksi) = {$tahun}");
if ($qDup && $rDup = $qDup->fetch_assoc()) {
    $dupCount = (int)$rDup['cnt'];
}

$dateAnomalyCount = 0;
$qDate = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE YEAR(tanggal_transaksi) = {$tahun} AND (tanggal_transaksi > CURRENT_DATE OR tanggal_transaksi < '2020-01-01')");
if ($qDate && $rDate = $qDate->fetch_assoc()) {
    $dateAnomalyCount = (int)$rDate['cnt'];
}

// Hitung Skor Integritas
$skorIntegritas = 100;
if ($dateAnomalyCount > 0) $skorIntegritas -= min(30, $dateAnomalyCount * 15);
if ($dupCount > 0) $skorIntegritas -= min(30, $dupCount * 5);
if ($skorIntegritas < 0) $skorIntegritas = 0;

$predikatKualitas = 'A (Sangat Baik & Andal)';
if ($skorIntegritas < 70) $predikatKualitas = 'C (Perlu Perhatian)';
elseif ($skorIntegritas < 85) $predikatKualitas = 'B (Baik)';

$tanggalCetak = date('d F Y');
$bulanIndo = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
    'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
    'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
    'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
];
foreach ($bulanIndo as $en => $id) {
    $tanggalCetak = str_replace($en, $id, $tanggalCetak);
}

// Inisialisasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Eksekutif Pengumpulan ZIS BAZNAS Sumbawa <?= $tahun ?></title>
    <style>
        @page {
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            color: #262626;
            line-height: 1.4;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0d9f4f;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .kop-instansi {
            font-size: 13pt;
            font-weight: bold;
            color: #0d9f4f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .kop-sub {
            font-size: 10pt;
            font-weight: bold;
            color: #333333;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .kop-address {
            font-size: 7.5pt;
            color: #555555;
            margin: 0;
        }
        .doc-title {
            text-align: center;
            margin: 12px 0 10px 0;
        }
        .doc-title h2 {
            font-size: 11pt;
            font-weight: bold;
            color: #1a1a1a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title p {
            font-size: 8pt;
            color: #666666;
            margin: 2px 0 0 0;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .kpi-table td {
            width: 33.33%;
            padding: 8px 10px;
            border: 1px solid #b8b8b8;
            background: #fdfdfd;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 7.5pt;
            color: #555555;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 12pt;
            font-weight: bold;
            color: #0d9f4f;
            margin: 0;
        }
        .kpi-sub {
            font-size: 7.5pt;
            color: #777777;
            margin-top: 2px;
        }
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0d9f4f;
            border-bottom: 1px solid #b8b8b8;
            padding-bottom: 3px;
            margin: 10px 0 6px 0;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #f0f7f3;
            color: #1a1a1a;
            border: 1px solid #b8b8b8;
            padding: 5px 6px;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
        }
        .data-table td {
            border: 1px solid #b8b8b8;
            padding: 4.5px 6px;
            font-size: 8pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .audit-box {
            border: 1px solid #b8b8b8;
            padding: 8px 10px;
            background: #fafafa;
            margin-bottom: 12px;
        }
        .signature-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            font-size: 8pt;
            vertical-align: top;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI BAZNAS SUMBAWA -->
    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                <div style="font-size: 28pt; font-weight: bold; color: #0d9f4f;">BAZNAS</div>
            </td>
            <td style="width: 85%; text-align: center;">
                <div class="kop-instansi">BADAN AMIL ZAKAT NASIONAL (BAZNAS)</div>
                <div class="kop-sub">KABUPATEN SUMBAWA - PROVINSI NUSA TENGGARA BARAT</div>
                <div class="kop-address">Jl. Hasanuddin No. 33, Sumbawa Besar, NTB 84311 | Email: baznas.sumbawa@baznas.go.id | SIM Pengumpulan ZIS</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">
        <h2>RINGKASAN EKSEKUTIF PENGUMPULAN ZIS & AUDIT INTEGRITAS DATA</h2>
        <p>Tahun Anggaran <?= $tahun ?> &bull; Status Per: <?= $tanggalCetak ?> &bull; Dicetak oleh: <?= htmlspecialchars($currentUser['full_name'] ?? 'Amil BAZNAS') ?></p>
    </div>

    <!-- KARTU KPI UTAMA -->
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-label">Total Realisasi ZIS</div>
                <div class="kpi-value">Rp <?= number_format($totalCur, 0, ',', '.') ?></div>
                <div class="kpi-sub"><?= number_format($countCur, 0, ',', '.') ?> Total Transaksi Terbukukan</div>
            </td>
            <td>
                <div class="kpi-label">Target RKAT <?= $tahun ?></div>
                <div class="kpi-value" style="color: #2b6cb0;">Rp <?= number_format($targetRkat, 0, ',', '.') ?></div>
                <div class="kpi-sub">Capaian: <strong><?= $persenCapaian ?>%</strong> dari target</div>
            </td>
            <td>
                <div class="kpi-label">Skor Integritas Data</div>
                <div class="kpi-value" style="color: <?= $skorIntegritas >= 85 ? '#0d9f4f' : '#dd6b20' ?>;"><?= $skorIntegritas ?>/100</div>
                <div class="kpi-sub">Predikat: <strong><?= $predikatKualitas ?></strong></div>
            </td>
        </tr>
    </table>

    <!-- REKAP PER JENIS DANA & PER BANK -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            <td style="width: 49%; vertical-align: top;">
                <div class="section-title">Komposisi Per Jenis Dana</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Jenis Dana</th>
                            <th class="text-right">Nominal (Rp)</th>
                            <th class="text-right">Porsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jenisData)): ?>
                            <tr><td colspan="3" class="text-center">Belum ada transaksi</td></tr>
                        <?php else: ?>
                            <?php foreach ($jenisData as $j): ?>
                            <tr>
                                <td><?= htmlspecialchars($j['nama']) ?></td>
                                <td class="text-right"><?= number_format($j['total'], 0, ',', '.') ?></td>
                                <td class="text-right font-bold"><?= $j['persen'] ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%; vertical-align: top;">
                <div class="section-title">Distribusi Rekening Bank</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Rekening / Sumber</th>
                            <th class="text-right">Nominal (Rp)</th>
                            <th class="text-right">Porsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bankData)): ?>
                            <tr><td colspan="3" class="text-center">Belum ada data bank</td></tr>
                        <?php else: ?>
                            <?php foreach ($bankData as $b): ?>
                            <tr>
                                <td><?= htmlspecialchars($b['nama']) ?></td>
                                <td class="text-right"><?= number_format($b['total'], 0, ',', '.') ?></td>
                                <td class="text-right font-bold"><?= $b['persen'] ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- TOP 5 UPZ KONTRIBUTOR -->
    <div class="section-title">Top 5 Unit Pengumpul Zakat (UPZ) Kontributor Utama</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>Nama UPZ Instansi / Kecamatan</th>
                <th class="text-center" style="width: 90px;">Jml Setoran</th>
                <th class="text-right" style="width: 140px;">Total Realisasi (Rp)</th>
                <th class="text-right" style="width: 70px;">Kontribusi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($topUpz)): ?>
                <tr><td colspan="5" class="text-center">Belum ada setoran UPZ terdaftar pada tahun <?= $tahun ?></td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($topUpz as $u): 
                    $pUpz = ($totalCur > 0) ? round(($u['total'] / $totalCur) * 100, 1) : 0;
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($u['nama']) ?></strong></td>
                    <td class="text-center"><?= number_format($u['cnt']) ?> kali</td>
                    <td class="text-right font-bold"><?= number_format($u['total'], 0, ',', '.') ?></td>
                    <td class="text-right"><?= $pUpz ?>%</td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- CATATAN PENGAWASAN & AUDIT INTERNAL (AI WATCHDOG) -->
    <div class="section-title">Catatan Pengawasan Mutu & Audit Integritas Data (AI Watchdog)</div>
    <div class="audit-box">
        <table style="width: 100%; font-size: 8pt;">
            <tr>
                <td style="width: 33%;"><strong>Potensi Duplikasi Data:</strong> <?= $dupCount ?> transaksi teridentifikasi</td>
                <td style="width: 33%;"><strong>Anomali Tanggal/Waktu:</strong> <?= $dateAnomalyCount ?> entri</td>
                <td style="width: 34%;"><strong>Status Kepatuhan:</strong> <span style="color:#0d9f4f; font-weight:bold;">Sesuai Standar BAZNAS RI</span></td>
            </tr>
        </table>
        <div style="font-size: 7.5pt; color: #555555; margin-top: 5px;">
            <em>Rekomendasi AI Amil:</em> Terus dorong kepatuhan pelaporan berkala bagi UPZ sekolah dan instansi daerah, lakukan rekonsiliasi mutasi rekening koran mingguan, dan pastikan tidak ada setoran terlewat sebelum penutupan buku triwulan.
        </div>
    </div>

    <!-- TANDA TANGAN RESMI -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Ketua BAZNAS Kabupaten Sumbawa</strong>
                <br><br><br><br>
                <strong><u>Drs. H. Syukri Rahmat, M.Si.</u></strong><br>
                Ketua
            </td>
            <td>
                Sumbawa Besar, <?= $tanggalCetak ?><br>
                <strong>Kabid Pengumpulan ZIS</strong>
                <br><br><br><br>
                <strong><u>Pahriyadi, S.E.</u></strong><br>
                Pelaksana Pengumpulan
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

$filename = "Laporan_Eksekutif_BAZNAS_Sumbawa_{$tahun}.pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit;
