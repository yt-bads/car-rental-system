<?php
// includes/auth_check.php
// Pengaman Sesi Global (Auth Guard) — Rental Mobil PT. Wildan Abadi Jaya

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cek apakah user sudah login (memiliki user_id)
if (!isset($_SESSION['user_id'])) {
    // Alihkan ke login (letak file login.php ada di tingkat root /auth/)
    // Karena semua file yang dilindungi berada di subfolder (admin/, customer/, direktur/),
    // maka relatif path "../auth/login.php" adalah tepat.
    header('Location: ../auth/login.php');
    exit;
}

// 2. Cek apakah variable $required_role didefinisikan untuk verifikasi hak akses
if (isset($required_role)) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        // Jika tidak memiliki akses peran yang sesuai, arahkan kembali ke login dengan flash message
        $_SESSION['flash'] = [
            'type' => 'danger',
            'msg' => 'Akses ditolak! Anda tidak memiliki wewenang untuk membuka halaman tersebut.'
        ];
        header('Location: ../auth/login.php');
        exit;
    }
}
