<?php
// includes/functions.php
// Helper Functions Global — Rental Mobil PT. Wildan Abadi Jaya

/**
 * Format angka ke format Rupiah Indonesia
 * Contoh: 350000 → "Rp 350.000"
 */
function formatRupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Sanitize input string dari user
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate kode pemesanan unik
 * Format: RNT-YYYYMMDD-XXXX
 */
function generateKodePesan(): string {
    return 'RNT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Upload file ke folder tertentu
 * @param  array  $file    $_FILES['field_name']
 * @param  string $folder  'mobil' atau 'bukti-bayar'
 * @param  array  $allowed Ekstensi yang diizinkan
 * @return string|false    Nama file baru atau false jika gagal
 */
function uploadFile(array $file, string $folder, array $allowed = ['jpg','jpeg','png']): string|false {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > 2097152) return false; // Max 2MB

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;

    // Check MIME type to ensure valid image
    $mime = mime_content_type($file['tmp_name']);
    $validMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf'
    ];
    
    // Check if the extension corresponds to the actual MIME type
    if (isset($validMimes[$ext]) && $mime !== $validMimes[$ext]) {
        return false;
    }

    $filename  = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/uploads/' . $folder . '/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    return false;
}

/**
 * Return HTML badge Bootstrap berdasarkan status
 */
function getStatusBadge(string $status): string {
    $map = [
        'tersedia'            => ['success',   'Tersedia'],
        'disewa'              => ['warning',   'Disewa'],
        'servis'              => ['secondary', 'Servis'],
        'menunggu_pembayaran' => ['warning',   'Menunggu Pembayaran'],
        'menunggu_konfirmasi' => ['info',      'Menunggu Konfirmasi'],
        'aktif'               => ['primary',   'Aktif'],
        'selesai'             => ['success',   'Selesai'],
        'dibatalkan'          => ['danger',    'Dibatalkan'],
        'menunggu'            => ['warning',   'Menunggu'],
        'diterima'            => ['success',   'Diterima'],
        'ditolak'             => ['danger',    'Ditolak'],
        'baik'                => ['success',   'Baik'],
        'rusak_ringan'        => ['warning',   'Rusak Ringan'],
        'rusak_berat'         => ['danger',    'Rusak Berat'],
    ];
    [$color, $label] = $map[$status] ?? ['secondary', ucfirst($status)];
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

/**
 * Hitung denda keterlambatan pengembalian
 * @param  string $tglRencana   tanggal kembali rencana (Y-m-d)
 * @param  string $tglAktual    tanggal kembali aktual (Y-m-d)
 * @param  float  $hargaPerHari harga sewa per hari
 * @return float  total denda (0 jika tidak terlambat)
 */
function hitungDenda(string $tglRencana, string $tglAktual, float $hargaPerHari): float {
    $rencana = new DateTime($tglRencana);
    $aktual  = new DateTime($tglAktual);
    if ($aktual <= $rencana) {
        return 0.0;
    }
    $selisih = $aktual->diff($rencana)->days;
    // Denda: 10% dari harga per hari × jumlah hari terlambat
    return $selisih * ($hargaPerHari * 0.1);
}

/**
 * Redirect dengan flash message
 */
function redirectWith(string $url, string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: ' . $url);
    exit;
}
