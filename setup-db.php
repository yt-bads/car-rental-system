<?php
// setup-db.php
// Skrip Inisialisasi Database — Rental Mobil PT. Wildan Abadi Jaya

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

echo "=== MEMULAI SETUP DATABASE RENTAL MOBIL ===\n";

try {
    // 1. Koneksi awal ke MySQL Server (tanpa nama database)
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "[OK] Terhubung ke MySQL Server.\n";

    // 2. Buat Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS rental_mobil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Database 'rental_mobil' berhasil dibuat / sudah ada.\n";

    // 3. Gunakan Database
    $pdo->exec("USE rental_mobil");
    echo "[OK] Menggunakan database 'rental_mobil'.\n";

    // 4. Buat Tabel Users
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
        nama_lengkap  VARCHAR(100)    NOT NULL,
        email         VARCHAR(100)    NOT NULL,
        password      VARCHAR(255)    NOT NULL,
        no_telepon    VARCHAR(20)     DEFAULT NULL,
        alamat        TEXT            DEFAULT NULL,
        foto_profil   VARCHAR(255)    DEFAULT NULL,
        role          ENUM('admin','customer','direktur') NOT NULL DEFAULT 'customer',
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_email (email)
    ) ENGINE=InnoDB";
    $pdo->exec($sqlUsers);
    echo "[OK] Tabel 'users' siap.\n";

    // 5. Buat Tabel Mobil
    $sqlMobil = "CREATE TABLE IF NOT EXISTS mobil (
        id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
        nama_mobil    VARCHAR(100)    NOT NULL,
        tipe          VARCHAR(50)     NOT NULL,
        tahun         YEAR            DEFAULT NULL,
        kapasitas     TINYINT         DEFAULT NULL COMMENT 'jumlah penumpang',
        warna         VARCHAR(50)     DEFAULT NULL,
        plat_nomor    VARCHAR(20)     DEFAULT NULL,
        harga_per_hari DECIMAL(12,2)  NOT NULL,
        deskripsi     TEXT            DEFAULT NULL,
        foto          VARCHAR(255)    DEFAULT NULL,
        status        ENUM('tersedia','disewa','servis') NOT NULL DEFAULT 'tersedia',
        created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB";
    $pdo->exec($sqlMobil);
    echo "[OK] Tabel 'mobil' siap.\n";

    // 6. Buat Tabel Pemesanan
    $sqlPemesanan = "CREATE TABLE IF NOT EXISTS pemesanan (
        id             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
        kode_pesan     VARCHAR(25)    NOT NULL,
        customer_id    INT UNSIGNED   NOT NULL,
        mobil_id       INT UNSIGNED   NOT NULL,
        tanggal_sewa   DATE           NOT NULL,
        tanggal_kembali DATE          NOT NULL,
        lama_sewa      TINYINT        NOT NULL COMMENT 'dalam hari',
        total_biaya    DECIMAL(14,2)  NOT NULL,
        catatan        TEXT           DEFAULT NULL,
        status         ENUM(
                         'menunggu_pembayaran',
                         'menunggu_konfirmasi',
                         'aktif',
                         'selesai',
                         'dibatalkan'
                       ) NOT NULL DEFAULT 'menunggu_pembayaran',
        created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_kode_pesan (kode_pesan),
        CONSTRAINT fk_pesan_customer FOREIGN KEY (customer_id) REFERENCES users (id) ON DELETE RESTRICT,
        CONSTRAINT fk_pesan_mobil    FOREIGN KEY (mobil_id)    REFERENCES mobil (id) ON DELETE RESTRICT
    ) ENGINE=InnoDB";
    $pdo->exec($sqlPemesanan);
    echo "[OK] Tabel 'pemesanan' siap.\n";

    // 7. Buat Tabel Pembayaran
    $sqlPembayaran = "CREATE TABLE IF NOT EXISTS pembayaran (
        id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
        pemesanan_id     INT UNSIGNED   NOT NULL,
        jumlah_bayar     DECIMAL(14,2)  NOT NULL,
        metode_pembayaran VARCHAR(50)   NOT NULL COMMENT 'transfer_bca, transfer_bri, dll',
        bukti_pembayaran  VARCHAR(255)  NOT NULL COMMENT 'path file upload',
        status           ENUM('menunggu','diterima','ditolak') NOT NULL DEFAULT 'menunggu',
        catatan_admin    TEXT           DEFAULT NULL,
        verified_by      INT UNSIGNED   DEFAULT NULL COMMENT 'user_id admin yang verifikasi',
        verified_at      TIMESTAMP      NULL DEFAULT NULL,
        created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        CONSTRAINT fk_bayar_pesan FOREIGN KEY (pemesanan_id) REFERENCES pemesanan (id) ON DELETE RESTRICT
    ) ENGINE=InnoDB";
    $pdo->exec($sqlPembayaran);
    echo "[OK] Tabel 'pembayaran' siap.\n";

    // 8. Buat Tabel Pengembalian
    $sqlPengembalian = "CREATE TABLE IF NOT EXISTS pengembalian (
        id                     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
        pemesanan_id           INT UNSIGNED   NOT NULL,
        tanggal_kembali_aktual DATE           NOT NULL,
        kondisi_mobil          ENUM('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
        denda                  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
        keterangan             TEXT           DEFAULT NULL,
        processed_by           INT UNSIGNED   DEFAULT NULL COMMENT 'admin yang proses (jika via admin)',
        created_at             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_pengembalian_pesan (pemesanan_id),
        CONSTRAINT fk_kembali_pesan FOREIGN KEY (pemesanan_id) REFERENCES pemesanan (id) ON DELETE RESTRICT
    ) ENGINE=InnoDB";
    $pdo->exec($sqlPengembalian);
    echo "[OK] Tabel 'pengembalian' siap.\n";

    // 9. Cek apakah tabel users kosong sebelum memasukkan seed data
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUsers == 0) {
        $sqlSeedUsers = "INSERT INTO users (nama_lengkap, email, password, no_telepon, role) VALUES
        ('Administrator',  'admin@rental.com',    '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'admin'),
        ('Bendi Setia Budi','direktur@rental.com','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081999888777', 'direktur'),
        ('Arjuna Wijaya',   'arjuna@gmail.com',   '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '089637657235', 'customer'),
        ('Siti Rahayu',     'siti@gmail.com',     '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '082111222333', 'customer')";
        $pdo->exec($sqlSeedUsers);
        echo "[OK] Seed data 'users' berhasil dimasukkan.\n";
    } else {
        echo "[INFO] Tabel 'users' sudah terisi. Skip seeding users.\n";
    }

    // 10. Cek apakah tabel mobil kosong sebelum memasukkan seed data
    $checkMobil = $pdo->query("SELECT COUNT(*) FROM mobil")->fetchColumn();
    if ($checkMobil == 0) {
        $sqlSeedMobil = "INSERT INTO mobil (nama_mobil, tipe, tahun, kapasitas, warna, plat_nomor, harga_per_hari, deskripsi, status, foto) VALUES
        ('Toyota Avanza', 'MPV', 2022, 7, 'Putih', 'B 1234 ABC', 350000.00, 'Mobil keluarga nyaman dan irit bahan bakar.', 'tersedia', 'Toyota Avanza.png'),
        ('Kijang Innova', 'MPV', 2023, 8, 'Silver', 'B 5678 DEF', 550000.00, 'Mobil premium keluarga dengan ruang kabin luas.', 'tersedia', 'Kijang Innova.png'),
        ('Daihatsu Xenia', 'MPV', 2021, 7, 'Hitam', 'B 9012 GHI', 300000.00, 'Mobil irit dan lincah untuk kota.', 'tersedia', 'Daihatsu Xenia.png'),
        ('Honda Brio', 'City Car', 2022, 4, 'Merah', 'B 3456 JKL', 250000.00, 'City car gesit dan mudah parkir.', 'tersedia', 'Honda Brio.png'),
        ('Mitsubishi Pajero', 'SUV', 2022, 7, 'Hitam', 'B 7890 MNO', 850000.00, 'SUV tangguh untuk berbagai medan.', 'tersedia', 'Pajero Sport.jpg')";
        $pdo->exec($sqlSeedMobil);
        echo "[OK] Seed data 'mobil' berhasil dimasukkan.\n";
    } else {
        echo "[INFO] Tabel 'mobil' sudah terisi. Skip seeding mobil.\n";
    }

    echo "=== SETUP DATABASE SELESAI DENGAN SUKSES ===\n";

} catch (PDOException $e) {
    echo "[ERROR] Setup database gagal: " . $e->getMessage() . "\n";
    exit(1);
}
