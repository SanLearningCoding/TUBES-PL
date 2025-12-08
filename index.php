<?php
// Production defaults: disable verbose debug output to avoid exposing details in public installs
$dev = false; // intentionally disable automatic dev-mode behaviours
// Simple Router untuk project PMI
$action = $_GET['action'] ?? 'dashboard';

// Include Database
require_once 'Config/Database.php';
// Controllers
require_once 'Controllers/StokController.php';
require_once 'Controllers/DistribusiController.php';
require_once 'Model/StokModel.php';
require_once 'Controllers/PetugasController.php';
require_once 'Model/PetugasModel.php';
// Start session (for flash messages and controllers)
if (session_status() == PHP_SESSION_NONE) session_start();

// If user is not logged in, force public accessibility only to login/authenticate
 $publicActions = ['login', 'authenticate'];
if (!isset($_SESSION['isLoggedIn']) || !$_SESSION['isLoggedIn']) {
    if (!in_array($action, $publicActions)) {
        $action = 'login';
    }
}

// Auto-update expired stock statuses globally so views display consistent data
try {
    $sm = new StokModel();
    $sm->updateExpiredStatuses();
} catch (Exception $_) {
    // ignore any errors here
}

// NOTE: `setup_admin` and automatic default admin creation have been removed by request.

