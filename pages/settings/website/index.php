<?php
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../config/skz_helper.php';
require_auth('admin');
$activeMenu = 'website_settings';
$pageTitle = 'Pengaturan Website';
$pageDescription = 'Kelola pengaturan website';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Pengaturan Website</li>';
$conn = db();
ensure_skz_table($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        $settings = [
            'site_title' => trim($_POST['site_title'] ?? ''),
            'site_description' => trim($_POST['site_description'] ?? ''),
            'site_keywords' => trim($_POST['site_keywords'] ?? ''),
            'admin_email' => trim($_POST['admin_email'] ?? ''),
            'timezone' => trim($_POST['timezone'] ?? 'Asia/Makassar'),
            'maintenance_mode' => $_POST['maintenance_mode'] ?? '0',
            'google_login_enabled' => $_POST['google_login_enabled'] ?? '0',
            'google_client_id' => trim($_POST['google_client_id'] ?? ''),
            'skz_nama_pimpinan' => trim($_POST['skz_nama_pimpinan'] ?? ''),
            'skz_jabatan_pimpinan' => trim($_POST['skz_jabatan_pimpinan'] ?? ''),
            'skz_kota' => trim($_POST['skz_kota'] ?? 'Sumbawa Besar'),
            'skz_alamat_kantor' => trim($_POST['skz_alamat_kantor'] ?? ''),
            'skz_telepon_kantor' => trim($_POST['skz_telepon_kantor'] ?? ''),
        ];

        // Reset logo jika diminta
        if (!empty($_POST['reset_skz_logo'])) {
            $oldPath = get_setting('skz_logo_path');
            if (!empty($oldPath)) {
                $fullOld = __DIR__ . '/../../../' . ltrim($oldPath, '/\\');
                if (file_exists($fullOld)) {
                    @unlink($fullOld);
                }
            }
            $settings['skz_logo_path'] = '';
        }

        // Upload logo baru jika diunggah
        if (isset($_FILES['skz_logo']) && $_FILES['skz_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['skz_logo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

            if (in_array($ext, $allowed, true) && $file['size'] <= 2 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../../assets/uploads/skz_logo/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'logo_skz_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $oldPath = get_setting('skz_logo_path');
                    if (!empty($oldPath) && $oldPath !== 'assets/uploads/skz_logo/' . $newFileName) {
                        $fullOld = __DIR__ . '/../../../' . ltrim($oldPath, '/\\');
                        if (file_exists($fullOld)) {
                            @unlink($fullOld);
                        }
                    }
                    $settings['skz_logo_path'] = 'assets/uploads/skz_logo/' . $newFileName;
                }
            }
        }

        // Reset TTD jika diminta
        if (!empty($_POST['reset_skz_ttd'])) {
            $oldTtd = get_setting('skz_ttd_path');
            if (!empty($oldTtd)) {
                $fullOld = __DIR__ . '/../../../' . ltrim($oldTtd, '/\\');
                if (file_exists($fullOld)) {
                    @unlink($fullOld);
                }
            }
            $settings['skz_ttd_path'] = '';
        }

        // Upload TTD baru jika diunggah
        if (isset($_FILES['skz_ttd']) && $_FILES['skz_ttd']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['skz_ttd'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp'];

            if (in_array($ext, $allowed, true) && $file['size'] <= 2 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../../assets/uploads/skz_ttd/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'ttd_ketua_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $oldTtd = get_setting('skz_ttd_path');
                    if (!empty($oldTtd) && $oldTtd !== 'assets/uploads/skz_ttd/' . $newFileName) {
                        $fullOld = __DIR__ . '/../../../' . ltrim($oldTtd, '/\\');
                        if (file_exists($fullOld)) {
                            @unlink($fullOld);
                        }
                    }
                    $settings['skz_ttd_path'] = 'assets/uploads/skz_ttd/' . $newFileName;
                }
            }
        }

        foreach ($settings as $key => $value) {
            $check = $conn->prepare('SELECT id FROM website_settings WHERE setting_key = ?');
            $check->bind_param('s', $key);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $stmt = $conn->prepare('UPDATE website_settings SET setting_value = ? WHERE setting_key = ?');
                $stmt->bind_param('ss', $value, $key);
            } else {
                $stmt = $conn->prepare('INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?)');
                $stmt->bind_param('ss', $key, $value);
            }
            $stmt->execute();
            $stmt->close();
        }

        redirect_with_flash(url('pages/settings/website/index.php'), 'Pengaturan berhasil disimpan', 'success');
    }
}

