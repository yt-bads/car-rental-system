<?php
// customer/pembayaran.php
// Halaman Konfirmasi Pembayaran Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil parameter pesan_id
$pesan_id = filter_var($_GET['pesan_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$pesan_id) {
    redirectWith('status-pemesanan.php', 'danger', 'Transaksi pemesanan tidak valid.');
}

// Ambil data pemesanan & detail mobil
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_mobil, m.tipe, m.harga_per_hari, m.foto, m.plat_nomor
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.id = ? AND p.customer_id = ?
");
$stmt->execute([$pesan_id, $_SESSION['user_id']]);
$pemesanan = $stmt->fetch();

// Validasi keberadaan transaksi
if (!$pemesanan) {
    redirectWith('status-pemesanan.php', 'danger', 'Transaksi pemesanan tidak ditemukan atau bukan milik Anda.');
}

// Validasi status pemesanan
if ($pemesanan['status'] !== 'menunggu_pembayaran') {
    redirectWith('status-pemesanan.php', 'warning', 'Transaksi pemesanan tidak sedang menunggu pembayaran.');
}

// Cek riwayat pembayaran terakhir untuk mengecek jika pernah ditolak admin
$stmtBayar = $pdo->prepare("
    SELECT * FROM pembayaran
    WHERE pemesanan_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmtBayar->execute([$pesan_id]);
$pembayaranTerakhir = $stmtBayar->fetch();

$metodeList = [
    'transfer_bca'     => 'Transfer Bank BCA',
    'transfer_bri'     => 'Transfer Bank BRI',
    'transfer_bni'     => 'Transfer Bank BNI',
    'transfer_mandiri' => 'Transfer Bank Mandiri',
    'dana'             => 'E-Wallet DANA',
    'gopay'            => 'E-Wallet GoPay',
    'ovo'              => 'E-Wallet OVO'
];

$page_title = "Pembayaran " . htmlspecialchars($pemesanan['kode_pesan']);
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';
?>

<div class="container py-4">

    <!-- ======= BREADCRUMB ======= -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="status-pemesanan.php" class="text-decoration-none">Status Pemesanan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Unggah Bukti Pembayaran</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Detail Tagihan -->
        <div class="col-lg-6">
            <!-- Info Rejection dari Admin -->
            <?php if ($pembayaranTerakhir && $pembayaranTerakhir['status'] === 'ditolak'): ?>
                <div class="alert alert-danger shadow-sm mb-4" role="alert" style="border-radius: 10px;">
                    <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-2"></i>Bukti Pembayaran Sebelumnya Ditolak</h6>
                    <p class="small mb-2">Mohon maaf, admin menolak konfirmasi pembayaran Anda sebelumnya dengan catatan:</p>
                    <div class="bg-white bg-opacity-75 p-2 rounded text-danger fw-semibold small">
                        "<?= htmlspecialchars($pembayaranTerakhir['catatan_admin'] ?? 'Tidak ada catatan khusus dari admin.') ?>"
                    </div>
                    <p class="small mt-2 mb-0">Silakan lakukan transfer ulang atau unggah bukti pembayaran yang valid di bawah ini.</p>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Rincian Tagihan Sewa</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if ($pemesanan['foto']): ?>
                            <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($pemesanan['foto']) ?>" 
                                 alt="Mobil" class="img-fluid rounded" style="max-height: 80px; width: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 80px; width: 120px;">
                                <i class="bi bi-car-front text-muted fs-3"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($pemesanan['nama_mobil']) ?></h6>
                            <span class="badge bg-light text-primary border border-primary-subtle"><?= htmlspecialchars($pemesanan['tipe']) ?></span>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1"><?= htmlspecialchars($pemesanan['plat_nomor']) ?></span>
                        </div>
                    </div>

                    <table class="table table-borderless small mb-0">
                        <tr>
                            <td class="text-muted ps-0 py-1" width="150">Kode Pesanan</td>
                            <td class="fw-semibold text-dark py-1">: <?= htmlspecialchars($pemesanan['kode_pesan']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Tanggal Mulai Sewa</td>
                            <td class="fw-semibold text-dark py-1">: <?= date('d F Y', strtotime($pemesanan['tanggal_sewa'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Tanggal Selesai Sewa</td>
                            <td class="fw-semibold text-dark py-1">: <?= date('d F Y', strtotime($pemesanan['tanggal_kembali'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Durasi Sewa</td>
                            <td class="fw-semibold text-dark py-1">: <?= htmlspecialchars($pemesanan['lama_sewa']) ?> Hari</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Harga Sewa / Hari</td>
                            <td class="fw-semibold text-dark py-1">: <?= formatRupiah($pemesanan['harga_per_hari']) ?></td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted ps-0 pt-3 pb-1 fs-6">Total Tagihan</td>
                            <td class="fw-bold text-primary pt-3 pb-1 fs-5">: <?= formatRupiah($pemesanan['total_biaya']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panduan Transfer & Form -->
        <div class="col-lg-6">
            <!-- Panduan Transfer -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Panduan Pembayaran</h6>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">Silakan lakukan pembayaran sejumlah total tagihan ke salah satu rekening/e-wallet PT. Wildan Abadi Jaya berikut:</p>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="fw-bold d-block text-dark small">Bank BCA</span>
                                <span class="fs-6 text-primary fw-bold d-block my-1">123-4567-890</span>
                                <span class="text-muted" style="font-size:0.7rem;">a.n. PT. Wildan Abadi Jaya</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="fw-bold d-block text-dark small">Bank Mandiri</span>
                                <span class="fs-6 text-primary fw-bold d-block my-1">098-7654-321</span>
                                <span class="text-muted" style="font-size:0.7rem;">a.n. PT. Wildan Abadi Jaya</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="fw-bold d-block text-dark small">E-Wallet DANA</span>
                                <span class="fs-6 text-primary fw-bold d-block my-1">0812-3456-7890</span>
                                <span class="text-muted" style="font-size:0.7rem;">a.n. PT. Wildan Abadi Jaya</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 bg-light">
                                <span class="fw-bold d-block text-dark small">E-Wallet GoPay / OVO</span>
                                <span class="fs-6 text-primary fw-bold d-block my-1">0812-3456-7890</span>
                                <span class="text-muted" style="font-size:0.7rem;">a.n. PT. Wildan Abadi Jaya</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Upload Bukti -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-primary text-white py-3 border-0" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h6 class="fw-bold mb-0"><i class="bi bi-cloud-arrow-up me-2"></i>Konfirmasi Pembayaran</h6>
                </div>
                <div class="card-body p-4">
                    <form action="pembayaran-proses.php" method="POST" enctype="multipart/form-data">
                        <!-- Hidden Input ID Pemesanan -->
                        <input type="hidden" name="pemesanan_id" value="<?= $pemesanan['id'] ?>">

                        <!-- Jumlah Bayar (Prefilled & Readonly) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">Jumlah yang Harus Dibayar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-dark fw-semibold">Rp</span>
                                <input type="text" class="form-control fw-bold text-primary bg-light" 
                                       value="<?= number_format($pemesanan['total_biaya'], 0, ',', '.') ?>" readonly>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="mb-3">
                            <label for="metode_pembayaran" class="form-label fw-bold text-dark">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select id="metode_pembayaran" class="form-select" name="metode_pembayaran" required>
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                <?php foreach ($metodeList as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Bukti Pembayaran File -->
                        <div class="mb-4">
                            <label for="bukti_pembayaran" class="form-label fw-bold text-dark">Unggah Bukti Pembayaran <span class="text-danger">*</span></label>
                            <input type="file" id="bukti_pembayaran" class="form-control" name="bukti_pembayaran" 
                                   accept="image/jpeg,image/png,application/pdf" required>
                            <div class="form-text small mt-1 text-muted">
                                Format berkas: JPG, JPEG, PNG, atau PDF. Ukuran berkas maksimal 2MB.
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom flex-grow-1 fw-bold py-2.5">
                                <i class="bi bi-send-check me-2"></i>Kirim Bukti Pembayaran
                            </button>
                            <a href="status-pemesanan.php" class="btn btn-outline-secondary py-2.5">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.customer-wrapper -->

<?php
require_once '../includes/footer.php';
?>
