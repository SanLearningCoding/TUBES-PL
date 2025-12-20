# OOP, Query Builder, dan PDO — Penjelasan & Contoh di Project

Dokumen ini menjelaskan konsep Object-Oriented Programming (OOP), Query Builder, dan PDO (PHP Data Objects), perbedaan ketika menggunakan atau tidak, serta contoh penggunaan di proyek ini.

---

## 1. Ringkasan singkat

- OOP: pendekatan pemrograman berbasis objek (class/instance) untuk mengorganisir kode.
- PDO: extension PHP untuk akses database menggunakan prepared statements dan driver-agnostic API.
- Query Builder: lapisan abstraksi untuk membangun query SQL secara programatik (lebih aman dan mudah dibaca daripada menulis string SQL mentah berkali-kali).

---

## 2. Object-Oriented Programming (OOP)

### Apa itu

OOP adalah gaya pengembangan yang mengorganisir kode ke dalam kelas (class) dan objek (instance). Kelas mengenkapsulasi data (properties) dan perilaku (methods).

### Keuntungan

- Struktur kode lebih rapi dan modular.
- Memudahkan reuse (inheritance, composition) dan pengujian (unit testing).
- Mempermudah pemeliharaan—perubahan kecil dapat diisolasi dalam sebuah class.

### Dalam proyek ini

- Controller dan Model ditulis dengan pendekatan OOP:
  - Contoh controller: [Controllers/LaporanController.php](Controllers/LaporanController.php)
  - Contoh model: [Model/StokModel.php](Model/StokModel.php)
- `LaporanController` membuat instance model (`new TransaksiModel()`, `new StokModel()`), memanggil method model tersebut, lalu meneruskan data ke view.

Contoh (pseudocode singkat):

```php
class LaporanController {
    private $stokModel;
    public function __construct() {
        $this->stokModel = new StokModel();
    }

    public function viewEvaluasiStok() {
        $data = $this->stokModel->getDashboardStokRealtime();
        $this->view('laporan/evaluasi_stok', ['stok' => $data]);
    }
}
```

---

## 3. PDO (PHP Data Objects)

### Apa itu

PDO adalah extension PHP yang menyediakan interface konsisten untuk akses database. PDO mendukung prepared statements sehingga mencegah SQL injection.

### Keuntungan

- Prepared statements (keamanan terhadap SQL Injection).
- API yang konsisten untuk banyak database (MySQL, PostgreSQL, dll.).
- Mengatur transaksi (beginTransaction/commit/rollback) dengan mudah.

### Dalam proyek ini

- Koneksi database dikelola melalui `Config/Database.php` yang menginisialisasi dan mengembalikan instance PDO.
  - Lihat: [Config/Database.php](Config/Database.php)
- Model menggunakan PDO untuk `prepare()` dan `execute()`.
  - Contoh: di banyak model, mis. [Model/StokModel.php](Model/StokModel.php) terdapat query dengan `prepare()` dan `execute()`.

Contoh (konsep):

```php
// Config/Database.php (ringkasan)
$pdo = new PDO($dsn, $user, $pass, $options);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
return $pdo;

// Penggunaan di Model
$stmt = $this->db->prepare("SELECT * FROM stok_darah WHERE id_stok = ?");
$stmt->execute([$id_stok]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
```

---

## 4. Query Builder (lapisan abstraksi SQL)

### Apa itu

Query Builder adalah utilitas untuk membangun query SQL lewat method chaining atau helper functions, sehingga mengurangi penulisan SQL mentah dan memudahkan pembuatan query dinamis.

### Keuntungan

- Membuat query lebih readable dan maintainable.
- Mengurangi pengulangan penulisan SQL string manual.
- Biasanya membantu mencegah kesalahan penulisan syntax SQL.

### Dalam proyek ini

- Proyek ini memiliki `Model/QueryBuilder.php` — sebuah query builder sederhana yang digunakan oleh model-model lain untuk menyusun query.
  - Lihat: [Model/QueryBuilder.php](Model/QueryBuilder.php)
