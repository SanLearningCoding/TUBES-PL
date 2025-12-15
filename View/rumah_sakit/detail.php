<?php 
// View/rumah_sakit/detail.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DETAIL RUMAH SAKIT
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_rs = $_GET['id'] ?? 0;

// Data rumah sakit
$query = "SELECT * FROM rumah_sakit WHERE id_rs = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_rs]);
$rs = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rs) {
    echo "<div class='alert alert-danger m-4'>Rumah sakit tidak ditemukan!
          <br><a href='?action=rumah_sakit' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Rumah Sakit</a></div>";
    include Path::template('footer.php');
    exit;
}

// Data distribusi ke rumah sakit ini
$query_distribusi = "SELECT dd.*, sd.id_stok, gd.nama_gol_darah, gd.rhesus
                     FROM distribusi_darah dd
                     JOIN stok_darah sd ON dd.id_stok = sd.id_stok
                     JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
                     WHERE dd.id_rs = ?
                     ORDER BY dd.tanggal_distribusi DESC
                     LIMIT 10";
$stmt_distribusi = $db->prepare($query_distribusi);
$stmt_distribusi->execute([$id_rs]);
$distribusi_list = $stmt_distribusi->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$query_stats = "SELECT 
                COUNT(*) as total_distribusi,
                SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as total_terkirim,
                SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as total_dalam_perjalanan,
                SUM(CASE WHEN status = 'dikirim' THEN 1 ELSE 0 END) as total_menunggu
                FROM distribusi_darah 
                WHERE id_rs = ?";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->execute([$id_rs]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1><?= htmlspecialchars($rs['nama_rs']) ?></h1>
    <a href="?action=rumah_sakit" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0"><i class="fas fa-hospital me-2"></i>Informasi Rumah Sakit</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-hospital fa-4x text-primary"></i>
                    <h4 class="mt-3"><?= htmlspecialchars($rs['nama_rs']) ?></h4>
                </div>
                
                <table class="table table-borderless">
                    <tr>
                        <th width="30%"><i class="fas fa-map-marker-alt me-2"></i>Alamat</th>
                        <td><?= htmlspecialchars($rs['alamat']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-phone me-2"></i>Kontak</th>
                        <td><?= htmlspecialchars($rs['kontak']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-id-card me-2"></i>ID RS</th>
                        <td>RS<?= str_pad($rs['id_rs'], 3, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Distribusi</h5>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <h3 class="text-primary"><?= $stats['total_distribusi'] ?? 0 ?></h3>
                    <p class="text-muted">Total Distribusi</p>
                </div>
                
                <div class="row mt-3">
                    <div class="col-4 text-center">
                        <h5 class="text-success"><?= $stats['total_terkirim'] ?? 0 ?></h5>
                        <small class="text-muted">Terkirim</small>
                    </div>
                    <div class="col-4 text-center">
                        <h5 class="text-warning"><?= $stats['total_dalam_perjalanan'] ?? 0 ?></h5>
                        <small class="text-muted">Dalam Perjalanan</small>
                    </div>
                    <div class="col-4 text-center">
                        <h5 class="text-secondary"><?= $stats['total_menunggu'] ?? 0 ?></h5>
                        <small class="text-muted">Menunggu</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Riwayat Distribusi Terbaru</h5>
            </div>
            <div class="card-body">
                <?php if (count($distribusi_list) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Golongan Darah</th>
                                <th>Status</th>
                                <th>ID Distribusi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distribusi_list as $distribusi): 
                                $status_class = 'bg-secondary';
                                if ($distribusi['status'] == 'dikirim') $status_class = 'bg-success';
                                elseif ($distribusi['status'] == 'dikirim') $status_class = 'bg-warning';
                                elseif ($distribusi['status'] == 'dibatalkan') $status_class = 'bg-danger';
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($distribusi['tanggal_distribusi'])) ?></td>
                                <td>
                                    <span class="badge bg-danger">
                                        <?= $distribusi['nama_gol_darah'] ?><?= $distribusi['rhesus'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $status_class ?>">
                                        <?= ucfirst($distribusi['status']) ?>
                                    </span>
                                </td>
                                <td>D<?= str_pad($distribusi['id_distribusi'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <a href="?action=distribusi_detail&id=<?= $distribusi['id_distribusi'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($stats['total_distribusi'] > 10): ?>
                <div class="text-center mt-3">
                    <a href="?action=rumah_sakit_laporan&id=<?= $rs['id_rs'] ?>" class="btn btn-outline-primary">
                        <i class="fas fa-list me-1"></i>Lihat Semua Distribusi (<?= $stats['total_distribusi'] ?>)
                    </a>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada distribusi ke rumah sakit ini</h5>
                    <a href="?action=distribusi_create" class="btn" style="background: #c62828; color: white; border: none; margin-top: 0.5rem;">
                        <i class="fas fa-plus me-1"></i>Buat Distribusi Pertama
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header card-header-red">
                <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i>Aksi</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?action=rumah_sakit_edit&id=<?= $rs['id_rs'] ?>" class="btn">
                        <i class="fas fa-edit"></i>Edit Data Rumah Sakit
                    </a>
                    <a href="?action=distribusi_create&rs_id=<?= $rs['id_rs'] ?>" class="btn">
                        <i class="fas fa-plus"></i>Buat Distribusi Baru ke RS Ini
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>