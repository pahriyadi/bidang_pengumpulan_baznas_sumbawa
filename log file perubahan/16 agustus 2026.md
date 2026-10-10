# Dokumen Pembaruan Server: Fitur Klasifikasi Sektoral & Urutan Kustom UPZ

Dokumen ini mencatat seluruh perubahan struktur database (SQL) dan daftar berkas (files) yang dimodifikasi pada sistem pengumpulan BAZNAS Sumbawa. Simpan dokumen ini sebagai panduan saat melakukan deployment/pembaruan di server produksi.

---

## 1. Perubahan Struktur Database (SQL Migrations)

Jalankan kueri SQL berikut pada database server produksi Anda (misal via phpMyAdmin):

```sql
-- 1. Membuat tabel kategori_upz untuk menampung klasifikasi sektoral
CREATE TABLE IF NOT EXISTS kategori_upz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    keterangan TEXT NULL,
    urutan INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Memasukkan data awal kategori sektoral sesuai klasifikasi BAZNAS
INSERT INTO kategori_upz (nama_kategori, keterangan, urutan) VALUES
('Dinas/Instansi', 'UPZ pada Dinas, Badan, Kantor, atau Instansi Pemerintah', 1),
('Sekolah SD', 'UPZ pada Sekolah Dasar / sederajat', 2),
('Sekolah SMP', 'UPZ pada Sekolah Menengah Pertama / sederajat', 3),
('Desa', 'UPZ pada tingkat Desa / Kelurahan', 4),
('Kecamatan', 'UPZ pada tingkat Kecamatan', 5),
('Perusahaan', 'UPZ pada Perusahaan Swasta, BUMN, BUMD, Bank, dll', 6),
('Masjid/Mushalla', 'UPZ pada Masjid atau Mushalla', 7),
('Pengada Barang/Jasa', 'UPZ pada penyedia/rekanan seperti CV, PT, UD, dll', 8);

-- 3. Menambahkan kolom relasi id_kategori_upz pada tabel upz utama
ALTER TABLE upz ADD COLUMN id_kategori_upz INT NULL AFTER nama_upz;
ALTER TABLE upz ADD CONSTRAINT fk_upz_kategori FOREIGN KEY (id_kategori_upz) REFERENCES kategori_upz(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Menambahkan catatan log perubahan (changelog) sistem
INSERT INTO system_changelog (tanggal, kategori, judul, deskripsi) VALUES 
('2026-08-14', 'Fitur Baru', 'Klasifikasi Sektoral & Urutan Kustom UPZ', 'Mengelompokkan UPZ dan Muzaki ke dalam kategori sektoral (Dinas, Sekolah, Desa, Kecamatan, Perusahaan, dll) serta mendukung urutan kustom dan pencarian detail.\n&bull; <strong>Urutan Kustom Sektoral:</strong> Admin dapat mengatur urutan tampilan kategori secara kustom melalui menu Referensi Kategori UPZ.\n&bull; <strong>Integrasi Laporan Tahunan:</strong> Laporan Tahunan menyajikan rincian pengumpulan terpisah per kategori sektoral secara dinamis, dengan kelompok Muzaki Individu di urutan teratas.\n&bull; <strong>Penyatuan Rekapitulasi Sektoral:</strong> Tabel Rekapitulasi Sektoral UPZ per Jenis Pembayaran kini menyatu indah di dalam kartu laporan utama.\n&bull; <strong>Tombol Detail Cerdas:</strong> Menambahkan aksi Detail pada rekap sektoral di Laporan Tahunan dan Laporan Bulanan untuk menelusuri transaksi detail tanpa menghilangkan parameter filter yang sedang berjalan.'),
('2026-08-14', 'Fitur Baru', 'Laporan Audit Pemeriksaan Keuangan (Pemetaan Dinamis)', 'Modul laporan audit khusus yang menyajikan penerimaan hanya dalam 2 klasifikasi (Zakat & Infak/Sedekah).\n&bull; <strong>Pemetaan Dinamis Dependen (4 Tingkatan Dropdown):</strong> Memungkinkan pemindahan dana CSR dan DSKL ke Zakat atau Infak beserta pemilihan sub-kategori tujuan secara fleksibel via formulir filter interaktif.\n&bull; <strong>Auto-Submit Cerdas:</strong> Halaman otomatis melakukan submit dan memuat ulang data saat aturan pemetaan diubah.\n&bull; <strong>Pengelompokan Standar Audit:</strong> Tabel Rekapitulasi Kategori Dana dan Sumber Rekening secara otomatis dikelompokkan ke dalam kategori audit standar (Zakat: 4 Kategori Utama, Infak: 2 Kategori Utama) secara rapi.\n&bull; <strong>Sinkronisasi Ekspor:</strong> Mendukung unduhan CSV, Excel, dan PDF dengan hasil data ter-mapping yang 100% konsisten.');
```

---

## 2. Daftar Berkas yang Diubah (Modified Files)

Berikut adalah daftar berkas yang perlu diunggah ke server produksi setelah diperbarui:

### A. Referensi Data & Kategori UPZ
*   📂 **`config/helpers.php`** (Modified)
    *   **Deskripsi Perubahan**: Menambahkan fungsi helper `get_kategori_upz_list()` untuk mengambil data kategori sektoral UPZ dari database berurutan kustom.
