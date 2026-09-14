<?php
// customer/riwayat.php
// Riwayat Transaksi Selesai Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil riwayat pemesanan yang statusnya selesai
$stmt = $pdo->prepare("
    SELECT p.*, m.nama_mobil, m.foto, m.tipe, m.harga_per_hari, m.plat_nomor,
           k.tanggal_kembali_aktual, k.kondisi_mobil, k.denda, k.keterangan AS ket_kembali
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    LEFT JOIN pengembalian k ON k.pemesanan_id = p.id
    WHERE p.customer_id = ? AND p.status IN ('selesai', 'dibatalkan')
    ORDER BY p.tanggal_sewa DESC
");
$stmt->execute([$_SESSION['user_id']]);
$riwayatList = $stmt->fetchAll();

$page_title = "Riwayat Rental";
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
                        <li class="breadcrumb-item"><a href="status-pemesanan.php" class="text-decoration-none">Status Pemesanan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Riwayat Rental</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history me-2" style="color: var(--color-primary);"></i>Riwayat Rental Selesai</h4>
                <p class="text-muted small mb-0">Arsip seluruh transaksi rental mobil Anda yang telah terselesaikan</p>
            </div>
            <a href="status-pemesanan.php" class="btn btn-primary-custom btn-sm fw-semibold">
                <i class="bi bi-eye me-1"></i>Lihat Transaksi Aktif
            </a>
        </div>
    </div>

    <!-- ======= HISTORY TABLE / LIST ======= -->
    <div class="row">
        <div class="col-12">
            <?php if (count($riwayatList) > 0): ?>
                <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
                            <thead class="table-light text-secondary small" style="background-color: #f8f9fa;">
                                <tr>
                                    <th class="ps-4" width="150">Kode & Tanggal</th>
                                    <th>Mobil & Tipe</th>
                                    <th>Periode Rencana</th>
                                    <th>Tanggal Pengembalian</th>
                                    <th>Kondisi Mobil</th>
                                    <th class="text-end">Biaya Sewa</th>
                                    <th class="text-end">Denda</th>
                                    <th class="text-end">Total Pembayaran</th>
                                    <th class="text-center pe-4" width="100">Status</th>
                                    <th class="text-center" width="90">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="text-dark small">
                                <?php foreach ($riwayatList as $r): ?>
                                    <tr>
                                        <!-- Kode Booking & Tanggal Booking -->
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($r['kode_pesan']) ?></span>
                                            <span class="text-muted" style="font-size:0.75rem;"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                                        </td>

                                        <!-- Detail Mobil -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($r['foto']): ?>
                                                    <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($r['foto']) ?>" 
                                                         alt="Foto" class="rounded" style="height: 40px; width: 60px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="height: 40px; width: 60px;">
                                                        <i class="bi bi-car-front text-muted" style="font-size: 1.2rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="fw-semibold d-block"><?= htmlspecialchars($r['nama_mobil']) ?></span>
                                                    <span class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($r['plat_nomor']) ?></span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Periode Rencana -->
                                        <td>
                                            <span class="d-block"><?= date('d M Y', strtotime($r['tanggal_sewa'])) ?> s/d</span>
                                            <span class="d-block"><?= date('d M Y', strtotime($r['tanggal_kembali'])) ?></span>
                                            <span class="text-muted" style="font-size:0.75rem;">(<?= htmlspecialchars($r['lama_sewa']) ?> Hari)</span>
                                        </td>

                                        <!-- Tanggal Kembali Aktual -->
                                        <td>
                                            <?php if ($r['status'] === 'dibatalkan'): ?>
                                                <span class="text-muted">-</span>
                                            <?php elseif ($r['tanggal_kembali_aktual']): ?>
                                                <span class="fw-semibold text-dark"><i class="bi bi-calendar-check text-success me-1"></i><?= date('d M Y', strtotime($r['tanggal_kembali_aktual'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Data kosong</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Kondisi Mobil -->
                                        <td>
                                            <?php if ($r['status'] === 'dibatalkan'): ?>
                                                <span class="text-muted">-</span>
                                            <?php elseif ($r['kondisi_mobil']): ?>
                                                <?= getStatusBadge($r['kondisi_mobil']) ?>
                                                <?php if (!empty($r['ket_kembali'])): ?>
                                                    <small class="text-muted d-block mt-1 text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($r['ket_kembali']) ?>">
                                                        "<?= htmlspecialchars($r['ket_kembali']) ?>"
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Biaya Sewa -->
                                        <td class="text-end fw-semibold">
                                            <?= formatRupiah($r['total_biaya']) ?>
                                        </td>

                                        <!-- Denda -->
                                        <td class="text-end fw-semibold text-danger">
                                            <?php if ($r['status'] === 'dibatalkan'): ?>
                                                -
                                            <?php else: ?>
                                                <?= formatRupiah($r['denda'] ?? 0.0) ?>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Total Keseluruhan -->
                                        <td class="text-end fw-bold text-primary">
                                            <?php
                                            if ($r['status'] === 'dibatalkan') {
                                                echo '-';
                                            } else {
                                                $totalKeseluruhan = (float)$r['total_biaya'] + (float)($r['denda'] ?? 0.0);
                                                echo formatRupiah($totalKeseluruhan);
                                            }
                                            ?>
                                        </td>

                                        <!-- Status Badge -->
                                        <td class="text-center pe-4">
                                            <?= getStatusBadge($r['status']) ?>
                                        </td>

                                        <!-- Lihat Detail -->
                                        <td class="text-center">
                                            <a href="detail-pesanan.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm py-5 text-center" style="border-radius: 12px;">
                    <div class="mb-3">
                        <i class="bi bi-archive text-muted" style="font-size: 3.5rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Riwayat sewa kosong</h5>
                    <p class="text-muted px-3">Anda belum memiliki transaksi penyewaan mobil yang sudah diselesaikan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.customer-wrapper -->

<?php
require_once '../includes/footer.php';
?>
