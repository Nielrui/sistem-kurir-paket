CREATE DATABASE IF NOT EXISTS kurir_db;
USE kurir_db;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_user VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_hp VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kurir') DEFAULT 'kurir',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kurir
CREATE TABLE IF NOT EXISTS kurir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    no_hp VARCHAR(20),
    kendaraan VARCHAR(50),
    plat_nomor VARCHAR(20),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel paket
CREATE TABLE IF NOT EXISTS paket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_resi VARCHAR(20) NOT NULL UNIQUE,
    nama_pengirim VARCHAR(100) NOT NULL,
    telp_pengirim VARCHAR(20),
    nama_penerima VARCHAR(100) NOT NULL,
    telp_penerima VARCHAR(20),
    alamat_tujuan TEXT NOT NULL,
    kota_tujuan VARCHAR(100),
    berat DECIMAL(5,2) NOT NULL,
    jenis_paket VARCHAR(50) DEFAULT 'reguler',
    ongkir DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending','diambil','diantar','terkirim','gagal') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel tracking_log
CREATE TABLE IF NOT EXISTS tracking_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paket_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    keterangan TEXT,
    lokasi VARCHAR(100),
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paket_id) REFERENCES paket(id) ON DELETE CASCADE
);

-- Tabel pengiriman
CREATE TABLE IF NOT EXISTS pengiriman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paket_id INT NOT NULL,
    kurir_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('assigned','dalam_pengiriman','selesai','gagal') DEFAULT 'assigned',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paket_id) REFERENCES paket(id) ON DELETE CASCADE,
    FOREIGN KEY (kurir_id) REFERENCES kurir(id) ON DELETE CASCADE
);

-- Tabel notifikasi
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pesan TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ================================================
-- DATA DUMMY
-- ================================================

-- Password semua akun: "password"
INSERT INTO users (kode_user, nama, email, no_hp, password, role) VALUES
('ADM001', 'Admin Utama',  'admin@kurir.com', '0811111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('KUR001', 'Budi Santoso', 'budi@kurir.com',  '0812222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kurir'),
('KUR002', 'Andi Prasetyo','andi@kurir.com',  '0813333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kurir'),
('KUR003', 'Siti Rahayu',  'siti@kurir.com',  '0814444444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kurir');

INSERT INTO kurir (user_id, no_hp, kendaraan, plat_nomor, status) VALUES
(2, '0812222222', 'Motor', 'L 1234 AB', 'aktif'),
(3, '0813333333', 'Motor', 'L 5678 CD', 'aktif'),
(4, '0814444444', 'Motor', 'L 9012 EF', 'aktif');

INSERT INTO paket (no_resi, nama_pengirim, telp_pengirim, nama_penerima, telp_penerima, alamat_tujuan, kota_tujuan, berat, jenis_paket, ongkir, status) VALUES
('PKT001', 'Toko Makmur',  '0811000001', 'Dewi Lestari',  '0821000001', 'Jl. Raya Darmo No.10, Surabaya',   'Surabaya', 1.5, 'reguler', 15000, 'terkirim'),
('PKT002', 'Toko Sejahtera','0811000002', 'Eko Wahyudi',   '0821000002', 'Jl. Pemuda No.25, Surabaya',       'Surabaya', 2.0, 'reguler', 20000, 'diantar'),
('PKT003', 'CV Maju Jaya',  '0811000003', 'Fitri Handayani','0821000003','Jl. Ahmad Yani No.88, Sidoarjo',   'Sidoarjo', 0.5, 'dokumen', 10000, 'diambil'),
('PKT004', 'Toko Berkah',   '0811000004', 'Gunawan Susilo', '0821000004','Jl. Basuki Rahmat No.5, Surabaya', 'Surabaya', 3.0, 'berat',   30000, 'pending'),
('PKT005', 'Online Shop X', '0811000005', 'Hani Pertiwi',   '0821000005','Jl. Diponegoro No.12, Gresik',     'Gresik',  1.0, 'reguler', 18000, 'pending');

INSERT INTO pengiriman (paket_id, kurir_id, tanggal, status) VALUES
(1, 1, '2026-06-08', 'selesai'),
(2, 2, '2026-06-08', 'dalam_pengiriman'),
(3, 1, '2026-06-08', 'assigned');

INSERT INTO tracking_log (paket_id, status, keterangan, lokasi) VALUES
(1, 'pending',   'Paket diterima di gudang',          'Gudang Surabaya'),
(1, 'diambil',   'Paket diambil kurir',                'Gudang Surabaya'),
(1, 'diantar',   'Paket dalam perjalanan ke penerima', 'Jl. Raya Darmo'),
(1, 'terkirim',  'Paket berhasil diterima penerima',   'Rumah Penerima'),
(2, 'pending',   'Paket diterima di gudang',           'Gudang Surabaya'),
(2, 'diambil',   'Paket diambil kurir',                'Gudang Surabaya'),
(2, 'diantar',   'Paket dalam perjalanan',             'Jl. Pemuda'),
(3, 'pending',   'Paket diterima di gudang',           'Gudang Surabaya'),
(3, 'diambil',   'Paket diambil kurir',                'Gudang Surabaya');