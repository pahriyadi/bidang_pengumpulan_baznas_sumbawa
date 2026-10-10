<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/dokumen_db.php';
require_auth();

$conn = db();
ensure_dokumen_tables($conn);

$activeMenu = 'dokumen_program';
$pageTitle = 'Lembar Dokumen Penerima Bantuan';
$pageDescription = 'Kelola lembar data daftar nama penerima bantuan / program BAZNAS Kabupaten Sumbawa';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li>' .
               '<li class="breadcrumb-item active">Dokumen Program</li>';

// Ambil daftar semua kategori
$kategoriList = get_dokumen_kategori_list($conn, false);

// Tentukan kategori aktif yang dipilih
$idKategoriAktif = (int)($_GET['id_kategori'] ?? 0);
if ($idKategoriAktif <= 0 && !empty($kategoriList)) {
    $idKategoriAktif = (int)$kategoriList[0]['id'];
}

$kategoriAktif = null;
$kolomList = [];
$penerimaList = [];

if ($idKategoriAktif > 0) {
    $kategoriAktif = get_dokumen_kategori_by_id($conn, $idKategoriAktif);
    if ($kategoriAktif) {
        $kolomList = get_dokumen_kolom_list($conn, $idKategoriAktif);
    }
}

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create_penerima') {
        $idKat = (int)($_POST['id_kategori'] ?? 0);
        if ($idKat <= 0) {
            echo json_encode(['success' => false, 'message' => 'Kategori dokumen tidak valid']);
            exit;
        }

        $koloms = get_dokumen_kolom_list($conn, $idKat);
        $dataValues = [];
        $totalNominal = 0.00;

        foreach ($koloms as $col) {
            $key = $col['key_kolom'];
            $val = $_POST['field_' . $key] ?? '';

            if ($col['is_required'] && trim($val) === '') {
                echo json_encode(['success' => false, 'message' => 'Kolom "' . $col['nama_kolom'] . '" wajib diisi']);
                exit;
            }

            // Bersihkan currency jika ada
            if ($col['tipe_data'] === 'currency') {
                $cleanNominal = preg_replace('/[^0-9]/', '', $val);
                $numVal = (float)$cleanNominal;
                $dataValues[$key] = $numVal;
                $totalNominal += $numVal;
            } elseif ($col['tipe_data'] === 'number') {
                $dataValues[$key] = is_numeric($val) ? (float)$val : 0;
            } else {
                $dataValues[$key] = trim($val);
            }
        }

        $jsonPayload = json_encode($dataValues, JSON_UNESCAPED_UNICODE);
        $catatan = trim($_POST['catatan_khusus'] ?? '');
        $userId = current_user()['id'] ?? null;

        $stmt = $conn->prepare("INSERT INTO dokumen_penerima_data (id_kategori_dokumen, data_values, nominal_total, catatan_khusus, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('isdsi', $idKat, $jsonPayload, $totalNominal, $catatan, $userId);

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            log_audit('CREATE_DOKUMEN_PENERIMA', 'dokumen_penerima_data', $newId, "Kategori: $idKat");

            // Simpan lampiran foto jika ada
            if (!empty($_FILES['foto_dokumentasi']['name'][0])) {
                $fileCount = count($_FILES['foto_dokumentasi']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['foto_dokumentasi']['error'][$i] === UPLOAD_ERR_OK) {
                        $fItem = [
                            'name' => $_FILES['foto_dokumentasi']['name'][$i],
                            'type' => $_FILES['foto_dokumentasi']['type'][$i],
                            'tmp_name' => $_FILES['foto_dokumentasi']['tmp_name'][$i],
                            'error' => $_FILES['foto_dokumentasi']['error'][$i],
                            'size' => $_FILES['foto_dokumentasi']['size'][$i],
                        ];
                        $uploadResult = proses_upload_foto_penerima($fItem, $newId);
                        if ($uploadResult) {
                            $judulFoto = 'Foto ' . ($i + 1);
                            $urutan = $i + 1;
                            $stFoto = $conn->prepare("INSERT INTO dokumen_penerima_foto (id_penerima_data, file_path, file_thumbnail, judul_foto, file_size, urutan) VALUES (?, ?, ?, ?, ?, ?)");
                            $stFoto->bind_param('isssii', $newId, $uploadResult['file_path'], $uploadResult['file_thumbnail'], $judulFoto, $uploadResult['file_size'], $urutan);
                            $stFoto->execute();
                            $stFoto->close();
                        }
                    }
                }
            }

            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Data penerima berhasil ditambahkan ke dokumen']);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal menyimpan data penerima.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update_penerima') {
        $id = (int)($_POST['id'] ?? 0);
        $idKat = (int)($_POST['id_kategori'] ?? 0);
        if ($id <= 0 || $idKat <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID data tidak valid']);
            exit;
        }

        $koloms = get_dokumen_kolom_list($conn, $idKat);
        $dataValues = [];
        $totalNominal = 0.00;

        foreach ($koloms as $col) {
            $key = $col['key_kolom'];
            $val = $_POST['field_' . $key] ?? '';

            if ($col['is_required'] && trim($val) === '') {
                echo json_encode(['success' => false, 'message' => 'Kolom "' . $col['nama_kolom'] . '" wajib diisi']);
                exit;
            }

            if ($col['tipe_data'] === 'currency') {
                $cleanNominal = preg_replace('/[^0-9]/', '', $val);
                $numVal = (float)$cleanNominal;
                $dataValues[$key] = $numVal;
                $totalNominal += $numVal;
            } elseif ($col['tipe_data'] === 'number') {
                $dataValues[$key] = is_numeric($val) ? (float)$val : 0;
            } else {
                $dataValues[$key] = trim($val);
            }
        }

        $jsonPayload = json_encode($dataValues, JSON_UNESCAPED_UNICODE);
        $catatan = trim($_POST['catatan_khusus'] ?? '');

        $stmt = $conn->prepare("UPDATE dokumen_penerima_data SET data_values = ?, nominal_total = ?, catatan_khusus = ? WHERE id = ? AND id_kategori_dokumen = ?");
        $stmt->bind_param('sdsii', $jsonPayload, $totalNominal, $catatan, $id, $idKat);

        if ($stmt->execute()) {
            log_audit('UPDATE_DOKUMEN_PENERIMA', 'dokumen_penerima_data', $id, "Kategori: $idKat");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Data penerima berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal memperbarui data penerima.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete_penerima') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID data tidak valid']);
            exit;
        }

        // Hapus berkas foto fisik terlebih dahulu
        $fotos = get_dokumen_penerima_foto_list($conn, $id);
        foreach ($fotos as $f) {
            hapus_berkas_foto($f['file_path'], $f['file_thumbnail']);
        }

        $stmt = $conn->prepare("DELETE FROM dokumen_penerima_data WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_DOKUMEN_PENERIMA', 'dokumen_penerima_data', $id);
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Data penerima beserta berkas fotonya berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => safe_db_error($conn->error, 'Gagal menghapus data penerima.')]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get_penerima') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM dokumen_penerima_data WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $decoded = json_decode($row['data_values'], true) ?: [];
            echo json_encode(['success' => true, 'data' => $row, 'fields' => $decoded]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data penerima tidak ditemukan']);
        }
        exit;
    }

    // Galeri Foto Handlers
    if ($action === 'get_galeri_foto') {
        $idPenerima = (int)($_POST['id_penerima'] ?? 0);
        if ($idPenerima <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID penerima tidak valid']);
            exit;
        }
        $fotos = get_dokumen_penerima_foto_list($conn, $idPenerima);
        $formatted = [];
        foreach ($fotos as $f) {
            $formatted[] = [
                'id' => (int)$f['id'],
                'url' => base_url($f['file_path']),
                'thumb' => base_url($f['file_thumbnail'] ?: $f['file_path']),
                'judul' => $f['judul_foto'] ?: 'Foto Dokumentasi',
                'size_kb' => round($f['file_size'] / 1024, 1),
                'created_at' => date('d/m/Y H:i', strtotime($f['created_at']))
            ];
        }
        echo json_encode(['success' => true, 'data' => $formatted]);
        exit;
    }

    if ($action === 'upload_galeri_foto') {
        $idPenerima = (int)($_POST['id_penerima'] ?? 0);
        $judul = trim($_POST['judul_foto'] ?? '');
        if ($idPenerima <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID penerima tidak valid']);
            exit;
        }

        if (empty($_FILES['foto_galeri']['name'][0])) {
            echo json_encode(['success' => false, 'message' => 'Silakan pilih minimal 1 berkas foto']);
            exit;
        }

        $resMax = $conn->query("SELECT COALESCE(MAX(urutan), 0) FROM dokumen_penerima_foto WHERE id_penerima_data = {$idPenerima}")->fetch_row();
        $currUrutan = (int)($resMax[0] ?? 0);

        $fileCount = count($_FILES['foto_galeri']['name']);
        $berhasil = 0;

        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['foto_galeri']['error'][$i] === UPLOAD_ERR_OK) {
                $fItem = [
                    'name' => $_FILES['foto_galeri']['name'][$i],
                    'type' => $_FILES['foto_galeri']['type'][$i],
                    'tmp_name' => $_FILES['foto_galeri']['tmp_name'][$i],
                    'error' => $_FILES['foto_galeri']['error'][$i],
                    'size' => $_FILES['foto_galeri']['size'][$i],
                ];
                $uploadResult = proses_upload_foto_penerima($fItem, $idPenerima);
                if ($uploadResult) {
                    $currUrutan++;
                    $judulFinal = $judul !== '' ? ($fileCount > 1 ? "$judul (" . ($i + 1) . ")" : $judul) : ('Foto ' . $currUrutan);
                    $stFoto = $conn->prepare("INSERT INTO dokumen_penerima_foto (id_penerima_data, file_path, file_thumbnail, judul_foto, file_size, urutan) VALUES (?, ?, ?, ?, ?, ?)");
                    $stFoto->bind_param('isssii', $idPenerima, $uploadResult['file_path'], $uploadResult['file_thumbnail'], $judulFinal, $uploadResult['file_size'], $currUrutan);
                    $stFoto->execute();
                    $stFoto->close();
                    $berhasil++;
                }
            }
        }

        clear_page_cache();
        if ($berhasil > 0) {
            echo json_encode(['success' => true, 'message' => "$berhasil foto berhasil diunggah & dikompresi"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengunggah foto. Pastikan format JPG, PNG, atau WEBP']);
        }
        exit;
    }

    if ($action === 'delete_galeri_foto') {
        $idFoto = (int)($_POST['id_foto'] ?? 0);
        if ($idFoto <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID foto tidak valid']);
            exit;
        }

        $stmt = $conn->prepare("SELECT * FROM dokumen_penerima_foto WHERE id = ?");
        $stmt->bind_param('i', $idFoto);
        $stmt->execute();
        $foto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($foto) {
            hapus_berkas_foto($foto['file_path'], $foto['file_thumbnail']);
            $conn->query("DELETE FROM dokumen_penerima_foto WHERE id = {$idFoto}");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Foto dokumentasi berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Foto tidak ditemukan']);
        }
        exit;
    }
}

