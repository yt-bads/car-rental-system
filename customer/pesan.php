<?php
// customer/pesan.php
// Halaman Form Pemesanan Mobil — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil parameter mobil_id
$mobil_id = filter_var($_GET['mobil_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$mobil_id) {
    redirectWith('daftar-mobil.php', 'danger', 'Mobil tidak valid atau tidak ditentukan.');
}

// Ambil data mobil
$stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ?");
$stmt->execute([$mobil_id]);
$mobil = $stmt->fetch();

// Validasi keberadaan mobil dan status ketersediaannya
if (!$mobil) {
    redirectWith('daftar-mobil.php', 'danger', 'Mobil tidak ditemukan di dalam sistem.');
}

if ($mobil['status'] !== 'tersedia') {
    redirectWith('daftar-mobil.php', 'danger', 'Maaf, mobil tersebut saat ini sedang tidak tersedia untuk disewa.');
}

$page_title = "Pesan " . htmlspecialchars($mobil['nama_mobil']);
$extra_css  = "customer.css";
$extra_js   = "pesan.js";

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
                    <li class="breadcrumb-item"><a href="daftar-mobil.php" class="text-decoration-none">Daftar Mobil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Form Pemesanan</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- ======= BOOKING DETAIL SHEET ======= -->
    <div class="row g-4">
        <!-- Kolom Kiri: Detail Mobil -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
                <?php if ($mobil['foto']): ?>
                    <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($mobil['foto']) ?>" 
                         class="detail-img-showcase" alt="<?= htmlspecialchars($mobil['nama_mobil']) ?>">
                <?php else: ?>
                    <div class="detail-img-showcase d-flex align-items-center justify-content-center bg-light">
                        <i class="bi bi-car-front text-muted fs-1"></i>
                    </div>
                <?php endif; ?>
                
                <div class="card-body p-4">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2 rounded mb-2">
                        <?= htmlspecialchars($mobil['tipe']) ?>
                    </span>
                    <h3 class="fw-bold text-dark mb-3"><?= htmlspecialchars($mobil['nama_mobil']) ?></h3>
                    
                    <h5 class="fw-bold text-dark mt-4 mb-3 border-bottom pb-2">Spesifikasi Armada</h5>
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <span class="text-muted small d-block">Tahun Rilis</span>
                            <span class="fw-semibold text-dark"><i class="bi bi-calendar-event me-2"></i><?= htmlspecialchars($mobil['tahun']) ?></span>
                        </div>
                        <div class="col-6 col-md-4">
                            <span class="text-muted small d-block">Kapasitas Penumpang</span>
                            <span class="fw-semibold text-dark"><i class="bi bi-people me-2"></i><?= htmlspecialchars($mobil['kapasitas']) ?> Penumpang</span>
                        </div>
                        <div class="col-6 col-md-4">
                            <span class="text-muted small d-block">Warna Mobil</span>
                            <span class="fw-semibold text-dark"><i class="bi bi-palette me-2"></i><?= htmlspecialchars($mobil['warna']) ?></span>
                        </div>
                        <div class="col-6 col-md-4">
                            <span class="text-muted small d-block">Plat Nomor</span>
                            <span class="fw-semibold text-dark"><i class="bi bi-tag me-2"></i><?= htmlspecialchars($mobil['plat_nomor']) ?></span>
                        </div>
                        <div class="col-6 col-md-8">
                            <span class="text-muted small d-block">Harga Sewa</span>
                            <span class="fw-bold text-primary fs-5"><?= formatRupiah($mobil['harga_per_hari']) ?> <small class="text-muted fw-normal" style="font-size:0.75rem;">/ Hari</small></span>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mt-4 mb-3 border-bottom pb-2">Deskripsi Mobil</h5>
                    <p class="text-muted" style="line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($mobil['deskripsi'] ?? 'Tidak ada deskripsi tambahan untuk mobil ini.')) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Pemesanan -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="border-radius: 12px; top: 90px; z-index: 1000;">
                <div class="card-header bg-primary text-white py-3 border-0" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="card-title mb-0 fw-bold"><i class="bi bi-clipboard-check me-2"></i>Detail Penyewaan</h5>
                </div>
                <div class="card-body p-4">
                    <form action="pesan-proses.php" method="POST">
                        <!-- Hidden Inputs -->
                        <input type="hidden" name="mobil_id" value="<?= $mobil['id'] ?>">
                        <input type="hidden" id="harga_per_hari" value="<?= $mobil['harga_per_hari'] ?>">

                        <!-- Tanggal Sewa -->
                        <div class="mb-3">
                            <label for="tanggal_sewa" class="form-label fw-bold text-dark">Tanggal Mulai Sewa <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-check"></i></span>
                                <input type="date" id="tanggal_sewa" class="form-control" name="tanggal_sewa" required>
                            </div>
                        </div>

                        <!-- Tanggal Kembali -->
                        <div class="mb-3">
                            <label for="tanggal_back" class="form-label fw-bold text-dark">Tanggal Pengembalian <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-x"></i></span>
                                <input type="date" id="tanggal_kembali" class="form-control" name="tanggal_kembali" required disabled>
                            </div>
                            <small class="text-muted mt-1 d-block" style="font-size:0.75rem;">* Minimal penyewaan adalah 1 hari.</small>
                        </div>

                        <!-- Catatan Opsional -->
                        <div class="mb-4">
                            <label for="catatan" class="form-label fw-bold text-dark">Catatan Tambahan (Opsional)</label>
                            <textarea id="catatan" class="form-control" name="catatan" rows="3" placeholder="Contoh: Bawa kunci cadangan, pasang pewangi mobil, dll."></textarea>
                        </div>

                        <!-- Kalkulasi Realtime -->
                        <div class="bg-light p-3 rounded-3 mb-4 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">Durasi Sewa</span>
                                <div>
                                    <input type="text" id="lama_sewa" class="form-control form-control-sm text-end fw-bold d-inline-block border-0 bg-transparent" 
                                           name="lama_sewa" value="0" readonly style="width: 50px;">
                                    <span class="fw-bold text-dark">Hari</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-muted small">Estimasi Total Biaya</span>
                                <div>
                                    <input type="hidden" id="total_biaya" name="total_biaya" value="0">
                                    <span class="fw-bold text-primary fs-5" id="total_display">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom flex-grow-1 fw-bold py-2.5">
                                <i class="bi bi-send-check me-2"></i>Konfirmasi Pemesanan
                            </button>
                            <a href="daftar-mobil.php" class="btn btn-outline-secondary py-2.5">
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
