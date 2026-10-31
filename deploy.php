<?php
/**
 * =========================================================================
 * GITHUB WEBHOOK AUTO-DEPLOY SCRIPT - SIM PENGUMPULAN BAZNAS SUMBAWA
 * =========================================================================
 * Subdomain : https://pengumpulan.baznassumbawa.id
 * Endpoint  : https://pengumpulan.baznassumbawa.id/deploy.php
 * Path cPanel: /home/n1579664/public_html/pengumpulan.baznassumbawa.id
 * Fungsi    : Menarik update otomatis (git pull origin main) setiap kali
 *             ada git push ke branch main di GitHub.
 * =========================================================================
 */

date_default_timezone_set('Asia/Makassar');
header('Content-Type: application/json; charset=utf-8');

// Secret Token untuk validasi keamanan Webhook GitHub
$webhook_secret = 'BaznasPengumpulan2026_AutoDeploy_88!';

// 1. Ambil raw payload dan header signature dari GitHub
$raw_payload   = file_get_contents('php://input');
$signature_256 = isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) ? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] : '';
$github_event  = isset($_SERVER['HTTP_X_GITHUB_EVENT']) ? $_SERVER['HTTP_X_GITHUB_EVENT'] : '';

// Mendukung pemicu manual lewat browser dengan ?key=BaznasPengumpulan2026_AutoDeploy_88!
$manual_key = isset($_GET['key']) ? $_GET['key'] : '';
$is_valid_manual = ($manual_key !== '' && hash_equals($webhook_secret, $manual_key));

// 2. Validasi HMAC SHA-256 dari GitHub Webhook
$is_valid_webhook = false;
if (!empty($signature_256) && !empty($raw_payload)) {
    $expected_signature = 'sha256=' . hash_hmac('sha256', $raw_payload, $webhook_secret);
    $is_valid_webhook = hash_equals($expected_signature, $signature_256);
}

if (!$is_valid_webhook && !$is_valid_manual) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Akses ditolak: Signature GitHub Webhook tidak valid.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// 3. Jika event dari GitHub adalah 'ping' (saat pertama kali pasang webhook)
if ($github_event === 'ping') {
    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Pong! Webhook SIM Pengumpulan BAZNAS Sumbawa berhasil terhubung.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// 4. Pastikan hanya memproses jika push dilakukan ke branch 'main'
if ($is_valid_webhook && $github_event === 'push') {
    $payload_data = json_decode($raw_payload, true);
    $ref = isset($payload_data['ref']) ? $payload_data['ref'] : '';
    if ($ref !== '' && $ref !== 'refs/heads/main') {
        http_response_code(200);
        echo json_encode([
            'status'  => 'ignored',
            'message' => "Push ke branch '{$ref}' diabaikan (hanya memproses refs/heads/main)."
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

$repo_dir  = __DIR__;
$cache_dir = $repo_dir . '/writable/cache';
$log_dir   = $repo_dir . '/logs';
$flag_file = $cache_dir . '/.deploy_trigger';
$log_file  = $log_dir . '/deploy.log';

chdir($repo_dir);

// 5. Eksekusi git menggunakan proc_open / fallback shell
function run_git_command($cmd) {
    $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    $full_cmd = $cmd . ' 2>&1';

    if (function_exists('proc_open') && !in_array('proc_open', $disabled, true)) {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = @proc_open($cmd, $descriptors, $pipes, __DIR__);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
            return ['output' => trim($stdout . "\n" . $stderr), 'code' => $code, 'method' => 'proc_open'];
        }
    }

    if (function_exists('exec') && !in_array('exec', $disabled, true)) {
        $output = [];
        $return_var = 0;
        @exec($full_cmd, $output, $return_var);
        return ['output' => implode("\n", $output), 'code' => $return_var, 'method' => 'exec'];
    }

    if (function_exists('shell_exec') && !in_array('shell_exec', $disabled, true)) {
        $out = @shell_exec($full_cmd);
        return ['output' => trim((string)$out), 'code' => 0, 'method' => 'shell_exec'];
    }

    if (function_exists('system') && !in_array('system', $disabled, true)) {
        ob_start();
        $return_var = 0;
        @system($full_cmd, $return_var);
        $out = ob_get_clean();
        return ['output' => trim((string)$out), 'code' => $return_var, 'method' => 'system'];
    }

    if (function_exists('popen') && !in_array('popen', $disabled, true)) {
        $handle = @popen($full_cmd, 'r');
        if ($handle) {
            $out = '';
            while (!feof($handle)) {
                $out .= fread($handle, 2096);
            }
            pclose($handle);
            return ['output' => trim($out), 'code' => 0, 'method' => 'popen'];
        }
    }

    return ['output' => '', 'code' => -1, 'method' => 'cron_queue'];
}

$git_bin = 'git';
if (file_exists('/usr/local/cpanel/3rdparty/bin/git')) {
    $git_bin = '/usr/local/cpanel/3rdparty/bin/git';
} elseif (file_exists('/usr/bin/git')) {
    $git_bin = '/usr/bin/git';
}

$cmd = "{$git_bin} fetch origin main && {$git_bin} reset --hard origin/main && {$git_bin} pull origin main";
$run_result = run_git_command($cmd);

if ($run_result['method'] === 'cron_queue') {
    if (!is_dir($cache_dir)) {
        @mkdir($cache_dir, 0775, true);
    }
    $trigger_data = json_encode([
        'requested_at' => date('Y-m-d H:i:s') . ' WITA',
        'event'        => ($is_valid_manual ? 'manual_trigger' : ($github_event ?: 'webhook')),
        'ip'           => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'
    ], JSON_PRETTY_PRINT);

    @file_put_contents($flag_file, $trigger_data);

    http_response_code(200);
    echo json_encode([
        'status'       => 'queued',
        'timestamp'    => date('Y-m-d H:i:s') . ' WITA',
        'method'       => 'cron_queue',
        'message'      => 'Sinyal Auto-Deploy disimpan untuk cron queue.'
    ], JSON_PRETTY_PRINT);
    exit;
}

if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0775, true);
}
if (is_dir($log_dir) && is_writable($log_dir)) {
    $log_entry = sprintf(
        "[%s WITA] Event: %s | Method: %s | Code: %d\nOutput:\n%s\n%s\n",
        date('Y-m-d H:i:s'),
        ($is_valid_manual ? 'manual_trigger' : ($github_event ?: 'webhook')),
        $run_result['method'],
        $run_result['code'],
        $run_result['output'],
        str_repeat('-', 60)
    );
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

http_response_code($run_result['code'] === 0 ? 200 : 500);
echo json_encode([
    'status'    => ($run_result['code'] === 0 ? 'success' : 'error'),
    'timestamp' => date('Y-m-d H:i:s') . ' WITA',
    'method'    => $run_result['method'],
    'git_bin'   => $git_bin,
    'output'    => $run_result['output']
], JSON_PRETTY_PRINT);

