# TEST_MANUAL.md
# Checklist Testing Manual — Rental Mobil PT. Wildan Abadi Jaya

> Jalankan semua langkah secara berurutan di browser.
> Pastikan Apache & MySQL sudah aktif di XAMPP sebelum memulai.
> Format: `[ ]` = belum ditest, `[x]` = sudah lulus

---

## PERSIAPAN

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 0.1 | `http://localhost/rental-mobil/setup-db.php` | Buka URL di browser | Muncul pesan `[OK]` untuk setiap tabel dan seed data. Tidak ada `[ERROR]`. | [ ] |
| 0.2 | `http://localhost/rental-mobil/` | Buka URL di browser | Redirect otomatis ke `auth/login.php` karena belum login. | [ ] |

---

## FLOW 1 — REGISTRASI CUSTOMER BARU

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 1.1 | `http://localhost/rental-mobil/auth/register.php` | Buka halaman register | Tampil form registrasi dengan field: Nama Lengkap, Email, Password, Konfirmasi Password, No. Telepon, Alamat. | [ ] |
| 1.2 | — | Klik tombol **Daftar** tanpa mengisi apapun | Browser menampilkan validasi HTML5 "Please fill out this field" pada field pertama yang kosong. | [ ] |
| 1.3 | — | Isi form: **Nama**: `Budi Tester`, **Email**: `tester@gmail.com`, **Password**: `password`, **Konfirmasi**: `password`, **Telepon**: `08123456789`, **Alamat**: `Jl. Testing No. 1` → klik **Daftar** | Redirect ke `auth/login.php` dengan alert hijau: "Registrasi berhasil! Silakan login." | [ ] |
| 1.4 | `http://localhost/rental-mobil/auth/register.php` | Daftar lagi dengan email yang sama `tester@gmail.com` | Muncul alert merah: "Email sudah terdaftar." Form tidak diproses. | [ ] |

---

## FLOW 2 — LOGIN & REDIRECT PER ROLE

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 2.1 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `tester@gmail.com`, **Password**: `password` | Redirect ke `customer/dashboard.php`. Navbar menampilkan nama "Budi Tester". | [ ] |
| 2.2 | — | Klik **Logout** di navbar | Redirect ke `auth/login.php`. Session dihapus. | [ ] |
| 2.3 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `admin@rental.com`, **Password**: `password` | Redirect ke `admin/dashboard.php`. Sidebar admin tampil. | [ ] |
| 2.4 | — | Klik **Logout** di sidebar | Redirect ke `auth/login.php`. | [ ] |
| 2.5 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `direktur@rental.com`, **Password**: `password` | Redirect ke `direktur/laporan.php`. Sidebar direktur tampil. | [ ] |
| 2.6 | — | Klik **Logout** di sidebar | Redirect ke `auth/login.php`. | [ ] |
| 2.7 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `salah@email.com`, **Password**: `salah` | Tetap di halaman login. Muncul alert merah: "Email atau Password salah." | [ ] |

---

## FLOW 3 — SESSION GUARD (AKSES TANPA LOGIN)

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 3.1 | `http://localhost/rental-mobil/admin/dashboard.php` | Buka langsung tanpa login | Redirect ke `auth/login.php`. | [ ] |
| 3.2 | `http://localhost/rental-mobil/customer/dashboard.php` | Buka langsung tanpa login | Redirect ke `auth/login.php`. | [ ] |
| 3.3 | `http://localhost/rental-mobil/direktur/laporan.php` | Buka langsung tanpa login | Redirect ke `auth/login.php`. | [ ] |
| 3.4 | `http://localhost/rental-mobil/customer/pesan-proses.php` | Akses via GET tanpa login | Redirect ke `auth/login.php`. | [ ] |
| 3.5 | `http://localhost/rental-mobil/admin/data-mobil-hapus.php` | Akses via GET tanpa login | Redirect ke `auth/login.php`. | [ ] |

---

