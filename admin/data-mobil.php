<?php
// admin/data-mobil.php
// Kelola Data Mobil — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Filter status (opsional)
$filterStatus = $_GET['status'] ?? '';
$allowedStatus = ['tersedia', 'disewa', 'servis'];

if ($filterStatus !== '' && in_array($filterStatus, $allowedStatus)) {
    $stmt = $pdo->prepare("SELECT * FROM mobil WHERE status = ? ORDER BY nama_mobil ASC");
    $stmt->execute([$filterStatus]);
} else {
    $stmt = $pdo->query("SELECT * FROM mobil ORDER BY nama_mobil ASC");
}
$mobilList = $stmt->fetchAll();

$page_title = 'Data Mobil | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-car-front me-2"></i>Data Mobil
            </h4>
            <p class="text-muted small mb-0">Kelola armada kendaraan rental</p>
        </div>
        <a href="data-mobil-tambah.php" class="btn btn-warning fw-semibold">
            <i class="bi bi-plus-lg me-1"></i> Tambah Mobil
        </a>
    </div>

    <!-- Filter Status -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small fw-semibold">Filter Status:</span>
                <a href="data-mobil.php"
                   class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Semua
                </a>
                <a href="data-mobil.php?status=tersedia"
                   class="btn btn-sm <?= $filterStatus === 'tersedia' ? 'btn-success' : 'btn-outline-success' ?>">
                    Tersedia
                </a>
                <a href="data-mobil.php?status=disewa"
                   class="btn btn-sm <?= $filterStatus === 'disewa' ? 'btn-warning' : 'btn-outline-warning' ?>">
                    Disewa
                </a>
                <a href="data-mobil.php?status=servis"
                   class="btn btn-sm <?= $filterStatus === 'servis' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                    Servis
                </a>
            </div>
        </div>
    </div>

    <!-- Tabel Mobil -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center px-4 py-3"
             style="background-color: var(--color-primary); color: white;">
            <span class="fw-semibold">
                <i class="bi bi-list-ul me-2"></i>Daftar Armada Kendaraan
                <span class="badge bg-warning text-dark ms-2"><?= count($mobilList) ?> unit</span>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($mobilList)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-car-front" style="font-size: 3rem; opacity:0.3;"></i>
                    <p class="mt-3 mb-0">Tidak ada data mobil</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th style="width:70px;">Foto</th>
                            <th>Nama Mobil</th>
                            <th>Tipe</th>
                            <th>Plat</th>
                            <th>Kapasitas</th>
                            <th>Harga/Hari</th>
                            <th>Status</th>
                            <th style="width:130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mobilList as $i => $m): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i + 1 ?></td>
                            <td class="text-center">
                                <?php if ($m['foto']): ?>
                                    <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($m['foto']) ?>"
                                         alt="<?= htmlspecialchars($m['nama_mobil']) ?>"
                                         style="width:55px; height:40px; object-fit:cover; border-radius:6px;">
                                <?php else: ?>
                                    <div style="width:55px; height:40px; border-radius:6px;
                                                background:#e0e0e0; display:flex; align-items:center;
                                                justify-content:center; font-size:1.2rem; color:#aaa;">
                                        <i class="bi bi-car-front"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($m['nama_mobil']) ?></div>
                                <div class="text-muted small"><?= htmlspecialchars($m['warna'] ?? '-') ?> · <?= $m['tahun'] ?? '-' ?></div>
                            </td>
                            <td><?= htmlspecialchars($m['tipe']) ?></td>
                            <td><code><?= htmlspecialchars($m['plat_nomor'] ?? '-') ?></code></td>
                            <td class="text-center"><?= $m['kapasitas'] ?? '-' ?> <span class="text-muted small">org</span></td>
                            <td class="text-rupiah fw-semibold"><?= formatRupiah($m['harga_per_hari']) ?></td>
                            <td><?= getStatusBadge($m['status']) ?></td>
                            <td>
                                <a href="data-mobil-edit.php?id=<?= $m['id'] ?>"
                                   class="btn btn-sm btn-primary me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($m['status'] !== 'disewa'): ?>
                                    <button class="btn btn-sm btn-danger"
                                            title="Hapus"
                                            onclick="confirmDelete(<?= $m['id'] ?>, 'data-mobil-hapus.php')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-danger" disabled title="Tidak bisa dihapus saat disewa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
