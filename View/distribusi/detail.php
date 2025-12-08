<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA DETAIL DISTRIBUSI
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_distribusi = $_GET['id'] ?? 0;

$query = "SELECT dd.*, rs.nama_rs, rs.alamat, rs.kontak as kontak_rs,
                 sd.id_stok, sd.jumlah_kantong, sd.tanggal_kadaluarsa,
                 gd.nama_gol_darah, gd.rhesus,
                 td.id_transaksi, td.tanggal_donasi,
                 p.nama as nama_pendonor
          FROM distribusi_darah dd
          JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
          JOIN stok_darah sd ON dd.id_stok = sd.id_stok
          JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
          LEFT JOIN transaksi_donasi td ON sd.id_transaksi = td.id_transaksi
          LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
          WHERE dd.id_distribusi = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_distribusi]);
$distribusi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$distribusi) {
    echo "<div class='alert alert-danger m-4'>Distribusi tidak ditemukan!
          <br><a href='?action=distribusi' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Distribusi</a></div>";
    include Path::template('footer.php');
    exit;
}

// Status warna
$status = $distribusi['status'] ?? 'dikirim';
$status_class = 'bg-secondary';
if ($status === 'diterima') $status_class = 'bg-success';
elseif ($status === 'dikirim') $status_class = 'bg-warning';
elseif ($status === 'dibatalkan') $status_class = 'bg-danger';
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

/* Informasi Sumber Darah Card */
.card-stok-darah {
    margin-top: 1rem;
}

.card-stok-darah .row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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
    
    .card-stok-darah .row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="detail-page-header">
    <h1>Detail Distribusi Darah</h1>
    <a href="?action=distribusi" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="detail-cards-wrapper">
    <div class="card mb-0">
        <div class="card-header card-header-red">
            <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Distribusi</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="40%">ID Distribusi</th>
                    <td>D<?= str_pad($distribusi['id_distribusi'], 3, '0', STR_PAD_LEFT) ?></td>
                </tr>
                <tr>
                    <th>Tanggal Distribusi</th>
                    <td><?= isset($distribusi['tanggal_distribusi']) && $distribusi['tanggal_distribusi'] ? date('d/m/Y', strtotime($distribusi['tanggal_distribusi'])) : '-' ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><span class="badge <?= $status_class ?>">
                        <?php
                            if ($status === 'diterima') echo 'Diterima';
                            elseif ($status === 'dikirim') echo 'Dikirim';
                            elseif ($status === 'dibatalkan') echo 'Dibatalkan';
                            else echo ucfirst($status);
                        ?>
                    </span></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card mb-0">
        <div class="card-header card-header-red">
            <h5 class="card-title mb-0"><i class="fas fa-hospital me-2"></i>Informasi Rumah Sakit</h5>
        </div>
        <div class="card-body">
            <table class="table table-borderless">
                <tr>
                    <th width="40%">Nama RS</th>
                    <td><?= htmlspecialchars($distribusi['nama_rs'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <th>Alamat</th>
                    <td><?= htmlspecialchars($distribusi['alamat'] ?? '-') ?></td>
                </tr>
                <tr>
                    <th>Kontak</th>
                    <td><?= htmlspecialchars($distribusi['kontak_rs'] ?? '-') ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Informasi Sumber Darah -->
<div class="card card-stok-darah">
    <div class="card-header card-header-red">
        <h5 class="card-title mb-0"><i class="fas fa-tint me-2"></i>Informasi Sumber Darah</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Golongan Darah</th>
                        <td><strong><?= htmlspecialchars($distribusi['nama_gol_darah'] ?? '-') ?> <?= htmlspecialchars($distribusi['rhesus'] ?? '') ?></strong></td>
                    </tr>
                    <tr>
                        <th>Jumlah Kantong</th>
                        <td><?= intval($distribusi['jumlah_kantong'] ?? 0) ?> kantong</td>
                    </tr>
                    <tr>
                        <th>Tanggal Kadaluarsa</th>
                        <td><?= isset($distribusi['tanggal_kadaluarsa']) && $distribusi['tanggal_kadaluarsa'] ? date('d/m/Y', strtotime($distribusi['tanggal_kadaluarsa'])) : '-' ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <th width="40%">Tanggal Donasi</th>
                        <td><?= isset($distribusi['tanggal_donasi']) && $distribusi['tanggal_donasi'] ? date('d/m/Y', strtotime($distribusi['tanggal_donasi'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Nama Pendonor</th>
                        <td><?= htmlspecialchars($distribusi['nama_pendonor'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>ID Stok</th>
                        <td>S<?= str_pad($distribusi['id_stok'] ?? 0, 3, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>