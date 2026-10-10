<?php
/**
 * AJAX Endpoint Asisten AI Intelijen
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 */

// Tandai request ini sebagai AJAX agar error handler bawaan sistem tidak mencetak template HTML error 500
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

// Pastikan notice atau warning non-fatal tidak diubah menjadi ErrorException oleh custom error handler
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/ai_engine.php';

// Wajib login sebagai amil / admin
if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'reply' => 'Sesi login Anda telah berakhir atau terputus. Silakan muat ulang (refresh) halaman ini untuk melanjutkan obrolan.',
        'quick_actions' => [
            ['label' => '🔑 Muat Ulang Halaman', 'url' => '']
        ]
    ]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'reply' => 'Hanya metode POST yang diizinkan.']);
    exit;
}

// 1. Dapatkan data user yang sedang login
$currentUser = current_user();
$userId = (int)($currentUser['id'] ?? 0);
$conn = db();

$action = trim($_POST['action'] ?? 'send_message');

// Aksi Mengambil Riwayat Chat User
if ($action === 'get_history') {
    try {
        $history = ai_get_user_history($conn, $userId, 20);
    } catch (Throwable $e) {
        $history = [];
    }
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'history' => $history
    ]);
    exit;
}

// Aksi Membersihkan Riwayat Chat User
if ($action === 'clear_history') {
    try {
        ai_clear_user_history($conn, $userId);
    } catch (Throwable $e) {
        error_log("[AI Clear History Error] " . $e->getMessage());
    }
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Riwayat obrolan Anda telah dibersihkan.'
    ]);
    exit;
}

// Aksi Pemindai Bukti Transfer & Kuitansi (Multimodal OCR)
if ($action === 'scan_slip') {
    $imageData = $_POST['image_data'] ?? '';
    $mimeType = $_POST['mime_type'] ?? 'image/jpeg';
    
    if (preg_match('/^data:([^;]+);base64,(.+)$/', $imageData, $matches)) {
        $mimeType = $matches[1];
        $imageData = $matches[2];
    }
    
    if (empty($imageData)) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-circle mr-2"></i>Gambar bukti transfer tidak terdeteksi atau format tidak valid.</div>',
            'quick_actions' => []
        ]);
        exit;
    }

    try {
        $resScan = ai_parse_transfer_slip($conn, $imageData, $mimeType);
        if ($userId > 0) {
            ai_save_user_history($conn, $userId, '📷 [Pindai Bukti Transfer / Kuitansi]', $resScan['reply'], $resScan['quick_actions'], 'ACTION: SCAN_SLIP');
        }
        if (ob_get_length()) ob_clean();
        echo json_encode($resScan);
        exit;
    } catch (Throwable $e) {
        error_log("[AI Slip Scan Error] " . $e->getMessage());
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-circle mr-2"></i>Gagal memindai bukti transfer: ' . htmlspecialchars($e->getMessage()) . '</div>',
            'quick_actions' => []
        ]);
        exit;
    }
}

// Aksi Deteksi Anomali & Audit Internal (AI Fraud & Data Quality Watchdog)
if ($action === 'run_watchdog') {
    try {
        $resWatch = ai_run_data_watchdog($conn, $userId);
        if ($userId > 0) {
            ai_save_user_history($conn, $userId, '🛡️ [Deteksi Anomali & Audit Mutu Data]', $resWatch['reply'], $resWatch['quick_actions'], 'ACTION: RUN_WATCHDOG');
        }
        if (ob_get_length()) ob_clean();
        echo json_encode($resWatch);
        exit;
    } catch (Throwable $e) {
        error_log("[AI Watchdog Error] " . $e->getMessage());
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-circle mr-2"></i>Gagal menjalankan audit watchdog: ' . htmlspecialchars($e->getMessage()) . '</div>',
            'quick_actions' => []
        ]);
        exit;
    }
}

