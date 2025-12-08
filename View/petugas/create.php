<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');
?>
<div class="detail-page-header">
    <h1>Tambah Petugas</h1>
    <a href="?action=dashboard" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body text-center p-5">
        <h3 class="mb-2">Fitur penambahan petugas dinonaktifkan</h3>
        <p class="text-muted mb-3">Anda tidak dapat menambahkan akun petugas melalui UI ini.</p>
        <a href="?action=dashboard" class="btn btn-pmi">Kembali ke Dashboard</a>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
