<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'audit_log';
$pageTitle = 'Audit Trail';
$pageDescription = 'Riwayat aktivitas pengguna di dalam sistem';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Audit Trail</li>';
$conn = db();

// Filters
$start_date = $conn->real_escape_string($_GET['start_date'] ?? date('Y-m-01'));
$end_date = $conn->real_escape_string($_GET['end_date'] ?? date('Y-m-d'));
$user_id = (int)($_GET['user_id'] ?? 0);

$where_sql = "DATE(al.created_at) BETWEEN '$start_date' AND '$end_date'";
if ($user_id > 0) {
    $where_sql .= " AND al.user_id = $user_id";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('audit_log al', [
        0 => 'al.created_at',
        1 => 'u.full_name',
        2 => 'al.action',
        3 => 'al.table_name',
        4 => 'al.notes',
        5 => 'al.ip_address'
    ], [
        'select' => 'al.*, u.full_name, u.username',
        'joins' => 'LEFT JOIN users u ON al.user_id = u.id',
        'where' => $where_sql,
        'search_columns' => ['al.action', 'al.table_name', 'al.notes', 'al.ip_address', 'u.full_name', 'u.username'],
        'row_formatter' => function($row, $no) {
            $badge = 'secondary';
            if (stripos($row['action'], 'CREATE') !== false) $badge = 'success';
            elseif (stripos($row['action'], 'UPDATE') !== false) $badge = 'warning';
            elseif (stripos($row['action'], 'DELETE') !== false) $badge = 'danger';
            elseif (stripos($row['action'], 'LOGIN') !== false) $badge = 'info';
            
            $badgeStyle = '';
            if ($badge === 'success') $badgeStyle = 'border:1px solid #28a745; color:#28a745; background-color:rgba(40,167,69,0.05);';
            elseif ($badge === 'warning') $badgeStyle = 'border:1px solid #ffc107; color:#ffc107; background-color:rgba(255,193,7,0.05);';
            elseif ($badge === 'danger') $badgeStyle = 'border:1px solid #dc3545; color:#dc3545; background-color:rgba(220,53,69,0.05);';
            elseif ($badge === 'info') $badgeStyle = 'border:1px solid #17a2b8; color:#17a2b8; background-color:rgba(23,162,184,0.05);';
            else $badgeStyle = 'border:1px solid #6c757d; color:#6c757d; background-color:rgba(108,117,125,0.05);';

            $badgeHtml = '<span class="badge badge-outline-' . $badge . ' font-weight-bold" style="' . $badgeStyle . '">' . clean($row['action']) . '</span>';
            
            return [
                date('d/m/Y H:i:s', strtotime($row['created_at'])),
                '<strong>' . clean($row['full_name'] ?: 'System') . '</strong><br><small class="text-muted">' . clean($row['username'] ?? '') . '</small>',
                $badgeHtml,
                '<code>' . clean($row['table_name']) . '</code>',
                '<small>' . clean($row['notes']) . '</small>',
                '<small class="text-muted">' . clean($row['ip_address']) . '</small>'
            ];
        }
    ]);
}

$usersList = $conn->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold"><i class="fas fa-filter mr-2"></i>Filter Log</h3>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Mulai Tanggal</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Hingga Tanggal</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label>Pengguna</label>
                            <select name="user_id" class="form-control select2">
                                <option value="0">Semua Pengguna</option>
                                <?php foreach ($usersList as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= $user_id == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title text-bold"><i class="fas fa-history mr-2"></i>Log Aktivitas</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="logTable" style="font-size: 0.85rem;">
                        <thead class="bg-light">
                            <tr>
                                <th width="15%">Waktu</th>
                                <th width="15%">Pengguna</th>
                                <th width="15%">Aksi</th>
                                <th width="15%">Tabel</th>
                                <th>Keterangan / Notes</th>
                                <th width="10%">IP Alamat</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $("#logTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            type: 'GET'
        },
        order: [[0, "desc"]],
        columnDefs: [
            { targets: [4], orderable: false }
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
    $(".select2").select2({ theme: "bootstrap4" });
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
