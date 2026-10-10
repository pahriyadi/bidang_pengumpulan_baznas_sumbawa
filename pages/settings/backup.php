<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

if (!has_role('admin')) {
    redirect_with_flash('index.php', 'Akses ditolak.', 'danger');
}

$activeMenu = 'settings';
$pageTitle = 'Backup & Restore';
$pageDescription = 'Manajemen pencadangan data database';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Backup</li>';
$conn = db();
$db_name = DB_NAME;

// Unduh dari riwayat: tautan memakai GET, bukan POST
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'download_file') {
    $file = $_GET['file'] ?? '';
    $path = __DIR__ . '/../../backups/' . basename($file);
    if (is_file($path) && str_ends_with(strtolower($path), '.sql')) {
        log_audit('DOWNLOAD_BACKUP_FILE', null, null, 'Downloaded: ' . basename($file));
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($path);
        exit;
    }
    redirect_with_flash('pages/settings/backup.php', 'File backup tidak ditemukan.', 'danger');
}

// --- ACTIONS ---
// Helper for SQL execution (robust)
function execute_sql_dump($sql, $conn) {
    $queries = [];
    $query = '';
    $in_string = false;
    $string_char = '';
    $escaped = false;
    
    $chars = str_split($sql);
    foreach ($chars as $c) {
        if ($escaped) { $query .= $c; $escaped = false; continue; }
        if ($c === '\\') { $query .= $c; $escaped = true; continue; }
        if (($c === "'" || $c === '"')) {
            if (!$in_string) { $in_string = true; $string_char = $c; }
            elseif ($c === $string_char) { $in_string = false; }
        }
        if ($c === ';' && !$in_string) {
            $queries[] = trim($query);
            $query = '';
        } else { $query .= $c; }
    }
    if (trim($query)) $queries[] = trim($query);

    $conn->begin_transaction();
    try {
        foreach ($queries as $q) {
            $q = trim($q);
            if ($q && !$conn->query($q)) {
                throw new Exception($conn->error . "\nQuery: " . substr($q, 0, 100) . "...");
            }
        }
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'backup') {
        $filename = 'manual_backup_' . $db_name . '_' . date('Ymd_His') . '.sql';
        
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) $tables[] = $row[0];
        
        $sqlDump = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
            $sqlDump .= "\n\n" . $row2[1] . ";\n\n";
            $res = $conn->query("SELECT * FROM $table");
            while ($row = $res->fetch_row()) {
                $sqlDump .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $res->field_count; $j++) {
                    if ($row[$j] === null) $sqlDump .= 'NULL';
                    else {
                        $val = addslashes($row[$j]);
                        $val = str_replace("\n", "\\n", $val);
                        $sqlDump .= '"' . $val . '"';
                    }
                    if ($j < ($res->field_count - 1)) $sqlDump .= ',';
                }
                $sqlDump .= ");\n";
            }
        }
        $sqlDump .= "\nSET FOREIGN_KEY_CHECKS=1;";
        
        // Save copy to server
        if (!is_dir(__DIR__ . '/../../backups')) mkdir(__DIR__ . '/../../backups');
        file_put_contents(__DIR__ . '/../../backups/' . $filename, $sqlDump);

        log_audit('BACKUP_DATABASE', null, null, "Generated backup file: $filename");
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $sqlDump;
        exit;
    }

    if ($action === 'restore') {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
            try {
                execute_sql_dump($sql, $conn);
                log_audit('RESTORE_DATABASE', null, null, "Restored database from uploaded file: " . $_FILES['sql_file']['name']);
                set_flash('global', 'Database berhasil dipulihkan.', 'success');
            } catch (Exception $e) {
                set_flash('global', 'Gagal memulihkan database: ' . $e->getMessage(), 'danger');
            }
        } else {
            set_flash('global', 'Unggah file gagal atau file kosong.', 'warning');
        }
        header('Location: ' . url('pages/settings/backup.php')); exit;
    }

    if ($action === 'restore_internal') {
        $file = $_POST['file'] ?? '';
        $path = __DIR__ . '/../../backups/' . basename($file);
        if (is_file($path)) {
            $sql = file_get_contents($path);
            try {
                execute_sql_dump($sql, $conn);
                log_audit('RESTORE_DATABASE', null, null, "Restored database from internal file: $file");
                set_flash('global', 'Database berhasil dipulihkan dari riwayat.', 'success');
            } catch (Exception $e) {
                set_flash('global', 'Gagal memulihkan database: ' . $e->getMessage(), 'danger');
            }
        } else {
            set_flash('global', 'File backup tidak ditemukan di server.', 'danger');
        }
        header('Location: ' . url('pages/settings/backup.php')); exit;
    }

    if ($action === 'delete_file') {
        $file = $_POST['file'] ?? '';
        $path = __DIR__ . '/../../backups/' . basename($file);
        if (is_file($path)) {
            unlink($path);
            log_audit('DELETE_BACKUP_FILE', null, null, "Deleted backup file: $file");
            set_flash('global', 'File backup berhasil dihapus.', 'success');
        }
        header('Location: ' . url('pages/settings/backup.php')); exit;
    }

    if ($action === 'optimize') {
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        $optimizedCount = 0;
        $failedCount = 0;
        foreach ($tables as $table) {
            $opt = $conn->query("OPTIMIZE TABLE `$table`");
            $anz = $conn->query("ANALYZE TABLE `$table`");
            if ($opt && $anz) {
                $optimizedCount++;
            } else {
                $failedCount++;
            }
        }
        log_audit('OPTIMIZE_DATABASE', null, null, "Optimized and analyzed $optimizedCount tables");
        if ($failedCount === 0) {
            set_flash('global', "Database berhasil dioptimalkan! Sebanyak $optimizedCount tabel telah dirampingkan dan indeks kueri diperbarui.", 'success');
        } else {
            set_flash('global', "Optimasi selesai dengan beberapa catatan: $optimizedCount tabel berhasil, $failedCount tabel gagal.", 'warning');
        }
        header('Location: ' . url('pages/settings/backup.php')); exit;
    }
}

