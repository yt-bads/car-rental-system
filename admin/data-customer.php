<?php
// admin/data-customer.php
// Kelola Data Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil semua customer + jumlah transaksi masing-masing
$customerList = $pdo->query("
    SELECT u.*,
           COUNT(p.id)  AS total_pesan,
           SUM(CASE WHEN p.status = 'aktif' THEN 1 ELSE 0 END) AS pesan_aktif
    FROM users u
    LEFT JOIN pemesanan p ON p.customer_id = u.id
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.nama_lengkap ASC
")->fetchAll();

$page_title = 'Data Customer | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-people me-2"></i>Data Customer
            </h4>
            <p class="text-muted small mb-0">Daftar semua pelanggan terdaftar di sistem</p>
        </div>
    </div>

    <!-- Tabel Customer -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center px-4 py-3"
             style="background-color: var(--color-primary); color: white;">
            <span class="fw-semibold">
                <i class="bi bi-person-lines-fill me-2"></i>Daftar Customer
                <span class="badge bg-warning text-dark ms-2"><?= count($customerList) ?> akun</span>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($customerList)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-people" style="font-size: 3rem; opacity:0.3;"></i>
                    <p class="mt-3 mb-0">Belum ada customer terdaftar</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Total Pesan</th>
                            <th>Status Sewa</th>
                            <th>Bergabung</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerList as $i => $c): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:38px; height:38px; border-radius:50%;
                                                background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
                                                display:flex; align-items:center; justify-content:center;
                                                color:white; font-weight:700; font-size:0.85rem; flex-shrink:0;">
                                        <?= strtoupper(substr($c['nama_lengkap'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($c['nama_lengkap']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['no_telepon'] ?? '-') ?></td>
                            <td class="text-center">
                                <span class="badge bg-primary"><?= $c['total_pesan'] ?> transaksi</span>
                            </td>
                            <td>
                                <?php if ($c['pesan_aktif'] > 0): ?>
                                    <span class="badge bg-warning">Sedang Menyewa</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                            <td>
                                <a href="data-customer-edit.php?id=<?= $c['id'] ?>"
                                   class="btn btn-sm btn-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
