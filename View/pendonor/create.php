<?php 
// View/pendonor/create.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 
?>
<?php
$golongans = $golongan ?? [];
if (!is_array($golongans) || count($golongans) === 0) {
    require_once __DIR__ . '/../../Config/Database.php';
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare('SELECT id_gol_darah, nama_gol_darah, rhesus FROM golongan_darah ORDER BY nama_gol_darah');
    $stmt->execute();
    $golongans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
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
    padding: 0 15px;
    margin: 0 auto;
}
.header-compact {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e0e0e0;
}
.header-compact h1 {
    font-size: 1.5rem;
    margin-bottom: 0;
    color: #333;
    font-weight: 700;
}
.card-compact {
    margin-bottom: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.card-body {
    padding: 1.25rem;
}
.form-group-compact {
    margin-bottom: 1rem;
}
.form-group-compact label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
}
.form-control, textarea, select {
    font-size: 0.95rem !important;
    padding: 0.5rem 0.75rem !important;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.form-control:focus {
    border-color: #c62828;
    box-shadow: 0 0 0 0.2rem rgba(198, 40, 40, 0.15);
}
/* Validasi styling dihapus - hanya menggunakan popup alert */
input[type="text"], input[type="email"], input[type="date"] {
    width: 100%;
}
.checkbox-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.5rem;
}
@media (min-width: 576px) {
    .checkbox-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (min-width: 768px) {
    .checkbox-grid {
        grid-template-columns: 1fr 1fr 1fr;
    }
}
.form-check {
    margin-bottom: 0;
    font-size: 0.9rem;
}
.form-check-label {
    margin-left: 0.5rem;
    margin-bottom: 0;
    padding-top: 0.2rem;
    cursor: pointer;
}
.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
}
.section-note {
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 1rem;
    font-style: italic;
}
.select-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}
@media (min-width: 768px) {
    .select-row {
        grid-template-columns: 1fr 1fr;
    }
}
.btn-group-compact {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.btn-group-compact .btn {
    flex: 1;
    min-width: 150px;
}
@media (max-width: 576px) {
    .btn-group-compact .btn {
        width: 100%;
    }
}

/* Checkbox dan label penyakit tetap hitam - tidak terpengaruh validasi */
.checkbox-grid .form-check-input {
    background-color: #fff !important;
    border-color: #ccc !important;
    color: #333 !important;
}

.checkbox-grid .form-check-input:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}

.checkbox-grid .form-check-label {
    color: #333 !important;
    font-size: 0.9rem !important;
}

.section-title,
.section-note {
    color: #333 !important;
}
</style>

