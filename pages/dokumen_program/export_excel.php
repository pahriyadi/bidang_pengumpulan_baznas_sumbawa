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

$filename = 'Daftar_Penerima_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $kategori['nama_kategori']) . '_' . date('Ymd_His') . '.xls';

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        th { background-color: #0d9f4f; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #000; }
        td { border: 1px solid #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="<?= count($kolomList) + 1 ?>" style="font-size: 14pt; font-weight: bold; text-align: center;">
                BADAN AMIL ZAKAT NASIONAL (BAZNAS) KABUPATEN SUMBAWA
            </td>
        </tr>
        <tr>
            <td colspan="<?= count($kolomList) + 1 ?>" style="font-size: 12pt; font-weight: bold; text-align: center;">
                DAFTAR PENERIMA MANFAAT: <?= strtoupper(htmlspecialchars($kategori['nama_kategori'])) ?>
            </td>
        </tr>
        <tr>
            <td colspan="<?= count($kolomList) + 1 ?>" style="font-size: 10pt; text-align: center;">
                Tahun Anggaran: <?= htmlspecialchars($kategori['tahun_anggaran'] ?: '-') ?> | Tanggal Unduh: <?= date('d/m/Y H:i') ?>
            </td>
        </tr>
        <tr><td colspan="<?= count($kolomList) + 1 ?>"></td></tr>
        <thead>
            <tr>
                <th width="50">No</th>
                <?php foreach ($kolomList as $col): ?>
                    <th><?= htmlspecialchars($col['nama_kolom']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($penerimaList)): ?>
                <tr>
                    <td colspan="<?= count($kolomList) + 1 ?>" class="text-center">Tidak ada data</td>
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
                                    echo (float)$val;
                                } elseif ($tipe === 'number') {
                                    echo (float)$val;
                                } elseif ($tipe === 'date') {
                                    echo !empty($val) ? date('d/m/Y', strtotime($val)) : '-';
                                } else {
                                    // Teks, jika NIK atau angka panjang berikan tanda kutip agar tidak scientific notation di Excel
                                    if (is_numeric($val) && strlen((string)$val) > 10) {
                                        echo "'" . htmlspecialchars((string)$val);
                                    } else {
                                        echo htmlspecialchars((string)$val);
                                    }
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
                                echo (float)($colTotals[$k] ?? 0);
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
</body>
</html>
