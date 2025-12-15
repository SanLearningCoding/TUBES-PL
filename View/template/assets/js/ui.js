// UI micro-interactions & Notification Functions
// View/template/assets/js/ui.js

// Display flash message dari session jika ada (harus dijalankan setelah DOM dan fungsi showToast siap)
document.addEventListener('DOMContentLoaded', function() {
    // Ambil data flash dari PHP jika disisipkan oleh PHP
    // Contoh: jika di header.php Anda menambahkan:
    // <script>
    //   window.phpFlash = <?php echo json_encode($_SESSION['flash'] ?? null); ?>;
    //   delete <?php unset($_SESSION['flash']); ?>;
    // </script>
    // Maka Anda bisa menggunakannya seperti ini:
    // if (window.phpFlash) {
    //     showToast(window.phpFlash.message, window.phpFlash.type, 3000, window.phpFlash.title);
    // }

    // Atau jika Anda ingin tetap menggunakan inline PHP di toast.php (meskipun tidak disarankan untuk JS besar),
    // pastikan toast.php di-include di bawah definisi fungsi ini di HTML sebelum script ini dimuat.
    // Karena kita memindahkan semuanya ke sini, bagian ini bisa dihapus dari toast.php.
});

// Fungsi untuk menampilkan notifikasi toast (muncul di atas)
function showToast(message, type = 'success', duration = 3000, title = null) {
    const container = document.getElementById('toast-container');
    if (!container) {
        console.error("Toast container '#toast-container' tidak ditemukan.");
        return;
    }

    const icons = {
        'success': 'fas fa-check-circle',
        'danger': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    };

    const titles = {
        'success': 'Berhasil',
        'danger': 'Gagal',
        'info': 'Informasi'
    };

    const toastTitle = title || titles[type] || 'Notifikasi';

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="${icons[type]} toast-icon"></i>
        <div class="toast-content">
            <div class="toast-title">${toastTitle}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="removeToast(this.closest('.toast-notification'))">&times;</button>
    `;

    container.appendChild(toast);

    if (duration > 0) {
        setTimeout(() => {
            removeToast(toast);
        }, duration);
    }
}

// Fungsi untuk menghapus notifikasi toast
function removeToast(element) {
    if (element) {
        element.classList.add('removing');
        setTimeout(() => {
            element.remove();
        }, 300);
    }
}

// Fungsi untuk menampilkan notifikasi alert (muncul di atas halaman)
function showPageAlert(message, type = 'success', duration = 3000) {
    const container = document.getElementById('alert-container');
    if (!container) {
        console.error("Alert container '#alert-container' tidak ditemukan.");
        return;
    }

    const icons = {
        'success': 'fas fa-check-circle',
        'danger': 'fas fa-exclamation-circle',
        'info': 'fas fa-info-circle'
    };

    const alert = document.createElement('div');
    alert.className = `alert-notification ${type}`;
    alert.innerHTML = `
        <i class="${icons[type]} alert-icon"></i>
        <div class="alert-content">${message}</div>
        <button class="alert-close" onclick="removeAlert(this.closest('.alert-notification'))">&times;</button>
    `;

    container.appendChild(alert);

    if (duration > 0) {
        setTimeout(() => {
            removeAlert(alert);
        }, duration);
    }
}

// Fungsi untuk menghapus notifikasi alert
function removeAlert(element) {
    if (element) {
        element.classList.add('removing');
        setTimeout(() => {
            element.remove();
        }, 300);
    }
}

// Fungsi untuk menampilkan konfirmasi kustom
function showCustomConfirm(message, onConfirm) {
    // Create modal overlay
    const backdrop = document.createElement('div');
    backdrop.className = 'custom-modal-backdrop';

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'custom-modal-dialog';
    modal.innerHTML = `
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h5 class="custom-modal-title">Konfirmasi Tindakan</h5>
            </div>
            <div class="custom-modal-body">
                ${message}
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-lg" data-confirm="true">Ya</button>
            </div>
        </div>
    `;

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);

    // Handle button clicks
    const cancelBtn = modal.querySelector('[data-dismiss="modal"]');
    const confirmBtn = modal.querySelector('[data-confirm="true"]');

    const closeModal = () => {
        backdrop.classList.add('closing');
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    };

    cancelBtn.addEventListener('click', closeModal);
    confirmBtn.addEventListener('click', () => {
        closeModal();
        onConfirm();
    });

    // Close on backdrop click
    backdrop.addEventListener('click', (e) => {
        if (e.target === backdrop) {
            closeModal();
        }
    });
}

// Fungsi untuk menghapus item (menggunakan modal konfirmasi)
function deleteItem(id, action, table, clickEvent) {
    let row = null;
    if (clickEvent && clickEvent.target) {
        row = clickEvent.target.closest('button')?.closest('tr') ||
              clickEvent.currentTarget?.closest('tr');
    }

    if (!row) {
        const buttons = document.querySelectorAll(`button[onclick*="${id}"]`);
        if (buttons.length > 0) {
            row = buttons[0].closest('tr');
        }
    }

    let itemName = '';

    if (row) {
        let targetColumn = 1;
        if (table === 'stok') targetColumn = 0;
        else if (table === 'kegiatan') targetColumn = 0;
        else if (table === 'transaksi') targetColumn = 1;
        else if (table === 'pendonor') targetColumn = 1;
        else if (table === 'rumah_sakit') targetColumn = 1;
        else if (table === 'distribusi') targetColumn = 1;

        const nameTd = row.querySelector(`td:nth-child(${targetColumn + 1})`);
        if (nameTd) {
            itemName = nameTd.textContent.trim().split('\n')[0];
        }
    }

    let confirmMsg = 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    if (itemName) {
        confirmMsg = `Apakah Anda yakin ingin menghapus "${itemName}"?`;
    }

    showCustomConfirm(confirmMsg, () => {
        performDelete(id, action, row);
    });
}

// Fungsi untuk menjalankan penghapusan via API
function performDelete(id, action, row) {
    if (!row) {
        row = document.querySelector(`tr:has(button[onclick*="${id}"])`);
    }

    fetch('api_delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: id,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (row) {
                row.style.transition = 'opacity 0.3s ease-out, height 0.3s ease-out';
                row.style.opacity = '0';
                row.style.height = '0';
                row.style.overflow = 'hidden';
                row.style.paddingTop = '0';
                row.style.paddingBottom = '0';

                setTimeout(() => {
                    row.remove();
                }, 300);
            }
            showPageAlert('Data berhasil dipindahkan', 'success', 3000);
        } else {
            let errorMessage = data.message || 'Gagal menghapus data';
            showPageAlert(errorMessage, 'danger', 5000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showPageAlert('Terjadi kesalahan saat menghapus data', 'danger', 5000);
    });
}

// --- Kode lama ui.js ---
document.addEventListener("DOMContentLoaded", function () {
  // Brand badge micro animation
  var badge = document.querySelector(".brand-badge");
  if (badge) {
    badge.animate(
      [
        { transform: "translateY(0)" },
        { transform: "translateY(-2px)" },
        { transform: "translateY(0)" },
      ],
      { duration: 600, easing: "ease-out" }
    );
  }

  // Active nav highlighting (based on ?action=...)
  var urlParams = new URLSearchParams(window.location.search);
  var action = urlParams.get("action") || "dashboard";
  var navLinks = document.querySelectorAll(".navbar .nav-link");
  navLinks.forEach(function (link) {
    if (
      link.getAttribute("href") &&
      link.getAttribute("href").includes("action=" + action)
    ) {
      link.classList.add("active");
    }
  });

  // Auto-hide flash alerts after 5s (hanya alert dismissible dari session, bukan alert di card body)
  var flashAlerts = document.querySelectorAll(".alert.alert-dismissible");
  flashAlerts.forEach(function (a) {
    setTimeout(function () {
      try {
        // Pastikan bootstrap.Alert tersedia
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(a);
            bsAlert.close();
        } else {
            a.style.display = "none";
        }
      } catch (e) {
        a.style.display = "none";
      }
    }, 5200);
  });

  // Show/Hide password for auth pages
  var pwToggles = document.querySelectorAll(".password-toggle");
  pwToggles.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      var card = btn.closest(".auth-card");
      if (!card) return;
      var pw = card.querySelector('input[type="password"], input[type="text"]');
      if (!pw) return;
      if (pw.type === "password") {
        pw.type = "text";
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        pw.type = "password";
        btn.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
  });

  // Subtle parallax on auth wrapper (mouse move) for added polish
  var authWrapper = document.querySelector(".auth-wrapper");
  if (
    authWrapper &&
    window.matchMedia("(prefers-reduced-motion: no-preference)").matches
  ) {
    authWrapper.addEventListener("mousemove", function (e) {
      var rect = authWrapper.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width - 0.5; // -0.5 .. 0.5
      var y = (e.clientY - rect.top) / rect.height - 0.5;
      var card = authWrapper.querySelector(".auth-card");
      var badge = authWrapper.querySelector(".brand-badge");
      if (card) card.style.transform = `translate3d(${x * 6}px, ${y * 6}px, 0)`;
      if (badge)
        badge.style.transform = `translate3d(${x * -6}px, ${y * -6}px, 0)`;
    });
    authWrapper.addEventListener("mouseleave", function () {
      var card = authWrapper.querySelector(".auth-card");
      var badge = authWrapper.querySelector(".brand-badge");
      if (card) card.style.transform = "";
      if (badge) badge.style.transform = "";
    });
  }
  // Ensure inputs in auth-card expose full content via tooltip and auto-scroll on focus
  var authInputs = document.querySelectorAll(
    '.auth-card input[type="email"], .auth-card input[type="text"], .auth-card input[type="password"]'
  );
  authInputs.forEach(function (inp) {
    // set initial title for hover
    if (inp.value && inp.value.length > 20)
      inp.setAttribute("title", inp.value);
    // update title as user types
    inp.addEventListener("input", function (e) {
      if (e.target.value && e.target.value.length > 20)
        e.target.setAttribute("title", e.target.value);
      else e.target.removeAttribute("title");
    });
    // try to scroll to the end on focus so the user sees the end of their email
    inp.addEventListener("focus", function (e) {
      try {
        e.target.scrollLeft = e.target.scrollWidth;
      } catch (err) {}
    });
  });
});