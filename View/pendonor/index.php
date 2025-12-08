<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DARI DATABASE - HANYA YANG TIDAK DIHAPUS
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Pagination & Search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// Check which columns exist
$checkSql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor'";
$stmtCheck = $db->prepare($checkSql);
$stmtCheck->execute();
$existingCols = array_map(function($r) { return $r['COLUMN_NAME']; }, $stmtCheck->fetchAll(PDO::FETCH_ASSOC));

// Build dynamic query with only existing columns
$selectCols = [];
$requiredCols = ['id_pendonor', 'nama', 'kontak', 'id_gol_darah', 'is_deleted', 'riwayat_penyakit', 'is_layak', 'other_illness'];
$screeningCols = ['has_hepatitis_b', 'has_hepatitis_c', 'has_aids', 'has_hemofilia', 'has_sickle_cell', 'has_thalassemia', 'has_leukemia', 'has_lymphoma', 'has_myeloma', 'has_cjd'];

foreach ($requiredCols as $col) {
    if (in_array($col, $existingCols)) {
        $selectCols[] = 'p.' . $col;
    }
}
foreach ($screeningCols as $col) {
    if (in_array($col, $existingCols)) {
        $selectCols[] = 'p.' . $col;
    }
}

// Add golongan columns if exist
$selectCols[] = 'gd.nama_gol_darah';
$selectCols[] = 'gd.rhesus';

$where = "WHERE p.is_deleted = 0";
if (!empty($search)) {
    $where .= " AND (p.nama LIKE :search OR p.kontak LIKE :search OR gd.nama_gol_darah LIKE :search)";
}

// Total records untuk pagination
$countQuery = "SELECT COUNT(*) as total FROM pendonor p LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah " . $where;
$stmtCount = $db->prepare($countQuery);
if (!empty($search)) {
    $stmtCount->bindValue(':search', "%$search%");
}
$stmtCount->execute();
$total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $items_per_page);

// Ambil data dengan pagination
$query = "SELECT " . implode(', ', $selectCols) . " FROM pendonor p
          LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah
          " . $where . " ORDER BY p.id_pendonor DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pendonor_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Flash Message -->
<?php if (isset($_SESSION['flash'])): 
    $flash = $_SESSION['flash'];
    $alertType = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    unset($_SESSION['flash']);
?>
    <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible fade show" role="alert" style="margin-bottom: 1.5rem; margin-top: 1rem;">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Data Pendonor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=pendonor_create" class="btn btn-create">
            <i class="fas fa-plus me-1"></i>Tambah Pendonor
        </a>
        <a href="?action=pendonor_trash" class="btn btn-outline-secondary ms-2">
            <i class="fas fa-archive me-1"></i> Arsip
        </a>

    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="pendonor">
            <input type="text" name="search" class="form-control" placeholder="Cari nama, kontak, atau golongan darah..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn" style="background: #c62828; color: white; border: none;"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=pendonor" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Table Pendonor -->
