<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/dokumen_db.php';
require_auth();

$idKategori = (int)($_GET['id'] ?? 0);
if ($idKategori <= 0) {
    redirect_with_flash(url('pages/dokumen_kategori/index.php'), 'Kategori dokumen tidak ditemukan.', 'danger');
}

$conn = db();
ensure_dokumen_tables($conn);

$kategori = get_dokumen_kategori_by_id($conn, $idKategori);
if (!$kategori) {
    redirect_with_flash(url('pages/dokumen_kategori/index.php'), 'Kategori dokumen tidak ditemukan di database.', 'danger');
}

$activeMenu = 'dokumen_kategori';
$pageTitle = 'Atur Header Kolom: ' . htmlspecialchars($kategori['nama_kategori']);
$pageDescription = 'Konfigurasi kolom header dinamis untuk lembar dokumen penerima bantuan';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li>' .
               '<li class="breadcrumb-item"><a href="' . url('pages/dokumen_kategori/index.php') . '">Kategori Dokumen</a></li>' .
               '<li class="breadcrumb-item active">Atur Header Kolom</li>';

// AJAX Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $namaKolom = trim($_POST['nama_kolom'] ?? '');
        $keyKolom = trim($_POST['key_kolom'] ?? '');
        $tipeData = $_POST['tipe_data'] ?? 'text';
        $opsiPilihan = trim($_POST['opsi_pilihan'] ?? '');
        $isRequired = !empty($_POST['is_required']) ? 1 : 0;
        $urutan = (int)($_POST['urutan'] ?? 1);
        $lebarKolom = trim($_POST['lebar_kolom'] ?? '');

        if ($namaKolom === '') {
            echo json_encode(['success' => false, 'message' => 'Nama label header kolom wajib diisi']);
            exit;
        }

        if ($keyKolom === '') {
            $keyKolom = generate_key_kolom($namaKolom);
        } else {
            $keyKolom = generate_key_kolom($keyKolom);
        }

        // Cek duplikasi key_kolom pada kategori ini
        $stmt = $conn->prepare("SELECT id FROM dokumen_kategori_kolom WHERE id_kategori_dokumen = ? AND key_kolom = ?");
        $stmt->bind_param('is', $idKategori, $keyKolom);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $keyKolom .= '_' . substr(md5(uniqid()), 0, 4);
        }
        $stmt->close();

        // Jika urutan default/0, set urutan terakhir + 1
        if ($urutan <= 0) {
            $resMax = $conn->query("SELECT COALESCE(MAX(urutan), 0) + 1 FROM dokumen_kategori_kolom WHERE id_kategori_dokumen = {$idKategori}")->fetch_row();
            $urutan = (int)($resMax[0] ?? 1);
        }

        $stmt = $conn->prepare("INSERT INTO dokumen_kategori_kolom (id_kategori_dokumen, nama_kolom, key_kolom, tipe_data, opsi_pilihan, is_required, urutan, lebar_kolom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssiss', $idKategori, $namaKolom, $keyKolom, $tipeData, $opsiPilihan, $isRequired, $urutan, $lebarKolom);

        if ($stmt->execute()) {
            $insertedId = $conn->insert_id;
            log_audit('CREATE_DOKUMEN_KOLOM', 'dokumen_kategori_kolom', $insertedId, "Kategori: {$kategori['nama_kategori']}, Kolom: $namaKolom ($keyKolom)");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Header kolom berhasil ditambahkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan kolom: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $namaKolom = trim($_POST['nama_kolom'] ?? '');
        $keyKolom = trim($_POST['key_kolom'] ?? '');
        $tipeData = $_POST['tipe_data'] ?? 'text';
        $opsiPilihan = trim($_POST['opsi_pilihan'] ?? '');
        $isRequired = !empty($_POST['is_required']) ? 1 : 0;
        $urutan = (int)($_POST['urutan'] ?? 1);
        $lebarKolom = trim($_POST['lebar_kolom'] ?? '');

        if ($id <= 0 || $namaKolom === '') {
            echo json_encode(['success' => false, 'message' => 'Data input tidak valid']);
            exit;
        }

        if ($keyKolom === '') {
            $keyKolom = generate_key_kolom($namaKolom);
        } else {
            $keyKolom = generate_key_kolom($keyKolom);
        }

        $stmt = $conn->prepare("UPDATE dokumen_kategori_kolom SET nama_kolom = ?, key_kolom = ?, tipe_data = ?, opsi_pilihan = ?, is_required = ?, urutan = ?, lebar_kolom = ? WHERE id = ? AND id_kategori_dokumen = ?");
        $stmt->bind_param('ssssissii', $namaKolom, $keyKolom, $tipeData, $opsiPilihan, $isRequired, $urutan, $lebarKolom, $id, $idKategori);

        if ($stmt->execute()) {
            log_audit('UPDATE_DOKUMEN_KOLOM', 'dokumen_kategori_kolom', $id, "Kolom: $namaKolom ($keyKolom)");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Header kolom berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID kolom tidak valid']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM dokumen_kategori_kolom WHERE id = ? AND id_kategori_dokumen = ?");
        $stmt->bind_param('ii', $id, $idKategori);
        if ($stmt->execute()) {
            log_audit('DELETE_DOKUMEN_KOLOM', 'dokumen_kategori_kolom', $id, "Kategori: {$idKategori}");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Header kolom berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM dokumen_kategori_kolom WHERE id = ? AND id_kategori_dokumen = ?");
        $stmt->bind_param('ii', $id, $idKategori);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        exit;
    }

    // Terapkan Template Default BAZNAS
    if ($action === 'apply_preset') {
        $preset = $_POST['preset'] ?? 'standar';
        $columns = [];

        if ($preset === 'standar') {
            $columns = [
                ['nama' => 'Nama Penerima Manfaat', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nomor KTP / NIK', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nomor KK', 'tipe' => 'text', 'req' => 0],
                ['nama' => 'Alamat Lengkap', 'tipe' => 'textarea', 'req' => 1],
                ['nama' => 'Desa / Kelurahan', 'tipe' => 'text', 'req' => 0],
                ['nama' => 'Kecamatan', 'tipe' => 'text', 'req' => 0],
                ['nama' => 'Nomor Telepon / HP', 'tipe' => 'text', 'req' => 0],
                ['nama' => 'Bentuk Bantuan', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nominal Bantuan (Rp)', 'tipe' => 'currency', 'req' => 0],
                ['nama' => 'Keterangan', 'tipe' => 'text', 'req' => 0],
            ];
        } elseif ($preset === 'gerobak') {
            $columns = [
                ['nama' => 'Nama Pelaku Usaha', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nomor KTP / NIK', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Alamat Domisili', 'tipe' => 'textarea', 'req' => 1],
                ['nama' => 'Jenis Usaha Dagang', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Lokasi Pangkalan Jualan', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nomor HP Aktif', 'tipe' => 'text', 'req' => 0],
                ['nama' => 'Biaya Pembuatan Gerobak (Rp)', 'tipe' => 'currency', 'req' => 1],
                ['nama' => 'Tanggal Serah Terima', 'tipe' => 'date', 'req' => 1],
                ['nama' => 'Keterangan / Kondisi', 'tipe' => 'text', 'req' => 0],
            ];
        } elseif ($preset === 'mahyani') {
            $columns = [
                ['nama' => 'Nama Kepala Keluarga', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nomor KTP / NIK', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Nomor Kartu Keluarga', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Alamat Rumah / Dusun', 'tipe' => 'textarea', 'req' => 1],
                ['nama' => 'Desa / Kelurahan', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Kecamatan', 'tipe' => 'text', 'req' => 1],
                ['nama' => 'Status Kepemilikan Tanah', 'tipe' => 'select', 'opsi' => 'Milik Sendiri (SHM), Hibah / Waris, Surat Keterangan Tanah (SKT), Lainnya', 'req' => 1],
                ['nama' => 'Nominal Bantuan Bedah Rumah (Rp)', 'tipe' => 'currency', 'req' => 1],
                ['nama' => 'Tahap Penyaluran', 'tipe' => 'select', 'opsi' => 'Tahap 1, Tahap 2, Selesai 100%', 'req' => 1],
                ['nama' => 'Keterangan', 'tipe' => 'text', 'req' => 0],
            ];
        }

        $resMax = $conn->query("SELECT COALESCE(MAX(urutan), 0) FROM dokumen_kategori_kolom WHERE id_kategori_dokumen = {$idKategori}")->fetch_row();
        $currUrutan = (int)($resMax[0] ?? 0);

        foreach ($columns as $col) {
            $currUrutan++;
            $kName = $col['nama'];
            $kKey = generate_key_kolom($kName);
            $kTipe = $col['tipe'];
            $kReq = $col['req'];
            $kOpsi = $col['opsi'] ?? '';

            // Cek jika key sudah ada
            $chk = $conn->prepare("SELECT id FROM dokumen_kategori_kolom WHERE id_kategori_dokumen = ? AND key_kolom = ?");
            $chk->bind_param('is', $idKategori, $kKey);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $kKey .= '_' . substr(md5(uniqid()), 0, 3);
            }
            $chk->close();

            $stmt = $conn->prepare("INSERT INTO dokumen_kategori_kolom (id_kategori_dokumen, nama_kolom, key_kolom, tipe_data, opsi_pilihan, is_required, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issssii', $idKategori, $kName, $kKey, $kTipe, $kOpsi, $kReq, $currUrutan);
            $stmt->execute();
            $stmt->close();
        }

        log_audit('APPLY_PRESET_KOLOM', 'dokumen_kategori_kolom', $idKategori, "Preset: $preset");
        clear_page_cache();
        echo json_encode(['success' => true, 'message' => 'Template header kolom berhasil diterapkan']);
        exit;
    }
}

$kolomList = get_dokumen_kolom_list($conn, $idKategori);

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <!-- Panel Info Kategori & Aksi Cepat -->
    <div class="col-md-12 mb-3">
        <div class="card shadow-none border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <a href="<?= url('pages/dokumen_kategori/index.php') ?>" class="btn btn-outline-secondary btn-sm mb-2 mr-2">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Kategori
                    </a>
                    <span class="badge badge-light border text-monospace px-2 py-1 mr-2"><?= htmlspecialchars($kategori['kode_kategori']) ?></span>
                    <h4 class="d-inline-block font-weight-bold text-dark mb-0 align-middle"><?= htmlspecialchars($kategori['nama_kategori']) ?></h4>
                    <?php if (!empty($kategori['tahun_anggaran'])): ?>
                        <span class="badge badge-secondary ml-2">Tahun <?= htmlspecialchars($kategori['tahun_anggaran']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="mt-2 mt-md-0">
                    <a href="<?= url('pages/dokumen_program/index.php?id_kategori=' . $kategori['id']) ?>" class="btn btn-outline-success font-weight-bold btn-sm">
                        <i class="fas fa-table mr-1"></i> Buka Lembar Dokumen Kerja
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Header Kolom -->
    <div class="col-md-8">
        <div class="card card-outline card-success shadow-none border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-columns text-success mr-2"></i>Header Kolom Dinamis Saat Ini (<?= count($kolomList) ?> Kolom)
                </h3>
            </div>

            <div class="card-body p-3">
                <?php if (empty($kolomList)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-columns fa-3x mb-3 d-block text-gray"></i>
                        <p class="font-weight-bold mb-1">Belum ada header kolom yang ditentukan untuk kategori ini.</p>
                        <p class="small text-muted">Silakan tambahkan kolom satu per satu menggunakan form di sebelah kanan, atau gunakan <strong>Template Cepat</strong> di bawah.</p>
                        
                        <div class="btn-group mt-2">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="applyPreset('standar')">
                                <i class="fas fa-magic mr-1"></i> Gunakan Template Standar BAZNAS
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="applyPreset('gerobak')">
                                <i class="fas fa-shopping-cart mr-1"></i> Template Gerobak Usaha
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="applyPreset('mahyani')">
                                <i class="fas fa-home mr-1"></i> Template Mahyani (Bedah Rumah)
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" id="tableKolom">
                            <thead class="bg-light text-dark">
                                <tr>
                                    <th width="8%" class="text-center align-middle">Urutan</th>
                                    <th class="align-middle">Label Kolom (Header)</th>
                                    <th width="20%" class="align-middle">ID Key Kolom</th>
                                    <th width="15%" class="text-center align-middle">Tipe Input</th>
                                    <th width="12%" class="text-center align-middle">Wajib Diisi</th>
                                    <th width="15%" class="text-center align-middle">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kolomList as $col): ?>
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold">
                                            <span class="badge badge-light border px-2 py-1"><?= (int)$col['urutan'] ?></span>
                                        </td>
                                        <td class="align-middle">
                                            <strong class="text-dark"><?= htmlspecialchars($col['nama_kolom']) ?></strong>
                                            <?php if ($col['tipe_data'] === 'select' && !empty($col['opsi_pilihan'])): ?>
                                                <small class="d-block text-muted mt-1"><i class="fas fa-list-ul mr-1"></i>Opsi: <?= htmlspecialchars($col['opsi_pilihan']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle text-monospace small text-muted">
                                            <?= htmlspecialchars($col['key_kolom']) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php
                                            $tipeBadges = [
                                                'text' => '<span class="badge badge-light border">Teks</span>',
                                                'number' => '<span class="badge badge-info" style="background:#e0f2fe; color:#0369a1; border:1px solid #7dd3fc;">Angka</span>',
                                                'currency' => '<span class="badge badge-success" style="background:#dcfce7; color:#15803d; border:1px solid #86efac;">Nominal (Rp)</span>',
                                                'date' => '<span class="badge badge-warning" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">Tanggal</span>',
                                                'textarea' => '<span class="badge badge-secondary" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;">Teks Panjang</span>',
                                                'select' => '<span class="badge badge-primary" style="background:#ede9fe; color:#6d28d9; border:1px solid #c4b5fd;">Dropdown</span>',
                                            ];
                                            echo $tipeBadges[$col['tipe_data']] ?? '<span class="badge badge-light border">' . htmlspecialchars($col['tipe_data']) . '</span>';
                                            ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($col['is_required']): ?>
                                                <span class="badge" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">Wajib</span>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;">Opsional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="editKolom(<?= $col['id'] ?>)" title="Ubah Kolom">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="hapusKolom(<?= $col['id'] ?>, '<?= htmlspecialchars(addslashes($col['nama_kolom'])) ?>')" title="Hapus Kolom">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form Tambah / Edit Kolom -->
    <div class="col-md-4">
        <div class="card card-outline card-success shadow-none border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-dark mb-0" id="cardKolomTitle">
                    <i class="fas fa-plus-circle text-success mr-2"></i>Tambah Header Kolom
                </h3>
            </div>

            <form id="formKolom">
                <input type="hidden" name="action" id="kolomAction" value="create">
                <input type="hidden" name="id" id="kolomId" value="">

                <div class="card-body p-3">
                    <div class="form-group">
                        <label for="nama_kolom" class="font-weight-bold">Nama Kolom (Label Header) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control border" style="border-color: #b8b8b8;" id="nama_kolom" name="nama_kolom" required placeholder="Contoh: Nomor KTP / NIK" onkeyup="autoFillKey()">
                        <small class="text-muted">Nama yang akan tampil sebagai kepala tabel di lembar dokumen & cetak.</small>
                    </div>

                    <div class="form-group">
                        <label for="key_kolom" class="font-weight-bold">ID Kunci Kolom (Key)</label>
                        <input type="text" class="form-control border text-monospace" style="border-color: #b8b8b8;" id="key_kolom" name="key_kolom" placeholder="Contoh: nomor_ktp_nik">
                        <small class="text-muted">Identitas penyimpanan data JSON (otomatis dibuat dari label).</small>
                    </div>

                    <div class="form-group">
                        <label for="tipe_data" class="font-weight-bold">Tipe Data Input <span class="text-danger">*</span></label>
                        <select class="form-control border" style="border-color: #b8b8b8;" id="tipe_data" name="tipe_data" onchange="toggleOpsiPilihan()">
                            <option value="text">Teks Singkat (Nama, No KTP, Alamat singkat)</option>
                            <option value="currency">Nominal Uang / Rupiah (Rp) - Total Otomatis</option>
                            <option value="number">Angka Bulat (Jumlah, Umur)</option>
                            <option value="date">Tanggal (Penyaluran, Lahir)</option>
                            <option value="textarea">Teks Panjang / Catatan (Alamat detail)</option>
                            <option value="select">Pilihan Dropdown (Pilihan tetap)</option>
                        </select>
                    </div>

                    <div class="form-group" id="groupOpsiPilihan" style="display: none;">
                        <label for="opsi_pilihan" class="font-weight-bold">Daftar Pilihan Dropdown <span class="text-danger">*</span></label>
                        <input type="text" class="form-control border" style="border-color: #b8b8b8;" id="opsi_pilihan" name="opsi_pilihan" placeholder="Contoh: Laki-laki, Perempuan">
                        <small class="text-muted">Pisahkan setiap opsi pilihan dengan tanda koma (,).</small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="urutan" class="font-weight-bold">Nomor Urut</label>
                                <input type="number" class="form-control border" style="border-color: #b8b8b8;" id="urutan" name="urutan" value="<?= count($kolomList) + 1 ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="is_required" class="font-weight-bold">Kewajiban Isi</label>
                                <select class="form-control border" style="border-color: #b8b8b8;" id="is_required" name="is_required">
                                    <option value="0">Opsional</option>
                                    <option value="1">Wajib Diisi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light p-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetFormKolom()">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-outline-success font-weight-bold btn-sm" id="btnSubmitKolom">
                        <i class="fas fa-save mr-1"></i> Simpan Kolom
                    </button>
                </div>
            </form>
        </div>

        <!-- Card Template Header Cepat -->
        <div class="card shadow-none border mt-3" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header bg-light py-2">
                <h6 class="font-weight-bold text-dark mb-0"><i class="fas fa-magic text-warning mr-1"></i> Template Header Cepat</h6>
            </div>
            <div class="card-body p-2">
                <small class="text-muted d-block mb-2">Tambahkan set kolom otomatis sesuai standar program BAZNAS:</small>
                <div class="d-flex flex-column" style="gap: 6px;">
                    <button type="button" class="btn btn-outline-secondary btn-xs text-left" onclick="applyPreset('standar')">
                        <i class="fas fa-plus-circle text-success mr-1"></i> Standar Penerima BAZNAS (10 Kolom)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-xs text-left" onclick="applyPreset('gerobak')">
                        <i class="fas fa-plus-circle text-info mr-1"></i> Penerima Bantuan Gerobak (9 Kolom)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-xs text-left" onclick="applyPreset('mahyani')">
                        <i class="fas fa-plus-circle text-primary mr-1"></i> Bedah Rumah / Mahyani (10 Kolom)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table tr:hover {
    background-color: #f0f7f3 !important;
    transition: background-color 0.15s ease-in-out;
}
.card {
    box-shadow: none !important;
}
</style>

<script>
function autoFillKey() {
    if ($('#kolomAction').val() === 'create') {
        const val = $('#nama_kolom').val();
        const slug = val.toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        $('#key_kolom').val(slug);
    }
}

function toggleOpsiPilihan() {
    const tipe = $('#tipe_data').val();
    if (tipe === 'select') {
        $('#groupOpsiPilihan').slideDown(150);
    } else {
        $('#groupOpsiPilihan').slideUp(150);
    }
}

function resetFormKolom() {
    $('#formKolom')[0].reset();
    $('#kolomAction').val('create');
    $('#kolomId').val('');
    $('#urutan').val('<?= count($kolomList) + 1 ?>');
    $('#cardKolomTitle').html('<i class="fas fa-plus-circle text-success mr-2"></i>Tambah Header Kolom');
    toggleOpsiPilihan();
}

const ajaxKolomUrl = '<?= url("pages/dokumen_kategori/kolom.php?id=" . $idKategori) ?>';

function editKolom(id) {
    $.post(ajaxKolomUrl, { action: 'get', id: id }, function(res) {
        if (res.success) {
            $('#kolomAction').val('update');
            $('#kolomId').val(res.data.id);
            $('#nama_kolom').val(res.data.nama_kolom);
            $('#key_kolom').val(res.data.key_kolom);
            $('#tipe_data').val(res.data.tipe_data);
            $('#opsi_pilihan').val(res.data.opsi_pilihan);
            $('#urutan').val(res.data.urutan);
            $('#is_required').val(res.data.is_required);
            $('#cardKolomTitle').html('<i class="fas fa-edit text-primary mr-2"></i>Ubah Header Kolom');
            toggleOpsiPilihan();
            $('html, body').animate({ scrollTop: $('#formKolom').offset().top - 100 }, 300);
        } else {
            showError(res.message);
        }
    }, 'json');
}

function hapusKolom(id, nama) {
    confirmDelete(function() {
        $.post(ajaxKolomUrl, { action: 'delete', id: id }, function(res) {
            if (res.success) {
                showSuccess(res.message);
                setTimeout(() => window.location.reload(), 500);
            } else {
                showError(res.message);
            }
        }, 'json');
    }, 'Apakah Anda yakin ingin menghapus kolom header "' + nama + '"?');
}

function applyPreset(preset) {
    Swal.fire({
        title: 'Terapkan Template Kolom?',
        text: 'Sistem akan menambahkan rangkaian kolom header otomatis untuk template ini ke dalam kategori.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d9f4f',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Terapkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(ajaxKolomUrl, { action: 'apply_preset', preset: preset }, function(res) {
                if (res.success) {
                    showSuccess(res.message);
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showError(res.message);
                }
            }, 'json');
        }
    });
}

$('#formKolom').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnSubmitKolom');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

    $.post(ajaxKolomUrl, $(this).serialize(), function(res) {
        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Kolom');
        if (res.success) {
            showSuccess(res.message);
            setTimeout(() => window.location.reload(), 500);
        } else {
            showError(res.message);
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Kolom');
        showError('Terjadi kesalahan jaringan atau server');
    });
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
