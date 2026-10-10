<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
$activeMenu = 'kategori_upz';
$pageTitle = 'Kategori UPZ';
$pageDescription = 'Kelola data kategori sektoral Unit Pengumpul Zakat (UPZ)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Kategori UPZ</li>';
$conn = db();

// Handle AJAX DataTables Server-side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('kategori_upz', [
        0 => 'id',
        1 => 'nama_kategori',
        2 => 'keterangan',
        3 => 'urutan'
    ], [
        'search_columns' => ['nama_kategori', 'keterangan'],
        'row_formatter' => function($row, $no) {
            $actionButtons = '
                <div class="text-center">
                    <button class="btn btn-warning btn-xs" onclick="editData(' . $row['id'] . ')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteData(' . $row['id'] . ')"><i class="fas fa-trash"></i></button>
                </div>
            ';
            return [
                $no,
                '<strong>' . clean($row['nama_kategori']) . '</strong>',
                clean($row['keterangan'] ?: '-'),
                (int)$row['urutan'],
                $actionButtons
            ];
        }
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $ket = trim($_POST['keterangan'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 0);
        if ($nama === '') {
            echo json_encode(['success' => false, 'message' => 'Nama kategori wajib diisi']);
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO kategori_upz (nama_kategori, keterangan, urutan) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $nama, $ket, $urutan);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_KATEGORI_UPZ', 'kategori_upz', $inserted_id, "Nama: $nama, Urutan: $urutan");
            echo json_encode(['success' => true, 'message' => 'Kategori UPZ berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }
    
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_kategori'] ?? '');
        $ket = trim($_POST['keterangan'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 0);
        if ($nama === '') {
            echo json_encode(['success' => false, 'message' => 'Nama kategori wajib diisi']);
            exit;
        }
        $stmt = $conn->prepare('UPDATE kategori_upz SET nama_kategori=?, keterangan=?, urutan=? WHERE id=?');
        $stmt->bind_param('ssii', $nama, $ket, $urutan, $id);
        if ($stmt->execute()) {
            log_audit('UPDATE_KATEGORI_UPZ', 'kategori_upz', $id, "Nama: $nama, Urutan: $urutan");
            echo json_encode(['success' => true, 'message' => 'Kategori UPZ berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM kategori_upz WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_KATEGORI_UPZ', 'kategori_upz', $id, "Deleted Kategori UPZ ID: $id");
            echo json_encode(['success' => true, 'message' => 'Kategori UPZ berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus. Data mungkin masih digunakan oleh UPZ aktif.']);
        }
        $stmt->close();
        exit;
    }
    
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT * FROM kategori_upz WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_assoc()]);
        $stmt->close();
        exit;
    }
}

include __DIR__ . '/../../inc/header.php';
?>
<div class="card shadow-none border" style="border: 1px solid #b8b8b8;">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-tags mr-2 text-success"></i>Data Kategori UPZ</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-outline-success btn-sm" onclick="openModal()" style="border-radius: 4px;">
                <i class="fas fa-plus mr-1"></i>Tambah Kategori
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Kategori</th>
                    <th>Keterangan</th>
                    <th width="10%">Urutan</th>
                    <th width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-none border" style="border: 1px solid #b8b8b8;">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="modalTitle"><i class="fas fa-plus mr-2 text-success"></i>Tambah Kategori</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="dataForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <input type="hidden" name="id" id="dataId">
                    <div class="form-group">
                        <label class="font-weight-bold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required style="border: 1px solid #b8b8b8;">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3" style="border: 1px solid #b8b8b8;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Urutan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="urutan" name="urutan" required value="0" style="border: 1px solid #b8b8b8;">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-outline-success font-weight-bold"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function(){
    $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            type: 'GET'
        },
        order: [[3, 'asc']],
        columnDefs: [
            { targets: [0, 4], orderable: false },
            { targets: [0, 3, 4], className: 'text-center' }
        ],
        language: {
            processing: '<div class="text-center py-2"><i class="fa fa-spinner fa-spin fa-2x text-success"></i></div>',
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Berikutnya",
                previous: "Sebelumnya"
            }
        }
    });
});

function openModal(id) {
    $('#dataForm')[0].reset();
    $('#dataId').val('');
    $('#modalTitle').html(id ? '<i class="fas fa-edit mr-2 text-warning"></i>Edit Kategori UPZ' : '<i class="fas fa-plus mr-2 text-success"></i>Tambah Kategori UPZ');
    $('#formModal').modal('show');
}

function editData(id) {
    $.post(window.location.href, {action: 'get', id: id}, function(r) {
        if (r.success) {
            openModal(id);
            $('#dataId').val(r.data.id);
            $('#nama_kategori').val(r.data.nama_kategori);
            $('#keterangan').val(r.data.keterangan);
            $('#urutan').val(r.data.urutan);
        }
    }, 'json');
}

function deleteData(id) {
    confirmDelete(function() {
        $.post(window.location.href, {action: 'delete', id: id}, function(r) {
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
    $.post(window.location.href, $(this).serialize() + '&action=' + (id ? 'update' : 'create'), function(r) {
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
