<?php
// customer/daftar-mobil.php
// Daftar Mobil Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil semua tipe mobil unik untuk filter dropdown
$tipeList = $pdo->query("SELECT DISTINCT tipe FROM mobil ORDER BY tipe ASC")->fetchAll(PDO::FETCH_COLUMN);

// Proses Filter & Pencarian
$search = sanitize($_GET['search'] ?? '');
$tipe   = sanitize($_GET['tipe'] ?? '');

$query = "SELECT m.*, (SELECT COUNT(*) FROM pemesanan p WHERE p.mobil_id = m.id AND p.status IN ('menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif')) AS is_booked FROM mobil m WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (m.nama_mobil LIKE ? OR m.plat_nomor LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($tipe !== '') {
    $query .= " AND m.tipe = ?";
    $params[] = $tipe;
}

// Urutkan berdasarkan mobil tersedia dan tidak sedang di-booking dahulu, kemudian nama mobil
$query .= " ORDER BY CASE WHEN m.status = 'tersedia' AND (SELECT COUNT(*) FROM pemesanan p WHERE p.mobil_id = m.id AND p.status IN ('menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif')) = 0 THEN 1 ELSE 2 END ASC, m.nama_mobil ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$mobilList = $stmt->fetchAll();

$page_title = "Daftar Mobil";
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';
?>

<div class="container py-4">

    <!-- ======= BREADCRUMB & HEADER ======= -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Daftar Mobil</li>
                </ol>
            </nav>
            <h4 class="fw-bold text-dark"><i class="bi bi-car-front-fill me-2" style="color: var(--color-primary);"></i>Armada Rental Kami</h4>
            <p class="text-muted small">Pilih armada terbaik yang sesuai dengan kebutuhan perjalanan Anda</p>
        </div>
    </div>

    <!-- ======= FILTER PANEL ======= -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="daftar-mobil.php" method="GET" class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" name="search" 
                               value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama mobil atau plat nomor...">
                    </div>
                </div>

                <!-- Tipe Mobil Filter -->
                <div class="col-md-4">
                    <select class="form-select" name="tipe">
                        <option value="">-- Semua Tipe Mobil --</option>
                        <?php foreach ($tipeList as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>" <?= $tipe === $t ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary-custom flex-grow-1">
                        <i class="bi bi-funnel me-1"></i>Filter
                    </button>
                    <?php if ($search !== '' || $tipe !== ''): ?>
                        <a href="daftar-mobil.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ======= CAR GRID ======= -->
    <div class="row g-4">
        <?php if (count($mobilList) > 0): ?>
            <?php foreach ($mobilList as $m): ?>
                <div class="col-lg-4 col-md-6 col-sm-12">
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
                            
                            <!-- Dynamic Status Badge -->
                            <?php
                            $badgeColor = 'bg-secondary';
                            $displayStatus = $m['status'];
                            if ($m['is_booked'] > 0) {
                                $badgeColor = 'bg-warning';
                                $displayStatus = 'disewa';
                            } elseif ($m['status'] === 'tersedia') {
                                $badgeColor = 'bg-success';
                            } elseif ($m['status'] === 'disewa') {
                                $badgeColor = 'bg-warning';
                            } elseif ($m['status'] === 'servis') {
                                $badgeColor = 'bg-danger';
                            }
                            ?>
                            <span class="car-badge <?= $badgeColor ?> text-white text-uppercase">
                                <?= htmlspecialchars($displayStatus) ?>
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="car-card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-light text-primary fw-semibold"><?= htmlspecialchars($m['tipe']) ?></span>
                                    <span class="text-muted small"><i class="bi bi-people me-1"></i><?= htmlspecialchars($m['kapasitas']) ?> Penumpang</span>
                                </div>
                                <h5 class="car-card-title"><?= htmlspecialchars($m['nama_mobil']) ?></h5>
                                <div class="text-muted small mb-3">
                                    <span class="car-meta-item"><i class="bi bi-palette me-1"></i><?= htmlspecialchars($m['warna']) ?></span>
                                    <span class="car-meta-item"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($m['tahun']) ?></span>
                                </div>
                                <p class="text-muted small text-truncate-2" style="font-size:0.8rem; min-height: 2.4rem;">
                                    <?= htmlspecialchars($m['deskripsi'] ?? 'Tidak ada deskripsi tambahan untuk mobil ini.') ?>
                                </p>
                            </div>

                            <!-- Price & Button -->
                            <div class="car-price-tag align-items-center mt-3">
                                <div>
                                    <span class="text-muted small d-block">Harga Sewa</span>
                                    <span class="car-price-value text-primary"><?= formatRupiah($m['harga_per_hari']) ?> <small class="text-muted fw-normal" style="font-size:0.75rem;">/ Hari</small></span>
                                </div>
                                
                                <?php if ($m['status'] === 'tersedia' && $m['is_booked'] == 0): ?>
                                    <a href="pesan.php?mobil_id=<?= $m['id'] ?>" class="btn btn-primary-custom">
                                        Pesan Sekarang
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        Tidak Tersedia
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="mb-3">
                    <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                </div>
                <h5 class="fw-semibold text-dark">Mobil tidak ditemukan</h5>
                <p class="text-muted">Cobalah untuk mengubah kata kunci pencarian Anda atau mengatur ulang filter tipe.</p>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.customer-wrapper -->

<?php
require_once '../includes/footer.php';
?>
