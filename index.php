<?php
// index.php
// Entry Point Aplikasi — Rental Mobil PT. Wildan Abadi Jaya

session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'customer':
            header('Location: customer/dashboard.php');
            break;
        case 'direktur':
            header('Location: direktur/dashboard.php');
            break;
        default:
            // Jika role tidak dikenal, hapus session dan redirect ke login
            session_destroy();
            header('Location: auth/login.php');
            break;
    }
    exit;
}

// Redirect ke halaman login jika belum masuk
header('Location: auth/login.php');
exit;
