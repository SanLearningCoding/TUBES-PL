<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_pendonor = $_GET['id'] ?? 0;
if (!is_numeric($id_pendonor) || $id_pendonor <= 0) {
    echo "<div class='alert alert-danger m-4'>ID pendonor tidak valid.</div>";
    include Path::template('footer.php');
    exit;
}

// Get pendonor info
$stmt = $db->prepare("SELECT id_pendonor, nama, kontak FROM pendonor WHERE id_pendonor = ? AND is_deleted = 0");
$stmt->execute([$id_pendonor]);
$pendonor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pendonor) {
    echo "<div class='alert alert-danger m-4'>Pendonor tidak ditemukan.</div>";
    include Path::template('footer.php');
    exit;
}

// Get all transaksi (donation history)
$stmt = $db->prepare("
    SELECT 
        td.id_transaksi,
        td.tanggal_donasi,
        td.jumlah_kantong,
        td.catatan,
        td.id_kegiatan,
        kd.nama_kegiatan,
        kd.lokasi,
        kd.tanggal as tanggal_kegiatan
    FROM transaksi_donasi td
    LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
    WHERE td.id_pendonor = ? AND td.is_deleted = 0
    ORDER BY td.tanggal_donasi DESC
");
$stmt->execute([$id_pendonor]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_kantong = 0;
$total_kegiatan = [];
foreach ($riwayat as $r) {
    $total_kantong += $r['jumlah_kantong'];
    if ($r['nama_kegiatan'] && !in_array($r['nama_kegiatan'], $total_kegiatan)) {
        $total_kegiatan[] = $r['nama_kegiatan'];
    }
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

.row > .col-md-6 .card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 1rem;
}

.row > .col-md-6 .card-body {
    background: #f9f9f9;
    max-height: none;
    overflow: visible;
}

.info-row,
.statistik-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child,
.statistik-row:last-child {
    border-bottom: none;
}

.info-label,
.statistik-label {
    font-weight: 600;
    color: #333;
    flex: 0 0 auto;
}

.info-value,
.statistik-value {
    color: #666;
    flex: 1;
    text-align: right;
}
</style>

<div class="container-fluid mt-4">
    <div class="detail-page-header">
        <h1>Riwayat Donasi Darah</h1>
        <a href="?action=pendonor" class="btn btn-back">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header card-header-red">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Pendonor</h5>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">ID Pendonor</span>
                        <span class="info-value">P<?= str_pad($pendonor['id_pendonor'], 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama</span>
                        <span class="info-value"><?= htmlspecialchars($pendonor['nama']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kontak</span>
                        <span class="info-value"><?= htmlspecialchars($pendonor['kontak']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-statistik">
                <div class="card-header card-header-red">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Statistik Donasi</h5>
                </div>
                <div class="card-body">
                    <div class="statistik-row">
                        <span class="statistik-label">Total Kantong Darah</span>
                        <span class="statistik-value"><strong><?= $total_kantong ?></strong> kantong</span>
                    </div>
                    <div class="statistik-row">
                        <span class="statistik-label">Total Kegiatan Donasi</span>
                        <span class="statistik-value"><strong><?= count($total_kegiatan) ?></strong> kegiatan</span>
                    </div>
                    <div class="statistik-row">
                        <span class="statistik-label">Total Riwayat Donasi</span>
                        <span class="statistik-value"><strong><?= count($riwayat) ?></strong> kali</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-red">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Daftar Riwayat Donasi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Tanggal Donasi</th>
                            <th>Kegiatan</th>
                            <th>Lokasi</th>
                            <th>Jumlah Kantong</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($riwayat) > 0): ?>
                            <?php foreach ($riwayat as $index => $item): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($item['tanggal_donasi'])) ?></strong>
                                </td>
                                <td>
                                    <?php if ($item['nama_kegiatan']): ?>
                                        <?= htmlspecialchars($item['nama_kegiatan']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item['lokasi']): ?>
                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($item['lokasi']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="background: #c62828;"><?= $item['jumlah_kantong'] ?> kantong</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    Belum ada riwayat donasi darah
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>