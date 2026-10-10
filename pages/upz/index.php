<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
$activeMenu = 'upz';
$pageTitle = 'Data UPZ';
$pageDescription = 'Kelola Unit Pengumpul Zakat (UPZ)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">UPZ</li>';
$conn = db();
// Handle AJAX DataTables Server-side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('upz u', [
        0 => 'u.id',
        1 => 'u.nama_upz',
        2 => 'ku.nama_kategori',
        3 => 'u.alamat',
        4 => 'u.kontak'
    ], [
        'select' => 'u.*, ku.nama_kategori',
        'joins' => 'LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id',
        'search_columns' => ['u.nama_upz', 'ku.nama_kategori', 'u.alamat', 'u.kontak'],
        'row_formatter' => function($row, $no) {
            $actionButtons = '
                <div class="text-center">
                    <button class="btn btn-warning btn-xs" onclick="editData(' . $row['id'] . ')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteData(' . $row['id'] . ')"><i class="fas fa-trash"></i></button>
                </div>
            ';
            $badgeHtml = $row['nama_kategori'] ? '<span class="badge badge-outline-success font-weight-bold" style="border:1px solid #0d9f4f; color:#0d9f4f; background-color:rgba(13,159,79,0.05);">' . clean($row['nama_kategori']) . '</span>' : '<span class="badge badge-outline-secondary font-weight-normal" style="border:1px solid #6c757d; color:#6c757d; background-color:rgba(108,117,125,0.05);">-</span>';
            return [
                $no,
                '<strong>' . clean($row['nama_upz']) . '</strong>',
                $badgeHtml,
                clean($row['alamat']),
                clean($row['kontak']),
                $actionButtons
            ];
        }
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $nama = trim($_POST['nama_upz']??'');
        $id_kategori = (int)($_POST['id_kategori_upz'] ?? 0) ?: null;
        $alamat = trim($_POST['alamat']??'');
        $kontak = trim($_POST['kontak']??'');
        if ($nama==='') { echo json_encode(['success'=>false,'message'=>'Nama UPZ wajib diisi']); exit; }
        $stmt = $conn->prepare('INSERT INTO upz (nama_upz, id_kategori_upz, alamat, kontak) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('siss', $nama, $id_kategori, $alamat, $kontak);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_UPZ', 'upz', $inserted_id, "Nama UPZ: $nama");
            echo json_encode(['success'=>true,'message'=>'UPZ berhasil ditambahkan']);
        } else { echo json_encode(['success'=>false,'message'=>safe_db_error($conn->error, 'Gagal menambahkan UPZ.')]); }
        $stmt->close(); exit;
    }
    if ($action === 'update') {
        $id=(int)($_POST['id']??0);
        $nama=trim($_POST['nama_upz']??'');
        $id_kategori = (int)($_POST['id_kategori_upz'] ?? 0) ?: null;
        $alamat=trim($_POST['alamat']??'');
        $kontak=trim($_POST['kontak']??'');
        $stmt=$conn->prepare('UPDATE upz SET nama_upz=?, id_kategori_upz=?, alamat=?, kontak=? WHERE id=?');
        $stmt->bind_param('sissi',$nama,$id_kategori,$alamat,$kontak,$id);
        if ($stmt->execute()) {
            log_audit('UPDATE_UPZ', 'upz', $id, "Nama UPZ: $nama");
            echo json_encode(['success'=>true,'message'=>'UPZ berhasil diperbarui']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal']);
        }
        $stmt->close(); exit;
    }
    if ($action === 'delete') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('DELETE FROM upz WHERE id=?');
        $stmt->bind_param('i',$id);
        if ($stmt->execute()) {
            log_audit('DELETE_UPZ', 'upz', $id, "Deleted UPZ ID: $id");
            echo json_encode(['success'=>true,'message'=>'UPZ berhasil dihapus']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal menghapus. UPZ mungkin masih digunakan.']);
        }
        $stmt->close(); exit;
    }
    if ($action === 'get') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('SELECT * FROM upz WHERE id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->get_result()->fetch_assoc()]);
        $stmt->close(); exit;
    }
}

