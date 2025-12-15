<?php
// View/stok/create.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// Controller should supply `golongan` list via $golongan variable
$gol_list = $golongan ?? [];
if (!is_array($gol_list) || count($gol_list) === 0) {
    require_once __DIR__ . '/../../Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
    $stmt->execute();
    $gol_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="detail-page-header">
    <h1>Tambah Stok Darah (Pasca Uji)</h1>
    <a href="?action=stok" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=stok_store" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="id_gol_darah" class="form-label">Golongan Darah *</label>
                        <div class="d-flex align-items-center">
                        <select id="id_gol_darah" name="id_gol_darah" class="form-control me-2" required>
                            <option value="">Pilih Golongan</option>
                            <?php if (count($gol_list) === 0): ?>
                                <option value="" disabled>Tidak ada data golongan. Tambahkan data golongan darah di database atau jalankan seed.</option>
                            <?php else: ?>
                                <?php foreach ($gol_list as $g): ?>
                                    <option value="<?= $g['id_gol_darah'] ?>"><?= htmlspecialchars($g['nama_gol_darah']) ?> <?= $g['rhesus'] ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if (count($gol_list) === 0): ?>
                            <a href="?action=seed_golongan" class="btn btn-sm" style="background: #c62828; color: white; border: none;">Seed</a>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal_pengujian" class="form-label">Tanggal Pengujian *</label>
                        <input type="date" id="tanggal_pengujian" name="tanggal_pengujian" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa *</label>
                        <input type="date" id="tanggal_kadaluarsa" name="tanggal_kadaluarsa" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status_uji" class="form-label">Status Uji</label>
                        <select id="status_uji" name="status_uji" class="form-control">
                            <option value="lolos">Lolos</option>
                            <option value="gagal">Gagal</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="jumlah_kantong" class="form-label">Jumlah Kantong</label>
                        <input type="number" id="jumlah_kantong" name="jumlah_kantong" class="form-control" min="1" value="1">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <button type="submit" class="btn" style="background: #c62828; color: white; border: none;">Simpan Stok</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
<script>
    (function(){
        const pengujian = document.getElementById('tanggal_pengujian');
        const kadaluarsa = document.getElementById('tanggal_kadaluarsa');
        function updateMin() {
            if(!pengujian || !kadaluarsa) return;
            const p = pengujian.value;
            if(!p) return;
            // set kadaluarsa min to one day after pengujian to ensure >
            const d = new Date(p);
            d.setDate(d.getDate() + 1);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            kadaluarsa.min = `${y}-${m}-${day}`;
            // if current value is invalid, clear it
            if (kadaluarsa.value && kadaluarsa.value <= pengujian.value) kadaluarsa.value = '';
        }
        pengujian.addEventListener('change', updateMin);
        document.addEventListener('DOMContentLoaded', updateMin);
    })();
</script>
