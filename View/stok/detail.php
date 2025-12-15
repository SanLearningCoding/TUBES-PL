<?php
// View/stok/detail.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

// Controller should provide $stok
$stok = $stok ?? null;

if (!$stok) {
    echo "<div class='alert alert-danger m-4'>Stok tidak ditemukan!<br><a href='?action=stok' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali</a></div>";
    include Path::template('footer.php');
    exit;
}

// Get pendonor info jika ada id_transaksi
$nama_pendonor = '-';
if (!empty($stok['id_transaksi'])) {
    require_once __DIR__ . '/../../Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT p.nama FROM transaksi_donasi td
              LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
              WHERE td.id_transaksi = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$stok['id_transaksi']]);
    $pendonor = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pendonor && !empty($pendonor['nama'])) {
        $nama_pendonor = htmlspecialchars($pendonor['nama']);
    }
}

// Hitung status: prioritas utama adalah status database, kemudian cek expiration
$is_expired = (!empty($stok['tanggal_kadaluarsa']) && strtotime($stok['tanggal_kadaluarsa']) < strtotime('today'));
$status = $stok['status'] ?? 'tersedia';

if ($is_expired) {
    $display_status = 'Kadaluarsa';
} elseif ($status === 'terpakai') {
    $display_status = 'Terpakai';
} else {
    $display_status = 'Tersedia';
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
    color: white;
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
        <h1>Detail Stok SD<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></h1>
        <a href="?action=stok" class="btn btn-back">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="container-fluid">
    <div class="card">
        <div class="card-header card-header-red">
            <h5 class="mb-0"><i class="fas fa-prescription-bottle me-2"></i>Informasi Stok</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr><th>ID Stok</th><td>SD<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></td></tr>
                <tr><th>Golongan</th><td><?= htmlspecialchars($stok['nama_gol_darah'] ?? '') ?> <?= $stok['rhesus'] ?? '' ?></td></tr>
                <tr><th>Pendonor</th><td><?= $nama_pendonor ?></td></tr>
                <tr><th>Jumlah Kantong</th><td>1 kantong</td></tr>
                <tr><th>Tanggal Donor</th><td><?= isset($stok['tanggal_donasi']) && $stok['tanggal_donasi'] ? date('d/m/Y', strtotime($stok['tanggal_donasi'])) : '-' ?></td></tr>
                <tr><th>Tanggal Kadaluarsa</th><td><?= isset($stok['tanggal_kadaluarsa']) && $stok['tanggal_kadaluarsa'] ? date('d/m/Y', strtotime($stok['tanggal_kadaluarsa'])) : '-' ?></td></tr>
                <tr><th>Status</th><td>
                    <?php if ($is_expired): ?>
                        <span class="badge bg-warning text-dark">Kadaluarsa</span>
                    <?php elseif ($status === 'terpakai'): ?>
                        <span class="badge bg-danger">Terpakai</span>
                    <?php else: ?>
                        <span class="badge bg-success">Tersedia</span>
                    <?php endif; ?>
                </td></tr>
            </table>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>
