<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM pendonor WHERE is_deleted = 0 ORDER BY id_pendonor DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pendonor_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Pendonor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=pendonor_create" class="btn btn-success me-2">
            <i class="fas fa-plus me-1"></i>Tambah Pendonor
        </a>
        <a href="?action=pendonor_trash" class="btn btn-warning">
            <i class="fas fa-trash-restore me-1"></i>Data Terhapus
        </a>
    </div>
</div>

<!-- Table Pendonor -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Riwayat Penyakit</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendonor_list) > 0): ?>
                        <?php foreach ($pendonor_list as $pendonor): ?>
                        <tr>
                            <td>P<?= str_pad($pendonor['id_pendonor'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($pendonor['nama']) ?></td>
                            <td><?= htmlspecialchars($pendonor['kontak']) ?></td>
                            <td><?= htmlspecialchars($pendonor['riwayat_penyakit']) ?></td>
                            <td>
                                <a href="?action=pendonor_edit&id=<?= $pendonor['id_pendonor'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=pendonor_riwayat&id=<?= $pendonor['id_pendonor'] ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-history"></i>
                                </a>
                                <button onclick="confirmDelete(<?= $pendonor['id_pendonor'] ?>)" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Belum ada data pendonor</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data pendonor ini?')) {
        window.location.href = '?action=pendonor_soft_delete&id=' + id;
    }
}
</script>

<?php include Path::template('footer.php'); ?>