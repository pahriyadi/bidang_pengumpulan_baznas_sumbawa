<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();
$activeMenu = 'transaksi';
$pageTitle = 'Data Transaksi';
$pageDescription = 'Kelola transaksi pengumpulan Zakat, Infak, dan Sedekah';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Transaksi</li>';
$conn = db();

// Handle AJAX DataTables Server-side
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    header('Content-Type: application/json');
    $draw = (int)$_GET['draw'];
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 10);
    $searchValue = trim($_GET['search']['value'] ?? '');
    
    // Sort parameters
    $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
    
    // Map Datatable columns to SQL columns
    $columns = [
        0 => 't.id',
        1 => 't.tanggal_transaksi',
        2 => 'nama_muzaki',
        3 => 'jp.nama_jenis',
        4 => 'mp.nama_metode',
        5 => 't.id_kategori_dana',
        6 => 't.jumlah_pembayaran',
        7 => 't.keterangan'
    ];
    
    $orderColumn = $columns[$orderColumnIndex] ?? 't.tanggal_transaksi';
    if (!in_array(strtolower($orderDir), ['asc', 'desc'])) {
        $orderDir = 'desc';
    }
    
    // Base SQL FROM and JOINs
    $sqlFrom = " FROM transaksi t
                 LEFT JOIN muzaki m ON t.id_muzaki = m.id
                 LEFT JOIN upz u ON t.id_upz = u.id
                 LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
                 LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
                 LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
                 LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id";
    
    // Search condition
    $where = " WHERE 1=1";
    $searchParams = [];
    $searchTypes = "";
    if ($searchValue !== '') {
        $where .= " AND (
            t.tanggal_transaksi LIKE ? OR 
            m.nama_muzaki LIKE ? OR 
            u.nama_upz LIKE ? OR 
            jp.nama_jenis LIKE ? OR 
            sjp.nama_sub_jenis LIKE ? OR 
            mp.nama_metode LIKE ? OR 
            t.jumlah_pembayaran LIKE ? OR 
            t.keterangan LIKE ?
        )";
        $searchWildcard = "%" . $searchValue . "%";
        for ($i = 0; $i < 8; $i++) {
            $searchParams[] = $searchWildcard;
            $searchTypes .= "s";
        }
    }
    
    // Get total records without filtering
    $totalRecords = $conn->query("SELECT COUNT(*) FROM transaksi")->fetch_row()[0] ?? 0;
    
    // Get total records with filtering
    $filteredQuery = "SELECT COUNT(*) " . $sqlFrom . $where;
    if ($searchValue !== '') {
        $stmt = $conn->prepare($filteredQuery);
        $stmt->bind_param($searchTypes, ...$searchParams);
        $stmt->execute();
        $totalRecordwithFilter = $stmt->get_result()->fetch_row()[0] ?? 0;
        $stmt->close();
    } else {
        $totalRecordwithFilter = $conn->query($filteredQuery)->fetch_row()[0] ?? 0;
    }
    
    // Fetch data with limit, offset, and order
    $sqlSelect = "SELECT t.*, 
                  COALESCE(m.nama_muzaki, u.nama_upz, 'UMUM') as nama_muzaki, 
                  u.nama_upz, jp.nama_jenis, sjp.nama_sub_jenis,
                  mp.nama_metode, sr.nama_sumber";
    
    $dataQuery = $sqlSelect . $sqlFrom . $where . " ORDER BY " . $orderColumn . " " . $orderDir . " LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($dataQuery);
    if ($searchValue !== '') {
        $types = $searchTypes . "ii";
        $bindParams = array_merge($searchParams, [$length, $start]);
        $stmt->bind_param($types, ...$bindParams);
    } else {
        $stmt->bind_param("ii", $length, $start);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    $no = $start + 1;
    $kategoriList = get_kategori_dana_list();
    $kategoriMap = [];
    foreach ($kategoriList as $k) {
        $kategoriMap[$k['id']] = $k['nama_kategori'];
    }
    
    while ($row = $result->fetch_assoc()) {
        // Map category
        $katIds = parse_kategori_dana_ids($row['id_kategori_dana']);
        $katNames = [];
        foreach ($katIds as $kid) {
            if (isset($kategoriMap[$kid])) {
                $katNames[] = '<span class="badge badge-outline-info font-weight-normal mr-1" style="font-size:75%; border:1px solid #17a2b8; color:#17a2b8; background-color:rgba(23,162,184,0.05);">' . clean($kategoriMap[$kid]) . '</span>';
            }
        }
        $kategoriStr = implode(' ', $katNames);
        
        $actionButtons = '
            <div class="text-center">
                <button class="btn btn-outline-info btn-xs" onclick="viewDetail(' . $row['id'] . ')" title="Detail">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-outline-primary btn-xs" onclick="editData(' . $row['id'] . ')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-outline-danger btn-xs" onclick="deleteData(' . $row['id'] . ')" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
        
        $muzakiStr = '<strong>' . clean($row['nama_muzaki']) . '</strong>';
        
        $jenisStr = '<span class="badge badge-outline-success font-weight-bold" style="border: 1px solid #28a745; color: #28a745; background-color: rgba(40,167,69,0.05);">' . clean($row['nama_jenis']) . '</span>';
        if ($row['nama_sub_jenis']) {
            $jenisStr .= '<br><small class="text-muted">' . clean($row['nama_sub_jenis']) . '</small>';
        }
        
        $metodeStr = '<span class="badge badge-outline-secondary" style="border:1px solid #6c757d; color:#6c757d; background-color:rgba(108,117,125,0.05);">' . clean($row['nama_metode']) . '</span>';
        if ($row['nama_sumber']) {
            $metodeStr .= '<br><small class="text-muted">' . clean($row['nama_sumber']) . '</small>';
        }
        
        $data[] = [
            $no++,
            '<strong>' . date('d/m/Y', strtotime($row['tanggal_transaksi'])) . '</strong>',
            $muzakiStr,
            $jenisStr,
            $metodeStr,
            $kategoriStr,
            '<div class="text-right"><strong>Rp ' . number_format($row['jumlah_pembayaran'], 0, ',', '.') . '</strong></div>',
            clean($row['keterangan'] ?? '-'),
            $actionButtons
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalRecordwithFilter,
        "data" => $data
    ]);
    exit;
}

