<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

if (!has_role('admin')) {
    header("Location: " . url('index.php'));
    exit;
}

$activeMenu = 'changelog';
$pageTitle = 'Manajemen Log Perubahan';
$pageDescription = 'Kelola catatan pembaruan fitur, perbaikan bug, dan perubahan sistem';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Log Perubahan</li>';
$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $tanggal = trim($_POST['tanggal'] ?? '');
        $kategori = trim($_POST['kategori'] ?? '');
        $judul = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if (!$tanggal || !$kategori || !$judul || !$deskripsi) {
            echo json_encode(['success' => false, 'message' => 'Semua kolom wajib diisi.']);
            exit;
        }

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO system_changelog (tanggal, kategori, judul, deskripsi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $tanggal, $kategori, $judul, $deskripsi);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("UPDATE system_changelog SET tanggal=?, kategori=?, judul=?, deskripsi=? WHERE id=?");
            $stmt->bind_param('ssssi', $tanggal, $kategori, $judul, $deskripsi, $id);
        }

        if ($stmt->execute()) {
            $log_id = ($action === 'create') ? $conn->insert_id : $id;
            log_audit(strtoupper($action) . '_CHANGELOG', 'system_changelog', $log_id, "Judul: $judul, Kategori: $kategori");
            echo json_encode(['success' => true, 'message' => 'Log perubahan berhasil ' . ($action === 'create' ? 'ditambahkan' : 'diperbarui')]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM system_changelog WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_CHANGELOG', 'system_changelog', $id, "Deleted changelog ID: $id");
            echo json_encode(['success' => true, 'message' => 'Log perubahan berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus log.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM system_changelog WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        exit;
    }
}

$data = $conn->query("SELECT * FROM system_changelog ORDER BY tanggal DESC, id DESC");
include __DIR__ . '/../../inc/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-bullhorn mr-2 text-success"></i>Catatan Riwayat Log Perubahan</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal()">
                <i class="fas fa-plus mr-1"></i>Tambah Log Baru
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
            <thead>
                <tr>
                    <th width="4%" class="text-center">No</th>
                    <th width="12%" class="text-center">Tanggal</th>
                    <th width="12%" class="text-center">Kategori</th>
                    <th>Judul Perubahan</th>
                    <th>Deskripsi Rincian</th>
                    <th width="10%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><strong><?= date('d/m/Y', strtotime($row['tanggal'])) ?></strong></td>
                    <td class="text-center">
                        <?php
                        $badgeClass = 'badge-info';
                        if ($row['kategori'] === 'Fitur Baru') $badgeClass = 'badge-success';
                        elseif ($row['kategori'] === 'Pembaruan Desain') $badgeClass = 'badge-primary';
                        elseif ($row['kategori'] === 'Perbaikan Bug') $badgeClass = 'badge-danger';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['kategori']) ?></span>
                    </td>
                    <td><strong><?= htmlspecialchars($row['judul']) ?></strong></td>
                    <td><small><?= $row['deskripsi'] ?></small></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-xs" onclick="editData(<?= $row['id'] ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-xs" onclick="deleteData(<?= $row['id'] ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Changelog -->
<div class="modal fade" id="formModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Log Perubahan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <form id="dataForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="dataId">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Perubahan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kategori <span class="text-danger">*</span></label>
                                <select class="form-control" id="kategori" name="kategori" required>
                                    <option value="">Pilih Kategori</option>
                                    <option value="Fitur Baru">Fitur Baru</option>
                                    <option value="Pembaruan Desain">Pembaruan Desain</option>
                                    <option value="Peningkatan">Peningkatan</option>
                                    <option value="Perbaikan Bug">Perbaikan Bug</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Judul Perubahan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" required placeholder="Judul ringkas, misal: Peningkatan Menu Laporan" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi Rincian <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="8" required placeholder="Gunakan tag &bull; untuk poin, atau &lt;strong&gt; &lt;code&gt; untuk penekanan..."></textarea>
                        <small class="text-muted">Ketikkan poin rincian perubahan. Anda dapat menggunakan format tag HTML dasar (seperti <code>&lt;br&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;code&gt;</code>, dan <code>&bull;</code> untuk karakter bulet).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#dataTable').DataTable({
        "order": [[1, "desc"]]
    });
});

function openModal(id) {
    $('#dataForm')[0].reset();
    $('#dataId').val('');
    $('#tanggal').val('<?= date('Y-m-d') ?>');
    $('#modalTitle').html(id ? '<i class="fas fa-edit mr-2 text-warning"></i>Edit Log Perubahan' : '<i class="fas fa-plus mr-2 text-success"></i>Tambah Log Perubahan');
    $('#formModal').modal('show');
}

function editData(id) {
    $.post(window.location.href, { action: 'get', id: id }, function(r) {
        if (r.success) {
            openModal(id);
            const d = r.data;
            $('#dataId').val(d.id);
            $('#tanggal').val(d.tanggal);
            $('#kategori').val(d.kategori);
            $('#judul').val(d.judul);
            $('#deskripsi').val(d.deskripsi);
        } else {
            showError(r.message);
        }
    }, 'json').fail(function() {
        showError('Gagal memuat data log.');
    });
}

function deleteData(id) {
    confirmDelete(function() {
        $.post(window.location.href, { action: 'delete', id: id }, function(r) {
            if (r.success) {
                showSuccess(r.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showError(r.message);
            }
        }, 'json');
    });
}

$('#dataForm').on('submit', function(e) {
    e.preventDefault();
    const id = $('#dataId').val();
    const formData = $(this).serialize() + '&action=' + (id ? 'update' : 'create');
    $.post(window.location.href, formData, function(r) {
        if (r.success) {
            $('#formModal').modal('hide');
            showSuccess(r.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showError(r.message);
        }
    }, 'json');
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