- Contoh pemakaian: beberapa model memanfaatkan helper builder untuk menyusun SELECT/WHERE/JOIN secara programatik.
  - Contoh referensi penggunaan: [Model/StokModel.php](Model/StokModel.php) menggunakan builder untuk membangun query statistik stok.

Contoh (konsep):

```php
$builder = new QueryBuilder($this->db);
$builder->select('sd.id_stok, sd.status')
        ->from('stok_darah sd')
        ->where('sd.status', 'tersedia')
        ->limit(10);
$sql = $builder->getSQL();
$rows = $builder->execute()->fetchAll();
```

(Tergantung implementasi `QueryBuilder.php` di project ini — lihat file untuk API detail.)

---

## 5. Perbedaan ketika memakai vs tidak

### A. Tanpa OOP / tanpa Model / tanpa Query Builder

- Kode cenderung procedural, query SQL dicampur langsung dalam file tampilan atau controller.
- Sulit di-test dan di-maintain.
- Risiko duplikasi kode tinggi.

Contoh masalah:

```php
// Di file view atau controller: langsung menulis SQL
$result = $db->query("SELECT * FROM stok_darah WHERE status = 'tersedia'");
```

### B. Menggunakan OOP + PDO (tanpa Query Builder)

- Struktur lebih baik: controller memanggil model, model menggunakan PDO.
- Keamanan lewat prepared statements.
- Masih perlu menulis SQL, tapi lebih terorganisir.

Contoh di proyek: Controller → Model → PDO `prepare()`.

### C. Menggunakan OOP + PDO + Query Builder

- Paling rapi dan mudah dikembangkan.
- Query dinamis lebih mudah dibangun tanpa concat string manual.
- Cocok untuk fitur reporting, filtering, dan pagination.

Di proyek ini, kombinasi OOP + PDO + QueryBuilder memberikan balance antara kontrol penuh atas SQL dan kenyamanan penulisan.

---

## 6. Contoh nyata di proyek ini

- `Config/Database.php`: inisialisasi koneksi PDO dan opsi (prepared statements, error mode).
  - File: [Config/Database.php](Config/Database.php)
- `Model/QueryBuilder.php`: helper untuk membangun query (lihat file untuk API lengkap).
  - File: [Model/QueryBuilder.php](Model/QueryBuilder.php)
- Models yang menggunakan PDO dan/atau QueryBuilder:
  - [Model/StokModel.php](Model/StokModel.php)
  - [Model/TransaksiModel.php](Model/TransaksiModel.php)
  - [Model/DistribusiModel.php](Model/DistribusiModel.php)

Contoh alur pemanggilan:

- User membuka halaman laporan stok → `LaporanController::viewEvaluasiStok()` → memanggil `StokModel::getDashboardStokRealtime()` → model menggunakan QueryBuilder/PDO untuk mengambil data → controller memanggil view.
  - Controller: [Controllers/LaporanController.php](Controllers/LaporanController.php)

---

## 7. Rekomendasi dan best practices

- Gunakan prepared statements (PDO) selalu untuk input pengguna.
- Simpan akses DB di satu tempat (`Config/Database.php`) dan gunakan dependency injection ke model jika memungkinkan.
- Gunakan Query Builder untuk query kompleks dan laporan; tetap gunakan raw SQL untuk query sangat spesifik bila perlu, tapi bungkus dalam method model.
- Tambahkan dokumentasi method pada `Model/QueryBuilder.php` dan model-model utama untuk memudahkan developer baru.

---

## 8. Di mana perlu perhatian

- Periksa semua place yang masih menulis SQL mentah di view atau controller — lebih baik pindahkan ke model.
- Pastikan transaction handling (`beginTransaction`, `commit`, `rollback`) digunakan untuk operasi multi-step (mis. distribusi + update stok).

---

Jika Anda mau, saya bisa:

- Menambahkan contoh kode nyata (potongan dari file model) ke dokumen ini.
- Men-generate contoh migrasi kecil atau README tambahan yang menunjukkan cara menulis query menggunakan `QueryBuilder.php` di project ini.
