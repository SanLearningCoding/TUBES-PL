<?php 

// View/distribusi/index.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

$where = "WHERE dd.is_deleted = 0";
if (!empty($search)) {
    $where .= " AND (rs.nama_rs LIKE :search OR gd.nama_gol_darah LIKE :search OR dd.status LIKE :search)";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM distribusi_darah dd
              JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
              JOIN stok_darah sd ON dd.id_stok = sd.id_stok
              JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
              " . $where;
$stmtCount = $db->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bindValue(':search', "%$search%");
}
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);

// Ambil distribusi yang belum diarsip (is_deleted = 0) dengan pagination
$query = "SELECT dd.*, rs.nama_rs, sd.id_stok, 
                 gd.nama_gol_darah, gd.rhesus
          FROM distribusi_darah dd
          JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
          JOIN stok_darah sd ON dd.id_stok = sd.id_stok
          JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
          " . $where . "
          ORDER BY dd.tanggal_distribusi DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$distribusi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Distribusi Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=distribusi_create" class="btn btn-create">
            <i class="fas fa-plus me-1"></i>Tambah Distribusi
        </a>
        <a href="?action=rumah_sakit" class="btn btn-create ms-2">
            <i class="fas fa-hospital me-1"></i>Data Rumah Sakit
        </a>
        <a href="?action=distribusi_trash" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-archive me-1"></i> Arsip
        </a>
    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="distribusi">
            <input type="text" name="search" class="form-control" placeholder="Cari rumah sakit, golongan darah, atau status..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn" style="background: #c62828; color: white; border: none;"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=distribusi" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Distribusi</th>
                        <th>Rumah Sakit</th>
                        <th>Golongan Darah</th>
                        <th>Tanggal Distribusi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($distribusi_list) > 0): ?>
                        <?php foreach ($distribusi_list as $distribusi): ?>
                        <tr>
                            <td>D<?= str_pad($distribusi['id_distribusi'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($distribusi['nama_rs']) ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?= htmlspecialchars($distribusi['nama_gol_darah']) ?><?= htmlspecialchars($distribusi['rhesus']) ?>
                                </span>
                            </td>
                            <td>
                                <?= isset($distribusi['tanggal_distribusi']) && $distribusi['tanggal_distribusi']
                                    ? date('d/m/Y', strtotime($distribusi['tanggal_distribusi']))
                                    : '-' ?>
                            </td>
                            <td>
                                <?php 
                                $status = $distribusi['status'] ?? 'dikirim';
                                $status_class = 'bg-secondary';
                                $status_label = 'Menunggu';
                                
                                if ($status === 'dikirim') {
                                    $status_class = 'bg-warning';
                                    $status_label = 'Dikirim';
                                } elseif ($status === 'diterima') {
                                    $status_class = 'bg-success';
                                    $status_label = 'Diterima';
                                } elseif ($status === 'dibatalkan') {
                                    $status_class = 'bg-danger';
                                    $status_label = 'Dibatalkan';
                                }
                                ?>
                                <span class="badge <?= $status_class ?>">
                                    <?= htmlspecialchars($status_label) ?>
                                </span>
                            </td>
                            <td>
                                <a href="?action=distribusi_detail&id=<?= $distribusi['id_distribusi'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?action=distribusi_edit&id=<?= $distribusi['id_distribusi'] ?>" class="btn btn-warning btn-sm ms-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteItem(<?= $distribusi['id_distribusi'] ?>, 'distribusi_delete', 'distribusi', event)" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data distribusi</td>
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
                        <a class="page-link" href="?action=distribusi&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=distribusi&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=distribusi&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=distribusi&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=distribusi&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
