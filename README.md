# 🚗 Car Rental System

Sistem informasi manajemen rental mobil berbasis web yang dirancang untuk mengotomatiskan proses reservasi kendaraan, verifikasi transaksi pembayaran, pengelolaan armada, serta pelaporan manajerial secara terstruktur.

---

## 📌 Fitur Utama

Aplikasi ini menggunakan sistem kontrol akses berbasis peran (*Role-Based Access Control*) dengan 3 hak akses:

### 👤 Customer
* **Katalog Armada:** Eksplorasi daftar kendaraan yang tersedia beserta spesifikasi dan tarif sewa.
* **Pemesanan Mandiri:** Pengajuan sewa mobil berdasarkan durasi dan tanggal sewa.
* **Pembayaran & Verifikasi:** Unggah bukti transfer pembayaran langsung melalui aplikasi.
* **Monitoring & Pengembalian:** Cek status reservasi, riwayat sewa, dan konfirmasi pengembalian mobil.

### 🛠️ Administrator
* **Manajemen Armada:** Tambah, perbarui, dan hapus data armada mobil.
* **Manajemen Transaksi & Pembayaran:** Validasi bukti pembayaran pelanggan dan pembaruan status transaksi sewa.
* **Manajemen Pengguna:** Monitoring data customer yang terdaftar di dalam sistem.
* **Laporan Operasional:** Ekspor laporan rekap transaksi rental.

### 👔 Direktur
* **Dashboard Eksekutif:** Monitoring ringkasan performa bisnis dan aktivitas operasional.
* **Laporan PDF:** Cetak dan unduh laporan transaksi rental terintegrasi mPDF.

---

## 💻 Tech Stack

* **Backend:** PHP
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript
* **Library / Package:** [mPDF](https://github.com/mpdf/mpdf) (Automated PDF Report Generation) via Composer

---

## 📁 Struktur Direktori

```text
├── admin/          # Panel kontrol, manajemen armada, & transaksi admin
├── assets/         # Aset statis (CSS, JS, gambar armada, upload bukti transfer)
├── auth/           # Modul autentikasi (login, register, logout)
├── config/         # Konfigurasi koneksi basis data (db.php)
├── customer/       # Antarmuka katalog, booking, dan pembayaran customer
├── direktur/       # Panel monitoring eksekutif & ekspor laporan
├── includes/       # Layout reusable (header, footer, navbar, auth check)
├── vendor/         # Dependensi Composer (mPDF)
├── rental_mobil.sql# Skema & data awal basis data
└── index.php       # Landing page utama
