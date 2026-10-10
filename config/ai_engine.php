<?php
/**
 * AI Engine & Copilot Intelligence
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 */

require_once __DIR__ . '/helpers.php';

function ai_get_config(): array
{
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/ai_config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
        } else {
            $config = [
                'api_key' => getenv('GEMINI_API_KEY') ?: '',
                'assistant_name' => 'Amil Virtual BAZNAS Sumbawa',
                'model_chain' => ['gemini-3.5-flash-lite', 'gemini-3.6-flash', 'gemini-flash-latest'],
                'timeout_seconds' => 12,
                'temperature' => 0.2,
                'max_output_tokens' => 1800,
                'forbidden_sql_keywords' => ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 'RENAME', 'REPLACE', 'CREATE', 'EXEC']
            ];
        }
    }
    return $config;
}

/**
 * Invalidate Cache Konteks Skema AI (dipanggil saat ada transaksi baru/update/delete)
 */
function ai_invalidate_schema_cache(): void
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_SESSION['ai_schema_context_cache'])) {
        unset($_SESSION['ai_schema_context_cache']);
    }
}

/**
 * Mengambil ringkasan skema dan konteks sistem untuk disuntikkan ke prompt AI (Dengan Caching Pintar 5 Menit)
 */
function ai_build_database_schema_context(mysqli $conn, bool $forceRefresh = false): string
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $tahunIni = (int)date('Y');
    $cacheKey = "schema_ctx_{$tahunIni}";

    if (!$forceRefresh && !empty($_SESSION['ai_schema_context_cache'][$cacheKey])) {
        $cached = $_SESSION['ai_schema_context_cache'][$cacheKey];
        if (!empty($cached['data']) && !empty($cached['expires_at']) && time() < $cached['expires_at']) {
            return $cached['data'];
        }
    }

    // 1. Target RKAT tahun berjalan
    $targetRkat = 0;
    try {
        $resTarget = $conn->query("SELECT nominal_target FROM target_pengumpulan_tahunan WHERE tahun = {$tahunIni} LIMIT 1");
        if ($resTarget && $rowT = $resTarget->fetch_assoc()) {
            $targetRkat = (float)$rowT['nominal_target'];
        }
    } catch (Throwable $e) {}

    // 2. Realisasi tahun berjalan & perincian bulanan aktual
    $totalRealisasi = 0;
    $totalTrx = 0;
    $monthlyStats = [];
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    try {
        $resTrx = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(jumlah_pembayaran), 0) as total FROM transaksi WHERE YEAR(tanggal_transaksi) = {$tahunIni}");
        if ($resTrx && $rowX = $resTrx->fetch_assoc()) {
            $totalTrx = (int)$rowX['cnt'];
            $totalRealisasi = (float)$rowX['total'];
        }

        $resM = $conn->query("SELECT MONTH(tanggal_transaksi) as bln, COUNT(*) as cnt, COALESCE(SUM(jumlah_pembayaran), 0) as tot FROM transaksi WHERE YEAR(tanggal_transaksi) = {$tahunIni} GROUP BY bln ORDER BY bln ASC");
        if ($resM) {
            while ($rm = $resM->fetch_assoc()) {
                $bNum = (int)$rm['bln'];
                $bName = $namaBulan[$bNum] ?? "Bulan {$bNum}";
                $monthlyStats[] = "   â€¢ {$bName} ({$bNum}): Rp " . number_format($rm['tot'], 0, ',', '.') . " ({$rm['cnt']} transaksi)";
            }
        }
    } catch (Throwable $e) {}
    $strMonthly = !empty($monthlyStats) ? implode("\n", $monthlyStats) : "   Belum ada transaksi di tahun {$tahunIni}.";

    // 3. Daftar Bank / Sumber Rekening Aktif
    $bankList = [];
    try {
        $resBank = $conn->query("SELECT id, nama_sumber FROM sumber_rekening ORDER BY id ASC");
        if ($resBank) {
            while ($b = $resBank->fetch_assoc()) {
                $bankList[] = "[ID {$b['id']}] {$b['nama_sumber']}";
            }
        }
    } catch (Throwable $e) {}

    // 4. Daftar Jenis & Sub-Jenis ZIS
    $jenisList = [];
    try {
        $resJenis = $conn->query("SELECT id, nama_jenis FROM jenis_pembayaran ORDER BY id ASC");
        if ($resJenis) {
            while ($j = $resJenis->fetch_assoc()) {
                $jenisList[] = "[ID {$j['id']}] {$j['nama_jenis']}";
            }
        }
    } catch (Throwable $e) {}

    $subJenisList = [];
    try {
        $resSub = $conn->query("SELECT sjp.id, sjp.nama_sub_jenis, jp.nama_jenis FROM sub_jenis_pembayaran sjp JOIN jenis_pembayaran jp ON sjp.id_jenis_pembayaran = jp.id LIMIT 10");
        if ($resSub) {
            while ($sj = $resSub->fetch_assoc()) {
                $subJenisList[] = "{$sj['nama_sub_jenis']} ({$sj['nama_jenis']})";
            }
        }
    } catch (Throwable $e) {}

    // 5. Kategori UPZ
    $katUpzList = [];
    try {
        $resKu = $conn->query("SELECT id, nama_kategori FROM kategori_upz ORDER BY urutan ASC, id ASC");
        if ($resKu) {
            while ($ku = $resKu->fetch_assoc()) {
                $katUpzList[] = "[ID {$ku['id']}] {$ku['nama_kategori']}";
            }
        }
    } catch (Throwable $e) {}

    // 6. Rekening Koran
    $countRk = 0;
    try {
        $resRk = $conn->query("SELECT COUNT(*) as cnt FROM rekening_koran");
        if ($resRk && $rkRow = $resRk->fetch_assoc()) {
            $countRk = (int)$rkRow['cnt'];
        }
    } catch (Throwable $e) {}

    // 7. Dokumen Program Bantuan yang Aktif
    $katDokList = [];
    try {
        $resDok = $conn->query("SELECT id, kode_kategori, nama_kategori FROM dokumen_kategori WHERE status = 'aktif'");
        if ($resDok) {
            while ($kd = $resDok->fetch_assoc()) {
                $katDokList[] = "[ID {$kd['id']}] {$kd['nama_kategori']}";
            }
        }
    } catch (Throwable $e) {}

    // 8. Daftar Master UPZ Resmi Instansi/Dinas/Kecamatan (dengan kode rekening OPD)
    $upzCatalog = [];
    try {
        $resUpz = $conn->query("SELECT id, nama_upz FROM upz WHERE nama_upz LIKE '%|%' OR id <= 40 ORDER BY id ASC LIMIT 50");
        if ($resUpz) {
            while ($u = $resUpz->fetch_assoc()) {
                $upzCatalog[] = "  â€¢ ID {$u['id']}: {$u['nama_upz']}";
            }
        }
    } catch (Throwable $e) {}
    $strUpzCatalog = !empty($upzCatalog) ? implode("\n", $upzCatalog) : "  â€¢ (Data UPZ ada di tabel upz)";

    $strBank = implode(", ", array_slice($bankList, 0, 8));
    $strJenis = implode(", ", array_slice($jenisList, 0, 8));
    $strSubJenis = implode(", ", array_slice($subJenisList, 0, 8));
    $strKatUpz = implode(", ", array_slice($katUpzList, 0, 8));
    $strDok = implode(", ", array_slice($katDokList, 0, 6));

    $baseUrl = url('');

    $contextResult = <<<EOT
IDENTITAS SISTEM:
Nama Sistem: SIM Pengumpulan Zakat, Infak, Sedekah (ZIS) BAZNAS Kabupaten Sumbawa.
Tahun Berjalan: {$tahunIni}
Target RKAT {$tahunIni}: Rp {$targetRkat}
Total Realisasi Terkumpul {$tahunIni}: Rp {$totalRealisasi} ({$totalTrx} transaksi)

DATA PENGUMPULAN REAL-TIME {$tahunIni} PER BULAN:
{$strMonthly}

MASTER DATA TERSEDIA:
â€¢ Sumber Rekening / Bank: {$strBank}
â€¢ Jenis ZIS: {$strJenis}
â€¢ Sub-Jenis ZIS: {$strSubJenis}
â€¢ Kategori UPZ: {$strKatUpz}
â€¢ Berkas Rekening Koran Terarsip: {$countRk} berkas mutasi bank
â€¢ Program Bantuan Dokumen (Mustahik): {$strDok}

DAFTAR MASTER UPZ PEMKAB SUMBAWA & KODE REKENING (PENTING!):
Sebagian besar nama UPZ dinas/badan/kantor/kecamatan memuat kode rekening di belakang tanda pipa '|'.
Jika amil menyebut 'Dinas Kesehatan' atau 'Dinkes', gunakan ID 4 (DINAS KESEHATAN| 1.02.0.00.0.00.02.0000).
Jika amil menyebut 'Dikbud' / 'Pendidikan' / 'P&K', gunakan ID 3 (DINAS DIKBUD | 1.01.2.22.0.00.01.0000).
Jika amil menyebut 'Kecamatan Alas', gunakan ID 33.
Jika amil menyebut 'Satpol PP', gunakan ID 7.
Katalog Master UPZ Terdaftar:
{$strUpzCatalog}

ATURAN PEMILIHAN ID_SUMBER_REKENING & METODE:
â€¢ Sumber Rekening (id_sumber_rekening):
  - ID 1: BANK NTB SYARIAH ZAKAT (Transfer)
  - ID 5: BANK NTB SYARIAH INFAK (Transfer)
  - ID 6: BANK NTB SYARIAH ZAKAT (Transfer)
  - ID 8: BANK MANDIRI INFAK (Transfer)
  - ID 9: BANK MANDIRI ZAKAT (Transfer)
  - ID 10: BANK BRI ZAKAT (Transfer)
  - ID 11: BUKU PENGUMPULAN (Kas Tunai BAZNAS / Tunai)
â€¢ Jenis ZIS (id_jenis_pembayaran) & Sub-Jenis (id_sub_jenis_pembayaran):
  - ID 6: Zakat (Sub-jenis: 10 Zakat Maal Perorangan, atau 11 Zakat Maal Perorangan Via UPZ jika UPZ)
  - ID 7: Infak dan sedekah (Sub-jenis: 19 Infak dan Sedekah Tidak Terikat, atau 20 Infak dan Sedekah Tidak Terikat Via UPZ jika UPZ)
  - ID 8: DSKL (Sub-jenis: 26 FIDYAH)
  - ID 11: CSR
â€¢ Metode Pembayaran (id_metode_pembayaran):
  - ID 4: Transfer Bank
  - ID 5: Tunai
â€¢ Kategori Dana: ID 2 (Pengumpulan)

STRUKTUR TABEL BASIS DATA SIM BAZNAS (SEMUA DAPAT DITELUSURI DENGAN SELECT):
1. `transaksi` (t):
   - Kolom: id, tanggal_transaksi (DATE), id_muzaki, id_upz, id_jenis_pembayaran, id_sub_jenis_pembayaran, id_metode_pembayaran, id_kategori_dana, id_sumber_rekening, jumlah_pembayaran (DECIMAL), keterangan (TEXT)
   - Relasi Join:
     LEFT JOIN muzaki m ON t.id_muzaki = m.id (nama_muzaki, alamat, no_ktp, no_hp)
     LEFT JOIN upz u ON t.id_upz = u.id (nama_upz, alamat, kontak, id_kategori_upz)
     LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id (nama_kategori as nama_kategori_upz)
     LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id (nama_jenis)
     LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id (nama_sub_jenis)
     LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id (nama_sumber)
     LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id (nama_metode)
     LEFT JOIN kategori_dana kd ON t.id_kategori_dana = kd.id (nama_kategori as nama_kategori_dana)

2. `upz` (u):
   - Kolom: id, nama_upz, id_kategori_upz, alamat, kontak
   - Relasi: JOIN kategori_upz ku ON u.id_kategori_upz = ku.id

3. `muzaki` (m):
   - Kolom: id, nama_muzaki, alamat, no_ktp, no_hp, id_status_muzaki, id_upz_sekarang

4. `rekening_koran` (rk):
   - Arsip file mutasi rekening koran bank yang diunggah amil.
   - Kolom: id, id_sumber_rekening, bulan (YYYY-MM), jumlah_kredit (DECIMAL), nama_file_original, created_at
   - Relasi: JOIN sumber_rekening sr ON rk.id_sumber_rekening = sr.id

5. `target_pengumpulan_tahunan`:
   - Kolom: id, tahun (YEAR), nominal_target (DECIMAL), keterangan

6. `target_pengumpulan_per_jenis`:
   - Target RKAT per jenis ZIS (Zakat, Infak, dll)
   - Kolom: id, tahun (YEAR), id_jenis_pembayaran, nominal_target (DECIMAL), keterangan
   - Relasi: JOIN jenis_pembayaran jp ON target_pengumpulan_per_jenis.id_jenis_pembayaran = jp.id

7. `dokumen_kategori` (dk) & `dokumen_penerima_data`:
   - Modul bantuan program BAZNAS (seperti Gerobak Usaha, Mahyani, dll).
   - Kolom `dokumen_kategori`: id, kode_kategori, nama_kategori, status
   - Kolom `dokumen_penerima_data`: id, id_kategori_dokumen, data_values (JSON), nominal_total, catatan_khusus, created_at

DAFTAR URL HALAMAN SISTEM UNTUK QUICK ACTIONS:
â€¢ Halaman Transaksi: {$baseUrl}pages/transaksi/index.php
â€¢ Rekap / Laporan Tahunan: {$baseUrl}pages/laporan_tahunan/index.php
â€¢ Rekap Pengumpulan: {$baseUrl}pages/laporan_rekap/index.php
â€¢ Rekening Koran Bank: {$baseUrl}pages/rekening_koran/index.php
â€¢ Master UPZ: {$baseUrl}pages/upz/index.php
â€¢ Master Muzaki: {$baseUrl}pages/muzaki/index.php
â€¢ Modul Dokumen Program: {$baseUrl}pages/dokumen_program/index.php
â€¢ Target RKAT: {$baseUrl}pages/settings/rkat.php
EOT;

    // Simpan ke cache sesi selama 5 menit (300 detik)
    $_SESSION['ai_schema_context_cache'][$cacheKey] = [
        'data' => $contextResult,
        'expires_at' => time() + 300
    ];

    return $contextResult;
}

/**
 * Memvalidasi dan mengeksekusi kueri SQL secara aman (Strict Read-Only)
 */
function ai_safe_execute_query(mysqli $conn, string $sql): array
{
    $cleanSql = trim($sql);

    // Wajib diawali SELECT
    if (!preg_match('/^SELECT\s+/i', $cleanSql)) {
        return ['success' => false, 'error' => 'Hanya query SELECT yang diizinkan untuk keamanan data.'];
    }

    // 1. Bersihkan tanda titik koma (;) di akhir kueri yang sering disertakan model AI
    $cleanSql = rtrim($cleanSql, "; \t\n\r\0\x0B");

    // Hapus string literal sebelum cek keyword (mencegah false positive pada alias atau keterangan)
    $sqlNoStrings = preg_replace('/\'[^\']*\'/', '""', $cleanSql);
    $sqlNoStrings = preg_replace('/"[^"]*"/', '""', $sqlNoStrings);
    $sqlNoStrings = rtrim($sqlNoStrings, "; \t\n\r\0\x0B");

    // Blokir mutlak keyword manipulasi (dicek pada SQL tanpa string literal)
    $forbidden = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 'RENAME', 'REPLACE', 'EXEC'];
    foreach ($forbidden as $kw) {
        if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $sqlNoStrings)) {
            return ['success' => false, 'error' => "Kata kunci '{$kw}' dilarang demi keamanan sistem."];
        }
    }

    // Blokir komentar SQL dan multi-statement di tengah kueri
    if (strpos($sqlNoStrings, '--') !== false || strpos($sqlNoStrings, ';') !== false) {
        return ['success' => false, 'error' => 'Komentar SQL dan multi-statement dilarang.'];
    }

    // Whitelist tabel yang boleh diakses
    $allowedTables = ['transaksi', 'muzaki', 'upz', 'kategori_upz', 'jenis_pembayaran',
        'sub_jenis_pembayaran', 'metode_pembayaran', 'kategori_dana', 'sumber_rekening',
        'rekening_koran', 'target_pengumpulan_tahunan', 'target_pengumpulan_per_jenis',
        'dokumen_kategori', 'dokumen_penerima_data', 'system_changelog', 'audit_log'];
    preg_match_all('/\b(?:FROM|JOIN)\s+([a-z_]+)/i', $sqlNoStrings, $tableMatches);
    if (!empty($tableMatches[1])) {
        foreach ($tableMatches[1] as $tbl) {
            if (!in_array(strtolower($tbl), $allowedTables)) {
                return ['success' => false, 'error' => "Akses ke tabel '{$tbl}' tidak diizinkan."];
            }
        }
    }

    // Tampilkan data secara penuh tanpa pembatasan kaku (Safety cap 3000 jika tanpa limit)
    if (!preg_match('/\bLIMIT\s+\d+/i', $cleanSql)) {
        $cleanSql .= ' LIMIT 3000';
    }

    try {
        $res = $conn->query($cleanSql);
        if (!$res) {
            return ['success' => false, 'error' => $conn->error, 'sql' => $cleanSql];
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }

        return ['success' => true, 'rows' => $rows, 'count' => count($rows), 'sql' => $cleanSql];
    } catch (Throwable $e) {
        error_log("[AI Safe Query Error] " . $e->getMessage() . " on SQL: " . $cleanSql);
        return ['success' => false, 'error' => $e->getMessage(), 'sql' => $cleanSql];
    }
}

/**
 * Memastikan tabel riwayat chat tersedia di database
 */
function ai_ensure_history_table(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;

    try {
        $sql = "CREATE TABLE IF NOT EXISTS ai_chat_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            user_message TEXT NOT NULL,
            ai_reply LONGTEXT NOT NULL,
            quick_actions TEXT NULL,
            sql_used TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_history (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $conn->query($sql);
    } catch (Throwable $e) {
        error_log("[AI History Table Error] " . $e->getMessage());
    }
}

/**
 * Akselerasi Pencarian Semantik & Agregasi Cerdas (Smart Query Indexing)
 * Memastikan indeks komposit berkinerja tinggi tersedia untuk transaksi, UPZ, dan Muzaki
 */
function ai_ensure_smart_indexes(mysqli $conn): array
{
    $results = ['applied' => [], 'already_existed' => [], 'errors' => []];

    $smartIndexes = [
        'transaksi' => [
            'idx_tx_tgl_kat' => 'ADD INDEX idx_tx_tgl_kat (tanggal_transaksi, id_kategori_dana, jumlah_pembayaran)',
            'idx_tx_upz_tgl' => 'ADD INDEX idx_tx_upz_tgl (id_upz, tanggal_transaksi, jumlah_pembayaran)',
            'idx_tx_rek_tgl' => 'ADD INDEX idx_tx_rek_tgl (id_sumber_rekening, tanggal_transaksi, jumlah_pembayaran)',
        ],
        'upz' => [
            'idx_upz_nama' => 'ADD INDEX idx_upz_nama (nama_upz)',
        ],
        'muzaki' => [
            'idx_muzaki_nama' => 'ADD INDEX idx_muzaki_nama (nama_muzaki)',
        ]
    ];

    foreach ($smartIndexes as $table => $indexes) {
        $existing = [];
        try {
            $res = $conn->query("SHOW INDEX FROM `{$table}`");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $existing[$row['Key_name']] = true;
                }
            }
        } catch (Throwable $e) {
            $results['errors'][] = "Gagal membaca indeks tabel {$table}: " . $e->getMessage();
            continue;
        }

        foreach ($indexes as $indexName => $alterClause) {
            if (isset($existing[$indexName])) {
                $results['already_existed'][] = "{$table}.{$indexName}";
            } else {
                try {
                    $alterSql = "ALTER TABLE `{$table}` {$alterClause}";
                    if ($conn->query($alterSql)) {
                        $results['applied'][] = "{$table}.{$indexName}";
                    } else {
                        $results['errors'][] = "Gagal membuat indeks {$table}.{$indexName}: " . $conn->error;
                    }
                } catch (Throwable $e) {
                    $results['errors'][] = "Pengecualian {$table}.{$indexName}: " . $e->getMessage();
                }
            }
        }
    }

    return $results;
}

/**
 * Mengambil riwayat percakapan untuk user tertentu (Ultra Cepat & Efisien)
 */
function ai_get_user_history(mysqli $conn, int $userId, int $limit = 15): array
{
    try {
        $stmt = $conn->prepare("SELECT id, user_message, ai_reply, quick_actions, created_at 
                                FROM ai_chat_history 
                                WHERE user_id = ? 
                                ORDER BY id DESC LIMIT ?");
        if (!$stmt) {
            ai_ensure_history_table($conn);
            return [];
        }
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $r['quick_actions'] = !empty($r['quick_actions']) ? json_decode($r['quick_actions'], true) : [];
            $list[] = $r;
        }
        $stmt->close();
        return array_reverse($list);
    } catch (Throwable $e) {
        error_log("[AI Get History Error] " . $e->getMessage());
        return [];
    }
}

/**
 * Menyimpan riwayat obrolan baru ke database
 */
function ai_save_user_history(mysqli $conn, int $userId, string $userMessage, string $aiReply, array $quickActions = [], ?string $sqlUsed = null): void
{
    try {
        ai_ensure_history_table($conn);
        $cleanReply = ai_format_markdown_to_html($aiReply);
        $jsonActions = !empty($quickActions) ? json_encode($quickActions) : null;
        $stmt = $conn->prepare("INSERT INTO ai_chat_history (user_id, user_message, ai_reply, quick_actions, sql_used) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('issss', $userId, $userMessage, $cleanReply, $jsonActions, $sqlUsed);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("[AI Save History Error] " . $e->getMessage());
    }
}

/**
 * Menghapus riwayat percakapan untuk user tertentu
 */
function ai_clear_user_history(mysqli $conn, int $userId): void
{
    try {
        ai_ensure_history_table($conn);
        $stmt = $conn->prepare("DELETE FROM ai_chat_history WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log("[AI Clear History Error] " . $e->getMessage());
    }
}

/**
 * Memanggil Google Gemini API via cURL dengan Dukungan Multi-Turn Context Memory & Fallback Chain
 */
function ai_call_gemini(string $systemPrompt, string $userMessage, array $config, array $historyTurns = []): ?string
{
    $apiKey = $config['api_key'] ?? '';
    if (empty($apiKey)) {
        return null;
    }

    $models = $config['model_chain'] ?? ['gemini-3.6-flash', 'gemini-3.5-flash-lite'];
    $timeout = (int)($config['timeout_seconds'] ?? 15);
    $temperature = (float)($config['temperature'] ?? 0.08);

    $contents = [];

    // Jika ada riwayat percakapan sebelumnya (Multi-Turn Conversational Memory)
    if (!empty($historyTurns)) {
        foreach ($historyTurns as $turn) {
            $uMsg = trim($turn['user_message'] ?? '');
            $aReply = trim($turn['ai_summary'] ?? '');
            if (!empty($uMsg) && !empty($aReply)) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $uMsg]]
                ];
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => $aReply]]
                ];
            }
        }
    }

    // Pertanyaan aktif saat ini
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $userMessage]]
    ];

    $payload = [
        'system_instruction' => [
            'parts' => [
                ['text' => $systemPrompt]
            ]
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => $temperature,
            'maxOutputTokens' => (int)($config['max_output_tokens'] ?? 2800),
            'topP' => (float)($config['top_p'] ?? 0.90),
            'topK' => (int)($config['top_k'] ?? 30)
        ]
    ];

    $jsonPayload = json_encode($payload);

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            $fullText = '';
            if (!empty($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $fullText .= $part['text'];
                    }
                }
            }
            if (!empty(trim($fullText))) {
                return trim($fullText);
            }
        }
    }

    return null;
}

/**
 * Memanggil Google Gemini Vision API via cURL dengan Multimodal Image (OCR Struk & Slip Transfer)
 */
function ai_call_gemini_vision(string $prompt, string $base64Image, string $mimeType = 'image/jpeg', array $config = []): ?string
{
    if (empty($config)) {
        $config = ai_get_config();
    }
    $apiKey = $config['api_key'] ?? '';
    if (empty($apiKey)) {
        return null;
    }

    $models = ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-2.0-flash'];
    $timeout = (int)($config['timeout_seconds'] ?? 25);

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $base64Image
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 1200
        ]
    ];

    $jsonPayload = json_encode($payload);

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            $fullText = '';
            if (!empty($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $fullText .= $part['text'];
                    }
                }
            }
            if (!empty(trim($fullText))) {
                return trim($fullText);
            }
        }
    }

    return null;
}

/**
 * Pemindai Bukti Transfer & Kuitansi (Multimodal OCR / Slip Scanner)
 */
