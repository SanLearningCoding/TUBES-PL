<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA RUMAH SAKIT BERDASARKAN ID
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_rs = $_GET['id'] ?? 0;

$query = "SELECT * FROM rumah_sakit WHERE id_rs = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_rs]);
$rs = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rs) {
    echo "<div class='alert alert-danger m-4'>Data rumah sakit tidak ditemukan!
          <br><a href='?action=rumah_sakit' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Rumah Sakit</a></div>";
    include Path::template('footer.php');
    exit;
}
?>

<div class="detail-page-header">
    <h1>Edit Rumah Sakit Mitra</h1>
    <a href="?action=rumah_sakit" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=rumah_sakit_update&id=<?= $rs['id_rs'] ?>" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_rs" class="form-label">Nama Rumah Sakit *</label>
                        <input type="text" class="form-control" id="nama_rs" name="nama_rs" 
                               value="<?= htmlspecialchars($rs['nama_rs']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                           <label for="kontak" class="form-label">Kontak (angka saja) *</label>
                           <input type="tel" pattern="\d+" class="form-control" id="kontak" name="kontak" 
                               value="<?= htmlspecialchars($rs['kontak']) ?>" required oninput="this.value = this.value.replace(/\D/g,'')" maxlength="20">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat Lengkap *</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" required><?= htmlspecialchars($rs['alamat']) ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="?action=rumah_sakit" class="btn btn-secondary me-md-2">Batal</a>
                <button type="submit" class="btn" style="background: #c62828; color: white; border: none;">Update Rumah Sakit</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>