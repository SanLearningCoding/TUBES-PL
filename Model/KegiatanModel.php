<?php
require_once __DIR__ . '/QueryBuilder.php';

// file baru
// Model/KegiatanModel.php

class KegiatanModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllKegiatan() {
        $builder = new QueryBuilder($this->db, 'kegiatan_donasi');
        return $builder->orderBy('tanggal', 'DESC')
                      ->getResultArray();
    }

    public function createKegiatan($data) {
        $builder = new QueryBuilder($this->db, 'kegiatan_donasi');
        return $builder->insert($data);
    }

    public function getKegiatanById($id_kegiatan) {
        $builder = new QueryBuilder($this->db, 'kegiatan_donasi');
        return $builder->get()
                      ->where('id_kegiatan', $id_kegiatan)
                      ->getRowArray();
    }
}