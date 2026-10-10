<?php
/**
 * Dokter Sistem & Database (AI System Diagnostics & Safe Auto-Repair)
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 * 
 * Modul pemantauan kesehatan server hosting, integritas database,
 * pembaca error log, dan pemeliharaan mandiri aman (0 Token API).
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Pemindaian menyeluruh kondisi sistem dan kalkulasi skor kesehatan komposit
 * 
 * @return array
 */
function doctor_scan_all(): array
{
    $dbCheck = doctor_check_database();
    $serverCheck = doctor_check_server();
    $logCheck = doctor_check_logs();

    // Kalkulasi Skor Kesehatan Komposit (0 - 100)
    $score = 100;
    $issues = [];

    // Evaluasi Database
    if (!empty($dbCheck['fragmented_tables'])) {
        $score -= min(15, count($dbCheck['fragmented_tables']) * 4);
        $issues[] = [
            'type' => 'warning',
            'category' => 'Database',
            'title' => count($dbCheck['fragmented_tables']) . ' Tabel Mengalami Fragmentasi Ruang',
            'description' => 'Terdapat sisa ruang kosong (overhead ' . $dbCheck['total_overhead_formatted'] . ') yang dapat dimampatkan untuk mempercepat kueri.',
            'action' => 'optimize_tables',
            'action_label' => 'Defrag & Optimasi Tabel'
        ];
    }

    if (!empty($dbCheck['missing_indexes'])) {
        $score -= min(20, count($dbCheck['missing_indexes']) * 7);
        $issues[] = [
            'type' => 'warning',
            'category' => 'Database',
            'title' => count($dbCheck['missing_indexes']) . ' Rekomendasi Indeks Performa Transaksi',
            'description' => 'Kolom filter pencarian transaksi (' . implode(', ', $dbCheck['missing_indexes']) . ') belum terindeks penuh, berpotensi memperlambat penarikan laporan besar.',
            'action' => 'add_indexes',
            'action_label' => 'Buat Indeks Otomatis'
        ];
    }

    if (!empty($dbCheck['integrity_issues'])) {
        foreach ($dbCheck['integrity_issues'] as $intIssue) {
            $score -= 8;
            $issues[] = [
                'type' => 'danger',
                'category' => 'Integritas Data',
                'title' => $intIssue['title'],
                'description' => $intIssue['description'],
                'action' => $intIssue['action'] ?? null,
                'action_label' => $intIssue['action_label'] ?? null
            ];
        }
    }

    // Evaluasi Server & Hosting
    if ($serverCheck['memory_limit_val'] < 128) {
        $score -= 15;
        $issues[] = [
            'type' => 'danger',
            'category' => 'Server Hosting',
            'title' => 'Batas Memory PHP Rendah (' . $serverCheck['memory_limit'] . ')',
            'description' => 'Disarankan minimal 256M di php.ini agar proses ekspor laporan tahunan dan pembacaan PDF tidak kehabisan memori.',
            'action' => null,
            'action_label' => null
        ];
    }

    if (!empty($serverCheck['unwritable_folders'])) {
        $score -= 20;
        $issues[] = [
            'type' => 'danger',
            'category' => 'Izin Folder',
            'title' => count($serverCheck['unwritable_folders']) . ' Direktori Tidak Memiliki Izin Tulis',
            'description' => 'Folder berikut tidak dapat ditulisi: ' . implode(', ', $serverCheck['unwritable_folders']) . '. Unggah bukti dan caching akan gagal.',
            'action' => null,
            'action_label' => null
        ];
    }

    if ($serverCheck['disk_free_percent'] !== null && $serverCheck['disk_free_percent'] < 10) {
        $score -= 25;
        $issues[] = [
            'type' => 'danger',
            'category' => 'Penyimpanan',
            'title' => 'Sisa Kapasitas Penyimpanan Kritis (' . $serverCheck['disk_free_formatted'] . ' / ' . round($serverCheck['disk_free_percent'], 1) . '%)',
            'description' => 'Kapasitas disk hosting hampir habis. Bersihkan file sampah atau hubungi penyedia hosting.',
            'action' => 'clean_temp_files',
            'action_label' => 'Bersihkan File Temp'
        ];
    } elseif ($serverCheck['temp_files_count'] > 15) {
        $issues[] = [
            'type' => 'info',
            'category' => 'Penyimpanan',
            'title' => $serverCheck['temp_files_count'] . ' Berkas Sementara / Cache Menumpuk',
            'description' => 'Terdapat berkas sementara (' . $serverCheck['temp_files_size_formatted'] . ') yang aman untuk dibersihkan.',
            'action' => 'clean_temp_files',
            'action_label' => 'Bersihkan Berkas Sementara'
        ];
    }

    // Evaluasi Error Log
    if ($logCheck['fatal_count'] > 0) {
        $score -= min(15, $logCheck['fatal_count'] * 3);
        $issues[] = [
            'type' => 'danger',
            'category' => 'Error Log',
            'title' => $logCheck['fatal_count'] . ' Fatal Error Terdeteksi di Log Server',
            'description' => 'Terdapat galat fatal terbaru pada server. Periksa rincian log untuk melihat skrip penyebabnya.',
            'action' => 'view_logs',
            'action_label' => 'Lihat Rincian Error'
        ];
    }

    $score = max(0, min(100, $score));

    // Status Teks Skor
    $statusText = 'Sistem Prima';
    $statusColor = '#0d9f4f'; // Hijau BAZNAS
    if ($score < 70) {
        $statusText = 'Perlu Perhatian Kritis';
        $statusColor = '#dc3545'; // Merah
    } elseif ($score < 90) {
        $statusText = 'Cukup Baik (Ada Rekomendasi)';
        $statusColor = '#f0ad4e'; // Kuning
    }

    return [
        'score' => $score,
        'status_text' => $statusText,
        'status_color' => $statusColor,
        'timestamp' => date('Y-m-d H:i:s'),
        'issues' => $issues,
        'database' => $dbCheck,
        'server' => $serverCheck,
        'logs' => $logCheck
    ];
}

