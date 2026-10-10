<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'sub_jenis_pembayaran';
$pageTitle = 'Sub Jenis Pembayaran';
$pageDescription = 'Kelola data sub jenis pembayaran';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Sub Jenis Pembayaran</li>';

$conn = db();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama = trim($_POST['nama_sub_jenis'] ?? '');
        $jenis_id = (int)($_POST['id_jenis_pembayaran'] ?? 0);
        if ($nama === '' || $jenis_id <= 0) { echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']); exit; }
        $stmt = $conn->prepare('INSERT INTO sub_jenis_pembayaran (id_jenis_pembayaran, nama_sub_jenis) VALUES (?, ?)');
        $stmt->bind_param('is', $jenis_id, $nama);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_SUB_JENIS_PEMBAYARAN', 'sub_jenis_pembayaran', $inserted_id, "Nama: $nama, ID Jenis: $jenis_id");
            echo json_encode(['success' => true, 'message' => 'Data berhasil ditambahkan']);
        }
        else { echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]); }
        $stmt->close(); exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_sub_jenis'] ?? '');
        $jenis_id = (int)($_POST['id_jenis_pembayaran'] ?? 0);
        if ($id <= 0 || $nama === '' || $jenis_id <= 0) { echo json_encode(['success' => false, 'message' => 'Data tidak valid']); exit; }
        $stmt = $conn->prepare('UPDATE sub_jenis_pembayaran SET id_jenis_pembayaran = ?, nama_sub_jenis = ? WHERE id = ?');
        $stmt->bind_param('isi', $jenis_id, $nama, $id);
        if ($stmt->execute()) {
            log_audit('UPDATE_SUB_JENIS_PEMBAYARAN', 'sub_jenis_pembayaran', $id, "Nama: $nama, ID Jenis: $jenis_id");
            echo json_encode(['success' => true, 'message' => 'Data berhasil diperbarui']);
        }
        else { echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]); }
        $stmt->close(); exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM sub_jenis_pembayaran WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_SUB_JENIS_PEMBAYARAN', 'sub_jenis_pembayaran', $id, "Deleted ID: $id");
            echo json_encode(['success' => true, 'message' => 'Data berhasil dihapus']);
        }
        else { echo json_encode(['success' => false, 'message' => 'Gagal menghapus. Data mungkin masih digunakan.']); }
        $stmt->close(); exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT * FROM sub_jenis_pembayaran WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]); exit;
    }
}

$jenisList = get_jenis_pembayaran_list();
$data = $conn->query('SELECT s.*, j.nama_jenis FROM sub_jenis_pembayaran s LEFT JOIN jenis_pembayaran j ON s.id_jenis_pembayaran = j.id ORDER BY j.nama_jenis ASC, s.nama_sub_jenis ASC');

include __DIR__ . '/../../inc/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Data Sub Jenis Pembayaran</h3>
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
                    <th>Jenis Pembayaran</th>
                    <th>Nama Sub Jenis</th>
                    <th width="15%">Dibuat</th>
                    <th width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><span class="badge badge-info"><?= clean($row['nama_jenis'] ?? '-') ?></span></td>
                    <td><?= clean($row['nama_sub_jenis']) ?></td>
                    <td><small><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small></td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-xs" onclick="editData(<?= $row['id'] ?>)"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-xs" onclick="deleteData(<?= $row['id'] ?>)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Sub Jenis Pembayaran</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="dataForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="dataId">
                    <div class="form-group">
                        <label>Jenis Pembayaran <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_jenis_pembayaran" name="id_jenis_pembayaran" required>
                            <option value="">Pilih Jenis Pembayaran</option>
                            <?php foreach ($jenisList as $j): ?>
                            <option value="<?= $j['id'] ?>"><?= clean($j['nama_jenis']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Sub Jenis <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_sub_jenis" name="nama_sub_jenis" required placeholder="Contoh: Zakat Maal Perorangan">
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
$(function() { $('#dataTable').DataTable(); });

function openModal(id) {
    $('#dataForm')[0].reset(); $('#dataId').val('');
    $('#modalTitle').html(id ? '<i class="fas fa-edit mr-2"></i>Edit Sub Jenis' : '<i class="fas fa-plus mr-2"></i>Tambah Sub Jenis');
    $('#formModal').modal('show');
}

function editData(id) {
    $.post(window.location.href, {action:'get',id:id}, function(res) {
        if(res.success) { openModal(id); $('#dataId').val(res.data.id); $('#id_jenis_pembayaran').val(res.data.id_jenis_pembayaran); $('#nama_sub_jenis').val(res.data.nama_sub_jenis); }
    },'json');
}

function deleteData(id) {
    confirmDelete(function() {
        $.post(window.location.href, {action:'delete',id:id}, function(res) {
            if(res.success){showSuccess(res.message);setTimeout(()=>location.reload(),1000);}else{showError(res.message);}
        },'json');
    });
}

$('#dataForm').on('submit', function(e) {
    e.preventDefault();
    const id=$('#dataId').val();
    $.post(window.location.href, $(this).serialize()+'&action='+(id?'update':'create'), function(res) {
        if(res.success){$('#formModal').modal('hide');showSuccess(res.message);setTimeout(()=>location.reload(),1000);}else{showError(res.message);}
    },'json');
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
