<?php
// customer/pembayaran-proses.php
// Skrip Proses Simpan Konfirmasi Pembayaran (POST Only) — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Guard Method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: status-pemesanan.php');
    exit;
}

// Tangkap dan Sanitasi Data Form
$pemesanan_id       = filter_var($_POST['pemesanan_id'] ?? 0, FILTER_VALIDATE_INT);
$metode_pembayaran  = sanitize($_POST['metode_pembayaran'] ?? '');
$file_bukti         = $_FILES['bukti_pembayaran'] ?? null;

// 1. Validasi Input Dasar Wajib
if (!$pemesanan_id || empty($metode_pembayaran)) {
    redirectWith('status-pemesanan.php', 'danger', 'Parameter pembayaran tidak lengkap.');
}

if (!$file_bukti || $file_bukti['error'] !== UPLOAD_ERR_OK) {
    redirectWith("pembayaran.php?pesan_id={$pemesanan_id}", 'danger', 'Wajib melampirkan berkas bukti pembayaran.');
}

// 2. Validasi Transaksi Pemesanan di Database (Kepemilikan & Status)
$stmt = $pdo->prepare("SELECT * FROM pemesanan WHERE id = ? AND customer_id = ?");
$stmt->execute([$pemesanan_id, $_SESSION['user_id']]);
$pemesanan = $stmt->fetch();

if (!$pemesanan) {
    redirectWith('status-pemesanan.php', 'danger', 'Transaksi tidak ditemukan atau Anda tidak memiliki akses.');
}

if ($pemesanan['status'] !== 'menunggu_pembayaran') {
    redirectWith('status-pemesanan.php', 'warning', 'Transaksi pemesanan tidak sedang menunggu konfirmasi pembayaran.');
}

// 3. Proses Unggah File ke Folder Bukti Bayar
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
$filename = uploadFile($file_bukti, 'bukti-bayar', $allowedExtensions);

if (!$filename) {
    redirectWith("pembayaran.php?pesan_id={$pemesanan_id}", 'danger', 'Gagal mengunggah bukti pembayaran. Pastikan format berkas JPG/PNG/PDF dan ukuran di bawah 2MB.');
}

// 4. Jalankan Transaksi Database
$pdo->beginTransaction();
try {
    // Simpan data bukti pembayaran baru ke tabel pembayaran
    $stmtBayar = $pdo->prepare("
        INSERT INTO pembayaran (pemesanan_id, jumlah_bayar, metode_pembayaran, bukti_pembayaran, status)
        VALUES (?, ?, ?, ?, 'menunggu')
    ");
    $stmtBayar->execute([
        $pemesanan_id,
        $pemesanan['total_biaya'],
        $metode_pembayaran,
        $filename
    ]);

    // Update status pemesanan menjadi menunggu_konfirmasi oleh admin
    $stmtUpdatePesan = $pdo->prepare("UPDATE pemesanan SET status = 'menunggu_konfirmasi' WHERE id = ?");
    $stmtUpdatePesan->execute([$pemesanan_id]);

    $pdo->commit();
    
    // Redirect ke halaman status pemesanan
    redirectWith('status-pemesanan.php', 'success', 'Bukti pembayaran berhasil diunggah! Mohon tunggu verifikasi oleh admin.');

} catch (PDOException $e) {
    $pdo->rollBack();
    // Hapus fisik file bukti pembayaran yang terlanjur terupload demi kebersihan folder
    $physicalPath = __DIR__ . '/../assets/uploads/bukti-bayar/' . $filename;
    if (file_exists($physicalPath)) {
        unlink($physicalPath);
    }
    
    error_log("[Payment Submission Error] " . $e->getMessage());
    redirectWith("pembayaran.php?pesan_id={$pemesanan_id}", 'danger', 'Gagal memproses konfirmasi pembayaran. Terjadi kesalahan pada basis data.');
}
