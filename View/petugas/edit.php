<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

$petugas = $petugas ?? null;
if (!$petugas) {
    echo "<div class='alert alert-danger m-4'>Petugas tidak ditemukan<br><a href='?action=dashboard' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali</a></div>";
    include Path::template('footer.php');
    exit;
}
?>

<div class="detail-page-header">
    <h1>Edit Profil</h1>
    <a href="?action=petugas_profile" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=petugas_update" method="POST">
            <input type="hidden" name="id_petugas" value="<?= $petugas['id_petugas'] ?>">
            <div class="mb-3">
                <label class="form-label">Nama</label>
                <input type="text" name="nama_petugas" class="form-control" value="<?= htmlspecialchars($petugas['nama_petugas']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($petugas['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">No Telepon</label>
                <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($petugas['no_telepon'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Password (kosongkan jika tidak ingin mengubah)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?action=petugas_profile'">Batal</button>
                <button type="submit" class="btn" style="background: #c62828; color: white; border: none;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
