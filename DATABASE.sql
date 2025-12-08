-- =====================================================
-- TUBES-PL: Complete Database Setup
-- PMI Blood Donation Management System
-- Database: pmi_darah
-- Last Updated: December 9, 2025
-- =====================================================
-- Description: File SQL lengkap untuk setup database TUBES-PL
-- Berisi:
-- - Pembuatan database dan semua tabel
-- - Foreign key constraints
-- - Indeks untuk optimasi query
-- - Data seed (golongan darah, petugas default, rumah sakit, pendonor)
-- - Migrasi kolom tambahan (screening, is_deleted, dll)
-- 
-- Instruksi:
-- 1. Buat database kosong terlebih dahulu, atau biarkan script membuat otomatis
-- 2. Jalankan file ini di MySQL client (phpMyAdmin, MySQL Workbench, atau CLI)
-- 3. Tunggu sampai semua query berhasil dieksekusi
--
-- CARA MENJALANKAN:
-- phpMyAdmin: Buka tab SQL, paste semua script ini, klik Execute
-- MySQL CLI: mysql -u root -p < DATABASE.sql
-- MySQL Workbench: File > Open SQL Script > pilih file ini > Execute All
-- =====================================================

-- =====================================================
-- SECTION 1: CREATE DATABASE
-- =====================================================
CREATE DATABASE IF NOT EXISTS pmi_darah;
USE pmi_darah;

-- =====================================================
-- SECTION 2: CREATE BASE TABLES
-- =====================================================

