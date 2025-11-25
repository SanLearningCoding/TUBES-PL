<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT COUNT(*) as total FROM pendonor";
$stmt = $db->prepare($query);
$stmt->execute();
$total_pendonor = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$total_stok = 0;
$transaksi_hari_ini = 0;
$distribusi_bulan_ini = 0;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard Sistem PMI</h1>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Pendonor</h5>
                        <h2><?= $total_pendonor ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Stok Tersedia</h5>
                        <h2><?= $total_stok ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-prescription-bottle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Transaksi Hari Ini</h5>
                        <h2><?= $transaksi_hari_ini ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-hand-holding-heart fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Distribusi Bulan Ini</h5>
                        <h2><?= $distribusi_bulan_ini ?></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-truck fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>