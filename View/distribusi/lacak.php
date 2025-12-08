<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$id_stok = $_GET['id'] ?? 0;

$query = "SELECT sd.*, gd.nama_gol_darah, gd.rhesus, td.tanggal_donasi, p.nama as pendonor
		  FROM stok_darah sd
		  LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
		  LEFT JOIN transaksi_donasi td ON sd.id_transaksi = td.id_transaksi
		  LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
		  WHERE sd.id_stok = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id_stok]);
$stok = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT dd.*, rs.nama_rs FROM distribusi_darah dd
		  LEFT JOIN rumah_sakit rs ON dd.id_rs = rs.id_rs
		  WHERE dd.id_stok = ? ORDER BY dd.tanggal_distribusi DESC";
$stmt2 = $db->prepare($query);
$stmt2->execute([$id_stok]);
$history = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if (!$stok) {
	echo "<div class='alert alert-danger m-4'>Stok tidak ditemukan!<br><a href='?action=stok' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali</a></div>";
	include Path::template('footer.php');
	exit;
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
	<h1 class="h2">Lacak Stok S<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></h1>
	<div class="btn-toolbar mb-2 mb-md-0">
		<a href="?action=stok" class="btn btn-secondary">
			<i class="fas fa-arrow-left me-1"></i>Kembali
		</a>
	</div>
</div>

<div class="row">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header bg-info text-white">
				<h5 class="card-title mb-0">Informasi Stok</h5>
			</div>
			<div class="card-body">
				<table class="table table-borderless">
					<tr><th>ID Stok</th><td>S<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></td></tr>
					<tr><th>Golongan</th><td><?= htmlspecialchars($stok['nama_gol_darah'] ?? '') ?> <?= $stok['rhesus'] ?? '' ?></td></tr>
					<tr><th>Jumlah Kantong</th><td><?= $stok['jumlah_kantong'] ?? 1 ?> kantong</td></tr>
					<tr><th>Tanggal Pengujian</th><td><?= isset($stok['tanggal_pengujian']) && $stok['tanggal_pengujian'] ? date('d/m/Y', strtotime($stok['tanggal_pengujian'])) : '-' ?></td></tr>
					<tr><th>Tanggal Kadaluarsa</th><td><?= isset($stok['tanggal_kadaluarsa']) && $stok['tanggal_kadaluarsa'] ? date('d/m/Y', strtotime($stok['tanggal_kadaluarsa'])) : '-' ?></td></tr>
					<tr><th>Status</th><td><?= htmlspecialchars($stok['status'] ?? '') ?></td></tr>
				</table>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-header bg-primary text-white">
				<h5 class="card-title mb-0">Riwayat Distribusi</h5>
			</div>
			<div class="card-body">
				<?php if (count($history) > 0): ?>
					<ul class="list-group">
						<?php foreach ($history as $h): ?>
							<li class="list-group-item d-flex justify-content-between align-items-start">
								<div>
									<div class="fw-bold"><?= htmlspecialchars($h['nama_rs']) ?></div>
									<small>Tanggal: <?= isset($h['tanggal_distribusi']) && $h['tanggal_distribusi'] ? date('d/m/Y', strtotime($h['tanggal_distribusi'])) : 'N/A' ?></small>
								</div>
								<span class="badge bg-secondary rounded-pill">
									<?php 
                                        $st = $h['status'] ?? 'dikirim';
                                        if ($st === 'diterima') echo 'Diterima';
                                        elseif ($st === 'dikirim') echo 'Dikirim';
                                        elseif ($st === 'dibatalkan') echo 'Dibatalkan';
                                        else echo htmlspecialchars($st);
                                    ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else: ?>
					<div class="alert alert-warning">Belum ada distribusi untuk stok ini.</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php include Path::template('footer.php'); ?>

