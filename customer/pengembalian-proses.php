<?php
// customer/pengembalian-proses.php
// Skrip Proses Simpan Pengembalian Mobil (POST Only) — Rental Mobil PT. Wildan Abadi Jaya

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
$pemesanan_id           = filter_var($_POST['pemesanan_id'] ?? 0, FILTER_VALIDATE_INT);
$tanggal_kembali_aktual = sanitize($_POST['tanggal_kembali_aktual'] ?? '');
$kondisi_mobil          = sanitize($_POST['kondisi_mobil'] ?? 'baik');
$keterangan             = sanitize($_POST['keterangan'] ?? '');

// 1. Validasi Input Dasar Wajib
if (!$pemesanan_id || empty($tanggal_kembali_aktual)) {
    redirectWith('status-pemesanan.php', 'danger', 'Parameter pengembalian tidak lengkap.');
}

$allowedKondisi = ['baik', 'rusak_ringan', 'rusak_berat'];
if (!in_array($kondisi_mobil, $allowedKondisi)) {
    redirectWith('status-pemesanan.php', 'danger', 'Kondisi mobil yang dipilih tidak valid.');
}

// 2. Validasi Transaksi Pemesanan di Database (Kepemilikan, Status Aktif)
$stmt = $pdo->prepare("
    SELECT p.*, m.harga_per_hari, m.id AS mobil_db_id
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.id = ? AND p.customer_id = ?
");
$stmt->execute([$pemesanan_id, $_SESSION['user_id']]);
$pemesanan = $stmt->fetch();

if (!$pemesanan) {
    redirectWith('status-pemesanan.php', 'danger', 'Transaksi tidak ditemukan atau Anda tidak memiliki akses.');
}

if ($pemesanan['status'] !== 'aktif') {
    redirectWith('status-pemesanan.php', 'warning', 'Sewa mobil ini tidak sedang dalam masa peminjaman aktif.');
}

if ($tanggal_kembali_aktual < $pemesanan['tanggal_sewa']) {
    redirectWith("pengembalian.php?pesan_id={$pemesanan_id}", 'danger', 'Tanggal kembali aktual tidak boleh sebelum tanggal mulai sewa.');
}

// 3. Hitung Denda Keterlambatan dan Kerusakan
// Denda telat dihitung dari tanggal rencana kembali s/d tanggal kembali aktual
$dendaTelat = hitungDenda($pemesanan['tanggal_kembali'], $tanggal_kembali_aktual, (float)$pemesanan['harga_per_hari']);

// Denda kondisi kerusakan fisik mobil
$dendaKerusakan = 0.00;
if ($kondisi_mobil === 'rusak_ringan') {
    $dendaKerusakan = 250000.00;
} elseif ($kondisi_mobil === 'rusak_berat') {
    $dendaKerusakan = 1000000.00;
}

$totalDenda = $dendaTelat + $dendaKerusakan;

// 4. Jalankan Transaksi Database untuk Menyimpan Pengembalian
$pdo->beginTransaction();
try {
    // A. INSERT data pengembalian
    $stmtInsert = $pdo->prepare("
        INSERT INTO pengembalian (pemesanan_id, tanggal_kembali_aktual, kondisi_mobil, denda, keterangan, processed_by)
        VALUES (?, ?, ?, ?, ?, NULL)
    ");
    $stmtInsert->execute([
        $pemesanan_id,
        $tanggal_kembali_aktual,
        $kondisi_mobil,
        $totalDenda,
        $keterangan
    ]);

    // B. UPDATE status pemesanan menjadi selesai
    $stmtUpdatePesan = $pdo->prepare("UPDATE pemesanan SET status = 'selesai' WHERE id = ?");
    $stmtUpdatePesan->execute([$pemesanan_id]);

    // C. UPDATE status mobil menjadi tersedia kembali
    $stmtUpdateMobil = $pdo->prepare("UPDATE mobil SET status = 'tersedia' WHERE id = ?");
    $stmtUpdateMobil->execute([$pemesanan['mobil_db_id']]);

    $pdo->commit();

    // Berhasil, redirect ke riwayat rental
    $msgSuccess = 'Pengembalian mobil berhasil diproses!';
    if ($totalDenda > 0) {
        $msgSuccess .= ' Anda dikenakan denda sebesar ' . formatRupiah($totalDenda) . ' (Terlambat: ' . formatRupiah($dendaTelat) . ', Kerusakan: ' . formatRupiah($dendaKerusakan) . ').';
    } else {
        $msgSuccess .= ' Mobil dikembalikan tepat waktu dengan kondisi baik.';
    }
    
    redirectWith('riwayat.php', 'success', $msgSuccess);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("[Return Process Error] " . $e->getMessage());
    redirectWith("pengembalian.php?pesan_id={$pemesanan_id}", 'danger', 'Gagal memproses pengembalian mobil. Terjadi kesalahan pada basis data.');
}
