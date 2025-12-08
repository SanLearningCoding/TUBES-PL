<?php
require_once __DIR__ . '/QueryBuilder.php';

// Model/DistribusiModel.php - Simplified for per-kantong distribution

class DistribusiModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Distribute kantong from a golongan to a hospital
     * Marks a single unit (id_stok) as distributed and creates a distribusi_darah record.
     */
    public function createDistribusiPerKantong($id_stok, $id_rs, $tanggal_distribusi, $id_petugas) {
        try {
            $this->db->beginTransaction();

            // Lock the selected unit
            $sel = $this->db->prepare("SELECT id_stok, status FROM stok_darah WHERE id_stok = ? AND is_deleted = 0 FOR UPDATE");
            $sel->execute([$id_stok]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['status'] !== 'tersedia') {
                $this->db->rollBack();
                return false;
            }

            // Update unit status
            $upd = $this->db->prepare("UPDATE stok_darah SET status = 'terpakai', updated_at = NOW() WHERE id_stok = ?");
            if (!$upd->execute([$id_stok])) {
                throw new Exception('Failed to update stok status');
            }

            // Insert distribusi record
            $ins = $this->db->prepare("INSERT INTO distribusi_darah (id_stok, id_rs, id_petugas, tanggal_distribusi, status, jumlah_volume) VALUES (?, ?, ?, ?, 'dikirim', 1)");
            if (!$ins->execute([$id_stok, $id_rs, $id_petugas, $tanggal_distribusi])) {
                throw new Exception('Failed to insert distribusi record');
            }

            $id_distribusi = $this->db->lastInsertId();
            $this->db->commit();
            return $id_distribusi;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("createDistribusiPerKantong error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of available stok per golongan for distribution
     */
    public function getAvailableStokForDistribusi() {
        // Return individual available units (per-kantong)
        $stmt = $this->db->prepare(
            "SELECT 
                sd.id_stok,
                sd.id_gol_darah,
                gd.nama_gol_darah,
                gd.rhesus,
                sd.tanggal_kadaluarsa,
                sd.status_uji,
                sd.status
            FROM stok_darah sd
            LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
            WHERE sd.status_uji = 'lolos'
                AND sd.status = 'tersedia'
                AND sd.is_deleted = 0
                AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa >= CURDATE())
            ORDER BY gd.nama_gol_darah, gd.rhesus, sd.tanggal_kadaluarsa ASC, sd.created_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Distribute one unit (FEFO) for a given golongan darah.
     * Returns id_stok of distributed unit or false if none available.
     */
    public function distributeOneUnit($id_gol_darah, $id_rs = null, $id_petugas = null, $tanggal_distribusi = null) {
        $db = $this->db;
        try {
            $db->beginTransaction();

            // select one available unit with earliest expiry and lock it
            $select = $db->prepare(
                "SELECT id_stok FROM stok_darah
                 WHERE id_gol_darah = ?
                   AND status_uji = 'lolos'
                   AND status = 'tersedia'
                   AND is_deleted = 0
                   AND (tanggal_kadaluarsa IS NULL OR tanggal_kadaluarsa >= CURDATE())
                 ORDER BY tanggal_kadaluarsa ASC, created_at ASC
                 LIMIT 1 FOR UPDATE"
            );
            $select->execute([$id_gol_darah]);
            $row = $select->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $db->rollBack();
                return false;
            }
            $id_stok = intval($row['id_stok']);

            // mark unit as distributed
            $upd = $db->prepare("UPDATE stok_darah SET status = 'terpakai', updated_at = NOW() WHERE id_stok = ?");
            $ok = $upd->execute([$id_stok]);
            if (!$ok) throw new Exception('Failed updating stok status');

            // insert distribusi record (if table exists with expected columns)
            $check = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'distribusi_darah'");
            $check->execute();
            if (intval($check->fetchColumn()) > 0) {
                $tanggal_distribusi = $tanggal_distribusi ?? date('Y-m-d');
                $ins = $db->prepare("INSERT INTO distribusi_darah (id_stok, id_rs, id_petugas, tanggal_distribusi, status, jumlah_volume) VALUES (?, ?, ?, ?, 'dikirim', 1)");
                $ins->execute([$id_stok, $id_rs, $id_petugas, $tanggal_distribusi]);
            }

            $db->commit();
            return $id_stok;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('distributeOneUnit error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get count of available stok per golongan
     */
    public function getAvailableCountByGolongan() {
        $stmt = $this->db->prepare(
            "SELECT 
                gd.id_gol_darah,
                gd.nama_gol_darah,
                gd.rhesus,
                COUNT(sd.id_stok) as jumlah_kantong
            FROM golongan_darah gd
            LEFT JOIN stok_darah sd ON sd.id_gol_darah = gd.id_gol_darah
                AND sd.status_uji = 'lolos'
                AND sd.status = 'tersedia'
                AND sd.is_deleted = 0 
                AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa >= CURDATE())
            GROUP BY gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus
            HAVING jumlah_kantong > 0
            ORDER BY gd.nama_gol_darah, gd.rhesus"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDistribusi($id_distribusi) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE distribusi_darah
                 SET is_deleted = 1, deleted_at = NOW()
                 WHERE id_distribusi = ?"
            );
            return $stmt->execute([$id_distribusi]);
        } catch (Exception $e) {
            error_log("deleteDistribusi error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllDistribusi() {
        $stmt = $this->db->prepare(
            "SELECT 
                dd.id_distribusi,
                dd.id_stok,
                dd.id_rs,
                dd.tanggal_distribusi,
                dd.status,
                dd.jumlah_volume,
                dd.created_at,
                rs.nama_rs,
                gd.nama_gol_darah,
                gd.rhesus,
                pt.nama_petugas
            FROM distribusi_darah dd
            LEFT JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
            LEFT JOIN stok_darah sd ON dd.id_stok = sd.id_stok
            LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
            LEFT JOIN petugas pt ON dd.id_petugas = pt.id_petugas
            WHERE dd.is_deleted = 0
            ORDER BY dd.tanggal_distribusi DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistribusiById($id_distribusi) {
        $stmt = $this->db->prepare(
            "SELECT 
                dd.*,
                rs.nama_rs,
                rs.alamat,
                rs.kontak,
                gd.nama_gol_darah,
                gd.rhesus,
                pt.nama_petugas
            FROM distribusi_darah dd
            LEFT JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
            LEFT JOIN stok_darah sd ON dd.id_stok = sd.id_stok
            LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
            LEFT JOIN petugas pt ON dd.id_petugas = pt.id_petugas
            WHERE dd.id_distribusi = ?"
        );
        $stmt->execute([$id_distribusi]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRumahSakit() {
        $stmt = $this->db->prepare("SELECT id_rs, nama_rs FROM rumah_sakit WHERE is_deleted = 0 ORDER BY nama_rs");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update distribusi and sync stok status based on distribution status
     * - dikirim/diterima: stok = terpakai
     * - dibatalkan: stok = tersedia
     */
    public function updateDistribusiAndRestoreIfCanceled($id_distribusi, $id_rs, $tanggal_distribusi, $status) {
        try {
            $this->db->beginTransaction();
            
            // Lock the distribusi row and get its current status and stok info
            $stmt = $this->db->prepare('SELECT id_distribusi, id_stok, status FROM distribusi_darah WHERE id_distribusi = ? FOR UPDATE');
            $stmt->execute([$id_distribusi]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                $this->db->rollBack();
                return false;
            }

            $prev_status = $row['status'];
            $id_stok = $row['id_stok'];

            // Update distribusi record
            $upd = $this->db->prepare('UPDATE distribusi_darah SET id_rs = ?, tanggal_distribusi = ?, status = ? WHERE id_distribusi = ?');
            if (!$upd->execute([$id_rs, $tanggal_distribusi, $status, $id_distribusi])) {
                $this->db->rollBack();
                return false;
            }

            // Synchronize stok status based on distribution status
            if (!empty($id_stok)) {
                if ($status === 'dibatalkan') {
                    // Restore stok to tersedia when distribution is canceled
                    $updStk = $this->db->prepare("UPDATE stok_darah SET status = 'tersedia', updated_at = NOW() WHERE id_stok = ?");
                    $updStk->execute([$id_stok]);
                } else if ($status === 'dikirim' || $status === 'diterima') {
                    // Mark stok as terpakai when distribution is dikirim or diterima
                    $updStk = $this->db->prepare("UPDATE stok_darah SET status = 'terpakai', updated_at = NOW() WHERE id_stok = ?");
                    $updStk->execute([$id_stok]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("updateDistribusiAndRestoreIfCanceled error: " . $e->getMessage());
            return false;
        }
    }

    public function createRumahSakit($data) {
        if (isset($data['kontak'])) {
            $data['kontak'] = preg_replace('/\D+/', '', $data['kontak']);
        }
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        return $builder->insert($data);
    }
}
?>
