<?php
//  View/stok/trash.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_gol = $_GET['gol_id'] ?? 0;
$stmt = $db->prepare("SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah WHERE id_gol_darah = ?");
$stmt->execute([$id_gol]);
$gol = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$gol) {
    echo "<div class='alert alert-danger m-4'>Golongan darah tidak ditemukan.<br><a href='?action=stok' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali</a></div>";
    include Path::template('footer.php');
    exit;
}

$stmt = $db->prepare("SELECT COALESCE(SUM(jumlah_kantong), 0) as total FROM stok_darah WHERE id_gol_darah = ? AND status = 'tersedia' AND status_uji = 'lolos'");
$stmt->execute([$id_gol]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$current_total = intval($row['total']);
?>

<div class="detail-page-header">
    <h1>Update Jumlah Stok - <?= htmlspecialchars($gol['nama_gol_darah']) ?><?= $gol['rhesus'] ?></h1>
    <a href="?action=stok" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=stok_set_group_total" method="POST">
            <input type="hidden" name="id_gol_darah" value="<?= $gol['id_gol_darah'] ?>">
            <div class="mb-3">
                <label class="form-label">Golongan</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($gol['nama_gol_darah']) ?><?= $gol['rhesus'] ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Jumlah Saat Ini (Kantong)</label>
                <input type="text" class="form-control" value="<?= $current_total ?> kantong" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Target Jumlah (Kantong)</label>
                <input type="number" name="target_volume_ml" class="form-control" min="0" value="<?= $current_total ?>">
                <small class="form-text text-muted">Isi jumlah kantong yang diinginkan. Sistem akan menambah atau mengurangi stok sesuai perbedaan.</small>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="?action=stok" class="btn btn-secondary me-md-2">Batal</a>
                <button type="submit" class="btn" style="background: #c62828; color: white; border: none;">Simpan Target</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
