<?php

/**
 * Tabel target kinerja & capaian dari transaksi yang memuat kategori dana **Penerimaan** saja
 * (bukan gabungan dengan Pengumpulan).
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
 * Filter SQL: transaksi memuat id kategori penerimaan di JSON id_kategori_dana.
 * Pola LIKE (kompatibel MariaDB/MySQL lama); format kolom seperti [3] atau [3,2].
 * Mengembalikan " AND 1=0 " jika kategori Penerimaan tidak ada di master.
 */
function kinerja_sql_and_transaksi_penerimaan(mysqli $conn, string $alias = 't'): string
{
    $kid = get_id_kategori_penerimaan($conn);
    if ($kid === null) {
        return ' AND 1=0 ';
    }
    $kid = (int)$kid;
    return " AND (
        {$alias}.id_kategori_dana LIKE '%[{$kid},%'
        OR {$alias}.id_kategori_dana LIKE '%,{$kid},%'
        OR {$alias}.id_kategori_dana LIKE '%,{$kid}]%'
        OR {$alias}.id_kategori_dana = '[{$kid}]'
    ) ";
}

function ensure_kinerja_tables(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $conn->query("
        CREATE TABLE IF NOT EXISTS target_pengumpulan_tahunan (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tahun YEAR NOT NULL,
            nominal_target DECIMAL(18,2) NOT NULL DEFAULT 0,
            keterangan VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_target_tahun (tahun)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $sqlJenis = "
        CREATE TABLE IF NOT EXISTS target_pengumpulan_per_jenis (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tahun YEAR NOT NULL,
            id_jenis_pembayaran INT NOT NULL,
            nominal_target DECIMAL(18,2) NOT NULL DEFAULT 0,
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
                keterangan VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_tahun_jenis (tahun, id_jenis_pembayaran),
                KEY idx_jenis (id_jenis_pembayaran)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
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

function get_ringkasan_kinerja_tahunan(mysqli $conn): array
{
    $p = kinerja_sql_and_transaksi_penerimaan($conn, 't');
    $sql = "
        SELECT ty.tahun,
            COALESCE(tt.nominal_target, 0) AS target_nominal,
            COALESCE(tt.keterangan, '') AS keterangan_target,
            COALESCE(c.total, 0) AS capaian
        FROM (
            SELECT tahun FROM target_pengumpulan_tahunan
            UNION
            SELECT DISTINCT YEAR(t.tanggal_transaksi) AS tahun FROM transaksi t WHERE 1=1 {$p}
            UNION SELECT YEAR(CURDATE()) AS tahun
        ) AS ty
        LEFT JOIN target_pengumpulan_tahunan tt ON tt.tahun = ty.tahun
        LEFT JOIN (
            SELECT YEAR(t.tanggal_transaksi) AS th, COALESCE(SUM(t.jumlah_pembayaran), 0) AS total
            FROM transaksi t
            WHERE 1=1 {$p}
            GROUP BY YEAR(t.tanggal_transaksi)
        ) c ON c.th = ty.tahun
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
