<?php
// admin/transaksi-detail.php
// Detail Satu Transaksi — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    redirectWith('transaksi.php', 'danger', 'ID transaksi tidak valid.');
}

// Ambil data transaksi lengkap
$stmt = $pdo->prepare("
    SELECT p.*,
           u.nama_lengkap, u.email, u.no_telepon, u.alamat,
           m.nama_mobil, m.tipe, m.plat_nomor, m.harga_per_hari, m.foto AS foto_mobil
    FROM pemesanan p
    JOIN users u ON p.customer_id = u.id
    JOIN mobil m ON p.mobil_id = m.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    redirectWith('transaksi.php', 'danger', 'Transaksi tidak ditemukan.');
}

// Ambil data pembayaran jika ada
$bayar = $pdo->prepare("SELECT * FROM pembayaran WHERE pemesanan_id = ? ORDER BY created_at DESC LIMIT 1");
$bayar->execute([$id]);
$pembayaran = $bayar->fetch();

// Ambil data pengembalian jika ada
$kembali = $pdo->prepare("SELECT * FROM pengembalian WHERE pemesanan_id = ?");
$kembali->execute([$id]);
$pengembalian = $kembali->fetch();

$page_title = 'Detail Transaksi | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-file-text me-2"></i>Detail Transaksi
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="transaksi.php">Transaksi</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($trx['kode_pesan']) ?></li>
                </ol>
            </nav>
        </div>
        <a href="transaksi.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row g-4">
        <!-- Kiri: Info Transaksi & Mobil -->
        <div class="col-lg-8">
            <!-- Info Pemesanan -->
            <div class="card shadow-sm mb-4">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">
                            <i class="bi bi-receipt me-2"></i><?= htmlspecialchars($trx['kode_pesan']) ?>
                        </span>
                        <?= getStatusBadge($trx['status']) ?>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Tanggal Sewa
                            </label>
                            <div class="fw-semibold"><?= date('d F Y', strtotime($trx['tanggal_sewa'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Tanggal Kembali (Rencana)
                            </label>
                            <div class="fw-semibold"><?= date('d F Y', strtotime($trx['tanggal_kembali'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Lama Sewa
                            </label>
                            <div class="fw-semibold"><?= $trx['lama_sewa'] ?> Hari</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Total Biaya
                            </label>
                            <div class="fw-bold text-rupiah" style="font-size:1.2rem;">
                                <?= formatRupiah($trx['total_biaya']) ?>
                            </div>
                        </div>
                        <?php if ($trx['catatan']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Catatan Customer
                            </label>
                            <div class="mt-1 p-3 rounded" style="background:#f8f9fa; border:1px solid var(--color-border);">
                                <?= htmlspecialchars($trx['catatan']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Info Mobil -->
            <div class="card shadow-sm mb-4">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold"><i class="bi bi-car-front me-2"></i>Kendaraan</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex gap-4 align-items-center">
                        <?php if ($trx['foto_mobil']): ?>
                            <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($trx['foto_mobil']) ?>"
                                 alt="Foto Mobil"
                                 style="width:120px; height:90px; object-fit:cover; border-radius:10px;">
                        <?php else: ?>
                            <div style="width:120px; height:90px; border-radius:10px; background:#e0e0e0;
                                        display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:#bbb;">
                                <i class="bi bi-car-front"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($trx['nama_mobil']) ?></h5>
                            <div class="text-muted"><?= htmlspecialchars($trx['tipe']) ?></div>
                            <div class="mt-1"><code><?= htmlspecialchars($trx['plat_nomor'] ?? '-') ?></code></div>
                            <div class="mt-1 text-rupiah fw-semibold"><?= formatRupiah($trx['harga_per_hari']) ?> / hari</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pembayaran -->
            <?php if ($pembayaran): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><i class="bi bi-credit-card me-2"></i>Pembayaran</span>
                        <?= getStatusBadge($pembayaran['status']) ?>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Jumlah Dibayar
                            </label>
                            <div class="fw-bold text-rupiah"><?= formatRupiah($pembayaran['jumlah_bayar']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Metode
                            </label>
                            <div class="fw-semibold"><?= htmlspecialchars(str_replace('_', ' ', ucwords($pembayaran['metode_pembayaran'], '_'))) ?></div>
                        </div>
                        <?php if ($pembayaran['bukti_pembayaran']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Bukti Pembayaran
                            </label>
                            <div class="mt-1">
                                <?php
                                $ext = strtolower(pathinfo($pembayaran['bukti_pembayaran'], PATHINFO_EXTENSION));
                                $buktiUrl = '/rental-mobil/assets/uploads/bukti-bayar/' . htmlspecialchars($pembayaran['bukti_pembayaran']);
                                ?>
                                <?php if ($ext === 'pdf'): ?>
                                    <a href="<?= $buktiUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-pdf me-1"></i>Lihat PDF
                                    </a>
                                <?php else: ?>
                                    <a href="<?= $buktiUrl ?>" target="_blank">
                                        <img src="<?= $buktiUrl ?>"
                                             alt="Bukti Bayar"
                                             style="max-width:200px; max-height:200px; border-radius:8px; cursor:pointer;
                                                    border:2px solid var(--color-border);">
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($pembayaran['catatan_admin']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">
                                Catatan Admin
                            </label>
                            <div class="mt-1 p-2 rounded" style="background:#f8f9fa; border:1px solid var(--color-border);">
                                <?= htmlspecialchars($pembayaran['catatan_admin']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Aksi verifikasi pembayaran jika masih menunggu -->
                    <?php if ($pembayaran['status'] === 'menunggu'): ?>
                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        <a href="pembayaran.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Verifikasi di Halaman Pembayaran
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pengembalian -->
            <?php if ($pengembalian): ?>
            <div class="card shadow-sm">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-success); color: white;">
                    <span class="fw-semibold"><i class="bi bi-check-circle me-2"></i>Data Pengembalian</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Tgl Kembali Aktual</label>
                            <div class="fw-semibold"><?= date('d F Y', strtotime($pengembalian['tanggal_kembali_aktual'])) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Kondisi Mobil</label>
                            <div><?= getStatusBadge($pengembalian['kondisi_mobil']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Denda</label>
                            <div class="fw-bold <?= $pengembalian['denda'] > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= formatRupiah($pengembalian['denda']) ?>
                            </div>
                        </div>
                        <?php if ($pengembalian['keterangan']): ?>
                        <div class="col-12">
                            <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Keterangan</label>
                            <div><?= htmlspecialchars($pengembalian['keterangan']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kanan: Info Customer -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold"><i class="bi bi-person me-2"></i>Data Customer</span>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div style="width:72px; height:72px; border-radius:50%; margin:0 auto;
                                    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
                                    display:flex; align-items:center; justify-content:center;
                                    color:white; font-weight:800; font-size:2rem;">
                            <?= strtoupper(substr($trx['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <h6 class="fw-bold mt-3 mb-0"><?= htmlspecialchars($trx['nama_lengkap']) ?></h6>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Email</label>
                        <div class="small"><?= htmlspecialchars($trx['email']) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">No. Telepon</label>
                        <div class="small"><?= htmlspecialchars($trx['no_telepon'] ?? '-') ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Alamat</label>
                        <div class="small"><?= htmlspecialchars($trx['alamat'] ?? '-') ?></div>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small fw-semibold text-uppercase" style="font-size:0.7rem; letter-spacing:0.05em;">Tanggal Pesan</label>
                        <div class="small"><?= date('d M Y H:i', strtotime($trx['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
