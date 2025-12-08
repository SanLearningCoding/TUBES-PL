<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA UNTUK DROPDOWN
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Ambil golongan darah yang memiliki stok tersedia (jumlah stok > 0) untuk opsi distribusi per golongan
$query_gol = "SELECT gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus, COALESCE(SUM(sd.volume_ml),0) AS total_volume
              FROM golongan_darah gd
              JOIN stok_darah sd ON sd.id_gol_darah = gd.id_gol_darah
                  AND sd.status = 'tersedia' AND sd.status_uji = 'lolos' AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa > CURDATE())
              GROUP BY gd.id_gol_darah
              HAVING total_volume > 0
              ORDER BY gd.nama_gol_darah";
$stmt_gol = $db->prepare($query_gol);
$stmt_gol->execute();
$gol_list = $stmt_gol->fetchAll(PDO::FETCH_ASSOC);

// Hanya ambil stok yang aktif (is_deleted = 0) dan masih tersedia
$query_stok = "SELECT sd.id_stok, gd.nama_gol_darah, gd.rhesus, sd.tanggal_kadaluarsa, sd.volume_ml
               FROM stok_darah sd
               LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
               WHERE sd.status = 'tersedia' AND sd.status_uji = 'lolos' AND sd.is_deleted = 0 AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa > CURDATE())
               ORDER BY gd.nama_gol_darah, sd.tanggal_kadaluarsa";
$stmt_stok = $db->prepare($query_stok);
$stmt_stok->execute();
$stok_list = $stmt_stok->fetchAll(PDO::FETCH_ASSOC);

