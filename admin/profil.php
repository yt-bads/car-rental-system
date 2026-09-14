<?php
// admin/profil.php
// Halaman Profil Admin — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Profil Pengguna | Admin';
$extra_css  = 'admin.css';

require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-person-circle me-2"></i>Profil Pengguna
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Profil</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Alert Info -->
    <div class="alert alert-info alert-dismissible fade show mb-4 d-flex align-items-center shadow-sm" role="alert">
        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
        <div>
            <strong>Pemberitahuan:</strong> Fitur ubah profil saat ini dinonaktifkan oleh sistem pusat.
        </div>
    </div>

    <!-- Profile UI Card -->
    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-header px-4 py-3" style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold">
                        <i class="bi bi-card-heading me-2"></i>Informasi Akun
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2rem;">
                            <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['nama'] ?? 'Administrator') ?></h5>
                        <span class="badge bg-primary text-uppercase px-2.5 py-1" style="font-size: 0.75rem;"><?= htmlspecialchars($_SESSION['role'] ?? 'admin') ?></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($_SESSION['nama'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Alamat Email</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted mb-1">Hak Akses / Role</label>
                            <input type="text" class="form-control bg-light text-capitalize" value="<?= htmlspecialchars($_SESSION['role'] ?? '') ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
