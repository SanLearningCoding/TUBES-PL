<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// Controller should provide $pendonor and $golongan
$pendonor = $pendonor ?? null;
$golongans = $golongan ?? [];
if (!is_array($golongans) || count($golongans) === 0) {
    require_once __DIR__ . '/../../Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
    $stmt->execute();
    $golongans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Jika data tidak ditemukan
if (!$pendonor) {
    echo "<div class='alert alert-danger m-4'>Data pendonor tidak ditemukan!<br><a href='?action=pendonor' class='btn' style='background: #c62828; color: white; border: none; margin-top: 0.5rem;'>Kembali ke Data Pendonor</a></div>";
    include Path::template('footer.php');
    exit;
}

// Mapping penyakit untuk checkboxes
$diseaseMap = [
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
?>

<style>
* {
    box-sizing: border-box;
}
body, html {
    overflow-x: hidden;
}
.container-compact {
    max-width: 100%;
    padding: 0 10px;
}
.header-compact {
    margin-bottom: 0.8rem;
    padding-bottom: 0.8rem;
}
.header-compact h1 {
    font-size: 1.5rem;
    margin-bottom: 0;
}
.card-compact {
    margin-bottom: 0.5rem;
    overflow-x: auto;
}
.card-body {
    padding: 0.8rem;
}
.form-group-compact {
    margin-bottom: 0.6rem;
}
.form-group-compact label {
    display: block;
    margin-bottom: 0.2rem;
    font-size: 0.9rem;
    font-weight: 500;
}
.form-control, textarea, select {
    font-size: 0.85rem !important;
    padding: 0.35rem 0.5rem !important;
    height: auto !important;
}
input[type="text"], input[type="email"], input[type="date"] {
    width: 100%;
}
.checkbox-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.3rem;
}
@media (min-width: 576px) {
    .checkbox-grid {
        grid-template-columns: 1fr 1fr;
    }
}
.form-check {
    margin-bottom: 0.3rem;
    font-size: 0.9rem;
}
.form-check-label {
    margin-left: 0.3rem;
    margin-bottom: 0;
    padding-top: 0.05rem;
}
.section-title {
    font-size: 0.95rem;
    font-weight: 600;
    margin-top: 0.6rem;
    margin-bottom: 0.4rem;
}
.section-note {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.4rem;
}
.btn-group-compact {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}
.btn-group-compact .btn {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
    white-space: nowrap;
}
.select-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}
@media (min-width: 768px) {
    .select-row {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<div class="detail-page-header">
    <h1>Edit Data Pendonor</h1>
    <a href="?action=pendonor" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

    <div class="card card-compact">
        <div class="card-body">
            <form action="?action=pendonor_update&id=<?= $pendonor['id_pendonor'] ?>" method="POST">
                <!-- Nama dan Kontak -->
                <div class="select-row">
                    <div class="form-group-compact">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" class="form-control" id="nama" name="nama" 
                               value="<?= htmlspecialchars($pendonor['nama']) ?>" required>
                    </div>
                    <div class="form-group-compact">
                        <label for="kontak">Kontak *</label>
                        <input type="text" class="form-control" id="kontak" name="kontak" 
                               value="<?= htmlspecialchars($pendonor['kontak']) ?>" required>
                    </div>
                </div>

                <!-- Screening Section -->
                <div class="form-group-compact" style="margin-top: 0.8rem;">
                    <div class="section-title">Riwayat Penyakit (Screening)</div>
                    <div class="section-note">Centang jika pendonor memiliki kondisi berikut (jika salah satu dicentang, pendonor akan otomatis ditandai Tidak Layak)</div>
                    
                    <div class="checkbox-grid">
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_hepatitis_b" id="has_hepatitis_b" value="1" <?= !empty($pendonor['has_hepatitis_b']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_hepatitis_b">Hepatitis B</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_thalassemia" id="has_thalassemia" value="1" <?= !empty($pendonor['has_thalassemia']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_thalassemia">Thalasemia</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_hepatitis_c" id="has_hepatitis_c" value="1" <?= !empty($pendonor['has_hepatitis_c']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_hepatitis_c">Hepatitis C</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_leukemia" id="has_leukemia" value="1" <?= !empty($pendonor['has_leukemia']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_leukemia">Leukemia</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_aids" id="has_aids" value="1" <?= !empty($pendonor['has_aids']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_aids">AIDS / HIV</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_lymphoma" id="has_lymphoma" value="1" <?= !empty($pendonor['has_lymphoma']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_lymphoma">Limfoma</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_hemofilia" id="has_hemofilia" value="1" <?= !empty($pendonor['has_hemofilia']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_hemofilia">Hemofilia</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_myeloma" id="has_myeloma" value="1" <?= !empty($pendonor['has_myeloma']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_myeloma">Myeloma</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_sickle_cell" id="has_sickle_cell" value="1" <?= !empty($pendonor['has_sickle_cell']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_sickle_cell">Penyakit Sel Sabit</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_cjd" id="has_cjd" value="1" <?= !empty($pendonor['has_cjd']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="has_cjd">CJD</label>
                        </div>
                    </div>

                    <!-- Riwayat Penyakit (Display) -->
                    <div style="margin-top: 0.6rem;">
                        <label style="font-size: 0.9rem; font-weight: 500; display: block; margin-bottom: 0.2rem;">Riwayat Penyakit Otomatis:</label>
                        <input type="text" class="form-control" id="riwayat_penyakit_display" readonly style="font-size: 0.85rem !important; padding: 0.35rem 0.5rem !important; background-color: #f8f9fa;" value="<?= htmlspecialchars($pendonor['riwayat_penyakit']) ?>" placeholder="(akan otomatis terisi saat mencentang penyakit)">
                        <input type="hidden" name="riwayat_penyakit" id="riwayat_penyakit_hidden" value="<?= htmlspecialchars($pendonor['riwayat_penyakit']) ?>">
                    </div>

                    <!-- Penyakit Lain -->
                    <div style="margin-top: 0.6rem;">
                        <label for="other_illness" style="font-size: 0.9rem; font-weight: 500; display: block; margin-bottom: 0.2rem;">Penyakit Lain (opsional)</label>
                        <textarea class="form-control" id="other_illness" name="other_illness" rows="2" placeholder="Sebutkan penyakit lain jika ada" style="font-size: 0.85rem !important; padding: 0.35rem 0.5rem !important;"><?= htmlspecialchars($pendonor['other_illness'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Golongan Darah -->
                <div class="form-group-compact" style="margin-top: 0.8rem;">
                    <label for="id_gol_darah" style="font-size: 0.9rem; font-weight: 500;">Golongan Darah</label>
                    <select id="id_gol_darah" name="id_gol_darah" class="form-control">
                        <option value="">Pilih Golongan</option>
                        <?php if (count($golongans) === 0): ?>
                            <option value="" disabled>Tidak ada data golongan darah</option>
                        <?php else: ?>
                            <?php foreach ($golongans as $g): ?>
                            <option value="<?= $g['id_gol_darah'] ?>" <?= $g['id_gol_darah'] == ($pendonor['id_gol_darah'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($g['nama_gol_darah']) ?> <?= $g['rhesus'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Tombol -->
                <div class="btn-group-compact">
                    <button type="reset" class="btn btn-secondary btn-sm">Reset</button>
                    <button type="submit" class="btn btn-sm" style="background: #c62828; color: white; border: none;">Update Pendonor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pemetaan checkbox ke nama penyakit
const diseaseMap = {
    'has_hepatitis_b': 'Hepatitis B',
    'has_hepatitis_c': 'Hepatitis C',
    'has_aids': 'AIDS / HIV',
    'has_hemofilia': 'Hemofilia',
    'has_sickle_cell': 'Penyakit Sel Sabit',
    'has_thalassemia': 'Thalasemia',
    'has_leukemia': 'Leukemia',
    'has_lymphoma': 'Limfoma',
    'has_myeloma': 'Myeloma',
    'has_cjd': 'CJD'
};

// Update riwayat_penyakit saat checkbox berubah atau other_illness diubah
function updateRiwayatPenyakit() {
    let diseases = [];
    
    // Ambil semua checkbox yang dicentang
    for (let key in diseaseMap) {
        if (document.getElementById(key) && document.getElementById(key).checked) {
            diseases.push(diseaseMap[key]);
        }
    }
    
    // Ambil nilai other_illness jika ada
    let otherIllness = document.getElementById('other_illness');
    if (otherIllness && otherIllness.value.trim() !== '') {
        diseases.push(otherIllness.value.trim());
    }
    
    // Isi display dan hidden field
    let riwayatText = diseases.length > 0 ? diseases.join(', ') : '';
    document.getElementById('riwayat_penyakit_display').value = riwayatText;
    document.getElementById('riwayat_penyakit_hidden').value = riwayatText;
}

// Tambahkan event listener ke semua checkbox dan other_illness textarea
document.addEventListener('DOMContentLoaded', function() {
    for (let key in diseaseMap) {
        let checkbox = document.getElementById(key);
        if (checkbox) {
            checkbox.addEventListener('change', updateRiwayatPenyakit);
        }
    }
    
    // Tambahkan event listener untuk other_illness textarea
    let otherIllnessField = document.getElementById('other_illness');
    if (otherIllnessField) {
        otherIllnessField.addEventListener('change', updateRiwayatPenyakit);
        otherIllnessField.addEventListener('blur', updateRiwayatPenyakit);
    }
});
</script>

<?php include Path::template('footer.php'); ?>