// Get backup history
$backups = [];
$backup_dir = __DIR__ . '/../../backups';
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && $f !== '.htaccess' && strpos($f, '.sql') !== false) {
            $path = $backup_dir . '/' . $f;
            $backups[] = [
                'name' => $f,
                'size' => filesize($path),
                'date' => filemtime($path)
            ];
        }
    }
}
usort($backups, function($a, $b) { return $b['date'] <=> $a['date']; });

// Get database stats
$total_data = 0;
$total_index = 0;
$total_overhead = 0;
$total_rows = 0;
$tables_count = 0;

$statusResult = $conn->query("SHOW TABLE STATUS FROM `" . DB_NAME . "`");
if ($statusResult) {
    while ($row = $statusResult->fetch_assoc()) {
        $total_data += $row['Data_length'];
        $total_index += $row['Index_length'];
        $total_overhead += $row['Data_free'];
        $total_rows += $row['Rows'];
        $tables_count++;
    }
}
$db_total_size = $total_data + $total_index;

if (!function_exists('format_bytes')) {
    function format_bytes($bytes) {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline h-100">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-download mr-2"></i>Backup Database</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Gunakan fitur ini untuk mengunduh salinan database sistem saat ini. File backup berupa format <code>.sql</code> yang dapat disimpan sebagai cadangan.</p>
                <div class="alert alert-info py-2">
                    <small><i class="fas fa-info-circle mr-1"></i> Disarankan melakukan backup secara berkala, terutama sebelum melakukan pembaruan besar.</small>
                </div>
            </div>
            <div class="card-footer bg-white">
                <form method="POST">
                    <input type="hidden" name="action" value="backup">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-cloud-download-alt mr-2"></i>Unduh Backup (.sql)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-warning card-outline h-100">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-upload mr-2"></i>Restore Database</h3>
            </div>
            <div class="card-body">
                <p class="text-muted text-justify">Unggah file <code>.sql</code> yang sebelumnya diunduh untuk mengembalikan data sistem ke titik tersebut.</p>
                <div class="alert alert-warning py-2">
                    <small><i class="fas fa-exclamation-triangle mr-1"></i> <strong>PERINGATAN:</strong> Proses ini akan menghapus data saat ini dan menggantinya dengan data dari file backup!</small>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="restoreForm">
                    <input type="hidden" name="action" value="restore">
                    <div class="form-group">
                        <label>Pilih File SQL</label>
                        <input type="file" name="sql_file" class="form-control-file" accept=".sql" required>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white">
                <button type="button" class="btn btn-warning btn-block" onclick="confirmRestore()">
                    <i class="fas fa-sync-alt mr-2"></i>Mulai Pemulihan Data
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-success card-outline h-100">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-magic mr-2"></i>Optimasi Database</h3>
            </div>
            <div class="card-body">
                <p class="text-muted text-justify">Lakukan perawatan database berkala untuk merapikan tabel (reclaim unused space) dan memperbarui indeks statistik kueri agar performa sistem tetap cepat.</p>
                
                <div class="db-stats-box mb-3 p-3 border rounded bg-light" style="border-color: #dee2e6 !important;">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <small class="text-muted d-block">Ukuran Data</small>
                            <strong class="text-dark"><?= format_bytes($db_total_size) ?></strong>
                        </div>
                        <div class="col-6 mb-2">
                            <small class="text-muted d-block">Overhead (Sampah)</small>
                            <strong class="<?= $total_overhead > 0 ? 'text-danger' : 'text-success' ?>"><?= format_bytes($total_overhead) ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Total Baris</small>
                            <strong class="text-dark"><?= number_format($total_rows, 0, ',', '.') ?> baris</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Jumlah Tabel</small>
                            <strong class="text-dark"><?= $tables_count ?> tabel</strong>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success py-2">
                    <small><i class="fas fa-info-circle mr-1"></i> Aman dilakukan kapan saja secara berkala tanpa menghentikan atau mengganggu jalannya transaksi aktif.</small>
                </div>
            </div>
            <div class="card-footer bg-white">
                <form method="POST" id="optimizeForm">
                    <input type="hidden" name="action" value="optimize">
                    <button type="button" class="btn btn-success btn-block" onclick="confirmOptimize()">
                        <i class="fas fa-magic mr-2"></i>Optimalkan Database
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card card-info card-outline">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2"></i>Riwayat Backup di Server</h3>
            </div>
            <div class="card-body">
                <table id="historyTable" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Tanggal Backup</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($backups as $b): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><code><?= clean($b['name']) ?></code></td>
                            <td><?= format_bytes($b['size']) ?></td>
                            <td><?= date('d/m/Y H:i:s', $b['date']) ?></td>
                            <td class="text-center">
                                <a href="?action=download_file&file=<?= urlencode($b['name']) ?>" class="btn btn-info btn-xs" title="Unduh">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button class="btn btn-warning btn-xs" onclick="restoreInternal('<?= $b['name'] ?>')" title="Restore">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="btn btn-danger btn-xs" onclick="deleteBackup('<?= $b['name'] ?>')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="internalActionForm" method="POST">
    <input type="hidden" name="action" id="internalAction">
    <input type="hidden" name="file" id="internalFile">
</form>

<script>
$(function() {
    $('#historyTable').DataTable({
        "order": [[3, "desc"]]
    });
});

function confirmRestore() {
    Swal.fire({
        title: "Konfirmasi Pemulihan?",
        text: "Seluruh data saat ini akan ditimpa oleh data dari file backup. Pastikan Anda sudah membuat backup data terbaru sebelum melanjutkan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Ya, Restore Sekarang",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            $("#restoreForm").submit();
        }
    });
}

function restoreInternal(filename) {
    Swal.fire({
        title: "Restore dari Server?",
        text: "Gunakan file '" + filename + "' untuk memulihkan database? Data saat ini akan ditimpa!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Ya, Restore",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            $('#internalAction').val('restore_internal');
            $('#internalFile').val(filename);
            $('#internalActionForm').submit();
        }
    });
}

function deleteBackup(filename) {
    Swal.fire({
        title: "Hapus File Backup?",
        text: "File '" + filename + "' akan dihapus permanen dari server!",
        icon: "error",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Ya, Hapus",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            $('#internalAction').val('delete_file');
            $('#internalFile').val(filename);
            $('#internalActionForm').submit();
        }
    });
}

function confirmOptimize() {
    Swal.fire({
        title: "Optimalkan Database?",
        text: "Sistem akan membersihkan overhead memori, menata ulang indeks kueri, dan merampingkan penyimpanan MySQL.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#0d9f4f",
        confirmButtonText: "Ya, Mulai Optimasi",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Sedang Mengoptimalkan...",
                text: "Harap tunggu, proses ini memerlukan waktu beberapa detik.",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            $("#optimizeForm").submit();
        }
    });
}
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
