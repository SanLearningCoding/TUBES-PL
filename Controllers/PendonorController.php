<?php

// Controllers/PendonorController.php

require_once 'Config/Database.php';
require_once 'Model/PendonorModel.php';
require_once 'Model/TransaksiModel.php';
require_once 'Model/StokModel.php';

class PendonorController {
    private $pendonorModel;
    private $transaksiModel;
    private $stokModel;

    public function __construct() {
        $this->pendonorModel = new PendonorModel();
        $this->transaksiModel = new TransaksiModel();
        $this->stokModel = new StokModel();
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    public function index() {
        $data['pendonor'] = $this->pendonorModel->getAllPendonor();
        $this->view('pendonor/index', $data);
    }

    public function create() {
        $data['golongan'] = $this->stokModel->getAllGolongan();
        $this->view('pendonor/create', $data);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validasi golongan darah
            if (empty($_POST['id_gol_darah'])) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Golongan darah harus dipilih', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=pendonor_create');
                exit;
            }

            // Cek screening fields
            $has_disease = isset($_POST['has_hepatitis_b']) || 
                          isset($_POST['has_hepatitis_c']) || 
                          isset($_POST['has_aids']) || 
                          isset($_POST['has_hemofilia']) || 
                          isset($_POST['has_sickle_cell']) || 
                          isset($_POST['has_thalassemia']) || 
                          isset($_POST['has_leukemia']) || 
                          isset($_POST['has_lymphoma']) || 
                          isset($_POST['has_myeloma']) || 
                          isset($_POST['has_cjd']);
            
            // Cek juga other_illness
            $has_other_illness = !empty(trim($_POST['other_illness'] ?? ''));
            $is_healthy = !$has_disease && !$has_other_illness;
            
            $data = [
                'nama' => $_POST['nama'],
                'kontak' => $_POST['kontak'],
                'id_gol_darah' => (int)$_POST['id_gol_darah'],
                'has_hepatitis_b' => isset($_POST['has_hepatitis_b']) ? 1 : 0,
                'has_hepatitis_c' => isset($_POST['has_hepatitis_c']) ? 1 : 0,
                'has_aids' => isset($_POST['has_aids']) ? 1 : 0,
                'has_hemofilia' => isset($_POST['has_hemofilia']) ? 1 : 0,
                'has_sickle_cell' => isset($_POST['has_sickle_cell']) ? 1 : 0,
                'has_thalassemia' => isset($_POST['has_thalassemia']) ? 1 : 0,
                'has_leukemia' => isset($_POST['has_leukemia']) ? 1 : 0,
                'has_lymphoma' => isset($_POST['has_lymphoma']) ? 1 : 0,
                'has_myeloma' => isset($_POST['has_myeloma']) ? 1 : 0,
                'has_cjd' => isset($_POST['has_cjd']) ? 1 : 0,
                'other_illness' => $_POST['other_illness'] ?? '',
                'is_layak' => $is_healthy ? 1 : 0  // Layak hanya jika tidak ada penyakit apapun
            ];

            if ($this->pendonorModel->insertPendonor($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pendonor berhasil ditambahkan', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan pendonor', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=pendonor');
            exit;
        }
    }

    public function showDaftarPeringatan() {
        $data['peringatan'] = $this->pendonorModel->getDaftarPeringatanDonorUlang();
        $this->view('pendonor/peringatan', $data);
    }

    public function viewRiwayat($id) {
        $data['riwayat'] = $this->pendonorModel->getRiwayatDonasi($id);
        $data['pendonor'] = $this->pendonorModel->getPendonorById($id);
        $this->view('pendonor/riwayat', $data);
    }

    // PERBAIKAN: Method edit dan update untuk kelengkapan
    public function edit($id) {
        $data['pendonor'] = $this->pendonorModel->getPendonorById($id);
        if (!$data['pendonor']) {
            $_SESSION['error'] = 'Pendonor tidak ditemukan';
            header('Location: index.php?action=pendonor');
            exit;
        }
        $data['golongan'] = $this->stokModel->getAllGolongan();
        $this->view('pendonor/edit', $data);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama' => $_POST['nama'],
                'kontak' => $_POST['kontak'],
                'riwayat_penyakit' => $_POST['riwayat_penyakit'] ?? '',
                'id_gol_darah' => $_POST['id_gol_darah'] ?? null
            ];

            if ($this->pendonorModel->updatePendonor($id, $data)) {
                $_SESSION['success'] = 'Pendonor berhasil diupdate';
            } else {
                $_SESSION['error'] = 'Gagal mengupdate pendonor';
            }
            header('Location: index.php?action=pendonor');
            exit;
        }
    }

    // PERBAIKAN: Hapus storePendaftaran karena sudah ada di TransaksiController
    // atau tambahkan routing jika memang diperlukan

    private function view($view, $data = []) {
        extract($data);
        require_once "View/$view.php";
    }
}