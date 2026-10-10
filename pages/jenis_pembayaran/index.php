<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'jenis_pembayaran';
$pageTitle = 'Jenis Pembayaran';
$pageDescription = 'Kelola data jenis pembayaran (Zakat, Infak, Sedekah, dll)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Jenis Pembayaran</li>';

$conn = db();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama = trim($_POST['nama_jenis'] ?? '');
        if ($nama === '') { echo json_encode(['success' => false, 'message' => 'Nama jenis tidak boleh kosong']); exit; }
        $stmt = $conn->prepare('INSERT INTO jenis_pembayaran (nama_jenis) VALUES (?)');
        $stmt->bind_param('s', $nama);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_JENIS_PEMBAYARAN', 'jenis_pembayaran', $inserted_id, "Nama: $nama");
            echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan']);
        }
        else { echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]); }
        $stmt->close(); exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_jenis'] ?? '');
        if ($id <= 0 || $nama === '') { echo json_encode(['success' => false, 'message' => 'Data tidak valid']); exit; }
        $stmt = $conn->prepare('UPDATE jenis_pembayaran SET nama_jenis = ? WHERE id = ?');
        $stmt->bind_param('si', $nama, $id);
        if ($stmt->execute()) {
            log_audit('UPDATE_JENIS_PEMBAYARAN', 'jenis_pembayaran', $id, "Nama: $nama");
            echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);
        }
        else { echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]); }
        $stmt->close(); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit; }
        $stmt = $conn->prepare('DELETE FROM jenis_pembayaran WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_JENIS_PEMBAYARAN', 'jenis_pembayaran', $id, "Deleted ID: $id");
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
        }
        else { echo json_encode(['success' => false, 'message' => 'Gagal menghapus. Data mungkin masih digunakan.']); }
        $stmt->close(); exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT * FROM jenis_pembayaran WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]); exit;
    }
}

$data = $conn->query('SELECT * FROM jenis_pembayaran ORDER BY nama_jenis ASC');

include __DIR__ . '/../../inc/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Data Jenis Pembayaran</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal()">
                <i class="fas fa-plus mr-1"></i>Tambah Data
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Jenis Pembayaran</th>
                    <th width="15%">Dibuat</th>
                    <th width="15%">Diperbarui</th>
                    <th width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
                <tr id="row-<?= $row['id'] ?>">
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= clean($row['nama_jenis']) ?></td>
                    <td><small><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small></td>
                    <td><small><?= date('d/m/Y H:i', strtotime($row['updated_at'])) ?></small></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-xs" onclick="editData(<?= $row['id'] ?>)" data-toggle="tooltip" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-xs" onclick="deleteData(<?= $row['id'] ?>)" data-toggle="tooltip" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="formModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Jenis Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="dataForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="dataId">
                    <div class="form-group">
                        <label for="nama_jenis"><i class="fas fa-tag mr-1"></i>Nama Jenis Pembayaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_jenis" name="nama_jenis" required placeholder="Contoh: Zakat, Infak, Sedekah">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSave"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() { $('#dataTable').DataTable(); });

function openModal(id = null) {
    $('#dataForm')[0].reset();
    $('#dataId').val('');
    if (id) {
        $('#modalTitle').html('<i class="fas fa-edit mr-2"></i>Edit Jenis Pembayaran');
    } else {
        $('#modalTitle').html('<i class="fas fa-plus mr-2"></i>Tambah Jenis Pembayaran');
    }
    $('#formModal').modal('show');
}

function editData(id) {
    $.post(window.location.href, { action: 'get', id: id }, function(res) {
        if (res.success) {
            openModal(id);
            $('#dataId').val(res.data.id);
            $('#nama_jenis').val(res.data.nama_jenis);
        }
    }, 'json');
}

function deleteData(id) {
    confirmDelete(function() {
        $.post(window.location.href, { action: 'delete', id: id }, function(res) {
            if (res.success) { showSuccess(res.message); setTimeout(() => location.reload(), 1000); }
            else { showError(res.message); }
        }, 'json');
    });
}

$('#dataForm').on('submit', function(e) {
    e.preventDefault();
    const id = $('#dataId').val();
    const formData = $(this).serialize() + '&action=' + (id ? 'update' : 'create');
    $.post(window.location.href, formData, function(res) {
        if (res.success) {
            $('#formModal').modal('hide');
            showSuccess(res.message);
            setTimeout(() => location.reload(), 1000);
        } else { showError(res.message); }
    }, 'json');
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
