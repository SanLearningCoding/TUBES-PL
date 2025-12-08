<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Model/DistribusiModel.php';

$distribusiModel = new DistribusiModel();

// Get available stok per golongan
$availableStok = $distribusiModel->getAvailableStokForDistribusi();

// Get hospitals
$rumahSakitList = $distribusiModel->getRumahSakit();
?>

<div class="detail-page-header">
    <h1>Tambah Distribusi Darah</h1>
    <a href="?action=distribusi" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=distribusi_store" method="POST">
            <div class="mb-3">
                <label class="form-label">Pilih Golongan Darah <span class="text-danger">*</span></label>
                <div class="alert alert-info">
                    <strong>Petunjuk:</strong> Pilih golongan darah untuk mendistribusi 1 kantong
                </div>
                
                <?php if (empty($availableStok)): ?>
                    <div class="alert alert-warning">Tidak ada stok darah yang tersedia untuk didistribusi</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($availableStok as $stok): ?>
                            <label class="list-group-item">
                                <input type="radio" name="id_stok" value="<?= $stok['id_stok'] ?>" class="form-check-input me-3" required>
                                <strong><?= htmlspecialchars($stok['nama_gol_darah'] ?? '') ?> <?= htmlspecialchars($stok['rhesus'] ?? '') ?></strong>
                                <span class="badge bg-primary ms-2">1 kantong</span>
                                <?php if (!empty($stok['tanggal_kadaluarsa'])): ?>
                                    <span class="text-muted ms-2" style="font-size: 0.9rem;">
                                        (Kadaluarsa: <?= date('d/m/Y', strtotime($stok['tanggal_kadaluarsa'])) ?>)
                                    </span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Rumah Sakit Tujuan <span class="text-danger">*</span></label>
                <select name="id_rs" class="form-control" required>
                    <option value="">-- Pilih Rumah Sakit --</option>
                    <?php foreach ($rumahSakitList as $rs): ?>
                        <option value="<?= $rs['id_rs'] ?>"><?= htmlspecialchars($rs['nama_rs']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal Distribusi <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_distribusi" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn btn-secondary me-2" type="reset">Reset</button>
                <button class="btn btn-primary" type="submit">Distribusi</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
