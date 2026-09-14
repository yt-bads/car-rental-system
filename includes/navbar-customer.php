<?php
// includes/navbar-customer.php
// Top Navigation Bar Customer — Rental Mobil PT. Wildan Abadi Jaya

// Tentukan halaman aktif berdasarkan file saat ini
$current_page = basename($_SERVER['PHP_SELF'], '.php');

function isActiveCustomer(string $pages): string {
    global $current_page;
    $list = explode(',', $pages);
    foreach ($list as $page) {
        if (trim($page) === $current_page) {
            return ' active fw-semibold';
        }
    }
    return '';
}
?>

<!-- ======= NAVBAR TOP CUSTOMER ======= -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm" style="background-color: var(--color-primary); z-index: 1050;">
    <div class="container">
        <!-- Brand / Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="/rental-mobil/customer/dashboard.php">
            <img src="/rental-mobil/assets/img/logo.png" alt="Logo" height="32" 
                 style="object-fit:contain;">
            <span class="fs-6 fw-bold tracking-wide">PT. Wildan Abadi Jaya</span>
        </a>

        <!-- Responsive Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCustomerContent" aria-controls="navbarCustomerContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarCustomerContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4 gap-1">
                <li class="nav-item">
                    <a class="nav-link<?= isActiveCustomer('dashboard') ?>" href="/rental-mobil/customer/dashboard.php">
                        <i class="bi bi-house-door me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isActiveCustomer('daftar-mobil,pesan') ?>" href="/rental-mobil/customer/daftar-mobil.php">
                        <i class="bi bi-car-front me-1"></i>Daftar Mobil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isActiveCustomer('status-pemesanan,pembayaran,detail-pesanan') ?>" href="/rental-mobil/customer/status-pemesanan.php">
                        <i class="bi bi-receipt me-1"></i>Status Pemesanan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= isActiveCustomer('riwayat,pengembalian') ?>" href="/rental-mobil/customer/riwayat.php">
                        <i class="bi bi-clock-history me-1"></i>Riwayat Rental
                    </a>
                </li>
            </ul>

            <!-- Profile Dropdown -->
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-2 rounded-3" 
                       href="#" id="customerProfileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span><?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="customerProfileDropdown">
                        <li>
                            <a class="dropdown-menu-item dropdown-item d-flex align-items-center gap-2" href="/rental-mobil/customer/edit-profil.php">
                                <i class="bi bi-person-gear"></i>Edit Profil
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-menu-item dropdown-item text-danger d-flex align-items-center gap-2" href="/rental-mobil/auth/logout.php"
                               onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
                                <i class="bi bi-box-arrow-right"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="customer-wrapper">
    <!-- Flash Message -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="container mt-4">
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
                <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
