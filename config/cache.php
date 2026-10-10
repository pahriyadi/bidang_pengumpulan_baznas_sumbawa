<?php
/**
 * Simple Page Caching System for SIM Pengumpulan BAZNAS Sumbawa.
 * Caches HTML outputs of GET requests per user to speed up load times.
 * Invalidate cache automatically on any POST request (write operations).
 */

function get_page_cache_dir(): string
{
    $dir = __DIR__ . '/../writable/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function clear_page_cache(): void
{
    $dir = get_page_cache_dir();
    $files = glob($dir . '/*.cache');
    if ($files) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}

// Invalidate all caches on write operations (POST, PUT, DELETE)
$reqMethod = $_SERVER['REQUEST_METHOD'] ?? '';
if ($reqMethod !== '' && $reqMethod !== 'GET') {
    clear_page_cache();
}

function start_page_cache(): void
{
    // Skip caching if:
    $reqMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($reqMethod !== 'GET') {
        return;
    }
    
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // List of pages to exclude from caching
    $exclusions = [
        'logout.php',
        'login.php',
        'print_bukti.php',
        'export.php',
        'backup.php',
        'restore.php',
        'dokumen_kategori',
        'dokumen_program',
        'cetak.php',
        'export_excel.php',
        'ai_asisten'
    ];
    
    foreach ($exclusions as $ex) {
        if (strpos($requestUri, $ex) !== false) {
            return;
        }
    }
    
    // Only cache HTML responses for authenticated users
    ensure_session_started();
    $currentUser = current_user();
    $userId = (int)($currentUser['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }
    
    // Cache duration: 120 seconds (2 minutes)
    $cacheDuration = 120;
    
    // Generate unique key based on URI + user ID + role
    $cacheKey = md5($requestUri . '_u_' . $userId . '_r_' . ($currentUser['role'] ?? ''));
    $cacheKey = preg_replace('/[^a-zA-Z0-9_]/', '', $cacheKey);
    $cacheFile = get_page_cache_dir() . '/' . $cacheKey . '.cache';
    
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheDuration)) {
        $html = file_get_contents($cacheFile);
        
        // Dynamically inject the fresh CSRF token so forms in cache don't fail validation
        $freshToken = csrf_token();
        $html = preg_replace('/(name="csrf_token"\s+value=")[^"]*(")/i', '${1}' . $freshToken . '${2}', $html);
        $html = preg_replace('/(name="csrf-token"\s+content=")[^"]*(")/i', '${1}' . $freshToken . '${2}', $html);
        
        // Premium subtle visual check
        $html .= "\n<!-- Cached page loaded in " . round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . " ms (Antigravity Cache) -->";
        
        echo $html;
        exit;
    }
    
    // Start output buffering
    ob_start();
}

function end_page_cache(): void
{
    $reqMethod = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($reqMethod !== 'GET') {
        return;
    }
    
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    $exclusions = [
        'logout.php',
        'login.php',
        'print_bukti.php',
        'export.php',
        'backup.php',
        'restore.php',
        'dokumen_kategori',
        'dokumen_program',
        'cetak.php',
        'export_excel.php'
    ];
    
    foreach ($exclusions as $ex) {
        if (strpos($requestUri, $ex) !== false) {
            return;
        }
    }
    
    ensure_session_started();
    $currentUser = current_user();
    $userId = (int)($currentUser['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }
    
    $cacheKey = md5($requestUri . '_u_' . $userId . '_r_' . ($currentUser['role'] ?? ''));
    $cacheKey = preg_replace('/[^a-zA-Z0-9_]/', '', $cacheKey);
    $cacheFile = get_page_cache_dir() . '/' . $cacheKey . '.cache';
    
    $html = ob_get_clean();
    if ($html) {
        // Only save to cache if it's a successful HTML document and response is OK (200)
        if (http_response_code() === 200 && strpos($html, '<html') !== false) {
            @file_put_contents($cacheFile, $html);
        }
        echo $html;
    }
}
