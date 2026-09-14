<?php
// customer/detail-pesanan.php
// Detail Pesanan Customer — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Tangkap ID pesanan dari GET
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    redirectWith('status-pemesanan.php', 'danger', 'ID pesanan tidak valid.');
}

// Ambil data pesanan — pastikan milik customer yang sedang login
$stmt = $pdo->prepare("
    SELECT p.*,
           m.nama_mobil, m.tipe, m.plat_nomor, m.harga_per_hari, m.foto AS foto_mobil, m.warna, m.tahun, m.kapasitas
    FROM pemesanan p
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.id = ? AND p.customer_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$trx = $stmt->fetch();

if (!$trx) {
    redirectWith('status-pemesanan.php', 'danger', 'Pesanan tidak ditemukan atau bukan milik Anda.');
}

// Ambil data pembayaran terakhir (jika ada)
$stmtBayar = $pdo->prepare("SELECT * FROM pembayaran WHERE pemesanan_id = ? ORDER BY id DESC LIMIT 1");
$stmtBayar->execute([$id]);
$pembayaran = $stmtBayar->fetch();

// Ambil data pengembalian (jika ada)
$stmtKembali = $pdo->prepare("SELECT * FROM pengembalian WHERE pemesanan_id = ?");
$stmtKembali->execute([$id]);
$pengembalian = $stmtKembali->fetch();

$page_title = "Detail Pesanan";
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';
?>

<div class="container py-4">

    <!-- ======= BREADCRUMB ======= -->
    <div class="row mb-4">
        <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="status-pemesanan.php" class="text-decoration-none">Status Pemesanan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail Pesanan</li>
                    </ol>
                </nav>
                <h4 class="fw-bold text-dark mb-0">
                    <i class="bi bi-file-text me-2" style="color: var(--color-primary);"></i>Detail Pesanan
                </h4>
                <p class="text-muted small mb-0">Rincian lengkap transaksi sewa mobil Anda</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (in_array($trx['status'], ['menunggu_pembayaran', 'menunggu_konfirmasi', 'aktif'])): ?>
                    <a href="status-pemesanan.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                <?php else: ?>
                    <a href="riwayat.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Riwayat
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ======= KOLOM KIRI: Info Pemesanan & Mobil ======= -->
        <div class="col-lg-8">

            <!-- Card: Info Pemesanan -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 border-0 d-flex justify-content-between align-items-center" 
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold">
                        <i class="bi bi-receipt me-2"></i><?= htmlspecialchars($trx['kode_pesan']) ?>
                    </span>
                    <?= getStatusBadge($trx['status']) ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Tanggal Sewa
                            </label>
                            <div class="fw-semibold"><i class="bi bi-calendar-event me-1 text-primary"></i><?= date('d F Y', strtotime($trx['tanggal_sewa'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Tanggal Kembali (Rencana)
                            </label>
                            <div class="fw-semibold"><i class="bi bi-calendar-check me-1 text-primary"></i><?= date('d F Y', strtotime($trx['tanggal_kembali'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Lama Sewa
                            </label>
                            <div class="fw-semibold"><i class="bi bi-hourglass-split me-1 text-primary"></i><?= $trx['lama_sewa'] ?> Hari</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Total Biaya Sewa
                            </label>
                            <div class="fw-bold text-primary" style="font-size:1.2rem;">
                                <?= formatRupiah($trx['total_biaya']) ?>
                            </div>
                        </div>
                        <?php if ($trx['catatan']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Catatan Anda
                            </label>
                            <div class="mt-1 p-3 rounded" style="background:#f8f9fa; border:1px solid var(--color-border);">
                                <?= htmlspecialchars($trx['catatan']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card: Info Mobil -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 border-0" style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold"><i class="bi bi-car-front me-2"></i>Kendaraan</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex gap-4 align-items-center flex-wrap">
                        <?php if ($trx['foto_mobil']): ?>
                            <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($trx['foto_mobil']) ?>"
                                 alt="Foto Mobil"
                                 class="rounded shadow-sm"
                                 style="width:140px; height:100px; object-fit:cover; border-radius:10px;">
                        <?php else: ?>
                            <div class="rounded d-flex align-items-center justify-content-center" 
                                 style="width:140px; height:100px; background:#e0e0e0; font-size:2.5rem; color:#bbb;">
                                <i class="bi bi-car-front"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($trx['nama_mobil']) ?></h5>
                            <div class="text-muted mb-1"><?= htmlspecialchars($trx['tipe']) ?></div>
                            <div class="d-flex flex-wrap gap-2 mb-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($trx['plat_nomor'] ?? '-') ?></span>
                                <?php if ($trx['warna']): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-palette me-1"></i><?= htmlspecialchars($trx['warna']) ?></span>
                                <?php endif; ?>
                                <?php if ($trx['tahun']): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($trx['tahun']) ?></span>
                                <?php endif; ?>
                                <?php if ($trx['kapasitas']): ?>
                                    <span class="badge bg-light text-dark border"><i class="bi bi-people me-1"></i><?= htmlspecialchars($trx['kapasitas']) ?> Kursi</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1 fw-semibold" style="color: var(--color-primary);">
                                <?= formatRupiah($trx['harga_per_hari']) ?> <span class="text-muted fw-normal small">/ hari</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card: Pembayaran -->
            <?php if ($pembayaran): ?>
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 border-0 d-flex justify-content-between align-items-center" 
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold"><i class="bi bi-credit-card me-2"></i>Pembayaran</span>
                    <?= getStatusBadge($pembayaran['status']) ?>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Jumlah Dibayar
                            </label>
                            <div class="fw-bold" style="color: var(--color-primary);"><?= formatRupiah($pembayaran['jumlah_bayar']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Metode Pembayaran
                            </label>
                            <div class="fw-semibold"><?= htmlspecialchars(str_replace('_', ' ', ucwords($pembayaran['metode_pembayaran'], '_'))) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Tanggal Upload
                            </label>
                            <div class="fw-semibold"><?= date('d F Y H:i', strtotime($pembayaran['created_at'])) ?></div>
                        </div>

                        <?php if (!empty($pembayaran['bukti_pembayaran'])): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-2" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Bukti Pembayaran
                            </label>
                            <?php
                            $ext = strtolower(pathinfo($pembayaran['bukti_pembayaran'], PATHINFO_EXTENSION));
                            $buktiUrl = '/rental-mobil/assets/uploads/bukti-bayar/' . htmlspecialchars($pembayaran['bukti_pembayaran']);
                            ?>
                            <?php if ($ext === 'pdf'): ?>
                                <a href="<?= $buktiUrl ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-file-pdf me-1"></i>Lihat PDF
                                </a>
                            <?php else: ?>
                                <img src="<?= $buktiUrl ?>" 
                                     alt="Bukti Bayar" 
                                     class="rounded shadow-sm border" 
                                     style="max-height: 120px; max-width: 200px; object-fit: cover; cursor: pointer;"
                                     data-bs-toggle="modal" 
                                     data-bs-target="#modalBuktiDetail"
                                     title="Klik untuk memperbesar">
                                <!-- Modal Full-Size -->
                                <div class="modal fade" id="modalBuktiDetail" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header border-0 bg-dark">
                                                <h6 class="modal-title text-white">
                                                    <i class="bi bi-receipt me-2"></i>Bukti Pembayaran — <?= htmlspecialchars($trx['kode_pesan']) ?>
                                                </h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-0 text-center bg-dark">
                                                <img src="<?= $buktiUrl ?>" alt="Bukti Bayar Full" class="img-fluid" style="max-height: 80vh; object-fit: contain;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($pembayaran['catatan_admin']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Catatan Admin
                            </label>
                            <div class="mt-1 p-2 rounded small" style="background:#fff3cd; border:1px solid #ffc107;">
                                <i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($pembayaran['catatan_admin']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card: Pengembalian -->
            <?php if ($pengembalian): ?>
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 border-0" style="background-color: var(--color-success); color: white;">
                    <span class="fw-semibold"><i class="bi bi-check-circle me-2"></i>Data Pengembalian</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Tanggal Kembali Aktual
                            </label>
                            <div class="fw-semibold"><i class="bi bi-calendar-check text-success me-1"></i><?= date('d F Y', strtotime($pengembalian['tanggal_kembali_aktual'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Kondisi Mobil
                            </label>
                            <div><?= getStatusBadge($pengembalian['kondisi_mobil']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Denda
                            </label>
                            <div class="fw-bold <?= $pengembalian['denda'] > 0 ? 'text-danger' : 'text-success' ?>" style="font-size:1.1rem;">
                                <?= formatRupiah($pengembalian['denda']) ?>
                            </div>
                        </div>
                        <?php if ($pengembalian['keterangan']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Keterangan
                            </label>
                            <div class="mt-1 p-3 rounded" style="background:#f8f9fa; border:1px solid var(--color-border);">
                                <?= htmlspecialchars($pengembalian['keterangan']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ======= KOLOM KANAN: Ringkasan & Aksi ======= -->
        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 border-0" style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold"><i class="bi bi-calculator me-2"></i>Ringkasan Biaya</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Biaya Sewa</span>
                        <span class="fw-semibold"><?= formatRupiah($trx['total_biaya']) ?></span>
                    </div>
                    <?php 
                    $denda = $pengembalian ? (float)$pengembalian['denda'] : 0;
                    $totalAkhir = (float)$trx['total_biaya'] + $denda;
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Denda</span>
                        <span class="fw-semibold <?= $denda > 0 ? 'text-danger' : '' ?>"><?= formatRupiah($denda) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Akhir</span>
                        <span class="fw-bold text-primary" style="font-size:1.15rem;"><?= formatRupiah($totalAkhir) ?></span>
                    </div>
                </div>
            </div>

            <!-- Status Timeline Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header py-3 px-4 border-0" style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Status Transaksi</span>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small d-block">Status Saat Ini</span>
                        <div class="mt-1"><?= getStatusBadge($trx['status']) ?></div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Tanggal Pemesanan</span>
                        <span class="fw-semibold small"><?= date('d F Y H:i', strtotime($trx['created_at'])) ?></span>
                    </div>
                    <?php if ($pembayaran): ?>
                    <div class="mb-3">
                        <span class="text-muted small d-block">Pembayaran</span>
                        <span class="fw-semibold small"><?= getStatusBadge($pembayaran['status']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($pengembalian): ?>
                    <div class="mb-0">
                        <span class="text-muted small d-block">Dikembalikan</span>
                        <span class="fw-semibold small"><?= date('d F Y', strtotime($pengembalian['tanggal_kembali_aktual'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Panel -->
            <div class="d-grid gap-2">
                <?php if ($trx['status'] === 'menunggu_pembayaran'): ?>
                    <a href="pembayaran.php?pesan_id=<?= $trx['id'] ?>" class="btn btn-warning fw-bold">
                        <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                    </a>
                <?php elseif ($trx['status'] === 'aktif'): ?>
                    <a href="pengembalian.php?pesan_id=<?= $trx['id'] ?>" class="btn btn-primary-custom fw-bold">
                        <i class="bi bi-arrow-left-right me-2"></i>Kembalikan Mobil
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /.container -->

<?php
require_once '../includes/footer.php';
?>
