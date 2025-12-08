<?php

// Controllers/TransaksiController.php

require_once 'Config/Database.php';
require_once 'Model/TransaksiModel.php';
require_once 'Model/PendonorModel.php';
require_once 'Model/KegiatanModel.php';

class TransaksiController {
    private $transaksiModel;
    private $pendonorModel;
    private $kegiatanModel;

    public function __construct() {
        $this->transaksiModel = new TransaksiModel();
        $this->pendonorModel = new PendonorModel();
        $this->kegiatanModel = new KegiatanModel();
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    public function index() {
        $data['transaksi'] = $this->transaksiModel->getAllTransaksi();
        $data['pendonor'] = $this->pendonorModel->getAllPendonor();
        $data['kegiatan'] = $this->kegiatanModel->getAllKegiatan();
        $this->view('transaksi/index', $data);
    }

    public function storeTransaksi() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id_pendonor = $_POST['id_pendonor'] ?? 0;
            
            // VALIDASI: Pastikan pendonor yang dipilih layak (status kesehatan sehat)
            $pendonor = $this->pendonorModel->getPendonorById($id_pendonor);
            
            if (!$pendonor) {
                $_SESSION['error'] = 'Pendonor tidak ditemukan!';
                header('Location: index.php?action=transaksi');
                exit;
            }
            
            // CEK STATUS KESEHATAN - Kolom is_layak harus 1
            if (isset($pendonor['is_layak']) && $pendonor['is_layak'] != 1) {
                $_SESSION['error'] = 'Transaksi ditolak! Pendonor dengan status kesehatan tidak layak tidak dapat melakukan transaksi donasi. Hanya pendonor yang sehat yang dapat melakukan donasi.';
                header('Location: index.php?action=transaksi');
                exit;
            }
            
            $data = [
                'id_pendonor' => $id_pendonor,
                'id_kegiatan' => $_POST['id_kegiatan'],
                'id_petugas' => $_SESSION['id_petugas'],
                'tanggal_donasi' => $_POST['tanggal_donasi'],
                'jumlah_kantong' => $_POST['jumlah_kantong']
            ];

            $id_transaksi = $this->transaksiModel->createTransaksi($data);
            if ($id_transaksi) {
                $_SESSION['success'] = 'Transaksi donor berhasil dicatat';
            } else {
                $_SESSION['error'] = 'Gagal mencatat transaksi donor';
            }
            header('Location: index.php?action=transaksi');
            exit;
        }
    }

    public function storeKegiatan() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama_kegiatan' => $_POST['nama_kegiatan'],
                'tanggal' => $_POST['tanggal'],
                'lokasi' => $_POST['lokasi']
            ];

            if ($this->kegiatanModel->createKegiatan($data)) {
                $_SESSION['success'] = 'Kegiatan donor berhasil ditambahkan';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan kegiatan donor';
            }
            header('Location: index.php?action=transaksi');
            exit;
        }
    }

    public function updateTransaksi($id) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id_pendonor' => $_POST['id_pendonor'],
                'id_kegiatan' => $_POST['id_kegiatan'],
                'tanggal_donasi' => $_POST['tanggal_donasi'],
                'jumlah_kantong' => $_POST['jumlah_kantong']
            ];

            if ($this->transaksiModel->updateTransaksi($id, $data)) {
                $_SESSION['success'] = 'Transaksi berhasil diupdate';
            } else {
                $_SESSION['error'] = 'Gagal mengupdate transaksi';
            }
            header('Location: index.php?action=transaksi');
            exit;
        }
    }
  
    private function view($view, $data = []) {
        extract($data);
        // PERBAIKAN: Pastikan path view konsisten
        require_once "View/$view.php";
    }
}