function ai_parse_transfer_slip(mysqli $conn, string $base64Image, string $mimeType = 'image/jpeg'): array
{
    $bankNames = [];
    $resB = $conn->query("SELECT id, nama_sumber FROM sumber_rekening ORDER BY id ASC");
    if ($resB) {
        while ($b = $resB->fetch_assoc()) {
            $bankNames[] = "[ID: {$b['id']}] {$b['nama_sumber']}";
        }
    }
    $strBanks = implode(', ', $bankNames);

    $prompt = <<<PROMPT
Anda adalah asisten AI OCR Pembukuan ZIS BAZNAS Kabupaten Sumbawa.
Tugas Anda adalah membaca gambar bukti transfer bank (m-banking, ATM, teller, slip setoran, kuitansi) secara teliti.
Ekstrak informasi transaksi ini dan kembalikan HANYA format JSON murni tanpa markdown, tanpa penjelasan di luar JSON:
{
  "tanggal": "YYYY-MM-DD",
  "nominal": 500000,
  "pengirim": "Nama lengkap pembayar / pengirim / UPZ",
  "penerima": "Nama penerima / BAZNAS",
  "bank_tujuan": "Nama bank penerima (misal Bank NTB Syariah, BSI, BNI, dll)",
  "nomor_referensi": "Nomor transaksi / ref / RRN jika ada",
  "keterangan": "Keterangan setoran ZIS",
  "jenis_dana_prediksi": "Zakat Profesi | Infak / Sedekah | Zakat Maal | DSKL",
  "confidence_score": 95
}
Daftar Rekening Bank Aktif di BAZNAS Sumbawa: {$strBanks}
Jika tahun tidak tercantum, gunakan tahun berjalan. Jika nominal ada pemisah titik/koma, kembalikan hanya angka murni sebagai integer.
PROMPT;

    $aiRaw = ai_call_gemini_vision($prompt, $base64Image, $mimeType);

    $tanggal = date('Y-m-d');
    $nominal = 0;
    $pengirim = 'Muzaki Umum';
    $bankTujuan = '';
    $nomorRef = '-';
    $keterangan = 'Setoran ZIS via Transfer Bank';
    $jenisPrediksi = 'Infak / Sedekah';
    $confidence = 0;

    if (!empty($aiRaw)) {
        $cleanJson = trim($aiRaw);
        if (strpos($cleanJson, '```') !== false) {
            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', $cleanJson);
            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
            $cleanJson = trim($cleanJson);
        }
        $parsed = json_decode($cleanJson, true);
        if ($parsed && is_array($parsed)) {
            if (!empty($parsed['tanggal'])) $tanggal = $parsed['tanggal'];
            if (!empty($parsed['nominal'])) $nominal = (float)$parsed['nominal'];
            if (!empty($parsed['pengirim'])) $pengirim = trim($parsed['pengirim']);
            if (!empty($parsed['bank_tujuan'])) $bankTujuan = trim($parsed['bank_tujuan']);
            if (!empty($parsed['nomor_referensi'])) $nomorRef = trim($parsed['nomor_referensi']);
            if (!empty($parsed['keterangan'])) $keterangan = trim($parsed['keterangan']);
            if (!empty($parsed['jenis_dana_prediksi'])) $jenisPrediksi = trim($parsed['jenis_dana_prediksi']);
            if (!empty($parsed['confidence_score'])) $confidence = (int)$parsed['confidence_score'];
        }
    }

    $idUpz = null;
    $idMuzaki = null;
    $tipePembayar = 'muzaki';
    $namaPembayarTerdaftar = $pengirim;

    $safePengirim = $conn->real_escape_string($pengirim);
    $qUpz = $conn->query("SELECT id, nama_upz FROM upz WHERE nama_upz LIKE '%{$safePengirim}%' LIMIT 1");
    if ($qUpz && $rUpz = $qUpz->fetch_assoc()) {
        $idUpz = (int)$rUpz['id'];
        $tipePembayar = 'upz';
        $namaPembayarTerdaftar = $rUpz['nama_upz'];
    } else {
        $qMuz = $conn->query("SELECT id, nama_muzaki FROM muzaki WHERE nama_muzaki LIKE '%{$safePengirim}%' LIMIT 1");
        if ($qMuz && $rMuz = $qMuz->fetch_assoc()) {
            $idMuzaki = (int)$rMuz['id'];
            $tipePembayar = 'muzaki';
            $namaPembayarTerdaftar = $rMuz['nama_muzaki'];
        }
    }

    $idSumber = 1;
    $namaSumber = 'Bank NTB Syariah';
    if (!empty($bankTujuan)) {
        $safeBank = $conn->real_escape_string($bankTujuan);
        $qS = $conn->query("SELECT id, nama_sumber FROM sumber_rekening WHERE nama_sumber LIKE '%{$safeBank}%' LIMIT 1");
        if ($qS && $rS = $qS->fetch_assoc()) {
            $idSumber = (int)$rS['id'];
            $namaSumber = $rS['nama_sumber'];
        }
    }

    $idJenis = 1;
    $namaJenis = 'Zakat Profesi';
    $safeJenis = $conn->real_escape_string($jenisPrediksi);
    $qJ = $conn->query("SELECT id, nama_jenis FROM jenis_pembayaran WHERE nama_jenis LIKE '%{$safeJenis}%' LIMIT 1");
    if ($qJ && $rJ = $qJ->fetch_assoc()) {
        $idJenis = (int)$rJ['id'];
        $namaJenis = $rJ['nama_jenis'];
    }

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $_SESSION['ai_pending_action'] = [
        'action' => 'create',
        'data' => [
            'tanggal_transaksi' => $tanggal,
            'tipe_pembayar' => $tipePembayar,
            'id_upz' => $idUpz,
            'id_muzaki' => $idMuzaki,
            'nama_pembayar' => $namaPembayarTerdaftar,
            'id_jenis_pembayaran' => $idJenis,
            'id_metode_pembayaran' => 4,
            'id_sumber_rekening' => $idSumber,
            'jumlah_pembayaran' => $nominal,
            'keterangan' => $keterangan . ($nomorRef !== '-' ? " (Ref: {$nomorRef})" : "")
        ],
        'summary' => "Setoran " . ($nominal > 0 ? "Rp " . number_format($nominal, 0, ',', '.') : "") . " dari {$namaPembayarTerdaftar}"
    ];

    $urlMassal = url("pages/transaksi/massal.php?prefill_tgl={$tanggal}&prefill_nominal={$nominal}&prefill_nama=" . urlencode($namaPembayarTerdaftar) . "&prefill_bank={$idSumber}&prefill_jenis={$idJenis}");
    $nominalFormatted = "Rp " . number_format($nominal, 0, ',', '.');

    $cardHtml = <<<HTML
<div class="ai-ocr-card ai-custom-card my-2 p-3" style="border: 1.5px solid #0d9f4f; border-radius: 6px; box-shadow: none;">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div class="font-weight-bold" style="color: #0d9f4f; font-size: 0.92rem;">
            <i class="fas fa-receipt mr-1"></i> Hasil Pemindaian Bukti Transfer (OCR AI)
        </div>
        <span class="badge" style="background-color: #f0f7f3; color: #0d9f4f; border: 1px solid #b8b8b8; font-size: 0.72rem;">
            Akurasi: {$confidence}%
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-borderless mb-2 ai-card-table" style="font-size: 0.83rem;">
            <tr>
                <td class="text-muted" style="width: 32%; padding: 2px 0;">Nominal Terdeteksi</td>
                <td style="width: 68%; font-weight: 700; color: #0d9f4f; font-size: 1.05rem; padding: 2px 0;">
                    {$nominalFormatted}
                </td>
            </tr>
            <tr>
                <td class="text-muted" style="padding: 2px 0;">Tanggal</td>
                <td style="font-weight: 600; padding: 2px 0;">{$tanggal}</td>
            </tr>
            <tr>
                <td class="text-muted" style="padding: 2px 0;">Pengirim / Penyetor</td>
                <td style="font-weight: 600; padding: 2px 0;">
                    {$namaPembayarTerdaftar} 
                    <span class="badge badge-light border ml-1" style="border-color:#b8b8b8 !important;">{$tipePembayar}</span>
                </td>
            </tr>
            <tr>
                <td class="text-muted" style="padding: 2px 0;">Rekening Tujuan</td>
                <td style="font-weight: 600; padding: 2px 0;">{$namaSumber}</td>
            </tr>
            <tr>
                <td class="text-muted" style="padding: 2px 0;">Alokasi Jenis Dana</td>
                <td style="font-weight: 600; padding: 2px 0;">{$namaJenis}</td>
            </tr>
            <tr>
                <td class="text-muted" style="padding: 2px 0;">Nomor Referensi</td>
                <td style="font-family: monospace; padding: 2px 0;">{$nomorRef}</td>
            </tr>
        </table>
    </div>
    <div class="alert alert-light border py-1 px-2 mb-2 text-muted ai-card-alert" style="border-color: #b8b8b8 !important; font-size: 0.76rem;">
        <i class="fas fa-info-circle mr-1 text-info"></i> Draf telah disiapkan. Klik tombol di bawah untuk menyimpan langsung ke database atau membuka di formulir input massal.
    </div>
    <div class="d-flex flex-wrap" style="gap: 6px;">
        <button type="button" class="btn btn-xs btn-outline-success font-weight-bold px-2 py-1" onclick="sendAiQuickPrompt('ya simpan')" style="border: 1.5px solid #0d9f4f; border-radius: 4px; font-size: 0.78rem;">
            <i class="fas fa-check mr-1"></i> Simpan Transaksi Ini
        </button>
        <a href="{$urlMassal}" class="btn btn-xs btn-outline-primary font-weight-bold px-2 py-1" style="border: 1.5px solid #2b6cb0; border-radius: 4px; font-size: 0.78rem;">
            <i class="fas fa-external-link-alt mr-1"></i> Buka di Input Massal
        </a>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold px-2 py-1" onclick="sendAiQuickPrompt('batal')" style="border: 1px solid #b8b8b8; border-radius: 4px; font-size: 0.78rem;">
            <i class="fas fa-times mr-1"></i> Batalkan
        </button>
    </div>
</div>
HTML;

    return [
        'status' => 'success',
        'reply' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ’¾ Simpan Transaksi', 'prompt' => 'ya simpan'],
            ['label' => 'ðŸ“‹ Buka di Input Massal', 'url' => $urlMassal],
            ['label' => 'âŒ Batalkan', 'prompt' => 'batal']
        ]
    ];
}

/**
 * Deteksi Anomali & Audit Internal (AI Data Quality & Fraud Watchdog)
 */
function ai_run_data_watchdog(mysqli $conn, int $userId = 0): array
{
    $curYear = (int)date('Y');

    // 1. Transaksi Duplikat
    $duplicateRows = [];
    $qDup = "SELECT t1.id as id1, t2.id as id2, t1.tanggal_transaksi, t1.jumlah_pembayaran,
            COALESCE(u.nama_upz, m.nama_muzaki, 'Muzaki/UPZ') as nama_pembayar,
            t1.keterangan
            FROM transaksi t1
            LEFT JOIN upz u ON t1.id_upz = u.id
            LEFT JOIN muzaki m ON t1.id_muzaki = m.id
            JOIN transaksi t2 ON t1.id < t2.id 
              AND t1.jumlah_pembayaran = t2.jumlah_pembayaran 
              AND t1.tanggal_transaksi = t2.tanggal_transaksi
              AND (
                (t1.id_upz IS NOT NULL AND t1.id_upz = t2.id_upz) OR
                (t1.id_muzaki IS NOT NULL AND t1.id_muzaki = t2.id_muzaki)
              )
            WHERE YEAR(t1.tanggal_transaksi) >= {$curYear} - 1
            ORDER BY t1.tanggal_transaksi DESC
            LIMIT 5";
    $rDup = $conn->query($qDup);
    if ($rDup) {
        while ($rd = $rDup->fetch_assoc()) {
            $duplicateRows[] = $rd;
        }
    }
    $totalDupCount = count($duplicateRows);

    // 2. Anomali Tanggal & Waktu (Masa Depan / Terlalu Lampau)
    $dateAnomalies = [];
    $qDate = "SELECT id, tanggal_transaksi, jumlah_pembayaran, keterangan 
              FROM transaksi 
              WHERE tanggal_transaksi > CURRENT_DATE 
                 OR tanggal_transaksi < '2020-01-01'
              ORDER BY tanggal_transaksi DESC LIMIT 5";
    $rDate = $conn->query($qDate);
    if ($rDate) {
        while ($rda = $rDate->fetch_assoc()) {
            $dateAnomalies[] = $rda;
        }
    }
    $totalDateAnomalies = count($dateAnomalies);

    // 3. Anomali Nilai Transaksi (<= 0)
    $zeroRows = [];
    $qZero = "SELECT id, tanggal_transaksi, jumlah_pembayaran, keterangan 
              FROM transaksi 
              WHERE jumlah_pembayaran <= 0 
              ORDER BY id DESC LIMIT 5";
    $rZero = $conn->query($qZero);
    if ($rZero) {
        while ($rz = $rZero->fetch_assoc()) {
            $zeroRows[] = $rz;
        }
    }
    $totalZeroCount = count($zeroRows);

    // 4. Selisih Rekonsiliasi Rekening Koran Bank
    $rkVariances = [];
    $qRk = "SELECT rk.id, rk.bulan, sr.nama_sumber, rk.jumlah_kredit,
            (SELECT COALESCE(SUM(t.jumlah_pembayaran), 0) FROM transaksi t WHERE t.id_sumber_rekening = rk.id_sumber_rekening AND DATE_FORMAT(t.tanggal_transaksi, '%Y-%m') = rk.bulan) as total_transaksi
            FROM rekening_koran rk
            LEFT JOIN sumber_rekening sr ON rk.id_sumber_rekening = sr.id
            ORDER BY rk.bulan DESC LIMIT 4";
    $rRk = $conn->query($qRk);
    if ($rRk) {
        while ($rr = $rRk->fetch_assoc()) {
            $diff = abs((float)$rr['jumlah_kredit'] - (float)$rr['total_transaksi']);
            if ($diff > 1000) {
                $rkVariances[] = [
                    'bulan' => $rr['bulan'],
                    'bank' => $rr['nama_sumber'],
                    'kredit' => (float)$rr['jumlah_kredit'],
                    'transaksi' => (float)$rr['total_transaksi'],
                    'selisih' => $diff
                ];
            }
        }
    }

    // 5. Data Hygiene: Transaksi tanpa jenis / sumber rekening
    $hygieneCount = 0;
    $qHyg = $conn->query("SELECT COUNT(*) as cnt FROM transaksi WHERE id_sumber_rekening IS NULL OR id_jenis_pembayaran IS NULL");
    if ($qHyg && $rh = $qHyg->fetch_assoc()) {
        $hygieneCount = (int)$rh['cnt'];
    }

    // Perhitungan Skor Integritas & Kesehatan Data (0-100)
    $score = 100;
    if ($totalDateAnomalies > 0) $score -= min(30, $totalDateAnomalies * 15);
    if ($totalDupCount > 0) $score -= min(25, $totalDupCount * 5);
    if ($totalZeroCount > 0) $score -= min(20, $totalZeroCount * 10);
    if (!empty($rkVariances)) $score -= min(15, count($rkVariances) * 3);
    if ($hygieneCount > 0) $score -= min(10, $hygieneCount * 2);
    if ($score < 0) $score = 0;

    $predikat = 'Sangat Sehat & Andal (A)';
    $badgeColor = '#0d9f4f';
    $badgeBg = '#f0f7f3';
    if ($score < 70) {
        $predikat = 'Kritis - Butuh Tindakan (C)';
        $badgeColor = '#dc2626';
        $badgeBg = '#fef2f2';
    } elseif ($score < 85) {
        $predikat = 'Cukup Baik - Perlu Review (B)';
        $badgeColor = '#d97706';
        $badgeBg = '#fffbeb';
    }

    $urlPdf = url('pages/ai_asisten/export_pdf.php?tahun=' . $curYear);

    // Format Paper White Watchdog Card
    $cardHtml = <<<HTML
<div class="ai-watchdog-card ai-custom-card my-2 p-3" style="border: 1.5px solid #0d9f4f; border-radius: 6px; box-shadow: none;">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div class="font-weight-bold" style="color: #0d9f4f; font-size: 0.92rem;">
            <i class="fas fa-shield-alt mr-1"></i> AI Data Quality & Fraud Watchdog
        </div>
        <span class="badge" style="background-color: {$badgeBg}; color: {$badgeColor}; border: 1px solid #b8b8b8; font-size: 0.76rem; font-weight: 700;">
            Skor: {$score}/100 &bull; {$predikat}
        </span>
    </div>
    
    <div class="row no-gutters mb-2" style="gap: 6px;">
        <div class="col border p-2 ai-watchdog-metric-box">
            <small class="text-muted d-block" style="font-size: 0.7rem;">DUPLIKAT POTENSIAL</small>
            <strong style="color: #dc2626; font-size: 1.05rem;">{$totalDupCount}</strong> <small class="text-muted">pasang</small>
        </div>
        <div class="col border p-2 ai-watchdog-metric-box">
            <small class="text-muted d-block" style="font-size: 0.7rem;">ANOMALI TANGGAL</small>
            <strong style="color: #0d9f4f; font-size: 1.05rem;">{$totalDateAnomalies}</strong> <small class="text-muted">entri</small>
        </div>
        <div class="col border p-2 ai-watchdog-metric-box">
            <small class="text-muted d-block" style="font-size: 0.7rem;">SELISIH BANK</small>
            <strong style="color: #d97706; font-size: 1.05rem;">
HTML;
    $cardHtml .= count($rkVariances) . <<<HTML
</strong> <small class="text-muted">bulan</small>
        </div>
        <div class="col border p-2 ai-watchdog-metric-box">
            <small class="text-muted d-block" style="font-size: 0.7rem;">DATA HYGIENE</small>
            <strong style="color: #0d9f4f; font-size: 1.05rem;">{$hygieneCount}</strong> <small class="text-muted">anomali</small>
        </div>
    </div>
    
    <div class="mt-2" style="font-size: 0.82rem;">
        <div class="font-weight-bold ai-card-title mb-1"><i class="fas fa-search mr-1 text-info"></i> Temuan Audit Utama:</div>
HTML;

    if (!empty($duplicateRows)) {
        $cardHtml .= '<div class="ai-watchdog-finding-warn mb-2 p-2">';
        $cardHtml .= '<strong><i class="fas fa-copy mr-1 text-warning"></i> Ditemukan Potensi Transaksi Berulang (Sama Nominal, Tanggal & Pembayar):</strong><ul class="mb-0 pl-3 mt-1">';
        foreach ($duplicateRows as $d) {
            $cardHtml .= "<li>ID #{$d['id1']} & #{$d['id2']} &bull; {$d['nama_pembayar']} &bull; Rp " . number_format($d['jumlah_pembayaran'], 0, ',', '.') . " (" . $d['tanggal_transaksi'] . ")</li>";
        }
        $cardHtml .= '</ul></div>';
    }

    if (!empty($rkVariances)) {
        $cardHtml .= '<div class="ai-watchdog-finding-danger mb-2 p-2">';
        $cardHtml .= '<strong><i class="fas fa-university mr-1 text-danger"></i> Selisih Mutasi Rekening Koran Bank vs Transaksi:</strong><ul class="mb-0 pl-3 mt-1">';
        foreach ($rkVariances as $rv) {
            $cardHtml .= "<li>Bulan {$rv['bulan']} ({$rv['bank']}): RK Rp " . number_format($rv['kredit'], 0, ',', '.') . " vs Transaksi Rp " . number_format($rv['transaksi'], 0, ',', '.') . " &bull; Selisih Rp " . number_format($rv['selisih'], 0, ',', '.') . "</li>";
        }
        $cardHtml .= '</ul></div>';
    }

    if ($totalDateAnomalies === 0 && empty($duplicateRows) && empty($rkVariances)) {
        $cardHtml .= '<div class="alert alert-light border py-2 px-3 text-muted mb-2 ai-card-alert" style="border-color:#b8b8b8 !important;"><i class="fas fa-check-circle text-success mr-1"></i> Tidak ada anomali kritis. Integritas data pembukuan sangat baik dan sesuai tata kelola BAZNAS RI.</div>';
    }

    $cardHtml .= <<<HTML
    </div>
    
    <div class="d-flex flex-wrap mt-2 pt-2 border-top" style="gap: 6px;">
        <a href="{$urlPdf}" target="_blank" class="btn btn-xs btn-outline-success font-weight-bold px-2 py-1" style="border: 1.5px solid #0d9f4f; border-radius: 4px; font-size: 0.78rem;">
            <i class="fas fa-file-pdf mr-1 text-danger"></i> Unduh Laporan Eksekutif PDF
        </a>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold px-2 py-1" onclick="sendAiQuickPrompt('Tampilkan daftar transaksi ganda atau duplikat')" style="border: 1px solid #b8b8b8; border-radius: 4px; font-size: 0.78rem;">
            <i class="fas fa-search mr-1"></i> Telusuri Duplikat
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold px-2 py-1" onclick="sendAiQuickPrompt('Tampilkan grafik tren penerimaan ZIS tahun ini')" style="border: 1px solid #b8b8b8; border-radius: 4px; font-size: 0.78rem;">
            <i class="fas fa-chart-line mr-1 text-info"></i> Tampilkan Grafik Tren
        </button>
    </div>
</div>
HTML;

    return [
        'status' => 'success',
        'reply' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ“„ Unduh PDF Eksekutif', 'url' => $urlPdf],
            ['label' => 'ðŸ” Telusuri Duplikat', 'prompt' => 'Tampilkan daftar transaksi ganda atau duplikat'],
            ['label' => 'ðŸ“Š Grafik Tren ZIS', 'prompt' => 'Tampilkan grafik tren penerimaan ZIS tahun ini']
        ]
    ];
}

/**
 * Visualisasi Grafik Interaktif di Dalam Chat (Chart.js Integration)
 */
function ai_generate_interactive_chart(mysqli $conn, string $prompt): array
{
    $lower = strtolower($prompt);
    $chartId = 'ai_chart_' . uniqid();
    $curYear = (int)date('Y');

    // 1. Grafik Jenis Dana (Doughnut)
    if (preg_match('/(jenis|kategori|zakat vs infak|komposisi|alokasi)/i', $lower)) {
        $labels = [];
        $data = [];
        $colors = ['#0d9f4f', '#2b6cb0', '#d97706', '#8b5cf6', '#dc2626', '#14b8a6'];

        $q = "SELECT jp.nama_jenis, COALESCE(SUM(t.jumlah_pembayaran), 0) as total 
              FROM jenis_pembayaran jp
              LEFT JOIN transaksi t ON jp.id = t.id_jenis_pembayaran AND YEAR(t.tanggal_transaksi) = {$curYear}
              GROUP BY jp.id, jp.nama_jenis
              HAVING total > 0
              ORDER BY total DESC";
        $res = $conn->query($q);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $labels[] = $r['nama_jenis'];
                $data[] = (float)$r['total'];
            }
        }

        $chartConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => array_slice($colors, 0, count($labels)),
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'legend' => [
                    'position' => 'bottom',
                    'labels' => ['boxWidth' => 12, 'fontSize' => 11]
                ]
            ]
        ];

        $title = "Komposisi Penerimaan ZIS Berdasarkan Jenis Dana ({$curYear})";
    }
    // 2. Grafik Rekening Bank (Bar)
    elseif (preg_match('/(bank|rekening|sumber)/i', $lower)) {
        $labels = [];
        $data = [];

        $q = "SELECT sr.nama_sumber, COALESCE(SUM(t.jumlah_pembayaran), 0) as total 
              FROM sumber_rekening sr
              LEFT JOIN transaksi t ON sr.id = t.id_sumber_rekening AND YEAR(t.tanggal_transaksi) = {$curYear}
              GROUP BY sr.id, sr.nama_sumber
              HAVING total > 0
              ORDER BY total DESC";
        $res = $conn->query($q);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $labels[] = $r['nama_sumber'];
                $data[] = (float)$r['total'];
            }
        }

        $chartConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Total Setoran (Rp)',
                        'data' => $data,
                        'backgroundColor' => '#2b6cb0',
                        'borderColor' => '#1a4971',
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true]
                    ]]
                ]
            ]
        ];

        $title = "Distribusi Pengumpulan per Sumber Rekening Bank ({$curYear})";
    }
    // 3. Default: Tren Bulanan (Line Chart)
    else {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $data = array_fill(0, 12, 0);

        $q = "SELECT MONTH(tanggal_transaksi) as bln, SUM(jumlah_pembayaran) as total 
              FROM transaksi 
              WHERE YEAR(tanggal_transaksi) = {$curYear} 
              GROUP BY MONTH(tanggal_transaksi) 
              ORDER BY bln ASC";
        $res = $conn->query($q);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $idx = (int)$r['bln'] - 1;
                if ($idx >= 0 && $idx < 12) {
                    $data[$idx] = (float)$r['total'];
                }
            }
        }

        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Penerimaan ZIS Bulanan (Rp)',
                        'data' => $data,
                        'borderColor' => '#0d9f4f',
                        'backgroundColor' => 'rgba(13, 159, 79, 0.1)',
                        'borderWidth' => 2,
                        'pointBackgroundColor' => '#0d9f4f',
                        'pointRadius' => 4,
                        'fill' => true
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'scales' => [
                    'yAxes' => [[
                        'ticks' => ['beginAtZero' => true]
                    ]]
                ]
            ]
        ];

        $title = "Grafik Tren Pengumpulan ZIS Bulanan Tahun {$curYear}";
    }

    $jsonConfig = htmlspecialchars(json_encode($chartConfig), ENT_QUOTES, 'UTF-8');

    $cardHtml = <<<HTML
<div class="ai-chart-card ai-custom-card my-2 p-3" style="border: 1.5px solid #0d9f4f; border-radius: 6px; box-shadow: none;">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
        <div class="font-weight-bold ai-card-title" style="color: #0d9f4f; font-size: 0.92rem;">
            <i class="fas fa-chart-area mr-1"></i> {$title}
        </div>
        <span class="badge" style="background-color: #f0f7f3; color: #0d9f4f; border: 1px solid #b8b8b8; font-size: 0.72rem;">
            Interaktif (Chart.js)
        </span>
    </div>
    <div style="position: relative; height: 260px; width: 100%;">
        <canvas class="ai-chat-chart" id="{$chartId}" data-chart="{$jsonConfig}"></canvas>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
        <small class="text-muted" style="font-size: 0.76rem;">
            <i class="fas fa-info-circle mr-1 text-info"></i> Arahkan kursor ke grafik untuk melihat nominal detail per titik data.
        </small>
        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="sendAiQuickPrompt('Jalankan deteksi anomali dan audit kualitas data')" style="font-size: 0.74rem; border-radius: 4px;">
            <i class="fas fa-shield-alt mr-1"></i> Cek Anomali
        </button>
    </div>
</div>
HTML;

    return [
        'status' => 'success',
        'reply' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ“ˆ Grafik Bulanan', 'prompt' => 'Tampilkan grafik tren penerimaan ZIS tahun ini'],
            ['label' => 'ðŸ© Komposisi Jenis Dana', 'prompt' => 'Tampilkan grafik komposisi jenis dana ZIS'],
            ['label' => 'ðŸ¦ Distribusi Bank', 'prompt' => 'Tampilkan grafik distribusi rekening bank']
        ]
    ];
}

/**
 * Konversi Format Markdown (Bold, Italic, Code, List, Newlines) ke HTML Bersih & Rapi
 */
