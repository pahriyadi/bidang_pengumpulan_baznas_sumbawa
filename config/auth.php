<?php

require_once __DIR__ . '/database.php';

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    return false;
}

function emit_security_headers(): void
{
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (is_https_request()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function ensure_session_started(): void
{
    emit_security_headers();

    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        $isHttps = is_https_request();
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        if ($isHttps) {
            ini_set('session.cookie_secure', '1');
        }

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
        }
        @session_start();
    }
}

function current_user(): ?array
{
    ensure_session_started();
    $sessUser = $_SESSION['user'] ?? null;
    if (!$sessUser) return null;
    
    // Verifikasi session_id terhadap database untuk mendeteksi force-logout
    static $verifiedUser = null;
    if ($verifiedUser !== null) return $verifiedUser;

    $stmt = db()->prepare("SELECT session_id, is_active, full_name, username, role, photo FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $sessUser['id']);
    $stmt->execute();
    $dbUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$dbUser || !$dbUser['is_active'] || $dbUser['session_id'] !== ($sessUser['session_id'] ?? '')) {
        // Hancurkan session jika session_id tidak cocok
        $_SESSION = [];
        session_destroy();

        $isLoginPage = (strpos($_SERVER['SCRIPT_NAME'] ?? '', 'login.php') !== false);
        if (!$isLoginPage) {
            $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
            $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
            $appBase = '';
            if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
                $appBase = substr($appRoot, strlen($docRoot));
            }
            $appBase = rtrim($appBase, '/');
            header('Location: ' . $appBase . '/login?error=kicked');
            exit;
        }
        return null;
    }

    // Perbarui data session lokal agar selalu up-to-date (misal setelah ganti foto/nama)
    $_SESSION['user']['full_name'] = $dbUser['full_name'];
    $_SESSION['user']['username'] = $dbUser['username'];
    $_SESSION['user']['role'] = $dbUser['role'];
    $_SESSION['user']['photo'] = $dbUser['photo'];
    $sessUser = $_SESSION['user'];

    // Perbarui waktu aktivitas terakhir di database
    $stmt = db()->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
    $stmt->bind_param('i', $sessUser['id']);
    $stmt->execute();
    $stmt->close();

    $verifiedUser = $sessUser;
    return $sessUser;
}

function is_authenticated(): bool
{
    return current_user() !== null;
}

function has_role(array|string $roles): bool
{
    $user = current_user();
    if (!$user) return false;
    $roles = is_array($roles) ? $roles : [$roles];
    return in_array($user['role'], $roles, true);
}

function require_auth(array|string $roles = []): void
{
    ensure_session_started();

    if (!is_authenticated()) {
        // Build login URL without depending on helpers.php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname(dirname($scriptName));
        if ($basePath === '\\' || $basePath === '/') $basePath = '';
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        // If we are in pages/ subdirectory, go up to find login.php
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $appRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
        $appBase = '';
        if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
            $appBase = substr($appRoot, strlen($docRoot));
        }
        $appBase = rtrim($appBase, '/');
        header('Location: ' . $appBase . '/login');
        exit;
    }

    if ($roles !== [] && !has_role($roles)) {
        header('HTTP/1.1 403 Forbidden');
        include __DIR__ . '/../pages/errors/403.php';
        exit;
    }
}

function find_user_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT id, full_name, username, email, password, role, is_active, photo, session_id, last_activity FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

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

/**
 * Memverifikasi Google ID Token (JWT) melalui endpoint tokeninfo resmi Google.
 * Mengembalikan payload user Google jika valid, atau null jika gagal.
 */
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

    if (function_exists('curl_init')) {
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => ['timeout' => 10, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);
        $response = @file_get_contents($verifyUrl, false, $ctx);
        $httpCode = $response !== false ? 200 : 0;
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

    // Validasi audience (Client ID)
    $aud = $payload['aud'] ?? '';
    if ($aud !== $expectedClientId) {
        $errorMessage = 'Google Client ID tidak cocok dengan aplikasi ini.';
        return null;
    }

    // Validasi issuer
    $iss = $payload['iss'] ?? '';
    if (!in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)) {
        $errorMessage = 'Penerbit token (issuer) tidak sah.';
        return null;
    }

    // Validasi waktu kedaluwarsa
    if (isset($payload['exp']) && (int)$payload['exp'] < time()) {
        $errorMessage = 'Sesi login Google telah kedaluwarsa. Silakan coba lagi.';
        return null;
    }

    // Pastikan email ada dan terverifikasi
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

function login_user(array $user): void
{
    ensure_session_started();
    $sessId = session_id();
    $_SESSION['user'] = [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'username'  => $user['username'],
        'role'      => $user['role'],
        'session_id'=> $sessId
    ];

    // Simpan session_id terbaru dan perbarui last_activity di database
    $stmt = db()->prepare("UPDATE users SET session_id = ?, last_activity = NOW() WHERE id = ?");
    $stmt->bind_param('si', $sessId, $user['id']);
    $stmt->execute();
    $stmt->close();
}

