<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

require_once __DIR__ . '/../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID Transaksi tidak valid.");
}

$conn = db();
$sql = "SELECT t.*, 
        m.nama_muzaki, u.nama_upz, jp.nama_jenis, sjp.nama_sub_jenis,
        mp.nama_metode, sr.nama_sumber
        FROM transaksi t
        LEFT JOIN muzaki m ON t.id_muzaki = m.id
        LEFT JOIN upz u ON t.id_upz = u.id
        LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
        LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
        LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
        LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
        WHERE t.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$t = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$t) {
    die("Transaksi tidak ditemukan.");
}

// Translate id_kategori_dana
$kategoriList = get_kategori_dana_list();
$kategoriMap = [];
foreach ($kategoriList as $k) {
    $kategoriMap[$k['id']] = $k['nama_kategori'];
}

$katIds = parse_kategori_dana_ids($t['id_kategori_dana']);
$katNames = [];
foreach ($katIds as $kid) {
    if (isset($kategoriMap[$kid])) {
        $katNames[] = $kategoriMap[$kid];
    }
}
$kategori_str = implode(', ', $katNames);

// Start PDF Generation
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
    <title>Bukti Pembayaran ZIS - <?= $t['id'] ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #222; margin: 0; padding: 20px; }
        .receipt-container { width: 100%; max-width: 800px; margin: 0 auto; border: 2px solid #16a34a; padding: 20px; border-radius: 8px; }
        .header { width: 100%; border-bottom: 2px solid #ccc; padding-bottom: 15px; margin-bottom: 15px; }
        .header td { vertical-align: middle; }
        .title { text-align: center; color: #16a34a; font-size: 1.4em; font-weight: bold; margin: 0 0 5px 0; }
        .subtitle { text-align: center; font-size: 1.1em; margin: 0; font-weight: normal; }
        .transaction-details { width: 100%; margin-bottom: 20px; }
        .transaction-details td { padding: 6px; }
        .label { font-weight: bold; width: 30%; }
        .separator { width: 2%; text-align: center; }
        .value { width: 68%; }
        .amount-box { background-color: #f0fdf4; border: 1px dashed #16a34a; border-radius: 5px; padding: 10px; text-align: center; margin: 20px 0; }
        .amount-box h3 { margin: 0; color: #15803d; font-size: 1.5em; }
        .signature-table { width: 100%; margin-top: 40px; text-align: center; }
        .signature-table td { width: 50%; }
    </style>
</head>
<body>
    <div class="receipt-container">
        <table class="header">
            <tr>
                <td width="15%">
                    <!-- Logo placeholder -->
                </td>
                <td width="70%">
                    <h1 class="title">BADAN AMIL ZAKAT NASIONAL (BAZNAS)</h1>
                    <h2 class="subtitle">BUKTI PENERIMAAN / PENGUMPULAN ZIS</h2>
                </td>
                <td width="15%">
                </td>
            </tr>
        </table>

        <div style="text-align: right; margin-bottom: 20px; font-weight: bold;">
            No. Transaksi: #TRX-<?= str_pad($t['id'], 6, '0', STR_PAD_LEFT) ?>
        </div>

        <table class="transaction-details">
            <tr>
                <td class="label">Tanggal Transaksi</td>
                <td class="separator">:</td>
                <td class="value"><?= date('d F Y', strtotime($t['tanggal_transaksi'])) ?></td>
            </tr>
            <tr>
                <td class="label">Nama Muzaki / Instansi</td>
                <td class="separator">:</td>
                <td class="value">
                    <strong><?= htmlspecialchars($t['nama_muzaki'] ?? '-') ?></strong>
                    <?= $t['nama_upz'] ? '<br><small>('.htmlspecialchars($t['nama_upz']).')</small>' : '' ?>
                </td>
            </tr>
            <tr>
                <td class="label">Jenis Dana</td>
                <td class="separator">:</td>
                <td class="value">
                    <?= htmlspecialchars($t['nama_jenis'] ?? '-') ?>
                    <?= $t['nama_sub_jenis'] ? ' - ' . htmlspecialchars($t['nama_sub_jenis']) : '' ?>
                </td>
            </tr>
            <tr>
                <td class="label">Kategori Dana</td>
                <td class="separator">:</td>
                <td class="value"><?= htmlspecialchars($kategori_str ?: '-') ?></td>
            </tr>
            <tr>
                <td class="label">Metode Pembayaran</td>
                <td class="separator">:</td>
                <td class="value"><?= htmlspecialchars($t['nama_metode'] ?? '-') ?> (via <?= htmlspecialchars($t['nama_sumber'] ?? '-') ?>)</td>
            </tr>
            <tr>
                <td class="label">Keterangan</td>
                <td class="separator">:</td>
                <td class="value"><?= htmlspecialchars($t['keterangan'] ?? '-') ?></td>
            </tr>
        </table>

        <div class="amount-box">
            <span>Nominal Pembayaran</span>
            <h3>Rp <?= number_format($t['jumlah_pembayaran'], 0, ',', '.') ?>,-</h3>
        </div>

        <p style="text-align: center; font-style: italic; color: #555;">Semoga Allah memberikan pahala atas apa yang engkau berikan, memberikan keberkahan atas harta yang tersisa, dan menjadikannya sebagai penyuci bagimu.</p>

        <table class="signature-table">
            <tr>
                <td>
                    <strong>Muzaki / Penyetor</strong><br><br><br><br><br>
                    (_______________________)
                </td>
                <td>
                    <strong>Petugas BAZNAS</strong><br><br><br><br><br>
                    (_______________________)
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'landscape');
$dompdf->render();
$dompdf->stream('Bukti_ZIS_' . $t['id'] . '.pdf', array("Attachment" => false));
exit;
