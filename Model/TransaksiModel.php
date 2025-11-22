<?php
class TransaksiModel {
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function createTransaksi($data) {
        $builder = $this->db->table('transaksi_donasi');
        $builder->insert($data);
        return $this->db->insertID();
    }

    public function getLaporanKinerjaDonor($periode) {
        $builder = $this->db->table('transaksi_donasi td');
        $builder->select("
            DATE_FORMAT(td.tanggal_donasi, '%Y-%m') as bulan,
            COUNT(td.id_transaksi) as total_donor,
            SUM(td.jumlah_kantong) as total_kantong,
            kd.nama_kegiatan,
            COUNT(DISTINCT td.id_pendonor) as jumlah_pendonor
        ")
        ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
        ->where("DATE_FORMAT(td.tanggal_donasi, '%Y-%m')", $periode)
        ->groupBy("DATE_FORMAT(td.tanggal_donasi, '%Y-%m'), kd.nama_kegiatan")
        ->orderBy('bulan', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    //method tambahan
    public function getAllTransaksi() {
        $builder = $this->db->table('transaksi_donasi td');
        $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('Petugas pt', 'td.id_petugas = pt.id_petugas', 'left')
                ->orderBy('td.tanggal_donasi', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    public function getTransaksiById($id_transaksi) {
        $builder = $this->db->table('transaksi_donasi td');
        $builder->select('td.*, p.nama as nama_pendonor, kd.nama_kegiatan, pt.nama_petugas')
                ->join('pendonor p', 'td.id_pendonor = p.id_pendonor')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('Petugas pt', 'td.id_petugas = pt.id_petugas', 'left')
                ->where('td.id_transaksi', $id_transaksi);
        
        return $builder->get()->getRowArray();
    }
}