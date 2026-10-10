# Dokumen Pembaruan Server: Modul Pengelolaan Dokumen Penerima Bantuan (Kolom Dinamis)

Dokumen ini mencatat seluruh perubahan struktur database (SQL) dan daftar berkas (files) yang ditambahkan dan dimodifikasi pada sistem SIM Pengumpulan ZIS BAZNAS Sumbawa. Simpan dokumen ini sebagai panduan saat melakukan deployment/pembaruan di server produksi.

---

## 1. Perubahan Struktur Database (SQL Migrations)

Jalankan kueri SQL berikut pada database server produksi (misal via phpMyAdmin):

```sql
-- 1. Tabel Kategori Dokumen (Master Program / Template)
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Kolom Header Dinamis per Kategori
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Data Penerima Manfaat
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Foto Dokumentasi Penerima Manfaat (Multi-Foto Teroptimasi)
CREATE TABLE IF NOT EXISTS `dokumen_penerima_foto` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_penerima_data` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_thumbnail` VARCHAR(255) NULL,
  `judul_foto` VARCHAR(255) NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `urutan` INT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_foto_penerima` (`id_penerima_data`),
  CONSTRAINT `fk_dokumen_penerima_foto` FOREIGN KEY (`id_penerima_data`) 
    REFERENCES `dokumen_penerima_data` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Catatan Log Perubahan (Changelog)