// Handle AJAX for sub_jenis cascading
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'sub_jenis') {
    header('Content-Type: application/json');
    $jenis_id = (int)($_GET['jenis_id'] ?? 0);
    echo json_encode(get_sub_jenis_pembayaran_list($jenis_id));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF Token tidak valid.']);
        exit;
    }
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $tgl = $_POST['tanggal_transaksi'] ?? '';
        $id_muzaki = (int)($_POST['id_muzaki'] ?? 0) ?: null;
        $id_upz = (int)($_POST['id_upz'] ?? 0) ?: null;
        $id_jenis = (int)($_POST['id_jenis_pembayaran'] ?? 0);
        $id_sub = (int)($_POST['id_sub_jenis_pembayaran'] ?? 0) ?: null;
        $id_metode = (int)($_POST['id_metode_pembayaran'] ?? 0);
        $kategori_arr = $_POST['id_kategori_dana'] ?? [];
        $id_kategori_json = is_array($kategori_arr) ? json_encode(array_map('intval', $kategori_arr)) : '[]';
        $id_sumber = (int)($_POST['id_sumber_rekening'] ?? 0);
        $jumlah = (float)str_replace(['.', ','], ['', '.'], $_POST['jumlah_pembayaran'] ?? '0');
        $ket = trim($_POST['keterangan'] ?? '');

        if (!$tgl || !$id_jenis || !$id_metode || empty($kategori_arr) || !$id_sumber || $jumlah <= 0) {
            echo json_encode(['success' => false, 'message' => 'Field wajib harus diisi dan jumlah harus > 0']);
            exit;
        }

        if ($action === 'create') {
            $stmt = $conn->prepare('INSERT INTO transaksi (tanggal_transaksi, id_muzaki, id_upz, id_jenis_pembayaran, id_sub_jenis_pembayaran, id_metode_pembayaran, id_kategori_dana, id_sumber_rekening, jumlah_pembayaran, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('siiiiisids', $tgl, $id_muzaki, $id_upz, $id_jenis, $id_sub, $id_metode, $id_kategori_json, $id_sumber, $jumlah, $ket);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare('UPDATE transaksi SET tanggal_transaksi=?, id_muzaki=?, id_upz=?, id_jenis_pembayaran=?, id_sub_jenis_pembayaran=?, id_metode_pembayaran=?, id_kategori_dana=?, id_sumber_rekening=?, jumlah_pembayaran=?, keterangan=? WHERE id=?');
            $stmt->bind_param('siiiiisidsi', $tgl, $id_muzaki, $id_upz, $id_jenis, $id_sub, $id_metode, $id_kategori_json, $id_sumber, $jumlah, $ket, $id);
        }

        if ($stmt->execute()) {
            $log_id = ($action === 'create') ? $conn->insert_id : $id;
            log_audit(strtoupper($action) . '_TRANSAKSI', 'transaksi', $log_id, "Jumlah: $jumlah, Muzaki ID: $id_muzaki");
            echo json_encode(['success' => true, 'message' => 'Transaksi berhasil ' . ($action === 'create' ? 'ditambahkan' : 'diperbarui')]);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal menyimpan transaksi ke database.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM transaksi WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_TRANSAKSI', 'transaksi', $id, "Deleted transaction ID: $id");
            echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'view') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "SELECT t.*, 
                m.nama_muzaki, u.nama_upz, jp.nama_jenis, sjp.nama_sub_jenis,
                mp.nama_metode, sr.nama_sumber
                FROM transaksi t
                LEFT JOIN muzaki m ON t.id_muzaki = m.id
                LEFT JOIN upz u ON t.id_upz = u.id
                LEFT JOIN jenis_pembayaran jp ON t.id_jenis_pembayaran = jp.id
                LEFT JOIN sub_jenis_pembayaran sjp ON t.id_sub_jenis_pembayaran = sjp.id
                LEFT JOIN metode_pembayaran mp ON t.id_metode_pembayaran = mp.id
                LEFT JOIN sumber_rekening sr ON t.id_sumber_rekening = sr.id
                WHERE t.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            // Map category names
            $katIds = parse_kategori_dana_ids($row['id_kategori_dana']);
            $kategoriList = get_kategori_dana_list();
            $katNames = [];
            foreach ($kategoriList as $k) {
                if (in_array($k['id'], $katIds)) $katNames[] = $k['nama_kategori'];
            }
            $row['kategori_dana_str'] = implode(', ', $katNames);
            
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT * FROM transaksi WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        // Also get sub_jenis options for the selected jenis
        $subJenis = [];
        if ($row && $row['id_jenis_pembayaran']) {
            $subJenis = get_sub_jenis_pembayaran_list((int)$row['id_jenis_pembayaran']);
        }
        echo json_encode(['success' => true, 'data' => $row, 'sub_jenis_options' => $subJenis]);
        exit;
    }
}

