<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
$activeMenu = 'muzaki_upz_history';
$pageTitle = 'Riwayat UPZ Muzaki';
$pageDescription = 'Kelola riwayat perpindahan UPZ muzaki';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Riwayat UPZ</li>';
$conn = db();
// Handle AJAX DataTables Server-side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('muzaki_upz_history h', [
        0 => 'h.id',
        1 => 'm.nama_muzaki',
        2 => 'u.nama_upz',
        3 => 'h.tanggal_bergabung',
        4 => 'h.tanggal_keluar',
        5 => 'h.keterangan'
    ], [
        'select' => 'h.*, m.nama_muzaki, u.nama_upz',
        'joins' => 'LEFT JOIN muzaki m ON h.id_muzaki=m.id LEFT JOIN upz u ON h.id_upz=u.id',
        'search_columns' => ['m.nama_muzaki', 'u.nama_upz', 'h.keterangan'],
        'row_formatter' => function($row, $no) {
            $actionButtons = '
                <div class="text-center">
                    <button class="btn btn-warning btn-xs" onclick="editData(' . $row['id'] . ')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-xs" onclick="deleteData(' . $row['id'] . ')"><i class="fas fa-trash"></i></button>
                </div>
            ';
            
            $keluarStr = $row['tanggal_keluar'] ? date('d/m/Y', strtotime($row['tanggal_keluar'])) : '<span class="badge badge-outline-success font-weight-bold" style="border:1px solid #28a745; color:#28a745; background-color:rgba(40,167,69,0.05);">Aktif</span>';
            
            return [
                $no,
                '<strong>' . clean($row['nama_muzaki'] ?? '-') . '</strong>',
                clean($row['nama_upz'] ?? '-'),
                date('d/m/Y', strtotime($row['tanggal_bergabung'])),
                $keluarStr,
                '<small>' . clean($row['keterangan']) . '</small>',
                $actionButtons
            ];
        }
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $id_m=(int)($_POST['id_muzaki']??0); $id_u=(int)($_POST['id_upz']??0);
        $tgl_gabung=$_POST['tanggal_bergabung']??''; $tgl_keluar=$_POST['tanggal_keluar']??null;
        $ket=trim($_POST['keterangan']??'');
        if(!$id_m||!$id_u||!$tgl_gabung){echo json_encode(['success'=>false,'message'=>'Muzaki, UPZ, dan tanggal bergabung wajib diisi']);exit;}
        $tgl_keluar = $tgl_keluar ?: null;
        $stmt=$conn->prepare('INSERT INTO muzaki_upz_history (id_muzaki, id_upz, tanggal_bergabung, tanggal_keluar, keterangan) VALUES (?,?,?,?,?)');
        $stmt->bind_param('iisss',$id_m,$id_u,$tgl_gabung,$tgl_keluar,$ket);
        echo json_encode($stmt->execute()?['success'=>true,'message'=>'Data berhasil ditambahkan']:['success'=>false,'message'=>'Gagal: '.$conn->error]);
        $stmt->close(); exit;
    }
    if ($action === 'update') {
        $id=(int)($_POST['id']??0); $id_m=(int)($_POST['id_muzaki']??0); $id_u=(int)($_POST['id_upz']??0);
        $tgl_gabung=$_POST['tanggal_bergabung']??''; $tgl_keluar=$_POST['tanggal_keluar']??null;
        $ket=trim($_POST['keterangan']??'');
        $tgl_keluar = $tgl_keluar ?: null;
        $stmt=$conn->prepare('UPDATE muzaki_upz_history SET id_muzaki=?, id_upz=?, tanggal_bergabung=?, tanggal_keluar=?, keterangan=? WHERE id=?');
        $stmt->bind_param('iisssi',$id_m,$id_u,$tgl_gabung,$tgl_keluar,$ket,$id);
        echo json_encode($stmt->execute()?['success'=>true,'message'=>'Data berhasil diperbarui']:['success'=>false,'message'=>'Gagal']);
        $stmt->close(); exit;
    }
    if ($action === 'delete') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('DELETE FROM muzaki_upz_history WHERE id=?');
        $stmt->bind_param('i',$id);
        echo json_encode($stmt->execute()?['success'=>true,'message'=>'Data berhasil dihapus']:['success'=>false,'message'=>'Gagal']);
        $stmt->close(); exit;
    }
    if ($action === 'get') {
        $id=(int)($_POST['id']??0);
        $stmt=$conn->prepare('SELECT * FROM muzaki_upz_history WHERE id=?');
        $stmt->bind_param('i',$id); $stmt->execute();
        echo json_encode(['success'=>true,'data'=>$stmt->get_result()->fetch_assoc()]);
        $stmt->close(); exit;
    }
}