$settings = [];
$result = $conn->query('SELECT setting_key, setting_value FROM website_settings');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$skzLogoUrl = get_skz_logo_url();
$skzHasCustomLogo = !empty(get_setting('skz_logo_path'));
$skzTtdUrl = get_skz_ttd_url();
$skzHasCustomTtd = !empty($skzTtdUrl);

include __DIR__ . '/../../../inc/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-globe mr-2"></i>Pengaturan Website</h3>
    </div>
    <form method="post" enctype="multipart/form-data">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-heading mr-1"></i>Judul Website</label>
                        <input type="text" class="form-control" name="site_title" value="<?= clean($settings['site_title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-align-left mr-1"></i>Deskripsi Website</label>
                        <textarea class="form-control" name="site_description" rows="3"><?= clean($settings['site_description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tags mr-1"></i>Keywords</label>
                        <input type="text" class="form-control" name="site_keywords" value="<?= clean($settings['site_keywords'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-envelope mr-1"></i>Email Admin</label>
                        <input type="email" class="form-control" name="admin_email" value="<?= clean($settings['admin_email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock mr-1"></i>Timezone</label>
                        <select class="form-control" name="timezone">
                            <option value="Asia/Makassar" <?= ($settings['timezone'] ?? '') === 'Asia/Makassar' ? 'selected' : '' ?>>WITA (Asia/Makassar)</option>
                            <option value="Asia/Jakarta" <?= ($settings['timezone'] ?? '') === 'Asia/Jakarta' ? 'selected' : '' ?>>WIB (Asia/Jakarta)</option>
                            <option value="Asia/Jayapura" <?= ($settings['timezone'] ?? '') === 'Asia/Jayapura' ? 'selected' : '' ?>>WIT (Asia/Jayapura)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tools mr-1"></i>Mode Maintenance</label>
                        <select class="form-control" name="maintenance_mode">
                            <option value="0" <?= ($settings['maintenance_mode'] ?? '0') === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            <option value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'selected' : '' ?>>Aktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-top: 1px solid #dee2e6;">

            <h5 class="font-weight-bold text-success mb-3">
                <i class="fab fa-google mr-2"></i>Pengaturan Login Akun Google (OAuth 2.0 / Google Identity)
            </h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label><i class="fas fa-toggle-on mr-1"></i>Status Login Google</label>
                        <select class="form-control" name="google_login_enabled">
                            <option value="0" <?= ($settings['google_login_enabled'] ?? '0') === '0' ? 'selected' : '' ?>>Nonaktif</option>
                            <option value="1" <?= ($settings['google_login_enabled'] ?? '0') === '1' ? 'selected' : '' ?>>Aktif</option>
                        </select>
                        <small class="text-muted d-block mt-1">Hanya mengizinkan masuk bagi email Google yang terdaftar pada menu <strong>Manajemen Pengguna</strong>.</small>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label><i class="fas fa-key mr-1"></i>Google OAuth Client ID</label>
                        <input type="text" class="form-control" name="google_client_id" value="<?= clean($settings['google_client_id'] ?? '') ?>" placeholder="Contoh: 1234567890-abcdefg.apps.googleusercontent.com">
                        <small class="text-muted d-block mt-1">
                            Dapatkan Client ID dari <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console (Credentials)</a>.
                            Tambahkan origin situs Anda (misal <code>http://localhost</code> dan <code>https://<?= clean($_SERVER['HTTP_HOST'] ?? 'pengumpulan.baznassumbawa.id') ?></code>) pada bagian <strong>Authorized JavaScript origins</strong>.
                        </small>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-top: 1px solid #dee2e6;">

            <h5 class="font-weight-bold text-success mb-3">
                <i class="fas fa-file-contract mr-2"></i>Pengaturan Penandatangan & Logo Surat Ketetapan Zakat (SKZ) & BSZIS
            </h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-user-tie mr-1"></i>Nama Pimpinan / Pejabat Penandatangan</label>
                        <input type="text" class="form-control" name="skz_nama_pimpinan" value="<?= clean($settings['skz_nama_pimpinan'] ?? 'Syukri Rahmat, S.Ag., M.M Inov') ?>" placeholder="Nama lengkap dan gelar pimpinan">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-badge mr-1"></i>Jabatan Pimpinan</label>
                        <input type="text" class="form-control" name="skz_jabatan_pimpinan" value="<?= clean($settings['skz_jabatan_pimpinan'] ?? 'Ketua BAZNAS Kabupaten Sumbawa') ?>" placeholder="Jabatan pimpinan">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-map-marker-alt mr-1"></i>Kota Surat</label>
                        <input type="text" class="form-control" name="skz_kota" value="<?= clean($settings['skz_kota'] ?? 'Sumbawa Besar') ?>" placeholder="Sumbawa Besar">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fas fa-building mr-1"></i>Alamat Kantor BAZNAS (Kop BSZIS)</label>
                        <textarea class="form-control" name="skz_alamat_kantor" rows="2" placeholder="Alamat kantor BAZNAS"><?= clean($settings['skz_alamat_kantor'] ?? 'Jl. Hasanuddin No. 01 Kelurahan Bugis Kec. Sumbawa') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone mr-1"></i>Nomor Telepon Kantor (Kop BSZIS)</label>
                        <input type="text" class="form-control" name="skz_telepon_kantor" value="<?= clean($settings['skz_telepon_kantor'] ?? '082 342 569 646') ?>" placeholder="Nomor telepon BAZNAS">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-image mr-1"></i>Logo Surat Resmi (SKZ & BSZIS)</label>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="mr-3 mb-2" style="width: 140px; height: 80px; border: 1px solid #ced4da; background: #fff; display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 4px;">
                                <img id="previewSkzLogo" src="<?= $skzLogoUrl ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <div class="mb-2" style="flex: 1; min-width: 200px;">
                                <input type="file" name="skz_logo" id="inputSkzLogo" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" class="form-control-file">
                                <small class="text-muted d-block mt-1">Format: PNG, JPG, SVG, atau WEBP (Maks. 2MB). Biarkan kosong jika tidak ingin mengubah.</small>
                                <?php if ($skzHasCustomLogo): ?>
                                    <button type="button" class="btn btn-outline-danger btn-xs mt-1" id="btnResetLogo">
                                        <i class="fas fa-undo mr-1"></i>Reset ke Logo Garuda BAZNAS Default
                                    </button>
                                <?php endif; ?>
                                <input type="hidden" name="reset_skz_logo" id="reset_skz_logo" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-signature mr-1"></i>Tanda Tangan Digital Ketua / Pimpinan (SKZ)</label>
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="mr-3 mb-2" style="width: 140px; height: 80px; border: 1px solid #ced4da; background: #fff; display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 4px;">
                                <img id="previewSkzTtd" src="<?= $skzTtdUrl ?: '' ?>" style="<?= empty($skzTtdUrl) ? 'display:none;' : '' ?>max-width: 100%; max-height: 100%; object-fit: contain;">
                                <span id="textNoTtd" class="text-muted font-italic" style="<?= !empty($skzTtdUrl) ? 'display:none;' : '' ?>font-size: 0.8rem;">(Belum ada TTD)</span>
                            </div>
                            <div class="mb-2" style="flex: 1; min-width: 200px;">
                                <input type="file" name="skz_ttd" id="inputSkzTtd" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp" class="form-control-file">
                                <small class="text-muted d-block mt-1">Format: PNG transparan (disarankan), JPG, SVG, WEBP (Maks. 2MB).</small>
                                <?php if ($skzHasCustomTtd): ?>
                                    <button type="button" class="btn btn-outline-danger btn-xs mt-1" id="btnResetTtd">
                                        <i class="fas fa-trash-alt mr-1"></i>Hapus Tanda Tangan
                                    </button>
                                <?php endif; ?>
                                <input type="hidden" name="reset_skz_ttd" id="reset_skz_ttd" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan Pengaturan</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#inputSkzLogo').on('change', function(e) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                $('#previewSkzLogo').attr('src', evt.target.result);
                $('#reset_skz_logo').val('0');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#btnResetLogo').on('click', function() {
        $('#reset_skz_logo').val('1');
        $('#previewSkzLogo').attr('src', '<?= base_url("assets/img/logo_baznas_garuda.svg") ?>');
        $('#inputSkzLogo').val('');
        $(this).hide();
    });

    $('#inputSkzTtd').on('change', function(e) {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                $('#previewSkzTtd').attr('src', evt.target.result).show();
                $('#textNoTtd').hide();
                $('#reset_skz_ttd').val('0');
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('#btnResetTtd').on('click', function() {
        $('#reset_skz_ttd').val('1');
        $('#previewSkzTtd').attr('src', '').hide();
        $('#textNoTtd').show();
        $('#inputSkzTtd').val('');
        $(this).hide();
    });
});
</script>

<?php include __DIR__ . '/../../../inc/footer.php'; ?>
