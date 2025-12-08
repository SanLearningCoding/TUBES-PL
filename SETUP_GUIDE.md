# 🚀 SETUP GUIDE - TUBES-PL

**Untuk menjalankan TUBES-PL di device lain**

---

## 📋 Requirements

Sebelum memulai, pastikan sudah installed:

- [x] PHP 7.4 atau lebih baru
- [x] MySQL 5.7 atau MariaDB 10.3
- [x] Web Server (Apache, Nginx, atau Laragon)
- [x] Browser modern (Chrome, Firefox, Safari, Edge)
- [x] Git (opsional, untuk clone source code)

### Untuk Windows (Recommended)

**Download Laragon:** https://laragon.org/

Laragon sudah include:

- Apache
- MySQL/MariaDB
- PHP
- PhpMyAdmin
- Semua dependencies

---

## 📥 Installation Steps

### STEP 1: Download Source Code

#### Option A: Via Git

```bash
cd C:\laragon\www
git clone https://github.com/SanLearningCoding/TUBES-PL.git
cd TUBES-PL
```

#### Option B: Manual (Download ZIP)

1. Download ZIP dari: https://github.com/SanLearningCoding/TUBES-PL
2. Extract ke: `C:\laragon\www\TUBES-PL`
3. Buka folder `TUBES-PL`

### STEP 2: Setup Database

#### Option A: Using PhpMyAdmin (GUI - Recommended)

1. **Start Laragon**

   - Buka Laragon (ada di taskbar atau Start Menu)
   - Klik tombol "Start All" (jika belum running)

2. **Akses PhpMyAdmin**

   - Buka browser: http://localhost/phpmyadmin
   - Login dengan:
     - Username: `root`
     - Password: (kosong - tekan Enter saja)

3. **Import Database**

   - Klik tab "Import"
   - Klik "Choose File"
   - Pilih file: `DATABASE.sql` (ada di folder TUBES-PL)
   - Klik "Import"
   - Tunggu sampai selesai (akan ada pesan "Import berhasil")

4. **Verifikasi Database**
   - Di sidebar kiri, cari database: `pmi_darah`
   - Klik database tersebut
   - Lihat daftar tabel (seharusnya ada 8 tabel)

#### Option B: Using MySQL CLI

```bash
# Buka MySQL command line
# Windows: Laragon > Tools > MySQL Command Line (atau buka terminal)

mysql -u root -p

# Di MySQL CLI, jalankan:
create database pmi_darah;
use pmi_darah;
source C:/path/to/DATABASE.sql;

# Atau gunakan single command:
mysql -u root pmi_darah < C:\path\to\DATABASE.sql
```

### STEP 3: Konfigurasi Aplikasi

Edit file: `TUBES-PL/Config/Database.php`

```php
<?php

class Database {
    private $host = 'localhost';          // MySQL host (SESUAIKAN JIKA PERLU)
    private $db_name = 'pmi_darah';       // Nama database
    private $user = 'root';               // MySQL username
    private $pass = '';                   // MySQL password (kosong jika default)
    private $charset = 'utf8mb4';
    private $conn;

    public function connect() {
        $this->conn = null;

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=' . $this->charset;

        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }

        return $this->conn;
    }
}
```

**Jika password MySQL Anda berbeda:**

```php
$pass = 'password_anda_di_sini';  // Ubah ke password yang benar
```

### STEP 4: Test Aplikasi

#### Jika pakai Laragon:

1. **Start Laragon**

   - Buka aplikasi Laragon
   - Klik tombol "Start All"
   - Status akan berubah menjadi "RUNNING"

2. **Akses Aplikasi**

   - Buka browser
   - Ketik URL: `http://localhost/TUBES-PL/TUBES-PL/`
   - Seharusnya muncul halaman login

3. **Login**

   - Email: `admin@pmidarah.local`
   - Password: `admin123`
   - Klik "Masuk"

4. **Jika berhasil:**
   - Akan masuk ke Dashboard
   - Lihat Statistik Donasi
   - Sistem sudah siap digunakan!

#### Jika pakai Apache/Nginx manual:

```bash
# Pastikan DocumentRoot di config Apache/Nginx mengarah ke:
# C:/laragon/www/TUBES-PL

# Atau akses dengan port custom:
# http://your-ip-address:8080/TUBES-PL

# Atau akses dengan Virtual Host:
# http://tubes-pl.local/
# (setelah konfigurasi hosts file)
```

---

## 🔐 Keamanan: Ubah Password Admin

### ⚠️ HARUS DILAKUKAN SETELAH LOGIN PERTAMA!

#### Method 1: Via Database

1. Buka PhpMyAdmin
2. Klik database: `pmi_darah`
3. Klik tabel: `petugas`
4. Cari baris dengan email: `admin@pmidarah.local`
5. Klik "Edit"
6. Copy hash password baru dari sini:

**Buka PowerShell/Terminal dan jalankan:**

```bash
php -r "echo password_hash('PASSWORD_BARU_ANDA', PASSWORD_BCRYPT);"
```

Contoh:

```bash
$ php -r "echo password_hash('MySecure123!', PASSWORD_BCRYPT);"
# Output: $2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
```

