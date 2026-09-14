<?php
// customer/dashboard.php
// Beranda Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// 1. Cek apakah ada transaksi pemesanan aktif/berjalan milik customer ini
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_mobil, m.foto, m.tipe, m.harga_per_hari, m.plat_nomor
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.customer_id = ? AND p.status IN ('menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif')
    ORDER BY p.created_at DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$bookingAktif = $stmt->fetch();

// 2. Ambil 3 unit mobil yang berstatus tersedia DAN tidak sedang di-booking
$rekomendasiMobil = $pdo->query("
    SELECT m.*,
           (SELECT COUNT(*) FROM pemesanan p
            WHERE p.mobil_id = m.id
              AND p.status IN ('menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif')
           ) AS is_booked
    FROM mobil m
    WHERE m.status = 'tersedia'
    HAVING is_booked = 0
    ORDER BY m.id DESC
    LIMIT 3
")->fetchAll();

$page_title = "Beranda Pelanggan";
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';
?>

<div class="container py-4">

    <!-- ======= HERO SECTION ======= -->
    <div class="customer-hero text-center text-lg-start d-lg-flex align-items-center justify-content-between">
        <div class="mb-4 mb-lg-0">
            <h1 class="customer-hero-title">Temukan Mobil Impian Anda</h1>
            <p class="customer-hero-subtitle">Nikmati perjalanan Anda dengan armada mobil terbaik dari PT. Wildan Abadi Jaya. Layanan sewa aman, tepercaya, dan harga bersahabat.</p>
            <a href="daftar-mobil.php" class="btn btn-accent-custom px-4 py-2 fs-6">
                <i class="bi bi-car-front-fill me-2"></i>Mulai Sewa Mobil
            </a>
        </div>
        <div class="d-none d-lg-block">
            <img src="/rental-mobil/assets/img/logo.png" alt="Logo Hero" height="150" 
                 style="object-fit:contain; opacity: 0.85;">
        </div>
    </div>

    <!-- ======= DYNAMIC STATUS TRANSAKSI ======= -->
    <?php if ($bookingAktif): ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
                <div class="card-header bg-warning bg-opacity-10 py-3 border-0 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-warning-emphasis"><i class="bi bi-clock-history me-2"></i>Status Rental Anda</span>
                    <?= getStatusBadge($bookingAktif['status']) ?>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-2 col-sm-4 mb-3 mb-md-0 text-center">
                            <?php if ($bookingAktif['foto']): ?>
                                <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($bookingAktif['foto']) ?>" 
                                     alt="Foto Mobil" class="img-fluid rounded" style="max-height: 90px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 90px;">
                                    <i class="bi bi-car-front text-muted fs-2"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-7 col-sm-8 mb-3 mb-md-0">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($bookingAktif['nama_mobil']) ?></h5>
                            <p class="text-muted small mb-2">Plat Nomor: <span class="badge bg-secondary"><?= htmlspecialchars($bookingAktif['plat_nomor']) ?></span></p>
                            <div class="row g-2">
                                <div class="col-auto me-3">
                                    <span class="text-muted small d-block">Tanggal Sewa</span>
                                    <span class="fw-semibold text-dark"><?= date('d M Y', strtotime($bookingAktif['tanggal_sewa'])) ?></span>
                                </div>
                                <div class="col-auto">
                                    <span class="text-muted small d-block">Tanggal Kembali</span>
                                    <span class="fw-semibold text-dark"><?= date('d M Y', strtotime($bookingAktif['tanggal_kembali'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <span class="text-muted small d-block mb-1">Total Biaya</span>
                            <h4 class="fw-bold text-primary mb-3"><?= formatRupiah($bookingAktif['total_biaya']) ?></h4>
                            
                            <?php if ($bookingAktif['status'] === 'menunggu_pembayaran'): ?>
                                <a href="pembayaran.php?pesan_id=<?= $bookingAktif['id'] ?>" class="btn btn-warning w-100 fw-bold">
                                    <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                                </a>
                            <?php elseif ($bookingAktif['status'] === 'aktif'): ?>
                                <a href="pengembalian.php?pesan_id=<?= $bookingAktif['id'] ?>" class="btn btn-primary-custom w-100 fw-bold">
                                    <i class="bi bi-arrow-left-right me-2"></i>Kembalikan Mobil
                                </a>
                            <?php else: ?>
                                <a href="status-pemesanan.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-eye me-2"></i>Pantau Status
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======= REKOMENDASI MOBIL TERSEDIA ======= -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Rekomendasi Mobil Tersedia</h4>
            <p class="text-muted small mb-0">Armada siap pakai untuk menemani mobilitas Anda hari ini</p>
        </div>
        <a href="daftar-mobil.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Lihat Semua <i class="bi bi-chevron-right ms-1"></i>
        </a>
    </div>

    <div class="row g-4">
        <?php if (count($rekomendasiMobil) > 0): ?>
            <?php foreach ($rekomendasiMobil as $m): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 car-card">
                        <!-- Image Wrapper -->
                        <div class="car-card-img-wrapper">
                            <?php if ($m['foto']): ?>
                                <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($m['foto']) ?>" 
                                     class="car-card-img" alt="<?= htmlspecialchars($m['nama_mobil']) ?>">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                    <i class="bi bi-car-front text-muted fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <span class="car-badge bg-success text-white">Tersedia</span>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="car-card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-light text-primary fw-semibold"><?= htmlspecialchars($m['tipe']) ?></span>
                                    <span class="text-muted small"><i class="bi bi-people me-1"></i><?= htmlspecialchars($m['kapasitas']) ?> Kursi</span>
                                </div>
                                <h5 class="car-card-title"><?= htmlspecialchars($m['nama_mobil']) ?></h5>
                                <div class="text-muted small mb-3">
                                    <span class="car-meta-item"><i class="bi bi-palette me-1"></i><?= htmlspecialchars($m['warna']) ?></span>
                                    <span class="car-meta-item"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($m['tahun']) ?></span>
                                </div>
                            </div>
                            
                            <!-- Price & Action -->
                            <div class="car-price-tag align-items-center">
                                <div>
                                    <span class="text-muted small d-block">Harga Sewa</span>
                                    <span class="car-price-value"><?= formatRupiah($m['harga_per_hari']) ?> <small class="text-muted fw-normal" style="font-size:0.75rem;">/ Hari</small></span>
                                </div>
                                <a href="pesan.php?mobil_id=<?= $m['id'] ?>" class="btn btn-primary-custom">
                                    Pesan <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-emoji-frown text-muted" style="font-size: 3rem;"></i>
                </div>
                <h5 class="fw-semibold text-dark">Maaf, saat ini tidak ada mobil yang tersedia</h5>
                <p class="text-muted">Silakan hubungi admin atau periksa kembali halaman status pemesanan Anda.</p>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.customer-wrapper -->

<?php
require_once '../includes/footer.php';
?>
