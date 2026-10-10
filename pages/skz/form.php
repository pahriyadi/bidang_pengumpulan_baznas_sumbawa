<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

$activeMenu = 'skz';
$conn = db();
ensure_skz_table($conn);

$id = (int)($_GET['id'] ?? 0);
$isEdit = ($id > 0);

$pageTitle = $isEdit ? 'Edit SKZ & BSZIS' : 'Buat SKZ & BSZIS Baru';
$pageDescription = 'Formulir Surat Ketetapan Zakat dan Bukti Setoran ZIS';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item"><a href="' . url('pages/skz/index.php') . '">SKZ & BSZIS</a></li><li class="breadcrumb-item active">' . ($isEdit ? 'Edit' : 'Tambah') . '</li>';

$data = [
    'id' => 0,
    'nomor_urut' => '',
    'nomor_surat' => '',
    'tanggal_surat' => date('Y-m-d'),
    'nama_pimpinan' => get_setting('skz_nama_pimpinan', 'Syukri Rahmat, S.Ag., M.M Inov'),
    'jabatan_pimpinan' => get_setting('skz_jabatan_pimpinan', 'Ketua BAZNAS Kabupaten Sumbawa'),
    'kota_surat' => get_setting('skz_kota', 'Sumbawa Besar'),
    'id_upz_badan_usaha' => '',
    'nama_badan_usaha' => '',
    'alamat_badan_usaha' => '',
    'id_upz_dinas' => '',
    'dinas_instansi' => '',
    'paket_pekerjaan' => '',
    'nilai_kontrak' => '0',
    'persentase_keuntungan' => get_setting('skz_default_profit_percent', '6.00'),
    'estimasi_keuntungan' => '0',
    'persentase_zis' => '2.50',
    'nilai_zis' => '0',
    'id_objek_zis' => '',
    'objek_zis' => 'ZAKAT',
    'id_metode_pembayaran' => '',
    'via_pembayaran' => 'TUNAI',
    'nama_penyetor' => '',
    'nama_petugas' => current_user()['full_name'] ?? 'Petugas Amil',
    'keterangan' => ''
];

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM surat_ketetapan_zakat WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        redirect_with_flash('pages/skz/index.php', 'Data SKZ tidak ditemukan.', 'danger');
    }
    $data = array_merge($data, $existing);
} else {
    // Generate nomor surat otomatis untuk form baru
    $autoNomor = get_next_skz_nomor($conn, $data['tanggal_surat']);
    $data['nomor_urut'] = $autoNomor['urut'];
    $data['nomor_surat'] = $autoNomor['nomor_surat'];
}

// Ambil data referensi
$upzList = $conn->query("SELECT id, nama_upz, alamat, kontak, id_kategori_upz FROM upz ORDER BY nama_upz ASC")->fetch_all(MYSQLI_ASSOC);
$kategoriList = get_kategori_upz_list();
$jenisList = get_jenis_pembayaran_list();
$metodeList = get_metode_pembayaran_list();

