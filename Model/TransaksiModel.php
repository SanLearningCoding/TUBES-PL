<?php


class TransaksiModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createTransaksi($data) {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi');
        $builder->insert($data);
        return $this->db->lastInsertId();
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
        ->groupBy("DATE_FORMAT(td.tanggal_donasi, '%Y-%m'), kd.nama_kegiatan")
        ->orderBy('bulan', 'DESC')
        ->getResultArray();
    }

    public function getAllTransaksi() {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
        return $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('petugas pt', 'td.id_petugas = pt.id_petugas', 'LEFT')
                ->orderBy('td.tanggal_donasi', 'DESC')
                ->getResultArray();
    }

    public function getTransaksiById($id_transaksi) {
        $builder = new QueryBuilder($this->db, 'transaksi_donasi td');
        return $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('petugas pt', 'td.id_petugas = pt.id_petugas', 'LEFT')
                ->where('td.id_transaksi', $id_transaksi)
                ->getRowArray();
    }
}