<?php

// Controllers/PetugasController.php

require_once 'Config/Database.php';
require_once 'Model/PetugasModel.php';

class PetugasController {
    private $petugasModel;

    public function __construct() {
        $this->petugasModel = new PetugasModel();
        session_start();
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
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'no_telepon' => $_POST['no_telepon'],
                'status' => 'aktif'
            ];

            if ($this->petugasModel->insertPetugas($data)) {
                $_SESSION['success'] = 'Petugas berhasil ditambahkan';
                header('Location: index.php?action=petugas');
                exit;
            } else {
                $_SESSION['error'] = 'Gagal menambahkan petugas';
                header('Location: index.php?action=petugas&method=create');
                exit;
            }
        }
    }

    public function edit($id_petugas) {
        $this->checkAuth();
        $data['petugas'] = $this->petugasModel->getPetugasById($id_petugas);
        if (!$data['petugas']) {
            $_SESSION['error'] = 'Petugas tidak ditemukan';
            header('Location: index.php?action=petugas');
            exit;
        }
        $this->view('edit', $data);
    }

    public function update($id_petugas) {
        $this->checkAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'nama_petugas' => $_POST['nama_petugas'],
                'email' => $_POST['email'],
                'no_telepon' => $_POST['no_telepon']
            ];

            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            if ($this->petugasModel->updatePetugas($id_petugas, $data)) {
                $_SESSION['success'] = 'Petugas berhasil diupdate';
            } else {
                $_SESSION['error'] = 'Gagal mengupdate petugas';
            }
            header('Location: index.php?action=petugas');
            exit;
        }
    }

    public function delete($id_petugas) {
        $this->checkAuth();
        if ($this->petugasModel->SoftDeletePetugas($id_petugas)) {
            $_SESSION['success'] = 'Petugas berhasil dinonaktifkan';
        } else {
            $_SESSION['error'] = 'Gagal menonaktifkan petugas';
        }
        header('Location: index.php?action=petugas');
        exit;
    }

    public function updateStatus($id_petugas) {
        $this->checkAuth();
        if ($this->petugasModel->restorePetugas($id_petugas)) {
            $_SESSION['success'] = 'Status petugas berhasil diaktifkan';
        } else {
            $_SESSION['error'] = 'Gagal mengaktifkan petugas';
        }
        header('Location: index.php?action=petugas');
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
            $email = $_POST['email'];
            $password = $_POST['password'];

            $petugas = $this->petugasModel->getPetugasByEmail($email);

            if ($petugas) {
                if (password_verify($password, $petugas['password'])) {
                    if ($petugas['status'] == 'nonaktif') {
                        $_SESSION['error'] = 'Akun dinonaktifkan';
                        header('Location: index.php?action=login');
                        exit;
                    }

                    $_SESSION['id_petugas'] = $petugas['id_petugas'];
                    $_SESSION['nama_petugas'] = $petugas['nama_petugas'];
                    $_SESSION['email'] = $petugas['email'];
                    $_SESSION['isLoggedIn'] = true;

                    $_SESSION['success'] = 'Login berhasil';
                    header('Location: index.php?action=dashboard');
                    exit;
                } else {
                    $_SESSION['error'] = 'Email atau password salah';
                    header('Location: index.php?action=login');
                    exit;
                }
            } else {
                $_SESSION['error'] = 'Email tidak ditemukan';
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
            $_SESSION['error'] = 'Anda harus login terlebih dahulu';
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