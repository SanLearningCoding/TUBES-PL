<?php
require_once __DIR__ . '/QueryBuilder.php';

// Model/PendonorModel.php

class PendonorModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Tambahkan getter untuk akses koneksi DB (untuk validasi unik kontak di controller)
    public function getDbConnection() {
        return $this->db;
    }

    private function hasColumn($table, $column) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $column]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($row['cnt']) > 0;
    }

    public function getRiwayatDonasi($id_pendonor) {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
        return $builder->select('td.*, kd.nama_kegiatan, kd.lokasi, p.nama_petugas')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('petugas p', 'td.id_petugas = p.id_petugas', 'LEFT')
                ->where('td.id_pendonor', $id_pendonor)
                ->orderBy('td.tanggal_donasi', 'DESC')
                ->getResultArray();
    }

    public function getDaftarPeringatanDonorUlang() {
        $builder = new QueryBuilder($this->db, 'pendonor p');
        return $builder->select('p.*, MAX(td.tanggal_donasi) as terakhir_donasi')
                ->join('transaksi_donasi td', 'p.id_pendonor = td.id_pendonor', 'LEFT')
                ->groupBy('p.id_pendonor')
                ->having("DATE_ADD(MAX(td.tanggal_donasi), INTERVAL 3 MONTH) <= CURDATE()")
                ->orHaving("terakhir_donasi IS NULL")
                ->getResultArray();
    }

    public function getAllPendonor() {
        if ($this->hasColumn('pendonor', 'id_gol_darah')) {
            $builder = new QueryBuilder($this->db, 'pendonor p');
            return $builder->select('p.*, gd.nama_gol_darah, gd.rhesus')
                ->join('golongan_darah gd', 'p.id_gol_darah = gd.id_gol_darah', 'LEFT')
                ->where('p.is_deleted', 0)
                ->orderBy('p.id_pendonor', 'DESC')
                ->getResultArray();
        } else {
            $builder = new QueryBuilder($this->db, 'pendonor p');
            return $builder->select('p.*')
                ->where('p.is_deleted', 0)
                ->orderBy('p.id_pendonor', 'DESC')
                ->getResultArray();
        }
    }

    public function getPendonorById($id_pendonor) {
        if ($this->hasColumn('pendonor', 'id_gol_darah')) {
            $builder = new QueryBuilder($this->db, 'pendonor p');
            return $builder->select('p.*, gd.nama_gol_darah, gd.rhesus')
                ->join('golongan_darah gd', 'p.id_gol_darah = gd.id_gol_darah', 'LEFT')
                ->where('p.id_pendonor', $id_pendonor)
                ->getRowArray();
        } else {
            $builder = new QueryBuilder($this->db, 'pendonor p');
            return $builder->select('p.*')
                ->where('p.id_pendonor', $id_pendonor)
                ->getRowArray();
        }
    }

    public function insertPendonor($data) {
        $builder = new QueryBuilder($this->db, 'pendonor');
        return $builder->insert($data);
    }

    public function updatePendonor($id_pendonor, $data) {
        $builder = new QueryBuilder($this->db, 'pendonor');
        return $builder->where('id_pendonor', $id_pendonor)
                      ->update($data);
    }
}