# Sistem Manajemen Donor Darah PMI

## Pendahuluan

**Nama Proyek:** Sistem Manajemen Donor Darah PMI  
**Tujuan:** Mengelola data pendonor, stok darah, transaksi donasi, kegiatan donasi, dan distribusi darah secara terpusat dan terorganisir  
**Teknologi:** PHP 7.4+, MySQL, Bootstrap 5, PDO (Database Abstraction), Query Builder Custom  
**Bahasa:** Bahasa Indonesia

---

## Gambaran Umum Fitur

Aplikasi ini menyediakan modul-modul utama untuk mengelola sistem PMI:

1. **Authentication (Autentikasi)**

   - Login/Logout untuk petugas
   - Session management
   - Password hashing dengan BCRYPT

2. **Dashboard**

   - Overview stok darah real-time
   - Statistik jumlah kantong per golongan darah
   - Status stok (tersedia, terpakai, kadaluarsa)

3. **Manajemen Pendonor**

   - Registrasi pendonor baru
   - Data screening kesehatan pendonor
   - Riwayat donasi per pendonor
   - Edit/delete pendonor

4. **Manajemen Transaksi Donasi**

   - Input data donasi dari kegiatan
   - Automatic stok creation setelah pengujian darah
   - Status uji (lolos/tidak lolos)
   - Tracking tanggal donasi

5. **Manajemen Stok Darah**

   - Lihat stok darah per golongan
   - Update status uji dan tanggal kadaluarsa
   - Soft delete dan arsip
   - Real-time status (tersedia, terpakai, kadaluarsa)

6. **Manajemen Distribusi**

   - Input penerimaan darah oleh rumah sakit
   - Tracking status distribusi (diajukan, diterima, selesai)

7. **Manajemen Kegiatan Donasi**

   - CRUD kegiatan donasi
   - Soft delete kegiatan

8. **Manajemen Petugas (Admin)**
   - Profile management (edit nama, email, password)
   - User roles (saat ini semua petugas same role)

---

## Struktur Folder & File

