<?php
require_once __DIR__ . '/QueryBuilder.php';

// Model/StokModel.php

class StokModel {
    protected $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // ... (metode lainnya tetap sama ...) ...
    public function getStokById($id_stok) {
        // Return stok fields, golongan, dan tanggal donor dari transaksi
        $builder = new QueryBuilder($this->db, 'stok_darah sd');
        $result = $builder->select('sd.*, gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi')
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah', 'LEFT')
            ->join('transaksi_donasi td', 'sd.id_transaksi = td.id_transaksi', 'LEFT')
            ->where('sd.id_stok', $id_stok)
            ->where('sd.is_deleted', 0)
            ->getRowArray();
        if ($result) return $result;

        // Fallback: if the left-join query returned nothing (for odd schema reasons), try a simple query to check existence
        $builder2 = new QueryBuilder($this->db, 'stok_darah');
        $fallback = $builder2->select('*')
            ->where('id_stok', $id_stok)
            ->where('is_deleted', 0)
            ->getRowArray();
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
        $builder = new QueryBuilder($this->db, 'stok_darah sd');
        return $builder->select("gd.nama_gol_darah, gd.rhesus, COUNT(sd.id_stok) as total_kantong, SUM(sd.jumlah_kantong) as total_kantong_count, SUM(CASE WHEN sd.status = 'tersedia' THEN 1 ELSE 0 END) as tersedia, SUM(CASE WHEN sd.status = 'terpakai' THEN 1 ELSE 0 END) as terpakai, SUM(CASE WHEN sd.status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa")
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
            ->where('sd.status_uji', 'lolos')
            ->where('sd.is_deleted', 0)
            ->groupBy('gd.nama_gol_darah, gd.rhesus')
            ->orderBy('gd.nama_gol_darah', 'ASC')
            ->orderBy('gd.rhesus', 'ASC')
            ->getResultArray();
    }

    public function updateStatusStok($id_stok, $status) {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
        $builder = new QueryBuilder($this->db, 'stok_darah');
        return $builder->where('id_stok', $id_stok)
            ->update(['status' => $status]);
    }

    public function getStokTersedia() {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
        $builder = new QueryBuilder($this->db, 'stok_darah sd');
        return $builder->select('sd.*, gd.nama_gol_darah, gd.rhesus')
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah')
            ->where('sd.status', 'tersedia')
            ->where('sd.status_uji', 'lolos')
            ->where('sd.is_deleted', 0)
            ->where('sd.tanggal_kadaluarsa', date('Y-m-d'), '>=')
            ->orderBy('sd.tanggal_kadaluarsa', 'ASC')
            ->getResultArray();
    }

    public function createStokPascaUji($id_transaksi, $dataStok) {
        // GANTI QUERYBUILDER DENGAN PDO BIASA
        $builder = new QueryBuilder($this->db, 'stok_darah');
        return $builder->insert($dataStok);
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
        $builder = new QueryBuilder($this->db, 'stok_darah sd');
        return $builder->select('sd.id_stok, sd.id_transaksi, sd.id_gol_darah, sd.tanggal_pengujian, sd.status_uji, sd.status, sd.tanggal_kadaluarsa, sd.jumlah_kantong, sd.is_deleted, gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi')
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah', 'LEFT')
            ->join('transaksi_donasi td', 'sd.id_transaksi = td.id_transaksi', 'LEFT')
            ->where('sd.is_deleted', 0)
            ->orderBy('gd.nama_gol_darah', 'ASC')
            ->orderBy('gd.rhesus', 'ASC')
            ->orderBy('sd.tanggal_kadaluarsa', 'ASC')
            ->orderBy('sd.created_at', 'DESC')
            ->getResultArray();
    }

    /**
     * Get aggregated stok summary per golongan darah
     * Shows total kantong per golongan (for dashboard/summary views)
     */
    public function getAggregatedStockSummary() {
        $builder = new QueryBuilder($this->db, 'golongan_darah gd');
        return $builder->select("gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus, COUNT(sd.id_stok) as jumlah_kantong, SUM(CASE WHEN sd.status = 'tersedia' THEN 1 ELSE 0 END) as tersedia, SUM(CASE WHEN sd.status = 'terpakai' THEN 1 ELSE 0 END) as terpakai, SUM(CASE WHEN sd.status = 'kadaluarsa' THEN 1 ELSE 0 END) as kadaluarsa")
            ->join('stok_darah sd', 'sd.id_gol_darah = gd.id_gol_darah AND sd.is_deleted = 0', 'LEFT')
            ->groupBy('gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus')
            ->orderBy('gd.nama_gol_darah', 'ASC')
            ->orderBy('gd.rhesus', 'ASC')
            ->getResultArray();
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
        $builder = new QueryBuilder($this->db, 'stok_darah');
        return $builder->insert($data);
    }

    public function updateStock($id_stok, $data) {
        $builder = new QueryBuilder($this->db, 'stok_darah');
        return $builder->where('id_stok', $id_stok)
            ->update($data);
    }

   public function deleteStock($id_stok) {
        // Soft delete: tandai stok sebagai diarsipkan, tapi tidak benar-benar dihapus
        $builder = new QueryBuilder($this->db, 'stok_darah');
        return $builder->where('id_stok', $id_stok)
            ->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
    }