// Aksi Konfirmasi Eksekusi Aksi Tertunda (Human-in-the-Loop One-Click Action)
if ($action === 'confirm_pending_action') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $pending = $_SESSION['ai_pending_action'] ?? null;
    if (!$pending || empty($pending['action'])) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-warning mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Tidak ada draf aksi yang sedang menunggu konfirmasi atau sesi telah kedaluwarsa.</div>',
            'quick_actions' => []
        ]);
        exit;
    }

    $userName = !empty($currentUser['full_name']) ? $currentUser['full_name'] : 'Amil BAZNAS';
    $pAction = $pending['action'];
    $reply = '';
    $actions = [];

    try {
        if ($pAction === 'create' && !empty($pending['data'])) {
            $exec = ai_execute_create_transaction($conn, $pending['data'], $userId, $userName);
            $reply = "Alhamdulillah, draf transaksi berhasil disimpan ke sistem:<br>" . $exec['card_html'];
            $actions = $exec['quick_actions'] ?? [];
            unset($_SESSION['ai_pending_action']);
            if ($userId > 0) {
                ai_save_user_history($conn, $userId, 'Konfirmasi simpan transaksi', $reply, $actions, 'ACTION: CONFIRM_CREATE');
            }
        } elseif ($pAction === 'update' && !empty($pending['id']) && !empty($pending['data'])) {
            $exec = ai_execute_update_transaction($conn, (int)$pending['id'], $pending['data'], $userId, $userName);
            $reply = "Alhamdulillah, data transaksi berhasil diperbarui:<br>" . $exec['card_html'];
            $actions = $exec['quick_actions'] ?? [];
            unset($_SESSION['ai_pending_action']);
            if ($userId > 0) {
                ai_save_user_history($conn, $userId, 'Konfirmasi perbarui transaksi #' . $pending['id'], $reply, $actions, 'ACTION: CONFIRM_UPDATE');
            }
        } elseif ($pAction === 'delete' && !empty($pending['id'])) {
            $exec = ai_execute_delete_transaction($conn, (int)$pending['id'], $userId, $userName);
            $reply = "Transaksi <strong>#TRX-{$pending['id']}</strong> telah berhasil dihapus dari sistem:<br>" . $exec['card_html'];
            $actions = $exec['quick_actions'] ?? [];
            unset($_SESSION['ai_pending_action']);
            if ($userId > 0) {
                ai_save_user_history($conn, $userId, 'Konfirmasi hapus transaksi #' . $pending['id'], $reply, $actions, 'ACTION: CONFIRM_DELETE');
            }
        } elseif ($pAction === 'batch_create' && (!empty($pending['batch_data']) || !empty($pending['data']))) {
            $batchList = !empty($pending['batch_data']) ? $pending['batch_data'] : $pending['data'];
            $exec = ai_execute_batch_create_transactions($conn, $batchList, $userId, $userName);
            $reply = "Alhamdulillah, seluruh transaksi massal berhasil dibukukan ke sistem:<br>" . $exec['card_html'];
            $actions = $exec['quick_actions'] ?? [];
            unset($_SESSION['ai_pending_action']);
            if ($userId > 0) {
                ai_save_user_history($conn, $userId, 'Konfirmasi simpan transaksi massal', $reply, $actions, 'ACTION: CONFIRM_BATCH_CREATE');
            }
        } else {
            unset($_SESSION['ai_pending_action']);
            $reply = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Parameter draf aksi tidak lengkap.</div>';
        }
    } catch (Throwable $e) {
        error_log("[AI Confirm Action Error] " . $e->getMessage());
        $reply = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Terjadi kendala saat mengeksekusi aksi: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'reply' => ai_format_markdown_to_html($reply),
        'quick_actions' => $actions
    ]);
    exit;
}