```
TUBES-PL/
├── Config/
│   ├── Database.php          # Koneksi database PDO
│   └── Path.php              # Helper untuk path file
├── Controllers/
│   ├── PetugasController.php        # Logic authentication & profile
│   ├── PendonorController.php       # Logic pendonor CRUD
│   ├── TransaksiController.php      # Logic transaksi donasi
│   ├── StokController.php           # Logic stok darah
│   ├── DistribusiController.php     # Logic distribusi
│   └── LaporanController.php        # Logic laporan
├── Model/
│   ├── QueryBuilder.php       # Custom query builder (ORM sederhana)
│   ├── PetugasModel.php       # Query pendonor ke DB
│   ├── PendonorModel.php      # Query pendonor ke DB
│   ├── TransaksiModel.php     # Query transaksi ke DB
│   ├── StokModel.php          # Query stok ke DB
│   ├── DistribusiModel.php    # Query distribusi ke DB
│   ├── KegiatanModel.php      # Query kegiatan ke DB
│   └── LaporanModel.php       # Query laporan ke DB
├── View/
│   ├── dashboard/
│   │   └── index.php          # Dashboard home page
│   ├── petugas/
│   │   ├── login.php          # Login form
│   │   ├── profile.php        # Profile page
│   │   └── edit.php           # Edit profile
│   ├── pendonor/
│   │   ├── index.php          # List pendonor
│   │   ├── create.php         # Form tambah pendonor
│   │   ├── edit.php           # Form edit pendonor
│   │   ├── detail.php         # Detail pendonor
│   │   ├── riwayat.php        # History donasi pendonor
│   │   └── trash.php          # Daftar pendonor terhapus
│   ├── transaksi/
│   │   ├── index.php          # List transaksi
│   │   ├── create.php         # Form input transaksi
│   │   ├── edit.php           # Form edit transaksi
│   │   ├── detail.php         # Detail transaksi
│   │   ├── lacak.php          # Tracking transaksi
│   │   └── trash.php          # Transaksi terhapus
│   ├── stok/
│   │   ├── index.php          # List stok darah
│   │   ├── create.php         # Form buat stok (disabled)
│   │   ├── edit.php           # Form edit stok (disabled)
│   │   ├── detail.php         # Detail stok + input status uji
│   │   ├── trash.php          # Stok terhapus
│   │   └── update_group.php   # Batch update stok
│   ├── distribusi/
│   │   ├── index.php          # List distribusi
│   │   ├── create.php         # Form distribusi baru
│   │   ├── create_grouped.php # Distribusi grouped
│   │   ├── edit.php           # Edit distribusi
│   │   ├── detail.php         # Detail distribusi
│   │   ├── lacak.php          # Tracking distribusi
│   │   └── trash.php          # Distribusi terhapus
│   ├── kegiatan/
│   │   ├── index.php          # List kegiatan
│   │   ├── create.php         # Form tambah kegiatan
│   │   ├── edit.php           # Form edit kegiatan
│   │   ├── detail.php         # Detail kegiatan
│   │   └── trash.php          # Kegiatan terhapus
│   ├── rumah_sakit/
│   │   ├── index.php          # List rumah sakit
│   │   ├── create.php         # Form tambah RS
│   │   ├── edit.php           # Form edit RS
│   │   ├── detail.php         # Detail RS
│   │   ├── laporan.php        # Laporan distribusi RS
│   │   └── trash.php          # RS terhapus
│   └── template/
│       ├── header.php         # Header navbar
│       ├── footer.php         # Footer
│       ├── alerts.php         # Flash message component
│       ├── pagination.php     # Pagination component
│       ├── toast.php          # Toast notification
│       ├── auth_header.php    # Header untuk halaman login
│       ├── auth_footer.php    # Footer untuk halaman login
│       └── assets/
│           ├── css/
│           │   ├── ui.css                 # Styling PMI theme
│           │   └── custom-overrides.css   # Custom color overrides
│           ├── js/
│           │   └── ui.js                  # JavaScript utilities
│           └── img/                       # Logo dan image
├── seeds/
│   └── DATABASE.sql           # SQL schema & initial data
├── index.php                  # Router utama (entry point)
├── api_delete.php             # API untuk soft delete
└── README.md                  # Documentation (file ini)
```

---

## Alur Data & Request-Response

### 1. Flow Umum Request

```
User (Browser)
    ↓ [GET/POST to index.php?action=...]
index.php (Router)
    ↓ [Match action parameter]
Controller (e.g., PendonorController)
    ↓ [Process request, call Model methods]
Model (e.g., PendonorModel)
    ↓ [Execute query via QueryBuilder → PDO]
Database (MySQL)
    ↓ [Return query result]
Model
    ↓ [Return data array]
Controller
    ↓ [Extract data, include View file]
View (PHP template)
    ↓ [Render HTML + send to browser]
User (Browser)
```

### 2. Authentication Flow

```
1. User open index.php?action=login
   → view/petugas/login.php (form login)

2. User submit form (POST)
   → index.php?action=authenticate
   → PetugasController::authenticate()
   → Query DB cari email & verify password
   → Set $_SESSION['isLoggedIn'] = true
   → Redirect ke dashboard

3. User akses halaman lain
   → Controller checkAuth() → Check $_SESSION['isLoggedIn']
   → Jika tidak login, redirect ke login page

4. User logout
   → index.php?action=logout
   → session_destroy()
   → Redirect ke login
```

### 3. Stok Creation Flow (Otomatis dari Transaksi)

```
1. Input transaksi donasi (nama pendonor, tanggal, etc)
   → TransaksiController::store()
   → Insert ke tabel transaksi_donasi
   → Redirect ke stok detail form

2. Petugas isi hasil pengujian (status uji, tanggal kadaluarsa)
   → StokController::storeInputPascaUji()
   → Create stok baru di tabel stok_darah
   → Insert history transaksi_donasi_detail

3. Stok now tersedia untuk distribusi
```

