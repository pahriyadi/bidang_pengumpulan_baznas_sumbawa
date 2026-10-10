<?php
require_once __DIR__ . '/../config/helpers.php';

ensure_session_started();
$user = current_user();

require_once __DIR__ . '/../config/cache.php';
start_page_cache();

$pageTitle = $pageTitle ?? 'Pengumpulan ZIS';
$pageDescription = $pageDescription ?? 'Sistem Informasi Pengumpulan Zakat, Infak, dan Sedekah';

// Lazy Cron for Auto Backup at 19:00 WITA (with catch-up if missed)
$lastBackupFile = __DIR__ . '/../config/last_auto_backup.txt';
$lastBackupDate = is_file($lastBackupFile) ? trim(file_get_contents($lastBackupFile)) : '';
$today = date('Y-m-d');
$currentTime = date('H:i');

$shouldBackup = false;
if ($lastBackupDate !== $today) {
    if (empty($lastBackupDate)) {
        $shouldBackup = true;
    } else {
        $diffDays = (strtotime($today) - strtotime($lastBackupDate)) / (60 * 60 * 24);
        if ($diffDays > 1) {
            $shouldBackup = true;
        } elseif ($diffDays == 1 && $currentTime >= '19:00') {
            $shouldBackup = true;
        }
    }
}

if ($shouldBackup) {
    @include_once __DIR__ . '/../cron/backup_auto.php';
}

$conn = db();
// Auto-create reminders table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS `reminders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `due_date` date DEFAULT NULL,
        `is_completed` tinyint(1) NOT NULL DEFAULT 0,
        `type` varchar(20) NOT NULL DEFAULT 'reminder',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `fk_reminders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Check and add 'type' column to reminders table if it doesn't exist
$checkCol = $conn->query("SHOW COLUMNS FROM `reminders` LIKE 'type'");
if ($checkCol && $checkCol->num_rows === 0) {
    $conn->query("ALTER TABLE `reminders` ADD COLUMN `type` varchar(20) NOT NULL DEFAULT 'reminder'");
}

// Global handler for Reminders & Notes CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['create_reminder', 'toggle_reminder', 'delete_reminder'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $currentUser = current_user();
    $userId = (int)($currentUser['id'] ?? 0);

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sesi tidak valid']);
        exit;
    }

    if ($action === 'create_reminder') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $type = $_POST['type'] ?? 'reminder';

        if ($title === '') {
            echo json_encode(['success' => false, 'message' => 'Judul wajib diisi']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO reminders (user_id, title, description, due_date, type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $userId, $title, $description, $due_date, $type);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'toggle_reminder') {
        $id = (int)($_POST['id'] ?? 0);
        $is_completed = (int)($_POST['is_completed'] ?? 0);

        $stmt = $conn->prepare("UPDATE reminders SET is_completed = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('iii', $is_completed, $id, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete_reminder') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM reminders WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | SIM Pengumpulan ZIS</title>
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <link rel="icon" type="image/x-icon" href="<?= get_favicon_url() ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/datatables-colreorder/css/colReorder.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/select2/css/select2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/dist/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
    <script src="<?= asset('adminlte/plugins/jquery/jquery.min.js') ?>"></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script>
        // Anti-flicker Dark / Light Mode Detection
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed text-sm">
<script>
    if (document.documentElement.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }
</script>

<!-- Page Preloader (Paper White Minimalist Loading Screen) -->
<div id="page-preloader" class="page-preloader">
    <div class="preloader-spinner-wrapper">
        <div class="preloader-logo-container">
            <img src="<?= get_favicon_url() ?>" alt="Logo BAZNAS" class="preloader-logo-img">
            <div class="preloader-spinner"></div>
        </div>
        <div class="preloader-title">BAZNAS SUMBAWA</div>
        <div class="preloader-text">Memuat data...</div>
    </div>
</div>

<script>
    // Global helper untuk kontrol preloader
    window.showPreloader = function() {
        const preloader = document.getElementById('page-preloader');
        if (preloader) {
            preloader.style.display = 'flex';
            preloader.offsetHeight; // force reflow
            preloader.classList.remove('fade-out');
        }
    };

    window.hidePreloader = function() {
        const preloader = document.getElementById('page-preloader');
        if (preloader) {
            preloader.classList.add('fade-out');
            setTimeout(function() {
                preloader.style.display = 'none';
            }, 250);
        }
    };

    // Fade out preloader saat seluruh resource halaman selesai dimuat
    window.addEventListener('load', function() {
        window.hidePreloader();
    });

    // Sembunyikan preloader jika halaman dimuat dari cache browser (tombol Back/Forward browser)
    window.addEventListener('pageshow', function(event) {
        window.hidePreloader();
    });

    // Tampilkan preloader saat form dikirim (hanya untuk form standar, BUKAN modal / AJAX)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form) return;

        // Jangan tampilkan preloader jika:
        // 1. Submit event sudah di-prevent oleh AJAX (e.defaultPrevented)
        // 2. Form berada di dalam popup modal (.modal) seperti Tambah UPZ, Muzaki, dsb.
        // 3. Form ditandai no-preloader atau data-ajax
        // 4. Form membuka tab baru (_blank)
        if (
            e.defaultPrevented ||
            form.target === '_blank' ||
            form.classList.contains('no-preloader') ||
            form.hasAttribute('data-ajax') ||
            form.closest('.modal')
        ) {
            return;
        }

        window.showPreloader();

        // Safety timeout: jika dalam 6 detik halaman belum pindah (misal validasi browser tertunda), sembunyikan otomatis
        setTimeout(function() {
            window.hidePreloader();
        }, 6000);
    });

    // Tampilkan preloader saat berpindah halaman via link menu/tabel
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;
        
        const href = link.getAttribute('href');
        const target = link.getAttribute('target');
        
        if (!href || 
            href.startsWith('#') || 
            href.startsWith('javascript:') || 
            target === '_blank' || 
            link.hasAttribute('download') || 
            link.classList.contains('no-preloader') ||
            link.classList.contains('dropdown-toggle') ||
            link.closest('.modal')
        ) {
            return;
        }
        
        window.showPreloader();

        // Safety timeout
        setTimeout(function() {
            window.hidePreloader();
        }, 6000);
    });
</script>

<div class="wrapper">
    <?php include __DIR__ . '/topbar.php'; ?>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark"><?= htmlspecialchars($pageTitle) ?></h1>
                        <p class="text-muted mb-0"><?= htmlspecialchars($pageDescription) ?></p>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <?= $breadcrumbs ?? '<li class="breadcrumb-item active">Beranda</li>' ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <?php if ($flash = get_flash('global')): ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle mr-2"></i><?= clean($flash['message']) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
