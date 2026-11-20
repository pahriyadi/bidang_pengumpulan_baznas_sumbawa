<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/kinerja_db.php';
require_auth();

$activeMenu = 'off_balance';
$pageTitle = 'Off Balance Sheet';
$pageDescription = 'Pencatatan realisasi pengumpulan & penyaluran ZIS mandiri oleh UPZ, Masjid, Desa, dan Lembaga (Off Balance Sheet)';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Off Balance Sheet</li>';

$conn = db();
ensure_kinerja_tables($conn);

// Filter parameters
$filter_tahun = (int)($_GET['tahun'] ?? date('Y'));
if ($filter_tahun < 2000 || $filter_tahun > 2100) {
    $filter_tahun = (int)date('Y');
}
$filter_bulan = (int)($_GET['bulan'] ?? 0);
$filter_jenis = (int)($_GET['id_jenis'] ?? 0);
$filter_upz = (int)($_GET['id_upz'] ?? 0);

// Handle AJAX for sub_jenis cascading
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'sub_jenis') {
    header('Content-Type: application/json');
    $jenis_id = (int)($_GET['jenis_id'] ?? 0);
    echo json_encode(get_sub_jenis_pembayaran_list($jenis_id));
    exit;
}

// Handle AJAX DataTables Server-side Processing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    header('Content-Type: application/json');
    $draw = (int)$_GET['draw'];
    $start = (int)($_GET['start'] ?? 0);
    $length = (int)($_GET['length'] ?? 10);
    $searchValue = trim($_GET['search']['value'] ?? '');

    $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 1);
    $orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

    $columns = [
        0 => 'ob.id',
        1 => 'ob.tanggal_transaksi',
        2 => 'pelapor_display',
        3 => 'jp.nama_jenis',
        4 => 'ob.no_berita_acara',
        5 => 'ob.jumlah_muzaki',
        6 => 'ob.jumlah_mustahik',
        7 => 'ob.nominal',
        8 => 'ob.keterangan'
    ];
    $orderColumn = $columns[$orderColumnIndex] ?? 'ob.tanggal_transaksi';

    $sqlFrom = " FROM transaksi_off_balance ob
                 LEFT JOIN upz u ON ob.id_upz = u.id
                 LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
                 LEFT JOIN jenis_pembayaran jp ON ob.id_jenis_pembayaran = jp.id
                 LEFT JOIN sub_jenis_pembayaran sjp ON ob.id_sub_jenis_pembayaran = sjp.id";

    $baseConds = ["YEAR(ob.tanggal_transaksi) = ?"];
    $baseTypes = "i";
    $baseParams = [$filter_tahun];

    if ($filter_bulan >= 1 && $filter_bulan <= 12) {
        $baseConds[] = "MONTH(ob.tanggal_transaksi) = ?";
        $baseTypes .= "i";
        $baseParams[] = $filter_bulan;
    }
    if ($filter_jenis > 0) {
        $baseConds[] = "ob.id_jenis_pembayaran = ?";
        $baseTypes .= "i";
        $baseParams[] = $filter_jenis;
    }
    if ($filter_upz > 0) {
        $baseConds[] = "ob.id_upz = ?";
        $baseTypes .= "i";
        $baseParams[] = $filter_upz;
    }

    $baseWhere = " WHERE " . implode(" AND ", $baseConds);

    // Count total records for active filter
    $stmtTotal = $conn->prepare("SELECT COUNT(*) " . $sqlFrom . $baseWhere);
    $stmtTotal->bind_param($baseTypes, ...$baseParams);
    $stmtTotal->execute();
    $totalRecords = (int)($stmtTotal->get_result()->fetch_row()[0] ?? 0);
    $stmtTotal->close();

    // Add search condition
    $searchWhere = $baseWhere;
    $allTypes = $baseTypes;
    $allParams = $baseParams;

    if ($searchValue !== '') {
        $searchWhere .= " AND (
            ob.tanggal_transaksi LIKE ? OR
            u.nama_upz LIKE ? OR
            ob.nama_pelapor LIKE ? OR
            jp.nama_jenis LIKE ? OR
            sjp.nama_sub_jenis LIKE ? OR
            ob.no_berita_acara LIKE ? OR
            ob.nominal LIKE ? OR
            ob.keterangan LIKE ?
        )";
        $wild = "%" . $searchValue . "%";
        for ($i = 0; $i < 8; $i++) {
            $allParams[] = $wild;
            $allTypes .= "s";
        }
    }

    // Count filtered records
    $stmtFiltered = $conn->prepare("SELECT COUNT(*) " . $sqlFrom . $searchWhere);
    $stmtFiltered->bind_param($allTypes, ...$allParams);
    $stmtFiltered->execute();
    $totalRecordwithFilter = (int)($stmtFiltered->get_result()->fetch_row()[0] ?? 0);
    $stmtFiltered->close();

    // Fetch paginated data
    $sqlSelect = "SELECT ob.*,
                  u.nama_upz,
                  ku.nama_kategori AS kategori_upz_nama,
                  COALESCE(NULLIF(u.nama_upz, ''), NULLIF(ob.nama_pelapor, ''), 'Masyarakat / Umum') AS pelapor_display,
                  jp.nama_jenis,
                  sjp.nama_sub_jenis";

    $dataQuery = $sqlSelect . $sqlFrom . $searchWhere . " ORDER BY " . $orderColumn . " " . $orderDir . ", ob.id DESC LIMIT ? OFFSET ?";
    $stmtData = $conn->prepare($dataQuery);
    $dataTypes = $allTypes . "ii";
    $dataParams = array_merge($allParams, [$length, $start]);
    $stmtData->bind_param($dataTypes, ...$dataParams);
    $stmtData->execute();
    $result = $stmtData->get_result();

    $data = [];
    $no = $start + 1;
    while ($row = $result->fetch_assoc()) {
        $pelaporStr = '<strong>' . clean($row['pelapor_display']) . '</strong>';
        if (!empty($row['nama_upz']) && !empty($row['nama_pelapor']) && strcasecmp($row['nama_upz'], $row['nama_pelapor']) !== 0) {
            $pelaporStr .= '<br><small class="text-muted"><i class="fas fa-user mr-1"></i>' . clean($row['nama_pelapor']) . '</small>';
        } elseif (!empty($row['kategori_upz_nama'])) {
            $pelaporStr .= '<br><small class="text-muted">' . clean($row['kategori_upz_nama']) . '</small>';
        }

        $jenisStr = '<span class="badge badge-success">' . clean($row['nama_jenis'] ?? '-') . '</span>';
        if (!empty($row['nama_sub_jenis'])) {
            $jenisStr .= '<br><small class="text-muted">' . clean($row['nama_sub_jenis']) . '</small>';
        }

        $baStr = !empty($row['no_berita_acara'])
            ? '<span class="badge badge-secondary">' . clean($row['no_berita_acara']) . '</span>'
            : '<span class="text-muted">—</span>';

        $actionButtons = '
            <div class="text-center" style="white-space: nowrap;">
                <button class="btn btn-outline-info btn-xs" onclick="viewDetail(' . (int)$row['id'] . ')" title="Detail">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-outline-primary btn-xs" onclick="editData(' . (int)$row['id'] . ')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-outline-danger btn-xs" onclick="deleteData(' . (int)$row['id'] . ')" title="Hapus">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';

        $data[] = [
            $no++,
            '<strong>' . date('d/m/Y', strtotime($row['tanggal_transaksi'])) . '</strong>',
            $pelaporStr,
            $jenisStr,
            $baStr,
            '<div class="text-center">' . number_format((int)$row['jumlah_muzaki'], 0, ',', '.') . '</div>',
            '<div class="text-center">' . number_format((int)$row['jumlah_mustahik'], 0, ',', '.') . '</div>',
            '<div class="text-right"><strong>Rp ' . number_format((float)$row['nominal'], 0, ',', '.') . '</strong></div>',
            clean($row['keterangan'] ?: '-'),
            $actionButtons
        ];
    }
    $stmtData->close();

    echo json_encode([
        "draw" => $draw,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalRecordwithFilter,
        "data" => $data
    ]);
    exit;
}

