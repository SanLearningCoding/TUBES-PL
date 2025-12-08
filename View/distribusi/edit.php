<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DISTRIBUSI BERDASARKAN ID
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_distribusi = $_GET['id'] ?? 0;

// Data distribusi
$query = "SELECT dd.*, rs.nama_rs, sd.id_stok, gd.nama_gol_darah, gd.rhesus
          FROM distribusi_darah dd
          JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
          JOIN stok_darah sd ON dd.id_stok = sd.id_stok
          JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
          WHERE dd.id_distribusi = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_distribusi]);
$distribusi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$distribusi) {
    echo "<div class='alert alert-danger m-4'>Data distribusi tidak ditemukan!
          <br><a href='?action=distribusi' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Distribusi</a></div>";
    include Path::template('footer.php');
    exit;
}

$checkRSCol = $db->query("SHOW COLUMNS FROM rumah_sakit LIKE 'is_deleted'");
$rsHasIsDeleted = $checkRSCol && $checkRSCol->rowCount() > 0;
$query_rs = $rsHasIsDeleted ? "SELECT id_rs, nama_rs FROM rumah_sakit WHERE is_deleted = 0 ORDER BY nama_rs" : "SELECT id_rs, nama_rs FROM rumah_sakit ORDER BY nama_rs";
$stmt_rs = $db->prepare($query_rs);
$stmt_rs->execute();
$rs_list = $stmt_rs->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1>Edit Distribusi Darah</h1>
    <a href="?action=distribusi" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Stok darah: <strong><?= $distribusi['nama_gol_darah'] ?><?= $distribusi['rhesus'] ?></strong> 
            (ID Stok: <?= $distribusi['id_stok'] ?>)
        </div>
        
        <form action="?action=distribusi_update&id=<?= $distribusi['id_distribusi'] ?>" method="POST">
            <div class="row g-1">
                <div class="col-sm-6 col-md-3">
                    <div class="mb-1">
                        <label class="form-label" style="font-size: 0.9rem; margin-bottom: 0.25rem;">Golongan Darah</label>
                        <input type="text" class="form-control form-control-sm" 
                               value="<?= $distribusi['nama_gol_darah'] ?><?= $distribusi['rhesus'] ?>" readonly>
                        <input type="hidden" name="id_stok" value="<?= $distribusi['id_stok'] ?>">
                    </div>
                </div>
                <div class="col-sm-6 col-md-4">
                    <div class="mb-1">
                        <label for="id_rs" class="form-label" style="font-size: 0.9rem; margin-bottom: 0.25rem;">Rumah Sakit *</label>
                        <select class="form-control form-control-sm" id="id_rs" name="id_rs" required>
                            <?php foreach ($rs_list as $rs): ?>
                            <option value="<?= $rs['id_rs'] ?>" <?= $rs['id_rs'] == $distribusi['id_rs'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rs['nama_rs']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="mb-1">
                        <label for="tanggal_distribusi" class="form-label" style="font-size: 0.9rem; margin-bottom: 0.25rem;">Tanggal Distribusi *</label>
                        <input type="date" class="form-control form-control-sm" id="tanggal_distribusi" name="tanggal_distribusi" 
                               value="<?= $distribusi['tanggal_distribusi'] ?>" required>
                    </div>
                </div>
                <div class="col-sm-6 col-md-2">
                    <div class="mb-1">
                        <label for="status" class="form-label" style="font-size: 0.9rem; margin-bottom: 0.25rem;">Status *</label>
                        <select class="form-control form-control-sm" id="status" name="status" required>
                            <option value="dikirim" <?= $distribusi['status'] == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                            <option value="diterima" <?= $distribusi['status'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                            <option value="dibatalkan" <?= $distribusi['status'] == 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-2">
                <a href="?action=distribusi" class="btn btn-secondary btn-sm me-md-2">Batal</a>
                <button type="submit" class="btn btn-primary btn-sm">Update Distribusi</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>