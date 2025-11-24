<?php include '../template/header.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Riwayat Donor - Budi Santoso</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="pendonor" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<!-- Riwayat Donor -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Tanggal Donor</th>
                        <th>Kegiatan</th>
                        <th>Jumlah Kantong</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>15 Nov 2024</td>
                        <td>Donor Rutin PMI</td>
                        <td>1 Kantong</td>
                        <td><span class="badge bg-success">Selesai</span></td>
                    </tr>
                    <tr>
                        <td>10 Sep 2024</td>
                        <td>Donor Darah Kampus</td>
                        <td>1 Kantong</td>
                        <td><span class="badge bg-success">Selesai</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>