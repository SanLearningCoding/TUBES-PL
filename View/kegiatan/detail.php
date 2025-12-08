<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DETAIL KEGIATAN
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_kegiatan = $_GET['id'] ?? 0;

// Data kegiatan
$query = "SELECT * FROM kegiatan_donasi WHERE id_kegiatan = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_kegiatan]);
$kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kegiatan) {
    echo "<div class='alert alert-danger m-4'>Kegiatan tidak ditemukan!
          <br><a href='?action=kegiatan' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Kegiatan</a></div>";
    include Path::template('footer.php');
    exit;
}

// Data donor di kegiatan ini
$query_donor = "SELECT td.*, p.nama as nama_pendonor, p.kontak
                FROM transaksi_donasi td
                JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                WHERE td.id_kegiatan = ?
                ORDER BY td.tanggal_donasi DESC";
$stmt_donor = $db->prepare($query_donor);
$stmt_donor->execute([$id_kegiatan]);
$donor_list = $stmt_donor->fetchAll(PDO::FETCH_ASSOC);

// Total kantong darah
$total_kantong = 0;
foreach ($donor_list as $donor) {
    $total_kantong += $donor['jumlah_kantong'];
}
?>

<style>
.detail-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.detail-page-header h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
}

.detail-page-header .btn-back {
    background: #c62828;
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.detail-page-header .btn-back:hover {
    background: #8b1a1a;
    transform: translateY(-2px);
}

.card-header-red {
    background: #c62828 !important;
    color: white !important;
}

.card-body {
    background: #f9f9f9;
}

.card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 1rem;
}

.table-borderless th {
    color: #555;
    font-weight: 600;
    padding: 0.75rem;
}

.table-borderless td {
    padding: 0.75rem;
    color: #333;
}
</style>

<div class="container-fluid mt-4">
    <div class="detail-page-header">
        <h1>Detail Kegiatan: <?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></h1>
        <a href="?action=kegiatan" class="btn btn-back">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="container-fluid">

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header card-header-red">
                <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Kegiatan</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Nama Kegiatan</th>
                        <td><?= htmlspecialchars($kegiatan['nama_kegiatan']) ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal</th>
                        <td><?= date('d/m/Y', strtotime($kegiatan['tanggal'])) ?></td>
                    </tr>
                    <tr>
                        <th>Lokasi</th>
                        <td><?= htmlspecialchars($kegiatan['lokasi']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header card-header-red">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Statistik</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h3 class="text-primary"><?= count($donor_list) ?></h3>
                    <p class="text-muted">Total Donor</p>
                </div>
                <div class="text-center mt-3">
                    <h3 class="text-success"><?= $total_kantong ?></h3>
                    <p class="text-muted">Total Kantong Darah</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header card-header-red">
                <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Daftar Donor</h5>
            </div>
            <div class="card-body">
                <?php if (count($donor_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nama Donor</th>
                                <th>Tanggal Donor</th>
                                <th>Kontak</th>
                                <th>Jumlah Kantong</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donor_list as $donor): ?>
                            <tr>
                                <td><?= htmlspecialchars($donor['nama_pendonor']) ?></td>
                                <td><?= date('d/m/Y', strtotime($donor['tanggal_donasi'])) ?></td>
                                <td><?= htmlspecialchars($donor['kontak']) ?></td>
                                <td><?= $donor['jumlah_kantong'] ?> kantong</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada donor di kegiatan ini</h5>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>