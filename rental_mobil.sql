-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Jul 2026 pada 17.11
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_mobil`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `mobil`
--

CREATE TABLE `mobil` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_mobil` varchar(100) NOT NULL,
  `tipe` varchar(50) NOT NULL,
  `tahun` year(4) DEFAULT NULL,
  `kapasitas` tinyint(4) DEFAULT NULL COMMENT 'jumlah penumpang',
  `warna` varchar(50) DEFAULT NULL,
  `plat_nomor` varchar(20) DEFAULT NULL,
  `harga_per_hari` decimal(12,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('tersedia','disewa','servis') NOT NULL DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `mobil`
--

INSERT INTO `mobil` (`id`, `nama_mobil`, `tipe`, `tahun`, `kapasitas`, `warna`, `plat_nomor`, `harga_per_hari`, `deskripsi`, `foto`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Toyota Avanza', 'MPV', '2022', 7, 'Putih', 'B 1234 ABC', 350000.00, 'Mobil keluarga nyaman dan irit bahan bakar.', NULL, 'tersedia', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(2, 'Kijang Innova', 'MPV', '2023', 8, 'Silver', 'B 5678 DEF', 550000.00, 'Mobil premium keluarga dengan ruang kabin luas.', NULL, 'tersedia', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(3, 'Daihatsu Xenia', 'MPV', '2021', 7, 'Hitam', 'B 9012 GHI', 300000.00, 'Mobil irit dan lincah untuk kota.', '1782483910_e1095964.png', 'tersedia', '2026-05-23 15:29:04', '2026-06-26 15:34:54'),
(4, 'Honda Brio', 'City Car', '2022', 4, 'Merah', 'B 3456 JKL', 250000.00, 'City car gesit dan mudah parkir.', NULL, 'tersedia', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(5, 'Mitsubishi Pajero', 'SUV', '2022', 7, 'Hitam', 'B 7890 MNO', 850000.00, 'SUV tangguh untuk berbagai medan.', NULL, 'disewa', '2026-05-23 15:29:04', '2026-06-27 11:48:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(10) UNSIGNED NOT NULL,
  `pemesanan_id` int(10) UNSIGNED NOT NULL,
  `jumlah_bayar` decimal(14,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL COMMENT 'transfer_bca, transfer_bri, dll',
  `bukti_pembayaran` varchar(255) NOT NULL COMMENT 'path file upload',
  `status` enum('menunggu','diterima','ditolak') NOT NULL DEFAULT 'menunggu',
  `catatan_admin` text DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'user_id admin yang verifikasi',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `pemesanan_id`, `jumlah_bayar`, `metode_pembayaran`, `bukti_pembayaran`, `status`, `catatan_admin`, `verified_by`, `verified_at`, `created_at`) VALUES
(2, 2, 600000.00, 'transfer_bca', '1782379140_29410f5d.png', 'diterima', NULL, 1, '2026-06-25 09:21:32', '2026-06-25 09:19:00'),
(3, 3, 550000.00, 'transfer_mandiri', '1782394396_ab2bf200.jpg', 'ditolak', 'tanggal sudah terlewat', 1, '2026-06-27 11:48:21', '2026-06-25 13:33:16'),
(4, 4, 850000.00, 'transfer_bca', '1782489085_c75401fb.jpg', 'diterima', '', 1, '2026-06-27 11:48:39', '2026-06-26 15:51:25'),
(5, 5, 500000.00, 'transfer_mandiri', '1782555631_fd7d2e15.jpg', 'ditolak', 'unit belum sempat dicuci', 1, '2026-06-27 11:49:00', '2026-06-27 10:20:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pemesanan`
--

CREATE TABLE `pemesanan` (
  `id` int(10) UNSIGNED NOT NULL,
  `kode_pesan` varchar(25) NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `mobil_id` int(10) UNSIGNED NOT NULL,
  `tanggal_sewa` date NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `lama_sewa` tinyint(4) NOT NULL COMMENT 'dalam hari',
  `total_biaya` decimal(14,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('menunggu_pembayaran','menunggu_konfirmasi','aktif','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pemesanan`
--

INSERT INTO `pemesanan` (`id`, `kode_pesan`, `customer_id`, `mobil_id`, `tanggal_sewa`, `tanggal_kembali`, `lama_sewa`, `total_biaya`, `catatan`, `status`, `created_at`, `updated_at`) VALUES
(2, 'RNT-20260625-C654', 7, 3, '2026-06-26', '2026-06-28', 2, 600000.00, '', 'selesai', '2026-06-25 09:18:28', '2026-06-25 09:21:32'),
(3, 'RNT-20260625-DC77', 7, 2, '2026-06-25', '2026-06-26', 1, 550000.00, 'Pasang pewangi mobil aroma lavender', 'menunggu_pembayaran', '2026-06-25 13:31:51', '2026-06-27 11:48:21'),
(4, 'RNT-20260626-EE1B', 7, 5, '2026-07-04', '2026-07-05', 1, 850000.00, '', 'aktif', '2026-06-26 15:51:09', '2026-06-27 11:48:39'),
(5, 'RNT-20260627-CA65', 8, 4, '2026-06-27', '2026-06-29', 2, 500000.00, '', 'menunggu_pembayaran', '2026-06-27 10:19:58', '2026-06-27 11:49:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(10) UNSIGNED NOT NULL,
  `pemesanan_id` int(10) UNSIGNED NOT NULL,
  `tanggal_kembali_aktual` date NOT NULL,
  `kondisi_mobil` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `denda` decimal(12,2) NOT NULL DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'admin yang proses (jika via admin)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `pemesanan_id`, `tanggal_kembali_aktual`, `kondisi_mobil`, `denda`, `keterangan`, `processed_by`, `created_at`) VALUES
(2, 2, '2026-06-28', 'baik', 0.00, 'Mobil dikembalikan tepat waktu dalam kondisi baik via E2E Script.', NULL, '2026-06-25 09:21:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `role` enum('admin','customer','direktur') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama_lengkap`, `email`, `password`, `no_telepon`, `alamat`, `foto_profil`, `role`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@rental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', NULL, NULL, 'admin', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(2, 'Bendi Setia Budi', 'direktur@rental.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081999888777', NULL, NULL, 'direktur', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(3, 'Arjuna Wijaya', 'arjuna@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '089637657235', NULL, NULL, 'customer', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(4, 'Siti Rahayu', 'siti@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '082111222333', NULL, NULL, 'customer', '2026-05-23 15:29:04', '2026-05-23 15:29:04'),
(7, 'Budi Tester', 'tester@gmail.com', '$2y$10$K2kPPVtzXNtehurrnVzdMOSJksdI0qKNwfqCvlarnVdoeRqdvwWki', '08123456789', 'Jl. Tester No. 123', NULL, 'customer', '2026-06-25 09:16:40', '2026-06-25 09:16:40'),
(8, 'Fikri', 'fikri@tester.com', '$2y$10$EpF76XxqdaYAH9uDmsmyQuF5jYelPBt3cWULK3TQwaz0H6/Cbr0MW', '08987654321', 'Jl. Sumur pecung, No.77', NULL, 'customer', '2026-06-26 15:53:48', '2026-06-26 15:53:48');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `mobil`
--
ALTER TABLE `mobil`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bayar_pesan` (`pemesanan_id`);

--
-- Indeks untuk tabel `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kode_pesan` (`kode_pesan`),
  ADD KEY `fk_pesan_customer` (`customer_id`),
  ADD KEY `fk_pesan_mobil` (`mobil_id`);

--
-- Indeks untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pengembalian_pesan` (`pemesanan_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `mobil`
--
ALTER TABLE `mobil`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pemesanan`
--
ALTER TABLE `pemesanan`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_bayar_pesan` FOREIGN KEY (`pemesanan_id`) REFERENCES `pemesanan` (`id`);

--
-- Ketidakleluasaan untuk tabel `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD CONSTRAINT `fk_pesan_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_pesan_mobil` FOREIGN KEY (`mobil_id`) REFERENCES `mobil` (`id`);

--
-- Ketidakleluasaan untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `fk_kembali_pesan` FOREIGN KEY (`pemesanan_id`) REFERENCES `pemesanan` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
