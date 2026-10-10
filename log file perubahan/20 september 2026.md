# Dokumen Pembaruan Server: Asisten AI Mode Layar Penuh, Penyajian Data Lengkap & Self-Healing SQL (AI Copilot v2.0)

Dokumen ini mencatat seluruh perubahan, pembaruan, dan pengembangan fitur baru pada sistem SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa per tanggal **20 September 2026**.

---

## 1. Ringkasan Fitur & Peningkatan Utama

*   🖥️ **Halaman Mandiri Asisten AI (Full Page View)**:
    Menyediakan antarmuka kerja Asisten AI berskala penuh di menu navigasi utama (`pages/ai_asisten/index.php`). Terintegrasi serasi dengan layout AdminLTE (Sidebar, Header, Footer) dan standar visual **Paper White** (border `#b8b8b8`, shadow-free, aksen hijau BAZNAS `#0d9f4f`), memberikan ruang pandang yang leluasa untuk membaca tabel data transaksi dan grafik analisis pengumpulan.
*   🪟 **Floating Widget Jendela Penuh (Full Window Floating View)**:
    Widget melayang bawaan kini tampil secara default dalam mode layar penuh (`calc(100vw - 30px)` x `calc(100vh - 30px)`), dilengkapi tombol toggle mode kompak (*is-compact*) serta tombol pintas membuka halaman mandiri penuh.
*   📊 **Penyajian Data Penuh Tanpa Limit Kaku (Limitless Data Presentation)**:
    Menghilangkan batasan paksa `LIMIT 20` pada validator query SQL backend dan melarang AI memotong data dengan limit sempit secara sepihak. Seluruh baris transaksi (100 baris atau lebih) ditampilkan utuh secara transparan dengan batas pengaman teknis database hingga 3.000 baris.
*   🛡️ **Self-Healing SQL & Eliminasi Galat Titik Koma (`;`)**:
    Menyelesaikan masalah tabel transaksi yang kerap tidak muncul (hanya menampilkan teks narasi pembuka). Penyebabnya adalah model AI yang kerap menambahkan titik koma (`;`) di akhir query yang sebelumnya ditolak oleh filter multi-statement atau menyebabkan sintaks SQL rusak saat limit diterapkan. Kini backend otomatis membersihkan *trailing semicolon* dan menyertakan mekanisme kueri *fallback* cerdas mandiri (*self-healing*) jika kueri AI bermasalah.
*   ✍️ **Penyempurnaan Parser Markdown Bold (`**bold**`)**:
    Menyempurnakan parser regex Markdown ke HTML. Simbol bullet (`*` atau `-`) di awal baris diisolasi terlebih dahulu menjadi bulatan entitas (`• `) sehingga tidak mengganggu parsing asteris ganda (`**teks tebal**`), mendukung triple asterisk (`***tebal miring***`), multiline bold, serta penegasan CSS `font-weight: 700 !important` yang tajam dan kontras di mode terang maupun gelap (*Paper Dark*).
*   📜 **Auto-Scroll Otomatis ke Chat Terakhir**:
    Menjamin jendela percakapan yang baru dibuka langsung mendarat mulus dan instan di posisi percakapan paling bawah (terakhir) menggunakan penjadwalan multi-frame `scrollIntoView({ block: 'end' })`.
*   🔒 **Penguatan Keamanan Sistem (Anti-Brute Force, Anti-CSRF, dan Account Lockout)**:
    Menerapkan sistem deteksi brute-force otomatis pada `login.php` menggunakan tabel `login_attempts`. Jika terjadi 5 kali kegagalan login berturut-turut dalam 15 menit, akun/IP akan dikunci otomatis selama 15 menit dengan hitung mundur. Dilengkapi timing delay (300ms) untuk menahan bot dan token anti-CSRF sekali pakai pada form login.
*   🔑 **Kebijakan Kekuatan Kata Sandi Ketat**:
    Mewajibkan kata sandi baru/edit minimal 8 karakter dan harus berupa kombinasi huruf dan angka (`validate_password_strength`) pada penambahan pengguna maupun ubah profil.
*   🚫 **Pencegahan Kebocoran Informasi Sistem (Information Disclosure Masking)**:
    Menonaktifkan `display_errors` secara default pada lingkungan produksi (`DEBUG_MODE = false`) serta menyamarkan pesan galat SQL mentah (`$conn->error`) menggunakan fungsi `safe_db_error()`. Detail teknis dialihkan dengan aman ke `logs/error.log` internal, sementara pengguna menerima notifikasi yang ramah dan aman.
