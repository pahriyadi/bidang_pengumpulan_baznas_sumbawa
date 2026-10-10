<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
$activeMenu = 'muzaki';
$pageTitle = 'Data Muzaki';
$pageDescription = 'Kelola data muzaki (pembayar zakat)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Muzaki</li>';
$conn = db();
// Handle AJAX DataTables Server-side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('muzaki m', [
        0 => 'm.id',
        1 => 'm.nama_muzaki',
        2 => 'm.no_ktp',
        3 => 'm.no_hp',
        4 => 's.nama_status',
        5 => 'u.nama_upz',
        6 => 'm.alamat'
    ], [
        'select' => 'm.*, s.nama_status, u.nama_upz',
        'joins' => 'LEFT JOIN status_muzaki s ON m.id_status_muzaki = s.id LEFT JOIN upz u ON m.id_upz_sekarang = u.id',
        'search_columns' => ['m.nama_muzaki', 'm.no_ktp', 'm.no_hp', 's.nama_status', 'u.nama_upz', 'm.alamat'],
        'row_formatter' => function($row, $no) {
            $actionButtons = '
                <div class="text-center">
                    <button class="btn btn-warning btn-xs" onclick="editData(' . $row['id'] . ')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteData(' . $row['id'] . ')"><i class="fas fa-trash"></i></button>
                </div>
            ';
            return [
                $no,
                '<strong>' . clean($row['nama_muzaki']) . '</strong>',
                clean($row['no_ktp'] ?? '-'),
                clean($row['no_hp'] ?? '-'),
                '<span class="badge badge-outline-info font-weight-normal" style="border:1px solid #17a2b8; color:#17a2b8; background-color:rgba(23,162,184,0.05);">' . clean($row['nama_status'] ?? '-') . '</span>',
                clean($row['nama_upz'] ?? '-'),
                '<small>' . clean($row['alamat'] ?? '') . '</small>',
                $actionButtons
            ];
        }
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama = trim($_POST['nama_muzaki']??'');
        $alamat = trim($_POST['alamat']??'');
        $no_ktp = trim($_POST['no_ktp']??'') ?: null;
        $no_hp = trim($_POST['no_hp']??'') ?: null;
        $id_status = (int)($_POST['id_status_muzaki']??0) ?: null;
        $id_upz = (int)($_POST['id_upz_sekarang']??0) ?: null;
        if ($nama==='') { echo json_encode(['success'=>false,'message'=>'Nama muzaki wajib diisi']); exit; }
        $stmt = $conn->prepare('INSERT INTO muzaki (nama_muzaki, alamat, no_ktp, no_hp, id_status_muzaki, id_upz_sekarang) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssii', $nama, $alamat, $no_ktp, $no_hp, $id_status, $id_upz);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_MUZAKI', 'muzaki', $inserted_id, "Nama: $nama, KTP: $no_ktp");
            echo json_encode(['success'=>true,'message'=>'Muzaki berhasil ditambahkan']);
        }
        else { echo json_encode(['success'=>false,'message'=>safe_db_error($conn->error, 'Gagal menambahkan muzaki.')]); }
        $stmt->close(); exit;
    }
    if ($action === 'update') {
        $id = (int)($_POST['id']??0);
        $nama = trim($_POST['nama_muzaki']??'');
        $alamat = trim($_POST['alamat']??'');
        $no_ktp = trim($_POST['no_ktp']??'') ?: null;
        $no_hp = trim($_POST['no_hp']??'') ?: null;
        $id_status = (int)($_POST['id_status_muzaki']??0) ?: null;
        $id_upz = (int)($_POST['id_upz_sekarang']??0) ?: null;
        $stmt = $conn->prepare('UPDATE muzaki SET nama_muzaki=?, alamat=?, no_ktp=?, no_hp=?, id_status_muzaki=?, id_upz_sekarang=? WHERE id=?');
        $stmt->bind_param('ssssiii', $nama, $alamat, $no_ktp, $no_hp, $id_status, $id_upz, $id);
        if ($stmt->execute()) {
            log_audit('UPDATE_MUZAKI', 'muzaki', $id, "Nama: $nama, KTP: $no_ktp");
            echo json_encode(['success'=>true,'message'=>'Muzaki berhasil diperbarui']);
        } else {
            echo json_encode(['success'=>false,'message'=>safe_db_error($conn->error, 'Gagal memperbarui muzaki.')]);
        }
        $stmt->close(); exit;
    }
    if ($action === 'delete') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('DELETE FROM muzaki WHERE id=?');
        $stmt->bind_param('i',$id);
        if ($stmt->execute()) {
            log_audit('DELETE_MUZAKI', 'muzaki', $id, "Deleted muzaki ID: $id");
            echo json_encode(['success'=>true,'message'=>'Muzaki berhasil dihapus']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Gagal menghapus. Data mungkin masih digunakan.']);
        }
        $stmt->close(); exit;
    }
    if ($action === 'get') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('SELECT * FROM muzaki WHERE id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->get_result()->fetch_assoc()]);
        $stmt->close(); exit;
    }
}

