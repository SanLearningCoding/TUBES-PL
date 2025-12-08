# 📝 README - TUBES-PL

## Sistem Manajemen Donasi Darah PMI

Sistem informasi untuk mengelola donasi darah, stok, dan distribusi ke rumah sakit.

---

## 🚀 Quick Start (5 menit)

### 1. Persiapan

```bash
# Pastikan sudah ada di folder:
# C:\laragon\www\TUBES-PL

# Atau jika belum:
cd C:\laragon\www
git clone https://github.com/SanLearningCoding/TUBES-PL.git
```

### 2. Import Database

- Buka: http://localhost/phpmyadmin
- Tab Import → Pilih file: `DATABASE.sql`
- Klik Import
- ✅ Selesai!

### 3. Login

- Akses: http://localhost/TUBES-PL/TUBES-PL/
- Email: `admin@pmidarah.local`
- Password: `admin123`
- 🎉 Siap digunakan!

---

## 📚 Dokumentasi

### File-File Penting:

| File                 | Deskripsi                      |
| -------------------- | ------------------------------ |
| **SETUP_GUIDE.md**   | 📖 Panduan instalasi lengkap   |
| **DOCUMENTATION.md** | 📚 Dokumentasi sistem & fitur  |
| **CLEANUP_GUIDE.md** | 🧹 File yang bisa dihapus      |
| **DATABASE.sql**     | 💾 Database schema & seed data |

---

## 💡 Fitur Utama

✅ **Manajemen Pendonor**

- Database pendonor dengan screening kesehatan
- Tracking kelayakan donor otomatis
- Riwayat donasi per pendonor

✅ **Transaksi Donasi**

- Pencatatan transaksi donasi
- Auto-generate stok darah per kantong
- Link ke kegiatan donasi

✅ **Manajemen Stok Darah**

- Inventory per unit (kantong)
- Tracking status: belum_uji, lolos, tidak_lolos, dll
- Auto-calculate tanggal kadaluarsa

✅ **Distribusi Darah**

- Distribusi ke rumah sakit mitra
- Tracking status: dikirim, diterima, dibatalkan
- Riwayat distribusi per RS

✅ **Laporan & Statistik**

- Dashboard statistik real-time
- Laporan donasi per bulan
- Laporan kinerja pendonor
- Evaluasi stok darah

✅ **Multi-User Authentication**

- Login dengan email & password
- Role-based access (Admin, Petugas)
- Secure password hashing (bcrypt)

---

## 🏗️ Teknologi

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** Bootstrap 5, JavaScript
- **Architecture:** MVC (Model-View-Controller)
- **Security:** PDO, Bcrypt, Soft Delete

---

## 📊 Database Structure

8 Tabel utama:

1. `golongan_darah` - Jenis darah (O+, O-, A+, A-, B+, B-, AB+, AB-)
2. `petugas` - Staff dengan authentication
3. `pendonor` - Database pendonor + screening
4. `kegiatan_donasi` - Event/acara donasi
5. `transaksi_donasi` - Mencatat setiap transaksi
6. `stok_darah` - Inventory per kantong (auto-generate)
7. `rumah_sakit` - Rumah sakit penerima darah
8. `distribusi_darah` - Riwayat distribusi

---

## 👤 Default Admin

**Email:** `admin@pmidarah.local`  
**Password:** `admin123`

⚠️ **PENTING:** Ubah password setelah login pertama!

---

## 🔧 Konfigurasi

Edit file: `Config/Database.php`

```php
$host = 'localhost';       // MySQL host
$db_name = 'pmi_darah';    // Database name
$user = 'root';            // MySQL user
$pass = '';                // MySQL password
```

---

## 🐛 Troubleshooting

### Login Error

→ Cek di SETUP_GUIDE.md bagian Troubleshooting

### Database Error

→ Import DATABASE.sql di PhpMyAdmin

### Koneksi Error

→ Start MySQL di Laragon atau command line

---

## 📖 Selanjutnya?

1. **Baca DOCUMENTATION.md** → Pelajari cara kerja sistem
2. **Baca SETUP_GUIDE.md** → Instalasi di device lain
3. **Baca CLEANUP_GUIDE.md** → Hapus file development

---

## 🎯 Panduan Penambahan Admin

Lihat: **DOCUMENTATION.md** → Bagian "Cara Menambah Akun Admin"

Tl;dr:

```bash
php -r "echo password_hash('PASSWORD_BARU', PASSWORD_BCRYPT);"
```

Lalu insert ke database dengan hash tersebut.

---

## 📅 Version & Update

- **Version:** 1.0
- **Last Updated:** December 9, 2025
- **Database Version:** MySQL 5.7+
- **PHP Version:** 7.4+

---

## 📝 License & Credits

Dikembangkan untuk sistem manajemen donasi darah PMI.

---

## ❓ FAQ Cepat

**Q: Berapa password default admin?**
A: `admin123` (HARUS DIUBAH!)

**Q: Bagaimana menambah pendonor baru?**
A: Menu Pendonor → Tambah Pendonor

**Q: Bagaimana track darah sampai rumah sakit?**
A: Via Menu Distribusi → Lihat tracking status

**Q: Apa bedanya stok dan transaksi?**
A: Transaksi = catatan 1 kali donasi dari 1 pendonor. Stok = per kantong darah (bisa multiple dari 1 transaksi).

**Q: Berapa lama darah expired?**
A: Default 35 hari (bisa diubah di Model)

---

**Need help?** Lihat DOCUMENTATION.md atau SETUP_GUIDE.md
