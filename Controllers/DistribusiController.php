<?php

// Controllers/DistribusiController.php

require_once 'Config/Database.php';
require_once 'Model/DistribusiModel.php';
require_once 'Model/StokModel.php';

class DistribusiController {
    private $distribusiModel;
    private $stokModel;

    public function __construct() {
        $this->distribusiModel = new DistribusiModel();
        $this->stokModel = new StokModel();
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    public function index() {
        $data['distribusi'] = $this->distribusiModel->getAllDistribusi();
        $data['stok_tersedia'] = $this->stokModel->getStokTersedia();
        $data['rumah_sakit'] = $this->distribusiModel->getRumahSakit();
        $this->view('distribusi/index', $data);
    }

    public function storeDistribusi() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_stok = $_POST['id_stok'] ?? null;
            $id_rs = $_POST['id_rs'] ?? null;
            $tanggal_distribusi = $_POST['tanggal_distribusi'] ?? date('Y-m-d');
            $id_petugas = $_SESSION['id_petugas'] ?? null;
            
            // Validation
            if (!$id_stok) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pilih kantong darah', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=distribusi_create');
                exit;
            }
            
            if (!$id_rs) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pilih rumah sakit tujuan', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=distribusi_create');
                exit;
            }
            
            // Distribute single kantong
            $success = $this->distribusiModel->createDistribusiPerKantong($id_stok, $id_rs, $tanggal_distribusi, $id_petugas);
            
            if ($success) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Distribusi darah berhasil dicatat', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mencatat distribusi darah', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=distribusi');
            exit;
        }
    }

    public function deleteDistribusi($id) {
    if ($this->distribusiModel->deleteDistribusi($id)) {
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Distribusi darah berhasil dipindahkan ke Arsip',
            'icon'    => 'trash'
        ];
    } else {
        $_SESSION['flash'] = [
            'type'    => 'danger',
            'message' => 'Gagal menghapus (mengarsipkan) distribusi',
            'icon'    => 'exclamation-triangle'
        ];
    }
    header('Location: index.php?action=distribusi');
    exit;
}


    public function storeRumahSakit() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama_rs' => $_POST['nama_rs'],
                'alamat' => $_POST['alamat'],
                'kontak' => $_POST['kontak']
            ];

            if ($this->distribusiModel->createRumahSakit($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rumah sakit berhasil ditambahkan', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan rumah sakit', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=distribusi');
            exit;
        }
    }

    private function view($view, $data = []) {
        extract($data);
        // PERBAIKAN: Pastikan path view konsisten
        require_once "View/$view.php";
    }
}