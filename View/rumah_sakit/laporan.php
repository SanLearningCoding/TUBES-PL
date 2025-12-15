<?php 
// View/rumah_sakit/laporan.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DISTRIBUSI PER RUMAH SAKIT
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_rs = $_GET['id'] ?? 0;

// Data rumah sakit
$query_rs = "SELECT * FROM rumah_sakit WHERE id_rs = ?";
$stmt_rs = $db->prepare($query_rs);
$stmt_rs->execute([$id_rs]);
$rs = $stmt_rs->fetch(PDO::FETCH_ASSOC);

if (!$rs) {
    echo "<div class='alert alert-danger m-4'>Rumah sakit tidak ditemukan!
          <br><a href='?action=rumah_sakit' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Rumah Sakit</a></div>";
    include Path::template('footer.php');
    exit;
}

// Data distribusi lengkap
$query_distribusi = "SELECT dd.*, sd.id_stok, sd.jumlah_kantong, sd.tanggal_kadaluarsa,
                            gd.nama_gol_darah, gd.rhesus,
                            td.tanggal_donasi,
                            p.nama as nama_pendonor
                     FROM distribusi_darah dd
                     JOIN stok_darah sd ON dd.id_stok = sd.id_stok
                     JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
                     LEFT JOIN transaksi_donasi td ON sd.id_transaksi = td.id_transaksi
                     LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
                     WHERE dd.id_rs = ?
                     ORDER BY dd.tanggal_distribusi DESC";
$stmt_distribusi = $db->prepare($query_distribusi);
$stmt_distribusi->execute([$id_rs]);
$distribusi_list = $stmt_distribusi->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1>Laporan Distribusi - <?= htmlspecialchars($rs['nama_rs']) ?></h1>
    <div class="btn-toolbar">
        <a href="?action=rumah_sakit_detail&id=<?= $rs['id_rs'] ?>" class="btn btn-back me-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali ke Detail
        </a>
        <button onclick="window.print()" class="btn" style="background: #c62828; color: white; border: none;">
            <i class="fas fa-print me-1"></i>Cetak Laporan
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-hospital me-2"></i><?= htmlspecialchars($rs['nama_rs']) ?></h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Alamat:</strong> <?= htmlspecialchars($rs['alamat']) ?></p>
                <p><strong>Kontak:</strong> <?= htmlspecialchars($rs['kontak']) ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Total Distribusi:</strong> <?= count($distribusi_list) ?> kali</p>
                <p><strong>Tanggal Cetak:</strong> <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
        
        <?php if (count($distribusi_list) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal</th>
                        <th>Golongan Darah</th>
                        <th>Jumlah Kantong</th>
                        <th>Pendonor</th>
                        <th>Tanggal Donasi</th>
                        <th>Kadaluarsa</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribusi_list as $distribusi): 
                        $st = $distribusi['status'] ?? 'dikirim';
                        $status_class = 'bg-secondary';
                        $status_label = 'Menunggu';
                        if ($st === 'diterima') {
                            $status_class = 'bg-success';
                            $status_label = 'Diterima';
                        } elseif ($st === 'dikirim') {
                            $status_class = 'bg-warning';
                            $status_label = 'Dikirim';
                        } elseif ($st === 'dibatalkan') {
                            $status_class = 'bg-danger';
                            $status_label = 'Dibatalkan';
                        }
                    ?>
                    <tr>
                        <td><?= isset($distribusi['tanggal_distribusi']) && $distribusi['tanggal_distribusi'] ? date('d/m/Y', strtotime($distribusi['tanggal_distribusi'])) : '-' ?></td>
                        <td>
                            <span class="badge bg-danger">
                                <?= $distribusi['nama_gol_darah'] ?><?= $distribusi['rhesus'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($distribusi['jumlah_volume'] ?? 1) ?> kantong</td>
                        <td><?= $distribusi['nama_pendonor'] ?? 'N/A' ?></td>
                        <td><?= isset($distribusi['tanggal_donasi']) && $distribusi['tanggal_donasi'] ? date('d/m/Y', strtotime($distribusi['tanggal_donasi'])) : 'N/A' ?></td>
                        <td><?= isset($distribusi['tanggal_kadaluarsa']) && $distribusi['tanggal_kadaluarsa'] ? date('d/m/Y', strtotime($distribusi['tanggal_kadaluarsa'])) : '-' ?></td>
                        <td>
                            <span class="badge <?= $status_class ?>">
                                <?= $status_label ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Belum ada data distribusi</h5>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <a href="?action=rumah_sakit_detail&id=<?= $rs['id_rs'] ?>" class="btn" style="background: #c62828; color: white; border: none;">
        <i class="fas fa-arrow-left me-1"></i>Kembali ke Detail Rumah Sakit
    </a>
</div>

<?php include Path::template('footer.php'); ?>