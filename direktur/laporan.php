<?php
// direktur/laporan.php
// Laporan Rental — Read Only (Direktur) — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi Direktur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direktur') {
    header('Location: ../auth/login.php');
    exit;
}

// 1. Ambil Parameter Filter Tanggal (Default: Awal bulan s/d akhir bulan ini)
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');

$dari   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)   ? $dari   : date('Y-m-01');
$sampai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai) ? $sampai : date('Y-m-t');

// 2. Validasi Input Tanggal (Tanggal akhir tidak boleh sebelum tanggal mulai)
if ($sampai < $dari) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'msg' => 'Rentang tanggal tidak valid! Tanggal akhir tidak boleh lebih awal dari tanggal mulai.'
    ];
    $sampai = $dari; // Reset agar seimbang
}

// Filter jenis laporan
$jenis = $_GET['jenis'] ?? 'semua';
if (!in_array($jenis, ['semua', 'pendapatan', 'denda'])) {
    $jenis = 'semua';
}

$headerLabel = 'Rincian Transaksi';
if ($jenis === 'denda') {
    $headerLabel = 'Laporan Khusus Denda Keterlambatan/Kerusakan';
} elseif ($jenis === 'pendapatan') {
    $headerLabel = 'Laporan Pendapatan Rental Mobil';
}

// 3. Tarik Data Laporan Periode
$sql = "
    SELECT
        p.kode_pesan, p.tanggal_sewa, p.tanggal_kembali, p.lama_sewa, p.total_biaya, p.status, p.created_at,
        u.nama_lengkap AS nama_customer, u.no_telepon,
        m.nama_mobil, m.tipe, m.plat_nomor,
        py.metode_pembayaran, py.jumlah_bayar, py.status AS status_bayar,
        k.tanggal_kembali_aktual, k.kondisi_mobil, k.denda
    FROM pemesanan p
    JOIN users u ON p.customer_id = u.id
    JOIN mobil m ON p.mobil_id = m.id
    LEFT JOIN pembayaran py ON py.pemesanan_id = p.id
    LEFT JOIN pengembalian k ON k.pemesanan_id = p.id
    WHERE p.created_at BETWEEN ? AND ?
";

if ($jenis === 'denda') {
    $sql .= " AND k.denda > 0";
} elseif ($jenis === 'pendapatan') {
    $sql .= " AND p.status IN ('aktif', 'selesai')";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$dari . ' 00:00:00', $sampai . ' 23:59:59']);
$laporan = $stmt->fetchAll();

// 4. Hitung Nilai Agregat Statistik
$totalTransaksi  = count($laporan);
$totalPendapatan = array_sum(array_column(
    array_filter($laporan, fn($r) => $r['status'] === 'selesai'),
    'total_biaya'
));
$totalDenda   = array_sum(array_column($laporan, 'denda'));
$totalAktif   = count(array_filter($laporan, fn($r) => $r['status'] === 'aktif'));
$totalSelesai = count(array_filter($laporan, fn($r) => $r['status'] === 'selesai'));

$page_title = 'Laporan Rental | Direktur';
$extra_css  = 'admin.css';

require_once '../includes/header.php';
require_once '../includes/navbar-direktur.php';
?>

