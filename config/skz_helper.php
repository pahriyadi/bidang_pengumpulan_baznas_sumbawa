<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Pastikan tabel surat_ketetapan_zakat dan pengaturan default tersedia
 */
function ensure_skz_table(?mysqli $conn = null): void
{
    $conn = $conn ?: db();

    $tableSql = "CREATE TABLE IF NOT EXISTS `surat_ketetapan_zakat` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `nomor_urut` INT(11) NOT NULL,
        `nomor_surat` VARCHAR(100) NOT NULL,
        `tanggal_surat` DATE NOT NULL,
        `nama_pimpinan` VARCHAR(150) NOT NULL,
        `jabatan_pimpinan` VARCHAR(150) NOT NULL,
        `kota_surat` VARCHAR(100) DEFAULT 'Sumbawa Besar',
        `id_upz_badan_usaha` INT(11) NULL,
        `nama_badan_usaha` VARCHAR(200) NOT NULL,
        `alamat_badan_usaha` TEXT NULL,
        `id_upz_dinas` INT(11) NULL,
        `dinas_instansi` VARCHAR(200) NOT NULL,
        `paket_pekerjaan` TEXT NOT NULL,
        `nilai_kontrak` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `persentase_keuntungan` DECIMAL(5,2) NOT NULL DEFAULT 6.00,
        `estimasi_keuntungan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `persentase_zis` DECIMAL(5,2) NOT NULL DEFAULT 2.50,
        `nilai_zis` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `id_objek_zis` INT(11) NULL,
        `objek_zis` VARCHAR(100) NOT NULL DEFAULT 'ZAKAT',
        `id_metode_pembayaran` INT(11) NULL,
        `via_pembayaran` VARCHAR(100) NOT NULL DEFAULT 'TUNAI',
        `nama_penyetor` VARCHAR(150) NOT NULL,
        `nama_petugas` VARCHAR(150) NOT NULL,
        `user_id` INT(11) NULL,
        `keterangan` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_nomor_surat` (`nomor_surat`),
        KEY `idx_tanggal_surat` (`tanggal_surat`),
        KEY `idx_upz_badan_usaha` (`id_upz_badan_usaha`),
        KEY `idx_upz_dinas` (`id_upz_dinas`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $conn->query($tableSql);

    // Inisialisasi pengaturan default SKZ & BSZIS jika belum ada
    $defaultSettings = [
        'skz_nama_pimpinan' => 'Syukri Rahmat, S.Ag., M.M Inov',
        'skz_jabatan_pimpinan' => 'Ketua BAZNAS Kabupaten Sumbawa',
        'skz_kota' => 'Sumbawa Besar',
        'skz_default_profit_percent' => '6.00',
        'skz_alamat_kantor' => 'Jl. Hasanuddin No. 01 Kelurahan Bugis Kec. Sumbawa',
        'skz_telepon_kantor' => '082 342 569 646'
    ];

    foreach ($defaultSettings as $k => $v) {
        $check = $conn->prepare("SELECT id FROM website_settings WHERE setting_key = ?");
        $check->bind_param('s', $k);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            $stmt = $conn->prepare("INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->bind_param('ss', $k, $v);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Konversi angka bulan ke format Romawi (1 -> I, 9 -> IX)
 */
function angka_ke_romawi(int $angka): string
{
    $romawiMap = [
        12 => 'XII', 11 => 'XI', 10 => 'X', 9 => 'IX',
        8 => 'VIII', 7 => 'VII', 6 => 'VI', 5 => 'V',
        4 => 'IV', 3 => 'III', 2 => 'II', 1 => 'I'
    ];
    return $romawiMap[$angka] ?? 'I';
}

/**
 * Ambil nomor urut dan nomor surat otomatis berikutnya
 */
function get_next_skz_nomor(?mysqli $conn = null, ?string $tgl = null): array
{
    $conn = $conn ?: db();
    $tgl = $tgl ?: date('Y-m-d');
    $time = strtotime($tgl);
    $bulan = (int)date('n', $time);
    $tahun = date('Y', $time);
    $romawi = angka_ke_romawi($bulan);

    // Ambil nomor urut tertinggi tahun ini
    $stmt = $conn->prepare("SELECT MAX(nomor_urut) as max_urut FROM surat_ketetapan_zakat WHERE YEAR(tanggal_surat) = ?");
    $stmt->bind_param('s', $tahun);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $nextUrut = ($row && !empty($row['max_urut'])) ? ((int)$row['max_urut'] + 1) : 1;
    $nomorFormatted = str_pad((string)$nextUrut, 2, '0', STR_PAD_LEFT);
    $nomorSurat = "{$nomorFormatted}/PENGUMPULAN/BAZNAS/{$romawi}/{$tahun}";

    return [
        'urut' => $nextUrut,
        'nomor_surat' => $nomorSurat,
        'romawi' => $romawi,
        'tahun' => $tahun
    ];
}

/**
 * Format terbilang rupiah
 */
function terbilang_rupiah(float|int $nilai): string
{
    $nilai = abs($nilai);
    $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    $hasil = "";

    if ($nilai < 12) {
        $hasil = " " . $huruf[$nilai];
    } else if ($nilai < 20) {
        $hasil = terbilang_rupiah($nilai - 10) . " Belas";
    } else if ($nilai < 100) {
        $hasil = terbilang_rupiah(floor($nilai / 10)) . " Puluh" . terbilang_rupiah($nilai % 10);
    } else if ($nilai < 200) {
        $hasil = " Seratus" . terbilang_rupiah($nilai - 100);
    } else if ($nilai < 1000) {
        $hasil = terbilang_rupiah(floor($nilai / 100)) . " Ratus" . terbilang_rupiah($nilai % 100);
    } else if ($nilai < 2000) {
        $hasil = " Seribu" . terbilang_rupiah($nilai - 1000);
    } else if ($nilai < 1000000) {
        $hasil = terbilang_rupiah(floor($nilai / 1000)) . " Ribu" . terbilang_rupiah($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $hasil = terbilang_rupiah(floor($nilai / 1000000)) . " Juta" . terbilang_rupiah($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $hasil = terbilang_rupiah(floor($nilai / 1000000000)) . " Miliar" . terbilang_rupiah(fmod($nilai, 1000000000));
    } else {
        $hasil = "Nilai terlalu besar";
    }

    return trim($hasil) . " Rupiah";
}

/**
 * Ambil URL logo dokumen surat SKZ & BSZIS
 */
function get_skz_logo_url(): string
{
    $customLogo = get_setting('skz_logo_path');
    if (!empty($customLogo)) {
        $fullPath = __DIR__ . '/../' . ltrim($customLogo, '/\\');
        if (file_exists($fullPath)) {
            return base_url($customLogo);
        }
    }
    return base_url('assets/img/logo_baznas_garuda.svg');
}

/**
 * Ambil URL gambar tanda tangan digital Ketua SKZ jika ada
 */
function get_skz_ttd_url(): ?string
{
    $customTtd = get_setting('skz_ttd_path');
    if (!empty($customTtd)) {
        $fullPath = __DIR__ . '/../' . ltrim($customTtd, '/\\');
        if (file_exists($fullPath)) {
            return base_url($customTtd);
        }
    }
    return null;
}


