# 📘 DOKUMENTASI & CETAK BIRU INTEGRASI LOGIN AKUN GOOGLE (OAUTH 2.0)
**Standar Implementasi Sistem Informasi BAZNAS Kabupaten Sumbawa**

Dokumen ini berisi panduan langkah demi langkah serta cetak biru teknis (*blueprint*) untuk menerapkan fitur **Login menggunakan Akun Google (Google Identity Services)** ke sistem informasi BAZNAS lainnya di masa mendatang.

> 💡 **Cara Cepat Menggunakan Dokumen Ini untuk Sistem Lain:**
> Saat Anda membuka project/sistem lain bersama AI Assistant, cukup berikan instruksi:
> *"Tolong terapkan fitur Login Akun Google di sistem ini sesuai cetak biru pada file `DOKUMENTASI_INTEGRASI_LOGIN_GOOGLE.md`"*

---

## 🌐 BAGIAN 1: PENGATURAN DI GOOGLE CLOUD CONSOLE (HANYA 1 MENIT)

Karena Anda sudah memiliki **Google OAuth Client ID**, Anda **tidak perlu** membuat project atau Client ID baru untuk sistem lainnya. Cukup daftarkan domain/subdomain sistem baru tersebut:

1. Buka **[Google Cloud Console — Credentials](https://console.cloud.google.com/apis/credentials)**.
2. Klik nama **OAuth 2.0 Client ID** yang sudah Anda miliki.
3. Pada bagian **Authorized JavaScript origins**, klik tombol **+ ADD URI**, lalu masukkan URL sistem baru Anda:
   - `http://localhost` *(untuk pengujian di XAMPP lokal)*
   - `https://namasubdomain.baznassumbawa.id` *(tanpa garis miring `/` di akhir)*
4. Klik **SAVE**.
5. Pastikan pada menu **OAuth consent screen**, status *Publishing status* sudah berada pada **In production** (bukan *Testing*).
6. Salin kode **Client ID** (`xxxxx.apps.googleusercontent.com`) untuk dimasukkan ke Pengaturan Website di sistem tujuan.

---

## 🛠️ BAGIAN 2: ARSITEKTUR & KOMPONEN KODE YANG DITERAPKAN

Integrasi ini menggunakan **Google Identity Services (GIS) SDK** terbaru tanpa memerlukan library Composer eksternal yang berat, dengan keamanan verifikasi token langsung di sisi server (*Server-Side JWT Verification*).

### 1. Parameter Pengaturan (`website_settings`)
Simpan 3 kunci pengaturan berikut di tabel pengaturan sistem (`website_settings` atau sejenisnya):
- `google_login_enabled`: `'0'` (Nonaktif) atau `'1'` (Aktif)
- `google_login_mode`:
  - `'both'` — **Keduanya (Manual & Google)**: Form Username/Password dan tombol Google tampil bersama.
  - `'staff_google_only'` — **Wajib Google (Staf) + Manual (Admin)**: Staf biasa wajib login menggunakan Google, sedangkan Admin tetap bisa memakai form manual.
  - `'google_only'` — **Hanya Akun Google (Sembunyikan Form Manual)**: Form manual disembunyikan secara default, menyisakan tombol Google dan tautan kecil *"Login Darurat Admin"*.
- `google_client_id`: String Client ID dari Google Cloud Console.

---

### 2. Fungsi Backend & Verifikasi Keamanan (`config/auth.php`)
Tambahkan dua fungsi utama pada file autentikasi sistem:

#### A. Pencarian Pengguna Berdasarkan Email (`find_user_by_email`)
Mencari pengguna yang terdaftar di tabel `users` berdasarkan kecocokan email (`LOWER(TRIM(email)) = LOWER(?)`). Pastikan kolom `email` bersifat unik antar pengguna.

```php
function find_user_by_email(string $email): ?array
{
    $email = trim($email);
    if ($email === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT id, full_name, username, email, password, role, is_active, photo, session_id, last_activity FROM users WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}
```

#### B. Verifikasi Token Google ke Server Resmi (`verify_google_id_token`)
Memvalidasi `id_token` (JWT) yang dikirim oleh Google melalui endpoint `https://oauth2.googleapis.com/tokeninfo`:
- Menggunakan `CURLOPT_SSL_VERIFYPEER = true` dan `CURLOPT_SSL_VERIFYHOST = 2` di server produksi (dengan fallback otomatis khusus `localhost` di XAMPP Windows).
- Memastikan klaim `aud` sama persis dengan `google_client_id`.
- Memastikan klaim `iss` berasal dari `accounts.google.com` atau `https://accounts.google.com`.
- Memastikan klaim `exp` belum kedaluwarsa (`exp >= time()`).
- Memastikan klaim `email_verified` bernilai `true`.
- Pada fungsi `login_user()`, jalankan `session_regenerate_id(true)` untuk mencegah *Session Fixation*.

```php
function verify_google_id_token(string $idToken, string $expectedClientId, string &$errorMessage = ''): ?array
{
    $idToken = trim($idToken);
    $expectedClientId = trim($expectedClientId);

    if ($idToken === '' || $expectedClientId === '') {
        $errorMessage = 'Token atau Google Client ID belum dikonfigurasi.';
        return null;
    }

    $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    $response = false;
    $httpCode = 0;

    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    $isLocalhost = ($host === 'localhost' || strpos($host, '127.0.0.1') === 0 || strpos($host, 'localhost:') === 0);

    if (function_exists('curl_init')) {
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (($response === false || $curlErrno === 60 || $curlErrno === 77) && $isLocalhost) {
            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
    }

    if ($response === false || $httpCode !== 200) {
        $errorMessage = 'Gagal memverifikasi kredensial dengan server Google.';
        return null;
    }

    $payload = json_decode((string)$response, true);
    if (!is_array($payload) || isset($payload['error'])) {
        $errorMessage = 'Token Google tidak valid atau telah kedaluwarsa.';
        return null;
    }

    if (($payload['aud'] ?? '') !== $expectedClientId) {
        $errorMessage = 'Google Client ID tidak cocok dengan aplikasi ini.';
        return null;
    }

    if (!in_array($payload['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true)) {
        $errorMessage = 'Penerbit token (issuer) tidak sah.';
        return null;
    }

    if (isset($payload['exp']) && (int)$payload['exp'] < time()) {
        $errorMessage = 'Sesi login Google telah kedaluwarsa. Silakan coba lagi.';
        return null;
    }

    $emailVerified = $payload['email_verified'] ?? false;
    if (empty($payload['email']) || ($emailVerified !== true && $emailVerified !== 'true' && $emailVerified !== '1')) {
        $errorMessage = 'Alamat email Google belum terverifikasi.';
        return null;
    }

    return [
        'sub'     => (string)($payload['sub'] ?? ''),
        'email'   => strtolower(trim((string)$payload['email'])),
        'name'    => trim((string)($payload['name'] ?? '')),
        'picture' => trim((string)($payload['picture'] ?? ''))
    ];
}
```

---

### 3. Alur Halaman Login (`login.php`)

1. **Pengecekan Konfigurasi**:
   ```php
   $googleLoginEnabled = get_setting('google_login_enabled', '0') === '1';
   $googleClientId = trim(get_setting('google_client_id', ''));
   $isGoogleAuthActive = $googleLoginEnabled && $googleClientId !== '';
   $googleLoginMode = $isGoogleAuthActive ? get_setting('google_login_mode', 'both') : 'both';
   $hideManualFormByDefault = ($isGoogleAuthActive && $googleLoginMode === 'google_only' && ($_POST['auth_type'] ?? '') !== 'password');
   ```
2. **Handler POST `auth_type === 'google'`**:
   - Verifikasi `csrf_token`.
   - Panggil `verify_google_id_token($_POST['google_credential'], $googleClientId, $googleErr)`.
   - Cek *brute-force lockout* untuk alamat email terkait.
   - Cari akun via `find_user_by_email($email)`.
   - Tolak jika email belum terdaftar di database atau `is_active == 0`.
   - Jika valid dan aktif, panggil `login_user($user)` dan catat ke log audit (`LOGIN_GOOGLE`).
3. **Pembatasan Mode Login Manual**:
   - Pada blok login manual (`auth_type === 'password'`), jika `$googleLoginMode` bernilai `'staff_google_only'` atau `'google_only'` dan `$user['role'] !== 'admin'`, tolak login manual dan arahkan staf untuk menggunakan tombol Google.
4. **Render Tombol Google Identity (Mendukung Dark/Light Mode)**:
   - Muat script `<script src="https://accounts.google.com/gsi/client" async defer onload="initGoogleSignIn()"></script>`.
   - Gunakan `google.accounts.id.initialize({ client_id: ..., callback: handleGoogleCredentialResponse })`.
   - Render tombol dengan tema adaptif (`theme: isDark ? 'filled_black' : 'outline'`) dan panggil ulang `renderGoogleButton()` saat pengguna menekan tombol *toggle dark mode*.

---

### 4. Validasi Keunikan Email Pengguna (`pages/users/` & `pages/profile/`)
Pastikan saat Admin menambah/mengedit pengguna atau saat pengguna memperbarui profilnya sendiri, sistem memeriksa agar tidak ada dua pengguna yang menggunakan alamat email yang sama:
```sql
SELECT id FROM users WHERE LOWER(TRIM(email)) = LOWER(?) AND id != ? LIMIT 1
```

---

## ✅ CHECKLIST PENGUJIAN SETELAH IMPLEMENTASI
1. Buka **Pengaturan Website**, aktifkan **Status Login Google**, pilih **Metode Login**, dan isi **Google OAuth Client ID**.
2. Pastikan akun di **Manajemen Pengguna** sudah memiliki alamat email Gmail/Google yang valid.
3. Buka halaman **Login**, klik tombol **Sign in with Google**, dan pilih akun Google yang terdaftar $\rightarrow$ Berhasil masuk ke Dashboard.
4. Uji dengan akun Google yang **tidak terdaftar** di Manajemen Pengguna $\rightarrow$ Sistem wajib menolak dengan pesan bahwa email belum terdaftar.
5. Uji *toggle* **Mode Gelap / Terang** di halaman Login $\rightarrow$ Tombol Google otomatis menyesuaikan warna tema.

