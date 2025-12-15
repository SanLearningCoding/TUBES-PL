<!-- View/transaksi/edit.php -->
<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

$transaksi = $data['transaksi'] ?? null;
$kegiatans = $data['kegiatans'] ?? [];
$pendonor = $data['pendonor'] ?? [];

if (!$transaksi) {
    echo "<p>Data transaksi tidak ditemukan.</p>";
    include Path::template('footer.php');
    exit;
}
?>

<div class="container">
    <h2>Edit Transaksi Donasi</h2>
    <form method="POST" action="index.php?action=transaksi_update&id=<?php echo $transaksi['id_transaksi']; ?>">
        <div class="mb-3">
            <label for="id_kegiatan" class="form-label">Kegiatan Donasi</label>
            <select class="form-select" id="id_kegiatan" name="id_kegiatan" required>
                <option value="">Pilih Kegiatan...</option>
                <?php foreach ($kegiatans as $keg): ?>
                    <option value="<?php echo $keg['id_kegiatan']; ?>" <?php echo $transaksi['id_kegiatan'] == $keg['id_kegiatan'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($keg['nama_kegiatan']); ?> (<?php echo $keg['tanggal']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="id_pendonor" class="form-label">Pendonor</label>
            <select class="form-select" id="id_pendonor" name="id_pendonor" required>
                <option value="">Pilih Pendonor...</option>
                <?php foreach ($pendonor as $p): ?>
                    <option value="<?php echo $p['id_pendonor']; ?>" <?php echo $transaksi['id_pendonor'] == $p['id_pendonor'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['nama']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="jumlah_kantong" class="form-label">Jumlah Kantong</label>
            <input type="number" class="form-control" id="jumlah_kantong" name="jumlah_kantong" value="<?php echo htmlspecialchars($transaksi['jumlah_kantong']); ?>" min="1" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_donasi" class="form-label">Tanggal Donasi</label>
            <input type="date" class="form-control" id="tanggal_donasi" name="tanggal_donasi" value="<?php echo $transaksi['tanggal_donasi']; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="index.php?action=transaksi" class="btn btn-secondary">Batal</a>
    </form>
</div>

<?php include Path::template('footer.php'); ?>