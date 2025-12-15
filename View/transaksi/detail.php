<?php 
// View/transaksi/detail.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DETAIL TRANSAKSI
$id_transaksi = $_GET['id'] ?? 0;

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Check whether pendonor has id_gol_darah column
$checkSql = "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor' AND column_name = 'id_gol_darah'";
$stmtCheck = $db->prepare($checkSql);
$stmtCheck->execute();
$hasGolColumn = (int) $stmtCheck->fetchColumn() > 0;

// Check whether pendonor has other_illness column
$checkOtherIllness = $db->query("SHOW COLUMNS FROM pendonor LIKE 'other_illness'");
$hasOtherIllness = $checkOtherIllness && $checkOtherIllness->rowCount() > 0;

if ($hasGolColumn) {
    $selectPendonor = $hasOtherIllness ? 'p.nama as nama_pendonor, p.kontak, p.riwayat_penyakit, p.other_illness,' : 'p.nama as nama_pendonor, p.kontak, p.riwayat_penyakit,';
    $query = "SELECT td.*, $selectPendonor
                 kd.nama_kegiatan, kd.tanggal as tanggal_kegiatan, kd.lokasi,
                 gd.nama_gol_darah, gd.rhesus
          FROM transaksi_donasi td
          JOIN pendonor p ON td.id_pendonor = p.id_pendonor
          JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
          LEFT JOIN golongan_darah gd ON p.id_gol_darah = gd.id_gol_darah
          WHERE td.id_transaksi = ?";
} else {
    $selectPendonor = $hasOtherIllness ? 'p.nama as nama_pendonor, p.kontak, p.riwayat_penyakit, p.other_illness,' : 'p.nama as nama_pendonor, p.kontak, p.riwayat_penyakit,';
    $query = "SELECT td.*, $selectPendonor
                 kd.nama_kegiatan, kd.tanggal as tanggal_kegiatan, kd.lokasi
          FROM transaksi_donasi td
          JOIN pendonor p ON td.id_pendonor = p.id_pendonor
          JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
          WHERE td.id_transaksi = ?";
}
$stmt = $db->prepare($query);
$stmt->execute([$id_transaksi]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    echo "<div class='alert alert-danger m-4'>Transaksi tidak ditemukan!
          <br><a href='?action=transaksi' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Transaksi</a></div>";
    include Path::template('footer.php');
    exit;
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

.badge-red {
    background: #c62828 !important;
}

.table-borderless th {
    color: #666;
    font-weight: 600;
    padding: 0.75rem 0;
}

.table-borderless td {
    color: #333;
    padding: 0.75rem 0;
}

.table-borderless tr:not(:last-child) {
    border-bottom: 1px solid #e0e0e0;
}

.detail-cards-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .detail-page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .detail-page-header h1 {
        width: 100%;
    }
    
    .detail-cards-wrapper {
        grid-template-columns: 1fr;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}
</style>

<div class="detail-page-header">
    <h1>Detail Transaksi Donasi</h1>
    <a href="?action=transaksi" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="detail-cards-wrapper">
    <div class="card mb-0">
        <div class="card-header card-header-red">
            <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Transaksi</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="40%">ID Transaksi</th>
                    <td>T<?= str_pad($transaksi['id_transaksi'], 3, '0', STR_PAD_LEFT) ?></td>
                </tr>
                <tr>
                    <th>Tanggal Donasi</th>
                    <td><?= date('d/m/Y', strtotime($transaksi['tanggal_donasi'])) ?></td>
                </tr>
                <tr>
                    <th>Jumlah Kantong</th>
                    <td><span class="badge badge-red"><?= $transaksi['jumlah_kantong'] ?> kantong</span></td>
                </tr>
                <tr>
                    <th>Kegiatan</th>
                    <td><?= htmlspecialchars($transaksi['nama_kegiatan']) ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="card mb-0">
        <div class="card-header card-header-red">
            <h5 class="card-title mb-0"><i class="fas fa-user me-2"></i>Data Pendonor</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="40%">Nama Pendonor</th>
                    <td><?= htmlspecialchars($transaksi['nama_pendonor']) ?></td>
                </tr>
                <tr>
                    <th>Kontak</th>
                    <td><?= htmlspecialchars($transaksi['kontak']) ?></td>
                </tr>
                <tr>
                    <th>Golongan Darah</th>
                    <td><?= htmlspecialchars($transaksi['nama_gol_darah'] ?? 'N/A') ?> <?= htmlspecialchars($transaksi['rhesus'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Riwayat Penyakit</th>
                    <td><?= htmlspecialchars($transaksi['riwayat_penyakit']) ?: 'Tidak ada' ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header card-header-red">
        <h5 class="card-title mb-0"><i class="fas fa-calendar me-2"></i>Informasi Kegiatan</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <th width="20%">Nama Kegiatan</th>
                <td><?= htmlspecialchars($transaksi['nama_kegiatan']) ?></td>
            </tr>
            <tr>
                <th>Tanggal Kegiatan</th>
                <td><?= date('d/m/Y', strtotime($transaksi['tanggal_kegiatan'])) ?></td>
            </tr>
            <tr>
                <th>Lokasi</th>
                <td><?= htmlspecialchars($transaksi['lokasi']) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php include Path::template('footer.php'); ?>