// Ambil data penerima jika kategori aktif ada
if ($kategoriAktif) {
    $resData = $conn->query("SELECT p.*, 
        (SELECT COUNT(*) FROM dokumen_penerima_foto WHERE id_penerima_data = p.id) as total_foto,
        (SELECT file_thumbnail FROM dokumen_penerima_foto WHERE id_penerima_data = p.id ORDER BY urutan ASC, id ASC LIMIT 1) as foto_utama
        FROM dokumen_penerima_data p 
        WHERE p.id_kategori_dokumen = {$kategoriAktif['id']} 
        ORDER BY p.id ASC");
    if ($resData) {
        while ($r = $resData->fetch_assoc()) {
            $r['decoded'] = json_decode($r['data_values'], true) ?: [];
            $penerimaList[] = $r;
        }
    }
}

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <!-- Header Pemilih Dokumen & Statistik -->
    <div class="col-12 mb-3">
        <div class="card shadow-none border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-md-12 mb-2 mb-lg-0">
                        <label class="font-weight-bold text-dark mb-1">
                            <i class="fas fa-filter text-success mr-1"></i> Pilih Kategori / Program Dokumen:
                        </label>
                        <select class="form-control select2 border" style="width: 100%; border-color: #b8b8b8;" id="selectKategori" onchange="gantiKategori(this.value)">
                            <?php if (empty($kategoriList)): ?>
                                <option value="">-- Belum ada kategori dokumen --</option>
                            <?php else: ?>
                                <?php foreach ($kategoriList as $kat): ?>
                                    <option value="<?= $kat['id'] ?>" <?= $kat['id'] == $idKategoriAktif ? 'selected' : '' ?>>
                                        [<?= htmlspecialchars($kat['kode_kategori']) ?>] <?= htmlspecialchars($kat['nama_kategori']) ?> (<?= $kat['tahun_anggaran'] ?: '-' ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-lg-6 col-md-12 text-lg-right">
                        <div class="d-inline-flex flex-wrap align-items-center mt-2 mt-lg-0" style="gap: 8px;">
                            <a href="<?= url('pages/dokumen_kategori/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-cog mr-1"></i> Kelola Kategori
                            </a>
                            <?php if ($kategoriAktif): ?>
                                <a href="<?= url('pages/dokumen_kategori/kolom.php?id=' . $kategoriAktif['id']) ?>" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-columns mr-1"></i> Atur Header Kolom (<?= count($kolomList) ?>)
                                </a>
                                <a href="<?= url('pages/dokumen_program/cetak.php?id_kategori=' . $kategoriAktif['id']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-print mr-1"></i> Cetak Dokumen Resmi
                                </a>
                                <a href="<?= url('pages/dokumen_program/export_excel.php?id_kategori=' . $kategoriAktif['id']) ?>" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-file-excel mr-1"></i> Ekspor Excel
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$kategoriAktif): ?>
        <div class="col-12">
            <div class="card shadow-none border" style="border: 1px solid #b8b8b8 !important;">
                <div class="card-body text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3 d-block"></i>
                    <h4 class="font-weight-bold text-dark">Belum Ada Kategori Dokumen</h4>
                    <p class="text-muted">Silakan buat kategori dokumen program bantuan terlebih dahulu untuk mulai mengelola lembar data penerima.</p>
                    <a href="<?= url('pages/dokumen_kategori/index.php') ?>" class="btn btn-outline-success font-weight-bold">
                        <i class="fas fa-plus mr-1"></i> Buka Modul Kategori Dokumen
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (empty($kolomList)): ?>
        <div class="col-12">
            <div class="card shadow-none border" style="border: 1px solid #b8b8b8 !important;">
                <div class="card-body text-center py-5">
                    <i class="fas fa-columns fa-4x text-warning mb-3 d-block"></i>
                    <h4 class="font-weight-bold text-dark">Header Kolom Belum Dikonfigurasi</h4>
                    <p class="text-muted">Kategori <strong><?= htmlspecialchars($kategoriAktif['nama_kategori']) ?></strong> belum memiliki header kolom untuk lembar kerja dokumen.</p>
                    <a href="<?= url('pages/dokumen_kategori/kolom.php?id=' . $kategoriAktif['id']) ?>" class="btn btn-outline-success font-weight-bold">
                        <i class="fas fa-sliders-h mr-1"></i> Atur Header Kolom Sekarang
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Info Ringkas & Toolbar Tambah Data -->
        <div class="col-12">
            <div class="card card-outline card-success shadow-none border" style="border: 1px solid #b8b8b8 !important;">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
                    <div>
                        <h4 class="card-title font-weight-bold text-dark mb-0">
                            <i class="fas fa-file-alt text-success mr-2"></i><?= htmlspecialchars($kategoriAktif['nama_kategori']) ?>
                        </h4>
                        <?php if (!empty($kategoriAktif['deskripsi'])): ?>
                            <small class="text-muted d-block mt-1"><i class="fas fa-info-circle mr-1"></i><?= htmlspecialchars($kategoriAktif['deskripsi']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="card-tools ml-auto mt-2 mt-md-0">
                        <button type="button" class="btn btn-outline-success btn-sm font-weight-bold" onclick="openModalTambahPenerima()">
                            <i class="fas fa-plus mr-1"></i> Tambah Penerima Manfaat
                        </button>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="tablePenerima" class="table table-bordered table-hover mb-0" style="width: 100%;">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="4%" class="text-center align-middle">No</th>
                                    <?php foreach ($kolomList as $col): ?>
                                        <th class="align-middle <?= in_array($col['tipe_data'], ['currency', 'number']) ? 'text-right' : '' ?>"
                                            <?= !empty($col['lebar_kolom']) ? 'style="min-width: ' . htmlspecialchars($col['lebar_kolom']) . ';"' : '' ?>>
                                            <?= htmlspecialchars($col['nama_kolom']) ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th width="12%" class="text-center align-middle">Dokumentasi</th>
                                    <th width="8%" class="text-center align-middle">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($penerimaList)): ?>
                                    <tr>
                                        <td colspan="<?= count($kolomList) + 3 ?>" class="text-center py-4 text-muted">
                                            <i class="fas fa-user-friends fa-3x mb-3 d-block text-gray"></i>
                                            Belum ada data penerima bantuan dalam dokumen ini. Klik tombol <strong>Tambah Penerima Manfaat</strong> di atas untuk memasukkan data.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $no = 1; 
                                    $colTotals = [];
                                    foreach ($kolomList as $c) {
                                        if ($c['tipe_data'] === 'currency' || $c['tipe_data'] === 'number') {
                                            $colTotals[$c['key_kolom']] = 0;
                                        }
                                    }

                                    foreach ($penerimaList as $item): 
                                        $f = $item['decoded'];
                                        $totalFoto = (int)($item['total_foto'] ?? 0);
                                        // Cari nama representatif penerima untuk label galeri
                                        $labelNama = '';
                                        foreach ($kolomList as $col) {
                                            if (stripos($col['nama_kolom'], 'nama') !== false || stripos($col['key_kolom'], 'nama') !== false) {
                                                $labelNama = $f[$col['key_kolom']] ?? '';
                                                break;
                                            }
                                        }
                                        if (!$labelNama) {
                                            $labelNama = reset($f) ?: ('Penerima #' . $item['id']);
                                        }
                                    ?>
                                        <tr>
                                            <td class="text-center align-middle font-weight-bold"><?= $no++ ?></td>
                                            <?php foreach ($kolomList as $col): 
                                                $k = $col['key_kolom'];
                                                $val = $f[$k] ?? '';
                                                $tipe = $col['tipe_data'];
                                            ?>
                                                <td class="align-middle <?= in_array($tipe, ['currency', 'number']) ? 'text-right' : '' ?>">
                                                    <?php 
                                                    if ($tipe === 'currency') {
                                                        $numVal = (float)$val;
                                                        $colTotals[$k] = ($colTotals[$k] ?? 0) + $numVal;
                                                        echo '<strong class="text-success">Rp ' . number_format($numVal, 0, ',', '.') . '</strong>';
                                                    } elseif ($tipe === 'number') {
                                                        $numVal = (float)$val;
                                                        $colTotals[$k] = ($colTotals[$k] ?? 0) + $numVal;
                                                        echo number_format($numVal, 0, ',', '.');
                                                    } elseif ($tipe === 'date') {
                                                        echo !empty($val) ? date('d/m/Y', strtotime($val)) : '-';
                                                    } elseif ($tipe === 'textarea') {
                                                        echo nl2br(htmlspecialchars($val));
                                                    } else {
                                                        echo htmlspecialchars($val ?: '-');
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm <?= $totalFoto > 0 ? 'btn-outline-success font-weight-bold' : 'btn-outline-secondary' ?>" 
                                                        onclick="bukaGaleriFoto(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($labelNama)) ?>')"
                                                        title="<?= $totalFoto > 0 ? $totalFoto . ' Foto Dokumentasi Tersimpan' : 'Unggah Foto Dokumentasi' ?>"
                                                        style="border-radius: 4px; font-size: 0.82rem; padding: 3px 8px;">
                                                    <i class="fas fa-camera mr-1"></i>
                                                    <?= $totalFoto > 0 ? $totalFoto . ' Foto' : '+ Foto' ?>
                                                </button>
                                            </td>
                                            <td class="text-center align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" onclick="editPenerima(<?= $item['id'] ?>)" title="Ubah Data">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" onclick="hapusPenerima(<?= $item['id'] ?>)" title="Hapus Data">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <?php if (!empty($penerimaList) && !empty($colTotals)): ?>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td class="text-center align-middle">TOTAL</td>
                                        <?php foreach ($kolomList as $col): 
                                            $k = $col['key_kolom'];
                                            $tipe = $col['tipe_data'];
                                        ?>
                                            <td class="align-middle <?= in_array($tipe, ['currency', 'number']) ? 'text-right' : '' ?>">
                                                <?php 
                                                if ($tipe === 'currency') {
                                                    echo '<span class="text-success font-weight-bold">Rp ' . number_format($colTotals[$k] ?? 0, 0, ',', '.') . '</span>';
                                                } elseif ($tipe === 'number') {
                                                    echo number_format($colTotals[$k] ?? 0, 0, ',', '.');
                                                } else {
                                                    echo '';
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Form Penerima (Dinamis Sesuai Kolom Header) -->
<?php if ($kategoriAktif && !empty($kolomList)): ?>
<div class="modal fade" id="modalPenerima" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border shadow-none" style="border: 1px solid #b8b8b8 !important;">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="modalPenerimaTitle">
                    <i class="fas fa-user-plus text-success mr-2"></i>Tambah Penerima Manfaat
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formPenerima" enctype="multipart/form-data">
                <input type="hidden" name="action" id="penerimaAction" value="create_penerima">
                <input type="hidden" name="id" id="penerimaId" value="">
                <input type="hidden" name="id_kategori" value="<?= $kategoriAktif['id'] ?>">

                <div class="modal-body p-4">
                    <div class="row">
                        <?php foreach ($kolomList as $col): 
                            $k = $col['key_kolom'];
                            $tipe = $col['tipe_data'];
                            $req = $col['is_required'];
                            $colWidth = ($tipe === 'textarea') ? 'col-12' : 'col-md-6';
                        ?>
                            <div class="<?= $colWidth ?> mb-3">
                                <label for="field_<?= $k ?>" class="font-weight-bold">
                                    <?= htmlspecialchars($col['nama_kolom']) ?>
                                    <?php if ($req): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($tipe === 'textarea'): ?>
                                    <textarea class="form-control border" style="border-color: #b8b8b8;" id="field_<?= $k ?>" name="field_<?= $k ?>" rows="2" <?= $req ? 'required' : '' ?>></textarea>
                                
                                <?php elseif ($tipe === 'currency'): ?>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light border" style="border-color: #b8b8b8; font-weight: bold;">Rp</span>
                                        </div>
                                        <input type="text" class="form-control border input-currency" style="border-color: #b8b8b8;" id="field_<?= $k ?>" name="field_<?= $k ?>" placeholder="0" <?= $req ? 'required' : '' ?> onkeyup="formatRupiahInput(this)">
                                    </div>

                                <?php elseif ($tipe === 'number'): ?>
                                    <input type="number" class="form-control border" style="border-color: #b8b8b8;" id="field_<?= $k ?>" name="field_<?= $k ?>" <?= $req ? 'required' : '' ?>>

                                <?php elseif ($tipe === 'date'): ?>
                                    <input type="date" class="form-control border" style="border-color: #b8b8b8;" id="field_<?= $k ?>" name="field_<?= $k ?>" value="<?= date('Y-m-d') ?>" <?= $req ? 'required' : '' ?>>

                                <?php elseif ($tipe === 'select'): ?>
                                    <select class="form-control border" style="border-color: #b8b8b8;" id="field_<?= $k ?>" name="field_<?= $k ?>" <?= $req ? 'required' : '' ?>>
                                        <option value="">-- Pilih --</option>
                                        <?php 
                                        $opsiArr = array_map('trim', explode(',', $col['opsi_pilihan'] ?? ''));
                                        foreach ($opsiArr as $opt):
                                            if ($opt === '') continue;
                                        ?>
                                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                        <?php endforeach; ?>
                                    </select>

                                <?php else: ?>
                                    <input type="text" class="form-control border" style="border-color: #b8b8b8;" id="field_<?= $k ?>" name="field_<?= $k ?>" <?= $req ? 'required' : '' ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="col-12 mt-1">
                            <label for="catatan_khusus" class="font-weight-bold">Catatan Khusus / Internal (Opsional)</label>
                            <input type="text" class="form-control border" style="border-color: #b8b8b8;" id="catatan_khusus" name="catatan_khusus" placeholder="Catatan internal amil jika ada">
                        </div>

                        <!-- Unggah Multi-Foto Awal (Khusus Tambah Baru) -->
                        <div class="col-12 mt-3" id="wrapFotoAwal">
                            <div class="p-3 bg-light rounded border" style="border: 1px dashed #b8b8b8 !important;">
                                <label for="foto_dokumentasi" class="font-weight-bold text-dark mb-1">
                                    <i class="fas fa-camera text-success mr-1"></i> Unggah Foto Dokumentasi (Opsional - Multi Foto)
                                </label>
                                <input type="file" class="form-control-file border p-2 bg-white" style="border: 1px solid #b8b8b8 !important; border-radius: 4px; width: 100%;" id="foto_dokumentasi" name="foto_dokumentasi[]" multiple accept="image/jpeg,image/png,image/webp">
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-shield-alt text-info mr-1"></i> Sistem otomatis mengompresi dan mengubah ukuran gambar (maks 1280px, WebP/JPEG ~150KB) sehingga hosting/server tetap cepat, ringan, dan hemat memori. Foto lanjutan juga bisa dikelola di tombol galeri setelah disimpan.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-outline-success font-weight-bold" id="btnSubmitPenerima">
                        <i class="fas fa-save mr-1"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Galeri Multi-Foto Interaktif -->
<div class="modal fade" id="modalGaleriFoto" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border shadow-none" style="border: 1px solid #b8b8b8 !important;">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark mb-0" id="galeriModalTitle">
                        <i class="fas fa-images text-success mr-2"></i>Dokumentasi Foto Penerima Manfaat
                    </h5>
                    <small class="text-muted" id="galeriPenerimaSub">-</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <!-- Panel Unggah Foto Tambahan -->
                <div class="card shadow-none border mb-4" style="border: 1px solid #b8b8b8 !important; background-color: #fbfbfb;">
                    <div class="card-body p-3">
                        <form id="formUploadGaleri" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_galeri_foto">
                            <input type="hidden" name="id_penerima" id="galeriIdPenerima" value="">
                            
                            <div class="row align-items-end">
                                <div class="col-md-5 mb-2 mb-md-0">
                                    <label class="font-weight-bold small text-dark mb-1">
                                        <i class="fas fa-file-image text-success mr-1"></i> Pilih Foto (Bisa Banyak / Multi-Select):
                                    </label>
                                    <input type="file" class="form-control-file border p-1 bg-white" style="border: 1px solid #b8b8b8 !important; border-radius: 4px; width: 100%; font-size: 0.85rem;" name="foto_galeri[]" id="inputFotoGaleri" multiple accept="image/jpeg,image/png,image/webp" required>
                                </div>
                                <div class="col-md-4 mb-2 mb-md-0">
                                    <label class="font-weight-bold small text-dark mb-1">Keterangan / Judul Foto (Opsional):</label>
                                    <input type="text" class="form-control form-control-sm border" style="border-color: #b8b8b8;" name="judul_foto" placeholder="Contoh: Penyerahan Bantuan / Rumah">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-outline-success btn-sm btn-block font-weight-bold" id="btnUploadGaleri">
                                        <i class="fas fa-cloud-upload-alt mr-1"></i> Unggah & Kompresi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Kontainer Daftar Foto (Grid Galeri) -->
                <div id="galeriLoading" class="text-center py-4 d-none">
                    <i class="fas fa-circle-notch fa-spin fa-2x text-success mb-2"></i>
                    <p class="text-muted small">Memuat galeri foto...</p>
                </div>

                <div id="galeriEmpty" class="text-center py-5 d-none">
                    <i class="fas fa-camera-retro fa-3x text-muted mb-2 d-block"></i>
                    <h6 class="font-weight-bold text-dark">Belum Ada Foto Dokumentasi</h6>
                    <p class="text-muted small mb-0">Pilih berkas foto di atas untuk menambahkan dokumentasi penyerahan bantuan.</p>
                </div>

                <div class="row" id="galeriGrid">
                    <!-- Foto di-render via AJAX -->
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Tutup Galeri
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pratinjau Foto Resolusi Penuh -->
<div class="modal fade" id="modalPreviewFoto" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border shadow-none" style="border: 1px solid #b8b8b8 !important;">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title font-weight-bold" id="previewFotoTitle">Pratinjau Foto Dokumentasi</h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2 text-center bg-dark">
                <img src="" id="imgPreviewBesar" class="img-fluid rounded" style="max-height: 75vh; object-fit: contain;" alt="Preview Foto">
            </div>
            <div class="modal-footer bg-light py-2 justify-content-between">
                <span class="text-muted small" id="previewFotoMeta">-</span>
                <a href="" target="_blank" id="btnDownloadFoto" class="btn btn-outline-primary btn-sm" download>
                    <i class="fas fa-download mr-1"></i> Buka / Unduh Berkas Asli
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.table tr:hover {
    background-color: #f0f7f3 !important;
    transition: background-color 0.15s ease-in-out;
}
.card, .modal-content {
    box-shadow: none !important;
}
.galeri-card {
    border: 1px solid #b8b8b8 !important;
    transition: transform 0.15s ease, border-color 0.15s ease;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}
.galeri-card:hover {
    border-color: #0d9f4f !important;
}
.galeri-thumb-wrap {
    height: 160px;
    background-color: #e9ecef;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.galeri-thumb-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s ease;
}
.galeri-thumb-wrap:hover img {
    transform: scale(1.05);
}
</style>

<script>
$(document).ready(function() {
    $('.select2').select2({ theme: 'bootstrap4' });

    <?php if (!empty($penerimaList)): ?>
    $('#tablePenerima').DataTable({
        "language": {
            "search": "Cari Penerima:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ penerima",
            "paginate": { "first": "«", "last": "»", "next": "›", "previous": "‹" }
        },
        "order": [[0, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [-1, -2] }
        ]
    });
    <?php endif; ?>
});

function gantiKategori(id) {
    if (id) {
        window.location.href = '<?= url("pages/dokumen_program/index.php?id_kategori=") ?>' + id;
    }
}

function formatRupiahInput(el) {
    let val = el.value.replace(/[^0-9]/g, '');
    if (val === '') {
        el.value = '';
    } else {
        el.value = new Intl.NumberFormat('id-ID').format(val);
    }
}

function openModalTambahPenerima() {
    $('#formPenerima')[0].reset();
    $('#penerimaAction').val('create_penerima');
    $('#penerimaId').val('');
    $('#wrapFotoAwal').show();
    $('#modalPenerimaTitle').html('<i class="fas fa-user-plus text-success mr-2"></i>Tambah Penerima Manfaat');
    $('#modalPenerima').modal('show');
}

const ajaxDocUrl = '<?= url("pages/dokumen_program/index.php?id_kategori=" . ($kategoriAktif['id'] ?? 0)) ?>';

function editPenerima(id) {
    $.post(ajaxDocUrl, { action: 'get_penerima', id: id }, function(res) {
        if (res.success) {
            $('#formPenerima')[0].reset();
            $('#penerimaAction').val('update_penerima');
            $('#penerimaId').val(res.data.id);
            $('#catatan_khusus').val(res.data.catatan_khusus || '');
            $('#wrapFotoAwal').hide(); // Kelola foto dilakukan di tombol Galeri

            const fields = res.fields || {};
            for (let key in fields) {
                const el = $('#field_' + key);
                if (el.length) {
                    if (el.hasClass('input-currency')) {
                        let num = fields[key];
                        el.val(new Intl.NumberFormat('id-ID').format(num));
                    } else {
                        el.val(fields[key]);
                    }
                }
            }

            $('#modalPenerimaTitle').html('<i class="fas fa-user-edit text-primary mr-2"></i>Ubah Data Penerima');
            $('#modalPenerima').modal('show');
        } else {
            showError(res.message);
        }
    }, 'json');
}

function hapusPenerima(id) {
    confirmDelete(function() {
        $.post(ajaxDocUrl, { action: 'delete_penerima', id: id }, function(res) {
            if (res.success) {
                showSuccess(res.message);
                setTimeout(() => window.location.reload(), 500);
            } else {
                showError(res.message);
            }
        }, 'json');
    }, 'Apakah Anda yakin ingin menghapus data penerima bantuan beserta seluruh foto dokumentasinya?');
}

$('#formPenerima').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnSubmitPenerima');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan & Mengompresi...');

    const formData = new FormData(this);

    $.ajax({
        url: ajaxDocUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Data');
            if (res.success) {
                $('#modalPenerima').modal('hide');
                showSuccess(res.message);
                setTimeout(() => window.location.reload(), 500);
            } else {
                showError(res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Data');
            showError('Terjadi kesalahan jaringan atau server saat menyimpan data.');
        }
    });
});

/* =========================================================
   Fungsi Manajemen Galeri Multi-Foto Interaktif
   ========================================================= */
let currentActivePenerimaId = 0;

function bukaGaleriFoto(idPenerima, namaPenerima) {
    currentActivePenerimaId = idPenerima;
    $('#galeriIdPenerima').val(idPenerima);
    $('#galeriPenerimaSub').text('Penerima: ' + namaPenerima);
    $('#formUploadGaleri')[0].reset();
    $('#modalGaleriFoto').modal('show');
    loadGaleriFoto(idPenerima);
}

function loadGaleriFoto(idPenerima) {
    $('#galeriLoading').removeClass('d-none');
    $('#galeriEmpty').addClass('d-none');
    $('#galeriGrid').empty();

    $.post(ajaxDocUrl, { action: 'get_galeri_foto', id_penerima: idPenerima }, function(res) {
        $('#galeriLoading').addClass('d-none');
        if (res.success) {
            const fotos = res.data || [];
            if (fotos.length === 0) {
                $('#galeriEmpty').removeClass('d-none');
            } else {
                let html = '';
                fotos.forEach(function(f, idx) {
                    html += `
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3" id="fotoCard_${f.id}">
                            <div class="galeri-card h-100 d-flex flex-column">
                                <div class="galeri-thumb-wrap" onclick="previewFotoBesar('${f.url}', '${escapeHtml(f.judul)}', '${f.size_kb} KB | ${f.created_at}')">
                                    <img src="${f.thumb}" alt="${escapeHtml(f.judul)}" loading="lazy">
                                    <span class="badge badge-dark position-absolute" style="bottom: 6px; right: 6px; font-size: 0.72rem; opacity: 0.85;">
                                        ${f.size_kb} KB
                                    </span>
                                </div>
                                <div class="p-2 d-flex flex-column flex-grow-1 justify-content-between">
                                    <div>
                                        <div class="font-weight-bold text-truncate text-dark small" title="${escapeHtml(f.judul)}">${escapeHtml(f.judul)}</div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="far fa-clock mr-1"></i>${f.created_at}</small>
                                    </div>
                                    <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-outline-info btn-xs py-0 px-2" style="font-size: 0.75rem;" onclick="previewFotoBesar('${f.url}', '${escapeHtml(f.judul)}', '${f.size_kb} KB | ${f.created_at}')">
                                            <i class="fas fa-eye mr-1"></i> Lihat
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2" style="font-size: 0.75rem;" onclick="hapusFotoGaleri(${f.id})">
                                            <i class="fas fa-trash-alt mr-1"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#galeriGrid').html(html);
            }
        } else {
            showError(res.message);
        }
    }, 'json').fail(function() {
        $('#galeriLoading').addClass('d-none');
        showError('Gagal memuat galeri foto.');
    });
}

$('#formUploadGaleri').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnUploadGaleri');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengompresi...');

    const formData = new FormData(this);

    $.ajax({
        url: ajaxDocUrl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt mr-1"></i> Unggah & Kompresi');
            if (res.success) {
                showSuccess(res.message);
                $('#formUploadGaleri')[0].reset();
                loadGaleriFoto(currentActivePenerimaId);
            } else {
                showError(res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt mr-1"></i> Unggah & Kompresi');
            showError('Terjadi kesalahan jaringan atau berkas terlalu besar.');
        }
    });
});

function hapusFotoGaleri(idFoto) {
    confirmDelete(function() {
        $.post(ajaxDocUrl, { action: 'delete_galeri_foto', id_foto: idFoto }, function(res) {
            if (res.success) {
                $('#fotoCard_' + idFoto).fadeOut(200, function() {
                    $(this).remove();
                    if ($('#galeriGrid').children().length === 0) {
                        $('#galeriEmpty').removeClass('d-none');
                    }
                });
                showSuccess(res.message);
            } else {
                showError(res.message);
            }
        }, 'json');
    }, 'Hapus berkas foto dokumentasi ini? Berkas fisik di server juga akan dihapus.');
}

function previewFotoBesar(url, judul, meta) {
    $('#imgPreviewBesar').attr('src', url);
    $('#previewFotoTitle').text(judul || 'Pratinjau Foto Dokumentasi');
    $('#previewFotoMeta').text(meta || '');
    $('#btnDownloadFoto').attr('href', url);
    $('#modalPreviewFoto').modal('show');
}

function escapeHtml(text) {
    if (!text) return '';
    return $('<div>').text(text).html();
}

// Refresh tabel saat modal galeri ditutup untuk sinkronisasi jumlah foto
$('#modalGaleriFoto').on('hidden.bs.modal', function() {
    window.location.reload();
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>

