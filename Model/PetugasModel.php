<?php

// Model/PetugasModel.php

class PetugasModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function SoftDeletePetugas($id_petugas) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update([
                          'status' => 'nonaktif'
                      ]);
    }

    public function restorePetugas($id_petugas) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update([
                          'status' => 'aktif'
                      ]);
    }

    public function getTrashedPetugas() {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('status', 'nonaktif')
                      ->getResultArray();
    }

    public function getAllPetugas() {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->get()->getResultArray();
    }

    public function getPetugasById($id_petugas) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->get()
                      ->getRowArray();
    }

    public function getPetugasByEmail($email) {
        $builder = new QueryBuilder($this->db, 'petugas');
        $result = $builder->where('email', $email)
                        ->getRowArray();

        return $result;
    }

    public function insertPetugas($data) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->insert($data);
    }

    public function updatePetugas($id_petugas, $data) {
        $builder = new QueryBuilder($this->db, 'petugas');
        return $builder->where('id_petugas', $id_petugas)
                      ->update($data);
    }
}