function ai_format_markdown_to_html(string $text): string
{
    if ($text === '') return '';

    // 0. Amankan tanda bullet point di awal baris (* atau -) menjadi simbol bullet 'â€¢ '
    // Ini mencegah tanda asterisk bullet di awal baris bertabrakan dengan regex asterisk
    $text = preg_replace('/^[\t ]*[\*\-]\s+/m', 'â€¢ ', $text);

    // 1. Bold + Italic gabungan: ***teks*** atau ___teks___
    $text = preg_replace('/\*\*\*([\s\S]+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
    $text = preg_replace('/___([\s\S]+?)___/', '<strong><em>$1</em></strong>', $text);

    // 2. Bold asteris ganda: **teks** atau __teks__
    $text = preg_replace('/\*\*([\s\S]+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__([\s\S]+?)__/', '<strong>$1</strong>', $text);

    // 3. Italic asteris tunggal: *teks* atau _teks_
    $text = preg_replace('/(?<!\*|\w)\*([^\*\n]+?)\*(?!\*|\w)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_|\w)_([^_\n]+?)_(?!_|\w)/', '<em>$1</em>', $text);

    // 4. Inline Code: `kode`
    $text = preg_replace('/`([^`]+)`/', '<code style="background:rgba(0,0,0,0.06); padding:1px 4px; border-radius:3px; font-size:0.88em;">$1</code>', $text);

    // 5. Newline normalizer jika belum ada tag block HTML
    if (!preg_match('/<(div|table|ul|ol|li|p|blockquote)[^>]*>/i', $text)) {
        $text = nl2br($text);
    }

    return $text;
}

/**
 * Mengubah nama kolom basis data menjadi judul header bahasa Indonesia yang rapi
 */
function ai_humanize_column_name(string $col): string
{
    $colMap = [
        'bulan' => 'Bulan',
        'tahun' => 'Tahun',
        'nama_upz' => 'Nama UPZ',
        'nama_muzaki' => 'Nama Muzaki',
        'pembayar' => 'Pembayar (UPZ / Muzaki)',
        'tipe_pembayar' => 'Tipe',
        'nama_sumber' => 'Sumber Rekening / Bank',
        'nama_jenis' => 'Jenis ZIS',
        'nama_sub_jenis' => 'Sub-Jenis ZIS',
        'nama_kategori' => 'Kategori',
        'nama_kategori_upz' => 'Kategori UPZ',
        'nama_metode' => 'Metode Bayar',
        'frekuensi' => 'Frekuensi',
        'cnt' => 'Jml Transaksi',
        'count' => 'Jumlah Data',
        'jumlah_transaksi' => 'Jml Transaksi',
        'total_transaksi' => 'Total Transaksi',
        'total' => 'Total Nominal (Rp)',
        'total_nominal' => 'Total Pengumpulan (Rp)',
        'total_pengumpulan' => 'Total Pengumpulan (Rp)',
        'jumlah_pembayaran' => 'Nominal (Rp)',
        'nominal_target' => 'Target RKAT (Rp)',
        'target' => 'Target RKAT (Rp)',
        'realisasi' => 'Realisasi (Rp)',
        'persentase' => 'Capaian (%)',
        'persen' => 'Capaian (%)',
        'capaian' => 'Capaian (%)',
        'keterangan' => 'Keterangan / Memo',
        'catatan' => 'Catatan',
        'catatan_khusus' => 'Catatan Khusus',
        'alamat' => 'Alamat',
        'kontak' => 'Kontak / No HP',
        'no_hp' => 'No HP',
        'no_ktp' => 'No KTP',
        'jumlah_kredit' => 'Mutasi Kredit (Rp)',
        'nama_file_original' => 'Berkas Rekening Koran',
        'total_penerima' => 'Jml Mustahik',
        'nominal_total' => 'Total Bantuan (Rp)'
    ];

    $clean = strtolower(trim($col));
    if (isset($colMap[$clean])) {
        return $colMap[$clean];
    }

    return ucwords(str_replace('_', ' ', $clean));
}

/**
 * Format Tabel Transaksi Individual Detail
 */
function ai_format_detailed_transaction_table(array $rows): string
{
    $html = '<div class="table-responsive my-2"><table class="table table-sm table-bordered mb-0" style="font-size: 0.78rem; background: #fff; border: 1px solid #b8b8b8;">';
    $html .= '<thead class="bg-light font-weight-bold text-dark"><tr>';
    $html .= '<th class="text-center" width="4%">No</th>';
    $html .= '<th class="text-center" width="11%">Tanggal</th>';
    $html .= '<th width="24%">Pembayar (UPZ / Muzaki)</th>';
    $html .= '<th width="18%">Jenis ZIS & Bank</th>';
    $html .= '<th width="22%">Uraian / Keterangan</th>';
    $html .= '<th class="text-right" width="14%">Nominal</th>';
    $html .= '<th class="text-center" width="7%">Aksi</th>';
    $html .= '</tr></thead><tbody>';

    $no = 1;
    $totalNominal = 0;

    foreach ($rows as $r) {
        $id = (int)($r['id'] ?? 0);
        $tgl = !empty($r['tanggal_transaksi']) ? date('d/m/Y', strtotime($r['tanggal_transaksi'])) : '-';

        $pembayar = $r['pembayar'] ?? ($r['nama_upz'] ?? ($r['nama_muzaki'] ?? 'Muzaki Umum'));
        $tipePembayar = $r['tipe_pembayar'] ?? (!empty($r['nama_upz']) ? 'UPZ' : 'Muzaki');
        $badgeClass = $tipePembayar === 'UPZ' ? 'badge-info' : 'badge-secondary';

        $pembayarHtml = "<strong>" . htmlspecialchars($pembayar) . "</strong>";
        $pembayarHtml .= " <span class='badge {$badgeClass}' style='font-size: 0.65rem;'>{$tipePembayar}</span>";

        $jenis = htmlspecialchars($r['nama_jenis'] ?? ($r['jenis'] ?? '-'));
        $bankRaw = $r['nama_sumber'] ?? ($r['sumber_rekening'] ?? ($r['bank'] ?? ''));
        $bank = htmlspecialchars(explode('|', $bankRaw)[0]);
        $metode = htmlspecialchars($r['nama_metode'] ?? ($r['metode'] ?? ''));

        $jenisBankHtml = "<div>{$jenis}</div>";
        if ($bank) {
            $jenisBankHtml .= "<small class='text-muted d-block'><i class='fas fa-university mr-1'></i>{$bank}</small>";
        }
        if ($metode) {
            $jenisBankHtml .= "<span class='badge' style='background:#f0f7f3; color:#0d9f4f; border:1px solid #b8b8b8; font-size:0.65rem;'>{$metode}</span>";
        }

        $ket = !empty($r['keterangan']) ? htmlspecialchars($r['keterangan']) : '<span class="text-muted">-</span>';
        $nom = (float)($r['jumlah_pembayaran'] ?? ($r['nominal'] ?? ($r['total'] ?? 0)));
        $totalNominal += $nom;

        $linkDetail = url("pages/transaksi/index.php?search=" . ($id ?: urlencode($pembayar)));

        $html .= '<tr>';
        $html .= "<td class='text-center align-middle'>{$no}</td>";
        $html .= "<td class='text-center align-middle text-nowrap font-weight-bold'>{$tgl}</td>";
        $html .= "<td class='align-middle'>{$pembayarHtml}</td>";
        $html .= "<td class='align-middle'>{$jenisBankHtml}</td>";
        $html .= "<td class='align-middle' style='max-width: 170px; word-break: break-word;'>{$ket}</td>";
        $html .= "<td class='text-right align-middle text-nowrap font-weight-bold text-success'>Rp " . number_format($nom, 0, ',', '.') . "</td>";
        $html .= "<td class='text-center align-middle'><a href='{$linkDetail}' target='_blank' class='btn btn-xs btn-outline-primary py-1 px-2' title='Buka Transaksi' style='border-radius:4px;'><i class='fas fa-external-link-alt'></i></a></td>";
        $html .= '</tr>';
        $no++;
    }

    $html .= '</tbody><tfoot class="bg-light font-weight-bold"><tr>';
    $html .= '<td colspan="5" class="text-center font-weight-bold">TOTAL (' . count($rows) . ' TRANSAKSI DITAMPILKAN)</td>';
    $html .= '<td class="text-right text-success font-weight-bold" style="font-size: 0.85rem;">Rp ' . number_format($totalNominal, 0, ',', '.') . '</td>';
    $html .= '<td></td>';
    $html .= '</tr></tfoot></table></div>';

    return $html;
}

/**
 * Format Kotak Metrik Ringkasan Eksekutif (Untuk hasil agregat tunggal)
 */
function ai_format_metric_summary_card(array $row): string
{
    $html = '<div class="p-3 my-2 rounded border" style="background: #ffffff; border: 1px solid #b8b8b8 !important;">';
    $html .= '<div class="d-flex flex-wrap align-items-center justify-content-around" style="gap: 12px;">';

    foreach ($row as $k => $v) {
        $label = ai_humanize_column_name($k);
        $isCurrency = preg_match('/(total|nominal|pembayaran|target|realisasi|sum|dana|uang|kredit)/i', $k);
        $isCount = preg_match('/(cnt|count|jumlah_transaksi|frekuensi|total_transaksi)/i', $k);
        $isPercent = preg_match('/(persen|persentase|capaian)/i', $k);

        $html .= '<div class="text-center px-3 py-1">';
        $html .= "<small class='text-muted d-block font-weight-bold text-uppercase' style='font-size: 0.72rem;'>{$label}</small>";

        if ($isCurrency) {
            $html .= "<span class='font-weight-bold text-success' style='font-size: 1.15rem;'>Rp " . number_format((float)$v, 0, ',', '.') . "</span>";
        } elseif ($isPercent) {
            $html .= "<span class='badge badge-success' style='font-size: 0.9rem;'>" . round((float)$v, 2) . "%</span>";
        } elseif ($isCount) {
            $html .= "<span class='font-weight-bold text-dark' style='font-size: 1.1rem;'>" . number_format((int)$v, 0, ',', '.') . " <small class='font-weight-normal text-muted'>transaksi</small></span>";
        } else {
            $html .= "<span class='font-weight-bold text-dark' style='font-size: 1.05rem;'>" . htmlspecialchars((string)$v) . "</span>";
        }

        $html .= '</div>';
    }

    $html .= '</div></div>';
    return $html;
}

/**
 * Format Tabel Rekapitulasi Adaptif Dinamis (Universal untuk segala kueri rekap, bank, UPZ, dll)
 */
function ai_format_adaptive_recap_table(array $rows): string
{
    if (empty($rows)) return '';

    $firstRow = $rows[0];
    $keys = array_keys($firstRow);

    $html = '<div class="table-responsive my-2"><table class="table table-sm table-bordered mb-0" style="font-size: 0.78rem; background: #fff; border: 1px solid #b8b8b8;">';
    $html .= '<thead class="bg-light font-weight-bold text-dark"><tr>';
    $html .= '<th class="text-center" width="5%">No</th>';

    $totals = [];
    $isCurrencyCol = [];
    $isNumberCol = [];

    foreach ($keys as $k) {
        $label = ai_humanize_column_name($k);
        $isCurr = preg_match('/(total|nominal|pembayaran|target|realisasi|kredit|debet|dana|bantuan|biaya)/i', $k);
        $isCnt = preg_match('/(frekuensi|cnt|count|jumlah_transaksi|total_transaksi|penerima|jumlah)/i', $k);

        $isCurrencyCol[$k] = $isCurr;
        $isNumberCol[$k] = $isCnt;
        $totals[$k] = 0;

        $align = $isCurr ? 'text-right' : ($isCnt ? 'text-center' : 'text-left');
        $html .= "<th class='{$align}'>{$label}</th>";
    }
    $html .= '</tr></thead><tbody>';

    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    $no = 1;
    foreach ($rows as $r) {
        $html .= '<tr>';
        $html .= "<td class='text-center align-middle font-weight-bold'>{$no}</td>";

        foreach ($keys as $k) {
            $val = $r[$k] ?? '-';
            $isCurr = $isCurrencyCol[$k];
            $isCnt = $isNumberCol[$k];

            if ($isCurr) {
                $numVal = (float)$val;
                $totals[$k] += $numVal;
                $html .= "<td class='text-right align-middle text-nowrap font-weight-bold text-success'>Rp " . number_format($numVal, 0, ',', '.') . "</td>";
            } elseif ($isCnt) {
                $intVal = (int)$val;
                $totals[$k] += $intVal;
                $html .= "<td class='text-center align-middle font-weight-bold'>{$intVal} kali</td>";
            } elseif ($k === 'bulan' && is_numeric($val) && isset($namaBulan[(int)$val])) {
                $html .= "<td class='align-middle font-weight-bold text-dark'><i class='far fa-calendar-alt text-muted mr-1'></i>" . $namaBulan[(int)$val] . "</td>";
            } elseif (preg_match('/(persen|persentase|capaian)/i', $k)) {
                $pVal = round((float)$val, 2);
                $pBadge = $pVal >= 100 ? 'badge-success' : ($pVal >= 50 ? 'badge-info' : 'badge-warning');
                $html .= "<td class='text-center align-middle'><span class='badge {$pBadge}' style='font-size:0.75rem;'>{$pVal}%</span></td>";
            } else {
                $html .= "<td class='align-middle'>" . htmlspecialchars((string)$val) . "</td>";
            }
        }

        $html .= '</tr>';
        $no++;
    }

    // Hitung apakah ada kolom numerik untuk footer
    $hasTotals = false;
    foreach ($totals as $k => $sum) {
        if ($sum > 0) {
            $hasTotals = true;
            break;
        }
    }

    if ($hasTotals) {
        $html .= '</tbody><tfoot class="bg-light font-weight-bold"><tr>';
        $html .= '<td colspan="2" class="text-center font-weight-bold">TOTAL REKAPITULASI</td>';
        $skipCount = 0;
        foreach ($keys as $idx => $k) {
            if ($idx === 0) continue; // Tercover oleh colspan 2
            if ($isCurrencyCol[$k]) {
                $html .= "<td class='text-right text-success font-weight-bold'>Rp " . number_format($totals[$k], 0, ',', '.') . "</td>";
            } elseif ($isNumberCol[$k]) {
                $html .= "<td class='text-center font-weight-bold'>{$totals[$k]} kali</td>";
            } else {
                $html .= "<td></td>";
            }
        }
        $html .= '</tr></tfoot>';
    } else {
        $html .= '</tbody>';
    }

    $html .= '</table></div>';
    return $html;
}

/**
 * Universal Smart Formatter: Memilih format tabel terbaik secara otomatis
 */
function ai_format_smart_table(array $rows, string $tipe = 'auto'): string
{
    if (empty($rows)) {
        return '<div class="alert alert-light border my-2 py-2 px-3 text-muted" style="border-color: #b8b8b8 !important; font-size: 0.82rem;"><i class="fas fa-info-circle mr-1 text-info"></i> Tidak ada baris data yang cocok dengan kriteria pencarian ini.</div>';
    }

    $firstRow = $rows[0];
    $keys = array_keys($firstRow);

    // 1. Cek apakah ini transaksi individual detail
    $isTransaction = isset($firstRow['tanggal_transaksi']) || 
                     (isset($firstRow['jumlah_pembayaran']) && (isset($firstRow['pembayar']) || isset($firstRow['nama_upz']) || isset($firstRow['nama_muzaki'])));

    if ($tipe === 'transaksi_table' || ($tipe === 'auto' && $isTransaction)) {
        return ai_format_detailed_transaction_table($rows);
    }

    // 2. Cek apakah 1 baris ringkasan/agregat murni (misal: "SELECT SUM(...) as total")
    if (count($rows) === 1 && !isset($firstRow['bulan']) && !isset($firstRow['nama_upz']) && !isset($firstRow['nama_sumber']) && !isset($firstRow['nama_jenis'])) {
        $hasAggregateKey = false;
        foreach ($keys as $k) {
            if (preg_match('/(total|nominal|sum|cnt|count|rata|avg|target|realisasi|persen)/i', $k)) {
                $hasAggregateKey = true;
                break;
            }
        }
        if ($hasAggregateKey) {
            return ai_format_metric_summary_card($firstRow);
        }
    }

    // 3. Tabel Rekapitulasi Adaptif Universal
    return ai_format_adaptive_recap_table($rows);
}

// Wrapper fungsi lama untuk kompatibilitas ke belakang
function ai_format_transaction_table(array $rows): string
{
    return ai_format_smart_table($rows, 'transaksi_table');
}

function ai_format_rank_table(array $rows, string $labelEntitas = 'UPZ'): string
{
    return ai_format_smart_table($rows, 'rekap_table');
}

/**
 * Parsing teks bahasa alami menjadi data transaksi terstruktur (Fallback & Normalizer)
 */
function ai_parse_transaction_from_text(string $text): array
{
    $res = [
        'tanggal_transaksi' => date('Y-m-d'),
        'tipe_pembayar' => 'muzaki',
        'nama_pembayar' => 'Muzaki Umum',
        'nominal' => 0,
        'id_jenis_pembayaran' => 6,
        'id_sub_jenis_pembayaran' => null,
        'id_metode_pembayaran' => 4,
        'id_sumber_rekening' => 1,
        'keterangan' => ''
    ];

    $lower = strtolower($text);

    // 1. Ekstrak Nominal
    if (preg_match('/(?:rp\.?|sebesar|jumlah|senilai)?\s*([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta|m)\b/i', $text, $m)) {
        $val = str_replace(',', '.', $m[1]);
        $res['nominal'] = ((float)$val) * 1000000;
    } elseif (preg_match('/(?:rp\.?|sebesar|jumlah|senilai)?\s*([0-9]+(?:[\.,][0-9]+)?)\s*(?:rb|ribu|k)\b/i', $text, $m)) {
        $val = str_replace(',', '.', $m[1]);
        $res['nominal'] = ((float)$val) * 1000;
    } elseif (preg_match('/(?:rp\.?|sebesar|jumlah|senilai)?\s*([0-9]{1,3}(?:\.[0-9]{3})+(?:,[0-9]+)?)/i', $text, $m)) {
        $res['nominal'] = (float)str_replace(['.', ','], ['', '.'], $m[1]);
    } elseif (preg_match('/(?:rp\.?|sebesar|jumlah|senilai)?\s*([0-9]{4,12})/i', $text, $m)) {
        $res['nominal'] = (float)$m[1];
    }

    // 2. Ekstrak Pembayar (Muzaki vs UPZ)
    if (preg_match('/(?:dari|atas\s+nama|oleh)\s+([a-zA-Z0-9\.\s\-\'\&]+?)(?:\s+(?:[0-9]|rp\.?|via|melalui|sebesar|sejumlah|dengan|tanggal|tgl|keterangan|ket|untuk|tunai|bank)|$)/i', $text, $m)) {
        $namaRaw = trim($m[1]);
        $res['nama_pembayar'] = ucwords($namaRaw);
    } elseif (preg_match('/(?:upz|muzaki)\s+([a-zA-Z0-9\.\s\-\'\&]+?)(?:\s+(?:[0-9]|rp\.?|sebesar|sejumlah|via|melalui|tunai)|$)/i', $text, $m)) {
        $res['nama_pembayar'] = ucwords(trim($m[1]));
    }

    if (preg_match('/\b(upz|dinas|badan|instansi|kantor|masjid|musholla|sekolah|sman|smkn|smpn|sdn|universitas|pt|cv|koperasi|kecamatan|kelurahan|desa)\b/i', $res['nama_pembayar']) || preg_match('/\bupz\b/i', $lower)) {
        $res['tipe_pembayar'] = 'upz';
    } else {
        $res['tipe_pembayar'] = 'muzaki';
    }

    // 3. Ekstrak Jenis ZIS
    if (preg_match('/\b(infak|infaq|sedekah)\b/i', $lower)) {
        $res['id_jenis_pembayaran'] = 7;
        $res['id_sub_jenis_pembayaran'] = ($res['tipe_pembayar'] === 'upz') ? 20 : 19;
    } elseif (preg_match('/\b(dskl|fidyah)\b/i', $lower)) {
        $res['id_jenis_pembayaran'] = 8;
        $res['id_sub_jenis_pembayaran'] = 26;
    } elseif (preg_match('/\b(csr)\b/i', $lower)) {
        $res['id_jenis_pembayaran'] = 11;
        $res['id_sub_jenis_pembayaran'] = null;
    } else {
        $res['id_jenis_pembayaran'] = 6;
        $res['id_sub_jenis_pembayaran'] = ($res['tipe_pembayar'] === 'upz') ? 11 : 10;
    }

    // 4. Ekstrak Metode & Rekening
    if (preg_match('/\b(tunai|cash)\b/i', $lower)) {
        $res['id_metode_pembayaran'] = 5;
        $res['id_sumber_rekening'] = 11; // BUKU PENGUMPULAN
    } else {
        $res['id_metode_pembayaran'] = 4; // Transfer
        if (preg_match('/\b(bri)\b/i', $lower)) {
            $res['id_sumber_rekening'] = 10;
        } elseif (preg_match('/\b(mandiri)\b/i', $lower)) {
            $res['id_sumber_rekening'] = ($res['id_jenis_pembayaran'] === 7) ? 8 : 9;
        } else {
            // Default NTB Syariah
            $res['id_sumber_rekening'] = ($res['id_jenis_pembayaran'] === 7) ? 5 : 1;
        }
    }

    // 5. Ekstrak Keterangan
    if (preg_match('/(?:keterangan|ket|untuk|uraian|memo)\s+[:\-]?\s*([^$\n]+)/i', $text, $m)) {
        $res['keterangan'] = trim($m[1]);
    } else {
        $res['keterangan'] = "Setoran via Amil Virtual AI";
    }

    return $res;
}

/**
 * Parser Tanggal Cerdas Bahasa Indonesia (Smart Date Parser)
 * Memahami: "hari ini", "kemarin", "minggu lalu", "3 hari lalu",
 *           "3 September 2026", "15/09/2026", "2026-09-15", "awal bulan", "akhir bulan lalu"
 */
function ai_parse_smart_date(string $input): string
{
    $s = trim(strtolower($input));
    if ($s === '' || $s === 'hari ini' || $s === 'today' || $s === 'sekarang') {
        return date('Y-m-d');
    }

    // Format standar YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;

    // Format DD/MM/YYYY atau DD-MM-YYYY
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $m)) {
        return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    }

    // "kemarin" / "yesterday"
    if (preg_match('/^(kemarin|kemaren|yesterday)$/', $s)) {
        return date('Y-m-d', strtotime('-1 day'));
    }

    // "lusa" (2 hari lalu)
    if ($s === 'lusa' || $s === 'kemarin lusa' || $s === 'kemaren lusa') {
        return date('Y-m-d', strtotime('-2 days'));
    }

    // "N hari (yang) lalu"
    if (preg_match('/(\d+)\s*hari\s*(?:yang\s*)?lalu/', $s, $m)) {
        return date('Y-m-d', strtotime("-{$m[1]} days"));
    }

    // "minggu lalu" / "pekan lalu"
    if (preg_match('/^(?:minggu|pekan)\s+lalu$/', $s)) {
        return date('Y-m-d', strtotime('-7 days'));
    }

    // "N minggu (yang) lalu"
    if (preg_match('/(\d+)\s*(?:minggu|pekan)\s*(?:yang\s*)?lalu/', $s, $m)) {
        $days = (int)$m[1] * 7;
        return date('Y-m-d', strtotime("-{$days} days"));
    }

    // "bulan lalu"
    if (preg_match('/^bulan\s+lalu$/', $s)) {
        return date('Y-m-d', strtotime('-1 month'));
    }

    // "awal bulan" / "awal bulan ini"
    if (preg_match('/^awal\s+bulan/', $s)) {
        return date('Y-m-01');
    }

    // "akhir bulan lalu"
    if (preg_match('/^akhir\s+bulan\s+lalu/', $s)) {
        return date('Y-m-t', strtotime('first day of last month'));
    }

    // Nama bulan Indonesia: "3 September 2026", "15 Agustus", "25 Sept 2026"
    $bulanMap = [
        'jan' => 1, 'januari' => 1, 'january' => 1,
        'feb' => 2, 'februari' => 2, 'february' => 2,
        'mar' => 3, 'maret' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4,
        'mei' => 5, 'may' => 5,
        'jun' => 6, 'juni' => 6, 'june' => 6,
        'jul' => 7, 'juli' => 7, 'july' => 7,
        'agu' => 8, 'agustus' => 8, 'august' => 8, 'ags' => 8, 'aug' => 8,
        'sep' => 9, 'september' => 9, 'sept' => 9,
        'okt' => 10, 'oktober' => 10, 'october' => 10, 'oct' => 10,
        'nov' => 11, 'november' => 11, 'nop' => 11,
        'des' => 12, 'desember' => 12, 'december' => 12, 'dec' => 12,
    ];

    $bulanPattern = implode('|', array_keys($bulanMap));
    if (preg_match('/(\d{1,2})\s+(' . $bulanPattern . ')(?:\s+(\d{4}))?/i', $s, $m)) {
        $day = (int)$m[1];
        $month = $bulanMap[strtolower($m[2])] ?? 0;
        $year = isset($m[3]) ? (int)$m[3] : (int)date('Y');
        if ($month > 0 && $day >= 1 && $day <= 31) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    // "September 3, 2026" (English-style)
    if (preg_match('/(' . $bulanPattern . ')\s+(\d{1,2})(?:\s*,?\s*(\d{4}))?/i', $s, $m)) {
        $month = $bulanMap[strtolower($m[1])] ?? 0;
        $day = (int)$m[2];
        $year = isset($m[3]) ? (int)$m[3] : (int)date('Y');
        if ($month > 0 && $day >= 1 && $day <= 31) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    // Fallback: kembalikan hari ini
    return date('Y-m-d');
}

/**
 * Parser Nominal Cerdas Bahasa Indonesia (Smart Nominal Parser)
 * Memahami: "1,5 juta", "1.5jt", "750rb", "setengah juta", "2M", "Rp 1.500.000,-",
 *           "seperempat juta", "500 ribu", "1500000", "2,5 jt", "3.000.000"
 *
 * @return float Nominal dalam satuan Rupiah penuh (contoh: 1500000.0)
 */
function ai_parse_smart_nominal($input): float
{
    if (is_numeric($input)) return (float)$input;
    if (!is_string($input)) return 0.0;

    $s = trim(strtolower($input));
    $s = preg_replace('/^rp\.?\s*/', '', $s);      // Hapus prefix Rp / Rp.
    $s = preg_replace('/[,\-]+$/', '', $s);         // Hapus trailing comma/dash (Rp 1.500.000,-)
    $s = trim($s);

    if ($s === '') return 0.0;

    // Kata kunci fraksi khusus
    if (preg_match('/^setengah\s+(juta|miliar|milyar|m)$/i', $s, $m)) {
        $unit = strtolower($m[1]);
        return ($unit === 'juta' || $unit === 'm') ? 500000.0 : 500000000.0;
    }
    if (preg_match('/^seperempat\s+(juta|miliar|milyar|m)$/i', $s, $m)) {
        $unit = strtolower($m[1]);
        return ($unit === 'juta' || $unit === 'm') ? 250000.0 : 250000000.0;
    }
    if (preg_match('/^sepertiga\s+(juta|miliar|milyar|m)$/i', $s, $m)) {
        $unit = strtolower($m[1]);
        return ($unit === 'juta' || $unit === 'm') ? round(1000000 / 3, 0) : round(1000000000 / 3, 0);
    }

    // Pattern: angka (dengan titik/koma desimal) + satuan
    // "1,5 juta", "1.5jt", "2,5 jt", "750rb", "500 ribu", "10k", "2M", "1 miliar"
    if (preg_match('/^([0-9]+(?:[,\.][0-9]+)?)\s*(jt|juta|m(?:iliar|ilyar)?|rb|ribu|k|ratus\s*(?:jt|juta|rb|ribu))?$/i', $s, $m)) {
        $num = str_replace(',', '.', $m[1]);
        $val = (float)$num;
        $unit = strtolower(trim($m[2] ?? ''));

        if ($unit === 'jt' || $unit === 'juta') {
            return $val * 1000000;
        } elseif ($unit === 'miliar' || $unit === 'milyar') {
            return $val * 1000000000;
        } elseif ($unit === 'm') {
            // "m" bisa juta (umumnya di Indonesia)
            return $val * 1000000;
        } elseif ($unit === 'rb' || $unit === 'ribu' || $unit === 'k') {
            return $val * 1000;
        } elseif ($unit === '') {
            // Angka murni tanpa satuan
            return $val;
        }
    }

    // Pattern: "N ratus ribu" / "N ratus juta"
    if (preg_match('/^([0-9]+)\s*ratus\s*(ribu|rb|juta|jt)?$/i', $s, $m)) {
        $val = (float)$m[1] * 100;
        $unit = strtolower(trim($m[2] ?? 'ribu'));
        if ($unit === 'juta' || $unit === 'jt') return $val * 1000000;
        return $val * 1000; // ratus ribu
    }

    // Format Indonesia standard: 1.500.000 atau 1.500.000,00
    if (preg_match('/^([0-9]{1,3}(?:\.[0-9]{3})+(?:,[0-9]+)?)$/', $s, $m)) {
        return (float)str_replace(['.', ','], ['', '.'], $m[1]);
    }

    // Angka murni â‰¥ 4 digit (misal: 1500000, 500000)
    if (preg_match('/^([0-9]{4,15})$/', $s, $m)) {
        return (float)$m[1];
    }

    // Angka dengan desimal (misal: 1500000.00)
    if (preg_match('/^([0-9]+\.[0-9]+)$/', $s, $m)) {
        return (float)$m[1];
    }

    // Bersihkan semua non-digit dan coba parse
    $cleaned = preg_replace('/[^0-9]/', '', $s);
    if ($cleaned !== '' && strlen($cleaned) >= 4) {
        return (float)$cleaned;
    }

    return 0.0;
}

/**
 * Resolusi Pintar UPZ (Cerdas Kode Rekening & Alias OPD Sumbawa)
 * Memetakan input pengguna (misal "Dinas Kesehatan", "dinkes", "dikbud", "Kecamatan Alas", "1.02...")
 * ke rekaman master UPZ resmi beserta kode rekeningnya.
 */
function ai_resolve_upz(mysqli $conn, string $query): ?array
{
    $q = trim(strtolower($query));
    if ($q === '') return null;

    // Bersihkan kata depan umum
    $qClean = preg_replace('/^(upz\s+|kantor\s+|dinas\s+)/i', '', $q);
    $qClean = trim($qClean);

    // Kamus Singkatan & Alias OPD Sumbawa
    $aliases = [
        'dinkes' => 'dinas kesehatan',
        'kesehatan' => 'dinas kesehatan',
        'dikbud' => 'dinas dikbud',
        'disdik' => 'dinas dikbud',
        'p&k' => 'dinas dikbud',
        'pendidikan' => 'dinas dikbud',
        'pupr' => 'dinas pupr',
        'pu' => 'dinas pupr',
        'prkp' => 'dinas prkp',
        'perkim' => 'dinas prkp',
        'satpol' => 'satpol pp',
        'pol pp' => 'satpol pp',
        'damkar' => 'dinas damkar',
        'bpbd' => 'bpbd',
        'dinsos' => 'dinas sosial',
        'sosial' => 'dinas sosial',
        'disnakertrans' => 'disnakertrans',
        'tenaga kerja' => 'disnakertrans',
        'p2kbp3a' => 'dinas p2kbp3a',
        'kb' => 'dinas p2kbp3a',
        'ketahanan pangan' => 'dinas ketahanan pangan',
        'dlh' => 'dinas lh',
        'lh' => 'dinas lh',
        'lingkungan hidup' => 'dinas lh',
        'dukcapil' => 'dinas dukcapil',
        'disdukcapil' => 'dinas dukcapil',
        'pmd' => 'dinas pmd',
        'perhubungan' => 'dinas perhubungan',
        'dishub' => 'dinas perhubungan',
        'kominfotik' => 'dinas kominfotiksandi',
        'kominfo' => 'dinas kominfotiksandi',
        'diskominfo' => 'dinas kominfotiksandi',
        'diskoperindag' => 'diskoperindag',
        'koperindag' => 'diskoperindag',
        'koperasi' => 'diskoperindag',
        'pmptsp' => 'dinas pmptsp',
        'dpmptsp' => 'dinas pmptsp',
        'perizinan' => 'dinas pmptsp',
        'dispopar' => 'dispopar',
        'pemuda olahraga' => 'dispopar',
        'dispersip' => 'dispersip',
        'perpustakaan' => 'dispersip',
        'dislutkan' => 'dislutkan',
        'kelautan' => 'dislutkan',
        'perikanan' => 'dislutkan',
        'pertanian' => 'dinas pertanian',
        'distan' => 'dinas pertanian',
        'disnakeswan' => 'disnakeswan',
        'peternakan' => 'disnakeswan',
        'setda' => 'sekretariat daerah',
        'sekda' => 'sekretariat daerah',
        'setwan' => 'sekretariat dprd',
        'dprd' => 'sekretariat dprd',
        'bapperida' => 'bapperida',
        'bappeda' => 'bapperida',
        'bkad' => 'bkad',
        'keuangan' => 'bkad',
        'bapenda' => 'bapenda',
        'pendapatan' => 'bapenda',
        'bkpsdm' => 'bkpsdm',
        'kepegawaian' => 'bkpsdm',
        'inspektorat' => 'inspektorat'
    ];

    $targetQuery = $aliases[$q] ?? ($aliases[$qClean] ?? $q);

    // Cek typo pada kamus alias (Levenshtein <= 2 atau metaphone match, misal: 'dikbut' -> 'dikbud', 'dinkkes' -> 'dinkes')
    if (!isset($aliases[$q]) && !isset($aliases[$qClean])) {
        $bestAliasKey = null;
        $lowestLev = 999;
        $srcForAlias = (strlen($qClean) >= 3) ? $qClean : $q;
        $srcMeta = metaphone($srcForAlias);
        foreach ($aliases as $aliasKey => $aliasVal) {
            $lev = levenshtein($srcForAlias, $aliasKey);
            if ($lev <= 2 && $lev < $lowestLev) {
                $lowestLev = $lev;
                $bestAliasKey = $aliasKey;
            } elseif ($srcMeta !== '' && $srcMeta === metaphone($aliasKey)) {
                $bestAliasKey = $aliasKey;
                break;
            }
        }
        if ($bestAliasKey !== null) {
            $targetQuery = $aliases[$bestAliasKey];
        }
    }

    // Ambil daftar UPZ (ringan di memori)
    $res = $conn->query("SELECT id, nama_upz FROM upz ORDER BY id ASC");
    if (!$res) return null;

    $upzList = [];
    while ($r = $res->fetch_assoc()) {
        $parts = explode('|', $r['nama_upz']);
        $cleanName = trim(strtolower($parts[0]));
        $code = isset($parts[1]) ? trim(strtolower($parts[1])) : '';
        $upzList[] = [
            'id' => (int)$r['id'],
            'raw_name' => $r['nama_upz'],
            'nama_upz' => $r['nama_upz'],
            'clean_name' => $cleanName,
            'code' => $code
        ];
    }

    // 1. Cek kecocokan ID jika pengguna mengetik ID (misal: "id 4", "upz 4", "#4")
    if (preg_match('/^(?:id\s*|upz\s*|#)?(\d+)$/i', $q, $mId)) {
        $checkId = (int)$mId[1];
        foreach ($upzList as $item) {
            if ($item['id'] === $checkId) {
                $item['match_confidence'] = 100;
                return $item;
            }
        }
    }

    // 2. Cek kecocokan persis nama_upz lengkap atau clean_name (tanpa kode)
    foreach ($upzList as $item) {
        if (strtolower($item['raw_name']) === $q || $item['clean_name'] === $targetQuery || $item['clean_name'] === $q) {
            $item['match_confidence'] = 100;
            return $item;
        }
    }

    // 3. Cek apakah query ada di dalam clean_name atau clean_name ada di dalam query
    foreach ($upzList as $item) {
        if (strpos($item['clean_name'], $targetQuery) !== false || strpos($targetQuery, $item['clean_name']) !== false) {
            $item['match_confidence'] = 95;
            return $item;
        }
    }

    // 4. Cek pencocokan tanpa kata dinas/kecamatan/badan
    foreach ($upzList as $item) {
        $subClean = preg_replace('/^(dinas\s+|kecamatan\s+|badan\s+|kantor\s+|bagian\s+|sekretariat\s+)/i', '', $item['clean_name']);
        if ($subClean === $qClean || strpos($subClean, $qClean) !== false) {
            $item['match_confidence'] = 90;
            return $item;
        }
    }

    // 5. Cek pencocokan kode rekening jika query mengandung kode angka
    if (preg_match('/[0-9]+\.[0-9]+/', $q)) {
        foreach ($upzList as $item) {
            if ($item['code'] && strpos($item['code'], $q) !== false) {
                $item['match_confidence'] = 95;
                return $item;
            }
        }
    }

    // 6. Cek kemiripan kata mendalam (Similar Text, Levenshtein, & Metaphone Fonetik)
    $bestMatch = null;
    $highestSim = 0;
    $qMetaphone = metaphone($qClean);

    foreach ($upzList as $item) {
        $subClean = preg_replace('/^(dinas\s+|kecamatan\s+|badan\s+|kantor\s+|bagian\s+|sekretariat\s+)/i', '', $item['clean_name']);

        similar_text($targetQuery, $item['clean_name'], $pct1);
        similar_text($qClean, $subClean, $pct2);
        similar_text($targetQuery, $subClean, $pct3);
        $percent = max($pct1, $pct2, $pct3);

        // Kesamaan fonetik bunyi konsonan (Metaphone)
        $subMeta = metaphone($subClean);
        if (!empty($qMetaphone) && !empty($subMeta) && $qMetaphone === $subMeta) {
            $percent = max($percent, 85);
        }

        // Jarak Levenshtein
        $lev = levenshtein($qClean, $subClean);
        if ($lev <= 1 && strlen($qClean) >= 4) {
            $percent = max($percent, 90);
        } elseif ($lev <= 2 && strlen($qClean) >= 5) {
            $percent = max($percent, 80);
        }

        if ($percent > $highestSim && $percent >= 60) {
            $highestSim = $percent;
            $bestMatch = $item;
        }
    }

    if ($bestMatch) {
        $bestMatch['match_confidence'] = round($highestSim, 1);
    }

    return $bestMatch;
}

/**
 * Pencarian Fuzzy Toleran Typo untuk Master UPZ (Wrapper)
 */
function ai_fuzzy_find_upz(mysqli $conn, string $query): ?array
{
    return ai_resolve_upz($conn, $query);
}

/**
 * Resolusi Pintar Muzaki
 */
function ai_resolve_muzaki(mysqli $conn, string $query): ?array
{
    $q = trim(strtolower($query));
    if ($q === '' || $q === 'umum' || $q === 'muzaki umum') return null;

    $clean = preg_replace('/^(h\.|haji\s+|hj\.\s+|ibu\s+|bapak\s+|bpk\s+|sdr\s+|ust\s+|ustadz\s+)/i', '', $q);
    $clean = trim($clean);

    // 1. Exact match
    $stmt = $conn->prepare("SELECT id, nama_muzaki, alamat FROM muzaki WHERE LOWER(nama_muzaki) = ? OR LOWER(nama_muzaki) = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ss', $q, $clean);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res) return $res;
    }

    // 2. Substring match
    if (strlen($clean) >= 3) {
        $safeLike = '%' . $conn->real_escape_string($clean) . '%';
        $resSub = $conn->query("SELECT id, nama_muzaki, alamat FROM muzaki WHERE nama_muzaki LIKE '{$safeLike}' LIMIT 1");
        if ($resSub && $r = $resSub->fetch_assoc()) {
            return $r;
        }
    }

    return null;
}

/**
 * Normalisasi ID Sumber Rekening agar selalu valid dan mencegah pelanggaran Foreign Key Constraint (transaksi_ibfk_7)
 */
function ai_normalize_sumber_rekening(mysqli $conn, $rawInput, int $idJenis = 6, int $idMetode = 4): int
{
    static $validIds = null;
    if ($validIds === null) {
        $validIds = [];
        try {
            $res = $conn->query("SELECT id FROM sumber_rekening");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $validIds[(int)$r['id']] = true;
                }
            }
        } catch (Throwable $e) {}
    }

    $raw = strtolower(trim((string)$rawInput));
    $id = is_numeric($raw) ? (int)$raw : 0;

    // Pemetaan ID lampau / typo (22, 23, 24) ke ID resmi
    if ($id === 22 || $id === 24) $id = 1; // Bank NTB Syariah Zakat
    if ($id === 23) $id = 5; // Bank NTB Syariah Infak

    // Jika ID terdaftar resmi di tabel sumber_rekening, gunakan langsung
    if ($id > 0 && isset($validIds[$id])) {
        return $id;
    }

    // Resolusi cerdas berdasarkan metode dan kata kunci
    if ($idMetode === 5 || strpos($raw, 'tunai') !== false || strpos($raw, 'kas') !== false || strpos($raw, 'buku') !== false) {
        return 11; // BUKU PENGUMPULAN (Kas Tunai)
    }
    if (strpos($raw, 'bri') !== false) {
        return 10; // BANK BRI ZAKAT
    }
    if (strpos($raw, 'mandiri') !== false) {
        return ($idJenis === 7) ? 8 : 9; // 8: Mandiri Infak, 9: Mandiri Zakat
    }

    // Default Bank NTB Syariah
    return ($idJenis === 7) ? 5 : 1; // 5: NTB Infak, 1: NTB Zakat
}

