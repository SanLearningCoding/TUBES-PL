<?php
// View/stok/edit.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

// Controller should provide $stok and $golongan
$stok = $stok ?? null;
// Controller may supply $golongan; if not, fetch directly
$gol_list = $golongan ?? [];
if (!is_array($gol_list) || count($gol_list) === 0) {
    require_once __DIR__ . '/../../Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
    $stmt->execute();
    $gol_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$stok) {
    echo "<div class='alert alert-danger m-4'>Stok tidak ditemukan!<br><a href='?action=stok' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali</a></div>";
    include Path::template('footer.php');
    exit;
}

?>

<div class="detail-page-header">
    <h1>Edit Stok S<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></h1>
    <a href="?action=stok" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=stok_update" method="POST">
            <input type="hidden" name="id_stok" value="<?= $stok['id_stok'] ?>">
            <div class="mb-3">
                <label class="form-label">Golongan Darah</label>
                <select name="id_gol_darah" class="form-control" required>
                    <?php if (count($gol_list) === 0): ?>
                        <option value="" disabled>Tidak ada data golongan darah. Silakan tambahkan di database atau jalankan seed.</option>
                    <?php else: ?>
                        <?php foreach ($gol_list as $g): ?>
                        <option value="<?= $g['id_gol_darah'] ?>" <?= $g['id_gol_darah'] == $stok['id_gol_darah'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nama_gol_darah']) ?><?= $g['rhesus'] ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Jumlah Kantong</label>
                <input type="number" name="jumlah_kantong" class="form-control" min="1" value="<?= $stok['jumlah_kantong'] ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tanggal Pengujian</label>
                <input type="date" name="tanggal_pengujian" class="form-control" value="<?= htmlspecialchars($stok['tanggal_pengujian'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Tanggal Kadaluarsa</label>
                <input type="date" name="tanggal_kadaluarsa" class="form-control" value="<?= htmlspecialchars($stok['tanggal_kadaluarsa'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Status Uji</label>
                <select name="status_uji" class="form-control">
                    <option value="lolos" <?= $stok['status_uji'] == 'lolos' ? 'selected' : '' ?>>Lolos</option>
                    <option value="gagal" <?= $stok['status_uji'] == 'gagal' ? 'selected' : '' ?>>Gagal</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="tersedia" <?= $stok['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="terpakai" <?= $stok['status'] == 'terpakai' ? 'selected' : '' ?>>Terpakai</option>
                    <option value="kadaluarsa" <?= $stok['status'] == 'kadaluarsa' ? 'selected' : '' ?>>Kadaluarsa</option>
                </select>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn btn-secondary me-2" type="reset">Reset</button>
                <button class="btn" type="submit" style="background: #c62828; color: white; border: none;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
<script>
    (function(){
        const pengujian = document.querySelector("input[name='tanggal_pengujian']");
        const kadaluarsa = document.querySelector("input[name='tanggal_kadaluarsa']");
        function updateMin() {
            if(!pengujian || !kadaluarsa) return;
            const p = pengujian.value;
            if(!p) return;
            const d = new Date(p);
            d.setDate(d.getDate() + 1);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            kadaluarsa.min = `${y}-${m}-${day}`;
            if (kadaluarsa.value && kadaluarsa.value <= pengujian.value) kadaluarsa.value = '';
        }
        pengujian.addEventListener('change', updateMin);
        document.addEventListener('DOMContentLoaded', updateMin);
    })();
</script>
