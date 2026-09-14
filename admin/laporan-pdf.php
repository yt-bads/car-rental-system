<?php
// admin/laporan-pdf.php
// Generator PDF Laporan - Admin — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

session_start();

// Guard Sesi
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'direktur'])) {
    header('Location: ../auth/login.php');
    exit;
}

$isAdmin = $_SESSION['role'] === 'admin';

// Read GET parameters
$dari   = $_GET['dari']   ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');

$dari   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)   ? $dari   : date('Y-m-01');
$sampai = preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai) ? $sampai : date('Y-m-t');

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

// Database query
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

// Build HTML content for mPDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: sans-serif;
            color: #212121;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 15px;
        }
        .header h3 {
            color: #1a237e;
            margin: 0 0 5px 0;
            font-size: 14pt;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0;
            color: #333;
        }
        .header .subtitle {
            font-size: 11pt;
            font-weight: bold;
        }
        .header .meta {
            font-size: 8pt;
            color: #666;
        }
        hr {
            border: 0;
            border-top: 2px solid #1a237e;
            margin: 10px 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            border-top: 1.5px solid #dcdcdc;
            border-bottom: 1.5px solid #dcdcdc;
        }
        .summary-cell {
            padding: 10px 5px;
            text-align: center;
            border-right: 1px solid #e0e0e0;
        }
        .summary-cell:last-child {
            border-right: none;
        }
        .card-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #757575;
            letter-spacing: 0.5px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .card-value {
            font-size: 13pt;
            font-weight: bold;
            color: #1a237e;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .report-table th {
            background-color: #1a237e;
            color: white;
            font-size: 8pt;
            font-weight: bold;
            padding: 8px 5px;
            border: 1px solid #1a237e;
            text-align: left;
        }
        .report-table td {
            font-size: 8pt;
            padding: 6px 5px;
            border: 1px solid #e0e0e0;
            vertical-align: top;
            word-wrap: break-word;
        }
        .report-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .fw-bold {
            font-weight: bold;
        }
        .text-rupiah {
            font-weight: bold;
            color: #1a237e;
        }
        .text-danger {
            color: #d32f2f;
            font-weight: bold;
        }
        .text-muted {
            color: #757575;
        }
        /* Badges styling compatible with mPDF */
        .badge {
            padding: 2px 4px;
            font-weight: bold;
            border-radius: 4px;
            font-size: 7pt;
            display: inline-block;
            text-align: center;
        }
        .bg-success { background-color: #d4edda; color: #155724; }
        .bg-warning { background-color: #fff3cd; color: #856404; }
        .bg-danger { background-color: #f8d7da; color: #721c24; }
        .bg-info { background-color: #d1ecf1; color: #0c5460; }
        .bg-primary { background-color: #cce5ff; color: #004085; }
        .bg-secondary { background-color: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
    <div class="header">
        <h3>PT. Wildan Abadi Jaya</h3>
        <p class="subtitle">' . htmlspecialchars($headerLabel) . '</p>
        <p>Periode: ' . date('d M Y', strtotime($dari)) . ' s/d ' . date('d M Y', strtotime($sampai)) . '</p>
        <p class="meta">Dicetak oleh: ' . htmlspecialchars($_SESSION['nama']) . ' | ' . date('d M Y H:i') . '</p>
    </div>
    <hr />

    <table class="summary-table">
        <tr>
            <td width="25%" class="summary-cell">
                <div class="card-label">Total Transaksi</div>
                <div class="card-value">' . $totalTransaksi . '</div>
            </td>
            <td width="25%" class="summary-cell">
                <div class="card-label">Selesai</div>
                <div class="card-value">' . $totalSelesai . '</div>
            </td>
            <td width="25%" class="summary-cell">
                <div class="card-label">Aktif Berjalan</div>
                <div class="card-value">' . $totalAktif . '</div>
            </td>
            <td width="25%" class="summary-cell">
                <div class="card-label">Total Pendapatan</div>
                <div class="card-value">' . formatRupiah($totalPendapatan) . '</div>
            </td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="12%">Kode Booking</th>
                <th width="15%">Customer</th>
                <th width="15%">Mobil</th>
                <th width="14%">Periode Sewa</th>
                <th width="7%" class="text-center">Durasi</th>
                <th width="11%" class="text-end">Biaya Sewa</th>
                <th width="11%" class="text-center">Status Bayar</th>
                <th width="8%" class="text-center">Status</th>
                <th width="10%" class="text-end">Denda</th>
            </tr>
        </thead>
        <tbody>';

if (empty($laporan)) {
    $html .= '
            <tr>
                <td colspan="10" class="text-center text-muted" style="padding: 20px 0;">
                    Tidak ada transaksi ditemukan untuk periode ini.
                </td>
            </tr>';
} else {
    foreach ($laporan as $i => $r) {
        // Resolve status badges manually with custom mPDF classes
        $statusBayarBadge = '—';
        if ($r['status_bayar']) {
            $map = [
                'menunggu' => ['warning', 'Menunggu'],
                'diterima' => ['success', 'Diterima'],
                'ditolak'  => ['danger',  'Ditolak'],
            ];
            [$color, $label] = $map[$r['status_bayar']] ?? ['secondary', ucfirst($r['status_bayar'])];
            $statusBayarBadge = '<span class="badge bg-' . $color . '">' . $label . '</span>';
        }

        $mapSewa = [
            'menunggu_pembayaran' => ['warning',   'Menunggu'],
            'menunggu_konfirmasi' => ['info',      'Konfirmasi'],
            'aktif'               => ['primary',   'Aktif'],
            'selesai'             => ['success',   'Selesai'],
            'dibatalkan'          => ['danger',    'Batal'],
        ];
        [$colorSewa, $labelSewa] = $mapSewa[$r['status']] ?? ['secondary', ucfirst($r['status'])];
        $statusSewaBadge = '<span class="badge bg-' . $colorSewa . '">' . $labelSewa . '</span>';

        $dendaLabel = ($r['denda'] ?? 0) > 0 ? formatRupiah($r['denda']) : '—';
        $dendaClass = ($r['denda'] ?? 0) > 0 ? 'class="text-danger"' : 'class="text-muted"';

        $html .= '
            <tr>
                <td class="text-center">' . ($i + 1) . '</td>
                <td class="fw-bold">' . htmlspecialchars($r['kode_pesan']) . '</td>
                <td>
                    <div>' . htmlspecialchars($r['nama_customer']) . '</div>
                    <div style="font-size: 7.5pt; color: #666;">' . htmlspecialchars($r['no_telepon'] ?? '') . '</div>
                </td>
                <td>
                    <div>' . htmlspecialchars($r['nama_mobil']) . '</div>
                    <div style="font-size: 7.5pt; color: #666;">' . htmlspecialchars($r['plat_nomor'] ?? '') . '</div>
                </td>
                <td>
                    <div>' . date('d/m/y', strtotime($r['tanggal_sewa'])) . ' s/d</div>
                    <div>' . date('d/m/y', strtotime($r['tanggal_kembali'])) . '</div>
                </td>
                <td class="text-center">' . htmlspecialchars($r['lama_sewa']) . ' hr</td>
                <td class="text-end fw-bold">' . formatRupiah($r['total_biaya']) . '</td>
                <td class="text-center">' . $statusBayarBadge . '</td>
                <td class="text-center">' . $statusSewaBadge . '</td>
                <td class="text-end ' . (($r['denda'] ?? 0) > 0 ? 'text-danger' : '') . '">' . $dendaLabel . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f3f9; font-weight: bold;">
                <td colspan="6" class="text-end py-3">TOTAL PENDAPATAN (Selesai):</td>
                <td class="text-end text-rupiah">' . formatRupiah($totalPendapatan) . '</td>
                <td colspan="2"></td>
                <td class="text-end ' . ($totalDenda > 0 ? 'text-danger' : 'text-muted') . '">' . ($totalDenda > 0 ? formatRupiah($totalDenda) : '—') . '</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
';

// Generate PDF using mPDF
try {
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 12,
        'margin_bottom' => 12,
        'orientation' => 'P'
    ]);

    $mpdf->SetTitle('Laporan Rental PT. Wildan Abadi Jaya');
    $mpdf->WriteHTML($html);
    
    // Set output name
    $filename = 'Laporan-Rental-' . date('d-m-Y', strtotime($dari)) . '-sd-' . date('d-m-Y', strtotime($sampai)) . '.pdf';
    
    $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
} catch (\Mpdf\MpdfException $e) {
    die('Terjadi kesalahan saat membuat PDF: ' . $e->getMessage());
}