switch ($action) {
        case 'stok_store':
            $sc = new StokController();
            $sc->store();
            break;
        case 'stok_delete':
            $sc = new StokController();
            $sc->delete($_GET['id'] ?? 0);
            break;
        case 'stok_update':
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $sc = new StokController();
                // id sent via hidden form field
                $id = $_POST['id_stok'] ?? 0;
                $sc->update($id);
            }
            break;
    case 'dashboard':
        // Use controller to show dashboard so we can run pre-checks (e.g., auto-expire stocks)
        $sc = new StokController();
        $sc->showDashboard();
        break;
        
    case 'pendonor':
        include 'View/pendonor/index.php';
        break;
    case 'pendonor_create':
        include 'View/pendonor/create.php';
        break;
    case 'pendonor_store':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            $nama = trim($_POST['nama']);
            $kontak = $_POST['kontak'];
            // NOTE: riwayat_penyakit akan dibangun dari flags + other_illness, bukan dari POST
            $id_gol_darah = $_POST['id_gol_darah'] ?? null;
            // Check for duplicate active pendonor name. If an active pendonor with same name exists, block create.
            $checkCol = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_deleted'");
            $hasIsDeleted = $checkCol && $checkCol->rowCount() > 0;
            
            // First check: active pendonor with same name
            if ($hasIsDeleted) {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND is_deleted = 0");
            } else {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?))");
            }
            $stmtCheck->execute([$nama]);
            $exists_active = intval($stmtCheck->fetchColumn()) > 0;
            
            // Second check: deleted pendonor with same name (for better error message)
            $exists_deleted = false;
            if ($hasIsDeleted) {
                $stmtCheckDeleted = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND is_deleted = 1");
                $stmtCheckDeleted->execute([$nama]);
                $exists_deleted = intval($stmtCheckDeleted->fetchColumn()) > 0;
            }
            
            if ($exists_active) {
                // Pendonor aktif dengan nama sama sudah ada
                $old_nama = $nama;
                $old_kontak = $kontak;
                $old_riwayat_penyakit = $riwayat_penyakit;
                $old_id_gol = $id_gol_darah;
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan pendonor: nama "' . htmlspecialchars($nama) . '" sudah ada pada daftar aktif. Silakan gunakan nama lain.', 'icon' => 'exclamation-triangle'];
                include 'View/pendonor/create.php';
                exit;
            } else {
                // Pendonor dengan nama unik, lanjut ke proses insert
                // Determine screening flags from POST
                $flags = [
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
                ];
                $other_illness = trim($_POST['other_illness'] ?? '');
                
                // PENTING: Status kesehatan ditentukan oleh:
                // - is_layak = 0: TIDAK LAYAK (jika ada salah satu dari 9 penyakit screening)
                // - is_layak = 1: LAYAK (jika tidak ada dari 9 penyakit, tapi ada other_illness)
                // - is_layak = 2: SEHAT (tidak ada penyakit apapun)
                
                $has_screening_disease = false;
                foreach ($flags as $f) {
                    if ($f) {
                        $has_screening_disease = true;
                        break;
                    }
                }
                
                // Tentukan is_layak berdasarkan screening diseases dan other_illness
                if ($has_screening_disease) {
                    $is_layak = 0; // Ada salah satu dari 9 penyakit screening = TIDAK LAYAK
                } elseif (!empty($other_illness)) {
                    $is_layak = 1; // Hanya ada other_illness = LAYAK
                } else {
                    $is_layak = 2; // Tidak ada penyakit apapun = SEHAT
                }

                // Build riwayat_penyakit: gabungkan 9 penyakit + other_illness
                $riwayat_items = [];
                $disease_names = [
                    'has_hepatitis_b' => 'Hepatitis B',
                    'has_hepatitis_c' => 'Hepatitis C',
                    'has_aids' => 'AIDS / HIV',
                    'has_hemofilia' => 'Hemofilia',
                    'has_sickle_cell' => 'Penyakit Sel Sabit',
                    'has_thalassemia' => 'Thalasemia',
                    'has_leukemia' => 'Leukemia',
                    'has_lymphoma' => 'Limfoma',
                    'has_myeloma' => 'Myeloma',
                    'has_cjd' => 'CJD'
                ];
                foreach ($flags as $key => $value) {
                    if ($value && isset($disease_names[$key])) {
                        $riwayat_items[] = $disease_names[$key];
                    }
                }
                if (!empty($other_illness)) {
                    $riwayat_items[] = $other_illness;
                }
                $riwayat_penyakit = implode(', ', $riwayat_items);

                // Check whether new screening columns exist in DB
                $colCheck = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor'");
                $colCheck->execute();
                $cols = array_map(function($r){return $r['column_name'];}, $colCheck->fetchAll(PDO::FETCH_ASSOC));

                // Build insert accordingly
                $baseCols = ['nama','kontak','riwayat_penyakit','id_gol_darah'];
                $placeholders = ['?','?','?','?'];
                $values = [$nama, $kontak, $riwayat_penyakit, $id_gol_darah];

                foreach (array_keys($flags) as $k) {
                    if (in_array($k, $cols)) {
                        $baseCols[] = $k;
                        $placeholders[] = '?';
                        $values[] = $flags[$k];
                    }
                }
                if (in_array('other_illness', $cols)) { $baseCols[] = 'other_illness'; $placeholders[]='?'; $values[] = $other_illness; }
                if (in_array('is_layak', $cols)) { $baseCols[] = 'is_layak'; $placeholders[]='?'; $values[] = $is_layak; }
                if (in_array('is_deleted', $cols)) { $baseCols[] = 'is_deleted'; $placeholders[]='?'; $values[] = 0; }

                $query = "INSERT INTO pendonor (" . implode(',', $baseCols) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $db->prepare($query);
                
                try {
                    if ($stmt->execute($values)) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil disimpan!', 'icon' => 'check-circle'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data pendonor!', 'icon' => 'exclamation-triangle'];
                    }
                } catch (PDOException $e) {
                    // Handle duplicate entry error
                    if ($e->getCode() == '23000') {
                        // Duplicate entry error - nama sudah ada meski sudah di-delete
                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menambahkan pendonor: nama "' . htmlspecialchars($nama) . '" sudah pernah terdaftar. Silakan gunakan nama lain atau hubungi administrator.', 'icon' => 'exclamation-triangle'];
                        // Stay on create page with old values
                        $old_nama = $nama;
                        $old_kontak = $kontak;
                        $old_riwayat_penyakit = $riwayat_penyakit;
                        $old_id_gol = $id_gol_darah;
                        include 'View/pendonor/create.php';
                        exit;
                    } else {
                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data pendonor: ' . htmlspecialchars($e->getMessage()), 'icon' => 'exclamation-triangle'];
                    }
                }
            }
            header('Location: index.php?action=pendonor');
            exit;
        }
        break;
    case 'pendonor_detail':
        // Lihat detail pendonor
        $database = new Database();
        $db = $database->getConnection();
        $id_pendonor = $_GET['id'] ?? 0;
        
        $stmt = $db->prepare('SELECT p.*, gd.nama_gol_darah, gd.rhesus FROM pendonor p LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah WHERE p.id_pendonor = ?');
        $stmt->execute([$id_pendonor]);
        $pendonor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        include 'View/pendonor/detail.php';
        break;

    case 'pendonor_edit':
        // Prepare pendonor data for edit view so the view has $pendonor and $golongan
        $database = new Database();
        $db = $database->getConnection();
        $id_pendonor = $_GET['id'] ?? 0;
        $stmt = $db->prepare('SELECT p.*, gd.nama_gol_darah, gd.rhesus FROM pendonor p LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah WHERE p.id_pendonor = ?');
        $stmt->execute([$id_pendonor]);
        $pendonor = $stmt->fetch(PDO::FETCH_ASSOC);
        // Fetch golongan list for dropdown
        $stmtG = $db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
        $stmtG->execute();
        $golongan = $stmtG->fetchAll(PDO::FETCH_ASSOC);
        include 'View/pendonor/edit.php';
        break;
    case 'pendonor_trash':
        include 'View/pendonor/trash.php';
        break;
        
    
        $stmtG->execute();
        $golongan = $stmtG->fetchAll(PDO::FETCH_ASSOC);
        include 'View/pendonor/edit.php';
        break;
        
    case 'pendonor_update':
        // UPDATE DATA KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $id_pendonor = $_GET['id'] ?? 0;
            if (empty($id_pendonor) || !is_numeric($id_pendonor)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid', 'title' => 'Error'];
                header('Location: ?action=pendonor');
                exit;
            }
            $nama = trim($_POST['nama']);
            $kontak = $_POST['kontak'];
            $id_gol_darah = $_POST['id_gol_darah'] ?? null;
            $other_illness = trim($_POST['other_illness'] ?? '');
            
            // Prevent changing name to a duplicate of another active pendonor
            $checkCol = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_deleted'");
            $hasIsDeleted = $checkCol && $checkCol->rowCount() > 0;
            if ($hasIsDeleted) {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND is_deleted = 0 AND id_pendonor != ?");
                $stmtCheck->execute([$nama, $id_pendonor]);
            } else {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND id_pendonor != ?");
                $stmtCheck->execute([$nama, $id_pendonor]);
            }
            $exists = intval($stmtCheck->fetchColumn()) > 0;
            if ($exists) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nama pendonor sudah terdaftar di daftar aktif!', 'title' => 'Duplikat Data'];
                header('Location: ?action=pendonor');
                exit;
            }
            
            // Build screening columns for update
            $screening_cols = [
                'has_hepatitis_b', 'has_hepatitis_c', 'has_aids', 'has_hemofilia',
                'has_sickle_cell', 'has_thalassemia', 'has_leukemia', 'has_lymphoma',
                'has_myeloma', 'has_cjd'
            ];
            
            // Check which columns exist
            $existing_cols = [];
            $checkSql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor'";
            $stmtCheckCols = $db->prepare($checkSql);
            $stmtCheckCols->execute();
            foreach ($stmtCheckCols->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $existing_cols[] = $row['COLUMN_NAME'];
            }
            
            // Build flags dari POST
            $flags = [];
            foreach ($screening_cols as $col) {
                $flags[$col] = isset($_POST[$col]) && $_POST[$col] == '1' ? 1 : 0;
            }
            
            // PENTING: Status kesehatan ditentukan oleh:
            // - is_layak = 0: TIDAK LAYAK (jika ada salah satu dari 9 penyakit screening)
            // - is_layak = 1: LAYAK (jika tidak ada dari 9 penyakit, tapi ada other_illness)
            // - is_layak = 2: SEHAT (tidak ada penyakit apapun)
            
            $has_screening_disease = false;
            foreach ($flags as $f) {
                if ($f) {
                    $has_screening_disease = true;
                    break;
                }
            }
            
            // Tentukan is_layak berdasarkan screening diseases dan other_illness
            if ($has_screening_disease) {
                $is_layak = 0; // Ada salah satu dari 9 penyakit screening = TIDAK LAYAK
            } elseif (!empty($other_illness)) {
                $is_layak = 1; // Hanya ada other_illness = LAYAK
            } else {
                $is_layak = 2; // Tidak ada penyakit apapun = SEHAT
            }
            
            // Build riwayat_penyakit: gabungkan 9 penyakit + other_illness
            $riwayat_items = [];
            $disease_names = [
                'has_hepatitis_b' => 'Hepatitis B',
                'has_hepatitis_c' => 'Hepatitis C',
                'has_aids' => 'AIDS / HIV',
                'has_hemofilia' => 'Hemofilia',
                'has_sickle_cell' => 'Penyakit Sel Sabit',
                'has_thalassemia' => 'Thalasemia',
                'has_leukemia' => 'Leukemia',
                'has_lymphoma' => 'Limfoma',
                'has_myeloma' => 'Myeloma',
                'has_cjd' => 'CJD'
            ];
            foreach ($flags as $key => $value) {
                if ($value && isset($disease_names[$key])) {
                    $riwayat_items[] = $disease_names[$key];
                }
            }
            if (!empty($other_illness)) {
                $riwayat_items[] = $other_illness;
            }
            $riwayat_penyakit = implode(', ', $riwayat_items);
            
            // Build update query dynamically
            $update_fields = ['nama = ?', 'kontak = ?', 'riwayat_penyakit = ?', 'id_gol_darah = ?', 'other_illness = ?'];
            $update_values = [$nama, $kontak, $riwayat_penyakit, $id_gol_darah, $other_illness];
            
            // Add screening columns
            foreach ($screening_cols as $col) {
                if (in_array($col, $existing_cols)) {
                    $update_fields[] = "$col = ?";
                    $update_values[] = $flags[$col];
                }
            }
            
            // Add is_layak if column exists
            if (in_array('is_layak', $existing_cols)) {
                $update_fields[] = "is_layak = ?";
                $update_values[] = $is_layak;
            }
            
            $update_values[] = $id_pendonor;
            $query = "UPDATE pendonor SET " . implode(', ', $update_fields) . " WHERE id_pendonor = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute($update_values)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil diupdate!', 'title' => 'Berhasil Diperbarui'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate data pendonor!', 'title' => 'Gagal Diperbarui'];
            }
            
            header('Location: ?action=pendonor');
            exit;
        }
        break;
        
       case 'pendonor_soft_delete':
        // SOFT DELETE: tandai sebagai dihapus, tapi tidak benar-benar dihapus dari tabel
        $database = new Database();
        $db = $database->getConnection();

        $id_pendonor = $_GET['id'] ?? 0;

        if (empty($id_pendonor) || !is_numeric($id_pendonor)) {
            $message = "ID pendonor tidak valid.";
            $alert_type = "danger";
        } else {
            // Block deletion if this pendonor has ANY transaksi_donasi (active OR soft-deleted)
            // This prevents FK constraint errors during permanent deletion
            $stmtTrans = $db->prepare("SELECT COUNT(*) FROM transaksi_donasi WHERE id_pendonor = ?");
            $stmtTrans->execute([$id_pendonor]);
            $hasTrans = intval($stmtTrans->fetchColumn());

            if ($hasTrans > 0) {
                $message = "Pendonor ini memiliki $hasTrans data transaksi donasi (aktif atau dihapus) dan tidak dapat dihapus. Hubungi administrator untuk menghapus transaksi terkait.";
                $alert_type = "danger";
            } else {
                // Pastikan kolom is_deleted ada
                $check = $db->prepare("SELECT COUNT(*) FROM information_schema.columns 
                    WHERE table_schema = DATABASE() 
                      AND table_name = 'pendonor' 
                      AND column_name = 'is_deleted'");
                $check->execute();
                $hasIsDeleted = (int)$check->fetchColumn() > 0;

                if ($hasIsDeleted) {
                    try {
                        $stmt = $db->prepare("UPDATE pendonor 
                            SET is_deleted = 1, deleted_at = NOW() 
                            WHERE id_pendonor = ?");
                        $ok = $stmt->execute([$id_pendonor]);

                        if ($ok) {
                            $message = "Data pendonor berhasil dipindahkan ke tong sampah (soft delete).";
                            $alert_type = "warning";
                        } else {
                            $message = "Gagal menghapus data pendonor.";
                            $alert_type = "danger";
                        }
                    } catch (PDOException $e) {
                        if ($e->getCode() == '23000') {
                            // Duplicate entry error - nama sudah ada di daftar dihapus
                            // Workaround: update nama dengan suffix timestamp untuk menghindari duplikat
                            try {
                                $timestamp = time();
                                $stmt2 = $db->prepare("UPDATE pendonor 
                                    SET is_deleted = 1, deleted_at = NOW(), nama = CONCAT(nama, '_DELETED_', ?) 
                                    WHERE id_pendonor = ?");
                                $ok2 = $stmt2->execute([$timestamp, $id_pendonor]);
                                
                                if ($ok2) {
                                    $message = "Data pendonor berhasil dipindahkan ke tong sampah (soft delete). Nama dimodifikasi untuk menghindari duplikat.";
                                    $alert_type = "warning";
                                } else {
                                    $message = "Gagal menghapus data pendonor.";
                                    $alert_type = "danger";
                                }
                            } catch (PDOException $e2) {
                                $message = "Gagal menghapus: " . htmlspecialchars($e2->getMessage());
                                $alert_type = "danger";
                            }
                        } else {
                            $message = "Gagal menghapus data pendonor: " . htmlspecialchars($e->getMessage());
                            $alert_type = "danger";
                        }
                    }
                } else {
                    // Fallback kalau kolom belum ada
                    $message = "Kolom is_deleted belum ada di tabel pendonor. Tambahkan kolom terlebih dahulu.";
                    $alert_type = "danger";
                }
            }
        }

        // Convert to flash message
        if ($alert_type == 'warning') {
            $alert_type = 'success';
        }
        $title = ($alert_type == 'success') ? 'Berhasil Dihapus' : 'Gagal Dihapus';
        $_SESSION['flash'] = ['type' => $alert_type, 'message' => $message, 'title' => $title];
        
        header('Location: ?action=pendonor');
        exit;

    case 'pendonor_restore':
        // RESTORE SATU PENDONOR DARI TRASH
        $database = new Database();
        $db = $database->getConnection();
        $id_pendonor = $_GET['id'] ?? 0;

        if (!empty($id_pendonor) && is_numeric($id_pendonor)) {
            $checkCol = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_deleted'");
            $hasIsDeleted = $checkCol && $checkCol->rowCount() > 0;
            if (!$hasIsDeleted) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Kolom is_deleted tidak ada di tabel pendonor. Tambahkan kolom terlebih dahulu.', 'title' => 'Error'];
                header('Location: ?action=pendonor_trash');
                exit;
            }
            // Fetch the pendonor name and check if an active pendonor with same name already exists
            $stmtName = $db->prepare('SELECT nama FROM pendonor WHERE id_pendonor = ?');
            $stmtName->execute([$id_pendonor]);
            $row = $stmtName->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $namaToRestore = $row['nama'];
                // Check active pendonor conflict (case-insensitive)
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND is_deleted = 0");
                $stmtCheck->execute([$namaToRestore]);
                $exists = intval($stmtCheck->fetchColumn()) > 0;
                if ($exists) {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data pendonor: entitas dengan nama yang sama sudah ada di daftar aktif.', 'title' => 'Gagal Dipulihkan'];
                    header('Location: ?action=pendonor_trash');
                    exit;
                } else {
                    try {
                        $stmt = $db->prepare("UPDATE pendonor 
                            SET is_deleted = 0, deleted_at = NULL 
                            WHERE id_pendonor = ?");
                        $ok = $stmt->execute([$id_pendonor]);
                        if ($ok) {
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil direstore.', 'title' => 'Berhasil Dipulihkan'];
                        } else {
                            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data pendonor.', 'title' => 'Gagal Dipulihkan'];
                        }
                        header('Location: ?action=pendonor_trash');
                        exit;
                    } catch (PDOException $e) {
                        if ($e->getCode() == '23000') {
                            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data pendonor: terjadi konflik data. Silakan hubungi administrator.', 'title' => 'Error'];
                        } else {
                            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data pendonor: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
                        }
                        header('Location: ?action=pendonor_trash');
                        exit;
                    }
                }
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Data pendonor tidak ditemukan.', 'title' => 'Not Found'];
                header('Location: ?action=pendonor_trash');
                exit;
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'title' => 'Invalid ID'];
            header('Location: ?action=pendonor_trash');
            exit;
        }

        break;

    case 'pendonor_restore_all':
        // RESTORE SEMUA PENDONOR YANG DI-SOFT DELETE
        $database = new Database();
        $db = $database->getConnection();

        // Ensure is_deleted exists
        $checkCol = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_deleted'");
        $hasIsDeleted = $checkCol && $checkCol->rowCount() > 0;
        if (!$hasIsDeleted) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Kolom is_deleted tidak ada di tabel pendonor. Tambahkan kolom terlebih dahulu.', 'title' => 'Error'];
            header('Location: ?action=pendonor_trash');
            exit;
        }
        // Iterate over archived pendonor and only restore if there's no active pendonor with the same name
        $stmtList = $db->prepare("SELECT id_pendonor, nama FROM pendonor WHERE is_deleted = 1");
        $stmtList->execute();
        $rows = $stmtList->fetchAll(PDO::FETCH_ASSOC);
        $restored = 0;
        $skipped = [];
        $errors = [];
        foreach ($rows as $r) {
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND is_deleted = 0");
            $stmtCheck->execute([$r['nama']]);
            $exists = intval($stmtCheck->fetchColumn()) > 0;
            if ($exists) {
                $skipped[] = $r['nama'];
                continue;
            }
            try {
                $stmt = $db->prepare("UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE id_pendonor = ?");
                if ($stmt->execute([$r['id_pendonor']])) $restored++;
                else $skipped[] = $r['nama'];
            } catch (PDOException $e) {
                $errors[] = $r['nama'];
            }
        }
        if ($restored > 0) {
            $message = "$restored data pendonor berhasil direstore.";
            $alert_type = 'success';
        } else {
            $message = "Tidak ada data pendonor berhasil direstore.";
            $alert_type = 'danger';
        }
        if (count($skipped) > 0) {
            $message .= ' Beberapa data tidak dapat direstore karena nama entitas sudah ada di daftar aktif: ' . htmlspecialchars(implode(', ', array_unique($skipped)));
        }
        if (count($errors) > 0) {
            $message .= ' Gagal restore data: ' . htmlspecialchars(implode(', ', array_unique($errors))) . ' (kemungkinan duplikat constraint).';
        }

        $_SESSION['flash'] = ['type' => $alert_type, 'message' => $message, 'title' => ($alert_type == 'success') ? 'Berhasil Dipulihkan' : 'Ada Kesalahan'];
        header('Location: ?action=pendonor_trash');
        exit;

    case 'pendonor_permanent_delete':
        // HAPUS PERMANEN SATU DATA DARI TRASH
        // Otomatis menghapus transaksi dan stok terkait terlebih dahulu
        $database = new Database();
        $db = $database->getConnection();
        $id_pendonor = $_GET['id'] ?? 0;

        if (!empty($id_pendonor) && is_numeric($id_pendonor)) {
            try {
                // Start transaction
                $db->beginTransaction();
                
                // 1. Get all transaksi IDs for this pendonor
                $stmt = $db->prepare("SELECT id_transaksi FROM transaksi_donasi WHERE id_pendonor = ?");
                $stmt->execute([$id_pendonor]);
                $transIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // 2. Delete all stok_darah linked to those transaksi
                if (!empty($transIds)) {
                    foreach ($transIds as $tid) {
                        $delStok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi = ?");
                        $delStok->execute([$tid]);
                    }
                }
                
                // 3. Delete all transaksi_donasi for this pendonor
                $delTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_pendonor = ?");
                $delTrans->execute([$id_pendonor]);
                
                // 4. Finally, delete the pendonor
                $stmt = $db->prepare("DELETE FROM pendonor WHERE id_pendonor = ? AND is_deleted = 1");
                $ok = $stmt->execute([$id_pendonor]);
                
                // Commit transaction
                $db->commit();
                
                if ($ok && $stmt->rowCount() > 0) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pendonor berhasil dihapus permanen (beserta transaksi dan stok terkait).', 'title' => 'Berhasil Dihapus'];
                } else {
                    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Pendonor tidak ditemukan atau tidak dalam status dihapus.', 'title' => 'Informasi'];
                }
            } catch (PDOException $e) {
                // Rollback on error
                $db->rollBack();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID pendonor tidak valid.', 'title' => 'Invalid ID'];
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
            
            // 1. Get all transaksi IDs from all pendonor in trash
            $stmt = $db->prepare("SELECT DISTINCT id_transaksi FROM transaksi_donasi WHERE id_pendonor IN (SELECT id_pendonor FROM pendonor WHERE is_deleted = 1)");
            $stmt->execute();
            $transIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 2. Delete all stok_darah linked to those transaksi
            if (!empty($transIds)) {
                foreach ($transIds as $tid) {
                    $delStok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi = ?");
                    $delStok->execute([$tid]);
                }
            }
            
            // 3. Delete all transaksi_donasi for pendonor in trash
            $delTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_pendonor IN (SELECT id_pendonor FROM pendonor WHERE is_deleted = 1)");
            $delTrans->execute();
            
            // 4. Finally, delete all pendonor in trash
            $stmt = $db->prepare("DELETE FROM pendonor WHERE is_deleted = 1");
            $ok = $stmt->execute();
            $deleted_count = $stmt->rowCount();
            
            // Commit transaction
            $db->commit();
            
            if ($ok && $deleted_count > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Berhasil menghapus $deleted_count data pendonor dari tong sampah (beserta transaksi dan stok terkait).", 'title' => 'Berhasil Dihapus'];
            } else {
                $_SESSION['flash'] = ['type' => 'info', 'message' => 'Tidak ada data pendonor di tong sampah.', 'title' => 'Informasi'];
            }
        } catch (PDOException $e) {
            // Rollback on error
            $db->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
        }

        header('Location: ?action=pendonor_trash');
        exit;

        
    case 'pendonor_riwayat':
        // TAMPILKAN RIWAYAT DONASI PENDONOR
        include 'View/pendonor/riwayat.php';
        break;

    // ==================== TRANSAKSI DONASI ====================
    case 'transaksi':
        include 'View/transaksi/index.php';
        break;
        
    case 'transaksi_create':
        include 'View/transaksi/create.php';
        break;
        
    case 'transaksi_store':
        // SIMPAN TRANSAKSI KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $id_pendonor = $_POST['id_pendonor'];
            $id_kegiatan = $_POST['id_kegiatan'];
            $tanggal_donasi = $_POST['tanggal_donasi'];
            $tanggal_kadaluarsa = $_POST['tanggal_kadaluarsa'] ?? null;
            $jumlah_kantong = $_POST['jumlah_kantong'];
            
            // VALIDASI: Pastikan pendonor yang dipilih layak (status kesehatan sehat)
            $checkIsLayak = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_layak'");
            $hasIsLayak = $checkIsLayak && $checkIsLayak->rowCount() > 0;
            
            // Check which screening columns exist
            $screening_cols_list = ['has_hepatitis_b', 'has_hepatitis_c', 'has_aids', 'has_hemofilia', 'has_sickle_cell', 'has_thalassemia', 'has_leukemia', 'has_lymphoma', 'has_myeloma', 'has_cjd'];
            $existing_screening_cols = [];
            foreach ($screening_cols_list as $col) {
                $checkCol = $db->query("SHOW COLUMNS FROM pendonor LIKE '$col'");
                if ($checkCol && $checkCol->rowCount() > 0) {
                    $existing_screening_cols[] = $col;
                }
            }
            
            // Build SELECT fields
            $selectFields = 'id_pendonor, nama, riwayat_penyakit, other_illness';
            if (!empty($existing_screening_cols)) {
                $selectFields .= ', ' . implode(', ', $existing_screening_cols);
            }
            if ($hasIsLayak) {
                $selectFields .= ', is_layak';
            }
            
            $stmtValidasi = $db->prepare("SELECT $selectFields FROM pendonor WHERE id_pendonor = ? AND is_deleted = 0");
            $stmtValidasi->execute([$id_pendonor]);
            $pendonorValidasi = $stmtValidasi->fetch(PDO::FETCH_ASSOC);
            
            if (!$pendonorValidasi) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pendonor tidak ditemukan atau telah dihapus!', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }
            
            // CEK STATUS KESEHATAN: Gunakan is_layak jika ada, jika tidak ada cek riwayat_penyakit
            $pendonor_tidak_layak = false;
            if ($hasIsLayak && isset($pendonorValidasi['is_layak'])) {
                // Jika kolom is_layak ada, gunakan itu untuk validasi
                // is_layak = 0 berarti TIDAK LAYAK (reject)
                // is_layak = 1 atau 2 berarti LAYAK atau SEHAT (accept)
                // is_layak = NULL berarti data lama, cek riwayat_penyakit sebagai fallback
                if ($pendonorValidasi['is_layak'] === null || $pendonorValidasi['is_layak'] === '') {
                    // Fallback untuk data lama: parse riwayat_penyakit untuk screening disease
                    // Hanya reject jika mengandung salah satu dari 9 screening disease
                    $screening_diseases = [
                        'Hepatitis B', 'Hepatitis C', 'AIDS / HIV', 'Hemofilia',
                        'Penyakit Sel Sabit', 'Thalasemia', 'Leukemia', 'Limfoma',
                        'Myeloma', 'CJD'
                    ];
                    $riwayat = $pendonorValidasi['riwayat_penyakit'] ?? '';
                    $pendonor_tidak_layak = false;
                    foreach ($screening_diseases as $disease) {
                        if (stripos($riwayat, $disease) !== false) {
                            $pendonor_tidak_layak = true;
                            break;
                        }
                    }
                } else {
                    // is_layak ada nilai: hanya reject jika = 0
                    $pendonor_tidak_layak = $pendonorValidasi['is_layak'] == 0;
                }
            } else {
                // Kolom is_layak tidak ada, gunakan fallback dengan screening disease parsing
                $screening_diseases = [
                    'Hepatitis B', 'Hepatitis C', 'AIDS / HIV', 'Hemofilia',
                    'Penyakit Sel Sabit', 'Thalasemia', 'Leukemia', 'Limfoma',
                    'Myeloma', 'CJD'
                ];
                $riwayat = $pendonorValidasi['riwayat_penyakit'] ?? '';
                $pendonor_tidak_layak = false;
                foreach ($screening_diseases as $disease) {
                    if (stripos($riwayat, $disease) !== false) {
                        $pendonor_tidak_layak = true;
                        break;
                    }
                }
            }
            
            if ($pendonor_tidak_layak) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Transaksi ditolak! ' . htmlspecialchars($pendonorValidasi['nama']) . ' memiliki status kesehatan tidak layak (' . htmlspecialchars($pendonorValidasi['riwayat_penyakit']) . ') dan tidak dapat melakukan transaksi donasi. Hanya pendonor yang sehat yang dapat melakukan donasi.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }
            
            // Validate tanggal_kadaluarsa
            if (!$tanggal_kadaluarsa || strtotime($tanggal_kadaluarsa) <= strtotime($tanggal_donasi)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Tanggal kadaluarsa harus lebih besar dari tanggal donasi.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }
            
            // Get golongan darah from pendonor
            $stmtGol = $db->prepare("SELECT id_gol_darah FROM pendonor WHERE id_pendonor = ?");
            $stmtGol->execute([$id_pendonor]);
            $pendonorData = $stmtGol->fetch(PDO::FETCH_ASSOC);
            $id_gol_darah = $pendonorData['id_gol_darah'] ?? null;
            
            $query = "INSERT INTO transaksi_donasi (id_pendonor, id_kegiatan, tanggal_donasi, jumlah_kantong) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            // Basic server-side validation
            if (!is_numeric($jumlah_kantong) || intval($jumlah_kantong) <= 0) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Jumlah kantong tidak valid.', 'icon' => 'exclamation-triangle'];
                header('Location: index.php?action=transaksi_create');
                exit;
            }
            if ($stmt->execute([$id_pendonor, $id_kegiatan, $tanggal_donasi, $jumlah_kantong])) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => "Transaksi donasi berhasil disimpan!",
                    'icon' => 'check-circle'
                ];
                // Create placeholder stok rows for each kantong (status immediately tersedia/lolos)
                try {
                    $sm = new StokModel();
                    $id_transaksi = $db->lastInsertId();
                    $sm->createStockPlaceholdersForTransaction($id_transaksi, intval($jumlah_kantong), 450, $tanggal_kadaluarsa, $id_gol_darah);
                } catch (Exception $e) {
                    // ignore errors creating placeholders
                }
            } else {
                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'message' => "Gagal menyimpan transaksi donasi!",
                    'icon' => 'exclamation-triangle'
                ];
            }
            header('Location: index.php?action=transaksi');
            exit;
        }
        break;
        
    case 'transaksi_detail':
        // TAMPILKAN DETAIL TRANSAKSI
        include 'View/transaksi/detail.php';
        break;
        
    case 'transaksi_delete':
        // SOFT DELETE TRANSAKSI
        $database = new Database();
        $db = $database->getConnection();
        
        $id_transaksi = $_GET['id'] ?? 0;

        if (empty($id_transaksi) || !is_numeric($id_transaksi)) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'ID transaksi tidak valid.',
                'icon' => 'exclamation-triangle'
            ];
            header('Location: index.php?action=transaksi');
            exit;
        }

        $stmt = $db->prepare("
            UPDATE transaksi_donasi 
            SET is_deleted = 1, deleted_at = NOW()
            WHERE id_transaksi = ?
        ");

        if ($stmt->execute([$id_transaksi])) {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Transaksi berhasil dipindahkan ke tong sampah.',
                'icon' => 'trash'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Gagal menghapus transaksi.',
                'icon' => 'exclamation-triangle'
            ];
        }

        header('Location: index.php?action=transaksi');
        exit;
        break;

    case 'transaksi_trash':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        // AMBIL TRANSAKSI YANG SUDAH DI-SOFT DELETE
        $query = "SELECT td.*, 
                        p.nama AS nama_pendonor,
                        kd.nama_kegiatan
                FROM transaksi_donasi td
                LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                WHERE td.is_deleted = 1
                ORDER BY td.deleted_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $trashed_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include 'View/transaksi/trash.php';
        break;
    
   case 'transaksi_restore':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $id = $_GET['id'] ?? 0;

        $stmt = $db->prepare("
            UPDATE transaksi_donasi
            SET is_deleted = 0, deleted_at = NULL
            WHERE id_transaksi = ?
        ");
        $stmt->execute([$id]);

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
                $stmt = $db->prepare("DELETE FROM transaksi_donasi WHERE id_transaksi = ? AND is_deleted = 1");
                $ok = $stmt->execute([$id]);
                if ($ok) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data transaksi berhasil dihapus permanen.', 'title' => 'Berhasil Dihapus'];
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen data transaksi.', 'title' => 'Gagal Dihapus'];
                }
            } catch (PDOException $e) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus data transaksi: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID transaksi tidak valid.', 'title' => 'Invalid ID'];
        }

        header('Location: ?action=transaksi_trash');
        exit;
        break;

    case 'transaksi_permanent_delete_all':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        try {
            $stmt = $db->prepare("DELETE FROM transaksi_donasi WHERE is_deleted = 1");
            $ok = $stmt->execute();
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data transaksi di tong sampah berhasil dihapus permanen.', 'title' => 'Berhasil Dihapus'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen semua data transaksi.', 'title' => 'Gagal Dihapus'];
            }
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus semua data transaksi: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
        }

        header('Location: ?action=transaksi_trash');
        exit;
        break;

                            case 'rumah_sakit_trash':
                                include 'View/rumah_sakit/trash.php';
                                break;

                            case 'rumah_sakit_restore':
                                $database = new Database();
                                $db = $database->getConnection();
                                $id = $_GET['id'] ?? 0;
                                if (!empty($id) && is_numeric($id)) {
                                    $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 0, deleted_at = NULL WHERE id_rs = ?");
                                    $ok = $stmt->execute([$id]);
                                    if ($ok) {
                                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil direstore.', 'title' => 'Berhasil Dipulihkan'];
                                    } else {
                                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data rumah sakit.', 'title' => 'Gagal Dipulihkan'];
                                    }
                                } else {
                                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID rumah sakit tidak valid.', 'title' => 'Invalid ID'];
                                }
                                header('Location: ?action=rumah_sakit_trash');
                                exit;
                                break;

                            case 'rumah_sakit_restore_all':
                                $database = new Database();
                                $db = $database->getConnection();
                                $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1");
                                $ok = $stmt->execute();
                                if ($ok) {
                                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data rumah sakit yang dihapus sudah dikembalikan.', 'title' => 'Berhasil Dipulihkan'];
                                } else {
                                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore semua data rumah sakit.', 'title' => 'Gagal Dipulihkan'];
                                }
                                header('Location: ?action=rumah_sakit_trash');
                                exit;
                                break;

                            case 'rumah_sakit_permanent_delete':
                                $database = new Database();
                                $db = $database->getConnection();
                                $id = $_GET['id'] ?? 0;
                                if (!empty($id) && is_numeric($id)) {
                                    $stmt = $db->prepare("DELETE FROM rumah_sakit WHERE id_rs = ? AND is_deleted = 1");
                                    $ok = $stmt->execute([$id]);
                                    if ($ok) {
                                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil dihapus permanen.', 'title' => 'Berhasil Dihapus'];
                                    } else {
                                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen data rumah sakit.', 'title' => 'Gagal Dihapus'];
                                    }
                                } else {
                                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID rumah sakit tidak valid.', 'title' => 'Invalid ID'];
                                }
                                header('Location: ?action=rumah_sakit_trash');
                                exit;
                                break;

                            case 'rumah_sakit_permanent_delete_all':
                                $database = new Database();
                                $db = $database->getConnection();
                                $stmt = $db->prepare("DELETE FROM rumah_sakit WHERE is_deleted = 1");
                                $ok = $stmt->execute();
                                if ($ok) {
                                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data rumah sakit di arsip berhasil dihapus permanen.', 'title' => 'Berhasil Dihapus'];
                                } else {
                                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus permanen semua data rumah sakit.', 'title' => 'Gagal Dihapus'];
                                }
                                header('Location: ?action=rumah_sakit_trash');
                                exit;
                                break;


    
        
    // (kegiatan cases are implemented later below)

    // ==================== STOK DARAH ====================
    case 'stok':
        $sc = new StokController();
        $sc->index();
        break;
    case 'stok_create':
        $sc = new StokController();
        $sc->create();
        break;
    case 'stok_trash':
        include 'View/stok/trash.php';
        break;
    case 'stok_restore':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $id = $_GET['id'] ?? 0;
        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("
                UPDATE stok_darah 
                SET is_deleted = 0, deleted_at = NULL
                WHERE id_stok = ?
            ");
            $stmt->execute([$id]);
        }
        header('Location: index.php?action=stok_trash');
        exit;
        break;

    case 'stok_restore_all':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("
            UPDATE stok_darah 
            SET is_deleted = 0, deleted_at = NULL
            WHERE is_deleted = 1
        ");
        $stmt->execute();
        header('Location: index.php?action=stok_trash');
        exit;
        break;

    case 'stok_permanent_delete':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $id = $_GET['id'] ?? 0;
        if (!empty($id) && is_numeric($id)) {
            $stmt = $db->prepare("DELETE FROM stok_darah WHERE id_stok = ? AND is_deleted = 1");
            $stmt->execute([$id]);
        }
        header('Location: index.php?action=stok_trash');
        exit;
        break;

    case 'stok_permanent_delete_all':
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("DELETE FROM stok_darah WHERE is_deleted = 1");
        $stmt->execute();
        header('Location: index.php?action=stok_trash');
        exit;
        break;


    case 'seed_golongan':
        // Run seeder SQL to populate golongan_darah and example stok entries
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        try {
            $db->exec('SET FOREIGN_KEY_CHECKS = 0');
            $sqlFile = __DIR__ . '/seeds/seed_golongan_pendonor.sql';
            if (!file_exists($sqlFile)) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'File seed tidak ditemukan: ' . $sqlFile, 'icon' => 'exclamation-triangle'];
            } else {
                $sql = file_get_contents($sqlFile);
                $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
                $db->beginTransaction();
                foreach ($statements as $stmtSql) {
                    if (strlen($stmtSql) > 0) {
                        try { $db->exec($stmtSql); } catch (Exception $e) { /* continue */ }
                    }
                }
                $db->commit();
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Seeder berhasil dijalankan. Golongan darah dan contoh stok ditambahkan.', 'icon' => 'check-circle'];
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menjalankan seeder: ' . $e->getMessage(), 'icon' => 'exclamation-triangle'];
        } finally {
            try { $db->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (Exception $_) {}
        }
        header('Location: index.php?action=stok');
        exit;
        break;
    case 'stok_detail':
        $sc = new StokController();
        $sc->detail($_GET['id'] ?? 0);
        break;
    case 'stok_edit':
        $sc = new StokController();
        $sc->edit($_GET['id'] ?? 0);
        break;
    case 'stok_set_group_total':
        $sc = new StokController();
        $sc->setGroupTotal();
        break;
    case 'distribusi_store':
        $dc = new DistribusiController();
        $dc->storeDistribusi();
        break;
        // ==================== DISTRIBUSI DARAH ====================
    case 'distribusi':
        include 'View/distribusi/index.php';
        break;
    
    case 'distribusi_trash':
    include 'View/distribusi/trash.php';
    break;
        
    case 'distribusi_create':
        include 'View/distribusi/create.php';
        break;
        
    
        
    case 'distribusi_edit':
        include 'View/distribusi/edit.php';
        break;
        
    case 'distribusi_update':
        // UPDATE DISTRIBUSI KE DATABASE (with stock restore when status becomes 'dibatalkan')
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            require_once 'Model/DistribusiModel.php';
            $dm = new DistribusiModel();
            $id_distribusi = $_GET['id'] ?? 0;
            $id_rs = $_POST['id_rs'];
            $tanggal_distribusi = $_POST['tanggal_distribusi'];
            $status = $_POST['status'];
            
            $ok = $dm->updateDistribusiAndRestoreIfCanceled($id_distribusi, $id_rs, $tanggal_distribusi, $status);
            if ($ok) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Distribusi darah berhasil diupdate!', 'title' => 'Berhasil Diperbarui'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate distribusi darah!', 'title' => 'Gagal Diperbarui'];
            }
            
            header('Location: ?action=distribusi');
            exit;
        }
        break;
        
    case 'distribusi_detail':
        include 'View/distribusi/detail.php';
        break;

    case 'distribusi_lacak':
        include 'View/distribusi/lacak.php';
        break;
        
    case 'distribusi_delete':
        $dc = new DistribusiController();
        $dc->deleteDistribusi($_GET['id'] ?? 0);
        break;
    
    case 'distribusi_restore':
    require_once 'Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();

    $id = $_GET['id'] ?? 0;
    if (!empty($id) && is_numeric($id)) {
        $stmt = $db->prepare("
            UPDATE distribusi_darah
            SET is_deleted = 0, deleted_at = NULL
            WHERE id_distribusi = ?
        ");
        $stmt->execute([$id]);
    }
    header('Location: index.php?action=distribusi_trash');
    exit;
    break;

case 'distribusi_restore_all':
    require_once 'Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        UPDATE distribusi_darah
        SET is_deleted = 0, deleted_at = NULL
        WHERE is_deleted = 1
    ");
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
        $stmt = $db->prepare("DELETE FROM distribusi_darah WHERE id_distribusi = ? AND is_deleted = 1");
        $stmt->execute([$id]);
    }
    header('Location: index.php?action=distribusi_trash');
    exit;
    break;

