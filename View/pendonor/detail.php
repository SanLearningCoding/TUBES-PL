<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 
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
        <h1>Detail Pendonor</h1>
        <a href="?action=pendonor" class="btn btn-back">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <?php if ($pendonor): ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header card-header-red">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Dasar Pendonor</h5>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <span class="info-label">ID Pendonor</span>
                        <span class="info-value">P<?= str_pad($pendonor['id_pendonor'], 3, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nama</span>
                        <span class="info-value"><?= htmlspecialchars($pendonor['nama']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kontak</span>
                        <span class="info-value"><?= htmlspecialchars($pendonor['kontak']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Golongan Darah</span>
                        <span class="info-value"><?= htmlspecialchars($pendonor['nama_gol_darah'] ?? 'N/A') ?> <?= htmlspecialchars($pendonor['rhesus'] ?? '') ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-red">
                    <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Status Kesehatan</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $disease_map = [
                        'has_hepatitis_b' => 'Hepatitis B',
                        'has_hepatitis_c' => 'Hepatitis C',
                        'has_aids' => 'AIDS / HIV',
                        'has_hemofilia' => 'Hemofilia',
                        'has_sickle_cell' => 'Penyakit Sel Sabit',
                        'has_thalassemia' => 'Thalasemia',
                        'has_leukemia' => 'Leukemia',
                        'has_lymphoma' => 'Limfoma',
                        'has_myeloma' => 'Myeloma',
                        'has_cjd' => 'CJD'
                    ];
                    
                    // Tentukan status kesehatan dari field is_layak
                    // is_layak = 0: TIDAK LAYAK (ada penyakit dari 9 screening)
                    // is_layak = 1: LAYAK (hanya ada other_illness)
                    // is_layak = 2: SEHAT (tidak ada penyakit apapun)
                    // Fallback: jika is_layak NULL/tidak ada, cek riwayat_penyakit untuk screening diseases
                    
                    $is_layak_value = $pendonor['is_layak'] ?? null;
                    $status = 'unknown'; // default
                    
                    if ($is_layak_value !== null && $is_layak_value !== '') {
                        // Kolom is_layak ada dan punya nilai
                        if ($is_layak_value == 0) {
                            $status = 'tidak_layak';
                        } elseif ($is_layak_value == 1) {
                            $status = 'layak';
                        } else {
                            $status = 'sehat';
                        }
                    } else {
                        // Fallback: cek riwayat_penyakit untuk lihat apakah ada screening disease
                        $riwayat = $pendonor['riwayat_penyakit'] ?? '';
                        $has_screening = false;
                        foreach ($disease_map as $col => $disease_name) {
                            if (stripos($riwayat, $disease_name) !== false) {
                                $has_screening = true;
                                break;
                            }
                        }
                        
                        if ($has_screening) {
                            $status = 'tidak_layak'; // Ada screening disease = tidak layak
                        } elseif (!empty($riwayat) || !empty($pendonor['other_illness'])) {
                            $status = 'layak'; // Ada hanya penyakit lain/other_illness = layak
                        } else {
                            $status = 'sehat'; // Tidak ada penyakit = sehat
                        }
                    }
                    
                    // Kumpulkan penyakit untuk ditampilkan (dari 9 screening + other_illness)
                    $screening_diseases = [];
                    foreach ($disease_map as $col => $name) {
                        if (isset($pendonor[$col]) && !empty($pendonor[$col])) {
                            $screening_diseases[] = $name;
                        }
                    }
                    
                    // Juga tambahkan other_illness jika ada
                    $other_illness_val = isset($pendonor['other_illness']) ? $pendonor['other_illness'] : '';
                    
                    // Tampilkan status dengan warna berbeda
                    if ($status == 'tidak_layak') {
                        ?>
                        <div class="alert alert-danger mb-3">
                            <strong>Status: TIDAK LAYAK DONOR</strong>
                        </div>
                        <h6>Riwayat Penyakit:</h6>
                        <div class="alert alert-light border">
                            <?= htmlspecialchars($pendonor['riwayat_penyakit'] ?? 'Tidak ada catatan penyakit') ?>
                        </div>
                        <?php
                    } elseif ($status == 'layak') {
                        ?>
                        <div class="alert alert-warning mb-3">
                            <strong>Status: LAYAK DONOR</strong> <small class="text-muted">(dengan catatan kesehatan)</small>
                        </div>
                        <h6>Riwayat Penyakit:</h6>
                        <div class="alert alert-light border">
                            <?= htmlspecialchars($pendonor['riwayat_penyakit'] ?? 'Tidak ada catatan penyakit') ?>
                        </div>
                        <?php
                    } else { // sehat
                        ?>
                        <div class="alert alert-success mb-3">
                            <strong>Status: SEHAT - LAYAK DONOR</strong>
                        </div>
                        <p class="text-muted">Tidak ada riwayat penyakit yang tercatat.</p>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header card-header-red">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi</h5>
                </div>
                <div class="card-body">
                    <a href="?action=pendonor_edit&id=<?= $pendonor['id_pendonor'] ?>" class="btn w-100 mb-2">
                        <i class="fas fa-edit"></i>Edit Data
                    </a>
                    <a href="?action=pendonor_riwayat&id=<?= $pendonor['id_pendonor'] ?>" class="btn w-100 mb-2">
                        <i class="fas fa-history"></i>Riwayat Donasi
                    </a>
                    <button onclick="confirmDelete(<?= $pendonor['id_pendonor'] ?>)" class="btn w-100">
                        <i class="fas fa-trash"></i>Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger">
        Data pendonor tidak ditemukan.
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data pendonor ini?')) {
        window.location.href = '?action=pendonor_soft_delete&id=' + id;
    }
}
</script>

<?php include Path::template('footer.php'); ?>