*   🛡️ **Penegakan HTTPS, HSTS & Security Headers**:
    Mengonfigurasi session cookie dengan flag keamanan `HttpOnly`, `SameSite=Lax`, dan `Secure` (saat HTTPS aktif). Mengaktifkan security headers modern (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, dan `Strict-Transport-Security`).
*   🌐 **Optimasi Kompatibilitas `.htaccess`**:
    Menyesuaikan konfigurasi rewrite URL agar bekerja mulus tanpa benturan `RewriteBase` baik pada server produksi berbasis LiteSpeed / Apache (cPanel) maupun pada localhost XAMPP.

---

## 2. Daftar Berkas yang Ditambahkan & Dimodifikasi

### A. Berkas Baru (Created Files)
1.  📂 **`pages/ai_asisten/index.php`** (NEW)
    *   Halaman mandiri Asisten AI berdesain responsif Paper White, dilengkapi bilah navigasi breadcrumb, kartu chat full-page, chip pertanyaan cepat, pemuat riwayat percakapan otomatis, panel status koneksi, dan penampil tabel data transaksi interaktif.
2.  📂 **`log file perubahan/20 september 2026.md`** (NEW)
    *   Dokumentasi resmi seluruh pembaruan sistem per 20 September 2026.

### B. Berkas yang Dimodifikasi (Modified Files)
1.  📂 **`inc/sidebar.php`** (MODIFIED)
    *   Menambahkan item menu **"Asisten AI"** (`nav-icon fas fa-robot`) langsung di bawah menu Dashboard utama dengan badge penanda status aktif.
2.  📂 **`config/ai_engine.php`** (MODIFIED)
    *   Pembaruan fungsi `ai_format_markdown_to_html()`: isolasi bullet point sebelum deteksi bold/italic, penanganan triple asterisk, dan multiline bold formatting.
    *   Pembaruan fungsi `ai_safe_execute_query()`: sanitasi pemotongan *trailing semicolon* (`rtrim($sql, ';')`), penghapusan `LIMIT 20` paksa, dan batas keamanan kuota data 3.000 baris.
    *   Penambahan instruksi pelarangan `LIMIT` kecil pada *System Prompt* Gemini.
    *   Penambahan mekanisme *Self-Healing SQL Fallback*: menjalankan kueri data transaksi tahun berjalan secara otomatis jika kueri AI gagal mengeksekusi data tabel.
3.  📂 **`pages/ai_asisten/chat.php`** (MODIFIED)
    *   Penambahan validasi keberadaan `id_sumber_rekening` di database sebelum eksekusi aksi mutasi untuk mencegah galat integritas relasional data (*foreign key constraint*).
4.  📂 **`inc/widget_ai_copilot.php`** (MODIFIED)
    *   Mengubah ukuran default jendela floating menjadi *Full Window* (`calc(100vw - 30px)`), dengan tombol beralih ke mode melayang kompak (*compact toggle*).
    *   Menambahkan tombol navigasi tautan langsung ke halaman mandiri `pages/ai_asisten/index.php`.
    *   Menambahkan CSS penegasan ketebalan teks tebal `.ai-msg-content strong { font-weight: 700 !important; }`.
    *   Memperbaiki fungsi `scrollToBottom()` menggunakan multi-frame scheduling dan `scrollIntoView`.
5.  📂 **`config/auth.php`** (MODIFIED)
    *   Menambahkan fungsi deteksi HTTPS `is_https_request()` dan emisi header keamanan `emit_security_headers()`.
    *   Mengonfigurasi session cookie dengan parameter `HttpOnly`, `SameSite=Lax`, dan `Secure`.
    *   Menambahkan sistem proteksi brute force `check_brute_force_lockout()`, `record_login_attempt()`, dan validasi password kuat `validate_password_strength()`.
6.  📂 **`config/database.php`** (MODIFIED)
    *   Menonaktifkan `DEBUG_MODE` secara default (`false`) demi keamanan server produksi.
7.  📂 **`config/error_handler.php`** (MODIFIED)
    *   Menonaktifkan `display_errors` di lingkungan non-debug dan menambahkan fungsi `safe_db_error()` untuk masking error database.
