# Dokumentasi Lengkap & Rencana Arsitektur Modul Dokumen Program Bantuan (Kolom Dinamis & Multi-Foto Terkompresi)

Dokumen ini memuat dokumentasi teknis menyeluruh, arsitektur data, logika algoritma kompresi, daftar berkas yang ditambahkan/diubah, panduan instalasi server/hosting, serta cetak biru (*blueprint*) untuk menerapkan arsitektur ini ke sistem web lainnya.

---

## DAFTAR ISI
1. [Latar Belakang & Konsep Solusi](#1-latar-belakang--konsep-solusi)
2. [Daftar Lengkap Berkas (Files Added & Modified)](#2-daftar-lengkap-berkas-files-added--modified)
3. [Panduan Deployment di Server / Web Hosting](#3-panduan-deployment-di-server--web-hosting)
4. [Arsitektur Basis Data (Hybrid Schema-Flexible & Relasional)](#4-arsitektur-basis-data-hybrid-schema-flexible--relasional)
5. [Logika & Pipeline Kompresi Foto Otomatis (GD Engine)](#5-logika--pipeline-kompresi-foto-otomatis-gd-engine)
6. [Logika Frontend & Rendering Dinamis](#6-logika-frontend--rendering-dinamis)
7. [Format Output: Cetak Kop Resmi & Ekspor Excel](#7-format-output-cetak-kop-resmi--ekspor-excel)
8. [Panduan Replikasi ke Sistem Website Lain](#8-panduan-replikasi-ke-sistem-website-lain)

---

## 1. Latar Belakang & Konsep Solusi

### A. Permasalahan di Lapangan
BAZNAS Kabupaten Sumbawa mendistribusikan puluhan ragam program bantuan ZIS (contoh: *Bantuan Gerobak Usaha UMKM, Bedah Rumah / Mahyani, Modal Bergulir, Bantuan Ternak, Beasiswa Pendidikan, Biaya Pengobatan, dll.*). Tiap program memiliki kebutuhan field yang sangat unik:
- **Gerobak Usaha**: Butuh *Nama, NIK, No KK, Lokasi Jualan, Jenis Usaha, Tanggal Serah Terima, Foto Gerobak*.
- **Bedah Rumah (Mahyani)**: Butuh *Kepala Keluarga, Status Kepemilikan Tanah, Luas Bangunan, Anggaran, Foto Rumah Awal & Progres*.
- **Beasiswa**: Butuh *Nama Siswa/Mahasiswa, Kampus/Sekolah, Semester, Jurusan, Nominal, Foto Serah Terima*.

Jika setiap program baru mewajibkan programmer menambah tabel atau kolom di database (`ALTER TABLE`), sistem menjadi rapuh, database kembung, dan amil tidak mandiri. Selain itu, foto dokumentasi dari kamera ponsel modern (5 MB – 15 MB per foto) akan menghabiskan ruang hosting dan melumpuhkan pemuatan web jika tidak ditangani dengan benar.

### B. Solusi yang Diterapkan
1. **Header Kolom Dinamis (*Dynamic Custom Fields*)**: Amil dapat membuat kolom header sendiri (Teks, Angka, Nominal Rupiah, Tanggal, Textarea, Pilihan Dropdown) tanpa menyentuh kode program.
2. **Penyimpanan Hibrida JSON**: Nilai baris penerima disimpan dalam payload JSON terstruktur, menjamin skalabilitas tanpa batas kolom.
3. **Dokumentasi Multi-Foto Otomatis Terkompresi**:
   - Foto disimpan di disk server (bukan database BLOB).
   - Pemrosesan gambar otomatis dengan PHP GD: *downscale* proporsional (maks 1280px), kompresi 80% (menghasilkan berkas ~100–150 KB), koreksi orientasi sensor kamera ponsel (EXIF Auto-Rotate), dan pembuatan thumbnail (~240px).
   - Galeri interaktif & modal lightbox pratinjau penuh.
   - Pembersihan berkas fisik otomatis saat data/foto dihapus.
4. **Cetak Kop Resmi BAZNAS + Lampiran Dokumentasi**: Dokumen ber-Kop resmi siap cetak A4/Folio lengkap dengan lampiran galeri foto dokumentasi lapangan untuk audit SPJ.

---

## 2. Daftar Lengkap Berkas (Files Added & Modified)

Berikut adalah daftar seluruh berkas yang dibangun dan disesuaikan dalam proyek ini:

| No | Lokasi Berkas | Status | Peran & Deskripsi Fungsional |
|---|---|---|---|
| 1 | [`config/dokumen_db.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/config/dokumen_db.php) | **BARU** | Skrip inisialisasi tabel otomatis (`ensure_dokumen_tables`), pembacaan metadata, fungsi upload & kompresi foto GD (`proses_upload_foto_penerima`), dan pembersih berkas fisik (`hapus_berkas_foto`). |
| 2 | [`pages/dokumen_kategori/index.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_kategori/index.php) | **BARU** | Antarmuka manajemen master kategori dokumen (CRUD Kategori, Kode Program, Tahun Anggaran, status aktif/nonaktif). |
| 3 | [`pages/dokumen_kategori/kolom.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_kategori/kolom.php) | **BARU** | Antarmuka konfigurasi header kolom dinamis: tambah, edit, hapus, urutan, tipe data, serta tombol *Template Header Cepat* (Standar BAZNAS, Gerobak Usaha, Mahyani). |
| 4 | [`pages/dokumen_program/index.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_program/index.php) | **BARU** | Lembar kerja data penerima bantuan: dropdown pemilih program, tabel dinamis, form modal adaptif, kolom status foto, modal galeri multi-foto interaktif, modal lightbox preview, dan hitung total nominal otomatis. |
| 5 | [`pages/dokumen_program/cetak.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_program/cetak.php) | **BARU** | Template cetak resmi ber-Kop Surat BAZNAS Kabupaten Sumbawa, tabel data rapi bergaris, kolom tanda tangan pengesahan, dan lampiran galeri foto dokumentasi lapangan. |
| 6 | [`pages/dokumen_program/export_excel.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_program/export_excel.php) | **BARU** | Generator ekspor lembar kerja dokumen ke format Microsoft Excel (.xls) dengan heading dan baris total nominal. |
| 7 | `assets/uploads/dokumen_penerima/` | **BARU (DIR)** | Direktori disk penyimpanan foto dokumentasi terkompresi (~100-150 KB). |
| 8 | `assets/uploads/dokumen_penerima/thumbs/` | **BARU (DIR)** | Direktori disk penyimpanan thumbnail foto (~240px) untuk kartu galeri dan tabel. |
| 9 | [`inc/sidebar.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/inc/sidebar.php) | **MODIFIKASI** | Penambahan kelompok menu navigasi sidebar *"Dokumen Bantuan"* (`Lembar Penerima` & `Kategori & Kolom`). |
| 10 | [`index.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/index.php) | **MODIFIKASI** | Panggilan require `config/dokumen_db.php` dan eksekusi `ensure_dokumen_tables($conn)` agar migrasi tabel berjalan otomatis saat aplikasi diakses. |
| 11 | [`config/helpers.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/config/helpers.php) | **MODIFIKASI** | Pemuatan `config/cache.php` terpusat agar pembersihan cache berjalan merata. |
| 12 | [`config/cache.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/config/cache.php) | **MODIFIKASI** | Pengecualian halaman dokumen dari caching statis agar perubahan data real-time, dan pengamanan null coalescing operator `??`. |
| 13 | [`log file perubahan/12 september 2026.md`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/log%20file%20perubahan/12%20september%202026.md) | **MODIFIKASI** | Dokumentasi log perubahan, SQL migrations, dan panduan upload hosting. |

---

## 3. Panduan Deployment di Server / Web Hosting

Jika Anda ingin memindahkan atau menerapkan fitur ini di server cPanel / VPS / Cloud Hosting, ikuti langkah berikut:

### Langkah 1: Aktifkan Ekstensi PHP GD & Exif
Fitur kompresi gambar membutuhkan ekstensi `gd` dan `exif`.
- **Pada cPanel**: Masuk ke menu **Select PHP Version** -> Tab **Extensions** -> Centang `gd` dan `exif`.
- **Pada VPS (Ubuntu/Debian)**:
  ```bash
  sudo apt-get install php-gd php-mbstring
  sudo systemctl restart apache2 # atau php-fpm
  ```
- **Pada XAMPP Windows**: Buka `php.ini`, pastikan baris `extension=gd` tidak diawali tanda titik koma `;`.

### Langkah 2: Eksekusi SQL Migration (atau Biarkan Otomatis)
Sistem telah dilengkapi fungsi `ensure_dokumen_tables($conn)` di [`dokumen_db.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/config/dokumen_db.php). Begitu berkas diunggah dan website dibuka, tabel akan dibuat otomatis. Namun jika ingin menjalankan manual via **phpMyAdmin**, jalankan SQL berikut:

```sql
-- 1. Tabel Master Kategori / Program
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

-- 2. Tabel Konfigurasi Header Kolom Dinamis
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

-- 3. Tabel Baris Data Penerima Manfaat
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

-- 4. Tabel Relasi Foto Dokumentasi Penerima Manfaat
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
```

### Langkah 3: Pengaturan Hak Akses Folder Unggahan (Permissions)
Pastikan web server memiliki izin tulis (*write permission*) pada folder unggahan:
```bash
chmod -R 775 assets/uploads/dokumen_penerima/
# atau di cPanel File Manager: Permissions -> 755
```

---

## 4. Arsitektur Basis Data (Hybrid Schema-Flexible & Relasional)

Mengapa arsitektur ini dipilih dan bukan EAV (*Entity-Attribute-Value*) murni ataupun skema relasional kaku?

```
┌───────────────────────────┐       1 : N       ┌──────────────────────────────┐
│     dokumen_kategori      │ ────────────────> │    dokumen_kategori_kolom    │
│  (Master Program Bantuan) │                   │  (Definisi Kolom Dinamis)    │
└─────────────┬─────────────┘                   └──────────────────────────────┘
              │ 1 : N
              ▼
┌───────────────────────────┐       1 : N       ┌──────────────────────────────┐
│   dokumen_penerima_data   │ ────────────────> │    dokumen_penerima_foto     │
│   (Row Penerima Manfaat)  │                   │ (File Path, Thumb, Size, Ur) │
│ [Payload: JSON Key-Value] │                   └──────────────────────────────┘
└───────────────────────────┘
```

### Keunggulan Arsitektur:
1. **Zero Database Alteration**: Penambahan kategori baru (misal: "Bantuan Perahu Nelayan") tidak membutuhkan perubahan DDL database (`ALTER TABLE`). Amil cukup menambah kolom header di antarmuka web.
2. **Kueri Sangat Cepat (Single-Row Fetch)**: Seluruh atribut baris penerima dibaca dalam 1 kueri (`SELECT * FROM dokumen_penerima_data WHERE id_kategori_dokumen = ?`), kemudian di-`json_decode()` di PHP. Tidak ada operasi puluhan baris `JOIN` seperti kelemahan EAV tradisional.
3. **Database Sangat Ramping**: Database tidak menyimpan biner gambar (*BLOB*), melainkan hanya string path relatif berkas (`assets/uploads/...`), menjaga performa backup harian SQL tetap cepat dalam hitungan detik.

---

## 5. Logika & Pipeline Kompresi Foto Otomatis (GD Engine)

Alur kompresi gambar diimplementasikan dalam fungsi `proses_upload_foto_penerima()` di [`config/dokumen_db.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/config/dokumen_db.php).

### A. Diagram Alur Pemrosesan Berkas:

```
[ Berkas Foto Kamera HP ] (5 MB - 15 MB)
          │
          ▼
1. Validasi MIME Type Asli (finfo_file: image/jpeg, image/png, image/webp)
          │
          ▼
2. Baca Orientasi EXIF (Deteksi kamera miring/tegak, putar otomatis via imagerotate)
          │
          ▼
3. Perhitungan Dimensi Baru (Preserve Aspect Ratio):
   • Jika Lebar atau Tinggi > 1280px ──> Downscale proporsional (max 1280px)
   • Jika sudah <= 1280px ────────────> Pertahankan ukuran asli
          │
          ▼
4. Resampling Berkualitas Tinggi (imagecopyresampled dengan kanvas 24-bit TrueColor)
          │
          ▼
5. Output Berkas Utama: JPEG Kualitas 80% ──> Ukuran berkas turun menjadi ~100 - 150 KB
          │
          ▼
6. Pembuatan Thumbnail Cepat:
   • Downscale proporsional ke lebar 240px
   • Simpan ke folder `thumbs/` ──> Ukuran berkas ~10 - 20 KB
          │
          ▼
7. Catat metadata ke MySQL: path utama, path thumbnail, ukuran bytes, dan timestamp.
```

### B. Kode Inti Pipeline Kompresi:
```php
// Penentuan rasio skala proporsional
$maxDim = 1280;
$newW = $origW;
$newH = $origH;
if ($origW > $maxDim || $origH > $maxDim) {
    if ($origW >= $origH) {
        $newW = $maxDim;
        $newH = (int)round(($origH / $origW) * $maxDim);
    } else {
        $newH = $maxDim;
        $newW = (int)round(($origW / $origH) * $maxDim);
    }
}

// Resampling ke kanvas TrueColor
$destImg = imagecreatetruecolor($newW, $newH);
// Tangani transparansi PNG/WebP agar latar tidak menjadi hitam
$white = imagecolorallocate($destImg, 255, 255, 255);
imagefilledrectangle($destImg, 0, 0, $newW, $newH, $white);
imagecopyresampled($destImg, $sourceImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

// Simpan JPEG terkompresi kualitas 80%
imagejpeg($destImg, $destFilePath, 80);
```

---

## 6. Logika Frontend & Rendering Dinamis

Antarmuka pada [`pages/dokumen_program/index.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_program/index.php) bekerja secara reaktif menggunakan perpaduan PHP Server-Side Rendering dan jQuery AJAX:

### A. Adaptasi Input Form Sesuai Kolom Header
Saat tombol *Tambah Penerima* atau *Edit* diklik, modal me-looping konfigurasi kolom:
- Jika kolom tipe **`currency`**: Diberikan prefix `Rp`, dipasang event `onkeyup="formatRupiahInput(this)"`, dan nilai dijumlahkan otomatis di baris `<tfoot>` tabel.
- Jika tipe **`select`**: Diurai opsi pilihannya berdasarkan daftar koma (`explode(',', $opsi)`).
- Jika tipe **`date`**: Diberikan datepicker format `YYYY-MM-DD`.
- Jika tipe **`textarea`**: Diberikan textarea 2-3 baris.

### B. Pengiriman Multi-File Tanpa Refresh Halaman
Formulir menggunakan `FormData` JavaScript untuk mengizinkan upload berkas biner secara asinkron:
```javascript
const formData = new FormData(this);
$.ajax({
    url: ajaxDocUrl,
    type: 'POST',
    data: formData,
    processData: false,  // Mencegah jQuery mengubah FormData menjadi string query
    contentType: false,  // Mengizinkan browser mengatur multipart/form-data boundary
    dataType: 'json',
    success: function(res) {
        // Tampilkan notifikasi & reload tabel data
    }
});
```

### C. Modal Galeri & Lightbox Preview
- Tiap penerima memiliki tombol **`[📷 N Foto]`** yang menampilkan jumlah foto terdaftar.
- Mengklik tombol membuka `#modalGaleriFoto`, memuat daftar kartu foto via AJAX (`get_galeri_foto`).
- Amil dapat mengunggah foto tambahan kapan saja dengan formulir dropzone di dalam modal.
- Kartu foto dilengkapi tombol **Lihat** (membuka modal `#modalPreviewFoto` berukuran besar) dan tombol **Hapus** (menghapus data database sekaligus berkas fisik di server).

---

## 7. Format Output: Cetak Kop Resmi & Ekspor Excel

### A. Cetak Dokumen Resmi ([`cetak.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_program/cetak.php))
- **Kop Surat BAZNAS Kabupaten Sumbawa**: Lengkap dengan logo, alamat kantor, garis kop ganda (tebal-tipis), dan judul SK/Daftar resmi.
- **Tabel Data Bergaris Solid**: Format angka/rupiah rata kanan, nomor urut rata tengah, teks rata kiri.
- **Ruang Pengesahan**: Dua kolom tanda tangan resmi untuk Ketua BAZNAS dan Pelaksana Program / Staf Pendistribusian.
- **Lampiran Foto Lapangan**: Halaman tambahan otomatis berformat grid 2-kolom (`page-break-before: always;`) yang merender seluruh foto dokumentasi penerima manfaat beserta nama, keterangan, dan tanggal serah terima.

### B. Ekspor Microsoft Excel ([`export_excel.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/pages/dokumen_program/export_excel.php))
- Mengirimkan header MIME `application/vnd.ms-excel` dengan format tabel HTML berstruktur.
- Otomatis membuat baris judul program, header kolom dinamis, isi data, dan baris **TOTAL** di bagian bawah.

---

## 8. Panduan Replikasi ke Sistem Website Lain

Jika Anda ingin menerapkan arsitektur *Dynamic Fields + Compressed Multi-Photo Gallery* ini ke aplikasi web lain (misal: Sistem Arsip Surat, SIM Aset/Inventaris, Data Anggota, atau Rekam Medis), ikuti 5 langkah modular berikut:

### Langkah 1: Struktur Tabel Standar (Copy-Paste)
Cukup buat 4 tabel standar:
1. `ref_kategori` (id, nama_kategori, kode, deskripsi)
2. `ref_kategori_field` (id, id_kategori, nama_field, field_key, tipe, opsi, urutan, is_required)
3. `data_entitas` (id, id_kategori, data_values [LONGTEXT JSON], created_at)
4. `data_entitas_foto` (id, id_entitas, file_path, file_thumbnail, judul_foto, file_size, created_at)

### Langkah 2: Helper Kompresi GD Mandiri (Standalone PHP Function)
Salin fungsi `proses_upload_foto_penerima()` dari [`config/dokumen_db.php`](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/config/dokumen_db.php) ke file helper proyek Anda. Sesuaikan konstanta direktori penyimpanan:
```php
$uploadDir = __DIR__ . '/uploads/nama_modul';
$thumbDir  = $uploadDir . '/thumbs';
```

### Langkah 3: Render Header & Baris Tabel Dinamis
Di halaman utama tabel, ambil definisi kolom dari tabel definisi:
```php
$kolomList = $conn->query("SELECT * FROM ref_kategori_field WHERE id_kategori = $idKat ORDER BY urutan ASC")->fetch_all(MYSQLI_ASSOC);

// Render THEAD:
foreach ($kolomList as $c) {
    echo "<th>" . htmlspecialchars($c['nama_field']) . "</th>";
}

// Render TBODY:
$data = $conn->query("SELECT * FROM data_entitas WHERE id_kategori = $idKat")->fetch_all(MYSQLI_ASSOC);
foreach ($data as $row) {
    $vals = json_decode($row['data_values'], true);
    echo "<tr>";
    foreach ($kolomList as $c) {
        $val = $vals[$c['field_key']] ?? '-';
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }
    echo "</tr>";
}
```

### Langkah 4: Render Form Input Dinamis
Looping array `$kolomList` yang sama pada modal dialog penambahan/pengubahan data, sesuaikan tag input `<input type="text">`, `<input type="date">`, `<textarea>`, atau `<select>`.

### Langkah 5: Hubungkan Galeri Foto dengan Entitas
Tambahkan tombol pada baris tabel:
```html
<button onclick="bukaGaleri(<?= $row['id'] ?>)">Galeri Foto</button>
```
Gunakan AJAX untuk memanggil berkas handler yang mengembalikan JSON daftar foto penerima (`SELECT * FROM data_entitas_foto WHERE id_entitas = ?`).

---

**Selesai.** Seluruh rancangan modul, arsitektur data, dan dokumentasi implementasi siap dijadikan rujukan operasional serta pengembangan berkelanjutan.
