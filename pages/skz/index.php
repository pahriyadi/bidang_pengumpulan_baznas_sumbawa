<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/skz_helper.php';
require_auth();

$activeMenu = 'skz';
$pageTitle = 'Surat Ketetapan Zakat (SKZ) & BSZIS';
$pageDescription = 'Kelola SKZ dan Bukti Setoran ZIS Rekanan Proyek';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">SKZ & BSZIS</li>';

$conn = db();
ensure_skz_table($conn);

// Handle AJAX DataTables Server-Side Processing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['draw'])) {
    datatable_handler('surat_ketetapan_zakat s', [
        0 => 's.id',
        1 => 's.nomor_surat',
        2 => 's.nama_badan_usaha',
        3 => 's.dinas_instansi',
        4 => 's.nilai_kontrak',
        5 => 's.nilai_zis',
        6 => 's.via_pembayaran',
        7 => 's.id'
    ], [
        'select' => 's.*',
        'search_columns' => ['s.nomor_surat', 's.nama_badan_usaha', 's.dinas_instansi', 's.paket_pekerjaan', 's.nama_penyetor'],
        'row_formatter' => function($row, $no) {
            $tglSurat = date('d/m/Y', strtotime($row['tanggal_surat']));
            $nomorCol = '<strong>' . clean($row['nomor_surat']) . '</strong><br><small class="text-muted"><i class="far fa-calendar-alt mr-1"></i>' . $tglSurat . '</small>';
            
            $badanUsahaCol = '<strong>' . clean($row['nama_badan_usaha']) . '</strong>';
            if (!empty($row['paket_pekerjaan'])) {
                $badanUsahaCol .= '<br><small class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="' . clean($row['paket_pekerjaan']) . '"><i class="fas fa-briefcase mr-1"></i>' . clean($row['paket_pekerjaan']) . '</small>';
            }

            $dinasCol = '<span class="badge badge-outline-secondary font-weight-normal" style="border:1px solid #6c757d; color:#6c757d; background-color:rgba(108,117,125,0.05);">' . clean($row['dinas_instansi']) . '</span>';
            
            $nilaiKontrak = 'Rp ' . number_format((float)$row['nilai_kontrak'], 0, ',', '.');
            $nilaiZis = '<strong class="text-success" style="color: #0d9f4f !important;">Rp ' . number_format((float)$row['nilai_zis'], 0, ',', '.') . '</strong>';
            if ((float)$row['persentase_keuntungan'] > 0) {
                $nilaiKontrak .= '<br><small class="text-muted">(Est. ' . rtrim(rtrim(number_format((float)$row['persentase_keuntungan'], 2, ',', '.'), '0'), ',') . '%)</small>';
            }

            $viaCol = '<span class="badge badge-outline-info" style="border:1px solid #17a2b8; color:#17a2b8; background-color:rgba(23,162,184,0.05);">' . clean($row['via_pembayaran']) . '</span><br><small class="text-muted">' . clean($row['objek_zis']) . '</small>';

            $actionBtns = '
                <div class="btn-group">
                    <a href="' . url('pages/skz/cetak_skz.php?id=' . $row['id']) . '" target="_blank" class="btn btn-outline-success btn-xs" title="Cetak SKZ (Lampiran 1)">
                        <i class="fas fa-file-invoice mr-1"></i>SKZ
                    </a>
                    <a href="' . url('pages/skz/cetak_bszis.php?id=' . $row['id']) . '" target="_blank" class="btn btn-outline-primary btn-xs" title="Cetak BSZIS (Lampiran 2)">
                        <i class="fas fa-receipt mr-1"></i>BSZIS
                    </a>
                    <a href="' . url('pages/skz/form.php?id=' . $row['id']) . '" class="btn btn-outline-warning btn-xs" title="Edit Data">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-xs" onclick="deleteSkz(' . $row['id'] . ')" title="Hapus Data">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            ';

            return [
                $no,
                $nomorCol,
                $badanUsahaCol,
                $dinasCol,
                $nilaiKontrak,
                $nilaiZis,
                $viaCol,
                $actionBtns
            ];
        }
    ]);
}

