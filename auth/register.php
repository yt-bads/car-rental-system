<?php
// auth/register.php
// Halaman Registrasi Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'customer':
            header('Location: ../customer/dashboard.php');
            break;
        case 'direktur':
            header('Location: ../direktur/laporan.php');
            break;
    }
    exit;
}

// Proses form registrasi ketika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $noTelepon   = sanitize($_POST['no_telepon'] ?? '');
    $alamat      = sanitize($_POST['alamat'] ?? '');
    $password    = $_POST['password'] ?? '';
    $konfirmasi  = $_POST['konfirmasi_password'] ?? '';

    // Validasi data input
    if (empty($namaLengkap) || empty($email) || empty($noTelepon) || empty($alamat) || empty($password)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'msg' => 'Semua kolom wajib diisi kecuali dinyatakan opsional.'
        ];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'msg' => 'Format alamat email tidak valid.'
        ];
    } elseif (strlen($password) < 6) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'msg' => 'Kata sandi minimal terdiri dari 6 karakter.'
        ];
    } elseif ($password !== $konfirmasi) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'msg' => 'Konfirmasi kata sandi tidak cocok.'
        ];
    } else {
        try {
            // 1. Cek apakah email sudah terdaftar
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'msg' => 'Alamat email sudah terdaftar di sistem kami.'
                ];
            } else {
                // 2. Simpan user baru (role: customer)
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (nama_lengkap, email, password, no_telepon, alamat, role)
                    VALUES (?, ?, ?, ?, ?, 'customer')
                ");
                $insertStmt->execute([$namaLengkap, $email, $passwordHash, $noTelepon, $alamat]);

                // Redirect dengan sukses flash message
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg' => 'Pendaftaran akun berhasil! Silakan masuk menggunakan email Anda.'
                ];
                header('Location: login.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('[Register Error] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type' => 'danger',
                'msg' => 'Terjadi kesalahan sistem saat mendaftar, silakan coba lagi.'
            ];
        }
    }
}

// Set Judul Halaman
$page_title = "Daftar Akun Baru";

// Tampilkan Header (Output HTML)
require_once '../includes/header.php';
?>

<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);">
    <div class="row justify-content-center w-100">
        <div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            
            <!-- Notifikasi Flash Alert -->
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= $_SESSION['flash']['msg'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php unset($_SESSION['flash']); endif; ?>

            <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <!-- Decorative Top Bar -->
                <div style="height: 6px; background-color: var(--color-accent);"></div>
                
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="../assets/img/logo.png" alt="Logo PT. Wildan Abadi Jaya" class="img-fluid mb-3" style="max-height: 70px; width: auto; object-fit: contain;">
                        <h4 class="fw-bold mb-1" style="color: var(--color-primary);">Daftar Customer Baru</h4>
                        <p class="text-muted small">Bergabunglah dengan PT. Wildan Abadi Jaya untuk sewa mobil premium</p>
                    </div>

                    <form action="register.php" method="POST" autocomplete="off">
                        
                        <!-- Nama Lengkap -->
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" id="nama_lengkap" class="form-control border-start-0 bg-light" name="nama_lengkap" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" placeholder="Masukkan nama lengkap Anda" required>
                            </div>
                        </div>

                        <!-- Baris Ganda: Email & No. Telepon -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="email" class="form-control border-start-0 bg-light" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="no_telepon" class="form-label">Nomor Telepon / WA <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-telephone"></i></span>
                                    <input type="text" id="no_telepon" class="form-control border-start-0 bg-light" name="no_telepon" value="<?= htmlspecialchars($_POST['no_telepon'] ?? '') ?>" placeholder="Contoh: 0812xxxx" required>
                                </div>
                            </div>
                        </div>

                        <!-- Alamat Lengkap -->
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat Rumah Lengkap <span class="text-danger">*</span></label>
                            <textarea id="alamat" class="form-control bg-light" name="alamat" rows="2" placeholder="Masukkan alamat lengkap tempat tinggal Anda" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                        </div>

                        <!-- Baris Ganda: Password & Konfirmasi -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="password" class="form-label">Kata Sandi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" class="form-control border-start-0 border-end-0 bg-light" name="password" placeholder="Min. 6 karakter" required>
                                    <button class="btn btn-outline-secondary border-start-0 bg-light toggle-password" type="button" data-target="#password">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="konfirmasi_password" class="form-label">Konfirmasi Sandi <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-shield-check"></i></span>
                                    <input type="password" id="konfirmasi_password" class="form-control border-start-0 border-end-0 bg-light" name="konfirmasi_password" placeholder="Ulangi kata sandi" required>
                                    <button class="btn btn-outline-secondary border-start-0 bg-light toggle-password" type="button" data-target="#konfirmasi_password">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 mb-3" style="border-radius: 8px;">
                            Daftar Sekarang
                        </button>

                        <div class="text-center mt-3">
                            <p class="small text-muted mb-0">Sudah punya akun? <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--color-primary-light);">Masuk</a></p>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="small text-white-50 mb-0">&copy; 2026 PT. Wildan Abadi Jaya. All rights reserved.</p>
            </div>

        </div>
    </div>
</div>

<?php
// Tampilkan Footer
require_once '../includes/footer.php';
?>
