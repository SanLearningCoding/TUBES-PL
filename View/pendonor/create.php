<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Pendonor Baru</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=pendonor" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=pendonor_store" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" id="nama" name="nama" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="kontak" class="form-label">Kontak *</label>
                        <input type="text" class="form-control" id="kontak" name="kontak" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="riwayat_penyakit" class="form-label">Riwayat Penyakit</label>
                <textarea class="form-control" id="riwayat_penyakit" name="riwayat_penyakit" rows="3" placeholder="Kosongkan jika tidak ada"></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <button type="submit" class="btn btn-primary">Simpan Pendonor</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>