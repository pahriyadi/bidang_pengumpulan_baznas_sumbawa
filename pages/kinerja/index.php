<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_auth();

$activeMenu = 'kinerja';
$pageTitle = 'Kinerja Pengumpulan';
$pageDescription = 'Target tahunan, capaian dari transaksi, dan target per jenis pembayaran';
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
        $nominal = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_target'] ?? '0');
        $ket = trim($_POST['keterangan'] ?? '');
        if ($tahun < 2000 || $tahun > 2100) {
            echo json_encode(['success' => false, 'message' => 'Tahun tidak valid']);
            exit;
        }
        $stmt = $conn->prepare('
            INSERT INTO target_pengumpulan_tahunan (tahun, nominal_target, keterangan)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE nominal_target = VALUES(nominal_target), keterangan = VALUES(keterangan)
        ');
        $stmt->bind_param('ids', $tahun, $nominal, $ket);
        if ($stmt->execute()) {
            log_audit('SAVE_TARGET_TAHUNAN', 'target_pengumpulan_tahunan', $tahun, "Target Rp $nominal tahun $tahun");
            echo json_encode(['success' => true, 'message' => 'Target tahunan disimpan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'save_target_jenis') {
        $tahun = (int)($_POST['tahun'] ?? 0);
        $id_jenis = (int)($_POST['id_jenis_pembayaran'] ?? 0);
        $nominal = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal_target'] ?? '0');
        $ket = trim($_POST['keterangan'] ?? '');
        if ($tahun < 2000 || $id_jenis <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
            exit;
        }
        $stmt = $conn->prepare('
            INSERT INTO target_pengumpulan_per_jenis (tahun, id_jenis_pembayaran, nominal_target, keterangan)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE nominal_target = VALUES(nominal_target), keterangan = VALUES(keterangan)
        ');
        $stmt->bind_param('iids', $tahun, $id_jenis, $nominal, $ket);
        if ($stmt->execute()) {
            log_audit('SAVE_TARGET_JENIS', 'target_pengumpulan_per_jenis', $id_jenis, "Tahun $tahun jenis $id_jenis Rp $nominal");
            echo json_encode(['success' => true, 'message' => 'Target per jenis disimpan']);
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

$capaianJenis = get_capaian_per_tahun_jenis($conn, $tahun_pilih);

$stmtTt = $conn->prepare('SELECT * FROM target_pengumpulan_tahunan WHERE tahun = ? LIMIT 1');
$stmtTt->bind_param('i', $tahun_pilih);
$stmtTt->execute();
$targetTahunanRow = $stmtTt->get_result()->fetch_assoc();
$stmtTt->close();

$capaianTahun = 0.0;
$pCap = kinerja_sql_and_transaksi_penerimaan($conn, 't');
$stmtC = $conn->prepare("SELECT COALESCE(SUM(t.jumlah_pembayaran), 0) AS t FROM transaksi t WHERE YEAR(t.tanggal_transaksi) = ? {$pCap}");
$stmtC->bind_param('i', $tahun_pilih);
$stmtC->execute();
$capaianTahun = (float)($stmtC->get_result()->fetch_assoc()['t'] ?? 0);
$stmtC->close();

$targetTahunanNom = (float)($targetTahunanRow['nominal_target'] ?? 0);
$pctTahunan = $targetTahunanNom > 0 ? min(100, round(($capaianTahun / $targetTahunanNom) * 100, 1)) : null;

$idKatPenerimaan = get_id_kategori_penerimaan($conn);

include __DIR__ . '/../../inc/header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<?php if ($idKatPenerimaan === null): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Tidak ada kategori dana bernama <strong>Penerimaan</strong> di master data. Capaian kinerja akan tampil nol sampai kategori itu dibuat di menu <a href="<?= url('pages/kategori_dana/index.php') ?>">Kategori Dana</a>.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12 mb-3">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8; background-color: #ffffff;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-filter mr-2 text-success"></i>Pilih Tahun Analisis</h3>
            </div>
            <div class="card-body">
                <form method="get" class="form-inline">
                    <label class="mr-2 font-weight-bold">Tahun</label>
                    <select name="tahun" class="form-control mr-2" onchange="this.form.submit()" style="border: 1px solid #b8b8b8;">
                        <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $tahun_pilih === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8; background-color: #ffffff;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-bullseye mr-2 text-success"></i>Target &amp; Capaian <?= $tahun_pilih ?></h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="openModalTahunan()">
                        <i class="fas fa-edit mr-1"></i>Input Target
                    </button>
                </div>
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <div id="radialTargetChart" style="width: 100%; min-height: 280px;"></div>
                <div class="text-center mt-2">
                    <p class="small text-muted mb-1">Target: <strong><?= $targetTahunanNom > 0 ? format_currency($targetTahunanNom) : '<span class="text-muted">Belum diatur</span>' ?></strong></p>
                    <p class="small text-success font-weight-bold mb-1">Capaian: <strong><?= format_currency($capaianTahun) ?></strong></p>
                    <?php if ($targetTahunanNom > 0 && $capaianTahun < $targetTahunanNom): ?>
                    <p class="small text-muted mb-0">Sisa: <strong class="text-danger"><?= format_currency($targetTahunanNom - $capaianTahun) ?></strong></p>
                    <?php elseif ($targetTahunanNom > 0): ?>
                    <p class="small text-success font-weight-bold mb-0"><i class="fas fa-check-circle mr-1"></i>Target tercapai!</p>
                    <?php endif; ?>
                    <?php if (!empty($targetTahunanRow['keterangan'])): ?>
                    <p class="small text-muted mt-1 mb-0"><i class="fas fa-info-circle mr-1"></i><?= clean($targetTahunanRow['keterangan']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card shadow-none h-100 mb-0" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8; background-color: #ffffff;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2 text-success"></i>Ringkasan Semua Tahun</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table id="tblRingkasan" class="table table-hover table-sm mb-0">
                    <thead>
                        <tr style="border-bottom: 1px solid #b8b8b8;">
                            <th>Tahun</th>
                            <th class="text-right">Target</th>
                            <th class="text-right">Capaian</th>
                            <th class="text-center">%</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ringkasan as $r):
                            $t = (int)$r['tahun'];
                            $tgt = (float)$r['target_nominal'];
                            $cap = (float)$r['capaian'];
                            $pct = $tgt > 0 ? min(100, round(($cap / $tgt) * 100, 1)) : null;
                        ?>
                        <tr <?= $t === $tahun_pilih ? 'style="background-color: #f0f7f3;"' : '' ?>>
                            <td><strong><?= $t ?></strong></td>
                            <td class="text-right"><?= $tgt > 0 ? format_currency($tgt) : '—' ?></td>
                            <td class="text-right"><?= format_currency($cap) ?></td>
                            <td class="text-center"><?= $pct !== null ? $pct . '%' : '—' ?></td>
                            <td>
                                <?php if ($tgt <= 0): ?>
                                    <span class="badge badge-secondary">Target kosong</span>
                                <?php elseif ($cap >= $tgt): ?>
                                    <span class="badge badge-success">Tercapai</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Berlangsung</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ringkasan)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #b8b8b8; background-color: #ffffff;">
                <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-chart-bar mr-2 text-success"></i>Target vs Capaian per Jenis — <?= $tahun_pilih ?></h3>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="openModalJenis()">
                    <i class="fas fa-plus mr-1"></i>Tambah / Ubah Target Jenis
                </button>
            </div>
            <div class="card-body">
                <div id="jenisBarChart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="card-header" style="border-bottom: 1px solid #b8b8b8; background-color: #ffffff;">
                <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-2 text-success"></i>Rincian Target per Jenis Pembayaran</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size: 0.9rem; border: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid #b8b8b8;">
                            <th>Jenis</th>
                            <th class="text-right">Target</th>
                            <th class="text-right">Capaian</th>
                            <th class="text-center">%</th>
                            <th width="12%" class="text-center">Aksi</th>
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
                            $tgtJ = $tj ? (float)$tj['nominal_target'] : 0;
                            $capJ = (float)($capaianJenis[$jid] ?? 0);
                            $pctJ = $tgtJ > 0 ? min(100, round(($capJ / $tgtJ) * 100, 1)) : null;
                        ?>
                        <tr>
                            <td class="font-weight-bold"><?= clean($j['nama_jenis']) ?></td>
                            <td class="text-right"><?= $tgtJ > 0 ? format_currency($tgtJ) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-right text-success font-weight-bold"><?= format_currency($capJ) ?></td>
                            <td class="text-center">
                                <?php if ($pctJ !== null): ?>
                                <span class="badge <?= $pctJ >= 100 ? 'badge-success' : 'badge-warning' ?>"><?= $pctJ ?>%</span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tj): ?>
                                <button type="button" class="btn btn-warning btn-xs" onclick="editJenis(<?= (int)$tj['id'] ?>)"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-danger btn-xs" onclick="hapusJenis(<?= (int)$tj['id'] ?>)"><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-success btn-xs" onclick="tambahJenisPrefill(<?= $jid ?>)"><i class="fas fa-plus"></i> Target</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($jenisList)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada master jenis pembayaran.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted small" style="border-top: 1px solid #b8b8b8;">
                Capaian = <code>SUM(jumlah_pembayaran)</code> hanya untuk transaksi yang memuat kategori dana <strong>Penerimaan</strong>.
            </div>
        </div>
    </div>
</div>

<!-- Modal target tahunan -->
<div class="modal fade" id="modalTahunan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-bullseye mr-2 text-success"></i>Target Pengumpulan Tahunan</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formTahunan">
                <div class="modal-body">
                    <input type="hidden" name="tahun" value="<?= $tahun_pilih ?>">
                    <div class="form-group">
                        <label class="font-weight-bold">Tahun</label>
                        <input type="number" class="form-control" value="<?= $tahun_pilih ?>" readonly style="border: 1px solid #b8b8b8; background: #f9f9f9;">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Nominal target (Rp) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control currency-input" name="nominal_target" id="nominal_target_tahun" required
                               value="<?= $targetTahunanNom > 0 ? number_format($targetTahunanNom, 0, ',', '.') : '' ?>" placeholder="0" style="border: 1px solid #b8b8b8;">
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Keterangan (opsional)</label>
                        <textarea class="form-control" name="keterangan" rows="2" placeholder="Contoh: Target RKAT BAZNAS" style="border: 1px solid #b8b8b8;"><?= clean($targetTahunanRow['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #b8b8b8;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal target jenis -->
<div class="modal fade" id="modalJenis" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalJenisTitle"><i class="fas fa-layer-group mr-2"></i>Target per Jenis</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formJenis">
                <div class="modal-body">
                    <input type="hidden" name="id" id="tj_id" value="">
                    <input type="hidden" name="tahun" value="<?= $tahun_pilih ?>">
                    <div class="form-group">
                        <label>Jenis pembayaran <span class="text-danger">*</span></label>
                        <select class="form-control" name="id_jenis_pembayaran" id="tj_jenis" required>
                            <option value="">Pilih</option>
                            <?php foreach ($jenisList as $j): ?>
                            <option value="<?= (int)$j['id'] ?>"><?= clean($j['nama_jenis']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nominal target (Rp) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control currency-input" name="nominal_target" id="tj_nominal" required placeholder="0">
                    </div>
                    <div class="form-group mb-0">
                        <label>Keterangan (opsional)</label>
                        <textarea class="form-control" name="keterangan" id="tj_ket" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#tblRingkasan').DataTable({ order: [[0, 'desc']], pageLength: 15 });
    initCurrencyInput('.currency-input');

    // ApexCharts: Radial Bar - Target Tahunan
    var pct = <?= $pctTahunan !== null ? $pctTahunan : 0 ?>;
    var radialOptions = {
        series: [pct],
        chart: {
            height: 280,
            type: 'radialBar',
            fontFamily: 'Inter, sans-serif'
        },
        colors: [pct >= 100 ? '#0d9f4f' : '#ffc107'],
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                dataLabels: {
                    name: {
                        fontSize: '14px',
                        color: '#6c757d',
                        offsetY: 80
                    },
                    value: {
                        offsetY: 40,
                        fontSize: '32px',
                        fontWeight: 'bold',
                        color: '#212529',
                        formatter: function (val) {
                            return val + "%";
                        }
                    }
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'dark',
                shadeIntensity: 0.1,
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 50, 65, 91, 100]
            }
        },
        stroke: { dashArray: 4 },
        labels: ['Pencapaian Target'],
    };
    if (document.querySelector('#radialTargetChart')) {
        var radialChart = new ApexCharts(document.querySelector('#radialTargetChart'), radialOptions);
        radialChart.render();
    }

    // ApexCharts: Grouped Bar - Target vs Capaian per Jenis
    <?php
    $jenisNama = [];
    $jenisTargetArr = [];
    $jenisCapaianArr = [];
    $tjByJenisId = [];
    foreach ($targetJenisRows as $tj) {
        $tjByJenisId[(int)$tj['id_jenis_pembayaran']] = (float)$tj['nominal_target'];
    }
    foreach ($jenisList as $j) {
        $jid = (int)$j['id'];
        $jenisNama[] = $j['nama_jenis'];
        $jenisTargetArr[] = $tjByJenisId[$jid] ?? 0;
        $jenisCapaianArr[] = (float)($capaianJenis[$jid] ?? 0);
    }
    ?>
    var jenisBarOptions = {
        series: [{
            name: 'Target',
            data: <?= json_encode($jenisTargetArr) ?>
        }, {
            name: 'Capaian Aktual',
            data: <?= json_encode($jenisCapaianArr) ?>
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false },
            fontFamily: 'Inter, sans-serif'
        },
        colors: ['#b8b8b8', '#0d9f4f'],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%',
                borderRadius: 4
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

function openModalTahunan() {
    $('#modalTahunan').modal('show');
}

function openModalJenis() {
    $('#formJenis')[0].reset();
    $('#tj_id').val('');
    $('#modalJenisTitle').html('<i class="fas fa-plus mr-2"></i>Tambah Target Jenis');
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
        var n = parseFloat(d.nominal_target) || 0;
        $('#tj_nominal').val(n ? Math.round(n).toLocaleString('id-ID') : '');
        $('#tj_ket').val(d.keterangan || '');
        $('#modalJenisTitle').html('<i class="fas fa-edit mr-2"></i>Ubah Target Jenis');
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