---

## Entity Relationship Diagram (ERD) & Penjelasan Hubungan Data

### 4. ERD - Struktur Database Visual

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         SISTEM MANAJEMEN DONOR DARAH                         │
└─────────────────────────────────────────────────────────────────────────────┘

                              ┌──────────────────┐
                              │  GOLONGAN_DARAH  │
                              │  (Master Data)   │
                              ├──────────────────┤
                              │ id_gol_darah (PK)│
                              │ nama_gol_darah   │
                              │ rhesus (+/-)     │
                              └────────┬─────────┘
                                       │ (1:M)
                    ┌──────────────────┼──────────────────┐
                    │                  │                  │
                    ↓                  ↓                  ↓
        ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
        │    PENDONOR      │ │   STOK_DARAH     │ │  TRANSAKSI_DONASI│
        ├──────────────────┤ ├──────────────────┤ ├──────────────────┤
        │ id_pendonor (PK) │ │ id_stok (PK)     │ │id_transaksi (PK) │
        │ nama (UNIQUE)    │ │ id_gol_darah (FK)│ │ id_pendonor (FK) │
        │ kontak           │ │ id_transaksi(FK) │ │ id_kegiatan (FK) │
        │ is_layak (0/1)   │ │ status_uji       │ │ tanggal_donasi   │
        │ screening fields │ │ tanggal_pengujian│ │ volume           │
        │ is_deleted       │ │ tgl_kadaluarsa   │ │ is_deleted       │
        └──────────┬───────┘ │ status (status)  │ └────────┬─────────┘
                   │          │ is_deleted       │          │
                   │          └────────┬─────────┘          │
                   │                   │                    │
                   └─────────┬─────────┴────────────────────┘
                             │
                   ┌─────────┴─────────┐
                   │                   │
                   ↓                   ↓
        ┌──────────────────┐ ┌──────────────────┐
        │  KEGIATAN_DONASI │ │    DISTRIBUSI    │
        ├──────────────────┤ ├──────────────────┤
        │id_kegiatan (PK)  │ │id_distribusi (PK)│
        │ nama_kegiatan    │ │ id_stok (FK)     │
        │ tanggal          │ │ id_rs (FK)       │
        │ lokasi           │ │ tgl_pengajuan    │
        │ is_deleted       │ │ status (status)  │
        └──────────────────┘ │ jml_kantong      │
                             │ is_deleted       │
                             └────────┬─────────┘
                                      │ (M:1)
                                      │
                        ┌─────────────┴──────────┐
                        │                        │
                        ↓                        ↓
                ┌──────────────────┐  ┌──────────────────┐
                │   RUMAH_SAKIT    │  │    PETUGAS       │
                ├──────────────────┤  ├──────────────────┤
                │ id_rs (PK)       │  │id_petugas (PK)   │
                │ nama_rs          │  │ nama_petugas     │
                │ alamat_rs        │  │ email (UNIQUE)   │
                │ kontak_rs        │  │ password_hash    │
                │ is_deleted       │  │ kontak           │
                └──────────────────┘  │ status           │
                                      │ is_deleted       │
                                      └──────────────────┘
```

**Keterangan Hubungan (Relationships):**

| From Table       | To Table         | Type | Keterangan                                                 |
| ---------------- | ---------------- | ---- | ---------------------------------------------------------- |
| GOLONGAN_DARAH   | PENDONOR         | 1:M  | Satu golongan darah memiliki banyak pendonor               |
| GOLONGAN_DARAH   | STOK_DARAH       | 1:M  | Satu golongan darah memiliki banyak stok                   |
| PENDONOR         | TRANSAKSI_DONASI | 1:M  | Satu pendonor bisa melakukan banyak transaksi donasi       |
| KEGIATAN_DONASI  | TRANSAKSI_DONASI | 1:M  | Satu kegiatan memiliki banyak transaksi donor              |
| TRANSAKSI_DONASI | STOK_DARAH       | 1:M  | Satu transaksi bisa menghasilkan banyak stok (per kantong) |
| STOK_DARAH       | DISTRIBUSI       | 1:M  | Satu stok darah bisa didistribusikan ke banyak RS          |
| RUMAH_SAKIT      | DISTRIBUSI       | 1:M  | Satu rumah sakit menerima banyak distribusi darah          |

---

### 5. Alur Bisnis Proses (Business Process Flow)

#### **A. Alur Pendaftaran Pendonor**

```
START
  ↓
