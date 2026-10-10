# Dokumen Pembaruan Server: Asisten AI Intelijen (AI Data Copilot SIM BAZNAS)

Dokumen ini mencatat seluruh berkas yang ditambahkan dan dimodifikasi untuk mengaktifkan **Asisten AI Eksekutif & Intelijen Data** pada SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa.

---

## 1. Konsep & Fitur Baru yang Diaktifkan
*   🤖 **Pencarian Transaksi Multi-Header Cerdas**: Mampu mencari data transaksi di tabel transaksi berdasarkan seluruh kombinasi field (Muzaki, UPZ, Jenis ZIS, Sumber Rekening/Bank, Nominal, dan Rentang Tanggal) menggunakan bahasa alami sehari-hari.
*   📊 **Rekapitulasi RKAT & Laporan Real-Time**: Perhitungan otomatis capaian target pengumpulan tahunan (`target_pengumpulan_tahunan`), persentase pencapaian, komposisi per kategori dana (Zakat vs Infak vs DSKL), dan distribusi rekening bank.
*   🏢 **Intelijen UPZ & Muzaki**: Menampilkan peringkat kontributor terbesar (*Top UPZ & Top Muzaki*) serta deteksi dini (*early warning*) UPZ yang belum menyetorkan ZIS dalam 60 hari terakhir.
*   📦 **Penelusuran Dokumen Bantuan Program**: Mengetahui jumlah mustahik/penerima manfaat yang terdaftar dan total anggaran bantuan yang dicatat pada modul dokumen program.
*   🔒 **Keamanan Berlapis (Strict Read-Only)**: Hanya mengizinkan eksekusi query `SELECT` dengan parameter binding, memblokir mutlak kueri modifikasi (`INSERT`, `UPDATE`, `DELETE`, `DROP`, dll.).
*   ⚡ **Hybrid Cloud & Local Fallback**: Memanfaatkan Google Gemini AI (`gemini-3.5-flash-lite`) dengan fallback otomatis ke *Internal Local Rule Engine* saat internet lambat/offline.
*   📄 **Pembaca PDF Rekening Koran (0 Token - Open Source)**: Mampu memindai dan mengekstrak dokumen rekening koran bank format PDF (Bank NTB Syariah, Mandiri, BRI, BNI, BSI) secara lokal menggunakan `smalot/pdfparser` tanpa memakan kuota token AI (0 Token). Mendeteksi otomatis Bank, Nomor Rekening, Periode Bulan, Mutasi Debet, Saldo, dan **Total Mutasi Kredit**, serta langsung melakukan rekonsiliasi otomatis dengan transaksi sistem (Klop vs Selisih).

---

## 2. Daftar Berkas yang Ditambahkan & Diubah

### A. Berkas Baru (Created Files)
1.  📂 **`config/ai_config.php`** (NEW)
    *   Pengaturan API Key Google Gemini, urutan model fallback, parameter batas waktu cURL, dan batasan keamanan kata kunci query.
2.  📂 **`config/ai_engine.php`** (NEW)
    *   Core Engine AI: pembangun konteks skema database, pemanggil API Gemini via cURL, parser intent, *Safe Query Builder*, pembuat tabel mini interaktif, dan *Internal Local Engine Fallback*.
3.  📂 **`config/pdf_parser.php`** (NEW)
    *   Engine pembaca PDF open-source (Smalot\PdfParser): ekstraksi teks cerdas, detektor bank/rekening/periode/mutasi kredit, komparator rekonsiliasi dengan tabel `transaksi` dan `rekening_koran`, serta generator Paper-White HTML Card.
4.  📂 **`pages/ai_asisten/chat.php`** (NEW)
    *   Endpoint AJAX backend untuk otentikasi sesi amil, proteksi rate-limiting, handler `upload_pdf`, handler `save_rk_statement`, dan pengiriman respons JSON.
5.  📂 **`inc/widget_ai_copilot.php`** (NEW)
    *   Antarmuka pengguna widget floating chat bergaya desain "Putih Kertas" (Paper White), tombol unggah klip kertas (`fas fa-paperclip`), tombol chip pertanyaan cepat, animasi titik berpikir, dan integrasi shortcut keyboard `Alt + A`.
6.  📂 **`config/system_doctor.php`** (NEW)
    *   Engine diagnostik mandiri (0 Token): kalkulator skor kesehatan komposit, audit fragmentasi/indeks database, evaluasi lingkungan PHP hosting, penganalisis error log, dan eksekutor perbaikan aman (*Safe 1-Click Repair*).
7.  📂 **`pages/settings/diagnostics.php`** (NEW)
    *   Dasbor GUI Dokter Sistem & Database dengan gaya desain "Putih Kertas" (Paper White), tab rincian tabel database, konfigurasi server, dan analisis log error interaktif dengan tombol konsultasi solusi AI.
8.  📂 **`rencana asisten ai.md`** (NEW)
    *   Dokumentasi teknis menyeluruh konsep arsitektur AI copilot.

### B. Berkas yang Dimodifikasi (Modified Files)
1.  📂 **`composer.json`** & **`vendor/`** (MODIFIED)
    *   Menambahkan pustaka open-source `smalot/pdfparser` (v2.12) untuk pemrosesan teks PDF murni PHP.
2.  📂 **`pages/rekening_koran/index.php`** (MODIFIED)
    *   Menambahkan endpoint `action: parse_pdf` dan fitur *Auto-Isi dari PDF* pada modal formulir Tambah Rekening Koran.
3.  📂 **`inc/sidebar.php`** (MODIFIED)
    *   Menambahkan menu navigasi *Dokter Sistem & DB* di bawah kategori PENGATURAN untuk Super Admin.
4.  📂 **`config/auth.php`** (MODIFIED)
    *   Mencegah peringatan *headers already sent* pada inisialisasi sesi.
5.  📂 **`inc/footer.php`** (MODIFIED)
    *   Menyertakan `widget_ai_copilot.php` agar aktif secara global di seluruh halaman sistem.
6.  📂 **`inc/topbar.php`** (MODIFIED)
    *   Menambahkan tombol pintas navigasi `[Tanya AI]` pada navbar atas.
7.  📂 **`config/cache.php`** (MODIFIED)
    *   Mengecualikan `ai_asisten` dari caching HTML statis agar interaksi selalu real-time.

