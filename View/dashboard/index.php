<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// View/dashboard/index.php

// AMBIL DATA STATISTIK DARI DATABASE - PERBAIKI PATH
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Hitung total pendonor
$checkIsDeleted = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pendonor' AND column_name = 'is_deleted'");
$checkIsDeleted->execute();
$hasIsDeleted = intval($checkIsDeleted->fetchColumn()) > 0;
if ($hasIsDeleted) {
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM pendonor WHERE is_deleted = 0');
} else {
    $stmt = $db->prepare('SELECT COUNT(*) as total FROM pendonor');
}
$stmt->execute();
$total_pendonor = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

// Per-unit model: count individual stok_darah records as kantong
// Only count stok that have passed testing and are not expired
$query = "SELECT COALESCE(COUNT(*), 0) as total FROM stok_darah WHERE is_deleted = 0 AND status_uji = 'lolos' AND (tanggal_kadaluarsa IS NULL OR tanggal_kadaluarsa > CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$total_stok = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

// transaksi hari ini dan distribusi bulan ini (tidak tergantung schema stok)
// ONLY count non-deleted transactions to sync with Transaksi page
$query = "SELECT COUNT(*) as total FROM transaksi_donasi WHERE DATE(tanggal_donasi) = CURDATE() AND is_deleted = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$transaksi_hari_ini = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

$query = "SELECT COUNT(*) as total FROM distribusi_darah WHERE DATE_FORMAT(tanggal_distribusi, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
$stmt = $db->prepare($query);
$stmt->execute();
$distribusi_bulan_ini = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
?>

<style>
.hero {
    margin-bottom: 2rem;
}

.hero h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-card .card-body {
    padding: 1.25rem;
}

.stat-card .label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.stat-card .value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #c62828;
}

.stat-card .icon-circle {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
    }
    
    .stat-card .card-body {
        padding: 1rem;
    }
    
    .stat-card .value {
        font-size: 1.5rem;
    }
    
    .stat-card .icon-circle {
        width: 40px;
        height: 40px;
    }
    
    .hero h1 {
        font-size: 1.4rem;
    }
}
</style>

<div class="hero">
    <h1>Selamat Datang</h1>
</div>

