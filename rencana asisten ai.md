# 🤖 Rencana Konsep & Arsitektur: Asisten AI Intelijen SIM Pengumpulan ZIS BAZNAS Sumbawa

Berkas ini memuat rancangan lengkap, spesifikasi kemampuan, arsitektur teknis, dan alur kerja untuk mengintegrasikan **Asisten AI Eksekutif & Intelijen Data (AI Data Copilot)** ke dalam sistem **SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa**.

---

## 📋 Daftar Isi
1. [Latar Belakang & Peran AI](#1-latar-belakang--peran-ai)
2. [Spesifikasi Kemampuan AI yang Diterapkan](#2-spesifikasi-kemampuan-ai-yang-diterapkan)
   - [A. Pencarian Transaksi Multi-Header Cerdas](#a-pencarian-transaksi-multi-header-cerdas)
   - [B. Rekapitulasi Laporan & Capaian RKAT Instan](#b-rekapitulasi-laporan--capaian-rkat-instan)
   - [C. Intelijen Profil & Kepatuhan UPZ / Muzaki](#c-intelijen-profil--kepatuhan-upz--muzaki)
   - [D. Penelusuran Dokumen Program Bantuan & Mustahik](#d-penelusuran-dokumen-program-bantuan--mustahik)
   - [E. Pintasan Navigasi Cerdas (Smart Deep Links)](#e-pintasan-navigasi-cerdas-smart-deep-links)
3. [Arsitektur Keamanan Data (Read-Only Safety Guard)](#3-arsitektur-keamanan-data-read-only-safety-guard)
4. [Arsitektur Hybrid Engine: Google Gemini API + Local Query Engine](#4-arsitektur-hybrid-engine-google-gemini-api--local-query-engine)
5. [Desain Antarmuka Pengguna (UI/UX "Putih Kertas")](#5-desain-antarmuka-pengguna-uiux-putih-kertas)
6. [Struktur Berkas & Modul yang Akan Dibangun](#6-struktur-berkas--modul-yang-akan-dibangun)
7. [Contoh Interaksi & Format Output AI](#7-contoh-interaksi--format-output-ai)
8. [Tahapan Pengerjaan & Implementasi](#8-tahapan-pengerjaan--implementasi)

---

## 1. Latar Belakang & Peran AI

### A. Perbedaan dengan Chatbot Publik Biasa
Berdasarkan rujukan [**`asisten ai rancangan.md`**](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/asisten%20ai%20rancangan.md), chatbot biasa hanya menjawab informasi statis (jam kantor, rekening bank, artikel berita). 

Sebaliknya, pada sistem internal **SIM Pengumpulan ZIS BAZNAS Sumbawa**, asisten ini ditingkatkan menjadi **AI Data Copilot / Asisten Eksekutif Data** yang terhubung langsung ke basis data MySQL. AI ini dapat membaca, memfilter, menjumlahkan, dan menganalisis ribuan data secara instan dalam hitungan detik berdasarkan perintah bahasa manusia sehari-hari.

### B. Siapa yang Memanfaatkan?
- **Pimpinan / Ketua BAZNAS**: Memeriksa ringkasan capaian pengumpulan, target RKAT, dan UPZ mana yang berkinerja terbaik tanpa harus membuka banyak menu laporan.
- **Amil / Operator Pengumpulan**: Mencari riwayat setoran muzaki tertentu, memverifikasi apakah suatu transaksi sudah masuk ke rekening bank tertentu, dan memeriksa UPZ yang belum menyetor.

---

## 2. Spesifikasi Kemampuan AI yang Diterapkan

### A. Pencarian Transaksi Multi-Header Cerdas
AI mampu menelusuri tabel `transaksi` dengan menggabungkan semua parameter header kolom yang ada:
- **Tanggal Transaksi**: "hari ini", "kemarin", "minggu ini", "bulan Mei 2026", "rentang tanggal X s/d Y".
- **Identitas Muzaki**: Nama muzaki, NIK, nomor HP, status muzaki.
- **Identitas UPZ**: Nama UPZ, kode UPZ, kategori UPZ (Dinas, Kecamatan, Sekolah, Masjid, dll.).
- **Jenis & Sub-Jenis ZIS**: Zakat Fitrah, Zakat Penghasilan, Zakat Maal, Infak Terikat, Infak Tidak Terikat, DSKL, CSR.
- **Kategori Dana**: ZAKAT, INFAK, DSKL, CSR.
- **Sumber Rekening & Metode**: Bank NTB Syariah, BSI, Kas Tunai, QRIS, Payroll / Potong Gaji.
- **Nominal Transaksi**: "di atas Rp 5.000.000", "antara 1 juta sampai 3 juta", "transaksi terbesar bulan ini".
- **Keterangan**: Kata kunci di dalam catatan transaksi amil.

*Contoh Perintah Pengguna:*
> *"Cari transaksi zakat penghasilan dari Bank NTB Syariah di atas 5 juta selama bulan Agustus 2026"*

### B. Rekapitulasi Laporan & Capaian RKAT Instan
AI dapat melakukan agregasi matematis (`SUM`, `COUNT`, `AVG`, `PERCENTAGE`) secara langsung:
- **Rekap Penerimaan**: Total ZIS tahun berjalan, per bulan, per kuartal.
- **Capaian Target RKAT**: Membandingkan realisasi saat ini dengan target yang ditetapkan di `target_pengumpulan_tahunan`.
- **Komposisi ZIS**: Berapa persen porsi Zakat vs Infak vs DSKL.
- **Penyebaran Rekening Bank**: Total dana yang masuk ke Bank NTB Syariah vs Bank BSI vs Kas Tunai.

*Contoh Perintah Pengguna:*
> *"Berapa total penerimaan ZIS tahun 2026 sampai hari ini dan bagaimana persentase capaian target RKAT kita?"*

### C. Intelijen Profil & Kepatuhan UPZ / Muzaki
- **Top Kontributor**: Peringkat 5 atau 10 Muzaki / UPZ dengan kontribusi terbesar.
- **Riwayat UPZ Tertentu**: Rekap riwayat setoran UPZ tertentu sepanjang tahun berjalan.
- **Deteksi UPZ Pasif (Early Warning System)**: Menemukan UPZ yang aktif tahun lalu namun belum menyetor dalam 2–3 bulan terakhir, sehingga amil pengumpulan bisa segera melakukan silaturahmi/jemput bola.
- **Rekap Sektoral**: Total penerimaan dari kelompok UPZ Kecamatan vs UPZ Dinas/Instansi vs UPZ Sekolah.

*Contoh Perintah Pengguna:*
> *"Tampilkan 5 UPZ dengan setoran tertinggi tahun ini dan apakah ada UPZ kategori Dinas yang belum setor di bulan ini?"*

### D. Penelusuran Dokumen Program Bantuan & Mustahik
AI memiliki akses membaca modul dokumen bantuan yang baru saja kita bangun:
- Mencari penerima bantuan berdasarkan nama atau program (*Gerobak Usaha, Mahyani, dll.*).
- Menghitung total dana bantuan yang telah disalurkan pada lembar dokumen program.
- Mengetahui kelengkapan foto dokumentasi penerima.

*Contoh Perintah Pengguna:*
> *"Berapa penerima manfaat yang sudah terdaftar di dokumen program Gerobak Usaha dan berapa total dana bantuannya?"*

### E. Pintasan Navigasi Cerdas (Smart Deep Links)
Jika pengguna menanyakan hal yang membutuhkan aksi di sistem, AI menyertakan tombol tautan langsung:
- *"Buka input transaksi baru"* -> Tombol `[+ Input Transaksi]`
- *"Lihat laporan tahunan 2026"* -> Tombol `[Buka Laporan Tahunan]`
- *"Cetak kuitansi nomor 045"* -> Tombol `[🖨️ Cetak BSZ Transaksi #045]`

---

## 3. Arsitektur Keamanan Data (Read-Only Safety Guard)

Keamanan data keuangan adalah prioritas nomor satu. Sistem menerapkan aturan keamanan berlapis:

```
[ PENGGUNA BERTANYA ]
         │
         ▼
[ SANITASI & FILTER KEAMANAN ]
• Hanya menerima query bertipe SELECT
• Blokir mutlak kata kunci berbahaya:
  (INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, REPLACE, EXEC)
         │
         ▼
[ PARAMETERIZED PREPARED STATEMENT ]
• Semua filter menggunakan binding parameter (?, ?, ?)
• Mencegah SQL Injection dan Prompt Injection
         │
         ▼
[ EXECUTE & FORMAT KE LAYAR ]
```

---

## 4. Arsitektur Hybrid Engine: Google Gemini API + Local Query Engine

Sistem menggabungkan kekuatan kecerdasan buatan Google Gemini Cloud dengan mesin aturan lokal:

```
                                [ PERTANYAAN AMIL ]
                                         │
                                         ▼
                     ┌───────────────────────────────────────┐
                     │          Apakah API Key Gemini        │
                     │          Terisi & Ada Internet?       │
                     └───────────────────┬───────────────────┘
                                         │
                   ┌─────────────────────┴─────────────────────┐
                   │ [YA]                                      │ [TIDAK / LIMIT HABIS]
                   ▼                                           ▼
┌─────────────────────────────────────┐     ┌─────────────────────────────────────┐
│      GOOGLE GEMINI 2.5 FLASH        │     │     INTERNAL LOCAL INTENT ENGINE    │
│ • Natural Language Understanding    │     │ • Regex Pattern Matcher             │
│ • Menerjemahkan bahasa amil ke      │     │ • Kueri Standar BAZNAS Terdefinisi  │
│   parameter filter database JSON    │     │   (Rekap ZIS, Top UPZ, RKAT, dll.)  │
│ • Menghasilkan analisis & saran     │     │ • Selalu Siap 100% Offline          │
└──────────────────┬──────────────────┘     └──────────────────┬──────────────────┘
                   │                                           │
                   └─────────────────────┬─────────────────────┘
                                         │
                                         ▼
                   ┌───────────────────────────────────────────┐
                   │    Eksekusi Kueri Data MySQL SIM BAZNAS   │
                   │    (Aman, Ter-parameterisasi, Read-Only)  │
                   └─────────────────────┬─────────────────────┘
                                         │
                                         ▼
                   ┌───────────────────────────────────────────┐
                   │       Format Jawaban Interaktif:          │
                   │   • Balon Chat Narasi Cerdas              │
                   │   • Tabel Mini Interaktif                 │
                   │   • Quick Action Buttons & Deep Links     │
                   └───────────────────────────────────────────┘
```

---

## 5. Desain Antarmuka Pengguna (UI/UX "Putih Kertas")

Antarmuka dirancang selaras dengan panduan [**`AGENTS.md`**](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/.agents/AGENTS.md) dan [**`desain_tabel.md`**](file:///c:/xampp/htdocs/pengumpulan.baznassumbawa.id/desain_tabel.md):

1. **Tombol Mengambang (Floating Button)**:
   - Diletakkan di pojok kanan bawah (`bottom: 25px; right: 25px;`).
   - Warna putih ber-border abu-abu `#b8b8b8` dengan ikon robot/asisten beraksen hijau BAZNAS `#0d9f4f`.
2. **Jendela Obrolan (Chat Modal Window)**:
   - Lebar 420px (responsif di HP layar penuh).
   - Flat / Shadow-free (`box-shadow: none !important`), border tegas `#b8b8b8`.
   - Header putih bersih dengan logo BAZNAS dan status *Online*.
3. **Pill Chips Pertanyaan Cepat**:
   - Amil bisa mengklik pertanyaan umum dalam 1 ketukan:
     - `📊 Rekap ZIS 2026`
     - `🎯 Capaian RKAT`
     - `🏢 Top 5 UPZ`
     - `🔍 Cari Transaksi`
     - `📦 Data Bantuan`
4. **Tabel Mini di Dalam Balon Chat**:
   - Hasil pencarian transaksi atau rekap ditampilkan dalam format tabel mini bergaris rapi (`.table-sm`).
   - Nominal otomatis berformat Rupiah tebal (`Rp 2.500.000`).

---

## 6. Struktur Berkas & Modul yang Akan Dibangun

```
c:/xampp/htdocs/pengumpulan.baznassumbawa.id/
│
├── config/
│   ├── ai_config.php            -- Pengaturan Kunci API Gemini, Model Chain, & Keamanan
│   └── ai_engine.php            -- Core Engine: Gemini Client, Intent Parser, & Safe Query Builder
│
├── pages/
│   └── ai_asisten/
│       └── chat.php             -- Backend Endpoint AJAX untuk komunikasi chat
│
├── inc/
│   ├── widget_ai_copilot.php    -- Komponen UI Widget Chat (HTML, CSS Paper White, JS)
│   ├── footer.php               -- Menyertakan widget AI secara global di seluruh halaman
│   └── topbar.php               -- Menambahkan tombol pintas buka AI di navbar atas
```

---

## 7. Contoh Interaksi & Format Output AI

### Skenario 1: Rekapitulasi Capaian RKAT
* **User:** *"Berapa capaian target pengumpulan ZIS tahun 2026?"*
* **AI Output:**
  > **Laporan Capaian RKAT Tahun 2026:**
  > - **Target Tahunan:** Rp 7.500.000.000
  > - **Realisasi Terkumpul:** **Rp 4.820.550.000**
  > - **Persentase Capaian:** **64,27%**
  >
  > *Rincian Komposisi Dana:*
  > • **Zakat:** Rp 3.650.000.000 (75,7%)
  > • **Infak/Sedekah:** Rp 1.120.550.000 (23,2%)
  > • **DSKL/CSR:** Rp 50.000.000 (1,1%)
  >
  > `[Buka Laporan Tahunan]` `[Rekap per Bulan]`

---

### Skenario 2: Pencarian Transaksi Multi-Header
* **User:** *"Cari transaksi dari UPZ Dinas Pendidikan di atas 10 juta"*
* **AI Output:**
  > Ditemukan **3 transaksi** dari UPZ Dinas Pendidikan dengan nominal > Rp 10.000.000:
  >
  > | Tgl | Jenis ZIS | Bank / Metode | Nominal | Aksi |
  > |---|---|---|---|---|
  > | 02/08/2026 | Zakat Penghasilan | Bank NTB Syariah | **Rp 45.200.000** | `[Detail]` `[BSZ]` |
  > | 05/07/2026 | Zakat Penghasilan | Bank NTB Syariah | **Rp 43.800.000** | `[Detail]` `[BSZ]` |
  > | 04/06/2026 | Zakat Penghasilan | Bank NTB Syariah | **Rp 44.150.000** | `[Detail]` `[BSZ]` |
  >
  > `[Lihat Semua di Halaman Transaksi]`

---

### Skenario 3: Peringkat & Kepatuhan UPZ
* **User:** *"Siapa 3 UPZ dengan setoran terbanyak tahun ini?"*
* **AI Output:**
  > Berikut **Top 3 UPZ Pengumpulan Terbesar Tahun 2026**:
  > 1. 🥇 **UPZ Dinas Pendidikan & Kebudayaan**: Rp 354.200.000 (8x penyetoran)
  > 2. 🥈 **UPZ RSUD Kabupaten Sumbawa**: Rp 187.500.000 (8x penyetoran)
  > 3. 🥉 **UPZ Kementerian Agama Kab. Sumbawa**: Rp 142.300.000 (7x penyetoran)
  >
  > *Catatan Sistem:* Terdapat 4 UPZ kategori Kecamatan yang belum menyetor dalam 60 hari terakhir.
  > `[Lihat Daftar UPZ Pasif]` `[Buka Master UPZ]`

---

## 8. Tahapan Pengerjaan & Implementasi

1. **Tahap 1: Konfigurasi & Core Engine (`config/ai_config.php` & `config/ai_engine.php`)**:
   - Memasukkan API Key Google Gemini.
   - Membangun *Safe Query Builder* (fungsi pembuat kueri SQL otomatis ter-parameterisasi untuk tabel transaksi, muzaki, UPZ, target, dokumen program).
   - Mengatur multi-model fallback chain (`gemini-2.5-flash`, `gemini-2.5-flash-lite`, `gemini-flash-latest`).
2. **Tahap 2: Backend Endpoint AJAX (`pages/ai_asisten/chat.php`)**:
   - Menangani komunikasi request-response dengan otentikasi sesi login amil.
   - Memformat respons teks markdown, tabel HTML mini, dan tombol aksi.
3. **Tahap 3: Visual Widget Frontend (`inc/widget_ai_copilot.php`)**:
   - Membangun UI mengambang dengan standar Paper White (border `#b8b8b8`, shadow-free, aksen hijau BAZNAS).
   - Menambahkan fitur suara ketik/animasi indikator, quick action chips, dan auto-scroll.
4. **Tahap 4: Integrasi Global (`inc/footer.php` & `inc/topbar.php`)**:
   - Mengaktifkan widget di semua halaman aplikasi.
   - Menambahkan tombol pembuka di header atas.
5. **Tahap 5: Pengujian & Validasi**:
   - Menguji akurasi kueri data terhadap database nyata.
   - Menguji proteksi read-only agar aman dari perubahan data tidak sah.
   - Menguji keandalan saat internet offline / fallback engine.
6. **Tahap 6: Persistent Chat History & Multi-Turn Conversational Memory**:
   - **Tabel MySQL `ai_chat_history`**: Menyimpan seluruh riwayat obrolan setiap user/admin secara terisolasi (`user_id`, `user_message`, `ai_reply`, `quick_actions`, `sql_used`, `created_at`).
   - **Multi-Turn Context Learning**: Sistem otomatis menginjeksi konteks pertanyaan dan jawaban sebelumnya (5 giliran terakhir) ke payload Google Gemini API (`contents` multi-role `user` dan `model`) sehingga AI dapat memahami dan menjawab pertanyaan lanjutan (*follow-up questions*) secara cerdas tanpa perlu mengulang konteks awal.
   - **Pemuatan Riwayat Otomatis di Frontend**: Riwayat obrolan dimuat kembali secara otomatis saat widget dibuka atau halaman direfresh via AJAX `action: get_history`.
   - **Tombol Bersihkan Riwayat**: Pengguna dapat menghapus seluruh memori percakapan mereka dari database dengan konfirmasi aman via AJAX `action: clear_history`.
7. **Tahap 7: Universal Smart Table & Intelijen Basis Data Menyeluruh**:
   - **Format Tabel Cerdas Adaptif (`ai_format_smart_table`)**: Menghilangkan batasan tabel kaku sebelumnya. Asisten AI kini mampu merender secara otomatis segala macam kueri agregat, rekapitulasi bulanan, perbankan, peringkat, maupun arsip dokumen dengan header bahasa Indonesia yang rapi dan total di footer.
   - **Tabel Transaksi Detail**: Menyajikan Tanggal, Pembayar (badge UPZ/Muzaki), Jenis ZIS, Sumber Rekening/Bank & Metode, Uraian/Memo Transaksi, Nominal Rupiah tebal, dan tombol aksi link langsung ke data transaksi.
   - **Kartu Metrik Eksekutif (`metric_card`)**: Menampilkan ringkasan angka tunggal (misal total bulan tertentu) dalam info card Paper White yang bersih dan elegan, bukan tabel kosong.
   - **Cakupan Seluruh Sistem**: AI dibekali pengetahuan penuh tentang seluruh tabel sistem (Rekening Koran, Target RKAT per jenis, Dokumen Program Bantuan Mustahik, Sub-jenis ZIS, Kategori UPZ, dll) serta data pengumpulan real-time per bulan.

8. **Tahap 8: Human-in-the-Loop One-Click Action & Multi-Setoran Massal**:
   - **Pencegahan Halusinasi Tulis (Safety Guard)**: AI tidak pernah mengubah atau menghapus data langsung ke tabel. Seluruh instruksi mutasi (CREATE, UPDATE, DELETE, BATCH CREATE) selalu disiapkan sebagai *Draf Aksi Terverifikasi* yang memuat kartu review data transparan dengan tombol konfirmasi satu klik ("✅ Ya, Simpan", "✏️ Ya, Perbarui", "🗑️ Ya, Hapus", "❌ Batalkan").
   - **Pencatatan Log Audit Otomatis**: Setiap aksi konfirmasi yang disetujui pengguna secara otomatis direkam ke tabel `audit_log` sistem lengkap dengan user_id dan detail riwayat perubahan.
   - **Input Multi-Setoran Massal (Batch Text Parser)**: AI mampu membedah daftar setoran berbaris-baris atau multi-entitas dari pesan pengguna (misal: "Setoran Zakat: Dinkes 5jt, Dikbud 3.5jt, Kec Alas 1.5jt") menjadi array batch transaksi terstruktur, memvalidasi foreign key secara aman, dan membukukannya ke database sekaligus setelah disetujui.

9. **Tahap 9: Analisis & Rekonsiliasi Mutasi PDF Rekening Koran (0 Token API)**:
   - **Ekstraksi Teks Lokal Ringan**: Modul parser PDF berbasis stream decoding internal tanpa dependensi library eksternal berat atau konsumsi kuota token API Gemini.
   - **Kompilasi Mutasi Kredit Bank**: Mendeteksi pola transaksi kredit rekening koran bank (Bank NTB Syariah, Bank Mandiri, Bank BRI), menghitung total kredit bulanan, dan membandingkannya langsung dengan data transaksi tercatat di sistem SIM BAZNAS.
   - **Penyimpanan 1-Klik ke Modul Rekening Koran**: Menyediakan kartu visual hasil analisis dengan tombol simpan otomatis ke tabel `rekening_koran` dan link langsung ke halaman rekonsiliasi.

10. **Tahap 10: Mode Layar Penuh (Full Page View), Self-Healing SQL, Penyajian Data Penuh & Markdown Bold V2**:
    - **Halaman Mandiri Dedicated Penuh (`pages/ai_asisten/index.php`)**: Menghadirkan antarmuka Asisten AI berskala layar penuh terintegrasi dengan layout AdminLTE BAZNAS (`inc/sidebar.php`, `inc/navbar.php`, `inc/footer.php`) untuk kenyamanan analisa data mendalam.
    - **Jendela Floating Full Window Default**: Widget melayang kini berukuran *Full Screen* secara default (`calc(100vw - 30px)` x `calc(100vh - 30px)`) dengan transisi halus dan tombol beralih ke mode melayang (*compact*) kapan saja.
    - **Penyajian Data Penuh Tanpa Limit Kaku**: Menghapus pembatasan `LIMIT 20` paksa di validator kueri dan membersihkan limit otomatis AI agar seluruh baris transaksi (100 baris atau lebih) tersaji utuh tanpa terpotong.
    - **Self-Healing SQL & Eliminasi Galat Titik Koma (`;`)**: Membersihkan trailing semicolon yang sebelumnya memicu galat multi-statement/sintaks yang menyebabkan tabel hilang, serta dilengkapi kueri cerdas fallback otomatis agar tabel data transaksi dijamin selalu tampil.
    - **Penyempurnaan Markdown Bold (`**bold**`)**: Mengisolasi simbol bullet point list di awal baris sebelum parsing asteris, mendukung triple asterisk (`***bold italic***`), bold multiline, dan penegasan CSS `font-weight: 700 !important;` yang kontras di mode terang maupun mode gelap (*Paper Dark*).
    - **Auto-Scroll Chat Terakhir**: Mengintegrasikan `scrollIntoView({ block: 'end' })` dengan multi-frame scheduling agar jendela chat yang baru dibuka langsung mendarat tepat pada percakapan paling bawah.



