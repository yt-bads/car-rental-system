<?php
// direktur/dashboard.php
// Executive Dashboard — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

// Guard Sesi khusus 'direktur'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'direktur') {
    header('Location: ../auth/login.php');
    exit;
}

// 1. Data Agregat
$totalPendapatan = (float)($pdo->query("SELECT SUM(total_biaya) FROM pemesanan WHERE status = 'selesai'")->fetchColumn() ?: 0.0);
$totalTransaksi  = (int)($pdo->query("SELECT COUNT(*) FROM pemesanan")->fetchColumn() ?: 0);
$totalDenda      = (float)($pdo->query("SELECT SUM(denda) FROM pengembalian")->fetchColumn() ?: 0.0);
$pesananAktif    = (int)($pdo->query("SELECT COUNT(*) FROM pemesanan WHERE status = 'aktif'")->fetchColumn() ?: 0);

// 2. Data Grafik Pendapatan (Bar Chart - 6 Bulan Terakhir)
$months_data = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $months_data[$key] = 0.0;
}

$startDate = date('Y-m-01 00:00:00', strtotime('-5 months'));
$stmtRevenue = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS bulan, 
        SUM(total_biaya) AS total
    FROM pemesanan
    WHERE status = 'selesai'
      AND created_at >= ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY bulan ASC
");
$stmtRevenue->execute([$startDate]);
$revenueResults = $stmtRevenue->fetchAll();

foreach ($revenueResults as $row) {
    if (isset($months_data[$row['bulan']])) {
        $months_data[$row['bulan']] = (float)$row['total'];
    }
}

// Terjemahkan nama bulan ke Indonesia
$indonesian_months = [
    '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mei', '06' => 'Jun',
    '07' => 'Jul', '08' => 'Ags', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
];
$chart_labels = [];
foreach (array_keys($months_data) as $ym) {
    [$year, $month] = explode('-', $ym);
    $chart_labels[] = ($indonesian_months[$month] ?? $month) . ' ' . $year;
}
$chart_values = array_values($months_data);

// 3. Data Grafik Status (Donut Chart)
$statusResults = $pdo->query("
    SELECT status, COUNT(*) AS jumlah 
    FROM pemesanan 
    GROUP BY status
")->fetchAll();

$status_map = [
    'menunggu_pembayaran' => ['label' => 'Menunggu Pembayaran', 'color' => '#ffc107'],
    'menunggu_konfirmasi' => ['label' => 'Menunggu Konfirmasi', 'color' => '#0dcaf0'],
    'aktif' => ['label' => 'Aktif', 'color' => '#0d6efd'],
    'selesai' => ['label' => 'Selesai', 'color' => '#198754'],
    'dibatalkan' => ['label' => 'Dibatalkan', 'color' => '#dc3545']
];

$status_labels = [];
$status_values = [];
$status_colors = [];

foreach ($statusResults as $row) {
    $status = $row['status'];
    $jumlah = (int)$row['jumlah'];
    if (isset($status_map[$status])) {
        $status_labels[] = $status_map[$status]['label'];
        $status_values[] = $jumlah;
        $status_colors[] = $status_map[$status]['color'];
    } else {
        $status_labels[] = ucfirst($status);
        $status_values[] = $jumlah;
        $status_colors[] = '#6c757d';
    }
}

$page_title = 'Executive Dashboard';
$extra_css  = 'admin.css';

require_once '../includes/header.php';
require_once '../includes/navbar-direktur.php';
?>

<div class="admin-content">
    
    <!-- Title & Header Bar -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-speedometer2 me-2"></i>Executive Dashboard
            </h4>
            <p class="text-muted small mb-0">Visualisasi data operasional dan pendapatan PT. Wildan Abadi Jaya</p>
        </div>
    </div>

    <!-- ===== SUMMARY CARDS ROW ===== -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Pendapatan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card-stat" style="background-image: linear-gradient(135deg, #1a237e 0%, #3f51b5 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Total Pendapatan</div>
                        <div class="card-stat-value" style="font-size: 1.55rem; margin-bottom: 1.25rem;"><?= formatRupiah($totalPendapatan) ?></div>
                        <a href="/rental-mobil/direktur/laporan.php?jenis=pendapatan" class="card-stat-link">
                            Lihat Pendapatan <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Transaksi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card-stat" style="background-image: linear-gradient(135deg, #0288d1 0%, #039be5 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Total Transaksi</div>
                        <div class="card-stat-value"><?= number_format($totalTransaksi) ?></div>
                        <a href="/rental-mobil/direktur/laporan.php?jenis=semua" class="card-stat-link">
                            Lihat Transaksi <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Total Denda -->
        <div class="col-sm-6 col-xl-3">
            <div class="card-stat" style="background-image: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Total Denda</div>
                        <div class="card-stat-value" style="font-size: 1.55rem; margin-bottom: 1.25rem;"><?= formatRupiah($totalDenda) ?></div>
                        <a href="/rental-mobil/direktur/laporan.php?jenis=denda" class="card-stat-link">
                            Lihat Detail Denda <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Pesanan Aktif -->
        <div class="col-sm-6 col-xl-3">
            <div class="card-stat" style="background-image: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-stat-label">Pesanan Aktif</div>
                        <div class="card-stat-value"><?= number_format($pesananAktif) ?></div>
                        <a href="/rental-mobil/direktur/laporan.php?jenis=semua" class="card-stat-link">
                            Lihat Laporan <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="font-size: 2.5rem; opacity: 0.2;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== CHARTS ROW ===== -->
    <div class="row g-4">
        <!-- Kolom Kiri: Grafik Pendapatan -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0" style="color: var(--color-primary);">
                        <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Grafik Pendapatan (6 Bulan Terakhir)
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 350px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Grafik Status Pesanan -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-0" style="color: var(--color-primary);">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Status Pesanan
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 350px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.admin-content -->

    </div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<!-- Load Chart.js via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Bar Chart: Monthly Revenue
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?= json_encode($chart_values) ?>,
                backgroundColor: 'rgba(26, 35, 126, 0.85)',
                borderColor: 'rgb(26, 35, 126)',
                borderWidth: 1,
                borderRadius: 6,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            let val = context.parsed.y;
                            return 'Pendapatan: Rp ' + new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(val);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11
                        },
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000) + ' Jt';
                            }
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: 'Inter',
                            size: 11,
                            weight: '500'
                        }
                    }
                }
            }
        }
    });

    // 2. Donut Chart: Status Pesanan
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($status_labels) ?>,
            datasets: [{
                data: <?= json_encode($status_values) ?>,
                backgroundColor: <?= json_encode($status_colors) ?>,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            family: 'Inter',
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let val = context.parsed;
                            return ' ' + label + ': ' + val + ' pesanan';
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
