<?php 
// View/transaksi/trash.php
include __DIR__ . '/../../Config/Path.php';
include Path::template('header.php'); 

require_once __DIR__ . '/../../Config/Database.php';
$database = new Database();
$db = $database->getConnection();

// AMBIL DATA TRANSAKSI YANG DIARSIPKAN (is_deleted = 1)
$query = "SELECT td.*, 
                 p.nama AS nama_pendonor,
                 kd.nama_kegiatan
          FROM transaksi_donasi td
          LEFT JOIN pendonor p ON td.id_pendonor = p.id_pendonor
          LEFT JOIN kegiatan_donasi kd ON td.id_kegiatan = kd.id_kegiatan
          WHERE td.is_deleted = 1
          ORDER BY td.deleted_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$trashed_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-page-header">
    <h1><i class="fas fa-archive me-2"></i>Arsip Transaksi Donor</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="?action=transaksi" class="btn btn-back me-2">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
        <?php if (count($trashed_list) > 0): ?>
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
        <?php if (count($trashed_list) > 0): ?>
        <div class="mb-3">
            <div class="d-flex gap-2">
                <span id="selectionInfo" class="text-muted align-self-center">
                    <small id="selectedCountText">0 data terpilih</small>
                </span>
                <!-- Tombol Batal Pilihan Massal (awalnya disembunyikan) -->
                <button id="cancelSelectionBtn" type="button" class="btn btn-sm btn-outline-secondary ms-auto" style="display:none;" onclick="cancelBulkSelection('transaksi')">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 30px;">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input" 
                                   onchange="toggleSelectAll(this, 'transaksi')">
                        </th>
                        <th>ID</th>
                        <th>Pendonor</th>
                        <th>Kegiatan</th>
                        <th>Tanggal Donor</th>
                        <th>Dihapus Pada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trashed_list as $t): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input transaksi-checkbox" 
                                   value="<?= $t['id_transaksi'] ?>" 
                                   onchange="updateBulkDeleteButton('transaksi')">
                        </td>
                        <td>T<?= str_pad($t['id_transaksi'], 3, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($t['nama_pendonor'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($t['nama_kegiatan'] ?? '-') ?></td>
                        <td>
                            <?= $t['tanggal_donasi'] 
                                ? date('d/m/Y', strtotime($t['tanggal_donasi'])) 
                                : '-' ?>
                        </td>
                        <td>
                            <?= $t['deleted_at'] 
                                ? date('d/m/Y H:i', strtotime($t['deleted_at'])) 
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
            <h5 class="text-muted">Tidak ada data dalam Arsip Transaksi Donasi</h5>
            <p class="text-muted mb-0">Transaksi donasi yang diarsipkan akan muncul di sini.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
    // Fungsi untuk toggle select all
    function toggleSelectAll(source, className) {
        const checkboxes = document.querySelectorAll('.' + className + '-checkbox');
        checkboxes.forEach(checkbox => checkbox.checked = source.checked);
        updateBulkDeleteButton(className);
        updateCancelButton(className);
    }

    // Fungsi untuk update tombol bulk delete/restore dan info seleksi
    function updateBulkDeleteButton(className) {
        const selectedCheckboxes = document.querySelectorAll('.' + className + '-checkbox:checked');
        const count = selectedCheckboxes.length;
        const restoreBtn = document.getElementById('bulkRestoreBtn');
        const deleteBtn = document.getElementById('bulkDeleteAllBtn');
        const selectionInfo = document.getElementById('selectionInfo');
        const cancelBtn = document.getElementById('cancelSelectionBtn');

        if (restoreBtn && deleteBtn) {
            if (count > 0) {
                restoreBtn.style.display = 'inline-block';
                deleteBtn.style.display = 'inline-block';
                selectionInfo.style.display = 'block';
                cancelBtn.style.display = 'inline-block'; // Tampilkan tombol batal
            } else {
                restoreBtn.style.display = 'none';
                deleteBtn.style.display = 'none';
                selectionInfo.style.display = 'none';
                cancelBtn.style.display = 'none'; // Sembunyikan tombol batal
            }
        }
        document.getElementById('selectedCountText').textContent = count + ' data terpilih';
    }

    // Fungsi untuk batal pilihan massal
    function cancelBulkSelection(className) {
        const checkboxes = document.querySelectorAll('.' + className + '-checkbox');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        checkboxes.forEach(checkbox => checkbox.checked = false);
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        updateBulkDeleteButton(className);
        // Tombol batal otomatis disembunyikan oleh updateBulkDeleteButton
    }

    // Fungsi untuk update tampilan tombol batal
    function updateCancelButton(className) {
        const selectedCheckboxes = document.querySelectorAll('.' + className + '-checkbox:checked');
        const cancelBtn = document.getElementById('cancelSelectionBtn');
        if (cancelBtn) {
            if (selectedCheckboxes.length > 0) {
                cancelBtn.style.display = 'inline-block';
            } else {
                cancelBtn.style.display = 'none';
            }
        }
    }

    // Inisialisasi saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        // Tambahkan event listener onchange ke semua checkbox individu
        const individualCheckboxes = document.querySelectorAll('.transaksi-checkbox');
        individualCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkDeleteButton('transaksi');
                updateCancelButton('transaksi');
            });
        });

        // Tambahkan event listener onchange ke checkbox "select all"
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                // Fungsi toggleSelectAll akan memanggil updateBulkDeleteButton dan updateCancelButton
            });
        }

        // Tambahkan event listener ke tombol restore massal
        const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
        if (bulkRestoreBtn) {
            bulkRestoreBtn.addEventListener('click', function() {
                bulkRestoreSelected('transaksi');
            });
        }

         // Tambahkan event listener ke tombol hapus permanen massal
        const bulkDeleteBtn = document.getElementById('bulkDeleteAllBtn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                bulkDeleteSelected('transaksi');
            });
        }

        // Panggil sekali untuk menginisialisasi tampilan tombol jika ada data terpilih (misalnya dari state sebelumnya)
        updateBulkDeleteButton('transaksi');
        updateCancelButton('transaksi');
    });

    // Fungsi untuk restore massal
    function bulkRestoreSelected(table) {
        const checkboxes = document.querySelectorAll('.' + table + '-checkbox:checked');
        if (checkboxes.length === 0) {
            showPageAlert('Pilih minimal 1 data untuk dipulihkan', 'info', 3000);
            return;
        }

        // Definisikan 'ids' di awal fungsi
        const ids = Array.from(checkboxes).map(cb => cb.value);
        // Panggil showCustomConfirm dan kirim 'ids' dan 'table' ke fungsi callback
        showCustomConfirm(`Apakah Anda yakin ingin memulihkan ${ids.length} data terpilih dari arsip?`, () => {
            // Fungsi callback yang dipanggil jika pengguna menekan 'Ya'
            // 'ids' dan 'table' tersedia di sini karena closure
            bulkRestoreItems(ids, table);
        });
    }

        // Fungsi untuk hapus permanen massal
    function bulkDeleteSelected(table) {
        const checkboxes = document.querySelectorAll('.' + table + '-checkbox:checked');
        if (checkboxes.length === 0) {
            showPageAlert('Pilih minimal 1 data untuk dihapus', 'info', 3000);
            return;
        }

        // Definisikan 'ids' di awal fungsi
        const ids = Array.from(checkboxes).map(cb => cb.value);
        // Panggil showCustomConfirm dan kirim 'ids' dan 'table' ke fungsi callback
        showCustomConfirm(`Apakah Anda yakin ingin menghapus PERMANEN ${checkboxes.length} data terpilih?<br><strong>Peringatan: Ini tidak dapat dikembalikan!</strong>`, () => {
            // Fungsi callback yang dipanggil jika pengguna menekan 'Ya'
            // 'ids' dan 'table' tersedia di sini karena closure
            bulkDeleteItems(ids, table);
        });
    }

    // Fungsi untuk restore items (menggunakan API)
    function bulkRestoreItems(ids, table) {
        // Validasi parameter
        if (!Array.isArray(ids) || ids.length === 0 || typeof table !== 'string' || table.trim() === '') {
            console.error("bulkRestoreItems: Invalid parameters received.", {ids, table});
            // Tampilkan alert error umum
            showPageAlert('Parameter untuk pemulihan tidak valid.', 'danger', 5000);
            return;
        }

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
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Restore API Response:", data);

            if (typeof data.success !== 'boolean') {
                 console.error("Restore API: Unexpected response format", data);
                 // Tampilkan alert error umum
                 showPageAlert('Respon dari server tidak valid.', 'danger', 5000);
                 return;
            }

            if (data.success) {
                try {
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
                    updateCancelButton(table); // Pastikan tombol batal juga diupdate

                    // Tampilkan alert success menggunakan page alert
                    showPageAlert(`${ids.length} data berhasil dipulihkan dari arsip`, 'success', 3000);
                } catch (innerError) {
                     console.error("Error during successful restore handling:", innerError);
                     // Tampilkan alert error jika gagal memproses sukses (misalnya error DOM)
                     showPageAlert('Terjadi kesalahan saat memperbarui tampilan setelah sukses.', 'danger', 5000);
                }
            } else {
                // Respons sukses tetapi operasi gagal di server
                showPageAlert(data.message || 'Gagal memulihkan data', 'danger', 5000);
            }
        })
        .catch(error => {
            console.error('Restore error:', error); // Log error untuk debugging
            // Tampilkan alert error jika fetch gagal (jaringan, bukan JSON, error server)
            showPageAlert('Error mengembalikan data: ' + error.message, 'danger', 5000);
        });
    }

    // Fungsi untuk hapus items (menggunakan API)
    function bulkDeleteItems(ids, table) {
        // Validasi parameter
        if (!Array.isArray(ids) || ids.length === 0 || typeof table !== 'string' || table.trim() === '') {
            console.error("bulkDeleteItems: Invalid parameters received.", {ids, table});
            // Tampilkan alert error umum
            showPageAlert('Parameter untuk penghapusan tidak valid.', 'danger', 5000);
            return;
        }

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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Delete API Response:", data);

            if (typeof data.success !== 'boolean') {
                 console.error("Delete API: Unexpected response format", data);
                 // Tampilkan alert error umum
                 showPageAlert('Respon dari server tidak valid.', 'danger', 5000);
                 return;
            }

            if (data.success) {
                try {
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

                    // Reset bulk delete button
                    setTimeout(() => {
                        const bulkRestoreBtn = document.getElementById('bulkRestoreBtn');
                        const bulkDeleteBtn = document.getElementById('bulkDeleteAllBtn');
                        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
                        const selectedCountText = document.getElementById('selectedCountText');

                        if (bulkRestoreBtn) bulkRestoreBtn.style.display = 'none';
                        if (bulkDeleteBtn) bulkDeleteBtn.style.display = 'none';
                        if (selectAllCheckbox) selectAllCheckbox.checked = false;
                        if (selectedCountText) selectedCountText.textContent = '0 data terpilih';

                        updateBulkDeleteButton(table);
                        updateCancelButton(table); // Pastikan tombol batal juga diupdate
                    }, rowsToDelete.length * 50 + 300);

                    // Show success notification
                    showPageAlert(`${ids.length} data berhasil dihapus secara permanen`, 'success', 3000);
                } catch (innerError) {
                     console.error("Error during successful delete handling:", innerError);
                     // Tampilkan alert error jika gagal memproses sukses (misalnya error DOM)
                     showPageAlert('Terjadi kesalahan saat memperbarui tampilan setelah sukses.', 'danger', 5000);
                }
            } else {
                // Respons sukses tetapi operasi gagal di server
                showPageAlert(data.message || 'Gagal menghapus data', 'danger', 5000);
            }
        })
        .catch(error => {
            console.error('Delete error:', error); // Log error untuk debugging
            // Tampilkan alert error jika fetch gagal (jaringan, bukan JSON, error server)
            showPageAlert('Error menghapus data: ' + error.message, 'danger', 5000);
        });
    }

    // Fungsi untuk deleteItem individual (jika ada) - Juga menggunakan showPageAlert
    function deleteItem(id, action, name, event) {
        event.preventDefault();
        let confirmationMessage = '';
        let successMessage = '';
        let errorMessage = '';

        if (action.includes('permanent_delete')) {
            confirmationMessage = `Apakah Anda yakin ingin menghapus ${name} secara permanen?`;
            successMessage = `${name} berhasil dihapus permanen.`;
            errorMessage = `Gagal menghapus ${name} secara permanen.`;
        } else if (action.includes('restore')) {
            confirmationMessage = `Apakah Anda yakin ingin memulihkan ${name} dari arsip?`;
            successMessage = `${name} berhasil dipulihkan.`;
            errorMessage = `Gagal memulihkan ${name}.`;
        } else {
            confirmationMessage = `Apakah Anda yakin ingin menghapus ${name}?`;
            successMessage = `${name} berhasil dihapus.`;
            errorMessage = `Gagal menghapus ${name}.`;
        }

        // Ganti confirm dengan showCustomConfirm
        showCustomConfirm(confirmationMessage, () => {
            fetch(`index.php?action=${action}&id=${id}`, {
                method: 'GET',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ganti alert dengan showPageAlert
                    showPageAlert(data.message || successMessage, 'success', 3000);
                    location.reload(); // Refresh halaman setelah sukses
                } else {
                    // Ganti alert dengan showPageAlert
                    showPageAlert(data.message || errorMessage, 'danger', 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Ganti alert dengan showPageAlert
                showPageAlert(errorMessage, 'danger', 5000);
            });
        });
    }
</script>

<?php include Path::template('footer.php'); ?>