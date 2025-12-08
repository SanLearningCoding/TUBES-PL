<?php 
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// Ambil stok darah yang sudah di-soft delete (is_deleted = 1)
$query = "SELECT sd.*, gd.nama_gol_darah, gd.rhesus
          FROM stok_darah sd
          LEFT JOIN golongan_darah gd ON sd.id_gol_darah = gd.id_gol_darah
          WHERE sd.is_deleted = 1
          ORDER BY sd.deleted_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$stok_arsip = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1><i class="fas fa-archive me-2"></i>Arsip Stok Darah</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=stok" class="btn btn-back me-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <?php if (count($stok_arsip) > 0): ?>
        <button id="bulkRestoreBtn" type="button"
                class="btn-icon-only me-2" style="display: none; color: #B71C1C;" title="Pulihkan semua data terpilih">
            <span class="material-symbols-outlined">unarchive</span>
        </button>
        <button id="bulkDeleteAllBtn" type="button"
                class="btn-icon-only me-2" style="display: none; color: #B71C1C;" title="Hapus permanen semua data terpilih">
            <i class="fas fa-trash"></i>
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($stok_arsip) > 0): ?>
        <div class="mb-3">
            <div class="d-flex gap-2">
                <span id="selectionInfo" class="text-muted align-self-center">
                    <small id="selectedCountText">0 data terpilih</small>
                </span>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 30px;">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input" 
                                   onchange="toggleSelectAll(this, 'stok')">
                        </th>
                        <th>ID Stok</th>
                        <th>Golongan Darah</th>
                        <th>Jumlah Kantong</th>
                        <th>Kadaluarsa</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stok_arsip as $stok): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input stok-checkbox" 
                                   value="<?= $stok['id_stok'] ?>" 
                                   onchange="updateBulkDeleteButton('stok')">
                        </td>
                        <td>SD<?= str_pad($stok['id_stok'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($stok['nama_gol_darah'] ?? 'N/A') ?> <?= $stok['rhesus'] ?? '' ?></td>
                        <td><?= (int)($stok['jumlah_kantong'] ?? 0) ?> kantong</td>
                        <td><?= htmlspecialchars($stok['status'] ?? '-') ?></td>
                        <td>
                            <?= $stok['deleted_at'] 
                                ? date('d/m/Y H:i', strtotime($stok['deleted_at'])) 
                                : '-' ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm me-1" 
                                onclick="deleteItem(<?= $stok['id_stok'] ?>, 'stok_restore', 'stok', event)">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">unarchive</span>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" 
                                onclick="deleteItem(<?= $stok['id_stok'] ?>, 'stok_permanent_delete', 'stok', event)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-archive fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Tidak ada data dalam Arsip Stok Darah</h5>
            <p class="text-muted mb-0">Stok darah yang diarsipkan akan muncul di sini.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add change listeners to all checkboxes
    const checkboxes = document.querySelectorAll('.stok-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton('stok');
        });
    });
    
    // Add change listener to select all checkbox
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleSelectAll(this, 'stok');
        });
    }
    
    // Add click listeners to action buttons
    const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteAllBtn');
    
    if (bulkRestoreBtn) {
        bulkRestoreBtn.addEventListener('click', function() {
            bulkRestoreSelected('stok');
        });
    }
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            bulkDeleteSelected('stok');
        });
    }
});

