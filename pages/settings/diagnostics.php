<?php
/**
 * Dokter Sistem & Database (AI System Diagnostics & Safe Auto-Repair)
 * SIM Pengumpulan ZIS BAZNAS Kabupaten Sumbawa
 * Sistem Desain: "Putih Kertas" (Paper White)
 */

require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/system_doctor.php';
require_once __DIR__ . '/../../config/ai_engine.php';

require_auth();

if (!has_role('admin')) {
    redirect_with_flash('index.php', 'Akses ditolak. Halaman ini hanya untuk Administrator.', 'danger');
}

$activeMenu = 'diagnostics';
$pageTitle = 'Dokter Sistem & Database';
$pageDescription = 'Diagnostik mandiri kesehatan server hosting, optimasi database, dan analisis log error';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item"><a href="#">Pengaturan</a></li><li class="breadcrumb-item active">Dokter Sistem & DB</li>';

// --- AJAX HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // 1. Pindai Ulang Sistem
    if ($action === 'scan') {
        $scan = doctor_scan_all();
        echo json_encode(['success' => true, 'data' => $scan]);
        exit;
    }

    // 2. Eksekusi Perbaikan Otomatis Aman (Safe 1-Click Repair)
    if ($action === 'repair') {
        $repairType = trim($_POST['repair_type'] ?? '');
        $res = doctor_execute_repair($repairType);
        echo json_encode($res);
        exit;
    }

    // 3. Konsultasi Analisis Log dengan AI (Gemini Hybrid)
    if ($action === 'consult_ai') {
        $logSnippet = trim($_POST['log_snippet'] ?? '');
        if (empty($logSnippet)) {
            echo json_encode(['success' => false, 'explanation' => 'Tidak ada potongan log yang diberikan.']);
            exit;
        }

        $prompt = "Anda adalah Ahli IT Senior dan Sysadmin BAZNAS. Analisis galat/error server PHP/MySQL berikut ini secara ringkas, jelas, dan santun:\n\n"
                . "```\n" . substr($logSnippet, 0, 1500) . "\n```\n\n"
                . "Berikan jawaban dalam format:\n"
                . "1. **Penyebab Utama**: Jelaskan secara sederhana apa yang terjadi.\n"
                . "2. **Tingkat Bahaya**: (Rendah / Sedang / Kritis)\n"
                . "3. **Langkah Solusi Konkret**: Apa yang harus dilakukan oleh admin/programmer untuk mengatasinya.";

        try {
            $explanation = ai_ask_gemini($prompt);
            if (empty($explanation)) {
                $explanation = "Pesan log tersebut mengindikasikan adanya kendala pada runtime server. Pastikan baris kode pada file yang tertera dicek parameternya atau pastikan ekstensi database/session aktif.";
            }
        } catch (Throwable $e) {
            $explanation = "Maaf, konsultasi cloud AI mengalami kendala koneksi: " . $e->getMessage() . ". Namun pesan galat ini umumnya berkaitan dengan ketidaksesuaian tipe data atau sesi yang terputus.";
        }

        echo json_encode([
            'success' => true,
            'explanation' => $explanation
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
    exit;
}

// Data awal pemindaian saat halaman dimuat (0 Token)
$initialScan = doctor_scan_all();

include __DIR__ . '/../../inc/header.php';
?>

<style>
/* Putih Kertas (Paper White) & Flat Design */
.doctor-card {
    border: 1px solid #b8b8b8;
    border-radius: 6px;
    background: #ffffff;
    box-shadow: none !important;
    margin-bottom: 20px;
}
.doctor-header {
    background: #fcfcfc;
    border-bottom: 1px solid #b8b8b8;
    padding: 12px 18px;
}
.doctor-body {
    padding: 18px;
}
.metric-box {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 14px;
    background: #ffffff;
    height: 100%;
}
.metric-val {
    font-size: 1.5rem;
    font-weight: 700;
}
.badge-soft-success {
    background-color: #eef7f2;
    color: #0d9f4f;
    border: 1px solid #0d9f4f;
}
.badge-soft-warning {
    background-color: #fffdf0;
    color: #856404;
    border: 1px solid #f0ad4e;
}
.badge-soft-danger {
    background-color: #fff5f5;
    color: #d9534f;
    border: 1px solid #d9534f;
}
.issue-card {
    border-left: 4px solid #b8b8b8;
    border-radius: 4px;
    padding: 12px 16px;
    margin-bottom: 12px;
    background: #fafafa;
    border-top: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
}
.issue-card.danger {
    border-left-color: #dc3545;
    background: #fffafa;
}
.issue-card.warning {
    border-left-color: #f0ad4e;
    background: #fffdf5;
}
.issue-card.info {
    border-left-color: #17a2b8;
    background: #f8fbff;
}
.log-table td, .log-table th {
    font-size: 0.8rem;
    font-family: 'Consolas', 'Courier New', monospace;
    padding: 6px 10px;
}
</style>

<div class="row">
    <!-- Header Ringkasan & Tombol Pindai Ulang -->
    <div class="col-12 mb-3 d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
        <div>
            <h4 class="font-weight-bold mb-0 text-dark">
                <i class="fas fa-stethoscope text-success mr-2"></i>Dokter Sistem & Database
            </h4>
            <small class="text-muted">
                Pemantauan kesehatan server hosting, audit integritas database, dan pemeliharaan mandiri aman (0 Token).
            </small>
        </div>
        <div>
            <button type="button" class="btn btn-outline-success font-weight-bold px-3 py-2" id="btnRescan" onclick="rescanDoctor()" style="border: 1.5px solid #0d9f4f; background:#fff; border-radius:4px;">
                <i class="fas fa-sync-alt mr-1" id="iconRescan"></i> Pindai Ulang Sekarang
            </button>
        </div>
    </div>
</div>

<!-- Kartu Ringkasan Metrik Utama -->
<div class="row">
    <!-- Skor Kesehatan Sistem -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-box border">
            <small class="text-muted text-uppercase font-weight-bold" style="font-size:0.75rem;">Skor Kesehatan Sistem</small>
            <div class="d-flex align-items-center justify-content-between mt-1">
                <div class="metric-val" id="scoreVal" style="color: <?= $initialScan['status_color'] ?>;">
                    <?= $initialScan['score'] ?>%
                </div>
                <span class="badge px-2 py-1" id="scoreBadge" style="border: 1px solid <?= $initialScan['status_color'] ?>; color: <?= $initialScan['status_color'] ?>; background: #fff; font-size: 0.78rem;">
                    <?= htmlspecialchars($initialScan['status_text']) ?>
                </span>
            </div>
            <div class="progress mt-2" style="height: 6px;">
                <div class="progress-bar" id="scoreBar" role="progressbar" style="width: <?= $initialScan['score'] ?>%; background-color: <?= $initialScan['status_color'] ?>;"></div>
            </div>
            <small class="text-muted d-block mt-2" style="font-size:0.72rem;" id="scanTimestamp">
                <i class="far fa-clock mr-1"></i> Terakhir dipindai: <?= date('H:i:s', strtotime($initialScan['timestamp'])) ?> WITA
            </small>
        </div>
    </div>

    <!-- Metrik Database -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-box border">
            <small class="text-muted text-uppercase font-weight-bold" style="font-size:0.75rem;">Database MySQL</small>
            <div class="metric-val text-dark mt-1" id="dbSizeVal">
                <?= $initialScan['database']['total_data_size_formatted'] ?>
            </div>
            <div class="d-flex justify-content-between mt-2 small text-muted">
                <span><i class="fas fa-table mr-1"></i> <?= $initialScan['database']['table_count'] ?> Tabel</span>
                <span id="dbOverheadBadge" class="<?= !empty($initialScan['database']['fragmented_tables']) ? 'text-warning font-weight-bold' : 'text-success' ?>">
                    <i class="fas fa-compress-arrows-alt mr-1"></i> <?= $initialScan['database']['total_overhead_formatted'] ?> sisa
                </span>
            </div>
            <div class="mt-2 text-right">
                <?php if (!empty($initialScan['database']['fragmented_tables'])): ?>
                    <button type="button" class="btn btn-xs btn-outline-warning py-0 px-2" onclick="executeRepair('optimize_tables')" style="border-radius:3px;">
                        <i class="fas fa-magic mr-1"></i> Optimasi
                    </button>
                <?php else: ?>
                    <span class="badge badge-soft-success py-1 px-2" style="font-size:0.72rem;"><i class="fas fa-check mr-1"></i> Indeks & Tabel Prima</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Metrik Server Hosting -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-box border">
            <small class="text-muted text-uppercase font-weight-bold" style="font-size:0.75rem;">Server & Lingkungan PHP</small>
            <div class="metric-val text-dark mt-1">
                PHP <?= $initialScan['server']['php_version'] ?>
            </div>
            <div class="d-flex justify-content-between mt-2 small text-muted">
                <span>Mem: <strong><?= $initialScan['server']['memory_limit'] ?></strong></span>
                <span>Max Upload: <strong><?= $initialScan['server']['upload_max_filesize'] ?></strong></span>
            </div>
            <div class="mt-2 text-right">
                <?php if (empty($initialScan['server']['unwritable_folders'])): ?>
                    <span class="badge badge-soft-success py-1 px-2" style="font-size:0.72rem;"><i class="fas fa-check mr-1"></i> Izin Folder Aman</span>
                <?php else: ?>
                    <span class="badge badge-soft-danger py-1 px-2" style="font-size:0.72rem;"><i class="fas fa-exclamation-triangle mr-1"></i> Izin Perlu Dicek</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Metrik Kapasitas Penyimpanan -->
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="metric-box border">
            <small class="text-muted text-uppercase font-weight-bold" style="font-size:0.75rem;">Ruang Penyimpanan (Disk)</small>
            <div class="metric-val text-dark mt-1" id="diskFreeVal">
                <?= $initialScan['server']['disk_free_formatted'] ?>
            </div>
            <div class="d-flex justify-content-between mt-2 small text-muted">
                <span>Total: <?= $initialScan['server']['disk_total_formatted'] ?></span>
                <span>Temp: <?= $initialScan['server']['temp_files_size_formatted'] ?></span>
            </div>
            <div class="mt-2 text-right">
                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="executeRepair('clean_temp_files')" style="border-radius:3px;">
                    <i class="fas fa-broom mr-1"></i> Bersihkan Sampah
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bagian Rekomendasi & Tindakan Perbaikan Cepat (Actionable Issues) -->
<div class="row">
    <div class="col-12">
        <div class="doctor-card">
            <div class="doctor-header d-flex justify-content-between align-items-center">
                <strong style="color: #222; font-size: 0.95rem;">
                    <i class="fas fa-clipboard-check text-success mr-2"></i>Daftar Rekomendasi & Tindakan Perbaikan Mandiri
                </strong>
                <span class="badge badge-secondary" id="issuesCountBadge" style="border: 1px solid #b8b8b8;">
                    <?= count($initialScan['issues']) ?> Catatan Ditemukan
                </span>
            </div>
            <div class="doctor-body" id="issuesListContainer">
                <?php if (empty($initialScan['issues'])): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-success mb-2" style="font-size: 3rem;"></i>
                        <h5 class="font-weight-bold text-success">Alhamdulillah, Sistem dalam Kondisi Sempurna!</h5>
                        <p class="text-muted mb-0">Tidak ditemukan fragmentasi tabel, kueri yang lambat, maupun galat fatal pada server hosting Anda.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($initialScan['issues'] as $iss): ?>
                        <div class="issue-card <?= htmlspecialchars($iss['type']) ?> d-flex flex-wrap justify-content-between align-items-center" style="gap: 12px;">
                            <div style="flex: 1; min-width: 250px;">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge mr-2 badge-soft-<?= $iss['type'] === 'danger' ? 'danger' : ($iss['type'] === 'warning' ? 'warning' : 'info') ?>" style="font-size:0.75rem;">
                                        <?= htmlspecialchars($iss['category']) ?>
                                    </span>
                                    <strong class="text-dark" style="font-size:0.92rem;"><?= htmlspecialchars($iss['title']) ?></strong>
                                </div>
                                <div class="text-muted small"><?= htmlspecialchars($iss['description']) ?></div>
                            </div>
                            <div>
                                <?php if (!empty($iss['action'])): ?>
                                    <?php if ($iss['action'] === 'open_rekening_koran'): ?>
                                        <a href="<?= url('pages/rekening_koran/index.php') ?>" class="btn btn-sm btn-outline-success font-weight-bold px-3" style="border-radius:4px; font-size:0.8rem;">
                                            <i class="fas fa-external-link-alt mr-1"></i> <?= htmlspecialchars($iss['action_label']) ?>
                                        </a>
                                    <?php elseif ($iss['action'] === 'view_logs'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold px-3" onclick="$('#tab-logs-link').tab('show');" style="border-radius:4px; font-size:0.8rem;">
                                            <i class="fas fa-terminal mr-1"></i> <?= htmlspecialchars($iss['action_label']) ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold px-3" onclick="executeRepair('<?= $iss['action'] ?>')" style="border-radius:4px; font-size:0.8rem;">
                                            <i class="fas fa-bolt mr-1"></i> <?= htmlspecialchars($iss['action_label']) ?>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tab Rincian Mendalam (Database Tables, Server Environment, Error Logs) -->
<div class="row">
    <div class="col-12">
        <div class="doctor-card">
            <div class="card-header p-0 pt-1" style="background:#fcfcfc; border-bottom: 1px solid #b8b8b8;">
                <ul class="nav nav-tabs px-3" id="doctorTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold text-dark" id="tab-tables-link" data-toggle="tab" href="#tab-tables" role="tab">
                            <i class="fas fa-database mr-1 text-primary"></i> Rincian Tabel Database (<?= count($initialScan['database']['tables']) ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" id="tab-server-link" data-toggle="tab" href="#tab-server" role="tab">
                            <i class="fas fa-server mr-1 text-info"></i> Lingkungan & Ekstensi Server
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold text-dark" id="tab-logs-link" data-toggle="tab" href="#tab-logs" role="tab">
                            <i class="fas fa-file-alt mr-1 text-danger"></i> Log Error Server & Analisis AI
                        </a>
                    </li>
                </ul>
            </div>
            <div class="doctor-body">
                <div class="tab-content" id="doctorTabContent">
                    <!-- Tab 1: Rincian Tabel Database -->
                    <div class="tab-pane fade show active" id="tab-tables" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small">Status fisik tabel basis data `<?= DB_NAME ?>`:</span>
                            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" onclick="executeRepair('optimize_tables')" style="border-radius:4px; font-size:0.78rem;">
                                <i class="fas fa-magic mr-1"></i> Defrag & Optimasi Seluruh Tabel
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover" id="tableDbDetails" style="font-size:0.83rem; border-color:#b8b8b8;">
                                <thead style="background:#f8f9fa;">
                                    <tr>
                                        <th>Nama Tabel</th>
                                        <th>Engine</th>
                                        <th class="text-right">Jumlah Baris</th>
                                        <th class="text-right">Ukuran Data</th>
                                        <th class="text-right">Ukuran Index</th>
                                        <th class="text-right">Fragmentasi</th>
                                        <th class="text-center" width="10%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($initialScan['database']['tables'] as $t): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                                            <td><span class="badge badge-light border"><?= htmlspecialchars($t['engine']) ?></span></td>
                                            <td class="text-right"><?= number_format($t['rows'], 0, ',', '.') ?></td>
                                            <td class="text-right"><?= doctor_format_bytes($t['data_size']) ?></td>
                                            <td class="text-right"><?= doctor_format_bytes($t['index_size']) ?></td>
                                            <td class="text-right font-weight-bold <?= $t['overhead'] > 102400 ? 'text-warning' : 'text-muted' ?>">
                                                <?= doctor_format_bytes($t['overhead']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($t['overhead'] > 102400): ?>
                                                    <span class="badge badge-warning" style="border:1px solid #f0ad4e;">Perlu Defrag</span>
                                                <?php else: ?>
                                                    <span class="badge badge-soft-success">Ramping</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 2: Lingkungan & Ekstensi Server -->
                    <div class="tab-pane fade" id="tab-server" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-cogs text-info mr-1"></i> Konfigurasi Runtime PHP</h6>
                                <table class="table table-sm table-bordered" style="font-size:0.85rem; border-color:#b8b8b8;">
                                    <tr>
                                        <td width="50%">Versi PHP</td>
                                        <td><strong>PHP <?= $initialScan['server']['php_version'] ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td>Memory Limit</td>
                                        <td><code><?= $initialScan['server']['memory_limit'] ?></code> (Disarankan min. 256M)</td>
                                    </tr>
                                    <tr>
                                        <td>Upload Max Filesize</td>
                                        <td><code><?= $initialScan['server']['upload_max_filesize'] ?></code></td>
                                    </tr>
                                    <tr>
                                        <td>Post Max Size</td>
                                        <td><code><?= $initialScan['server']['post_max_size'] ?></code></td>
                                    </tr>
                                    <tr>
                                        <td>Max Execution Time</td>
                                        <td><code><?= $initialScan['server']['max_execution_time'] ?></code></td>
                                    </tr>
                                    <tr>
                                        <td>Web Server</td>
                                        <td><?= htmlspecialchars($initialScan['server']['server_software']) ?></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-puzzle-piece text-success mr-1"></i> Ekstensi PHP yang Terpasang</h6>
                                <div class="row">
                                    <?php foreach ($initialScan['server']['extensions'] as $extName => $isLoaded): ?>
                                        <div class="col-6 mb-2">
                                            <div class="p-2 border rounded d-flex justify-content-between align-items-center" style="font-size:0.8rem;">
                                                <span><code><?= htmlspecialchars($extName) ?></code></span>
                                                <?php if ($isLoaded): ?>
                                                    <span class="badge badge-soft-success"><i class="fas fa-check"></i> Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-soft-danger"><i class="fas fa-times"></i> Mati</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <h6 class="font-weight-bold text-dark mt-3 mb-2"><i class="fas fa-folder-open text-warning mr-1"></i> Izin Tulis Direktori</h6>
                                <div class="p-2 border rounded" style="font-size:0.8rem;">
                                    <?php if (empty($initialScan['server']['unwritable_folders'])): ?>
                                        <span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Semua folder upload & cache dapat ditulisi (Writable 755/777).</span>
                                    <?php else: ?>
                                        <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> Folder berikut terkunci:</span>
                                        <ul class="mb-0 text-danger pl-3">
                                            <?php foreach ($initialScan['server']['unwritable_folders'] as $uF): ?>
                                                <li><code><?= htmlspecialchars($uF) ?></code></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Log Error Server & Konsultasi AI -->
                    <div class="tab-pane fade" id="tab-logs" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong class="text-dark"><i class="fas fa-file-medical-alt text-danger mr-1"></i> File Log Aktif:</strong>
                                <code><?= htmlspecialchars($initialScan['logs']['log_path'] ?: 'Tidak ada file log') ?></code>
                                <?php if ($initialScan['logs']['found']): ?>
                                    <small class="text-muted ml-2">(<?= $initialScan['logs']['file_size_formatted'] ?>)</small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="badge badge-soft-danger mr-1 font-weight-bold"><?= $initialScan['logs']['fatal_count'] ?> Fatal</span>
                                <span class="badge badge-soft-warning font-weight-bold"><?= $initialScan['logs']['warning_count'] ?> Warning</span>
                            </div>
                        </div>

                        <?php if (!$initialScan['logs']['found'] || empty($initialScan['logs']['lines'])): ?>
                            <div class="alert alert-success" style="border:1px solid #0d9f4f; background:#eef7f2; color:#0d9f4f; border-radius:4px;">
                                <i class="fas fa-check-circle mr-1"></i> Tidak ditemukan catatan galat atau log server bersih dari error kritis.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive border rounded" style="max-height: 420px; overflow-y: auto;">
                                <table class="table table-sm table-striped log-table mb-0">
                                    <thead class="thead-dark" style="position: sticky; top: 0; z-index: 2;">
                                        <tr>
                                            <th width="8%">Tingkat</th>
                                            <th>Isi Catatan Log Error</th>
                                            <th width="14%" class="text-center">Tindakan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($initialScan['logs']['lines'] as $idx => $line): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($line['type'] === 'fatal'): ?>
                                                        <span class="badge badge-danger">FATAL</span>
                                                    <?php elseif ($line['type'] === 'warning'): ?>
                                                        <span class="badge badge-warning">WARN</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">INFO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-break" style="white-space: pre-wrap;"><?= htmlspecialchars($line['raw']) ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-outline-success font-weight-bold" onclick="consultAiForError(<?= htmlspecialchars(json_encode($line['raw']), ENT_QUOTES, 'UTF-8') ?>)" style="border-radius:3px; font-size:0.72rem;">
                                                        <i class="fas fa-robot mr-1"></i> Tanya AI
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konsultasi AI untuk Error Log -->
<div class="modal fade" id="modalAiConsult" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border: 1.5px solid #b8b8b8; border-radius: 6px; box-shadow: none !important;">
            <div class="modal-header py-2 px-3" style="background: #fdfdfd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title font-weight-bold" style="font-size: 1rem; color:#333;">
                    <i class="fas fa-robot text-success mr-2"></i>Analisis Solusi Cerdas AI
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <small class="text-muted font-weight-bold text-uppercase" style="font-size:0.72rem;">Potongan Error yang Ditelusuri:</small>
                    <pre class="p-2 border rounded bg-light" id="consultSnippet" style="font-size:0.78rem; max-height:120px; overflow-y:auto;"></pre>
                </div>
                <div>
                    <small class="text-muted font-weight-bold text-uppercase" style="font-size:0.72rem;">Diagnosa & Rekomendasi Solusi AI:</small>
                    <div id="consultResult" class="p-3 border rounded bg-white" style="font-size:0.88rem; min-height: 140px; line-height: 1.6;">
                        <span class="text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Meminta analisis solusi kepada AI...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" style="border-radius:4px;">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    if ($("#tableDbDetails").length) {
        $("#tableDbDetails").DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 10,
            order: [[5, 'desc']] // Urutkan berdasarkan fragmentasi overhead terbesar
        });
    }
});

function rescanDoctor() {
    const btn = $("#btnRescan");
    const icon = $("#iconRescan");
    btn.prop("disabled", true);
    icon.addClass("fa-spin");

    $.post("", { action: "scan" }, function(res) {
        btn.prop("disabled", false);
        icon.removeClass("fa-spin");

        if (res.success) {
            Swal.fire({
                icon: "success",
                title: "Pemindaian Selesai",
                text: "Skor kesehatan sistem: " + res.data.score + "% (" + res.data.status_text + ")",
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire("Gagal", "Gagal memindai kondisi sistem.", "error");
        }
    }, "json").fail(function() {
        btn.prop("disabled", false);
        icon.removeClass("fa-spin");
        Swal.fire("Error", "Koneksi ke server terputus saat memindai.", "error");
    });
}

function executeRepair(repairType) {
    let confirmMsg = "Jalankan pemeliharaan mandiri ini?";
    if (repairType === 'optimize_tables') {
        confirmMsg = "Sistem akan menjalankan OPTIMIZE & ANALYZE pada seluruh tabel database untuk mengompres ruang kosong. Lanjutkan?";
    } else if (repairType === 'add_indexes') {
        confirmMsg = "Sistem akan menambahkan indeks performa pada kolom transaksi kunci untuk mempercepat penarikan kueri. Lanjutkan?";
    } else if (repairType === 'clean_temp_files') {
        confirmMsg = "Sistem akan menghapus file sementara, cache kedaluwarsa, dan berkas unggahan usang. Lanjutkan?";
    }

    Swal.fire({
        title: "Konfirmasi Pemeliharaan",
        text: confirmMsg,
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#0d9f4f",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Ya, Eksekusi Perbaikan",
        cancelButtonText: "Batal"
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Memproses...",
                text: "Sedang mengeksekusi pemeliharaan aman pada server...",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.post("", { action: "repair", repair_type: repairType }, function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Perbaikan Berhasil",
                        text: res.message
                    }).then(() => location.reload());
                } else {
                    Swal.fire("Gagal", res.message || "Tindakan perbaikan gagal dieksekusi.", "error");
                }
            }, "json").fail(function() {
                Swal.fire("Error", "Terjadi galat pada server saat memproses perbaikan.", "error");
            });
        }
    });
}

function consultAiForError(rawLog) {
    $("#consultSnippet").text(rawLog);
    $("#consultResult").html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin text-success mr-2"></i> Amil Virtual sedang menganalisis log dan menyiapkan rekomendasi solusi...</div>');
    $("#modalAiConsult").modal("show");

    $.post("", { action: "consult_ai", log_snippet: rawLog }, function(res) {
        if (res.success && res.explanation) {
            let formatted = res.explanation.replace(/\n/g, '<br>');
            $("#consultResult").html(formatted);
        } else {
            $("#consultResult").html('<div class="alert alert-warning mb-0">Tidak dapat menghasilkan penjelasan otomatis untuk log ini.</div>');
        }
    }, "json").fail(function() {
        $("#consultResult").html('<div class="alert alert-danger mb-0">Terjadi kesalahan koneksi saat berkonsultasi dengan asisten AI.</div>');
    });
}
</script>

<?php
include __DIR__ . '/../../inc/footer.php';
?>
