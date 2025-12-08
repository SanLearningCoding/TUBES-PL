<?php

// Controllers/LaporanController.php

require_once 'Config/Database.php';
require_once 'Model/TransaksiModel.php';
require_once 'Model/StokModel.php';
require_once 'Model/DistribusiModel.php';

class LaporanController {
    private $transaksiModel;
    private $stokModel;
    private $distribusiModel;

    public function __construct() {
        $this->transaksiModel = new TransaksiModel();
        $this->stokModel = new StokModel();
        $this->distribusiModel = new DistribusiModel();
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    public function viewKinerjaDonor() {
        $periode = $_GET['periode'] ?? date('Y-m');
        $data['laporan'] = $this->transaksiModel->getLaporanKinerjaDonor($periode);
        $data['periode'] = $periode;
        $this->view('laporan/kinerja_donor', $data);
    }

    public function viewEvaluasiStok() {
        $data['stok'] = $this->stokModel->getDashboardStokRealtime();
        $data['stok_kadaluarsa'] = $this->stokModel->getStokKadaluarsa();
        $this->view('laporan/evaluasi_stok', $data);
    }

    public function generateLaporanDistribusi() {
        $rs_id = $_GET['rs_id'] ?? null;
        $tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
        $tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');

        $data['laporan'] = $this->distribusiModel->getLaporanDistribusi($rs_id, $tanggal_awal, $tanggal_akhir);
        $data['rumah_sakit'] = $this->distribusiModel->getRumahSakit();
        $data['rs_id'] = $rs_id;
        $data['tanggal_awal'] = $tanggal_awal;
        $data['tanggal_akhir'] = $tanggal_akhir;

        $this->view('laporan/distribusi', $data);
    }

    private function view($view, $data = []) {
        extract($data);
        // PERBAIKAN: Pastikan path view konsisten
        require_once "View/laporan/$view.php";
    }
}