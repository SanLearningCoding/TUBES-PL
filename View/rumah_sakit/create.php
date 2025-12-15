<?php 
// View/rumah_sakit/create.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php');

// Siapkan database untuk validasi
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Jika ada request validasi nama (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_nama'])) {
    header('Content-Type: application/json');
    $nama_rs = trim($_POST['check_nama']);
    if (empty($nama_rs)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM rumah_sakit WHERE LOWER(TRIM(nama_rs)) = LOWER(TRIM(?)) AND is_deleted = 0");
    $stmt->execute([$nama_rs]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $exists = ($result && $result['cnt'] > 0);
    echo json_encode(['exists' => $exists]);
    exit;
}
?>

<div class="detail-page-header">
    <h1>Tambah Rumah Sakit Mitra</h1>
    <a href="?action=rumah_sakit" class="btn btn-back">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="?action=rumah_sakit_store" method="POST" id="rsForm" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nama_rs" class="form-label">Nama Rumah Sakit *</label>
                        <input type="text" class="form-control" id="nama_rs" name="nama_rs" 
                               placeholder="Contoh: RS Umum Daerah Balikpapan" required>
                        <div id="nama_error" style="color: #c62828; font-size: 0.85rem; margin-top: 0.25rem; display: none;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                           <label for="kontak" class="form-label">Kontak (minimal 6 angka) *</label>
                           <input type="tel" class="form-control" id="kontak" name="kontak" 
                               placeholder="Contoh: 08123456789" required minlength="6" maxlength="20" inputmode="numeric">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat Lengkap *</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3" 
                          placeholder="Contoh: Jl. Jendral Sudirman No. 1, Balikpapan" required></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Rumah Sakit</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rsForm');
    const namaField = document.getElementById('nama_rs');
    const kontakField = document.getElementById('kontak');
    const alamatField = document.getElementById('alamat');
    const namaError = document.getElementById('nama_error');
    const submitBtn = document.getElementById('submitBtn');
    
    let isNamaDuplicate = false;
    let checkTimeout;
    
    // Validasi nama real-time untuk cek duplikat
    namaField.addEventListener('input', function() {
        const namaValue = this.value.trim();
        
        // Clear timeout sebelumnya
        clearTimeout(checkTimeout);
        
        if (!namaValue) {
            namaError.style.display = 'none';
            isNamaDuplicate = false;
            return;
        }
        
        // Debounce: tunggu user selesai mengetik (500ms)
        checkTimeout = setTimeout(() => {
            // Check via AJAX
            const formData = new FormData();
            formData.append('check_nama', namaValue);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    namaError.textContent = 'Nama Rumah Sakit ini sudah ada. Gunakan nama yang berbeda.';
                    namaError.style.display = 'block';
                    isNamaDuplicate = true;
                } else {
                    namaError.style.display = 'none';
                    isNamaDuplicate = false;
                }
            })
            .catch(err => console.error('Error:', err));
        }, 500);
    });
    
    // Setup validation untuk field kontak
    kontakField.addEventListener('invalid', function(e) {
        if (this.validity.valueMissing) {
            this.setCustomValidity('Kontak wajib diisi');
        } else if (this.validity.tooShort) {
            this.setCustomValidity('Kontak harus minimal 6 angka');
        }
    });
    
    kontakField.addEventListener('input', function(e) {
        // Hanya biarkan angka
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
        this.setCustomValidity('');
    });
    
    // Setup validation untuk field nama
    namaField.addEventListener('invalid', function(e) {
        if (this.validity.valueMissing) {
            this.setCustomValidity('Nama Rumah Sakit wajib diisi');
        }
    });
    
    namaField.addEventListener('input', function() {
        this.setCustomValidity('');
    });
    
    // Setup validation untuk field alamat
    alamatField.addEventListener('invalid', function(e) {
        if (this.validity.valueMissing) {
            this.setCustomValidity('Alamat Lengkap wajib diisi');
        }
    });
    
    alamatField.addEventListener('input', function() {
        this.setCustomValidity('');
    });
    
    // Override form submit untuk custom validation
    form.addEventListener('submit', function(e) {
        // Cek apakah ada error nama duplikat
        if (isNamaDuplicate) {
            e.preventDefault();
            namaField.focus();
            return false;
        }
        
        // Validasi kontak length lagi
        if (kontakField.value.replace(/[^0-9]/g, '').length < 6) {
            e.preventDefault();
            kontakField.setCustomValidity('Kontak harus minimal 6 angka');
            kontakField.reportValidity();
            return false;
        }
    });
});
</script>

<?php include Path::template('footer.php'); ?>