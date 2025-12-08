<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA UNTUK DROPDOWN
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Ambil data pendonor. If id_gol_darah exists on pendonor, include golongan join; otherwise, fallback to simple select.
$checkSql = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor' AND column_name = 'id_gol_darah'";
$stmtCheck = $db->prepare($checkSql);
$stmtCheck->execute();
$hasGolColumn = (int) $stmtCheck->fetchColumn() > 0;

// Check column existence
$checkDel = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_deleted'");
$pendHasIsDeleted = $checkDel && $checkDel->rowCount() > 0;

$checkLayak = $db->query("SHOW COLUMNS FROM pendonor LIKE 'is_layak'");
$hasIsLayak = $checkLayak && $checkLayak->rowCount() > 0;

// Build WHERE conditions - FILTER pendonor yang tidak di-delete DAN yang LAYAK/SEHAT
// Hanya tampilkan pendonor dengan is_layak = 1 (LAYAK) atau 2 (SEHAT)
// Untuk data lama (is_layak = NULL), akan di-filter di PHP untuk exclude yang punya screening diseases
$where = [];
if ($pendHasIsDeleted) $where[] = 'p.is_deleted = 0';
if ($hasIsLayak) $where[] = '(p.is_layak = 1 OR p.is_layak = 2 OR p.is_layak IS NULL)';  // Include NULL to filter in PHP

$whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

if ($hasGolColumn) {
    $query_pendonor = "SELECT p.id_pendonor, p.nama, p.is_layak, p.riwayat_penyakit, gd.nama_gol_darah, gd.rhesus FROM pendonor p LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah $whereSql ORDER BY p.nama";
} else {
    $query_pendonor = "SELECT p.id_pendonor, p.nama, p.is_layak, p.riwayat_penyakit FROM pendonor p $whereSql ORDER BY p.nama";
}
$stmt_pendonor = $db->prepare($query_pendonor);
$stmt_pendonor->execute();
$pendonor_list_raw = $stmt_pendonor->fetchAll(PDO::FETCH_ASSOC);

// FILTER pendonor - exclude yang punya screening diseases
$screening_disease_names = [
    'Hepatitis B', 'Hepatitis C', 'AIDS / HIV', 'Hemofilia',
    'Penyakit Sel Sabit', 'Thalasemia', 'Leukemia', 'Limfoma',
    'Myeloma', 'CJD'
];

$pendonor_list = [];
foreach ($pendonor_list_raw as $p) {
    $is_layak = $p['is_layak'] ?? null;
    $riwayat = $p['riwayat_penyakit'] ?? '';
    
    // Jika is_layak sudah punya nilai (1 atau 2), gunakan langsung
    if ($is_layak !== null && $is_layak !== '') {
        // Sudah ada is_layak value, check apakah 0 atau 1/2
        if ($is_layak != 0) {  // 1 atau 2 = LAYAK/SEHAT, bukan 0
            $pendonor_list[] = $p;
        }
        // Skip jika is_layak = 0
    } else {
        // Old data (is_layak = NULL), cek riwayat_penyakit untuk screening diseases
        $has_screening = false;
        foreach ($screening_disease_names as $disease) {
            if (stripos($riwayat, $disease) !== false) {
                $has_screening = true;
                break;
            }
        }
        // Include hanya jika TIDAK punya screening disease
        if (!$has_screening) {
            $pendonor_list[] = $p;
        }
    }
}

// Ambil data kegiatan - HANYA YANG TIDAK DI-ARSIP
$checkKegiatanDel = $db->query("SHOW COLUMNS FROM kegiatan_donasi LIKE 'is_deleted'");
$kegiatanHasIsDeleted = $checkKegiatanDel && $checkKegiatanDel->rowCount() > 0;
if ($kegiatanHasIsDeleted) {
    $query_kegiatan = "SELECT id_kegiatan, nama_kegiatan FROM kegiatan_donasi WHERE is_deleted = 0 ORDER BY tanggal DESC";
} else {
    $query_kegiatan = "SELECT id_kegiatan, nama_kegiatan FROM kegiatan_donasi ORDER BY tanggal DESC";
}
$stmt_kegiatan = $db->prepare($query_kegiatan);
$stmt_kegiatan->execute();
$kegiatan_list = $stmt_kegiatan->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1>Tambah Transaksi Donasi</h1>
    <a href="?action=transaksi" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=transaksi_store" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="id_pendonor" class="form-label">Pendonor * (Hanya Pendonor Sehat)</label>
                        <select class="form-control" id="id_pendonor" name="id_pendonor" required>
                            <option value="">Pilih Pendonor</option>
                            <?php foreach ($pendonor_list as $pendonor): ?>
                            <option value="<?= $pendonor['id_pendonor'] ?>"><?= htmlspecialchars($pendonor['nama']) ?> - <?= htmlspecialchars($pendonor['nama_gol_darah'] ?? 'N/A') ?><?= $pendonor['rhesus'] ?? '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="id_kegiatan" class="form-label">Kegiatan Donor *</label>
                        <select class="form-control" id="id_kegiatan" name="id_kegiatan" required>
                            <option value="">Pilih Kegiatan</option>
                            <?php foreach ($kegiatan_list as $kegiatan): ?>
                            <option value="<?= $kegiatan['id_kegiatan'] ?>"><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal_donasi" class="form-label">Tanggal Donasi *</label>
                        <input type="date" class="form-control" id="tanggal_donasi" name="tanggal_donasi" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa *</label>
                        <input type="date" class="form-control" id="tanggal_kadaluarsa" name="tanggal_kadaluarsa" required>
                    </div>
                </div>
            </div>
            <!-- Each transaksi now represents ONE kantong. jumlah_kantong input removed. -->
            <input type="hidden" name="jumlah_kantong" value="1">
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <button type="submit" class="btn" style="background: #c62828; color: white; border: none;">Simpan Transaksi</button>
            </div>
        </form>
    </div>
</div>

<?php include Path::template('footer.php'); ?>