/**
 * Pemeriksaan kesehatan database MySQL
 */
function doctor_check_database(): array
{
    $conn = db();
    $dbName = DB_NAME;

    // 1. Ukuran Tabel & Fragmentasi
    $sql = "SELECT 
                TABLE_NAME, 
                ENGINE, 
                TABLE_ROWS, 
                DATA_LENGTH, 
                INDEX_LENGTH, 
                DATA_FREE
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = '$dbName'";
    
    $res = $conn->query($sql);
    $tables = [];
    $totalDataSize = 0;
    $totalIndexSize = 0;
    $totalOverhead = 0;
    $fragmentedTables = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $dataSize = (int)$row['DATA_LENGTH'];
            $indexSize = (int)$row['INDEX_LENGTH'];
            $dataFree = (int)$row['DATA_FREE'];

            $totalDataSize += $dataSize;
            $totalIndexSize += $indexSize;
            $totalOverhead += $dataFree;

            $tables[] = [
                'name' => $row['TABLE_NAME'],
                'engine' => $row['ENGINE'],
                'rows' => (int)$row['TABLE_ROWS'],
                'data_size' => $dataSize,
                'index_size' => $indexSize,
                'overhead' => $dataFree
            ];

            // Fragmentasi di atas 100 KB
            if ($dataFree > 102400) {
                $fragmentedTables[] = $row['TABLE_NAME'];
            }
        }
    }

    // 2. Pemeriksaan Indeks Performa pada Tabel `transaksi`
    $existingIndexes = [];
    $resIdx = $conn->query("SHOW INDEX FROM transaksi");
    if ($resIdx) {
        while ($idx = $resIdx->fetch_assoc()) {
            $existingIndexes[] = $idx['Column_name'];
        }
    }

    $crucialCols = [
        'tanggal_transaksi' => 'idx_tx_tanggal',
        'id_sumber_rekening' => 'idx_tx_sumber',
        'id_muzaki' => 'idx_tx_muzaki',
        'id_upz' => 'idx_tx_upz',
        'id_jenis_pembayaran' => 'idx_tx_jenis'
    ];

    $missingIndexes = [];
    foreach ($crucialCols as $col => $idxName) {
        if (!in_array($col, $existingIndexes, true)) {
            $missingIndexes[$col] = $idxName;
        }
    }

    // 3. Pemeriksaan Integritas Data
    $integrityIssues = [];

    // Transaksi dengan nominal 0 atau minus
    $resZero = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE jumlah_pembayaran <= 0");
    $zeroCount = $resZero ? (int)$resZero->fetch_assoc()['cnt'] : 0;
    if ($zeroCount > 0) {
        $integrityIssues[] = [
            'title' => "$zeroCount Transaksi Bernilai Rp 0 atau Negatif",
            'description' => 'Terdapat transaksi dengan jumlah setoran tidak wajar yang berpotensi merusak akurasi laporan keuangan.',
            'action' => null
        ];
    }

    // Transaksi dengan tanggal masa depan (anomali)
    $today = date('Y-m-d');
    $resFuture = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE tanggal_transaksi > '$today'");
    $futureCount = $resFuture ? (int)$resFuture->fetch_assoc()['cnt'] : 0;
    if ($futureCount > 0) {
        $integrityIssues[] = [
            'title' => "$futureCount Transaksi Bertanggal Masa Depan",
            'description' => "Ditemukan transaksi bertanggal setelah hari ini ($today). Kemungkinan terjadi kesalahan input kalender.",
            'action' => null
        ];
    }

    // Cek rekening koran yang memiliki selisih dengan transaksi sistem
    $resSelisih = $conn->query("
        SELECT rk.bulan, sr.nama_sumber, rk.jumlah_kredit,
               COALESCE(SUM(t.jumlah_pembayaran), 0) as total_sistem
        FROM rekening_koran rk
        LEFT JOIN sumber_rekening sr ON rk.id_sumber_rekening = sr.id
        LEFT JOIN transaksi t ON t.id_sumber_rekening = rk.id_sumber_rekening 
             AND DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = rk.bulan
        GROUP BY rk.id
        HAVING ABS(rk.jumlah_kredit - total_sistem) > 1
        LIMIT 5
    ");
    $rekonIssuesCount = $resSelisih ? $resSelisih->num_rows : 0;
    if ($rekonIssuesCount > 0) {
        $integrityIssues[] = [
            'title' => "$rekonIssuesCount Periode Rekening Koran Belum Klop (Ada Selisih)",
            'description' => 'Terdapat perbedaan antara nominal mutasi kredit bank dengan transaksi terinput di sistem. Perlu rekonsiliasi manual.',
            'action' => 'open_rekening_koran',
            'action_label' => 'Buka Modul Rekening Koran'
        ];
    }

    return [
        'database_name' => $dbName,
        'table_count' => count($tables),
        'total_data_size' => $totalDataSize,
        'total_data_size_formatted' => doctor_format_bytes($totalDataSize),
        'total_index_size' => $totalIndexSize,
        'total_index_size_formatted' => doctor_format_bytes($totalIndexSize),
        'total_overhead' => $totalOverhead,
        'total_overhead_formatted' => doctor_format_bytes($totalOverhead),
        'fragmented_tables' => $fragmentedTables,
        'missing_indexes' => $missingIndexes,
        'integrity_issues' => $integrityIssues,
        'tables' => $tables
    ];
}