$muzakiList = get_muzaki_list();
$upzList = get_upz_list();
$jenisList = get_jenis_pembayaran_list();
$metodeList = get_metode_pembayaran_list();
$kategoriList = get_kategori_dana_list();
$sumberList = get_sumber_rekening_list();

$kategoriMap = [];
foreach ($kategoriList as $k) {
    $kategoriMap[$k['id']] = $k['nama_kategori'];
}

include __DIR__ . '/../../inc/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Data Transaksi ZIS</h3>
        <div class="card-tools">
            <a href="batch.php" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-layer-group mr-1"></i>Input Massal (Batch)
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal()">
                <i class="fas fa-plus mr-1"></i>Tambah Transaksi
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th>Tanggal</th>
                    <th>Muzaki</th>
                    <th>Jenis</th>
                    <th>Metode</th>
                    <th>Kategori</th>
                    <th class="text-right">Jumlah</th>
                    <th>Keterangan</th>
                    <th width="8%">Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Transaksi -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg" style="max-height: 90vh;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-plus mr-2"></i>Tambah Transaksi</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="dataForm">
                <?= csrf_field() ?>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" name="id" id="dataId">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-calendar mr-1"></i>Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-money-bill-wave mr-1"></i>Jumlah Pembayaran <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><strong>Rp</strong></span></div>
                                    <input type="text" class="form-control currency-input" id="jumlah_pembayaran" name="jumlah_pembayaran" required placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-1"></i>Muzaki</label>
                                <select class="form-control select2-muzaki" id="id_muzaki" name="id_muzaki">
                                    <option value="">Pilih Muzaki (opsional)</option>
                                    <?php foreach ($muzakiList as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= clean($m['nama_muzaki']) ?> <?= $m['no_ktp'] ? '(' . clean($m['no_ktp']) . ')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-building mr-1"></i>UPZ</label>
                                <select class="form-control select2-upz" id="id_upz" name="id_upz">
                                    <option value="">Pilih UPZ (opsional)</option>
                                    <?php foreach ($upzList as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= clean($u['nama_upz']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-tags mr-1"></i>Jenis Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-control" id="id_jenis_pembayaran" name="id_jenis_pembayaran" required>
                                    <option value="">Pilih Jenis</option>
                                    <?php foreach ($jenisList as $j): ?>
                                    <option value="<?= $j['id'] ?>"><?= clean($j['nama_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-layer-group mr-1"></i>Sub Jenis Pembayaran</label>
                                <select class="form-control" id="id_sub_jenis_pembayaran" name="id_sub_jenis_pembayaran">
                                    <option value="">Pilih Sub Jenis (opsional)</option>
                                </select>
                                <small class="text-muted">Otomatis terisi setelah memilih Jenis Pembayaran</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-credit-card mr-1"></i>Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-control" id="id_metode_pembayaran" name="id_metode_pembayaran" required>
                                    <option value="">Pilih Metode</option>
                                    <?php foreach ($metodeList as $mt): ?>
                                    <option value="<?= $mt['id'] ?>"><?= clean($mt['nama_metode']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-folder mr-1"></i>Kategori Dana <span class="text-danger">*</span></label>
                                <select class="form-control select2-kategori" id="id_kategori_dana" name="id_kategori_dana[]" multiple="multiple" required>
                                    <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>"><?= clean($k['nama_kategori']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-university mr-1"></i>Sumber Rekening <span class="text-danger">*</span></label>
                                <select class="form-control" id="id_sumber_rekening" name="id_sumber_rekening" required>
                                    <option value="">Pilih Sumber</option>
                                    <?php foreach ($sumberList as $sr): ?>
                                    <option value="<?= $sr['id'] ?>"><?= clean($sr['nama_sumber']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i>Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail & Print Bukti -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-receipt mr-2"></i>Detail Transaksi ZIS</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-borderless table-sm">
                        <tr><td width="35%"><strong>Tanggal</strong></td><td width="5%">:</td><td id="detTanggal"></td></tr>
                        <tr><td><strong>Muzaki</strong></td><td>:</td><td id="detMuzaki"></td></tr>
                        <tr><td><strong>UPZ / Instansi</strong></td><td>:</td><td id="detUPZ"></td></tr>
                        <tr><td><strong>Jenis</strong></td><td>:</td><td id="detJenis"></td></tr>
                        <tr><td><strong>Kategori Dana</strong></td><td>:</td><td id="detKategori"></td></tr>
                        <tr><td><strong>Metode Bayar</strong></td><td>:</td><td id="detMetode"></td></tr>
                        <tr><td><strong>Sumber Rekening</strong></td><td>:</td><td id="detSumber"></td></tr>
                        <tr><td><strong>Nominal (Rp)</strong></td><td>:</td><td><strong id="detNominal" style="font-size: 1.2em; color: #16a34a;"></strong></td></tr>
                        <tr><td><strong>Keterangan</strong></td><td>:</td><td id="detKeterangan"></td></tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <a href="#" id="btnPrintBukti" target="_blank" class="btn btn-primary">
                    <i class="fas fa-print mr-1"></i>Cetak Bukti PDF
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            type: 'GET'
        },
        order: [[1, 'desc']], // Urutkan berdasarkan kolom tanggal default
        columnDefs: [
            { targets: [0, 5, 8], orderable: false }, // matikan sorting untuk No, Kategori, Aksi
            { targets: [6], className: 'text-right' }, // rata kanan untuk kolom jumlah
            { targets: [0, 8], className: 'text-center' }
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
    initCurrencyInput('.currency-input');
    initSelect2('.select2-muzaki', 'Cari Muzaki...', '#formModal');
    initSelect2('.select2-upz', 'Cari UPZ...', '#formModal');
    initSelect2('.select2-kategori', 'Pilih Kategori', '#formModal');

    // Cascading: load sub_jenis when jenis changes
    $('#id_jenis_pembayaran').on('change', function () {
        const jenisId = $(this).val();
        const subSelect = $('#id_sub_jenis_pembayaran');
        subSelect.html('<option value="">Memuat...</option>');

        if (!jenisId) {
            subSelect.html('<option value="">Pilih Sub Jenis (opsional)</option>');
            return;
        }

        $.get(window.location.href, { ajax: 'sub_jenis', jenis_id: jenisId }, function (data) {
            let options = '<option value="">Pilih Sub Jenis (opsional)</option>';
            data.forEach(function (item) {
                options += '<option value="' + item.id + '">' + item.nama_sub_jenis + '</option>';
            });
            subSelect.html(options);
        }, 'json');
    });
});

function openModal(id) {
    $('#dataForm')[0].reset();
    $('#dataId').val('');
    $('#tanggal_transaksi').val(new Date().toISOString().split('T')[0]);
    $('#id_muzaki').val('').trigger('change');
    $('#id_upz').val('').trigger('change');
    $('#id_kategori_dana').val([]).trigger('change');
    $('#id_sub_jenis_pembayaran').html('<option value="">Pilih Sub Jenis (opsional)</option>');
    $('#modalTitle').html(id ? '<i class="fas fa-edit mr-2"></i>Edit Transaksi' : '<i class="fas fa-plus mr-2"></i>Tambah Transaksi');
    $('#formModal').modal('show');
}

function editData(id) {
    $.post(window.location.href, { action: 'get', id: id }, function (res) {
        if (res.success) {
            try {
                var d = res.data;
                openModal(id);
                
                $('#dataId').val(d.id || '');
                $('#tanggal_transaksi').val(d.tanggal_transaksi || '');
                
                if (d.id_muzaki) {
                    $('#id_muzaki').val(d.id_muzaki.toString()).trigger('change');
                } else {
                    $('#id_muzaki').val(null).trigger('change');
                }
                
                $('#id_upz').val(d.id_upz ? d.id_upz.toString() : '').trigger('change');
                $('#id_jenis_pembayaran').val(d.id_jenis_pembayaran || '');
                $('#id_metode_pembayaran').val(d.id_metode_pembayaran || '');
                
                try {
                    let katArr = typeof d.id_kategori_dana === 'string' ? JSON.parse(d.id_kategori_dana) : d.id_kategori_dana;
                    if (!Array.isArray(katArr)) katArr = katArr ? [katArr] : [];
                    $('#id_kategori_dana').val(katArr).trigger('change');
                } catch(e) {
                    $('#id_kategori_dana').val(d.id_kategori_dana ? [d.id_kategori_dana] : []).trigger('change');
                }
                
                $('#id_sumber_rekening').val(d.id_sumber_rekening || '');
                $('#keterangan').val(d.keterangan || '');

                var jumlahStr = d.jumlah_pembayaran ? d.jumlah_pembayaran.toString().split('.')[0] : '0';
                var jumlah = parseInt(jumlahStr) || 0;
                $('#jumlah_pembayaran').val(jumlah.toLocaleString('id-ID'));

                if (res.sub_jenis_options && res.sub_jenis_options.length > 0) {
                    let options = '<option value="">Pilih Sub Jenis (opsional)</option>';
                    res.sub_jenis_options.forEach(function (item) {
                        options += '<option value="' + item.id + '"' + (item.id == d.id_sub_jenis_pembayaran ? ' selected' : '') + '>' + item.nama_sub_jenis + '</option>';
                    });
                    $('#id_sub_jenis_pembayaran').html(options);
                } else {
                    $('#id_sub_jenis_pembayaran').html('<option value="">Pilih Sub Jenis (opsional)</option>');
                }

            } catch (err) {
                alert("Error saat memuat form edit: " + err.message);
                console.error(err);
            }
        } else {
            alert('Gagal mengambil data dari server: ' + (res.message || 'Unknown error'));
        }
    }, 'json').fail(function() {
        alert("Gagal memuat data. Periksa koneksi atau hubungi admin.");
    });
}

function deleteData(id) {
    confirmDelete(function () {
        $.post(window.location.href, { action: 'delete', id: id }, function (res) {
            if (res.success) { showSuccess(res.message); setTimeout(() => location.reload(), 1000); }
            else { showError(res.message); }
        }, 'json');
    });
}

function viewDetail(id) {
    $.post(window.location.href, { action: 'view', id: id }, function(res) {
        if (res.success) {
            const d = res.data;
            $('#detTanggal').text(d.tanggal_transaksi);
            $('#detMuzaki').text(d.nama_muzaki || '-');
            $('#detUPZ').text(d.nama_upz || '-');
            $('#detJenis').text((d.nama_jenis || '') + ' ' + (d.nama_sub_jenis ? '('+d.nama_sub_jenis+')' : ''));
            $('#detKategori').html(d.kategori_dana_str ? '<span class="badge badge-info">'+d.kategori_dana_str+'</span>' : '-');
            $('#detMetode').text(d.nama_metode || '-');
            $('#detSumber').text(d.nama_sumber || '-');
            $('#detKeterangan').text(d.keterangan || '-');
            
            const num = parseInt(d.jumlah_pembayaran) || 0;
            $('#detNominal').text(num.toLocaleString('id-ID'));
            
            $('#btnPrintBukti').attr('href', '<?= url('pages/transaksi/print_bukti.php?id=') ?>' + id);
            
            $('#detailModal').modal('show');
        } else {
            showError(res.message);
        }
    }, 'json').fail(function() {
        showError('Gagal memuat detail transaksi.');
    });
}

$('#dataForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#dataId').val();
    const formData = $(this).serialize() + '&action=' + (id ? 'update' : 'create');
    $.post(window.location.href, formData, function (res) {
        if (res.success) {
            $('#formModal').modal('hide');
            showSuccess(res.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            showError(res.message);
        }
    }, 'json');
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