// Aksi Pembatalan Draf Tertunda (One-Click Cancel)
if ($action === 'cancel_pending_action') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $summary = $_SESSION['ai_pending_action']['summary'] ?? 'Draf aksi';
    unset($_SESSION['ai_pending_action']);

    $reply = "❌ <strong>{$summary} telah dibatalkan.</strong> Tidak ada data yang disimpan, diubah, atau dihapus dari basis data.";
    $actions = [
        ['label' => '📊 Capaian RKAT 2026', 'prompt' => 'Berapa capaian target RKAT tahun 2026?'],
        ['label' => '🔍 Cari Transaksi', 'prompt' => 'Tampilkan 5 transaksi terakhir']
    ];

    if ($userId > 0) {
        ai_save_user_history($conn, $userId, 'Batalkan draf aksi', $reply, $actions, 'ACTION: CANCEL_ACTION');
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'reply' => ai_format_markdown_to_html($reply),
        'quick_actions' => $actions
    ]);
    exit;
}

// Aksi Unggah dan Analisis PDF Rekening Koran (0 Token API)
if ($action === 'upload_pdf') {
    require_once __DIR__ . '/../../config/pdf_parser.php';

    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal mengunggah berkas PDF. Pastikan file valid dan tidak melebihi batas unggah server.</div>'
        ]);
        exit;
    }

    $file = $_FILES['pdf_file'];
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($ext !== 'pdf') {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-warning mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-info-circle mr-2"></i>Format tidak didukung. Mohon unggah dokumen rekening koran dalam format <strong>.pdf</strong>.</div>'
        ]);
        exit;
    }

    $uploadDir = __DIR__ . '/../../assets/uploads/rekening_koran/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $safeName = 'RK_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
    $targetPath = $uploadDir . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'reply' => '<div class="alert alert-danger mb-0" style="border:1px solid #b8b8b8; border-radius:4px;"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal memindahkan file ke direktori penyimpanan server.</div>'
        ]);
        exit;
    }

    $analysis = analyze_bank_statement_pdf($targetPath, $originalName);

    // Siapkan aksi cepat yang relevan
    $quickActions = [];
    if (!empty($analysis['success'])) {
        if (!empty($analysis['nama_sumber_rekening']) && !empty($analysis['bulan_nama'])) {
            $quickActions[] = [
                'label' => '🔍 Transaksi ' . $analysis['bulan_nama'],
                'prompt' => 'Tampilkan transaksi ' . $analysis['nama_sumber_rekening'] . ' bulan ' . $analysis['bulan_nama']
            ];
        }
        $quickActions[] = [
            'label' => '🎯 Capaian RKAT 2026',
            'prompt' => 'Berapa capaian target pengumpulan RKAT tahun 2026?'
        ];
        $quickActions[] = [
            'label' => '📑 Modul Rekening Koran',
            'url' => 'index.php?page=rekening_koran'
        ];
    } else {
        $quickActions[] = [
            'label' => '📑 Buka Rekening Koran',
            'url' => 'index.php?page=rekening_koran'
        ];
    }

    // Rekam ke riwayat obrolan pengguna agar tidak hilang
    $userMsg = '📄 [Unggah Rekening Koran PDF: ' . htmlspecialchars($originalName) . ']';
    ai_save_user_history($conn, $userId, $userMsg, $analysis['html_card'], $quickActions);

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'reply' => $analysis['html_card'],
        'quick_actions' => $quickActions,
        'data' => $analysis,
        'file_path' => 'assets/uploads/rekening_koran/' . $safeName,
        'original_name' => $originalName
    ]);
    exit;
}