/**
 * Pemeriksaan konfigurasi dan lingkungan server hosting
 */
function doctor_check_server(): array
{
    // Konfigurasi PHP
    $memoryLimit = ini_get('memory_limit');
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    $maxExec = ini_get('max_execution_time');
    $phpVersion = PHP_VERSION;

    $memoryLimitVal = doctor_parse_bytes($memoryLimit) / (1024 * 1024);

    // Ekstensi Penting
    $extensions = [
        'mysqli' => extension_loaded('mysqli'),
        'curl' => extension_loaded('curl'),
        'mbstring' => extension_loaded('mbstring'),
        'gd' => extension_loaded('gd'),
        'zip' => extension_loaded('zip'),
        'openssl' => extension_loaded('openssl'),
        'fileinfo' => extension_loaded('fileinfo')
    ];

    // Izin Tulis Direktori
    $dirs = [
        'assets/uploads/rekening_koran' => __DIR__ . '/../assets/uploads/rekening_koran',
        'assets/uploads/bukti' => __DIR__ . '/../assets/uploads/bukti',
        'assets/cache' => __DIR__ . '/../assets/cache',
        'backups' => __DIR__ . '/../backups'
    ];

    $unwritableFolders = [];
    foreach ($dirs as $label => $path) {
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }
        if (!is_writable($path)) {
            $unwritableFolders[] = $label;
        }
    }

    // Kapasitas Penyimpanan
    $diskFree = @disk_free_space(__DIR__);
    $diskTotal = @disk_total_space(__DIR__);
    $diskFreePercent = ($diskFree && $diskTotal) ? ($diskFree / $diskTotal) * 100 : null;

    // File Sementara / Temp PDF
    $tempDir = __DIR__ . '/../assets/uploads/rekening_koran';
    $tempFilesCount = 0;
    $tempFilesSize = 0;
    if (is_dir($tempDir)) {
        foreach (glob($tempDir . '/*') as $f) {
            if (is_file($f)) {
                $tempFilesCount++;
                $tempFilesSize += filesize($f);
            }
        }
    }

    return [
        'php_version' => $phpVersion,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Web Server',
        'memory_limit' => $memoryLimit,
        'memory_limit_val' => $memoryLimitVal,
        'upload_max_filesize' => $uploadMax,
        'post_max_size' => $postMax,
        'max_execution_time' => $maxExec . ' detik',
        'extensions' => $extensions,
        'unwritable_folders' => $unwritableFolders,
        'disk_free' => $diskFree,
        'disk_free_formatted' => $diskFree ? doctor_format_bytes($diskFree) : 'Tidak terdeteksi',
        'disk_total' => $diskTotal,
        'disk_total_formatted' => $diskTotal ? doctor_format_bytes($diskTotal) : 'Tidak terdeteksi',
        'disk_free_percent' => $diskFreePercent,
        'temp_files_count' => $tempFilesCount,
        'temp_files_size' => $tempFilesSize,
        'temp_files_size_formatted' => doctor_format_bytes($tempFilesSize)
    ];
}

