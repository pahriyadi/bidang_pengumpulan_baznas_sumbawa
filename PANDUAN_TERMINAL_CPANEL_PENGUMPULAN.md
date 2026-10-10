# 📘 PANDUAN PRAKTIS TERMINAL CPANEL — BIDANG PENGUMPULAN BAZNAS SUMBAWA
Subdomain: **https://pengumpulan.baznassumbawa.id**
Repositori GitHub: **https://github.com/pahriyadi/bidang_pengumpulan_baznas_sumbawa.git**
Folder Server: /home/n1579664/public_html/pengumpulan.baznassumbawa.id

---

## ⚡ 1. HUBUNGKAN PERTAMA KALI DI TERMINAL CPANEL
Buka menu **Terminal** di cPanel, lalu jalankan:

``bash
cd /home/n1579664/public_html/pengumpulan.baznassumbawa.id
git init
git remote add origin https://github.com/pahriyadi/bidang_pengumpulan_baznas_sumbawa.git
git fetch
git checkout -b main -t origin/main -f
``

---

## 🔄 2. ALUR UPDATE RUTIN (3 DETIK)

### 💻 Tahap 1: Di Laptop (Folder Lokal)
``bash
git add .
git commit -m "update: pembaruan fitur"
git push origin main
``

### 🌐 Tahap 2: Di Terminal cPanel
``bash
cd /home/n1579664/public_html/pengumpulan.baznassumbawa.id && git pull origin main
``

---

## 🚨 3. SOLUSI JIKA ADA KONFLIK DI SERVER
``bash
git reset --hard origin/main && git pull origin main
``