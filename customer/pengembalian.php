<?php
// customer/pengembalian.php
// Halaman Form Pengembalian Mobil Mandiri — Rental Mobil PT. Wildan Abadi Jaya

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

// Ambil data pemesanan dan detail mobil
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

// Validasi status pemesanan (harus aktif / sedang disewa)
if ($pemesanan['status'] !== 'aktif') {
    redirectWith('status-pemesanan.php', 'warning', 'Sewa mobil ini belum berstatus aktif (pembayaran harus disetujui admin terlebih dahulu).');
}

$page_title = "Kembalikan " . htmlspecialchars($pemesanan['nama_mobil']);
$extra_css  = "customer.css";

require_once '../includes/header.php';
require_once '../includes/navbar-customer.php';

// Set default tanggal pengembalian aktual = hari ini
$today = date('Y-m-d');
?>

<div class="container py-4">

    <!-- ======= BREADCRUMB ======= -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Beranda</a></li>
                    <li class="breadcrumb-item"><a href="status-pemesanan.php" class="text-decoration-none">Status Pemesanan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengembalian Mobil</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- Kolom Kiri: Ringkasan Sewa -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Ringkasan Informasi Penyewaan</h6>
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
                            <td class="text-muted ps-0 py-1" width="180">Kode Transaksi</td>
                            <td class="fw-semibold text-dark py-1">: <?= htmlspecialchars($pemesanan['kode_pesan']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Tanggal Mulai Sewa</td>
                            <td class="fw-semibold text-dark py-1">: <?= date('d F Y', strtotime($pemesanan['tanggal_sewa'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Tanggal Rencana Kembali</td>
                            <td class="fw-semibold text-dark py-1">: <?= date('d F Y', strtotime($pemesanan['tanggal_kembali'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Durasi Sewa</td>
                            <td class="fw-semibold text-dark py-1">: <?= htmlspecialchars($pemesanan['lama_sewa']) ?> Hari</td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-0 py-1">Tarif Harian Mobil</td>
                            <td class="fw-semibold text-dark py-1">: <?= formatRupiah($pemesanan['harga_per_hari']) ?> / Hari</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted ps-0 pt-3 pb-1">Biaya Sewa Terbayar</td>
                            <td class="fw-bold text-success pt-3 pb-1">: <?= formatRupiah($pemesanan['total_biaya']) ?> <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">Lunas</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Pengembalian -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-primary text-white py-3 border-0" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h6 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2"></i>Formulir Pengembalian Armada</h6>
                </div>
                <div class="card-body p-4">
                    <form action="pengembalian-proses.php" method="POST">
                        <input type="hidden" name="pemesanan_id" value="<?= $pemesanan['id'] ?>">

                        <!-- Tanggal Pengembalian Aktual -->
                        <div class="mb-3">
                            <label for="tanggal_kembali_aktual" class="form-label fw-bold text-dark">Tanggal Kembali Realisasi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" id="tanggal_kembali_aktual" class="form-control" name="tanggal_kembali_aktual" 
                                       value="<?= $today ?>" min="<?= $pemesanan['tanggal_sewa'] ?>" required>
                            </div>
                        </div>

                        <!-- Kondisi Mobil Dropdown -->
                        <div class="mb-3">
                            <label for="kondisi_mobil" class="form-label fw-bold text-dark">Kondisi Fisik Mobil <span class="text-danger">*</span></label>
                            <select id="kondisi_mobil" class="form-select" name="kondisi_mobil" required>
                                <option value="baik">Baik (Sesuai kondisi awal)</option>
                                <option value="rusak_ringan">Rusak Ringan (Lecet/Gores - Denda Rp 250.000)</option>
                                <option value="rusak_berat">Rusak Berat (Penyok/Pecah/Tabrakan - Denda Rp 1.000.000)</option>
                            </select>
                        </div>

                        <!-- Keterangan Tambahan -->
                        <div class="mb-4">
                            <label for="keterangan" class="form-label fw-bold text-dark">Keterangan Pengembalian (Opsional)</label>
                            <textarea id="keterangan" class="form-control" name="keterangan" rows="3" placeholder="Tuliskan keterangan mengenai kondisi mobil pasca sewa jika diperlukan..."></textarea>
                        </div>

                        <!-- Tampilan Denda Simulasi -->
                        <div class="bg-light p-3 rounded-3 mb-4 border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Denda Terlambat</span>
                                <span class="fw-bold text-dark" id="displayDendaTelat">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Denda Kerusakan</span>
                                <span class="fw-bold text-dark" id="displayDendaKondisi">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="text-muted small">Estimasi Total Denda</span>
                                <span class="fw-bold text-danger fs-5" id="displayTotalDenda">Rp 0</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-custom flex-grow-1 fw-bold py-2.5" 
                                    onclick="return confirm('Apakah Anda yakin ingin memproses pengembalian mobil ini?')">
                                <i class="bi bi-check-all me-1"></i>Selesaikan Pengembalian
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

<!-- ======= LIVE PENALTY CALCULATION JS ======= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tglAktual      = document.getElementById('tanggal_kembali_aktual');
    const kondisiMobil   = document.getElementById('kondisi_mobil');
    
    const dispDendaTelat = document.getElementById('displayDendaTelat');
    const dispDendaKond  = document.getElementById('displayDendaKondisi');
    const dispTotalDenda = document.getElementById('displayTotalDenda');

    const tglRencanaStr  = "<?= $pemesanan['tanggal_kembali'] ?>";
    const hargaPerHari   = parseFloat("<?= $pemesanan['harga_per_hari'] ?>");

    // Format Rupiah Helper untuk Javascript inline
    function formatRupiahLocal(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(angka);
    }

    function hitungDendaSimulasi() {
        if (!tglAktual.value) return;

        const rencana = new Date(tglRencanaStr);
        const aktual  = new Date(tglAktual.value);
        
        let dendaTelat = 0;
        
        if (aktual > rencana) {
            // Hitung selisih hari
            const diffTime = aktual.getTime() - rencana.getTime();
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays > 0) {
                // Denda: 10% dari harga per hari * jumlah hari keterlambatan
                dendaTelat = diffDays * (hargaPerHari * 0.1);
            }
        }

        let dendaKondisi = 0;
        const kondisi = kondisiMobil.value;
        if (kondisi === 'rusak_ringan') {
            dendaKondisi = 250000;
        } else if (kondisi === 'rusak_berat') {
            dendaKondisi = 1000000;
        }

        const totalDenda = dendaTelat + dendaKondisi;

        dispDendaTelat.textContent = formatRupiahLocal(dendaTelat);
        dispDendaKond.textContent  = formatRupiahLocal(dendaKondisi);
        dispTotalDenda.textContent = formatRupiahLocal(totalDenda);
        
        if (totalDenda > 0) {
            dispTotalDenda.className = "fw-bold text-danger fs-5";
        } else {
            dispTotalDenda.className = "fw-bold text-success fs-5";
        }
    }

    tglAktual.addEventListener('change', hitungDendaSimulasi);
    kondisiMobil.addEventListener('change', hitungDendaSimulasi);

    // Hitung saat load awal
    hitungDendaSimulasi();
});
</script>

<?php
require_once '../includes/footer.php';
?>