Petugas buka Menu Pendonor → Tambah Pendonor
  ↓
Form Pendaftaran Pendonor muncul
  ↓
Input data pendonor:
├─ Nama (REQUIRED)
├─ Kontak (REQUIRED - minimum 6 digit)
├─ Golongan Darah (REQUIRED)
├─ Riwayat Penyakit (optional)
├─ Screening Kesehatan:
│  ├─ Hepatitis B/C
│  ├─ AIDS
│  ├─ Hemofilia
│  ├─ Sickle Cell
│  ├─ Thalassemia
│  ├─ Leukemia
│  ├─ Lymphoma
│  ├─ Myeloma
│  ├─ CJD
│  └─ Other Illness (text)
  ↓
Sistem hitung: is_layak = (tidak ada penyakit) ? 1 : 0
  ↓
Save ke tabel PENDONOR
  ↓
Success message
  ↓
Redirect ke list Pendonor
  ↓
END
```

#### **B. Alur Transaksi Donasi → Stok Creation**

```
START
  ↓
Petugas buka Menu Transaksi Donasi → Tambah Transaksi
  ↓
Form Input Transaksi:
├─ Pilih Pendonor (dari PENDONOR table)
├─ Pilih Kegiatan (dari KEGIATAN_DONASI table)
├─ Tanggal Donasi
└─ Volume (ml)
  ↓
Validasi:
├─ Pendonor harus is_layak = 1
└─ Pendonor tidak boleh ulang < 3 bulan
  ↓
INSERT ke TRANSAKSI_DONASI table
  ↓
Redirect ke STOK Detail Input Page
  ↓
Petugas isi hasil pengujian darah:
├─ Status Uji (Lolos / Tidak Lolos)
├─ Tanggal Pengujian
└─ Tanggal Kadaluarsa
  ↓
Validasi: Tgl Kadaluarsa > Tgl Pengujian
  ↓
System CREATE stok baru:
├─ INSERT ke STOK_DARAH
│  ├─ id_gol_darah = dari pendonor
│  ├─ id_transaksi = dari transaksi yang baru dibuat
│  ├─ status_uji = lolos/tidak lolos
│  ├─ status = "tersedia" (default)
│  └─ jumlah_kantong = 1
  ↓
Success message
  ↓
Stok sekarang tersedia untuk DISTRIBUSI
  ↓
END
```

#### **C. Alur Distribusi Darah ke Rumah Sakit**

```
START
  ↓
Petugas buka Menu Distribusi → Tambah Distribusi
  ↓
Form Input Distribusi:
├─ Pilih Stok Darah (dari STOK_DARAH yang status="tersedia")
├─ Pilih Rumah Sakit (dari RUMAH_SAKIT table)
└─ Jumlah Kantong
  ↓
Validasi: Stok tersedia >= Jumlah yang diminta
  ↓
INSERT ke DISTRIBUSI table:
├─ id_stok = stok yang dipilih
├─ id_rs = rumah sakit yang dipilih
├─ status = "diajukan"
├─ tgl_pengajuan = hari ini
└─ jml_kantong = jumlah
  ↓
UPDATE STOK_DARAH:
├─ status = "terpakai" (jika semua kantong distributed)
└─ atau create 2 stok (1 terpakai, 1 tersisa)
  ↓
Success message
  ↓
Rumah Sakit bisa update status distribusi:
├─ "diajukan" → "diterima" (RS terima darah)
└─ "diterima" → "selesai" (RS selesai gunakan darah)
  ↓
END
```

#### **D. Alur Dashboard & Status Stok Real-time**

```
START (User buka Dashboard)
  ↓