<div class="detail-page-header">
    <h1>Tambah Pendonor Baru</h1>
    <a href="?action=pendonor" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

    <div class="card card-compact">
        <div class="card-body">
            <form action="?action=pendonor_store_controller" method="POST" id="pendonorForm"> <!-- Ganti nama action -->
                <!-- Nama dan Kontak -->
                <div class="select-row">
                    <div class="form-group-compact">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" class="form-control" id="nama" name="nama" required value="<?= htmlspecialchars($old_nama ?? '') ?>">
                    </div>
                    <div class="form-group-compact">
                        <label for="kontak">Kontak (Nomor Telepon) *</label>
                        <input type="tel" class="form-control" id="kontak" name="kontak" required pattern="[0-9]+" inputmode="numeric" value="<?= htmlspecialchars($old_kontak ?? '') ?>" placeholder="Contoh: 081234567890">
                    </div>
                </div>

                <!-- Screening Section -->
                <div class="form-group-compact" style="margin-top: 0.8rem;">
                    <div class="section-title">Riwayat Penyakit (Screening)</div>
                    <div class="section-note">Centang jika pendonor memiliki kondisi berikut (jika salah satu dicentang, pendonor akan otomatis ditandai Tidak Layak)</div>
                    
                    <div class="checkbox-grid">
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_hepatitis_b" id="has_hepatitis_b" value="1">
                            <label class="form-check-label" for="has_hepatitis_b">Hepatitis B</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_thalassemia" id="has_thalassemia" value="1">
                            <label class="form-check-label" for="has_thalassemia">Thalasemia</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_hepatitis_c" id="has_hepatitis_c" value="1">
                            <label class="form-check-label" for="has_hepatitis_c">Hepatitis C</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_leukemia" id="has_leukemia" value="1">
                            <label class="form-check-label" for="has_leukemia">Leukemia</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_aids" id="has_aids" value="1">
                            <label class="form-check-label" for="has_aids">AIDS / HIV</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_lymphoma" id="has_lymphoma" value="1">
                            <label class="form-check-label" for="has_lymphoma">Limfoma</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_hemofilia" id="has_hemofilia" value="1">
                            <label class="form-check-label" for="has_hemofilia">Hemofilia</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_myeloma" id="has_myeloma" value="1">
                            <label class="form-check-label" for="has_myeloma">Myeloma</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_sickle_cell" id="has_sickle_cell" value="1">
                            <label class="form-check-label" for="has_sickle_cell">Penyakit Sel Sabit</label>
                        </div>
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="has_cjd" id="has_cjd" value="1">
                            <label class="form-check-label" for="has_cjd">CJD</label>
                        </div>
                    </div>

                    <!-- Riwayat Penyakit (Display) -->
                    <div style="margin-top: 0.6rem;">
                        <label style="font-size: 0.9rem; font-weight: 500; display: block; margin-bottom: 0.2rem;">Riwayat Penyakit Otomatis:</label>
                        <input type="text" class="form-control" id="riwayat_penyakit_display" readonly style="font-size: 0.85rem !important; padding: 0.35rem 0.5rem !important; background-color: #f8f9fa;" placeholder="(akan otomatis terisi saat mencentang penyakit)">
                        <input type="hidden" name="riwayat_penyakit" id="riwayat_penyakit_hidden">
                    </div>

                    <!-- Penyakit Lain -->
                    <div style="margin-top: 0.6rem;">
                        <label for="other_illness" style="font-size: 0.9rem; font-weight: 500; display: block; margin-bottom: 0.2rem;">Penyakit Lain (opsional)</label>
                        <textarea class="form-control" id="other_illness" name="other_illness" rows="2" placeholder="Sebutkan penyakit lain jika ada" style="font-size: 0.85rem !important; padding: 0.35rem 0.5rem !important;"></textarea>
                    </div>
                </div>

                <!-- Golongan Darah -->
                <div class="form-group-compact" style="margin-top: 0.8rem;">
                    <label for="id_gol_darah" style="font-size: 0.9rem; font-weight: 500;">Golongan Darah *</label>
                    <select id="id_gol_darah" name="id_gol_darah" class="form-control" required>
                        <option value="">Pilih Golongan</option>
                        <?php if (count($golongans) === 0): ?>
                            <option value="" disabled>Tidak ada data golongan darah</option>
                        <?php else: ?>
                            <?php foreach ($golongans as $g): ?>
                            <option value="<?= $g['id_gol_darah'] ?>" <?= isset($old_id_gol) && $old_id_gol == $g['id_gol_darah'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nama_gol_darah']) ?> <?= $g['rhesus'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Tombol -->
                <div class="btn-group-compact">
                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Simpan Pendonor</button>
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
    
    // Set up validation for required fields
    const namaField = document.getElementById('nama');
    const kontakField = document.getElementById('kontak');
    const golonganField = document.getElementById('id_gol_darah');
    
    // Setup invalid event listeners
    if (namaField) {
        namaField.addEventListener('invalid', function(e) {
            if (this.validity.valueMissing) {
                this.setCustomValidity('Nama Lengkap wajib diisi');
            }
        });
        namaField.addEventListener('input', function() {
            this.setCustomValidity('');
        });
    }
    
    if (kontakField) {
        kontakField.addEventListener('invalid', function(e) {
            if (this.validity.valueMissing) {
                this.setCustomValidity('Kontak (Nomor Telepon) wajib diisi');
            } else if (this.validity.patternMismatch) {
                this.setCustomValidity('Kontak hanya boleh berisi angka');
            }
        });
        kontakField.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
            this.setCustomValidity('');
        });
    }
    
    if (golonganField) {
        golonganField.addEventListener('invalid', function(e) {
            if (this.validity.valueMissing) {
                this.setCustomValidity('Golongan Darah wajib dipilih');
            }
        });
        golonganField.addEventListener('change', function() {
            this.setCustomValidity('');
        });
    }
});
</script>

<?php include Path::template('footer.php'); ?>

