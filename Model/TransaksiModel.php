<?php
require_once __DIR__ . '/QueryBuilder.php';

// Model/TransaksiModel.php

class TransaksiModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    private function hasColumn($table, $column) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
        $stmt->execute([$table, $column]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($row['cnt']) > 0;
    }

    public function createTransaksi($data) {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi');
        $builder->insert($data);
        $id_transaksi = $this->db->lastInsertId();
        
        // Auto-generate stok from transaksi
        if ($id_transaksi) {
            $stokModel = new StokModel();
            $stokModel->generateStokFromTransaksi($id_transaksi);
        }
        
        return $id_transaksi;
    }

    public function updateTransaksi($id, $data) {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi');
        return $builder->where('id_transaksi', $id)
                      ->update($data);
    }

    public function getLaporanKinerjaDonor($periode) {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
        return $builder->select("
            DATE_FORMAT(td.tanggal_donasi, '%Y-%m') as bulan,
            COUNT(td.id_transaksi) as total_donor,
            SUM(td.jumlah_kantong) as total_kantong,
            kd.nama_kegiatan,
            COUNT(DISTINCT td.id_pendonor) as jumlah_pendonor
        ")
        ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
        ->where("DATE_FORMAT(td.tanggal_donasi, '%Y-%m')", $periode)
        ->where('td.is_deleted', 0)
        ->groupBy("DATE_FORMAT(td.tanggal_donasi, '%Y-%m'), kd.nama_kegiatan")
        ->orderBy('bulan', 'DESC')
        ->getResultArray();
    }

    public function getAllTransaksi() {
        // If pendonor.id_gol_darah doesn't exist, avoid joining to golongan_darah
        if ($this->hasColumn('pendonor', 'id_gol_darah')) {
            $sql = "SELECT td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas, gd.nama_gol_darah, gd.rhesus
                FROM transaksi_donasi td
                LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah
                LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
                WHERE td.is_deleted = 0
                ORDER BY td.tanggal_donasi DESC";
        } else {
            $sql = "SELECT td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas
                FROM transaksi_donasi td
                LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
                WHERE td.is_deleted = 0
                ORDER BY td.tanggal_donasi DESC";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransaksiById($id_transaksi) {
        if ($this->hasColumn('pendonor', 'id_gol_darah')) {
            $sql = "SELECT td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas, gd.nama_gol_darah, gd.rhesus
                FROM transaksi_donasi td
                JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah
                LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
                WHERE td.id_transaksi = ? AND td.is_deleted = 0";
        } else {
            $sql = "SELECT td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas
                FROM transaksi_donasi td
                JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
                WHERE td.id_transaksi = ? AND td.is_deleted = 0";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_transaksi]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}