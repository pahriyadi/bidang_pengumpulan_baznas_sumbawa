-- Target kinerja pengumpulan ZIS (On Balance Sheet & Off Balance Sheet)
-- serta Tabel Realisasi Off Balance Sheet

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
