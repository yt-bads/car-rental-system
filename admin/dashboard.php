<?php
// admin/dashboard.php
// Dashboard Admin — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// ===== AMBIL DATA SUMMARY =====
$summary = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status IN ('menunggu_pembayaran','menunggu_konfirmasi') THEN 1 ELSE 0 END) AS proses,
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) AS aktif,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
        SUM(CASE WHEN status = 'selesai' THEN total_biaya ELSE 0 END) AS total_pendapatan
    FROM pemesanan
")->fetch();

// Total mobil & status
$totalMobil    = (int)$pdo->query("SELECT COUNT(*) FROM mobil")->fetchColumn();
$mobilTersedia = (int)$pdo->query("SELECT COUNT(*) FROM mobil WHERE status='tersedia'")->fetchColumn();
$mobilDisewa   = (int)$pdo->query("SELECT COUNT(*) FROM mobil WHERE status='disewa'")->fetchColumn();

// Total customer terdaftar
$totalCustomer = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

// Pembayaran menunggu verifikasi
$bayarMenunggu = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status='menunggu'")->fetchColumn();

// 5 Transaksi terbaru
$recentTransaksi = $pdo->query("
    SELECT p.kode_pesan, p.tanggal_sewa, p.tanggal_kembali, p.total_biaya, p.status,
           u.nama_lengkap, m.nama_mobil
    FROM pemesanan p
    JOIN users u ON p.customer_id = u.id
    JOIN mobil m ON p.mobil_id = m.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

$page_title  = 'Dashboard Admin';
$extra_css   = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<!-- ======= CONTENT ======= -->
<div class="admin-content">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
            </h4>
            <p class="text-muted small mb-0">
                Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?> — Ringkasan operasional hari ini
            </p>
        </div>
    </div>

    <!-- ===== STAT CARDS ROW 1 — Transaksi ===== -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card-stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Total Transaksi</div>
                        <div class="card-stat-value"><?= number_format($summary['total'] ?? 0) ?></div>
                        <a href="transaksi.php" class="card-stat-link">
                            Lihat Semua <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card-stat card-stat--warning">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Proses</div>
                        <div class="card-stat-value"><?= number_format($summary['proses'] ?? 0) ?></div>
                        <a href="transaksi.php" class="card-stat-link">
                            Lihat Detail <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card-stat" style="background-image: linear-gradient(135deg, #0288d1, #039be5);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Aktif Disewa</div>
                        <div class="card-stat-value"><?= number_format($summary['aktif'] ?? 0) ?></div>
                        <a href="transaksi.php" class="card-stat-link">
                            Lihat Detail <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-car-front"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card-stat card-stat--success">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Selesai</div>
                        <div class="card-stat-value"><?= number_format($summary['selesai'] ?? 0) ?></div>
                        <a href="laporan.php" class="card-stat-link">
                            Lihat Laporan <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== STAT CARDS ROW 2 — Pendapatan & Operasional ===== -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px; height:50px; border-radius:12px;
                                background:rgba(26,35,126,0.1); display:flex;
                                align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="bi bi-currency-dollar" style="font-size:1.5rem; color:var(--color-primary);"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                            Total Pendapatan
                        </div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--color-primary);">
                            <?= formatRupiah($summary['total_pendapatan'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px; height:50px; border-radius:12px;
                                background:rgba(56,142,60,0.1); display:flex;
                                align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="bi bi-car-front" style="font-size:1.5rem; color:var(--color-success);"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                            Mobil Tersedia
                        </div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--color-success);">
                            <?= $mobilTersedia ?> / <?= $totalMobil ?> unit
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px; height:50px; border-radius:12px;
                                background:rgba(245,124,0,0.1); display:flex;
                                align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="bi bi-clock-history" style="font-size:1.5rem; color:var(--color-warning);"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                            Pembayaran Pending
                        </div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--color-warning);">
                            <?= $bayarMenunggu ?> pembayaran
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:50px; height:50px; border-radius:12px;
                                background:rgba(2,136,209,0.1); display:flex;
                                align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="bi bi-people" style="font-size:1.5rem; color:var(--color-info);"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                            Total Customer
                        </div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--color-info);">
                            <?= $totalCustomer ?> akun
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== RECENT TRANSACTIONS ===== -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold">
                        <i class="bi bi-clock-history me-2"></i>Transaksi Terbaru
                    </span>
                    <a href="transaksi.php" class="btn btn-sm btn-warning">
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentTransaksi)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2.5rem; opacity:0.3;"></i>
                            <p class="mt-2 mb-0">Belum ada transaksi</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Kode Pesan</th>
                                    <th>Customer</th>
                                    <th>Mobil</th>
                                    <th>Tgl Sewa</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransaksi as $t): ?>
                                <tr>
                                    <td>
                                        <a href="transaksi-detail.php?id=<?= $t['kode_pesan'] ?>"
                                           class="fw-semibold text-decoration-none"
                                           style="color:var(--color-primary);">
                                            <?= htmlspecialchars($t['kode_pesan']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($t['nama_lengkap']) ?></td>
                                    <td><?= htmlspecialchars($t['nama_mobil']) ?></td>
                                    <td><?= date('d M Y', strtotime($t['tanggal_sewa'])) ?></td>
                                    <td class="text-rupiah"><?= formatRupiah($t['total_biaya']) ?></td>
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

</div><!-- /.admin-content -->

<?php
require_once '../includes/footer.php';
?>