// Handle Delete Request via AJAX POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM surat_ketetapan_zakat WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                log_audit('DELETE_SKZ', 'surat_ketetapan_zakat', $id, "Menghapus SKZ ID: {$id}");
                echo json_encode(['success' => true, 'message' => 'Data SKZ & BSZIS berhasil dihapus.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus data: ' . $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'ID data tidak valid.']);
        }
        exit;
    }

    if ($action === 'save_settings') {
        $pimpinan = trim($_POST['skz_nama_pimpinan'] ?? '');
        $jabatan = trim($_POST['skz_jabatan_pimpinan'] ?? '');
        $kota = trim($_POST['skz_kota'] ?? 'Sumbawa Besar');
        $kantor = trim($_POST['skz_alamat_kantor'] ?? '');
        $telp = trim($_POST['skz_telepon_kantor'] ?? '');
        $resetLogo = (int)($_POST['reset_logo'] ?? 0);

        $updates = [
            'skz_nama_pimpinan' => $pimpinan,
            'skz_jabatan_pimpinan' => $jabatan,
            'skz_kota' => $kota,
            'skz_alamat_kantor' => $kantor,
            'skz_telepon_kantor' => $telp
        ];

        // Reset logo jika diminta
        if (!empty($_POST['reset_logo'])) {
            $oldPath = get_setting('skz_logo_path');
            if (!empty($oldPath)) {
                $fullOld = __DIR__ . '/../../' . ltrim($oldPath, '/\\');
                if (file_exists($fullOld)) {
                    @unlink($fullOld);
                }
            }
            $updates['skz_logo_path'] = '';
        }

        // Upload logo baru jika diunggah
        if (isset($_FILES['skz_logo']) && $_FILES['skz_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['skz_logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

            if (in_array($ext, $allowed, true) && $file['size'] <= 2 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../assets/uploads/skz_logo/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'logo_skz_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $oldPath = get_setting('skz_logo_path');
                    if (!empty($oldPath) && $oldPath !== 'assets/uploads/skz_logo/' . $newFileName) {
                        $fullOld = __DIR__ . '/../../' . ltrim($oldPath, '/\\');
                        if (file_exists($fullOld)) {
                            @unlink($fullOld);
                        }
                    }
                    $updates['skz_logo_path'] = 'assets/uploads/skz_logo/' . $newFileName;
                }
            }
        }

        // Reset TTD jika diminta
        if (!empty($_POST['reset_ttd'])) {
            $oldTtd = get_setting('skz_ttd_path');
            if (!empty($oldTtd)) {
                $fullOld = __DIR__ . '/../../' . ltrim($oldTtd, '/\\');
                if (file_exists($fullOld)) {
                    @unlink($fullOld);
                }
            }
            $updates['skz_ttd_path'] = '';
        }

        // Upload TTD baru jika diunggah
        if (isset($_FILES['skz_ttd']) && $_FILES['skz_ttd']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['skz_ttd'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

            if (in_array($ext, $allowed, true) && $file['size'] <= 2 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../assets/uploads/skz_ttd/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'ttd_ketua_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $oldTtd = get_setting('skz_ttd_path');
                    if (!empty($oldTtd) && $oldTtd !== 'assets/uploads/skz_ttd/' . $newFileName) {
                        $fullOld = __DIR__ . '/../../' . ltrim($oldTtd, '/\\');
                        if (file_exists($fullOld)) {
                            @unlink($fullOld);
                        }
                    }
                    $updates['skz_ttd_path'] = 'assets/uploads/skz_ttd/' . $newFileName;
                }
            }
        }

        foreach ($updates as $k => $v) {
            $stmt = $conn->prepare("INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param('sss', $k, $v, $v);
            $stmt->execute();
            $stmt->close();
        }

        log_audit('UPDATE_SETTINGS_SKZ', 'website_settings', null, "Memperbarui Pimpinan, Logo & TTD SKZ: {$pimpinan}");
        echo json_encode(['success' => true, 'message' => 'Pengaturan penandatangan, logo, dan tanda tangan SKZ berhasil disimpan.']);
        exit;
    }
}

// Ambil info pimpinan terkini untuk modal
$skzPimpinan = get_setting('skz_nama_pimpinan', 'Syukri Rahmat, S.Ag., M.M Inov');
$skzJabatan = get_setting('skz_jabatan_pimpinan', 'Ketua BAZNAS Kabupaten Sumbawa');
$skzKota = get_setting('skz_kota', 'Sumbawa Besar');
$skzAlamat = get_setting('skz_alamat_kantor', 'Jl. Hasanuddin No. 01 Kelurahan Bugis Kec. Sumbawa');
$skzTelp = get_setting('skz_telepon_kantor', '082 342 569 646');
$skzLogoUrl = get_skz_logo_url();
$skzHasCustomLogo = !empty(get_setting('skz_logo_path'));
$skzTtdUrl = get_skz_ttd_url();
$skzHasCustomTtd = !empty($skzTtdUrl);

