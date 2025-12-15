<?php 
// View/kegiatan/index.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA KEGIATAN DARI DATABASE
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// If schema supports soft-delete, only include non-deleted. Otherwise return all.
$check = $db->query("SHOW COLUMNS FROM kegiatan_donasi LIKE 'is_deleted'");
$hasIsDeleted = $check && $check->rowCount() > 0;

$where = $hasIsDeleted ? "WHERE is_deleted = 0" : "";
if (!empty($search)) {
    $where .= ($hasIsDeleted ? " AND " : "WHERE ") . "(nama_kegiatan LIKE :search OR lokasi LIKE :search)";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM kegiatan_donasi " . $where;
$stmtCount = $db->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bindValue(':search', "%$search%");
}
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);

// Get data with pagination
$query = ($hasIsDeleted ? "SELECT * FROM kegiatan_donasi WHERE is_deleted = 0" : "SELECT * FROM kegiatan_donasi");
if (!empty($search)) {
    $query .= ($hasIsDeleted ? " AND " : " WHERE ") . "(nama_kegiatan LIKE :search OR lokasi LIKE :search)";
}
$query .= " ORDER BY tanggal DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$kegiatan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kegiatan Donor Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=kegiatan_create" class="btn btn-create">
            <i class="fas fa-plus me-1"></i>Tambah Kegiatan
        </a>
        <a href="?action=kegiatan_trash" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-archive me-1"></i> Arsip
        </a>
    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="kegiatan">
            <input type="text" name="search" class="form-control" placeholder="Cari nama kegiatan atau lokasi..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=kegiatan" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($search)): ?>
        <p class="text-muted">Menampilkan <?= count($kegiatan_list) ?> dari <?= $total ?> hasil pencarian untuk "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nama Kegiatan</th>
                        <th>Tanggal</th>
                        <th>Lokasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($kegiatan_list) > 0): ?>
                        <?php foreach ($kegiatan_list as $kegiatan): ?>
                        <tr>
                            <td><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></td>
                            <td><?= date('d/m/Y', strtotime($kegiatan['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($kegiatan['lokasi']) ?></td>
                            <td>
                                <a href="?action=kegiatan_edit&id=<?= $kegiatan['id_kegiatan'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=kegiatan_detail&id=<?= $kegiatan['id_kegiatan'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                    <button onclick="deleteItem(<?= $kegiatan['id_kegiatan'] ?>, 'kegiatan_delete', 'kegiatan', event)" class="btn btn-danger btn-sm ms-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Belum ada kegiatan donor</td>
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
                        <a class="page-link" href="?action=kegiatan&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=kegiatan&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=kegiatan&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=kegiatan&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=kegiatan&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>