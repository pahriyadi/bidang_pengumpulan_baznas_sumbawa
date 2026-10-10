<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth('admin');
$activeMenu = 'users';
$pageTitle = 'Manajemen Pengguna';
$pageDescription = 'Kelola pengguna sistem';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Pengguna</li>';
$conn = db();
// Handle AJAX DataTables Server-side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('users', [
        0 => 'id',
        1 => 'username',
        2 => 'full_name',
        3 => 'email',
        4 => 'role',
        5 => 'is_active',
        6 => 'created_at'
    ], [
        'select' => 'id, username, role, full_name, email, is_active, created_at',
        'search_columns' => ['username', 'full_name', 'email', 'role'],
        'row_formatter' => function($row, $no) {
            $currentUser = current_user();
            $isSelf = ((int)$row['id'] === (int)$currentUser['id']);
            
            $deleteBtn = '';
            if (!$isSelf) {
                $deleteBtn = '<button class="btn btn-danger btn-xs" onclick="deleteData(' . $row['id'] . ')"><i class="fas fa-trash"></i></button>';
            }
            
            $actionButtons = '
                <div class="text-center">
                    <button class="btn btn-warning btn-xs" onclick="editData(' . $row['id'] . ')"><i class="fas fa-edit"></i></button>
                    ' . $deleteBtn . '
                </div>
            ';
            
            $roleBadge = $row['role'] === 'admin' ? '<span class="badge badge-outline-success font-weight-bold" style="border:1px solid #28a745; color:#28a745; background-color:rgba(40,167,69,0.05);">' . clean($row['role']) . '</span>' : '<span class="badge badge-outline-secondary" style="border:1px solid #6c757d; color:#6c757d; background-color:rgba(108,117,125,0.05);">' . clean($row['role']) . '</span>';
            
            $statusBadge = $row['is_active'] ? '<span class="badge badge-outline-success font-weight-bold" style="border:1px solid #28a745; color:#28a745; background-color:rgba(40,167,69,0.05);">Aktif</span>' : '<span class="badge badge-outline-danger font-weight-bold" style="border:1px solid #dc3545; color:#dc3545; background-color:rgba(220,53,69,0.05);">Nonaktif</span>';
            
            $createdAt = !empty($row['created_at']) ? '<small>' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</small>' : '<small class="text-muted">-</small>';
            
            return [
                $no,
                '<strong>' . clean($row['username']) . '</strong>',
                clean($row['full_name'] ?? '-'),
                clean($row['email'] ?? '-'),
                $roleBadge,
                $statusBadge,
                $createdAt,
                $actionButtons
            ];
        }
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $is_active = (int)($_POST['is_active'] ?? 1);

        if ($username === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi']);
            exit;
        }

        $pwdErr = '';
        if (!validate_password_strength($password, $pwdErr)) {
            echo json_encode(['success' => false, 'message' => $pwdErr]);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password, role, full_name, email, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssi', $username, $hashed, $role, $full_name, $email, $is_active);
        if ($stmt->execute()) {
            $inserted_id = $conn->insert_id;
            log_audit('CREATE_USER', 'users', $inserted_id, "Username: $username, Role: $role");
            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal: Username mungkin sudah digunakan.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $is_active = (int)($_POST['is_active'] ?? 1);

        if ($password !== '') {
            $pwdErr = '';
            if (!validate_password_strength($password, $pwdErr)) {
                echo json_encode(['success' => false, 'message' => $pwdErr]);
                exit;
            }
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET username=?, password=?, role=?, full_name=?, email=?, is_active=? WHERE id=?');
            $stmt->bind_param('sssssii', $username, $hashed, $role, $full_name, $email, $is_active, $id);
        } else {
            $stmt = $conn->prepare('UPDATE users SET username=?, role=?, full_name=?, email=?, is_active=? WHERE id=?');
            $stmt->bind_param('ssssii', $username, $role, $full_name, $email, $is_active, $id);
        }
        if ($stmt->execute()) {
            log_audit('UPDATE_USER', 'users', $id, "Username: $username, Role: $role");
            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal memperbarui pengguna. Username mungkin sudah digunakan.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $currentUser = current_user();
        if ($id === (int)$currentUser['id']) {
            echo json_encode(['success' => false, 'message' => 'Tidak bisa menghapus akun sendiri!']);
            exit;
        }
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_USER', 'users', $id, "Deleted user ID: $id");
            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT id, username, role, full_name, email, is_active FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->get_result()->fetch_assoc()]);
        $stmt->close();
        exit;
    }
}

include __DIR__ . '/../../inc/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i>Manajemen Pengguna</h3>
        <div class="card-tools"><button type="button" class="btn btn-primary btn-sm" onclick="openModal()"><i class="fas fa-plus mr-1"></i>Tambah Pengguna</button></div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead><tr><th width="4%">No</th><th>Username</th><th>Nama Lengkap</th><th>Email</th><th>Role</th><th>Status</th><th>Tanggal Dibuat</th><th width="10%">Aksi</th></tr></thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Pengguna</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form id="dataForm"><div class="modal-body"><input type="hidden" name="id" id="dataId">
        <div class="form-group"><label>Username <span class="text-danger">*</span></label><input type="text" class="form-control" id="username" name="username" required></div>
        <div class="form-group"><label>Password <span class="text-danger" id="pwdRequired">*</span></label><input type="password" class="form-control" id="password" name="password"><small class="text-muted" id="pwdHint" style="display:none;">Kosongkan jika tidak ingin mengubah password</small></div>
        <div class="form-group"><label>Nama Lengkap</label><input type="text" class="form-control" id="full_name" name="full_name"></div>
        <div class="form-group"><label>Email</label><input type="email" class="form-control" id="email" name="email"></div>
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Role</label><select class="form-control" id="role" name="role"><option value="user">User</option><option value="admin">Admin</option></select></div></div>
            <div class="col-md-6"><div class="form-group"><label>Status</label><select class="form-control" id="is_active" name="is_active"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div></div>
        </div>
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
        order: [[1, 'asc']], // Order by username ascending by default
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
function openModal(id){
    $('#dataForm')[0].reset();$('#dataId').val('');
    if(id){$('#password').removeAttr('required');$('#pwdRequired').hide();$('#pwdHint').show();$('#modalTitle').html('<i class="fas fa-edit mr-2"></i>Edit Pengguna');}
    else{$('#password').attr('required','required');$('#pwdRequired').show();$('#pwdHint').hide();$('#modalTitle').html('<i class="fas fa-plus mr-2"></i>Tambah Pengguna');}
    $('#formModal').modal('show');
}
function editData(id){$.post(window.location.href,{action:'get',id:id},function(r){if(r.success){openModal(id);var d=r.data;$('#dataId').val(d.id);$('#username').val(d.username);$('#full_name').val(d.full_name);$('#email').val(d.email);$('#role').val(d.role);$('#is_active').val(d.is_active);}},'json');}
function deleteData(id){confirmDelete(function(){$.post(window.location.href,{action:'delete',id:id},function(r){if(r.success){showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});}
$('#dataForm').on('submit',function(e){e.preventDefault();const id=$('#dataId').val();$.post(window.location.href,$(this).serialize()+'&action='+(id?'update':'create'),function(r){if(r.success){$('#formModal').modal('hide');showSuccess(r.message);setTimeout(()=>location.reload(),1000);}else{showError(r.message);}},'json');});
</script>
<?php include __DIR__ . '/../../inc/footer.php'; ?>
