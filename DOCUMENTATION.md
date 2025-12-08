# 📚 DOKUMENTASI LENGKAP TUBES-PL

## Sistem Manajemen Donasi Darah PMI (TUBES-PL)

**Last Updated:** December 9, 2025  
**Version:** 1.0  
**Database:** MySQL / MariaDB

---

## 📖 Daftar Isi

1. [Pengenalan Sistem](#pengenalan-sistem)
2. [Arsitektur Aplikasi](#arsitektur-aplikasi)
3. [Struktur Database](#struktur-database)
4. [Alur Kerja Sistem](#alur-kerja-sistem)
5. [Panduan Pengguna](#panduan-pengguna)
6. [Cara Menambah Akun Admin](#cara-menambah-akun-admin)
7. [Fitur-Fitur Utama](#fitur-fitur-utama)
8. [Troubleshooting](#troubleshooting)

---

## Pengenalan Sistem

### Apa itu TUBES-PL?

TUBES-PL (Tubes Pendonor Logistik) adalah sistem manajemen donasi darah berbasis web yang dirancang untuk membantu PMI (Palang Merah Indonesia) dalam mengelola:

- 🩸 **Data Pendonor** - Database pendonor dengan screening kesehatan
- 📋 **Transaksi Donasi** - Pencatatan setiap donasi darah
- 📦 **Stok Darah** - Inventory darah dengan tracking per unit kantong
- 🏥 **Distribusi** - Pendistribusian darah ke rumah sakit mitra
- 📊 **Laporan** - Statistik dan analisis donasi
- 👥 **Petugas** - Manajemen staff dengan authentication

### Teknologi yang Digunakan

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** Bootstrap 5, JavaScript, FontAwesome Icons
- **Security:** Password hashing (bcrypt), Soft delete, Foreign keys

---

## Arsitektur Aplikasi

### Model-View-Controller (MVC)

```
TUBES-PL/
│
├── Config/
│   ├── Database.php        ← Koneksi database (PDO)
│   └── Path.php            ← Helper untuk path file
│
├── Model/                  ← Business logic & database queries
│   ├── StokModel.php       ← Mengelola stok darah
│   ├── TransaksiModel.php  ← Mengelola transaksi donasi
│   ├── DistribusiModel.php ← Mengelola distribusi darah
│   ├── PendonorModel.php   ← Mengelola data pendonor
│   ├── RumahSakitModel.php ← Mengelola rumah sakit
│   ├── PetugasModel.php    ← Mengelola petugas/staff
│   ├── KegiatanModel.php   ← Mengelola kegiatan donasi
│   └── QueryBuilder.php    ← Helper untuk query building
│
├── Controllers/            ← Routing & request handling
│   ├── TransaksiController.php
│   ├── DistribusiController.php
│   ├── PendonorController.php
│   ├── StokController.php
│   ├── PetugasController.php
│   ├── LaporanController.php
│   └── etc.
│
├── View/                   ← Frontend/User Interface
│   ├── dashboard/          ← Dashboard utama
│   ├── pendonor/           ← Halaman pendonor
│   ├── transaksi/          ← Halaman transaksi
│   ├── stok/               ← Halaman stok darah
│   ├── distribusi/         ← Halaman distribusi
│   ├── rumah_sakit/        ← Halaman rumah sakit
│   ├── kegiatan/           ← Halaman kegiatan
│   ├── petugas/            ← Halaman petugas
│   ├── laporan/            ← Halaman laporan
│   ├── template/           ← Template (header, footer, sidebar)
│   │   └── assets/         ← CSS, JS, images
│   └── etc.
│
├── index.php               ← Entry point (routing utama)
└── seeds/
    └── complete_database_setup.sql ← Database schema & seed data
```

### Flow Aplikasi

```
1. User mengakses index.php
   ↓
2. index.php menerima parameter ?action=...
   ↓
3. Routing ke Controller yang sesuai
   ↓
4. Controller memanggil Model untuk database operations
   ↓
5. Model menjalankan query ke database
   ↓
6. Data dikembalikan ke Controller
   ↓
7. Controller mengirim data ke View
   ↓
8. View menampilkan HTML ke browser
```

---

## Struktur Database

### Diagram Relasi Tabel

```
golongan_darah (Blood Types)
    ↑↑
    ║╚═══════════════════════════════════════════════════╗
    ║                                                    ║
    ║                                    pendonor (Donors)
    ║                                        ↑
    ║                                        ║
    ║                                        ║
petugas (Staff) ←──────────────────────────── ║
    ↑                                         ║
    ║                                         ║
    ║ transaksi_donasi (Donation Transactions)
    ║ id_transaksi ← PRIMARY KEY
    │              └──→ stok_darah (Blood Stock)
    │                    │
    │                    ↓
    │             golongan_darah
    │             rumah_sakit
    │             distribusi_darah ←────────┘
    │
    kegiatan_donasi (Donation Activities)
         ↑
         ║
         ║
    transaksi_donasi
```

### Detail Tabel

#### 1. **golongan_darah** (Blood Types)

| Kolom          | Tipe        | Deskripsi          |
| -------------- | ----------- | ------------------ |
| id_gol_darah   | INT         | Primary Key        |
| nama_gol_darah | VARCHAR(10) | A, B, O, AB        |
| rhesus         | VARCHAR(5)  | +/-                |
| keterangan     | TEXT        | Informasi tambahan |

**Data seed:** 8 kombinasi (O+, O-, A+, A-, B+, B-, AB+, AB-)

#### 2. **petugas** (Staff/Officers)

| Kolom         | Tipe         | Deskripsi                    |
| ------------- | ------------ | ---------------------------- |
| id_petugas    | INT          | Primary Key                  |
| nama_petugas  | VARCHAR(100) | Nama lengkap                 |
| email         | VARCHAR(100) | Email (UNIQUE)               |
| password_hash | VARCHAR(255) | Hash password (bcrypt)       |
| nip           | VARCHAR(50)  | Nomor induk pegawai          |
| jabatan       | VARCHAR(50)  | Posisi (Admin, Petugas, dll) |
| kontak        | VARCHAR(20)  | Nomor telepon                |
| is_deleted    | TINYINT(1)   | Soft delete flag             |
| deleted_at    | DATETIME     | Waktu penghapusan            |

**Default admin:**

- Email: `admin@pmidarah.local`
- Password: `admin123` (HARUS DIUBAH SETELAH LOGIN)

#### 3. **pendonor** (Donors)

| Kolom                                      | Tipe         | Deskripsi                               |
| ------------------------------------------ | ------------ | --------------------------------------- |
| id_pendonor                                | INT          | Primary Key                             |
| nama                                       | VARCHAR(150) | Nama lengkap pendonor                   |
| kontak                                     | VARCHAR(20)  | Nomor telepon                           |
| id_gol_darah                               | INT          | Foreign key ke golongan_darah           |
| has_hepatitis_b, has_hepatitis_c, has_aids | TINYINT(1)   | Screening diseases (0/1)                |
| other_illness                              | TEXT         | Penyakit lainnya                        |
| is_layak                                   | TINYINT(1)   | Flag kelayakan donor (1=layak, 0=tidak) |
| is_deleted                                 | TINYINT(1)   | Soft delete flag                        |

#### 4. **kegiatan_donasi** (Donation Activities)

| Kolom         | Tipe         | Deskripsi           |
| ------------- | ------------ | ------------------- |
| id_kegiatan   | INT          | Primary Key         |
| nama_kegiatan | VARCHAR(200) | Nama event/kegiatan |
| tanggal       | DATE         | Tanggal kegiatan    |
| lokasi        | VARCHAR(200) | Lokasi kegiatan     |
| keterangan    | TEXT         | Deskripsi detail    |
| is_deleted    | TINYINT(1)   | Soft delete flag    |

#### 5. **transaksi_donasi** (Donation Transactions)

| Kolom          | Tipe       | Deskripsi                       |
| -------------- | ---------- | ------------------------------- |
| id_transaksi   | INT        | Primary Key                     |
| id_pendonor    | INT        | FK ke pendonor                  |
| id_kegiatan    | INT        | FK ke kegiatan_donasi           |
| id_petugas     | INT        | FK ke petugas (yang mencatat)   |
| tanggal_donasi | DATE       | Tanggal donasi                  |
| jumlah_kantong | INT        | Jumlah kantong darah (sering 1) |
| catatan        | TEXT       | Catatan transaksi               |
| is_deleted     | TINYINT(1) | Soft delete flag                |

#### 6. **stok_darah** (Blood Stock/Inventory)

| Kolom              | Tipe       | Deskripsi                                                |
| ------------------ | ---------- | -------------------------------------------------------- |
| id_stok            | INT        | Primary Key                                              |
| id_transaksi       | INT        | FK ke transaksi_donasi                                   |
| id_gol_darah       | INT        | FK ke golongan_darah                                     |
| tanggal_pengujian  | DATE       | Tanggal tes kesehatan darah                              |
| status_uji         | ENUM       | belum_uji, lolos, tidak_lolos                            |
| status             | ENUM       | belum_uji, tersedia, terpakai, kadaluarsa, terdistribusi |
| tanggal_kadaluarsa | DATE       | Tanggal ekspirasi (biasanya 35/42 hari)                  |
| volume_ml          | INT        | Volume darah (default 450ml)                             |
| is_deleted         | TINYINT(1) | Soft delete flag                                         |

**PENTING:** 1 baris stok = 1 kantong darah

#### 7. **rumah_sakit** (Hospitals/Partners)

| Kolom      | Tipe         | Deskripsi         |
| ---------- | ------------ | ----------------- |
| id_rs      | INT          | Primary Key       |
| nama_rs    | VARCHAR(150) | Nama rumah sakit  |
| alamat     | TEXT         | Alamat lengkap    |
| kontak     | VARCHAR(20)  | Nomor telepon     |
| email      | VARCHAR(100) | Email rumah sakit |
| is_deleted | TINYINT(1)   | Soft delete flag  |

#### 8. **distribusi_darah** (Blood Distribution)

| Kolom              | Tipe       | Deskripsi                          |
| ------------------ | ---------- | ---------------------------------- |
| id_distribusi      | INT        | Primary Key                        |
| id_stok            | INT        | FK ke stok_darah                   |
| id_rs              | INT        | FK ke rumah_sakit                  |
| id_petugas         | INT        | FK ke petugas (yang mendistribusi) |
| tanggal_distribusi | DATE       | Tanggal pengiriman                 |
| jumlah_volume      | INT        | Volume yang didistribusi           |
| status             | ENUM       | dikirim, diterima, dibatalkan      |
| catatan            | TEXT       | Catatan distribusi                 |
| is_deleted         | TINYINT(1) | Soft delete flag                   |

---

## Alur Kerja Sistem

### 1. **Alur Pendaftaran Pendonor**

```
Pendonor Baru
    ↓
Admin input: nama, kontak, golongan darah, screening penyakit
    ↓
Sistem cek screening → Jika ada penyakit berat → is_layak = 0 (Tidak Layak)
    ↓
Data tersimpan di tabel pendonor
    ↓
Pendonor siap untuk transaksi donasi
```

### 2. **Alur Transaksi Donasi**

```
Pendonor datang ke kegiatan donasi
    ↓
Petugas input transaksi:
  - Pilih pendonor
  - Pilih kegiatan
  - Tanggal donasi
  - Jumlah kantong (biasanya 1)
    ↓
Sistem OTOMATIS membuat stok_darah:
  - Untuk setiap kantong, buat 1 baris di tabel stok_darah
  - id_transaksi di-link dari transaksi_donasi
  - id_gol_darah diambil dari pendonor
  - status awal: 'belum_uji'
  - tanggal_kadaluarsa: +35 hari dari tanggal donasi
    ↓
Data tersimpan & siap untuk testing/distribusi
```

### 3. **Alur Testing Darah**

```
Stok darah yang baru diterima
    ↓
Petugas input hasil tes:
  - Status test: lolos / tidak lolos
  - Tanggal test
    ↓
Jika LOLOS:
  - status_uji = 'lolos'
  - status = 'tersedia'

Jika TIDAK LOLOS:
  - status_uji = 'tidak_lolos'
  - status = 'tidak_lolos' (tidak bisa didistribusi)
    ↓
Stok siap untuk distribusi (jika lolos)
```

### 4. **Alur Distribusi Darah ke Rumah Sakit**

```
Admin akan mendistribusi stok tersedia
    ↓
Pilih stok darah yang lolos tes
    ↓
Input data distribusi:
  - Pilih stok darah
  - Pilih rumah sakit tujuan
  - Tanggal distribusi
  - Status: dikirim / diterima / dibatalkan
    ↓
Sistem UPDATE stok_darah:
  - status = 'terdistribusi'
    ↓
Record distribusi tersimpan
    ↓
Rumah sakit menerima darah
```

### 5. **Alur Screening Otomatis**

```
Admin input screening saat mendaftar pendonor
    ↓
Sistem cek: Ada penyakit berbahaya? (Hepatitis, AIDS, dll)
    ↓
YA → is_layak = 0 (Tidak boleh donor)
TIDAK → is_layak = 1 (Boleh donor)
    ↓
Sistem memfilter pendonor layak otomatis di transaksi
```

---

## Panduan Pengguna

### Login

1. Akses `http://localhost/TUBES-PL/TUBES-PL/`
2. Klik "Login" atau akses `/index.php?action=petugas_login`
3. Input email: `admin@pmidarah.local`
4. Input password: `admin123`
5. Klik "Masuk"

### Dashboard

Setelah login, Anda akan melihat:

- 📊 Statistik donasi bulan ini
- 📦 Stok darah tersedia per golongan darah
- 🩸 Riwayat donasi terbaru
- 📈 Grafik distribusi

### Menu Utama

#### 1. **Data Pendonor**

- 🔍 Lihat daftar semua pendonor
- ➕ Tambah pendonor baru (Ctrl: nama, kontak, golongan darah, screening)
- ✏️ Edit data pendonor
- 👁️ Lihat detail & riwayat donasi
- 🗑️ Arsip pendonor (soft delete)

#### 2. **Transaksi Donasi**

- 🔍 Lihat riwayat transaksi
- ➕ Buat transaksi baru (input: pendonor, kegiatan, jumlah kantong)
- 👁️ Detail transaksi
- 🗑️ Arsip transaksi

#### 3. **Stok Darah**

- 🔍 Lihat inventory darah (per unit/kantong)
- 📋 Filter by golongan darah & status
- ✏️ Update status testing
- 👁️ Detail stok (termasuk info pendonor)
- 📊 Laporan stok

#### 4. **Distribusi Darah**

- 🔍 Lihat riwayat distribusi
- ➕ Buat distribusi baru (pilih stok, rumah sakit)
- ✏️ Update status distribusi
- 👁️ Track distribusi
- 📊 Laporan distribusi per rumah sakit

#### 5. **Rumah Sakit**

- 🔍 Daftar rumah sakit mitra
- ➕ Tambah rumah sakit baru
- ✏️ Edit data rumah sakit
- 📊 Laporan distribusi per RS

#### 6. **Kegiatan Donasi**

- 🔍 Daftar kegiatan/event
- ➕ Buat kegiatan baru
- ✏️ Edit kegiatan
- 📊 Laporan per kegiatan

#### 7. **Laporan**

- 📊 Laporan donasi per bulan
- 📈 Laporan kinerja pendonor
- 📦 Evaluasi stok darah
- 🏥 Laporan distribusi per rumah sakit

---

## Cara Menambah Akun Admin

### METODE 1: Via Database (Recommended)

#### Langkah 1: Persiapan Password

Buka terminal dan jalankan command berikut untuk hash password yang Anda inginkan:

```bash
# Linux/Mac
php -r "echo password_hash('PASSWORD_ANDA', PASSWORD_BCRYPT);"

# Windows (dengan PHP terinstall)
php -r "echo password_hash('PASSWORD_ANDA', PASSWORD_BCRYPT);"
```

**Contoh:**

```bash
$ php -r "echo password_hash('MySecurePass123', PASSWORD_BCRYPT);"
# Output: $2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
```

Catat hash yang dihasilkan.

#### Langkah 2: Input ke Database

Akses PhpMyAdmin atau MySQL client, jalankan query:

```sql
USE pmi_darah;

INSERT INTO petugas (nama_petugas, email, password_hash, nip, jabatan, kontak)
VALUES
('Nama Admin Baru', 'email@pmidarah.local', '$2y$10$HASH_ANDA_DI_SINI', '00002', 'Administrator', '0812-XXXX-XXXX');
```

**Contoh lengkap:**

```sql
INSERT INTO petugas (nama_petugas, email, password_hash, nip, jabatan, kontak)
VALUES
('Budi Santoso', 'budi@pmidarah.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36jGgKTm', '00002', 'Administrator', '0812-3456-7890');
```

#### Langkah 3: Verifikasi

1. Logout dari akun sekarang
2. Login dengan email baru: `email@pmidarah.local`
3. Password: sesuai yang Anda hash
4. Jika berhasil, akun admin baru sudah terbuat!

---

### METODE 2: Via Form di Aplikasi (Butuh Manual Edit)

Saat ini, TUBES-PL tidak memiliki form untuk menambah admin via interface. Untuk menambahkannya:

#### Step 1: Buat form di `View/petugas/create.php`

Edit bagian form untuk include password field:

```php
<form action="?action=petugas_store" method="POST">
    <div class="mb-3">
        <label for="nama_petugas" class="form-label">Nama Petugas *</label>
        <input type="text" class="form-control" id="nama_petugas" name="nama_petugas" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email *</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password *</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>

    <div class="mb-3">
        <label for="jabatan" class="form-label">Jabatan *</label>
        <select class="form-control" id="jabatan" name="jabatan" required>
            <option value="Administrator">Administrator</option>
            <option value="Petugas">Petugas</option>
            <option value="Manager">Manager</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Tambah Petugas</button>
</form>
```

#### Step 2: Update Controller (`Controllers/PetugasController.php`)

Di bagian `petugas_store`, tambahkan hashing password:

```php
if ($action === 'petugas_store') {
    $nama_petugas = $_POST['nama_petugas'];
    $email = $_POST['email'];
    $password = $_POST['password']; // plaintext dari form
    $jabatan = $_POST['jabatan'];
    $nip = $_POST['nip'] ?? '';
    $kontak = $_POST['kontak'] ?? '';

    // HASH PASSWORD
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert ke database
    $query = "INSERT INTO petugas (nama_petugas, email, password_hash, nip, jabatan, kontak)
              VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($query);
    $stmt->execute([$nama_petugas, $email, $password_hash, $nip, $jabatan, $kontak]);

    // Redirect
    header('Location: ?action=petugas');
    exit;
}
```

#### Step 3: Update login validation

Di file login handling (biasanya di index.php), pastikan menggunakan `password_verify()`:

```php
// Login process
$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT * FROM petugas WHERE email = ? AND is_deleted = 0";
$stmt = $db->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password_hash'])) {
    // Login berhasil
    $_SESSION['petugas_id'] = $user['id_petugas'];
    $_SESSION['nama_petugas'] = $user['nama_petugas'];
    header('Location: ?action=dashboard');
} else {
    // Login gagal
    echo "Email atau password salah!";
}
```

---

## Fitur-Fitur Utama

### 1. **Soft Delete (Archive)**

Semua tabel support soft delete - data tidak benar-benar dihapus, hanya ditandai sebagai deleted:

```sql
-- Data masih ada tapi tidak ditampilkan
UPDATE pendonor SET is_deleted = 1, deleted_at = NOW() WHERE id_pendonor = 1;

-- Lihat hanya data aktif (default)
SELECT * FROM pendonor WHERE is_deleted = 0;

-- Lihat arsip
SELECT * FROM pendonor WHERE is_deleted = 1;
```

### 2. **Screening Otomatis Pendonor**

Jika pendonor memiliki penyakit berbahaya (Hepatitis, AIDS, dll), sistem otomatis set `is_layak = 0`:

```php
// Di PendonorController.php
if ($has_hepatitis_b || $has_hepatitis_c || /* ... */ $has_cjd) {
    $is_layak = 0; // Tidak layak donor
} else {
    $is_layak = 1; // Layak donor
}
```

### 3. **Auto-Generate Stok Darah**

Saat transaksi donasi dibuat dengan jumlah kantong > 1, sistem otomatis membuat record di `stok_darah`:

```php
// Jika pendonor donasi 3 kantong:
INSERT INTO stok_darah (...) VALUES (...);  // Kantong 1
INSERT INTO stok_darah (...) VALUES (...);  // Kantong 2
INSERT INTO stok_darah (...) VALUES (...);  // Kantong 3
```

### 4. **Tracking Stok Darah**

Setiap kantong darah bisa di-track statusnya:

- `belum_uji` → Baru diterima, belum di-test
- `lolos` → Test berhasil, tersedia untuk distribusi
- `tidak_lolos` → Test gagal, tidak bisa digunakan
- `terpakai` → Sudah dipakai di rumah sakit
- `kadaluarsa` → Masa berlaku habis

### 5. **Foreign Key Constraints**

Database menjamin integritas data dengan constraints:

```sql
-- Tidak bisa hapus pendonor jika masih ada transaksi
FOREIGN KEY (id_pendonor) REFERENCES pendonor(id_pendonor) ON DELETE RESTRICT

-- Jika kegiatan dihapus, transaksi terkait di-set NULL
FOREIGN KEY (id_kegiatan) REFERENCES kegiatan_donasi(id_kegiatan) ON DELETE SET NULL
```

### 6. **Pagination & Search**

Semua halaman list (pendonor, stok, distribusi, dll) support:

- Pagination (5 item per halaman)
- Search by nama, email, golongan, status, dll

### 7. **Laporan & Statistik**

Di bagian laporan bisa lihat:

- Total donasi per bulan
- Total stok per golongan darah
- Kinerja donor (berapa kali donasi)
- Distribusi per rumah sakit

---

## Troubleshooting

### 1. **Error: "Database connection failed"**

**Solusi:**

```php
// Cek Config/Database.php
// Pastikan setting sesuai dengan environment:
$host = 'localhost';      // MySQL host
$db_name = 'pmi_darah';   // Nama database
$user = 'root';           // Username (default Laragon)
$pass = '';               // Password (default Laragon kosong)
```

### 2. **Error: "Table 'pmi_darah.pendonor' doesn't exist"**

**Solusi:**

- Import file `seeds/complete_database_setup.sql` ke database
- Atau jalankan di terminal MySQL:

```bash
mysql -u root -p pmi_darah < seeds/complete_database_setup.sql
```

### 3. **Login gagal dengan "Email atau password salah"**

**Solusi:**

```sql
-- Verifikasi akun admin ada
SELECT * FROM petugas WHERE email = 'admin@pmidarah.local';

-- Reset password ke default jika lupa
UPDATE petugas SET password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36jGgKTm'
WHERE id_petugas = 1;
-- Password akan menjadi: admin123
```

### 4. **Stok darah tidak bisa dihapus, muncul "FK constraint failed"**

**Solusi:**
Stok yang sudah didistribusi tidak bisa dihapus. Gunakan soft delete (archive) saja:

```php
// Di View, tombol Arsip sudah tersedia
// Ini akan set is_deleted = 1, bukan hard delete
```

### 5. **Pendonor tidak muncul di dropdown transaksi**

**Solusi:**
Pendonor harus:

1. Sudah terdaftar (ada di tabel pendonor)
2. Memiliki golongan darah (id_gol_darah ≠ NULL)
3. Tidak di-archive (is_deleted = 0)
4. Atau jika is_layak = 0 (tidak layak), cek apakah ada filter di transaksi

---

## FAQ

**Q: Berapa lama masa berlaku darah?**  
A: Default 35 hari (bisa disesuaikan di Model). Diatur saat membuat transaksi: `tanggal_donasi + 35 hari = tanggal_kadaluarsa`

**Q: Bisa track darah sampai ke rumah sakit?**  
A: Ya, via tabel distribusi. Setiap stok yang didistribusi dicatat di tabel distribusi_darah dengan id_stok, id_rs, dan status.

**Q: Bisa laporan per pendonor?**  
A: Ya, di halaman pendonor detail ada "Riwayat Donasi" yang menampilkan semua transaksi pendonor tersebut.

**Q: Apa bedanya is_layak dan status di stok_darah?**  
A:

- `is_layak` di pendonor = apakah orang ini boleh donor
- `status` di stok_darah = kondisi darah setelah donor (belum_uji, lolos, tidak_lolos, dll)

---

## Kontak & Support

Untuk pertanyaan lebih lanjut atau melaporkan bug:

- Author: Development Team
- Last Updated: December 9, 2025
- Version: 1.0

---

**END OF DOCUMENTATION**
