<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_auth();

$activeMenu = 'kinerja';
$pageTitle = 'Kinerja Pengumpulan';
$pageDescription = 'Target tahunan, realisasi On Balance Sheet, Off Balance Sheet, dan target per jenis pembayaran';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Kinerja Pengumpulan</li>';

$conn = db();
ensure_kinerja_tables($conn);

$tahun_pilih = (int)($_GET['tahun'] ?? date('Y'));
if ($tahun_pilih < 2000 || $tahun_pilih > 2100) {
    $tahun_pilih = (int)date('Y');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_target_tahunan') {
        $tahun = (int)($_POST['tahun'] ?? 0);
        $nominal_on = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_target'] ?? '0');
        $nominal_off = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_target_off_balance'] ?? '0');
        $ket_on = trim($_POST['keterangan'] ?? '');
        $ket_off = trim($_POST['keterangan_off_balance'] ?? '');

        if ($tahun < 2000 || $tahun > 2100) {
            echo json_encode(['success' => false, 'message' => 'Tahun tidak valid']);
            exit;
        }
        $stmt = $conn->prepare('
            INSERT INTO target_pengumpulan_tahunan (tahun, nominal_target, nominal_target_off_balance, keterangan, keterangan_off_balance)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nominal_target = VALUES(nominal_target),
                nominal_target_off_balance = VALUES(nominal_target_off_balance),
                keterangan = VALUES(keterangan),
                keterangan_off_balance = VALUES(keterangan_off_balance)
        ');
        $stmt->bind_param('iddss', $tahun, $nominal_on, $nominal_off, $ket_on, $ket_off);
        if ($stmt->execute()) {
            log_audit('SAVE_TARGET_TAHUNAN', 'target_pengumpulan_tahunan', $tahun, "Tahun $tahun: On Balance Rp $nominal_on, Off Balance Rp $nominal_off");
            echo json_encode(['success' => true, 'message' => 'Target tahunan On Balance & Off Balance berhasil disimpan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'save_target_jenis') {
        $tahun = (int)($_POST['tahun'] ?? 0);
        $id_jenis = (int)($_POST['id_jenis_pembayaran'] ?? 0);
        $nominal_on = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_target'] ?? '0');
        $nominal_off = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_target_off_balance'] ?? '0');
        $ket = trim($_POST['keterangan'] ?? '');
        if ($tahun < 2000 || $id_jenis <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }
        $stmt = $conn->prepare('
            INSERT INTO target_pengumpulan_per_jenis (tahun, id_jenis_pembayaran, nominal_target, nominal_target_off_balance, keterangan)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nominal_target = VALUES(nominal_target),
                nominal_target_off_balance = VALUES(nominal_target_off_balance),
                keterangan = VALUES(keterangan)
        ');
        $stmt->bind_param('iidds', $tahun, $id_jenis, $nominal_on, $nominal_off, $ket);
        if ($stmt->execute()) {
            log_audit('SAVE_TARGET_JENIS', 'target_pengumpulan_per_jenis', $id_jenis, "Tahun $tahun jenis $id_jenis: On Rp $nominal_on, Off Rp $nominal_off");
            echo json_encode(['success' => true, 'message' => 'Target per jenis berhasil disimpan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete_target_jenis') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM target_pengumpulan_per_jenis WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_TARGET_JENIS', 'target_pengumpulan_per_jenis', $id, 'Hapus target per jenis');
            echo json_encode(['success' => true, 'message' => 'Target per jenis dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get_target_jenis') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT * FROM target_pengumpulan_per_jenis WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => (bool)$row, 'data' => $row]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    exit;
}

$ringkasan = get_ringkasan_kinerja_tahunan($conn);
$jenisList = get_jenis_pembayaran_list();

$stmtTj = $conn->prepare('SELECT * FROM target_pengumpulan_per_jenis WHERE tahun = ? ORDER BY id_jenis_pembayaran ASC');
$stmtTj->bind_param('i', $tahun_pilih);
$stmtTj->execute();
$targetJenisRows = $stmtTj->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtTj->close();

$capaianJenisOn = get_capaian_per_tahun_jenis($conn, $tahun_pilih);
$capaianJenisOff = get_capaian_off_balance_per_tahun_jenis($conn, $tahun_pilih);

$stmtTt = $conn->prepare('SELECT * FROM target_pengumpulan_tahunan WHERE tahun = ? LIMIT 1');
$stmtTt->bind_param('i', $tahun_pilih);
$stmtTt->execute();
$targetTahunanRow = $stmtTt->get_result()->fetch_assoc();
$stmtTt->close();

// Capaian On Balance sesuai mode aktif
$pCap = kinerja_sql_and_transaksi_penerimaan($conn, 't');
$stmtC = $conn->prepare("SELECT COALESCE(SUM(t.jumlah_pembayaran), 0) AS t FROM transaksi t WHERE YEAR(t.tanggal_transaksi) = ? {$pCap}");
$stmtC->bind_param('i', $tahun_pilih);
$stmtC->execute();
$capaianTahunOn = (float)($stmtC->get_result()->fetch_assoc()['t'] ?? 0);
$stmtC->close();

$pPen = kinerja_sql_and_transaksi_penerimaan($conn, 't', 'penerimaan');
$stmtPen = $conn->prepare("SELECT COALESCE(SUM(t.jumlah_pembayaran), 0) AS t FROM transaksi t WHERE YEAR(t.tanggal_transaksi) = ? {$pPen}");
$stmtPen->bind_param('i', $tahun_pilih);
$stmtPen->execute();
$capaianTahunPenerimaan = (float)($stmtPen->get_result()->fetch_assoc()['t'] ?? 0);
$stmtPen->close();

$pPeng = kinerja_sql_and_transaksi_penerimaan($conn, 't', 'pengumpulan');
$stmtPeng = $conn->prepare("SELECT COALESCE(SUM(t.jumlah_pembayaran), 0) AS t FROM transaksi t WHERE YEAR(t.tanggal_transaksi) = ? {$pPeng}");
$stmtPeng->bind_param('i', $tahun_pilih);
$stmtPeng->execute();
$capaianTahunPengumpulan = (float)($stmtPeng->get_result()->fetch_assoc()['t'] ?? 0);
$stmtPeng->close();

// Capaian Off Balance Tahun Terpilih
$stmtOff = $conn->prepare("SELECT COALESCE(SUM(nominal), 0) AS t, COUNT(*) AS cnt FROM transaksi_off_balance WHERE YEAR(tanggal_transaksi) = ?");
$stmtOff->bind_param('i', $tahun_pilih);
$stmtOff->execute();
$offRow = $stmtOff->get_result()->fetch_assoc();
$stmtOff->close();
$capaianTahunOff = (float)($offRow['t'] ?? 0);
$countTahunOff = (int)($offRow['cnt'] ?? 0);

$targetTahunanOn = (float)($targetTahunanRow['nominal_target'] ?? 0);
$targetTahunanOff = (float)($targetTahunanRow['nominal_target_off_balance'] ?? 0);
$targetTahunanTotal = $targetTahunanOn + $targetTahunanOff;
$capaianTahunTotal = $capaianTahunOn + $capaianTahunOff;

$pctTahunanOn = $targetTahunanOn > 0 ? min(100, round(($capaianTahunOn / $targetTahunanOn) * 100, 1)) : null;
$pctTahunanOff = $targetTahunanOff > 0 ? min(100, round(($capaianTahunOff / $targetTahunanOff) * 100, 1)) : null;
$pctTahunanTotal = $targetTahunanTotal > 0 ? min(100, round(($capaianTahunTotal / $targetTahunanTotal) * 100, 1)) : null;

$activeDanaMode = get_dana_mode();

include __DIR__ . '/../../inc/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- Bar Filter Tahun & Aksi Cepat -->
<div class="row">
    <div class="col-md-12 mb-3">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-body py-2 px-3">
                <div class="row align-items-center">
                    <div class="col-lg-4 col-md-5 mb-2 mb-md-0">
                        <form method="get" class="form-inline">
                            <label class="mr-2 font-weight-bold"><i class="fas fa-calendar-alt mr-1 text-success"></i>Tahun Analisis:</label>
                            <select name="tahun" class="form-control form-control-sm" onchange="this.form.submit()" style="border: 1px solid #b8b8b8; min-width: 100px;">
                                <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $tahun_pilih === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                    <div class="col-lg-8 col-md-7 text-md-right">
                        <div class="d-inline-flex align-items-center flex-wrap" style="gap: 8px;">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="openModalTahunan('all')">
                                <i class="fas fa-bullseye mr-1"></i>Atur Target On &amp; Off Balance <?= $tahun_pilih ?>
                            </button>
                            <a href="<?= url('pages/off_balance/index.php?tahun=' . $tahun_pilih) ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-clipboard-list mr-1"></i>Kelola Data Off Balance Sheet (<?= $countTahunOff ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3 Kartu Utama: On Balance Sheet, Off Balance Sheet, & Total Gabungan -->
<div class="row">
    <!-- 1. On Balance Sheet -->
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0" style="font-size: 0.95rem;">
                    <i class="fas fa-university mr-2 text-success"></i>1. On Balance Sheet (<?= $tahun_pilih ?>)
                </h3>
                <button type="button" class="btn btn-outline-success btn-xs" onclick="openModalTahunan('on')" title="Atur Target On Balance">
                    <i class="fas fa-edit mr-1"></i>Target
                </button>
            </div>
            <div class="card-body py-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">Target On Balance:</span>
                        <strong style="font-size: 1rem;"><?= $targetTahunanOn > 0 ? format_currency($targetTahunanOn) : '<span class="text-muted">Belum diatur</span>' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="small text-muted">Realisasi (<?= $activeDanaMode === 'pengumpulan' ? 'Pengumpulan' : 'Penerimaan' ?>):</span>
                        <strong class="text-success" style="font-size: 1.15rem;"><?= format_currency($capaianTahunOn) ?></strong>
                    </div>
                    <div class="progress mb-2" style="height: 10px; border: 1px solid #b8b8b8;">
                        <div class="progress-bar bg-success" style="width: <?= $pctTahunanOn ?? 0 ?>%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>Capaian: <strong><?= $pctTahunanOn !== null ? $pctTahunanOn . '%' : '—' ?></strong></span>
                        <?php if ($targetTahunanOn > 0): ?>
                            <?php if ($capaianTahunOn >= $targetTahunanOn): ?>
                                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Tercapai (+<?= format_currency($capaianTahunOn - $targetTahunanOn) ?>)</span>
                            <?php else: ?>
                                <span class="text-danger">Sisa: <?= format_currency($targetTahunanOn - $capaianTahunOn) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 pt-2 small text-muted" style="border-top: 1px dashed #b8b8b8;">
                    <div class="d-flex justify-content-between">
                        <span>Total Penerimaan:</span>
                        <strong><?= format_currency($capaianTahunPenerimaan) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Pengumpulan Murni:</span>
                        <strong><?= format_currency($capaianTahunPengumpulan) ?></strong>
                    </div>
                    <?php if (!empty($targetTahunanRow['keterangan'])): ?>
                        <div class="mt-1 text-truncate" title="<?= clean($targetTahunanRow['keterangan']) ?>">
                            <i class="fas fa-info-circle mr-1"></i><?= clean($targetTahunanRow['keterangan']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Off Balance Sheet -->
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0" style="font-size: 0.95rem;">
                    <i class="fas fa-clipboard-list mr-2 text-primary"></i>2. Off Balance Sheet (<?= $tahun_pilih ?>)
                </h3>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-xs mr-1" onclick="openModalTahunan('off')" title="Atur Target Off Balance">
                        <i class="fas fa-edit mr-1"></i>Target
                    </button>
                    <a href="<?= url('pages/off_balance/index.php?tahun=' . $tahun_pilih) ?>" class="btn btn-outline-info btn-xs" title="Kelola Transaksi Off Balance">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>
            <div class="card-body py-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">Target Off Balance:</span>
                        <strong style="font-size: 1rem;"><?= $targetTahunanOff > 0 ? format_currency($targetTahunanOff) : '<span class="text-muted">Belum diatur</span>' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="small text-muted">Realisasi Off Balance:</span>
                        <strong class="text-primary" style="font-size: 1.15rem;"><?= format_currency($capaianTahunOff) ?></strong>
                    </div>
                    <div class="progress mb-2" style="height: 10px; border: 1px solid #b8b8b8;">
                        <div class="progress-bar bg-primary" style="width: <?= $pctTahunanOff ?? 0 ?>%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>Capaian: <strong><?= $pctTahunanOff !== null ? $pctTahunanOff . '%' : '—' ?></strong></span>
                        <?php if ($targetTahunanOff > 0): ?>
                            <?php if ($capaianTahunOff >= $targetTahunanOff): ?>
                                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Tercapai (+<?= format_currency($capaianTahunOff - $targetTahunanOff) ?>)</span>
                            <?php else: ?>
                                <span class="text-danger">Sisa: <?= format_currency($targetTahunanOff - $capaianTahunOff) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 pt-2 small text-muted" style="border-top: 1px dashed #b8b8b8;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Jumlah Laporan Tercatat:</span>
                        <a href="<?= url('pages/off_balance/index.php?tahun=' . $tahun_pilih) ?>" class="font-weight-bold text-primary">
                            <?= number_format($countTahunOff, 0, ',', '.') ?> Berita Acara / Laporan &rarr;
                        </a>
                    </div>
                    <?php if (!empty($targetTahunanRow['keterangan_off_balance'])): ?>
                        <div class="mt-1 text-truncate" title="<?= clean($targetTahunanRow['keterangan_off_balance']) ?>">
                            <i class="fas fa-info-circle mr-1"></i><?= clean($targetTahunanRow['keterangan_off_balance']) ?>
                        </div>
                    <?php else: ?>
                        <div class="mt-1">ZIS dikumpulkan &amp; disalurkan mandiri oleh UPZ/Masjid/Desa</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Total Gabungan (On + Off Balance Sheet) -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center py-2" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0" style="font-size: 0.95rem;">
                    <i class="fas fa-chart-pie mr-2 text-warning"></i>3. Total Kinerja RKAT (On + Off)
                </h3>
                <span class="badge badge-success">Gabungan <?= $tahun_pilih ?></span>
            </div>
            <div class="card-body py-3 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">Total Target RKAT:</span>
                        <strong style="font-size: 1rem;"><?= $targetTahunanTotal > 0 ? format_currency($targetTahunanTotal) : '<span class="text-muted">Belum diatur</span>' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-baseline mb-2">
                        <span class="small text-muted">Total Realisasi Gabungan:</span>
                        <strong class="text-success" style="font-size: 1.15rem;"><?= format_currency($capaianTahunTotal) ?></strong>
                    </div>
                    <div class="progress mb-2" style="height: 10px; border: 1px solid #b8b8b8;">
                        <div class="progress-bar bg-warning" style="width: <?= $pctTahunanTotal ?? 0 ?>%;"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>Capaian Total: <strong><?= $pctTahunanTotal !== null ? $pctTahunanTotal . '%' : '—' ?></strong></span>
                        <?php if ($targetTahunanTotal > 0): ?>
                            <?php if ($capaianTahunTotal >= $targetTahunanTotal): ?>
                                <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Target RKAT Tercapai!</span>
                            <?php else: ?>
                                <span class="text-danger">Sisa: <?= format_currency($targetTahunanTotal - $capaianTahunTotal) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 pt-2 small text-muted" style="border-top: 1px dashed #b8b8b8;">
                    <?php
                    $propOn = $capaianTahunTotal > 0 ? round(($capaianTahunOn / $capaianTahunTotal) * 100, 1) : 0;
                    $propOff = $capaianTahunTotal > 0 ? round(($capaianTahunOff / $capaianTahunTotal) * 100, 1) : 0;
                    ?>
                    <div class="d-flex justify-content-between">
                        <span>Kontribusi On Balance:</span>
                        <strong><?= $propOn ?>% (<?= format_currency($capaianTahunOn) ?>)</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Kontribusi Off Balance:</span>
                        <strong><?= $propOff ?>% (<?= format_currency($capaianTahunOff) ?>)</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Radial & Tabel Ringkasan Semua Tahun -->
<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-bullseye mr-2 text-success"></i>Persentase Capaian <?= $tahun_pilih ?></h3>
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <div id="radialTargetChart" style="width: 100%; min-height: 270px;"></div>
                <div class="text-center small text-muted mt-1">
                    Perbandingan persentase realisasi terhadap masing-masing target tahun <?= $tahun_pilih ?>.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-history mr-2 text-success"></i>Ringkasan Kinerja Semua Tahun (On Balance &amp; Off Balance)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table id="tblRingkasan" class="table table-hover table-sm mb-0" style="font-size: 0.84rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid #b8b8b8;">
                            <th>Tahun</th>
                            <th class="text-right" style="white-space: nowrap;">Target On Balance</th>
                            <th class="text-right" style="white-space: nowrap;">Capaian On Balance</th>
                            <th class="text-center" style="white-space: nowrap;">% On</th>
                            <th class="text-right" style="white-space: nowrap;">Target Off Balance</th>
                            <th class="text-right" style="white-space: nowrap;">Capaian Off Balance</th>
                            <th class="text-center" style="white-space: nowrap;">% Off</th>
                            <th class="text-right" style="white-space: nowrap;">Total Gabungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ringkasan as $r):
                            $t = (int)$r['tahun'];
                            $tgtOn = (float)$r['target_nominal'];
                            $capOn = (float)$r['capaian'];
                            $pctOn = $tgtOn > 0 ? min(100, round(($capOn / $tgtOn) * 100, 1)) : null;

                            $tgtOff = (float)($r['target_off_balance'] ?? 0);
                            $capOff = (float)($r['capaian_off_balance'] ?? 0);
                            $pctOff = $tgtOff > 0 ? min(100, round(($capOff / $tgtOff) * 100, 1)) : null;

                            $totCap = $capOn + $capOff;
                            $totTgt = $tgtOn + $tgtOff;
                            $pctTot = $totTgt > 0 ? min(100, round(($totCap / $totTgt) * 100, 1)) : null;
                        ?>
                        <tr <?= $t === $tahun_pilih ? 'style="background-color: rgba(13, 159, 79, 0.08);"' : '' ?>>
                            <td><a href="?tahun=<?= $t ?>" class="font-weight-bold"><?= $t ?></a></td>
                            <td class="text-right" style="white-space: nowrap;"><?= $tgtOn > 0 ? format_currency($tgtOn) : '—' ?></td>
                            <td class="text-right font-weight-bold text-success" style="white-space: nowrap;"><?= format_currency($capOn) ?></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <?php if ($pctOn !== null): ?>
                                    <span class="badge <?= $pctOn >= 100 ? 'badge-success' : 'badge-warning' ?>"><?= $pctOn ?>%</span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-right" style="white-space: nowrap;"><?= $tgtOff > 0 ? format_currency($tgtOff) : '—' ?></td>
                            <td class="text-right font-weight-bold text-primary" style="white-space: nowrap;"><?= format_currency($capOff) ?></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <?php if ($pctOff !== null): ?>
                                    <span class="badge <?= $pctOff >= 100 ? 'badge-success' : 'badge-info' ?>"><?= $pctOff ?>%</span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-right" style="white-space: nowrap;">
                                <strong><?= format_currency($totCap) ?></strong>
                                <?php if ($pctTot !== null): ?>
                                    <br><small class="text-muted">Target: <?= $pctTot ?>%</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ringkasan)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Grafik Batang Target vs Capaian per Jenis Pembayaran -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-chart-bar mr-2 text-success"></i>Target vs Capaian per Jenis Pembayaran — <?= $tahun_pilih ?></h3>
                <button type="button" class="btn btn-outline-success btn-sm mt-2 mt-md-0" onclick="openModalJenis()">
                    <i class="fas fa-plus mr-1"></i>Tambah / Ubah Target Jenis
                </button>
            </div>
            <div class="card-body">
                <div id="jenisBarChart" style="min-height: 330px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Rincian Target & Capaian per Jenis (On Balance & Off Balance) -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-list mr-2 text-success"></i>Rincian Target &amp; Realisasi per Jenis Pembayaran (On Balance vs Off Balance)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0" style="font-size: 0.88rem; border: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid #b8b8b8;">
                            <th>Jenis Pembayaran</th>
                            <th class="text-right">Target On Balance</th>
                            <th class="text-right">Realisasi On Balance</th>
                            <th class="text-center">% On</th>
                            <th class="text-right">Target Off Balance</th>
                            <th class="text-right">Realisasi Off Balance</th>
                            <th class="text-center">% Off</th>
                            <th class="text-right">Total Realisasi</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $targetByJenisId = [];
                        foreach ($targetJenisRows as $tj) {
                            $targetByJenisId[(int)$tj['id_jenis_pembayaran']] = $tj;
                        }
                        foreach ($jenisList as $j):
                            $jid = (int)$j['id'];
                            $tj = $targetByJenisId[$jid] ?? null;
                            $tgtOnJ = $tj ? (float)$tj['nominal_target'] : 0;
                            $tgtOffJ = $tj ? (float)($tj['nominal_target_off_balance'] ?? 0) : 0;
                            $capOnJ = (float)($capaianJenisOn[$jid] ?? 0);
                            $capOffJ = (float)($capaianJenisOff[$jid] ?? 0);
                            $pctOnJ = $tgtOnJ > 0 ? min(100, round(($capOnJ / $tgtOnJ) * 100, 1)) : null;
                            $pctOffJ = $tgtOffJ > 0 ? min(100, round(($capOffJ / $tgtOffJ) * 100, 1)) : null;
                        ?>
                        <tr>
                            <td class="font-weight-bold"><?= clean($j['nama_jenis']) ?></td>
                            <td class="text-right"><?= $tgtOnJ > 0 ? format_currency($tgtOnJ) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-right text-success font-weight-bold"><?= format_currency($capOnJ) ?></td>
                            <td class="text-center">
                                <?php if ($pctOnJ !== null): ?>
                                    <span class="badge <?= $pctOnJ >= 100 ? 'badge-success' : 'badge-warning' ?>"><?= $pctOnJ ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= $tgtOffJ > 0 ? format_currency($tgtOffJ) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-right text-primary font-weight-bold"><?= format_currency($capOffJ) ?></td>
                            <td class="text-center">
                                <?php if ($pctOffJ !== null): ?>
                                    <span class="badge <?= $pctOffJ >= 100 ? 'badge-success' : 'badge-info' ?>"><?= $pctOffJ ?>%</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-weight-bold"><?= format_currency($capOnJ + $capOffJ) ?></td>
                            <td class="text-center" style="white-space: nowrap;">
                                <?php if ($tj): ?>
                                    <button type="button" class="btn btn-outline-warning btn-xs" onclick="editJenis(<?= (int)$tj['id'] ?>)" title="Ubah Target"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-xs" onclick="hapusJenis(<?= (int)$tj['id'] ?>)" title="Hapus Target"><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-success btn-xs" onclick="tambahJenisPrefill(<?= $jid ?>)"><i class="fas fa-plus"></i> Target</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($jenisList)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Belum ada master jenis pembayaran.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small" style="border-top: 1px solid #b8b8b8;">
                <strong>On Balance Sheet</strong> dihitung otomatis dari tabel <code>transaksi</code> (sesuai mode aktif di topbar), sedangkan <strong>Off Balance Sheet</strong> dihitung dari tabel <code>transaksi_off_balance</code> (<a href="<?= url('pages/off_balance/index.php?tahun=' . $tahun_pilih) ?>">Kelola Off Balance Sheet</a>).
            </div>
        </div>
    </div>
</div>

<!-- Modal Target Tahunan (On Balance & Off Balance) -->
<div class="modal fade" id="modalTahunan" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-bullseye mr-2 text-success"></i>Pengaturan Target Tahunan (On Balance &amp; Off Balance Sheet) — <?= $tahun_pilih ?></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formTahunan">
                <div class="modal-body">
                    <input type="hidden" name="tahun" value="<?= $tahun_pilih ?>">
                    <div class="row">
                        <!-- Kolom On Balance -->
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="border: 1px solid #b8b8b8;">
                                <h6 class="font-weight-bold text-success mb-3">
                                    <i class="fas fa-university mr-1"></i>1. Target On Balance Sheet
                                </h6>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Nominal Target On Balance (Rp)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><strong>Rp</strong></span></div>
                                        <input type="text" class="form-control currency-input" name="nominal_target" id="nominal_target_tahun"
                                               value="<?= $targetTahunanOn > 0 ? number_format($targetTahunanOn, 0, ',', '.') : '' ?>" placeholder="0">
                                    </div>
                                    <small class="text-muted">Target pengumpulan dana ZIS yang masuk ke rekening/kas BAZNAS.</small>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold small">Keterangan On Balance (Opsional)</label>
                                    <textarea class="form-control" name="keterangan" rows="2" placeholder="Contoh: Target RKAT On Balance BAZNAS"><?= clean($targetTahunanRow['keterangan'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Off Balance -->
                        <div class="col-md-6 mt-3 mt-md-0">
                            <div class="p-3 rounded h-100" style="border: 1px solid #b8b8b8;">
                                <h6 class="font-weight-bold text-primary mb-3">
                                    <i class="fas fa-clipboard-list mr-1"></i>2. Target Off Balance Sheet
                                </h6>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Nominal Target Off Balance (Rp)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><strong>Rp</strong></span></div>
                                        <input type="text" class="form-control currency-input" name="nominal_target_off_balance" id="nominal_target_off_tahun"
                                               value="<?= $targetTahunanOff > 0 ? number_format($targetTahunanOff, 0, ',', '.') : '' ?>" placeholder="0">
                                    </div>
                                    <small class="text-muted">Target pencatatan ZIS yang dikumpulkan &amp; disalurkan mandiri oleh UPZ/Masjid/Desa.</small>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="font-weight-bold small">Keterangan Off Balance (Opsional)</label>
                                    <textarea class="form-control" name="keterangan_off_balance" rows="2" placeholder="Contoh: Target RKAT Off Balance Zakat Fitrah & Kurban Desa/Masjid"><?= clean($targetTahunanRow['keterangan_off_balance'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #b8b8b8;">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-outline-success"><i class="fas fa-save mr-1"></i>Simpan Target <?= $tahun_pilih ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Target per Jenis (On Balance & Off Balance) -->
<div class="modal fade" id="modalJenis" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold" id="modalJenisTitle"><i class="fas fa-layer-group mr-2 text-success"></i>Target per Jenis Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formJenis">
                <div class="modal-body">
                    <input type="hidden" name="id" id="tj_id" value="">
                    <input type="hidden" name="tahun" value="<?= $tahun_pilih ?>">
                    <div class="form-group">
                        <label class="font-weight-bold">Jenis Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-control" name="id_jenis_pembayaran" id="tj_jenis" required>
                            <option value="">Pilih Jenis</option>
                            <?php foreach ($jenisList as $j): ?>
                            <option value="<?= (int)$j['id'] ?>"><?= clean($j['nama_jenis']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-success"><i class="fas fa-university mr-1"></i>Nominal Target On Balance (Rp)</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="text" class="form-control currency-input" name="nominal_target" id="tj_nominal" placeholder="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-primary"><i class="fas fa-clipboard-list mr-1"></i>Nominal Target Off Balance (Rp)</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="text" class="form-control currency-input" name="nominal_target_off_balance" id="tj_nominal_off" placeholder="0">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Keterangan (opsional)</label>
                        <textarea class="form-control" name="keterangan" id="tj_ket" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #b8b8b8;">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-outline-success"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#tblRingkasan').DataTable({ order: [[0, 'desc']], pageLength: 10 });
    initCurrencyInput('.currency-input');

    // ApexCharts: Multi-Radial Bar - On Balance, Off Balance, Total
    var pctOn = <?= $pctTahunanOn !== null ? $pctTahunanOn : 0 ?>;
    var pctOff = <?= $pctTahunanOff !== null ? $pctTahunanOff : 0 ?>;
    var pctTot = <?= $pctTahunanTotal !== null ? $pctTahunanTotal : 0 ?>;

    var radialOptions = {
        series: [pctOn, pctOff, pctTot],
        chart: {
            height: 280,
            type: 'radialBar',
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#0d9f4f', '#2563eb', '#f59e0b'],
        plotOptions: {
            radialBar: {
                dataLabels: {
                    name: {
                        fontSize: '13px'
                    },
                    value: {
                        fontSize: '20px',
                        fontWeight: 'bold',
                        formatter: function (val) {
                            return val + "%";
                        }
                    },
                    total: {
                        show: true,
                        label: 'Total RKAT',
                        formatter: function () {
                            return pctTot + "%";
                        }
                    }
                }
            }
        },
        labels: ['On Balance', 'Off Balance', 'Total Gabungan'],
        legend: {
            show: true,
            position: 'bottom'
        }
    };
    if (document.querySelector('#radialTargetChart')) {
        var radialChart = new ApexCharts(document.querySelector('#radialTargetChart'), radialOptions);
        radialChart.render();
    }

    // ApexCharts: Grouped Bar - Target vs Capaian per Jenis (On & Off Balance)
    <?php
    $jenisNama = [];
    $jenisTargetOnArr = [];
    $jenisCapaianOnArr = [];
    $jenisTargetOffArr = [];
    $jenisCapaianOffArr = [];
    $tjOnMap = [];
    $tjOffMap = [];
    foreach ($targetJenisRows as $tj) {
        $jidKey = (int)$tj['id_jenis_pembayaran'];
        $tjOnMap[$jidKey] = (float)$tj['nominal_target'];
        $tjOffMap[$jidKey] = (float)($tj['nominal_target_off_balance'] ?? 0);
    }
    foreach ($jenisList as $j) {
        $jid = (int)$j['id'];
        $jenisNama[] = $j['nama_jenis'];
        $jenisTargetOnArr[] = $tjOnMap[$jid] ?? 0;
        $jenisCapaianOnArr[] = (float)($capaianJenisOn[$jid] ?? 0);
        $jenisTargetOffArr[] = $tjOffMap[$jid] ?? 0;
        $jenisCapaianOffArr[] = (float)($capaianJenisOff[$jid] ?? 0);
    }
    ?>
    var jenisBarOptions = {
        series: [
            { name: 'Target On Balance', data: <?= json_encode($jenisTargetOnArr) ?> },
            { name: 'Realisasi On Balance', data: <?= json_encode($jenisCapaianOnArr) ?> },
            { name: 'Target Off Balance', data: <?= json_encode($jenisTargetOffArr) ?> },
            { name: 'Realisasi Off Balance', data: <?= json_encode($jenisCapaianOffArr) ?> }
        ],
        chart: {
            type: 'bar',
            height: 330,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#9ca3af', '#0d9f4f', '#93c5fd', '#2563eb'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '58%',
                borderRadius: 3
            }
        },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: <?= json_encode($jenisNama) ?>,
            axisBorder: { show: true, color: '#b8b8b8' }
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        },
        grid: { borderColor: '#e5e5e5', strokeDashArray: 4 },
        fill: { opacity: 1 },
        legend: { position: 'top', horizontalAlign: 'right' },
        tooltip: {
            y: {
                formatter: function (val) {
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            }
        }
    };
    if (document.querySelector('#jenisBarChart')) {
        var jenisBarChart = new ApexCharts(document.querySelector('#jenisBarChart'), jenisBarOptions);
        jenisBarChart.render();
    }
});

function openModalTahunan(focusTarget) {
    $('#modalTahunan').modal('show');
    setTimeout(function () {
        if (focusTarget === 'off') {
            $('#nominal_target_off_tahun').focus().select();
        } else {
            $('#nominal_target_tahun').focus().select();
        }
    }, 350);
}

function openModalJenis() {
    $('#formJenis')[0].reset();
    $('#tj_id').val('');
    $('#modalJenisTitle').html('<i class="fas fa-plus mr-2 text-success"></i>Tambah Target per Jenis');
    $('#tj_jenis').prop('disabled', false);
    $('#modalJenis').modal('show');
}

function tambahJenisPrefill(idJenis) {
    openModalJenis();
    $('#tj_jenis').val(String(idJenis));
}

function editJenis(id) {
    $.post(window.location.href, { action: 'get_target_jenis', id: id }, function (res) {
        if (!res.success || !res.data) { showError('Data tidak ditemukan'); return; }
        const d = res.data;
        $('#formJenis')[0].reset();
        $('#tj_id').val(d.id);
        $('#tj_jenis').val(d.id_jenis_pembayaran).prop('disabled', true);
        var nOn = parseFloat(d.nominal_target) || 0;
        var nOff = parseFloat(d.nominal_target_off_balance) || 0;
        $('#tj_nominal').val(nOn ? Math.round(nOn).toLocaleString('id-ID') : '');
        $('#tj_nominal_off').val(nOff ? Math.round(nOff).toLocaleString('id-ID') : '');
        $('#tj_ket').val(d.keterangan || '');
        $('#modalJenisTitle').html('<i class="fas fa-edit mr-2 text-primary"></i>Ubah Target per Jenis');
        $('#modalJenis').modal('show');
    }, 'json');
}

function hapusJenis(id) {
    confirmDelete(function () {
        $.post(window.location.href, { action: 'delete_target_jenis', id: id }, function (res) {
            if (res.success) { showSuccess(res.message); setTimeout(function () { location.reload(); }, 600); }
            else { showError(res.message); }
        }, 'json');
    });
}

$('#formTahunan').on('submit', function (e) {
    e.preventDefault();
    var fd = $(this).serialize() + '&action=save_target_tahunan';
    $.post(window.location.href, fd, function (res) {
        if (res.success) { $('#modalTahunan').modal('hide'); showSuccess(res.message); setTimeout(function () { location.reload(); }, 600); }
        else { showError(res.message); }
    }, 'json');
});

$('#formJenis').on('submit', function (e) {
    e.preventDefault();
    $('#tj_jenis').prop('disabled', false);
    var fd = $(this).serialize() + '&action=save_target_jenis';
    $.post(window.location.href, fd, function (res) {
        if (res.success) { $('#modalJenis').modal('hide'); showSuccess(res.message); setTimeout(function () { location.reload(); }, 600); }
        else { showError(res.message); }
    }, 'json');
});

$('#modalJenis').on('hidden.bs.modal', function () {
    $('#tj_jenis').prop('disabled', false);
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
