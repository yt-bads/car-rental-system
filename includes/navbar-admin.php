<?php
// includes/navbar-admin.php
// Sidebar Navigasi Admin — Rental Mobil PT. Wildan Abadi Jaya

// Tentukan halaman aktif berdasarkan file saat ini
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

function isActive(string $pages): string {
    global $current_page;
    $list = explode(',', $pages);
    foreach ($list as $page) {
        if (trim($page) === $current_page) {
            return ' active';
        }
    }
    return '';
}
?>

<div class="admin-wrapper">
    <!-- ======= SIDEBAR ======= -->
    <aside class="admin-sidebar" id="adminSidebar">
        <!-- Brand / Logo -->
        <div class="admin-sidebar-brand">
            <a href="/rental-mobil/admin/dashboard.php">
                <img src="/rental-mobil/assets/img/logo.png" alt="Logo" height="36"
                     style="object-fit:contain;">
                <span>PT. Wildan Abadi</span>
            </a>
        </div>

        <!-- Menu Label -->
        <div style="padding: 1.25rem 1.5rem 0.5rem; font-size: 0.7rem; text-transform: uppercase;
                    letter-spacing: 0.1em; color: rgba(255,255,255,0.4); font-weight: 600;">
            Menu Utama
        </div>

        <!-- Navigation Items -->
        <nav class="admin-sidebar-menu">
            <a href="/rental-mobil/admin/dashboard.php"
               class="sidebar-item<?= isActive('dashboard') ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <a href="/rental-mobil/admin/data-mobil.php"
               class="sidebar-item<?= isActive('data-mobil,data-mobil-tambah,data-mobil-edit') ?>">
                <i class="bi bi-car-front"></i>
                <span>Data Mobil</span>
            </a>

            <a href="/rental-mobil/admin/data-customer.php"
               class="sidebar-item<?= isActive('data-customer,data-customer-edit') ?>">
                <i class="bi bi-people"></i>
                <span>Data Customer</span>
            </a>

            <a href="/rental-mobil/admin/transaksi.php"
               class="sidebar-item<?= isActive('transaksi,transaksi-detail') ?>">
                <i class="bi bi-receipt"></i>
                <span>Transaksi Rental</span>
            </a>

            <a href="/rental-mobil/admin/pembayaran.php"
               class="sidebar-item<?= isActive('pembayaran') ?>">
                <i class="bi bi-credit-card"></i>
                <span>Verifikasi Pembayaran</span>
            </a>

            <a class="sidebar-item<?= $current_page === 'laporan' ? ' active' : '' ?>" 
               data-bs-toggle="collapse" href="#collapseLaporan" role="button" 
               aria-expanded="<?= $current_page === 'laporan' ? 'true' : 'false' ?>" aria-controls="collapseLaporan">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Laporan Rental</span>
                <i class="bi bi-chevron-down ms-auto" style="font-size: 0.75rem;"></i>
            </a>
            <div class="collapse<?= $current_page === 'laporan' ? ' show' : '' ?>" id="collapseLaporan">
                <div class="bg-black bg-opacity-25" style="padding-left: 1.5rem;">
                    <a href="/rental-mobil/admin/laporan.php?jenis=semua" 
                       class="sidebar-item py-2<?= $current_page === 'laporan' && ($_GET['jenis'] ?? 'semua') === 'semua' ? ' active' : '' ?>" style="font-size: 0.85rem; border-left: none;">
                        <i class="bi bi-circle small me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <span>Semua Laporan</span>
                    </a>
                    <a href="/rental-mobil/admin/laporan.php?jenis=pendapatan" 
                       class="sidebar-item py-2<?= $current_page === 'laporan' && ($_GET['jenis'] ?? '') === 'pendapatan' ? ' active' : '' ?>" style="font-size: 0.85rem; border-left: none;">
                        <i class="bi bi-circle small me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <span>Laporan Pendapatan</span>
                    </a>
                    <a href="/rental-mobil/admin/laporan.php?jenis=denda" 
                       class="sidebar-item py-2<?= $current_page === 'laporan' && ($_GET['jenis'] ?? '') === 'denda' ? ' active' : '' ?>" style="font-size: 0.85rem; border-left: none;">
                        <i class="bi bi-circle small me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <span>Laporan Denda</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Divider -->
        <div style="margin: 0.5rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.08);"></div>

        <!-- Logout -->
        <nav style="padding-bottom: 1rem;">
            <a href="/rental-mobil/auth/logout.php"
               class="sidebar-item"
               onclick="return confirm('Yakin ingin keluar?')">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ======= MAIN AREA ======= -->
    <div class="admin-main">
        <!-- Top Header Bar -->
        <header class="admin-header">
            <button class="btn btn-primary d-lg-none me-2" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50" style="font-size: var(--font-sm);">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d F Y') ?>
                </span>
                <div class="dropdown">
                    <a href="#" class="admin-user-profile text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
                        <li><a class="dropdown-item py-2" href="profil.php"><i class="bi bi-person-gear me-2 text-primary"></i>Edit Profil</a></li>
                        <li><hr class="dropdown-divider m-0"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Message -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show m-3 mb-0"
                 role="alert">
                <i class="bi bi-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
                <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