$kategoriList = get_kategori_upz_list();
include __DIR__ . '/../../inc/header.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building mr-2"></i>Data Unit Pengumpul Zakat (UPZ)</h3>
        <div class="card-tools"><button type="button" class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-plus mr-1"></i>Tambah UPZ</button></div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead><tr><th width="5%">No</th><th>Nama UPZ</th><th>Kategori</th><th>Alamat</th><th>Kontak</th><th width="12%">Aksi</th></tr></thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content border shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="modal-header bg-light"><h5 class="modal-title font-weight-bold" id="modalTitle"><i class="fas fa-plus mr-2 text-success"></i>Tambah UPZ</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="dataForm" class="no-preloader"><div class="modal-body"><input type="hidden" name="id" id="dataId">
        <div class="form-group"><label class="font-weight-bold">Nama UPZ <span class="text-danger">*</span></label><input type="text" class="form-control" id="nama_upz" name="nama_upz" required style="border: 1px solid #b8b8b8;"></div>
        <div class="form-group">
            <label class="font-weight-bold">Kategori UPZ</label>
            <select class="form-control" id="id_kategori_upz" name="id_kategori_upz" style="border: 1px solid #b8b8b8;">
                <option value="">Pilih Kategori</option>
                <?php foreach ($kategoriList as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= clean($k['nama_kategori']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label class="font-weight-bold">Alamat</label><textarea class="form-control" id="alamat" name="alamat" rows="3" style="border: 1px solid #b8b8b8;"></textarea></div>
        <div class="form-group"><label class="font-weight-bold">Kontak</label><input type="text" class="form-control" id="kontak" name="kontak" placeholder="No. HP/Telepon" style="border: 1px solid #b8b8b8;"></div>
    </div><div class="modal-footer bg-light"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button><button type="submit" class="btn btn-outline-success font-weight-bold" id="btnSaveUpz"><i class="fas fa-save mr-1"></i>Simpan</button></div></form>
</div></div></div>
<script>
$(function(){
    var urlParams = new URLSearchParams(window.location.search);
    var defaultSearch = urlParams.has('search') ? urlParams.get('search') : '';

    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            type: 'GET'
        },
        search: {
            search: defaultSearch
        },
        order: [[1, 'asc']], // Sort by nama_upz column
        columnDefs: [
            { targets: [0, 5], orderable: false }, // Disable sorting on No and Aksi
            { targets: [0, 5], className: 'text-center' }
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

    $('#dataForm').on('submit', function(e) {
        e.preventDefault();
        if (window.hidePreloader) window.hidePreloader();
        var $btn = $('#btnSaveUpz');
        var btnOld = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Menyimpan...');

        const id = $('#dataId').val();
        $.post(window.location.href, $(this).serialize() + '&action=' + (id ? 'update' : 'create'), function(r) {
            $btn.prop('disabled', false).html(btnOld);
            if (window.hidePreloader) window.hidePreloader();
            if (r.success) {
                $('#formModal').modal('hide');
                showSuccess(r.message);
                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().ajax.reload(null, false);
                } else {
                    setTimeout(() => location.reload(), 800);
                }
            } else {
                showError(r.message);
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false).html(btnOld);
            if (window.hidePreloader) window.hidePreloader();
            showError('Terjadi kesalahan koneksi server.');
        });
    });
});

function openModal(id){$('#dataForm')[0].reset();$('#dataId').val('');$('#id_kategori_upz').val('');$('#modalTitle').html(id?'<i class="fas fa-edit mr-2 text-warning"></i>Edit UPZ':'<i class="fas fa-plus mr-2 text-success"></i>Tambah UPZ');$('#formModal').modal('show');}
function editData(id){$.post(window.location.href,{action:'get',id:id},function(r){if(r.success){openModal(id);$('#dataId').val(r.data.id);$('#nama_upz').val(r.data.nama_upz);$('#id_kategori_upz').val(r.data.id_kategori_upz || '');$('#alamat').val(r.data.alamat);$('#kontak').val(r.data.kontak);}},'json');}
function deleteData(id){confirmDelete(function(){$.post(window.location.href,{action:'delete',id:id},function(r){if(window.hidePreloader) window.hidePreloader(); if(r.success){showSuccess(r.message); if ($.fn.DataTable.isDataTable('#dataTable')) { $('#dataTable').DataTable().ajax.reload(null, false); } else { setTimeout(()=>location.reload(),800); }}else{showError(r.message);}},'json').fail(function(){ if(window.hidePreloader) window.hidePreloader(); showError('Terjadi kesalahan koneksi server.'); });});}
</script>
<?php include __DIR__ . '/../../inc/footer.php'; ?>
