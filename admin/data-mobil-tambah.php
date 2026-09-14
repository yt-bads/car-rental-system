<?php
// admin/data-mobil-tambah.php
// Form Tambah Mobil Baru — Rental Mobil PT. Wildan Abadi Jaya

require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
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
    $status       = in_array($_POST['status'] ?? '', ['tersedia', 'servis']) ? $_POST['status'] : 'tersedia';

    $old = compact('namaMobil', 'tipe', 'tahun', 'kapasitas', 'warna', 'platNomor', 'hargaPerHari', 'deskripsi', 'status');

    // Validasi wajib
    if (empty($namaMobil)) $errors[] = 'Nama mobil wajib diisi.';
    if (empty($tipe))      $errors[] = 'Tipe mobil wajib diisi.';
    if ($hargaPerHari === false || $hargaPerHari <= 0) $errors[] = 'Harga per hari harus angka positif.';

    // Cek plat nomor unik (jika diisi)
    if (!empty($platNomor)) {
        $cekPlat = $pdo->prepare("SELECT id FROM mobil WHERE plat_nomor = ?");
        $cekPlat->execute([$platNomor]);
        if ($cekPlat->rowCount() > 0) {
            $errors[] = 'Plat nomor sudah terdaftar.';
        }
    }

    // Upload foto (opsional)
    $fotoFilename = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fotoFilename = uploadFile($_FILES['foto'], 'mobil', ['jpg', 'jpeg', 'png', 'webp']);
        if ($fotoFilename === false) {
            $errors[] = 'Upload foto gagal. Pastikan format JPG/PNG/WEBP dan ukuran maks 2MB.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO mobil
                    (nama_mobil, tipe, tahun, kapasitas, warna, plat_nomor, harga_per_hari, deskripsi, foto, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $namaMobil, $tipe,
                ($tahun !== false ? $tahun : null),
                ($kapasitas !== false ? $kapasitas : null),
                $warna, $platNomor, $hargaPerHari, $deskripsi,
                $fotoFilename, $status
            ]);
            redirectWith('data-mobil.php', 'success', 'Mobil baru berhasil ditambahkan.');
        } catch (PDOException $e) {
            error_log('[TambahMobil Error] ' . $e->getMessage());
            $errors[] = 'Terjadi kesalahan sistem, silakan coba lagi.';
        }
    }
}

$page_title = 'Tambah Mobil | Admin';
$extra_css  = 'admin.css';
require_once '../includes/header.php';
require_once '../includes/navbar-admin.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
                <i class="bi bi-plus-circle me-2"></i>Tambah Mobil Baru
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="data-mobil.php">Data Mobil</a></li>
                    <li class="breadcrumb-item active">Tambah Mobil</li>
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

    <!-- Form Card -->
    <div class="card shadow-sm">
        <div class="card-header px-4 py-3"
             style="background-color: var(--color-primary); color: white;">
            <span class="fw-semibold">
                <i class="bi bi-car-front me-2"></i>Informasi Kendaraan
            </span>
        </div>
        <div class="card-body p-4">
            <form action="data-mobil-tambah.php" method="POST" enctype="multipart/form-data">

                <div class="row g-3">
                    <!-- Nama Mobil -->
                    <div class="col-md-6">
                        <label for="nama_mobil" class="form-label">
                            Nama Mobil <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nama_mobil" name="nama_mobil"
                               value="<?= htmlspecialchars($old['namaMobil'] ?? '') ?>"
                               placeholder="cth: Toyota Avanza" required>
                    </div>

                    <!-- Tipe -->
                    <div class="col-md-6">
                        <label for="tipe" class="form-label">
                            Tipe Kendaraan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="tipe" name="tipe" required>
                            <option value="">-- Pilih Tipe --</option>
                            <?php
                            $tipes = ['MPV', 'SUV', 'City Car', 'Sedan', 'Pickup', 'Minibus', 'Sport'];
                            foreach ($tipes as $t):
                                $sel = ($old['tipe'] ?? '') === $t ? 'selected' : '';
                            ?>
                                <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tahun -->
                    <div class="col-md-4">
                        <label for="tahun" class="form-label">Tahun Produksi</label>
                        <input type="number" class="form-control" id="tahun" name="tahun"
                               value="<?= htmlspecialchars($old['tahun'] ?? date('Y')) ?>"
                               min="1990" max="<?= date('Y') + 1 ?>">
                    </div>

                    <!-- Kapasitas -->
                    <div class="col-md-4">
                        <label for="kapasitas" class="form-label">Kapasitas (Penumpang)</label>
                        <input type="number" class="form-control" id="kapasitas" name="kapasitas"
                               value="<?= htmlspecialchars($old['kapasitas'] ?? '') ?>"
                               min="1" max="20" placeholder="cth: 7">
                    </div>

                    <!-- Warna -->
                    <div class="col-md-4">
                        <label for="warna" class="form-label">Warna</label>
                        <input type="text" class="form-control" id="warna" name="warna"
                               value="<?= htmlspecialchars($old['warna'] ?? '') ?>"
                               placeholder="cth: Putih">
                    </div>

                    <!-- Plat Nomor -->
                    <div class="col-md-6">
                        <label for="plat_nomor" class="form-label">Plat Nomor</label>
                        <input type="text" class="form-control" id="plat_nomor" name="plat_nomor"
                               value="<?= htmlspecialchars($old['platNomor'] ?? '') ?>"
                               placeholder="cth: B 1234 ABC"
                               style="text-transform: uppercase;">
                    </div>

                    <!-- Harga per Hari -->
                    <div class="col-md-6">
                        <label for="harga_per_hari" class="form-label">
                            Harga per Hari (Rp) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="harga_per_hari" name="harga_per_hari"
                                   value="<?= htmlspecialchars($old['hargaPerHari'] ?? '') ?>"
                                   min="1000" step="1000" placeholder="cth: 350000" required>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status Awal</label>
                        <select class="form-select" id="status" name="status">
                            <option value="tersedia" <?= ($old['status'] ?? 'tersedia') === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="servis"   <?= ($old['status'] ?? '') === 'servis' ? 'selected' : '' ?>>Servis</option>
                        </select>
                    </div>

                    <!-- Foto Mobil -->
                    <div class="col-md-8">
                        <label for="foto" class="form-label">Foto Mobil</label>
                        <input type="file" class="form-control" id="foto" name="foto"
                               accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">Format: JPG, JPEG, PNG, WEBP. Maks 2MB.</div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="col-12">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi"
                                  rows="3" placeholder="Deskripsi singkat tentang kendaraan ini..."><?= htmlspecialchars($old['deskripsi'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-floppy me-1"></i> Simpan Mobil
                    </button>
                    <a href="data-mobil.php" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x me-1"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