## FLOW 4 — CUSTOMER: LIHAT DAFTAR MOBIL

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 4.1 | — | Login sebagai `tester@gmail.com` / `password` | Masuk ke `customer/dashboard.php`. | [ ] |
| 4.2 | `http://localhost/rental-mobil/customer/daftar-mobil.php` | Klik menu **Daftar Mobil** di navbar | Tampil grid kartu mobil. Minimal ada 5 mobil dari seed data (Avanza, Innova, Xenia, Brio, Pajero). Semua berstatus "Tersedia". | [ ] |
| 4.3 | — | Ketik "Brio" di kolom pencarian (jika ada) | Hanya tampil kartu Honda Brio. | [ ] |
| 4.4 | — | Kosongkan pencarian, pilih filter tipe "SUV" (jika ada) | Hanya tampil Mitsubishi Pajero. | [ ] |

---

## FLOW 5 — CUSTOMER: PEMESANAN MOBIL + KALKULASI HARGA

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 5.1 | `http://localhost/rental-mobil/customer/daftar-mobil.php` | Klik tombol **Pesan** pada kartu **Daihatsu Xenia** | Redirect ke `customer/pesan.php?mobil_id=3`. Tampil info mobil: nama, tipe, harga Rp 300.000/hari. | [ ] |
| 5.2 | — | Isi **Tanggal Sewa**: `besok` (misal `2026-06-26`) | Field tanggal kembali menjadi aktif. Minimum tanggal kembali = 1 hari setelah tanggal sewa. | [ ] |
| 5.3 | — | Isi **Tanggal Kembali**: 2 hari setelah tanggal sewa (misal `2026-06-28`) | Otomatis terisi: **Lama Sewa**: `2 hari`, **Total Biaya**: `Rp 600.000`. Kalkulasi realtime tanpa reload halaman. | [ ] |
| 5.4 | — | Ubah **Tanggal Kembali** jadi 3 hari setelah sewa | Total otomatis berubah jadi `Rp 900.000`, Lama Sewa jadi `3 hari`. | [ ] |
| 5.5 | — | Kembalikan **Tanggal Kembali** ke 2 hari setelah sewa. Isi **Catatan**: `Test pemesanan.` → klik **Pesan Sekarang** | Redirect ke `customer/pembayaran.php?pesan_id=X`. Muncul alert hijau: "Pemesanan berhasil dibuat!" | [ ] |

---

## FLOW 6 — CUSTOMER: PEMBAYARAN (UPLOAD BUKTI)

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 6.1 | `http://localhost/rental-mobil/customer/pembayaran.php?pesan_id=X` | Lihat halaman pembayaran | Tampil ringkasan pesanan: mobil Xenia, total Rp 600.000. Form upload bukti pembayaran tersedia. | [ ] |
| 6.2 | — | Klik **Kirim** tanpa mengisi metode dan tanpa upload file | Browser menampilkan validasi HTML5 pada field yang kosong. | [ ] |
| 6.3 | — | Pilih **Metode Pembayaran**: `Transfer BCA`. Upload file gambar (.jpg/.png, max 2MB) sebagai bukti. → klik **Kirim Bukti Pembayaran** | Redirect ke `customer/status-pemesanan.php`. Alert hijau: "Bukti pembayaran berhasil diunggah!" Status pesanan berubah menjadi **Menunggu Konfirmasi** (badge biru). | [ ] |
| 6.4 | — | Coba upload file `.exe` atau file > 2MB | Muncul pesan error: "Gagal mengunggah bukti pembayaran. Pastikan format berkas JPG/PNG/PDF dan ukuran di bawah 2MB." | [ ] |

---

## FLOW 7 — ADMIN: VERIFIKASI PEMBAYARAN

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 7.1 | — | **Logout** dari akun customer | Redirect ke `auth/login.php`. | [ ] |
| 7.2 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `admin@rental.com`, **Password**: `password` | Redirect ke `admin/dashboard.php`. | [ ] |
| 7.3 | `http://localhost/rental-mobil/admin/dashboard.php` | Lihat dashboard admin | Summary cards menunjukkan jumlah transaksi, proses, aktif, selesai, dan pendapatan. Tabel 5 transaksi terbaru menampilkan pesanan Budi Tester. | [ ] |
| 7.4 | `http://localhost/rental-mobil/admin/pembayaran.php` | Klik menu **Pembayaran** di sidebar | Tampil daftar pembayaran. Terlihat entry dari Budi Tester, status **Menunggu** (badge kuning). | [ ] |
| 7.5 | — | Klik **Lihat Bukti** pada pembayaran Budi Tester | Tampil gambar/PDF bukti pembayaran yang diupload customer. | [ ] |
| 7.6 | — | Klik tombol **Terima** pada pembayaran Budi Tester | Muncul konfirmasi. Setelah dikonfirmasi: alert hijau sukses. Status pembayaran berubah jadi **Diterima** (badge hijau). | [ ] |
| 7.7 | `http://localhost/rental-mobil/admin/transaksi.php` | Klik menu **Transaksi** di sidebar | Pesanan Budi Tester sekarang berstatus **Aktif** (badge biru). | [ ] |

