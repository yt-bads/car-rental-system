<?php
// includes/navbar-direktur.php
// Sidebar & Header Navigasi Direktur — Rental Mobil PT. Wildan Abadi Jaya

$current_page = basename($_SERVER['PHP_SELF'], '.php');

function isActiveDirektur(string $pages): string {
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
    <!-- ======= SIDEBAR DIREKTUR ======= -->
    <aside class="admin-sidebar" id="adminSidebar" style="width: 240px; background-color: var(--color-primary); min-height: 100vh; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100;">
        <!-- Brand / Logo -->
        <div class="admin-sidebar-brand" style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: center;">
            <a href="/rental-mobil/direktur/dashboard.php" class="text-decoration-none d-flex align-items-center gap-2 text-white">
                <img src="/rental-mobil/assets/img/logo.png" alt="Logo" height="34"
                     style="object-fit:contain;">
                <span class="fw-bold fs-6 tracking-wide">PT. Wildan Abadi</span>
            </a>
        </div>

        <!-- Menu Group Label -->
        <div style="padding: 1.5rem 1.5rem 0.5rem; font-size: 0.7rem; text-transform: uppercase;
                    letter-spacing: 0.1em; color: rgba(255,255,255,0.4); font-weight: 600;">
            Menu Pemantauan
        </div>

        <!-- Navigation Menu -->
        <nav class="admin-sidebar-menu">
            <a href="/rental-mobil/direktur/dashboard.php" class="sidebar-item<?= isActiveDirektur('dashboard') ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Executive Dashboard</span>
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
                    <a href="/rental-mobil/direktur/laporan.php?jenis=semua" 
                       class="sidebar-item py-2<?= $current_page === 'laporan' && ($_GET['jenis'] ?? 'semua') === 'semua' ? ' active' : '' ?>" style="font-size: 0.85rem; border-left: none;">
                        <i class="bi bi-circle small me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <span>Semua Laporan</span>
                    </a>
                    <a href="/rental-mobil/direktur/laporan.php?jenis=pendapatan" 
                       class="sidebar-item py-2<?= $current_page === 'laporan' && ($_GET['jenis'] ?? '') === 'pendapatan' ? ' active' : '' ?>" style="font-size: 0.85rem; border-left: none;">
                        <i class="bi bi-circle small me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <span>Laporan Pendapatan</span>
                    </a>
                    <a href="/rental-mobil/direktur/laporan.php?jenis=denda" 
                       class="sidebar-item py-2<?= $current_page === 'laporan' && ($_GET['jenis'] ?? '') === 'denda' ? ' active' : '' ?>" style="font-size: 0.85rem; border-left: none;">
                        <i class="bi bi-circle small me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <span>Laporan Denda</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Divider -->
        <div style="margin: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.08);"></div>

        <!-- Logout Link -->
        <nav style="padding-bottom:1rem;">
            <a href="/rental-mobil/auth/logout.php"
               class="sidebar-item text-warning"
               onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ======= MAIN CONTENT WRAPPER ======= -->
    <div class="admin-main" style="margin-left: 240px; background-color: var(--color-bg); min-height: 100vh; flex: 1;">
        <!-- Top Header Bar -->
        <header class="admin-header">
            <button class="btn btn-primary d-lg-none me-2" id="sidebarToggle" type="button" aria-label="Toggle Sidebar">
                <i class="bi bi-list"></i>
            </button>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 small no-print">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= date('d F Y') ?>
                </span>
                
                <div class="dropdown">
                    <a href="#" class="admin-user-profile text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['nama'] ?? 'Direktur') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
                        <li><a class="dropdown-item py-2" href="profil.php"><i class="bi bi-person-gear me-2 text-primary"></i>Edit Profil</a></li>
                        <li><hr class="dropdown-divider m-0"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Message Panel -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert alert-<?= $_SESSION['flash']['type'] ?> alert-dismissible fade show m-3 mb-0 shadow-sm" role="alert">
                <i class="bi bi-<?= $_SESSION['flash']['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
                <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
