<?php


require_once 'Config/Database.php';
require_once 'Model/DistribusiModel.php';
require_once 'Model/StokModel.php';

class DistribusiController {
    private $distribusiModel;
    private $stokModel;

    public function __construct() {
        $this->distribusiModel = new DistribusiModel();
        $this->stokModel = new StokModel();
        session_start();
    }

    public function index() {
        $data['distribusi'] = $this->distribusiModel->getAllDistribusi();
        $data['stok_tersedia'] = $this->stokModel->getStokTersedia();
        $data['rumah_sakit'] = $this->distribusiModel->getRumahSakit();
        $this->view('distribusi/index', $data);
    }

    public function storeDistribusi() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id_stok' => $_POST['id_stok'],
                'id_rs' => $_POST['id_rs'],
                'id_petugas' => $_SESSION['id_petugas'],
                'tanggal_distribusi' => $_POST['tanggal_distribusi'],
                'status_pengiriman' => $_POST['status_pengiriman']
            ];

            $id_distribusi = $this->distribusiModel->createDistribusi($data, $data['id_stok']);
            if ($id_distribusi) {
                $_SESSION['success'] = 'Distribusi darah berhasil dicatat';
            } else {
                $_SESSION['error'] = 'Gagal mencatat distribusi darah';
            }
            header('Location: index.php?action=distribusi');
            exit;
        }
    }

    public function viewLacakRiwayat($id_stok) {
        $data['stok'] = $this->stokModel->getStokById($id_stok);
        $data['distribusi'] = $this->distribusiModel->getDistribusiByStok($id_stok);
        $this->view('distribusi/lacak', $data);
    }

    public function storeRumahSakit() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama_rs' => $_POST['nama_rs'],
                'alamat' => $_POST['alamat'],
                'kontak' => $_POST['kontak']
            ];

            if ($this->distribusiModel->createRumahSakit($data)) {
                $_SESSION['success'] = 'Rumah sakit berhasil ditambahkan';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan rumah sakit';
            }
            header('Location: index.php?action=distribusi');
            exit;
        }
    }

    private function view($view, $data = []) {
        extract($data);
        require_once "View/distribusi/$view.php";
    }
}