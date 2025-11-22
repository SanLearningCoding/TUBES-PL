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
        session_start();
    }

    public function showDashboard() {
        $data['stok'] = $this->stokModel->getDashboardStokRealtime();
        $data['stok_tersedia'] = $this->stokModel->getStokTersedia();
        $this->view('stok/dashboard', $data);
    }

    public function storeInputPascaUji() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id_transaksi' => $_POST['id_transaksi'],
                'id_gol_darah' => $_POST['id_gol_darah'],
                'tanggal_pengujian' => $_POST['tanggal_pengujian'],
                'status_uji' => $_POST['status_uji'],
                'tanggal_kadaluarsa' => $_POST['tanggal_kadaluarsa'],
                'volume_ml' => $_POST['volume_ml'],
                'status' => 'tersedia'
            ];

            if ($this->stokModel->createStokPascaUji($data['id_transaksi'], $data)) {
                $_SESSION['success'] = 'Hasil uji berhasil disimpan dan stok ditambahkan';
            } else {
                $_SESSION['error'] = 'Gagal menyimpan hasil uji';
            }
            header('Location: index.php?action=stok');
            exit;
        }
    }

    public function updateStatusKadaluarsa($id_stok) {
        if ($this->stokModel->updateStatusStok($id_stok, 'kadaluarsa')) {
            $_SESSION['success'] = 'Status stok berhasil diupdate menjadi kadaluarsa';
        } else {
            $_SESSION['error'] = 'Gagal mengupdate status stok';
        }
        header('Location: index.php?action=stok');
        exit;
    }

    private function view($view, $data = []) {
        extract($data);
        // PERBAIKAN: Pastikan path view konsisten
        require_once "View/stok/$view.php";
    }
}