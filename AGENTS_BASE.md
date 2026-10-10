# Panduan Arsitektur & Desain Pengembangan Web (AGENTS.md Template)

Berkas ini memuat standar arsitektur kode, sistem desain, sistem caching, dan pola interaksi pengguna yang digunakan dalam basis kode proyek ini. Jika Anda menyalin atau menggunakan arsitektur ini untuk aplikasi baru, salin berkas ini ke direktori `.agents/AGENTS.md` pada proyek baru Anda agar agen kecerdasan buatan (AI) dapat memahami basis kodenya secara langsung.

---

## 1. Arsitektur Folder & Teknologi

Aplikasi ini dibangun menggunakan tumpukan teknologi (**Stack**) berikut:
*   **Backend**: PHP Murni (prosedural dengan bantuan fungsi utilitas) & Database MySQLi.
*   **Frontend**: HTML5, Vanilla CSS, Bootstrap 4, jQuery, AdminLTE 3 (dimodifikasi datar), Select2, dan SweetAlert2.
*   **Pola Akses Halaman**: Modular berbasis folder halaman (misal `pages/transaksi/index.php`) yang menyertakan header (`inc/header.php`) dan footer (`inc/footer.php`) global untuk konsistensi layout.

---

## 2. Sistem Desain "Putih Kertas" (Paper White)

Seluruh antarmuka pengguna (UI) wajib mengikuti aturan desain yang minimalis, tegas, dan berkarakter bersih di atas kertas:

*   **Shadow-Free (Tanpa Bayangan)**:
    Hindari penggunaan bayangan bayang (`box-shadow: none !important` atau kelas shadow Bootstrap/AdminLTE). Seluruh panel filter, card, modal, dan dropdown harus datar (flat).
*   **Border Abu-Abu Soft**:
    Semua elemen pembungkus (card, kolom input, textarea, select, dan pembungkus pencarian Select2) wajib menggunakan border tipis berwarna abu-abu sedang yang tegas (`1px solid #b8b8b8`).
*   **Gaya Tombol (Outline Buttons)**:
    Gunakan tombol outline dengan latar belakang putih bersih, border dan warna teks sesuai fungsi/aksinya (success = hijau `#0d9f4f`, primary = biru, warning = kuning, danger = merah), dengan efek transisi warna hover yang sangat lembut.
*   **Hover Tabel**:
    Baris tabel saat disorot (`tr:hover`) harus bertransisi ke warna latar belakang hijau brand lembut (`#f0f7f3`) dengan efek transisi transparan `transition: background-color 0.15s ease`.
*   **Gaya Badge (Outline & Soft Tint)**:
    Seluruh badge status atau kategori (`badge-primary`, `badge-success`, dll.) tidak boleh menggunakan warna solid bawaan Bootstrap/AdminLTE. Badge wajib dibuat datar (shadow-free), menggunakan outline border tipis (`1px solid`), warna teks kontras/gelap, dan latar belakang transparan tipis (soft tinted) disesuaikan dengan jenis badge.

---

## 3. Manajemen Kinerja Tinggi (High-Performance Engine)

### A. Sistem Caching Halaman GET Dinamis
*   Untuk mempercepat pemuatan halaman GET, gunakan modul caching global (`config/cache.php`) yang membungkus output buffering (`ob_start()` di `header.php` dan `ob_get_clean()` di `footer.php`).
*   Halaman cache disimpan dalam folder sementara sebagai berkas HTML matang per user untuk memotong waktu proses menjadi < 5ms.
*   **Invalidasi Cache**: Setiap request modifikasi data (`POST` / CRUD) wajib menghapus seluruh file cache halaman agar data di browser selalu up-to-date.
*   **CSRF Token Regex Replacement**: Token CSRF pada berkas HTML cache wajib diperbarui secara dinamis sebelum dikirim ke browser untuk mencegah kegagalan validasi form.

### B. Standar DataTables Server-Side Processing
Untuk seluruh tabel yang memuat volume data besar (> 100 data), wajib menerapkan pemrosesan server-side menggunakan fungsi pembantu global `datatable_handler()` di `config/helpers.php`:
*   **HTML tbody Kosong**: Kosongkan elemen `<tbody>` pada struktur HTML tabel awal.
*   **REST/AJAX Handler GET**: Letakkan penanganan kueri AJAX di bagian atas berkas utama. Tangkap parameter DataTables (`draw`, `start`, `length`, `search`, `order`) untuk membentuk kueri MySQL ter-paginasi (`LIMIT ? OFFSET ?`) menggunakan prepared statement.
*   **Visual Formatter Server-Side**: Konversikan data mentah menjadi bentuk HTML (seperti badge status dan tombol aksi) langsung di server sebelum dikirim sebagai JSON.

### C. Deferred Rendering (Kasus Laporan)
Jika halaman memerlukan seluruh dataset di browser (misalnya untuk pembuatan grafik atau dashboard visual), namun memuat ribuan baris membuat browser hang:
*   Kirim dataset lengkap sebagai array JSON tunggal ke browser.
*   Inisialisasi DataTable dengan opsi `deferRender: true` dan pasang dataset JSON sebagai sumber data. DataTable hanya akan merender baris HTML pada halaman aktif saja (misal 25 baris), menghemat rendering DOM browser secara signifikan.

---

## 4. Pola Interaksi Form Input Massal (Batch Entry)

Setiap formulir input massal (multi-baris) dinamis wajib menerapkan aturan berikut:
*   **Pencegahan Bug Select2 Kloning**: Jangan menduplikasi baris DOM yang sudah diinisialisasi Select2. Gunakan template string HTML murni dalam Javascript untuk menambahkan baris baru ke tabel.
*   **Penyalinan Tanggal Otomatis**: Secara otomatis salin nilai tanggal dari baris di atasnya ke baris baru saat ditambahkan untuk mempercepat input harian.
*   **Pintasan Keyboard (Shortcuts)**:
    *   Tekan tombol `Tab` di kolom terakhir (Keterangan) baris paling bawah untuk otomatis menambahkan baris baru dan memfokuskan kursor ke kolom awal baris tersebut.
    *   Tekan tombol `Ctrl + Enter` (atau `Cmd + Enter`) untuk melakukan submit form secara instan dari input mana pun.
*   **Auto-Save Draft (LocalStorage)**:
    Gunakan `localStorage` browser untuk menyimpan draf form secara berkala. Berikan notifikasi pemulihan draf jika halaman ditutup tanpa disimpan, dan bersihkan LocalStorage setelah transaksi sukses disimpan ke database.
*   **Pendaftaran Dinamis Muzaki & UPZ (Select2 Tags)**:
    Aktifkan mode `tags: true` pada Select2. Jika pengguna menginput teks baru (bukan ID angka), backend PHP harus mendeteksi nilai string baru tersebut dan menyimpannya secara otomatis ke tabel database master sebelum memproses transaksi utama.

---

## 5. Standar Keamanan & Keandalan

*   **Pencegahan SQL Injection**: Seluruh input dinamis wajib disanitasi menggunakan `prepare` dan `bind_param` MySQLi. Hindari penggabungan string query SQL secara langsung (`$sql = "SELECT ... WHERE col = '$val'"`).
*   **CSRF Protection**: Seluruh form wajib menyertakan token CSRF (`csrf_field()`) dan validasi token tersebut wajib dijalankan di bagian atas handler POST menggunakan `verify_csrf_token()`.
*   **Audit Logging**: Setiap aksi penting (CREATE, UPDATE, DELETE, LOGIN) wajib mencatat riwayat ke database log aktivitas menggunakan fungsi `log_audit()`.
