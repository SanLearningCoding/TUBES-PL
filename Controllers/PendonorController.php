<?php


require_once 'Config/Database.php';
require_once 'Model/PendonorModel.php';
require_once 'Model/TransaksiModel.php';

class PendonorController {
    private $pendonorModel;
    private $transaksiModel;

    public function __construct() {
        $this->pendonorModel = new PendonorModel();
        $this->transaksiModel = new TransaksiModel();
        session_start();
    }

    public function index() {
        $data['pendonor'] = $this->pendonorModel->getAllPendonor();
        $this->view('pendonor/index', $data);
    }

    public function create() {
        $this->view('pendonor/create');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama' => $_POST['nama'],
                'kontak' => $_POST['kontak'],
                'riwayat_penyakit' => $_POST['riwayat_penyakit'] ?? ''
            ];

            if ($this->pendonorModel->insertPendonor($data)) {
                $_SESSION['success'] = 'Pendonor berhasil ditambahkan';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan pendonor';
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

    public function edit($id) {
        $data['pendonor'] = $this->pendonorModel->getPendonorById($id);
        if (!$data['pendonor']) {
            $_SESSION['error'] = 'Pendonor tidak ditemukan';
            header('Location: index.php?action=pendonor');
            exit;
        }
        $this->view('pendonor/edit', $data);
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama' => $_POST['nama'],
                'kontak' => $_POST['kontak'],
                'riwayat_penyakit' => $_POST['riwayat_penyakit'] ?? ''
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

    private function view($view, $data = []) {
        extract($data);
        require_once "View/pendonor/$view.php";
    }
}