    public function getAllGolongan() {
        $builder = new QueryBuilder($this->db, 'golongan_darah');
        return $builder->select('id_gol_darah, nama_gol_darah, rhesus')
            ->orderBy('nama_gol_darah', 'ASC')
            ->getResultArray();
    }

    /**
     * Set status='kadaluarsa' for any stok where tanggal_kadaluarsa has passed
     * and the status is not already 'kadaluarsa'. This is run as a convenience
     * to ensure expired units are marked without manual intervention.
     */
    public function updateExpiredStatuses() {
        $builder = new QueryBuilder($this->db, 'stok_darah');
        return $builder->where('tanggal_kadaluarsa', date('Y-m-d'), '<')
            ->where('status', 'kadaluarsa', '!=')
            ->update(['status' => 'kadaluarsa']);
    }

    /**
     * Auto-generate stok from transaksi_donasi
     * Creates one stok record per kantong donated. Each record represents one unique unit.
     * tanggal_kadaluarsa is computed as tanggal_donasi + 42 days.
     * --- PERBAIKAN: Fungsi ini sekarang menerima tanggal_kadaluarsa_input ---
     * @param int $id_transaksi ID transaksi yang akan digunakan untuk membuat stok
     * @param string|null $tanggal_kadaluarsa_input (Opsional) Jika disediakan, gunakan ini sebagai tanggal kadaluarsa. Jika tidak, hitung otomatis.
     */
    public function generateStokFromTransaksi($id_transaksi, $tanggal_kadaluarsa_input = null) { // Tambahkan parameter
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
                error_log("StokModel: Transaksi dengan ID $id_transaksi tidak ditemukan saat generateStokFromTransaksi.");
                return false;
            }

            $units = max(1, intval($transaksi['jumlah_kantong'] ?? 1));
            $id_gol = $transaksi['id_gol_darah'] ?? null;
            $tanggal_donasi = $transaksi['tanggal_donasi'];

            if (empty($id_gol) || empty($tanggal_donasi)) {
                $this->db->rollBack();
                error_log("StokModel: ID Golongan atau Tanggal Donasi kosong untuk transaksi ID $id_transaksi.");
                return false;
            }

            // --- PERBAIKAN: Gunakan tanggal_kadaluarsa_input jika disediakan ---
            if ($tanggal_kadaluarsa_input) {
                // Validasi tanggal_kadaluarsa_input (opsional tapi disarankan)
                $date_obj = DateTime::createFromFormat('Y-m-d', $tanggal_kadaluarsa_input);
                if (!$date_obj || $date_obj->format('Y-m-d') !== $tanggal_kadaluarsa_input) {
                    $this->db->rollBack();
                    error_log("StokModel: Format tanggal_kadaluarsa_input '$tanggal_kadaluarsa_input' tidak valid untuk transaksi ID $id_transaksi.");
                    return false;
                }
                $tanggal_kadaluarsa = $tanggal_kadaluarsa_input;
                error_log("StokModel: Menggunakan tanggal_kadaluarsa dari input: $tanggal_kadaluarsa untuk transaksi ID $id_transaksi.");
            } else {
                // Jika tidak disediakan, hitung otomatis
                $tanggal_kadaluarsa = date('Y-m-d', strtotime($tanggal_donasi . ' +42 days'));
                error_log("StokModel: Menghitung tanggal_kadaluarsa otomatis: $tanggal_kadaluarsa untuk transaksi ID $id_transaksi (dari $tanggal_donasi + 42 hari).");
            }
            // --- END PERBAIKAN ---

            $insert = $this->db->prepare("INSERT INTO stok_darah (id_transaksi, id_gol_darah, tanggal_pengujian, status_uji, tanggal_kadaluarsa, status, is_deleted) VALUES (?, ?, NULL, 'lolos', ?, 'tersedia', 0)");
            for ($i = 0; $i < $units; $i++) {
                $ok = $insert->execute([$id_transaksi, $id_gol, $tanggal_kadaluarsa]);
                if (!$ok) {
                     error_log("StokModel: Gagal menyisipkan stok unit ke-$i untuk transaksi ID $id_transaksi.");
                     throw new Exception('Failed inserting stok unit');
                }
            }

            $this->db->commit();
            error_log("StokModel: Berhasil membuat $units unit stok untuk transaksi ID $id_transaksi dengan tanggal_kadaluarsa $tanggal_kadaluarsa.");
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log("generateStokFromTransaksi error (ID: $id_transaksi, Input Tgl Kadaluarsa: $tanggal_kadaluarsa_input): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Return available stock counts per golongan (COUNT of units)
     * Only units with status = 'tersedia' and not expired are counted.
     */
    public function getAvailableStockSummary() {
        $builder = new QueryBuilder($this->db, 'golongan_darah gd');
        return $builder->select("gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus, COALESCE(COUNT(sd.id_stok),0) AS total_kantong")
            ->join('stok_darah sd', 'sd.id_gol_darah = gd.id_gol_darah AND sd.is_deleted = 0 AND sd.status = \'tersedia\' AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa >= CURDATE())', 'LEFT')
            ->groupBy('gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus')
            ->orderBy('gd.nama_gol_darah', 'ASC')
            ->orderBy('gd.rhesus', 'ASC')
            ->getResultArray();
    }
}
?>