8.  📂 **`login.php`** (MODIFIED)
    *   Mengintegrasikan pengecekan lockout brute force, token anti-CSRF, timing delay 300ms, dan pelacak percobaan login.
9.  📂 **`pages/users/index.php` & `pages/profile/index.php`** (MODIFIED)
    *   Menambahkan validasi kata sandi kuat minimal 8 karakter kombinasi huruf dan angka serta masking error SQL.
10. 📂 **`.htaccess`** (MODIFIED)
    *   Menambahkan Security Headers dan mengoptimalkan rewrite rules agar agnostik terhadap subfolder localhost maupun root domain hosting/cPanel.
11. 📂 **`rencana asisten ai.md` & `asisten ai rancangan.md`** (MODIFIED)
    *   Memperbarui dokumentasi teknis dan panduan troubleshooting.
12. 🗄️ **Tabel Database `system_changelog`** (MODIFIED)
    *   Menambahkan entri ID 11: *Asisten AI Mode Layar Penuh & Penyajian Data Transaksi Lengkap (AI Copilot v2.0)*.
    *   Menambahkan entri ID 12: *Penguatan Keamanan Sistem (Anti-Brute Force, Anti-CSRF, Validasi Password & Security Headers)*.

---

## 3. Panduan Penggunaan Fitur Baru Bagi Amil / Pengguna

1.  **Membuka Asisten AI Mode Layar Penuh**:
    *   Klik menu **"Asisten AI"** di sidebar navigasi sebelah kiri, atau
    *   Buka widget floating chat di kanan bawah, lalu klik ikon panah luar di header widget `[Buka Halaman Penuh]`.
2.  **Melihat Seluruh Data Transaksi**:
    *   Ketik pertanyaan seperti: *"Tampilkan semua transaksi tahun 2026"* atau *"Tampilkan transaksi dari UPZ Bulan ini"*.
    *   Sistem akan menampilkan seluruh baris transaksi yang cocok tanpa dipotong batasan limit.
3.  **Membaca Teks Tebal**:
    *   Format penekanan kata menggunakan tanda asteris ganda (`**penting**`) kini tampil dengan ketebalan teks yang jelas dan tegas.
4.  **Memeriksa Pembaruan Sistem (Apa yang Baru?)**:
    *   Buka menu **Pengaturan > Log Pembaruan** atau klik menu changelog di navigasi untuk melihat riwayat pembaruan yang tersimpan di sistem.

---

## 4. Hotfix: Penyelesaian DataTables Ajax Error & Error 500 Analisis/Laporan

### A. Penyebab Masalah
Inkonsistensi data `id_kategori_dana` di tabel `transaksi` (beberapa data lama bernilai integer tunggal `'2'` bukan array JSON `'[2]'`). Hal ini menyebabkan `json_decode` menghasilkan integer, memicu fatal `TypeError` pada fungsi `foreach` dan `in_array` di PHP 8.2+ sehingga memutus respons AJAX DataTables dan menimbulkan galat 500 pada Halaman Analisis dan Laporan.

### B. Kueri Normalisasi Database (phpMyAdmin Server)
Wajib dijalankan pada database server produksi:
```sql
UPDATE transaksi SET id_kategori_dana = CONCAT('[', id_kategori_dana, ']') 
WHERE id_kategori_dana REGEXP '^[0-9]+$';
```

### C. Daftar Berkas Patch yang Diperbarui (11 Berkas)
1. `config/helpers.php` (Fungsi parser aman `parse_kategori_dana_ids`)
2. `pages/transaksi/index.php` (Handler AJAX server-side DataTables)
3. `pages/transaksi/print_bukti.php`
4. `pages/analisis/index.php`
5. `pages/laporan/index.php`
6. `pages/laporan/export.php`
7. `pages/laporan_tahunan/index.php`
8. `pages/laporan_tahunan/export.php`
9. `pages/laporan_kategori/index.php`
10. `pages/laporan_kategori/export.php`
11. `pages/laporan_audit/index.php`

> [!TIP]
> Paket ZIP siap unggah telah dibuat: **`deploy_patch_20_september_2026.zip`**. 
> Anda dapat langsung mengunggah file ZIP ini ke File Manager cPanel pada root direktori aplikasi, lalu klik **Extract** untuk menimpa file yang diperbarui secara instan.
