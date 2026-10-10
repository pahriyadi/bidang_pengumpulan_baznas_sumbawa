<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID BSZIS tidak valid.");
}

$conn = db();
ensure_skz_table($conn);

$stmt = $conn->prepare("SELECT * FROM surat_ketetapan_zakat WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$skz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$skz) {
    die("Data Bukti Setoran ZIS tidak ditemukan.");
}

// Format Tanggal Indonesia
$bulanIndo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$timeSurat = strtotime($skz['tanggal_surat']);
$tglSuratFormat = date('j', $timeSurat) . ' ' . $bulanIndo[(int)date('n', $timeSurat)] . ' ' . date('Y', $timeSurat);

$nilaiZisFormatted = number_format((float)$skz['nilai_zis'], 0, ',', '.');
$logoUrl = get_skz_logo_url();
$alamatKantor = get_setting('skz_alamat_kantor', 'Jl. Hasanuddin No. 01 Kelurahan Bugis Kec. Sumbawa');
$telpKantor = get_setting('skz_telepon_kantor', '082 342 569 646');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSZIS - <?= clean($skz['nomor_surat']) ?> - <?= clean($skz['nama_badan_usaha']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 15mm 10mm 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            font-size: 10pt;
            line-height: 1.35;
        }

        .no-print-bar {
            background: #f8f9fa;
            border: 1px solid #ced4da;
            padding: 10px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
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

        .bszis-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
        }

        /* Border Box Wrapper untuk Lembar */
        .lembar-box {
            border: 1.5px solid #000;
            padding: 0;
            margin-bottom: 20px;
            background: #fff;
        }

        /* Header Lembar */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #000;
        }
        .kop-table td {
            padding: 8px 10px;
            vertical-align: middle;
        }
        .kop-logo-td {
            width: 180px;
            text-align: center;
            border-right: 1.5px solid #000;
        }
        .kop-logo-td img {
            width: 140px;
            height: auto;
        }
        .kop-instansi-td {
            text-align: center;
            line-height: 1.3;
        }
        .kop-instansi-td h4 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .kop-instansi-td p {
            margin: 4px 0 0 0;
            font-size: 8.5pt;
        }
        .kop-lembar-td {
            width: 180px;
            border-left: 1.5px solid #000;
            padding: 0 !important;
            vertical-align: top;
        }
        .box-lembar-inner {
            display: flex;
            border-bottom: 1.5px solid #000;
        }
        .box-lembar-title {
            padding: 5px 8px;
            font-size: 9pt;
            font-weight: bold;
            flex-grow: 1;
            border-right: 1.5px solid #000;
        }
        .box-lembar-num {
            padding: 5px 12px;
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            min-width: 25px;
        }
        .box-lembar-desc {
            padding: 6px 8px;
            font-size: 8.5pt;
            text-align: center;
            font-weight: 500;
        }

        /* Judul Bukti Setor */
        .title-bukti {
            text-align: center;
            padding: 8px 10px;
            font-size: 12pt;
            font-weight: bold;
            border-bottom: 1.5px solid #000;
            background-color: #fafafa;
        }

        /* Data Baris Identitas */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #000;
        }
        .info-table td {
            padding: 3px 12px;
            font-size: 9pt;
            vertical-align: top;
        }
        .info-td-label {
            width: 140px;
        }
        .info-td-colon {
            width: 15px;
            text-align: center;
        }

        /* Tabel Rincian Objek ZIS */
        .table-rincian {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #000;
        }
        .table-rincian th, .table-rincian td {
            border: 1px solid #000;
            padding: 6px 10px;
            font-size: 9pt;
        }
        .table-rincian th {
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .table-rincian td.col-objek {
            text-align: center;
            vertical-align: middle;
            width: 130px;
            font-weight: bold;
        }
        .table-rincian td.col-uraian {
            vertical-align: middle;
        }
        .table-rincian td.col-via {
            text-align: center;
            vertical-align: middle;
            width: 100px;
            font-weight: bold;
        }
        .table-rincian td.col-jumlah {
            text-align: right;
            vertical-align: middle;
            width: 130px;
            font-weight: bold;
        }
        .table-rincian tr.tr-total td {
            border-top: 1.5px solid #000;
            font-weight: bold;
        }

        /* Catatan Doa */
        .doa-note {
            padding: 6px 12px;
            font-size: 9pt;
            font-weight: bold;
            border-bottom: 1.5px solid #000;
            text-align: center;
            line-height: 1.4;
        }

        /* Tanda Tangan */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-table th {
            width: 50%;
            padding: 6px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #000;
            font-weight: bold;
            text-align: center;
        }
        .ttd-table th:first-child {
            border-right: 1.5px solid #000;
        }
        .ttd-table td {
            width: 50%;
            height: 70px;
            vertical-align: bottom;
            text-align: center;
            padding: 6px 10px;
            font-size: 9pt;
        }
        .ttd-table td:first-child {
            border-right: 1.5px solid #000;
        }

        /* Lembar 2 (Bagian Bawah) */
        .lembar2-top-info {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            border-bottom: 1px solid #000;
            font-size: 9pt;
            font-weight: bold;
        }
        .box-paraf {
            width: 110px;
            border: 1px solid #000;
            margin: 6px 0 0 10px;
            text-align: center;
        }
        .box-paraf-title {
            border-bottom: 1px solid #000;
            font-size: 8pt;
            font-weight: bold;
            padding: 2px 0;
            background-color: #f8f9fa;
        }
        .box-paraf-space {
            height: 35px;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .bszis-container {
                max-width: 100%;
            }
            .lembar-box {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Pratinjau Cetak Bukti Setoran ZIS (BSZIS):</strong> <?= clean($skz['nomor_surat']) ?> - <?= clean($skz['nama_badan_usaha']) ?>
        </div>
        <div>
            <button type="button" class="btn-print" onclick="window.print()">
                🖨️ Cetak BSZIS Sekarang
            </button>
            <button type="button" class="btn-close-win" onclick="window.close()">
                Tutup
            </button>
        </div>
    </div>

    <div class="bszis-container">

        <!-- ==================== LEMBAR 1 (UNTUK MUZAKI) ==================== -->
        <div class="lembar-box">
            <!-- Header Kop -->
            <table class="kop-table">
                <tr>
                    <td class="kop-logo-td">
                        <img src="<?= $logoUrl ?>" alt="Logo BAZNAS">
                    </td>
                    <td class="kop-instansi-td">
                        <h4>BADAN AMIL ZAKAT NASIONAL</h4>
                        <p><?= clean($alamatKantor) ?><br>Telp :<?= clean($telpKantor) ?></p>
                    </td>
                    <td class="kop-lembar-td">
                        <div class="box-lembar-inner">
                            <div class="box-lembar-title">Lembar</div>
                            <div class="box-lembar-num">1</div>
                        </div>
                        <div class="box-lembar-desc">
                            Untuk Muzaki Wajib<br>Zakat/Infak dan Sedekah
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Judul -->
            <div class="title-bukti">
                Bukti Setoran Zakat/Infak dan Sedekah
            </div>

            <!-- Detail Muzaki / Rekanan -->
            <table class="info-table">
                <tr>
                    <td class="info-td-label">Nomor</td>
                    <td class="info-td-colon">:</td>
                    <td><strong><?= clean($skz['nomor_surat']) ?></strong></td>
                </tr>
                <tr>
                    <td class="info-td-label">Periode</td>
                    <td class="info-td-colon">:</td>
                    <td><?= $tglSuratFormat ?></td>
                </tr>
                <tr>
                    <td class="info-td-label">Telah terima dari</td>
                    <td class="info-td-colon">:</td>
                    <td><strong><?= strtoupper(clean($skz['nama_badan_usaha'])) ?></strong></td>
                </tr>
                <tr>
                    <td class="info-td-label">NPWZ</td>
                    <td class="info-td-colon">:</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="info-td-label">NPWP</td>
                    <td class="info-td-colon">:</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td class="info-td-label">Alamat</td>
                    <td class="info-td-colon">:</td>
                    <td><?= strtoupper(clean($skz['alamat_badan_usaha'])) ?></td>
                </tr>
                <tr>
                    <td class="info-td-label">Telepon/Email</td>
                    <td class="info-td-colon">:</td>
                    <td>-</td>
                </tr>
            </table>

            <!-- Tabel Rincian -->
            <table class="table-rincian">
                <thead>
                    <tr>
                        <th style="width: 140px;">Objek ZIS</th>
                        <th>Uraian</th>
                        <th style="width: 90px;">Via</th>
                        <th style="width: 140px;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-objek"><?= strtoupper(clean($skz['objek_zis'])) ?></td>
                        <td class="col-uraian"><strong><?= strtoupper(clean($skz['paket_pekerjaan'])) ?></strong></td>
                        <td class="col-via"><?= strtoupper(clean($skz['via_pembayaran'])) ?></td>
                        <td class="col-jumlah">Rp &nbsp; <?= $nilaiZisFormatted ?></td>
                    </tr>
                    <tr class="tr-total">
                        <td colspan="3" style="text-align: left; font-weight: bold; padding-left: 12px;">Total</td>
                        <td class="col-jumlah">Rp &nbsp; <?= $nilaiZisFormatted ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Kalimat Doa -->
            <div class="doa-note">
                Semoga Allah SWT memberikan pahala kepada Bapak/Ibu atas harta yang telah dikeluarkan dan menjadi berkah dan suci atas harta yang lainnya
            </div>

            <!-- Tanda Tangan -->
            <table class="ttd-table">
                <thead>
                    <tr>
                        <th>Pengesahan Petugas Amil</th>
                        <th>Penyetor/Wajib Zakat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <br><br><br>
                            <strong>( <?= clean($skz['nama_petugas']) ?> )</strong>
                        </td>
                        <td>
                            <br><br><br>
                            <strong>( <?= clean($skz['nama_penyetor']) ?> )</strong>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


        <!-- ==================== LEMBAR 2 (UNTUK ARSIP) ==================== -->
        <div class="lembar-box" style="margin-top: 15px;">
            <!-- Header Kop -->
            <table class="kop-table">
                <tr>
                    <td class="kop-logo-td">
                        <img src="<?= $logoUrl ?>" alt="Logo BAZNAS">
                    </td>
                    <td class="kop-instansi-td">
                        <h4>BADAN AMIL ZAKAT NASIONAL<br>KABUPATEN SUMBAWA</h4>
                        <p><?= clean($alamatKantor) ?><br>Telp :<?= clean($telpKantor) ?></p>
                    </td>
                    <td class="kop-lembar-td">
                        <div class="box-lembar-inner">
                            <div class="box-lembar-title">Lembar</div>
                            <div class="box-lembar-num">2</div>
                        </div>
                        <div class="box-lembar-desc">
                            Untuk Arsip Wajib Zakat
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Baris Penerima & Tanggal -->
            <div class="lembar2-top-info">
                <div>
                    Telah terima dari : <strong><?= strtoupper(clean($skz['nama_badan_usaha'])) ?></strong>
                </div>
                <div>
                    TANGGAL : <?= $tglSuratFormat ?>
                </div>
            </div>

            <!-- Tabel Rincian Lembar 2 -->
            <table class="table-rincian">
                <thead>
                    <tr>
                        <th style="width: 140px;">Objek ZIS</th>
                        <th>Uraian</th>
                        <th style="width: 90px;">Via</th>
                        <th style="width: 140px;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="col-objek"><?= strtoupper(clean($skz['objek_zis'])) ?></td>
                        <td class="col-uraian"><strong><?= strtoupper(clean($skz['paket_pekerjaan'])) ?></strong></td>
                        <td class="col-via"><?= strtoupper(clean($skz['via_pembayaran'])) ?></td>
                        <td class="col-jumlah">Rp &nbsp; <?= $nilaiZisFormatted ?></td>
                    </tr>
                    <tr class="tr-total">
                        <td colspan="3" style="text-align: left; font-weight: bold; padding-left: 12px;">Total</td>
                        <td class="col-jumlah">Rp &nbsp; <?= $nilaiZisFormatted ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Kolom Kotak Paraf di Kiri Bawah -->
            <div style="padding: 5px 0 8px 0;">
                <div class="box-paraf">
                    <div class="box-paraf-title">PARAF</div>
                    <div class="box-paraf-space"></div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
