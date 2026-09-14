<?php
// admin/pembayaran.php
// Verifikasi Pembayaran — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// ===== HANDLE AKSI VERIFIKASI =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $bayarId = filter_var($_POST['bayar_id'] ?? 0, FILTER_VALIDATE_INT);
    $catatan = sanitize($_POST['catatan_admin'] ?? '');

    if (!$bayarId || !in_array($action, ['terima', 'tolak'])) {
        redirectWith('pembayaran.php', 'danger', 'Aksi tidak valid.');
    }

    // Ambil data pembayaran + pemesanan
    $stmt = $pdo->prepare("
        SELECT py.*, p.id AS pesan_id, p.mobil_id, p.status AS status_pesan
        FROM pembayaran py
        JOIN pemesanan p ON py.pemesanan_id = p.id
        WHERE py.id = ? AND py.status = 'menunggu'
    ");
    $stmt->execute([$bayarId]);
    $data = $stmt->fetch();

    if (!$data) {
        redirectWith('pembayaran.php', 'danger', 'Pembayaran tidak ditemukan atau sudah diproses.');
    }

    $pdo->beginTransaction();
    try {
        if ($action === 'terima') {
            // Update pembayaran → diterima
            $stmt = $pdo->prepare("
                UPDATE pembayaran
                SET status='diterima', catatan_admin=?, verified_by=?, verified_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$catatan, $_SESSION['user_id'], $bayarId]);

            // Update pemesanan → aktif
            $stmt = $pdo->prepare("UPDATE pemesanan SET status='aktif' WHERE id=?");
            $stmt->execute([$data['pesan_id']]);

            // Update mobil → disewa
            $stmt = $pdo->prepare("UPDATE mobil SET status='disewa' WHERE id=?");
            $stmt->execute([$data['mobil_id']]);

            $pdo->commit();
            redirectWith('pembayaran.php', 'success', 'Pembayaran berhasil DITERIMA. Status pemesanan diubah ke Aktif.');

        } else {
            // Tolak: update pembayaran → ditolak
            $stmt = $pdo->prepare("
                UPDATE pembayaran
                SET status='ditolak', catatan_admin=?, verified_by=?, verified_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$catatan, $_SESSION['user_id'], $bayarId]);

            // Kembalikan status pemesanan → menunggu_pembayaran
            $stmt = $pdo->prepare("UPDATE pemesanan SET status='menunggu_pembayaran' WHERE id=?");
            $stmt->execute([$data['pesan_id']]);

            $pdo->commit();
            redirectWith('pembayaran.php', 'warning', 'Pembayaran DITOLAK. Customer perlu upload ulang bukti bayar.');
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('[VerifikasiPembayaran Error] ' . $e->getMessage());
        redirectWith('pembayaran.php', 'danger', 'Gagal memproses verifikasi. Silakan coba lagi.');
    }
}

// ===== AMBIL DATA PEMBAYARAN =====
$filterStatus = $_GET['status'] ?? 'menunggu';
$allowedStatus = ['menunggu', 'diterima', 'ditolak'];
if (!in_array($filterStatus, $allowedStatus)) $filterStatus = 'menunggu';