// Handle AJAX Quick Create UPZ langsung dari form SKZ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_create_upz') {
    header('Content-Type: application/json');
    $nama = trim($_POST['nama_upz'] ?? '');
    $id_kategori = (int)($_POST['id_kategori_upz'] ?? 0) ?: null;
    $alamat = trim($_POST['alamat'] ?? '');
    $kontak = trim($_POST['kontak'] ?? '');

    if ($nama === '') {
        echo json_encode(['success' => false, 'message' => 'Nama UPZ wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO upz (nama_upz, id_kategori_upz, alamat, kontak) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('siss', $nama, $id_kategori, $alamat, $kontak);
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        log_audit('CREATE_UPZ', 'upz', $newId, "Tambah UPZ dari Form SKZ: $nama");
        echo json_encode([
            'success' => true,
            'message' => 'UPZ baru berhasil ditambahkan ke database.',
            'upz' => [
                'id' => $newId,
                'nama_upz' => $nama,
                'id_kategori_upz' => $id_kategori,
                'alamat' => $alamat,
                'kontak' => $kontak
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan UPZ: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// Handle Simpan POST SKZ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_urut = (int)($_POST['nomor_urut'] ?? 0);
    $nomor_surat = trim($_POST['nomor_surat'] ?? '');
    $tanggal_surat = trim($_POST['tanggal_surat'] ?? date('Y-m-d'));
    $nama_pimpinan = trim($_POST['nama_pimpinan'] ?? '');
    $jabatan_pimpinan = trim($_POST['jabatan_pimpinan'] ?? '');
    $kota_surat = trim($_POST['kota_surat'] ?? 'Sumbawa Besar');
    
    $id_upz_badan_usaha = (int)($_POST['id_upz_badan_usaha'] ?? 0) ?: null;
    $nama_badan_usaha = trim($_POST['nama_badan_usaha'] ?? '');
    $alamat_badan_usaha = trim($_POST['alamat_badan_usaha'] ?? '');
    
    // Jika nama badan usaha belum terisi manual, ambil otomatis dari tabel UPZ
    if ($id_upz_badan_usaha && empty($nama_badan_usaha)) {
        $qBu = $conn->prepare("SELECT nama_upz, alamat FROM upz WHERE id = ?");
        $qBu->bind_param('i', $id_upz_badan_usaha);
        $qBu->execute();
        $rBu = $qBu->get_result()->fetch_assoc();
        $qBu->close();
        if ($rBu) {
            $nama_badan_usaha = $rBu['nama_upz'];
            if (empty($alamat_badan_usaha)) {
                $alamat_badan_usaha = $rBu['alamat'];
            }
        }
    }

    $id_upz_dinas = (int)($_POST['id_upz_dinas'] ?? 0) ?: null;
    $dinas_instansi = trim($_POST['dinas_instansi'] ?? '');

    // Jika dinas belum terisi manual, ambil otomatis dari tabel UPZ
    if ($id_upz_dinas && empty($dinas_instansi)) {
        $qDinas = $conn->prepare("SELECT nama_upz FROM upz WHERE id = ?");
        $qDinas->bind_param('i', $id_upz_dinas);
        $qDinas->execute();
        $rDinas = $qDinas->get_result()->fetch_assoc();
        $qDinas->close();
        if ($rDinas) {
            $dinas_instansi = $rDinas['nama_upz'];
        }
    }
    
    $paket_pekerjaan = trim($_POST['paket_pekerjaan'] ?? '');
    
    // Normalisasi input nominal
    $nilai_kontrak = (float)str_replace(['.', ','], ['', '.'], $_POST['nilai_kontrak'] ?? '0');
    $persentase_keuntungan = (float)str_replace(',', '.', $_POST['persentase_keuntungan'] ?? '6.00');
    $estimasi_keuntungan = (float)str_replace(['.', ','], ['', '.'], $_POST['estimasi_keuntungan'] ?? '0');
    $persentase_zis = (float)str_replace(',', '.', $_POST['persentase_zis'] ?? '2.50');
    $nilai_zis = (float)str_replace(['.', ','], ['', '.'], $_POST['nilai_zis'] ?? '0');
    
    $id_objek_zis = (int)($_POST['id_objek_zis'] ?? 0) ?: null;
    $objek_zis = trim($_POST['objek_zis'] ?? 'ZAKAT');
    
    $id_metode_pembayaran = (int)($_POST['id_metode_pembayaran'] ?? 0) ?: null;
    $via_pembayaran = trim($_POST['via_pembayaran'] ?? 'TUNAI');
    
    $nama_penyetor = trim($_POST['nama_penyetor'] ?? '');
    $nama_petugas = trim($_POST['nama_petugas'] ?? (current_user()['full_name'] ?? 'Petugas Amil'));
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user_id = current_user()['id'] ?? null;

    // Jika nomor urut belum ada, ambil dari generator
    if ($nomor_urut <= 0) {
        $autoNomor = get_next_skz_nomor($conn, $tanggal_surat);
        $nomor_urut = $autoNomor['urut'];
    }

    if (empty($nomor_surat) || empty($nama_badan_usaha) || empty($paket_pekerjaan)) {
        set_flash('skz_form', 'Nomor surat, Nama Badan Usaha, dan Paket Pekerjaan wajib diisi.', 'danger');
    } else {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE surat_ketetapan_zakat SET 
                nomor_urut = ?, nomor_surat = ?, tanggal_surat = ?, nama_pimpinan = ?, jabatan_pimpinan = ?, kota_surat = ?,
                id_upz_badan_usaha = ?, nama_badan_usaha = ?, alamat_badan_usaha = ?, id_upz_dinas = ?, dinas_instansi = ?,
                paket_pekerjaan = ?, nilai_kontrak = ?, persentase_keuntungan = ?, estimasi_keuntungan = ?, persentase_zis = ?,
                nilai_zis = ?, id_objek_zis = ?, objek_zis = ?, id_metode_pembayaran = ?, via_pembayaran = ?,
                nama_penyetor = ?, nama_petugas = ?, keterangan = ?
                WHERE id = ?");
            $stmt->bind_param('isssssississdddddissssssi',
                $nomor_urut, $nomor_surat, $tanggal_surat, $nama_pimpinan, $jabatan_pimpinan, $kota_surat,
                $id_upz_badan_usaha, $nama_badan_usaha, $alamat_badan_usaha, $id_upz_dinas, $dinas_instansi,
                $paket_pekerjaan, $nilai_kontrak, $persentase_keuntungan, $estimasi_keuntungan, $persentase_zis,
                $nilai_zis, $id_objek_zis, $objek_zis, $id_metode_pembayaran, $via_pembayaran,
                $nama_penyetor, $nama_petugas, $keterangan, $id
            );
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                log_audit('UPDATE_SKZ', 'surat_ketetapan_zakat', $id, "Update SKZ {$nomor_surat} - {$nama_badan_usaha}");
                redirect_with_flash('pages/skz/index.php', "SKZ {$nomor_surat} berhasil diperbarui.", 'success');
            } else {
                set_flash('skz_form', 'Gagal memperbarui SKZ: ' . $conn->error, 'danger');
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO surat_ketetapan_zakat (
                nomor_urut, nomor_surat, tanggal_surat, nama_pimpinan, jabatan_pimpinan, kota_surat,
                id_upz_badan_usaha, nama_badan_usaha, alamat_badan_usaha, id_upz_dinas, dinas_instansi,
                paket_pekerjaan, nilai_kontrak, persentase_keuntungan, estimasi_keuntungan, persentase_zis,
                nilai_zis, id_objek_zis, objek_zis, id_metode_pembayaran, via_pembayaran,
                nama_penyetor, nama_petugas, user_id, keterangan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('isssssississdddddisssssis',
                $nomor_urut, $nomor_surat, $tanggal_surat, $nama_pimpinan, $jabatan_pimpinan, $kota_surat,
                $id_upz_badan_usaha, $nama_badan_usaha, $alamat_badan_usaha, $id_upz_dinas, $dinas_instansi,
                $paket_pekerjaan, $nilai_kontrak, $persentase_keuntungan, $estimasi_keuntungan, $persentase_zis,
                $nilai_zis, $id_objek_zis, $objek_zis, $id_metode_pembayaran, $via_pembayaran,
                $nama_penyetor, $nama_petugas, $user_id, $keterangan
            );
            $success = $stmt->execute();
            $newId = $conn->insert_id;
            $stmt->close();

            if ($success) {
                log_audit('CREATE_SKZ', 'surat_ketetapan_zakat', $newId, "Penerbitan SKZ {$nomor_surat} - {$nama_badan_usaha}");
                redirect_with_flash('pages/skz/index.php', "SKZ {$nomor_surat} berhasil diterbitkan.", 'success');
            } else {
                set_flash('skz_form', 'Gagal menyimpan SKZ: ' . $conn->error, 'danger');
            }
        }
    }
}

include __DIR__ . '/../../inc/header.php';
$flash = get_flash('skz_form');
?>

<div class="row justify-content-center">
    <div class="col-lg-11">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= clean($flash['message']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="formSkz">
            <input type="hidden" name="nomor_urut" id="nomor_urut" value="<?= clean($data['nomor_urut']) ?>">

            <!-- KARTU 1: DATA ADMINISTRASI & PENOMORAN -->
            <div class="card shadow-none mb-4" style="border: 1px solid #b8b8b8;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #dee2e6;">
                    <h5 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-file-signature text-success mr-2"></i>Informasi Surat & Pimpinan
                    </h5>
                    <a href="<?= url('pages/skz/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Daftar
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Nomor Surat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-weight-bold" name="nomor_surat" id="nomor_surat" value="<?= clean($data['nomor_surat']) ?>" required placeholder="01/PENGUMPULAN/BAZNAS/IX/2026">
                                <small class="text-muted">Format otomatis berurutan (mulai 01), dapat disesuaikan manual bila perlu.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Tanggal Surat <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_surat" id="tanggal_surat" value="<?= clean($data['tanggal_surat']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Kota Penetapan Surat</label>
                                <input type="text" class="form-control" name="kota_surat" value="<?= clean($data['kota_surat']) ?>" placeholder="Sumbawa Besar">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Nama Pimpinan Penandatangan</label>
                                <input type="text" class="form-control" name="nama_pimpinan" value="<?= clean($data['nama_pimpinan']) ?>" placeholder="Nama Ketua/Pimpinan">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Jabatan Pimpinan</label>
                                <input type="text" class="form-control" name="jabatan_pimpinan" value="<?= clean($data['jabatan_pimpinan']) ?>" placeholder="Ketua BAZNAS Kabupaten Sumbawa">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARTU 2: DATA BADAN USAHA & PROYEK / PAKET PEKERJAAN -->
            <div class="card shadow-none mb-4" style="border: 1px solid #b8b8b8;">
                <div class="card-header bg-white" style="border-bottom: 1px solid #dee2e6;">
                    <h5 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-building text-primary mr-2"></i>Identitas Badan Usaha & Paket Pekerjaan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-weight-bold mb-0">Nama Badan Usaha / Rekanan <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-outline-success btn-xs" onclick="openModalUpz('bu')">
                                        <i class="fas fa-plus-circle mr-1"></i>Tambah UPZ Baru
                                    </button>
                                </div>
                                <select class="form-control select2" name="id_upz_badan_usaha" id="select_upz_bu" style="width: 100%;" required>
                                    <option value="">-- Pilih dari Data UPZ / Rekanan --</option>
                                    <?php if (!empty($data['nama_badan_usaha']) && empty($data['id_upz_badan_usaha'])): ?>
                                        <option value="" data-nama="<?= clean($data['nama_badan_usaha']) ?>" data-alamat="<?= clean($data['alamat_badan_usaha']) ?>" selected><?= clean($data['nama_badan_usaha']) ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($upzList as $u): ?>
                                        <option value="<?= $u['id'] ?>" data-alamat="<?= clean($u['alamat']) ?>" data-nama="<?= clean($u['nama_upz']) ?>" <?= ($data['id_upz_badan_usaha'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= clean($u['nama_upz']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="nama_badan_usaha" id="nama_badan_usaha" value="<?= clean($data['nama_badan_usaha']) ?>">
                                <small class="text-muted">Pilih badan usaha/rekanan dari referensi UPZ. Klik "Tambah UPZ Baru" jika belum terdaftar.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Alamat Badan Usaha <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="alamat_badan_usaha" id="alamat_badan_usaha" rows="3" required placeholder="Alamat lengkap rekanan / badan usaha"><?= clean($data['alamat_badan_usaha']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Paket Pekerjaan <span class="text-danger">*</span></label>
                                <textarea class="form-control font-weight-bold" name="paket_pekerjaan" id="paket_pekerjaan" rows="2" required placeholder="Contoh: PEMBANGUNAN GUDANG TEMBAKAU"><?= clean($data['paket_pekerjaan']) ?></textarea>
                                <small class="text-muted">Uraian pada lembar bukti setoran BSZIS akan otomatis menggunakan teks ini.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="font-weight-bold mb-0">Dinas / Instansi Terkait <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-outline-success btn-xs" onclick="openModalUpz('dinas')">
                                        <i class="fas fa-plus-circle mr-1"></i>Tambah UPZ Baru
                                    </button>
                                </div>
                                <select class="form-control select2" name="id_upz_dinas" id="select_upz_dinas" style="width: 100%;" required>
                                    <option value="">-- Pilih Dinas / Instansi dari Data UPZ --</option>
                                    <?php if (!empty($data['dinas_instansi']) && empty($data['id_upz_dinas'])): ?>
                                        <option value="" data-nama="<?= clean($data['dinas_instansi']) ?>" selected><?= clean($data['dinas_instansi']) ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($upzList as $u): ?>
                                        <option value="<?= $u['id'] ?>" data-nama="<?= clean($u['nama_upz']) ?>" <?= ($data['id_upz_dinas'] == $u['id']) ? 'selected' : '' ?>>
                                            <?= clean($u['nama_upz']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="dinas_instansi" id="dinas_instansi" value="<?= clean($data['dinas_instansi']) ?>">
                                <small class="text-muted">Pilih dinas/instansi dari referensi UPZ. Klik "Tambah UPZ Baru" jika belum terdaftar.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARTU 3: KALKULASI NILAI KONTRAK, KEUNTUNGAN & NILAI ZIS (REALTIME) -->
            <div class="card shadow-none mb-4" style="border: 1px solid #b8b8b8;">
                <div class="card-header bg-white" style="border-bottom: 1px solid #dee2e6;">
                    <h5 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-calculator text-warning mr-2"></i>Perhitungan Nilai Kontrak & Ketetapan ZIS
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Nilai Kontrak (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold">Rp</span>
                                    </div>
                                    <input type="text" class="form-control font-weight-bold text-right" name="nilai_kontrak" id="nilai_kontrak" value="<?= number_format((float)$data['nilai_kontrak'], 0, ',', '.') ?>" required style="font-size: 1.15rem;">
                                </div>
                                <small class="text-muted">Ketik angka nilai kontrak (misal: 90.300.000).</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Estimasi Keuntungan (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100" class="form-control font-weight-bold text-center" name="persentase_keuntungan" id="persentase_keuntungan" value="<?= clean($data['persentase_keuntungan']) ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="text-muted">Standar awal 6% (dapat disesuaikan dinamis).</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Hasil Estimasi Keuntungan (Rp)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold">Rp</span>
                                    </div>
                                    <input type="text" class="form-control font-weight-bold text-right bg-light" name="estimasi_keuntungan" id="estimasi_keuntungan" value="<?= number_format((float)$data['estimasi_keuntungan'], 0, ',', '.') ?>" readonly style="font-size: 1.15rem;">
                                </div>
                                <small class="text-muted">Otomatis dihitung: Kontrak &times; % Keuntungan.</small>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3" style="border-top: 1px dashed #ced4da;">

                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Tarif ZIS (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.1" min="0" max="100" class="form-control font-weight-bold text-center" name="persentase_zis" id="persentase_zis" value="<?= clean($data['persentase_zis']) ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="text-muted">Standar Zakat Maal: 2,5%.</small>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="font-weight-bold text-success" style="color: #0d9f4f !important;">
                                    Nilai Ketetapan ZIS (Rp) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold bg-success text-white" style="background-color: #0d9f4f !important;">Rp</span>
                                    </div>
                                    <input type="text" class="form-control font-weight-bold text-right text-success" name="nilai_zis" id="nilai_zis" value="<?= number_format((float)$data['nilai_zis'], 0, ',', '.') ?>" required style="font-size: 1.3rem; border: 2px solid #0d9f4f;">
                                </div>
                                <small class="text-muted">Dihitung otomatis (Keuntungan &times; 2,5%), dapat dibulatkan sesuai bukti setor.</small>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group pt-md-3">
                                <button type="button" class="btn btn-outline-info btn-block btn-sm" id="btnBulatkanRibuan" title="Bulatkan ke ribuan terdekat">
                                    <i class="fas fa-magic mr-1"></i>Bulatkan Ribuan
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-block btn-sm mt-1" id="btnHitungUlang" title="Hitung ulang otomatis">
                                    <i class="fas fa-sync-alt mr-1"></i>Hitung Ulang Murni
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARTU 4: PENGATURAN BUKTI SETORAN (BSZIS) -->
            <div class="card shadow-none mb-4" style="border: 1px solid #b8b8b8;">
                <div class="card-header bg-white" style="border-bottom: 1px solid #dee2e6;">
                    <h5 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-receipt text-info mr-2"></i>Rincian Bukti Setoran (BSZIS)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Objek ZIS</label>
                                <select class="form-control font-weight-bold" name="objek_zis" id="objek_zis">
                                    <option value="ZAKAT" <?= ($data['objek_zis'] === 'ZAKAT') ? 'selected' : '' ?>>ZAKAT</option>
                                    <option value="INFAK" <?= ($data['objek_zis'] === 'INFAK') ? 'selected' : '' ?>>INFAK</option>
                                    <option value="SEDEKAH" <?= ($data['objek_zis'] === 'SEDEKAH') ? 'selected' : '' ?>>SEDEKAH</option>
                                    <?php foreach ($jenisList as $j): ?>
                                        <?php if (!in_array(strtoupper($j['nama_jenis']), ['ZAKAT', 'INFAK', 'SEDEKAH'])): ?>
                                            <option value="<?= clean(strtoupper($j['nama_jenis'])) ?>" <?= ($data['objek_zis'] === strtoupper($j['nama_jenis'])) ? 'selected' : '' ?>>
                                                <?= clean(strtoupper($j['nama_jenis'])) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Metode Penyetoran (Via) <span class="text-danger">*</span></label>
                                <select class="form-control font-weight-bold" name="via_pembayaran" id="via_pembayaran">
                                    <option value="TUNAI" <?= ($data['via_pembayaran'] === 'TUNAI') ? 'selected' : '' ?>>TUNAI</option>
                                    <?php foreach ($metodeList as $m): ?>
                                        <?php if (strtoupper($m['nama_metode']) !== 'TUNAI'): ?>
                                            <option value="<?= clean(strtoupper($m['nama_metode'])) ?>" <?= ($data['via_pembayaran'] === strtoupper($m['nama_metode'])) ? 'selected' : '' ?>>
                                                <?= clean(strtoupper($m['nama_metode'])) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Nama Penyetor / Wajib ZIS <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-weight-bold" name="nama_penyetor" id="nama_penyetor" value="<?= clean($data['nama_penyetor']) ?>" required placeholder="Ketik nama penyetor">
                                <small class="text-muted">Nama yang tertera pada kolom tanda tangan Penyetor.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Pengesahan Petugas Amil</label>
                                <input type="text" class="form-control bg-light" name="nama_petugas" id="nama_petugas" value="<?= clean($data['nama_petugas']) ?>" readonly>
                                <small class="text-muted">Otomatis dari nama lengkap akun petugas amil yang sedang masuk.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Catatan Tambahan / Keterangan (Opsional)</label>
                                <input type="text" class="form-control" name="keterangan" value="<?= clean($data['keterangan']) ?>" placeholder="Catatan internal jika ada">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="<?= url('pages/skz/index.php') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times mr-1"></i>Batal
                    </a>
                    <div>
                        <button type="submit" class="btn btn-success px-4 font-weight-bold">
                            <i class="fas fa-save mr-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Terbitkan SKZ & BSZIS' ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah UPZ Baru Langsung -->
<div class="modal fade" id="modalQuickUpz" tabindex="-1" role="dialog" aria-labelledby="modalQuickUpzLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="modalQuickUpzLabel">
                    <i class="fas fa-building mr-2 text-success"></i>Tambah UPZ Baru
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formQuickUpz" class="no-preloader">
                <input type="hidden" name="action" value="quick_create_upz">
                <input type="hidden" id="target_upz_type" value="bu">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nama UPZ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama_upz" id="quick_nama_upz" required placeholder="Contoh: CV. DINDA LESTARI atau DINAS PERTANIAN" style="border: 1px solid #b8b8b8;">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Kategori UPZ</label>
                        <select class="form-control" name="id_kategori_upz" id="quick_id_kategori_upz" style="border: 1px solid #b8b8b8;">
                            <option value="">-- Pilih Kategori UPZ --</option>
                            <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>" data-name="<?= strtolower(clean($k['nama_kategori'])) ?>"><?= clean($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Alamat UPZ / Rekanan</label>
                        <textarea class="form-control" name="alamat" id="quick_alamat" rows="2" placeholder="Alamat lengkap UPZ / rekanan" style="border: 1px solid #b8b8b8;"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Kontak (No. HP / Telepon)</label>
                        <input type="text" class="form-control" name="kontak" id="quick_kontak" placeholder="Contoh: 08123456789" style="border: 1px solid #b8b8b8;">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnSaveQuickUpz">
                        <i class="fas fa-save mr-1"></i>Simpan UPZ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        placeholder: '-- Pilih --',
        allowClear: true
    });

    // Helper format rupiah input
    function formatRibuan(angka) {
        var number_string = angka.toString().replace(/[^,\d]/g, ''),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            var separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        return split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
    }

    function parseNominal(str) {
        if (!str) return 0;
        var cleanStr = str.toString().replace(/\./g, '').replace(',', '.');
        return parseFloat(cleanStr) || 0;
    }

    // Kalkulasi Realtime Nilai Kontrak -> Estimasi Keuntungan -> Nilai ZIS
    function hitungZis(bulatkan = false) {
        var kontrak = parseNominal($('#nilai_kontrak').val());
        var profitPercent = parseFloat($('#persentase_keuntungan').val()) || 0;
        var zisPercent = parseFloat($('#persentase_zis').val()) || 0;

        var estProfit = kontrak * (profitPercent / 100);
        var nilaiZis = estProfit * (zisPercent / 100);

        if (bulatkan) {
            // Pembulatan ke atas / ribuan terdekat
            nilaiZis = Math.round(nilaiZis / 1000) * 1000;
        }

        $('#estimasi_keuntungan').val(formatRibuan(Math.round(estProfit)));
        $('#nilai_zis').val(formatRibuan(Math.round(nilaiZis)));
    }

    $('#nilai_kontrak').on('input', function() {
        var val = $(this).val();
        $(this).val(formatRibuan(val));
        hitungZis(false);
    });

    $('#persentase_keuntungan, #persentase_zis').on('input change', function() {
        hitungZis(false);
    });

    $('#btnBulatkanRibuan').on('click', function() {
        hitungZis(true);
    });

    $('#btnHitungUlang').on('click', function() {
        hitungZis(false);
    });

    $('#nilai_zis').on('input', function() {
        $(this).val(formatRibuan($(this).val()));
    });

    // Pilihan UPZ Badan Usaha
    $('#select_upz_bu').on('change', function() {
        var $opt = $(this).find(':selected');
        if ($opt.val()) {
            var nama = $opt.data('nama') || $opt.text().trim();
            var alamat = $opt.data('alamat');
            $('#nama_badan_usaha').val(nama);
            if (alamat) {
                $('#alamat_badan_usaha').val(alamat);
            }
        } else {
            $('#nama_badan_usaha').val('');
        }
    });

    // Pilihan UPZ Dinas
    $('#select_upz_dinas').on('change', function() {
        var $opt = $(this).find(':selected');
        if ($opt.val()) {
            var nama = $opt.data('nama') || $opt.text().trim();
            $('#dinas_instansi').val(nama);
        } else {
            $('#dinas_instansi').val('');
        }
    });

    // Inisialisasi awal nilai hidden jika sudah ada pilihan
    if ($('#select_upz_bu').val()) {
        $('#nama_badan_usaha').val($('#select_upz_bu').find(':selected').data('nama') || $('#select_upz_bu').find(':selected').text().trim());
    }
    if ($('#select_upz_dinas').val()) {
        $('#dinas_instansi').val($('#select_upz_dinas').find(':selected').data('nama') || $('#select_upz_dinas').find(':selected').text().trim());
    }

    // Fungsi membuka modal Tambah UPZ
    window.openModalUpz = function(targetType) {
        $('#target_upz_type').val(targetType);
        $('#formQuickUpz')[0].reset();
        
        if (targetType === 'bu') {
            $('#modalQuickUpzLabel').html('<i class="fas fa-building mr-2 text-success"></i>Tambah UPZ Badan Usaha / Rekanan');
            $('#quick_nama_upz').attr('placeholder', 'Contoh: CV. DINDA LESTARI / PT. MAJU JAYA');
            // Coba pilih kategori badan usaha / swasta otomatis jika ada
            $('#quick_id_kategori_upz option').each(function() {
                var name = $(this).data('name') || '';
                if (name.indexOf('badan usaha') !== -1 || name.indexOf('swasta') !== -1 || name.indexOf('perusahaan') !== -1) {
                    $('#quick_id_kategori_upz').val($(this).val());
                    return false;
                }
            });
        } else {
            $('#modalQuickUpzLabel').html('<i class="fas fa-landmark mr-2 text-success"></i>Tambah UPZ Dinas / Instansi');
            $('#quick_nama_upz').attr('placeholder', 'Contoh: DINAS PERTANIAN / DINAS KESEHATAN');
            // Coba pilih kategori dinas / pemerintah otomatis jika ada
            $('#quick_id_kategori_upz option').each(function() {
                var name = $(this).data('name') || '';
                if (name.indexOf('dinas') !== -1 || name.indexOf('pemerintah') !== -1 || name.indexOf('instansi') !== -1) {
                    $('#quick_id_kategori_upz').val($(this).val());
                    return false;
                }
            });
        }

        $('#modalQuickUpz').modal('show');
        setTimeout(function() {
            $('#quick_nama_upz').focus();
        }, 400);
    };

    // Submit form modal quick UPZ
    $('#formQuickUpz').on('submit', function(e) {
        e.preventDefault();
        if (window.hidePreloader) window.hidePreloader();
        var $btn = $('#btnSaveQuickUpz');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Menyimpan...');

        var targetType = $('#target_upz_type').val();
        var formData = $(this).serialize();

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp) {
                if (window.hidePreloader) window.hidePreloader();
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan UPZ');
                if (resp.success && resp.upz) {
                    var u = resp.upz;
                    // Buat option baru
                    var newOptionBu = new Option(u.nama_upz, u.id, false, (targetType === 'bu'));
                    $(newOptionBu).attr('data-nama', u.nama_upz).attr('data-alamat', u.alamat || '');
                    $('#select_upz_bu').append(newOptionBu);

                    var newOptionDinas = new Option(u.nama_upz, u.id, false, (targetType === 'dinas'));
                    $(newOptionDinas).attr('data-nama', u.nama_upz).attr('data-alamat', u.alamat || '');
                    $('#select_upz_dinas').append(newOptionDinas);

                    // Pilih otomatis di select terkait
                    if (targetType === 'bu') {
                        $('#select_upz_bu').val(u.id).trigger('change');
                        if (u.alamat) {
                            $('#alamat_badan_usaha').val(u.alamat);
                        }
                    } else {
                        $('#select_upz_dinas').val(u.id).trigger('change');
                    }

                    $('#modalQuickUpz').modal('hide');

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'UPZ Berhasil Ditambahkan!',
                            text: u.nama_upz + ' telah masuk ke database dan terpilih otomatis.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert(resp.message);
                    }
                } else {
                    alert(resp.message || 'Gagal menambahkan UPZ.');
                }
            },
            error: function() {
                if (window.hidePreloader) window.hidePreloader();
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan UPZ');
                alert('Terjadi kesalahan koneksi.');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
