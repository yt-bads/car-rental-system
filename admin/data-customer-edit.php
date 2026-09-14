<?php
// admin/data-customer-edit.php
// Form Edit Data Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    redirectWith('data-customer.php', 'danger', 'ID customer tidak valid.');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirectWith('data-customer.php', 'danger', 'Customer tidak ditemukan.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaLengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $noTelepon   = sanitize($_POST['no_telepon'] ?? '');
    $alamat      = sanitize($_POST['alamat'] ?? '');

    // Validasi
    if (empty($namaLengkap)) $errors[] = 'Nama lengkap wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';

    // Cek email unik kecuali milik sendiri
    if (!empty($email)) {
        $cekEmail = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $cekEmail->execute([$email, $id]);
        if ($cekEmail->rowCount() > 0) {
            $errors[] = 'Email sudah digunakan oleh akun lain.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users SET nama_lengkap=?, email=?, no_telepon=?, alamat=?
                WHERE id=? AND role='customer'
            ");
            $stmt->execute([$namaLengkap, $email, $noTelepon, $alamat, $id]);
            redirectWith('data-customer.php', 'success', 'Data customer berhasil diperbarui.');
        } catch (PDOException $e) {
            error_log('[EditCustomer Error] ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem.';
        }
    }

    $customer = array_merge($customer, [
        'nama_lengkap' => $namaLengkap,
        'email'        => $email,
        'no_telepon'   => $noTelepon,
        'alamat'       => $alamat,
    ]);
}

// Riwayat transaksi customer
$riwayat = $pdo->prepare("
    SELECT p.kode_pesan, p.tanggal_sewa, p.tanggal_kembali,
           p.total_biaya, p.status, m.nama_mobil
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.customer_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
");
$riwayat->execute([$id]);
$transaksiList = $riwayat->fetchAll();

$page_title = 'Edit Customer | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-person-gear me-2"></i>Edit Data Customer
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="data-customer.php">Data Customer</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="data-customer.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Periksa kembali form:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Form Edit -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold">
                        <i class="bi bi-person-fill me-2"></i>Informasi Akun
                    </span>
                </div>
                <div class="card-body p-4">
                    <form action="data-customer-edit.php?id=<?= $id ?>" method="POST">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                                   value="<?= htmlspecialchars($customer['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($customer['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="no_telepon" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control" id="no_telepon" name="no_telepon"
                                   value="<?= htmlspecialchars($customer['no_telepon'] ?? '') ?>"
                                   placeholder="cth: 081234567890">
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3"
                                      placeholder="Alamat lengkap customer"><?= htmlspecialchars($customer['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2 pt-2 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-floppy me-1"></i> Simpan
                            </button>
                            <a href="data-customer.php" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Riwayat Transaksi Customer -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Transaksi
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($transaksiList)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size:2rem; opacity:0.3;"></i>
                            <p class="mt-2 mb-0 small">Belum ada transaksi</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Mobil</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksiList as $t): ?>
                                <tr>
                                    <td class="small fw-semibold"><?= htmlspecialchars($t['kode_pesan']) ?></td>
                                    <td class="small"><?= htmlspecialchars($t['nama_mobil']) ?></td>
                                    <td class="small text-rupiah"><?= formatRupiah($t['total_biaya']) ?></td>
                                    <td><?= getStatusBadge($t['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