INSERT INTO system_changelog (tanggal, kategori, judul, deskripsi) VALUES 
('2026-09-12', 'Fitur Baru', 'Modul Dokumen Program & Dokumentasi Multi-Foto Terkompresi', 'Pengembangan menyeluruh modul dokumen penerima bantuan program BAZNAS Kabupaten Sumbawa:
&bull; <strong>Header Kolom Dinamis:</strong> Master template dan kategori dokumen bantuan dengan kolom custom (Teks, Nominal Rupiah auto-separator, Angka, Tanggal, Textarea, Select pilihan) dan template cepat (Standar BAZNAS, Gerobak Usaha, Mahyani).
&bull; <strong>Dokumentasi Multi-Foto Cerdas & Ringan:</strong> Fitur simpan multi-foto (foto 1, 2, 3, dst.) per penerima manfaat dengan pipeline kompresi otomatis PHP GD (downscale maks 1280px, kualitas 80%, rotasi otomatis EXIF ponsel, pembuatan thumbnail cepat ~240px) sehingga tidak membebani server/hosting.
&bull; <strong>Galeri Interaktif & Lightbox:</strong> Modal galeri foto per penerima untuk melihat pratinjau, mengunggah foto susulan kapan saja, dan menghapus foto lama dengan pembersihan berkas fisik secara otomatis.
&bull; <strong>Cetak Kop Resmi + Lampiran Foto:</strong> Format cetak dokumen ber-Kop BAZNAS resmi beserta lampiran foto lapangan siap print & tanda tangan pimpinan.
&bull; <strong>Ekspor Excel:</strong> Fitur unduh lembar dokumen ke Microsoft Excel.');
```

---

## 2. Daftar Berkas yang Ditambahkan & Diubah (Files Created & Modified)

Berikut adalah daftar berkas yang perlu diunggah ke server produksi:

### A. Berkas Konfigurasi & Inisialisasi Database
*   📂 **`config/dokumen_db.php`** (NEW)
    *   Fungsi inisialisasi tabel otomatis `ensure_dokumen_tables()` termasuk tabel `dokumen_penerima_foto`.
    *   Fungsi pembacaan kategori, header kolom, dan query data penerima.
    *   Fungsi pipeline kompresi gambar `proses_upload_foto_penerima()` (auto-resize max 1280px, auto-rotate EXIF, thumbnail generation 240px, penyimpanan disk berkas).
    *   Fungsi `hapus_berkas_foto()` untuk pembersihan fisik berkas foto dan thumbnail saat data dihapus.
*   📂 **`config/helpers.php`** (MODIFIED)
    *   Memuat `config/cache.php` secara terpusat agar seluruh proses write (POST) memicu pembersihan cache halaman secara konsisten di seluruh modul.
*   📂 **`config/cache.php`** (MODIFIED)
    *   Mengecualikan halaman dokumen (`dokumen_kategori`, `dokumen_program`, `cetak.php`, `export_excel.php`) dari caching HTML statis agar data yang baru ditambah, diedit, atau dihapus langsung ter-refresh secara real-time. Menambahkan pengamanan null coalescing operator `??` pada `$_SERVER['REQUEST_METHOD']`.
*   📂 **`index.php`** (MODIFIED)
    *   Memanggil require `config/dokumen_db.php` dan menjalankan `ensure_dokumen_tables($conn)` agar database server langsung ter-inisialisasi otomatis saat pertama kali dibuka.
*   📂 **`inc/sidebar.php`** (MODIFIED)
    *   Menambahkan kelompok menu navigasi baru **"Dokumen Bantuan"** berisi tautan ke:
        1. **Lembar Penerima** (`pages/dokumen_program/index.php`)
        2. **Kategori & Kolom** (`pages/dokumen_kategori/index.php`)

### B. Modul 1: Kategori Dokumen & Header Kolom Dinamis
*   📂 **`pages/dokumen_kategori/index.php`** (NEW)
    *   Manajemen master kategori dokumen (kode kategori, nama kategori, tahun anggaran, deskripsi, status aktif/nonaktif).
    *   Informasi jumlah kolom dan jumlah penerima terdaftar.
*   📂 **`pages/dokumen_kategori/kolom.php`** (NEW)
    *   Antarmuka pengaturan header kolom dinamis: label nama kolom, kunci key, tipe input (teks, number, currency nominal rupiah, date, textarea, dropdown select), urutan, dan status wajib/opsional.
    *   Fitur tombol *Template Header Cepat* (Standar BAZNAS, Gerobak Usaha, Mahyani Bedah Rumah).

### C. Modul 2: Dokumen Program (Lembar Kerja Penerima Bantuan & Multi-Foto)
*   📂 **`pages/dokumen_program/index.php`** (NEW)
    *   Lembar kerja dokumen dengan pemilih kategori dropdown di bagian atas.
    *   Tabel merender header kolom dinamis serta kolom **Dokumentasi** dengan indikator jumlah foto aktif.
    *   Form modal dinamis untuk Tambah & Edit data penerima dengan dukungan unggah multi-foto awal.
    *   Modal Galeri Foto interaktif (unggah multi-foto susulan, pratinjau thumbnail kartu, tombol hapus foto).
    *   Modal Lightbox Preview resolusi tinggi dengan metadata ukuran berkas dan tombol unduh.
    *   Baris Kaki Tabel (`tfoot`) yang otomatis menghitung dan menjumlahkan Total Nominal Bantuan.
*   📂 **`pages/dokumen_program/cetak.php`** (NEW)
    *   Template cetak dokumen resmi berformat Kop Surat BAZNAS Kabupaten Sumbawa (lengkap dengan logo, alamat, garis kop ganda, tabel data rapi, dan ruang tanda tangan pengesahan Ketua & Pelaksana Program).
    *   Lampiran otomatis dokumen foto dokumentasi penerima manfaat lapangan berbingkai rapi dan siap print.
*   📂 **`pages/dokumen_program/export_excel.php`** (NEW)
    *   Fitur unduh dokumen lembar kerja ke format Microsoft Excel (.xls) dengan format kolom dan total baris yang rapi.
*   📂 **`assets/uploads/dokumen_penerima/`** & **`assets/uploads/dokumen_penerima/thumbs/`** (NEW DIRECTORY)
    *   Folder penyimpanan berkas gambar terkompresi dan thumbnail di server lokal/hosting.

