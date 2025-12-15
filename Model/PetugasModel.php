<?php
require_once __DIR__ . '/QueryBuilder.php';

/**
 * Model/PetugasModel.php
 * 
 * Model untuk mengelola data Petugas (admin/staff) dalam database
 * Menggunakan QueryBuilder untuk membuat query yang aman
 * 
 * CATATAN UMUM:
 * - Status petugas: 'aktif' atau 'nonaktif' (soft delete)
 * - Password disimpan dengan hash BCRYPT (jangan simpan plain text)
 * - Email harus unik per petugas
 */

class PetugasModel {
    protected $db;

    /**
     * __construct()
     * Inisialisasi koneksi database untuk Model ini
     */
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * SoftDeletePetugas($id_petugas)
     * Menandai petugas sebagai nonaktif (soft delete)
     * Data tetap ada di database, hanya statusnya berubah
     * 
     * @param int $id_petugas ID petugas yang akan dinonaktifkan
     * @return bool true jika berhasil, false jika gagal
     */
    public function SoftDeletePetugas($id_petugas) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update([
                          'status' => 'nonaktif'
                      ]);
    }

    /**
     * restorePetugas($id_petugas)
     * Mengaktifkan kembali petugas yang sebelumnya dinonaktifkan
     * 
     * @param int $id_petugas ID petugas yang akan diaktifkan
     * @return bool true jika berhasil, false jika gagal
     */
    public function restorePetugas($id_petugas) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update([
                          'status' => 'aktif'
                      ]);
    }

    /**
     * getTrashedPetugas()
     * Mengambil daftar semua petugas yang telah dinonaktifkan
     * 
     * @return array Array berisi data petugas dengan status 'nonaktif'
     */
    public function getTrashedPetugas() {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('status', 'nonaktif')
                      ->getResultArray();
    }

    /**
     * getAllPetugas()
     * Mengambil semua data petugas aktif dari database
     * 
     * @return array Array berisi semua record petugas
     */
    public function getAllPetugas() {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->select('*')->getResultArray();
    }

    /**
     * getPetugasById($id_petugas)
     * Mengambil data satu petugas berdasarkan ID
     * 
     * @param int $id_petugas ID petugas yang dicari
     * @return array|null Data petugas jika ditemukan, null jika tidak
     */
    public function getPetugasById($id_petugas) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->select('*')
                      ->where('id_petugas', $id_petugas)
                      ->getRowArray();
    }

    /**
     * getPetugasByEmail($email)
     * Mengambil data petugas berdasarkan email
     * Digunakan saat login dan validasi email unik
     * 
     * @param string $email Email petugas yang dicari
     * @return array|null Data petugas jika ditemukan, null jika tidak
     */
    public function getPetugasByEmail($email) {
        $builder = new QueryBuilder($this->db, 'petugas');
        $result = $builder->select('*')
                        ->where('email', $email)
                        ->getRowArray();

        return $result;
    }

    /**
     * insertPetugas($data)
     * Menambahkan petugas baru ke database
     * $data harus berisi: nama_petugas, email, password_hash, kontak, is_deleted
     * 
     * @param array $data Array data petugas yang akan diinsert
     * @return bool true jika berhasil, false jika gagal
     */
    public function insertPetugas($data) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->insert($data);
    }

    /**
     * updatePetugas($id_petugas, $data)
     * Memperbarui data petugas yang sudah ada
     * $data bisa berisi salah satu atau semua field: nama_petugas, email, password_hash, kontak
     * 
     * @param int $id_petugas ID petugas yang akan diupdate
     * @param array $data Array berisi field yang akan diupdate
     * @return bool true jika berhasil, false jika gagal
     */
    public function updatePetugas($id_petugas, $data) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update($data);
    }
}
