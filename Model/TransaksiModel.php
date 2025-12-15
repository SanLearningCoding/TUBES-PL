<?php
require_once __DIR__ . '/QueryBuilder.php';

// Model/TransaksiModel.php

class TransaksiModel {
    protected $db; // Properti ini tetap protected

    public function __construct() {
        $database = new Database(); // Asumsikan Database class bisa diakses
        $this->db = $database->getConnection(); // Inisialisasi koneksi di sini
    }

    // --- TAMBAHKAN: Getter method untuk akses koneksi ---
    public function getDbConnection() {
        return $this->db;
    }
    // --- END TAMBAHAN ---

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
        
        if ($id_transaksi) {
            // Perlu mengakses StokModel di sini untuk membuat stok
            // Pastikan StokModel bisa diakses dan generateStokFromTransaksi menerima tanggal_kadaluarsa
            $stokModel = new StokModel(); // Asumsikan bisa diakses
            // Karena createTransaksi tidak menerima tanggal_kadaluarsa_input,
            // kita tidak bisa meneruskannya langsung di sini kecuali kita ubah signature-nya.
            // Pendekatan yang lebih baik adalah memanggil generateStokFromTransaksi dari CONTROLLER setelah createTransaksi.
            // Kita biarkan ini seperti sebelumnya, karena controller sekarang menanganinya.
            // $stokModel->generateStokFromTransaksi($id_transaksi); // Jangan panggil di sini untuk kasus ini
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
        if ($this->hasColumn('pendonor', 'id_gol_darah')) {
            $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
            return $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas, gd.nama_gol_darah, gd.rhesus')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor', 'LEFT')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan', 'LEFT')
                ->join('golongan_darah gd', 'p.id_gol_darah = gd.id_gol_darah', 'LEFT')
                ->join('petugas pt', 'td.id_petugas = pt.id_petugas', 'LEFT')
                ->where('td.is_deleted', 0)
                ->orderBy('td.tanggal_donasi', 'DESC')
                ->getResultArray();
        } else {
            $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
            return $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor', 'LEFT')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan', 'LEFT')
                ->join('petugas pt', 'td.id_petugas = pt.id_petugas', 'LEFT')
                ->where('td.is_deleted', 0)
                ->orderBy('td.tanggal_donasi', 'DESC')
                ->getResultArray();
        }
    }

    public function getTransaksiById($id_transaksi) {
        if ($this->hasColumn('pendonor', 'id_gol_darah')) {
            $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
            return $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas, gd.nama_gol_darah, gd.rhesus')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('golongan_darah gd', 'p.id_gol_darah = gd.id_gol_darah', 'LEFT')
                ->join('petugas pt', 'td.id_petugas = pt.id_petugas', 'LEFT')
                ->where('td.id_transaksi', $id_transaksi)
                ->where('td.is_deleted', 0)
                ->getRowArray();
        } else {
            $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
            return $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('petugas pt', 'td.id_petugas = pt.id_petugas', 'LEFT')
                ->where('td.id_transaksi', $id_transaksi)
                ->where('td.is_deleted', 0)
                ->getRowArray();
        }
    }
}
?>