$muzakiList = get_muzaki_list();
$upzList = get_upz_list();
include __DIR__ . '/../../inc/header.php';
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat UPZ Muzaki</h3>
        <div class="card-tools"><button type="button" class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-plus mr-1"></i>Tambah Data</button></div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead><tr><th width="4%">No</th><th>Muzaki</th><th>UPZ</th><th>Tgl Bergabung</th><th>Tgl Keluar</th><th>Keterangan</th><th width="10%">Aksi</th></tr></thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Riwayat UPZ</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="dataForm"><div class="modal-body"><input type="hidden" name="id" id="dataId">
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Muzaki <span class="text-danger">*</span></label>
                <select class="form-control select2" id="id_muzaki" name="id_muzaki" required>
                    <option value="">Pilih Muzaki</option>
                    <?php foreach($muzakiList as $m): ?><option value="<?=$m['id']?>"><?=clean($m['nama_muzaki'])?> (<?=clean($m['no_ktp']??'-')?>)</option><?php endforeach; ?>
                </select>
            </div></div>
            <div class="col-md-6"><div class="form-group"><label>UPZ <span class="text-danger">*</span></label>
                <select class="form-control" id="id_upz" name="id_upz" required>
                    <option value="">Pilih UPZ</option>
                    <?php foreach($upzList as $u): ?><option value="<?=$u['id']?>"><?=clean($u['nama_upz'])?></option><?php endforeach; ?>
                </select>
            </div></div>
        </div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Tanggal Bergabung <span class="text-danger">*</span></label><input type="date" class="form-control" id="tanggal_bergabung" name="tanggal_bergabung" required></div></div>
            <div class="col-md-6"><div class="form-group"><label>Tanggal Keluar</label><input type="date" class="form-control" id="tanggal_keluar" name="tanggal_keluar"></div></div>
        </div>
        <div class="form-group"><label>Keterangan</label><textarea class="form-control" id="keterangan" name="keterangan" rows="2"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan</button></div></form>
</div></div></div>
<script>
$(function(){
    $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            type: 'GET'
        },
        order: [[3, 'desc']], // Sort by tanggal_bergabung column by default
        columnDefs: [
            { targets: [0, 6], orderable: false }, // Disable sorting on No and Aksi
            { targets: [0, 6], className: 'text-center' }
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
    initSelect2('.select2','Pilih Muzaki', '#formModal');
});
function openModal(id){$('#dataForm')[0].reset();$('#dataId').val('');$('#id_muzaki').val('').trigger('change');$('#modalTitle').html(id?'<i class="fas fa-edit mr-2"></i>Edit Riwayat':'<i class="fas fa-plus mr-2"></i>Tambah Riwayat');$('#formModal').modal('show');}
function editData(id){$.post(window.location.href,{action:'get',id:id},function(r){if(r.success){openModal(id);var d=r.data;$('#dataId').val(d.id);$('#id_muzaki').val(d.id_muzaki).trigger('change');$('#id_upz').val(d.id_upz);$('#tanggal_bergabung').val(d.tanggal_bergabung);$('#tanggal_keluar').val(d.tanggal_keluar||'');$('#keterangan').val(d.keterangan);}},'json');}
function deleteData(id){confirmDelete(function(){$.post(window.location.href,{action:'delete',id:id},function(r){if(r.success){showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});}
$('#dataForm').on('submit',function(e){e.preventDefault();const id=$('#dataId').val();$.post(window.location.href,$(this).serialize()+'&action='+(id?'update':'create'),function(r){if(r.success){$('#formModal').modal('hide');showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});
</script>
<?php include __DIR__ . '/../../inc/footer.php'; ?>
