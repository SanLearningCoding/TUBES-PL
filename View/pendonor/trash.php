<?php 
// View/pendonor/trash.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

// AMBIL DATA YANG DIHAPUS (SOFT DELETE)
require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM pendonor WHERE is_deleted = 1 ORDER BY deleted_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$deleted_pendonor = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1><i class="fas fa-archive me-2"></i>Arsip Pendonor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=pendonor" class="btn btn-back me-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <?php if (count($deleted_pendonor) > 0): ?>
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
        <?php if (count($deleted_pendonor) > 0): ?>
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
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                        </th>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Kontak</th>
                        <th>Dihapus Pada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_pendonor as $pendonor): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input pendonor-checkbox" 
                                   value="<?= $pendonor['id_pendonor'] ?>">
                        </td>
                        <td>P<?= str_pad($pendonor['id_pendonor'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($pendonor['nama']) ?></td>
                        <td><?= htmlspecialchars($pendonor['kontak']) ?></td>
                        <td>
                            <?= $pendonor['deleted_at'] 
                                ? date('d/m/Y H:i', strtotime($pendonor['deleted_at'])) 
                                : '-' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-archive fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Tidak ada data dalam Arsip Pendonor</h5>
            <p class="text-muted mb-0">Data pendonor yang diarsipkan akan muncul di sini.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add change listeners to all checkboxes
    const checkboxes = document.querySelectorAll('.pendonor-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkDeleteButton('pendonor');
        });
    });
    
    // Add change listener to select all checkbox
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            toggleSelectAll(this, 'pendonor');
        });
    }
    
    // Add click listeners to action buttons
    const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteAllBtn');
    
    if (bulkRestoreBtn) {
        bulkRestoreBtn.addEventListener('click', function() {
            bulkRestoreSelected('pendonor');
        });
    }
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            bulkDeleteSelected('pendonor');
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
    
    console.log('Checked checkboxes:', checkboxes.length); // Debug log
    
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
    console.log('Delete IDs:', ids); // Debug log
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
    console.log('Restore IDs:', ids); // Debug log
    showCustomConfirm(`Apakah Anda yakin ingin memulihkan ${ids.length} data terpilih dari arsip?`, () => {
        bulkRestoreItems(ids, table);
    });
}

function bulkDeleteItems(ids, table) {
    // Collect rows to delete
    const rowsToDelete = ids.map(id => document.querySelector(`tr:has([value="${id}"])`))
                            .filter(row => row !== null);
    
    const apiUrl = './api_delete.php';
    
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
    const apiUrl = './api_delete.php';
    console.log('Calling API:', apiUrl);
    console.log('Payload:', {
        ids: ids,
        action: table + '_bulk_restore'
    });
    
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
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Restore response:', data);
        if (data.success) {
            // Hapus row dari tabel dengan animasi
            const rowsToDelete = ids.map(id => document.querySelector(`input[value="${id}"]`)?.closest('tr'))
                                      .filter(row => row !== null && row !== undefined);
            
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
            
            // Reset bulk buttons dan counter
            setTimeout(() => {
                document.getElementById('bulkRestoreBtn').style.display = 'none';
                document.getElementById('bulkDeleteAllBtn').style.display = 'none';
                document.getElementById('selectedCountText').textContent = '0 data terpilih';
            }, rowsToDelete.length * 50 + 300);
            
            // Tampilkan alert success
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
