<?php
// auth/logout.php
// Logika Keluar Sistem — Rental Mobil PT. Wildan Abadi Jaya

session_start();

// Hapus semua data sesi
session_unset();
session_destroy();

// Mulai sesi baru secara instan khusus untuk menyimpan flash message sukses logout
session_start();
$_SESSION['flash'] = [
    'type' => 'success',
    'msg' => 'Anda telah berhasil keluar dari sistem.'
];

// Alihkan kembali ke halaman login
header('Location: login.php');
exit;
