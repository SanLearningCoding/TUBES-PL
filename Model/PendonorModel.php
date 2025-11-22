<?php
class PendonorModel {
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function getRiwayatDonasi($id_pendonor) {
        $builder = $this->db->table('transaksi_donasi td');
        $builder->select('td.*, kd.nama_kegiatan, kd.lokasi, p.nama_petugas')
                ->join('kegiatan_donasi kd', 'td.id_kegiatan = kd.id_kegiatan')
                ->join('Petugas p', 'td.id_petugas = p.id_petugas', 'left')
                ->where('td.id_pendonor', $id_pendonor)
                ->orderBy('td.tanggal_donasi', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    public function getDaftarPeringatanDonorUlang() {
        $builder = $this->db->table('pendonor p');
        $builder->select('p.*, MAX(td.tanggal_donasi) as terakhir_donasi')
                ->join('transaksi_donasi td', 'p.id_pendonor = td.id_pendonor')
                ->groupBy('p.id_pendonor')
                ->having("DATE_ADD(MAX(td.tanggal_donasi), INTERVAL 3 MONTH) <= CURDATE()")
                ->orHaving("terakhir_donasi IS NULL");
        
        return $builder->get()->getResultArray();
    }

    // Method tambahan
    public function getAllPendonor() {
        $builder = $this->db->table('pendonor');
        return $builder->get()->getResultArray();
    }

    public function getPendonorById($id_pendonor) {
        $builder = $this->db->table('pendonor');
        return $builder->where('id_pendonor', $id_pendonor)
                      ->get()
                      ->getRowArray();
    }
}