System auto-check STOK_DARAH table:
├─ Update stok yang sudah kadaluarsa:
│  └─ IF tanggal_kadaluarsa < hari_ini THEN status = "kadaluarsa"
  ↓
Query dan COUNT stok per golongan darah:
├─ Total kantong per golongan
├─ Count tersedia (status="tersedia")
├─ Count terpakai (status="terpakai")
└─ Count kadaluarsa (status="kadaluarsa")
  ↓
Display dashboard dengan:
├─ Card per golongan darah (O+, O-, A+, dll)
├─ Warna coding:
│  ├─ Green = tersedia
│  ├─ Yellow = terpakai
│  └─ Red = kadaluarsa
└─ Total summary di atas
  ↓
END
```

---

### 6. Data Flow & Dependency

```
MASTER DATA (Static References)
├─ GOLONGAN_DARAH (Jenis darah)
├─ PETUGAS (Staff login)
└─ RUMAH_SAKIT (Hospital list)

TRANSACTION DATA (Dynamic)
├─ PENDONOR (Donor registry)
│  └─ depends on: GOLONGAN_DARAH
├─ KEGIATAN_DONASI (Donation event)
│  └─ independent (dapat dibuat kapan saja)
└─ TRANSAKSI_DONASI (Donation transaction)
   ├─ depends on: PENDONOR, KEGIATAN_DONASI
   └─ creates: STOK_DARAH (auto-generate stok)

PROCESSING DATA (After Transaction)
├─ STOK_DARAH (Blood inventory)
│  ├─ depends on: TRANSAKSI_DONASI, GOLONGAN_DARAH
│  └─ used by: DISTRIBUSI
└─ DISTRIBUSI (Distribution record)
   ├─ depends on: STOK_DARAH, RUMAH_SAKIT
   └─ tracks: delivery status
```

---

## Penjelasan File-File Penting & Cara Memodifikasinya

### A. Config/Database.php

**Tujuan:** Mengelola koneksi database

**Jika ingin mengubah database:**

- Buka file: `Config/Database.php` baris 14-17
- Ubah:
  - `$host` = alamat server database
  - `$db_name` = nama database
  - `$username` = username database
  - `$password` = password database

**Contoh:**

```php
private $host = "localhost";
private $db_name = "pmi_darah";  // Ganti nama database di sini
private $username = "root";
private $password = "";
```

---

### B. Config/Path.php

**Tujuan:** Helper untuk path ke View dan Template

**Jika ingin menambah path baru:**

- Buka file: `Config/Path.php`
- Tambah method static baru:

```php
public static function uploads($path) {
    return __DIR__ . '/../uploads/' . $path;
}
```

---

### C. Controllers/PetugasController.php

**Tujuan:** Mengelola authentication dan profil petugas

**Bagian yang sering diubah saat ujian:**

1. **Login & Password Verification** (baris 155-185)

   - Query email dari database
   - Verify password dengan `password_verify()`
   - Set session jika berhasil

   _Jika diminta: Ubah validasi password, tambah 2FA, atau custom message_

<!-- 2. **Profile Update** (baris 91-128)
   - Update nama, email, kontak
   - Optional update password
   - Validate email uniqueness

   *Jika diminta: Tambah field baru, atau ubah validasi* -->

3. **Authentication Check** (baris 224-231)

   - Helper method yang dipanggil di awal setiap action
   - Memastikan user sudah login

   _Jika diminta: Tambah role-based access control_

---

### D. Model/QueryBuilder.php

**Tujuan:** Memudahkan query database dengan ORM sederhana

**Contoh penggunaan:**

```php
// SELECT dengan WHERE, JOIN, ORDER BY
$builder = new QueryBuilder($pdo, 'stok_darah sd');
$result = $builder->select('sd.*, gd.nama_gol_darah')
                  ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
                  ->where('sd.status', 'tersedia')
                  ->orderBy('sd.tanggal_kadaluarsa', 'ASC')
                  ->getResultArray();

