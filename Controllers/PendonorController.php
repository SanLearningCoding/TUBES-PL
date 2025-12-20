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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Jika bukan POST, redirect atau tampilkan error
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Akses tidak sah.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor_create');
            exit;
        }

        // Ambil data dari $_POST
        $nama = trim($_POST['nama'] ?? '');
        $tanggal_lahir_raw = $_POST['tanggal_lahir'] ?? null; // Ambil dulu, bisa null
        $jenis_kelamin_raw = $_POST['jenis_kelamin'] ?? null; // Ambil dulu, bisa null
        $alamat_raw = trim($_POST['alamat'] ?? ''); // Ambil dulu, bisa string kosong
        $kontak_raw = $_POST['kontak'] ?? ''; // Ambil nilai mentah
        $riwayat_penyakit_raw = trim($_POST['riwayat_penyakit'] ?? '');
        $id_gol_darah = $_POST['id_gol_darah'] ?? null;

        // Proses kontak: hanya simpan angka
        $kontak = preg_replace('/\D+/', '', $kontak_raw);

        // Determine health status (is_layak) based on screening diseases and other_illness:
        // - is_layak = 0 (TIDAK LAYAK/Merah): if ANY screening disease checkbox is checked
        // - is_layak = 1 (LAYAK/Kuning): if NO screening diseases but other_illness is filled
        // - is_layak = 2 (SEHAT/Hijau): if NO screening diseases AND NO other_illness
        
        $screening_diseases = [
            'has_hepatitis_b', 'has_hepatitis_c', 'has_aids', 'has_hemofilia',
            'has_sickle_cell', 'has_thalassemia', 'has_leukemia', 'has_lymphoma',
            'has_myeloma', 'has_cjd'
        ];
        
        // Check if any screening disease checkbox is checked
        $has_disease = false;
        foreach ($screening_diseases as $disease_field) {
            if (isset($_POST[$disease_field]) && $_POST[$disease_field] == 1) {
                $has_disease = true;
                break;
            }
        }
        
        // Check if other_illness is filled
        $other_illness_filled = !empty(trim($_POST['other_illness'] ?? ''));
        
        // Calculate is_layak status based on business rules
        if ($has_disease) {
            $is_layak_status = 0; // TIDAK LAYAK (merah)
        } elseif ($other_illness_filled) {
            $is_layak_status = 1; // LAYAK (kuning)
        } else {
            $is_layak_status = 2; // SEHAT (hijau)
        }

        // --- VALIDASI ---
        $errors = [];
        if (empty($nama)) {
            $errors[] = 'Nama wajib diisi.';
        }
        if (strlen($kontak) < 6) {
            $errors[] = 'Nomor HP tidak valid. Minimal 6 digit.';
        }
        if (is_null($id_gol_darah)) {
            $errors[] = 'Golongan darah wajib dipilih.';
        }
        // Validasi nomor HP/kontak harus unik
        $stmt = $this->pendonorModel->getDbConnection()->prepare("SELECT COUNT(*) FROM pendonor WHERE kontak = ? AND is_deleted = 0");
        $stmt->execute([$kontak]);
        $exists = intval($stmt->fetchColumn()) > 0;
        if ($exists) {
            $errors[] = 'Nomor HP/kontak sudah terdaftar, gunakan nomor lain.';
        }
        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors), 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor_create');
            exit;
        }
        // --- END VALIDASI ---

        // --- PERSIAPAN DATA UNTUK INSERT ---
        // Hanya masukkan data yang sesuai dengan kolom di tabel 'pendonor'
        // Kolom yang tidak ada di tabel harus dihilangkan dari array $data
        // Berdasarkan skema database, kolom yang valid: nama, kontak, riwayat_penyakit, id_gol_darah, is_deleted, deleted_at, created_at, updated_at, has_*, other_illness, is_layak
        // Kolom yang TIDAK ADA: tanggal_lahir, jenis_kelamin, alamat
        $data = [
            'nama' => $nama,
            // 'tanggal_lahir' => $tanggal_lahir_raw, // Dihapus
            // 'jenis_kelamin' => $jenis_kelamin_raw, // Dihapus
            // 'alamat' => $alamat_raw, // Dihapus
            'kontak' => $kontak, // Gunakan nama kolom yang sesuai dengan DB
            'riwayat_penyakit' => $riwayat_penyakit_raw,
            'id_gol_darah' => (int)$id_gol_darah,
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
            'is_layak' => $is_layak_status,  // 0 = TIDAK LAYAK, 1 = LAYAK, 2 = SEHAT
            // 'created_at' => date('Y-m-d H:i:s') // Tambahkan jika kolom ada di DB dan tidak otomatis
        ];

        // --- END PERSIAPAN DATA ---

        if ($this->pendonorModel->insertPendonor($data)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pendonor berhasil ditambahkan', 'icon' => 'check-circle'];
        } else {
            // Tambahkan logging untuk debugging jika gagal
            error_log("PendonorController: Gagal insert ke DB. Data: " . print_r($data, true));
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan pendonor', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=pendonor');
        exit; // Tambahkan exit
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: index.php?action=pendonor');
                exit;
            }

            // --- AWAL PERUBAHAN ---
            // Ambil data dari $_POST dan proses
            // Termasuk field-field screening dan other_illness
            $nama = trim($_POST['nama'] ?? '');
            $kontak_raw = $_POST['kontak'] ?? '';
            $riwayat_penyakit_raw = trim($_POST['riwayat_penyakit'] ?? ''); // Ini sekarang diisi oleh JS
            $id_gol_darah = $_POST['id_gol_darah'] ?? null;

            // Proses kontak: hanya simpan angka
            $kontak = preg_replace('/\D+/', '', $kontak_raw);

            // Cek screening fields dari POST
            $screening_diseases = [
                'has_hepatitis_b', 'has_hepatitis_c', 'has_aids', 'has_hemofilia',
                'has_sickle_cell', 'has_thalassemia', 'has_leukemia', 'has_lymphoma',
                'has_myeloma', 'has_cjd'
            ];
            
            // Check if any screening disease checkbox is checked
            $has_disease = false;
            foreach ($screening_diseases as $disease_field) {
                if (isset($_POST[$disease_field]) && $_POST[$disease_field] == 1) {
                    $has_disease = true;
                    break;
                }
            }
            
            // Check if other_illness is filled
            $other_illness_filled = !empty(trim($_POST['other_illness'] ?? ''));
            
            // Calculate is_layak status based on business rules
            if ($has_disease) {
                $is_layak_status = 0; // TIDAK LAYAK (merah)
            } elseif ($other_illness_filled) {
                $is_layak_status = 1; // LAYAK (kuning)
            } else {
                $is_layak_status = 2; // SEHAT (hijau)
            }

            // Validasi sederhana + kontak unik
            $errors = [];
            if (empty($nama)) {
                $errors[] = 'Nama wajib diisi.';
            }
            if (strlen($kontak) < 6) {
                $errors[] = 'Nomor HP tidak valid. Minimal 6 digit.';
            }
            if (is_null($id_gol_darah)) {
                $errors[] = 'Golongan darah wajib dipilih.';
            }
            // Validasi nomor HP/kontak harus unik (kecuali milik sendiri)
            $stmt = $this->pendonorModel->getDbConnection()->prepare("SELECT COUNT(*) FROM pendonor WHERE kontak = ? AND id_pendonor != ? AND is_deleted = 0");
            $stmt->execute([$kontak, $id]);
            $exists = intval($stmt->fetchColumn()) > 0;
            if ($exists) {
                $errors[] = 'Nomor HP/kontak sudah terdaftar, gunakan nomor lain.';
            }
            if (!empty($errors)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors), 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=pendonor_edit&id=' . $id);
                exit;
            }

            // Siapkan data untuk update
            // Sertakan field-field screening dan other_illness juga
            $data = [
                'nama' => $nama,
                'kontak' => $kontak, // Gunakan kontak yang sudah difilter
                'riwayat_penyakit' => $riwayat_penyakit_raw, // Gunakan nilai dari JS/form
                'id_gol_darah' => (int)$id_gol_darah,
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
                // IMPORTANT: Calculate is_layak correctly: 0 = TIDAK LAYAK, 1 = LAYAK, 2 = SEHAT
                'is_layak' => $is_layak_status,
            ];
            // --- AKHIR PERUBAHAN ---

            // Panggil model untuk update
            if ($this->pendonorModel->updatePendonor($id, $data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pendonor berhasil diupdate', 'icon' => 'check-circle'];
            } else {
                // Tambahkan logging untuk debugging jika gagal
                error_log("PendonorController: Gagal update pendonor ID $id. Data: " . print_r($data, true));
                error_log("PendonorController: Data POST: " . print_r($_POST, true)); // Log POST data juga untuk debugging
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate pendonor', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=pendonor');
            exit;
        }

    // PERBAIKAN: Hapus storePendaftaran karena sudah ada di TransaksiController
    // atau tambahkan routing jika memang diperlukan

    private function view($view, $data = []) {
        extract($data);
        require_once "View/$view.php";
    }
}