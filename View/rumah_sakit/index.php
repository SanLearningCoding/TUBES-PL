<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA RUMAH SAKIT DARI DATABASE
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// If the DB schema supports soft deletes, fetch non-deleted; otherwise fetch all
$checkCol = $db->query("SHOW COLUMNS FROM rumah_sakit LIKE 'is_deleted'");
$hasIsDeleted = $checkCol && $checkCol->rowCount() > 0;

$where = $hasIsDeleted ? "WHERE is_deleted = 0" : "";
if (!empty($search)) {
    $where .= ($hasIsDeleted ? " AND " : "WHERE ") . "(nama_rs LIKE :search OR alamat LIKE :search OR kontak LIKE :search)";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM rumah_sakit " . $where;
$stmtCount = $db->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bindValue(':search', "%$search%");
}
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);

// Get data with pagination
$query = ($hasIsDeleted ? "SELECT * FROM rumah_sakit WHERE is_deleted = 0" : "SELECT * FROM rumah_sakit");
if (!empty($search)) {
    $query .= ($hasIsDeleted ? " AND " : " WHERE ") . "(nama_rs LIKE :search OR alamat LIKE :search OR kontak LIKE :search)";
}
$query .= " ORDER BY nama_rs LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rs_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung jumlah distribusi per rumah sakit
// Fallback if distribusi_darah doesn't have is_deleted column
$checkDistribusiCol = $db->query("SHOW COLUMNS FROM distribusi_darah LIKE 'is_deleted'");
$distHasIsDeleted = $checkDistribusiCol && $checkDistribusiCol->rowCount() > 0;
foreach ($rs_list as &$rs) {
    $query_count = $distHasIsDeleted ? "SELECT COUNT(*) as total FROM distribusi_darah WHERE id_rs = ? AND is_deleted = 0" : "SELECT COUNT(*) as total FROM distribusi_darah WHERE id_rs = ?";
    $stmt_count = $db->prepare($query_count);
    $stmt_count->execute([$rs['id_rs']]);
    $rs['jumlah_distribusi'] = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
}
?>

<!-- Flash Message -->
<?php if (isset($_SESSION['flash'])): 
    $flash = $_SESSION['flash'];
    $alertType = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    unset($_SESSION['flash']);
?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show m-3" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Rumah Sakit Mitra</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=rumah_sakit_create" class="btn btn-create">
            <i class="fas fa-plus me-1"></i>Tambah Rumah Sakit
        </a>
        <a href="?action=rumah_sakit_trash" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-archive me-1"></i> Arsip
        </a>
    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="rumah_sakit">
            <input type="text" name="search" class="form-control" placeholder="Cari nama rumah sakit, alamat, atau kontak..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=rumah_sakit" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nama Rumah Sakit</th>
                        <th>Alamat</th>
                        <th>Kontak</th>
                        <th>Jumlah Distribusi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rs_list) > 0): ?>
                        <?php foreach ($rs_list as $rs): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($rs['nama_rs']) ?>
                            </td>
                            <td class="rs-address text-break" style="max-width:360px; white-space: normal;">
                                <?= htmlspecialchars($rs['alamat']) ?>
                            </td>
                            <td class="text-nowrap">
                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($rs['kontak']) ?>
                            </td>
                            <td>
                                <span class="badge" style="background: #c62828;"><?= $rs['jumlah_distribusi'] ?> distribusi</span>
                            </td>
                            <td class="no-wrap-actions">
                                <a href="?action=rumah_sakit_edit&id=<?= $rs['id_rs'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=rumah_sakit_detail&id=<?= $rs['id_rs'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="deleteItem(<?= $rs['id_rs'] ?>, 'rumah_sakit_delete', 'rumah_sakit', event)" class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="py-4">
                                    <i class="fas fa-hospital fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Belum ada data rumah sakit</h5>
                                    <a href="?action=rumah_sakit_create" class="btn btn-create mt-2">
                                        <i class="fas fa-plus me-1"></i>Tambah Rumah Sakit Pertama
                                    </a>
                                </div>
                            </td>
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
                        <a class="page-link" href="?action=rumah_sakit&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=rumah_sakit&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=rumah_sakit&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=rumah_sakit&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=rumah_sakit&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>