$statusList = get_status_muzaki_list();
$upzList = get_upz_list();

include __DIR__ . '/../../inc/header.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-tie mr-2"></i>Data Muzaki</h3>
        <div class="card-tools"><button type="button" class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-plus mr-1"></i>Tambah Muzaki</button></div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead><tr>
                <th width="4%">No</th><th>Nama Muzaki</th><th>No. KTP</th><th>No. HP</th><th>Status</th><th>UPZ</th><th>Alamat</th><th width="10%">Aksi</th>
            </tr></thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Muzaki</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="dataForm"><div class="modal-body"><input type="hidden" name="id" id="dataId">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label>Nama Muzaki <span class="text-danger">*</span></label><input type="text" class="form-control" id="nama_muzaki" name="nama_muzaki" required></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label>No. KTP</label><input type="text" class="form-control" id="no_ktp" name="no_ktp" maxlength="20"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label>No. HP</label><input type="text" class="form-control" id="no_hp" name="no_hp" maxlength="20"></div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label>Status Muzaki</label>
                    <select class="form-control" id="id_status_muzaki" name="id_status_muzaki">
                        <option value="">Pilih Status</option>
                        <?php foreach($statusList as $s): ?><option value="<?=$s['id']?>"><?=clean($s['nama_status'])?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group"><label>UPZ Sekarang</label>
                    <select class="form-control" id="id_upz_sekarang" name="id_upz_sekarang">
                        <option value="">Pilih UPZ</option>
                        <?php foreach($upzList as $u): ?><option value="<?=$u['id']?>"><?=clean($u['nama_upz'])?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group"><label>Alamat</label><textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea></div>
            </div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div></form>
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
        order: [[1, 'asc']], // Order by nama_muzaki column by default
        columnDefs: [
            { targets: [0, 7], orderable: false }, // Disable sorting on No and Aksi
            { targets: [0, 7], className: 'text-center' }
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
function openModal(id){$('#dataForm')[0].reset();$('#dataId').val('');$('#modalTitle').html(id?'<i class="fas fa-edit mr-2"></i>Edit Muzaki':'<i class="fas fa-plus mr-2"></i>Tambah Muzaki');$('#formModal').modal('show');}
function editData(id){$.post(window.location.href,{action:'get',id:id},function(r){if(r.success){openModal(id);var d=r.data;$('#dataId').val(d.id);$('#nama_muzaki').val(d.nama_muzaki);$('#no_ktp').val(d.no_ktp);$('#no_hp').val(d.no_hp);$('#id_status_muzaki').val(d.id_status_muzaki);$('#id_upz_sekarang').val(d.id_upz_sekarang);$('#alamat').val(d.alamat);}},'json');}
function deleteData(id){confirmDelete(function(){$.post(window.location.href,{action:'delete',id:id},function(r){if(r.success){showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});}
$('#dataForm').on('submit',function(e){e.preventDefault();const id=$('#dataId').val();$.post(window.location.href,$(this).serialize()+'&action='+(id?'update':'create'),function(r){if(r.success){$('#formModal').modal('hide');showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});
</script>
<?php include __DIR__ . '/../../inc/footer.php'; ?>
