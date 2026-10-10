# 📘 PANDUAN MASTER OPERASIONAL TERMINAL CPANEL
### Sistem Informasi Manajemen Bidang Pengumpulan — BAZNAS Kabupaten Sumbawa

* **Website Subdomain:** [https://pengumpulan.baznassumbawa.id](https://pengumpulan.baznassumbawa.id)
* **Repositori GitHub:** [https://github.com/pahriyadi/bidang_pengumpulan_baznas_sumbawa.git](https://github.com/pahriyadi/bidang_pengumpulan_baznas_sumbawa.git)
* **Direktori Server cPanel:** /home/n1579664/public_html/pengumpulan.baznassumbawa.id

---

## ⚡ 1. INISIALISASI AWAL DI SERVER CPANEL (SEKALI SAJA)

Buka menu **Terminal** di cPanel Anda, kemudian jalankan seluruh baris perintah berikut secara berurutan:

`ash
# 1. Masuk ke folder subdomain pengumpulan
cd /home/n1579664/public_html/pengumpulan.baznassumbawa.id

# 2. Inisialisasi Git di server
git init

# 3. Hubungkan ke repositori GitHub resmi
git remote add origin https://github.com/pahriyadi/bidang_pengumpulan_baznas_sumbawa.git

# 4. Tarik seluruh file dan aktifkan branch main
git fetch
git checkout -b main -t origin/main -f
`

---

## 🗄️ 2. SETUP DATABASE & PERMISSIONS DI SERVER

### A. Import Database Master
1. Buka cPanel ➔ Masuk ke **phpMyAdmin**.
2. Pilih database subdomain (contoh: 
1579664_pengumpulan).
3. Klik menu **Import** ➔ Pilih berkas pengumpulan_zis.sql ➔ Klik **Go / Kirim**.

### B. Konfigurasi Koneksi Database
Jalankan perintah berikut di Terminal cPanel untuk memeriksa konfigurasi koneksi:
`ash
nano config/database.php
`
Pastikan username, password, dan nama database cPanel sudah terisi dengan benar.  
*(Tekan Ctrl + X, lalu tekan Y dan Enter untuk menyimpan)*.

### C. Set Izin Folder Upload
`ash
chmod -R 775 assets/uploads
`

---

## 🔄 3. ALUR UPDATE RUTIN 3 DETIK (ONGOING WORKFLOW)

Setiap kali Anda selesai melakukan penambahan fitur atau perbaikan kode di laptop:

### 💻 Langkah 1 — Di Laptop (Folder Lokal Project):
Buka terminal di c:\xampp\htdocs\pengumpulan.baznassumbawa.id:
`ash
git add .
git commit -m "update: rincian pembaruan fitur"
git push origin main
`

### 🌐 Langkah 2 — Di Terminal cPanel Hosting (Hanya 1 Baris):
Buka Terminal cPanel dan jalankan:
`ash
cd /home/n1579664/public_html/pengumpulan.baznassumbawa.id && git pull origin main
`
> **Hasil:** Seluruh perubahan terbaru langsung aktif dan diterapkan ke website https://pengumpulan.baznassumbawa.id seketika.

---

## 📋 4. TABEL PERINTAH CEPAT (CHEATSHEET) TERMINAL CPANEL

| Kebutuhan Operasional | Perintah Terminal | Fungsi & Keterangan |
|---|---|---|
| **Navigasi ke Project** | cd /home/n1579664/public_html/pengumpulan.baznassumbawa.id | Masuk ke folder website subdomain |
| **Cek Lokasi Direktori** | pwd | Memastikan posisi Anda sudah benar |
| **Tarik Update GitHub** | git pull origin main | Mengunduh perubahan terbaru |
| **Cek Status Sinkronisasi** | git status | Melihat status branch & file |
| **Lihat Riwayat Update** | git log -n 5 --oneline | Menampilkan 5 commit/update terakhir |
| **Daftar File & Permissions** | ls -la | Menampilkan daftar file dan hak aksesnya |
| **Pantau Pesan Error PHP** | 	ail -n 50 error_log | Memeriksa 50 baris catatan error terbaru |

---

## 🚨 5. SOLUSI MENGATASI KENDALA (TROUBLESHOOTING)

### Kasus 1: Gagal git pull karena ada perubahan file lokal di server
Jika muncul peringatan *“Your local changes would be overwritten by merge”*:
`ash
git reset --hard origin/main && git pull origin main
`
*Perintah ini akan meratakan seluruh file di server hosting agar 100% identik dengan GitHub.*

### Kasus 2: Ingin Membatalkan Perubahan Sementara di Server
`ash
git stash && git pull origin main
`

---

*Dokumen ini dibuat otomatis sebagai SOP resmi deployment Bidang Pengumpulan BAZNAS Kabupaten Sumbawa.*