<div class="card">
    <div class="card-body">
        <?php if (!empty($search)): ?>
        <p class="text-muted">Menampilkan <?= count($pendonor_list) ?> dari <?= $total ?> hasil pencarian untuk "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Golongan</th>
                        <th>Status Kesehatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendonor_list) > 0): ?>
                        <?php foreach ($pendonor_list as $pendonor): ?>
                        <tr data-id="<?= $pendonor['id_pendonor'] ?>">
                            <td>P<?= str_pad($pendonor['id_pendonor'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= htmlspecialchars($pendonor['nama']) ?></td>
                            <td><?= htmlspecialchars($pendonor['kontak']) ?></td>
                            <td><?= htmlspecialchars($pendonor['nama_gol_darah'] ?? 'N/A') ?> <?= htmlspecialchars($pendonor['rhesus'] ?? '') ?></td>
                            <td>
                                <?php 
                                $disease_map = [
                                    'has_hepatitis_b' => 'Hepatitis B',
                                    'has_hepatitis_c' => 'Hepatitis C',
                                    'has_aids' => 'AIDS',
                                    'has_hemofilia' => 'Hemofilia',
                                    'has_sickle_cell' => 'Sel Sabit',
                                    'has_thalassemia' => 'Thalasemia',
                                    'has_leukemia' => 'Leukemia',
                                    'has_lymphoma' => 'Limfoma',
                                    'has_myeloma' => 'Myeloma',
                                    'has_cjd' => 'CJD'
                                ];
                                
                                // Kumpulkan penyakit yang ditemukan
                                $diseases = [];
                                foreach ($disease_map as $col => $name) {
                                    if (isset($pendonor[$col]) && !empty($pendonor[$col])) {
                                        $diseases[] = $name;
                                    }
                                }
                                // Tambahkan dari riwayat_penyakit jika ada
                                if (isset($pendonor['riwayat_penyakit']) && !empty(trim($pendonor['riwayat_penyakit']))) {
                                    $riwayat = trim($pendonor['riwayat_penyakit']);
                                    if (!in_array($riwayat, $diseases)) {
                                        $diseases[] = $riwayat;
                                    }
                                }
                                // Tambahkan dari other_illness jika ada
                                if (isset($pendonor['other_illness']) && !empty($pendonor['other_illness'])) {
                                    $other = $pendonor['other_illness'];
                                    if (!in_array($other, $diseases)) {
                                        $diseases[] = $other;
                                    }
                                }
                                
                                // Tentukan status kesehatan dari is_layak field
                                // is_layak = 0: TIDAK LAYAK (merah)
                                // is_layak = 1: LAYAK (kuning)
                                // is_layak = 2: SEHAT (hijau)
                                // Fallback: jika is_layak NULL/tidak ada, parsing riwayat_penyakit
                                
                                $is_layak_val = $pendonor['is_layak'] ?? null;
                                
                                if ($is_layak_val !== null && $is_layak_val !== '') {
                                    // Kolom is_layak ada nilai
                                    if ($is_layak_val == 0) {
                                        echo '<span class="badge bg-danger">Tidak Layak</span>';
                                    } elseif ($is_layak_val == 1) {
                                        echo '<span class="badge bg-warning text-dark">Layak</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Sehat</span>';
                                    }
                                } else {
                                    // Fallback: parsing riwayat_penyakit untuk tentukan status
                                    $riwayat_text = $pendonor['riwayat_penyakit'] ?? '';
                                    $screening_names = [
                                        'Hepatitis B', 'Hepatitis C', 'AIDS / HIV', 'Hemofilia',
                                        'Penyakit Sel Sabit', 'Thalasemia', 'Leukemia', 'Limfoma',
                                        'Myeloma', 'CJD'
                                    ];
                                    
                                    $has_screening = false;
                                    foreach ($screening_names as $name) {
                                        if (stripos($riwayat_text, $name) !== false) {
                                            $has_screening = true;
                                            break;
                                        }
                                    }
                                    
                                    if ($has_screening) {
                                        echo '<span class="badge bg-danger">Tidak Layak</span>';
                                    } elseif (!empty($riwayat_text) || !empty($pendonor['other_illness'])) {
                                        echo '<span class="badge bg-warning text-dark">Layak</span>';
                                    } else {
                                        echo '<span class="badge bg-success">Sehat</span>';
                                    }
                                }
                                ?>
                            </td>
                            <td class="no-wrap-actions">
                                <a href="?action=pendonor_detail&id=<?= $pendonor['id_pendonor'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?action=pendonor_edit&id=<?= $pendonor['id_pendonor'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=pendonor_riwayat&id=<?= $pendonor['id_pendonor'] ?>" class="btn btn-sm" title="Riwayat Donasi">
                                    <i class="fas fa-history"></i>
                                </a>
                                <button onclick="deleteItem(<?= $pendonor['id_pendonor'] ?>, 'pendonor_delete', 'pendonor', event)" class="btn btn-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data pendonor</td>
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
                        <a class="page-link" href="?action=pendonor&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=pendonor&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=pendonor&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=pendonor&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=pendonor&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>