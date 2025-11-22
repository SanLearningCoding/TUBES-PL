<?php

// Model/DistribusiModel.php

class DistribusiModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createDistribusi($data, $id_stok) {
        $this->db->beginTransaction();
        
        try {
            $builder = new QueryBuilder($this->db, 'distribusi_darah');
            $builder->insert($data);
            $id_distribusi = $this->db->lastInsertId();
            
            $stokBuilder = new QueryBuilder($this->db, 'stok_darah');
            $stokBuilder->where('id_stok', $id_stok)
                       ->update(['status' => 'terpakai']);
            
            $this->db->commit();
            return $id_distribusi;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getLaporanDistribusi($rs_id = null, $tanggal_awal = null, $tanggal_akhir = null) {
        $builder = new QueryBuilder($this->db, 'distribusi_darah dd');
        $query = $builder->select('
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
        ->join('petugas p', 'dd.id_petugas = p.id_petugas', 'LEFT')
        ->orderBy('dd.tanggal_distribusi', 'DESC');
        
        if ($rs_id) {
            $query->where('dd.id_rs', $rs_id);
        }
        
        if ($tanggal_awal && $tanggal_akhir) {
            $query->where('dd.tanggal_distribusi >=', $tanggal_awal)
                  ->where('dd.tanggal_distribusi <=', $tanggal_akhir);
        }
        
        return $query->getResultArray();
    }

    public function getAllDistribusi() {
        return $this->getLaporanDistribusi();
    }

    public function getDistribusiById($id_distribusi) {
        $builder = new QueryBuilder($this->db, 'distribusi_darah dd');
        return $builder->select('
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
        ->join('petugas p', 'dd.id_petugas = p.id_petugas', 'LEFT')
        ->where('dd.id_distribusi', $id_distribusi)
        ->getRowArray();
    }

    public function getDistribusiByStok($id_stok) {
        $builder = new QueryBuilder($this->db, 'distribusi_darah dd');
        return $builder->select('dd.*, rs.nama_rs')
                ->join('rumah_sakit rs', 'dd.id_rs = rs.id_rs')
                ->where('dd.id_stok', $id_stok)
                ->getResultArray();
    }

    public function getRumahSakit() {
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        return $builder->getResultArray();
    }

    public function createRumahSakit($data) {
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        return $builder->insert($data);
    }
}