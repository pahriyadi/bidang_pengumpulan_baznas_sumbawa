<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/dokumen_db.php';
require_auth();

$activeMenu = 'dokumen_kategori';
$pageTitle = 'Kategori Dokumen Program';
$pageDescription = 'Kelola referensi kategori dokumen program bantuan & pengaturan header kolom dinamis';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Kategori Dokumen</li>';

$conn = db();
ensure_dokumen_tables($conn);

// AJAX Handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $kode = trim($_POST['kode_kategori'] ?? '');
        $nama = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tahun = !empty($_POST['tahun_anggaran']) ? (int)$_POST['tahun_anggaran'] : (int)date('Y');
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif'], true) ? $_POST['status'] : 'aktif';

        if ($nama === '') {
            echo json_encode(['success' => false, 'message' => 'Nama kategori dokumen wajib diisi']);
            exit;
        }

        if ($kode === '') {
            $kode = strtoupper('DOC-' . substr(md5(uniqid()), 0, 6));
        }

        // Cek duplikasi kode
        $stmt = $conn->prepare("SELECT id FROM dokumen_kategori WHERE kode_kategori = ?");
        $stmt->bind_param('s', $kode);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Kode kategori sudah digunakan, silakan gunakan kode lain']);
            exit;
        }
        $stmt->close();

        $userId = current_user()['id'] ?? null;
        $stmt = $conn->prepare("INSERT INTO dokumen_kategori (kode_kategori, nama_kategori, deskripsi, tahun_anggaran, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssisi', $kode, $nama, $deskripsi, $tahun, $status, $userId);

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            log_audit('CREATE_DOKUMEN_KATEGORI', 'dokumen_kategori', $newId, "Nama: $nama, Kode: $kode");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Kategori dokumen berhasil ditambahkan', 'id' => $newId]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $kode = trim($_POST['kode_kategori'] ?? '');
        $nama = trim($_POST['nama_kategori'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tahun = !empty($_POST['tahun_anggaran']) ? (int)$_POST['tahun_anggaran'] : (int)date('Y');
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif'], true) ? $_POST['status'] : 'aktif';

        if ($id <= 0 || $nama === '') {
            echo json_encode(['success' => false, 'message' => 'Data input tidak valid']);
            exit;
        }

        // Cek duplikasi kode selain id ini
        $stmt = $conn->prepare("SELECT id FROM dokumen_kategori WHERE kode_kategori = ? AND id != ?");
        $stmt->bind_param('si', $kode, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Kode kategori sudah digunakan oleh kategori lain']);
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("UPDATE dokumen_kategori SET kode_kategori = ?, nama_kategori = ?, deskripsi = ?, tahun_anggaran = ?, status = ? WHERE id = ?");
        $stmt->bind_param('sssisi', $kode, $nama, $deskripsi, $tahun, $status, $id);

        if ($stmt->execute()) {
            log_audit('UPDATE_DOKUMEN_KATEGORI', 'dokumen_kategori', $id, "Nama: $nama, Kode: $kode");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Kategori dokumen berhasil diperbarui']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM dokumen_kategori WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            log_audit('DELETE_DOKUMEN_KATEGORI', 'dokumen_kategori', $id, "Hapus kategori id: $id");
            clear_page_cache();
            echo json_encode(['success' => true, 'message' => 'Kategori dokumen beserta kolom & datanya berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus kategori: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $data = get_dokumen_kategori_by_id($conn, $id);
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
        }
        exit;
    }
}

$kategoriList = get_dokumen_kategori_list($conn);

include __DIR__ . '/../../inc/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success shadow-none border" style="border: 1px solid #b8b8b8 !important;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-folder-open text-success mr-2"></i>Daftar Kategori Dokumen Program
                </h3>
                <div class="card-tools ml-auto">
                    <button type="button" class="btn btn-outline-success btn-sm font-weight-bold" onclick="openModalTambah()">
                        <i class="fas fa-plus mr-1"></i> Tambah Kategori Baru
                    </button>
                </div>
            </div>

            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="tableKategori" class="table table-bordered table-hover mb-0" style="width: 100%;">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th width="5%" class="text-center align-middle">No</th>
                                <th width="15%" class="align-middle">Kode Kategori</th>
                                <th class="align-middle">Nama Kategori & Deskripsi</th>
                                <th width="10%" class="text-center align-middle">Tahun</th>
                                <th width="12%" class="text-center align-middle">Kolom Header</th>
                                <th width="12%" class="text-center align-middle">Penerima</th>
                                <th width="10%" class="text-center align-middle">Status</th>
                                <th width="16%" class="text-center align-middle">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kategoriList)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block text-gray"></i>
                                        Belum ada kategori dokumen program. Klik tombol <strong>Tambah Kategori Baru</strong> di atas untuk membuat yang pertama.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($kategoriList as $item): ?>
                                    <tr>
                                        <td class="text-center align-middle font-weight-bold"><?= $no++ ?></td>
                                        <td class="align-middle font-weight-bold text-monospace">
                                            <span class="badge badge-light border px-2 py-1"><?= htmlspecialchars($item['kode_kategori']) ?></span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="font-weight-bold text-dark text-md"><?= htmlspecialchars($item['nama_kategori']) ?></div>
                                            <?php if (!empty($item['deskripsi'])): ?>
                                                <small class="text-muted d-block mt-1"><i class="fas fa-info-circle mr-1"></i><?= htmlspecialchars($item['deskripsi']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-secondary" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600;">
                                                <?= htmlspecialchars($item['tahun_anggaran'] ?: '-') ?>
                                            </span>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="<?= url('pages/dokumen_kategori/kolom.php?id=' . $item['id']) ?>" class="btn btn-outline-info btn-xs px-2 py-1" title="Atur Header Kolom">
                                                <i class="fas fa-columns mr-1"></i> <strong><?= (int)$item['total_kolom'] ?></strong> Kolom
                                            </a>
                                        </td>
                                        <td class="text-center align-middle">
                                            <a href="<?= url('pages/dokumen_program/index.php?id_kategori=' . $item['id']) ?>" class="btn btn-outline-primary btn-xs px-2 py-1" title="Buka Lembar Dokumen">
                                                <i class="fas fa-users mr-1"></i> <strong><?= (int)$item['total_penerima'] ?></strong> Data
                                            </a>
                                            <?php if ((float)$item['total_nominal'] > 0): ?>
                                                <small class="d-block text-success font-weight-bold mt-1">
                                                    Rp <?= number_format($item['total_nominal'], 0, ',', '.') ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($item['status'] === 'aktif'): ?>
                                                <span class="badge" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac;">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url('pages/dokumen_program/index.php?id_kategori=' . $item['id']) ?>" class="btn btn-outline-success" title="Buka Lembar Dokumen">
                                                    <i class="fas fa-table"></i>
                                                </a>
                                                <a href="<?= url('pages/dokumen_kategori/kolom.php?id=' . $item['id']) ?>" class="btn btn-outline-info" title="Atur Kolom Header">
                                                    <i class="fas fa-sliders-h"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-primary" onclick="editKategori(<?= $item['id'] ?>)" title="Ubah Info Kategori">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="hapusKategori(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_kategori'])) ?>')" title="Hapus Kategori">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Kategori -->
<div class="modal fade" id="modalKategori" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border shadow-none" style="border: 1px solid #b8b8b8 !important;">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="modalKategoriTitle">
                    <i class="fas fa-folder-plus text-success mr-2"></i>Tambah Kategori Dokumen
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formKategori">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="kategoriId" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_kategori" class="font-weight-bold">Nama Kategori Dokumen / Program <span class="text-danger">*</span></label>
                        <input type="text" class="form-control border" style="border-color: #b8b8b8;" id="nama_kategori" name="nama_kategori" required placeholder="Contoh: Penerima Manfaat Gerobak Usaha">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="kode_kategori" class="font-weight-bold">Kode Unik Kategori</label>
                                <input type="text" class="form-control border" style="border-color: #b8b8b8;" id="kode_kategori" name="kode_kategori" placeholder="Contoh: PM-GBK-2026">
                                <small class="text-muted">Bisa dikosongkan untuk kode otomatis.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tahun_anggaran" class="font-weight-bold">Tahun Anggaran</label>
                                <input type="number" class="form-control border" style="border-color: #b8b8b8;" id="tahun_anggaran" name="tahun_anggaran" value="<?= date('Y') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi" class="font-weight-bold">Deskripsi / Catatan Program</label>
                        <textarea class="form-control border" style="border-color: #b8b8b8;" id="deskripsi" name="deskripsi" rows="3" placeholder="Contoh: Penyaluran bantuan sarana usaha gerobak bagi mustahik pelaku usaha mikro di Kabupaten Sumbawa."></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label for="status" class="font-weight-bold">Status</label>
                        <select class="form-control border" style="border-color: #b8b8b8;" id="status" name="status">
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-outline-success font-weight-bold" id="btnSubmitKategori">
                        <i class="fas fa-save mr-1"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Kepatuhan Sistem Desain Putih Kertas */
.table tr:hover {
    background-color: #f0f7f3 !important;
    transition: background-color 0.15s ease-in-out;
}
.card, .modal-content {
    box-shadow: none !important;
}
</style>

<script>
$(document).ready(function() {
    <?php if (!empty($kategoriList)): ?>
    $('#tableKategori').DataTable({
        "language": {
            "search": "Cari Kategori:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ data",
            "paginate": { "first": "«", "last": "»", "next": "›", "previous": "‹" }
        },
        "order": [[3, "desc"], [0, "asc"]],
        "columnDefs": [
            { "orderable": false, "targets": [4, 5, 7] }
        ]
    });
    <?php endif; ?>
});

function openModalTambah() {
    $('#formKategori')[0].reset();
    $('#formAction').val('create');
    $('#kategoriId').val('');
    $('#tahun_anggaran').val('<?= date('Y') ?>');
    $('#status').val('aktif');
    $('#modalKategoriTitle').html('<i class="fas fa-folder-plus text-success mr-2"></i>Tambah Kategori Dokumen');
    $('#modalKategori').modal('show');
}

const ajaxUrl = '<?= url("pages/dokumen_kategori/index.php") ?>';

function editKategori(id) {
    $.post(ajaxUrl, { action: 'get', id: id }, function(res) {
        if (res.success) {
            $('#formKategori')[0].reset();
            $('#formAction').val('update');
            $('#kategoriId').val(res.data.id);
            $('#kode_kategori').val(res.data.kode_kategori);
            $('#nama_kategori').val(res.data.nama_kategori);
            $('#deskripsi').val(res.data.deskripsi);
            $('#tahun_anggaran').val(res.data.tahun_anggaran);
            $('#status').val(res.data.status);
            $('#modalKategoriTitle').html('<i class="fas fa-edit text-primary mr-2"></i>Ubah Kategori Dokumen');
            $('#modalKategori').modal('show');
        } else {
            showError(res.message || 'Gagal memuat data kategori');
        }
    }, 'json');
}

function hapusKategori(id, nama) {
    confirmDelete(function() {
        $.post(ajaxUrl, { action: 'delete', id: id }, function(res) {
            if (res.success) {
                showSuccess(res.message);
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                showError(res.message);
            }
        }, 'json');
    }, 'Apakah Anda yakin ingin menghapus kategori "' + nama + '"? Seluruh pengaturan kolom header dan data penerima di dalamnya akan ikut terhapus!');
}

$('#formKategori').on('submit', function(e) {
    e.preventDefault();
    const btn = $('#btnSubmitKategori');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

    $.post(ajaxUrl, $(this).serialize(), function(res) {
        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Kategori');
        if (res.success) {
            $('#modalKategori').modal('hide');
            showSuccess(res.message);
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            showError(res.message);
        }
    }, 'json').fail(function() {
        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Kategori');
        showError('Terjadi kesalahan jaringan atau server');
    });
});
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
