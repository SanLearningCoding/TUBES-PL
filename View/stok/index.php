<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Stok Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=stok_create" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Tambah Stok
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Halaman Stok Darah - Dalam Pengembangan
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Stok</th>
                        <th>Golongan Darah</th>
                        <th>Tanggal Kadaluarsa</th>
                        <th>Status</th>
                        <th>Volume</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>S001</td>
                        <td>A+</td>
                        <td>15/02/2025</td>
                        <td><span class="badge bg-success">Tersedia</span></td>
                        <td>450 ml</td>
                        <td>
                            <button class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>S002</td>
                        <td>B+</td>
                        <td>20/02/2025</td>
                        <td><span class="badge bg-success">Tersedia</span></td>
                        <td>450 ml</td>
                        <td>
                            <button class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>