/**
 * Menyiapkan Draf Konfirmasi Penambahan Transaksi (Human-in-the-Loop)
 */
function ai_propose_create_transaction(mysqli $conn, array $txData): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // 1. Validasi & normalisasi nominal
    $nominalRaw = $txData['nominal'] ?? ($txData['jumlah'] ?? ($txData['jumlah_pembayaran'] ?? 0));
    if (is_string($nominalRaw)) {
        $clean = strtolower(trim($nominalRaw));
        if (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta|m)\b/i', $clean, $m)) {
            $val = str_replace(',', '.', $m[1]);
            $nominal = ((float)$val) * 1000000;
        } elseif (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:rb|ribu|k)\b/i', $clean, $m)) {
            $val = str_replace(',', '.', $m[1]);
            $nominal = ((float)$val) * 1000;
        } else {
            $nominal = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9\.,]/', '', $clean));
        }
    } else {
        $nominal = (float)$nominalRaw;
    }

    if ($nominal <= 0) {
        $namaPembayar = trim($txData['nama_pembayar'] ?? ($txData['pembayar'] ?? ''));
        $targetEntity = !empty($namaPembayar) ? htmlspecialchars($namaPembayar) : 'UPZ / Muzaki';
        return [
            'success' => false,
            'reply' => '<div class="alert alert-info mb-0" style="border:1px solid #b8b8b8; border-radius:4px; background:#f0fdf4; color:#166534;"><i class="fas fa-hand-holding-usd mr-2 text-success"></i>Siap, saya siap menyiapkan draf transaksi untuk <strong>' . $targetEntity . '</strong>. Mohon sebutkan nominal dan jenis ZIS yang ingin dicatat.<br><small class="text-muted mt-1 d-block">Contoh ketik: <em>"Catat zakat 5.000.000 via Bank NTB Syariah"</em> atau <em>"Catat infak 2.500.000 via BRI"</em></small></div>',
            'quick_actions' => [
                ['label' => 'ðŸ’° Setor Zakat 5 Juta', 'prompt' => "Catat transaksi zakat Rp 5.000.000 dari {$targetEntity} via Bank NTB Syariah"],
                ['label' => 'ðŸ’¸ Setor Infak 2.5 Juta', 'prompt' => "Catat transaksi infak Rp 2.500.000 dari {$targetEntity} via Bank NTB Syariah"],
                ['label' => 'ðŸ“ Formulir Input Transaksi', 'url' => url('pages/transaksi/create.php')]
            ]
        ];
    }

    // 2. Tanggal Transaksi
    $tgl = trim($txData['tanggal_transaksi'] ?? ($txData['tanggal'] ?? ''));
    if (empty($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $tgl, $tm)) {
            $tgl = sprintf('%04d-%02d-%02d', $tm[3], $tm[2], $tm[1]);
        } else {
            $tgl = date('Y-m-d');
        }
    }

    // 3. Resolusi Pembayar Pintar (Cerdas Kode Rekening UPZ & Muzaki)
    $namaPembayar = trim($txData['nama_pembayar'] ?? ($txData['pembayar'] ?? ''));
    if (empty($namaPembayar)) {
        $namaPembayar = 'Muzaki Umum';
    }

    $id_upz = !empty($txData['id_upz']) ? (int)$txData['id_upz'] : null;
    $id_muzaki = !empty($txData['id_muzaki']) ? (int)$txData['id_muzaki'] : null;
    $tipe = strtolower(trim($txData['tipe_pembayar'] ?? ''));
    $resolvedPembayar = $namaPembayar;
    $matchedSubText = '';

    // Coba temukan di Master UPZ terlebih dahulu
    $upzMatch = null;
    if ($id_upz) {
        $stmtU = $conn->prepare("SELECT id, nama_upz FROM upz WHERE id = ?");
        $stmtU->bind_param('i', $id_upz);
        $stmtU->execute();
        $rU = $stmtU->get_result()->fetch_assoc();
        $stmtU->close();
        if ($rU) {
            $upzMatch = [
                'id' => (int)$rU['id'],
                'raw_name' => $rU['nama_upz'],
                'clean_name' => trim(explode('|', $rU['nama_upz'])[0])
            ];
        }
    }
    if (!$upzMatch) {
        $upzMatch = ai_resolve_upz($conn, $namaPembayar);
    }

    if ($upzMatch) {
        $tipe = 'upz';
        $id_upz = (int)$upzMatch['id'];
        $resolvedPembayar = $upzMatch['raw_name'];
        $badgeEntity = '<span class="badge badge-info" style="font-size:0.68rem; vertical-align:middle;">UPZ</span>';
        $matchedSubText = '<div class="text-success font-weight-bold mt-1" style="font-size:0.75rem;"><i class="fas fa-check-double mr-1"></i>Master UPZ Terdaftar: #' . $id_upz . ' (Kode OPD terdeteksi)</div>';
    } else {
        // Cek Muzaki terdaftar
        $muzakiMatch = null;
        if ($id_muzaki) {
            $stmtM = $conn->prepare("SELECT id, nama_muzaki FROM muzaki WHERE id = ?");
            $stmtM->bind_param('i', $id_muzaki);
            $stmtM->execute();
            $rM = $stmtM->get_result()->fetch_assoc();
            $stmtM->close();
            if ($rM) $muzakiMatch = $rM;
        }
        if (!$muzakiMatch) {
            $muzakiMatch = ai_resolve_muzaki($conn, $namaPembayar);
        }

        if ($muzakiMatch) {
            $tipe = 'muzaki';
            $id_muzaki = (int)$muzakiMatch['id'];
            $resolvedPembayar = $muzakiMatch['nama_muzaki'];
            $badgeEntity = '<span class="badge badge-secondary" style="font-size:0.68rem; vertical-align:middle;">Muzaki</span>';
            $matchedSubText = '<div class="text-success font-weight-bold mt-1" style="font-size:0.75rem;"><i class="fas fa-user-check mr-1"></i>Muzaki Terdaftar (#' . $id_muzaki . ')</div>';
        } else {
            if (empty($tipe)) {
                if (preg_match('/\b(upz|dinas|badan|instansi|kantor|masjid|musholla|sekolah|sman|smkn|smpn|sdn|universitas|pt|cv|koperasi|kecamatan|kelurahan|desa|bapperida|bappeda|bkad|bapenda|bkpsdm|inspektorat|setda|setwan|satpol|damkar|bpbd|dinsos|dinkes|dikbud|pupr)\b/i', $namaPembayar)) {
                    $tipe = 'upz';
                } else {
                    $tipe = 'muzaki';
                }
            }
            if ($tipe === 'upz') {
                $badgeEntity = '<span class="badge badge-info" style="font-size:0.68rem; vertical-align:middle;">UPZ</span>';
                $matchedSubText = '<div class="text-warning font-weight-bold mt-1" style="font-size:0.75rem;"><i class="fas fa-plus-circle mr-1"></i>UPZ Baru (Belum ada di Master)</div>';
            } else {
                $badgeEntity = '<span class="badge badge-secondary" style="font-size:0.68rem; vertical-align:middle;">Muzaki</span>';
                $matchedSubText = '<div class="text-info font-weight-bold mt-1" style="font-size:0.75rem;"><i class="fas fa-user-plus mr-1"></i>Muzaki Baru (Belum terdaftar)</div>';
            }
        }
    }

    // 4. Resolusi Jenis Pembayaran
    $jenisRaw = strtolower(trim((string)($txData['id_jenis_pembayaran'] ?? ($txData['jenis_pembayaran'] ?? ''))));
    if (is_numeric($jenisRaw) && (int)$jenisRaw > 0) {
        $id_jenis = (int)$jenisRaw;
    } elseif (strpos($jenisRaw, 'infak') !== false || strpos($jenisRaw, 'sedekah') !== false || strpos($jenisRaw, 'infaq') !== false) {
        $id_jenis = 7;
    } elseif (strpos($jenisRaw, 'dskl') !== false || strpos($jenisRaw, 'fidyah') !== false) {
        $id_jenis = 8;
    } elseif (strpos($jenisRaw, 'csr') !== false) {
        $id_jenis = 11;
    } else {
        $id_jenis = 6;
    }

    $namaJenis = 'Zakat';
    if ($id_jenis === 7) $namaJenis = 'Infak dan sedekah';
    elseif ($id_jenis === 8) $namaJenis = 'DSKL';
    elseif ($id_jenis === 11) $namaJenis = 'CSR';

    // 5. Resolusi Sub-Jenis Pembayaran
    $subRaw = $txData['id_sub_jenis_pembayaran'] ?? ($txData['sub_jenis_pembayaran'] ?? null);
    $id_sub = null;
    if (is_numeric($subRaw) && (int)$subRaw > 0) {
        $id_sub = (int)$subRaw;
    } else {
        if ($id_jenis === 6) {
            $id_sub = ($tipe === 'upz') ? 11 : 10;
        } elseif ($id_jenis === 7) {
            $id_sub = ($tipe === 'upz') ? 20 : 19;
        } elseif ($id_jenis === 8) {
            $id_sub = 26;
        }
    }

    $namaSub = '';
    if ($id_sub) {
        $stmtSub = $conn->prepare("SELECT nama_sub_jenis FROM sub_jenis_pembayaran WHERE id = ?");
        $stmtSub->bind_param('i', $id_sub);
        $stmtSub->execute();
        $rowSub = $stmtSub->get_result()->fetch_assoc();
        $stmtSub->close();
        if ($rowSub) $namaSub = $rowSub['nama_sub_jenis'];
    }

    // 6. Resolusi Metode Pembayaran
    $metodeRaw = strtolower(trim((string)($txData['id_metode_pembayaran'] ?? ($txData['metode_pembayaran'] ?? ''))));
    if (is_numeric($metodeRaw) && (int)$metodeRaw > 0) {
        $id_metode = (int)$metodeRaw;
    } elseif (strpos($metodeRaw, 'tunai') !== false || strpos($metodeRaw, 'cash') !== false) {
        $id_metode = 5;
    } else {
        $id_metode = 4;
    }
    $namaMetode = ($id_metode === 5) ? 'Tunai' : 'Transfer Bank';

    // 7. Resolusi Sumber Rekening (Aman dari Foreign Key Constraint Violation)
    $id_sumber = ai_normalize_sumber_rekening($conn, $txData['id_sumber_rekening'] ?? ($txData['sumber_rekening'] ?? ''), $id_jenis, $id_metode);

    $namaSumber = 'BANK NTB SYARIAH';
    $stmtS = $conn->prepare("SELECT nama_sumber FROM sumber_rekening WHERE id = ?");
    $stmtS->bind_param('i', $id_sumber);
    $stmtS->execute();
    $rowS = $stmtS->get_result()->fetch_assoc();
    $stmtS->close();
    if ($rowS) $namaSumber = explode('|', $rowS['nama_sumber'])[0];

    // 8. Keterangan
    $ket = trim($txData['keterangan'] ?? '');
    if (empty($ket)) {
        $ket = "Penerimaan " . $namaJenis . " dari " . $resolvedPembayar . " via AI Copilot";
    }

    $normalized = [
        'tanggal_transaksi' => $tgl,
        'tipe_pembayar' => $tipe,
        'id_upz' => $id_upz,
        'id_muzaki' => $id_muzaki,
        'nama_pembayar' => $resolvedPembayar,
        'nominal' => $nominal,
        'id_jenis_pembayaran' => $id_jenis,
        'id_sub_jenis_pembayaran' => $id_sub,
        'id_metode_pembayaran' => $id_metode,
        'id_sumber_rekening' => $id_sumber,
        'keterangan' => $ket
    ];

    // Simpan ke Session sebagai aksi tertunda
    $_SESSION['ai_pending_action'] = [
        'action' => 'create',
        'data' => $normalized,
        'summary' => "Simpan transaksi {$resolvedPembayar} Rp " . number_format($nominal, 0, ',', '.'),
        'timestamp' => time()
    ];

    $tglIndo = date('d/m/Y', strtotime($tgl));
    $nominalFmt = number_format($nominal, 0, ',', '.');

    $cardHtml = <<<HTML
<div class="ai-confirm-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #f59e0b; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #fef3c7 !important;">
        <span class="badge px-2 py-1" style="background:#fffbeb; color:#b45309; border:1px solid #f59e0b; font-size: 0.76rem; font-weight:700;">
            <i class="fas fa-question-circle mr-1"></i> KONFIRMASI PENAMBAHAN TRANSAKSI
        </span>
        <span class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt mr-1"></i>{$tglIndo}</span>
    </div>
    
    <p class="mb-2 text-dark font-weight-normal" style="font-size: 0.83rem;">
        Mohon periksa kembali rincian draf transaksi berikut sebelum disimpan ke pembukuan resmi SIM BAZNAS:
    </p>

    <div class="row" style="font-size: 0.82rem; row-gap: 6px;">
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Pembayar:</small>
            <strong style="color:#1f2937;">{$resolvedPembayar}</strong> {$badgeEntity}
            {$matchedSubText}
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Nominal Disetorkan:</small>
            <span class="font-weight-bold text-success" style="font-size: 1.15rem;">Rp {$nominalFmt}</span>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Jenis Pembayaran:</small>
            <span>{$namaJenis}</span> <small class="text-muted">({$namaSub})</small>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Bank / Metode:</small>
            <span>{$namaSumber}</span> <span class="badge badge-secondary" style="font-size: 0.65rem;">{$namaMetode}</span>
        </div>
        <div class="col-12 mt-1">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Keterangan / Memo:</small>
            <span style="font-style: italic; color: #4b5563;">{$ket}</span>
        </div>
    </div>
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #fef3c7 !important;">
        <button type="button" class="btn btn-xs btn-success font-weight-bold py-1 px-3" onclick="confirmAiPendingAction('create')" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-check-circle mr-1"></i> Ya, Simpan Transaksi
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold py-1 px-3" onclick="cancelAiPendingAction()" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-times mr-1"></i> Batalkan
        </button>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'reply' => "Saya telah menyiapkan draf transaksi berikut. Silakan klik <strong>Ya, Simpan Transaksi</strong> atau ketik <em>\"ya simpan\"</em> jika sudah sesuai:<br>" . $cardHtml,
        'quick_actions' => [
            ['label' => 'âœ… Ya, Simpan Transaksi', 'prompt' => 'ya simpan'],
            ['label' => 'âŒ Batalkan', 'prompt' => 'batalkan']
        ]
    ];
}

/**
 * Menyiapkan Draf Konfirmasi Multi-Setoran / Batch Transaksi (Human-in-the-Loop)
 */
function ai_propose_batch_create_transaction(mysqli $conn, array $batchItems): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (empty($batchItems)) {
        return [
            'success' => false,
            'reply' => '<div class="alert alert-warning mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Tidak ada daftar transaksi yang valid untuk diproses dalam batch.</div>',
            'quick_actions' => []
        ];
    }

    $validated = [];
    $totalNominal = 0;
    $today = date('Y-m-d');

    foreach ($batchItems as $idx => $item) {
        // Nominal
        $nomRaw = $item['nominal'] ?? ($item['jumlah'] ?? ($item['jumlah_pembayaran'] ?? 0));
        if (is_string($nomRaw)) {
            $cleanNom = strtolower(trim($nomRaw));
            if (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta|m)\b/i', $cleanNom, $m)) {
                $val = str_replace(',', '.', $m[1]);
                $nom = ((float)$val) * 1000000;
            } elseif (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:rb|ribu|k)\b/i', $cleanNom, $m)) {
                $val = str_replace(',', '.', $m[1]);
                $nom = ((float)$val) * 1000;
            } else {
                $nom = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9\.,]/', '', $cleanNom));
            }
        } else {
            $nom = (float)$nomRaw;
        }

        if ($nom <= 0) continue;

        // Tanggal
        $tgl = trim($item['tanggal_transaksi'] ?? ($item['tanggal'] ?? ''));
        if (empty($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            $tgl = $today;
        }

        // Pembayar (UPZ / Muzaki)
        $namaRaw = trim($item['nama_pembayar'] ?? ($item['pembayar'] ?? ''));
        $idUpz = !empty($item['id_upz']) ? (int)$item['id_upz'] : null;
        $idMuzaki = !empty($item['id_muzaki']) ? (int)$item['id_muzaki'] : null;
        $tipe = strtolower(trim($item['tipe_pembayar'] ?? ''));

        $resolvedName = $namaRaw;
        if ($idUpz) {
            $tipe = 'upz';
            $stmtU = $conn->prepare("SELECT nama_upz FROM upz WHERE id = ?");
            $stmtU->bind_param('i', $idUpz);
            $stmtU->execute();
            if ($rU = $stmtU->get_result()->fetch_assoc()) $resolvedName = $rU['nama_upz'];
            $stmtU->close();
        } elseif ($idMuzaki) {
            $tipe = 'muzaki';
            $stmtM = $conn->prepare("SELECT nama_muzaki FROM muzaki WHERE id = ?");
            $stmtM->bind_param('i', $idMuzaki);
            $stmtM->execute();
            if ($rM = $stmtM->get_result()->fetch_assoc()) $resolvedName = $rM['nama_muzaki'];
            $stmtM->close();
        } else {
            // Coba fuzzy UPZ
            $fUpz = ai_resolve_upz($conn, $namaRaw);
            if ($fUpz) {
                $tipe = 'upz';
                $idUpz = (int)$fUpz['id'];
                $resolvedName = $fUpz['raw_name'];
            } else {
                $fMuz = ai_resolve_muzaki($conn, $namaRaw);
                if ($fMuz) {
                    $tipe = 'muzaki';
                    $idMuzaki = (int)$fMuz['id'];
                    $resolvedName = $fMuz['nama_muzaki'];
                } else {
                    $tipe = 'upz';
                }
            }
        }

        // Jenis ZIS
        $jenisRaw = strtolower(trim((string)($item['id_jenis_pembayaran'] ?? ($item['jenis_pembayaran'] ?? ''))));
        if ($jenisRaw === '7' || strpos($jenisRaw, 'infak') !== false || strpos($jenisRaw, 'sedekah') !== false) {
            $idJenis = 7;
            $namaJenis = 'Infak';
            $idSub = ($tipe === 'upz') ? 20 : 19;
        } elseif ($jenisRaw === '8' || strpos($jenisRaw, 'dskl') !== false || strpos($jenisRaw, 'fidyah') !== false) {
            $idJenis = 8;
            $namaJenis = 'DSKL';
            $idSub = 26;
        } elseif ($jenisRaw === '11' || strpos($jenisRaw, 'csr') !== false) {
            $idJenis = 11;
            $namaJenis = 'CSR';
            $idSub = null;
        } else {
            $idJenis = 6;
            $namaJenis = 'Zakat';
            $idSub = ($tipe === 'upz') ? 11 : 10;
        }

        // Metode Pembayaran & Bank
        $metodeRaw = strtolower(trim((string)($item['id_metode_pembayaran'] ?? ($item['metode_pembayaran'] ?? ''))));
        $isTunai = ($metodeRaw === '5' || strpos($metodeRaw, 'tunai') !== false || strpos($metodeRaw, 'cash') !== false);
        $idMetode = $isTunai ? 5 : 4;

        $idSumber = ai_normalize_sumber_rekening($conn, $item['id_sumber_rekening'] ?? ($item['bank'] ?? ''), $idJenis, $idMetode);
        $namaSumber = 'BANK NTB SYARIAH';
        $stmtS = $conn->prepare("SELECT nama_sumber FROM sumber_rekening WHERE id = ?");
        $stmtS->bind_param('i', $idSumber);
        $stmtS->execute();
        $rowS = $stmtS->get_result()->fetch_assoc();
        $stmtS->close();
        if ($rowS) $namaSumber = explode('|', $rowS['nama_sumber'])[0];

        $ket = trim($item['keterangan'] ?? '');
        if (empty($ket)) {
            $ket = "Setoran {$namaJenis} via {$resolvedName}";
        }

        $totalNominal += $nom;
        $validated[] = [
            'tanggal_transaksi' => $tgl,
            'id_muzaki' => $idMuzaki,
            'id_upz' => $idUpz,
            'tipe_pembayar' => $tipe,
            'nama_pembayar' => $resolvedName,
            'id_jenis_pembayaran' => $idJenis,
            'nama_jenis' => $namaJenis,
            'id_sub_jenis_pembayaran' => $idSub,
            'id_metode_pembayaran' => $idMetode,
            'id_kategori_dana' => 2,
            'id_sumber_rekening' => $idSumber,
            'nama_sumber' => $namaSumber,
            'nominal' => $nom,
            'keterangan' => $ket
        ];
    }

    if (empty($validated)) {
        return [
            'success' => false,
            'reply' => '<div class="alert alert-warning mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Tidak ada baris transaksi yang memiliki nominal valid. Silakan sebutkan nominal (contoh: <em>"Dikbud 5jt, Dinkes 3jt"</em>).</div>',
            'quick_actions' => []
        ];
    }

    // Simpan draf batch ke Session Pending Action
    $countItems = count($validated);
    $totFmt = number_format($totalNominal, 0, ',', '.');
    $_SESSION['ai_pending_action'] = [
        'action' => 'batch_create',
        'data' => $validated,
        'batch_data' => $validated,
        'summary' => "Pencatatan Batch {$countItems} Transaksi (Total Rp {$totFmt})"
    ];

    // Bangun Tabel Draf Paper White
    $tableRows = '';
    foreach ($validated as $i => $v) {
        $no = $i + 1;
        $nomItemFmt = number_format($v['nominal'], 0, ',', '.');
        $badgeTipe = ($v['tipe_pembayar'] === 'upz')
            ? '<span class="badge badge-info" style="font-size:0.65rem;">UPZ</span>'
            : '<span class="badge badge-secondary" style="font-size:0.65rem;">Muzaki</span>';
        $tglDisp = date('d/m/Y', strtotime($v['tanggal_transaksi']));

        $tableRows .= "<tr>
            <td class='text-center align-middle'>{$no}</td>
            <td class='text-center align-middle font-weight-bold text-nowrap' style='font-size:0.75rem;'>{$tglDisp}</td>
            <td class='align-middle'><strong>" . htmlspecialchars($v['nama_pembayar']) . "</strong> {$badgeTipe}</td>
            <td class='align-middle' style='font-size:0.75rem;'>{$v['nama_jenis']}<br><small class='text-muted'>{$v['nama_sumber']}</small></td>
            <td class='text-right align-middle font-weight-bold text-success text-nowrap'>Rp {$nomItemFmt}</td>
            <td class='align-middle text-muted' style='font-size:0.75rem; max-width:140px; word-break:break-word;'>" . htmlspecialchars($v['keterangan']) . "</td>
        </tr>";
    }

    $cardHtml = <<<HTML
