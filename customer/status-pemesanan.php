<?php
// customer/status-pemesanan.php
// Daftar Status Pemesanan Aktif Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil semua transaksi customer yang statusnya BUKAN selesai
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_mobil, m.foto, m.tipe, m.harga_per_hari, m.plat_nomor
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.customer_id = ? AND p.status != 'selesai'
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$transaksiList = $stmt->fetchAll();

$page_title = "Status Pemesanan";
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';
?>

<div class="container py-4">

    <!-- ======= BREADCRUMB & HEADER ======= -->
    <div class="row mb-4">
        <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Beranda</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Status Pemesanan</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2" style="color: var(--color-primary);"></i>Pemesanan Berjalan</h4>
                <p class="text-muted small mb-0">Pantau dan kelola transaksi pemesanan mobil aktif Anda</p>
            </div>
            <a href="riwayat.php" class="btn btn-outline-primary rounded-pill px-3 py-2 btn-sm fw-semibold">
                <i class="bi bi-archive me-1"></i>Lihat Riwayat Selesai
            </a>
        </div>
    </div>

    <!-- ======= LIST OF ORDERS ======= -->
    <div class="row">
        <div class="col-12">
            <?php if (count($transaksiList) > 0): ?>
                <?php foreach ($transaksiList as $t): ?>
                    <!-- Ambil detail pembayaran terakhir untuk cek alasan tolak (jika ada) -->
                    <?php
                    $stmtBayar = $pdo->prepare("
                        SELECT * FROM pembayaran 
                        WHERE pemesanan_id = ? 
                        ORDER BY id DESC 
                        LIMIT 1
                    ");
                    $stmtBayar->execute([$t['id']]);
                    $pembayaranLast = $stmtBayar->fetch();
                    ?>

                    <div class="order-card order-card--<?= htmlspecialchars($t['status']) ?>">
                        <div class="row align-items-center">
                            <!-- Detail Mobil (Foto & Nama) -->
                            <div class="col-lg-3 col-md-4 col-sm-12 text-center text-md-start mb-3 mb-md-0">
                                <div class="d-flex align-items-center flex-column flex-md-row gap-3">
                                    <?php if ($t['foto']): ?>
                                        <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($t['foto']) ?>" 
                                             alt="Mobil" class="img-fluid rounded shadow-sm" style="max-height: 90px; width: 130px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="height: 90px; width: 130px;">
                                            <i class="bi bi-car-front text-muted fs-2"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-center text-md-start">
                                        <span class="badge bg-light text-primary fw-semibold mb-1"><?= htmlspecialchars($t['tipe']) ?></span>
                                        <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($t['nama_mobil']) ?></h6>
                                        <span class="badge bg-secondary" style="font-size: 0.75rem;"><?= htmlspecialchars($t['plat_nomor']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Rental (Durasi & Tanggal) -->
                            <div class="col-lg-5 col-md-5 col-sm-12 mb-3 mb-md-0">
                                <div class="row text-center text-md-start">
                                    <div class="col-md-6 mb-2 mb-md-0">
                                        <span class="text-muted small d-block">Periode Sewa</span>
                                        <span class="fw-semibold text-dark small">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?= date('d M Y', strtotime($t['tanggal_sewa'])) ?> s/d <?= date('d M Y', strtotime($t['tanggal_kembali'])) ?>
                                        </span>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted small d-block">Durasi & Tarif</span>
                                        <span class="fw-semibold text-dark small">
                                            <i class="bi bi-hourglass-split me-1"></i>
                                            <?= htmlspecialchars($t['lama_sewa']) ?> Hari (<?= formatRupiah($t['harga_per_hari']) ?>/Hari)
                                        </span>
                                    </div>
                                </div>
                                <div class="text-muted small text-center text-md-start mt-2">
                                    <span class="d-inline-block bg-light px-2 py-1 rounded" style="font-size:0.75rem;">
                                        <strong>Kode Booking:</strong> <?= htmlspecialchars($t['kode_pesan']) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Total Bayar & Aksi -->
                            <div class="col-lg-4 col-md-3 col-sm-12 text-center text-md-end">
                                <div class="mb-2">
                                    <span class="text-muted small d-block">Total Tagihan</span>
                                    <h4 class="fw-bold text-primary mb-0"><?= formatRupiah($t['total_biaya']) ?></h4>
                                </div>
                                
                                <div class="mb-3">
                                    <?= getStatusBadge($t['status']) ?>
                                </div>

                                <!-- Panel Aksi Dinamis -->
                                <div class="d-grid gap-2 d-md-inline-flex justify-content-md-end">
                                    <?php if ($t['status'] === 'menunggu_pembayaran'): ?>
                                        <a href="pembayaran.php?pesan_id=<?= $t['id'] ?>" class="btn btn-warning btn-sm fw-bold">
                                            <i class="bi bi-credit-card me-1"></i>Bayar Sekarang
                                        </a>
                                    <?php elseif ($t['status'] === 'menunggu_konfirmasi'): ?>
                                        <button class="btn btn-outline-info btn-sm" disabled>
                                            <i class="bi bi-patch-check me-1"></i>Menunggu Verifikasi
                                        </button>
                                    <?php elseif ($t['status'] === 'aktif'): ?>
                                        <a href="pengembalian.php?pesan_id=<?= $t['id'] ?>" class="btn btn-primary-custom btn-sm fw-bold">
                                            <i class="bi bi-arrow-left-right me-1"></i>Kembalikan Mobil
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Tidak ada aksi</span>
                                    <?php endif; ?>
                                    <a href="detail-pesanan.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- TASK 6: Tampilkan Bukti Transfer (jika status menunggu_konfirmasi) -->
                        <?php if ($t['status'] === 'menunggu_konfirmasi' && $pembayaranLast && !empty($pembayaranLast['bukti_pembayaran'])): ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div>
                                        <span class="text-muted small fw-semibold d-block mb-1">
                                            <i class="bi bi-receipt-cutoff me-1"></i>Bukti Pembayaran Terunggah
                                        </span>
                                        <?php
                                        $extBukti = strtolower(pathinfo($pembayaranLast['bukti_pembayaran'], PATHINFO_EXTENSION));
                                        $buktiBayarUrl = '/rental-mobil/assets/uploads/bukti-bayar/' . htmlspecialchars($pembayaranLast['bukti_pembayaran']);
                                        ?>
                                        <?php if ($extBukti === 'pdf'): ?>
                                            <a href="<?= $buktiBayarUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-file-pdf me-1"></i>Lihat PDF
                                            </a>
                                        <?php else: ?>
                                            <img src="<?= $buktiBayarUrl ?>" 
                                                 alt="Bukti Bayar" 
                                                 class="rounded shadow-sm border" 
                                                 style="max-height: 80px; max-width: 120px; object-fit: cover; cursor: pointer;"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#modalBukti<?= $t['id'] ?>"
                                                 title="Klik untuk memperbesar">
                                            <!-- Modal Full-Size Image -->
                                            <div class="modal fade" id="modalBukti<?= $t['id'] ?>" tabindex="-1" aria-labelledby="modalBuktiLabel<?= $t['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content border-0 shadow">
                                                        <div class="modal-header border-0 bg-dark">
                                                            <h6 class="modal-title text-white" id="modalBuktiLabel<?= $t['id'] ?>">
                                                                <i class="bi bi-receipt me-2"></i>Bukti Pembayaran — <?= htmlspecialchars($t['kode_pesan']) ?>
                                                            </h6>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-0 text-center bg-dark">
                                                            <img src="<?= $buktiBayarUrl ?>" alt="Bukti Bayar Full" 
                                                                 class="img-fluid" style="max-height: 80vh; object-fit: contain;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i>Menunggu admin memverifikasi pembayaran Anda.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Detil Note Penolakan Admin -->
                        <?php if ($t['status'] === 'menunggu_pembayaran' && $pembayaranLast && $pembayaranLast['status'] === 'ditolak'): ?>
                            <div class="mt-3 p-3 bg-danger bg-opacity-10 border border-danger-subtle rounded text-danger small">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Bukti Pembayaran Ditolak Admin:</strong> "<?= htmlspecialchars($pembayaranLast['catatan_admin'] ?? 'Tidak ada catatan.') ?>"
                                <a href="pembayaran.php?pesan_id=<?= $t['id'] ?>" class="alert-link text-danger fw-bold ms-2">Unggah bukti baru &raquo;</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card border-0 shadow-sm py-5 text-center" style="border-radius: 12px;">
                    <div class="mb-3">
                        <i class="bi bi-journal-x text-muted" style="font-size: 3.5rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Tidak ada pemesanan berjalan</h5>
                    <p class="text-muted px-3">Saat ini Anda tidak memiliki transaksi sewa mobil yang aktif atau menunggu pembayaran.</p>
                    <div class="mt-2">
                        <a href="daftar-mobil.php" class="btn btn-primary-custom btn-sm">
                            <i class="bi bi-car-front me-1"></i>Pilih Armada Sewa
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.customer-wrapper -->

<?php
require_once '../includes/footer.php';
?>