function toggleSelectAll(checkbox, table) {
    const checkboxes = document.querySelectorAll('.' + table + '-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkDeleteButton(table);
}

function updateBulkDeleteButton(table) {
    const checkboxes = document.querySelectorAll('.' + table + '-checkbox:checked');
    const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteAllBtn');
    const selectedCountText = document.getElementById('selectedCountText');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    
    if (checkboxes.length > 0) {
        bulkRestoreBtn.style.display = 'inline-flex';
        bulkDeleteBtn.style.display = 'inline-flex';
        selectedCountText.textContent = checkboxes.length + ' data terpilih';
        
        // Update select all checkbox
        const allCheckboxes = document.querySelectorAll('.' + table + '-checkbox');
        selectAllCheckbox.checked = checkboxes.length === allCheckboxes.length;
    } else {
        bulkRestoreBtn.style.display = 'none';
        bulkDeleteBtn.style.display = 'none';
        selectedCountText.textContent = '0 data terpilih';
        selectAllCheckbox.checked = false;
    }
}

function bulkDeleteSelected(table) {
    const checkboxes = document.querySelectorAll('.' + table + '-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal 1 data untuk dihapus');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    showCustomConfirm(`Apakah Anda yakin ingin menghapus PERMANEN ${ids.length} data terpilih?<br><strong>Peringatan: Ini tidak dapat dikembalikan!</strong>`, () => {
        bulkDeleteItems(ids, table);
    });
}

function bulkRestoreSelected(table) {
    const checkboxes = document.querySelectorAll('.' + table + '-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal 1 data untuk dipulihkan');
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    showCustomConfirm(`Apakah Anda yakin ingin memulihkan ${ids.length} data terpilih dari arsip?`, () => {
        bulkRestoreItems(ids, table);
    });
}

function bulkDeleteItems(ids, table) {
    // Collect rows to delete
    const rowsToDelete = ids.map(id => document.querySelector(`tr:has([value="${id}"])`))
                            .filter(row => row !== null);
    
    const apiUrl = '<?= Path::API() ?>api_delete.php';
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ids: ids,
            action: table + '_bulk_permanent_delete'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Animate removal of rows
            rowsToDelete.forEach((row, index) => {
                setTimeout(() => {
                    row.style.transition = 'opacity 0.3s ease-out, height 0.3s ease-out';
                    row.style.opacity = '0';
                    row.style.height = '0';
                    row.style.overflow = 'hidden';
                    row.style.paddingTop = '0';
                    row.style.paddingBottom = '0';
                    
                    setTimeout(() => row.remove(), 300);
                }, index * 50);
            });
            
            showPageAlert(
                `${ids.length} data berhasil dihapus secara permanen`,
                'success',
                3000
            );
            
            // Reset bulk delete button
            setTimeout(() => {
                document.getElementById('bulkRestoreBtn').style.display = 'none';
                document.getElementById('bulkDeleteAllBtn').style.display = 'none';
                document.getElementById('selectAllCheckbox').checked = false;
                document.getElementById('selectedCountText').textContent = '0 data terpilih';
            }, rowsToDelete.length * 50 + 300);
        } else {
            showPageAlert(
                data.message || 'Gagal menghapus data',
                'danger',
                5000
            );
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showPageAlert(
            'Error: ' + error.message,
            'danger',
            5000
        );
    });
}

function bulkRestoreItems(ids, table) {
    const apiUrl = '<?= Path::API() ?>api_delete.php';
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            ids: ids,
            action: table + '_bulk_restore'
        })
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Langsung hapus row dari tabel tanpa delay
            const rowsToDelete = ids.map(id => document.querySelector(`tr:has([value="${id}"])`))
                                    .filter(row => row !== null);
            
            rowsToDelete.forEach((row, index) => {
                setTimeout(() => {
                    row.style.transition = 'opacity 0.3s ease-out, height 0.3s ease-out';
                    row.style.opacity = '0';
                    row.style.height = '0';
                    row.style.overflow = 'hidden';
                    row.style.paddingTop = '0';
                    row.style.paddingBottom = '0';
                    
                    setTimeout(() => row.remove(), 300);
                }, index * 50);
            });
            
            // Update header checkbox dan tombol bulk
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateBulkDeleteButton(table);
            
            // Tampilkan alert success menggunakan page alert
            showPageAlert(`${ids.length} data berhasil dipulihkan dari arsip`, 'success', 3000);
        } else {
            showPageAlert(data.message || 'Gagal memulihkan data', 'danger', 5000);
        }
    })
    .catch(error => {
        console.error('Restore error:', error);
        showPageAlert('Error mengembalikan data', 'danger', 5000);
    });
}
</script>

<?php include Path::template('footer.php'); ?>
