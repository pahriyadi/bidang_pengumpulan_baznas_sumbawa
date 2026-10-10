<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/dokumen_db.php';
require_auth();

$idKategori = (int)($_GET['id_kategori'] ?? 0);
if ($idKategori <= 0) {
    die("Kategori dokumen tidak valid.");
}

$conn = db();
ensure_dokumen_tables($conn);

$kategori = get_dokumen_kategori_by_id($conn, $idKategori);
if (!$kategori) {
    die("Kategori dokumen tidak ditemukan.");
}

$kolomList = get_dokumen_kolom_list($conn, $idKategori);
$penerimaList = [];
$res = $conn->query("SELECT * FROM dokumen_penerima_data WHERE id_kategori_dokumen = {$idKategori} ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $r['decoded'] = json_decode($r['data_values'], true) ?: [];
        $penerimaList[] = $r;
    }
}

// Ambil semua foto dokumentasi penerima untuk lampiran cetak
$allFotos = [];
if (!empty($penerimaList)) {
    $penerimaIds = array_column($penerimaList, 'id');
    $idsStr = implode(',', array_map('intval', $penerimaIds));
    $qFoto = $conn->query("SELECT f.*, p.data_values 
                           FROM dokumen_penerima_foto f 
                           JOIN dokumen_penerima_data p ON f.id_penerima_data = p.id 
                           WHERE f.id_penerima_data IN ({$idsStr}) 
                           ORDER BY f.id_penerima_data ASC, f.urutan ASC, f.id ASC");
    if ($qFoto) {
        while ($rf = $qFoto->fetch_assoc()) {
            $rf['penerima_decoded'] = json_decode($rf['data_values'], true) ?: [];
            $allFotos[] = $rf;
        }
    }
}

// Menghitung total untuk kolom currency & number
$colTotals = [];
foreach ($kolomList as $c) {
    if ($c['tipe_data'] === 'currency' || $c['tipe_data'] === 'number') {
        $colTotals[$c['key_kolom']] = 0;
    }
}
foreach ($penerimaList as $item) {
    $f = $item['decoded'];
    foreach ($kolomList as $col) {
        $k = $col['key_kolom'];
        if ($col['tipe_data'] === 'currency' || $col['tipe_data'] === 'number') {
            $colTotals[$k] += (float)($f[$k] ?? 0);
        }
    }
}

// Bulan Indonesia
$bulanIndo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$tglCetak = date('j') . ' ' . $bulanIndo[(int)date('n')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kategori['nama_kategori']) ?> - Cetak Dokumen BAZNAS</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
            font-size: 11pt;
        }

        .no-print-bar {
            background: #f8f9fa;
            border: 1px solid #ced4da;
            padding: 10px 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
        }
        .btn-print {
            background: #0d9f4f;
            color: #fff;
            border: none;
            padding: 8px 16px;
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
        }

        /* Kop Surat Resmi */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-logo {
            width: 95px;
            text-align: center;
        }
        .kop-logo img {
            width: 85px;
            height: auto;
            object-fit: contain;
        }
        .kop-text {
            text-align: center;
            line-height: 1.25;
        }
        .kop-text h2 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            color: #000;
            letter-spacing: 0.5px;
        }
        .kop-text h1 {
            margin: 2px 0 0 0;
            font-size: 19pt;
            font-weight: bold;
            color: #0d9f4f;
            letter-spacing: 1px;
        }
        .kop-text p {
            margin: 4px 0 0 0;
            font-size: 9.5pt;
            color: #333;
        }

        .line-kop-1 {
            border-top: 2.5px solid #000;
            margin-top: 5px;
        }
        .line-kop-2 {
            border-top: 1px solid #000;
            margin-top: 2px;
            margin-bottom: 20px;
        }

        /* Judul Dokumen */
        .doc-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 13pt;
            text-transform: uppercase;
            font-weight: bold;
            text-decoration: underline;
        }
        .doc-title p {
            margin: 4px 0 0 0;
            font-size: 11pt;
            font-weight: bold;
        }

        /* Tabel Data */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 10pt;
        }
        .table-data th, .table-data td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .table-data th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Tanda Tangan */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .ttd-table td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 11pt;
        }
        .ttd-space {
            height: 75px;
        }

        /* Lampiran Foto Dokumentasi */
        .photo-appendix-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 15px;
        }
        .photo-card-print {
            border: 1px solid #000;
            padding: 8px;
            page-break-inside: avoid;
            background: #fff;
            text-align: center;
        }
        .photo-card-print img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border: 1px solid #ddd;
            margin-bottom: 6px;
            display: block;
        }
        .photo-card-caption {
            font-size: 10pt;
            font-weight: bold;
            color: #000;
            line-height: 1.25;
            margin-bottom: 2px;
        }
        .photo-card-sub {
            font-size: 8.5pt;
            color: #333;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: auto;
                margin: 12mm 15mm;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>Pratinjau Lembar Cetak Dokumen:</strong> <?= htmlspecialchars($kategori['nama_kategori']) ?>
        </div>
        <div>
            <button type="button" class="btn-print" onclick="window.print()">
                🖨️ Cetak Dokumen Sekarang
            </button>
            <button type="button" class="btn-close-win" onclick="window.close()">
                Tutup Jendela
            </button>
        </div>
    </div>

    <!-- Kop Surat Resmi BAZNAS Kabupaten Sumbawa -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                <img src="<?= get_favicon_url() ?>" alt="Logo BAZNAS">
            </td>
            <td class="kop-text">
                <h2>BADAN AMIL ZAKAT NASIONAL</h2>
                <h1>BAZNAS KABUPATEN SUMBAWA</h1>
                <p>Jl. Hasanuddin No. 33 (Kompleks Masjid Agung Nurul Huda), Sumbawa Besar - NTB 84311<br>
                Email: baznaskab.sumbawa@baznas.go.id | Website: baznassumbawa.id</p>
            </td>
            <td style="width: 85px;"></td>
        </tr>
    </table>
    <div class="line-kop-1"></div>
    <div class="line-kop-2"></div>

    <!-- Judul Dokumen Resmi -->
    <div class="doc-title">
        <h3>DAFTAR PENERIMA MANFAAT / BANTUAN</h3>
        <p><?= strtoupper(htmlspecialchars($kategori['nama_kategori'])) ?></p>
        <?php if (!empty($kategori['tahun_anggaran'])): ?>
            <p style="font-weight: normal; font-size: 10pt; margin-top: 2px;">TAHUN ANGGARAN <?= htmlspecialchars($kategori['tahun_anggaran']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Tabel Data Penerima -->
    <table class="table-data">
        <thead>
            <tr>
                <th width="4%">No</th>
                <?php foreach ($kolomList as $col): ?>
                    <th class="<?= in_array($col['tipe_data'], ['currency', 'number']) ? 'text-right' : 'text-center' ?>">
                        <?= htmlspecialchars($col['nama_kolom']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($penerimaList)): ?>
                <tr>
                    <td colspan="<?= count($kolomList) + 1 ?>" class="text-center" style="padding: 20px;">
                        <em>Tidak ada data penerima manfaat yang terdaftar.</em>
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($penerimaList as $item): $f = $item['decoded']; ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <?php foreach ($kolomList as $col): 
                            $k = $col['key_kolom'];
                            $val = $f[$k] ?? '';
                            $tipe = $col['tipe_data'];
                        ?>
                            <td class="<?= in_array($tipe, ['currency', 'number']) ? 'text-right' : '' ?>">
                                <?php 
                                if ($tipe === 'currency') {
                                    echo number_format((float)$val, 0, ',', '.');
                                } elseif ($tipe === 'number') {
                                    echo number_format((float)$val, 0, ',', '.');
                                } elseif ($tipe === 'date') {
                                    echo !empty($val) ? date('d/m/Y', strtotime($val)) : '-';
                                } elseif ($tipe === 'textarea') {
                                    echo nl2br(htmlspecialchars($val));
                                } else {
                                    echo htmlspecialchars($val ?: '-');
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($penerimaList) && !empty($colTotals)): ?>
            <tfoot>
                <tr class="font-bold">
                    <td class="text-center">TOTAL</td>
                    <?php foreach ($kolomList as $col): 
                        $k = $col['key_kolom'];
                        $tipe = $col['tipe_data'];
                    ?>
                        <td class="<?= in_array($tipe, ['currency', 'number']) ? 'text-right' : '' ?>">
                            <?php 
                            if ($tipe === 'currency' || $tipe === 'number') {
                                echo number_format($colTotals[$k] ?? 0, 0, ',', '.');
                            } else {
                                echo '';
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <table class="ttd-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Ketua BAZNAS Kabupaten Sumbawa</strong>
                <div class="ttd-space"></div>
                <strong><u>H. Muhammad Ali, M.Pd.I</u></strong><br>
                <span>Ketua</span>
            </td>
            <td>
                Sumbawa Besar, <?= $tglCetak ?><br>
                <strong>Pelaksana Program / Pendistribusian</strong>
                <div class="ttd-space"></div>
                <strong><u>Staf Pendistribusian BAZNAS</u></strong><br>
                <span>Pelaksana Kegiatan</span>
            </td>
        </tr>
    </table>

    <?php if (!empty($allFotos)): ?>
        <div class="page-break" style="page-break-before: always; margin-top: 35px;"></div>

        <!-- Kop Lampiran Dokumentasi -->
        <table class="kop-table">
            <tr>
                <td class="kop-logo">
                    <img src="<?= get_favicon_url() ?>" alt="Logo BAZNAS">
                </td>
                <td class="kop-text">
                    <h2>BADAN AMIL ZAKAT NASIONAL</h2>
                    <h1>BAZNAS KABUPATEN SUMBAWA</h1>
                    <p>Jl. Hasanuddin No. 33 (Kompleks Masjid Agung Nurul Huda), Sumbawa Besar - NTB 84311<br>
                    Email: baznaskab.sumbawa@baznas.go.id | Website: baznassumbawa.id</p>
                </td>
                <td style="width: 85px;"></td>
            </tr>
        </table>
        <div class="line-kop-1"></div>
        <div class="line-kop-2"></div>

        <!-- Judul Lampiran -->
        <div class="doc-title">
            <h3>LAMPIRAN FOTO DOKUMENTASI PENERIMA MANFAAT</h3>
            <p><?= strtoupper(htmlspecialchars($kategori['nama_kategori'])) ?></p>
            <p style="font-weight: normal; font-size: 9.5pt; margin-top: 2px;">Total: <?= count($allFotos) ?> Foto Dokumentasi Lapangan Terlampir</p>
        </div>

        <div class="photo-appendix-grid">
            <?php foreach ($allFotos as $idx => $fItem): 
                $dec = $fItem['penerima_decoded'];
                $labelNama = '';
                foreach ($kolomList as $col) {
                    if (stripos($col['nama_kolom'], 'nama') !== false || stripos($col['key_kolom'], 'nama') !== false) {
                        $labelNama = $dec[$col['key_kolom']] ?? '';
                        break;
                    }
                }
                if (!$labelNama) {
                    $labelNama = reset($dec) ?: ('Penerima #' . $fItem['id_penerima_data']);
                }
            ?>
                <div class="photo-card-print">
                    <img src="<?= base_url($fItem['file_path']) ?>" alt="<?= htmlspecialchars($fItem['judul_foto']) ?>">
                    <div class="photo-card-caption">
                        <?= htmlspecialchars($labelNama) ?>
                    </div>
                    <div class="photo-card-sub">
                        <?= htmlspecialchars($fItem['judul_foto'] ?: 'Dokumentasi Penyerahan') ?> &bull; <?= date('d/m/Y', strtotime($fItem['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>
