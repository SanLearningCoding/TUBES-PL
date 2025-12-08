<?php

// Controllers/StokController.php

require_once 'Config/Database.php';
require_once 'Model/StokModel.php';
require_once 'Model/TransaksiModel.php';

class StokController {
    private $stokModel;
    private $transaksiModel;

    public function __construct() {
        $this->stokModel = new StokModel();
        $this->transaksiModel = new TransaksiModel();
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    public function showDashboard() {
        // Auto update expired statuses before calculating dashboard
        $this->stokModel->updateExpiredStatuses();
        $data['stok'] = $this->stokModel->getDashboardStokRealtime();
        $data['stok_tersedia'] = $this->stokModel->getStokTersedia();
        // Render the dashboard view (uses View/dashboard/index.php)
        $this->view('dashboard/index', $data);
    }

    // New: index list of stocks
    public function index() {
        // Ensure expired stok are updated before listing
        $this->stokModel->updateExpiredStatuses();
        $data['stocks'] = $this->stokModel->getAllStocks();
        // Golongan for create dropdown
        $data['golongan'] = $this->stokModel->getAllGolongan();
        $this->view('stok/index', $data);
    }

    public function create() {
        // Stok is now auto-generated from transaksi, not manually created
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Stok darah otomatis dibuat dari data transaksi donasi', 'icon' => 'info-circle'];
        header('Location: index.php?action=stok');
        exit;
    }

    public function store() {
        // Stok creation is now automatic from transaksi_donasi
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Stok darah otomatis dibuat dari data transaksi donasi', 'icon' => 'info-circle'];
        header('Location: index.php?action=stok');
        exit;
    }

    public function detail($id) {
        $data['stok'] = $this->stokModel->getStokById($id);
        if (!$data['stok']) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Stok tidak ditemukan untuk ID: ' . htmlspecialchars($id), 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=stok');
            exit;
        }
        $this->view('stok/detail', $data);
    }

    public function edit($id) {
        // Stok cannot be edited directly, only status_uji can be changed in detail view
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Stok tidak bisa diubah secara manual. Lihat detail untuk update status uji.', 'icon' => 'info-circle'];
        header('Location: index.php?action=stok');
        exit;
    }

    public function update($id) {
        // Stok cannot be edited directly after creation
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Stok tidak dapat diubah setelah dibuat', 'icon' => 'exclamation-triangle'];
        header('Location: index.php?action=stok');
        exit;
    }

    public function delete($id) {
        if ($this->stokModel->deleteStock($id)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Stok berhasil dihapus', 'icon' => 'trash'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus stok', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=stok');
        exit;
    }

    public function storeInputPascaUji() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validation: ensure tanggal_kadaluarsa is after tanggal_pengujian
            $tgl_pengujian = $_POST['tanggal_pengujian'] ?? '';
            $tgl_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? '';
            if (!$tgl_pengujian || !$tgl_kadaluarsa || strtotime($tgl_kadaluarsa) <= strtotime($tgl_pengujian)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tanggal kadaluarsa harus lebih besar daripada tanggal pengujian.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=stok');
                exit;
            }
            $data = [
                'id_transaksi' => $_POST['id_transaksi'],
                'id_gol_darah' => $_POST['id_gol_darah'],
                'tanggal_pengujian' => $_POST['tanggal_pengujian'],
                'status_uji' => $_POST['status_uji'],
                'tanggal_kadaluarsa' => $_POST['tanggal_kadaluarsa'],
                'jumlah_kantong' => $_POST['jumlah_kantong'] ?? 1,
                'status' => 'tersedia'
            ];

            if ($this->stokModel->fillPlaceholderForTransaction($data['id_transaksi'], $data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Hasil uji berhasil disimpan dan stok ditambahkan', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan hasil uji', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=stok');
            exit;
        }
    }

    public function setGroupTotal() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: index.php?action=stok');
            exit;
        }
        $id_gol = $_POST['id_gol_darah'] ?? null;
        $mode = $_POST['mode'] ?? 'ml';
        $target_ml = 0;
        if ($mode == 'ml') {
            $target_ml = intval($_POST['target_volume_ml'] ?? 0);
        } else {
            $bags = intval($_POST['target_bags'] ?? 0);
            $target_ml = $bags * 450;
        }
        if (!$id_gol) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Golongan tidak valid', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=stok');
            exit;
        }
        // Calculate current total kantong
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_kantong),0) as total FROM stok_darah WHERE id_gol_darah = ? AND status = 'tersedia' AND status_uji = 'lolos'");
        $stmt->execute([$id_gol]);
        $current = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
        $delta = $target_ml - $current;
        if ($delta == 0) {
            $_SESSION['success'] = 'Jumlah stok sudah sesuai target';
            header('Location: index.php?action=stok');
            exit;
        }
        try {
            $db->beginTransaction();
            if ($delta > 0) {
                // Add placeholder stok rows (1 kantong per row)
                for ($i = 0; $i < $delta; $i++) {
                    $ins = $db->prepare("INSERT INTO stok_darah (id_transaksi, id_gol_darah, tanggal_pengujian, status_uji, tanggal_kadaluarsa, jumlah_kantong, status) VALUES (NULL, ?, NULL, 'lolos', NULL, 1, 'tersedia')");
                    $ins->execute([$id_gol]);
                }
                $_SESSION['success'] = 'Stok berhasil ditambahkan untuk mencapai target';
            } else {
                // Reduce stok: mark stok as terpakai starting from oldest
                $toReduce = abs($delta);
                $stmtRows = $db->prepare("SELECT id_stok, jumlah_kantong FROM stok_darah WHERE id_gol_darah = ? AND status = 'tersedia' AND status_uji = 'lolos' ORDER BY tanggal_kadaluarsa ASC, id_stok ASC FOR UPDATE");
                $stmtRows->execute([$id_gol]);
                while ($row = $stmtRows->fetch(PDO::FETCH_ASSOC)) {
                    $id_stok = $row['id_stok'];
                    $avail = intval($row['jumlah_kantong']);
                    if ($avail <= 0) continue;
                    $consume = min($avail, $toReduce);
                    $new = $avail - $consume;
                    if ($new <= 0) {
                        $upd = $db->prepare("UPDATE stok_darah SET jumlah_kantong = 0, status = 'terpakai' WHERE id_stok = ?");
                        $upd->execute([$id_stok]);
                    } else {
                        $upd = $db->prepare("UPDATE stok_darah SET jumlah_kantong = ? WHERE id_stok = ?");
                        $upd->execute([$new, $id_stok]);
                    }
                    $toReduce -= $consume;
                    if ($toReduce <= 0) break;
                }
                $_SESSION['success'] = 'Stok berhasil disesuaikan untuk mencapai target';
            }
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['error'] = 'Gagal menyesuaikan stok: ' . $e->getMessage();
        }
        header('Location: index.php?action=stok');
        exit;
    }

    public function updateStatusKadaluarsa($id_stok) {
        if ($this->stokModel->updateStatusStok($id_stok, 'kadaluarsa')) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status stok berhasil diupdate menjadi kadaluarsa', 'icon' => 'check-circle'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate status stok', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=stok');
        exit;
    }

    private function view($view, $data = []) {
        extract($data);
        // PERBAIKAN: Pastikan path view konsisten
        require_once "View/$view.php";
    }
}