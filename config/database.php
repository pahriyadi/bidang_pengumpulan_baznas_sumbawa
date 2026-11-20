<?php
/**
 * Konfigurasi koneksi database Pengumpulan ZIS.
 * Mendukung auto-switch antara Server Production (cPanel) dan Localhost (XAMPP)
 * serta mendukung override melalui config/database.local.php (tidak dilacak Git).
 */

$localOverride = __DIR__ . '/database.local.php';
if (is_file($localOverride)) {
    require_once $localOverride;
}

$isProductionServer = is_dir('/home/n1579664') || (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'baznassumbawa.id') !== false);

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $isProductionServer ? 'n1579664_xPgbaZtas7iajQGtgfZis' : 'pengumpulan_zis');
}
if (!defined('DB_USER')) {
    define('DB_USER', $isProductionServer ? 'n1579664_baznassbw' : 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $isProductionServer ? 'kQR*8rgk' : '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}
if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

/**
 * Membuat koneksi mysqli tunggal (singleton).
 */
function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    @$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($connection->connect_errno) {
        die('<div style="font-family:sans-serif;text-align:center;padding:50px;"><h2>❌ Koneksi Database Gagal</h2><p>Pastikan database <strong>' . DB_NAME . '</strong> sudah dibuat dan SQL sudah diimport.</p><p style="color:red;">' . htmlspecialchars($connection->connect_error) . '</p></div>');
    }

    if (!$connection->set_charset(DB_CHARSET)) {
        die('Gagal mengatur charset database: ' . $connection->error);
    }

    return $connection;
}