/**
 * Pemeriksaan log error server terkini
 */
function doctor_check_logs(int $maxLines = 80): array
{
    // Cari lokasi error log yang aktif
    $logPaths = [
        ini_get('error_log'),
        'C:/xampp/apache/logs/error.log',
        'C:/xampp/php/logs/php_error_log',
        __DIR__ . '/../error_log',
        __DIR__ . '/../php_errors.log'
    ];

    $activePath = null;
    foreach ($logPaths as $p) {
        if (!empty($p) && file_exists($p) && is_readable($p) && filesize($p) > 0) {
            $activePath = $p;
            break;
        }
    }

    if (!$activePath) {
        return [
            'found' => false,
            'log_path' => null,
            'lines' => [],
            'fatal_count' => 0,
            'warning_count' => 0,
            'notice_count' => 0,
            'summary' => 'Tidak ada file error log aktif atau server dalam keadaan bersih tanpa error.'
        ];
    }

    $lines = [];
    $fatalCount = 0;
    $warningCount = 0;
    $noticeCount = 0;

    $file = @fopen($activePath, 'r');
    if ($file) {
        $buffer = [];
        while (($line = fgets($file)) !== false) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $buffer[] = $trimmed;
                if (count($buffer) > $maxLines) {
                    array_shift($buffer);
                }
            }
        }
        fclose($file);

        $buffer = array_reverse($buffer); // Terbaru di atas
        foreach ($buffer as $rawLine) {
            $type = 'info';
            if (stripos($rawLine, 'fatal') !== false || stripos($rawLine, 'parse error') !== false || stripos($rawLine, 'uncaught') !== false) {
                $type = 'fatal';
                $fatalCount++;
            } elseif (stripos($rawLine, 'warning') !== false) {
                $type = 'warning';
                $warningCount++;
            } elseif (stripos($rawLine, 'notice') !== false) {
                $type = 'notice';
                $noticeCount++;
            }

            $lines[] = [
                'type' => $type,
                'raw' => $rawLine
            ];
        }
    }

    return [
        'found' => true,
        'log_path' => $activePath,
        'file_size_formatted' => doctor_format_bytes(filesize($activePath)),
        'lines' => $lines,
        'fatal_count' => $fatalCount,
        'warning_count' => $warningCount,
        'notice_count' => $noticeCount,
        'summary' => count($lines) . ' baris log terkini dipindai.'
    ];
}

/**
 * Eksekusi perbaikan mandiri aman (Safe 1-Click Repair)
 * 
 * @param string $action Jenis perbaikan ('optimize_tables' | 'add_indexes' | 'clean_temp_files' | 'clear_cache')
 * @return array ['success' => bool, 'message' => string, 'details' => array]
 */
