<?php
// admin/data-mobil-hapus.php
// Proses Hapus Mobil — POST only — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data-mobil.php');
    exit;
}

$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    redirectWith('data-mobil.php', 'danger', 'ID mobil tidak valid.');
}

// Ambil data mobil
$stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ?");
$stmt->execute([$id]);
$mobil = $stmt->fetch();

if (!$mobil) {
    redirectWith('data-mobil.php', 'danger', 'Mobil tidak ditemukan.');
}

// Cek apakah masih ada pemesanan aktif
$cekAktif = $pdo->prepare("
    SELECT COUNT(*) FROM pemesanan
    WHERE mobil_id = ? AND status IN ('aktif','menunggu_pembayaran','menunggu_konfirmasi')
");
$cekAktif->execute([$id]);
if ($cekAktif->fetchColumn() > 0) {
    redirectWith('data-mobil.php', 'danger', 'Mobil tidak bisa dihapus karena masih memiliki pemesanan aktif.');
}

try {
    // Hapus record mobil
    $stmt = $pdo->prepare("DELETE FROM mobil WHERE id = ?");
    $stmt->execute([$id]);

    // Hapus foto fisik jika ada
    if ($mobil['foto']) {
        $fotoPath = __DIR__ . '/../assets/uploads/mobil/' . $mobil['foto'];
        if (file_exists($fotoPath)) {
            @unlink($fotoPath);
        }
    }

    redirectWith('data-mobil.php', 'success', 'Mobil "' . $mobil['nama_mobil'] . '" berhasil dihapus.');
} catch (PDOException $e) {
    error_log('[HapusMobil Error] ' . $e->getMessage());
    redirectWith('data-mobil.php', 'danger', 'Gagal menghapus mobil. Mungkin masih ada data terkait di sistem.');
}