<div class="admin-content">
    
    <!-- ======= TITLE & HEADER ACTION ======= -->
    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Rental Kendaraan
            </h4>
            <p class="text-muted small mb-0">Rekapitulasi operasional transaksi rental (Mode Lihat & Cetak)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="laporan-pdf.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&jenis=<?= urlencode($jenis) ?>" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>Ekspor PDF
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-printer me-2"></i>Cetak Laporan
            </button>
        </div>
    </div>

    <!-- ======= FILTER PANEL ======= -->
    <div class="card border-0 shadow-sm mb-4 no-print" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form action="laporan.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="jenis" value="<?= htmlspecialchars($jenis) ?>">
                <div class="col-md-4">
                    <label for="dari" class="form-label fw-semibold">Dari Tanggal</label>
                    <input type="date" class="form-control" id="dari" name="dari"
                           value="<?= htmlspecialchars($dari) ?>">
                </div>
                <div class="col-md-4">
                    <label for="sampai" class="form-label fw-semibold">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="sampai" name="sampai"
                           value="<?= htmlspecialchars($sampai) ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="laporan.php?jenis=<?= htmlspecialchars($jenis) ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div id="area-cetak" class="bg-white p-3 rounded">
        <!-- ===== PDF HEADER ===== -->
        <div class="pdf-header mb-4" style="display: none;">
            <h3 style="color: #1a237e; margin-bottom:0.25rem; font-weight: bold;">PT. Wildan Abadi Jaya</h3>
            <p style="margin:0; color:#333; font-weight: 600; font-size: 1.15rem;"><?= htmlspecialchars($headerLabel) ?></p>
            <p style="margin:0; color:#555;">Periode: <?= date('d M Y', strtotime($dari)) ?> s/d <?= date('d M Y', strtotime($sampai)) ?></p>
            <p style="margin:0; color:#888; font-size:0.8rem;">
                Dicetak oleh: <?= htmlspecialchars($_SESSION['nama']) ?> | <?= date('d M Y H:i') ?>
            </p>
            <hr style="border-top: 2px solid #1a237e; margin: 0.75rem 0; opacity: 1;">
        </div>

        <!-- ======= PRINT HEADERS (HIDDEN ON BROWSER VIEW) ======= -->
        <div class="print-only mb-4" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="fw-bold mb-0" style="color: #1a237e;">PT. Wildan Abadi Jaya</h3>
                    <p class="text-muted small mb-1">Layanan Penyewaan Mobil Terpercaya</p>
                </div>
                <div class="text-end">
                    <h5 class="fw-bold mb-0"><?= htmlspecialchars(strtoupper($headerLabel)) ?></h5>
                    <p class="text-muted small mb-0">Periode: <?= date('d M Y', strtotime($dari)) ?> s/d <?= date('d M Y', strtotime($sampai)) ?></p>
                </div>
            </div>
            <hr style="border-top: 2px solid #000; opacity: 1;">
            <p class="text-muted small">Dicetak oleh Direktur: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> pada <?= date('d M Y H:i') ?></p>
        </div>

        <!-- ======= AGREGATE STATISTIC CARDS ======= -->
        <div class="row g-3 mb-4">
            <!-- Card 1: Total Transaksi -->
            <div class="col-6 col-md-3">
                <div class="card-stat d-flex flex-column justify-content-between" style="padding: 1.25rem; min-height: 110px; border-radius: 10px; background-color: var(--color-primary); color: white;">
                    <div class="card-stat-label" style="font-size: var(--font-sm); opacity: 0.85;">Total Transaksi</div>
                    <div class="card-stat-value" style="font-size: 2.25rem; font-weight: 700;"><?= $totalTransaksi ?></div>
                </div>
            </div>
            
            <!-- Card 2: Selesai -->
            <div class="col-6 col-md-3">
                <div class="card-stat d-flex flex-column justify-content-between" style="padding: 1.25rem; min-height: 110px; border-radius: 10px; background-color: var(--color-success); color: white;">
                    <div class="card-stat-label" style="font-size: var(--font-sm); opacity: 0.85;">Rental Selesai</div>
                    <div class="card-stat-value" style="font-size: 2.25rem; font-weight: 700;"><?= $totalSelesai ?></div>
                </div>
            </div>
            
            <!-- Card 3: Aktif -->
            <div class="col-6 col-md-3">
                <div class="card-stat d-flex flex-column justify-content-between" style="padding: 1.25rem; min-height: 110px; border-radius: 10px; background-color: var(--color-warning); color: white;">
                    <div class="card-stat-label" style="font-size: var(--font-sm); opacity: 0.85;">Sewa Aktif</div>
                    <div class="card-stat-value" style="font-size: 2.25rem; font-weight: 700;"><?= $totalAktif ?></div>
                </div>
            </div>
            
            <!-- Card 4: Total Pendapatan -->
            <div class="col-6 col-md-3">
                <div class="card-stat d-flex flex-column justify-content-between" style="padding: 1.25rem; min-height: 110px; border-radius: 10px; background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%); color: white;">
                    <div class="card-stat-label" style="font-size: var(--font-sm); opacity: 0.85;">Pendapatan Rental</div>
                    <div class="card-stat-value" style="font-size: 1.35rem; font-weight: 700;"><?= formatRupiah($totalPendapatan) ?></div>
                </div>
            </div>
        </div>

        <!-- ======= REPORT DATA TABLE ======= -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center"
                 style="background-color: var(--color-primary); color: white;">
                <span class="fw-semibold">
                    <i class="bi bi-table me-2"></i><?= htmlspecialchars($headerLabel) ?>
                </span>
                <span class="badge bg-warning text-dark fw-bold"><?= $totalTransaksi ?> Transaksi</span>
            </div>
        
        <div class="card-body p-0">
            <?php if (empty($laporan)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x text-muted mb-3" style="font-size:3.5rem; opacity:0.4;"></i>
                    <h6 class="fw-semibold text-dark">Tidak ada transaksi ditemukan</h6>
                    <p class="small text-muted mb-0">Sesuaikan rentang tanggal filter laporan di atas.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle" style="font-size:0.875rem; min-width: 1000px;">
                        <thead class="table-light text-secondary small">
                            <tr>
                                <th class="text-center" width="50">No</th>
                                <th>Kode Booking</th>
                                <th>Nama Pelanggan</th>
                                <th>Nama Mobil</th>
                                <th>Periode Sewa</th>
                                <th class="text-center">Durasi</th>
                                <th class="text-end">Biaya Sewa</th>
                                <th class="text-center">Status Bayar</th>
                                <th class="text-center">Status Sewa</th>
                                <th class="text-end">Denda</th>
                                <th class="text-end pe-4">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="text-dark">
                            <?php foreach ($laporan as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted small"><?= $i + 1 ?></td>
                                    <td class="fw-bold small"><?= htmlspecialchars($r['kode_pesan']) ?></td>
                                    <td>
                                        <span class="fw-semibold d-block text-dark"><?= htmlspecialchars($r['nama_customer']) ?></span>
                                        <span class="text-muted small" style="font-size:0.75rem;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($r['no_telepon'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold d-block text-dark"><?= htmlspecialchars($r['nama_mobil']) ?></span>
                                        <span class="text-muted small" style="font-size:0.75rem;"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($r['plat_nomor'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <span class="d-block text-dark"><?= date('d/m/Y', strtotime($r['tanggal_sewa'])) ?> s/d</span>
                                        <span class="d-block text-dark"><?= date('d/m/Y', strtotime($r['tanggal_kembali'])) ?></span>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars($r['lama_sewa']) ?> hr</td>
                                    <td class="text-end fw-semibold text-rupiah">
                                        <?= formatRupiah($r['total_biaya']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $r['status_bayar'] ? getStatusBadge($r['status_bayar']) : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= getStatusBadge($r['status']) ?>
                                    </td>
                                    <td class="text-end fw-semibold <?= ($r['denda'] ?? 0) > 0 ? 'text-danger' : 'text-muted' ?>">
                                        <?= ($r['denda'] ?? 0) > 0 ? formatRupiah($r['denda']) : '—' ?>
                                    </td>
                                    <td class="text-end fw-bold pe-4 text-primary">
                                        <?php
                                        if ($r['status'] === 'dibatalkan') {
                                            echo '—';
                                        } else {
                                            $totalBiayaTransaksi = (float)$r['total_biaya'] + (float)($r['denda'] ?? 0.0);
                                            echo formatRupiah($totalBiayaTransaksi);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold" style="border-top: 2px solid var(--color-border);">
                            <tr>
                                <td colspan="6" class="text-end text-dark small py-3">TOTAL KESELURUHAN (Rental Selesai & Denda):</td>
                                <td class="text-end text-success"><?= formatRupiah($totalPendapatan) ?></td>
                                <td colspan="2"></td>
                                <td class="text-end text-danger"><?= $totalDenda > 0 ? formatRupiah($totalDenda) : '—' ?></td>
                                <td class="text-end pe-4 text-primary fs-6"><?= formatRupiah($totalPendapatan + $totalDenda) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div><!-- /#area-cetak -->
</div><!-- /.admin-content -->

<!-- ======= PRINT STYLES MEDIA OVERRIDES ======= -->
<style>
@media print {
    .no-print,
    .admin-sidebar,
    .admin-header,
    .alert,
    .btn-close {
        display: none !important;
    }
    
    .admin-main {
        margin-left: 0 !important;
        padding: 0 !important;
        background-color: white !important;
    }
    
    .admin-content {
        padding: 0 !important;
    }
    
    .print-only {
        display: block !important;
    }
    
    body {
        background-color: white !important;
        color: black !important;
        font-size: 10px;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    
    .card-header {
        background-color: #1a237e !important;
        color: white !important;
        border-radius: 0 !important;
    }
    
    .card-stat {
        border: 1px solid #ddd !important;
        color: black !important;
        background: transparent !important;
    }
    
    .card-stat-value {
        color: black !important;
    }
    
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    th, td {
        border: 1px solid #ddd !important;
        padding: 6px !important;
    }
    
    .badge {
        border: 1px solid #888 !important;
        color: black !important;
        background: transparent !important;
    }
}
</style>



    </div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<?php require_once '../includes/footer.php'; ?>
