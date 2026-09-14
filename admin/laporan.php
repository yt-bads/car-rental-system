<?php
// admin/laporan.php
// Laporan Rental — Admin & Direktur — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'direktur'])) {
    header('Location: ../auth/login.php');
    exit;
}

$isAdmin    = $_SESSION['role'] === 'admin';
$isDirektur = $_SESSION['role'] === 'direktur';

// Default periode: bulan ini
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');

// Validasi format tanggal
$dari   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)   ? $dari   : date('Y-m-01');
$sampai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai) ? $sampai : date('Y-m-t');

// Filter jenis laporan
$jenis = $_GET['jenis'] ?? 'semua';
if (!in_array($jenis, ['semua', 'pendapatan', 'denda'])) {
    $jenis = 'semua';
}

$sql = "
    SELECT
        p.kode_pesan,
        p.tanggal_sewa,
        p.tanggal_kembali,
        p.lama_sewa,
        p.total_biaya,
        p.status,
        p.created_at,
        u.nama_lengkap AS nama_customer,
        u.no_telepon,
        m.nama_mobil,
        m.tipe,
        m.plat_nomor,
        py.metode_pembayaran,
        py.jumlah_bayar,
        py.status AS status_bayar,
        k.tanggal_kembali_aktual,
        k.kondisi_mobil,
        k.denda
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

// Hitung ringkasan
$totalTransaksi  = count($laporan);
$totalPendapatan = array_sum(array_column(
    array_filter($laporan, fn($r) => $r['status'] === 'selesai'),
    'total_biaya'
));
$totalDenda = array_sum(array_column($laporan, 'denda'));
$totalAktif = count(array_filter($laporan, fn($r) => $r['status'] === 'aktif'));
$totalSelesai = count(array_filter($laporan, fn($r) => $r['status'] === 'selesai'));

// Set page title & include navbar sesuai role
$page_title = 'Laporan Rental | ' . ($isAdmin ? 'Admin' : 'Direktur');
$extra_css  = 'admin.css';
$extra_js   = 'laporan.js';

$headerLabel = 'Rincian Transaksi';
if ($jenis === 'denda') {
    $headerLabel = 'Laporan Khusus Denda Keterlambatan/Kerusakan';
} elseif ($jenis === 'pendapatan') {
    $headerLabel = 'Laporan Pendapatan Rental Mobil';
}

require_once '../includes/header.php';

