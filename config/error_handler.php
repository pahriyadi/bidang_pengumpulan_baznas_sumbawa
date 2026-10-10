<?php
/**
 * Custom Error and Exception Handler for SIM Pengumpulan ZIS
 */

if (!defined('DEBUG_MODE')) {
    define('DEBUG_MODE', false);
}

// Perlindungan Information Disclosure: Jangan tampilkan pesan galat sistem ke publik
if (!DEBUG_MODE) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function($exception) {
    if (ob_get_length()) ob_clean(); // Clean any previous output

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || 
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    $title = get_class($exception);
    $message = $exception->getMessage();
    
    if ($isAjax) {
        if (!headers_sent()) header('Content-Type: application/json');
        http_response_code(500);
        
        $response = [
            'success' => false,
            'message' => DEBUG_MODE ? $message : 'Maaf, terjadi kesalahan internal pada sistem.'
        ];
        
        if (DEBUG_MODE) {
            $response['error_type'] = $title;
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
        }
        
        echo json_encode($response);
        exit;
    }

    if (DEBUG_MODE) {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        
        display_pretty_error($title, $message, $file, $line, $trace);
    } else {
        // Log error
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
        
        $error_log = $log_dir . '/error.log';
        $log_msg = "[" . date('Y-m-d H:i:s') . "] " . get_class($exception) . ": " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
        error_log($log_msg, 3, $error_log);
        
        // Show generic error
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        echo "<div style='font-family:sans-serif; text-align:center; padding-top:100px;'>";
        echo "<h1>500 Internal Server Error</h1>";
        echo "<p>Maaf, terjadi kesalahan pada sistem. Silakan hubungi admin.</p>";
        echo "</div>";
    }
    exit;
});

function display_pretty_error($title, $message, $file, $line, $trace) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Sistem Error - BAZNAS</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; margin: 0; padding: 20px; }
            .error-container { max-width: 1000px; margin: 0 auto; background: #fff; border-left: 5px solid #dc3545; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
            .error-header { background: #dc3545; color: #fff; padding: 20px; font-size: 24px; font-weight: bold; }
            .error-body { padding: 30px; }
            .error-msg { font-size: 20px; color: #721c24; background: #f8d7da; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
            .error-meta { margin-bottom: 20px; font-size: 16px; }
            .error-meta b { color: #dc3545; }
            .error-trace { background: #272822; color: #f8f8f2; padding: 20px; border-radius: 4px; overflow-x: auto; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 14px; white-space: pre-wrap; }
            .label { font-weight: bold; text-transform: uppercase; font-size: 12px; color: #6c757d; margin-bottom: 5px; display: block; }
            .footer { text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-header">
                System Error Detected
            </div>
            <div class="error-body">
                <div class="label">Exception Type</div>
                <div class="error-msg"><?= htmlspecialchars($title) ?></div>
                
                <div class="label">Error Message</div>
                <p style="font-size: 18px;"><?= nl2br(htmlspecialchars($message)) ?></p>

                <div class="error-meta">
                    <div class="label">Location</div>
                    File: <b><?= htmlspecialchars($file) ?></b><br>
                    Line: <b><?= htmlspecialchars($line) ?></b>
                </div>

                <div class="label">Stack Trace</div>
                <div class="error-trace"><?= htmlspecialchars($trace) ?></div>
            </div>
        </div>
        <div class="footer">
            SIM Pengumpulan ZIS © <?= date('Y') ?> - BAZNAS
        </div>
    </body>
    </html>
    <?php
}

/**
 * Catat error teknis database ke server log, dan kembalikan pesan aman bagi pengguna.
 * Mencegah information disclosure nama tabel, kolom, atau constraint database.
 */
function safe_db_error(?string $rawError = '', string $defaultUserMessage = 'Terjadi kendala saat memproses data ke database.'): string
{
    if (!empty($rawError)) {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) @mkdir($log_dir, 0777, true);
        $log_file = $log_dir . '/error.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] [DB_ERROR] " . $rawError . " [URI: " . ($_SERVER['REQUEST_URI'] ?? '') . "]\n";
        @error_log($entry, 3, $log_file);
    }
    
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return 'Gagal: ' . htmlspecialchars($rawError ?? '');
    }
    
    if (stripos($rawError, 'duplicate entry') !== false) {
        return 'Data dengan informasi ini sudah terdaftar dalam sistem.';
    }
    if (stripos($rawError, 'foreign key constraint fails') !== false) {
        return 'Data tidak dapat diproses karena berelasi dengan data lain yang tidak valid.';
    }
    
    return $defaultUserMessage;
}
