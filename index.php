<?php
// Simple Router untuk project PMI
$action = $_GET['action'] ?? 'dashboard';

// Include Database
require_once 'Config/Database.php';

switch ($action) {
    case 'dashboard':
        include 'View/dashboard/index.php';
        break;
        
    case 'pendonor':
        include 'View/pendonor/index.php';
        break;
        
    case 'pendonor_create':
        include 'View/pendonor/create.php';
        break;
        
    case 'pendonor_store':
        // SIMPAN DATA KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $nama = $_POST['nama'];
            $kontak = $_POST['kontak'];
            $riwayat_penyakit = $_POST['riwayat_penyakit'] ?? '';
            
            $query = "INSERT INTO pendonor (nama, kontak, riwayat_penyakit) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$nama, $kontak, $riwayat_penyakit])) {
                $message = "Data pendonor berhasil disimpan!";
                $alert_type = "success";
            } else {
                $message = "Gagal menyimpan data pendonor!";
                $alert_type = "danger";
            }
            
            // Tampilkan pesan dengan template
            include 'View/template/header.php';
            echo "<div class='alert alert-$alert_type m-4'>$message 
                  <br><a href='?action=pendonor' class='btn btn-primary mt-2'>Kembali ke Data Pendonor</a></div>";
            include 'View/template/footer.php';
        }
        break;
        
    case 'pendonor_edit':
        include 'View/pendonor/edit.php';
        break;
        
    case 'pendonor_update':
        // UPDATE DATA KE DATABASE
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $database = new Database();
            $db = $database->getConnection();
            
            $id_pendonor = $_GET['id'] ?? 0;
            $nama = $_POST['nama'];
            $kontak = $_POST['kontak'];
            $riwayat_penyakit = $_POST['riwayat_penyakit'] ?? '';
            
            $query = "UPDATE pendonor SET nama = ?, kontak = ?, riwayat_penyakit = ? WHERE id_pendonor = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$nama, $kontak, $riwayat_penyakit, $id_pendonor])) {
                $message = "Data pendonor berhasil diupdate!";
                $alert_type = "success";
            } else {
                $message = "Gagal mengupdate data pendonor!";
                $alert_type = "danger";
            }
            
            include 'View/template/header.php';
            echo "<div class='alert alert-$alert_type m-4'>$message 
                  <br><a href='?action=pendonor' class='btn btn-primary mt-2'>Kembali ke Data Pendonor</a></div>";
            include 'View/template/footer.php';
        }
        break;
        
    // SOFT DELETE ROUTES
    case 'pendonor_soft_delete':
        // SOFT DELETE - HANYA TANDAI SEBAGAI DIHAPUS
        $database = new Database();
        $db = $database->getConnection();
        
        $id_pendonor = $_GET['id'] ?? 0;
        
        $query = "UPDATE pendonor SET is_deleted = 1, deleted_at = NOW() WHERE id_pendonor = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id_pendonor])) {
            $message = "Data pendonor berhasil dihapus (dapat dikembalikan)!";
            $alert_type = "warning";
        } else {
            $message = "Gagal menghapus data pendonor!";
            $alert_type = "danger";
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=pendonor' class='btn btn-primary mt-2'>Kembali ke Data Pendonor</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'pendonor_restore':
        // RESTORE DATA
        $database = new Database();
        $db = $database->getConnection();
        
        $id_pendonor = $_GET['id'] ?? 0;
        
        $query = "UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE id_pendonor = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id_pendonor])) {
            $message = "Data pendonor berhasil dikembalikan!";
            $alert_type = "success";
        } else {
            $message = "Gagal mengembalikan data pendonor!";
            $alert_type = "danger";
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=pendonor_trash' class='btn btn-primary mt-2'>Kembali ke Data Terhapus</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'pendonor_permanent_delete':
        // HAPUS PERMANEN
        $database = new Database();
        $db = $database->getConnection();
        
        $id_pendonor = $_GET['id'] ?? 0;
        
        $query = "DELETE FROM pendonor WHERE id_pendonor = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$id_pendonor])) {
            $message = "Data pendonor berhasil dihapus PERMANEN!";
            $alert_type = "danger";
        } else {
            $message = "Gagal menghapus data pendonor!";
            $alert_type = "danger";
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=pendonor_trash' class='btn btn-primary mt-2'>Kembali ke Data Terhapus</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'pendonor_trash':
        // TAMPILKAN DATA TERHAPUS
        include 'View/pendonor/trash.php';
        break;
        
    case 'pendonor_restore_all':
        // RESTORE SEMUA DATA
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE pendonor SET is_deleted = 0, deleted_at = NULL WHERE is_deleted = 1";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute()) {
            $message = "Semua data pendonor berhasil dikembalikan!";
            $alert_type = "success";
        } else {
            $message = "Gagal mengembalikan data pendonor!";
            $alert_type = "danger";
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=pendonor_trash' class='btn btn-primary mt-2'>Kembali ke Data Terhapus</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'pendonor_permanent_delete_all':
        // HAPUS PERMANEN SEMUA DATA
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "DELETE FROM pendonor WHERE is_deleted = 1";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute()) {
            $message = "Semua data pendonor berhasil dihapus PERMANEN!";
            $alert_type = "danger";
        } else {
            $message = "Gagal menghapus data pendonor!";
            $alert_type = "danger";
        }
        
        include 'View/template/header.php';
        echo "<div class='alert alert-$alert_type m-4'>$message 
              <br><a href='?action=pendonor_trash' class='btn btn-primary mt-2'>Kembali ke Data Terhapus</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'pendonor_riwayat':
        $id = $_GET['id'] ?? '';
        include 'View/template/header.php';
        echo "<div class='alert alert-info m-4'>Riwayat donor pendonor ID: $id - Fitur dalam pengembangan
              <br><a href='?action=pendonor' class='btn btn-primary mt-2'>Kembali ke Data Pendonor</a></div>";
        include 'View/template/footer.php';
        break;
        
    case 'transaksi':
        include 'View/transaksi/index.php';
        break;
        
    case 'stok':
        include 'View/stok/index.php';
        break;
        
    case 'distribusi':
        include 'View/distribusi/index.php';
        break;
        
    default:
        echo "Halaman tidak ditemukan - Action: $action";
        break;
}
?>