function logout_user(): void
{
    ensure_session_started();
    $user = $_SESSION['user'] ?? null;
    if ($user) {
        // Hapus penanda sesi aktif di database
        $stmt = db()->prepare("UPDATE users SET session_id = NULL, last_activity = NULL WHERE id = ?");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION = [];
    session_destroy();
}

/**
 * Mendapatkan alamat IP klien secara aman.
 */
function get_client_ip(): string
{
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ipList = explode(',', $_SERVER[$header]);
            foreach ($ipList as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }
    return '127.0.0.1';
}

/**
 * Pemeriksaan Proteksi Brute Force Login.
 * Mengunci akun / IP jika gagal >= 5 kali dalam 15 menit terakhir.
 */
function check_brute_force_lockout(string $username): array
{
    $ip = get_client_ip();
    $maxAttempts = 5;
    $lockoutMinutes = 15;

    try {
        // 1. Cek apakah ada blokir aktif berbasis block_until
        $stmtBlock = db()->prepare("
            SELECT block_until 
            FROM login_attempts 
            WHERE (ip_address = ? OR username = ?) 
              AND block_until > NOW() 
            ORDER BY block_until DESC 
            LIMIT 1
        ");
        if ($stmtBlock) {
            $stmtBlock->bind_param('ss', $ip, $username);
            $stmtBlock->execute();
            $resBlock = $stmtBlock->get_result()->fetch_assoc();
            $stmtBlock->close();
            if (!empty($resBlock['block_until'])) {
                $unlockTime = strtotime($resBlock['block_until']);
                $remainingSeconds = max(0, $unlockTime - time());
                $remainingMinutes = max(1, (int)ceil($remainingSeconds / 60));
                return [
                    'locked' => true,
                    'failed_count' => $maxAttempts,
                    'remaining_seconds' => $remainingSeconds,
                    'remaining_minutes' => $remainingMinutes
                ];
            }
        }

        // 2. Cek akumulasi percobaan gagal dalam 15 menit terakhir
        $stmt = db()->prepare("
            SELECT COUNT(*) as failed_count, MAX(attempt_time) as last_attempt 
            FROM login_attempts 
            WHERE (ip_address = ? OR username = ?) 
              AND success = 0 
              AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        if (!$stmt) {
            return ['locked' => false, 'failed_count' => 0];
        }
        $stmt->bind_param('ssi', $ip, $username, $lockoutMinutes);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $failedCount = (int)($res['failed_count'] ?? 0);
        if ($failedCount >= $maxAttempts) {
            $lastAttemptTime = strtotime($res['last_attempt'] ?? 'now');
            $unlockTime = $lastAttemptTime + ($lockoutMinutes * 60);
            $remainingSeconds = max(0, $unlockTime - time());
            $remainingMinutes = max(1, (int)ceil($remainingSeconds / 60));

            return [
                'locked' => true,
                'failed_count' => $failedCount,
                'remaining_seconds' => $remainingSeconds,
                'remaining_minutes' => $remainingMinutes
            ];
        }

        return [
            'locked' => false,
            'failed_count' => $failedCount
        ];
    } catch (\Throwable $e) {
        return ['locked' => false, 'failed_count' => 0];
    }
}

/**
 * Mencatat percobaan login (sukses atau gagal).
 */
function record_login_attempt(string $username, bool $isSuccess): void
{
    $ip = get_client_ip();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown';
    try {
        $successVal = $isSuccess ? 1 : 0;
        $blockUntil = null;

        // Jika gagal dan percobaan ini akan menjadikannya >= 5, set waktu pemblokiran 15 menit
        if (!$isSuccess) {
            $stmtCount = db()->prepare("
                SELECT COUNT(*) as cnt 
                FROM login_attempts 
                WHERE (ip_address = ? OR username = ?) 
                  AND success = 0 
                  AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            if ($stmtCount) {
                $stmtCount->bind_param('ss', $ip, $username);
                $stmtCount->execute();
                $c = (int)($stmtCount->get_result()->fetch_assoc()['cnt'] ?? 0);
                $stmtCount->close();
                if ($c + 1 >= 5) {
                    $blockUntil = date('Y-m-d H:i:s', time() + (15 * 60));
                }
            }
        }

        $stmt = db()->prepare("INSERT INTO login_attempts (username, ip_address, user_agent, attempt_time, success, block_until) VALUES (?, ?, ?, NOW(), ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sssis', $username, $ip, $userAgent, $successVal, $blockUntil);
            $stmt->execute();
            $stmt->close();
        }

        // Jika berhasil login, hapus riwayat kegagalan untuk IP dan username ini
        if ($isSuccess) {
            $stmtClean = db()->prepare("DELETE FROM login_attempts WHERE (ip_address = ? OR username = ?) AND success = 0");
            if ($stmtClean) {
                $stmtClean->bind_param('ss', $ip, $username);
                $stmtClean->execute();
                $stmtClean->close();
            }
        }
    } catch (\Throwable $e) {
        // Silent fail-safe
    }
}

/**
 * Validasi kekuatan kata sandi:
 * Minimal 8 karakter dan mengandung kombinasi huruf dan angka.
 */
function validate_password_strength(string $password, string &$errorMessage = ''): bool
{
    if (strlen($password) < 8) {
        $errorMessage = 'Password minimal terdiri dari 8 karakter.';
        return false;
    }
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errorMessage = 'Password wajib memuat kombinasi huruf dan angka.';
        return false;
    }
    return true;
}