<div class="ai-confirm-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #f59e0b; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #fef3c7 !important;">
        <div class="d-flex align-items-center" style="gap: 8px;">
            <span class="badge px-2 py-1" style="background:#fffbeb; color:#b45309; border:1px solid #f59e0b; font-size: 0.76rem; font-weight:700;">
                <i class="fas fa-layer-group mr-1"></i> KONFIRMASI INPUT MULTI-TRANSAKSI (BATCH)
            </span>
            <span class="font-weight-bold text-dark" style="font-size: 0.8rem;">{$countItems} Transaksi Dideteksi</span>
        </div>
        <span class="font-weight-bold text-success" style="font-size: 0.95rem;">Total: Rp {$totFmt}</span>
    </div>
    
    <p class="mb-2 text-dark font-weight-normal" style="font-size: 0.83rem;">
        Berikut adalah draf rekap transaksi yang berhasil diurai. Silakan konfirmasi untuk menyimpan seluruh baris sekaligus ke database:
    </p>

    <div class="table-responsive my-2">
        <table class="table table-sm table-bordered mb-0" style="font-size: 0.78rem; background: #fff; border: 1px solid #b8b8b8;">
            <thead class="bg-light font-weight-bold text-dark">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th class="text-center" width="12%">Tanggal</th>
                    <th width="30%">Pembayar</th>
                    <th width="22%">Jenis & Bank</th>
                    <th class="text-right" width="16%">Nominal</th>
                    <th width="15%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                {$tableRows}
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="4" class="text-center font-weight-bold">TOTAL KUMULATIF ({$countItems} TRANSAKSI)</td>
                    <td class="text-right text-success font-weight-bold" style="font-size: 0.85rem;">Rp {$totFmt}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #fef3c7 !important;">
        <button type="button" class="btn btn-xs btn-success font-weight-bold py-1 px-3" onclick="confirmAiPendingAction('batch_create')" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-check-double mr-1"></i> Ya, Simpan Semua ({$countItems} Transaksi)
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold py-1 px-3" onclick="cancelAiPendingAction()" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-times mr-1"></i> Batalkan
        </button>
        <a href="pages/transaksi/create_batch.php" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2 ml-auto" style="border-radius:4px; font-size:0.75rem;">
            <i class="fas fa-external-link-alt mr-1"></i> Buka Form Input Massal
        </a>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'reply' => "Saya telah menyiapkan draf batch <strong>{$countItems} transaksi</strong> berikut. Silakan klik <strong>Ya, Simpan Semua</strong> atau ketik <em>\"ya simpan\"</em>:<br>" . $cardHtml,
        'quick_actions' => [
            ['label' => "âœ… Ya, Simpan Semua ({$countItems} Transaksi)", 'prompt' => 'Ya, simpan sekarang'],
            ['label' => 'âŒ Batalkan', 'prompt' => 'Batalkan']
        ]
    ];
}

/**
 * Eksekusi Penyimpanan Atomic Multi-Setoran / Batch Transaksi
 */
function ai_execute_batch_create_transactions(mysqli $conn, array $batchItems, int $userId = 0, string $userName = 'Amil BAZNAS'): array
{
    if (empty($batchItems)) {
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-danger mb-0">Daftar transaksi batch kosong.</div>',
            'quick_actions' => []
        ];
    }

    $conn->begin_transaction();

    $insertedIds = [];
    $totalSaved = 0;

    try {
        $stmtIns = $conn->prepare('INSERT INTO transaksi (tanggal_transaksi, id_muzaki, id_upz, id_jenis_pembayaran, id_sub_jenis_pembayaran, id_metode_pembayaran, id_kategori_dana, id_sumber_rekening, jumlah_pembayaran, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        foreach ($batchItems as $item) {
            $tgl = $item['tanggal_transaksi'] ?? date('Y-m-d');
            $idMuzaki = $item['id_muzaki'] ?? null;
            $idUpz = $item['id_upz'] ?? null;
            $idJenis = (int)($item['id_jenis_pembayaran'] ?? 6);
            $idSub = !empty($item['id_sub_jenis_pembayaran']) ? (int)$item['id_sub_jenis_pembayaran'] : null;
            $idMetode = (int)($item['id_metode_pembayaran'] ?? 4);
            $idKat = (int)($item['id_kategori_dana'] ?? 2);
            $idSumber = ai_normalize_sumber_rekening($conn, $item['id_sumber_rekening'] ?? ($item['bank'] ?? 1), $idJenis, $idMetode);
            $nom = (float)($item['nominal'] ?? 0);
            $ket = (string)($item['keterangan'] ?? '');

            if ($nom <= 0) continue;

            $stmtIns->bind_param('siiiiisids', $tgl, $idMuzaki, $idUpz, $idJenis, $idSub, $idMetode, $idKat, $idSumber, $nom, $ket);
            if (!$stmtIns->execute()) {
                throw new Exception("Gagal menyimpan baris '{$item['nama_pembayar']}': " . $stmtIns->error);
            }

            $insertedIds[] = $conn->insert_id;
            $totalSaved += $nom;
        }

        $stmtIns->close();
        $conn->commit();

        // Invalidate Cache Konteks Skema agar data analitik langsung terupdate
        ai_invalidate_schema_cache();

        // Log Audit Konsolidasi
        $count = count($insertedIds);
        $totFmt = number_format($totalSaved, 0, ',', '.');
        log_audit('CREATE_BATCH_TRANSAKSI_AI', 'transaksi', 0, "Batch {$count} transaksi dicatat via AI Copilot oleh {$userName}. Total: Rp {$totFmt}. IDs: " . implode(',', $insertedIds));

        $urlList = url("pages/transaksi/index.php");

        $cardHtml = <<<HTML
<div class="ai-trx-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #0d9f4f; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #e2e8f0 !important;">
        <span class="badge px-2 py-1" style="background:#f0f7f3; color:#0d9f4f; border:1px solid #0d9f4f; font-size: 0.76rem; font-weight:700;">
            <i class="fas fa-check-double mr-1"></i> BATCH {$count} TRANSAKSI BERHASIL DICATAT
        </span>
        <span class="font-weight-bold text-success" style="font-size: 1.05rem;">Rp {$totFmt}</span>
    </div>
    
    <p class="mb-2 text-dark font-weight-normal" style="font-size: 0.83rem;">
        Alhamdulillah, sebanyak <strong>{$count} transaksi setoran</strong> telah berhasil dibukukan ke sistem SIM BAZNAS secara serentak (ID Transaksi: <code>#TRX-{$insertedIds[0]}</code> s/d <code>#TRX-{$insertedIds[$count - 1]}</code>).
    </p>

    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #e2e8f0 !important;">
        <a href="{$urlList}" target="_blank" class="btn btn-xs btn-outline-primary font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-list mr-1"></i> Buka Halaman Transaksi
        </a>
        <a href="pages/laporan_tahunan/index.php" target="_blank" class="btn btn-xs btn-outline-success font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-chart-bar mr-1"></i> Lihat Laporan Tahunan
        </a>
    </div>
</div>
HTML;

        return [
            'success' => true,
            'ids' => $insertedIds,
            'count' => $count,
            'card_html' => $cardHtml,
            'quick_actions' => [
                ['label' => 'ðŸ“‹ Buka Riwayat Transaksi', 'url' => $urlList],
                ['label' => 'ðŸ“ˆ Lihat Realisasi RKAT', 'prompt' => 'Berapa capaian target RKAT tahun 2026?']
            ]
        ];

    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal menyimpan batch transaksi: ' . htmlspecialchars($e->getMessage()) . ' (Seluruh transaksi dibatalkan demi keamanan data).</div>',
            'quick_actions' => []
        ];
    }
}

/**
 * Parser Teks Multi-Setoran Cerdas (Mendeteksi beberapa transaksi dari teks baris baru / koma / nomor)
 */
function ai_parse_batch_text(mysqli $conn, string $text): ?array
{
    $rawText = trim($text);
    if ($rawText === '') return null;

    // Hapus awalan perintah seperti: "Catat setoran:", "Input transaksi berikut:", "Input ZIS:"
    $rawText = preg_replace('/^(?:tolong\s+)?(?:catat(?:kan)?|input|masukkan|tambah|simpan|rekam)\s+(?:transaksi|setoran|pembayaran|zis|data)?\s*(?:berikut|massal|sekaligus)?\s*[:\-]\s*/i', '', $rawText);

    // Deteksi apakah teks mengandung beberapa baris atau daftar item
    $lines = [];
    if (strpos($rawText, "\n") !== false) {
        $lines = preg_split('/[\r\n;]+/', $rawText);
    } elseif (strpos($rawText, ',') !== false) {
        $lines = explode(',', $rawText);
    } elseif (preg_match('/\b\d+[\.\)]/', $rawText)) {
        $lines = preg_split('/(?:\s*\b\d+[\.\)]\s*)+/', $rawText);
    } else {
        return null;
    }

    $candidates = [];

    foreach ($lines as $line) {
        $l = trim($line);
        if ($l === '') continue;

        // Hapus penomoran baris di awal (misal: "1.", "2)", "-", "*")
        $l = preg_replace('/^(?:\d+[\.\)]|\-|\*)\s*/', '', $l);
        $l = trim($l);
        if ($l === '') continue;

        // Cari nominal transaksi dalam baris ini
        $nom = 0.0;
        $nomMatched = '';
        if (preg_match('/(?:rp\.?\s*)?([0-9]+(?:[,\.][0-9]+)?\s*(?:jt|juta|rb|ribu|k|miliar|milyar)\b)/i', $l, $mNom)) {
            $nomMatched = $mNom[0];
            $nom = ai_parse_smart_nominal($nomMatched);
        } elseif (preg_match('/(?:rp\.?\s*)?([0-9]{1,3}(?:\.[0-9]{3})+(?:,[0-9]+)?|[0-9]{4,})/i', $l, $mNom)) {
            $nomMatched = $mNom[0];
            $nom = ai_parse_smart_nominal($nomMatched);
        }

        if ($nom <= 0) continue;

        // Bersihkan baris untuk mencari entitas pembayar
        $cleanLine = str_ireplace($nomMatched, '', $l);
        $cleanLine = preg_replace('/(catat|setoran|masukkan|tambah|zakat\s*maal|zakat|infak|infaq|sedekah|via|bank|ntb|syariah|bri|mandiri|tunai|cash|sebesar|nominal|rp\.?)/i', '', $cleanLine);
        $cleanLine = trim(preg_replace('/[,\:\-]+/', ' ', $cleanLine));

        if (empty($cleanLine) || strlen($cleanLine) < 2) continue;

        // Resolusi UPZ / Muzaki dengan toleransi alias dan fonetik typo
        $upz = ai_resolve_upz($conn, $cleanLine);
        $entityName = $upz ? $upz['raw_name'] : $cleanLine;
        $idUpz = $upz ? (int)$upz['id'] : null;

        $isInfak = (stripos($l, 'infak') !== false || stripos($l, 'infaq') !== false || stripos($l, 'sedekah') !== false);
        $isTunai = (stripos($l, 'tunai') !== false || stripos($l, 'cash') !== false);
        $isBri = (stripos($l, 'bri') !== false);

        $idJenis = $isInfak ? 7 : 6;
        $idMetode = $isTunai ? 5 : 4;
        $idSumber = ai_normalize_sumber_rekening($conn, $isTunai ? 11 : ($isBri ? 10 : ($isInfak ? 5 : 1)), $idJenis, $idMetode);

        $candidates[] = [
            'tanggal_transaksi' => date('Y-m-d'),
            'tipe_pembayar' => 'upz',
            'id_upz' => $idUpz,
            'nama_pembayar' => $entityName,
            'nominal' => $nom,
            'id_jenis_pembayaran' => $idJenis,
            'id_metode_pembayaran' => $idMetode,
            'id_sumber_rekening' => $idSumber,
            'keterangan' => "Setoran " . ($isInfak ? 'Infak' : 'Zakat') . " via {$entityName}"
        ];
    }

    if (count($candidates) >= 2) {
        return $candidates;
    }

    return null;
}

/**
 * Menyiapkan Draf Konfirmasi Pembaruan Transaksi (Human-in-the-Loop)
 */
function ai_propose_update_transaction(mysqli $conn, int $trxId, array $changes): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if ($trxId <= 0) {
        return [
            'success' => false,
            'reply' => '<div class="alert alert-warning mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Nomor ID transaksi tidak valid atau belum disebutkan. Contoh: <em>\"Ubah nominal transaksi #920 menjadi 2.000.000\"</em>.</div>',
            'quick_actions' => []
        ];
    }

    $stmt = $conn->prepare("SELECT t.*, COALESCE(u.nama_upz, m.nama_muzaki, 'Muzaki Umum') as pembayar, jp.nama_jenis, sjp.nama_sub_jenis, sr.nama_sumber, mp.nama_metode FROM transaksi t LEFT JOIN muzaki m ON t.id_muzaki = m.id LEFT JOIN upz u ON t.id_upz = u.id LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id WHERE t.id = ?");
    $stmt->bind_param('i', $trxId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        return [
            'success' => false,
            'reply' => "<div class='alert alert-danger mb-0' style='border:1px solid #b8b8b8; border-radius:4px;'><i class='fas fa-times-circle mr-2'></i>Transaksi <strong>#TRX-{$trxId}</strong> tidak ditemukan di dalam basis data sistem.</div>",
            'quick_actions' => [
                ['label' => 'ðŸ” Cari Transaksi', 'prompt' => 'Tampilkan 5 transaksi terakhir']
            ]
        ];
    }

    // Normalisasi perubahan
    $cleanChanges = [];
    $comparisonRows = [];

    // 1. Cek Nominal
    if (isset($changes['nominal']) || isset($changes['jumlah']) || isset($changes['jumlah_pembayaran'])) {
        $nominalRaw = $changes['nominal'] ?? ($changes['jumlah'] ?? $changes['jumlah_pembayaran']);
        if (is_string($nominalRaw)) {
            $clean = strtolower(trim($nominalRaw));
            if (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta|m)\b/i', $clean, $m)) {
                $val = str_replace(',', '.', $m[1]);
                $newNom = ((float)$val) * 1000000;
            } elseif (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:rb|ribu|k)\b/i', $clean, $m)) {
                $val = str_replace(',', '.', $m[1]);
                $newNom = ((float)$val) * 1000;
            } else {
                $newNom = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9\.,]/', '', $clean));
            }
        } else {
            $newNom = (float)$nominalRaw;
        }

        if ($newNom > 0 && $newNom != (float)$current['jumlah_pembayaran']) {
            $cleanChanges['jumlah_pembayaran'] = $newNom;
            $comparisonRows[] = [
                'field' => 'Total Nominal',
                'old' => 'Rp ' . number_format($current['jumlah_pembayaran'], 0, ',', '.'),
                'new' => '<strong class="text-success">Rp ' . number_format($newNom, 0, ',', '.') . '</strong>'
            ];
        }
    }

    // 2. Cek Tanggal
    if (!empty($changes['tanggal_transaksi']) || !empty($changes['tanggal'])) {
        $rawTgl = trim($changes['tanggal_transaksi'] ?? $changes['tanggal']);
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $rawTgl, $tm)) {
            $newTgl = sprintf('%04d-%02d-%02d', $tm[3], $tm[2], $tm[1]);
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawTgl)) {
            $newTgl = $rawTgl;
        } else {
            $newTgl = '';
        }

        if ($newTgl && $newTgl !== $current['tanggal_transaksi']) {
            $cleanChanges['tanggal_transaksi'] = $newTgl;
            $comparisonRows[] = [
                'field' => 'Tanggal Transaksi',
                'old' => date('d/m/Y', strtotime($current['tanggal_transaksi'])),
                'new' => '<strong>' . date('d/m/Y', strtotime($newTgl)) . '</strong>'
            ];
        }
    }

    // 3. Cek Keterangan
    if (isset($changes['keterangan'])) {
        $newKet = trim($changes['keterangan']);
        if ($newKet !== '' && $newKet !== $current['keterangan']) {
            $cleanChanges['keterangan'] = $newKet;
            $comparisonRows[] = [
                'field' => 'Keterangan',
                'old' => htmlspecialchars($current['keterangan'] ?: '-'),
                'new' => '<strong>' . htmlspecialchars($newKet) . '</strong>'
            ];
        }
    }

    if (empty($cleanChanges)) {
        return [
            'success' => false,
            'reply' => "Tidak ada perubahan data yang berbeda dengan data transaksi <strong>#TRX-{$trxId}</strong> saat ini.",
            'quick_actions' => [
                ['label' => 'ðŸ“‹ Buka Transaksi #' . $trxId, 'url' => url('pages/transaksi/index.php?search=' . $trxId)]
            ]
        ];
    }

    $_SESSION['ai_pending_action'] = [
        'action' => 'update',
        'id' => $trxId,
        'data' => $cleanChanges,
        'summary' => "Perbarui transaksi #{$trxId}",
        'timestamp' => time()
    ];

    $compHtml = '<table class="table table-sm table-bordered mt-2 mb-0" style="font-size:0.8rem; background:#fff; border:1px solid #b8b8b8;">';
    $compHtml .= '<thead class="bg-light"><tr><th>Kolom</th><th>Data Saat Ini</th><th>Diubah Menjadi</th></tr></thead><tbody>';
    foreach ($comparisonRows as $cr) {
        $compHtml .= "<tr><td><strong>{$cr['field']}</strong></td><td>{$cr['old']}</td><td>{$cr['new']}</td></tr>";
    }
    $compHtml .= '</tbody></table>';

    $cardHtml = <<<HTML
<div class="ai-confirm-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #3b82f6; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #dbeafe !important;">
        <span class="badge px-2 py-1" style="background:#eff6ff; color:#1d4ed8; border:1px solid #3b82f6; font-size: 0.76rem; font-weight:700;">
            <i class="fas fa-edit mr-1"></i> KONFIRMASI PERUBAHAN TRANSAKSI #TRX-{$trxId}
        </span>
        <span class="text-muted" style="font-size: 0.75rem;">Pembayar: <strong>{$current['pembayar']}</strong></span>
    </div>
    
    <p class="mb-2 text-dark font-weight-normal" style="font-size: 0.83rem;">
        Berikut adalah perbandingan data transaksi yang akan diperbarui di sistem:
    </p>

    {$compHtml}
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #dbeafe !important;">
        <button type="button" class="btn btn-xs btn-primary font-weight-bold py-1 px-3" onclick="confirmAiPendingAction('update')" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-check-circle mr-1"></i> Ya, Perbarui Transaksi
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold py-1 px-3" onclick="cancelAiPendingAction()" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-times mr-1"></i> Batalkan
        </button>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'reply' => "Draf perubahan untuk transaksi <strong>#TRX-{$trxId}</strong> telah disiapkan. Mohon konfirmasi sebelum disimpan:<br>" . $cardHtml,
        'quick_actions' => [
            ['label' => 'âœï¸ Ya, Perbarui Transaksi', 'prompt' => 'Ya, perbarui sekarang'],
            ['label' => 'âŒ Batalkan', 'prompt' => 'Batalkan']
        ]
    ];
}

/**
 * Menyiapkan Draf Konfirmasi Penghapusan Transaksi (Human-in-the-Loop)
 */
function ai_propose_delete_transaction(mysqli $conn, int $trxId): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if ($trxId <= 0) {
        return [
            'success' => false,
            'reply' => '<div class="alert alert-warning mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Nomor ID transaksi tidak valid atau belum disebutkan. Contoh: <em>\"Hapus transaksi nomor 920\"</em>.</div>',
            'quick_actions' => []
        ];
    }

    $stmt = $conn->prepare("SELECT t.*, COALESCE(u.nama_upz, m.nama_muzaki, 'Muzaki Umum') as pembayar, jp.nama_jenis, sr.nama_sumber FROM transaksi t LEFT JOIN muzaki m ON t.id_muzaki = m.id LEFT JOIN upz u ON t.id_upz = u.id LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id WHERE t.id = ?");
    $stmt->bind_param('i', $trxId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        return [
            'success' => false,
            'reply' => "<div class='alert alert-danger mb-0' style='border:1px solid #b8b8b8; border-radius:4px;'><i class='fas fa-times-circle mr-2'></i>Transaksi <strong>#TRX-{$trxId}</strong> tidak ditemukan di dalam basis data sistem.</div>",
            'quick_actions' => [
                ['label' => 'ðŸ” Cari Transaksi', 'prompt' => 'Tampilkan 5 transaksi terakhir']
            ]
        ];
    }

    $_SESSION['ai_pending_action'] = [
        'action' => 'delete',
        'id' => $trxId,
        'data' => [],
        'summary' => "Hapus transaksi #{$trxId} ({$current['pembayar']})",
        'timestamp' => time()
    ];

    $tglIndo = date('d/m/Y', strtotime($current['tanggal_transaksi']));
    $nomFmt = number_format($current['jumlah_pembayaran'], 0, ',', '.');
    $pembayar = htmlspecialchars($current['pembayar']);
    $jenis = htmlspecialchars($current['nama_jenis'] ?? '-');
    $bank = htmlspecialchars(explode('|', $current['nama_sumber'] ?? '-')[0]);
    $ket = htmlspecialchars($current['keterangan'] ?: '-');

    $cardHtml = <<<HTML
<div class="ai-delete-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #ef4444; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #fee2e2 !important;">
        <span class="badge px-2 py-1" style="background:#fef2f2; color:#b91c1c; border:1px solid #ef4444; font-size: 0.76rem; font-weight:700;">
            <i class="fas fa-exclamation-triangle mr-1"></i> KONFIRMASI PENGHAPUSAN TRANSAKSI
        </span>
        <span class="text-danger font-weight-bold" style="font-size: 0.8rem;">#TRX-{$trxId}</span>
    </div>
    
    <p class="mb-2 text-danger font-weight-bold" style="font-size: 0.83rem;">
        âš ï¸ Peringatan: Data transaksi berikut akan dihapus secara permanen dari basis data sistem:
    </p>

    <div class="row" style="font-size: 0.82rem; row-gap: 6px;">
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Pembayar:</small>
            <strong style="color:#1f2937;">{$pembayar}</strong>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Total Nominal:</small>
            <span class="font-weight-bold text-danger" style="font-size: 1.15rem;">Rp {$nomFmt}</span>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Tanggal & Jenis:</small>
            <span>{$tglIndo}</span> &bull; <span>{$jenis}</span>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Rekening Bank:</small>
            <span>{$bank}</span>
        </div>
        <div class="col-12 mt-1">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Keterangan:</small>
            <span style="font-style: italic; color: #4b5563;">{$ket}</span>
        </div>
    </div>
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #fee2e2 !important;">
        <button type="button" class="btn btn-xs btn-danger font-weight-bold py-1 px-3" onclick="confirmAiPendingAction('delete')" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-trash-alt mr-1"></i> Ya, Hapus Transaksi Permanen
        </button>
        <button type="button" class="btn btn-xs btn-outline-secondary font-weight-bold py-1 px-3" onclick="cancelAiPendingAction()" style="border-radius:4px; font-size:0.8rem;">
            <i class="fas fa-times mr-1"></i> Batalkan
        </button>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'reply' => "Apakah Anda yakin ingin menghapus transaksi <strong>#TRX-{$trxId}</strong>? Tindakan ini tidak dapat diurungkan:<br>" . $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ—‘ï¸ Ya, Hapus Transaksi', 'prompt' => 'Ya, hapus transaksi sekarang'],
            ['label' => 'âŒ Batalkan', 'prompt' => 'Batalkan']
        ]
    ];
}

/**
 * Mengeksekusi Pembaruan Transaksi Secara Aman
 */
function ai_execute_update_transaction(mysqli $conn, int $trxId, array $changes, int $userId = 0, string $userName = 'Amil BAZNAS'): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    unset($_SESSION['ai_pending_action']);

    if ($trxId <= 0 || empty($changes)) {
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Parameter pembaruan transaksi tidak valid.</div>',
            'quick_actions' => []
        ];
    }

    $sets = [];
    $types = '';
    $values = [];

    if (isset($changes['jumlah_pembayaran'])) {
        $sets[] = "jumlah_pembayaran = ?";
        $types .= 'd';
        $values[] = (float)$changes['jumlah_pembayaran'];
    }
    if (isset($changes['tanggal_transaksi'])) {
        $sets[] = "tanggal_transaksi = ?";
        $types .= 's';
        $values[] = $changes['tanggal_transaksi'];
    }
    if (isset($changes['keterangan'])) {
        $sets[] = "keterangan = ?";
        $types .= 's';
        $values[] = $changes['keterangan'];
    }
    if (isset($changes['id_sumber_rekening'])) {
        $sets[] = "id_sumber_rekening = ?";
        $types .= 'i';
        $values[] = (int)$changes['id_sumber_rekening'];
    }
    if (isset($changes['id_metode_pembayaran'])) {
        $sets[] = "id_metode_pembayaran = ?";
        $types .= 'i';
        $values[] = (int)$changes['id_metode_pembayaran'];
    }

    if (empty($sets)) {
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Tidak ada kolom yang dapat diperbarui.</div>',
            'quick_actions' => []
        ];
    }

    $sql = "UPDATE transaksi SET " . implode(', ', $sets) . ", updated_at = NOW() WHERE id = ?";
    $types .= 'i';
    $values[] = $trxId;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $res = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$res) {
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal memperbarui transaksi: ' . htmlspecialchars($err) . '</div>',
            'quick_actions' => []
        ];
    }

    // Invalidate Cache Konteks Skema
    ai_invalidate_schema_cache();

    // Log Audit
    log_audit('UPDATE_TRANSAKSI_AI', 'transaksi', $trxId, "Diperbarui via AI Copilot oleh {$userName}. Detail: " . json_encode($changes));

    $urlCetak = url("pages/transaksi/print_bukti.php?id={$trxId}");
    $urlLihat = url("pages/transaksi/index.php?search={$trxId}");

    $cardHtml = <<<HTML
