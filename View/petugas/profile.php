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
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Profil Admin</h1>
    <div class="btn-toolbar">
        <a href="?action=petugas_edit&id=<?= $petugas['id_petugas'] ?>" class="btn" style="background: #c62828; color: white; border: none;">Edit Profil</a>
    </div>
</div>

<div class="card card-tile">
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th>Nama</th><td><?= htmlspecialchars($petugas['nama_petugas']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($petugas['email']) ?></td></tr>
            <tr><th>No Telepon</th><td><?= htmlspecialchars($petugas['no_telepon'] ?? '-') ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($petugas['status']) ?></td></tr>
        </table>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