-- TABLE 1: GOLONGAN DARAH (Blood Types)
-- Menyimpan informasi golongan darah (A, B, O, AB) dengan rhesus (+/-)
CREATE TABLE IF NOT EXISTS golongan_darah (
  id_gol_darah INT AUTO_INCREMENT PRIMARY KEY,
  nama_gol_darah VARCHAR(10) NOT NULL,
  rhesus VARCHAR(5) NOT NULL,
  keterangan TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_gol_rhesus (nama_gol_darah, rhesus),
  INDEX idx_nama_gol (nama_gol_darah)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 2: PETUGAS (Officers/Staff)
-- Menyimpan data petugas PMI dengan autentikasi
CREATE TABLE IF NOT EXISTS petugas (
  id_petugas INT AUTO_INCREMENT PRIMARY KEY,
  nama_petugas VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nip VARCHAR(50),
  jabatan VARCHAR(50),
  kontak VARCHAR(20),
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 3: PENDONOR (Donors)
-- Menyimpan data pendonor darah dengan screening kesehatan
CREATE TABLE IF NOT EXISTS pendonor (
  id_pendonor INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  kontak VARCHAR(20) NOT NULL,
  riwayat_penyakit TEXT,
  id_gol_darah INT,
  -- Screening fields untuk pengecekan kelayakan
  has_hepatitis_b TINYINT(1) DEFAULT 0,
  has_hepatitis_c TINYINT(1) DEFAULT 0,
  has_aids TINYINT(1) DEFAULT 0,
  has_hemofilia TINYINT(1) DEFAULT 0,
  has_sickle_cell TINYINT(1) DEFAULT 0,
  has_thalassemia TINYINT(1) DEFAULT 0,
  has_leukemia TINYINT(1) DEFAULT 0,
  has_lymphoma TINYINT(1) DEFAULT 0,
  has_myeloma TINYINT(1) DEFAULT 0,
  has_cjd TINYINT(1) DEFAULT 0,
  other_illness TEXT NULL,
  is_layak TINYINT(1) DEFAULT 1,
  -- Soft delete fields
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_gol_darah) REFERENCES golongan_darah(id_gol_darah) ON DELETE SET NULL,
  INDEX idx_nama (nama),
  INDEX idx_is_deleted (is_deleted),
  INDEX idx_is_layak (is_layak),
  UNIQUE KEY uk_nama_active (nama(100), is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 4: KEGIATAN DONASI (Donation Activities/Events)
-- Menyimpan informasi acara/kegiatan donasi darah
CREATE TABLE IF NOT EXISTS kegiatan_donasi (
  id_kegiatan INT AUTO_INCREMENT PRIMARY KEY,
  nama_kegiatan VARCHAR(200) NOT NULL,
  tanggal DATE NOT NULL,
  lokasi VARCHAR(200),
  keterangan TEXT,
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_tanggal (tanggal),
  INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 5: TRANSAKSI DONASI (Donation Transactions)
-- Mencatat setiap transaksi donasi darah dari pendonor
CREATE TABLE IF NOT EXISTS transaksi_donasi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  id_pendonor INT NOT NULL,
  id_kegiatan INT NOT NULL,
  id_petugas INT,
  tanggal_donasi DATE NOT NULL,
  jumlah_kantong INT NOT NULL DEFAULT 1,
  catatan TEXT,
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pendonor) REFERENCES pendonor(id_pendonor) ON DELETE RESTRICT,
  FOREIGN KEY (id_kegiatan) REFERENCES kegiatan_donasi(id_kegiatan) ON DELETE RESTRICT,
  FOREIGN KEY (id_petugas) REFERENCES petugas(id_petugas) ON DELETE SET NULL,
  INDEX idx_id_pendonor (id_pendonor),
  INDEX idx_id_kegiatan (id_kegiatan),
  INDEX idx_tanggal_donasi (tanggal_donasi),
  INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 6: STOK DARAH (Blood Stock/Inventory - Per Unit)
-- Setiap baris mewakili SATU kantong darah (satu unit)
-- Tersimpan otomatis saat transaksi donasi dibuat
CREATE TABLE IF NOT EXISTS stok_darah (
  id_stok INT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT NOT NULL,
  id_gol_darah INT NOT NULL,
  tanggal_donasi DATE,
  tanggal_pengujian DATE DEFAULT NULL,
  status_uji ENUM('belum_uji', 'lolos', 'tidak_lolos') DEFAULT 'belum_uji',
  status ENUM('belum_uji','tersedia','terpakai','kadaluarsa','terdistribusi') DEFAULT 'belum_uji',
  tanggal_kadaluarsa DATE NOT NULL,
  volume_ml INT DEFAULT 450,
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_stok_transaksi FOREIGN KEY (id_transaksi) REFERENCES transaksi_donasi(id_transaksi) ON DELETE RESTRICT,
  CONSTRAINT fk_stok_golongan FOREIGN KEY (id_gol_darah) REFERENCES golongan_darah(id_gol_darah) ON DELETE RESTRICT,
  INDEX idx_id_gol_darah (id_gol_darah),
  INDEX idx_status (status),
  INDEX idx_tanggal_kadaluarsa (tanggal_kadaluarsa),
  INDEX idx_is_deleted (is_deleted),
  INDEX idx_id_transaksi (id_transaksi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 7: RUMAH SAKIT (Hospitals/Partners)
-- Menyimpan daftar rumah sakit penerima darah
CREATE TABLE IF NOT EXISTS rumah_sakit (
  id_rs INT AUTO_INCREMENT PRIMARY KEY,
  nama_rs VARCHAR(150) NOT NULL,
  alamat TEXT,
  kontak VARCHAR(20),
  email VARCHAR(100),
  keterangan TEXT,
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_nama_rs (nama_rs),
  INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE 8: DISTRIBUSI DARAH (Blood Distribution)
-- Mencatat distribusi darah dari stok ke rumah sakit
CREATE TABLE IF NOT EXISTS distribusi_darah (
  id_distribusi INT AUTO_INCREMENT PRIMARY KEY,
  id_stok INT NOT NULL,
  id_rs INT NOT NULL,
  id_petugas INT,
  tanggal_distribusi DATE NOT NULL,
  jumlah_volume INT NOT NULL,
  status ENUM('dikirim', 'diterima', 'dibatalkan') DEFAULT 'dikirim',
  catatan TEXT,
  is_deleted TINYINT(1) DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (id_stok) REFERENCES stok_darah(id_stok) ON DELETE RESTRICT,
  FOREIGN KEY (id_rs) REFERENCES rumah_sakit(id_rs) ON DELETE RESTRICT,
  FOREIGN KEY (id_petugas) REFERENCES petugas(id_petugas) ON DELETE SET NULL,
  INDEX idx_id_stok (id_stok),
  INDEX idx_id_rs (id_rs),
  INDEX idx_tanggal_distribusi (tanggal_distribusi),
  INDEX idx_status (status),
  INDEX idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECTION 3: SEED DATA - GOLONGAN DARAH
-- =====================================================
-- Insert 8 jenis golongan darah (A+, A-, B+, B-, O+, O-, AB+, AB-)
INSERT IGNORE INTO golongan_darah (nama_gol_darah, rhesus, keterangan) VALUES
('O', '+', 'Golongan O Positif (Universal Donor - Dapat diberikan ke semua golongan)'),
('O', '-', 'Golongan O Negatif (Universal Donor - Paling dicari untuk emergency)'),
('A', '+', 'Golongan A Positif'),
('A', '-', 'Golongan A Negatif'),
('B', '+', 'Golongan B Positif'),
('B', '-', 'Golongan B Negatif'),
('AB', '+', 'Golongan AB Positif (Universal Recipient - Dapat menerima dari semua golongan)'),
('AB', '-', 'Golongan AB Negatif (Universal Recipient)');

-- =====================================================
-- SECTION 4: SEED DATA - DEFAULT ADMIN PETUGAS
-- =====================================================
-- Default admin user dengan password 'admin123'
-- PENTING: Ubah password ini setelah login pertama kali!
-- Hash di-generate dengan: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
INSERT IGNORE INTO petugas (nama_petugas, email, password_hash, nip, jabatan, kontak) VALUES
('Admin Sistem', 'admin@pmidarah.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36jGgKTm', '00001', 'Administrator', '0812-0000-0001');

-- =====================================================
-- SECTION 5: SEED DATA - SAMPLE HOSPITALS
-- =====================================================
INSERT IGNORE INTO rumah_sakit (nama_rs, alamat, kontak, email) VALUES
('Rumah Sakit Pusat Kesehatan', 'Jl. Merdeka No. 1, Jakarta', '021-1234567', 'info@rspk.id'),
('Rumah Sakit Sejahtera', 'Jl. Kesehatan No. 10, Bandung', '022-7654321', 'info@rssejatera.id'),
('Rumah Sakit Medika', 'Jl. Ahmad Yani No. 25, Surabaya', '031-9876543', 'info@rsmedika.id');

-- =====================================================
-- SECTION 6: SEED DATA - SAMPLE DONORS (OPTIONAL)
-- =====================================================
INSERT IGNORE INTO pendonor (nama, kontak, riwayat_penyakit, id_gol_darah, is_layak) VALUES
('Budi Santoso', '0812-3456-7890', '', 1, 1),
('Siti Nurhaliza', '0813-2345-6789', '', 3, 1),
('Ahmad Wijaya', '0814-5678-9012', '', 5, 1),
('Linda Kusuma', '0815-4321-8765', '', 7, 1);

-- =====================================================
-- SECTION 7: SEED DATA - SAMPLE DONATION ACTIVITIES (OPTIONAL)
-- =====================================================
INSERT IGNORE INTO kegiatan_donasi (nama_kegiatan, tanggal, lokasi) VALUES
('Donor Darah Rutin Bulanan', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'PMI Pusat'),
('Kegiatan Donor Darah di Kampus', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Universitas Indonesia'),
('Penggalangan Donor Darah Sosial', CURDATE(), 'Mall Central Park');

-- =====================================================
-- SECTION 8: VERIFICATION & SUMMARY
-- =====================================================
-- Jalankan query di bawah ini untuk memverifikasi bahwa semua tabel berhasil dibuat:
-- 
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'pmi_darah' ORDER BY TABLE_NAME;
-- 
-- Query untuk melihat jumlah data seed:
-- SELECT 'Golongan Darah' AS entity, COUNT(*) AS count FROM golongan_darah
-- UNION ALL
-- SELECT 'Petugas', COUNT(*) FROM petugas
-- UNION ALL
-- SELECT 'Pendonor', COUNT(*) FROM pendonor
-- UNION ALL
-- SELECT 'Kegiatan Donasi', COUNT(*) FROM kegiatan_donasi
-- UNION ALL
-- SELECT 'Rumah Sakit', COUNT(*) FROM rumah_sakit;

-- =====================================================
-- SECTION 9: IMPORTANT NOTES
-- =====================================================
-- 1. DATABASE SETUP COMPLETED SUCCESSFULLY!
-- 2. Struktur tabel sudah support soft delete dengan kolom is_deleted dan deleted_at
-- 3. Foreign key constraints sudah dikonfigurasi dengan ON DELETE RESTRICT/SET NULL
-- 4. Semua tabel memiliki indeks untuk optimasi query
-- 5. Default admin user dibuat: email: admin@pmidarah.local, password: admin123
--    PENTING: Ubah password admin setelah login pertama kali!
-- 6. Screening fields sudah ditambahkan di tabel pendonor untuk pengecekan kelayakan
-- 7. Soft delete untuk archive data sudah terintegrasi di semua tabel
-- 8. Database name: pmi_darah
-- 9. Charset: utf8mb4 (support emoji dan character internasional)
-- 
-- =====================================================
-- END OF COMPLETE DATABASE SETUP
-- =====================================================
