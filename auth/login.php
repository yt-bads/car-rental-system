<?php
// auth/login.php
// Halaman Login Pengguna — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                header('Location: ../admin/dashboard.php');
                break;
            case 'customer':
                header('Location: ../customer/dashboard.php');
                break;
            case 'direktur':
                header('Location: ../direktur/dashboard.php');
                break;
        }
        exit;
    } else {
        session_destroy();
        $_SESSION = [];
    }
}

// Proses form login ketika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['flash'] = [
            'type' => 'danger',
            'msg' => 'Email dan Password wajib diisi.'
        ];
    } else {
        try {
            // Prepared statement untuk keamanan dari SQL Injection
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set Session Kredensial
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['nama']     = $user['nama_lengkap'];
                $_SESSION['email']    = $user['email'];

                // Redirect sesuai role masing-masing
                switch ($user['role']) {
                    case 'admin':
                        header('Location: ../admin/dashboard.php');
                        break;
                    case 'customer':
                        header('Location: ../customer/dashboard.php');
                        break;
                    case 'direktur':
                        header('Location: ../direktur/dashboard.php');
                        break;
                    default:
                        // Bersihkan jika ada anomali role
                        session_destroy();
                        redirectWith('login.php', 'danger', 'Peran pengguna tidak valid.');
                        break;
                }
                exit;
            } else {
                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'msg' => 'Email atau Password salah.'
                ];
            }
        } catch (PDOException $e) {
            error_log('[Login Error] ' . $e->getMessage());
            $_SESSION['flash'] = [
                'type' => 'danger',
                'msg' => 'Terjadi kesalahan sistem, silakan coba lagi nanti.'
            ];
        }
    }
}

// Set Judul Halaman
$page_title = "Masuk";

// Tampilkan Header (Output HTML)
require_once '../includes/header.php';
?>

<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);">
    <div class="row justify-content-center w-100">
        <div class="col-11 col-sm-9 col-md-7 col-lg-5 col-xl-4">
            
            <!-- Notifikasi Flash Alert -->
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
                    <?= $_SESSION['flash']['msg'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php unset($_SESSION['flash']); endif; ?>

            <div class="card border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <!-- Decorative Top Bar -->
                <div style="height: 6px; background-color: var(--color-accent);"></div>
                
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="../assets/img/logo.png" alt="Logo PT. Wildan Abadi Jaya" class="img-fluid mb-3" style="max-height: 80px; width: auto; object-fit: contain;">
                        <h4 class="fw-bold mb-1" style="color: var(--color-primary);">PT. Wildan Abadi Jaya</h4>
                        <p class="text-muted small">Sistem Informasi Rental Mobil Premium</p>
                    </div>

                    <form action="login.php" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label for="email" class="form-label">Alamat Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" class="form-control border-start-0 bg-light" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="nama@email.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Kata Sandi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" id="password" class="form-control border-start-0 border-end-0 bg-light" name="password" placeholder="Masukkan kata sandi" required>
                                <button class="btn btn-outline-secondary border-start-0 bg-light toggle-password"
                                        type="button"
                                        data-target="#password"
                                        tabindex="-1">
                                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2.5 mb-3" style="border-radius: 8px;">
                            Masuk
                        </button>

                        <div class="text-center mt-3">
                            <p class="small text-muted mb-0">Belum punya akun? <a href="register.php" class="text-decoration-none fw-semibold" style="color: var(--color-primary-light);">Daftar Sekarang</a></p>
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
