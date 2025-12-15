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

            // Lock the selected unit to prevent race conditions
            $sel = $this->db->prepare("SELECT id_stok, status FROM stok_darah WHERE id_stok = ? AND is_deleted = 0 FOR UPDATE");
            $sel->execute([$id_stok]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);

            // Check if stok exists and is available
            if (!$row || $row['status'] !== 'tersedia') {
                $this->db->rollBack();
                error_log("createDistribusiPerKantong: Stok tidak ditemukan atau tidak tersedia (ID: $id_stok). Transaksi dibatalkan.");
                return false; // Indicate failure
            }

            // Update unit status to 'terpakai'
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
            error_log("createDistribusiPerKantong: Sukses. ID Stok: $id_stok, ID RS: $id_rs, ID Distribusi Baru: $id_distribusi");
            return $id_distribusi; // Indicate success and return new ID

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("createDistribusiPerKantong error (ID Stok: $id_stok): " . $e->getMessage());
            return false; // Indicate failure
        }
    }

    /**
     * Get list of available stok per golongan for distribution
     * Returns individual available units (per-kantong)
     */
    public function getAvailableStokForDistribusi() {
        $builder = new QueryBuilder($this->db, 'stok_darah sd');
        return $builder->select("sd.id_stok, sd.id_gol_darah, gd.nama_gol_darah, gd.rhesus, sd.tanggal_kadaluarsa, sd.status_uji, sd.status")
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah', 'LEFT')
            ->where('sd.status_uji', 'lolos')
            ->where('sd.status', 'tersedia')
            ->where('sd.is_deleted', 0)
            // Gunakan whereRaw untuk kondisi tanggal
            ->whereRaw("(sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa >= CURDATE())")
            ->orderBy('gd.nama_gol_darah', 'ASC')
            ->orderBy('gd.rhesus', 'ASC')
            ->orderBy('sd.tanggal_kadaluarsa', 'ASC') // FEFO
            ->orderBy('sd.created_at', 'ASC')         // Jika expiry sama, ambil yang lebih lama dibuat
            ->getResultArray();
    }

    /**
     * Distribute one unit (FEFO) for a given golongan darah.
     * Returns id_stok of distributed unit or false if none available.
     * Note: This function seems less relevant if UI picks specific stok, kept for completeness.
     */
    public function distributeOneUnit($id_gol_darah, $id_rs = null, $id_petugas = null, $tanggal_distribusi = null) {
        $db = $this->db;
        try {
            $db->beginTransaction();

            // Select one available unit with earliest expiry (FEFO) and lock it
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
                error_log("distributeOneUnit: Tidak ada stok tersedia untuk golongan ID: $id_gol_darah. Transaksi dibatalkan.");
                return false; // Indicate failure/no stock
            }
            $id_stok = intval($row['id_stok']);

            // Mark unit as distributed ('terpakai')
            $upd = $db->prepare("UPDATE stok_darah SET status = 'terpakai', updated_at = NOW() WHERE id_stok = ?");
            $ok = $upd->execute([$id_stok]);
            if (!$ok) {
                 throw new Exception('Failed updating stok status to terpakai');
            }

            // Check if distribusi_darah table exists (optional step, might be guaranteed)
            $checkTable = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'distribusi_darah'");
            $checkTable->execute();
            $tableExists = intval($checkTable->fetchColumn()) > 0;

            if ($tableExists) {
                $tanggal_distribusi = $tanggal_distribusi ?? date('Y-m-d');
                $ins = $db->prepare("INSERT INTO distribusi_darah (id_stok, id_rs, id_petugas, tanggal_distribusi, status, jumlah_volume) VALUES (?, ?, ?, ?, 'dikirim', 1)");
                $ins->execute([$id_stok, $id_rs, $id_petugas, $tanggal_distribusi]);
            } else {
                error_log("distributeOneUnit: Tabel 'distribusi_darah' tidak ditemukan. Hanya stok yang diperbarui.");
                // Hanya memperbarui stok, tidak menyimpan ke tabel distribusi.
            }

            $db->commit();
            error_log("distributeOneUnit: Sukses mendistribusikan 1 unit. ID Stok: $id_stok");
            return $id_stok; // Return the ID of the distributed unit
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('distributeOneUnit error: ' . $e->getMessage());
            return false; // Indicate failure
        }
    }

    /**
     * Get count of available stok per golongan (for summary/dashboard views)
     */
    public function getAvailableCountByGolongan() {
        $builder = new QueryBuilder($this->db, 'golongan_darah gd');
        // Join stok_darah dengan kondisi di ON clause untuk menghitung hanya stok yang lolos uji, tersedia, tidak dihapus, dan tidak kadaluarsa
        return $builder->select("gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus, COUNT(sd.id_stok) as jumlah_kantong")
            ->join('stok_darah sd', 'sd.id_gol_darah = gd.id_gol_darah AND sd.status_uji = \'lolos\' AND sd.status = \'tersedia\' AND sd.is_deleted = 0 AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa >= CURDATE())', 'LEFT')
            ->groupBy('gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus')
            // Hanya tampilkan golongan yang memiliki stok > 0
            ->having('jumlah_kantong > 0')
            ->orderBy('gd.nama_gol_darah', 'ASC')
            ->orderBy('gd.rhesus', 'ASC')
            ->getResultArray();
    }

    public function deleteDistribusi($id_distribusi) {
        try {
            // Gunakan QueryBuilder untuk soft delete
            $builder = new QueryBuilder($this->db, 'distribusi_darah');
            $rowsAffected = $builder->where('id_distribusi', $id_distribusi)
                                  ->update(['is_deleted' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);

            // Cek apakah update berhasil (artinya baris ditemukan dan diupdate)
            if ($rowsAffected > 0) {
                error_log("deleteDistribusi: Sukses mengarsipkan distribusi ID: $id_distribusi");
                return true;
            } else {
                error_log("deleteDistribusi: Gagal mengarsipkan distribusi ID: $id_distribusi. Mungkin tidak ditemukan.");
                return false;
            }
        } catch (Exception $e) {
            error_log("deleteDistribusi error (ID: $id_distribusi): " . $e->getMessage());
            return false;
        }
    }

    public function getAllDistribusi() {
        $builder = new QueryBuilder($this->db, 'distribusi_darah dd');
        return $builder->select("dd.id_distribusi, dd.id_stok, dd.id_rs, dd.tanggal_distribusi, dd.status, dd.jumlah_volume, dd.created_at, dd.updated_at, dd.deleted_at, rs.nama_rs, sd.id_transaksi, gd.nama_gol_darah, gd.rhesus, pt.nama_petugas")
            ->join('rumah_sakit rs', 'dd.id_rs = rs.id_rs', 'LEFT')
            ->join('stok_darah sd', 'dd.id_stok = sd.id_stok', 'LEFT')
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah', 'LEFT')
            ->join('petugas pt', 'dd.id_petugas = pt.id_petugas', 'LEFT')
            ->where('dd.is_deleted', 0) // Hanya tampilkan yang tidak diarsipkan
            ->orderBy('dd.tanggal_distribusi', 'DESC')
            ->orderBy('dd.created_at', 'DESC') // Jika tanggal sama, urutkan berdasarkan created_at
            ->getResultArray();
    }

    public function getDistribusiById($id_distribusi) {
        $builder = new QueryBuilder($this->db, 'distribusi_darah dd');
        return $builder->select("dd.*, rs.nama_rs, rs.alamat, rs.kontak, sd.id_transaksi, gd.nama_gol_darah, gd.rhesus, pt.nama_petugas")
            ->join('rumah_sakit rs', 'dd.id_rs = rs.id_rs', 'LEFT')
            ->join('stok_darah sd', 'dd.id_stok = sd.id_stok', 'LEFT')
            ->join('golongan_darah gd', 'sd.id_gol_darah = gd.id_gol_darah', 'LEFT')
            ->join('petugas pt', 'dd.id_petugas = pt.id_petugas', 'LEFT')
            ->where('dd.id_distribusi', $id_distribusi)
            ->where('dd.is_deleted', 0) // Pastikan hanya mengambil yang tidak dihapus
            ->getRowArray();
    }

    public function getRumahSakit() {
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        return $builder->select('id_rs, nama_rs, alamat, kontak')
            ->where('is_deleted', 0) // Hanya tampilkan yang tidak dihapus
            ->orderBy('nama_rs', 'ASC')
            ->getResultArray();
    }

    /**
     * Update distribusi dan sinkronkan status stok berdasarkan status distribusi
     * - dikirim/diterima: stok = terpakai
     * - dibatalkan: stok = tersedia
     */
    public function updateDistribusiAndRestoreIfCanceled($id_distribusi, $id_rs, $tanggal_distribusi, $status) {
        try {
            $this->db->beginTransaction();

            // Ambil data distribusi saat ini untuk mendapatkan id_stok dan status lama
            $stmt = $this->db->prepare('SELECT id_distribusi, id_stok, status FROM distribusi_darah WHERE id_distribusi = ? FOR UPDATE');
            $stmt->execute([$id_distribusi]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->rollBack();
                error_log("updateDistribusiAndRestoreIfCanceled: Distribusi ID $id_distribusi tidak ditemukan. Transaksi dibatalkan.");
                return false;
            }

            $current_status = $row['status'];
            $id_stok = $row['id_stok'];

            // Update record distribusi
            $upd_dist = $this->db->prepare('UPDATE distribusi_darah SET id_rs = ?, tanggal_distribusi = ?, status = ? WHERE id_distribusi = ?');
            $ok_dist = $upd_dist->execute([$id_rs, $tanggal_distribusi, $status, $id_distribusi]);

            if (!$ok_dist) {
                $this->db->rollBack();
                error_log("updateDistribusiAndRestoreIfCanceled: Gagal update distribusi_darah ID $id_distribusi. Transaksi dibatalkan.");
                return false;
            }

            // Sinkronkan status stok jika id_stok valid dan status berubah
            if (!empty($id_stok) && $current_status !== $status) {
                $new_stok_status = null;
                if ($status === 'dibatalkan' && $current_status !== 'dibatalkan') {
                    // Hanya kembalikan ke 'tersedia' jika sebelumnya bukan 'dibatalkan'
                    $new_stok_status = 'tersedia';
                } elseif (($status === 'dikirim' || $status === 'diterima') && $current_status === 'dibatalkan') {
                    // Hanya ubah ke 'terpakai' jika sebelumnya 'dibatalkan' dan sekarang dikirim/diterima
                    $new_stok_status = 'terpakai';
                }
                // Jika status baru bukan dibatalkan/dikirim/diterima, atau status tidak berubah, jangan ubah stok

                if ($new_stok_status) {
                    $upd_stk = $this->db->prepare("UPDATE stok_darah SET status = ?, updated_at = NOW() WHERE id_stok = ?");
                    $ok_stk = $upd_stk->execute([$new_stok_status, $id_stok]);
                    if (!$ok_stk) {
                        throw new Exception("Gagal update status stok_darah ID $id_stok ke '$new_stok_status'");
                    }
                    error_log("updateDistribusiAndRestoreIfCanceled: Status stok ID $id_stok diupdate ke '$new_stok_status' karena distribusi ID $id_distribusi status berubah dari '$current_status' ke '$status'.");
                }
            }

            $this->db->commit();
            error_log("updateDistribusiAndRestoreIfCanceled: Sukses update distribusi ID $id_distribusi dan sinkronisasi stok jika diperlukan.");
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("updateDistribusiAndRestoreIfCanceled error (ID: $id_distribusi): " . $e->getMessage());
            return false;
        }
    }

    public function createRumahSakit($data) {
        // Sanitasi kontak: hanya angka
        if (isset($data['kontak'])) {
            $data['kontak'] = preg_replace('/\D+/', '', $data['kontak']);
        }
        // Gunakan QueryBuilder untuk insert
        $builder = new QueryBuilder($this->db, 'rumah_sakit');
        return $builder->insert($data);
    }
}
?>