---

## FLOW 8 — CUSTOMER: PENGEMBALIAN MOBIL

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 8.1 | — | **Logout** dari akun admin | Redirect ke `auth/login.php`. | [ ] |
| 8.2 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `tester@gmail.com`, **Password**: `password` | Redirect ke `customer/dashboard.php`. | [ ] |
| 8.3 | `http://localhost/rental-mobil/customer/status-pemesanan.php` | Klik menu **Status Pemesanan** / akses langsung | Tampil pesanan Budi Tester dengan status **Aktif**. Tombol **Kembalikan Mobil** tersedia. | [ ] |
| 8.4 | — | Klik tombol **Kembalikan Mobil** | Redirect ke `customer/pengembalian.php?pesan_id=X`. Tampil form: tanggal kembali aktual, kondisi mobil, keterangan. Info denda ditampilkan. | [ ] |
| 8.5 | — | Isi: **Tanggal Kembali**: tanggal hari ini (tepat waktu atau sebelum batas). **Kondisi**: `Baik`. **Keterangan**: `Mobil dikembalikan dalam kondisi baik.` → klik **Proses Pengembalian** | Redirect ke `customer/riwayat.php`. Alert hijau: "Pengembalian mobil berhasil diproses! Mobil dikembalikan tepat waktu dengan kondisi baik." Denda: Rp 0. Status: **Selesai**. | [ ] |
| 8.6 | `http://localhost/rental-mobil/customer/riwayat.php` | Lihat halaman riwayat | Tampil entry rental Daihatsu Xenia, status **Selesai** (badge hijau), total Rp 600.000. | [ ] |

---

## FLOW 9 — DIREKTUR: LIHAT LAPORAN

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 9.1 | — | **Logout** dari akun customer | Redirect ke `auth/login.php`. | [ ] |
| 9.2 | `http://localhost/rental-mobil/auth/login.php` | Login: **Email**: `direktur@rental.com`, **Password**: `password` | Redirect ke `direktur/laporan.php`. | [ ] |
| 9.3 | `http://localhost/rental-mobil/direktur/laporan.php` | Lihat laporan (default: bulan ini) | Tampil tabel rincian transaksi. Terlihat entry Budi Tester — Daihatsu Xenia — Rp 600.000 — Status: Selesai. Summary cards menunjukkan total pendapatan yang mencakup Rp 600.000. | [ ] |
| 9.4 | — | Ubah filter: **Dari**: `2026-06-01`, **Sampai**: `2026-06-30` → klik **Tampilkan** | Data tetap menampilkan transaksi Budi Tester dalam rentang tanggal tersebut. | [ ] |
| 9.5 | — | Ubah filter ke rentang tanggal yang tidak ada transaksi (misal **Dari**: `2025-01-01`, **Sampai**: `2025-01-31`) → klik **Tampilkan** | Tabel kosong atau menampilkan pesan "Tidak ada data transaksi pada periode ini." | [ ] |
| 9.6 | — | Kembalikan filter ke bulan ini. Klik tombol **Cetak Laporan** | Browser membuka dialog print. Preview cetak bersih: sidebar, navbar, tombol, dan filter hilang. Hanya tampil tabel laporan dan header. | [ ] |

---