// Handle POST CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF Token tidak valid.']);
        exit;
    }
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $tgl = trim($_POST['tanggal_transaksi'] ?? '');
        $id_upz_raw = trim($_POST['id_upz'] ?? '');
        $nama_pelapor = trim($_POST['nama_pelapor'] ?? '');
        $id_jenis = (int)($_POST['id_jenis_pembayaran'] ?? 0);
        $id_sub = (int)($_POST['id_sub_jenis_pembayaran'] ?? 0) ?: null;
        $jumlah_muzaki = max(0, (int)str_replace(['.', ','], '', $_POST['jumlah_muzaki'] ?? '0'));
        $jumlah_mustahik = max(0, (int)str_replace(['.', ','], '', $_POST['jumlah_mustahik'] ?? '0'));
        $nominal = (float)str_replace(['.', ','], ['', '.'], $_POST['nominal'] ?? '0');
        $no_ba = trim($_POST['no_berita_acara'] ?? '');
        $ket = trim($_POST['keterangan'] ?? '');

        if (!$tgl || !$id_jenis || $nominal <= 0) {
            echo json_encode(['success' => false, 'message' => 'Tanggal, Jenis Pembayaran, dan Nominal (> 0) wajib diisi.']);
            exit;
        }

        // Resolve UPZ (Auto-create if new Tag string is typed)
        $id_upz = null;
        if ($id_upz_raw !== '') {
            if (is_numeric($id_upz_raw)) {
                $id_upz = (int)$id_upz_raw;
            } else {
                $stmtCheck = $conn->prepare("SELECT id FROM upz WHERE LOWER(nama_upz) = LOWER(?) LIMIT 1");
                $stmtCheck->bind_param('s', $id_upz_raw);
                $stmtCheck->execute();
                $resC = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();

                if ($resC) {
                    $id_upz = (int)$resC['id'];
                } else {
                    $stmtInsUpz = $conn->prepare("INSERT INTO upz (nama_upz) VALUES (?)");
                    $stmtInsUpz->bind_param('s', $id_upz_raw);
                    if ($stmtInsUpz->execute()) {
                        $id_upz = (int)$conn->insert_id;
                        log_audit('CREATE_UPZ_AUTO', 'upz', $id_upz, "Auto created via Off Balance Sheet: " . $id_upz_raw);
                    }
                    $stmtInsUpz->close();
                }
            }
        }

        if (!$id_upz && $nama_pelapor === '') {
            echo json_encode(['success' => false, 'message' => 'Pilih UPZ atau isi Nama Lembaga/Masjid/Desa Pelapor.']);
            exit;
        }

        $user_id = (int)($_SESSION['user']['id'] ?? 0) ?: null;

        if ($action === 'create') {
            $stmt = $conn->prepare("
                INSERT INTO transaksi_off_balance
                (tanggal_transaksi, id_upz, nama_pelapor, id_jenis_pembayaran, id_sub_jenis_pembayaran, jumlah_muzaki, jumlah_mustahik, nominal, no_berita_acara, keterangan, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('sisiiiidssi', $tgl, $id_upz, $nama_pelapor, $id_jenis, $id_sub, $jumlah_muzaki, $jumlah_mustahik, $nominal, $no_ba, $ket, $user_id);
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("
                UPDATE transaksi_off_balance
                SET tanggal_transaksi=?, id_upz=?, nama_pelapor=?, id_jenis_pembayaran=?, id_sub_jenis_pembayaran=?, jumlah_muzaki=?, jumlah_mustahik=?, nominal=?, no_berita_acara=?, keterangan=?
                WHERE id=?
            ");
            $stmt->bind_param('sisiiiidssi', $tgl, $id_upz, $nama_pelapor, $id_jenis, $id_sub, $jumlah_muzaki, $jumlah_mustahik, $nominal, $no_ba, $ket, $id);
        }

        if ($stmt->execute()) {
            $log_id = ($action === 'create') ? $conn->insert_id : $id;
            log_audit(strtoupper($action) . '_OFF_BALANCE', 'transaksi_off_balance', $log_id, "Nominal Off Balance: Rp $nominal");
            echo json_encode([
                'success' => true,
                'message' => 'Data Off Balance Sheet berhasil ' . ($action === 'create' ? 'ditambahkan' : 'diperbarui')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal menyimpan data Off Balance Sheet.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM transaksi_off_balance WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_OFF_BALANCE', 'transaksi_off_balance', $id, "Deleted Off Balance ID: $id");
            echo json_encode(['success' => true, 'message' => 'Data Off Balance Sheet berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus data.']);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT * FROM transaksi_off_balance WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $subJenis = [];
        if ($row && !empty($row['id_jenis_pembayaran'])) {
            $subJenis = get_sub_jenis_pembayaran_list((int)$row['id_jenis_pembayaran']);
        }
        echo json_encode(['success' => (bool)$row, 'data' => $row, 'sub_jenis_options' => $subJenis]);
        exit;
    }

    if ($action === 'view') {
        $id = (int)($_POST['id'] ?? 0);
        $sql = "SELECT ob.*,
                u.nama_upz,
                ku.nama_kategori AS kategori_upz_nama,
                jp.nama_jenis,
                sjp.nama_sub_jenis
                FROM transaksi_off_balance ob
                LEFT JOIN upz u ON ob.id_upz = u.id
                LEFT JOIN kategori_upz ku ON u.id_kategori_upz = ku.id
                LEFT JOIN jenis_pembayaran jp ON ob.id_jenis_pembayaran = jp.id
                LEFT JOIN sub_jenis_pembayaran sjp ON ob.id_sub_jenis_pembayaran = sjp.id
                WHERE ob.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    exit;
}

// Fetch Summary Stats for Selected Year & Filter
$sumConds = ["YEAR(ob.tanggal_transaksi) = ?"];
$sumTypes = "i";
$sumParams = [$filter_tahun];
if ($filter_bulan >= 1 && $filter_bulan <= 12) {
    $sumConds[] = "MONTH(ob.tanggal_transaksi) = ?";
    $sumTypes .= "i";
    $sumParams[] = $filter_bulan;
}
if ($filter_jenis > 0) {
    $sumConds[] = "ob.id_jenis_pembayaran = ?";
    $sumTypes .= "i";
    $sumParams[] = $filter_jenis;
}
if ($filter_upz > 0) {
    $sumConds[] = "ob.id_upz = ?";
    $sumTypes .= "i";
    $sumParams[] = $filter_upz;
}
$sumWhere = " WHERE " . implode(" AND ", $sumConds);

$stmtSum = $conn->prepare("
    SELECT
        COUNT(*) AS total_data,
        COALESCE(SUM(ob.nominal), 0) AS total_nominal,
        COALESCE(SUM(ob.jumlah_muzaki), 0) AS total_muzaki,
        COALESCE(SUM(ob.jumlah_mustahik), 0) AS total_mustahik,
        COUNT(DISTINCT COALESCE(CAST(ob.id_upz AS CHAR), ob.nama_pelapor)) AS total_pelapor
    FROM transaksi_off_balance ob
    {$sumWhere}
");
$stmtSum->bind_param($sumTypes, ...$sumParams);
$stmtSum->execute();
$summaryRow = $stmtSum->get_result()->fetch_assoc();
$stmtSum->close();

$totalDataOB = (int)($summaryRow['total_data'] ?? 0);
$totalNominalOB = (float)($summaryRow['total_nominal'] ?? 0);
$totalMuzakiOB = (int)($summaryRow['total_muzaki'] ?? 0);
$totalMustahikOB = (int)($summaryRow['total_mustahik'] ?? 0);
$totalPelaporOB = (int)($summaryRow['total_pelapor'] ?? 0);

// Target Off Balance Sheet Tahun Ini
$stmtTgt = $conn->prepare("SELECT nominal_target_off_balance FROM target_pengumpulan_tahunan WHERE tahun = ? LIMIT 1");
$stmtTgt->bind_param('i', $filter_tahun);
$stmtTgt->execute();
$tgtRow = $stmtTgt->get_result()->fetch_assoc();
$stmtTgt->close();
$targetOffBalanceTahun = (float)($tgtRow['nominal_target_off_balance'] ?? 0);

// Capaian Off Balance 1 Tahun Penuh (untuk dibandingkan dengan target tahunan)
$stmtCapYear = $conn->prepare("SELECT COALESCE(SUM(nominal), 0) AS total FROM transaksi_off_balance WHERE YEAR(tanggal_transaksi) = ?");
$stmtCapYear->bind_param('i', $filter_tahun);
$stmtCapYear->execute();
$capaianOffBalanceTahun = (float)($stmtCapYear->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCapYear->close();

$pctOffBalanceTahun = $targetOffBalanceTahun > 0
    ? min(100, round(($capaianOffBalanceTahun / $targetOffBalanceTahun) * 100, 1))
    : null;

$upzList = get_upz_list();
$jenisList = get_jenis_pembayaran_list();

// Rekapitulasi per Sumber Jenis Dana & Sub-Jenis Dana (Mengikuti Filter Aktif)
$stmtSumber = $conn->prepare("
    SELECT
        ob.id_jenis_pembayaran AS id_jenis,
        ob.id_sub_jenis_pembayaran AS id_sub,
        COALESCE(jp.nama_jenis, 'LAINNYA') AS nama_jenis,
        COALESCE(NULLIF(sjp.nama_sub_jenis, ''), 'Umum / Tanpa Sub-Jenis') AS nama_sub_jenis,
        COUNT(*) AS jumlah_laporan,
        COALESCE(SUM(ob.jumlah_muzaki), 0) AS jumlah_muzaki,
        COALESCE(SUM(ob.jumlah_mustahik), 0) AS jumlah_mustahik,
        COALESCE(SUM(ob.nominal), 0) AS total_nominal
    FROM transaksi_off_balance ob
    LEFT JOIN jenis_pembayaran jp ON ob.id_jenis_pembayaran = jp.id
    LEFT JOIN sub_jenis_pembayaran sjp ON ob.id_sub_jenis_pembayaran = sjp.id
    {$sumWhere}
    GROUP BY ob.id_jenis_pembayaran, ob.id_sub_jenis_pembayaran, jp.nama_jenis, sjp.nama_sub_jenis
    ORDER BY jp.id ASC, sjp.id ASC
");
$stmtSumber->bind_param($sumTypes, ...$sumParams);
$stmtSumber->execute();
$resSumber = $stmtSumber->get_result();

$groupedSumberOB = [];
while ($rS = $resSumber->fetch_assoc()) {
    $jNama = $rS['nama_jenis'];
    if (!isset($groupedSumberOB[$jNama])) {
        $groupedSumberOB[$jNama] = [
            'id_jenis' => (int)$rS['id_jenis'],
            'total_laporan' => 0,
            'total_muzaki' => 0,
            'total_mustahik' => 0,
            'total_nominal' => 0.0,
            'subs' => []
        ];
    }
    $groupedSumberOB[$jNama]['total_laporan'] += (int)$rS['jumlah_laporan'];
    $groupedSumberOB[$jNama]['total_muzaki'] += (int)$rS['jumlah_muzaki'];
    $groupedSumberOB[$jNama]['total_mustahik'] += (int)$rS['jumlah_mustahik'];
    $groupedSumberOB[$jNama]['total_nominal'] += (float)$rS['total_nominal'];
    $groupedSumberOB[$jNama]['subs'][] = $rS;
}
$stmtSumber->close();

include __DIR__ . '/../../inc/header.php';
?>

<!-- Kartu Ringkasan Off Balance Sheet -->
<div class="row dashboard-stats-row">
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-success h-100 mb-0">
            <div class="inner">
                <h3 class="stat-currency-val" title="<?= format_currency($totalNominalOB) ?>"><?= format_currency($totalNominalOB) ?></h3>
                <p>Realisasi Off Balance (<?= $filter_tahun ?><?= $filter_bulan > 0 ? ' - ' . month_name($filter_bulan) : '' ?>)</p>
            </div>
            <div class="icon"><i class="fas fa-clipboard-check"></i></div>
            <a href="<?= url('pages/kinerja/index.php?tahun=' . $filter_tahun) ?>" class="small-box-footer">
                Target <?= $filter_tahun ?>: <?= $targetOffBalanceTahun > 0 ? format_currency($targetOffBalanceTahun) . ' (' . $pctOffBalanceTahun . '%)' : 'Belum Diatur' ?>
                <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-primary h-100 mb-0">
            <div class="inner">
                <h3><?= number_format($totalDataOB, 0, ',', '.') ?> <small style="font-size: 0.55em; font-weight: normal;">Laporan</small></h3>
                <p>Total Berita Acara / Pencatatan</p>
            </div>
            <div class="icon"><i class="fas fa-file-signature"></i></div>
            <span class="small-box-footer">Dari <?= number_format($totalPelaporOB, 0, ',', '.') ?> UPZ / Lembaga Pelapor</span>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-info h-100 mb-0">
            <div class="inner">
                <h3><?= number_format($totalMuzakiOB, 0, ',', '.') ?> <small style="font-size: 0.55em; font-weight: normal;">Jiwa</small></h3>
                <p>Total Muzaki / Munfik Tercatat</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <span class="small-box-footer">Pemberi ZIS Off Balance Sheet</span>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="small-box bg-warning h-100 mb-0">
            <div class="inner">
                <h3><?= number_format($totalMustahikOB, 0, ',', '.') ?> <small style="font-size: 0.55em; font-weight: normal;">Jiwa</small></h3>
                <p>Total Mustahik Penerima Manfaat</p>
            </div>
            <div class="icon"><i class="fas fa-hands-helping"></i></div>
            <span class="small-box-footer">Penyaluran Langsung di UPZ/Desa/Masjid</span>
        </div>
    </div>
</div>

<!-- Panel Filter & Ekspor -->
<div class="card shadow-none mb-3" style="border: 1px solid #b8b8b8;">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
        <h3 class="card-title font-weight-bold mb-0"><i class="fas fa-filter mr-2 text-success"></i>Filter &amp; Ekspor Data Off Balance Sheet</h3>
        <div class="card-tools mt-2 mt-md-0">
            <button type="button" class="btn btn-outline-info btn-sm mr-1" onclick="submitExport('csv')">
                <i class="fas fa-file-csv mr-1"></i>CSV
            </button>
            <button type="button" class="btn btn-outline-success btn-sm mr-1" onclick="submitExport('excel')">
                <i class="fas fa-file-excel mr-1"></i>Excel
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="submitExport('pdf')">
                <i class="fas fa-file-pdf mr-1"></i>PDF
            </button>
        </div>
    </div>
    <div class="card-body py-3">
        <form method="GET" action="index.php" id="filterForm">
            <div class="row align-items-end">
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <label class="small font-weight-bold mb-1">Tahun</label>
                    <select name="tahun" class="form-control form-control-sm" onchange="this.form.submit()">
                        <?php for ($y = (int)date('Y') + 1; $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $filter_tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 col-6 mb-2 mb-md-0">
                    <label class="small font-weight-bold mb-1">Bulan</label>
                    <select name="bulan" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="0">Semua Bulan</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $filter_bulan === $m ? 'selected' : '' ?>><?= month_name($m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 col-12 mb-2 mb-md-0">
                    <label class="small font-weight-bold mb-1">Jenis Pembayaran ZIS</label>
                    <select name="id_jenis" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="0">Semua Jenis Pembayaran</option>
                        <?php foreach ($jenisList as $j): ?>
                            <option value="<?= (int)$j['id'] ?>" <?= $filter_jenis === (int)$j['id'] ? 'selected' : '' ?>><?= clean($j['nama_jenis']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-12 mb-2 mb-md-0">
                    <label class="small font-weight-bold mb-1">UPZ Terdaftar</label>
                    <select name="id_upz" class="form-control form-control-sm select2-filter-upz" onchange="this.form.submit()">
                        <option value="0">Semua UPZ / Lembaga</option>
                        <?php foreach ($upzList as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= $filter_upz === (int)$u['id'] ? 'selected' : '' ?>><?= clean($u['nama_upz']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-12 text-md-right">
                    <a href="index.php?tahun=<?= $filter_tahun ?>" class="btn btn-outline-secondary btn-sm btn-block">
                        <i class="fas fa-sync-alt mr-1"></i>Reset Filter
                    </a>
                </div>
            </div>
        </form>
        <form method="POST" action="export.php" id="exportForm" target="_blank" class="d-none">
            <?= csrf_field() ?>
            <input type="hidden" name="tahun" value="<?= $filter_tahun ?>">
            <input type="hidden" name="bulan" value="<?= $filter_bulan ?>">
            <input type="hidden" name="id_jenis" value="<?= $filter_jenis ?>">
            <input type="hidden" name="id_upz" value="<?= $filter_upz ?>">
            <input type="hidden" name="export_format" id="export_format_input" value="excel">
        </form>
    </div>
</div>

<!-- Tabel Utama DataTables Server-Side -->
<div class="card shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-clipboard-list mr-2 text-success"></i>Daftar Pencatatan Off Balance Sheet
        </h3>
        <div class="card-tools mt-2 mt-md-0">
            <a href="laporan.php?start_date=<?= $filter_tahun ?>-01-01&end_date=<?= $filter_tahun ?>-12-31" class="btn btn-outline-info btn-sm mr-1">
                <i class="fas fa-chart-pie mr-1"></i>Laporan per Sumber &amp; Sub-Jenis Dana
            </a>
            <a href="<?= url('pages/kinerja/index.php?tahun=' . $filter_tahun) ?>" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-bullseye mr-1"></i>Atur Target Off Balance
            </a>
            <a href="batch.php" class="btn btn-outline-success btn-sm mr-1">
                <i class="fas fa-layer-group mr-1"></i>Input Massal (Batch)
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openModal()">
                <i class="fas fa-plus mr-1"></i>Tambah Off Balance
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="dataTable" class="table table-bordered table-hover" style="font-size: 0.85rem;">
            <thead>
                <tr>
                    <th width="3%">No</th>
                    <th width="9%">Tanggal</th>
                    <th width="20%">UPZ / Lembaga Pelapor</th>
                    <th width="14%">Jenis ZIS</th>
                    <th width="10%">No. BA / Laporan</th>
                    <th width="7%" class="text-center">Muzaki</th>
                    <th width="7%" class="text-center">Mustahik</th>
                    <th width="13%" class="text-right">Nominal (Rp)</th>
                    <th>Keterangan</th>
                    <th width="8%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<!-- Rekapitulasi Off Balance Sheet per Sumber Jenis Dana & Sub-Jenis Dana -->
<div class="card shadow-none mt-4" style="border: 1px solid #b8b8b8;">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="border-bottom: 1px solid #b8b8b8;">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-layer-group mr-2 text-success"></i>Rekapitulasi Off Balance Sheet per Sumber Jenis Dana &amp; Sub-Jenis Dana (<?= $filter_tahun ?><?= $filter_bulan > 0 ? ' - ' . month_name($filter_bulan) : '' ?>)
        </h3>
        <a href="laporan.php?start_date=<?= $filter_tahun ?>-01-01&end_date=<?= $filter_tahun ?>-12-31" class="btn btn-outline-info btn-xs mt-2 mt-md-0">
            <i class="fas fa-external-link-alt mr-1"></i>Buka Halaman Laporan Lengkap Off Balance
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size: 0.85rem;">
                <thead class="bg-gray-light">
                    <tr>
                        <th>Sumber Dana (Jenis &amp; Sub-Jenis Pembayaran)</th>
                        <th width="12%" class="text-center">Jumlah Laporan</th>
                        <th width="12%" class="text-center">Muzaki (Jiwa)</th>
                        <th width="12%" class="text-center">Mustahik (Jiwa)</th>
                        <th width="18%" class="text-right">Realisasi Off Balance (Rp)</th>
                        <th width="10%" class="text-center">% Proporsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($groupedSumberOB)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data Off Balance Sheet untuk filter ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($groupedSumberOB as $jNama => $jData):
                            $pctJ = $totalNominalOB > 0 ? round(($jData['total_nominal'] / $totalNominalOB) * 100, 1) : 0;
                        ?>
                            <tr class="table-secondary font-weight-bold">
                                <td><i class="fas fa-folder-open mr-2 text-success"></i><?= strtoupper(htmlspecialchars($jNama)) ?></td>
                                <td class="text-center"><?= number_format($jData['total_laporan'], 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($jData['total_muzaki'], 0, ',', '.') ?></td>
                                <td class="text-center"><?= number_format($jData['total_mustahik'], 0, ',', '.') ?></td>
                                <td class="text-right">Rp <?= number_format($jData['total_nominal'], 0, ',', '.') ?>,-</td>
                                <td class="text-center"><span class="badge badge-success"><?= $pctJ ?>%</span></td>
                            </tr>
                            <?php foreach ($jData['subs'] as $sRow):
                                $sNom = (float)$sRow['total_nominal'];
                                $pctS = $totalNominalOB > 0 ? round(($sNom / $totalNominalOB) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td style="padding-left: 30px;"><i class="fas fa-angle-right mr-2 text-muted"></i><?= htmlspecialchars($sRow['nama_sub_jenis']) ?></td>
                                    <td class="text-center"><?= number_format((int)$sRow['jumlah_laporan'], 0, ',', '.') ?></td>
                                    <td class="text-center"><?= number_format((int)$sRow['jumlah_muzaki'], 0, ',', '.') ?></td>
                                    <td class="text-center"><?= number_format((int)$sRow['jumlah_mustahik'], 0, ',', '.') ?></td>
                                    <td class="text-right font-weight-bold">Rp <?= number_format($sNom, 0, ',', '.') ?>,-</td>
                                    <td class="text-center"><?= $pctS ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($groupedSumberOB)): ?>
                <tfoot class="bg-gray-light font-weight-bold">
                    <tr>
                        <td class="text-right">TOTAL KESELURUHAN OFF BALANCE SHEET</td>
                        <td class="text-center"><?= number_format($totalDataOB, 0, ',', '.') ?></td>
                        <td class="text-center"><?= number_format($totalMuzakiOB, 0, ',', '.') ?></td>
                        <td class="text-center"><?= number_format($totalMustahikOB, 0, ',', '.') ?></td>
                        <td class="text-right text-success">Rp <?= number_format($totalNominalOB, 0, ',', '.') ?>,-</td>
                        <td class="text-center">100%</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah / Edit Off Balance Sheet -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold" id="modalTitle"><i class="fas fa-plus mr-2 text-success"></i>Tambah Pencatatan Off Balance Sheet</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="dataForm">
                <?= csrf_field() ?>
                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <input type="hidden" name="id" id="dataId">

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-calendar mr-1"></i>Tanggal Laporan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-file-contract mr-1"></i>No. Berita Acara / Laporan</label>
                                <input type="text" class="form-control" id="no_berita_acara" name="no_berita_acara" placeholder="Contoh: BA-ZF/01/2026">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-money-bill-wave mr-1"></i>Nominal / Nilai Konversi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><strong>Rp</strong></span></div>
                                    <input type="text" class="form-control currency-input" id="nominal" name="nominal" required placeholder="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-building mr-1"></i>UPZ (Ketik baru jika belum terdaftar)</label>
                                <select class="form-control select2-upz-tag" id="id_upz" name="id_upz">
                                    <option value="">Pilih atau Ketik UPZ Baru...</option>
                                    <?php foreach ($upzList as $u): ?>
                                        <option value="<?= (int)$u['id'] ?>"><?= clean($u['nama_upz']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Mendukung pembuatan UPZ baru otomatis jika nama diketik.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-mosque mr-1"></i>Nama Pelapor / Masjid / Desa / Lembaga</label>
                                <input type="text" class="form-control" id="nama_pelapor" name="nama_pelapor" placeholder="Isi jika bukan UPZ atau nama penanggung jawab">
                                <small class="text-muted">Wajib diisi salah satu: UPZ atau Nama Pelapor.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-tags mr-1"></i>Jenis Pembayaran ZIS <span class="text-danger">*</span></label>
                                <select class="form-control" id="id_jenis_pembayaran" name="id_jenis_pembayaran" required>
                                    <option value="">Pilih Jenis</option>
                                    <?php foreach ($jenisList as $j): ?>
                                        <option value="<?= (int)$j['id'] ?>"><?= clean($j['nama_jenis']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-layer-group mr-1"></i>Sub Jenis Pembayaran</label>
                                <select class="form-control" id="id_sub_jenis_pembayaran" name="id_sub_jenis_pembayaran">
                                    <option value="">Pilih Sub Jenis (opsional)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-users mr-1"></i>Jumlah Muzaki / Munfik (Jiwa/Orang)</label>
                                <input type="number" min="0" class="form-control" id="jumlah_muzaki" name="jumlah_muzaki" value="0" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold"><i class="fas fa-hands-helping mr-1"></i>Jumlah Mustahik Penerima (Jiwa/Orang)</label>
                                <input type="number" min="0" class="form-control" id="jumlah_mustahik" name="jumlah_mustahik" value="0" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold"><i class="fas fa-sticky-note mr-1"></i>Keterangan Pengumpulan &amp; Penyaluran</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="2" placeholder="Contoh: Zakat Fitrah Ramadhan 1447 H terkumpul & disalurkan langsung di Masjid Agung / Desa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #b8b8b8;">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Batal</button>
                    <button type="submit" class="btn btn-outline-success"><i class="fas fa-save mr-1"></i>Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Off Balance Sheet -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header" style="border-bottom: 1px solid #b8b8b8;">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-clipboard-check mr-2 text-success"></i>Detail Pencatatan Off Balance Sheet</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td width="40%"><strong>Tanggal Laporan</strong></td><td width="5%">:</td><td id="detTanggal"></td></tr>
                    <tr><td><strong>No. Berita Acara</strong></td><td>:</td><td id="detBA"></td></tr>
                    <tr><td><strong>UPZ Terdaftar</strong></td><td>:</td><td id="detUPZ"></td></tr>
                    <tr><td><strong>Nama Pelapor / Lembaga</strong></td><td>:</td><td id="detPelapor"></td></tr>
                    <tr><td><strong>Jenis / Sub-Jenis ZIS</strong></td><td>:</td><td id="detJenis"></td></tr>
                    <tr><td><strong>Jumlah Muzaki</strong></td><td>:</td><td id="detMuzaki"></td></tr>
                    <tr><td><strong>Jumlah Mustahik</strong></td><td>:</td><td id="detMustahik"></td></tr>
                    <tr><td><strong>Nominal (Rp)</strong></td><td>:</td><td><strong id="detNominal" class="text-success" style="font-size: 1.15rem;"></strong></td></tr>
                    <tr><td><strong>Keterangan</strong></td><td>:</td><td id="detKeterangan"></td></tr>
                </table>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #b8b8b8;">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Tutup</button>
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
        order: [[1, 'desc']],
        columnDefs: [
            { targets: [0, 9], orderable: false, className: 'text-center' },
            { targets: [5, 6], className: 'text-center' },
            { targets: [7], className: 'text-right' }
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
    initSelect2('.select2-filter-upz', 'Semua UPZ / Lembaga');

    $('#id_upz').select2({
        theme: 'bootstrap4',
        dropdownParent: $('#formModal'),
        placeholder: 'Pilih atau Ketik UPZ Baru...',
        allowClear: true,
        tags: true,
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '') return null;
            return {
                id: term,
                text: term + ' (UPZ Baru)',
                newTag: true
            };
        }
    });

    $('#id_jenis_pembayaran').on('change', function () {
        const jenisId = $(this).val();
        const subSelect = $('#id_sub_jenis_pembayaran');
        subSelect.html('<option value="">Memuat...</option>');

        if (!jenisId) {
            subSelect.html('<option value="">Pilih Sub Jenis (opsional)</option>');
            return;
        }

        $.get('index.php', { ajax: 'sub_jenis', jenis_id: jenisId }, function (data) {
            let options = '<option value="">Pilih Sub Jenis (opsional)</option>';
            data.forEach(function (item) {
                options += '<option value="' + item.id + '">' + item.nama_sub_jenis + '</option>';
            });
            subSelect.html(options);
        }, 'json');
    });
});

function submitExport(fmt) {
    $('#export_format_input').val(fmt);
    $('#exportForm').submit();
}

function openModal(id) {
    $('#dataForm')[0].reset();
    $('#dataId').val('');
    $('#tanggal_transaksi').val(new Date().toISOString().split('T')[0]);
    $('#id_upz').val('').trigger('change');
    $('#jumlah_muzaki').val('0');
    $('#jumlah_mustahik').val('0');
    $('#id_sub_jenis_pembayaran').html('<option value="">Pilih Sub Jenis (opsional)</option>');
    $('#modalTitle').html(id ? '<i class="fas fa-edit mr-2 text-primary"></i>Edit Pencatatan Off Balance Sheet' : '<i class="fas fa-plus mr-2 text-success"></i>Tambah Pencatatan Off Balance Sheet');
    $('#formModal').modal('show');
}

function editData(id) {
    $.post('index.php', { action: 'get', id: id }, function (res) {
        if (!res.success || !res.data) {
            showError('Data tidak ditemukan');
            return;
        }
        const d = res.data;
        openModal(id);
        $('#dataId').val(d.id);
        $('#tanggal_transaksi').val(d.tanggal_transaksi || '');
        $('#no_berita_acara').val(d.no_berita_acara || '');
        $('#id_upz').val(d.id_upz ? String(d.id_upz) : '').trigger('change');
        $('#nama_pelapor').val(d.nama_pelapor || '');
        $('#id_jenis_pembayaran').val(d.id_jenis_pembayaran || '');
        $('#jumlah_muzaki').val(d.jumlah_muzaki || 0);
        $('#jumlah_mustahik').val(d.jumlah_mustahik || 0);
        $('#keterangan').val(d.keterangan || '');

        var nom = parseInt(String(d.nominal || '0').split('.')[0], 10) || 0;
        $('#nominal').val(nom > 0 ? nom.toLocaleString('id-ID') : '');

        let options = '<option value="">Pilih Sub Jenis (opsional)</option>';
        if (res.sub_jenis_options && res.sub_jenis_options.length > 0) {
            res.sub_jenis_options.forEach(function (item) {
                options += '<option value="' + item.id + '"' + (item.id == d.id_sub_jenis_pembayaran ? ' selected' : '') + '>' + item.nama_sub_jenis + '</option>';
            });
        }
        $('#id_sub_jenis_pembayaran').html(options);
    }, 'json');
}

function deleteData(id) {
    confirmDelete(function () {
        $.post('index.php', { action: 'delete', id: id }, function (res) {
            if (res.success) {
                showSuccess(res.message);
                setTimeout(function () { location.reload(); }, 700);
            } else {
                showError(res.message);
            }
        }, 'json');
    });
}

function viewDetail(id) {
    $.post('index.php', { action: 'view', id: id }, function (res) {
        if (!res.success || !res.data) {
            showError(res.message || 'Gagal memuat detail');
            return;
        }
        const d = res.data;
        $('#detTanggal').text(d.tanggal_transaksi || '-');
        $('#detBA').text(d.no_berita_acara || '-');
        $('#detUPZ').text(d.nama_upz ? (d.nama_upz + (d.kategori_upz_nama ? ' (' + d.kategori_upz_nama + ')' : '')) : '-');
        $('#detPelapor').text(d.nama_pelapor || '-');
        $('#detJenis').text((d.nama_jenis || '-') + (d.nama_sub_jenis ? ' — ' + d.nama_sub_jenis : ''));
        $('#detMuzaki').text((parseInt(d.jumlah_muzaki, 10) || 0).toLocaleString('id-ID') + ' Jiwa');
        $('#detMustahik').text((parseInt(d.jumlah_mustahik, 10) || 0).toLocaleString('id-ID') + ' Jiwa');
        const nom = parseInt(String(d.nominal || '0').split('.')[0], 10) || 0;
        $('#detNominal').text('Rp ' + nom.toLocaleString('id-ID'));
        $('#detKeterangan').text(d.keterangan || '-');
        $('#detailModal').modal('show');
    }, 'json');
}

$('#dataForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#dataId').val();
    const fd = $(this).serialize() + '&action=' + (id ? 'update' : 'create');
    $.post('index.php', fd, function (res) {
        if (res.success) {
            $('#formModal').modal('hide');
            showSuccess(res.message);
            setTimeout(function () { location.reload(); }, 700);
        } else {
            showError(res.message);
        }
    }, 'json');
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
