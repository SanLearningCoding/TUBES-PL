<?php 
// View/kegiatan/edit.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA KEGIATAN BERDASARKAN ID
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_kegiatan = $_GET['id'] ?? 0;

$query = "SELECT * FROM kegiatan_donasi WHERE id_kegiatan = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_kegiatan]);
$kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kegiatan) {
    echo "<div class='alert alert-danger m-4'>Data kegiatan tidak ditemukan!
          <br><a href='?action=kegiatan' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Kegiatan</a></div>";
    include Path::template('footer.php');
    exit;
}
?>

<div class="detail-page-header">
    <h1>Edit Kegiatan Donor</h1>
    <a href="?action=kegiatan" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=kegiatan_update" method="POST"> <!-- Ganti action, hapus ?id=... -->
            <!-- TAMBAHKAN: Input tersembunyi untuk id_kegiatan -->
            <input type="hidden" name="id_kegiatan" value="<?= $kegiatan['id_kegiatan'] ?>">
            <!-- END TAMBAHAN -->

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_kegiatan" class="form-label">Nama Kegiatan *</label>
                        <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" 
                               value="<?= htmlspecialchars($kegiatan['nama_kegiatan']) ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal" class="form-label">Tanggal Kegiatan *</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" 
                               value="<?= $kegiatan['tanggal'] ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="lokasi" class="form-label">Lokasi Kegiatan *</label>
                <input type="text" class="form-control" id="lokasi" name="lokasi" 
                       value="<?= htmlspecialchars($kegiatan['lokasi']) ?>" required>
            </div>

            <!-- TAMBAHKAN: Input untuk keterangan (opsional), bisa sebagai textarea -->
            <div class="mb-3">
                <label for="keterangan" class="form-label">Keterangan</label>
                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($kegiatan['keterangan'] ?? '') ?></textarea>
            </div>
            <!-- END TAMBAHAN -->
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="?action=kegiatan" class="btn btn-secondary me-md-2">Batal</a>
                <button type="submit" class="btn" style="background: #c62828; color: white; border: none;">Update Kegiatan</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>