## FLOW 10 — ADMIN: KELOLA DATA MOBIL (CRUD)

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 10.1 | — | Login sebagai `admin@rental.com` / `password` | Masuk ke `admin/dashboard.php`. | [ ] |
| 10.2 | `http://localhost/rental-mobil/admin/data-mobil.php` | Klik menu **Data Mobil** di sidebar | Tampil tabel daftar semua mobil. Minimal 5 mobil dari seed. Status Daihatsu Xenia kembali **Tersedia**. | [ ] |
| 10.3 | — | Klik tombol **+ Tambah Mobil** | Redirect ke `admin/data-mobil-tambah.php`. Tampil form kosong: Nama, Tipe, Tahun, Kapasitas, Warna, Plat Nomor, Harga/Hari, Deskripsi, Foto, Status. | [ ] |
| 10.4 | — | Isi: **Nama**: `Toyota Fortuner`, **Tipe**: `SUV`, **Tahun**: `2024`, **Kapasitas**: `7`, **Warna**: `Hitam`, **Plat**: `B 1111 XYZ`, **Harga**: `750000`, **Deskripsi**: `SUV premium.`, **Foto**: upload gambar (.jpg/.png). → klik **Simpan** | Redirect ke `admin/data-mobil.php`. Alert hijau: "Data mobil berhasil ditambahkan." Toyota Fortuner muncul di tabel. | [ ] |
| 10.5 | — | Klik tombol **Edit** pada Toyota Fortuner | Redirect ke `admin/data-mobil-edit.php?id=X`. Form terisi data Fortuner. Preview foto tampil. | [ ] |
| 10.6 | — | Ubah **Harga/Hari** menjadi `800000` → klik **Simpan** | Redirect ke `admin/data-mobil.php`. Alert hijau sukses. Harga Fortuner berubah ke Rp 800.000. | [ ] |
| 10.7 | — | Klik tombol **Hapus** pada Toyota Fortuner | Muncul dialog konfirmasi "Apakah kamu yakin ingin menghapus data ini?" Klik OK. Alert hijau: mobil berhasil dihapus. Fortuner hilang dari tabel. | [ ] |

---

## FLOW 11 — ADMIN: KELOLA CUSTOMER

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 11.1 | `http://localhost/rental-mobil/admin/data-customer.php` | Klik menu **Data Customer** di sidebar | Tampil tabel daftar customer. Terlihat Budi Tester, Arjuna Wijaya, Siti Rahayu beserta jumlah transaksinya. | [ ] |
| 11.2 | — | Klik tombol **Edit** pada Budi Tester | Redirect ke `admin/data-customer-edit.php?id=X`. Form terisi data Budi Tester. Riwayat transaksi panel menampilkan rental Xenia. | [ ] |
| 11.3 | — | Ubah **No. Telepon** menjadi `08999999999` → klik **Simpan** | Redirect ke `admin/data-customer.php`. Alert hijau sukses. Telepon Budi Tester terupdate. | [ ] |

---

## FLOW 12 — ADMIN: LAPORAN + CETAK

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 12.1 | `http://localhost/rental-mobil/admin/laporan.php` | Klik menu **Laporan** di sidebar | Tampil halaman laporan dengan filter tanggal, summary cards, dan tabel rincian transaksi. | [ ] |
| 12.2 | — | Filter: **Dari**: `2026-06-01`, **Sampai**: `2026-06-30` → klik **Tampilkan** | Tabel menampilkan transaksi Budi Tester — Xenia — Rp 600.000 — Selesai. Summary cards menampilkan total pendapatan yang benar. | [ ] |
| 12.3 | — | Klik tombol **Cetak Laporan** | Browser dialog print terbuka. Preview bersih tanpa sidebar dan navigasi. | [ ] |

---

## FLOW 13 — CUSTOMER: EDIT PROFIL

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 13.1 | — | Login sebagai `tester@gmail.com` / `password` | Masuk ke `customer/dashboard.php`. | [ ] |
| 13.2 | `http://localhost/rental-mobil/customer/edit-profil.php` | Klik menu **Profil** di navbar atau akses langsung | Tampil form edit profil terisi data Budi Tester. | [ ] |
| 13.3 | — | Ubah **Nama Lengkap** menjadi `Budi Tester Updated` → klik **Simpan** | Alert hijau: "Profil berhasil diperbarui." Nama di navbar berubah menjadi "Budi Tester Updated". | [ ] |
| 13.4 | — | Isi field **Password Baru**: `newpassword`, **Konfirmasi**: `newpassword`, **Password Lama**: `password` → klik **Simpan** | Alert hijau sukses. | [ ] |
| 13.5 | — | Logout → Login lagi dengan email `tester@gmail.com` dan password `newpassword` | Berhasil login. Membuktikan password berhasil diubah. | [ ] |

