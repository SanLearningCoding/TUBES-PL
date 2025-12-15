<?php
// Controllers/TransaksiController.php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Model/TransaksiModel.php';
require_once __DIR__ . '/../Model/KegiatanModel.php';
require_once __DIR__ . '/../Model/PendonorModel.php';
require_once __DIR__ . '/../Model/StokModel.php'; // Tambahkan ini

class TransaksiController {
    private $model;
    private $pendonorModel;
    private $kegiatanModel;
    private $stokModel; // Tambahkan properti ini

    public function __construct() {
        $this->model = new TransaksiModel();
        $this->pendonorModel = new PendonorModel();
        $this->kegiatanModel = new KegiatanModel();
        $this->stokModel = new StokModel(); // Inisialisasi

        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    // ... (kode method index(), edit() tetap sama seperti sebelumnya) ...
    public function index() {
        $database = new Database();
        $db = $database->getConnection();

        // Pagination & Search
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $items_per_page = 5; // Atau ambil dari konfigurasi
        $offset = ($page - 1) * $items_per_page;

        // Query untuk pencarian dan pagination
        $where_clause = '';
        $params = [];
        if (!empty($search)) {
            // Query kompleks untuk pencarian di beberapa kolom terkait
            // Perlu menyesuaikan dengan kolom yang ingin dicari
            $where_clause = "WHERE (p.nama LIKE :search OR kd.nama_kegiatan LIKE :search) AND td.is_deleted = 0";
            $params[':search'] = "%$search%";
        } else {
            $where_clause = "WHERE td.is_deleted = 0";
        }

        // Gunakan query dari model untuk mendapatkan data utama
        // Kita tetap perlu query manual di sini untuk pagination
        $query = "SELECT td.*, p.nama as nama_pendonor, kd.nama_kegiatan, gd.nama_gol_darah, gd.rhesus
                  FROM transaksi_donasi td
                  LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                  LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                  LEFT JOIN pendonor pend ON td.id_pendonor = pend.id_pendonor -- Untuk mengambil golongan dari pendonor
                  LEFT JOIN golongan_darah gd ON pend.id_gol_darah = gd.id_gol_darah
                  $where_clause
                  ORDER BY td.tanggal_donasi DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching transaksi: " . $e->getMessage());
            $transaksi_list = []; // Kembalikan array kosong jika error
        }


        // Hitung total untuk pagination
        $count_query = "SELECT COUNT(*) FROM transaksi_donasi td
                        LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                        LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                        $where_clause";
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        try {
            $count_stmt->execute();
            $total_items = $count_stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting transaksi: " . $e->getMessage());
            $total_items = 0; // Kembalikan 0 jika error
        }

        $total_pages = ceil($total_items / $items_per_page);

        // Siapkan data untuk view
        $data['transaksi'] = $transaksi_list;
        $data['page'] = $page;
        $data['search'] = $search;
        $data['total_items'] = $total_items;
        $data['items_per_page'] = $items_per_page;
        $data['total_pages'] = $total_pages; // Kirim total_pages juga

        // Kirim juga data untuk dropdown jika diperlukan di view edit atau create
        $data['pendonor'] = $this->pendonorModel->getAllPendonor();
        $data['kegiatan'] = $this->kegiatanModel->getAllKegiatan();

        $this->view('transaksi/index', $data);
    }

    public function update($id) {
            // Pastikan hanya menerima request POST untuk update
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                // Jika bukan POST, redirect ke daftar transaksi
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Akses tidak sah.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi');
                exit;
            }

            // Ambil data dari formulir edit (POST)
            // Sesuaikan nama field dengan yang ada di View/transaksi/edit.php
            $id_kegiatan = $_POST['id_kegiatan'] ?? null;
            $id_pendonor = $_POST['id_pendonor'] ?? null;
            $jumlah_kantong = (int)($_POST['jumlah_kantong'] ?? 0);
            $tanggal_donasi = $_POST['tanggal_donasi'] ?? null;

            // Validasi data (contoh sederhana)
            if (empty($id_kegiatan) || empty($id_pendonor) || $jumlah_kantong <= 0 || empty($tanggal_donasi)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Semua field wajib diisi dengan benar.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_edit&id=' . $id);
                exit;
            }