$stmt = $pdo->prepare("
    SELECT py.*,
           p.kode_pesan, p.tanggal_sewa, p.tanggal_kembali, p.lama_sewa, p.total_biaya,
           u.nama_lengkap, u.no_telepon,
           m.nama_mobil, m.tipe,
           a.nama_lengkap AS nama_admin
    FROM pembayaran py
    JOIN pemesanan p ON py.pemesanan_id = p.id
    JOIN users u ON p.customer_id = u.id
    JOIN mobil m ON p.mobil_id = m.id
    LEFT JOIN users a ON py.verified_by = a.id
    WHERE py.status = ?
    ORDER BY py.created_at DESC
");
$stmt->execute([$filterStatus]);
$pembayaranList = $stmt->fetchAll();

$page_title = 'Verifikasi Pembayaran | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-credit-card me-2"></i>Verifikasi Pembayaran
            </h4>
            <p class="text-muted small mb-0">Konfirmasi penerimaan atau penolakan bukti bayar</p>
        </div>
    </div>

    <!-- Filter Tab -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex gap-2">
                <a href="pembayaran.php?status=menunggu"
                   class="btn btn-sm <?= $filterStatus === 'menunggu' ? 'btn-warning' : 'btn-outline-warning' ?>">
                   <i class="bi bi-hourglass me-1"></i>Menunggu
                </a>
                <a href="pembayaran.php?status=diterima"
                   class="btn btn-sm <?= $filterStatus === 'diterima' ? 'btn-success' : 'btn-outline-success' ?>">
                   <i class="bi bi-check-circle me-1"></i>Diterima
                </a>
                <a href="pembayaran.php?status=ditolak"
                   class="btn btn-sm <?= $filterStatus === 'ditolak' ? 'btn-danger' : 'btn-outline-danger' ?>">
                   <i class="bi bi-x-circle me-1"></i>Ditolak
                </a>
            </div>
        </div>
    </div>

    <!-- List Pembayaran -->
    <?php if (empty($pembayaranList)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem; opacity:0.3;"></i>
                <p class="mt-3 mb-0">Tidak ada pembayaran dengan status ini</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($pembayaranList as $p): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center"
                 style="background-color: var(--color-primary); color: white;">
                <span class="fw-semibold">
                    <i class="bi bi-receipt me-2"></i><?= htmlspecialchars($p['kode_pesan']) ?>
                    — <?= htmlspecialchars($p['nama_lengkap']) ?>
                </span>
                <?= getStatusBadge($p['status']) ?>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Info Pembayaran -->
                    <div class="col-md-5">
                        <div class="mb-2">
                            <span class="text-muted small">Mobil:</span>
                            <strong class="ms-1"><?= htmlspecialchars($p['nama_mobil']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Customer:</span>
                            <span class="ms-1"><?= htmlspecialchars($p['nama_lengkap']) ?>
                                (<?= htmlspecialchars($p['no_telepon'] ?? '-') ?>)
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Sewa:</span>
                            <span class="ms-1"><?= date('d M Y', strtotime($p['tanggal_sewa'])) ?>
                                — <?= date('d M Y', strtotime($p['tanggal_kembali'])) ?>
                                (<?= $p['lama_sewa'] ?> hari)
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Total Biaya:</span>
                            <strong class="ms-1 text-rupiah"><?= formatRupiah($p['total_biaya']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Metode:</span>
                            <span class="ms-1"><?= htmlspecialchars(str_replace('_', ' ', ucwords($p['metode_pembayaran'], '_'))) ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Jumlah Dibayar:</span>
                            <strong class="ms-1 text-rupiah"><?= formatRupiah($p['jumlah_bayar']) ?></strong>
                        </div>
                        <div class="mb-0">
                            <span class="text-muted small">Diunggah:</span>
                            <span class="ms-1"><?= date('d M Y H:i', strtotime($p['created_at'])) ?></span>
                        </div>
                        <?php if ($p['verified_at'] && $p['nama_admin']): ?>
                        <div class="mt-2 p-2 rounded small" style="background:#f8f9fa; border:1px solid var(--color-border);">
                            <i class="bi bi-person-check me-1"></i>
                            Diverifikasi oleh <strong><?= htmlspecialchars($p['nama_admin']) ?></strong>
                            pada <?= date('d M Y H:i', strtotime($p['verified_at'])) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($p['catatan_admin']): ?>
                        <div class="mt-2 p-2 rounded small" style="background:#fff3cd; border:1px solid #ffc107;">
                            <i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($p['catatan_admin']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bukti Pembayaran -->
                    <div class="col-md-4 text-center">
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-2"
                               style="font-size:0.7rem; letter-spacing:0.05em;">Bukti Pembayaran</label>
                        <?php
                        $ext = strtolower(pathinfo($p['bukti_pembayaran'], PATHINFO_EXTENSION));
                        $buktiUrl = '/rental-mobil/assets/uploads/bukti-bayar/' . htmlspecialchars($p['bukti_pembayaran']);
                        ?>
                        <?php if ($ext === 'pdf'): ?>
                            <a href="<?= $buktiUrl ?>" target="_blank" class="btn btn-outline-danger">
                                <i class="bi bi-file-pdf me-1"></i>Lihat PDF
                            </a>
                        <?php else: ?>
                            <a href="<?= $buktiUrl ?>" target="_blank" title="Klik untuk zoom">
                                <img src="<?= $buktiUrl ?>" alt="Bukti Bayar"
                                     style="max-width:100%; max-height:220px; border-radius:8px;
                                            border:2px solid var(--color-border); cursor:pointer;">
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Aksi (hanya jika masih menunggu) -->
                    <?php if ($p['status'] === 'menunggu'): ?>
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="w-100">
                            <!-- Form Terima -->
                            <form action="pembayaran.php" method="POST" class="mb-2"
                                  onsubmit="return confirm('Terima pembayaran ini? Status rental akan diubah ke AKTIF.')">
                                <input type="hidden" name="action" value="terima">
                                <input type="hidden" name="bayar_id" value="<?= $p['id'] ?>">
                                <input type="text" class="form-control form-control-sm mb-2"
                                       name="catatan_admin" placeholder="Catatan (opsional)">
                                <button type="submit" class="btn btn-success btn-sm w-100 fw-semibold">
                                    <i class="bi bi-check-circle me-1"></i>TERIMA
                                </button>
                            </form>
                            <!-- Form Tolak -->
                            <form action="pembayaran.php" method="POST"
                                  onsubmit="return confirm('Tolak pembayaran ini? Customer perlu upload ulang.')">
                                <input type="hidden" name="action" value="tolak">
                                <input type="hidden" name="bayar_id" value="<?= $p['id'] ?>">
                                <input type="text" class="form-control form-control-sm mb-2"
                                       name="catatan_admin" placeholder="Alasan penolakan *" required>
                                <button type="submit" class="btn btn-danger btn-sm w-100 fw-semibold">
                                    <i class="bi bi-x-circle me-1"></i>TOLAK
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
