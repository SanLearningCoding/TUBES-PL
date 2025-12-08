<?php

// Controllers/PetugasController.php

require_once 'Config/Database.php';
require_once 'Model/PetugasModel.php';

class PetugasController {
    private $petugasModel;

    public function __construct() {
        $this->petugasModel = new PetugasModel();
        if (session_status() == PHP_SESSION_NONE) session_start();
    }

    public function index() {
        $this->checkAuth();
        $data['petugas'] = $this->petugasModel->getAllPetugas();
        $this->view('index', $data);
    }

    public function create() {
        $this->checkAuth();
        $this->view('create');
    }

    public function store() {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama_petugas' => $_POST['nama_petugas'],
                'email' => $_POST['email'],
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

    public function edit($id_petugas) {
        $this->checkAuth();
        // Only allow editing of the current logged in user's profile
        $id_petugas = $_SESSION['id_petugas'];
        $data['petugas'] = $this->petugasModel->getPetugasById($id_petugas);
            if (!$data['petugas']) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Petugas tidak ditemukan', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=dashboard');
            exit;
        }
        $this->view('edit', $data);
    }

    public function update($id_petugas) {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Restrict updates to current logged-in user only
            $id_petugas = $_SESSION['id_petugas'];
            $data = [
                'nama_petugas' => trim($_POST['nama_petugas'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'kontak' => trim($_POST['kontak'] ?? '')
            ];

            if (!empty($_POST['password'])) {
                $data['password_hash'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            }

            // Validate unique email (prevent updating email to one used by another account)
            $existing = $this->petugasModel->getPetugasByEmail($data['email']);
            if ($existing && intval($existing['id_petugas']) !== intval($id_petugas)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email sudah digunakan oleh akun lain', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=petugas_edit&id=' . $id_petugas);
                exit;
            }

            if ($this->petugasModel->updatePetugas($id_petugas, $data)) {
                // Update session values so displayed name/email/kontak remain current
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

    public function login() {
        // Jika sudah login, redirect ke dashboard
        if (isset($_SESSION['isLoggedIn'])) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $this->view('login');
    }

    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Get database connection
            $database = new Database();
            $db = $database->getConnection();

            // Direct SQL query instead of using QueryBuilder
            $stmt = $db->prepare("SELECT id_petugas, nama_petugas, email, password_hash, is_deleted FROM petugas WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $petugas = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($petugas) {
                // Check if account is soft-deleted
                if ($petugas['is_deleted'] == 1) {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Akun dinonaktifkan', 'icon' => 'exclamation-triangle'];
                    header('Location: index.php?action=login');
                    exit;
                }

                // Verify password
                if (password_verify($password, $petugas['password_hash'])) {
                    $_SESSION['id_petugas'] = $petugas['id_petugas'];
                    $_SESSION['nama_petugas'] = $petugas['nama_petugas'];
                    $_SESSION['email'] = $petugas['email'];
                    $_SESSION['isLoggedIn'] = true;

                    header('Location: index.php?action=dashboard');
                    exit;
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email atau password salah', 'icon' => 'exclamation-triangle'];
                    header('Location: index.php?action=login');
                    exit;
                }
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Email tidak ditemukan', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=login');
                exit;
            }
        }
    
        // Jika bukan POST request, redirect ke login
        header('Location: index.php?action=login');
        exit;
    }

    public function logout() {
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    }

    public function showProfile() {
        $this->checkAuth();
        $data['petugas'] = $this->petugasModel->getPetugasById($_SESSION['id_petugas']);
        $this->view('profile', $data);
    }

    // Method helper untuk check authentication
    private function checkAuth() {
        if (!isset($_SESSION['isLoggedIn'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Anda harus login terlebih dahulu', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=login');
            exit;
        }
    }

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
        
        // Jika view tidak ditemukan
        die("View tidak ditemukan: $view. Path yang dicari: " . implode(', ', $possiblePaths));
    }
}