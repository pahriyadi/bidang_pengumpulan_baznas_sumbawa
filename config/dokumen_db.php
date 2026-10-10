<?php
/**
 * Inisialisasi dan Helper Skema Tabel Modul Dokumen Penerima Bantuan
 */

function ensure_dokumen_tables(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // 1. Tabel Kategori Dokumen
    $conn->query("
        CREATE TABLE IF NOT EXISTS `dokumen_kategori` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `kode_kategori` VARCHAR(50) NOT NULL UNIQUE,
            `nama_kategori` VARCHAR(255) NOT NULL,
            `deskripsi` TEXT NULL,
            `tahun_anggaran` INT NULL,
            `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
            `created_by` INT UNSIGNED NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 2. Tabel Kolom/Header Dinamis per Kategori
    $conn->query("
        CREATE TABLE IF NOT EXISTS `dokumen_kategori_kolom` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_kategori_dokumen` INT UNSIGNED NOT NULL,
            `nama_kolom` VARCHAR(100) NOT NULL,
            `key_kolom` VARCHAR(50) NOT NULL,
            `tipe_data` VARCHAR(20) DEFAULT 'text',
            `opsi_pilihan` TEXT NULL,
            `is_required` TINYINT(1) DEFAULT 0,
            `urutan` INT DEFAULT 1,
            `lebar_kolom` VARCHAR(20) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_kategori` (`id_kategori_dokumen`),
            CONSTRAINT `fk_dokumen_kategori_kolom` FOREIGN KEY (`id_kategori_dokumen`) 
                REFERENCES `dokumen_kategori` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 3. Tabel Data Penerima Manfaat
    $conn->query("
        CREATE TABLE IF NOT EXISTS `dokumen_penerima_data` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_kategori_dokumen` INT UNSIGNED NOT NULL,
            `data_values` LONGTEXT NOT NULL,
            `nominal_total` DECIMAL(15,2) DEFAULT 0.00,
            `catatan_khusus` TEXT NULL,
            `created_by` INT UNSIGNED NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_penerima_kategori` (`id_kategori_dokumen`),
            CONSTRAINT `fk_dokumen_penerima_kategori` FOREIGN KEY (`id_kategori_dokumen`) 
                REFERENCES `dokumen_kategori` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 4. Tabel Dokumentasi Foto Penerima Manfaat
    $conn->query("
        CREATE TABLE IF NOT EXISTS `dokumen_penerima_foto` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_penerima_data` INT UNSIGNED NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `file_thumbnail` VARCHAR(255) NULL,
            `judul_foto` VARCHAR(150) NULL,
            `file_size` INT UNSIGNED DEFAULT 0,
            `urutan` INT DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_foto_penerima` (`id_penerima_data`),
            CONSTRAINT `fk_dokumen_penerima_foto` FOREIGN KEY (`id_penerima_data`) 
                REFERENCES `dokumen_penerima_data` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * Mengambil daftar kategori dokumen aktif
 */
function get_dokumen_kategori_list(mysqli $conn, bool $onlyActive = false): array
{
    ensure_dokumen_tables($conn);
    $where = $onlyActive ? "WHERE status = 'aktif'" : "";
    $sql = "SELECT k.*, 
            (SELECT COUNT(*) FROM dokumen_kategori_kolom WHERE id_kategori_dokumen = k.id) as total_kolom,
            (SELECT COUNT(*) FROM dokumen_penerima_data WHERE id_kategori_dokumen = k.id) as total_penerima,
            (SELECT COALESCE(SUM(nominal_total), 0) FROM dokumen_penerima_data WHERE id_kategori_dokumen = k.id) as total_nominal
            FROM dokumen_kategori k 
            {$where}
            ORDER BY k.tahun_anggaran DESC, k.id DESC";
    $res = $conn->query($sql);
    $list = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $list[] = $row;
        }
    }
    return $list;
}

/**
 * Mengambil detail kategori dokumen berdasarkan ID
 */
function get_dokumen_kategori_by_id(mysqli $conn, int $id): ?array
{
    ensure_dokumen_tables($conn);
    $stmt = $conn->prepare("SELECT * FROM dokumen_kategori WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Mengambil daftar kolom dinamis untuk suatu kategori dokumen
 */
function get_dokumen_kolom_list(mysqli $conn, int $idKategori): array
{
    ensure_dokumen_tables($conn);
    $stmt = $conn->prepare("SELECT * FROM dokumen_kategori_kolom WHERE id_kategori_dokumen = ? ORDER BY urutan ASC, id ASC");
    $stmt->bind_param('i', $idKategori);
    $stmt->execute();
    $res = $stmt->get_result();
    $kolom = [];
    while ($r = $res->fetch_assoc()) {
        $kolom[] = $r;
    }
    $stmt->close();
    return $kolom;
}

/**
 * Mengubah nama kolom menjadi key_kolom yang aman (slug)
 */
function generate_key_kolom(string $nama): string
{
    $key = strtolower(trim($nama));
    $key = preg_replace('/[^a-z0-9_]+/i', '_', $key);
    $key = trim($key, '_');
    return $key ?: 'kolom_' . time();
}

/**
 * Mengambil daftar foto untuk suatu penerima
 */
function get_dokumen_penerima_foto_list(mysqli $conn, int $idPenerima): array
{
    ensure_dokumen_tables($conn);
    $stmt = $conn->prepare("SELECT * FROM dokumen_penerima_foto WHERE id_penerima_data = ? ORDER BY urutan ASC, id ASC");
    $stmt->bind_param('i', $idPenerima);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($r = $res->fetch_assoc()) {
        $list[] = $r;
    }
    $stmt->close();
    return $list;
}

/**
 * Menghitung jumlah foto yang dimiliki suatu penerima
 */
function count_dokumen_penerima_foto(mysqli $conn, int $idPenerima): int
{
    ensure_dokumen_tables($conn);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM dokumen_penerima_foto WHERE id_penerima_data = ?");
    $stmt->bind_param('i', $idPenerima);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_row()[0] ?? 0);
    $stmt->close();
    return $count;
}

/**
 * Proses upload, resize, dan kompresi foto otomatis (Best Practice Performance)
 */
function proses_upload_foto_penerima(array $fileItem, int $idPenerima): ?array
{
    if (empty($fileItem['tmp_name']) || (!is_uploaded_file($fileItem['tmp_name']) && php_sapi_name() !== 'cli')) {
        return null;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileItem['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMimes, true)) {
        return null; // Format tidak didukung
    }

    $uploadDir = __DIR__ . '/../assets/uploads/dokumen_penerima';
    $thumbDir = $uploadDir . '/thumbs';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    if (!is_dir($thumbDir)) {
        @mkdir($thumbDir, 0777, true);
    }

    $uniqueName = 'foto_' . $idPenerima . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $destFile = $uploadDir . '/' . $uniqueName . '.jpg';
    $thumbFile = $thumbDir . '/' . $uniqueName . '_thumb.jpg';

    if (extension_loaded('gd')) {
        $sourceImage = null;
        if ($mime === 'image/jpeg') {
            $sourceImage = @imagecreatefromjpeg($fileItem['tmp_name']);
        } elseif ($mime === 'image/png') {
            $sourceImage = @imagecreatefrompng($fileItem['tmp_name']);
        } elseif ($mime === 'image/webp') {
            $sourceImage = @imagecreatefromwebp($fileItem['tmp_name']);
        }

        if ($sourceImage) {
            // Rotasi otomatis jika orientasi ponsel miring (EXIF)
            if (function_exists('exif_read_data') && $mime === 'image/jpeg') {
                $exif = @exif_read_data($fileItem['tmp_name']);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $sourceImage = imagerotate($sourceImage, 180, 0);
                            break;
                        case 6:
                            $sourceImage = imagerotate($sourceImage, -90, 0);
                            break;
                        case 8:
                            $sourceImage = imagerotate($sourceImage, 90, 0);
                            break;
                    }
                }
            }

            $origWidth = imagesx($sourceImage);
            $origHeight = imagesy($sourceImage);

            // 1. Simpan foto utama (Resize jika > 1280px, kompresi kualitas 80%)
            $maxDim = 1280;
            if ($origWidth > $maxDim || $origHeight > $maxDim) {
                if ($origWidth > $origHeight) {
                    $newWidth = $maxDim;
                    $newHeight = (int)round(($origHeight / $origWidth) * $maxDim);
                } else {
                    $newHeight = $maxDim;
                    $newWidth = (int)round(($origWidth / $origHeight) * $maxDim);
                }
                $targetImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagejpeg($targetImage, $destFile, 80);
                imagedestroy($targetImage);
            } else {
                imagejpeg($sourceImage, $destFile, 80);
            }

            // 2. Simpan thumbnail kecil (~240px proporsional, kualitas 75%)
            $thumbDim = 240;
            if ($origWidth > $origHeight) {
                $tw = $thumbDim;
                $th = (int)round(($origHeight / $origWidth) * $thumbDim);
            } else {
                $th = $thumbDim;
                $tw = (int)round(($origWidth / $origHeight) * $thumbDim);
            }
            $targetThumb = imagecreatetruecolor($tw, $th);
            imagecopyresampled($targetThumb, $sourceImage, 0, 0, 0, 0, $tw, $th, $origWidth, $origHeight);
            imagejpeg($targetThumb, $thumbFile, 75);
            imagedestroy($targetThumb);

            imagedestroy($sourceImage);
        } else {
            @move_uploaded_file($fileItem['tmp_name'], $destFile);
            @copy($destFile, $thumbFile);
        }
    } else {
        @move_uploaded_file($fileItem['tmp_name'], $destFile);
        @copy($destFile, $thumbFile);
    }

    $relPath = 'assets/uploads/dokumen_penerima/' . basename($destFile);
    $relThumb = 'assets/uploads/dokumen_penerima/thumbs/' . basename($thumbFile);
    $fileSize = is_file($destFile) ? filesize($destFile) : 0;

    return [
        'file_path' => $relPath,
        'file_thumbnail' => $relThumb,
        'file_size' => $fileSize
    ];
}

/**
 * Menghapus file gambar fisik dari disk server
 */
function hapus_berkas_foto(?string $filePath, ?string $thumbPath = null): void
{
    $baseDir = dirname(__DIR__);
    if (!empty($filePath)) {
        $realPath = $baseDir . '/' . ltrim(str_replace('\\', '/', $filePath), '/');
        if (is_file($realPath)) {
            @unlink($realPath);
        }
    }
    if (!empty($thumbPath)) {
        $realThumb = $baseDir . '/' . ltrim(str_replace('\\', '/', $thumbPath), '/');
        if (is_file($realThumb)) {
            @unlink($realThumb);
        }
    }
}

