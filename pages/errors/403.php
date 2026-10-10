<?php
require_once __DIR__ . '/../../config/helpers.php';
$pageTitle = '403 - Akses Ditolak';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    <link rel="stylesheet" href="<?= asset('adminlte/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('adminlte/dist/css/adminlte.min.css') ?>">
</head>
<body class="hold-transition">
<div class="wrapper">
    <section class="content">
        <div class="error-page" style="margin-top: 100px;">
            <h2 class="headline text-danger">403</h2>
            <div class="error-content">
                <h3><i class="fas fa-exclamation-triangle text-danger"></i> Akses Ditolak</h3>
                <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href="<?= url('index.php') ?>" class="btn btn-primary">
                    <i class="fas fa-home mr-1"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>
</div>
</body>
</html>
