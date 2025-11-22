<?php
class DistribusiModel {
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function createDistribusi($data, $id_stok) {
        $this->db->transBegin();
        
        try {
            $builder = $this->db->table('distribusi_darah');
            $builder->insert($data);
            $id_distribusi = $this->db->insertID();
            
            $stokBuilder = $this->db->table('stok_darah');
            $stokBuilder->where('id_stok', $id_stok)
                       ->update(['status' => 'terpakai']);
            
            $this->db->transCommit();
            
            return $id_distribusi;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            return false;
        }
    }

    public function getLaporanDistribusi($rs_id = null) {
        $builder = $this->db->table('distribusi_darah dd');
        $builder->select('
            dd.*, 
            rs.nama_rs, 
            rs.alamat,
            sd.volume_ml,
            gd.nama_gol_darah,
            gd.rhesus,
            p.nama_petugas
        ')
        ->join('rumah_sakit rs', 'dd.id_rs = rs.id_rs')
        ->join('stok_darah sd', 'dd.id_stok = sd.id_stok')
        ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
        ->join('Petugas p', 'dd.id_petugas = p.id_petugas', 'left')
        ->orderBy('dd.tanggal_distribusi', 'DESC');
        
        if ($rs_id) {
            $builder->where('dd.id_rs', $rs_id);
        }
        
        return $builder->get()->getResultArray();
    }

    // Method tambahan
    public function getAllDistribusi() {
        return $this->getLaporanDistribusi();
    }

    public function getDistribusiById($id_distribusi) {
        $builder = $this->db->table('distribusi_darah dd');
        $builder->select('
            dd.*, 
            rs.nama_rs, 
            rs.alamat,
            rs.kontak as kontak_rs,
            sd.volume_ml,
            sd.tanggal_kadaluarsa,
            gd.nama_gol_darah,
            gd.rhesus,
            p.nama_petugas
        ')
        ->join('rumah_sakit rs', 'dd.id_rs = rs.id_rs')
        ->join('stok_darah sd', 'dd.id_stok = sd.id_stok')
        ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
        ->join('Petugas p', 'dd.id_petugas = p.id_petugas', 'left')
        ->where('dd.id_distribusi', $id_distribusi);
        
        return $builder->get()->getRowArray();
    }

    public function getRumahSakit() {
        $builder = $this->db->table('rumah_sakit');
        return $builder->get()->getResultArray();
    }
}