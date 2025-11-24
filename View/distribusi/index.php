<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Distribusi Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=distribusi_create" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Tambah Distribusi
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Halaman Distribusi Darah - Dalam Pengembangan
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Distribusi</th>
                        <th>Rumah Sakit</th>
                        <th>Tanggal Distribusi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>D001</td>
                        <td>RS Umum Daerah</td>
                        <td>15/11/2024</td>
                        <td><span class="badge bg-success">Terkirim</span></td>
                        <td>
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>D002</td>
                        <td>RS Siloam</td>
                        <td>16/11/2024</td>
                        <td><span class="badge bg-warning">Proses</span></td>
                        <td>
                            <button class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include Path::template('footer.php'); ?>