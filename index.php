<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// index.php
// Production defaults: disable verbose debug output to avoid exposing details in public installs
$dev = false; // intentionally disable automatic dev-mode behaviours

// Simple Router untuk project PMI
$action = $_GET['action'] ?? 'login';

// Include Database
require_once 'Config/Database.php';

// Controllers
require_once 'Controllers/StokController.php';
require_once 'Controllers/DistribusiController.php';
require_once 'Model/StokModel.php';
require_once 'Controllers/PetugasController.php';
require_once 'Controllers/TransaksiController.php';
require_once 'Model/PetugasModel.php';

// Start session (for flash messages and controllers)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple Router
switch ($action) {
    case 'login':
        include 'View/petugas/login.php';
        break;
    case 'logout':
        session_destroy();
        header('Location: index.php?action=login');
        exit;
        break;
    case 'dashboard':
        include 'View/dashboard/index.php';
        break;
    case 'kegiatan':
        include 'View/kegiatan/index.php';
        break;
    case 'kegiatan_create':
        include 'View/kegiatan/create.php';
        break;
    case 'kegiatan_store':
        $database = new Database();
        $db = $database->getConnection();
        $nama_kegiatan = trim($_POST['nama_kegiatan']);
        $tanggal = $_POST['tanggal'];
        $lokasi = trim($_POST['lokasi']);
        $keterangan = trim($_POST['keterangan']);

        // Basic validation
        if (empty($nama_kegiatan) || empty($tanggal)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nama kegiatan dan tanggal wajib diisi.', 'icon' => 'exclamation-triangle'];
        } else {
            $stmt = $db->prepare("INSERT INTO kegiatan_donasi (nama_kegiatan, tanggal, lokasi, keterangan) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$nama_kegiatan, $tanggal, $lokasi, $keterangan])) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan berhasil disimpan.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data kegiatan.', 'icon' => 'exclamation-triangle'];
            }
        }
        header('Location: index.php?action=kegiatan');
        exit;
        break;
    case 'kegiatan_edit':
        include 'View/kegiatan/edit.php';
        break;
    case 'kegiatan_update':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_POST['id_kegiatan'];
        $nama_kegiatan = trim($_POST['nama_kegiatan']);
        $tanggal = $_POST['tanggal'];
        $lokasi = trim($_POST['lokasi']);
        $keterangan = trim($_POST['keterangan']);

        // Basic validation
        if (empty($nama_kegiatan) || empty($tanggal)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nama kegiatan dan tanggal wajib diisi.', 'icon' => 'exclamation-triangle'];
        } else {
            $stmt = $db->prepare("UPDATE kegiatan_donasi SET nama_kegiatan = ?, tanggal = ?, lokasi = ?, keterangan = ? WHERE id_kegiatan = ?");
            if ($stmt->execute([$nama_kegiatan, $tanggal, $lokasi, $keterangan, $id])) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan berhasil diperbarui.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal memperbarui data kegiatan.', 'icon' => 'exclamation-triangle'];
            }
        }
        header('Location: index.php?action=kegiatan');
        exit;
        break;
    case 'kegiatan_delete':
        // SOFT DELETE KEGIATAN
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            try {
                $db->beginTransaction();

                // 1. Hapus semua transaksi_donasi yang mereferensi kegiatan ini
                $stmtTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_kegiatan = ?");
                $stmtTrans->execute([$id]);

                // 2. Hapus kegiatan_donasi itu sendiri
                $stmt = $db->prepare("UPDATE kegiatan_donasi SET is_deleted = 1, deleted_at = NOW() WHERE id_kegiatan = ?");
                $ok = $stmt->execute([$id]);

                if ($ok && $stmt->rowCount() > 0) {
                    $db->commit();
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan dan transaksi terkait berhasil dihapus.', 'icon' => 'check-circle'];
                } else {
                    $db->rollBack();
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Kegiatan tidak ditemukan atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID kegiatan tidak valid.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: index.php?action=kegiatan');
        exit;
        break;
    case 'kegiatan_detail':
        include 'View/kegiatan/detail.php';
        break;
    case 'kegiatan_trash':
        include 'View/kegiatan/trash.php';
        break;
    case 'kegiatan_restore':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("UPDATE kegiatan_donasi SET is_deleted = 0, deleted_at = NULL WHERE id_kegiatan = ?");
            $ok = $stmt->execute([$id]);
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan berhasil dipulihkan.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data kegiatan.', 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID kegiatan tidak valid.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=kegiatan_trash');
        exit;
        break;
    case 'kegiatan_restore_all':
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("UPDATE kegiatan_donasi SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1");
        $ok = $stmt->execute();
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data kegiatan yang dihapus sudah dikembalikan.', 'icon' => 'check-circle'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore semua data kegiatan.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=kegiatan_trash');
        exit;
        break;
    case 'kegiatan_permanent_delete':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            try {
                $db->beginTransaction();
                error_log("kegiatan_permanent_delete: Memulai transaksi untuk hapus permanen kegiatan ID $id.");

                // 1. Cek apakah kegiatan benar-benar diarsipkan (is_deleted = 1)
                $stmt_check_keg = $db->prepare("SELECT COUNT(*) FROM kegiatan_donasi WHERE id_kegiatan = ? AND is_deleted = 1");
                $stmt_check_keg->execute([$id]);
                $keg_exists_and_deleted = $stmt_check_keg->fetchColumn();

                if ($keg_exists_and_deleted == 0) {
                    throw new Exception('Kegiatan tidak ditemukan di arsip atau statusnya belum diarsipkan.');
                }
                error_log("kegiatan_permanent_delete: Kegiatan ID $id ditemukan dan diarsipkan.");

                // 2. Hapus *SEMUA* transaksi_donasi yang terkait dengan id_kegiatan ini,
                //    baik yang is_deleted = 0 maupun is_deleted = 1.
                //    Ini adalah langkah kritis untuk memenuhi constraint foreign key.
                $stmt_delete_trans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_kegiatan = ?");
                $stmt_delete_trans->execute([$id]);
                $deleted_transaksi_count = $stmt_delete_trans->rowCount();
                error_log("kegiatan_permanent_delete: Menghapus permanen $deleted_transaksi_count transaksi_donasi terkait untuk id_kegiatan $id sebelum menghapus kegiatan.");

                // 3. Sekarang, setelah semua transaksi_donasi terkait (aktif dan diarsipkan) dihapus,
                //    hapus permanen kegiatan_donasi itu sendiri.
                $stmt_delete_keg = $db->prepare("DELETE FROM kegiatan_donasi WHERE id_kegiatan = ? AND is_deleted = 1");
                $stmt_delete_keg->execute([$id]);
                $deleted_keg_count = $stmt_delete_keg->rowCount();

                if ($deleted_keg_count > 0) {
                    $db->commit();
                    error_log("kegiatan_permanent_delete: Berhasil commit hapus permanen kegiatan ID $id dan $deleted_transaksi_count transaksi terkait.");
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan dan transaksi terkait berhasil dihapus permanen.', 'icon' => 'check-circle'];
                } else {
                    // Jika kegiatan tidak dihapus, mungkin karena WHERE tidak cocok (walaupun cek di awal lolos)
                    // atau query gagal. Rollback untuk amannya.
                    $db->rollback();
                    error_log("kegiatan_permanent_delete: Gagal menghapus kegiatan ID $id (mungkin WHERE tidak cocok setelah transaksi dihapus). Rollback dilakukan.");
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Kegiatan tidak ditemukan di arsip atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
                }

            } catch (PDOException $e) {
                $db->rollback();
                error_log("kegiatan_permanent_delete: PDOException saat hapus permanen kegiatan ID $id: " . $e->getMessage());
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
            } catch (Exception $e) {
                $db->rollback();
                error_log("kegiatan_permanent_delete: Exception saat hapus permanen kegiatan ID $id: " . $e->getMessage());
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen: ' . $e->getMessage(), 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID kegiatan tidak valid.', 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=kegiatan_trash');
        exit;
        break;
    case 'kegiatan_permanent_delete_all':
        $database = new Database();
        $db = $database->getConnection();
        try {
            $db->beginTransaction();

            // 1. Hapus semua transaksi_donasi yang mereferensi kegiatan di arsip
            $stmtDelTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_kegiatan IN (SELECT id_kegiatan FROM kegiatan_donasi WHERE is_deleted = 1)");
            $stmtDelTrans->execute();

            // 2. Hapus semua kegiatan di arsip
            $stmt = $db->prepare("DELETE FROM kegiatan_donasi WHERE is_deleted = 1");
            $ok = $stmt->execute();
            $deleted_count = $stmt->rowCount();

            $db->commit();
            if ($ok && $deleted_count > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Berhasil menghapus $deleted_count data kegiatan dari arsip (beserta transaksi terkait).", 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'info', 'message' => 'Tidak ada data kegiatan di arsip.', 'icon' => 'info-circle'];
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen semua data kegiatan: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=kegiatan_trash');
        exit;
        break;
    case 'pendonor':
        include 'View/pendonor/index.php';
        break;
    case 'pendonor_create':
        include 'View/pendonor/create.php';
        break;
    case 'pendonor_store':
        error_log("pendonor_store: Case dipanggil. Method: " . $_SERVER['REQUEST_METHOD']);
        // Tambahkan pengecekan metode request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("pendonor_store: Akses bukan POST, redirect.");
            // Jika bukan POST, redirect atau tampilkan error
            // Misalnya, redirect kembali ke halaman create
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Akses tidak sah.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor_create');
            exit;
        }
    case 'pendonor_store_controller': // Gunakan nama action baru dari formulir
        require_once 'Controllers/PendonorController.php'; // Sesuaikan path jika berbeda
        $controller = new PendonorController();
        $controller->store();
        // store() method sudah handle redirect dan exit
        break;

        $database = new Database();
        $db = $database->getConnection();

        // Gunakan null coalescing operator (??) untuk mencegah undefined array key
        // dan pastikan nilai default disediakan sebelum trim
        $nama = trim($_POST['nama'] ?? '');
        $tanggal_lahir = $_POST['tanggal_lahir'] ?? null; // Bisa null, jangan trim
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? null; // Bisa null
        $alamat = trim($_POST['alamat'] ?? ''); // Tetap trim, tapi bisa string kosong
        $no_hp_raw = $_POST['no_hp'] ?? ''; // Ambil nilai mentah
        $riwayat_penyakit = trim($_POST['riwayat_penyakit'] ?? '');
        $id_gol_darah = $_POST['id_gol_darah'] ?? null;
        $is_layak = isset($_POST['is_layak']) ? 1 : 0; // Checkbox

        // Proses no_hp: hanya simpan angka
        $no_hp = preg_replace('/\D+/', '', $no_hp_raw);

        error_log("pendonor_store: Data POST diterima. no_hp_raw='$no_hp_raw', no_hp='$no_hp'");

        // Validasi sederhana - hanya nama, no_hp, dan id_gol_darah yang wajib
        $errors = [];
        if (empty($nama)) {
            $errors[] = 'Nama wajib diisi.';
        }
        if (strlen($no_hp) < 6) { // Validasi panjang setelah filter
            $errors[] = 'Nomor HP tidak valid. Minimal 6 digit.';
        }
        if (is_null($id_gol_darah)) {
            $errors[] = 'Golongan darah wajib dipilih.';
        }

        if (!empty($errors)) {
            error_log("pendonor_store: Error validasi: " . implode(', ', $errors));
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors), 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor_create');
            exit;
        }

        // Lanjutkan dengan query INSERT
        // Kolom yang tidak wajib diisi (tanggal_lahir, jenis_kelamin, alamat) bisa diset ke NULL atau string kosong
        // Pastikan skema database Anda mengizinkan NULL untuk kolom-kolom ini jika Anda ingin menyimpan NULL
        // Jika skema mengharuskan string kosong, pastikan kolom tidak NOT NULL tanpa default
        $stmt = $db->prepare("INSERT INTO pendonor (nama, tanggal_lahir, jenis_kelamin, alamat, no_hp, riwayat_penyakit, id_gol_darah, is_layak, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$nama, $tanggal_lahir, $jenis_kelamin, $alamat, $no_hp, $riwayat_penyakit, $id_gol_darah, $is_layak])) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil disimpan.', 'icon' => 'check-circle'];
            error_log("pendonor_store: Data pendonor '$nama' berhasil disimpan.");
        } else {
            // Tambahkan logging untuk debugging jika gagal
            $error_info = $stmt->errorInfo();
            error_log("pendonor_store: Gagal INSERT. Error Code: " . $error_info[0] . ", Error: " . $error_info[2]);
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data pendonor.', 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=pendonor');
        exit;
        break; // Pastikan ada break
    case 'pendonor_edit':
    // Ambil ID dari parameter URL
    $id_pendonor = $_GET['id'] ?? 0;

    // Validasi ID
    if (empty($id_pendonor) || !is_numeric($id_pendonor)) {
        error_log("PendonorController: ID pendonor tidak valid atau kosong untuk edit: " . var_export($_GET['id'], true));
        // Atau set flash message dan redirect
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'icon' => 'exclamation-triangle'];
        header('Location: index.php?action=pendonor');
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ambil data pendonor
    $query = 'SELECT p.*, gd.nama_gol_darah, gd.rhesus FROM pendonor p LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah WHERE p.id_pendonor = ? AND p.is_deleted = 0 LIMIT 1'; // Tambahkan is_deleted = 0
    try {
        $stmt = $db->prepare($query);
        if (!$stmt) {
            error_log("PendonorController: Gagal menyiapkan statement untuk edit pendonor dengan ID: $id_pendonor. Error: " . print_r($db->errorInfo(), true));
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan internal saat mengambil data pendonor.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor');
            exit;
        }

        $executionResult = $stmt->execute([$id_pendonor]);
        if (!$executionResult) {
            error_log("PendonorController: Gagal mengeksekusi statement untuk edit pendonor dengan ID: $id_pendonor. Error: " . print_r($stmt->errorInfo(), true));
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan saat mengambil data pendonor.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor');
            exit;
        }

        $pendonor = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("PendonorController: Query edit untuk ID $id_pendonor dijalankan. Data ditemukan: " . ($pendonor ? 'Ya' : 'Tidak'));

    } catch (PDOException $e) {
        error_log("PendonorController: Exception saat mengambil data untuk edit pendonor dengan ID: $id_pendonor. Error: " . $e->getMessage());
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan saat mengambil data pendonor.', 'icon' => 'exclamation-triangle'];
        header('Location: index.php?action=pendonor');
        exit;
    }

    // Ambil daftar golongan darah
    try {
        $stmtG = $db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
        $stmtG->execute();
        $golongan = $stmtG->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
         error_log("PendonorController: Exception saat mengambil daftar golongan darah untuk edit pendonor dengan ID: $id_pendonor. Error: " . $e->getMessage());
         // Bisa tetap lanjutkan jika daftar golongan gagal, karena hanya mempengaruhi dropdown
         $golongan = [];
         // Atau set flash message dan redirect jika dianggap krusial
         // $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan saat mengambil daftar golongan darah.', 'icon' => 'exclamation-triangle'];
         // header('Location: index.php?action=pendonor');
         // exit;
    }

    // Pastikan $pendonor selalu didefinisikan sebelum include view
    $pendonor = $pendonor ?: null; // Jika fetch gagal karena alasan lain, pastikan nilainya null

    $golongans = $golongan; // Sesuaikan nama variabel agar cocok dengan view
    include 'View/pendonor/edit.php';
    break; // Pastikan break ada

   case 'pendonor_update':
    // Pastikan path ke Controller benar
    require_once 'Controllers/PendonorController.php'; // Sesuaikan path jika perlu
    $pc = new PendonorController();
    // Ambil ID dari URL
    $id_pendonor = $_GET['id'] ?? 0;
    // Panggil method update dari controller
    $pc->update($id_pendonor);
    break; // Pastikan break ada
    case 'pendonor_delete':
        // SOFT DELETE PENDONOR
        $database = new Database();
        $db = $database->getConnection();
        $id_pendonor = $_GET['id'] ?? 0;

        if (empty($id_pendonor) || !is_numeric($id_pendonor)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'icon' => 'exclamation-triangle'];
        } else {
            try {
                $db->beginTransaction();

                // Hapus transaksi_donasi terkait
                $stmtTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_pendonor = ?");
                $stmtTrans->execute([$id_pendonor]);

                // Hapus stok_darah terkait (via transaksi yang dihapus di atas, jika FK CASCADE)
                // Jika tidak CASCADE, hapus manual terlebih dahulu
                // $stmtStok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi IN (SELECT id_transaksi FROM transaksi_donasi WHERE id_pendonor = ?)");
                // $stmtStok->execute([$id_pendonor]);

                // Soft delete pendonor
                $stmt = $db->prepare("UPDATE pendonor SET is_deleted = 1, deleted_at = NOW() WHERE id_pendonor = ?");
                $ok = $stmt->execute([$id_pendonor]);

                if ($ok && $stmt->rowCount() > 0) {
                    $db->commit();
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor dan transaksi terkait berhasil dihapus.', 'icon' => 'check-circle'];
                } else {
                    $db->rollBack();
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pendonor tidak ditemukan atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
            }
        }
        header('Location: index.php?action=pendonor');
        exit;
        break;
    case 'pendonor_detail':
        // Lihat detail pendonor
        $database = new Database();
        $db = $database->getConnection();
        $id_pendonor = $_GET['id'] ?? 0;

        // Validasi ID
        if (empty($id_pendonor) || !is_numeric($id_pendonor)) {
            error_log("PendonorController: ID pendonor tidak valid atau kosong: " . var_export($_GET['id'], true));
            // Atau set flash message dan redirect
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor');
            exit;
        }

        $query = 'SELECT p.*, gd.nama_gol_darah, gd.rhesus FROM pendonor p LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah WHERE p.id_pendonor = ? AND p.is_deleted = 0 LIMIT 1'; // Tambahkan is_deleted = 0
        try {
            $stmt = $db->prepare($query);
            if (!$stmt) {
                error_log("PendonorController: Gagal menyiapkan statement untuk detail pendonor dengan ID: $id_pendonor. Error: " . print_r($db->errorInfo(), true));
                // Atau set flash message dan redirect
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan internal saat mengambil data pendonor.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=pendonor');
                exit;
            }

            $executionResult = $stmt->execute([$id_pendonor]);
            if (!$executionResult) {
                error_log("PendonorController: Gagal mengeksekusi statement untuk detail pendonor dengan ID: $id_pendonor. Error: " . print_r($stmt->errorInfo(), true));
                // Atau set flash message dan redirect
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan saat mengambil data pendonor.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=pendonor');
                exit;
            }

            $pendonor = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("PendonorController: Query untuk ID $id_pendonor dijalankan. Data ditemukan: " . ($pendonor ? 'Ya' : 'Tidak'));

        } catch (PDOException $e) {
            error_log("PendonorController: Exception saat mengambil detail pendonor dengan ID: $id_pendonor. Error: " . $e->getMessage());
            // Atau set flash message dan redirect
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan saat mengambil data pendonor.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=pendonor');
            exit;
        }

        // Pastikan $pendonor selalu didefinisikan sebelum include view
        $pendonor = $pendonor ?: null; // Jika fetch gagal karena alasan lain, pastikan nilainya null

        include 'View/pendonor/detail.php';
        break; // Pastikan break ada
    case 'pendonor_riwayat':
        include 'View/pendonor/riwayat.php';
        break;
    case 'pendonor_trash':
        include 'View/pendonor/trash.php';
        break;
    case 'pendonor_restore':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE id_pendonor = ?");
            $ok = $stmt->execute([$id]);
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil dipulihkan.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data pendonor.', 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=pendonor_trash');
        exit;
        break;
    case 'pendonor_restore_all':
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1");
        $ok = $stmt->execute();
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data pendonor yang dihapus sudah dikembalikan.', 'icon' => 'check-circle'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore semua data pendonor.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=pendonor_trash');
        exit;
        break;
    case 'pendonor_permanent_delete':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            try {
                $db->beginTransaction();

                // Hapus transaksi_donasi terkait
                $stmtTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_pendonor = ?");
                $stmtTrans->execute([$id]);

                // Hapus stok_darah terkait (via transaksi yang dihapus di atas, jika FK CASCADE)
                // Jika tidak CASCADE, hapus manual terlebih dahulu
                // $stmtStok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi IN (SELECT id_transaksi FROM transaksi_donasi WHERE id_pendonor = ?)");
                // $stmtStok->execute([$id]);

                // Hapus pendonor permanen
                $stmt = $db->prepare("DELETE FROM pendonor WHERE id_pendonor = ? AND is_deleted = 1");
                $ok = $stmt->execute([$id]);

                if ($ok) {
                    $db->commit();
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil dihapus permanen.', 'icon' => 'check-circle'];
                } else {
                    $db->rollBack();
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pendonor tidak ditemukan di arsip atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=pendonor_trash');
        exit;
        break;
    case 'pendonor_permanent_delete_all':
        // HAPUS PERMANEN SEMUA DATA DI TRASH
        // Otomatis menghapus transaksi dan stok terkait terlebih dahulu
        $database = new Database();
        $db = $database->getConnection();
        try {
            // Start transaction
            $db->beginTransaction();

            // 1. Hapus semua transaksi_donasi terkait pendonor di trash
            $stmtDelTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_pendonor IN (SELECT id_pendonor FROM pendonor WHERE is_deleted = 1)");
            $stmtDelTrans->execute();

            // 2. Hapus semua stok_darah terkait transaksi di atas (jika FK CASCADE tidak diatur)
            // Jika FK CASCADE diatur, langkah ini otomatis.

            // 3. Hapus semua pendonor di trash
            $stmt = $db->prepare("DELETE FROM pendonor WHERE is_deleted = 1");
            $ok = $stmt->execute();
            $deleted_count = $stmt->rowCount();

            $db->commit(); // Commit jika semua berhasil
            if ($ok && $deleted_count > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Berhasil menghapus $deleted_count data pendonor dari arsip (beserta transaksi dan stok terkait).", 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'info', 'message' => 'Tidak ada data pendonor di arsip.', 'icon' => 'info-circle'];
            }
        } catch (PDOException $e) {
            $db->rollBack(); // Rollback jika terjadi error
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen semua data pendonor: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=pendonor_trash');
        exit;
        break;
    case 'petugas':
        include 'View/petugas/index.php';
        break;
    case 'petugas_create':
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur manajemen data petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    case 'petugas_store':
        // Handle gracefully if called, but staff creation is disabled
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur menambah petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    // Perbaikan: Nonaktifkan juga petugas_edit
    case 'petugas_edit':
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur manajemen data petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    // Perbaikan: Nonaktifkan juga petugas_update
    case 'petugas_update':
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur manajemen data petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    case 'petugas_delete':
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur hapus petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    case 'petugas_update_status':
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur update status petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    case 'petugas_profile':
        $pc = new PetugasController();
        $pc->showProfile();
        break;
    case 'login':
        $pc = new PetugasController();
        $pc->login();
        break;
    case 'authenticate':
        $pc = new PetugasController();
        $pc->authenticate();
        break;
    case 'logout':
        $pc = new PetugasController();
        $pc->logout();
        break;
    case 'rumah_sakit':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        // --- Pagination & Search ---
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $items_per_page = 5; // Sesuaikan dengan kebutuhan
        $offset = ($page - 1) * $items_per_page;

        // --- Perbaikan Query ---
        // Gunakan subquery untuk menghitung jumlah distribusi per rumah sakit
        // Ini mencegah duplikasi baris rumah sakit dalam hasil SELECT
        // Tambahkan kondisi pencarian ke subquery dan query utama jika diperlukan
        $where_clause = " WHERE rs.is_deleted = 0 "; // Selalu ambil yang tidak dihapus
        $search_params = [];
        if (!empty($search)) {
            $where_clause .= " AND (rs.nama_rs LIKE ? OR rs.alamat LIKE ? OR rs.kontak LIKE ?) ";
            $search_params = ["%$search%", "%$search%", "%$search%"];
        }

        // Query untuk menghitung total (untuk pagination)
        $total_query = "SELECT COUNT(*) as total FROM rumah_sakit rs " . $where_clause;
        $total_stmt = $db->prepare($total_query);
        $total_stmt->execute($search_params);
        $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total / $items_per_page);

        // Query utama untuk mengambil data rumah sakit dengan jumlah distribusi
        $query = "SELECT rs.id_rs, rs.nama_rs, rs.alamat, rs.kontak,
                        (SELECT COUNT(*) FROM distribusi_darah dd WHERE dd.id_rs = rs.id_rs AND dd.is_deleted = 0) AS jumlah_distribusi
                FROM rumah_sakit rs
                " . $where_clause . "
                ORDER BY rs.nama_rs
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        // Bind parameter pencarian
        $param_index = 1;
        foreach ($search_params as $param) {
            $stmt->bindValue($param_index++, $param, PDO::PARAM_STR);
        }
        $stmt->execute();
        $rs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set variabel untuk view
        $page = $page; // Jika perlu diakses di view
        $total_pages = $total_pages; // Jika perlu diakses di view
        $search = $search; // Jika perlu diakses di view

        include 'View/rumah_sakit/index.php';
        break;
    case 'rumah_sakit_create':
        include 'View/rumah_sakit/create.php';
        break;
    case 'rumah_sakit_store':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $nama_rs = trim($_POST['nama_rs']);
        $alamat = trim($_POST['alamat']);
        $kontak_raw = $_POST['kontak'] ?? '';
        $kontak = preg_replace('/\D+/', '', $kontak_raw); // Hanya angka

        $errors = [];
        if (empty($nama_rs) || empty($alamat)) {
            $errors[] = 'Nama dan alamat rumah sakit wajib diisi.';
        }
        if (strlen($kontak) < 6) {
            $errors[] = 'Nomor kontak tidak valid. Minimal 6 digit.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors), 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=rumah_sakit_create'); // Asumsikan halaman create ada
            exit;
        }

        try {
            // Cek duplikat berdasarkan nama RS (dan mungkin kontak, tergantung kebijakan)
            $stmt_check = $db->prepare("SELECT COUNT(*) FROM rumah_sakit WHERE LOWER(TRIM(nama_rs)) = LOWER(TRIM(?)) AND is_deleted = 0"); // Tambahkan is_deleted=0 jika menggunakan soft delete
            $stmt_check->execute([$nama_rs]);
            if ($stmt_check->fetchColumn() > 0) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Rumah Sakit dengan nama "' . htmlspecialchars($nama_rs) . '" sudah terdaftar.', 'icon' => 'exclamation-triangle'];
            } else {
                // Gunakan QueryBuilder jika tersedia, atau query langsung
                // Karena sebelumnya menggunakan query langsung di index.php, kita tetap gunakan pendekatan langsung di sini
                $stmt_insert = $db->prepare("INSERT INTO rumah_sakit (nama_rs, alamat, kontak, created_at) VALUES (?, ?, ?, NOW())");
                if ($stmt_insert->execute([$nama_rs, $alamat, $kontak])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil disimpan.', 'icon' => 'check-circle'];
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data rumah sakit.', 'icon' => 'exclamation-triangle'];
                }
            }
        } catch (PDOException $e) {
            // Tangani error database
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data rumah sakit: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=rumah_sakit');
        exit;
        break;
    case 'rumah_sakit_edit':
        include 'View/rumah_sakit/edit.php';
        break;
    case 'rumah_sakit_update':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_POST['id_rs'];
        $nama_rs = trim($_POST['nama_rs']);
        $alamat = trim($_POST['alamat']);
        // sanitize kontak: keep digits only
        $kontak_raw = $_POST['kontak'] ?? '';
        $kontak = preg_replace('/\D+/', '', $kontak_raw);

        if (strlen($kontak) < 6) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nomor kontak tidak valid. Mohon masukkan minimal 6 digit (hanya angka).', 'icon' => 'exclamation-triangle'];
        } else {
            // CEK DUPLIKAT BERDASARKAN NAMA (kecuali untuk rs yang sedang diupdate)
            $checkDupStmt = $db->prepare("SELECT COUNT(*) as cnt FROM rumah_sakit WHERE nama_rs = ? AND id_rs != ? AND is_deleted = 0");
            $checkDupStmt->execute([$nama_rs, $id]);
            $isDuplicate = $checkDupStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;

            if ($isDuplicate) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Rumah Sakit dengan nama "' . htmlspecialchars($nama_rs) . '" sudah terdaftar.', 'icon' => 'exclamation-triangle'];
            } else {
                $stmt = $db->prepare("UPDATE rumah_sakit SET nama_rs = ?, alamat = ?, kontak = ? WHERE id_rs = ?");
                if ($stmt->execute([$nama_rs, $alamat, $kontak, $id])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil diperbarui.', 'icon' => 'check-circle'];
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal memperbarui data rumah sakit.', 'icon' => 'exclamation-triangle'];
                }
            }
        }
        header('Location: index.php?action=rumah_sakit');
        exit;
        break;
    case 'rumah_sakit_delete':
        // SOFT DELETE RUMAH SAKIT
        $database = new Database();
        $db = $database->getConnection();
        $id_rs = $_GET['id'] ?? 0;

        if (empty($id_rs) || !is_numeric($id_rs)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID rumah sakit tidak valid.', 'icon' => 'exclamation-triangle'];
        } else {
            $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 1, deleted_at = NOW() WHERE id_rs = ?");
            if ($stmt->execute([$id_rs]) && $stmt->rowCount() > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil dihapus.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Rumah sakit tidak ditemukan atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
            }
        }
        header('Location: index.php?action=rumah_sakit');
        exit;
        break;
    case 'rumah_sakit_detail':
        include 'View/rumah_sakit/detail.php';
        break;
    case 'rumah_sakit_laporan':
        include 'View/rumah_sakit/laporan.php';
        break;
    case 'rumah_sakit_trash':
        require_once __DIR__ . '/Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $check = $db->query("SHOW COLUMNS FROM rumah_sakit LIKE 'is_deleted'");
        if (!$check || $check->rowCount() === 0) {
            $deleted_rs = [];
            $softDeleteAvailable = false;
        } else {
            $softDeleteAvailable = true;
            // Query untuk menampilkan rumah sakit yang dihapus (is_deleted = 1)
            $query = "SELECT * FROM rumah_sakit WHERE is_deleted = 1 ORDER BY deleted_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $deleted_rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        include 'View/rumah_sakit/trash.php';
        break;

    case 'rumah_sakit_restore':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 0, deleted_at = NULL WHERE id_rs = ? AND is_deleted = 1"); // Tambahkan kondisi is_deleted = 1
            $ok = $stmt->execute([$id]);
            if ($ok && $stmt->rowCount() > 0) { // Cek rowCount
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rumah sakit berhasil dipulihkan dari arsip.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Rumah sakit tidak ditemukan di arsip atau gagal dipulihkan.', 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID rumah sakit tidak valid.', 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=rumah_sakit_trash');
        exit;
        break;
    case 'rumah_sakit_restore_all':
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1");
        $ok = $stmt->execute();
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data rumah sakit yang dihapus sudah dikembalikan.', 'icon' => 'check-circle'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore semua data rumah sakit.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=rumah_sakit_trash');
        exit;
        break;
    case 'rumah_sakit_permanent_delete':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            try {
                $db->beginTransaction();

                // --- PERUBAHAN UTAMA ---
                // 1. Cek apakah rumah sakit benar-benar diarsipkan (is_deleted = 1)
                $stmt_check_rs = $db->prepare("SELECT COUNT(*) FROM rumah_sakit WHERE id_rs = ? AND is_deleted = 1");
                $stmt_check_rs->execute([$id]);
                $rs_exists_and_deleted = $stmt_check_rs->fetchColumn();

                if ($rs_exists_and_deleted == 0) {
                    throw new Exception('Rumah sakit tidak ditemukan di arsip atau statusnya belum diarsipkan.');
                }

                // 2. Cek apakah tabel distribusi_darah memiliki kolom is_deleted (soft delete)
                $check_distribusi_col = $db->query("SHOW COLUMNS FROM distribusi_darah LIKE 'is_deleted'");
                $distribusi_has_soft_delete = ($check_distribusi_col && $check_distribusi_col->rowCount() > 0);

                if ($distribusi_has_soft_delete) {
                    // 3a. Jika distribusi mendukung soft delete, arsipkan semua distribusi terkait
                    $stmt_archive_dist = $db->prepare("UPDATE distribusi_darah SET is_deleted = 1, deleted_at = NOW() WHERE id_rs = ? AND is_deleted = 0");
                    $stmt_archive_dist->execute([$id]);
                    $archived_distribusi = $stmt_archive_dist->rowCount();
                    error_log("rumah_sakit_permanent_delete: Mengarsipkan $archived_distribusi distribusi terkait untuk id_rs $id sebelum menghapus rumah sakit.");
                } else {
                    // 3b. Jika distribusi TIDAK mendukung soft delete, hapus permanen semua distribusi terkait
                    // Ini adalah skenario yang kurang ideal, karena menghapus data penting secara permanen.
                    // Namun, jika struktur DB tidak bisa diubah, ini satu-satunya cara.
                    $stmt_delete_dist = $db->prepare("DELETE FROM distribusi_darah WHERE id_rs = ?");
                    $stmt_delete_dist->execute([$id]);
                    $deleted_distribusi = $stmt_delete_dist->rowCount();
                    error_log("rumah_sakit_permanent_delete: Menghapus permanen $deleted_distribusi distribusi terkait untuk id_rs $id sebelum menghapus rumah sakit.");
                }

                // 4. Sekarang, setelah semua distribusi terkait dihapus atau diarsipkan, hapus permanen rumah sakitnya
                $stmt_delete_rs = $db->prepare("DELETE FROM rumah_sakit WHERE id_rs = ? AND is_deleted = 1"); // Pastikan hanya menghapus yang sudah diarsipkan
                $ok = $stmt_delete_rs->execute([$id]);

                if ($ok && $stmt_delete_rs->rowCount() > 0) {
                    $db->commit();
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Rumah sakit dan distribusi terkait (jika ada) berhasil dihapus permanen.', 'icon' => 'check-circle'];
                } else {
                    // Jika baris tidak terhapus meski query sukses, mungkin karena WHERE tidak cocok
                    $db->rollback();
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Rumah sakit tidak ditemukan di arsip atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
                }

            } catch (PDOException $e) {
                $db->rollback();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus rumah sakit: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
            } catch (Exception $e) {
                $db->rollback();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus rumah sakit: ' . $e->getMessage(), 'icon' => 'exclamation-triangle'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID rumah sakit tidak valid.', 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=rumah_sakit_trash');
        exit;
        break;
    case 'rumah_sakit_permanent_delete_all':
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("DELETE FROM rumah_sakit WHERE is_deleted = 1");
        $ok = $stmt->execute();
        if ($ok) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data rumah sakit di arsip berhasil dihapus permanen.', 'icon' => 'check-circle'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen semua data rumah sakit.', 'icon' => 'exclamation-triangle'];
        }
        header('Location: ?action=rumah_sakit_trash');
        exit;
        break;
    case 'stok':
        $sc = new StokController();
        $sc->index();
        break;
    case 'stok_detail':
    // Include controller
    require_once 'Controllers/StokController.php';
    // Buat instance controller
    $controller = new StokController();
    // Ambil ID dari URL
    $id_stok = $_GET['id'] ?? 0;
    // Validasi ID
    if (!empty($id_stok) && is_numeric($id_stok)) {
        // Panggil method detail di controller
        $controller->detail($id_stok);
        // Penting: exit agar tidak mengeksekusi bagian lain dari index.php setelah ini
        exit;
    } else {
        // Jika ID tidak valid, redirect kembali ke daftar stok
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID Stok tidak valid.', 'icon' => 'exclamation-triangle'];
        header('Location: index.php?action=stok');
        exit;
    }
    break;
    case 'stok_create':
        $sc = new StokController();
        $sc->create();
        break;
    case 'stok_trash':
        include 'View/stok/trash.php';
        break;
    case 'distribusi':
        include 'View/distribusi/index.php';
        break;
    case 'distribusi_create':
        include 'View/distribusi/create.php';
        break;
    case 'distribusi_store':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        // Ambil dan sanitasi input
        $id_stok = $_POST['id_stok'] ?? 0;
        $id_rs = $_POST['id_rs'] ?? 0;
        // Karena distribusi satu kantong, kita tetap gunakan jumlah_kantong = 1
        // Kita tidak menyertakan jumlah_kantong dalam INSERT ke distribusi_darah
        $jumlah_kantong_yang_didistribusikan = 1; // Selalu 1 kantong per distribusi
        $tanggal_distribusi = $_POST['tanggal_distribusi'] ?? date('Y-m-d');
        $id_petugas = $_SESSION['id_petugas'] ?? null; // Pastikan id_petugas diambil dari session

        // Definisikan status default yang valid untuk kolom `status` di tabel `distribusi_darah`
        // Misalnya, ENUM('dikirim', 'diterima', 'dibatalkan')
        $status_default = 'dikirim';

        $errors = [];
        if (empty($id_stok) || !is_numeric($id_stok)) {
            $errors[] = 'ID Stok tidak valid.';
        }
        if (empty($id_rs) || !is_numeric($id_rs)) {
            $errors[] = 'ID Rumah Sakit tidak valid.';
        }
        if (empty($id_petugas) || !is_numeric($id_petugas)) {
            $errors[] = 'ID Petugas tidak valid.';
        }
        // Tidak perlu validasi jumlah_kantong_yang_didistribusikan karena selalu 1

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors), 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=distribusi_create');
            exit;
        }

        try {
            $db->beginTransaction();

            // Ambil data stok untuk verifikasi dan pengurangan
            // Kita lock baris untuk mencegah race condition
            $stmt_stok = $db->prepare("SELECT id_transaksi, jumlah_kantong AS stok_tersedia, status FROM stok_darah WHERE id_stok = ? AND is_deleted = 0 FOR UPDATE");
            $stmt_stok->execute([$id_stok]);
            $stok = $stmt_stok->fetch(PDO::FETCH_ASSOC);

            if (!$stok) {
                throw new Exception('Stok darah tidak ditemukan atau telah dihapus.');
            }

            if ($stok['status'] !== 'tersedia') {
                throw new Exception('Stok darah yang dipilih tidak tersedia untuk distribusi (mungkin sudah didistribusikan atau kadaluarsa).');
            }

            // Dalam konteks distribusi per-id_stok, jumlah_kantong_yang_didistribusikan selalu 1
            // Jadi kita hanya perlu memastikan stok tersedia untuk 1 kantong
            if ($jumlah_kantong_yang_didistribusikan > $stok['stok_tersedia']) {
                throw new Exception('Jumlah kantong yang diminta melebihi stok tersedia.');
            }

            // Insert ke tabel distribusi_darah
            // Kita tidak menyertakan 'jumlah_kantong' dalam INSERT karena kemungkinan besar kolom itu tidak ada di tabel ini.
            // Kita SEKARANG menyertakan 'jumlah_volume' karena kolom tersebut wajib diisi.
            // Status default diatur ke 'dikirim' (atau sesuaikan dengan ENUM di DB).
            $stmt_insert = $db->prepare("INSERT INTO distribusi_darah (id_stok, id_rs, id_petugas, tanggal_distribusi, status, jumlah_volume, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            // Nilai untuk jumlah_volume diset ke 1 karena satu kantong didistribusikan
            $ok_insert = $stmt_insert->execute([$id_stok, $id_rs, $id_petugas, $tanggal_distribusi, $status_default, 1]);

            if (!$ok_insert) {
                throw new Exception('Gagal menyimpan data distribusi.');
            }

            $id_distribusi_baru = $db->lastInsertId();

            // Hitung sisa stok setelah distribusi
            $sisa_stok = $stok['stok_tersedia'] - $jumlah_kantong_yang_didistribusikan;

            if ($sisa_stok <= 0) {
                // Jika stok habis, ubah status menjadi 'terpakai'
                $stmt_update_stok = $db->prepare("UPDATE stok_darah SET jumlah_kantong = 0, status = 'terpakai' WHERE id_stok = ?");
                $ok_update_stok = $stmt_update_stok->execute([$id_stok]);
            } else {
                // Jika masih ada sisa, kurangi jumlah_kantong saja
                $stmt_update_stok = $db->prepare("UPDATE stok_darah SET jumlah_kantong = jumlah_kantong - ? WHERE id_stok = ?");
                $ok_update_stok = $stmt_update_stok->execute([$jumlah_kantong_yang_didistribusikan, $id_stok]);
            }

            if (!$ok_update_stok) {
                throw new Exception('Gagal memperbarui stok darah.');
            }

            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data distribusi berhasil disimpan.', 'icon' => 'check-circle'];

        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan distribusi: ' . $e->getMessage(), 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=distribusi');
        exit;
        break;

        case 'distribusi_update':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $id_distribusi = $_GET['id'] ?? 0; // Ambil ID dari URL

        if (empty($id_distribusi) || !is_numeric($id_distribusi)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID Distribusi tidak valid.', 'icon' => 'exclamation-triangle'];
            header('Location: index.php?action=distribusi');
            exit;
        }

        // Ambil data dari formulir POST DENGAN PENANGANAN KESALAHAN
        // Gunakan null coalescing operator (??) untuk memberikan nilai default jika key tidak ada
        $id_rs_raw = $_POST['id_rs'] ?? null; // Ambil nilai mentah
        $tanggal_distribusi_raw = $_POST['tanggal_distribusi'] ?? null; // Ambil nilai mentah
        // ... Ambil field lainnya jika ada, misalnya 'jumlah_volume', 'status', dll ...
        $jumlah_volume_raw = $_POST['jumlah_volume'] ?? null; // Contoh tambahan
        $status_raw = $_POST['status'] ?? null; // Contoh tambahan

        // Proses data (contoh untuk id_rs dan tanggal)
        // Validasi dan sanitasi id_rs
        $id_rs = is_numeric($id_rs_raw) ? (int)$id_rs_raw : null;
        $tanggal_distribusi = $tanggal_distribusi_raw; // Simpan mentah, validasi nanti
        $jumlah_volume = is_numeric($jumlah_volume_raw) ? (int)$jumlah_volume_raw : 1; // Default ke 1 jika tidak valid
        $status = $status_raw; // Simpan mentah, validasi nanti

        // --- VALIDASI ---
        $errors = [];
        if (is_null($id_rs)) {
            $errors[] = 'ID Rumah Sakit tujuan wajib dipilih.';
        }
        if (empty($tanggal_distribusi)) {
            $errors[] = 'Tanggal distribusi wajib diisi.';
        }
        // Validasi format tanggal (opsional tapi disarankan)
        $date_obj = DateTime::createFromFormat('Y-m-d', $tanggal_distribusi);
        if ($tanggal_distribusi && (!$date_obj || $date_obj->format('Y-m-d') !== $tanggal_distribusi)) {
            $errors[] = 'Format tanggal distribusi tidak valid.';
        }

        // Daftar status yang diizinkan (sesuaikan dengan ENUM di database Anda)
        $allowed_statuses = ['dikirim', 'diterima', 'dibatalkan']; // Contoh
        if (!is_null($status) && !in_array($status, $allowed_statuses)) {
            $errors[] = 'Status distribusi tidak valid.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => implode('<br>', $errors), 'icon' => 'exclamation-triangle'];
            // Redirect kembali ke halaman edit dengan ID yang sama agar user bisa memperbaiki input
            header("Location: index.php?action=distribusi_edit&id=$id_distribusi");
            exit;
        }
        // --- END VALIDASI ---

        try {
            $db->beginTransaction();

            // Ambil data distribusi lama sebelum update untuk pengecekan dan sinkronisasi stok nanti (jika diperlukan)
            $stmt_get_old = $db->prepare("SELECT id_stok, id_rs, status FROM distribusi_darah WHERE id_distribusi = ? AND is_deleted = 0 LIMIT 1");
            $stmt_get_old->execute([$id_distribusi]);
            $old_distribusi = $stmt_get_old->fetch(PDO::FETCH_ASSOC);

            if (!$old_distribusi) {
                throw new Exception("Distribusi dengan ID $id_distribusi tidak ditemukan atau telah dihapus.");
            }

            $old_id_stok = $old_distribusi['id_stok'];
            $old_id_rs = $old_distribusi['id_rs'];
            $old_status = $old_distribusi['status'];

            // Siapkan data untuk update
            $data_to_update = [
                'id_rs' => $id_rs,
                'tanggal_distribusi' => $tanggal_distribusi,
                'jumlah_volume' => $jumlah_volume, // Sertakan jika kolom ada
                'status' => $status, // Sertakan jika kolom ada
                // Tambahkan field lain yang bisa diupdate sesuai kebutuhan
                // 'updated_at' => date('Y-m-d H:i:s') // Jika kolom ada dan ingin diupdate otomatis
            ];

            // Update data distribusi
            $stmt_update = $db->prepare("UPDATE distribusi_darah SET id_rs = :id_rs, tanggal_distribusi = :tanggal_distribusi, jumlah_volume = :jumlah_volume, status = :status WHERE id_distribusi = :id_distribusi AND is_deleted = 0");
            $ok_update = $stmt_update->execute([
                ':id_rs' => $id_rs,
                ':tanggal_distribusi' => $tanggal_distribusi,
                ':jumlah_volume' => $jumlah_volume,
                ':status' => $status,
                ':id_distribusi' => $id_distribusi
            ]);

            if (!$ok_update) {
                throw new Exception("Gagal mengupdate data distribusi ID $id_distribusi.");
            }

            // --- OPSIONAL: Sinkronisasi Stok Darah ---
            // Jika status distribusi berubah, Anda mungkin perlu mengupdate status stok darah di tabel `stok_darah`.
            // Logikanya tergantung pada aturan bisnis Anda.
            // Contoh: Jika status berubah dari 'dikirim' ke 'dibatalkan', kembalikan status stok ke 'tersedia'.
            // Jika status berubah dari 'dibatalkan' ke 'dikirim', ubah status stok ke 'didistribusikan' atau 'terkirim'.
            // Jika status berubah dari 'dikirim' ke 'diterima', status stok mungkin tetap 'terkirim' atau menjadi 'digunakan' tergantung kebijakan.

            $old_status_stok = null;
            $new_status_stok = null;

            if ($old_status === 'dikirim' && $status === 'dibatalkan') {
                $old_status_stok = 'didistribusikan'; // Atau status lain yang sesuai dengan 'dikirim'
                $new_status_stok = 'tersedia';
            } elseif ($old_status === 'dibatalkan' && $status === 'dikirim') {
                $old_status_stok = 'tersedia';
                $new_status_stok = 'didistribusikan'; // Atau status lain yang sesuai
            } elseif ($old_status === 'dikirim' && $status === 'diterima') {
                $old_status_stok = 'didistribusikan';
                $new_status_stok = 'diterima'; // Atau 'digunakan' tergantung kebijakan
            }
            // Tambahkan kondisi lain sesuai kebutuhan

            if ($old_status_stok && $new_status_stok) {
                $stmt_sync_stok = $db->prepare("UPDATE stok_darah SET status = ? WHERE id_stok = ? AND status = ?");
                $stmt_sync_stok->execute([$new_status_stok, $old_id_stok, $old_status_stok]);
                // Catat jumlah baris yang diupdate jika perlu untuk logging/debugging
                $rows_affected = $stmt_sync_stok->rowCount();
                error_log("SyncStokDistribusi: ID Stok $old_id_stok, dari '$old_status_stok' ke '$new_status_stok', baris terpengaruh: $rows_affected");
            }

            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data distribusi berhasil diperbarui.', 'icon' => 'check-circle'];

        } catch (PDOException $e) {
            $db->rollback();
            // Tambahkan logging untuk debugging jika gagal
            error_log("DistribusiUpdateError (ID: $id_distribusi): " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal memperbarui data distribusi: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
        } catch (Exception $e) {
            $db->rollback();
            // Tambahkan logging untuk debugging jika gagal
            error_log("DistribusiUpdateError (ID: $id_distribusi): " . $e->getMessage());
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal memperbarui data distribusi: ' . $e->getMessage(), 'icon' => 'exclamation-triangle'];
        }

        header('Location: index.php?action=distribusi');
        exit;
        break;


    case 'distribusi_delete':
        // SOFT DELETE DISTRIBUSI
        $database = new Database();
        $db = $database->getConnection();
        $id_distribusi = $_GET['id'] ?? 0;

        if (empty($id_distribusi) || !is_numeric($id_distribusi)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID distribusi tidak valid.', 'icon' => 'exclamation-triangle'];
        } else {
            $stmt = $db->prepare("UPDATE distribusi_darah SET is_deleted = 1, deleted_at = NOW() WHERE id_distribusi = ?");
            if ($stmt->execute([$id_distribusi]) && $stmt->rowCount() > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data distribusi berhasil dihapus.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Distribusi tidak ditemukan atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
            }
        }
        header('Location: index.php?action=distribusi');
        exit;
        break;
    case 'distribusi_detail':
        include 'View/distribusi/detail.php';
        break;
    case 'distribusi_trash':
        include 'View/distribusi/trash.php';
        break;
    case 'distribusi_edit':
        include 'View/distribusi/edit.php';
        break;
    case 'distribusi_restore':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("UPDATE distribusi_darah SET is_deleted = 0, deleted_at = NULL WHERE id_distribusi = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?action=distribusi_trash');
        exit;
        break;
    case 'distribusi_restore_all':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("UPDATE distribusi_darah SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1");
        $stmt->execute();
        header('Location: index.php?action=distribusi_trash');
        exit;
        break;
    case 'distribusi_permanent_delete':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;
        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("DELETE FROM distribusi_darah WHERE id_distribusi = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?action=distribusi_trash');
        exit;
        break;
    // ==================== TRANSAKSI DONASI ====================
    case 'transaksi':
        require_once 'Controllers/TransaksiController.php';
        $tc = new TransaksiController();
        $tc->index(); // Pastikan method index() dipanggil
        break;
    // Tambahkan case ini untuk menampilkan form edit
    case 'transaksi_edit':
        require_once 'Controllers/TransaksiController.php';
        $tc = new TransaksiController();
        $tc->edit($_GET['id'] ?? 0);
        break;
    // Tambahkan case ini untuk memproses update
    case 'transaksi_update':
        require_once 'Controllers/TransaksiController.php'; // Pastikan 's' ada di sini
        $tc = new TransaksiController();
        $tc->update($_GET['id'] ?? 0);
        break;
    case 'transaksi_create':
        include 'View/transaksi/create.php';
        break;
    case 'transaksi_store':
        // Gunakan controller untuk menyimpan
        require_once 'Controllers/TransaksiController.php';
        $tc = new TransaksiController();
        $tc->storeTransaksi();
        break;
    case 'transaksi_detail':
        include 'View/transaksi/detail.php';
        break;
    case 'transaksi_delete':
        // SOFT DELETE TRANSAKSI
        $database = new Database();
        $db = $database->getConnection();
        $id_transaksi = $_GET['id'] ?? 0;

        if (empty($id_transaksi) || !is_numeric($id_transaksi)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID transaksi tidak valid.', 'icon' => 'exclamation-triangle'];
        } else {
            $stmt = $db->prepare("UPDATE transaksi_donasi SET is_deleted = 1, deleted_at = NOW() WHERE id_transaksi = ?");
            if ($stmt->execute([$id_transaksi]) && $stmt->rowCount() > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data transaksi berhasil dihapus.', 'icon' => 'check-circle'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus data transaksi atau data tidak ditemukan.', 'icon' => 'exclamation-triangle'];
            }
        }
        header('Location: index.php?action=transaksi');
        exit;
        break;
    case 'transaksi_trash':
        include 'View/transaksi/trash.php';
        break;
    case 'transaksi_restore':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;

        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("UPDATE transaksi_donasi SET is_deleted = 0, deleted_at = NULL WHERE id_transaksi = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?action=transaksi_trash');
        exit;
        break;
    case 'transaksi_restore_all':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("UPDATE transaksi_donasi SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1");
        $stmt->execute();
        header('Location: index.php?action=transaksi_trash');
        exit;
        break;
    case 'transaksi_permanent_delete':
    require_once 'Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    $id = $_GET['id'] ?? 0;

    if (!empty($id) && is_numeric($id)) {
        try {
            $db->beginTransaction();
            error_log("transaksi_permanent_delete: Memulai transaksi untuk hapus permanen transaksi ID $id.");

            // 1. Cek apakah transaksi benar-benar diarsipkan (is_deleted = 1)
            $stmt_check_trans = $db->prepare("SELECT COUNT(*) FROM transaksi_donasi WHERE id_transaksi = ? AND is_deleted = 1");
            $stmt_check_trans->execute([$id]);
            $trans_exists_and_deleted = $stmt_check_trans->fetchColumn();

            if ($trans_exists_and_deleted == 0) {
                throw new Exception('Transaksi tidak ditemukan di arsip atau statusnya belum diarsipkan.');
            }
            error_log("transaksi_permanent_delete: Transaksi ID $id ditemukan dan diarsipkan.");

            // 2. Hapus semua stok_darah yang terkait dengan id_transaksi ini
            // Kita hapus semua stok yang terkait dengan transaksi ini, baik yang diarsipkan maupun tidak,
            // untuk memenuhi constraint ke tabel lain (misalnya distribusi_darah).
            $stmt_delete_stok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi = ?");
            $stmt_delete_stok->execute([$id]);
            $deleted_stok_count = $stmt_delete_stok->rowCount();
            error_log("transaksi_permanent_delete: Menghapus permanen $deleted_stok_count stok_darah terkait untuk id_transaksi $id sebelum menghapus transaksi.");

            // 3. Sekarang, setelah stok_darah terkait dihapus,
            // hapus permanen transaksi_donasi itu sendiri.
            $stmt_delete_trans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_transaksi = ? AND is_deleted = 1");
            $stmt_delete_trans->execute([$id]);
            $deleted_trans_count = $stmt_delete_trans->rowCount();

            if ($deleted_trans_count > 0) {
                 $db->commit();
                 error_log("transaksi_permanent_delete: Berhasil commit hapus permanen transaksi ID $id dan $deleted_stok_count stok terkait.");
                 $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data transaksi dan stok terkait berhasil dihapus permanen.', 'icon' => 'check-circle'];
            } else {
                 // Jika transaksi tidak dihapus, mungkin karena WHERE tidak cocok (walaupun cek di awal lolos)
                 // atau query gagal. Rollback untuk amannya.
                 $db->rollback();
                 error_log("transaksi_permanent_delete: Gagal menghapus transaksi ID $id (mungkin WHERE tidak cocok setelah stok dihapus). Rollback dilakukan.");
                 $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Transaksi tidak ditemukan di arsip atau gagal dihapus.', 'icon' => 'exclamation-triangle'];
            }

        } catch (PDOException $e) {
             $db->rollback();
             error_log("transaksi_permanent_delete: PDOException saat hapus permanen transaksi ID $id: " . $e->getMessage());
             $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
        } catch (Exception $e) {
             $db->rollback();
             error_log("transaksi_permanent_delete: Exception saat hapus permanen transaksi ID $id: " . $e->getMessage());
             $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen: ' . $e->getMessage(), 'icon' => 'exclamation-triangle'];
        }
    } else {
         $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID transaksi tidak valid.', 'icon' => 'exclamation-triangle'];
    }

    header('Location: index.php?action=transaksi_trash');
    exit;
    break;
    // ==================== END TRANSAKSI DONASI ====================
    default:
        include 'View/dashboard/index.php';
        break;
}
?>