            // Ambil id_petugas dari session (penting untuk audit trail)
            $id_petugas_session = $_SESSION['id_petugas'] ?? null;
            if (!$id_petugas_session) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Sesi petugas tidak valid.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=login'); // Redirect ke login jika session hilang
                exit;
            }

            // Ambil data transaksi saat ini untuk mendapatkan id_petugas lama (jika ada)
            // Ini adalah perbaikan yang kita bahas sebelumnya untuk updateTransaksi
            $transaksi_sekarang = $this->model->getTransaksiById($id);
            if (!$transaksi_sekarang) {
                 $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Data transaksi tidak ditemukan.', 'icon' => 'exclamation-triangle'];
                 header('Location: index.php?action=transaksi');
                 exit;
            }

            // Siapkan data untuk update
            // Sertakan id_petugas dari session atau dari data lama jika sesuai
            $data_update = [
                'id_kegiatan' => $id_kegiatan,
                'id_pendonor' => $id_pendonor,
                'jumlah_kantong' => $jumlah_kantong,
                'tanggal_donasi' => $tanggal_donasi,
                // Penting: id_petugas harus diambil dari data lama jika tidak diupdate via form,
                // atau dari session jika memang harus pakai session.
                // Logika ini seharusnya sudah Anda terapkan di TransaksiModel::updateTransaksi sebelumnya.
                // Di sini, kita hanya mengirimkan data yang mungkin diupdate.
                // id_petugas akan ditangani di model.
            ];

            // Panggil method update dari model
            if ($this->model->updateTransaksi($id, $data_update)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data transaksi berhasil diperbarui.', 'icon' => 'check-circle'];
            } else {
                // Tambahkan logging untuk debugging jika gagal
                error_log("Gagal update transaksi ID: $id dengan data: " . print_r($data_update, true));
                error_log("Data transaksi sebelum update (ambil id_petugas): " . print_r($transaksi_sekarang, true));
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal memperbarui data transaksi.', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=transaksi');
            exit; // Pastikan exit setelah redirect
        }

    public function edit($id) {
        // Gunakan $this->model untuk mengakses TransaksiModel
        $transaksi = $this->model->getTransaksiById($id);
        if (!$transaksi) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Data transaksi tidak ditemukan.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=transaksi');
            exit;
        }

        // Ambil data untuk dropdown
        $kegiatans = $this->kegiatanModel->getAllKegiatan();
        $pendonor = $this->pendonorModel->getAllPendonor();

        $data = [
            'transaksi' => $transaksi,
            'kegiatans' => $kegiatans,
            'pendonor' => $pendonor,
        ];
        $this->view('transaksi/edit', $data);
    }

    public function storeTransaksi() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: index.php?action=transaksi_create');
                exit;
            }

            $id_pendonor = $_POST['id_pendonor'] ?? 0;
            $id_kegiatan = $_POST['id_kegiatan'] ?? null;
            $tanggal_donasi = $_POST['tanggal_donasi'] ?? null;
            $jumlah_kantong = (int)($_POST['jumlah_kantong'] ?? 0);
            // --- TAMBAHKAN: Ambil tanggal_kadaluarsa dari POST ---
            $tanggal_kadaluarsa_input = $_POST['tanggal_kadaluarsa'] ?? null;
            // --- END TAMBAHAN ---

            // Validasi sederhana
            if (empty($id_kegiatan) || empty($id_pendonor) || empty($tanggal_donasi) || $jumlah_kantong <= 0) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Semua field wajib diisi dengan benar.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }

            // Validasi format tanggal (opsional tapi disarankan)
            $date1 = DateTime::createFromFormat('Y-m-d', $tanggal_donasi);
            $date2 = $tanggal_kadaluarsa_input ? DateTime::createFromFormat('Y-m-d', $tanggal_kadaluarsa_input) : false;
            if (!$date1 || ($tanggal_kadaluarsa_input && !$date2)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Format tanggal tidak valid.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }

            // Ambil data pendonor untuk validasi status kesehatan jika perlu
            $pendonor = $this->pendonorModel->getPendonorById($id_pendonor);
            if (!$pendonor) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pendonor tidak ditemukan!', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }

            // Validasi status kesehatan (jika kolom is_layak ada)
            if (isset($pendonor['is_layak']) && $pendonor['is_layak'] != 1) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Transaksi ditolak! Pendonor dengan status kesehatan tidak layak tidak dapat melakukan transaksi donasi.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }

            // Ambil id_petugas dari session
            $id_petugas = $_SESSION['id_petugas'] ?? null; // Pastikan id_petugas diset di session saat login
            if (!$id_petugas) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Sesi petugas tidak valid. Silakan login kembali.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=login');
                exit;
            }

            $data = [
                'id_pendonor' => $id_pendonor,
                'id_kegiatan' => $id_kegiatan,
                'id_petugas' => $id_petugas, // Gunakan id_petugas dari session
                'tanggal_donasi' => $tanggal_donasi,
                'jumlah_kantong' => $jumlah_kantong,
            ];

            if ($this->model->createTransaksi($data)) {
                // --- PERBAIKAN: Gunakan metode getter untuk mengakses koneksi ---
                // Akses koneksi dari TransaksiModel melalui metode publik/getter
                $id_transaksi_baru = $this->model->getDbConnection()->lastInsertId();
                // --- END PERBAIKAN ---

                // --- PERBAIKAN: Kirim tanggal_kadaluarsa_input ke generateStokFromTransaksi ---
                $this->stokModel->generateStokFromTransaksi($id_transaksi_baru, $tanggal_kadaluarsa_input); // Kirim tanggal_kadaluarsa
                // --- END PERBAIKAN ---

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data transaksi dan stok darah berhasil disimpan.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data transaksi.', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=transaksi');
            exit;
        }

    private function view($view, $data = []) {
        extract($data);
        // PERBAIKAN: Pastikan path view konsisten dengan struktur direktori
        // Karena file controller berada di dalam direktori Controllers/,
        // maka path ke View harus relatif dari sana.
        require_once __DIR__ . "/../View/$view.php";
    }
}
?>