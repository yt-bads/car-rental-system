<?php
// admin/transaksi.php
// Kelola Transaksi Rental — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Filter status
$filterStatus = $_GET['status'] ?? '';
$allowedStatus = ['menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif', 'selesai', 'dibatalkan'];

if ($filterStatus !== '' && in_array($filterStatus, $allowedStatus)) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nama_lengkap, u.no_telepon, u.email, m.nama_mobil, m.tipe, m.plat_nomor
        FROM pemesanan p
        JOIN users u ON p.customer_id = u.id
        JOIN mobil m ON p.mobil_id = m.id
        WHERE p.status = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$filterStatus]);
} else {
    $stmt = $pdo->query("
        SELECT p.*, u.nama_lengkap, u.no_telepon, u.email, m.nama_mobil, m.tipe, m.plat_nomor
        FROM pemesanan p
        JOIN users u ON p.customer_id = u.id
        JOIN mobil m ON p.mobil_id = m.id
        ORDER BY p.created_at DESC
    ");
}
$transaksiList = $stmt->fetchAll();

$page_title = 'Transaksi Rental | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-receipt me-2"></i>Transaksi Rental
            </h4>
            <p class="text-muted small mb-0">Kelola semua transaksi pemesanan</p>
        </div>
    </div>

    <!-- Filter Status -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small fw-semibold">Filter:</span>
                <a href="transaksi.php"
                   class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">Semua</a>
                <a href="transaksi.php?status=menunggu_pembayaran"
                   class="btn btn-sm <?= $filterStatus === 'menunggu_pembayaran' ? 'btn-warning' : 'btn-outline-warning' ?>">
                   Menunggu Bayar</a>
                <a href="transaksi.php?status=menunggu_konfirmasi"
                   class="btn btn-sm <?= $filterStatus === 'menunggu_konfirmasi' ? 'btn-info' : 'btn-outline-info' ?>">
                   Menunggu Konfirmasi</a>
                <a href="transaksi.php?status=aktif"
                   class="btn btn-sm <?= $filterStatus === 'aktif' ? 'btn-primary' : 'btn-outline-primary' ?>">Aktif</a>
                <a href="transaksi.php?status=selesai"
                   class="btn btn-sm <?= $filterStatus === 'selesai' ? 'btn-success' : 'btn-outline-success' ?>">Selesai</a>
                <a href="transaksi.php?status=dibatalkan"
                   class="btn btn-sm <?= $filterStatus === 'dibatalkan' ? 'btn-danger' : 'btn-outline-danger' ?>">Dibatalkan</a>
            </div>
        </div>
    </div>

    <!-- Tabel Transaksi -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center px-4 py-3"
             style="background-color: var(--color-primary); color: white;">
            <span class="fw-semibold">
                <i class="bi bi-list-check me-2"></i>Daftar Transaksi
                <span class="badge bg-warning text-dark ms-2"><?= count($transaksiList) ?> data</span>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($transaksiList)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-receipt" style="font-size: 3rem; opacity:0.3;"></i>
                    <p class="mt-3 mb-0">Tidak ada data transaksi</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Kode Pesan</th>
                            <th>Customer</th>
                            <th>Mobil</th>
                            <th>Tgl Sewa</th>
                            <th>Tgl Kembali</th>
                            <th>Lama</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th style="width:90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaksiList as $t): ?>
                        <tr>
                            <td>
                                <a href="transaksi-detail.php?id=<?= $t['id'] ?>"
                                   class="fw-semibold text-decoration-none"
                                   style="color: var(--color-primary);">
                                    <?= htmlspecialchars($t['kode_pesan']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold small"><?= htmlspecialchars($t['nama_lengkap']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($t['no_telepon'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($t['nama_mobil']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><code><?= htmlspecialchars($t['plat_nomor'] ?? '') ?></code></div>
                            </td>
                            <td><?= date('d M Y', strtotime($t['tanggal_sewa'])) ?></td>
                            <td><?= date('d M Y', strtotime($t['tanggal_kembali'])) ?></td>
                            <td class="text-center"><?= $t['lama_sewa'] ?> hari</td>
                            <td class="text-rupiah fw-semibold"><?= formatRupiah($t['total_biaya']) ?></td>
                            <td><?= getStatusBadge($t['status']) ?></td>
                            <td>
                                <a href="transaksi-detail.php?id=<?= $t['id'] ?>"
                                   class="btn btn-sm btn-primary" title="Detail">
                                    <i class="bi bi-eye"></i>
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