$checkRSCol = $db->query("SHOW COLUMNS FROM rumah_sakit LIKE 'is_deleted'");
$rsHasIsDeleted = $checkRSCol && $checkRSCol->rowCount() > 0;
$query_rs = $rsHasIsDeleted ? "SELECT id_rs, nama_rs FROM rumah_sakit WHERE is_deleted = 0 ORDER BY nama_rs" : "SELECT id_rs, nama_rs FROM rumah_sakit ORDER BY nama_rs";
$stmt_rs = $db->prepare($query_rs);
$stmt_rs->execute();
$rs_list = $stmt_rs->fetchAll(PDO::FETCH_ASSOC);
// Prefill from GET params if present
$id_stok_prefill = $_GET['id_stok'] ?? '';
$id_rs_prefill = $_GET['rs_id'] ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Distribusi Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=distribusi" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=distribusi_store" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="distribusi_type" id="by_gol" value="golongan" <?= $id_stok_prefill ? '' : 'checked' ?> >
                            <label class="form-check-label" for="by_gol">Berdasarkan Golongan</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="distribusi_type" id="by_stok" value="stok" <?= $id_stok_prefill ? 'checked' : '' ?> >
                            <label class="form-check-label" for="by_stok">Per Stok</label>
                        </div>

                        <div id="form_by_gol" class="mt-2">
                            <label for="id_gol_darah" class="form-label">Golongan Darah *</label>
                            <select class="form-control" id="id_gol_darah" name="id_gol_darah">
                                <option value="">Pilih Golongan</option>
                                <?php foreach ($gol_list as $g): ?>
                                <option value="<?= $g['id_gol_darah'] ?>" data-total-volume="<?= (int)$g['total_volume'] ?>" <?= isset($_GET['id_gol_darah']) && $_GET['id_gol_darah'] == $g['id_gol_darah'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nama_gol_darah']) ?> <?= $g['rhesus'] ?> (Tersedia: <?= (int)$g['total_volume'] ?> ml)</option>
                                <?php endforeach; ?>
                            </select>
                            <!-- Volume input removed here: use the shared volume input on the right column.
                                 For group-based distribution, the shared 'volume_ml' input is required. -->
                        </div>

                        <div id="form_by_stok" class="mt-2" style="display:none;">
                            <label for="id_stok" class="form-label">Stok Darah</label>
                            <select class="form-control" id="id_stok" name="id_stok">
                            <option value="">Pilih Stok Darah</option>
                            <?php foreach ($stok_list as $stok): 
                                $expire_date = (isset($stok['tanggal_kadaluarsa']) && $stok['tanggal_kadaluarsa']) ? date('d/m/Y', strtotime($stok['tanggal_kadaluarsa'])) : '-';
                            ?>
                                                        <option value="<?= $stok['id_stok'] ?>" data-available="<?= (int)$stok['volume_ml'] ?>" <?= $id_stok_prefill && $id_stok_prefill == $stok['id_stok'] ? 'selected' : '' ?>>
                                <?= $stok['nama_gol_darah'] ?><?= $stok['rhesus'] ?> 
                                (Kadaluarsa: <?= $expire_date ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (count($stok_list) == 0): ?>
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle"></i> Tidak ada stok darah yang tersedia
                        </div>
                        <?php endif; ?>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        const byGol = document.getElementById('by_gol');
                        const byStok = document.getElementById('by_stok');
                        const formGol = document.getElementById('form_by_gol');
                        const formStok = document.getElementById('form_by_stok');
                        function toggleForms(){
                            if(byGol.checked){ formGol.style.display = 'block'; formStok.style.display = 'none'; }
                            else { formGol.style.display = 'none'; formStok.style.display = 'block'; }
                        }
                        byGol.addEventListener('change', toggleForms);
                        byStok.addEventListener('change', toggleForms);
                        toggleForms();
                        // Ensure the shared volume input is required for group-based distribution and optional for per-stock
                        const sharedVolume = document.getElementById('volume_ml_stok');
                        function updateVolumeRequired(){
                            if(byGol.checked){
                                sharedVolume.required = true;
                                sharedVolume.placeholder = '';
                            } else {
                                sharedVolume.required = false;
                                sharedVolume.placeholder = 'Kosongkan untuk pakai seluruh stok';
                            }
                        }
                        byGol.addEventListener('change', updateVolumeRequired);
                        byStok.addEventListener('change', updateVolumeRequired);
                        updateVolumeRequired();
                    });
                    </script>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="id_rs" class="form-label">Rumah Sakit *</label>
                        <select class="form-control" id="id_rs" name="id_rs" required>
                            <option value="">Pilih Rumah Sakit</option>
                            <?php foreach ($rs_list as $rs): ?>
                            <option value="<?= $rs['id_rs'] ?>" <?= $id_rs_prefill && $id_rs_prefill == $rs['id_rs'] ? 'selected' : '' ?>><?= htmlspecialchars($rs['nama_rs']) ?></option>
                            <?php endforeach; ?>
                        </select>
                            <div class="mb-3 mt-2">
                                <label for="volume_ml_stok" class="form-label">Jumlah (ml)</label>
                                <input type="number" min="1" name="volume_ml" class="form-control" id="volume_ml_stok" placeholder="Kosongkan untuk pakai seluruh stok">
                            </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal_distribusi" class="form-label">Tanggal Distribusi *</label>
                        <input type="date" class="form-control" id="tanggal_distribusi" name="tanggal_distribusi" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status_pengiriman" class="form-label">Status Pengiriman *</label>
                        <select class="form-control" id="status_pengiriman" name="status_pengiriman" required>
                            <option value="Menunggu">Menunggu</option>
                            <option value="Dalam Perjalanan">Dalam Perjalanan</option>
                            <option value="Terkirim">Terkirim</option>
                            <option value="Dibatalkan">Dibatalkan</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <?php $has_any_stock = (count($stok_list) > 0) || (count($gol_list) > 0); ?>
                <button type="submit" class="btn btn-primary" <?= !$has_any_stock ? 'disabled' : '' ?>>
                    Simpan Distribusi
                </button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var form = document.querySelector('form[action="?action=distribusi_store"]');
    if(!form) return;
    form.addEventListener('submit', function(e){
        var type = document.querySelector('input[name="distribusi_type"]:checked').value;
        var id_rs = document.getElementById('id_rs').value;
        if(!id_rs){ alert('Pilih Rumah Sakit tujuan'); e.preventDefault(); return; }
        var volumeInput = document.getElementById('volume_ml_stok');
        var volumeVal = volumeInput.value ? parseInt(volumeInput.value) : null;
        if(type === 'golongan'){
            var id_gol = document.getElementById('id_gol_darah').value;
            if(!id_gol){ alert('Pilih golongan darah'); e.preventDefault(); return; }
            if(!volumeVal || volumeVal <= 0){ alert('Masukkan volume (ml) yang valid untuk distribusi berdasarkan golongan'); e.preventDefault(); return; }
            var option = document.querySelector('#id_gol_darah option[value="' + id_gol + '"]');
            var totalAvailable = option ? parseInt(option.getAttribute('data-total-volume') || 0) : 0;
            if(volumeVal > totalAvailable){ alert('Volume melebihi stok golongan (' + totalAvailable + ' ml)'); e.preventDefault(); return; }
        } else {
            var id_stok = document.getElementById('id_stok').value;
            if(!id_stok){ alert('Pilih stok darah'); e.preventDefault(); return; }
            if(volumeVal){
                var option = document.querySelector('#id_stok option[value="' + id_stok + '"]');
                var available = option ? parseInt(option.getAttribute('data-available') || 0) : 0;
                if(volumeVal > available){ alert('Volume melebihi stok kantong ('+available+' ml)'); e.preventDefault(); return; }
            }
        }
    });
});
</script>