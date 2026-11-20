<?php

/**
 * Tabel target kinerja (On Balance Sheet & Off Balance Sheet),
 * capaian On Balance dari tabel `transaksi`, dan capaian Off Balance dari tabel `transaksi_off_balance`.
 */

/** ID kategori bernama "Penerimaan" (case-insensitive). */
function get_id_kategori_penerimaan(mysqli $conn): ?int
{
    static $cached = false;
    static $id = null;
    if ($cached) {
        return $id;
    }
    $cached = true;
    $stmt = $conn->prepare('SELECT id FROM kategori_dana WHERE LOWER(TRIM(nama_kategori)) = ? LIMIT 1');
    $nama = 'penerimaan';
    $stmt->bind_param('s', $nama);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    $id = (int)$row['id'];
    return $id > 0 ? $id : null;
}

/**
 * Filter SQL sesuai mode dana aktif ('penerimaan' atau 'pengumpulan'):
 * - 'penerimaan' => seluruh jenis, sub-jenis, dan kategori dana (" AND 1=1 ")
 * - 'pengumpulan' => mengecualikan Titipan Penyaluran (BAZ-PROV-PUSAT / APBN/D)
 */
function kinerja_sql_and_transaksi_penerimaan(mysqli $conn, string $alias = 't', ?string $mode = null): string
{
    $cond = get_dana_mode_sql_condition($mode, $alias, $conn);
    return " AND {$cond} ";
}

