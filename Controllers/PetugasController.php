<?php

/**
 * Controllers/PetugasController.php
 * 
 * Controller untuk menghandle semua logika terkait Petugas (authentication, profile management)
 * Menangani: login, logout, create/update profil, soft delete
 * 
 * CATATAN PENTING:
 * - Setiap method public wajib mengecek authentication dengan checkAuth()
 * - Password harus selalu di-hash dengan PASSWORD_BCRYPT
 * - Email harus unique di database
 * - Staff management (create/delete) saat ini di-disable, redirect ke dashboard
 */

require_once 'Config/Database.php';
require_once 'Model/PetugasModel.php';

class PetugasController {
    private $petugasModel;

    public function __construct() {
        $this->petugasModel = new PetugasModel();
        // Pastikan session sudah dimulai untuk semua action
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    /**
     * index()
     * Menampilkan daftar semua petugas (untuk admin only, tapi saat ini disabled)
     */
    public function index() {
        $this->checkAuth();
        $data['petugas'] = $this->petugasModel->getAllPetugas();
        $this->view('index', $data);
    }

    /**
     * create()
     * Menampilkan form untuk membuat petugas baru (disabled saat ini)
     */
    public function create() {
        $this->checkAuth();
        $this->view('create');
    }

    /**
     * store()
     * Menyimpan data petugas baru ke database
     * CATATAN: Feature ini saat ini disabled, redirect ke dashboard
     */
    public function store() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Prepare data untuk insert
            $data = [
                'nama_petugas' => $_POST['nama_petugas'],
                'email' => $_POST['email'],
                // PENTING: Password harus di-hash, jangan pernah simpan plain text
                'password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                'kontak' => $_POST['kontak'] ?? '',
                'is_deleted' => 0
            ];

            if ($this->petugasModel->insertPetugas($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Petugas berhasil ditambahkan', 'icon' => 'check-circle'];
                // staff management is disabled; redirect to dashboard
                header('Location: index.php?action=dashboard');
                exit;
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan petugas', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=dashboard');
                exit;
            }
        }
    }

    /**
     * edit($id_petugas)
     * Menampilkan form edit profil petugas
     * CATATAN: Hanya bisa edit profil sendiri (override parameter dengan session)
     */
    public function edit($id_petugas) {
        $this->checkAuth();
        // Hanya izinkan edit profil sendiri, tidak bisa edit petugas lain
        $id_petugas = $_SESSION['id_petugas'];
        $data['petugas'] = $this->petugasModel->getPetugasById($id_petugas);
            if (!$data['petugas']) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Petugas tidak ditemukan', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=dashboard');
            exit;
        }
        $this->view('edit', $data);
    }

    /**
     * update($id_petugas)
     * Mengupdate data profil petugas yang sudah login
     * Bisa update: nama, email, kontak, dan password (opsional)
     * 
     * Validasi:
     * - Email harus unique (tidak boleh ada petugas lain dengan email sama)
     * - Password di-hash jika tidak kosong
     */
    public function update($id_petugas) {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Hanya izinkan update profil sendiri
            $id_petugas = $_SESSION['id_petugas'];
            
            // Siapkan data update (hanya field yang bisa diubah user)
            $data = [
                'nama_petugas' => trim($_POST['nama_petugas'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'kontak' => trim($_POST['kontak'] ?? '')
            ];

            // Update password jika ada input password baru
            if (!empty($_POST['password'])) {
                $data['password_hash'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            }

            // Validasi: Email harus unique (cegah update ke email yang sudah ada)
            $existing = $this->petugasModel->getPetugasByEmail($data['email']);
            if ($existing && intval($existing['id_petugas']) !== intval($id_petugas)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email sudah digunakan oleh akun lain', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=petugas_edit&id=' . $id_petugas);
                exit;
            }

            // Lakukan update
            if ($this->petugasModel->updatePetugas($id_petugas, $data)) {
                // Update session agar nama/email di navbar tetap terbaru
                if (isset($data['nama_petugas'])) $_SESSION['nama_petugas'] = $data['nama_petugas'];
                if (isset($data['email'])) $_SESSION['email'] = $data['email'];
                if (isset($data['kontak'])) $_SESSION['kontak'] = $data['kontak'];
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profil berhasil diupdate', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate petugas', 'icon' => 'exclamation-triangle'];
            }
            header('Location: index.php?action=petugas_profile');
            exit;
        }
    }

    /**
     * delete($id_petugas)
     * Melakukan soft delete pada petugas (ubah status jadi nonaktif)
     * Data tidak dihapus, hanya statusnya yang berubah
     */
    public function delete($id_petugas) {
        $this->checkAuth();
        if ($this->petugasModel->SoftDeletePetugas($id_petugas)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Petugas berhasil dinonaktifkan', 'icon' => 'trash'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menonaktifkan petugas', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=dashboard');
        exit;
    }

    /**
     * updateStatus($id_petugas)
     * Mengaktifkan kembali petugas yang sebelumnya dinonaktifkan (restore)
     */
    public function updateStatus($id_petugas) {
        $this->checkAuth();
        if ($this->petugasModel->restorePetugas($id_petugas)) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status petugas berhasil diaktifkan', 'icon' => 'check-circle'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengaktifkan petugas', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=dashboard');
        exit;
    }

    /**
     * login()
     * Menampilkan form login
     * Jika sudah login, redirect ke dashboard
     */
    public function login() {
        // Jika sudah login, redirect ke dashboard (hindari double login)
        if (isset($_SESSION['isLoggedIn'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $this->view('login');
    }

    /**
     * authenticate()
     * Memproses login: validasi email & password
     * 
     * Alur:
     * 1. Validasi input email & password
     * 2. Query database cari email yang sesuai
     * 3. Verify password dengan password_verify()
     * 4. Cek apakah akun sudah di-soft delete
     * 5. Set session jika login berhasil
     * 
     * PENTING:
     * - Jangan pernah compare password plain text (gunakan password_verify)
     * - Cek is_deleted untuk memastikan akun tidak di-nonaktifkan
     */
    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Get database connection
            $database = new Database();
            $db = $database->getConnection();

            // Query untuk cari petugas berdasarkan email
            $stmt = $db->prepare("SELECT id_petugas, nama_petugas, email, password_hash, is_deleted FROM petugas WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $petugas = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($petugas) {
                // Validasi 1: Cek apakah akun sudah di-soft delete
                if ($petugas['is_deleted'] == 1) {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Akun dinonaktifkan', 'icon' => 'exclamation-triangle'];
                    header('Location: index.php?action=login');
                    exit;
                }

                // Validasi 2: Verify password dengan hash yang tersimpan
                if (password_verify($password, $petugas['password_hash'])) {
                    // Login berhasil: set session
                    $_SESSION['id_petugas'] = $petugas['id_petugas'];
                    $_SESSION['nama_petugas'] = $petugas['nama_petugas'];
                    $_SESSION['email'] = $petugas['email'];
                    $_SESSION['isLoggedIn'] = true;

                    header('Location: index.php?action=dashboard');
                    exit;
                } else {
                    // Password salah
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email atau password salah', 'icon' => 'exclamation-triangle'];
                    header('Location: index.php?action=login');
                    exit;
                }
            } else {
                // Email tidak ditemukan
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email tidak ditemukan', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=login');
                exit;
            }
        }
    
        // Jika bukan POST request, redirect ke login
        header('Location: index.php?action=login');
        exit;
    }

    /**
     * logout()
     * Menghapus session dan redirect ke login
     */
    public function logout() {
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    }

    /**
     * showProfile()
     * Menampilkan halaman profile/dashboard petugas yang login
     */
    public function showProfile() {
        $this->checkAuth();
        $data['petugas'] = $this->petugasModel->getPetugasById($_SESSION['id_petugas']);
        $this->view('profile', $data);
    }

    /**
     * checkAuth()
     * Helper method: memastikan user sudah login
     * Jika belum login, redirect ke halaman login
     * 
     * Gunakan di awal method yang memerlukan authentication
     */
    private function checkAuth() {
        if (!isset($_SESSION['isLoggedIn'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anda harus login terlebih dahulu', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=login');
            exit;
        }
    }

    /**
     * view($view, $data)
     * Helper method: include view file dengan safe variable extraction
     * 
     * @param string $view Nama view (tanpa .php)
     * @param array $data Array untuk di-extract sebagai variable
     */
    private function view($view, $data = []) {
        extract($data);
        
        // Cek beberapa kemungkinan path view
        $possiblePaths = [
            "View/petugas/$view.php"
        ];
        
        foreach ($possiblePaths as $viewPath) {
            if (file_exists($viewPath)) {
                require_once $viewPath;
                return;
            }
        }
        
        // Jika view tidak ditemukan, show error
        die("View tidak ditemukan: $view. Path yang dicari: " . implode(', ', $possiblePaths));
    }
}