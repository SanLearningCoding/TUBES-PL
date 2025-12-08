<?php
require_once __DIR__ . '/QueryBuilder.php';

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
            // Insert distribusi row (support optional volume_ml)
            $builder->insert($data);
            $id_distribusi = $this->db->lastInsertId();
            // If volume_ml specified, reduce stok volume by that amount
            if (isset($data['volume_ml'])) {
                $vol = intval($data['volume_ml']);
                // Get current stok volume
                $stmt = $this->db->prepare('SELECT volume_ml FROM stok_darah WHERE id_stok = ? FOR UPDATE');
                $stmt->execute([$id_stok]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $avail = intval($row['volume_ml']);
                    $new = $avail - $vol;
                    if ($new <= 0) {
                        $stkUpd = $this->db->prepare("UPDATE stok_darah SET volume_ml = 0, status = 'terpakai' WHERE id_stok = ?");
                        $stkUpd->execute([$id_stok]);
                    } else {
                        $stkUpd = $this->db->prepare("UPDATE stok_darah SET volume_ml = ? WHERE id_stok = ?");
                        $stkUpd->execute([$new, $id_stok]);
                    }
                }
            } else {
                // No volume provided -> mark stok as terpakai
                $stokBuilder = new QueryBuilder($this->db, 'stok_darah');
                $stokBuilder->where('id_stok', $id_stok)->update(['status' => 'terpakai']);
            }
            
            $this->db->commit();
            return $id_distribusi;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Create distribution by stock group (golongan darah) — consumes multiple stok items as needed
     */
    public function createDistribusiByGolongan($id_gol, $volume_needed, $id_rs, $tanggal_distribusi, $status_pengiriman, $id_petugas) {
        try {
            $this->db->beginTransaction();
            // Check total available
            $stmt = $this->db->prepare("SELECT COALESCE(SUM(volume_ml),0) as total_available FROM stok_darah WHERE id_gol_darah = ? AND status = 'tersedia' AND status_uji = 'lolos' AND is_deleted = 0 AND (tanggal_kadaluarsa IS NULL OR tanggal_kadaluarsa > CURDATE())");
            $stmt->execute([$id_gol]);
            $total_available = intval($stmt->fetch(PDO::FETCH_ASSOC)['total_available']);
            if ($total_available < $volume_needed) {
                $this->db->rollBack();
                return false;
            }
            $needed = $volume_needed;
            $stmtRows = $this->db->prepare("SELECT id_stok, volume_ml FROM stok_darah WHERE id_gol_darah = ? AND status = 'tersedia' AND status_uji = 'lolos' AND is_deleted = 0 AND (tanggal_kadaluarsa IS NULL OR tanggal_kadaluarsa > CURDATE()) ORDER BY (tanggal_kadaluarsa IS NULL), tanggal_kadaluarsa ASC, id_stok ASC FOR UPDATE");
            $stmtRows->execute([$id_gol]);
            while ($row = $stmtRows->fetch(PDO::FETCH_ASSOC)) {
                $id_stok = $row['id_stok'];
                $avail = intval($row['volume_ml']);
                if ($avail <= 0) continue;
                $consume = min($avail, $needed);
                $new_volume = $avail - $consume;
                if ($new_volume <= 0) {
                    $upd = $this->db->prepare("UPDATE stok_darah SET volume_ml = 0, status = 'terpakai' WHERE id_stok = ?");
                    $upd->execute([$id_stok]);
                } else {
                    $upd = $this->db->prepare("UPDATE stok_darah SET volume_ml = ? WHERE id_stok = ?");
                    $upd->execute([$new_volume, $id_stok]);
                }
                $ins = $this->db->prepare("INSERT INTO distribusi_darah (id_stok, id_rs, id_petugas, tanggal_distribusi, status, jumlah_volume) VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$id_stok, $id_rs, $id_petugas, $tanggal_distribusi, 'dikirim', $consume]);
                $needed -= $consume;
                if ($needed <= 0) break;
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("createDistribusiByGolongan error: " . $e->getMessage());
            return false;
        }
    }

    // Reordered params so optional parameter is last to avoid PHP warnings/fatal errors
    public function createDistribusiByStok($id_stok, $id_rs, $tanggal_distribusi, $status_pengiriman, $id_petugas, $volume_needed = null) {
        try {
            $this->db->beginTransaction();
            $stmtGet = $this->db->prepare('SELECT volume_ml, status FROM stok_darah WHERE id_stok = ? AND is_deleted = 0 FOR UPDATE');
            $stmtGet->execute([$id_stok]);
            $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
            if (!$row) { $this->db->rollBack(); return false; }
            $avail = intval($row['volume_ml']);
            if ($volume_needed === null) {
                // distribute entire stok
                $consume = $avail;
            } else {
                $v = intval($volume_needed);
                if ($v <= 0) { $this->db->rollBack(); return false; }
                $consume = min($avail, $v);
            }
            if ($consume <= 0) { $this->db->rollBack(); return false; }
            $new_volume = $avail - $consume;
            if ($new_volume <= 0) {
                $upd = $this->db->prepare("UPDATE stok_darah SET volume_ml = 0, status = 'terpakai' WHERE id_stok = ?");
                $upd->execute([$id_stok]);
            } else {
                $upd = $this->db->prepare("UPDATE stok_darah SET volume_ml = ? WHERE id_stok = ?");
                $upd->execute([$new_volume, $id_stok]);
            }
            $ins = $this->db->prepare("INSERT INTO distribusi_darah (id_stok, id_rs, id_petugas, tanggal_distribusi, status, jumlah_volume) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$id_stok, $id_rs, $id_petugas, $tanggal_distribusi, 'dikirim', $consume]);
            $id_distribusi = $this->db->lastInsertId();
            $this->db->commit();
            return $id_distribusi;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("createDistribusiByStok error: " . $e->getMessage());
            return false;
        }
    }

   public function deleteDistribusi($id_distribusi) {
    // Soft delete: tandai distribusi sebagai diarsipkan
    try {
        $stmt = $this->db->prepare("
            UPDATE distribusi_darah
            SET is_deleted = 1,
                deleted_at = NOW()
            WHERE id_distribusi = ?
        ");
        return $stmt->execute([$id_distribusi]);
    } catch (Exception $e) {
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
    ->where('dd.is_deleted', 0)              // ⬅ hanya yang belum diarsip
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

    /**
     * Update distribusi record and, if status becomes 'Dibatalkan', restore the stock
     * associated with that distribusi (add volume back and set status to tersedia if applicable).
     *
     * This is executed in a single transaction to keep stock and distribusi row in sync.
     */
    public function updateDistribusiAndRestoreIfCanceled($id_distribusi, $id_rs, $tanggal_distribusi, $status_pengiriman) {
        try {
            $this->db->beginTransaction();
            // Lock the distribusi row
            $stmt = $this->db->prepare('SELECT id_distribusi, id_stok, volume_ml, status_pengiriman FROM distribusi_darah WHERE id_distribusi = ? FOR UPDATE');
            $stmt->execute([$id_distribusi]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { $this->db->rollBack(); return false; }

            $prev_status = $row['status_pengiriman'];
            $id_stok = $row['id_stok'];
            $vol = intval($row['volume_ml']);

            // Update distribusi row
            $upd = $this->db->prepare('UPDATE distribusi_darah SET id_rs = ?, tanggal_distribusi = ?, status_pengiriman = ? WHERE id_distribusi = ?');
            if (!$upd->execute([$id_rs, $tanggal_distribusi, $status_pengiriman, $id_distribusi])) {
                $this->db->rollBack();
                return false;
            }

            // Restore stock if status changed to Dibatalkan
            if ($status_pengiriman === 'Dibatalkan' && $prev_status !== 'Dibatalkan') {
                if (!empty($id_stok) && $vol > 0) {
                    // Select stok row FOR UPDATE
                    $stmt2 = $this->db->prepare('SELECT volume_ml, status FROM stok_darah WHERE id_stok = ? FOR UPDATE');
                    $stmt2->execute([$id_stok]);
                    $stok = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($stok) {
                        $current_vol = intval($stok['volume_ml']);
                        $new_vol = $current_vol + $vol;
                        // Update new volume and status
                        if ($new_vol > 0) {
                            $updStk = $this->db->prepare("UPDATE stok_darah SET volume_ml = ?, status = 'tersedia' WHERE id_stok = ?");
                            $updStk->execute([$new_vol, $id_stok]);
                        } else {
                            $updStk = $this->db->prepare("UPDATE stok_darah SET volume_ml = ? WHERE id_stok = ?");
                            $updStk->execute([$new_vol, $id_stok]);
                        }
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

   public function getDistribusiByStok($id_stok) {
        $builder = new QueryBuilder($this->db, 'distribusi_darah dd');
        return $builder->select('dd.*, rs.nama_rs')
                ->join('rumah_sakit rs', 'dd.id_rs = rs.id_rs')
                ->where('dd.id_stok', $id_stok)
                ->where('dd.is_deleted', 0)
                ->getResultArray();
    }


    public function getRumahSakit() {
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        // Hanya ambil rumah sakit yang belum dihapus (soft-delete)
        return $builder->where('is_deleted', 0)->getResultArray();
    }

    public function createRumahSakit($data) {
        // sanitize kontak: keep digits only
        if (isset($data['kontak'])) {
            $data['kontak'] = preg_replace('/\D+/', '', $data['kontak']);
        }
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        return $builder->insert($data);
    }
}