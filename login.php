<?php
require_once __DIR__ . '/config/helpers.php';
ensure_session_started();

$error = '';
if (($_GET['error'] ?? '') === 'kicked') {
    $error = 'Sesi Anda telah berakhir karena akun ini masuk dari perangkat atau browser lain.';
}

if (is_authenticated()) {
    header('Location: ' . url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $error = 'Sesi formulir telah kedaluwarsa. Silakan muat ulang halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Cek proteksi brute force (maksimal 5x gagal dalam 15 menit)
        $lockStatus = check_brute_force_lockout($username);
        if ($lockStatus['locked']) {
            $error = 'Terlalu banyak percobaan login gagal. Demi keamanan, silakan coba kembali dalam ' . $lockStatus['remaining_minutes'] . ' menit.';
        } elseif ($username === '' || $password === '') {
            $error = 'Silakan masukkan username dan password.';
        } else {
            $user = find_user_by_username($username);
            if (!$user || !password_verify($password, $user['password'])) {
                // Catat percobaan gagal dan tambahkan timing delay
                record_login_attempt($username, false);
                usleep(300000); // 300ms delay untuk memperlambat automated brute-force

                $newLock = check_brute_force_lockout($username);
                if ($newLock['locked']) {
                    $error = 'Terlalu banyak percobaan login gagal. Akses ditangguhkan selama ' . $newLock['remaining_minutes'] . ' menit.';
                } else {
                    $remaining = max(1, 5 - ($newLock['failed_count'] ?? 1));
                    $error = 'Username atau password salah. (Sisa kesempatan: ' . $remaining . 'x)';
                }
            } elseif (!$user['is_active']) {
                $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            } else {
                // Catat sukses & reset catatan gagal
                record_login_attempt($username, true);
                login_user($user);
                log_audit('LOGIN', 'users', $user['id'], 'User logged in successfully');
                header('Location: ' . url('index.php'));
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | SIM Pengumpulan ZIS</title>
    <link rel="icon" type="image/x-icon" href="<?= get_favicon_url() ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/dist/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
    <style>
        .login-page {
            background-color: #f4f6f9 !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            width: 400px;
        }
        .login-box .card {
            border: 1px solid #b8b8b8 !important;
            border-radius: 4px !important;
            box-shadow: none !important;
            background-color: #ffffff !important;
        }
        .login-box .card-header {
            background-color: #ffffff !important;
            border-bottom: 1px solid #b8b8b8 !important;
            padding: 25px 20px !important;
            text-align: center;
            border-top-left-radius: 4px !important;
            border-top-right-radius: 4px !important;
        }
        .login-box .card-header .h1 {
            color: #333333 !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
        }
        .login-box .card-header p {
            color: #666666 !important;
        }
        .login-box .card-body {
            padding: 25px 25px !important;
            background-color: #ffffff !important;
            border-bottom-left-radius: 4px !important;
            border-bottom-right-radius: 4px !important;
        }
        .login-box .form-control {
            border: 1px solid #b8b8b8 !important;
            border-radius: 4px !important;
            background-color: #ffffff !important;
            color: #333333 !important;
            box-shadow: none !important;
            padding: 10px 15px !important;
            height: auto !important;
        }
        .login-box .form-control:focus {
            border-color: #0d9f4f !important;
            box-shadow: 0 0 0 0.15rem rgba(13, 159, 79, 0.15) !important;
            background-color: #ffffff !important;
        }
        .login-box .btn-primary {
            background-color: #ffffff !important;
            border: 1px solid #0d9f4f !important;
            color: #0d9f4f !important;
            border-radius: 4px !important;
            padding: 10px 15px !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px;
            transition: all 0.15s ease !important;
            box-shadow: none !important;
            width: 100%;
        }
        .login-box .btn-primary:hover {
            background-color: #0d9f4f !important;
            color: #ffffff !important;
        }

        /* Dark Mode on Login Page */
        body.dark-mode.login-page {
            background-color: #14171a !important;
            color: #e5e7eb !important;
        }
        .dark-mode .login-box .card {
            background-color: #1f2328 !important;
            border-color: #374151 !important;
        }
        .dark-mode .login-box .card-header {
            background-color: #1f2328 !important;
            border-color: #374151 !important;
        }
        .dark-mode .login-box .card-header .h1 {
            color: #f3f4f6 !important;
        }
        .dark-mode .login-box .card-header p {
            color: #9ca3af !important;
        }
        .dark-mode .login-box .card-body {
            background-color: #1f2328 !important;
        }
        .dark-mode .login-box .form-control {
            background-color: #262b32 !important;
            border-color: #4b5563 !important;
            color: #ffffff !important;
        }
        .dark-mode .login-box .form-control:focus {
            background-color: #262b32 !important;
            border-color: #0d9f4f !important;
            color: #ffffff !important;
        }
        .dark-mode .login-box .btn-primary {
            background-color: #262b32 !important;
            color: #34d399 !important;
            border-color: #0d9f4f !important;
        }
        .dark-mode .login-box .btn-primary:hover {
            background-color: #0d9f4f !important;
            color: #ffffff !important;
        }
        .dark-mode #btn-theme-toggle {
            border-color: #4b5563 !important;
            color: #f3f4f6 !important;
            background-color: #1f2328 !important;
        }
    </style>
</head>
<body class="hold-transition login-page">
<script>
    if (document.documentElement.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }
</script>

<div style="position: fixed; top: 15px; right: 20px; z-index: 1000;">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-theme-toggle" onclick="toggleDarkMode()" title="Ganti Mode Gelap / Terang" style="border-radius: 50%; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-moon" id="theme-toggle-icon"></i>
    </button>
</div>

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
    // Fade out preloader saat seluruh resource halaman selesai dimuat
    window.addEventListener('load', function() {
        const preloader = document.getElementById('page-preloader');
        if (preloader) {
            preloader.classList.add('fade-out');
            setTimeout(function() {
                preloader.style.display = 'none';
            }, 300);
        }
    });

    // Tampilkan preloader saat form dikirim (mencegah double click / submit berulang)
    document.addEventListener('submit', function() {
        const preloader = document.getElementById('page-preloader');
        if (preloader) {
            preloader.style.display = 'flex';
            preloader.offsetHeight; // force reflow
            preloader.classList.remove('fade-out');
        }
    });
</script>

<div class="login-box">
    <div class="card">
        <div class="card-header text-center">
            <div class="mb-3">
                <img src="<?= get_favicon_url() ?>" alt="Logo BAZNAS" style="width: 60px; height: 60px; object-fit: contain;">
            </div>
            <a href="#" class="h1"><b>SIM</b> Pengumpulan ZIS</a>
            <p class="mb-0 text-sm font-weight-bold">BAZNAS KABUPATEN SUMBAWA</p>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Masuk ke Sistem Informasi Manajemen</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form action="" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <div class="form-group mb-3">
                    <label class="text-sm text-muted"><i class="fas fa-user mr-1"></i>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="form-group mb-4">
                    <label class="text-sm text-muted"><i class="fas fa-lock mr-1"></i>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt mr-2"></i>MASUK KE SISTEM
                </button>
                <p class="mt-4 mb-0 text-xs text-muted text-center">
                    &copy; <?= date('Y') ?> BAZNAS Kabupaten Sumbawa.
                    <br>Sistem Pengumpulan Zakat, Infak, dan Sedekah.
                </p>
            </form>
        </div>
    </div>
</div>
<script src="<?= asset('adminlte/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
function toggleDarkMode() {
    var isDark = document.body.classList.toggle('dark-mode');
    document.documentElement.classList.toggle('dark-mode', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeUI(isDark);
}
function updateThemeUI(isDark) {
    var icon = document.getElementById('theme-toggle-icon');
    if (icon) {
        icon.className = isDark ? 'fas fa-sun text-warning' : 'fas fa-moon';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
    if (isDark && !document.body.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }
    updateThemeUI(isDark);
});
</script>
</body>
</html>