// INSERT
$builder = new QueryBuilder($pdo, 'pendonor');
$builder->insert([
    'nama' => 'John Doe',
    'kontak' => '081234567890',
    'id_gol_darah' => 1
]);

// UPDATE dengan WHERE
$builder = new QueryBuilder($pdo, 'stok_darah');
$builder->where('id_stok', $id)
        ->update(['status' => 'terpakai']);
```

**Method yang tersedia:**

- `select($columns)` - SELECT query
- `where($column, $value, $operator)` - WHERE condition (AND)
- `orWhere($column, $value, $operator)` - WHERE condition (OR)
- `whereRaw($condition)` - Raw WHERE (gunakan dengan hati-hati)
- `join($table, $condition, $type)` - JOIN clause
- `groupBy($column)` - GROUP BY clause
- `having($condition)` - HAVING clause
- `orderBy($column, $direction)` - ORDER BY clause
- `insert($data)` - INSERT query
- `update($data)` - UPDATE query
- `getResultArray()` - Fetch multiple rows
- `getRowArray()` - Fetch single row
- `execute()` - Execute non-SELECT query

---

### E. Controllers/PendonorController.php

**Tujuan:** CRUD Pendonor + Screening kesehatan

**Bagian yang sering diubah:**

1. **Input Pendonor Baru** (baris 44-134)

   - Validasi input (nama, kontak, golongan darah)
   - Filter kontak hanya angka
   - Check screening fields (penyakit)
   - Calculate `is_layak` (eligible/tidak)

   _Jika diminta: Tambah field screening baru, ubah validasi, atau ubah rules eligibility_

2. **Screening Fields** (baris 67-80)

   - Deteksi penyakit-penyakit tertentu
   - Hanya donor tanpa penyakit yang layak donasi

   _Jika diminta: Tambah/hapus jenis screening, atau ubah logic eligibility_

3. **Edit Pendonor** (baris 168-259)
   - Update semua field termasuk screening
   - Recalculate `is_layak` based on screening

---

### F. Controllers/StokController.php

**Tujuan:** Manajemen stok darah

**Bagian yang sering diubah:**

1. **Dashboard Real-time** (baris 26-31)

   - Auto-update expired statuses
   - Get stok by blood type & rhesus
   - Show counts: available, used, expired

   _Jika diminta: Ubah query untuk custom reports, atau tambah filter_

2. **Stock Detail & Input Hasil Uji** (baris 51-120)

   - Display stok information
   - Input status uji (lolos/tidak lolos)
   - Input tanggal pengujian & kadaluarsa

   _Jika diminta: Tambah field test details, atau ubah status options_

3. **Delete Stock** (baris 77-85)

   - Soft delete (tidak benar-benar dihapus)

   _Jika diminta: Implement hard delete, atau add confirmation_

---

### G. Model/StokModel.php

**Tujuan:** Query stok darah dari database

**Fungsi-fungsi utama:**

1. `getDashboardStokRealtime()` - Get stok count per golongan darah
2. `getStokTersedia()` - Get all available (non-expired) stock
3. `getStokById($id)` - Get detail stok dengan join ke golongan & transaksi
4. `updateStatusStok($id, $status)` - Update status (tersedia/terpakai/kadaluarsa)
5. `updateExpiredStatuses()` - Auto-update stok yang sudah kadaluarsa
6. `createStokPascaUji($id_transaksi, $data)` - Create stok after testing

**Jika diminta: Ubah query untuk custom logic**

---

### H. Views & Template

#### header.php (baris 1-50)

- Navbar dengan brand & navigation menu
- User profile dropdown
- CSS & JS includes
- Material Symbols font

**Jika diminta: Ubah navbar, tambah menu item, atau custom styling**

#### alerts.php

- Flash message component
- Show success/danger/warning/info messages

**Jika diminta: Custom styling messages, atau tambah message types**

#### footer.php

- Footer content
- Copyright info

---

## Database Schema (Ringkas)

### Table: petugas

- `id_petugas` (PK)
- `nama_petugas`
- `email` (UNIQUE)
- `password_hash` (BCRYPT)
- `kontak`
- `status` (aktif/nonaktif)
- `is_deleted` (soft delete)

### Table: pendonor

- `id_pendonor` (PK)
- `nama`
- `kontak` (number only)
- `riwayat_penyakit` (text)
- `id_gol_darah` (FK)
- `has_hepatitis_b`, `has_hepatitis_c`, ... (screening fields)
- `other_illness` (text)
- `is_layak` (1/0)
- `is_deleted` (soft delete)

### Table: transaksi_donasi

- `id_transaksi` (PK)
- `id_pendonor` (FK)
- `id_kegiatan` (FK)
- `tanggal_donasi`
- `volume` (ml)
- `is_deleted` (soft delete)

### Table: stok_darah

- `id_stok` (PK)
- `id_transaksi` (FK)
- `id_gol_darah` (FK)
- `tanggal_pengujian`
- `status_uji` (lolos/tidak lolos)
- `tanggal_kadaluarsa`
- `status` (tersedia/terpakai/kadaluarsa)
- `jumlah_kantong`
- `is_deleted` (soft delete)

### Table: golongan_darah

- `id_gol_darah` (PK)
- `nama_gol_darah` (O, A, B, AB)
- `rhesus` (+/-)

### Table: distribusi

- `id_distribusi` (PK)
- `id_stok` (FK)
- `id_rs` (FK)
- `tanggal_pengajuan`
- `status` (diajukan/diterima/selesai)
- `jumlah_kantong`

### Table: kegiatan_donasi

- `id_kegiatan` (PK)
- `nama_kegiatan`
- `tanggal`
- `lokasi`
- `is_deleted` (soft delete)

### Table: rumah_sakit

- `id_rs` (PK)
- `nama_rs`
- `alamat_rs`
- `kontak_rs`
- `is_deleted` (soft delete)

---

## Bagian-Bagian yang Sering Dimodifikasi Saat Ujian Praktik

### 1. **Validasi Input**

- File: `Controllers/PendonorController.php` (baris 73-87)
- Ubah rules, tambah/hapus validasi field

### 2. **Query Filtering**

- File: `Model/StokModel.php`, `Model/PendonorModel.php`
- Ubah WHERE conditions, JOIN tables, atau ORDER BY

### 3. **Business Logic**

- File: `Controllers/StokController.php`, `Controllers/PendonorController.php`
- Ubah calculation rules (e.g., is_layak), atau add new features

### 4. **Database Schema**

- File: `seeds/DATABASE.sql`
- Ubah/tambah kolom ke table
- Jangan lupa update Model & Controller untuk new columns

### 5. **UI/View**

- File: `View/` directory (all .php files)
- Ubah form fields, table columns, atau styling

### 6. **Routes & URL Parameters**

- File: `index.php` (switch statement)
- Tambah/ubah action routes

---

## Setup & Installation

### Prerequisites

- PHP 7.4+
- MySQL 5.7+
- Laragon / XAMPP / or local PHP server

### Langkah-Langkah

1. **Clone atau copy project ke folder web root**

   ```bash
   cd C:\laragon\www\
   git clone <repo-url> TUBES-PL
   # atau copy folder jika tidak menggunakan git
   ```

2. **Setup Database**

   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Create database baru dengan nama `pmi_darah`
   - Import file: `seeds/DATABASE.sql`

   ```sql
   -- Atau jalankan dari command line:
   mysql -u root pmi_darah < seeds/DATABASE.sql
   ```

3. **Verifikasi Config**

   - Buka `Config/Database.php`
   - Pastikan credentials sesuai (host, username, password)

4. **Run Aplikasi**

   - Buka browser: `http://localhost/TUBES-PL/`
   - Atau `http://127.0.0.1/TUBES-PL/`