function ensure_kinerja_tables(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // 1. Tabel Target Pengumpulan Tahunan (On Balance & Off Balance)
    $conn->query("
        CREATE TABLE IF NOT EXISTS target_pengumpulan_tahunan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tahun YEAR NOT NULL,
            nominal_target DECIMAL(18,2) NOT NULL DEFAULT 0,
            nominal_target_off_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
            keterangan VARCHAR(500) DEFAULT NULL,
            keterangan_off_balance VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_target_tahun (tahun)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Auto-migrate kolom Off Balance jika tabel sudah ada sebelumnya
    $colsTahunan = [];
    $resColT = $conn->query("SHOW COLUMNS FROM target_pengumpulan_tahunan");
    if ($resColT) {
        while ($c = $resColT->fetch_assoc()) {
            $colsTahunan[$c['Field']] = true;
        }
    }
    if (!isset($colsTahunan['nominal_target_off_balance'])) {
        $conn->query("ALTER TABLE target_pengumpulan_tahunan ADD COLUMN nominal_target_off_balance DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER nominal_target");
    }
    if (!isset($colsTahunan['keterangan_off_balance'])) {
        $conn->query("ALTER TABLE target_pengumpulan_tahunan ADD COLUMN keterangan_off_balance VARCHAR(500) DEFAULT NULL AFTER keterangan");
    }

    // 2. Tabel Target Pengumpulan per Jenis (On Balance & Off Balance)
    $sqlJenis = "
        CREATE TABLE IF NOT EXISTS target_pengumpulan_per_jenis (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tahun YEAR NOT NULL,
            id_jenis_pembayaran INT NOT NULL,
            nominal_target DECIMAL(18,2) NOT NULL DEFAULT 0,
            nominal_target_off_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
            keterangan VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_tahun_jenis (tahun, id_jenis_pembayaran),
            KEY idx_jenis (id_jenis_pembayaran),
            CONSTRAINT fk_target_p_jenis FOREIGN KEY (id_jenis_pembayaran)
                REFERENCES jenis_pembayaran (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if (!$conn->query($sqlJenis)) {
        $conn->query("
            CREATE TABLE IF NOT EXISTS target_pengumpulan_per_jenis (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tahun YEAR NOT NULL,
                id_jenis_pembayaran INT NOT NULL,
                nominal_target DECIMAL(18,2) NOT NULL DEFAULT 0,
                nominal_target_off_balance DECIMAL(18,2) NOT NULL DEFAULT 0,
                keterangan VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_tahun_jenis (tahun, id_jenis_pembayaran),
                KEY idx_jenis (id_jenis_pembayaran)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $colsJenis = [];
    $resColJ = $conn->query("SHOW COLUMNS FROM target_pengumpulan_per_jenis");
    if ($resColJ) {
        while ($c = $resColJ->fetch_assoc()) {
            $colsJenis[$c['Field']] = true;
        }
    }
    if (!isset($colsJenis['nominal_target_off_balance'])) {
        $conn->query("ALTER TABLE target_pengumpulan_per_jenis ADD COLUMN nominal_target_off_balance DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER nominal_target");
    }

    // 3. Tabel Transaksi / Realisasi Off Balance Sheet
    $conn->query("
        CREATE TABLE IF NOT EXISTS transaksi_off_balance (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tanggal_transaksi DATE NOT NULL,
            id_upz INT DEFAULT NULL,
            nama_pelapor VARCHAR(191) DEFAULT NULL,
            id_jenis_pembayaran INT NOT NULL,
            id_sub_jenis_pembayaran INT DEFAULT NULL,
            jumlah_muzaki INT UNSIGNED NOT NULL DEFAULT 0,
            jumlah_mustahik INT UNSIGNED NOT NULL DEFAULT 0,
            nominal DECIMAL(18,2) NOT NULL DEFAULT 0,
            no_berita_acara VARCHAR(100) DEFAULT NULL,
            keterangan TEXT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_ob_tanggal (tanggal_transaksi),
            KEY idx_ob_upz (id_upz),
            KEY idx_ob_jenis (id_jenis_pembayaran),
            KEY idx_ob_sub_jenis (id_sub_jenis_pembayaran)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function get_capaian_per_tahun(mysqli $conn): array
{
    $out = [];
    $p = kinerja_sql_and_transaksi_penerimaan($conn, 't');
    $r = $conn->query("
        SELECT YEAR(t.tanggal_transaksi) AS th, COALESCE(SUM(t.jumlah_pembayaran), 0) AS total
        FROM transaksi t
        WHERE 1=1 {$p}
        GROUP BY YEAR(t.tanggal_transaksi)
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $out[(int)$row['th']] = (float)$row['total'];
        }
    }
    return $out;
}

function get_capaian_off_balance_per_tahun(mysqli $conn): array
{
    ensure_kinerja_tables($conn);
    $out = [];
    $r = $conn->query("
        SELECT YEAR(ob.tanggal_transaksi) AS th, COALESCE(SUM(ob.nominal), 0) AS total
        FROM transaksi_off_balance ob
        GROUP BY YEAR(ob.tanggal_transaksi)
    ");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $out[(int)$row['th']] = (float)$row['total'];
        }
    }
    return $out;
}

function get_capaian_per_tahun_jenis(mysqli $conn, int $tahun): array
{
    $out = [];
    $p = kinerja_sql_and_transaksi_penerimaan($conn, 't');
    $stmt = $conn->prepare("
        SELECT t.id_jenis_pembayaran, COALESCE(SUM(t.jumlah_pembayaran), 0) AS total
        FROM transaksi t
        WHERE YEAR(t.tanggal_transaksi) = ? {$p}
        GROUP BY t.id_jenis_pembayaran
    ");
    $stmt->bind_param('i', $tahun);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $out[(int)$row['id_jenis_pembayaran']] = (float)$row['total'];
    }
    $stmt->close();
    return $out;
}

function get_capaian_off_balance_per_tahun_jenis(mysqli $conn, int $tahun): array
{
    ensure_kinerja_tables($conn);
    $out = [];
    $stmt = $conn->prepare("
        SELECT ob.id_jenis_pembayaran, COALESCE(SUM(ob.nominal), 0) AS total
        FROM transaksi_off_balance ob
        WHERE YEAR(ob.tanggal_transaksi) = ?
        GROUP BY ob.id_jenis_pembayaran
    ");
    $stmt->bind_param('i', $tahun);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $out[(int)$row['id_jenis_pembayaran']] = (float)$row['total'];
    }
    $stmt->close();
    return $out;
}

function get_ringkasan_kinerja_tahunan(mysqli $conn, ?string $mode = null): array
{
    ensure_kinerja_tables($conn);
    $p = kinerja_sql_and_transaksi_penerimaan($conn, 't', $mode);
    $pPenerimaan = kinerja_sql_and_transaksi_penerimaan($conn, 't', 'penerimaan');
    $pPengumpulan = kinerja_sql_and_transaksi_penerimaan($conn, 't', 'pengumpulan');
    $sql = "
        SELECT ty.tahun,
            COALESCE(tt.nominal_target, 0) AS target_nominal,
            COALESCE(tt.nominal_target_off_balance, 0) AS target_off_balance,
            COALESCE(tt.keterangan, '') AS keterangan_target,
            COALESCE(tt.keterangan_off_balance, '') AS keterangan_off_balance,
            COALESCE(c.total, 0) AS capaian,
            COALESCE(c_pen.total, 0) AS capaian_penerimaan,
            COALESCE(c_peng.total, 0) AS capaian_pengumpulan,
            COALESCE(c_ob.total, 0) AS capaian_off_balance
        FROM (
            SELECT tahun FROM target_pengumpulan_tahunan
            UNION
            SELECT DISTINCT YEAR(t.tanggal_transaksi) AS tahun FROM transaksi t
            UNION
            SELECT DISTINCT YEAR(ob.tanggal_transaksi) AS tahun FROM transaksi_off_balance ob
            UNION SELECT YEAR(CURDATE()) AS tahun
        ) AS ty
        LEFT JOIN target_pengumpulan_tahunan tt ON tt.tahun = ty.tahun
        LEFT JOIN (
            SELECT YEAR(t.tanggal_transaksi) AS th, COALESCE(SUM(t.jumlah_pembayaran), 0) AS total
            FROM transaksi t
            WHERE 1=1 {$p}
            GROUP BY YEAR(t.tanggal_transaksi)
        ) c ON c.th = ty.tahun
        LEFT JOIN (
            SELECT YEAR(t.tanggal_transaksi) AS th, COALESCE(SUM(t.jumlah_pembayaran), 0) AS total
            FROM transaksi t
            WHERE 1=1 {$pPenerimaan}
            GROUP BY YEAR(t.tanggal_transaksi)
        ) c_pen ON c_pen.th = ty.tahun
        LEFT JOIN (
            SELECT YEAR(t.tanggal_transaksi) AS th, COALESCE(SUM(t.jumlah_pembayaran), 0) AS total
            FROM transaksi t
            WHERE 1=1 {$pPengumpulan}
            GROUP BY YEAR(t.tanggal_transaksi)
        ) c_peng ON c_peng.th = ty.tahun
        LEFT JOIN (
            SELECT YEAR(ob.tanggal_transaksi) AS th, COALESCE(SUM(ob.nominal), 0) AS total
            FROM transaksi_off_balance ob
            GROUP BY YEAR(ob.tanggal_transaksi)
        ) c_ob ON c_ob.th = ty.tahun
        ORDER BY ty.tahun DESC
    ";
    $rows = [];
    $r = $conn->query($sql);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}
