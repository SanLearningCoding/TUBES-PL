<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Model/DistribusiModel.php';

$database = new Database();
$db = $database->getConnection();
$distribusiModel = new DistribusiModel();

// Get available stok grouped by golongan
$availableStok = $distribusiModel->getAvailableStokForDistribusi();
$golonganGroups = [];
foreach ($availableStok as $stok) {
    $key = $stok['id_gol_darah'] . '-' . $stok['nama_gol_darah'] . '-' . $stok['rhesus'];
    if (!isset($golonganGroups[$key])) {
        $golonganGroups[$key] = [
            'nama_gol_darah' => $stok['nama_gol_darah'],
            'rhesus' => $stok['rhesus'],
            'stok_list' => []
        ];
    }
    $golonganGroups[$key]['stok_list'][] = $stok;
}

// Get hospitals
$rumahSakitList = $distribusiModel->getRumahSakit();
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Distribusi Darah</h1>
    <div class="btn-toolbar">
        <a href="?action=distribusi" class="btn btn-secondary">Kembali</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=distribusi_store" method="POST">
            <div class="mb-3">
                <label class="form-label">Golongan Darah yang Tersedia</label>
                <div class="alert alert-info">
                    <strong>Petunjuk:</strong> Pilih golongan darah, kemudian pilih kantong spesifik dari golongan tersebut.
                </div>
                
                <?php if (empty($golonganGroups)): ?>
                    <div class="alert alert-warning">Tidak ada stok darah yang tersedia untuk didistribusi</div>
                <?php else: ?>
                    <?php foreach ($golonganGroups as $groupKey => $group): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong><?= htmlspecialchars($group['nama_gol_darah']) ?> <?= htmlspecialchars($group['rhesus']) ?></strong>
                                <span class="badge bg-primary ms-2"><?= count($group['stok_list']) ?> kantong</span>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php foreach ($group['stok_list'] as $stok): ?>
                                        <label class="list-group-item">
                                            <input type="radio" name="id_stok" value="<?= $stok['id_stok'] ?>" class="form-check-input me-2">
                                            <strong>Kantong S<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></strong>
                                            <?php if ($stok['tanggal_kadaluarsa']): ?>
                                                <span class="text-muted ms-2">(Kadaluarsa: <?= date('d/m/Y', strtotime($stok['tanggal_kadaluarsa'])) ?>)</span>
                                            <?php endif; ?>
                                            <span class="badge bg-success ms-2"><?= htmlspecialchars($stok['status_uji']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