<div class="ai-trx-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #3b82f6; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #dbeafe !important;">
        <span class="badge px-2 py-1" style="background:#eff6ff; color:#1d4ed8; border:1px solid #3b82f6; font-size: 0.76rem; font-weight:700;">
            <i class="fas fa-check-circle mr-1"></i> TRANSAKSI BERHASIL DIPERBARUI
        </span>
        <span class="text-muted font-weight-bold" style="font-size: 0.8rem;">#TRX-{$trxId}</span>
    </div>
    
    <p class="mb-2 text-dark font-weight-normal" style="font-size: 0.83rem;">
        Perubahan data transaksi <strong>#TRX-{$trxId}</strong> telah berhasil disimpan ke dalam basis data SIM BAZNAS.
    </p>
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #dbeafe !important;">
        <a href="{$urlLihat}" target="_blank" class="btn btn-xs btn-outline-primary font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-list mr-1"></i> Buka di Tabel Transaksi
        </a>
        <a href="{$urlCetak}" target="_blank" class="btn btn-xs btn-outline-success font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-print mr-1"></i> Cetak Bukti Setor Baru (PDF)
        </a>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'id' => $trxId,
        'card_html' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ“‹ Buka Transaksi #' . $trxId, 'url' => $urlLihat],
            ['label' => 'ðŸ–¨ï¸ Cetak Bukti Setor', 'url' => $urlCetak],
            ['label' => 'ðŸ“Š Capaian RKAT', 'prompt' => 'Berapa capaian target RKAT tahun 2026?']
        ]
    ];
}

/**
 * Mengeksekusi Penghapusan Transaksi Secara Aman
 */
function ai_execute_delete_transaction(mysqli $conn, int $trxId, int $userId = 0, string $userName = 'Amil BAZNAS'): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    unset($_SESSION['ai_pending_action']);

    if ($trxId <= 0) {
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> ID transaksi tidak valid.</div>',
            'quick_actions' => []
        ];
    }

    $stmtInfo = $conn->prepare("SELECT t.*, COALESCE(u.nama_upz, m.nama_muzaki, 'Muzaki Umum') as pembayar FROM transaksi t LEFT JOIN muzaki m ON t.id_muzaki = m.id LEFT JOIN upz u ON t.id_upz = u.id WHERE t.id = ?");
    $stmtInfo->bind_param('i', $trxId);
    $stmtInfo->execute();
    $rowInfo = $stmtInfo->get_result()->fetch_assoc();
    $stmtInfo->close();

    $pembayar = $rowInfo['pembayar'] ?? 'Umum';
    $nominal = (float)($rowInfo['jumlah_pembayaran'] ?? 0);
    $nominalFmt = number_format($nominal, 0, ',', '.');

    $stmt = $conn->prepare("DELETE FROM transaksi WHERE id = ?");
    $stmt->bind_param('i', $trxId);
    $res = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();

    if (!$res) {
        return [
            'success' => false,
            'card_html' => '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal menghapus transaksi: ' . htmlspecialchars($err) . '</div>',
            'quick_actions' => []
        ];
    }

    // Invalidate Cache Konteks Skema
    ai_invalidate_schema_cache();

    // Log Audit
    log_audit('DELETE_TRANSAKSI_AI', 'transaksi', $trxId, "Dihapus via AI Copilot oleh {$userName}. Pembayar: {$pembayar}, Nominal: Rp {$nominalFmt}");

    $urlList = url("pages/transaksi/index.php");

    $cardHtml = <<<HTML
<div class="ai-delete-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #6b7280; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #e5e7eb !important;">
        <span class="badge px-2 py-1" style="background:#f3f4f6; color:#374151; border:1px solid #9ca3af; font-size: 0.76rem; font-weight:700;">
            <i class="fas fa-check-circle mr-1 text-success"></i> TRANSAKSI BERHASIL DIHAPUS
        </span>
        <span class="text-muted font-weight-bold" style="font-size: 0.8rem;">#TRX-{$trxId}</span>
    </div>
    
    <p class="mb-0 text-dark font-weight-normal" style="font-size: 0.83rem;">
        Transaksi <strong>#TRX-{$trxId}</strong> atas nama <strong>{$pembayar}</strong> senilai <strong class="text-dark">Rp {$nominalFmt}</strong> telah berhasil dihapus dari sistem pembukuan SIM BAZNAS.
    </p>
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #e5e7eb !important;">
        <a href="{$urlList}" target="_blank" class="btn btn-xs btn-outline-primary font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-list mr-1"></i> Buka Daftar Transaksi
        </a>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'id' => $trxId,
        'card_html' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ“‹ Buka Daftar Transaksi', 'url' => $urlList],
            ['label' => 'ðŸ“Š Capaian RKAT 2026', 'prompt' => 'Berapa capaian target RKAT tahun 2026?']
        ]
    ];
}

/**
 * Mengeksekusi Pembuatan Transaksi Baru Secara Aman (Prepared Statement & Audit Log)
 */
function ai_execute_create_transaction(mysqli $conn, array $txData, int $userId = 0, string $userName = 'Amil BAZNAS'): array
{
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    unset($_SESSION['ai_pending_action']);
    // 1. Validasi & normalisasi nominal
    $nominalRaw = $txData['nominal'] ?? ($txData['jumlah'] ?? ($txData['jumlah_pembayaran'] ?? 0));
    if (is_string($nominalRaw)) {
        $clean = strtolower(trim($nominalRaw));
        if (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:jt|juta|m)\b/i', $clean, $m)) {
            $val = str_replace(',', '.', $m[1]);
            $nominal = ((float)$val) * 1000000;
        } elseif (preg_match('/([0-9]+(?:[\.,][0-9]+)?)\s*(?:rb|ribu|k)\b/i', $clean, $m)) {
            $val = str_replace(',', '.', $m[1]);
            $nominal = ((float)$val) * 1000;
        } else {
            $nominal = (float)str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9\.,]/', '', $clean));
        }
    } else {
        $nominal = (float)$nominalRaw;
    }

    if ($nominal <= 0) {
        $namaPembayar = trim($txData['nama_pembayar'] ?? ($txData['pembayar'] ?? ''));
        $targetEntity = !empty($namaPembayar) ? htmlspecialchars($namaPembayar) : 'UPZ / Muzaki';
        return [
            'success' => false,
            'message' => 'Nominal transaksi belum ditentukan.',
            'card_html' => '<div class="alert alert-info mb-0" style="border:1px solid #b8b8b8; border-radius:4px; background:#f0fdf4; color:#166534;"><i class="fas fa-hand-holding-usd mr-2 text-success"></i>Siap, saya siap mencatat transaksi untuk <strong>' . $targetEntity . '</strong>. Mohon sebutkan berapa nominal dan jenis ZIS yang ingin dicatat.<br><small class="text-muted mt-1 d-block">Contoh ketik: <em>"Setor zakat 5 juta via Bank NTB Syariah"</em></small></div>',
            'quick_actions' => [
                ['label' => 'ðŸ’° Setor Zakat 5 Juta', 'prompt' => "Catat transaksi zakat Rp 5.000.000 dari {$targetEntity} via Bank NTB Syariah"],
                ['label' => 'ðŸ’¸ Setor Infak 2.5 Juta', 'prompt' => "Catat transaksi infak Rp 2.500.000 dari {$targetEntity} via Bank NTB Syariah"],
                ['label' => 'ðŸ“ Formulir Input Transaksi', 'url' => url('pages/transaksi/create.php')]
            ]
        ];
    }

    // 2. Tanggal Transaksi
    $tgl = trim($txData['tanggal_transaksi'] ?? ($txData['tanggal'] ?? ''));
    if (empty($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $tgl, $tm)) {
            $tgl = sprintf('%04d-%02d-%02d', $tm[3], $tm[2], $tm[1]);
        } else {
            $tgl = date('Y-m-d');
        }
    }

    // 3. Resolusi Pembayar (UPZ vs Muzaki)
    $namaPembayar = trim($txData['nama_pembayar'] ?? ($txData['pembayar'] ?? ''));
    if (empty($namaPembayar)) {
        $namaPembayar = 'Muzaki Umum';
    }

    $tipe = strtolower(trim($txData['tipe_pembayar'] ?? ''));
    if (empty($tipe)) {
        if (preg_match('/\b(upz|dinas|badan|instansi|kantor|masjid|musholla|sekolah|sman|smkn|smpn|sdn|universitas|pt|cv|koperasi|kecamatan|kelurahan|desa)\b/i', $namaPembayar)) {
            $tipe = 'upz';
        } else {
            $tipe = 'muzaki';
        }
    }

    $id_muzaki = !empty($txData['id_muzaki']) ? (int)$txData['id_muzaki'] : null;
    $id_upz = !empty($txData['id_upz']) ? (int)$txData['id_upz'] : null;
    $isNewEntity = false;
    $resolvedPembayar = $namaPembayar;

    if ($id_upz) {
        $tipe = 'upz';
        $stmt = $conn->prepare("SELECT id, nama_upz FROM upz WHERE id = ?");
        $stmt->bind_param('i', $id_upz);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res) {
            $resolvedPembayar = $res['nama_upz'];
        }
    } elseif ($id_muzaki) {
        $tipe = 'muzaki';
        $stmt = $conn->prepare("SELECT id, nama_muzaki FROM muzaki WHERE id = ?");
        $stmt->bind_param('i', $id_muzaki);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($res) {
            $resolvedPembayar = $res['nama_muzaki'];
        }
    } else {
        // Coba Smart Match UPZ (Cerdas Kode Rekening & OPD)
        $upzMatch = ai_resolve_upz($conn, $namaPembayar);
        if ($upzMatch) {
            $tipe = 'upz';
            $id_upz = (int)$upzMatch['id'];
            $resolvedPembayar = $upzMatch['raw_name'];
        } else {
            // Coba Smart Match Muzaki
            $muzakiMatch = ai_resolve_muzaki($conn, $namaPembayar);
            if ($muzakiMatch) {
                $tipe = 'muzaki';
                $id_muzaki = (int)$muzakiMatch['id'];
                $resolvedPembayar = $muzakiMatch['nama_muzaki'];
            } else {
                if (empty($tipe)) {
                    if (preg_match('/\b(upz|dinas|badan|instansi|kantor|masjid|musholla|sekolah|sman|smkn|smpn|sdn|universitas|pt|cv|koperasi|kecamatan|kelurahan|desa|bapperida|bappeda|bkad|bapenda|bkpsdm|inspektorat|setda|setwan|satpol|damkar|bpbd|dinsos|dinkes|dikbud|pupr)\b/i', $namaPembayar)) {
                        $tipe = 'upz';
                    } else {
                        $tipe = 'muzaki';
                    }
                }

                if ($tipe === 'upz') {
                    $stmtIns = $conn->prepare("INSERT INTO upz (nama_upz, created_at) VALUES (?, NOW())");
                    $stmtIns->bind_param('s', $namaPembayar);
                    if ($stmtIns->execute()) {
                        $id_upz = $conn->insert_id;
                        $isNewEntity = true;
                        log_audit('CREATE_UPZ_AI', 'upz', $id_upz, "Auto created via AI Copilot by {$userName}: {$namaPembayar}");
                    }
                    $stmtIns->close();
                } else {
                    $stmtIns = $conn->prepare("INSERT INTO muzaki (nama_muzaki, alamat, created_at) VALUES (?, '', NOW())");
                    $stmtIns->bind_param('s', $namaPembayar);
                    if ($stmtIns->execute()) {
                        $id_muzaki = $conn->insert_id;
                        $isNewEntity = true;
                        log_audit('CREATE_MUZAKI_AI', 'muzaki', $id_muzaki, "Auto created via AI Copilot by {$userName}: {$namaPembayar}");
                    }
                    $stmtIns->close();
                }
            }
        }
    }

    // 4. Resolusi Jenis Pembayaran
    $jenisRaw = strtolower(trim((string)($txData['id_jenis_pembayaran'] ?? ($txData['jenis_pembayaran'] ?? ''))));
    if (is_numeric($jenisRaw) && (int)$jenisRaw > 0) {
        $id_jenis = (int)$jenisRaw;
    } elseif (strpos($jenisRaw, 'infak') !== false || strpos($jenisRaw, 'sedekah') !== false || strpos($jenisRaw, 'infaq') !== false) {
        $id_jenis = 7;
    } elseif (strpos($jenisRaw, 'dskl') !== false || strpos($jenisRaw, 'fidyah') !== false) {
        $id_jenis = 8;
    } elseif (strpos($jenisRaw, 'csr') !== false) {
        $id_jenis = 11;
    } else {
        $id_jenis = 6;
    }

    $namaJenis = 'Zakat';
    if ($id_jenis === 7) $namaJenis = 'Infak dan sedekah';
    elseif ($id_jenis === 8) $namaJenis = 'DSKL';
    elseif ($id_jenis === 11) $namaJenis = 'CSR';

    // 5. Resolusi Sub-Jenis Pembayaran
    $subRaw = $txData['id_sub_jenis_pembayaran'] ?? ($txData['sub_jenis_pembayaran'] ?? null);
    $id_sub = null;
    if (is_numeric($subRaw) && (int)$subRaw > 0) {
        $id_sub = (int)$subRaw;
    } else {
        if ($id_jenis === 6) {
            $id_sub = ($id_upz !== null) ? 11 : 10;
        } elseif ($id_jenis === 7) {
            $id_sub = ($id_upz !== null) ? 20 : 19;
        } elseif ($id_jenis === 8) {
            $id_sub = 26;
        }
    }

    $namaSub = '';
    if ($id_sub) {
        $stmtSub = $conn->prepare("SELECT nama_sub_jenis FROM sub_jenis_pembayaran WHERE id = ?");
        $stmtSub->bind_param('i', $id_sub);
        $stmtSub->execute();
        $rowSub = $stmtSub->get_result()->fetch_assoc();
        $stmtSub->close();
        if ($rowSub) $namaSub = $rowSub['nama_sub_jenis'];
    }

    // 6. Resolusi Metode Pembayaran
    $metodeRaw = strtolower(trim((string)($txData['id_metode_pembayaran'] ?? ($txData['metode_pembayaran'] ?? ''))));
    if (is_numeric($metodeRaw) && (int)$metodeRaw > 0) {
        $id_metode = (int)$metodeRaw;
    } elseif (strpos($metodeRaw, 'tunai') !== false || strpos($metodeRaw, 'cash') !== false) {
        $id_metode = 5;
    } else {
        $id_metode = 4;
    }
    $namaMetode = ($id_metode === 5) ? 'Tunai' : 'Transfer Bank';

    // 7. Resolusi Sumber Rekening (Aman dari Foreign Key Constraint Violation)
    $id_sumber = ai_normalize_sumber_rekening($conn, $txData['id_sumber_rekening'] ?? ($txData['sumber_rekening'] ?? ''), $id_jenis, $id_metode);

    $namaSumber = 'BANK NTB SYARIAH';
    $stmtS = $conn->prepare("SELECT nama_sumber FROM sumber_rekening WHERE id = ?");
    $stmtS->bind_param('i', $id_sumber);
    $stmtS->execute();
    $rowS = $stmtS->get_result()->fetch_assoc();
    $stmtS->close();
    if ($rowS) $namaSumber = explode('|', $rowS['nama_sumber'])[0];

    // 8. Kategori Dana (Default [2] = Pengumpulan)
    $id_kategori_json = json_encode([2]);

    // 9. Keterangan
    $ket = trim($txData['keterangan'] ?? '');
    if (empty($ket)) {
        $ket = "Penerimaan " . $namaJenis . " dari " . $resolvedPembayar . " via AI Copilot";
    }

    // 10. Simpan ke database transaksi
    $stmtIns = $conn->prepare('INSERT INTO transaksi (tanggal_transaksi, id_muzaki, id_upz, id_jenis_pembayaran, id_sub_jenis_pembayaran, id_metode_pembayaran, id_kategori_dana, id_sumber_rekening, jumlah_pembayaran, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmtIns->bind_param('siiiiisids', $tgl, $id_muzaki, $id_upz, $id_jenis, $id_sub, $id_metode, $id_kategori_json, $id_sumber, $nominal, $ket);

    if (!$stmtIns->execute()) {
        $err = $conn->error;
        $stmtIns->close();
        return [
            'success' => false,
            'message' => 'Gagal menyimpan transaksi: ' . $err,
            'card_html' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal menyimpan transaksi ke basis data: ' . htmlspecialchars($err) . '</div>',
            'quick_actions' => []
        ];
    }

    $newTrxId = $conn->insert_id;
    $stmtIns->close();

    // Invalidate Cache Konteks Skema agar AI langsung membaca data transaksi terbaru
    ai_invalidate_schema_cache();

    // Catat ke Audit Log
    log_audit('CREATE_TRANSAKSI_AI', 'transaksi', $newTrxId, "Dibuat via Amil Virtual AI oleh {$userName}. Nominal: Rp " . number_format($nominal, 0, ',', '.') . ", Pembayar: {$resolvedPembayar}");

    // Format Data Tampilan
    $tglIndo = date('d/m/Y', strtotime($tgl));
    $nominalFmt = number_format($nominal, 0, ',', '.');
    $badgeEntity = ($tipe === 'upz')
        ? '<span class="badge badge-info" style="font-size:0.68rem; vertical-align:middle;">UPZ</span>'
        : '<span class="badge badge-secondary" style="font-size:0.68rem; vertical-align:middle;">Muzaki</span>';
    if ($isNewEntity) {
        $badgeEntity .= ' <span class="badge badge-success" style="font-size:0.65rem; background:#f0f7f3; color:#0d9f4f; border:1px solid #0d9f4f; vertical-align:middle;"><i class="fas fa-plus mr-1"></i>Baru Didaftarkan</span>';
    }

    $urlCetak = url("pages/transaksi/print_bukti.php?id={$newTrxId}");
    $urlLihat = url("pages/transaksi/index.php?search={$newTrxId}");

    // Format Card HTML (Paper White & Paper Dark compatible)
    $cardHtml = <<<HTML
<div class="ai-trx-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #0d9f4f; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #e2e8f0 !important;">
        <div class="d-flex align-items-center" style="gap: 8px;">
            <span class="badge badge-success px-2 py-1" style="background:#f0f7f3; color:#0d9f4f; border:1px solid #0d9f4f; font-size: 0.76rem; font-weight:700;">
                <i class="fas fa-check-circle mr-1"></i> TRANSAKSI BERHASIL DICATAT
            </span>
            <span class="text-muted font-weight-bold" style="font-size: 0.8rem;">#TRX-{$newTrxId}</span>
        </div>
        <span class="text-muted" style="font-size: 0.75rem;"><i class="far fa-calendar-alt mr-1"></i>{$tglIndo}</span>
    </div>
    
    <div class="row" style="font-size: 0.82rem; row-gap: 6px;">
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Pembayar:</small>
            <strong style="color:#1f2937;">{$resolvedPembayar}</strong> {$badgeEntity}
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Total Nominal:</small>
            <span class="font-weight-bold text-success" style="font-size: 1.15rem;">Rp {$nominalFmt}</span>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Jenis Pembayaran:</small>
            <span>{$namaJenis}</span> <small class="text-muted">({$namaSub})</small>
        </div>
        <div class="col-sm-6">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Bank / Metode:</small>
            <span>{$namaSumber}</span> <span class="badge badge-secondary" style="font-size: 0.65rem;">{$namaMetode}</span>
        </div>
        <div class="col-12 mt-1">
            <small class="text-muted d-block" style="font-size: 0.72rem; text-transform:uppercase;">Keterangan / Memo:</small>
            <span style="font-style: italic; color: #4b5563;">{$ket}</span>
        </div>
    </div>
    
    <div class="mt-3 pt-2 border-top d-flex flex-wrap align-items-center" style="gap: 8px; border-color: #e2e8f0 !important;">
        <a href="{$urlCetak}" target="_blank" class="btn btn-xs btn-outline-success font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-print mr-1"></i> Cetak Bukti Setor (PDF)
        </a>
        <a href="{$urlLihat}" target="_blank" class="btn btn-xs btn-outline-primary font-weight-bold py-1 px-3" style="border-radius:4px; font-size:0.78rem;">
            <i class="fas fa-list mr-1"></i> Buka di Tabel Transaksi
        </a>
    </div>
</div>
HTML;

    return [
        'success' => true,
        'id' => $newTrxId,
        'message' => 'Transaksi berhasil dicatat ke sistem.',
        'card_html' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ–¨ï¸ Cetak Bukti Setor #' . $newTrxId, 'url' => $urlCetak],
            ['label' => 'ðŸ“‹ Buka Transaksi #' . $newTrxId, 'url' => $urlLihat],
            ['label' => 'âž• Catat Transaksi Lagi', 'prompt' => 'Catat transaksi zakat/infak sebesar ']
        ]
    ];
}

/**
 * Briefing Eksekutif Pagi Otomatis (Proactive AI Morning Briefing)
 */
function ai_generate_morning_briefing(mysqli $conn, string $userName = 'Amil BAZNAS'): array
{
    $hariMap = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
    $bulanMap = [1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'];
    
    $hariIni = $hariMap[date('l')] ?? date('l');
    $tglStr = $hariIni . ', ' . date('d') . ' ' . ($bulanMap[(int)date('m')] ?? date('F')) . ' ' . date('Y');
    
    $blnIni = (int)date('m');
    $tahunIni = (int)date('Y');
    $namaBlnIni = $bulanMap[$blnIni] ?? "Bulan {$blnIni}";

    // 1. Realisasi Bulan Berjalan & Capaian Tahunan
    $totBln = 0;
    $cntBln = 0;
    $resBln = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(jumlah_pembayaran), 0) as tot FROM transaksi WHERE MONTH(tanggal_transaksi) = {$blnIni} AND YEAR(tanggal_transaksi) = {$tahunIni}");
    if ($resBln && $rBln = $resBln->fetch_assoc()) {
        $totBln = (float)$rBln['tot'];
        $cntBln = (int)$rBln['cnt'];
    }

    $targetRkat = 0;
    $resTgt = $conn->query("SELECT nominal_target FROM target_pengumpulan_tahunan WHERE tahun = {$tahunIni} LIMIT 1");
    if ($resTgt && $rTgt = $resTgt->fetch_assoc()) {
        $targetRkat = (float)$rTgt['nominal_target'];
    }

    $totThn = 0;
    $cntThn = 0;
    $resThn = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(jumlah_pembayaran), 0) as tot FROM transaksi WHERE YEAR(tanggal_transaksi) = {$tahunIni}");
    if ($resThn && $rThn = $resThn->fetch_assoc()) {
        $totThn = (float)$rThn['tot'];
        $cntThn = (int)$rThn['cnt'];
    }
    $persenRkat = $targetRkat > 0 ? round(($totThn / $targetRkat) * 100, 1) : 0;

    // 2. UPZ Prioritas (Setor di bulan lalu tapi belum setor bulan ini)
    $upzPrioritas = [];
    $blnLalu = $blnIni > 1 ? $blnIni - 1 : 12;
    $thnLalu = $blnIni > 1 ? $tahunIni : $tahunIni - 1;
    $sqlPrio = "SELECT u.id, u.nama_upz 
                FROM upz u 
                WHERE u.id IN (
                    SELECT DISTINCT id_upz 
                    FROM transaksi 
                    WHERE MONTH(tanggal_transaksi) = {$blnLalu} AND YEAR(tanggal_transaksi) = {$thnLalu} AND id_upz IS NOT NULL
                ) 
                AND u.id NOT IN (
                    SELECT DISTINCT id_upz 
                    FROM transaksi 
                    WHERE MONTH(tanggal_transaksi) = {$blnIni} AND YEAR(tanggal_transaksi) = {$tahunIni} AND id_upz IS NOT NULL
                ) 
                LIMIT 3";
    $resPrio = $conn->query($sqlPrio);
    if ($resPrio) {
        while ($rp = $resPrio->fetch_assoc()) {
            $rawP = explode('|', $rp['nama_upz'])[0];
            $upzPrioritas[] = trim($rawP);
        }
    }

    // Format Card HTML
    $totBlnFmt = number_format($totBln, 0, ',', '.');
    $totThnFmt = number_format($totThn, 0, ',', '.');
    $targetRkatFmt = number_format($targetRkat, 0, ',', '.');

    $prioHtml = '';
    if (!empty($upzPrioritas)) {
        $prioItems = array_map(function($p) { return "<span class='badge mr-1 mb-1' style='border:1px solid #f59e0b; color:#b45309; background:rgba(245,158,11,0.06); font-size:0.75rem;'>â€¢ {$p}</span>"; }, $upzPrioritas);
        $prioHtml = implode(' ', $prioItems);
    } else {
        $prioHtml = "<span class='text-muted' style='font-size:0.78rem;'>Semua UPZ rutin terpantau aktif atau belum ada data setoran tertunda bulan ini.</span>";
    }

    $cardHtml = <<<HTML
<div class="ai-briefing-card ai-custom-card my-2 p-3 rounded text-left" style="border: 1.5px solid #0d9f4f; box-shadow: none !important;">
    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom" style="border-color: #e2e8f0 !important;">
        <div class="d-flex align-items-center" style="gap: 8px;">
            <span class="badge px-2 py-1" style="background:#f0f7f3; color:#0d9f4f; border:1px solid #0d9f4f; font-size: 0.76rem; font-weight:700;">
                <i class="fas fa-sun mr-1 text-warning"></i> BRIEFING EKSEKUTIF AMIL
            </span>
            <span class="text-muted font-weight-bold" style="font-size: 0.8rem;">{$tglStr}</span>
        </div>
        <span class="badge badge-secondary" style="font-size: 0.72rem;">Tahun {$tahunIni}</span>
    </div>

    <p class="mb-2 text-dark" style="font-size: 0.85rem;">
        Assalamuâ€™alaikum Warahmatullahi Wabarakatuh, <strong>{$userName}</strong>! Selamat pagi dan salam semangat amil. Berikut ringkasan intelijen pengumpulan hari ini:
    </p>

    <div class="row" style="font-size: 0.82rem; row-gap: 8px;">
        <div class="col-sm-6">
            <div class="p-2 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.7rem;">Realisasi {$namaBlnIni}:</small>
                <div class="text-success font-weight-bold" style="font-size: 1.05rem;">Rp {$totBlnFmt}</div>
                <small class="text-muted">{$cntBln} Transaksi tercatat</small>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="p-2 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.7rem;">Capaian RKAT {$tahunIni}:</small>
                <div class="text-dark font-weight-bold" style="font-size: 1.05rem;">{$persenRkat}%</div>
                <small class="text-muted">Rp {$totThnFmt} dari Rp {$targetRkatFmt}</small>
            </div>
        </div>
        <div class="col-12 mt-1">
            <small class="text-muted d-block font-weight-bold text-uppercase mb-1" style="font-size: 0.7rem;">
                <i class="fas fa-clock text-warning mr-1"></i> UPZ Prioritas Pengingat Setoran Bulan {$namaBlnIni}:
            </small>
            {$prioHtml}
        </div>
    </div>
</div>
HTML;

    return [
        'status' => 'success',
        'reply' => $cardHtml,
        'quick_actions' => [
            ['label' => 'ðŸ“Š Detail Realisasi 2026', 'prompt' => 'Berapa capaian target RKAT tahun 2026?'],
            ['label' => 'ðŸ¢ Top 5 UPZ Terbanyak', 'prompt' => 'Tampilkan top 5 UPZ dengan setoran terbanyak'],
            ['label' => 'ðŸ’³ Arsip Rekening Koran', 'url' => url('pages/rekening_koran/index.php')],
            ['label' => 'âž• Catat Transaksi Hari Ini', 'prompt' => 'Catat transaksi zakat/infak sebesar ']
        ]
    ];
}

/**
 * Mesin Aturan Lokal Cerdas (Fallback saat Offline / Kuota Habis)
 */
