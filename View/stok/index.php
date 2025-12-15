<?php 
// View/stok/index.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// Pagination & Search
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

$where = "WHERE sd.is_deleted = 0";
if (!empty($search)) {
    $where .= " AND (sd.id_stok LIKE :search OR gd.nama_gol_darah LIKE :search OR sd.status LIKE :search)";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM stok_darah sd
              LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
              " . $where;
$stmtCount = $db->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bindValue(':search', "%$search%");
}
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);

// Get data with pagination
$query = "SELECT sd.*, gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi
         FROM stok_darah sd
         LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
         LEFT JOIN transaksi_donasi td ON sd.id_transaksi = td.id_transaksi
         " . $where . "
         ORDER BY sd.id_stok DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$stok_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Stok Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=stok_trash" class="btn btn-outline-secondary">
            <i class="fas fa-archive me-1"></i> Arsip
        </a>
    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="stok">
            <input type="text" name="search" class="form-control" placeholder="Cari id, golongan darah, atau status...." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn" style="background: #c62828; color: white; border: none;"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=stok" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabel Stok Darah Per Unit -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($search)): ?>
        <p class="text-muted">Menampilkan <?= count($stok_list) ?> dari <?= $total ?> hasil pencarian untuk "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>ID Stok</th>
                        <th>Golongan Darah</th>
                        <th>Tanggal Donasi</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($stok_list) > 0): ?>
                        <?php foreach ($stok_list as $stok): ?>
                        <tr>
                            <td>
                                SD<?= str_pad($stok['id_stok'] ?? 0, 3, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($stok['nama_gol_darah'] ?? 'N/A') ?> <?= htmlspecialchars($stok['rhesus'] ?? '') ?>
                                </span>
                            </td>
                            <td>
                                <?= !empty($stok['tanggal_donasi']) ? date('d/m/Y', strtotime($stok['tanggal_donasi'])) : '-' ?>
                            </td>
                            <td>
                                <?= !empty($stok['tanggal_kadaluarsa']) ? date('d/m/Y', strtotime($stok['tanggal_kadaluarsa'])) : 'N/A' ?>
                            </td>
                            <td>
                                <?php 
                                // Check actual database status first
                                $status = $stok['status'] ?? 'tersedia';
                                $is_expired = (!empty($stok['tanggal_kadaluarsa']) && strtotime($stok['tanggal_kadaluarsa']) < strtotime('today'));
                                
                                if ($is_expired) {
                                    echo '<span class="badge bg-warning text-dark">Kadaluarsa</span>';
                                } elseif ($status === 'terpakai') {
                                    echo '<span class="badge bg-danger">Terpakai</span>';
                                } else {
                                    echo '<span class="badge bg-success">Tersedia</span>';
                                }
                                ?>
                            </td>
                                <td>
                                    <a href="?action=stok_detail&id=<?= $stok['id_stok'] ?>" 
                                    class="btn btn-sm" style="background: #c62828; color: white; border: none;" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- Hapus kondisi if (!$is_expired) -->
                                    <button onclick="deleteItem(<?= $stok['id_stok'] ?>, 'stok_delete', 'stok', event)" class="btn btn-danger btn-sm" title="Arsip">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <!-- Tutup kondisi if (hapus endif) -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada stok darah</td>
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
                        <a class="page-link" href="?action=stok&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=stok&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=stok&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=stok&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=stok&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>

