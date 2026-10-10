<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/cache.php';

date_default_timezone_set('Asia/Makassar');

// Tentukan BASE_PATH
if (!defined('BASE_PATH')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    
    $parts = explode('/', trim($scriptName, '/'));
    $pagesIndex = array_search('pages', $parts);
    
    if ($pagesIndex !== false) {
        $baseParts = array_slice($parts, 0, $pagesIndex);
        $basePath = '/' . implode('/', $baseParts);
    } else {
        // Jika tidak ada folder 'pages', berarti ini adalah root atau file di root
        $basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
    }
    
    define('BASE_PATH', rtrim($basePath, '/'));
}

/** Hanya membuang sufiks .php (query string tetap). */
function strip_php_extension(string $path): string
{
    if ($path === '') {
        return '';
    }
    $query = '';
    $qpos = strpos($path, '?');
    if ($qpos !== false) {
        $query = substr($path, $qpos);
        $path = substr($path, 0, $qpos);
    }
    $path = str_replace('\\', '/', $path);
    if (strlen($path) >= 4 && strcasecmp(substr($path, -4), '.php') === 0) {
        $path = substr($path, 0, -4);
    }
    return $path . $query;
}

function base_url(string $path = ''): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    $path = strip_php_extension($path);
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function url(string $path = ''): string
{
    return base_url($path);
}
function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function set_flash(string $key, string $message, string $type = 'success'): void
{
    ensure_session_started();
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function get_flash(string $key): ?array
{
    ensure_session_started();
    if (!isset($_SESSION['flash'][$key]))
        return null;
    $flash = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $flash;
}

function generate_csrf_token(): string
{
    ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token(): string
{
    return generate_csrf_token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf_token(?string $token): bool
{
    ensure_session_started();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function redirect_with_flash(string $location, string $message, string $type = 'success'): void
{
    set_flash('global', $message, $type);
    if (preg_match('/^(?:[a-z][a-z0-9+\\-.]*:)?\/\//i', $location)) {
        header('Location: ' . $location);
        exit;
    }
    if (strpos($location, '/') === 0) {
        header('Location: ' . $location);
        exit;
    }
    header('Location: ' . url($location));
    exit;
}

function format_currency(float|int $amount): string
{
    return 'Rp ' . number_format((float) $amount, 0, ',', '.');
}

function clean($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function month_options(): array
{
    return [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
}

function month_name(int $month): string
{
    return month_options()[$month] ?? 'Tidak diketahui';
}

// ==================== DROPDOWN HELPERS ====================

function get_jenis_pembayaran_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_jenis FROM jenis_pembayaran ORDER BY nama_jenis ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_sub_jenis_pembayaran_list(int $jenis_id = 0): array
{
    if ($jenis_id > 0) {
        $stmt = db()->prepare('SELECT id, nama_sub_jenis FROM sub_jenis_pembayaran WHERE id_jenis_pembayaran = ? ORDER BY nama_sub_jenis ASC');
        $stmt->bind_param('i', $jenis_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
    $result = db()->query('SELECT id, nama_sub_jenis, id_jenis_pembayaran FROM sub_jenis_pembayaran ORDER BY nama_sub_jenis ASC');
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function get_kategori_dana_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_kategori FROM kategori_dana ORDER BY nama_kategori ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_metode_pembayaran_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_metode FROM metode_pembayaran ORDER BY nama_metode ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_status_muzaki_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_status FROM status_muzaki ORDER BY nama_status ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_sumber_rekening_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_sumber FROM sumber_rekening ORDER BY nama_sumber ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_kategori_upz_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_kategori FROM kategori_upz ORDER BY nama_kategori ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_upz_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_upz FROM upz ORDER BY nama_upz ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_muzaki_list(): array
{
    static $cache = null;
    if ($cache !== null)
        return $cache;
    $result = db()->query('SELECT id, nama_muzaki, no_ktp FROM muzaki ORDER BY nama_muzaki ASC');
    $cache = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    return $cache;
}

function get_website_settings(bool $reload = false): array
{
    static $cache = null;
    if ($cache !== null && !$reload)
        return $cache;

    try {
        $conn = db();
        $result = @$conn->query("SELECT setting_key, setting_value FROM website_settings");
        $cache = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (\Exception $e) {
        $cache = [];
    }
    return $cache;
}

function get_setting(string $key, string $default = '', bool $reload = false): string
{
    $settings = get_website_settings($reload);
    return $settings[$key] ?? $default;
}

function get_favicon_url(): string
{
    try {
        $faviconPath = get_setting('favicon_path');
        if (!empty($faviconPath)) {
            return base_url($faviconPath);
        }
    } catch (\Exception $e) {
        // ignore
    }
    return asset('adminlte/dist/img/favicon.ico');
}

/**
 * Log an action to the audit trail
 */
function log_audit(string $action, ?string $table = null, ?int $record_id = null, ?string $notes = null): bool
{
    $userId = current_user()['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $stmt = db()->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, notes, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ississ', $userId, $action, $table, $record_id, $notes, $ip);
    $res = $stmt->execute();
    $stmt->close();
    return $res;
}

/**
 * Global helper for DataTables Server-Side Processing.
 * Dynamically queries MySQL and outputs AJAX JSON data for any table.
 */
function datatable_handler(string $table, array $columns, array $options = []): void
{
    if (!isset($_GET['draw'])) {
        return;
    }
    
    header('Content-Type: application/json');
    $conn = db();
    
    $draw = (int)$_GET['draw'];
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 10);
    $searchValue = trim($_GET['search']['value'] ?? '');
    
    $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
    
    $orderColumn = $columns[$orderColumnIndex] ?? $columns[0];
    if (!in_array(strtolower($orderDir), ['asc', 'desc'])) {
        $orderDir = 'asc';
    }
    
    $select = $options['select'] ?? '*';
    $joins = $options['joins'] ?? '';
    $baseWhere = $options['where'] ?? '1=1';
    $searchColumns = $options['search_columns'] ?? array_values($columns);
    $rowFormatter = $options['row_formatter'] ?? null;
    
    // Build search condition
    $where = " WHERE (" . $baseWhere . ")";
    $searchParams = [];
    $searchTypes = "";
    
    if ($searchValue !== '' && !empty($searchColumns)) {
        $searchTerms = [];
        $searchWildcard = "%" . $searchValue . "%";
        foreach ($searchColumns as $col) {
            $searchTerms[] = "{$col} LIKE ?";
            $searchParams[] = $searchWildcard;
            $searchTypes .= "s";
        }
        $where .= " AND (" . implode(" OR ", $searchTerms) . ")";
    }
    
    // Count total records (without filter)
    $totalRecords = $conn->query("SELECT COUNT(*) FROM {$table}")->fetch_row()[0] ?? 0;
    
    // Count filtered records
    $filteredQuery = "SELECT COUNT(*) FROM {$table} {$joins} {$where}";
    if ($searchValue !== '' && !empty($searchParams)) {
        $stmt = $conn->prepare($filteredQuery);
        $stmt->bind_param($searchTypes, ...$searchParams);
        $stmt->execute();
        $totalRecordwithFilter = $stmt->get_result()->fetch_row()[0] ?? 0;
        $stmt->close();
    } else {
        $totalRecordwithFilter = $conn->query($filteredQuery)->fetch_row()[0] ?? 0;
    }
    
    // Fetch records
    $dataQuery = "SELECT {$select} FROM {$table} {$joins} {$where} ORDER BY {$orderColumn} {$orderDir} LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($dataQuery);
    
    if ($searchValue !== '' && !empty($searchParams)) {
        $types = $searchTypes . "ii";
        $bindParams = array_merge($searchParams, [$length, $start]);
        $stmt->bind_param($types, ...$bindParams);
    } else {
        $stmt->bind_param("ii", $length, $start);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    $no = $start + 1;
    while ($row = $result->fetch_assoc()) {
        if ($rowFormatter) {
            $formattedRow = $rowFormatter($row, $no++);
        } else {
            $formattedRow = [];
            foreach ($columns as $idx => $col) {
                $cleanKey = strpos($col, '.') !== false ? substr($col, strpos($col, '.') + 1) : $col;
                $formattedRow[] = $row[$cleanKey] ?? '';
            }
        }
        $data[] = $formattedRow;
    }
    
    $stmt->close();
    
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalRecordwithFilter,
        "data" => $data
    ]);
    exit;
}

/**
 * Parser aman id_kategori_dana agar selalu menghasilkan array integer
 * Mencegah TypeError / ErrorException saat data di database tersimpan sebagai integer tunggal atau JSON
 */
function parse_kategori_dana_ids($val): array
{
    if (empty($val)) return [];
    if (is_array($val)) return array_values(array_filter(array_map('intval', $val)));
    
    if (is_numeric($val)) {
        return [(int)$val];
    }
    
    $decoded = json_decode((string)$val, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('intval', $decoded)));
    }
    
    // Fallback jika berupa format koma "1,2" atau string kurung "[1,2]"
    $clean = trim((string)$val, " \t\n\r\0\x0B[]");
    if ($clean !== '') {
        $parts = explode(',', $clean);
        return array_values(array_filter(array_map('intval', $parts)));
    }
    
    return [];
}