function ai_fallback_local_engine(mysqli $conn, string $msg, int $userId = 0, string $userName = 'Amil BAZNAS'): array
{
    $lower = strtolower($msg);
    $tahunIni = (int)date('Y');
    $quickActions = [];
    $reply = '';

    // 0A. Briefing Eksekutif Pagi
    if (preg_match('/^(briefing|selamat pagi|pagi|laporan hari ini|briefing pagi|ringkasan eksekutif|update hari ini)\b/i', $lower)) {
        return ai_generate_morning_briefing($conn, $userName);
    }

    // 0. Diagnostik Kesehatan Sistem & Database (0 Token)
    if (preg_match('/(kesehatan\s*sistem|status\s*database|kondisi\s*server|dokter\s*sistem|cek\s*database|pindai\s*server|error\s*log|performa\s*server|cek\s*server)/i', $lower)) {
        require_once __DIR__ . '/system_doctor.php';
        $scan = doctor_scan_all();
        $reply = "ðŸ©º <strong>Hasil Diagnostik Dokter Sistem & Database:</strong><br><br>";
        $reply .= "â€¢ <strong>Skor Kesehatan:</strong> <strong style='color:{$scan['status_color']};'>{$scan['score']}% ({$scan['status_text']})</strong><br>";
        $reply .= "â€¢ <strong>Database MySQL:</strong> {$scan['database']['table_count']} Tabel ({$scan['database']['total_data_size_formatted']} data, {$scan['database']['total_overhead_formatted']} sisa ruang)<br>";
        $reply .= "â€¢ <strong>Server Hosting:</strong> PHP {$scan['server']['php_version']}, Memory {$scan['server']['memory_limit']}, Disk Sisa {$scan['server']['disk_free_formatted']}<br>";
        if ($scan['logs']['found']) {
            $reply .= "â€¢ <strong>Log Error Server:</strong> {$scan['logs']['fatal_count']} Fatal, {$scan['logs']['warning_count']} Warning<br>";
        }
        if (!empty($scan['issues'])) {
            $reply .= "<br>âš ï¸ <strong>" . count($scan['issues']) . " Rekomendasi Terdeteksi:</strong><br>";
            foreach ($scan['issues'] as $iss) {
                $reply .= "&bull; [" . strtoupper($iss['type']) . "] " . htmlspecialchars($iss['title']) . "<br>";
            }
        } else {
            $reply .= "<br>âœ… <em>Alhamdulillah, seluruh komponen sistem dan basis data dalam kondisi prima.</em><br>";
        }

        $quickActions[] = ['label' => 'ðŸ©º Buka Dokter Sistem & DB', 'url' => url('pages/settings/diagnostics.php')];
        $quickActions[] = ['label' => 'ðŸ’¾ Backup & Restore', 'url' => url('pages/settings/backup.php')];
        return ['reply' => $reply, 'quick_actions' => $quickActions];
    }

    // 0b. Deteksi Perintah Hapus Transaksi (Local Rule Engine)
    if (preg_match('/(hapus|delete|buang)\s+(?:transaksi|setoran|data)?\s*(?:nomor|no|#)?\s*(\d+)/i', $lower, $mHapus)) {
        $idTrx = (int)$mHapus[2];
        $prop = ai_propose_delete_transaction($conn, $idTrx);
        return ['reply' => $prop['reply'], 'quick_actions' => $prop['quick_actions']];
    }

    // 0c. Deteksi Perintah Edit / Koreksi Transaksi (Local Rule Engine)
    if (preg_match('/(ubah|edit|koreksi|ganti)\s+(?:transaksi|setoran|data)?\s*(?:nomor|no|#)?\s*(\d+)/i', $lower, $mEdit)) {
        $idTrx = (int)$mEdit[2];
        $changes = [];
        if (preg_match('/(?:nominal|jumlah|sebesar|menjadi|jadi)\s+(?:rp\.?\s*)?([0-9\.,]+(?:\s*(?:jt|juta|rb|ribu|k))?)/i', $lower, $mNom)) {
            $changes['nominal'] = $mNom[1];
        }
        if (preg_match('/(?:keterangan|memo|catatan)\s*(?:menjadi|jadi|:)?\s*[\'"]?([^\'"]+)[\'"]?$/i', $msg, $mKet)) {
            $changes['keterangan'] = trim($mKet[1]);
        }
        $prop = ai_propose_update_transaction($conn, $idTrx, $changes);
        return ['reply' => $prop['reply'], 'quick_actions' => $prop['quick_actions']];
    }

    // 0d-1. Deteksi Perintah Input Multi-Setoran Sekaligus (Batch Text Parser)
    $parsedBatch = ai_parse_batch_text($conn, $msg);
    if (!empty($parsedBatch) && count($parsedBatch) >= 2) {
        $prop = ai_propose_batch_create_transaction($conn, $parsedBatch);
        return ['reply' => $prop['reply'], 'quick_actions' => $prop['quick_actions']];
    }

    // 0d. Deteksi Perintah Input Transaksi Baru (Local Rule Engine - Draf Konfirmasi)
    if (preg_match('/(masukkan|input|catat|tambah|simpan|rekam|buat)\s+(?:transaksi|setoran|pembayaran|zakat|infak|sedekah|dana)/i', $lower) || preg_match('/^(?:transaksi|setoran)\s+baru/i', $lower)) {
        $txData = ai_parse_transaction_from_text($msg);
        if (!empty($txData['nominal']) && $txData['nominal'] > 0) {
            $prop = ai_propose_create_transaction($conn, $txData);
            return ['reply' => $prop['reply'], 'quick_actions' => $prop['quick_actions']];
        } else {
            $reply = "âš ï¸ <strong>Perintah Input Transaksi Terdeteksi</strong>, namun nominal setoran belum terbaca secara jelas.<br><br>";
            $reply .= "Silakan ketikkan format seperti:<br>";
            $reply .= "â€¢ <em>\"Catat transaksi zakat maal Rp 1.500.000 dari H. Mahmud via Bank NTB Syariah\"</em><br>";
            $reply .= "â€¢ <em>\"Masukkan transaksi infak 250.000 dari UPZ Dinas Kesehatan tunai\"</em>";
            $quickActions[] = ['label' => 'âž• Contoh Input Transaksi', 'prompt' => 'Catat transaksi zakat maal 1.000.000 dari Muzaki Fulan via Bank NTB Syariah'];
            return ['reply' => $reply, 'quick_actions' => $quickActions];
        }
    }

    // 1. Rekap RKAT & Capaian Tahunan
    if (preg_match('/(target|rkat|capaian|persen|realisasi)/i', $lower)) {
        $resTarget = $conn->query("SELECT nominal_target FROM target_pengumpulan_tahunan WHERE tahun = {$tahunIni} LIMIT 1");
        $target = $resTarget && ($rowT = $resTarget->fetch_assoc()) ? (float)$rowT['nominal_target'] : 0;

        $resTrx = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(jumlah_pembayaran), 0) as total FROM transaksi WHERE YEAR(tanggal_transaksi) = {$tahunIni}");
        $rowX = $resTrx->fetch_assoc();
        $total = (float)$rowX['total'];
        $cnt = (int)$rowX['cnt'];

        $persen = $target > 0 ? round(($total / $target) * 100, 2) : 0;

        $reply = "ðŸ“Š <strong>Laporan Capaian Target RKAT Tahun {$tahunIni}</strong><br><br>";
        $reply .= "â€¢ <strong>Target Tahunan:</strong> Rp " . number_format($target, 0, ',', '.') . "<br>";
        $reply .= "â€¢ <strong>Realisasi Pengumpulan:</strong> <strong class='text-success'>Rp " . number_format($total, 0, ',', '.') . "</strong> ({$cnt} transaksi)<br>";
        $reply .= "â€¢ <strong>Persentase Capaian:</strong> <span class='badge " . ($persen >= 100 ? "badge-success" : "badge-info") . "' style='font-size: 0.85rem;'>{$persen}%</span><br><br>";

        // Rincian per Kategori Dana
        $resKat = $conn->query("SELECT kd.nama_kategori, COALESCE(SUM(t.jumlah_pembayaran), 0) as subtotal 
                                FROM transaksi t 
                                LEFT JOIN kategori_dana kd ON t.id_kategori_dana = kd.id 
                                WHERE YEAR(t.tanggal_transaksi) = {$tahunIni} 
                                GROUP BY t.id_kategori_dana 
                                ORDER BY subtotal DESC");
        if ($resKat && $resKat->num_rows > 0) {
            $reply .= "<em>Rincian per Kategori Dana:</em><br>";
            while ($rk = $resKat->fetch_assoc()) {
                $katName = $rk['nama_kategori'] ?: 'Zakat / Infak';
                $sub = (float)$rk['subtotal'];
                $subP = $total > 0 ? round(($sub / $total) * 100, 1) : 0;
                $reply .= "â€¢ {$katName}: Rp " . number_format($sub, 0, ',', '.') . " ({$subP}%)<br>";
            }
        }

        $quickActions[] = ['label' => 'ðŸ“ˆ Buka Laporan Tahunan', 'url' => url('pages/laporan_tahunan/index.php')];
        $quickActions[] = ['label' => 'ðŸŽ¯ Pengaturan RKAT', 'url' => url('pages/rkat/index.php')];

        return ['reply' => $reply, 'quick_actions' => $quickActions];
    }

    // 2. Peringkat / Top UPZ
    if (preg_match('/(top\s*\d*\s*upz|upz\s*terbesar|upz\s*terbanyak|peringkat\s*upz|upz\s*tertinggi)/i', $lower)) {
        $q = "SELECT u.nama_upz as nama, COUNT(t.id) as frekuensi, SUM(t.jumlah_pembayaran) as total 
              FROM transaksi t 
              JOIN upz u ON t.id_upz = u.id 
              WHERE YEAR(t.tanggal_transaksi) = {$tahunIni} 
              GROUP BY t.id_upz 
              ORDER BY total DESC 
              LIMIT 5";
        $res = $conn->query($q);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }

        $reply = "ðŸ¢ <strong>Daftar 5 UPZ dengan Penyetoran Tertinggi (Tahun {$tahunIni})</strong>:";
        $reply .= ai_format_rank_table($rows, 'UPZ');
        $reply .= "<small class='text-muted'>Data dihitung otomatis dari riwayat transaksi yang tercatat di database.</small>";

        $quickActions[] = ['label' => 'ðŸ“‹ Master UPZ', 'url' => url('pages/upz/index.php')];
        $quickActions[] = ['label' => 'ðŸ’³ Riwayat UPZ', 'url' => url('pages/muzaki_upz_history/index.php')];

        return ['reply' => $reply, 'quick_actions' => $quickActions];
    }

    // 3. Peringkat / Top Muzaki
    if (preg_match('/(top\s*\d*\s*muzaki|muzaki\s*terbesar|muzaki\s*terbanyak|peringkat\s*muzaki|muzaki\s*tertinggi)/i', $lower)) {
        $q = "SELECT m.nama_muzaki as nama, COUNT(t.id) as frekuensi, SUM(t.jumlah_pembayaran) as total 
              FROM transaksi t 
              JOIN muzaki m ON t.id_muzaki = m.id 
              WHERE YEAR(t.tanggal_transaksi) = {$tahunIni} 
              GROUP BY t.id_muzaki 
              ORDER BY total DESC 
              LIMIT 5";
        $res = $conn->query($q);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }

        $reply = "ðŸ‘¥ <strong>Daftar 5 Muzaki dengan Penyetoran Tertinggi (Tahun {$tahunIni})</strong>:";
        $reply .= ai_format_rank_table($rows, 'Muzaki');

        $quickActions[] = ['label' => 'ðŸ‘¤ Master Muzaki', 'url' => url('pages/muzaki/index.php')];
        return ['reply' => $reply, 'quick_actions' => $quickActions];
    }

    // 4. Deteksi UPZ Pasif (Belum setor dalam rentang waktu)
    if (preg_match('/(upz\s*pasif|belum\s*setor|tidak\s*setor)/i', $lower)) {
        $q = "SELECT u.nama_upz, MAX(t.tanggal_transaksi) as tgl_terakhir 
              FROM upz u 
              LEFT JOIN transaksi t ON u.id = t.id_upz 
              GROUP BY u.id 
              HAVING tgl_terakhir IS NULL OR tgl_terakhir < DATE_SUB(CURDATE(), INTERVAL 60 DAY) 
              LIMIT 8";
        $res = $conn->query($q);
        $reply = "âš ï¸ <strong>Daftar UPZ yang Belum Menyetorkan ZIS dalam 60 Hari Terakhir:</strong><br><br>";
        if ($res && $res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $tgl = !empty($r['tgl_terakhir']) ? date('d/m/Y', strtotime($r['tgl_terakhir'])) : 'Belum pernah setor';
                $reply .= "â€¢ <strong>" . htmlspecialchars($r['nama_upz']) . "</strong> (Terakhir: {$tgl})<br>";
            }
            $reply .= "<br><small class='text-muted'>Disarankan bidang pengumpulan melakukan silaturahmi / jemput bola ke UPZ di atas.</small>";
        } else {
            $reply .= "Alhamdulillah, seluruh UPZ terdata aktif melakukan penyetoran.";
        }

        $quickActions[] = ['label' => 'ðŸ¢ Kelola UPZ', 'url' => url('pages/upz/index.php')];
        return ['reply' => $reply, 'quick_actions' => $quickActions];
    }

    // 5. Dokumen Program Bantuan / Mustahik
    if (preg_match('/(dokumen|gerobak|mahyani|bantuan|mustahik|penerima)/i', $lower)) {
        $resDok = $conn->query("SELECT dk.nama_kategori, COUNT(p.id) as total_penerima, COALESCE(SUM(p.nominal_total), 0) as total_dana 
                                FROM dokumen_kategori dk 
                                LEFT JOIN dokumen_penerima_data p ON dk.id = p.id_kategori_dokumen 
                                WHERE dk.status = 'aktif' 
                                GROUP BY dk.id");
        $reply = "ðŸ“¦ <strong>Ringkasan Dokumen Program Bantuan / Mustahik:</strong><br><br>";
        if ($resDok && $resDok->num_rows > 0) {
            while ($rd = $resDok->fetch_assoc()) {
                $reply .= "â€¢ <strong>" . htmlspecialchars($rd['nama_kategori']) . "</strong>: " . (int)$rd['total_penerima'] . " Penerima terdaftar";
                if ($rd['total_dana'] > 0) {
                    $reply .= " (Total Dana: <strong class='text-success'>Rp " . number_format($rd['total_dana'], 0, ',', '.') . "</strong>)";
                }
                $reply .= "<br>";
            }
        } else {
            $reply .= "Belum ada dokumen program bantuan yang aktif.";
        }

        $quickActions[] = ['label' => 'ðŸ“„ Buka Lembar Dokumen', 'url' => url('pages/dokumen_program/index.php')];
        $quickActions[] = ['label' => 'âš™ï¸ Kategori Dokumen', 'url' => url('pages/dokumen_kategori/index.php')];
        return ['reply' => $reply, 'quick_actions' => $quickActions];
    }

    // 6. Deteksi Pertanyaan ID Master UPZ / Muzaki (Pencarian Entitas Langsung & Fuzzy Typo-Tolerant)
    if (preg_match('/(id|berapa|kode|nomor|siapa|cari|master)\s*(upz|muzaki|dinas|badan|instansi|kecamatan|kantor|sekolah)?/i', $lower) || 
        preg_match('/(dikbud|dikbut|pendidikan|kesehatan|dinkes|dinkkes|satpol|satpolpp|inspektorat|bappeda|bapeda|bkpsdm|dispora|pupr|pertanian|perhubungan|dishub|kecamatan|puskesmas)/i', $lower)) {
        
        $cleanKw = trim(preg_replace('/\b(id|berapa|idnya|kode|nomor|upz|muzaki|siapa|cari|master|tampilkan|data|tolong|cek)\b/i', '', $lower));
        $cleanKw = trim(preg_replace('/\s+/', ' ', $cleanKw));

        // 1. Coba Fuzzy Matcher UPZ Terlebih Dahulu (Toleran Typo)
        $fuzzyMatch = ai_resolve_upz($conn, !empty($cleanKw) ? $cleanKw : $lower);
        $foundUpzRows = [];

        if ($fuzzyMatch) {
            $stmtFz = $conn->prepare("SELECT u.id, u.nama_upz, ku.nama_kategori as kategori, u.alamat, u.kontak FROM upz u LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id WHERE u.id = ?");
            $stmtFz->bind_param('i', $fuzzyMatch['id']);
            $stmtFz->execute();
            if ($rowFz = $stmtFz->get_result()->fetch_assoc()) {
                $foundUpzRows[] = $rowFz;
            }
            $stmtFz->close();
        }

        // 2. Jika belum, cari UPZ yang cocok dengan nama di teks pengguna
        if (empty($foundUpzRows)) {
            $resAllUpz = $conn->query("SELECT u.id, u.nama_upz, ku.nama_kategori as kategori, u.alamat, u.kontak FROM upz u LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id");
            if ($resAllUpz) {
                while ($u = $resAllUpz->fetch_assoc()) {
                    $rawUName = strtolower(explode('|', $u['nama_upz'])[0]);
                    $cleanU = trim(preg_replace('/^(dinas|badan|kecamatan|kantor|bagian|sekretariat)\s*/i', '', $rawUName));
                    if (!empty($cleanU) && strlen($cleanU) >= 3 && stripos($lower, $cleanU) !== false) {
                        $foundUpzRows[] = $u;
                    }
                }
            }
        }
        
        // 3. Fallback pencarian LIKE
        if (empty($foundUpzRows) && !empty($cleanKw) && strlen($cleanKw) >= 2) {
            $safeKw = "%" . $conn->real_escape_string($cleanKw) . "%";
            $resUpz = $conn->query("SELECT u.id, u.nama_upz, ku.nama_kategori as kategori, u.alamat, u.kontak FROM upz u LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id WHERE u.nama_upz LIKE '{$safeKw}' ORDER BY u.id ASC LIMIT 5");
            if ($resUpz) {
                while ($ur = $resUpz->fetch_assoc()) {
                    $foundUpzRows[] = $ur;
                }
            }
        }

        if (!empty($foundUpzRows)) {
            $top = $foundUpzRows[0];
            $katText = !empty($top['kategori']) ? $top['kategori'] : '-';
            $reply = "ðŸ¢ Ditemukan data Master UPZ untuk <strong>{$top['nama_upz']}</strong>:<br>";
            $reply .= "â€¢ <strong>ID Master: #" . $top['id'] . "</strong><br>";
            $reply .= "â€¢ <strong>Nama Resmi:</strong> `{$top['nama_upz']}`<br>";
            $reply .= "â€¢ <strong>Kategori:</strong> {$katText}<br><br>";
            if (count($foundUpzRows) > 1) {
                $reply .= "<em>Daftar UPZ yang cocok:</em><br>";
                $reply .= ai_format_smart_table($foundUpzRows, 'upz_table');
            }
            $quickActions[] = [
                'label' => 'ðŸ’° Catat Transaksi UPZ Ini',
                'prompt' => "Catat transaksi zakat Rp 5.000.000 dari {$top['nama_upz']} via Bank NTB Syariah"
            ];
            $quickActions[] = ['label' => 'ðŸ“‹ Master Data UPZ', 'url' => url('pages/upz/index.php')];
            return ['reply' => $reply, 'quick_actions' => $quickActions];
        }
    }

    // 7. Deteksi Perintah Tambah / Catat Transaksi Baru di Local Engine
    if (preg_match('/(tambah|tambahkan|catat|masukkan|input|setor)\s*(transaksi|zis|zakat|infak|setoran)?/i', $lower)) {
        // Coba deteksi apakah ada nama UPZ
        $foundUpz = null;
        $resAllUpz = $conn->query("SELECT id, nama_upz FROM upz");
        if ($resAllUpz) {
            while ($uRow = $resAllUpz->fetch_assoc()) {
                $rawUName = strtolower(explode('|', $uRow['nama_upz'])[0]);
                $cleanU = trim(preg_replace('/^(dinas|badan|kecamatan|kantor|bagian|sekretariat)\s*/i', '', $rawUName));
                if (!empty($cleanU) && strlen($cleanU) >= 3 && stripos($lower, $cleanU) !== false) {
                    $foundUpz = $uRow;
                    break;
                }
            }
        }

        $targetName = $foundUpz ? $foundUpz['nama_upz'] : 'UPZ / Muzaki';
        $idUpzVal = $foundUpz ? (int)$foundUpz['id'] : null;

        // Coba ekstrak nominal jika ada
        $nomVal = 0;
        if (preg_match('/(?:rp\.?|sebesar|nominal)?\s*([\d\.,]+)\s*(?:juta|jt)?/i', $lower, $mNom)) {
            $nomClean = str_replace(['.', ','], '', $mNom[1]);
            $multiplier = (preg_match('/(juta|jt)/i', $lower)) ? 1000000 : 1;
            $nomVal = (float)$nomClean * $multiplier;
        }

        if ($nomVal > 0) {
            $draft = [
                'tanggal_transaksi' => date('Y-m-d'),
                'tipe_pembayar' => 'upz',
                'id_upz' => $idUpzVal,
                'nama_pembayar' => $targetName,
                'nominal' => $nomVal,
                'id_jenis_pembayaran' => (stripos($lower, 'infak') !== false) ? 7 : 6,
                'id_metode_pembayaran' => (stripos($lower, 'tunai') !== false) ? 5 : 4,
                'id_sumber_rekening' => (stripos($lower, 'infak') !== false) ? 5 : 1,
                'keterangan' => 'Setoran ZIS dari ' . $targetName
            ];
            $prop = ai_propose_create_transaction($conn, $draft);
            return ['reply' => $prop['reply'], 'quick_actions' => $prop['quick_actions']];
        } else {
            $reply = "<div class='alert alert-info mb-0' style='border:1px solid #b8b8b8; border-radius:4px; background:#f0fdf4; color:#166534;'><i class='fas fa-hand-holding-usd mr-2 text-success'></i>Siap, saya siap menyiapkan draf pencatatan transaksi untuk <strong>" . htmlspecialchars($targetName) . "</strong>" . ($idUpzVal ? " (ID: #{$idUpzVal})" : "") . ". Berapa nominal setoran dan jenis ZIS yang ingin dicatat?<br><small class='text-muted mt-1 d-block'>Contoh: Ketik <em>'Catat zakat Rp 5.000.000 via Bank NTB Syariah'</em></small></div>";
            $quickActions[] = ['label' => 'ðŸ’° Zakat 5 Juta', 'prompt' => "Catat transaksi zakat Rp 5.000.000 dari {$targetName} via Bank NTB Syariah"];
            $quickActions[] = ['label' => 'ðŸ’¸ Infak 2.5 Juta', 'prompt' => "Catat transaksi infak Rp 2.500.000 dari {$targetName} via Bank NTB Syariah"];
            $quickActions[] = ['label' => 'ðŸ“ Formulir Input Transaksi', 'url' => url('pages/transaksi/create.php')];
            return ['reply' => $reply, 'quick_actions' => $quickActions];
        }
    }

    // 8. Pencarian Transaksi Umum (Fallback Kata Kunci)
    // Ekstrak keyword pencarian
    $cleanKeyword = preg_replace('/(cari|transaksi|setoran|tolong|tampilkan|ada|data)/i', '', $lower);
    $cleanKeyword = trim($cleanKeyword);

    if (!empty($cleanKeyword) && strlen($cleanKeyword) >= 3) {
        $safeKw = "%" . $conn->real_escape_string($cleanKeyword) . "%";
        $sqlSearch = "SELECT t.id, t.tanggal_transaksi, t.jumlah_pembayaran, t.keterangan,
                             m.nama_muzaki, u.nama_upz, jp.nama_jenis, sr.nama_sumber
                      FROM transaksi t
                      LEFT JOIN muzaki m ON t.id_muzaki = m.id
                      LEFT JOIN upz u ON t.id_upz = u.id
                      LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
                      LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
                      WHERE m.nama_muzaki LIKE '{$safeKw}' 
                         OR u.nama_upz LIKE '{$safeKw}' 
                         OR t.keterangan LIKE '{$safeKw}' 
                         OR sr.nama_sumber LIKE '{$safeKw}' 
                         OR jp.nama_jenis LIKE '{$safeKw}'
                      ORDER BY t.tanggal_transaksi DESC, t.id DESC 
                      LIMIT 8";
        $resSearch = $conn->query($sqlSearch);
        $rows = [];
        if ($resSearch) {
            while ($rx = $resSearch->fetch_assoc()) {
                $rows[] = $rx;
            }
        }

        if (!empty($rows)) {
            $reply = "ðŸ” Ditemukan <strong>" . count($rows) . " transaksi</strong> dengan kata kunci <em>'" . htmlspecialchars($cleanKeyword) . "'</em>:";
            $reply .= ai_format_transaction_table($rows);
            $quickActions[] = ['label' => 'ðŸ“‹ Buka Halaman Transaksi', 'url' => url('pages/transaksi/index.php')];
            return ['reply' => $reply, 'quick_actions' => $quickActions];
        }
    }

    // Default Greeting & Menu Bantuan
    $reply = "Assalamuâ€™alaikum Warahmatullahi Wabarakatuh.<br><br>Saya <strong>Amil Virtual Intelijen BAZNAS Sumbawa</strong>. Saya dapat membantu Anda menelusuri data sistem secara instan:<br><br>";
    $reply .= "â€¢ <strong>Cari Transaksi:</strong> <em>\"Cari transaksi dari Bank NTB Syariah\"</em> atau <em>\"Transaksi di atas 5 juta\"</em>.<br>";
    $reply .= "â€¢ <strong>Rekapitulasi RKAT:</strong> <em>\"Berapa capaian target RKAT tahun 2026?\"</em>.<br>";
    $reply .= "â€¢ <strong>Peringkat UPZ & Muzaki:</strong> <em>\"Top 5 UPZ terbanyak tahun ini\"</em>.<br>";
    $reply .= "â€¢ <strong>Kepatuhan Setoran:</strong> <em>\"Daftar UPZ yang belum setor\"</em>.<br>";
    $reply .= "â€¢ <strong>Dokumen Program:</strong> <em>\"Rekap penerima bantuan gerobak usaha\"</em>.";

    $quickActions[] = ['label' => 'ðŸ“Š Capaian RKAT 2026', 'prompt' => 'Berapa capaian target RKAT tahun 2026?'];
    $quickActions[] = ['label' => 'ðŸ¢ Top 5 UPZ', 'prompt' => 'Tampilkan top 5 UPZ dengan setoran terbanyak'];
    $quickActions[] = ['label' => 'ðŸ” Cari Transaksi', 'prompt' => 'Tampilkan 5 transaksi terakhir'];
    $quickActions[] = ['label' => 'ðŸ“¦ Data Bantuan', 'prompt' => 'Rekap data penerima bantuan program'];

    return ['reply' => $reply, 'quick_actions' => $quickActions];
}

/**
 * Controller Utama untuk Menangani Pertanyaan Pengguna
 */