function doctor_execute_repair(string $action): array
{
    $conn = db();

    switch ($action) {
        case 'optimize_tables':
            $dbName = DB_NAME;
            $res = $conn->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName'");
            $optimized = [];
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $tName = $r['TABLE_NAME'];
                    $conn->query("OPTIMIZE TABLE `$tName`");
                    $conn->query("ANALYZE TABLE `$tName`");
                    $optimized[] = $tName;
                }
            }
            log_audit('DOCTOR_OPTIMIZE_TABLES', 'database', 0, 'Mengoptimasi & defrag ' . count($optimized) . ' tabel database');
            return [
                'success' => true,
                'message' => 'Berhasil melakukan defragmentasi & optimasi pada ' . count($optimized) . ' tabel database.',
                'details' => $optimized
            ];

        case 'add_indexes':
            $added = [];
            $existing = [];
            $resIdx = $conn->query("SHOW INDEX FROM transaksi");
            if ($resIdx) {
                while ($idx = $resIdx->fetch_assoc()) {
                    $existing[] = $idx['Column_name'];
                }
            }

            $indexesToAdd = [
                'tanggal_transaksi' => 'idx_tx_tanggal',
                'id_sumber_rekening' => 'idx_tx_sumber',
                'id_muzaki' => 'idx_tx_muzaki',
                'id_upz' => 'idx_tx_upz',
                'id_jenis_pembayaran' => 'idx_tx_jenis'
            ];

            foreach ($indexesToAdd as $col => $idxName) {
                if (!in_array($col, $existing, true)) {
                    $sql = "ALTER TABLE transaksi ADD INDEX `$idxName` (`$col`)";
                    if ($conn->query($sql)) {
                        $added[] = "$col ($idxName)";
                    }
                }
            }

            log_audit('DOCTOR_ADD_INDEXES', 'transaksi', 0, 'Menambahkan indeks performa: ' . implode(', ', $added));
            return [
                'success' => true,
                'message' => count($added) > 0 
                    ? 'Berhasil menambahkan ' . count($added) . ' indeks performa pada tabel transaksi.' 
                    : 'Seluruh kolom penting pada tabel transaksi telah terindeks dengan baik.',
                'details' => $added
            ];

        case 'clean_temp_files':
            $cleanedCount = 0;
            $freedBytes = 0;

            // Bersihkan file cache
            $cacheDir = __DIR__ . '/../assets/cache';
            if (is_dir($cacheDir)) {
                foreach (glob($cacheDir . '/*') as $f) {
                    if (is_file($f)) {
                        $freedBytes += filesize($f);
                        @unlink($f);
                        $cleanedCount++;
                    }
                }
            }

            // Bersihkan upload sementara yang tidak tercatat di rekening_koran
            $uploadDir = __DIR__ . '/../assets/uploads/rekening_koran';
            if (is_dir($uploadDir)) {
                $registeredFiles = [];
                $resRk = $conn->query("SELECT file_path FROM rekening_koran WHERE file_path IS NOT NULL");
                if ($resRk) {
                    while ($rk = $resRk->fetch_assoc()) {
                        $registeredFiles[] = basename($rk['file_path']);
                    }
                }

                foreach (glob($uploadDir . '/*') as $f) {
                    if (is_file($f)) {
                        $bName = basename($f);
                        // Hapus file RK_UPLOAD_ sementara yang berusia lebih dari 2 hari dan tidak terdaftar di DB
                        if (strpos($bName, 'RK_UPLOAD_') === 0 && !in_array($bName, $registeredFiles, true)) {
                            if ((time() - filemtime($f)) > 86400 * 2) {
                                $freedBytes += filesize($f);
                                @unlink($f);
                                $cleanedCount++;
                            }
                        }
                    }
                }
            }

            log_audit('DOCTOR_CLEAN_TEMP', 'storage', 0, "Membersihkan $cleanedCount file sampah (" . doctor_format_bytes($freedBytes) . ")");
            return [
                'success' => true,
                'message' => "Pembersihan selesai. $cleanedCount berkas sementara berhasil dihapus (ruang pulih: " . doctor_format_bytes($freedBytes) . ").",
                'details' => ['cleaned_count' => $cleanedCount, 'freed_bytes' => $freedBytes]
            ];

        case 'clear_cache':
            if (function_exists('cache_clear')) {
                cache_clear();
            }
            log_audit('DOCTOR_CLEAR_CACHE', 'cache', 0, 'Membersihkan seluruh cache sistem');
            return [
                'success' => true,
                'message' => 'Cache aplikasi berhasil dibersihkan.',
                'details' => []
            ];

        default:
            return [
                'success' => false,
                'message' => 'Tindakan perbaikan tidak dikenal.',
                'details' => []
            ];
    }
}

/**
 * Format bytes ke string yang mudah dibaca manusia (KB, MB, GB)
 */
function doctor_format_bytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Mengubah format string memory_limit PHP (e.g. 256M, 1G) ke bytes
 */
function doctor_parse_bytes(string $val): int
{
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $num = (int)$val;
    switch ($last) {
        case 'g': $num *= 1024 * 1024 * 1024; break;
        case 'm': $num *= 1024 * 1024; break;
        case 'k': $num *= 1024; break;
    }
    return $num;
}
