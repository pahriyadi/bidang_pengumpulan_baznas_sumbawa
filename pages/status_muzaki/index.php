<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
$activeMenu = 'status_muzaki';
$pageTitle = 'Status Muzaki';
$pageDescription = 'Kelola data status muzaki (ASN, Swasta, Pedagang, dll)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Status Muzaki</li>';
$conn = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $nama = trim($_POST['nama_status'] ?? '');
        if ($nama === '') { echo json_encode(['success'=>false,'message'=>'Nama tidak boleh kosong']); exit; }
        $stmt = $conn->prepare('INSERT INTO status_muzaki (nama_status) VALUES (?)');
        $stmt->bind_param('s', $nama);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_STATUS_MUZAKI', 'status_muzaki', $inserted_id, "Nama: $nama");
            echo json_encode(['success'=>true,'message'=>'Data berhasil ditambahkan']);
        } else { echo json_encode(['success'=>false,'message'=>'Gagal: '.$conn->error]); }
        $stmt->close(); exit;
    }
    if ($action === 'update') {
        $id=(int)($_POST['id']??0); $nama=trim($_POST['nama_status']??'');
        $stmt=$conn->prepare('UPDATE status_muzaki SET nama_status=? WHERE id=?');
        $stmt->bind_param('si',$nama,$id);
        if ($stmt->execute()) {
            log_audit('UPDATE_STATUS_MUZAKI', 'status_muzaki', $id, "Nama: $nama");
            echo json_encode(['success'=>true,'message'=>'Data berhasil diperbarui']);
        } else { echo json_encode(['success'=>false,'message'=>'Gagal']); }
        $stmt->close(); exit;
    }
    if ($action === 'delete') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('DELETE FROM status_muzaki WHERE id=?');
        $stmt->bind_param('i',$id);
        if ($stmt->execute()) {
            log_audit('DELETE_STATUS_MUZAKI', 'status_muzaki', $id, "Deleted ID: $id");
            echo json_encode(['success'=>true,'message'=>'Data berhasil dihapus']);
        } else { echo json_encode(['success'=>false,'message'=>'Gagal menghapus.']); }
        $stmt->close(); exit;
    }
    if ($action === 'get') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('SELECT * FROM status_muzaki WHERE id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->get_result()->fetch_assoc()]);
        $stmt->close(); exit;
    }
}

$data = $conn->query('SELECT * FROM status_muzaki ORDER BY nama_status ASC');
include __DIR__ . '/../../inc/header.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-id-badge mr-2"></i>Data Status Muzaki</h3>
        <div class="card-tools"><button type="button" class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-plus mr-1"></i>Tambah Data</button></div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead><tr><th width="5%">No</th><th>Nama Status</th><th width="15%">Dibuat</th><th width="15%">Diperbarui</th><th width="12%">Aksi</th></tr></thead>
            <tbody>
                <?php $no=1; while($row=$data->fetch_assoc()): ?>
                <tr><td class="text-center"><?=$no++?></td><td><?=clean($row['nama_status'])?></td>
                <td><small><?=date('d/m/Y H:i',strtotime($row['created_at']))?></small></td>
                <td><small><?=date('d/m/Y H:i',strtotime($row['updated_at']))?></small></td>
                <td class="text-center">
                    <button class="btn btn-warning btn-xs" onclick="editData(<?=$row['id']?>)"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteData(<?=$row['id']?>)"><i class="fas fa-trash"></i></button>
                </td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Status Muzaki</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="dataForm"><div class="modal-body"><input type="hidden" name="id" id="dataId">
        <div class="form-group"><label>Nama Status <span class="text-danger">*</span></label><input type="text" class="form-control" id="nama_status" name="nama_status" required placeholder="Contoh: ASN, Swasta, Pedagang"></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div></form>
</div></div></div>
<script>
$(function(){$('#dataTable').DataTable();});
function openModal(id){$('#dataForm')[0].reset();$('#dataId').val('');$('#modalTitle').html(id?'<i class="fas fa-edit mr-2"></i>Edit Status':'<i class="fas fa-plus mr-2"></i>Tambah Status');$('#formModal').modal('show');}
function editData(id){$.post(window.location.href,{action:'get',id:id},function(r){if(r.success){openModal(id);$('#dataId').val(r.data.id);$('#nama_status').val(r.data.nama_status);}},'json');}
function deleteData(id){confirmDelete(function(){$.post(window.location.href,{action:'delete',id:id},function(r){if(r.success){showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});}
$('#dataForm').on('submit',function(e){e.preventDefault();const id=$('#dataId').val();$.post(window.location.href,$(this).serialize()+'&action='+(id?'update':'create'),function(r){if(r.success){$('#formModal').modal('hide');showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});
</script>
<?php include __DIR__ . '/../../inc/footer.php'; ?>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Status Muzaki</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="dataForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="dataId">
                    <div class="form-group mb-0">
                        <label>Nama Status <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_status" name="nama_status" required placeholder="Contoh: ASN, Swasta, Pedagang" style="border: 1px solid #b8b8b8;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function(){ $('#dataTable').DataTable(); });

function openModal(id){
    $('#dataForm')[0].reset();
    $('#dataId').val('');
    $('#modalTitle').html(id?'<i class="fas fa-edit mr-2"></i>Edit Status':'<i class="fas fa-plus mr-2"></i>Tambah Status');
    $('#formModal').modal('show');
}

function editData(id){
    $.post(window.location.href,{action:'get',id:id},function(r){
        if(r.success){
            openModal(id);
            $('#dataId').val(r.data.id);
            $('#nama_status').val(r.data.nama_status);
        }
    },'json');
}

function deleteData(id){
    confirmDelete(function(){
        $.post(window.location.href,{action:'delete',id:id},function(r){
            if(r.success){showSuccess(r.message);setTimeout(()=>location.reload(),1000);}
            else{showError(r.message);}
        },'json');
    });
}

$('#dataForm').on('submit',function(e){
    e.preventDefault();
    const id=$('#dataId').val();
    $.post(window.location.href,$(this).serialize()+'&action='+(id?'update':'create'),function(r){
        if(r.success){$('#formModal').modal('hide');showSuccess(r.message);setTimeout(()=>location.reload(),1000);}
        else{showError(r.message);}
    },'json');
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