function ai_handle_chat(string $userMessage, mysqli $conn, int $userId = 0, array $userInfo = []): array
{
    $msg = trim($userMessage);
    if ($msg === '') {
        return [
            'status' => 'error',
            'reply' => 'Silakan ketikkan pertanyaan atau perintah pencarian data Anda.',
            'quick_actions' => []
        ];
    }

    $userName = !empty($userInfo['full_name']) ? $userInfo['full_name'] : 'Amil BAZNAS';
    $userRole = !empty($userInfo['role']) ? strtoupper($userInfo['role']) : 'AMIL';
    $today = date('Y-m-d');

    // 0. Deteksi Konfirmasi / Pembatalan Aksi Tertunda (Human-in-the-Loop Confirmation)
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    $lowerMsg = strtolower($msg);

    // 0A. Deteksi Permintaan Briefing Eksekutif Pagi
    if (preg_match('/^(briefing|selamat pagi|pagi|laporan hari ini|briefing pagi|ringkasan eksekutif|update hari ini)$/i', $lowerMsg) || $msg === 'ACTION: GET_BRIEFING') {
        $briefing = ai_generate_morning_briefing($conn, $userName);
        if ($userId > 0) {
            ai_save_user_history($conn, $userId, $msg, $briefing['reply'], $briefing['quick_actions'], 'ACTION: MORNING_BRIEFING');
        }
        return $briefing;
    }
    if (!empty($_SESSION['ai_pending_action']) && is_array($_SESSION['ai_pending_action'])) {
        $pending = $_SESSION['ai_pending_action'];
        $lowerMsg = strtolower($msg);

        // Afirmasi kata kunci: ya, simpan, lanjutkan, setuju, oke, eksekusi, ok, yes, perbarui, hapus, dll.
        $isAffirmative = preg_match('/^(ya|simpan|lanjutkan|setuju|oke|eksekusi|gas|ok|yes|benar|yup|betul|lanjut|lakukan|update|perbarui|hapus|jadi|lanjutkan simpan|ya simpan|ya perbarui|ya hapus|simpan transaksi|perbarui transaksi|hapus transaksi)\b/i', $lowerMsg) ||
                         preg_match('/\b(ya|setuju|lanjutkan|simpan|perbarui|hapus)\b/i', $lowerMsg);

        // Pembatalan kata kunci: batal, batalkan, cancel, jangan, tidak, nggak, batalin, stop, dll.
        $isCancel = preg_match('/^(batal|batalkan|cancel|jangan|tidak|nggak|batalin|no|stop|lewati)\b/i', $lowerMsg) ||
                    preg_match('/\b(batal|batalkan|jangan)\b/i', $lowerMsg);

        if ($isAffirmative && !$isCancel) {
            $pAction = $pending['action'] ?? '';
            if ($pAction === 'create' && !empty($pending['data'])) {
                $exec = ai_execute_create_transaction($conn, $pending['data'], $userId, $userName);
                $reply = "Siap, konfirmasi Anda diterima. Data berhasil disimpan ke sistem:<br>" . $exec['card_html'];
                $actions = $exec['quick_actions'] ?? [];
                unset($_SESSION['ai_pending_action']);
                if ($userId > 0) {
                    ai_save_user_history($conn, $userId, $msg, $reply, $actions, 'ACTION: CONFIRM_CREATE');
                }
                return ['status' => 'success', 'reply' => $reply, 'quick_actions' => $actions];
            } elseif ($pAction === 'update' && !empty($pending['id']) && !empty($pending['data'])) {
                $exec = ai_execute_update_transaction($conn, (int)$pending['id'], $pending['data'], $userId, $userName);
                $reply = "Siap, konfirmasi pembaruan diterima. Transaksi berhasil diperbarui:<br>" . $exec['card_html'];
                $actions = $exec['quick_actions'] ?? [];
                unset($_SESSION['ai_pending_action']);
                if ($userId > 0) {
                    ai_save_user_history($conn, $userId, $msg, $reply, $actions, 'ACTION: CONFIRM_UPDATE');
                }
                return ['status' => 'success', 'reply' => $reply, 'quick_actions' => $actions];
            } elseif ($pAction === 'delete' && !empty($pending['id'])) {
                $exec = ai_execute_delete_transaction($conn, (int)$pending['id'], $userId, $userName);
                $reply = "Siap, konfirmasi penghapusan diterima. Transaksi berhasil dihapus:<br>" . $exec['card_html'];
                $actions = $exec['quick_actions'] ?? [];
                unset($_SESSION['ai_pending_action']);
                if ($userId > 0) {
                    ai_save_user_history($conn, $userId, $msg, $reply, $actions, 'ACTION: CONFIRM_DELETE');
                }
                return ['status' => 'success', 'reply' => $reply, 'quick_actions' => $actions];
            } elseif ($pAction === 'batch_create' && (!empty($pending['batch_data']) || !empty($pending['data']))) {
                $batchList = !empty($pending['batch_data']) ? $pending['batch_data'] : $pending['data'];
                $exec = ai_execute_batch_create_transactions($conn, $batchList, $userId, $userName);
                $reply = "Siap, konfirmasi Anda diterima. Seluruh transaksi massal berhasil diproses dan disimpan ke sistem:<br>" . $exec['card_html'];
                $actions = $exec['quick_actions'] ?? [];
                unset($_SESSION['ai_pending_action']);
                if ($userId > 0) {
                    ai_save_user_history($conn, $userId, $msg, $reply, $actions, 'ACTION: CONFIRM_BATCH_CREATE');
                }
                return ['status' => 'success', 'reply' => $reply, 'quick_actions' => $actions];
            }
        } elseif ($isCancel) {
            $summary = $pending['summary'] ?? 'Aksi';
            unset($_SESSION['ai_pending_action']);
            $reply = "âŒ <strong>Draf aksi dibatalkan:</strong> {$summary}. Tidak ada data yang diubah atau dihapus dari basis data.";
            $actions = [
                ['label' => 'ðŸ“Š Capaian RKAT 2026', 'prompt' => 'Berapa capaian target RKAT tahun 2026?'],
                ['label' => 'ðŸ” Cari Transaksi', 'prompt' => 'Tampilkan 5 transaksi terakhir']
            ];
            if ($userId > 0) {
                ai_save_user_history($conn, $userId, $msg, $reply, $actions, 'ACTION: CANCEL_ACTION');
            }
            return ['status' => 'success', 'reply' => $reply, 'quick_actions' => $actions];
        }
    }

    // 0B. Deteksi Fitur Khusus AI Intelijen BAZNAS Sumbawa
    // 1) Deteksi Anomali & Audit Internal (AI Data Quality & Fraud Watchdog)
    if (preg_match('/(audit|anomali|fraud|watchdog|kualitas data|duplikat|cek transaksi ganda|transaksi ganda)/i', $lowerMsg) || $msg === 'ACTION: RUN_WATCHDOG') {
        $watchdog = ai_run_data_watchdog($conn, $userId);
        if ($userId > 0) {
            ai_save_user_history($conn, $userId, $msg, $watchdog['reply'], $watchdog['quick_actions'], 'ACTION: RUN_WATCHDOG');
        }
        return $watchdog;
    }

    // 2) Visualisasi Grafik Interaktif di Dalam Chat (Chart.js Integration)
    if (preg_match('/(grafik|chart|visualisasi|tren bulanan|diagram)/i', $lowerMsg)) {
        $chartResult = ai_generate_interactive_chart($conn, $msg);
        if ($userId > 0) {
            ai_save_user_history($conn, $userId, $msg, $chartResult['reply'], $chartResult['quick_actions'], 'ACTION: INTERACTIVE_CHART');
        }
        return $chartResult;
    }

    // 3) Generator Laporan Eksekutif PDF Otomatis (Dompdf AI Executive Summary)
    if (preg_match('/(laporan eksekutif|eksekutif pdf|download pdf|unduh pdf|cetak laporan pdf|ringkasan pdf)/i', $lowerMsg)) {
        $pdfYear = (int)date('Y');
        if (preg_match('/\b(20\d\d)\b/', $msg, $ym)) {
            $pdfYear = (int)$ym[1];
        }
        $pdfUrl = url("pages/ai_asisten/export_pdf.php?tahun={$pdfYear}");
        $reply = "<div class='ai-pdf-card ai-custom-card my-2 p-3' style='border:1.5px solid #0d9f4f; border-radius:6px;'>"
               . "<div class='d-flex justify-content-between align-items-center mb-2'>"
               . "<strong style='color:#0d9f4f; font-size:0.92rem;'><i class='fas fa-file-pdf text-danger mr-1'></i> Generator Laporan Eksekutif PDF Otomatis</strong>"
               . "<span class='badge' style='background:#f0f7f3; color:#0d9f4f; border:1px solid #b8b8b8;'>Siap Unduh</span>"
               . "</div>"
               . "<p class='mb-2 ai-card-desc' style='font-size:0.83rem;'>Dokumen resmi laporan eksekutif pengumpulan ZIS Tahun {$pdfYear} siap diunduh dalam format PDF. Dokumen memuat Kop Resmi BAZNAS Sumbawa, rekapitulasi realisasi RKAT, rincian per bank & jenis dana, top 5 UPZ, serta ringkasan audit mutu data lengkap dengan lembar tanda tangan pimpinan.</p>"
               . "<div class='d-flex flex-wrap' style='gap:6px;'>"
               . "<a href='{$pdfUrl}' target='_blank' class='btn btn-xs btn-outline-success font-weight-bold px-3 py-1' style='border:1.5px solid #0d9f4f; border-radius:4px; font-size:0.8rem;'><i class='fas fa-download mr-1'></i> Unduh Laporan Eksekutif PDF ({$pdfYear})</a>"
               . "</div>"
               . "</div>";
        $actions = [
            ['label' => "ðŸ“¥ Unduh PDF ({$pdfYear})", 'url' => $pdfUrl],
            ['label' => 'ðŸ›¡ï¸ Jalankan Audit Watchdog', 'prompt' => 'Jalankan deteksi anomali dan audit kualitas data'],
            ['label' => 'ðŸ“Š Grafik Tren ZIS', 'prompt' => 'Tampilkan grafik tren penerimaan ZIS tahun ini']
        ];
        if ($userId > 0) {
            ai_save_user_history($conn, $userId, $msg, $reply, $actions, 'ACTION: EXECUTIVE_PDF');
        }
        return ['status' => 'success', 'reply' => $reply, 'quick_actions' => $actions];
    }

    $config = ai_get_config();

    // Bangun konteks sistem
    $schemaContext = ai_build_database_schema_context($conn);

    // Ambil riwayat percakapan sebelumnya untuk user ini (Multi-turn Context Memory â€” UPGRADED)
    $historyTurns = [];
    if ($userId > 0) {
        try {
            ai_ensure_history_table($conn);
            $recentHist = $conn->query("SELECT user_message, ai_reply, sql_used FROM ai_chat_history WHERE user_id = {$userId} ORDER BY id DESC LIMIT 10");
            if ($recentHist) {
                $rawTurns = [];
                while ($rh = $recentHist->fetch_assoc()) {
                    $rawTurns[] = $rh;
                }
                $rawTurns = array_reverse($rawTurns);
                foreach ($rawTurns as $t) {
                    $uMsg = trim($t['user_message']);
                    // Abaikan pesan konfirmasi singkat dari memori percakapan
                    if (preg_match('/^(ya|simpan|lanjutkan|setuju|oke|batal|batalkan|ok|yes|gas|betul|yup|lanjut)$/i', $uMsg)) {
                        continue;
                    }
                    // Bersihkan HTML cards dari reply, pertahankan narasi teks
                    $cleanReply = preg_replace('/<div class="table-responsive[\s\S]*?<\/div>/i', '[Tabel Data Ditampilkan]', $t['ai_reply']);
                    $cleanReply = preg_replace('/<div class="ai-trx-card[\s\S]*?<\/div>/i', '[Transaksi Berhasil Dicatat]', $cleanReply);
                    $cleanReply = preg_replace('/<div class="ai-confirm-card[\s\S]*?<\/div>/i', '[Draf Konfirmasi Ditampilkan]', $cleanReply);
                    $cleanReply = preg_replace('/<div class="ai-delete-card[\s\S]*?<\/div>/i', '[Konfirmasi Hapus Ditampilkan]', $cleanReply);
                    $cleanReply = preg_replace('/<div class="p-3 my-2[\s\S]*?<\/div>/i', '[Metrik Ringkasan]', $cleanReply);
                    $cleanReply = strip_tags($cleanReply);
                    // Tambahkan konteks aksi yang dilakukan (CREATE/UPDATE/DELETE/SQL)
                    $actionTag = '';
                    $sqlUsed = $t['sql_used'] ?? '';
                    if (strpos($sqlUsed, 'CONFIRM_CREATE') !== false) $actionTag = '[Aksi: Transaksi dibuat]';
                    elseif (strpos($sqlUsed, 'CONFIRM_UPDATE') !== false) $actionTag = '[Aksi: Transaksi diperbarui]';
                    elseif (strpos($sqlUsed, 'CONFIRM_DELETE') !== false) $actionTag = '[Aksi: Transaksi dihapus]';
                    elseif (strpos($sqlUsed, 'PROPOSE_') !== false) $actionTag = '[Aksi: Draf konfirmasi ditampilkan]';

                    $summary = trim($actionTag . ' ' . trim($cleanReply));
                    $historyTurns[] = [
                        'user_message' => $uMsg,
                        'ai_summary' => substr($summary, 0, 500)
                    ];
                }
                // Ambil maksimal 5 pasang turn percakapan terakhir (dinaikkan dari 3)
                if (count($historyTurns) > 5) {
                    $historyTurns = array_slice($historyTurns, -5);
                }
            }
        } catch (Throwable $e) {
            error_log("[AI History Turns Error] " . $e->getMessage());
        }
    }

    $systemPrompt = <<<PROMPT
Anda adalah 'Amil Virtual Intelijen', asisten eksekutif AI resmi untuk SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa.
Pengguna yang sedang berbicara dengan Anda saat ini adalah: {$userName} (Peran: {$userRole}).
Tanggal server hari ini: {$today}.

PEDOMAN PENTING: MENCEGAH KELUAR DARI KONTEKS / MAKSUD PENGGUNA:
1. TOPIK BARU BERDIRI SENDIRI: Bila pengguna mengajukan pertanyaan atau perintah baru (misal sebelumnya membahas rekap tahunan lalu sekarang minta input setoran, atau sebaliknya), FOKUS PENUH pada instruksi baru tersebut. Jangan memaksa menghubungkan dengan topik percakapan sebelumnya.
2. PERTANYAAN MERUJUK (FOLLOW-UP): Hanya gunakan data dari percakapan sebelumnya bila pengguna menggunakan kata rujukan relatif (contoh: "berapa totalnya?", "siapa saja pembayarnya?", "yang tadi dari bank mana?", "tampilkan lagi").
3. NAMA UPZ DENGAN KODE REKENING (SANGAT PENTING):
   - Di BAZNAS Kabupaten Sumbawa, nama resmi UPZ dinas, badan, dan kecamatan hampir selalu memiliki kode di belakang namanya dipisahkan oleh tanda pipa '|' (contoh: "DINAS KESEHATAN| 1.02.0.00.0.00.02.0000", "DINAS DIKBUD | 1.01.2.22.0.00.01.0000", "KECAMATAN ALAS | 7.01.0.00.0.00.02.0000").
   - Jangan bingung dengan kode tersebut! Bila pengguna menyebut "Dinas Kesehatan" atau "Dinkes", langsung kenali itu adalah ID 4 ("DINAS KESEHATAN| 1.02.0.00.0.00.02.0000").
   - Bila pengguna menyebut "Dikbud", "P&K", atau "Dinas Pendidikan", kenali itu adalah ID 3 ("DINAS DIKBUD | 1.01.2.22.0.00.01.0000").
   - Bila pengguna menyebut "Kecamatan Alas", kenali itu adalah ID 33.
   - Bila pengguna menyebut "Satpol PP", kenali itu adalah ID 7.
   - Cantumkan "id_upz" yang tepat dalam "transaction_data" agar sistem langsung mencocokkan ke master data yang benar tanpa membuat data UPZ duplikat.

SISTEM MEMILIKI 4 MODE OPERASIONAL UTAMA:

MODE 1: BACA DATA (SELECT / QUERY)
Gunakan mode ini jika pengguna bertanya, mencari data, meminta rekapitulasi, capaian RKAT, peringkat UPZ/Muzaki, memeriksa mutasi rekening koran, atau bantuan program.
- Tetapkan "action": "select"
- Rumuskan "sql_query" HANYA menggunakan SELECT (Dilarang INSERT, UPDATE, DELETE, DROP, ALTER pada sql_query).
- "transaction_data": null, "target_id": null, "changes": null

MODE 2: TAMBAH TRANSAKSI DENGAN KONFIRMASI (PROPOSE CREATE)
Gunakan mode ini jika pengguna memerintahkan untuk mencatat, memasukkan, menginput, atau menyimpan transaksi baru (misal: "masukkan transaksi zakat mal 1.500.000 dari H. Mahmud via Bank NTB Syariah", "catat infak 250.000 dari UPZ Dinas Kesehatan tunai", "input setoran...").
- Tetapkan "action": "propose_create"
- Kosongkan "sql_query": ""
- Isi objek "transaction_data":
  {
    "tanggal_transaksi": "YYYY-MM-DD" (gunakan {$today} jika pengguna tidak menyebutkan tanggal spesifik),
    "tipe_pembayar": "muzaki" | "upz",
    "id_upz": integer ID UPZ jika cocok dari daftar master atau null,
    "id_muzaki": integer ID Muzaki jika cocok atau null,
    "nama_pembayar": "Nama Lengkap Pembayar atau Nama Resmi UPZ",
    "nominal": 1500000 (angka tanpa titik/simbol),
    "id_jenis_pembayaran": 6 (Zakat) | 7 (Infak) | 8 (DSKL) | 11 (CSR),
    "id_sub_jenis_pembayaran": id sub-jenis atau null,
    "id_metode_pembayaran": 4 (Transfer Bank) | 5 (Tunai),
    "id_sumber_rekening": id rekening bank/kas master,
    "keterangan": "Keterangan transaksi"
  }

MODE 3: EDIT / UBAH TRANSAKSI DENGAN KONFIRMASI (PROPOSE UPDATE)
Gunakan mode ini jika pengguna meminta mengubah/mengoreksi/mengedit transaksi yang sudah ada (misal: "ubah nominal transaksi #920 menjadi 2.000.000", "koreksi keterangan transaksi 915 menjadi Titipan Zakat UPZ", "ubah tanggal transaksi 910 menjadi 2026-03-10").
- Tetapkan "action": "propose_update"
- Isi "target_id": nomor ID transaksi (contoh: 920)
- Isi objek "changes": {"nominal": 2000000, "keterangan": "...", "tanggal_transaksi": "...", "id_sumber_rekening": ..., "id_metode_pembayaran": ...}

MODE 4: HAPUS TRANSAKSI DENGAN KONFIRMASI (PROPOSE DELETE)
Gunakan mode ini jika pengguna meminta menghapus transaksi (misal: "hapus transaksi nomor 925", "delete transaksi #925").
- Tetapkan "action": "propose_delete"
- Isi "target_id": nomor ID transaksi (contoh: 925)

MODE 5: TAMBAH MULTI-SETORAN SEKALIGUS (PROPOSE BATCH CREATE)
Gunakan mode ini jika pengguna memberikan beberapa transaksi sekaligus dalam satu pesan (misal: "Catat setoran: Dikbud 5jt zakat, Dinkes 3.5jt zakat, Kec Alas 1.5jt infak" atau daftar berbaris-baris).
- Tetapkan "action": "propose_batch_create"
- Kosongkan "sql_query": ""
- Isi array "batch_data":
  [
    {
      "tanggal_transaksi": "YYYY-MM-DD",
      "tipe_pembayar": "upz" | "muzaki",
      "id_upz": integer ID UPZ jika cocok dari daftar master atau null,
      "id_muzaki": integer ID Muzaki jika cocok atau null,
      "nama_pembayar": "Nama Lengkap Pembayar atau Nama Resmi UPZ",
      "nominal": 5000000,
      "id_jenis_pembayaran": 6 (Zakat) | 7 (Infak),
      "id_metode_pembayaran": 4 (Transfer) | 5 (Tunai),
      "id_sumber_rekening": id rekening bank/kas master,
      "keterangan": "Setoran ZIS via ..."
    }
  ]

ATURAN UTAMA MENGENAI TABEL & PEMBATASAN BARIS DATA (SANGAT PENTING):
1. JANGAN MEMBATASI KUERI DENGAN KLAUSA LIMIT KECIL (seperti LIMIT 10, 20, atau 30) saat pengguna meminta data transaksi, laporan, atau daftar setoran!
2. Pengguna meminta SELURUH data ditampilkan secara penuh tanpa pembatasan (jika ada 100 data atau lebih, tampilkan 100 baris atau lebih secara lengkap).
3. Hanya gunakan LIMIT bila pengguna secara eksplisit meminta batasan tertentu dalam pertanyaannya (contoh: "tampilkan 5 transaksi", "top 10 UPZ").
4. JANGAN PERNAH MENGAKHIRI kueri SQL dengan tanda titik koma (;).
5. Pada setiap permintaan penelusuran data transaksi, SELALU rumuskan kueri SQL SELECT yang valid pada "sql_query" agar tabel data transaksi interaktif otomatis muncul.

Konteks Sistem & Basis Data:
{$schemaContext}

Format Output JSON Wajib:
{
  "action": "select" | "propose_create" | "propose_batch_create" | "propose_update" | "propose_delete",
  "target_id": null | integer,
  "changes": null | { ... },
  "transaction_data": null | { ... },
  "batch_data": null | [ { ... } ],
  "sql_query": "SELECT ... (Kueri SQL SELECT jika action == select, atau kosong)",
  "narasi": "Penjelasan ramah, komprehensif, dan profesional dalam bahasa Indonesia mengenai tindakan yang dilakukan atau draf yang disiapkan",
  "tipe_tampilan": "transaksi_table | rekap_table | metric_card | text_only",
  "quick_actions": [
    {"label": "Teks Tombol", "url": "URL atau kosong", "prompt": "Prompt jika ada"}
  ]
}
PROMPT;

    $aiRawResponse = ai_call_gemini($systemPrompt, $msg, $config, $historyTurns);

    if (!empty($aiRawResponse)) {
        // Bersihkan blok kode jika ada
        // JSON Self-Healing Parser (UPGRADED)
        $cleanJson = trim($aiRawResponse);
        // 1. Hapus blok kode markdown
        if (strpos($cleanJson, '```') !== false) {
            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', $cleanJson);
            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
            $cleanJson = trim($cleanJson);
        }

        $parsed = json_decode($cleanJson, true);

        // Jika JSON decoding langsung gagal, perbaiki format umum
        if (!$parsed) {
            $firstBrace = strpos($cleanJson, '{');
            $lastBrace = strrpos($cleanJson, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $sub = substr($cleanJson, $firstBrace, $lastBrace - $firstBrace + 1);
                $parsed = json_decode($sub, true);
            }
            if (strlen($cleanJson) > 20 && !preg_match('/^[{\[]/', $cleanJson)) {
                // Response adalah teks biasa, bukan JSON â€” tampilkan sebagai narasi
                $textReply = strip_tags($cleanJson);
                if ($userId > 0) {
                    ai_save_user_history($conn, $userId, $msg, $textReply, [], 'GEMINI_TEXT_RESPONSE');
                }
                return [
                    'status' => 'success',
                    'reply' => $textReply,
                    'quick_actions' => []
                ];
            }
        }

        if ($parsed && is_array($parsed)) {
            $actionType = $parsed['action'] ?? (!empty($parsed['transaction_data']) ? 'propose_create' : 'select');
            $narasi = $parsed['narasi'] ?? '';
            $sql = trim($parsed['sql_query'] ?? '');
            $tipe = $parsed['tipe_tampilan'] ?? 'auto';
            $actions = $parsed['quick_actions'] ?? [];

            $finalReply = $narasi;
            $sqlUsed = null;

            // 1. Draf Tambah Transaksi (Propose Create)
            if (($actionType === 'propose_create' || $actionType === 'create_transaksi') && !empty($parsed['transaction_data']) && is_array($parsed['transaction_data'])) {
                $prop = ai_propose_create_transaction($conn, $parsed['transaction_data']);
                $finalReply = $prop['reply'];
                if (!empty($prop['quick_actions'])) {
                    $actions = $prop['quick_actions'];
                }
                $sqlUsed = 'ACTION: PROPOSE_CREATE';
            }
            // 1b. Draf Tambah Multi-Transaksi Massal (Propose Batch Create)
            elseif (($actionType === 'propose_batch_create' || $actionType === 'batch_create') && !empty($parsed['batch_data']) && is_array($parsed['batch_data'])) {
                $prop = ai_propose_batch_create_transaction($conn, $parsed['batch_data']);
                $finalReply = $prop['reply'];
                if (!empty($prop['quick_actions'])) {
                    $actions = $prop['quick_actions'];
                }
                $sqlUsed = 'ACTION: PROPOSE_BATCH_CREATE (' . count($parsed['batch_data']) . ' items)';
            }
            // 2. Draf Edit Transaksi (Propose Update)
            elseif ($actionType === 'propose_update' && !empty($parsed['target_id'])) {
                $targetId = (int)$parsed['target_id'];
                $changes = $parsed['changes'] ?? [];
                $prop = ai_propose_update_transaction($conn, $targetId, $changes);
                $finalReply = $prop['reply'];
                if (!empty($prop['quick_actions'])) {
                    $actions = $prop['quick_actions'];
                }
                $sqlUsed = 'ACTION: PROPOSE_UPDATE #' . $targetId;
            }
            // 3. Draf Hapus Transaksi (Propose Delete)
            elseif ($actionType === 'propose_delete' && !empty($parsed['target_id'])) {
                $targetId = (int)$parsed['target_id'];
                $prop = ai_propose_delete_transaction($conn, $targetId);
                $finalReply = $prop['reply'];
                if (!empty($prop['quick_actions'])) {
                    $actions = $prop['quick_actions'];
                }
                $sqlUsed = 'ACTION: PROPOSE_DELETE #' . $targetId;
            }
            // 4. Eksekusi Kueri Baca (SELECT)
            elseif (!empty($sql)) {
                // Jika pengguna tidak meminta pembatasan spesifik (misal tidak ada "top 5", "tampilkan 10"),
                // hapus klausa LIMIT yang dibuat otomatis oleh AI agar seluruh baris tampil utuh
                $execSql = $sql;
                if (!preg_match('/\b(top\s*\d+|\d+\s*(?:terbaik|terbanyak|terbesar|terakhir|transaksi)|hanya\s*\d+|limit\s*\d+)\b/i', $msg)) {
                    $execSql = preg_replace('/\bLIMIT\s+\d+(\s*,\s*\d+)?\b/i', '', $execSql);
                }

                $exec = ai_safe_execute_query($conn, $execSql);
                if ($exec['success'] && !empty($exec['rows'])) {
                    $rows = $exec['rows'];
                    $finalReply .= "<br>" . ai_format_smart_table($rows, $tipe);
                } else {
                    if (!$exec['success']) {
                        error_log("[AI SQL Notice] " . ($exec['error'] ?? 'Query failed') . " on " . $execSql);
                    }
                    // Self-healing: jika kueri AI gagal atau kosong saat user minta data transaksi,
                    // jalankan kueri cerdas transaksi tahun berjalan agar tabel tetap tampil
                    $curYear = (int)date('Y');
                    $smartFallbackSql = "SELECT t.id, t.tanggal_transaksi, 
                        COALESCE(u.nama_upz, m.nama_muzaki, 'Muzaki Umum') as pembayar, 
                        CASE WHEN t.id_upz IS NOT NULL THEN 'UPZ' ELSE 'Muzaki' END as tipe_pembayar, 
                        jp.nama_jenis, sr.nama_sumber, t.jumlah_pembayaran, t.keterangan 
                        FROM transaksi t 
                        LEFT JOIN upz u ON t.id_upz = u.id 
                        LEFT JOIN muzaki m ON t.id_muzaki = m.id 
                        LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id 
                        LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id 
                        WHERE YEAR(t.tanggal_transaksi) = {$curYear} 
                        ORDER BY t.tanggal_transaksi DESC, t.id DESC";
                    $execFallback = ai_safe_execute_query($conn, $smartFallbackSql);
                    if ($execFallback['success'] && !empty($execFallback['rows'])) {
                        $finalReply .= "<br>" . ai_format_smart_table($execFallback['rows'], 'transaksi_table');
                    }
                }
                $sqlUsed = $execSql;
            }
            // 4b. Jika sql kosong tetapi pengguna menanyakan/meminta data transaksi
            elseif (preg_match('/(transaksi|tampilkan|daftar|rekap|setoran|mutasi|semua data)/i', $msg)) {
                $curYear = (int)date('Y');
                $autoSql = "SELECT t.id, t.tanggal_transaksi, 
                    COALESCE(u.nama_upz, m.nama_muzaki, 'Muzaki Umum') as pembayar, 
                    CASE WHEN t.id_upz IS NOT NULL THEN 'UPZ' ELSE 'Muzaki' END as tipe_pembayar, 
                    jp.nama_jenis, sr.nama_sumber, t.jumlah_pembayaran, t.keterangan 
                    FROM transaksi t 
                    LEFT JOIN upz u ON t.id_upz = u.id 
                    LEFT JOIN muzaki m ON t.id_muzaki = m.id 
                    LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id 
                    LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id 
                    WHERE YEAR(t.tanggal_transaksi) = {$curYear} 
                    ORDER BY t.tanggal_transaksi DESC, t.id DESC";
                $execAuto = ai_safe_execute_query($conn, $autoSql);
                if ($execAuto['success'] && !empty($execAuto['rows'])) {
                    $finalReply .= "<br>" . ai_format_smart_table($execAuto['rows'], 'transaksi_table');
                    $sqlUsed = $autoSql;
                }
            }

            $finalReply = ai_format_markdown_to_html($finalReply);

            // Simpan ke riwayat database agar AI mengingat percakapan ini
            if ($userId > 0) {
                ai_save_user_history($conn, $userId, $msg, $finalReply, $actions, $sqlUsed);
            }

            return [
                'status' => 'success',
                'reply' => $finalReply,
                'quick_actions' => $actions
            ];
        }
    }

    // Jika Gemini API offline / tidak merespons JSON yang valid, gunakan Internal Local Engine
    $fallback = ai_fallback_local_engine($conn, $msg, $userId, $userName);
    $fallbackReply = ai_format_markdown_to_html($fallback['reply']);

    // Simpan juga percakapan fallback ke database
    if ($userId > 0) {
        ai_save_user_history($conn, $userId, $msg, $fallbackReply, $fallback['quick_actions'], 'ACTION: LOCAL_FALLBACK');
    }

    return [
        'status' => 'success',
        'reply' => $fallbackReply,
        'quick_actions' => $fallback['quick_actions']
    ];
}
