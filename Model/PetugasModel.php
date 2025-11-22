<?php
class PetugasModel {
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function SoftDeletePetugas($id_petugas) {
        $builder = $this->db->table('Petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update([
                          'status' => 'nonaktif',
                          'deleted_at' => date('Y-m-d H:i:s')
                      ]);
    }

    public function restorePetugas($id_petugas) {
        $builder = $this->db->table('Petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update([
                          'status' => 'aktif',
                          'deleted_at' => null
                      ]);
    }

    public function getTrashedPetugas() {
        $builder = $this->db->table('Petugas');
        return $builder->where('deleted_at IS NOT NULL')
                      ->orWhere('status', 'nonaktif')
                      ->get()
                      ->getResultArray();
    }

    // Method tambahan
    public function getAllPetugas() {
        $builder = $this->db->table('Petugas');
        return $builder->get()->getResultArray();
    }

    public function getPetugasById($id_petugas) {
        $builder = $this->db->table('Petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->get()
                      ->getRowArray();
    }
}