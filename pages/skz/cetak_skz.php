<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID SKZ tidak valid.");
}

$conn = db();
ensure_skz_table($conn);

$stmt = $conn->prepare("SELECT * FROM surat_ketetapan_zakat WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$skz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$skz) {
    die("Data Surat Ketetapan Zakat tidak ditemukan.");
}

// Format Tanggal Indonesia
$bulanIndo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$timeSurat = strtotime($skz['tanggal_surat']);
$tglSuratFormat = date('j', $timeSurat) . ' ' . $bulanIndo[(int)date('n', $timeSurat)] . ' ' . date('Y', $timeSurat);

$persenProfit = rtrim(rtrim(number_format((float)$skz['persentase_keuntungan'], 2, ',', '.'), '0'), ',');
$nilaiKontrak = 'Rp ' . number_format((float)$skz['nilai_kontrak'], 2, ',', '.');
$estimasiProfit = 'Rp ' . number_format((float)$skz['estimasi_keuntungan'], 2, ',', '.');
$nilaiZis = 'Rp ' . number_format((float)$skz['nilai_zis'], 2, ',', '.');
$kotaSurat = !empty($skz['kota_surat']) ? $skz['kota_surat'] : 'Sumbawa Besar';
$logoUrl = get_skz_logo_url();
$ttdUrl = get_skz_ttd_url();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKZ - <?= clean($skz['nomor_surat']) ?> - <?= clean($skz['nama_badan_usaha']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 25mm 20mm 25mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
            line-height: 1.5;
        }

        .no-print-bar {
            background: #f8f9fa;
            border: 1px solid #ced4da;
            padding: 10px 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
            font-family: sans-serif;
            font-size: 14px;
        }
        .btn-print {
            background: #0d9f4f;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
        }
        .btn-close-win {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            margin-left: 8px;
        }

        /* Container Dokumen Surat */
        .skz-paper {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Logo & Header Kop Atas */
        .skz-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .skz-header img {
            width: 155px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* Judul Surat */
        .skz-title-block {
            text-align: center;
            margin-bottom: 25px;
        }
        .skz-title-block h3 {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .skz-title-block p {
            margin: 4px 0 0 0;
            font-size: 11pt;
            font-weight: normal;
        }

        /* Paragraf & Tabel Identitas */
        .skz-body {
            text-align: justify;
        }
        .skz-body p {
            margin: 12px 0;
            line-height: 1.6;
        }

        .table-identitas {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 14px 0;
        }
        .table-identitas td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 12pt;
        }
        .td-label-1 {
            width: 130px;
            padding-left: 45px !important;
        }
        .td-label-2 {
            width: 220px;
            padding-left: 45px !important;
        }
        .td-colon {
            width: 18px;
            text-align: center;
        }
        .td-val {
            font-weight: normal;
        }

        /* Tanda Tangan */
        .skz-signature-wrap {
            margin-top: 35px;
            width: 100%;
        }
        .skz-signature-box {
            float: right;
            width: 320px;
            text-align: center;
        }
        .skz-signature-box p {
            margin: 2px 0;
            line-height: 1.4;
        }
        .skz-space-ttd {
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2px 0;
        }
        .skz-space-ttd img {
            max-height: 72px;
            max-width: 190px;
            object-fit: contain;
        }
        .skz-signer-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .skz-paper {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Pratinjau Cetak Surat Ketetapan Zakat (SKZ):</strong> <?= clean($skz['nomor_surat']) ?> - <?= clean($skz['nama_badan_usaha']) ?>
        </div>
        <div>
            <button type="button" class="btn-print" onclick="window.print()">
                🖨️ Cetak SKZ Sekarang
            </button>
            <button type="button" class="btn-close-win" onclick="window.close()">
                Tutup
            </button>
        </div>
    </div>

    <div class="skz-paper">
        <!-- Logo Header Tengah -->
        <div class="skz-header">
            <img src="<?= $logoUrl ?>" alt="Logo BAZNAS Kabupaten Sumbawa">
        </div>

        <!-- Judul SKZ & Nomor Surat -->
        <div class="skz-title-block">
            <h3>SURAT KETETAPAN ZAKAT (SKZ)</h3>
            <p><?= clean($skz['nomor_surat']) ?></p>
        </div>

        <!-- Isi Surat -->
        <div class="skz-body">
            <p>Yang bertanda tangan dibawah ini &nbsp; :</p>
            <table class="table-identitas">
                <tr>
                    <td class="td-label-1">Nama</td>
                    <td class="td-colon">:</td>
                    <td class="td-val font-weight-bold"><strong><?= clean($skz['nama_pimpinan']) ?></strong></td>
                </tr>
                <tr>
                    <td class="td-label-1">Jabatan</td>
                    <td class="td-colon">:</td>
                    <td class="td-val font-weight-bold"><strong><?= clean($skz['jabatan_pimpinan']) ?></strong></td>
                </tr>
            </table>

            <p>Menyatakan bahwa</p>
            <table class="table-identitas">
                <tr>
                    <td class="td-label-2">Nama Badan Usaha</td>
                    <td class="td-colon">:</td>
                    <td class="td-val"><strong><?= strtoupper(clean($skz['nama_badan_usaha'])) ?></strong></td>
                </tr>
                <tr>
                    <td class="td-label-2">Alamat</td>
                    <td class="td-colon">:</td>
                    <td class="td-val"><?= strtoupper(clean($skz['alamat_badan_usaha'])) ?></td>
                </tr>
                <tr>
                    <td class="td-label-2">Nilai Kontrak</td>
                    <td class="td-colon">:</td>
                    <td class="td-val font-weight-bold"><strong><?= $nilaiKontrak ?></strong></td>
                </tr>
                <tr>
                    <td class="td-label-2">Estimasi Keuntungan <?= $persenProfit ?>%</td>
                    <td class="td-colon">:</td>
                    <td class="td-val font-weight-bold"><strong><?= $estimasiProfit ?></strong></td>
                </tr>
                <tr>
                    <td class="td-label-2">Nilai ZIS</td>
                    <td class="td-colon">:</td>
                    <td class="td-val font-weight-bold"><strong><?= $nilaiZis ?></strong></td>
                </tr>
                <tr>
                    <td class="td-label-2">Paket Pekerjaan</td>
                    <td class="td-colon">:</td>
                    <td class="td-val"><strong><?= strtoupper(clean($skz['paket_pekerjaan'])) ?></strong></td>
                </tr>
                <tr>
                    <td class="td-label-2">Dinas/Instansi</td>
                    <td class="td-colon">:</td>
                    <td class="td-val"><strong><?= strtoupper(clean($skz['dinas_instansi'])) ?></strong></td>
                </tr>
            </table>

            <p style="margin-top: 18px;">
                Sudah memenuhi kewajiban membayar Zakat, Infak, dan Sedekah Sesuai dengan peraturan yang berlaku.
            </p>
            <p>
                Demikian surat ini untuk dipergunakan sebagaiamana mestinya.
            </p>
        </div>

        <!-- Bagian Tanda Tangan Kanan Bawah -->
        <div class="skz-signature-wrap clearfix">
            <div class="skz-signature-box">
                <p><?= clean($kotaSurat) ?>, <?= $tglSuratFormat ?></p>
                <p>Ketua,</p>
                <div class="skz-space-ttd">
                    <?php if (!empty($ttdUrl)): ?>
                        <img src="<?= $ttdUrl ?>" alt="Tanda Tangan Ketua">
                    <?php endif; ?>
                </div>
                <p class="skz-signer-name">(<?= clean($skz['nama_pimpinan']) ?>)</p>
            </div>
        </div>
    </div>

</body>
</html>