---

## FLOW 14 — SKENARIO DENDA KETERLAMBATAN

> **Catatan**: Flow ini opsional. Buat pemesanan baru untuk menguji denda.

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 14.1 | — | Ulangi Flow 5 & 6 & 7 untuk membuat pesanan baru dan approve pembayaran. Gunakan **Tanggal Sewa**: `2026-06-20`, **Tanggal Kembali**: `2026-06-22`. | Status rental menjadi **Aktif**. | [ ] |
| 14.2 | — | Kembalikan mobil dengan **Tanggal Kembali Aktual**: `2026-06-25` (3 hari terlambat). **Kondisi**: `Baik`. | Denda dihitung: 3 hari × (10% × harga/hari). Contoh Xenia: 3 × Rp 30.000 = **Rp 90.000**. Alert menunjukkan denda. Status: Selesai. | [ ] |
| 14.3 | — | Kembalikan mobil lain dengan kondisi **Rusak Ringan** | Denda kerusakan Rp 250.000 ditambahkan ke total denda. | [ ] |
| 14.4 | — | Kembalikan mobil lain dengan kondisi **Rusak Berat** | Denda kerusakan Rp 1.000.000 ditambahkan ke total denda. | [ ] |

---

## FLOW 15 — PROTEKSI FILE POST-ONLY

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 15.1 | `http://localhost/rental-mobil/customer/pesan-proses.php` | Buka langsung via GET (saat sudah login customer) | Redirect ke `customer/daftar-mobil.php`. Tidak ada error PHP. | [ ] |
| 15.2 | `http://localhost/rental-mobil/customer/pembayaran-proses.php` | Buka langsung via GET (saat sudah login customer) | Redirect ke `customer/status-pemesanan.php`. | [ ] |
| 15.3 | `http://localhost/rental-mobil/customer/pengembalian-proses.php` | Buka langsung via GET (saat sudah login customer) | Redirect ke `customer/status-pemesanan.php`. | [ ] |
| 15.4 | `http://localhost/rental-mobil/admin/data-mobil-hapus.php` | Buka langsung via GET (saat sudah login admin) | Redirect ke `admin/data-mobil.php`. | [ ] |

---

## FLOW 16 — FITUR UI/UX & KEAMANAN BARU

| # | URL | Aksi | Hasil yang Diharapkan | Status |
|---|-----|------|-----------------------|--------|
| 16.1 | — | Akses halaman Login/Register/Edit Profil. Klik icon 'mata' di kolom password. | Teks password disamarkan berubah menjadi teks terbaca. | [ ] |
| 16.2 | — | Isi form apapun (misal: pesan mobil) dan klik tombol Submit. | Tombol berubah menjadi 'disabled', muncul animasi loading spinner, dan mencegah form terkirim dua kali. | [ ] |
| 16.3 | `http://localhost/rental-mobil/customer/status-pemesanan.php` | Akses `customer/status-pemesanan.php`, klik tombol "Lihat Detail" pada pesanan. | Dialihkan ke `detail-pesanan.php?id=X` yang menampilkan rincian tagihan dan thumbnail bukti pembayaran. | [ ] |

---

## RINGKASAN AKUN TEST

| Role | Email | Password | Redirect Setelah Login |
|------|-------|----------|------------------------|
| Admin | `admin@rental.com` | `password` | `admin/dashboard.php` |
| Customer (Seed) | `arjuna@gmail.com` | `password` | `customer/dashboard.php` |
| Customer (Seed) | `siti@gmail.com` | `password` | `customer/dashboard.php` |
| Customer (Baru) | `tester@gmail.com` | `password` | `customer/dashboard.php` |
| Direktur | `direktur@rental.com` | `password` | `direktur/laporan.php` |

---

## CATATAN

- Semua `pesan_id` dan `mobil_id` di URL bersifat dinamis tergantung data di database.
- Jika ingin menguji dari awal (reset database), jalankan: `http://localhost/rental-mobil/setup-db.php` atau drop database `rental_mobil` lalu jalankan ulang setup.
- Screenshot setiap langkah untuk dokumentasi skripsi jika diperlukan.
