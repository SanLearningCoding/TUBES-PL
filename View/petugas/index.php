<?php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

$petugas_list = $petugas ?? [];
?>
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
	<h1 class="h2">Data Petugas</h1>
	<div class="btn-toolbar">
		<!-- management disabled -->
	</div>
</div>

<div class="card">
	<div class="card-body text-center p-5">
		<h3 class="mb-3">Fitur manajemen data petugas dinonaktifkan</h3>
		<p class="text-muted mb-3">Fitur penambahan, penghapusan, dan pengelolaan akun petugas nonaktif untuk sistem ini.</p>
		<a href="?action=dashboard" class="btn btn-pmi">Kembali ke Dashboard</a>
	</div>
</div>

<?php include Path::template('footer.php'); ?>
