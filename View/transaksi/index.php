<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA TRANSAKSI DARI DATABASE
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// Check if `id_gol_darah` exists on `pendonor` before joining to avoid SQL errors
$checkSql = "SELECT COUNT(*) FROM information_schema.columns 
             WHERE table_schema = DATABASE() 
               AND table_name = 'pendonor' 
               AND column_name = 'id_gol_darah'";
$stmtCheck = $db->prepare($checkSql);
$stmtCheck->execute();
$hasGolColumn = (int) $stmtCheck->fetchColumn() > 0;

$where = "WHERE td.is_deleted = 0";
if (!empty($search)) {
    $where .= " AND (p.nama LIKE :search OR kd.nama_kegiatan LIKE :search OR pt.nama_petugas LIKE :search)";
}

// AMBIL DATA TRANSAKSI, HANYA YANG TIDAK DI-SOFT DELETE
if ($hasGolColumn) {
    $countQuery = "SELECT COUNT(*) as total FROM transaksi_donasi td
                   LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                   LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                   LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah
                   LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
                   " . $where;
    
    $query = "SELECT td.*, 
                     p.nama AS nama_pendonor, 
                     kd.nama_kegiatan, 
                     gd.nama_gol_darah, 
                     gd.rhesus,
                     pt.nama_petugas
              FROM transaksi_donasi td
              LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
              LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
              LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah
              LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
              " . $where . "
              ORDER BY td.tanggal_donasi DESC
              LIMIT :limit OFFSET :offset";
} else {
    $countQuery = "SELECT COUNT(*) as total FROM transaksi_donasi td
                   LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                   LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
                   LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
                   " . $where;
    
    $query = "SELECT td.*, 
                     p.nama AS nama_pendonor, 
                     kd.nama_kegiatan,
                     pt.nama_petugas
              FROM transaksi_donasi td
              LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
              LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
              LEFT JOIN petugas pt ON td.id_petugas = pt.id_petugas
              " . $where . "
              ORDER BY td.tanggal_donasi DESC
              LIMIT :limit OFFSET :offset";
}

// Count total
$stmtCount = $db->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bindValue(':search', "%$search%");
}
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);

// Get data
$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Transaksi Donasi</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=transaksi_create" class="btn btn-create">
            <i class="fas fa-plus me-1"></i>Transaksi Baru
        </a>
        <a href="?action=kegiatan" class="btn btn-create ms-2">
            <i class="fas fa-calendar me-1"></i>Kegiatan Donor
        </a>
        <a href="?action=transaksi_trash" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-archive me-1"></i>Arsip
        </a>
    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="transaksi">
            <input type="text" name="search" class="form-control" placeholder="Cari nama pendonor, kegiatan, atau petugas..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn" style="background: #c62828; color: white; border: none;"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=transaksi" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($search)): ?>
        <p class="text-muted">Menampilkan <?= count($transaksi_list) ?> dari <?= $total ?> hasil pencarian untuk "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Pendonor</th>
                        <th>Tanggal Donor</th>
                        <th>Kegiatan</th>
                        <th>Jumlah Kantong</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksi_list) > 0): ?>
                        <?php foreach ($transaksi_list as $transaksi): ?>
                        <tr>
                            <td>T<?= str_pad($transaksi['id_transaksi'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <?= htmlspecialchars($transaksi['nama_pendonor']) ?>
                                <?php if (!empty($transaksi['nama_gol_darah'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($transaksi['nama_gol_darah']) ?> <?= htmlspecialchars($transaksi['rhesus'] ?? '') ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($transaksi['tanggal_donasi'])) ?></td>
                            <td><?= htmlspecialchars($transaksi['nama_kegiatan']) ?></td>
                            <td><?= (int)$transaksi['jumlah_kantong'] ?> kantong</td>
                            <td class="no-wrap-actions">
                                <a href="?action=transaksi_detail&id=<?= $transaksi['id_transaksi'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="deleteItem(<?= $transaksi['id_transaksi'] ?>, 'transaksi_delete', 'transaksi', event)" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada transaksi donasi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=transaksi&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
