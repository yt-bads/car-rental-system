<?php
// customer/edit-profil.php
// Edit Profil & Ganti Password Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// 1. Ambil data customer saat ini
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirectWith('../auth/logout.php', 'danger', 'Data profil Anda tidak ditemukan.');
}

// 2. Tangani Form Submission POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap        = sanitize($_POST['nama_lengkap'] ?? '');
    $no_telepon          = sanitize($_POST['no_telepon'] ?? '');
    $alamat              = sanitize($_POST['alamat'] ?? '');
    $password_lama       = $_POST['password_lama'] ?? '';
    $password_baru       = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    // Validasi input nama lengkap wajib diisi
    if (empty($nama_lengkap)) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Nama lengkap wajib diisi.'];
    } else {
        $changePassword = false;
        $hashedPassword = '';

        // Cek jika pengguna mencoba mengganti password
        if (!empty($password_baru) || !empty($password_lama) || !empty($konfirmasi_password)) {
            // Wajib isi ketiganya
            if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Untuk mengganti password, harap isi Password Lama, Password Baru, dan Konfirmasi.'];
            } elseif ($password_baru !== $konfirmasi_password) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Konfirmasi password baru tidak cocok.'];
            } elseif (strlen($password_baru) < 6) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Password baru minimal harus terdiri dari 6 karakter.'];
            } elseif (!password_verify($password_lama, $user['password'])) {
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Password lama yang Anda masukkan salah.'];
            } else {
                // Semua validasi ganti password lolos
                $changePassword = true;
                $hashedPassword = password_hash($password_baru, PASSWORD_BCRYPT);
            }
        }

        // Jika tidak ada error validasi di atas, proses update
        if (!isset($_SESSION['flash'])) {
            try {
                if ($changePassword) {
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users 
                        SET nama_lengkap = ?, no_telepon = ?, alamat = ?, password = ? 
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$nama_lengkap, $no_telepon, $alamat, $hashedPassword, $_SESSION['user_id']]);
                } else {
                    $stmtUpdate = $pdo->prepare("
                        UPDATE users 
                        SET nama_lengkap = ?, no_telepon = ?, alamat = ? 
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$nama_lengkap, $no_telepon, $alamat, $_SESSION['user_id']]);
                }

                // Perbarui nama di sesi agar tampilan header terupdate
                $_SESSION['nama'] = $nama_lengkap;
                
                // Redirect untuk memicu reload data terbaru (PRG pattern)
                redirectWith('edit-profil.php', 'success', 'Profil Anda berhasil diperbarui!');

            } catch (PDOException $e) {
                error_log("[Profile Update Error] " . $e->getMessage());
                $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Terjadi kesalahan sistem saat memperbarui profil.'];
            }
        }
    }
}

$page_title = "Edit Profil Saya";
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';
?>

<div class="container py-4">

    <!-- ======= BREADCRUMB ======= -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengaturan Profil</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- ======= PROFILE FORM SHEET ======= -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="edit-profil.php" method="POST">
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-gear me-2 text-primary"></i>Informasi Data Diri</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Email (Readonly) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark">Alamat Email (Username)</label>
                                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                <small class="text-muted" style="font-size:0.75rem;">Email tidak dapat diubah karena merupakan identitas login unik Anda.</small>
                            </div>

                            <!-- Nama Lengkap -->
                            <div class="col-md-6">
                                <label for="nama_lengkap" class="form-label fw-bold text-dark">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" id="nama_lengkap" class="form-control" name="nama_lengkap" 
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required placeholder="Masukkan nama lengkap Anda">
                            </div>

                            <!-- Nomor Telepon / HP -->
                            <div class="col-md-12">
                                <label for="no_telepon" class="form-label fw-bold text-dark">Nomor WhatsApp / Telepon</label>
                                <input type="text" id="no_telepon" class="form-control" name="no_telepon" 
                                       value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>" placeholder="Contoh: 08123456789">
                            </div>

                            <!-- Alamat Fisik -->
                            <div class="col-md-12">
                                <label for="alamat" class="form-label fw-bold text-dark">Alamat Domisili Lengkap</label>
                                <textarea id="alamat" class="form-control" name="alamat" rows="3" placeholder="Masukkan alamat lengkap tempat tinggal Anda..."><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bagian Ubah Password -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-lock me-2 text-primary"></i>Ubah Password Akun (Opsional)</h6>
                        <span class="badge bg-light text-muted">Biarkan kosong jika tidak ingin mengubah</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Password Lama -->
                            <div class="col-12">
                                <label for="password_lama" class="form-label fw-bold text-dark">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" id="password_lama" class="form-control" name="password_lama" 
                                           placeholder="Masukkan password Anda saat ini">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password_lama">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Password Baru -->
                            <div class="col-md-6">
                                <label for="password_baru" class="form-label fw-bold text-dark">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" id="password_baru" class="form-control" name="password_baru" 
                                           placeholder="Minimal 6 karakter">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password_baru">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Konfirmasi Password Baru -->
                            <div class="col-md-6">
                                <label for="konfirmasi_password" class="form-label fw-bold text-dark">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" id="konfirmasi_password" class="form-control" name="konfirmasi_password" 
                                           placeholder="Ketik ulang password baru">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#konfirmasi_password">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Panel -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                    <div class="card-body p-3 d-flex gap-2 justify-content-end">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4 py-2">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary-custom px-4 py-2">
                            <i class="bi bi-save me-1"></i>Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div><!-- /.customer-wrapper -->

<?php
require_once '../includes/footer.php';
?>