// Aksi Menyimpan Hasil Analisis PDF ke Database Tabel rekening_koran
if ($action === 'save_rk_statement') {
    $idSumber = (int)($_POST['id_sumber_rekening'] ?? 0);
    $bulan = trim($_POST['bulan'] ?? '');
    $jumlahKredit = (float)($_POST['jumlah_kredit'] ?? 0);
    $filePath = trim($_POST['file_path'] ?? '');
    $originalName = trim($_POST['original_name'] ?? '');

    if ($idSumber <= 0 || empty($bulan) || $jumlahKredit <= 0) {
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'Parameter data rekening koran tidak lengkap untuk disimpan.'
        ]);
        exit;
    }

    // Cek apakah entri sudah ada
    $checkStmt = $conn->prepare("SELECT id FROM rekening_koran WHERE id_sumber_rekening = ? AND bulan = ? LIMIT 1");
    $checkStmt->bind_param('is', $idSumber, $bulan);
    $checkStmt->execute();
    $resCheck = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($resCheck) {
        $idRk = (int)$resCheck['id'];
        $updateStmt = $conn->prepare("UPDATE rekening_koran SET jumlah_kredit = ?, file_path = ?, nama_file_original = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->bind_param('dssi', $jumlahKredit, $filePath, $originalName, $idRk);
        $updateStmt->execute();
        $updateStmt->close();
        $actType = 'UPDATE_REKENING_KORAN_AI';
    } else {
        $insertStmt = $conn->prepare("INSERT INTO rekening_koran (id_sumber_rekening, bulan, jumlah_kredit, file_path, nama_file_original) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param('isdss', $idSumber, $bulan, $jumlahKredit, $filePath, $originalName);
        $insertStmt->execute();
        $idRk = $conn->insert_id;
        $insertStmt->close();
        $actType = 'CREATE_REKENING_KORAN_AI';
    }

    log_audit($actType, 'rekening_koran', $idRk, "Disimpan dari Copilot AI: Bulan $bulan, Kredit Rp " . number_format($jumlahKredit, 2));

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Data mutasi kredit rekening koran berhasil dicatat ke sistem!'
    ]);
    exit;
}

// 2. Rate Limiting untuk Pengiriman Pesan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$now = time();
$rateLimit = 25; // 25 pesan per menit
$sessionKey = 'ai_rate_limit';

if (!isset($_SESSION[$sessionKey]) || ($now - $_SESSION[$sessionKey]['start']) >= 60) {
    $_SESSION[$sessionKey] = ['count' => 1, 'start' => $now];
} else {
    $_SESSION[$sessionKey]['count']++;
    if ($_SESSION[$sessionKey]['count'] > $rateLimit) {
        echo json_encode([
            'status' => 'error',
            'reply' => 'Afwan, Anda mengirim pesan terlalu cepat. Mohon tunggu beberapa detik sebelum bertanya kembali.',
            'quick_actions' => []
        ]);
        exit;
    }
}

// 3. Ambil dan sanitasi pesan pengguna
$userMessage = trim($_POST['message'] ?? '');
if (empty($userMessage)) {
    echo json_encode([
        'status' => 'error',
        'reply' => 'Silakan ketikkan pertanyaan atau perintah pencarian data Anda.',
        'quick_actions' => []
    ]);
    exit;
}

// 4. Proses pesan via AI Engine dengan Multi-Turn Memory & Kebal Galat (Anti-Crash 500)
try {
    $response = ai_handle_chat($userMessage, $conn, $userId, $currentUser ?: []);
} catch (Throwable $e) {
    error_log("[AI Copilot Error 500 Prevented] " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    try {
        $userName = !empty($currentUser['full_name']) ? $currentUser['full_name'] : 'Amil BAZNAS';
        $fallback = ai_fallback_local_engine($conn, $userMessage, $userId, $userName);
        $response = [
            'status' => 'success',
            'reply' => ai_format_markdown_to_html($fallback['reply']),
            'quick_actions' => $fallback['quick_actions']
        ];
    } catch (Throwable $f) {
        $response = [
            'status' => 'success',
            'reply' => 'Terima kasih atas pertanyaannya. Sistem asisten virtual sedang menyelaraskan data transaksi Anda. Ada data rekap atau pencarian lain yang ingin Anda tampilkan?',
            'quick_actions' => [
                ['label' => '📊 Capaian RKAT 2026', 'prompt' => 'Berapa capaian target RKAT tahun 2026?'],
                ['label' => '🏢 Top 5 UPZ', 'prompt' => 'Tampilkan top 5 UPZ penyetoran terbesar'],
                ['label' => '🔍 Transaksi Bank NTB', 'prompt' => 'Tampilkan 5 transaksi terakhir dari Bank NTB Syariah']
            ]
        ];
    }
}

// 5. Bersihkan buffer jika ada output kotor sebelum mengirimkan JSON
if (ob_get_length()) {
    ob_clean();
}

echo json_encode($response);
exit;