include __DIR__ . '/../../inc/header.php';
?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h4 class="mb-1 font-weight-bold" style="color: #0d9f4f;"><i class="fas fa-file-contract mr-2"></i>Surat Ketetapan Zakat (SKZ) & BSZIS</h4>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Penerbitan surat ketetapan zakat dan bukti setor zakat/infak untuk rekanan proyek dinas & instansi.</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <button type="button" class="btn btn-outline-secondary mr-2" data-toggle="modal" data-target="#settingsModal" title="Pengaturan Nama Pimpinan & Jabatan">
                <i class="fas fa-user-cog mr-1"></i>Pengaturan Pimpinan
            </button>
            <a href="<?= url('pages/skz/form.php') ?>" class="btn btn-success">
                <i class="fas fa-plus-circle mr-1"></i>Buat SKZ & BSZIS Baru
            </a>
        </div>
    </div>
</div>

<div class="card shadow-none" style="border: 1px solid #b8b8b8;">
    <div class="card-header bg-white" style="border-bottom: 1px solid #dee2e6;">
        <h3 class="card-title font-weight-bold text-dark"><i class="fas fa-list-alt mr-2 text-success"></i>Daftar Surat Ketetapan & Bukti Setor</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="skzTable" class="table table-bordered table-hover" style="width: 100%; font-size: 0.88rem;">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th width="18%">Nomor & Tgl Surat</th>
                        <th>Badan Usaha & Pekerjaan</th>
                        <th width="14%">Dinas / Instansi</th>
                        <th width="14%">Nilai Kontrak</th>
                        <th width="13%">Nilai ZIS</th>
                        <th width="11%">Via & Objek</th>
                        <th width="14%" class="text-center">Aksi & Cetak</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Pengaturan Pimpinan & Pejabat Penandatangan -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content shadow-none" style="border: 1px solid #b8b8b8;">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="settingsModalLabel">
                    <i class="fas fa-user-shield mr-2 text-success"></i>Pengaturan Penandatangan SKZ
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formSettingsSkz" class="no-preloader">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Nama Pimpinan / Pejabat Penandatangan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="skz_nama_pimpinan" value="<?= clean($skzPimpinan) ?>" required placeholder="Contoh: Syukri Rahmat, S.Ag., M.M Inov">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Jabatan Pimpinan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="skz_jabatan_pimpinan" value="<?= clean($skzJabatan) ?>" required placeholder="Contoh: Ketua BAZNAS Kabupaten Sumbawa">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Kota Penandatanganan</label>
                        <input type="text" class="form-control" name="skz_kota" value="<?= clean($skzKota) ?>" placeholder="Contoh: Sumbawa Besar">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Alamat Kantor (Kop BSZIS)</label>
                        <textarea class="form-control" name="skz_alamat_kantor" rows="2" placeholder="Alamat kantor BAZNAS"><?= clean($skzAlamat) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Nomor Telepon Kantor (Kop BSZIS)</label>
                        <input type="text" class="form-control" name="skz_telepon_kantor" value="<?= clean($skzTelp) ?>" placeholder="Contoh: 082 342 569 646">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-image mr-1 text-success"></i>Logo Surat (SKZ & BSZIS)</label>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="mr-3 mb-2" style="width: 130px; height: 75px; border: 1px solid #ced4da; background: #fff; display: flex; align-items: center; justify-content: center; padding: 4px; border-radius: 4px;">
                                <img id="previewSkzLogo" src="<?= $skzLogoUrl ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <div class="mb-2" style="flex: 1; min-width: 200px;">
                                <input type="file" name="skz_logo" id="inputSkzLogo" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" class="form-control-file">
                                <small class="text-muted d-block mt-1">Format: PNG, JPG, SVG, atau WEBP (Maks. 2MB). Kosongkan jika tidak ingin mengubah.</small>
                                <?php if ($skzHasCustomLogo): ?>
                                    <button type="button" class="btn btn-outline-danger btn-xs mt-1" id="btnResetLogo">
                                        <i class="fas fa-undo mr-1"></i>Reset ke Logo Garuda BAZNAS Default
                                    </button>
                                <?php endif; ?>
                                <input type="hidden" name="reset_logo" id="reset_logo" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-signature mr-1 text-primary"></i>Tanda Tangan Ketua / Pimpinan</label>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="mr-3 mb-2" style="width: 130px; height: 75px; border: 1px solid #ced4da; background: #fff; display: flex; align-items: center; justify-content: center; padding: 4px; border-radius: 4px;">
                                <img id="previewSkzTtd" src="<?= $skzTtdUrl ?: '' ?>" style="<?= empty($skzTtdUrl) ? 'display:none;' : '' ?>max-width: 100%; max-height: 100%; object-fit: contain;">
                                <span id="textNoTtd" class="text-muted font-italic" style="<?= !empty($skzTtdUrl) ? 'display:none;' : '' ?>font-size: 0.78rem;">(Belum ada TTD)</span>
                            </div>
                            <div class="mb-2" style="flex: 1; min-width: 200px;">
                                <input type="file" name="skz_ttd" id="inputSkzTtd" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" class="form-control-file">
                                <small class="text-muted d-block mt-1">Format: PNG transparan (disarankan), JPG, SVG, WEBP (Maks. 2MB).</small>
                                <?php if ($skzHasCustomTtd): ?>
                                    <button type="button" class="btn btn-outline-danger btn-xs mt-1" id="btnResetTtd">
                                        <i class="fas fa-trash-alt mr-1"></i>Hapus Tanda Tangan
                                    </button>
                                <?php endif; ?>
                                <input type="hidden" name="reset_ttd" id="reset_ttd" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnSaveSettings">
                        <i class="fas fa-save mr-1"></i>Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#skzTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.location.href,
            type: 'GET'
        },
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [0, 7], orderable: false },
            { targets: [0, 7], className: 'text-center' }
        ],
        language: {
            processing: '<div class="text-center py-2"><i class="fa fa-spinner fa-spin fa-2x text-success"></i></div>',
            search: "Cari Data:",
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

    // Preview Logo saat dipilih
    $('#inputSkzLogo').on('change', function(e) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                $('#previewSkzLogo').attr('src', evt.target.result);
                $('#reset_logo').val('0');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Tombol Reset Logo
    $('#btnResetLogo').on('click', function() {
        $('#reset_logo').val('1');
        $('#previewSkzLogo').attr('src', '<?= base_url("assets/img/logo_baznas_garuda.svg") ?>');
        $('#inputSkzLogo').val('');
        $(this).hide();
    });

    // Preview TTD saat dipilih
    $('#inputSkzTtd').on('change', function(e) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                $('#previewSkzTtd').attr('src', evt.target.result).show();
                $('#textNoTtd').hide();
                $('#reset_ttd').val('0');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Tombol Reset TTD
    $('#btnResetTtd').on('click', function() {
        $('#reset_ttd').val('1');
        $('#previewSkzTtd').attr('src', '').hide();
        $('#textNoTtd').show();
        $('#inputSkzTtd').val('');
        $(this).hide();
    });

    // Simpan Pengaturan Pimpinan & Logo
    $('#formSettingsSkz').on('submit', function(e) {
        e.preventDefault();
        if (window.hidePreloader) window.hidePreloader();
        var $btn = $('#btnSaveSettings');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i>Menyimpan...');

        var formData = new FormData(this);
        formData.append('action', 'save_settings');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (window.hidePreloader) window.hidePreloader();
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Pengaturan');
                if (resp.success) {
                    $('#settingsModal').modal('hide');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: resp.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        alert(resp.message);
                    }
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    alert(resp.message || 'Gagal menyimpan pengaturan.');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan Pengaturan');
                alert('Terjadi kesalahan jaringan.');
            }
        });
    });
});

function deleteSkz(id) {
    var confirmMsg = 'Apakah Anda yakin ingin menghapus data SKZ & BSZIS ini? Data yang dihapus tidak dapat dipulihkan.';
    
    var executeDelete = function() {
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(resp) {
                if (resp.success) {
                    $('#skzTable').DataTable().ajax.reload(null, false);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: resp.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        alert(resp.message);
                    }
                } else {
                    alert(resp.message || 'Gagal menghapus data.');
                }
            },
            error: function() {
                alert('Terjadi kesalahan jaringan.');
            }
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: confirmMsg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                executeDelete();
            }
        });
    } else if (confirm(confirmMsg)) {
        executeDelete();
    }
}
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
