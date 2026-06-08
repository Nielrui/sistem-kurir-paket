CREATE DATABASE IF NOT EXISTS kurir_db;
USE kurir_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kurir') DEFAULT 'kurir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS kurir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    no_hp VARCHAR(20),
    kendaraan VARCHAR(50),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS paket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_resi VARCHAR(20) NOT NULL UNIQUE,
    nama_pengirim VARCHAR(100) NOT NULL,
    nama_penerima VARCHAR(100) NOT NULL,
    alamat_tujuan TEXT NOT NULL,
    berat DECIMAL(5,2) NOT NULL,
    status ENUM('pending','diambil','diantar','terkirim','gagal') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tracking_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paket_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    keterangan TEXT,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paket_id) REFERENCES paket(id)
);

CREATE TABLE IF NOT EXISTS pengiriman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paket_id INT NOT NULL,
    kurir_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status ENUM('assigned','selesai') DEFAULT 'assigned',
    FOREIGN KEY (paket_id) REFERENCES paket(id),
    FOREIGN KEY (kurir_id) REFERENCES kurir(id)
);

-- Data dummy
INSERT INTO users (nama, email, password, role) VALUES
('Admin Utama', 'admin@kurir.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Budi Santoso', 'budi@kurir.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kurir');