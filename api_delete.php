<?php
// api_delete.php
// API untuk menangani soft delete via AJAX
// Menerima POST request dengan action dan id

require_once 'Config/Database.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header untuk JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Cek request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

// Ambil data dari request body
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id = isset($input['id']) ? intval($input['id']) : 0;
$ids = isset($input['ids']) && is_array($input['ids']) ? $input['ids'] : [];

// Validasi: harus ada action dan (id atau ids)
if (empty($action) || (empty($id) && empty($ids))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid: action dan id/ids diperlukan']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

/**
 * Helper function to check if required columns exist in a table
 */
function checkTableColumns($db, $tableName, $requiredColumns) {
    $placeholders = implode(',', array_fill(0, count($requiredColumns), '?'));
    $stmt = $db->prepare("SELECT column_name FROM information_schema.columns 
                         WHERE table_schema = DATABASE() 
                         AND table_name = ? 
                         AND column_name IN ($placeholders)");
    
    $params = array_merge([$tableName], $requiredColumns);
    $stmt->execute($params);
    $foundColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missing = array_diff($requiredColumns, $foundColumns);
    return [
        'missing' => $missing,
        'is_complete' => empty($missing)
    ];
}

/**
 * Helper function to translate database errors to user-friendly messages
 */
function translateDatabaseError($errorMsg, $context = '') {
    $userMsg = 'Terjadi kesalahan saat memproses permintaan.';
    
    if (stripos($errorMsg, 'Unknown column') !== false || stripos($errorMsg, 'Column not found') !== false) {
        // Extract column name and table name if possible
        preg_match("/Unknown column '([^']+)'/i", $errorMsg, $col_matches);
        preg_match("/in '([^']+)'/i", $errorMsg, $table_matches);
        
        $colName = $col_matches[1] ?? 'kolom (tidak diketahui)';
        $tableName = $table_matches[1] ?? 'tabel (tidak diketahui)';
        
        $userMsg = "Struktur database tidak lengkap. Kolom '$colName' tidak ada di tabel '$tableName'. Database perlu di-upgrade dengan menambahkan kolom yang hilang.";
    } elseif (stripos($errorMsg, 'Integrity constraint violation') !== false) {
        $userMsg = 'Data ini masih terhubung dengan data lain dan tidak dapat dihapus.';
    } elseif (stripos($errorMsg, 'Access denied') !== false) {
        $userMsg = 'Anda tidak memiliki izin akses untuk melakukan operasi ini.';
    } elseif (stripos($errorMsg, 'foreign key constraint fails') !== false) {
        $userMsg = 'Data ini memiliki referensi di tabel lain dan tidak dapat dihapus. Hapus data terkait terlebih dahulu.';
    }
    
    return $userMsg;
}

try {

    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // --- TAMBAHKAN LOGGING INI ---
    error_log("API_DELETE.PHP DEBUG: Raw Input: " . file_get_contents('php://input'));
    error_log("API_DELETE.PHP DEBUG: Action received: '$action'");
    error_log("API_DELETE.PHP DEBUG: Input Array: " . print_r($input, true));
    // --- AKHIR TAMBAHKAN LOGGING ---
    switch ($action) {
        case 'pendonor_delete':
            // SOFT DELETE PENDONOR
            // Block deletion if this pendonor has ANY transaksi_donasi
            $stmtTrans = $db->prepare("SELECT COUNT(*) FROM transaksi_donasi WHERE id_pendonor = ? AND is_deleted = 0");
            $stmtTrans->execute([$id]);
            $hasTrans = intval($stmtTrans->fetchColumn());

            if ($hasTrans > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Pendonor ini memiliki $hasTrans riwayat donasi aktif. Silakan arsipkan riwayat donasi terlebih dahulu."
                ]);
                exit;
            }

            // Perform soft delete
            try {
                $stmt = $db->prepare("UPDATE pendonor 
                    SET is_deleted = 1, deleted_at = NOW() 
                    WHERE id_pendonor = ?");
                $ok = $stmt->execute([$id]);

                if ($ok) {
                    echo json_encode(['success' => true, 'message' => 'Data pendonor berhasil dipindahkan ke tong sampah']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Gagal menghapus data pendonor']);
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    // Workaround: rename to avoid unique constraint
                    $timestamp = time();
                    $stmt2 = $db->prepare("UPDATE pendonor 
                        SET is_deleted = 1, deleted_at = NOW(), nama = CONCAT(nama, '_DELETED_', ?) 
                        WHERE id_pendonor = ?");
                    $ok2 = $stmt2->execute([$timestamp, $id]);
                    
                    if ($ok2) {
                        echo json_encode(['success' => true, 'message' => 'Data pendonor berhasil dipindahkan ke tong sampah (nama dimodifikasi)']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Gagal menghapus data pendonor']);
                    }
                } else {
                    http_response_code(500);
                    $errorMsg = $e->getMessage();
                    $userMsg = 'Gagal menghapus pendonor.';
                    
                    if (stripos($errorMsg, 'Unknown column') !== false || stripos($errorMsg, 'Column not found') !== false) {
                        $userMsg = 'Struktur database tidak lengkap. Hubungi administrator.';
                    } elseif (stripos($errorMsg, 'Integrity constraint') !== false) {
                        $userMsg = 'Pendonor masih terhubung dengan data lain.';
                    }
                    
                    echo json_encode(['success' => false, 'message' => $userMsg]);
                }
            }
            break;

        case 'stok_delete':
            // SOFT DELETE STOK
            try {
                $stmt = $db->prepare("UPDATE stok_darah 
                    SET is_deleted = 1, deleted_at = NOW() 
                    WHERE id_stok = ?");
                $ok = $stmt->execute([$id]);

                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Data stok berhasil dipindahkan ke tong sampah']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Data stok tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus stok']);
            }
            break;

        case 'transaksi_permanent_delete':
            // PERMANENT DELETE TRANSAKSI (dari trash)
            // Must delete in order: distribusi_darah -> stok_darah -> transaksi_donasi
            // to avoid foreign key constraint violations
            
            try {
                $db->beginTransaction();
                
                // 1. Get all stok IDs linked to this transaksi
                $getStok = $db->prepare("SELECT id_stok FROM stok_darah WHERE id_transaksi = ?");
                $getStok->execute([$id]);
                $stokIds = $getStok->fetchAll(PDO::FETCH_COLUMN);
                
                // 2. Delete all distribusi_darah linked to those stok (must be done first due to FK)
                if (!empty($stokIds)) {
                    $placeholders = implode(',', array_fill(0, count($stokIds), '?'));
                    $delDist = $db->prepare("DELETE FROM distribusi_darah WHERE id_stok IN ($placeholders)");
                    $delDist->execute($stokIds);
                }
                
                // 3. Delete all stok_darah linked to this transaksi
                $delStok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi = ?");
                $delStok->execute([$id]);
                
                // 4. Finally delete the transaksi
                $stmt = $db->prepare("DELETE FROM transaksi_donasi WHERE id_transaksi = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dihapus permanen']);
                } else {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
                }
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus transaksi: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'transaksi_bulk_permanent_delete':
            // BULK PERMANENT DELETE TRANSAKSI dari trash
            // Must delete in order: distribusi_darah -> stok_darah -> transaksi_donasi
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                $deleted = 0;
                $failed = [];
                
                // Step 1: Get all stok IDs for all transaksi to delete
                $placeholders = implode(',', array_fill(0, count($bulk_ids), '?'));
                $getStoks = $db->prepare("SELECT id_stok FROM stok_darah WHERE id_transaksi IN ($placeholders)");
                $getStoks->execute($bulk_ids);
                $allStokIds = $getStoks->fetchAll(PDO::FETCH_COLUMN);
                
                // Step 2: Delete all distribusi_darah linked to those stok (FIRST - to avoid FK constraint)
                if (!empty($allStokIds)) {
                    $stokPlaceholders = implode(',', array_fill(0, count($allStokIds), '?'));
                    $delDist = $db->prepare("DELETE FROM distribusi_darah WHERE id_stok IN ($stokPlaceholders)");
                    $delDist->execute($allStokIds);
                }
                
                // Step 3: Delete all stok_darah linked to these transaksi
                $delAllStok = $db->prepare("DELETE FROM stok_darah WHERE id_transaksi IN ($placeholders)");
                $delAllStok->execute($bulk_ids);
                
                // Step 4: Finally delete all transaksi
                $stmt = $db->prepare("DELETE FROM transaksi_donasi WHERE id_transaksi IN ($placeholders)");
                $stmt->execute($bulk_ids);
                $deleted = $stmt->rowCount();
                
                $db->commit();
                
                $message = "$deleted transaksi berhasil dihapus permanen beserta stok dan distribusinya";
                echo json_encode(['success' => $deleted > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'pendonor_bulk_permanent_delete':
            // BULK PERMANENT DELETE PENDONOR dari trash
            // Gunakan $ids yang sudah diparse di atas
            
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                // Untuk setiap pendonor: hapus transaksi dan stoknya
                foreach ($bulk_ids as $id_pendonor) {
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
                    $delPendonor = $db->prepare("DELETE FROM pendonor WHERE id_pendonor = ? AND is_deleted = 1");
                    $delPendonor->execute([$id_pendonor]);
                }
                
                $db->commit();
                echo json_encode(['success' => true, 'message' => count($bulk_ids) . ' data pendonor berhasil dihapus permanen beserta transaksi dan stok terkait']);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                $errorMsg = 'Gagal menghapus: ';
                if (strpos($e->getMessage(), 'foreign key') !== false) {
                    $errorMsg .= 'Ada data terkait yang tidak dapat dihapus (mungkin ada distribusi atau referensi lain)';
                } else {
                    $errorMsg .= htmlspecialchars($e->getMessage());
                }
                echo json_encode(['success' => false, 'message' => $errorMsg]);
            }
            break;

        case 'pendonor_bulk_restore':
            // BULK RESTORE PENDONOR dari trash
            // Gunakan $ids yang sudah diparse di atas
            
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                $restored = 0;
                $failed = [];
                
                foreach ($bulk_ids as $id_pendonor) {
                    // Check if an active pendonor with same name already exists
                    $stmtName = $db->prepare('SELECT nama FROM pendonor WHERE id_pendonor = ?');
                    $stmtName->execute([$id_pendonor]);
                    $row = $stmtName->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$row) {
                        $failed[] = "ID $id_pendonor tidak ditemukan";
                        continue;
                    }
                    
                    $namaToRestore = $row['nama'];
                    
                    // Check active pendonor conflict (case-insensitive)
                    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM pendonor WHERE LOWER(TRIM(nama)) = LOWER(TRIM(?)) AND is_deleted = 0");
                    $stmtCheck->execute([$namaToRestore]);
                    $exists = intval($stmtCheck->fetchColumn()) > 0;
                    
                    if ($exists) {
                        $failed[] = "Pendonor '$namaToRestore' sudah ada di daftar aktif";
                        continue;
                    }
                    
                    // Restore
                    $stmt = $db->prepare("UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE id_pendonor = ?");
                    $ok = $stmt->execute([$id_pendonor]);
                    if ($ok) {
                        $restored++;
                    } else {
                        $failed[] = "Gagal restore pendonor ID $id_pendonor";
                    }
                }
                
                $db->commit();
                
                $message = "$restored data pendonor berhasil dipulihkan";
                if (!empty($failed)) {
                    $message .= '. Gagal: ' . implode('; ', $failed);
                }
                
                echo json_encode(['success' => $restored > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal restore: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'transaksi_delete':
            // SOFT DELETE TRANSAKSI
            // Check if transaksi has any distribusi (join via stok_darah)
            $stmtDist = $db->prepare("SELECT COUNT(*) FROM distribusi_darah d 
                JOIN stok_darah s ON d.id_stok = s.id_stok 
                WHERE s.id_transaksi = ? AND d.is_deleted = 0");
            $stmtDist->execute([$id]);
            $hasDistribusi = intval($stmtDist->fetchColumn());

            if ($hasDistribusi > 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Transaksi ini sudah melakukan $hasDistribusi distribusi. Silakan arsipkan distribusi terlebih dahulu."
                ]);
                exit;
            }

            // Perform soft delete
            $stmt = $db->prepare("UPDATE transaksi_donasi 
                SET is_deleted = 1, deleted_at = NOW() 
                WHERE id_transaksi = ?");
            
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Transaksi berhasil dipindahkan ke tong sampah']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus transaksi']);
            }
            break;

        case 'rumah_sakit_delete':
            // SOFT DELETE RUMAH SAKIT
            try {
                $stmt = $db->prepare("UPDATE rumah_sakit 
                    SET is_deleted = 1, deleted_at = NOW() 
                    WHERE id_rs = ?");
                $ok = $stmt->execute([$id]);

                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Data rumah sakit berhasil dipindahkan ke tong sampah']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Data rumah sakit tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus rumah sakit']);
            }
            break;

        case 'rumah_sakit_permanent_delete':
            // PERMANENT DELETE RUMAH SAKIT (dari trash)
            try {
                $stmt = $db->prepare("DELETE FROM rumah_sakit WHERE id_rs = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Rumah sakit berhasil dihapus permanen']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Rumah sakit tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus rumah sakit: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'rumah_sakit_restore':
            // RESTORE RUMAH SAKIT dari trash
            try {
                $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 0, deleted_at = NULL WHERE id_rs = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Rumah sakit berhasil dipulihkan dari arsip']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Rumah sakit tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memulihkan rumah sakit']);
            }
            break;

        case 'rumah_sakit_bulk_permanent_delete':
            // BULK PERMANENT DELETE RUMAH SAKIT dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                $deleted = 0;
                
                foreach ($bulk_ids as $id_rs) {
                    $stmt = $db->prepare("DELETE FROM rumah_sakit WHERE id_rs = ?");
                    $stmt->execute([$id_rs]);
                    
                    if ($stmt->rowCount() > 0) {
                        $deleted++;
                    }
                }
                
                $db->commit();
                
                $message = "$deleted rumah sakit berhasil dihapus permanen";
                echo json_encode(['success' => $deleted > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'rumah_sakit_bulk_restore':
            // BULK RESTORE RUMAH SAKIT dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                $restored = 0;
                $failed = [];
                
                foreach ($bulk_ids as $id_rs) {
                    $stmt = $db->prepare("UPDATE rumah_sakit SET is_deleted = 0, deleted_at = NULL WHERE id_rs = ?");
                    $ok = $stmt->execute([$id_rs]);
                    if ($ok && $stmt->rowCount() > 0) {
                        $restored++;
                    } else {
                        $failed[] = "Rumah sakit ID $id_rs tidak ditemukan";
                    }
                }
                
                $db->commit();
                
                $message = "$restored rumah sakit berhasil dipulihkan";
                if (!empty($failed)) {
                    $message .= '. Gagal: ' . implode('; ', $failed);
                }
                
                echo json_encode(['success' => $restored > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal restore: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'kegiatan_delete':
            // SOFT DELETE KEGIATAN
            try {
                $stmt = $db->prepare("UPDATE kegiatan_donasi 
                    SET is_deleted = 1, deleted_at = NOW() 
                    WHERE id_kegiatan = ?");
                $ok = $stmt->execute([$id]);

                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Data kegiatan berhasil dipindahkan ke tong sampah']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Data kegiatan tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus kegiatan']);
            }
            break;

        case 'kegiatan_permanent_delete':
            // PERMANENT DELETE KEGIATAN (dari trash)
            try {
                $stmt = $db->prepare("DELETE FROM kegiatan_donasi WHERE id_kegiatan = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Kegiatan berhasil dihapus permanen']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus kegiatan: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'kegiatan_restore':
            // RESTORE KEGIATAN dari trash
            try {
                $stmt = $db->prepare("UPDATE kegiatan_donasi SET is_deleted = 0, deleted_at = NULL WHERE id_kegiatan = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Kegiatan berhasil dipulihkan dari arsip']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Kegiatan tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memulihkan kegiatan']);
            }
            break;

        case 'kegiatan_bulk_permanent_delete':
            // BULK PERMANENT DELETE KEGIATAN dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                $deleted = 0;
                
                foreach ($bulk_ids as $id_kegiatan) {
                    $stmt = $db->prepare("DELETE FROM kegiatan_donasi WHERE id_kegiatan = ?");
                    $stmt->execute([$id_kegiatan]);
                    
                    if ($stmt->rowCount() > 0) {
                        $deleted++;
                    }
                }
                
                $db->commit();
                
                $message = "$deleted kegiatan berhasil dihapus permanen";
                echo json_encode(['success' => $deleted > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'kegiatan_bulk_restore':
            // BULK RESTORE KEGIATAN dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                $restored = 0;
                $failed = [];
                
                foreach ($bulk_ids as $id_kegiatan) {
                    $stmt = $db->prepare("UPDATE kegiatan_donasi SET is_deleted = 0, deleted_at = NULL WHERE id_kegiatan = ?");
                    $ok = $stmt->execute([$id_kegiatan]);
                    if ($ok && $stmt->rowCount() > 0) {
                        $restored++;
                    } else {
                        $failed[] = "Kegiatan ID $id_kegiatan tidak ditemukan";
                    }
                }
                
                $db->commit();
                
                $message = "$restored kegiatan berhasil dipulihkan";
                if (!empty($failed)) {
                    $message .= '. Gagal: ' . implode('; ', $failed);
                }
                
                echo json_encode(['success' => $restored > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal restore: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'stok_permanent_delete':
            // PERMANENT DELETE STOK (dari trash)
            // Must delete distribusi_darah first (FK constraint)
            try {
                $db->beginTransaction();
                
                // 1. Delete all distribusi_darah linked to this stok
                $delDist = $db->prepare("DELETE FROM distribusi_darah WHERE id_stok = ?");
                $delDist->execute([$id]);
                
                // 2. Delete the stok
                $stmt = $db->prepare("DELETE FROM stok_darah WHERE id_stok = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Stok berhasil dihapus permanen beserta distribusinya']);
                } else {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Stok tidak ditemukan']);
                }
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus stok: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'stok_restore':
            // RESTORE STOK dari trash
            try {
                $stmt = $db->prepare("UPDATE stok_darah SET is_deleted = 0, deleted_at = NULL WHERE id_stok = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Stok berhasil dipulihkan dari arsip']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Stok tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memulihkan stok']);
            }
            break;

        case 'stok_bulk_permanent_delete':
            // BULK PERMANENT DELETE STOK dari trash
            // Must delete distribusi_darah first (FK constraint)
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                // Step 1: Delete all distribusi_darah linked to these stok (FIRST)
                $placeholders = implode(',', array_fill(0, count($bulk_ids), '?'));
                $delDist = $db->prepare("DELETE FROM distribusi_darah WHERE id_stok IN ($placeholders)");
                $delDist->execute($bulk_ids);
                
                // Step 2: Delete all stok
                $stmt = $db->prepare("DELETE FROM stok_darah WHERE id_stok IN ($placeholders)");
                $stmt->execute($bulk_ids);
                $deleted = $stmt->rowCount();
                
                $db->commit();
                
                $message = "$deleted stok berhasil dihapus permanen beserta distribusinya";
                echo json_encode(['success' => $deleted > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'stok_bulk_restore':
            // BULK RESTORE STOK dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                $restored = 0;
                $failed = [];
                
                foreach ($bulk_ids as $id_stok) {
                    $stmt = $db->prepare("UPDATE stok_darah SET is_deleted = 0, deleted_at = NULL WHERE id_stok = ?");
                    $ok = $stmt->execute([$id_stok]);
                    if ($ok && $stmt->rowCount() > 0) {
                        $restored++;
                    } else {
                        $failed[] = "Stok ID $id_stok tidak ditemukan";
                    }
                }
                
                $db->commit();
                
                $message = "$restored stok berhasil dipulihkan";
                if (!empty($failed)) {
                    $message .= '. Gagal: ' . implode('; ', $failed);
                }
                
                echo json_encode(['success' => $restored > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal restore: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'distribusi_delete':
            // SOFT DELETE DISTRIBUSI
            try {
                $stmt = $db->prepare("UPDATE distribusi_darah 
                    SET is_deleted = 1, deleted_at = NOW() 
                    WHERE id_distribusi = ?");
                $ok = $stmt->execute([$id]);

                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Data distribusi berhasil dipindahkan ke tong sampah']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Data distribusi tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus distribusi']);
            }
            break;

        case 'distribusi_permanent_delete':
            // PERMANENT DELETE DISTRIBUSI (dari trash)
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("DELETE FROM distribusi_darah WHERE id_distribusi = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Distribusi berhasil dihapus permanen']);
                } else {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Distribusi tidak ditemukan']);
                }
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus distribusi: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'distribusi_restore':
            // RESTORE DISTRIBUSI dari trash
            try {
                $stmt = $db->prepare("UPDATE distribusi_darah SET is_deleted = 0, deleted_at = NULL WHERE id_distribusi = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Distribusi berhasil dipulihkan dari arsip']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Distribusi tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memulihkan distribusi']);
            }
            break;

        case 'distribusi_bulk_permanent_delete':
            // BULK PERMANENT DELETE DISTRIBUSI dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                $placeholders = implode(',', array_fill(0, count($bulk_ids), '?'));
                $stmt = $db->prepare("DELETE FROM distribusi_darah WHERE id_distribusi IN ($placeholders)");
                $stmt->execute($bulk_ids);
                $deleted = $stmt->rowCount();
                
                $db->commit();
                
                $message = "$deleted distribusi berhasil dihapus permanen";
                echo json_encode(['success' => $deleted > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'distribusi_bulk_restore':
            // BULK RESTORE DISTRIBUSI dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }
            
            try {
                $db->beginTransaction();
                $restored = 0;
                $failed = [];
                
                foreach ($bulk_ids as $id_distribusi) {
                    $stmt = $db->prepare("UPDATE distribusi_darah SET is_deleted = 0, deleted_at = NULL WHERE id_distribusi = ?");
                    $ok = $stmt->execute([$id_distribusi]);
                    if ($ok && $stmt->rowCount() > 0) {
                        $restored++;
                    } else {
                        $failed[] = "Distribusi ID $id_distribusi tidak ditemukan";
                    }
                }
                
                $db->commit();
                
                $message = "$restored distribusi berhasil dipulihkan";
                if (!empty($failed)) {
                    $message .= '. Gagal: ' . implode('; ', $failed);
                }
                
                echo json_encode(['success' => $restored > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal restore: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'transaksi_restore':
            // RESTORE TRANSAKSI dari trash
            try {
                $stmt = $db->prepare("UPDATE transaksi_donasi SET is_deleted = 0, deleted_at = NULL WHERE id_transaksi = ?");
                $ok = $stmt->execute([$id]);
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Data transaksi berhasil dipulihkan dari arsip']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memulihkan transaksi']);
            }
            break;

        case 'transaksi_bulk_restore':
            // BULK RESTORE TRANSAKSI dari trash
            if (empty($ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Tidak ada data yang dipilih']);
                exit;
            }
            // Validate dan sanitize IDs
            $bulk_ids = array_filter(array_map('intval', $ids));
            if (empty($bulk_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
                exit;
            }

            try {
                $db->beginTransaction();
                $restored = 0;
                $failed = [];
                foreach ($bulk_ids as $id_transaksi) {
                    $stmt = $db->prepare("UPDATE transaksi_donasi SET is_deleted = 0, deleted_at = NULL WHERE id_transaksi = ? AND is_deleted = 1"); // Tambahkan is_deleted = 1 untuk keamanan
                    $ok = $stmt->execute([$id_transaksi]);
                    if ($ok && $stmt->rowCount() > 0) { // Periksa apakah baris benar-benar diupdate
                        $restored++;
                    } else {
                        $failed[] = "Transaksi ID $id_transaksi tidak ditemukan";
                    }
                }
                $db->commit();
                $message = "$restored data transaksi berhasil dipulihkan dari arsip";
                if (!empty($failed)) {
                    $message .= '. Gagal: ' . implode('; ', $failed);
                }
                echo json_encode(['success' => $restored > 0, 'message' => $message]);
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal restore: ' . htmlspecialchars($e->getMessage())]);
            }
            break;

        case 'pendonor_restore':
            // RESTORE PENDONOR dari trash
            try {
                $stmt = $db->prepare("UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE id_pendonor = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Pendonor berhasil dipulihkan dari arsip']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Pendonor tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memulihkan pendonor']);
            }
            break;

        case 'stok_delete_permanent':
            // PERMANENT DELETE STOK (dari trash) - alias
            try {
                $stmt = $db->prepare("DELETE FROM stok_darah WHERE id_stok = ?");
                $ok = $stmt->execute([$id]);
                
                if ($ok && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Stok berhasil dihapus permanen']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Stok tidak ditemukan']);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus stok']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    
    $errorMsg = $e->getMessage();
    $userFriendlyMsg = translateDatabaseError($errorMsg);
    
    echo json_encode([
        'success' => false, 
        'message' => $userFriendlyMsg,
        'debug' => htmlspecialchars($errorMsg) // Include technical message for debugging
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan saat memproses permintaan. Silakan coba lagi atau hubungi administrator.',
        'debug' => htmlspecialchars($e->getMessage())
    ]);
}
