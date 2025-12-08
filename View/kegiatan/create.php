<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 
?>

<div class="detail-page-header">
    <h1>Tambah Kegiatan Donor</h1>
    <a href="?action=kegiatan" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=kegiatan_store" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_kegiatan" class="form-label">Nama Kegiatan *</label>
                        <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" 
                               placeholder="Contoh: Donor Darah Rutin PMI" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal" class="form-label">Tanggal Kegiatan *</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="lokasi" class="form-label">Lokasi Kegiatan *</label>
                <input type="text" class="form-control" id="lokasi" name="lokasi" 
                       placeholder="Contoh: Kantor PMI Pusat, Balikpapan" required>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <button type="submit" class="btn btn-primary">Simpan Kegiatan</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>