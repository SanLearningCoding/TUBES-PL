<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_pendonor = $_GET['id'] ?? 0;

$query = "SELECT * FROM pendonor WHERE id_pendonor = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_pendonor]);
$pendonor = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika data tidak ditemukan
if (!$pendonor) {
    echo "<div class='alert alert-danger m-4'>Data pendonor tidak ditemukan!
          <br><a href='?action=pendonor' class='btn btn-primary mt-2'>Kembali ke Data Pendonor</a></div>";
    include Path::template('footer.php');
    exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Data Pendonor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=pendonor" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=pendonor_update&id=<?= $pendonor['id_pendonor'] ?>" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               value="<?= htmlspecialchars($pendonor['nama']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="kontak" class="form-label">Kontak *</label>
                        <input type="text" class="form-control" id="kontak" name="kontak" 
                               value="<?= htmlspecialchars($pendonor['kontak']) ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="riwayat_penyakit" class="form-label">Riwayat Penyakit</label>
                <textarea class="form-control" id="riwayat_penyakit" name="riwayat_penyakit" rows="3"><?= htmlspecialchars($pendonor['riwayat_penyakit']) ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="?action=pendonor" class="btn btn-secondary me-md-2">Batal</a>
                <button type="submit" class="btn btn-primary">Update Data</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>