5. **Login**
   - Email: (lihat di seed data)
   - Password: (lihat di seed data atau setup akun baru)

---

## Keamanan & Best Practice

### 1. Password

- **Selalu** gunakan `password_hash()` dengan `PASSWORD_BCRYPT`
- **Jangan pernah** simpan password plain text
- Verify dengan `password_verify()`

### 2. SQL Injection Prevention

- Gunakan **QueryBuilder** dengan prepared statements (parameter binding)
- **Jangan** gabung user input langsung ke SQL string

### 3. Session Management

- Check `$_SESSION['isLoggedIn']` di setiap protected page
- Destroy session saat logout

### 4. Soft Delete

- Gunakan `is_deleted` flag (1/0) untuk logical delete
- **Jangan** hard delete data

### 5. Error Handling

- Use `try-catch` untuk exception handling
- Log errors ke `error_log()` untuk debugging

### 6. Input Validation

- Validate dan sanitize semua user input (`$_POST`, `$_GET`)
- Use `trim()`, `htmlspecialchars()`, atau filter functions

---

## Troubleshooting

### 1. Database Connection Error

- **Error:** `Connection error: SQLSTATE[...]`
- **Solution:**
  - Pastikan MySQL server running
  - Cek `Config/Database.php` credentials
  - Cek database name di `$db_name`

