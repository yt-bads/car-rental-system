<?php
// customer/pesan-proses.php
// Skrip Proses Pemesanan Mobil (POST Only) — Rental Mobil PT. Wildan Abadi Jaya

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
    header('Location: daftar-mobil.php');
    exit;
}

// Tangkap dan Sanitasi Data Form
$mobil_id       = filter_var($_POST['mobil_id'] ?? 0, FILTER_VALIDATE_INT);
$tanggal_sewa   = sanitize($_POST['tanggal_sewa'] ?? '');
$tanggal_kembali = sanitize($_POST['tanggal_kembali'] ?? '');
$catatan        = sanitize($_POST['catatan'] ?? '');

// 1. Validasi Input Dasar Wajib
if (!$mobil_id || empty($tanggal_sewa) || empty($tanggal_kembali)) {
    redirectWith('daftar-mobil.php', 'danger', 'Data input pemesanan tidak lengkap.');
}

// 2. Validasi Logika Tanggal
$today = date('Y-m-d');
if ($tanggal_sewa < $today) {
    redirectWith("pesan.php?mobil_id={$mobil_id}", 'danger', 'Tanggal mulai sewa tidak boleh sebelum hari ini.');
}

if ($tanggal_kembali <= $tanggal_sewa) {
    redirectWith("pesan.php?mobil_id={$mobil_id}", 'danger', 'Tanggal pengembalian harus setelah tanggal sewa (minimal 1 hari).');
}

// 3. Validasi Keberadaan dan Ketersediaan Mobil di Database (Race Condition Protection)
$stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ? FOR UPDATE"); // Lock row untuk menghindari double-booking simultan
$stmt->execute([$mobil_id]);
$mobil = $stmt->fetch();

if (!$mobil) {
    redirectWith('daftar-mobil.php', 'danger', 'Mobil tidak ditemukan dalam sistem.');
}

if ($mobil['status'] !== 'tersedia') {
    redirectWith('daftar-mobil.php', 'danger', 'Maaf, mobil tersebut baru saja disewa oleh pelanggan lain.');
}

// Cek apakah mobil sudah memiliki pesanan aktif/menunggu (Double Booking Protection)
$stmtBooked = $pdo->prepare("SELECT COUNT(*) FROM pemesanan WHERE mobil_id = ? AND status IN ('menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif')");
$stmtBooked->execute([$mobil_id]);
if ($stmtBooked->fetchColumn() > 0) {
    redirectWith('daftar-mobil.php', 'danger', 'Maaf, mobil baru saja di-booking oleh orang lain dan menunggu pembayaran.');
}

// 4. Hitung Durasi Sewa dan Total Biaya
$dateSewa    = new DateTime($tanggal_sewa);
$dateKembali = new DateTime($tanggal_kembali);
$lama_sewa   = $dateKembali->diff($dateSewa)->days;

if ($lama_sewa <= 0) {
    redirectWith("pesan.php?mobil_id={$mobil_id}", 'danger', 'Tanggal pengembalian tidak valid.');
}

$total_biaya = $lama_sewa * (float)$mobil['harga_per_hari'];

// 5. Generate Parameter Transaksi Pemesanan
$kode_pesan  = generateKodePesan();
$customer_id = $_SESSION['user_id'];

// 6. Jalankan Transaksi Simpan Data Pemesanan
$pdo->beginTransaction();
try {
    $stmtInsert = $pdo->prepare("
        INSERT INTO pemesanan (kode_pesan, customer_id, mobil_id, tanggal_sewa, tanggal_kembali, lama_sewa, total_biaya, catatan, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu_pembayaran')
    ");
    $stmtInsert->execute([
        $kode_pesan,
        $customer_id,
        $mobil_id,
        $tanggal_sewa,
        $tanggal_kembali,
        $lama_sewa,
        $total_biaya,
        $catatan
    ]);
    
    $pesan_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    // Redirect ke halaman pembayaran
    redirectWith("pembayaran.php?pesan_id={$pesan_id}", 'success', 'Pemesanan berhasil dibuat! Silakan selesaikan pembayaran Anda.');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("[Booking Process Error] " . $e->getMessage());
    redirectWith("pesan.php?mobil_id={$mobil_id}", 'danger', 'Terjadi kesalahan sistem saat memproses pemesanan Anda.');
}