if ($isAdmin) {
    require_once '../includes/navbar-admin.php';
} else {
    require_once '../includes/navbar-direktur.php';
}
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Rental
            </h4>
            <p class="text-muted small mb-0">Rekap transaksi berdasarkan periode</p>
        </div>
        <div class="d-flex gap-2">
            <a href="laporan-pdf.php?dari=<?= urlencode($dari) ?>&sampai=<?= urlencode($sampai) ?>&jenis=<?= urlencode($jenis) ?>" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>Ekspor PDF
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card shadow-sm mb-4 no-print">
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

        <!-- ===== PRINT HEADER (hanya tampil saat print) ===== -->
        <div class="print-only" style="display:none; margin-bottom:1.5rem;">
            <h3 style="color: #1a237e; margin-bottom:0.25rem;">PT. Wildan Abadi Jaya</h3>
            <p style="margin:0; color:#555; font-weight: bold;"><?= htmlspecialchars($headerLabel) ?></p>
            <p style="margin:0; color:#555;">Periode: <?= date('d M Y', strtotime($dari)) ?>
                s/d <?= date('d M Y', strtotime($sampai)) ?>
            </p>
            <p style="margin:0; color:#888; font-size:0.85rem;">
                Dicetak oleh: <?= htmlspecialchars($_SESSION['nama']) ?>
                | <?= date('d M Y H:i') ?>
            </p>
            <hr style="margin: 0.75rem 0;">
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card-stat text-center" style="padding: 1rem;">
                    <div class="card-stat-label">Total Transaksi</div>
                    <div class="card-stat-value"><?= $totalTransaksi ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat card-stat--success text-center" style="padding: 1rem;">
                    <div class="card-stat-label">Selesai</div>
                    <div class="card-stat-value"><?= $totalSelesai ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat card-stat--warning text-center" style="padding: 1rem;">
                    <div class="card-stat-label">Aktif Berjalan</div>
                    <div class="card-stat-value"><?= $totalAktif ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat" style="background-image:linear-gradient(135deg,#388e3c,#4caf50); padding: 1rem;">
                    <div class="card-stat-label">Total Pendapatan</div>
                    <div class="card-stat-value" style="font-size:1.4rem;"><?= formatRupiah($totalPendapatan) ?></div>
                </div>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card shadow-sm">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center"
                 style="background-color: var(--color-primary); color: white;">
                <span class="fw-semibold">
                    <i class="bi bi-table me-2"></i><?= htmlspecialchars($headerLabel) ?>
                    | Periode: <?= date('d M Y', strtotime($dari)) ?> — <?= date('d M Y', strtotime($sampai)) ?>
                </span>
                <span class="badge bg-warning text-dark"><?= $totalTransaksi ?> data</span>
            </div>
        <div class="card-body p-0">
            <?php if (empty($laporan)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-file-earmark-x" style="font-size:3rem; opacity:0.3;"></i>
                    <p class="mt-3 mb-0">Tidak ada data untuk periode ini</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0" style="font-size:0.875rem;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Pesan</th>
                            <th>Customer</th>
                            <th>Mobil</th>
                            <th>Tgl Sewa</th>
                            <th>Tgl Kembali</th>
                            <th>Lama</th>
                            <th>Total Biaya</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporan as $i => $r): ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <a href="transaksi-detail.php?id=<?= htmlspecialchars($r['kode_pesan']) ?>"
                                       class="fw-semibold text-decoration-none small"
                                       style="color:var(--color-primary);">
                                        <?= htmlspecialchars($r['kode_pesan']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="fw-semibold small"><?= htmlspecialchars($r['kode_pesan']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small"><?= htmlspecialchars($r['nama_customer']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($r['no_telepon'] ?? '') ?></div>
                            </td>
                            <td>
                                <div class="small"><?= htmlspecialchars($r['nama_mobil']) ?></div>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($r['plat_nomor'] ?? '') ?></div>
                            </td>
                            <td class="small"><?= date('d/m/Y', strtotime($r['tanggal_sewa'])) ?></td>
                            <td class="small"><?= date('d/m/Y', strtotime($r['tanggal_kembali'])) ?></td>
                            <td class="text-center small"><?= $r['lama_sewa'] ?> hr</td>
                            <td class="text-rupiah small fw-semibold"><?= formatRupiah($r['total_biaya']) ?></td>
                            <td class="text-center small">
                                <?php if ($r['status_bayar']): ?>
                                    <?= getStatusBadge($r['status_bayar']) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= getStatusBadge($r['status']) ?></td>
                            <td class="small <?= ($r['denda'] ?? 0) > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                                <?= ($r['denda'] ?? 0) > 0 ? formatRupiah($r['denda']) : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background: #f8f9fa;">
                        <tr>
                            <td colspan="7" class="text-end fw-bold small py-3">TOTAL PENDAPATAN (Selesai):</td>
                            <td class="fw-bold text-rupiah" style="font-size:0.95rem;"><?= formatRupiah($totalPendapatan) ?></td>
                            <td colspan="2"></td>
                            <td class="fw-bold <?= $totalDenda > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= $totalDenda > 0 ? formatRupiah($totalDenda) : '—' ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </div><!-- /#area-cetak -->
</div>

<style>
@media print {
    .print-only { display: block !important; }
}
</style>



<?php require_once '../includes/footer.php'; ?>
