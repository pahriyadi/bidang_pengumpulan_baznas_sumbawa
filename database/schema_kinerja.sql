-- Target kinerja pengumpulan ZIS (jalankan sekali di MySQL / phpMyAdmin)
-- Capaian dihitung otomatis dari tabel transaksi.

CREATE TABLE IF NOT EXISTS target_pengumpulan_tahunan (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tahun YEAR NOT NULL,
    nominal_target DECIMAL(18,2) NOT NULL DEFAULT 0,
    keterangan VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_target_tahun (tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
