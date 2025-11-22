<?php
class StokModel {
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function createStokPascaUji($id_transaksi, $dataStok) {
        $builder = $this->db->table('stok_darah');
        $dataStok['id_transaksi'] = $id_transaksi;
        $builder->insert($dataStok);
        return $this->db->insertID();
    }

    public function getDashboardStokRealtime() {
        $builder = $this->db->table('stok_darah sd');
        $builder->select("
            gd.nama_gol_darah,
            gd.rhesus,
            COUNT(sd.id_stok) as total_kantong,
            SUM(sd.volume_ml) as total_volume,
            SUM(CASE WHEN sd.status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
            SUM(CASE WHEN sd.status = 'terpakai' THEN 1 ELSE 0 END) as terpakai,
            SUM(CASE WHEN sd.status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa
        ")
        ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
        ->where('sd.status_uji', 'lolos')
        ->groupBy('gd.nama_gol_darah, gd.rhesus')
        ->orderBy('gd.nama_gol_darah, gd.rhesus');
        
        return $builder->get()->getResultArray();
    }

    public function updateStatusStok($id_stok, $status) {
        $builder = $this->db->table('stok_darah');
        return $builder->where('id_stok', $id_stok)
                      ->update(['status' => $status]);
    }

    // method tambahan
    public function getStokTersedia() {
        $builder = $this->db->table('stok_darah sd');
        $builder->select('sd.*, gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi')
                ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
                ->join('transaksi_donasi td', 'sd.id_transaksi = td.id_transaksi')
                ->where('sd.status', 'tersedia')
                ->where('sd.status_uji', 'lolos')
                ->where('sd.tanggal_kadaluarsa >=', date('Y-m-d'))
                ->orderBy('sd.tanggal_kadaluarsa', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    public function getStokById($id_stok) {
        $builder = $this->db->table('stok_darah sd');
        $builder->select('sd.*, gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi')
                ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
                ->join('transaksi_donasi td', 'sd.id_transaksi = td.id_transaksi')
                ->where('sd.id_stok', $id_stok);
        
        return $builder->get()->getRowArray();
    }
}