<!-- Maintainer: debug banner removed -->
<div class="dashboard-grid">
    <div class="card stat-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="me-2">
                    <div class="label">Total Pendonor</div>
                    <div class="value card-metric"><?= $total_pendonor ?></div>
                </div>
                <div class="align-self-center ms-auto text-end">
                    <div class="icon-circle bg-light text-danger p-2 rounded-circle">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="me-2">
                    <div class="label">Stok Tersedia</div>
                    <div class="value card-metric"><?= $total_stok ?></div>
                </div>
                <div class="align-self-center ms-auto text-end">
                    <div class="icon-circle bg-light text-danger p-2 rounded-circle">
                        <i class="fas fa-prescription-bottle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="me-2">
                    <div class="label">Transaksi Hari Ini</div>
                    <div class="value card-metric"><?= $transaksi_hari_ini ?></div>
                </div>
                <div class="align-self-center ms-auto text-end">
                    <div class="icon-circle bg-light text-danger p-2 rounded-circle">
                        <i class="fas fa-hand-holding-heart fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card stat-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="me-2">
                    <div class="label">Distribusi Bulan Ini</div>
                    <div class="value card-metric"><?= $distribusi_bulan_ini ?></div>
                </div>
                <div class="align-self-center ms-auto text-end">
                    <div class="icon-circle bg-light text-danger p-2 rounded-circle">
                        <i class="fas fa-truck fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Group breakdown -->
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Stok Darah per Golongan</h5>
                <div id="stok-group-table" class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Golongan Darah</th>
                                <th>Total Kantong</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query: Count individual stok_darah records per golongan darah
                            // Only count stok that are available (status='tersedia'), not deleted, not expired, and passed testing
                            $stmt = $db->prepare("
                                SELECT 
                                    gd.id_gol_darah,
                                    gd.nama_gol_darah, 
                                    gd.rhesus, 
                                    COUNT(sd.id_stok) as total_kantong
                                FROM golongan_darah gd
                                LEFT JOIN stok_darah sd ON sd.id_gol_darah = gd.id_gol_darah 
                                    AND sd.is_deleted = 0 
                                    AND sd.status = 'tersedia'
                                    AND sd.status_uji = 'lolos'
                                    AND (sd.tanggal_kadaluarsa IS NULL OR sd.tanggal_kadaluarsa > CURDATE())
                                GROUP BY gd.id_gol_darah, gd.nama_gol_darah, gd.rhesus
                                ORDER BY gd.nama_gol_darah, gd.rhesus
                            ");
                            $stmt->execute();
                            $groupRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (count($groupRows) === 0) {
                                echo '<tr><td colspan="2" class="text-center">Tidak ada stok tersedia</td></tr>';
                            } else {
                                foreach ($groupRows as $g) {
                                    $kantong = intval($g['total_kantong'] ?? 0);
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($g['nama_gol_darah'] ?? 'N/A') . ' ' . htmlspecialchars($g['rhesus'] ?? '') . '</td>';
                                    echo '<td><span class="badge" style="background: #c62828;">' . $kantong . '</span></td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Optional: Polling script to refresh counts every 10 seconds -->
<script>
function refreshDashboard() {
    fetch('?action=dashboard_data')
        .then(res => res.json())
            .then(data => {
            document.getElementById('countPendonor').textContent = data.total_pendonor;
            document.getElementById('countStok').textContent = data.total_stok;
            document.getElementById('countTransToday').textContent = data.transaksi_hari_ini;
            document.getElementById('countDistribThisM').textContent = data.distribusi_bulan_ini;
            // Update group table
            // Replace tbody in one operation to minimize layout reflow and jank
            const stokWrapper = document.getElementById('stok-group-table');
            const oldTbody = document.querySelector('#stok-group-table tbody');
            // Preserve wrapper height to minimize layout jumps during tbody replacement
            let oldHeight = 0;
            try { if (oldTbody) oldHeight = oldTbody.getBoundingClientRect().height; } catch(e) { oldHeight = 0; }
            if (stokWrapper && oldHeight > 0) stokWrapper.style.minHeight = oldHeight + 'px';
            const newTbody = document.createElement('tbody');
            if (data.stok_group && data.stok_group.length > 0) {
                data.stok_group.forEach(function(g){
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (g.nama_gol_darah || 'N/A') + ' ' + (g.rhesus || '') + '</td>'+
                                   '<td><span class="badge" style="background: #c62828;">' + g.total_kantong + '</span></td>';
                    newTbody.appendChild(tr);
                });
            } else {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="2" class="text-center">Tidak ada stok tersedia</td>';
                newTbody.appendChild(tr);
            }
            if (oldTbody && oldTbody.parentNode) oldTbody.parentNode.replaceChild(newTbody, oldTbody);
            // restore wrapper min-height after a short delay so layout can stabilize
            if (stokWrapper) setTimeout(function(){ try { stokWrapper.style.minHeight = ''; } catch(e) {} }, 200);
            // Update last updated time
            const now = new Date();
            const fmt = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
            const lu = document.getElementById('dashboardLastUpdated');
            if (lu) lu.textContent = 'Last updated: ' + fmt;
        })
        .catch(err => { /* suppressed: error encountered while refreshing dashboard */ });
}

// Attach IDs to current counts to be updated
document.addEventListener('DOMContentLoaded', function() {
    // Create IDs for the counters in a defensive way (cards may not have specific bg classes)
    try {
        const metrics = document.querySelectorAll('.stat-card .card-metric, .card.card-tile .card-metric');
        if (metrics && metrics.length > 0) {
            if (metrics[0]) metrics[0].id = 'countPendonor';
            if (metrics[1]) metrics[1].id = 'countStok';
            if (metrics[2]) metrics[2].id = 'countTransToday';
            if (metrics[3]) metrics[3].id = 'countDistribThisM';
        }
    } catch (e) { /* suppressed: counter ID assignment error */ }
    // Start polling and kick off an immediate refresh
    setInterval(refreshDashboard, 10000); // every 10 seconds
    refreshDashboard();
});

</script>

<?php include Path::template('footer.php'); ?>