<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA YANG DIHAPUS (SOFT DELETE)
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM pendonor WHERE is_deleted = 1 ORDER BY deleted_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$deleted_pendonor = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Pendonor Terhapus</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=pendonor" class="btn btn-primary me-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <?php if (count($deleted_pendonor) > 0): ?>
        <button onclick="confirmRestoreAll()" class="btn btn-success me-2">
            <i class="fas fa-trash-restore me-1"></i>Restore Semua
        </button>
        <button onclick="confirmDeleteAll()" class="btn btn-danger">
            <i class="fas fa-trash me-1"></i>Hapus Permanen Semua
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($deleted_pendonor) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Dihapus Pada</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_pendonor as $pendonor): ?>
                    <tr>
                        <td>P<?= str_pad($pendonor['id_pendonor'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($pendonor['nama']) ?></td>
                        <td><?= htmlspecialchars($pendonor['kontak']) ?></td>
                        <td><?= $pendonor['deleted_at'] ? date('d/m/Y H:i', strtotime($pendonor['deleted_at'])) : '-' ?></td>
                        <td>
                            <button onclick="confirmRestore(<?= $pendonor['id_pendonor'] ?>)" class="btn btn-success btn-sm">
                                <i class="fas fa-trash-restore"></i> Restore
                            </button>
                            <button onclick="confirmPermanentDelete(<?= $pendonor['id_pendonor'] ?>)" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Hapus Permanen
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-trash fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Tidak ada data yang dihapus</h5>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmRestore(id) {
    if (confirm('Apakah Anda yakin ingin mengembalikan data pendonor ini?')) {
        window.location.href = '?action=pendonor_restore&id=' + id;
    }
}

function confirmPermanentDelete(id) {
    if (confirm('PERINGATAN: Data akan dihapus PERMANEN dan tidak dapat dikembalikan!\n\nApakah Anda yakin?')) {
        window.location.href = '?action=pendonor_permanent_delete&id=' + id;
    }
}

function confirmRestoreAll() {
    if (confirm('Apakah Anda yakin ingin mengembalikan SEMUA data pendonor yang dihapus?')) {
        window.location.href = '?action=pendonor_restore_all';
    }
}

function confirmDeleteAll() {
    if (confirm('PERINGATAN: SEMUA data akan dihapus PERMANEN dan tidak dapat dikembalikan!\n\nApakah Anda yakin?')) {
        window.location.href = '?action=pendonor_permanent_delete_all';
    }
}
</script>

<?php include Path::template('footer.php'); ?>