*   📂 **`pages/kategori_upz/index.php`** (NEW)
    *   **Deskripsi Perubahan**: Menambahkan kolom **Urutan** pada grid master, form tambah, dan form edit kategori UPZ. Mengintegrasikan penanganan kolom urutan pada penanganan query penyimpanan database (insert/update).
*   📂 **`pages/upz/index.php`** (Modified)
    *   **Deskripsi Perubahan**: Menambahkan dropdown Kategori Sektoral pada form tambah & edit UPZ, memperbarui logika kueri SQL insert/update untuk merekam relasi kategori sektoral, serta melakukan left join ke `kategori_upz` pada datatable server-side handler.

### B. Laporan Tahunan
*   📂 **`pages/laporan_tahunan/index.php`**
    *   **Deskripsi Perubahan**: 
        *   Menambahkan section laporan **"Rincian Pengumpulan per Kategori UPZ"** dan memisahkan data entitas muzaki/UPZ ke dalam tabel-tabel kecil per kategori.
        *   Menyatukan tabel **"Rekapitulasi Sektoral UPZ per Jenis Pembayaran"** di dalam satu card laporan yang sama (menggunakan dashed divider).
        *   Menempatkan kategori fallback **"Muzaki Individu / Non-UPZ"** sebagai urutan pertama di daftar laporan.
        *   Mendukung sorting kategori dinamis berdasarkan kolom `urutan` dari database.
        *   Menambahkan tombol **Detail** pada tabel rekap sektoral untuk mengalihkan pencarian langsung ke halaman Laporan Transaksi ZIS dengan preservasi filter tanggal/kriteria yang aktif.
*   📂 **`pages/laporan_tahunan/export.php`**
    *   **Deskripsi Perubahan**: Menyelaraskan query SQL, pengurutan, dan format visual data di dalam generator unduhan PDF (HTML layout), Excel, dan CSV agar menyatu rapi dan sama dengan halaman web.

### C. Laporan Transaksi ZIS (Laporan Utama)
*   📂 **`pages/laporan/index.php`** (Modified)
    *   **Deskripsi Perubahan**:
        *   Menambahkan filter query parameter `id_kategori_upz[]` dan penanganan WHERE clause yang aman untuk mendukung penyaringan data sektoral.
        *   Menambahkan card visual **"Rincian Pengumpulan per Kategori UPZ"** di bawah tabel transaksi utama.
        *   Menambahkan kolom **Aksi (Detail)** dengan tombol **Detail** pada tabel **Rekapitulasi Sektoral UPZ per Jenis Pembayaran** dengan kemampuan reload halaman dan memperbarui filter sektoral tanpa menghilangkan kriteria pencarian lain yang sudah terinput.
*   📂 **`pages/laporan/export.php`** (Modified)
    *   **Deskripsi Perubahan**: Menambahkan penerimaan parameter filter `id_kategori_upz[]`, melakukan join ke `upz_m` (UPZ Muzaki), serta menerapkan penyaringan query WHERE klausa yang sesuai agar data hasil unduhan CSV, Excel, dan PDF sinkron dengan filter sektoral halaman utama.

### D. Laporan Audit (Pemeriksaan Keuangan)
*   📂 **`inc/sidebar.php`** (Modified)
    *   **Deskripsi Perubahan**: Menambahkan tautan menu **Laporan Audit** di bawah menu Laporan Tahunan.
*   📂 **`pages/laporan_audit/index.php`** (NEW)
    *   **Deskripsi Perubahan**: Halaman Laporan Audit dengan **Aturan Pemetaan Dependen (4 Dropdown)**. Pengguna dapat memilih jenis asal (CSR/DSKL), sub-kategori asal, jenis tujuan (Zakat/Infak), dan sub-kategori tujuan secara spesifik dan fleksibel. Mengadopsi auto-submit interaktif saat aturan pemetaan diubah serta sinkronisasi ekspor instan. Serta menyajikan Rekapitulasi Kategori Dana dan Sumber Rekening dalam format standar audit (Zakat: 4 Kategori Utama, Infak: 2 Kategori Utama).
*   📂 **`pages/laporan_audit/export.php`** (NEW)
    *   **Deskripsi Perubahan**: Generator ekspor CSV, Excel, dan PDF untuk Laporan Audit yang secara penuh memproses aturan pemetaan kustom yang dibuat pengguna.

---

## 3. Langkah-langkah Pengunggahan (Deployment Steps)

1.  **Backup Database & Code**: Selalu backup database `pengumpulan_baznas` dan direktori web server aktif Anda sebelum melakukan perubahan.
2.  **Jalankan SQL Migrations**: Eksekusi seluruh kueri SQL di atas pada server database produksi Anda.
3.  **Unggah Berkas**: Unggah seluruh berkas di atas ke direktori masing-masing yang sesuai di web server produksi Anda.
4.  **Uji Coba**: Buka kembali browser, atur urutan kategori pada Master Data, periksa Laporan Tahunan, periksa Laporan Audit, lalu coba klik tombol Detail untuk memastikan routing parameter antar-halaman berjalan lancar.
