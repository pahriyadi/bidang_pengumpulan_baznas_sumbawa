<?php
require_once __DIR__ . '/../../config/helpers.php';
require_auth();

$activeMenu = 'profile';
$pageTitle = 'Profil Saya';
$pageDescription = 'Kelola informasi pribadi dan foto profil Anda';
$breadcrumbs = '<li class="breadcrumb-item"><a href="' . url('index.php') . '">Beranda</a></li><li class="breadcrumb-item active">Profil Saya</li>';

$user = current_user();
$conn = db();

// Query detail data user lengkap
$stmt = $conn->prepare("SELECT id, username, full_name, email, role, photo FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullName === '' || $username === '') {
        $error = 'Nama Lengkap dan Username wajib diisi.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek keunikan username
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
        $check->bind_param('si', $username, $user['id']);
        $check->execute();
        $usernameExists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($usernameExists) {
            $error = 'Username sudah digunakan oleh akun lain.';
        } elseif ($password !== '') {
            $pwdErr = '';
            if (!validate_password_strength($password, $pwdErr)) {
                $error = $pwdErr;
            }
        }
    }

    if ($error === '') {
        // Handle file upload foto profil
        $photoPath = $dbUser['photo'];
        if (isset($_FILES['photo_file']) && $_FILES['photo_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['photo_file']['tmp_name'];
            $fileName = $_FILES['photo_file']['name'];
            $fileSize = $_FILES['photo_file']['size'];

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                $error = 'Format foto harus berupa JPG, JPEG, PNG, GIF, atau WEBP.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal adalah 2MB.';
            } else {
                $uploadDir = __DIR__ . '/../../assets/uploads/profile/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'profile_' . $user['id'] . '_' . time() . '.' . $ext;
                $targetFile = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $targetFile)) {
                    // Hapus foto lama jika ada
                    if (!empty($dbUser['photo'])) {
                        $oldPhotoFile = __DIR__ . '/../../' . $dbUser['photo'];
                        if (is_file($oldPhotoFile)) {
                            @unlink($oldPhotoFile);
                        }
                    }
                    $photoPath = 'assets/uploads/profile/' . $newFileName;
                } else {
                    $error = 'Gagal mengunggah foto profil baru.';
                }
            }
        }
    }

    if ($error === '') {
        // Simpan data profil ke database
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, password = ?, photo = ? WHERE id = ?");
            $stmt->bind_param('sssssi', $fullName, $email, $username, $hashedPassword, $photoPath, $user['id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, username = ?, photo = ? WHERE id = ?");
            $stmt->bind_param('ssssi', $fullName, $email, $username, $photoPath, $user['id']);
        }

        if ($stmt->execute()) {
            log_audit('UPDATE_PROFILE', 'users', $user['id'], 'User updated their own profile');
            $stmt->close();
            
            // Redirect dengan flash message agar sidebar terupdate instan
            redirect_with_flash(url('pages/profile/index.php'), 'Profil Anda berhasil diperbarui.', 'success');
        } else {
            $error = 'Gagal memperbarui data profil di database.';
            $stmt->close();
        }
    }
}

include __DIR__ . '/../../inc/header.php';
?>

<form method="post" enctype="multipart/form-data" action="">
    <div class="row">
        <!-- Kolom Kiri: Foto Profil -->
        <div class="col-md-4">
            <div class="card card-outline card-success shadow-none">
                <div class="card-header text-center">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-image mr-2"></i>Foto Profil</h3>
                </div>
                <div class="card-body text-center">
                    <?php
                    $currentPhoto = !empty($dbUser['photo']) ? base_url($dbUser['photo']) : asset('adminlte/dist/img/user2-160x160.jpg');
                    ?>
                    <div class="mb-4 d-flex justify-content-center">
                        <img src="<?= $currentPhoto ?>" id="profile-preview" class="img-circle img-fluid border border-secondary" alt="Foto Profil" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <h5 class="font-weight-bold mb-1"><?= htmlspecialchars($dbUser['full_name']) ?></h5>
                    <p class="text-muted text-sm text-uppercase mb-4"><?= htmlspecialchars($dbUser['role']) ?></p>
                    
                    <div class="form-group text-left">
                        <label>Unggah Foto Baru</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="photo_file" id="photo_file" accept="image/*" onchange="previewImage(this)">
                            <label class="custom-file-label text-truncate" for="photo_file">Pilih foto...</label>
                        </div>
                        <small class="text-muted mt-2 d-block">Ekstensi: JPG, JPEG, PNG, WEBP. Maks 2MB.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Detail Informasi -->
        <div class="col-md-8">
            <div class="card card-outline card-success shadow-none">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-user-edit mr-2"></i>Detail Informasi</h3>
                </div>
                <div class="card-body">
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 4px;">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($dbUser['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($dbUser['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($dbUser['username']) ?>" required>
                    </div>

                    <hr class="my-4" style="border-top: 1px solid #b8b8b8;">

                    <div class="form-group mb-0">
                        <label>Password Baru <span class="text-muted font-weight-normal">(Kosongkan jika tidak ingin diubah)</span></label>
                        <input type="password" class="form-control" name="password" placeholder="Masukkan password baru">
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="<?= url('index.php') ?>" class="btn btn-secondary mr-2">Batal</a>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Preview foto yang dipilih secara langsung sebelum diunggah
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profile-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
            
            // Ubah label input file dengan nama file terpilih
            const fileName = input.files[0].name;
            const label = input.nextElementSibling;
            if (label) {
                label.textContent = fileName;
            }
        }
    }
</script>

<?php include __DIR__ . '/../../inc/footer.php'; ?>
