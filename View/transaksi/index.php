<!-- View/transaksi/index.php -->
<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

// Ambil data dari controller melalui variabel $data
$transaksi_list = $data['transaksi'] ?? []; // Gunakan data dari controller
$page = $data['page'] ?? 1;
$search = $data['search'] ?? '';
$total_items = $data['total_items'] ?? 0;
$items_per_page = $data['items_per_page'] ?? 5;
$total_pages = $data['total_pages'] ?? 1; // Ambil dari controller

// Debug: Hapus baris ini setelah selesai debugging
// echo "<pre>"; print_r($data); echo "</pre>";

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Transaksi Donasi</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=transaksi_create" class="btn btn-create">
            <i class="fas fa-plus me-1"></i>Transaksi Baru
        </a>
    </div>
</div>

<!-- Search Box -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="action" value="transaksi">
            <input type="text" name="search" class="form-control" placeholder="Cari nama pendonor, kegiatan, atau petugas..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
            <?php if (!empty($search)): ?>
            <a href="?action=transaksi" class="btn btn-secondary"><i class="fas fa-times"></i> Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($search)): ?>
        <p class="text-muted">Menampilkan <?= count($transaksi_list) ?> dari <?= $total_items ?> hasil pencarian untuk "<strong><?= htmlspecialchars($search) ?></strong>"</p>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Pendonor</th>
                        <th>Tanggal Donor</th>
                        <th>Kegiatan</th>
                        <th>Jumlah Kantong</th>
                        <th>Aksi</th> <!-- Kolom Aksi -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transaksi_list) > 0): ?>
                        <?php foreach ($transaksi_list as $transaksi): ?>
                        <tr>
                            <td>T<?= str_pad($transaksi['id_transaksi'], 3, '0', STR_PAD_LEFT) ?></td>
                            <td>
                                <?= htmlspecialchars($transaksi['nama_pendonor']) ?>
                                <?php if (!empty($transaksi['nama_gol_darah'])): ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($transaksi['nama_gol_darah']) ?><?= htmlspecialchars($transaksi['rhesus'] ?? '') ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($transaksi['tanggal_donasi'])) ?></td>
                            <td><?= htmlspecialchars($transaksi['nama_kegiatan']) ?></td>
                            <td><?= (int)$transaksi['jumlah_kantong'] ?> kantong</td>
                            <td>
                                <a href="?action=transaksi_detail&id=<?= $transaksi['id_transaksi'] ?>" class="btn btn-sm" style="background: #c62828; color: white; border: none;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?action=transaksi_edit&id=<?= $transaksi['id_transaksi'] ?>" class="btn btn-warning btn-sm me-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">Belum ada transaksi donasi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-3">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Awal</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Sebelumnya</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?= $i ?></span>
                        </li>
                    <?php elseif ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                        <li class="page-item">
                            <a class="page-link" href="?action=transaksi&page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $total_pages - 1): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Selanjutnya</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?action=transaksi&page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Akhir</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include Path::template('footer.php'); ?>