case 'distribusi_permanent_delete_all':
    require_once 'Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("DELETE FROM distribusi_darah WHERE is_deleted = 1");
    $stmt->execute();
    header('Location: index.php?action=distribusi_trash');
    exit;
    break;

        
    case 'rumah_sakit':
        // Include the proper rumah sakit index view
        include 'View/rumah_sakit/index.php';
        break;
        
            // ==================== KEGIATAN DONOR ====================
    case 'kegiatan':
        include 'View/kegiatan/index.php';
        break;
        
    case 'kegiatan_create':
        include 'View/kegiatan/create.php';
        break;
        
    case 'kegiatan_store':
        // SIMPAN KEGIATAN KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $nama_kegiatan = $_POST['nama_kegiatan'];
            $tanggal = $_POST['tanggal'];
            $lokasi = $_POST['lokasi'];
            
            $query = "INSERT INTO kegiatan_donasi (nama_kegiatan, tanggal, lokasi) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$nama_kegiatan, $tanggal, $lokasi])) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kegiatan donor berhasil disimpan!', 'title' => 'Berhasil Menyimpan'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan kegiatan donor!', 'title' => 'Gagal Menyimpan'];
            }
            
            header('Location: ?action=kegiatan');
            exit;
        }
        break;
        
    case 'kegiatan_edit':
        include 'View/kegiatan/edit.php';
        break;
        
    case 'kegiatan_update':
        // UPDATE KEGIATAN KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $id_kegiatan = $_GET['id'] ?? 0;
            $nama_kegiatan = $_POST['nama_kegiatan'];
            $tanggal = $_POST['tanggal'];
            $lokasi = $_POST['lokasi'];
            
            $query = "UPDATE kegiatan_donasi SET nama_kegiatan = ?, tanggal = ?, lokasi = ? WHERE id_kegiatan = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$nama_kegiatan, $tanggal, $lokasi, $id_kegiatan])) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kegiatan donor berhasil diupdate!', 'title' => 'Berhasil Diperbarui'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate kegiatan donor!', 'title' => 'Gagal Diperbarui'];
            }
            
            header('Location: ?action=kegiatan');
            exit;
        }
        break;
        
    case 'kegiatan_detail':
        include 'View/kegiatan/detail.php';
        break;
        
    case 'kegiatan_delete':
        // HAPUS KEGIATAN
        $database = new Database();
        $db = $database->getConnection();
        
        $id_kegiatan = $_GET['id'] ?? 0;
        // Soft delete if column exists, otherwise hard delete
        $check = $db->query("SHOW COLUMNS FROM kegiatan_donasi LIKE 'is_deleted'");
        if ($check && $check->rowCount() > 0) {
            $query = "UPDATE kegiatan_donasi SET is_deleted = 1, deleted_at = NOW() WHERE id_kegiatan = ?";
        } else {
            $query = "DELETE FROM kegiatan_donasi WHERE id_kegiatan = ?";
        }
        $stmt = $db->prepare($query);
        if ($stmt->execute([$id_kegiatan])) {
            $message = "Kegiatan berhasil dipindahkan ke arsip";
            $alert_type = "danger";
        } else {
            $message = "Gagal menghapus kegiatan!";
            $alert_type = "danger";
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=kegiatan' class='btn btn-primary mt-2'>Kembali ke Kegiatan</a></div>";
        include 'View/template/footer.php';
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
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan berhasil direstore.', 'title' => 'Berhasil Dipulihkan'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore data kegiatan.', 'title' => 'Gagal Dipulihkan'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID kegiatan tidak valid.', 'title' => 'Invalid ID'];
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
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Semua data kegiatan yang dihapus sudah dikembalikan.', 'title' => 'Berhasil Dipulihkan'];
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal merestore semua data kegiatan.', 'title' => 'Gagal Dipulihkan'];
        }
        header('Location: ?action=kegiatan_trash');
        exit;
        break;

    case 'kegiatan_permanent_delete':
        $database = new Database();
        $db = $database->getConnection();
        $id = $_GET['id'] ?? 0;
        if (!empty($id) && is_numeric($id)) {
            try {
                $db->beginTransaction();
                
                // 1. Hapus semua transaksi_donasi yang mereferensi kegiatan ini
                $stmtTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_kegiatan = ?");
                $stmtTrans->execute([$id]);
                
                // 2. Hapus kegiatan
                $stmt = $db->prepare("DELETE FROM kegiatan_donasi WHERE id_kegiatan = ? AND is_deleted = 1");
                $ok = $stmt->execute([$id]);
                
                $db->commit();
                
                if ($ok && $stmt->rowCount() > 0) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data kegiatan berhasil dihapus permanen (beserta transaksi terkait).', 'title' => 'Berhasil Dihapus'];
                } else {
                    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Kegiatan tidak ditemukan atau tidak dalam status arsip.', 'title' => 'Informasi'];
                }
            } catch (PDOException $e) {
                $db->rollBack();
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID kegiatan tidak valid.', 'title' => 'Invalid ID'];
        }
        header('Location: ?action=kegiatan_trash');
        exit;
        break;

    case 'kegiatan_permanent_delete_all':
        $database = new Database();
        $db = $database->getConnection();
        try {
            $db->beginTransaction();
            
            // 1. Hapus semua transaksi_donasi yang mereferensi kegiatan di arsip
            $stmtGetKeg = $db->prepare("SELECT id_kegiatan FROM kegiatan_donasi WHERE is_deleted = 1");
            $stmtGetKeg->execute();
            $kegList = $stmtGetKeg->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($kegList)) {
                foreach ($kegList as $kegId) {
                    $stmtDelTrans = $db->prepare("DELETE FROM transaksi_donasi WHERE id_kegiatan = ?");
                    $stmtDelTrans->execute([$kegId]);
                }
            }
            
            // 2. Hapus semua kegiatan di arsip
            $stmt = $db->prepare("DELETE FROM kegiatan_donasi WHERE is_deleted = 1");
            $ok = $stmt->execute();
            $deleted_count = $stmt->rowCount();
            
            $db->commit();
            
            if ($ok && $deleted_count > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Berhasil menghapus $deleted_count data kegiatan dari arsip (beserta transaksi terkait).", 'title' => 'Berhasil Dihapus'];
            } else {
                $_SESSION['flash'] = ['type' => 'info', 'message' => 'Tidak ada data kegiatan di arsip.', 'title' => 'Informasi'];
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage()), 'title' => 'Error'];
        }
        header('Location: ?action=kegiatan_trash');
        exit;
        break;
    

        // ==================== RUMAH SAKIT ====================
    case 'rumah_sakit':
        include 'View/rumah_sakit/index.php';
        break;
        
    case 'rumah_sakit_create':
        include 'View/rumah_sakit/create.php';
        break;
        
    case 'rumah_sakit_store':
        // SIMPAN RUMAH SAKIT KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $nama_rs = trim($_POST['nama_rs']);
            $alamat = trim($_POST['alamat']);
            // sanitize kontak: keep digits only
            $kontak_raw = $_POST['kontak'] ?? '';
            $kontak = preg_replace('/\D+/', '', $kontak_raw);
            
            if (strlen($kontak) < 6) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nomor kontak tidak valid. Mohon masukkan minimal 6 digit (hanya angka).'];
            } else {
                // CEK DUPLIKAT BERDASARKAN NAMA - Jangan simpan jika nama sudah ada
                $checkDupStmt = $db->prepare("SELECT COUNT(*) as cnt FROM rumah_sakit WHERE nama_rs = ? AND is_deleted = 0");
                $checkDupStmt->execute([$nama_rs]);
                $isDuplicate = $checkDupStmt->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
                
                if ($isDuplicate) {
                    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Nama Rumah Sakit "' . htmlspecialchars($nama_rs) . '" sudah ada di database. Silakan gunakan nama yang berbeda.'];
                } else {
                    $query = "INSERT INTO rumah_sakit (nama_rs, alamat, kontak) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($query);
                    if ($stmt->execute([$nama_rs, $alamat, $kontak])) {
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil disimpan!'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan data rumah sakit!'];
                    }
                }
            }
            
            // Redirect ke halaman rumah sakit
            header('Location: ?action=rumah_sakit');
            exit;
        }
        break;
        
    case 'rumah_sakit_edit':
        include 'View/rumah_sakit/edit.php';
        break;
        
    case 'rumah_sakit_update':
        // UPDATE RUMAH SAKIT KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $id_rs = $_GET['id'] ?? 0;
            $nama_rs = trim($_POST['nama_rs']);
            $alamat = trim($_POST['alamat']);
            $kontak_raw = $_POST['kontak'] ?? '';
            $kontak = preg_replace('/\D+/', '', $kontak_raw);
            
            if (strlen($kontak) < 6) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Nomor kontak tidak valid. Mohon masukkan minimal 6 digit (hanya angka).'];
            } else {
                $query = "UPDATE rumah_sakit SET nama_rs = ?, alamat = ?, kontak = ? WHERE id_rs = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$nama_rs, $alamat, $kontak, $id_rs])) {
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data rumah sakit berhasil diupdate!'];
                } else {
                    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal mengupdate data rumah sakit!'];
                }
            }
            
            // Redirect ke halaman rumah sakit
            header('Location: ?action=rumah_sakit');
            exit;
        }
        break;
        
    case 'rumah_sakit_detail':
        include 'View/rumah_sakit/detail.php';
        break;
        
    case 'rumah_sakit_delete':
        // HAPUS RUMAH SAKIT
        $database = new Database();
        $db = $database->getConnection();
        
        $id_rs = $_GET['id'] ?? 0;
        
        // Cek apakah rumah sakit memiliki distribusi (hanya yang belum diarsip)
        $query_check = "SELECT COUNT(*) as total FROM distribusi_darah WHERE id_rs = ? AND is_deleted = 0";
        // Fall back to non-is_deleted check if the column doesn't exist
        $stmt_check = $db->prepare($query_check);
        if (!$stmt_check) {
            $query_check = "SELECT COUNT(*) as total FROM distribusi_darah WHERE id_rs = ?";
            $stmt_check = $db->prepare($query_check);
        }
        $stmt_check->execute([$id_rs]);
        $has_distribusi = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'] > 0;
        
        if ($has_distribusi) {
            $message = "Tidak dapat menghapus rumah sakit karena sudah memiliki data distribusi!";
            $alert_type = "danger";
        } else {
            // Implement soft-delete if is_deleted column exists
            $check_column_stmt = $db->query("SHOW COLUMNS FROM rumah_sakit LIKE 'is_deleted'");
            if ($check_column_stmt && $check_column_stmt->rowCount() > 0) {
                $query = "UPDATE rumah_sakit SET is_deleted = 1, deleted_at = NOW() WHERE id_rs = ?";
            } else {
                $query = "DELETE FROM rumah_sakit WHERE id_rs = ?";
            }
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$id_rs])) {
                $message = "Rumah sakit berhasil dihapus (soft-deleted).";
                $alert_type = "success";
            } else {
                $message = "Gagal menghapus rumah sakit!";
                $alert_type = "danger";
            }
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=rumah_sakit' class='btn btn-primary mt-2'>Kembali ke Rumah Sakit</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'rumah_sakit_laporan':
        // HALAMAN LAPORAN DISTRIBUSI PER RUMAH SAKIT
        include 'View/rumah_sakit/laporan.php';  // Ganti dengan file yang benar
        break;
        
    case 'clean_rumah_sakit_duplicates':
        // BERSIHKAN DATA DUPLIKAT RUMAH SAKIT
        $database = new Database();
        $db = $database->getConnection();
        
        // Find duplicates and keep only the oldest one (lowest id)
        $stmt = $db->prepare("
            SELECT nama_rs, alamat, kontak, MIN(id_rs) as keep_id, GROUP_CONCAT(id_rs) as all_ids
            FROM rumah_sakit
            WHERE is_deleted = 0
            GROUP BY nama_rs, alamat, kontak
            HAVING COUNT(*) > 1
        ");
        $stmt->execute();
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $deleted_count = 0;
        foreach ($duplicates as $dup) {
            $keep_id = $dup['keep_id'];
            $all_ids = explode(',', $dup['all_ids']);
            
            foreach ($all_ids as $id) {
                if ($id != $keep_id) {
                    $deleteStmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 1, deleted_at = NOW() WHERE id_rs = ?");
                    if ($deleteStmt->execute([$id])) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-success m-4'>
            Berhasil membersihkan " . $deleted_count . " data duplikat rumah sakit!
            <br><a href='?action=rumah_sakit' class='btn btn-primary mt-2'>Kembali ke Rumah Sakit</a>
        </div>";
        include 'View/template/footer.php';
        break;
        
    case 'dashboard_data':
        // Return JSON with dashboard stats for AJAX refresh
        require_once 'Config/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        // Pendonor count
        $checkIsDeleted = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor' AND column_name = 'is_deleted'");
        $checkIsDeleted->execute();
        $hasIsDeleted = intval($checkIsDeleted->fetchColumn()) > 0;
        if ($hasIsDeleted) {
            $stmt = $db->prepare('SELECT COUNT(*) as total FROM pendonor WHERE is_deleted = 0');
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) as total FROM pendonor');
        }
        $stmt->execute();
        $total_pendonor = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

        // Total stok tersedia
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM stok_darah WHERE is_deleted = 0 AND status = 'tersedia' AND status_uji = 'lolos' AND (tanggal_kadaluarsa IS NULL OR tanggal_kadaluarsa > CURDATE())");
        $stmt->execute();
        $total_stok = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

        // Transaksi hari ini (ONLY count non-deleted to sync with Transaksi page)
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM transaksi_donasi WHERE DATE(tanggal_donasi) = CURDATE() AND is_deleted = 0");
        $stmt->execute();
        $transaksi_hari_ini = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

        // Distribusi bulan ini
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM distribusi_darah WHERE DATE_FORMAT(tanggal_distribusi, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
        $stmt->execute();
        $distribusi_bulan_ini = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

        // Stok per golongan (only count tersedia stok)
        $stmt = $db->prepare("
            SELECT 
                gd.id_gol_darah,
                gd.nama_gol_darah, 
                gd.rhesus, 
                COUNT(sd.id_stok) as total_kantong
            FROM golongan_darah gd
            LEFT JOIN stok_darah sd ON sd.id_gol_darah = gd.id_gol_darah 
                AND sd.is_deleted = 0 
                AND sd.status = 'tersedia'
                AND sd.status_uji = 'lolos'
                AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa > CURDATE())
            GROUP BY gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus
            ORDER BY gd.nama_gol_darah, gd.rhesus
        ");
        $stmt->execute();
        $stok_group = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'total_pendonor' => $total_pendonor,
            'total_stok' => $total_stok,
            'transaksi_hari_ini' => $transaksi_hari_ini,
            'distribusi_bulan_ini' => $distribusi_bulan_ini,
            'stok_group' => $stok_group
        ]);
        exit;
        break;
    
    case 'check_pendonor_transaksi':
        // AJAX endpoint: check if pendonor has any transaksi (for UI warnings)
        header('Content-Type: application/json');
        $id_pendonor = $_GET['id'] ?? 0;
        if (!is_numeric($id_pendonor) || $id_pendonor <= 0) {
            echo json_encode(['has_transaksi' => false, 'count' => 0, 'error' => 'Invalid ID']);
            exit;
        }
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM transaksi_donasi WHERE id_pendonor = ?");
        $stmt->execute([$id_pendonor]);
        $count = intval($stmt->fetchColumn());
        echo json_encode(['has_transaksi' => $count > 0, 'count' => $count]);
        exit;
        break;
    
    case 'petugas':
        // Staff management disabled
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Fitur manajemen data petugas dinonaktifkan', 'icon' => 'info-circle'];
        header('Location: index.php?action=dashboard');
        exit;
        break;
    case 'login':
        $pc = new PetugasController();
        $pc->login();
        break;
    case 'authenticate':
        $pc = new PetugasController();
        $pc->authenticate();
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
    case 'petugas_edit':
        $pc = new PetugasController();
        $pc->edit($_GET['id'] ?? $_SESSION['id_petugas']);
        break;
    case 'petugas_update':
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $pc = new PetugasController();
            $id = $_POST['id_petugas'] ?? $_GET['id'] ?? $_SESSION['id_petugas'];
            $pc->update($id);
        }
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
    case 'logout':
        $pc = new PetugasController();
        $pc->logout();
        break;
    // NOTE: the 'setup_admin' route has been removed for security and removed features.
}
?> 