7. Paste hash ke kolom `password_hash`
8. Klik "Go" untuk save

9. Logout dan login kembali dengan password baru

#### Method 2: Via SQL Command

```sql
-- Buka PhpMyAdmin > SQL tab
-- Paste command ini:

UPDATE petugas
SET password_hash = '$2y$10$NEW_HASH_ANDA_DI_SINI'
WHERE email = 'admin@pmidarah.local';
```

---

## 🐛 Troubleshooting

### Error: "Connection Error: SQLSTATE[HY000] [2002] No such file or directory"

**Penyebab:** MySQL tidak running  
**Solusi:**

```bash
# Windows - Buka Laragon
# Klik "Start All" atau "Start MySQL"

# Linux/Mac
sudo /usr/local/mysql/support-files/mysql.server start
# atau
brew services start mysql
```

### Error: "Connection Error: SQLSTATE[HY000] [1045]"

**Penyebab:** Username/Password salah  
**Solusi:**

- Cek di `Config/Database.php` apakah user dan pass sesuai
- Untuk Laragon, biasanya:
  - user: `root`
  - pass: (kosong)

### Error: "Table 'pmi_darah.pendonor' doesn't exist"

**Penyebab:** Database belum di-import  
**Solusi:**

- Jalankan import `DATABASE.sql` kembali
- Atau check di PhpMyAdmin apakah semua 8 tabel sudah ada

### Halaman login muncul tapi login gagal

**Penyebab:** Password admin belum reset atau database kosong  
**Solusi:**

```sql
-- Jalankan di PhpMyAdmin SQL tab:
SELECT * FROM petugas;

-- Jika kosong, insert admin baru:
INSERT INTO petugas (nama_petugas, email, password_hash, nip, jabatan, kontak)
VALUES ('Admin', 'admin@pmidarah.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36jGgKTm', '00001', 'Administrator', '0812-0000-0001');
```

---

## 📁 File Structure (Setelah Setup)

```
TUBES-PL/
├── CLEANUP_GUIDE.md              ← File yang bisa dihapus
├── DATABASE.sql                   ← Database schema (buat import)
├── DOCUMENTATION.md               ← Dokumentasi lengkap
├── SETUP_GUIDE.md                 ← File ini
├── Config/
│   ├── Database.php               ← EDIT INI jika db setting berbeda
│   └── Path.php
├── Controllers/
├── Model/
├── View/
│   ├── template/
│   │   └── assets/
│   └── (halaman-halaman)
├── seeds/
│   └── complete_database_setup.sql (backup DATABASE.sql)
└── index.php                      ← Entry point
```

---

## 📚 Setelah Setup Berhasil

1. **Baca DOCUMENTATION.md** untuk memahami cara kerja sistem
2. **Baca CLEANUP_GUIDE.md** untuk menghapus file development yang tidak diperlukan
3. **Ganti password admin** untuk keamanan
4. **Mulai gunakan sistem:**
   - Tambah pendonor
   - Catat transaksi donasi
   - Kelola stok darah
   - Distribusi ke rumah sakit
   - Lihat laporan

---

## 🎯 Quick Start Features

### 1. Tambah Pendonor Baru

- Menu: Pendonor → Tambah Pendonor
- Input: Nama, Kontak, Golongan Darah, Screening penyakit
- Sistem otomatis set kelayakan berdasarkan screening

### 2. Catat Transaksi Donasi

- Menu: Transaksi → Transaksi Baru
- Pilih pendonor yang layak
- Input jumlah kantong
- Sistem otomatis buat stok darah per kantong

### 3. Test Darah

- Menu: Stok → Lihat Stok
- Klik detail stok → Update status test
- Input hasil: Lolos / Tidak Lolos

### 4. Distribusi ke Rumah Sakit

- Menu: Distribusi → Tambah Distribusi
- Pilih stok yang lolos
- Pilih rumah sakit tujuan
- Input status: Dikirim / Diterima

### 5. Lihat Laporan

- Menu: Laporan
- Pilih: Donasi, Kinerja Donor, Evaluasi Stok, atau Distribusi

---

## 📞 Support & Help

Jika ada masalah:

1. Cek `DOCUMENTATION.md` → Bagian Troubleshooting
2. Verifikasi database sudah ter-import dengan benar
3. Pastikan MySQL running
4. Cek browser console untuk error (F12)
5. Lihat file logs (jika ada)

---

## ✅ Checklist Setup

- [ ] PHP 7.4+ terinstall
- [ ] MySQL/MariaDB running
- [ ] Web server (Apache/Nginx/Laragon) running
- [ ] Source code sudah download
- [ ] DATABASE.sql sudah di-import
- [ ] Config/Database.php sudah dicek
- [ ] Bisa akses http://localhost/TUBES-PL/TUBES-PL/
- [ ] Bisa login dengan admin@pmidarah.local / admin123
- [ ] Password admin sudah diganti
- [ ] Sudah baca DOCUMENTATION.md

---

**Version:** 1.0  
**Last Updated:** December 9, 2025  
**For:** TUBES-PL System
