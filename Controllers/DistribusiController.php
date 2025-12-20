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
        error_log("DistribusiController::storeDistribusi called. POST data: " . print_r($_POST, true)); // Debug log

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
             $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Metode tidak diizinkan.', 'icon' => 'exclamation-triangle'];
             header('Location: index.php?action=distribusi_create');
             exit;
        }

        // Ambil data dari $_POST
        $id_stok = $_POST['id_stok'] ?? null;
        $id_rs = $_POST['id_rs'] ?? null;
        $tanggal_distribusi = $_POST['tanggal_distribusi'] ?? date('Y-m-d');
        $id_petugas = $_SESSION['id_petugas'] ?? null; // Asumsikan id_petugas diambil dari session

        // Validasi: Pastikan id_stok dan id_rs adalah integer positif
        // is_numeric() memastikan nilainya numerik (termasuk string angka seperti "123")
        // intval() mengkonversi ke integer
        $id_stok_int = is_numeric($id_stok) ? intval($id_stok) : 0;
        $id_rs_int = is_numeric($id_rs) ? intval($id_rs) : 0;

        // Basic validation - Gunakan nilai integer yang telah dikonversi
        if ($id_stok_int <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pilih kantong darah yang valid.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=distribusi_create');
            exit;
        }

        if ($id_rs_int <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pilih rumah sakit tujuan yang valid.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=distribusi_create');
            exit;
        }

        if (empty($tanggal_distribusi) || !strtotime($tanggal_distribusi)) { // Validasi tanggal dasar
             $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tanggal distribusi tidak valid.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=distribusi_create');
            exit;
        }

        if (!$id_petugas) { // Validasi id_petugas dari session
             $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Sesi petugas tidak valid.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=distribusi_create'); // Atau redirect ke login
            exit;
        }

        // Jika semua validasi lolos, lanjutkan ke model
        // Distribute single kantong
        $success = $this->distribusiModel->createDistribusiPerKantong($id_stok_int, $id_rs_int, $tanggal_distribusi, $id_petugas);

        if ($success) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Distribusi darah berhasil dicatat.', 'icon' => 'check-circle'];
        } else {
            // Jika createDistribusiPerKantong gagal (karena stok mungkin sudah tidak tersedia, atau error DB)
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mencatat distribusi darah. Stok mungkin sudah tidak tersedia atau terjadi kesalahan.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=distribusi');
        exit;
    }


    public function deleteDistribusi($id) {
        if ($this->distribusiModel->deleteDistribusi($id)) {
            $_SESSION['flash'] = [
                'type'    => 'success', // Ganti 'danger' ke 'success' karena ini adalah keberhasilan menghapus
                'message' => 'Distribusi darah berhasil dipindahkan ke Arsip',
                'icon'    => 'archive' // Ganti 'trash' ke 'archive' atau sesuaikan
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
                'nama_rs' => trim($_POST['nama_rs']), // Tambahkan trim
                'alamat' => trim($_POST['alamat']),   // Tambahkan trim
                'kontak' => $_POST['kontak']
            ];

            // Validasi sederhana
            if (empty($data['nama_rs']) || empty($data['alamat'])) {
                 $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nama dan alamat rumah sakit wajib diisi.', 'icon' => 'exclamation-triangle'];
            } else {
                if ($this->distribusiModel->createRumahSakit($data)) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rumah sakit berhasil ditambahkan', 'icon' => 'check-circle'];
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan rumah sakit', 'icon' => 'exclamation-triangle'];
                }
            }
            header('Location: index.php?action=distribusi');
            exit;
        }
    }

    private function view($view, $data = []) {
        extract($data);
        require_once "View/$view.php";
    }
}
?>