### 2. View File Not Found

- **Error:** `View tidak ditemukan: ...`
- **Solution:**
  - Pastikan file view ada di folder `View/`
  - Cek path in Controller `$this->view()`

### 3. Session Not Working

- **Error:** Redirect ke login terus-menerus
- **Solution:**
  - Pastikan `session_start()` dipanggil
  - Check `$_SESSION` values di login form

### 4. QueryBuilder Error

- **Error:** `QueryBuilder Error: ...`
- **Solution:**
  - Check prepared statement syntax
  - Pastikan parameters count sesuai dengan `?` placeholders

### 5. Blank Screen

- **Solution:**
  - Check `error_log()` file (usually di `C:\laragon\logs\`)
  - Enable `error_reporting(E_ALL)` untuk debug mode

---

## Tips untuk Ujian Praktik

### Sebelum Ujian

1. Pahami alur request-response di `index.php`
2. Baca seluruh Controller & Model files
3. Understand QueryBuilder syntax dan prepared statements
4. Practice membuat form & tambah field baru
5. Backup database schema

### Saat Ujian

1. **Baca soal dengan teliti** - pahami requirement dengan detail
2. **Identifikasi file mana yang perlu diubah** - gunakan flow diagram di atas
3. **Ubah file secara bertahap:**
   - Database (jika perlu tambah kolom)
   - Model (ubah query)
   - Controller (ubah logic)
   - View (ubah form/table)
4. **Test setiap perubahan** - jangan tunda-tunda
5. **Gunakan comment** - catat perubahan & alasan

### Contoh Soal & Solusi

**Soal: Tambahkan field "No. Identitas" di form pendonor**

**Solusi:**

1. Buka `seeds/DATABASE.sql`

   - Tambah kolom: `ALTER TABLE pendonor ADD COLUMN no_identitas VARCHAR(20);`

2. Buka `View/pendonor/create.php`

   - Tambah input field: `<input name="no_identitas" class="form-control" required>`

3. Buka `Controllers/PendonorController.php` method `store()` (baris 44+)

   - Ambil input: `'no_identitas' => $_POST['no_identitas']`
   - Tambahkan ke `$data` array

4. Buka `View/pendonor/index.php`

   - Tambah kolom di table: `<th>No. Identitas</th>`
   - Tambah data di loop: `<td><?= htmlspecialchars($pendonor['no_identitas']) ?></td>`

5. Test & verify

---

## Contakt & Support

**Untuk pertanyaan lebih lanjut:**

- Baca dokumentasi di file-file (ada komentar detail)
- Check DATABASE.sql untuk schema
- Lihat View files untuk contoh form/table structure

---

**Dokumentasi ini ditulis dengan bahasa Indonesia formal-akademik untuk memudahkan pemahaman dan penggunaan saat ujian praktik.**

**Terakhir diperbaharui:** Desember 2025
