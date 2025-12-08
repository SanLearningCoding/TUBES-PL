# 📋 CLEANUP & OPTIMIZATION GUIDE - TUBES-PL

## File yang Bisa Dihapus (Safe to Delete)

### 1. **File Testing & Development**

```
❌ HAPUS DIREKTORI/FILE INI:
- test_transaksi.php          (Testing file untuk transaksi)
- insert_transaksi_today.php  (Script insert data untuk testing)
- api_delete.php              (API endpoint testing yang sudah deprecated)
```

### 2. **File Model Lama (Backup)**

```
❌ HAPUS DIREKTORI/FILE INI:
- Model/DistribusiModel_old.php    (Backup model lama - sudah ada versi baru DistribusiModel.php)
```

### 3. **File View Lama (Backup)**

```
❌ HAPUS DIREKTORI/FILE INI:
- View/distribusi/create_old.php   (Backup view lama - sudah ada versi baru create.php dan create_grouped.php)
```

### 4. **Directory .git (Opsional)**

```
⚠️  PERTIMBANGKAN UNTUK HAPUS:
- .git/                        (Version control history - hapus jika tidak diperlukan lagi)
                               (Bisa menghemat ~10-50MB space)
```

---

## Kode & Fitur yang Sudah Deprecated (Tidak Digunakan)

### 1. **Di index.php - Routing yang Tidak Digunakan**

Cek apakah ada action di switch statement yang tidak ada tombol/link untuk mengaksesnya:

```php
// Contoh: Jika ada case yang tidak pernah diakses, bisa dihapus
// case 'some_deprecated_action':
//     // kode lama yang tidak digunakan lagi
```

### 2. **Di Database - Kolom yang Sudah Ada Alternatif**

- Kolom `riwayat_penyakit` di tabel `pendonor` sekarang ditangani dengan screening fields:
  - `has_hepatitis_b`, `has_hepatitis_c`, `has_aids`, dll
  - `riwayat_penyakit` masih digunakan tapi bisa dianggap legacy

---

## Struktur File yang WAJIB DIPERTAHANKAN

```
TUBES-PL/ ✅ JANGAN DIHAPUS
├── Config/
│   ├── Database.php          (Koneksi database - CRITICAL)
│   └── Path.php              (Path routing - CRITICAL)
├── Controllers/              (Logic aplikasi - CRITICAL)
├── Model/                    (Database models - CRITICAL)
│   ├── StokModel.php         (Manage stok darah)
│   ├── TransaksiModel.php    (Manage transaksi)
│   ├── DistribusiModel.php   (Manage distribusi - JANGAN HAPUS)
│   └── (Model lainnya)
├── View/                     (Frontend HTML - CRITICAL)
│   ├── template/             (Layout & assets - CRITICAL)
│   │   ├── header.php
│   │   ├── footer.php
│   │   ├── assets/           (CSS, JS, images)
│   └── (View lainnya)
├── seeds/
│   └── complete_database_setup.sql  (Database schema - CRITICAL)
└── index.php                 (Entry point - CRITICAL)
```

---

## Rekomendasi Cleanup

### Before Production:

1. ✅ **Hapus file testing:**

   ```bash
   rm test_transaksi.php
   rm insert_transaksi_today.php
   rm api_delete.php
   ```

2. ✅ **Hapus file model lama:**

   ```bash
   rm Model/DistribusiModel_old.php
   ```

3. ✅ **Hapus file view lama:**

   ```bash
   rm View/distribusi/create_old.php
   ```

4. ⚠️ **Hapus git history (OPSIONAL):**

   ```bash
   rm -rf .git
   ```

5. ✅ **Verifikasi tidak ada dead code di index.php**

### After Cleanup:

- Size pengurangan: ~50-100KB (tidak signifikan tapi lebih rapi)
- Security improvement: Menghilangkan file testing yang bisa diakses
- Performance: Minimal improvement, tapi code lebih maintainable

---

## Checklist Pre-Deployment

- [ ] Hapus `test_transaksi.php`
- [ ] Hapus `insert_transaksi_today.php`
- [ ] Hapus `api_delete.php`
- [ ] Hapus `Model/DistribusiModel_old.php`
- [ ] Hapus `View/distribusi/create_old.php`
- [ ] Verifikasi semua fitur masih berfungsi
- [ ] Update database password (jika belum)
- [ ] Set error reporting ke production mode
- [ ] Backup database dan source code

---

**Created:** December 9, 2025  
**Version:** 1.0
