<?php
require_once __DIR__ . '/QueryBuilder.php';
// Model/StokModel.php

class StokModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ... metode lainnya tetap sama ...

    public function getStokById($id_stok) {
        // Return stok fields, golongan, dan tanggal donor dari transaksi
        $query = "SELECT sd.*, 
                         gd.nama_gol_darah, gd.rhesus,
                         td.tanggal_donasi
                  FROM stok_darah sd
                  LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
                  LEFT JOIN transaksi_donasi td ON sd.id_transaksi = td.id_transaksi
                  WHERE sd.id_stok = ? AND sd.is_deleted = 0";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_stok]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) return $result;

        // Fallback: if the left-join query returned nothing (for odd schema reasons), try a simple query to check existence
        $stmt2 = $this->db->prepare('SELECT * FROM stok_darah WHERE id_stok = ? AND is_deleted = 0');
        $stmt2->execute([$id_stok]);
        $fallback = $stmt2->fetch(PDO::FETCH_ASSOC);
        if ($fallback) {
            // Add placeholder fields to match expected keys in views
            $fallback['nama_gol_darah'] = $fallback['id_gol_darah'] ?? null;
            $fallback['rhesus'] = null;
            $fallback['tanggal_donasi'] = null;
        }
        return $fallback;
    }

    public function getDashboardStokRealtime() {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
        $query = "SELECT 
                    gd.nama_gol_darah,
                    gd.rhesus,
                    COUNT(sd.id_stok) as total_kantong,
                    SUM(sd.jumlah_kantong) as total_kantong_count,
                    SUM(CASE WHEN sd.status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
                    SUM(CASE WHEN sd.status = 'terpakai' THEN 1 ELSE 0 END) as terpakai,
                    SUM(CASE WHEN sd.status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa
                  FROM stok_darah sd
                  JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
                  WHERE sd.status_uji = 'lolos' AND sd.is_deleted = 0
                  GROUP BY gd.nama_gol_darah, gd.rhesus
                  ORDER BY gd.nama_gol_darah, gd.rhesus";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatusStok($id_stok, $status) {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
        $query = "UPDATE stok_darah SET status = ? WHERE id_stok = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$status, $id_stok]);
    }

    public function getStokTersedia() {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
                $query = "SELECT sd.*, gd.nama_gol_darah, gd.rhesus
                                    FROM stok_darah sd
                                    JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
                  WHERE sd.status = 'tersedia' 
                    AND sd.status_uji = 'lolos'
                    AND sd.is_deleted = 0
                    AND sd.tanggal_kadaluarsa >= CURDATE()
                  ORDER BY sd.tanggal_kadaluarsa ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createStokPascaUji($id_transaksi, $dataStok) {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
        $columns = implode(', ', array_keys($dataStok));
        $placeholders = implode(', ', array_fill(0, count($dataStok), '?'));
        
        $query = "INSERT INTO stok_darah ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($query);
        
        try {
            return $stmt->execute(array_values($dataStok));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Fill first available placeholder stok for a transaction.
     * If a placeholder exists (id_transaksi, status 'pending'), update it with data; otherwise insert new.
     */
    public function fillPlaceholderForTransaction($id_transaksi, $dataStok) {
        try {
            $this->db->beginTransaction();
            $sel = $this->db->prepare("SELECT id_stok FROM stok_darah WHERE id_transaksi = ? AND status = 'pending' LIMIT 1 FOR UPDATE");
            $sel->execute([$id_transaksi]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $id_stok = $row['id_stok'];
                $stmt = $this->db->prepare("UPDATE stok_darah SET id_gol_darah = ?, tanggal_pengujian = ?, status_uji = ?, tanggal_kadaluarsa = ?, volume_ml = ?, status = ? WHERE id_stok = ?");
                $result = $stmt->execute([
                    $dataStok['id_gol_darah'] ?? null,
                    $dataStok['tanggal_pengujian'] ?? null,
                    $dataStok['status_uji'] ?? 'lolos',
                    $dataStok['tanggal_kadaluarsa'] ?? null,
                    $dataStok['volume_ml'] ?? null,
                    $dataStok['status'] ?? 'tersedia',
                    $id_stok
                ]);
                $this->db->commit();
                return $result ? $id_stok : false;
            } else {
                $this->db->rollBack();
                // fallback to insert
                return $this->createStokPascaUji($id_transaksi, $dataStok);
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    /**
     * Create placeholder stok rows for a transaction (before testing), one row per kantong
     * @param int $id_transaksi
     * @param int $count number of kantong
     * @param int $volume_ml default volume for placeholders
     */
    public function createStockPlaceholdersForTransaction($id_transaksi, $count = 1, $volume_ml = 450, $tanggal_kadaluarsa = null, $id_gol_darah = null) {
        if ($count <= 0) return false;
        $stmt = $this->db->prepare("INSERT INTO stok_darah (id_transaksi, id_gol_darah, tanggal_pengujian, status_uji, tanggal_kadaluarsa, jumlah_kantong, status) VALUES (?, ?, NULL, 'lolos', ?, 1, 'tersedia')");
        try {
            $this->db->beginTransaction();
            for ($i = 0; $i < $count; $i++) {
                $stmt->execute([$id_transaksi, $id_gol_darah, $tanggal_kadaluarsa]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    // New CRUD methods (restore)
    /**
     * Get all individual stok records (per-unit, not aggregated)
     * Each row represents one kantong darah unit
     */
    public function getAllStocks() {
        $query = "SELECT sd.id_stok, sd.id_transaksi, sd.id_gol_darah, sd.tanggal_pengujian, sd.status_uji, sd.status, sd.tanggal_kadaluarsa, sd.jumlah_kantong, sd.is_deleted,
                         gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi
                  FROM stok_darah sd
                  LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
                  LEFT JOIN transaksi_donasi td ON sd.id_transaksi = td.id_transaksi
                  WHERE sd.is_deleted = 0
                  ORDER BY gd.nama_gol_darah, gd.rhesus, sd.tanggal_kadaluarsa ASC, sd.created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get aggregated stok summary per golongan darah
     * Shows total kantong per golongan (for dashboard/summary views)
     */
    public function getAggregatedStockSummary() {
        $query = "SELECT 
                    gd.id_gol_darah,
                    gd.nama_gol_darah,
                    gd.rhesus,
                    COUNT(sd.id_stok) as jumlah_kantong,
                    SUM(CASE WHEN sd.status = 'tersedia' THEN 1 ELSE 0 END) as tersedia,
                    SUM(CASE WHEN sd.status = 'terpakai' THEN 1 ELSE 0 END) as terpakai,
                    SUM(CASE WHEN sd.status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa
                  FROM golongan_darah gd
                  LEFT JOIN stok_darah sd ON sd.id_gol_darah = gd.id_gol_darah AND sd.is_deleted = 0
                  GROUP BY gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus
                  ORDER BY gd.nama_gol_darah, gd.rhesus";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalAvailableByGolongan($id_gol) {
        $query = "SELECT COALESCE(SUM(volume_ml), 0) as total_available FROM stok_darah WHERE id_gol_darah = ? AND status = 'tersedia' AND status_uji = 'lolos' AND (tanggal_kadaluarsa IS NULL OR tanggal_kadaluarsa > CURDATE())";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_gol]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($row['total_available']);
    }

    public function getAvailableVolumeByStok($id_stok) {
        $stmt = $this->db->prepare('SELECT volume_ml, status FROM stok_darah WHERE id_stok = ?');
        $stmt->execute([$id_stok]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return 0;
        if ($row['status'] !== 'tersedia') return 0;
        return intval($row['volume_ml']);
    }




    public function createStock($data) {
        // Try to ensure id_transaksi can be NULL
        try {
            $this->db->exec("ALTER TABLE stok_darah MODIFY id_transaksi INT NULL");
        } catch (Exception $e) {
            // ignore
        }
        $stmt = $this->db->prepare("INSERT INTO stok_darah (id_transaksi, id_gol_darah, tanggal_pengujian, status_uji, tanggal_kadaluarsa, volume_ml, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        try {
            return $stmt->execute([
                isset($data['id_transaksi']) ? $data['id_transaksi'] : null,
                $data['id_gol_darah'],
                $data['tanggal_pengujian'],
                $data['status_uji'] ?? 'lolos',
                $data['tanggal_kadaluarsa'],
                $data['volume_ml'],
                $data['status'] ?? 'tersedia'
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateStock($id_stok, $data) {
        $stmt = $this->db->prepare("UPDATE stok_darah SET id_gol_darah = ?, volume_ml = ?, tanggal_pengujian = ?, tanggal_kadaluarsa = ?, status_uji = ?, status = ? WHERE id_stok = ?");
        return $stmt->execute([
            $data['id_gol_darah'],
            $data['volume_ml'],
            $data['tanggal_pengujian'],
            $data['tanggal_kadaluarsa'],
            $data['status_uji'],
            $data['status'],
            $id_stok
        ]);
    }

   public function deleteStock($id_stok) {
        // Soft delete: tandai stok sebagai diarsipkan, tapi tidak benar-benar dihapus
        $stmt = $this->db->prepare("
            UPDATE stok_darah 
            SET is_deleted = 1, deleted_at = NOW()
            WHERE id_stok = ?
        ");
        return $stmt->execute([$id_stok]);
    }


    public function getAllGolongan() {
        $stmt = $this->db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Set status='kadaluarsa' for any stok where tanggal_kadaluarsa has passed
     * and the status is not already 'kadaluarsa'. This is run as a convenience
     * to ensure expired units are marked without manual intervention.
     */
    public function updateExpiredStatuses() {
        $stmt = $this->db->prepare("UPDATE stok_darah SET status = 'kadaluarsa' WHERE (tanggal_kadaluarsa IS NOT NULL AND tanggal_kadaluarsa < CURDATE()) AND status != 'kadaluarsa'");
        return $stmt->execute();
    }

    /**
     * Auto-generate stok from transaksi_donasi
     * Creates one stok record per kantong donated. Each record represents one unique unit.
     * tanggal_kadaluarsa is computed as tanggal_donasi + 42 days.
     */
    public function generateStokFromTransaksi($id_transaksi) {
        try {
            $this->db->beginTransaction();

            // Get transaksi data, including jumlah_kantong and pendonor
            $stmt = $this->db->prepare('SELECT td.id_transaksi, td.id_pendonor, td.jumlah_kantong, td.tanggal_donasi, p.id_gol_darah
                                         FROM transaksi_donasi td
                                         LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                                         WHERE td.id_transaksi = ? AND td.is_deleted = 0 LIMIT 1');
            $stmt->execute([$id_transaksi]);
            $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaksi) {
                $this->db->rollBack();
                return false;
            }

            $units = max(1, intval($transaksi['jumlah_kantong'] ?? 1));
            $id_gol = $transaksi['id_gol_darah'] ?? null;
            $tanggal_donasi = $transaksi['tanggal_donasi'];

            if (empty($id_gol) || empty($tanggal_donasi)) {
                $this->db->rollBack();
                return false;
            }

            $tanggal_kadaluarsa = date('Y-m-d', strtotime($tanggal_donasi . ' +42 days'));

            $insert = $this->db->prepare("INSERT INTO stok_darah (id_transaksi, id_gol_darah, tanggal_pengujian, status_uji, tanggal_kadaluarsa, status, is_deleted) VALUES (?, ?, NULL, 'lolos', ?, 'tersedia', 0)");
            for ($i = 0; $i < $units; $i++) {
                $ok = $insert->execute([$id_transaksi, $id_gol, $tanggal_kadaluarsa]);
                if (!$ok) throw new Exception('Failed inserting stok unit');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("generateStokFromTransaksi error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Return available stock counts per golongan (COUNT of units)
     * Only units with status = 'tersedia' and not expired are counted.
     */
    public function getAvailableStockSummary() {
        $sql = "SELECT gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus, COALESCE(COUNT(sd.id_stok),0) AS total_kantong
                FROM golongan_darah gd
                LEFT JOIN stok_darah sd ON sd.id_gol_darah = gd.id_gol_darah
                    AND sd.is_deleted = 0
                    AND sd.status = 'tersedia'
                    AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa >= CURDATE())
                GROUP BY gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus
                ORDER BY gd.nama_gol_darah, gd.rhesus";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>