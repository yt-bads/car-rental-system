<?php
// admin/data-mobil-edit.php
// Form Edit Data Mobil — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Ambil data mobil berdasarkan ID dari GET
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$id) {
    redirectWith('data-mobil.php', 'danger', 'ID mobil tidak valid.');
}

$stmt = $pdo->prepare("SELECT * FROM mobil WHERE id = ?");
$stmt->execute([$id]);
$mobil = $stmt->fetch();

if (!$mobil) {
    redirectWith('data-mobil.php', 'danger', 'Mobil tidak ditemukan.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaMobil    = sanitize($_POST['nama_mobil'] ?? '');
    $tipe         = sanitize($_POST['tipe'] ?? '');
    $tahun        = filter_var($_POST['tahun'] ?? '', FILTER_VALIDATE_INT,
                               ['options' => ['min_range' => 1990, 'max_range' => (int)date('Y') + 1]]);
    $kapasitas    = filter_var($_POST['kapasitas'] ?? '', FILTER_VALIDATE_INT,
                               ['options' => ['min_range' => 1, 'max_range' => 20]]);
    $warna        = sanitize($_POST['warna'] ?? '');
    $platNomor    = sanitize($_POST['plat_nomor'] ?? '');
    $hargaPerHari = filter_var($_POST['harga_per_hari'] ?? 0, FILTER_VALIDATE_FLOAT);
    $deskripsi    = sanitize($_POST['deskripsi'] ?? '');
    $status       = in_array($_POST['status'] ?? '', ['tersedia', 'disewa', 'servis']) ? $_POST['status'] : $mobil['status'];

    // Validasi wajib
    if (empty($namaMobil)) $errors[] = 'Nama mobil wajib diisi.';
    if (empty($tipe))      $errors[] = 'Tipe mobil wajib diisi.';
    if ($hargaPerHari === false || $hargaPerHari <= 0) $errors[] = 'Harga per hari harus angka positif.';

    // Cek plat nomor unik (kecuali milik sendiri)
    if (!empty($platNomor)) {
        $cekPlat = $pdo->prepare("SELECT id FROM mobil WHERE plat_nomor = ? AND id != ?");
        $cekPlat->execute([$platNomor, $id]);
        if ($cekPlat->rowCount() > 0) {
            $errors[] = 'Plat nomor sudah digunakan oleh mobil lain.';
        }
    }

    // Upload foto baru (opsional — hanya jika ada file yang di-upload)
    $fotoFilename = $mobil['foto']; // default: tetap foto lama
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = uploadFile($_FILES['foto'], 'mobil', ['jpg', 'jpeg', 'png', 'webp']);
        if ($uploaded === false) {
            $errors[] = 'Upload foto gagal. Pastikan format JPG/PNG/WEBP dan ukuran maks 2MB.';
        } else {
            // Hapus foto lama jika ada
            if ($mobil['foto']) {
                $oldPath = __DIR__ . '/../assets/uploads/mobil/' . $mobil['foto'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $fotoFilename = $uploaded;
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE mobil
                SET nama_mobil=?, tipe=?, tahun=?, kapasitas=?, warna=?,
                    plat_nomor=?, harga_per_hari=?, deskripsi=?, foto=?, status=?
                WHERE id=?
            ");
            $stmt->execute([
                $namaMobil, $tipe,
                ($tahun !== false ? $tahun : null),
                ($kapasitas !== false ? $kapasitas : null),
                $warna, $platNomor, $hargaPerHari, $deskripsi,
                $fotoFilename, $status, $id
            ]);
            redirectWith('data-mobil.php', 'success', 'Data mobil berhasil diperbarui.');
        } catch (PDOException $e) {
            error_log('[EditMobil Error] ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem, silakan coba lagi.';
        }
    }

    // Isi ulang $mobil dengan data POST untuk tampilan error
    $mobil = array_merge($mobil, [
        'nama_mobil'    => $namaMobil,
        'tipe'          => $tipe,
        'tahun'         => $tahun,
        'kapasitas'     => $kapasitas,
        'warna'         => $warna,
        'plat_nomor'    => $platNomor,
        'harga_per_hari' => $hargaPerHari,
        'deskripsi'     => $deskripsi,
        'status'        => $status,
    ]);
}

$page_title = 'Edit Mobil | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-pencil-square me-2"></i>Edit Data Mobil
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="data-mobil.php">Data Mobil</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="data-mobil.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Periksa kembali form:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Foto Preview -->
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <?php if ($mobil['foto']): ?>
                    <img src="/rental-mobil/assets/uploads/mobil/<?= htmlspecialchars($mobil['foto']) ?>"
                         alt="Foto Mobil"
                         id="fotoPreview"
                         style="width:100%; height:160px; object-fit:cover; border-radius:8px;">
                <?php else: ?>
                    <div id="fotoPreview"
                         style="width:100%; height:160px; border-radius:8px; background:#e0e0e0;
                                display:flex; align-items:center; justify-content:center;
                                font-size:3rem; color:#aaa;">
                        <i class="bi bi-image"></i>
                    </div>
                <?php endif; ?>
                <p class="text-muted small mt-2 mb-0">Foto Saat Ini</p>
            </div>
        </div>

        <!-- Form -->
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header px-4 py-3"
                     style="background-color: var(--color-primary); color: white;">
                    <span class="fw-semibold">
                        <i class="bi bi-pencil me-2"></i>Edit: <?= htmlspecialchars($mobil['nama_mobil']) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <form action="data-mobil-edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama_mobil" class="form-label">
                                    Nama Mobil <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nama_mobil" name="nama_mobil"
                                       value="<?= htmlspecialchars($mobil['nama_mobil']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="tipe" class="form-label">
                                    Tipe Kendaraan <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="tipe" name="tipe" required>
                                    <?php
                                    $tipes = ['MPV', 'SUV', 'City Car', 'Sedan', 'Pickup', 'Minibus', 'Sport'];
                                    foreach ($tipes as $t):
                                        $sel = $mobil['tipe'] === $t ? 'selected' : '';
                                    ?>
                                        <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="tahun" class="form-label">Tahun Produksi</label>
                                <input type="number" class="form-control" id="tahun" name="tahun"
                                       value="<?= htmlspecialchars($mobil['tahun'] ?? '') ?>"
                                       min="1990" max="<?= date('Y') + 1 ?>">
                            </div>

                            <div class="col-md-4">
                                <label for="kapasitas" class="form-label">Kapasitas</label>
                                <input type="number" class="form-control" id="kapasitas" name="kapasitas"
                                       value="<?= htmlspecialchars($mobil['kapasitas'] ?? '') ?>"
                                       min="1" max="20">
                            </div>

                            <div class="col-md-4">
                                <label for="warna" class="form-label">Warna</label>
                                <input type="text" class="form-control" id="warna" name="warna"
                                       value="<?= htmlspecialchars($mobil['warna'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="plat_nomor" class="form-label">Plat Nomor</label>
                                <input type="text" class="form-control" id="plat_nomor" name="plat_nomor"
                                       value="<?= htmlspecialchars($mobil['plat_nomor'] ?? '') ?>"
                                       style="text-transform: uppercase;">
                            </div>

                            <div class="col-md-6">
                                <label for="harga_per_hari" class="form-label">
                                    Harga per Hari (Rp) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="harga_per_hari" name="harga_per_hari"
                                           value="<?= htmlspecialchars($mobil['harga_per_hari']) ?>"
                                           min="1000" step="1000" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="tersedia" <?= $mobil['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="disewa"   <?= $mobil['status'] === 'disewa'   ? 'selected' : '' ?>>Disewa</option>
                                    <option value="servis"   <?= $mobil['status'] === 'servis'   ? 'selected' : '' ?>>Servis</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="foto" class="form-label">Ganti Foto</label>
                                <input type="file" class="form-control" id="foto" name="foto"
                                       accept=".jpg,.jpeg,.png,.webp"
                                       onchange="previewFoto(this)">
                                <div class="form-text">Kosongkan jika tidak ingin mengganti foto.</div>
                            </div>

                            <div class="col-12">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi"
                                          rows="3"><?= htmlspecialchars($mobil['deskripsi'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-floppy me-1"></i> Simpan Perubahan
                            </button>
                            <a href="data-mobil.php" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-x me-1"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewFoto(input) {
    'use strict';
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('fotoPreview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.id  = 'fotoPreview';
                img.style = 'width:100%; height:160px; object-fit:cover; border-radius:8px;';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
