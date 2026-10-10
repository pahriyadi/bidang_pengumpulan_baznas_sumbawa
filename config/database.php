<?php
/**
 * Konfigurasi koneksi database Pengumpulan ZIS.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'pengumpulan_zis');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
// Ubah ke true hanya saat debugging lokal. Pastikan false untuk server production demi mencegah kebocoran data.
define('DEBUG_MODE', false);

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
