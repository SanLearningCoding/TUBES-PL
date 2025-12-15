<!DOCTYPE html>
<html lang="id">
<head>
    <!-- View/template/header.php -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Donor Darah PMI</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css  " rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins  :wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css  ">
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <!-- Custom UI enhancements -->
    <link href="View/template/assets/css/ui.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Custom color overrides - uncomment CSS rules di file ini untuk customize warna -->
    <link href="View/template/assets/css/custom-overrides.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        /* --- Dropdown Kustom untuk Profil --- */

        /* Kontainer dropdown */
        .dropdown-custom-wrapper {
          position: relative;
          display: inline-block;
        }

        /* Gaya tombol toggle */
        .dropdown-toggle-custom {
          /* Turunan dari .nav-link dan .user-pill */
          padding: 0.5rem 1rem;
          text-decoration: none;
          border: 1px solid transparent;
          border-radius: 50rem; /* Membuatnya bulat */
          display: flex;
          align-items: center;
          gap: 0.5rem;
          cursor: pointer;
          background: none;
          color: inherit;
          /* Efek hover */
          transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
        }

        .dropdown-toggle-custom:hover {
          background-color: #f8f9fa;
          border-color: #dee2e6;
        }

        /* Gaya avatar di dalam tombol */
        .avatar-custom {
          width: 36px;
          height: 36px;
          background: var(--pmi-red); /* Gunakan variabel dari ui.css */
          color: white;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          border-radius: 50%;
          font-weight: 700;
          /* box-shadow: 0 6px 18px rgba(198, 40, 40, 0.12); */ /* Opsional: hilangkan jika tidak diinginkan */
        }

        /* Gaya menu dropdown */
        .dropdown-menu-custom {
          position: absolute;
          top: 100%;
          right: 0; /* Muncul di kanan, tepat di bawah tombol */
          z-index: 1000; /* Agar muncul di atas elemen lain */
          min-width: 160px; /* Lebar minimum */
          padding: 0.25rem 0;
          margin: 0.125rem 0 0 0; /* Jarak dari tombol */
          font-size: 1rem;
          color: #212529;
          text-align: left;
          list-style: none;
          background-color: #fff;
          background-clip: padding-box;
          border: 1px solid rgba(0, 0, 0, 0.15);
          border-radius: 0.375rem;
          box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
          display: none; /* Sembunyikan secara default */
          opacity: 0;
          visibility: hidden;
          transform: translateY(-10px);
          transition: opacity 0.2s ease, visibility 0.2s ease, transform 0.2s ease;
        }

        /* Tampilkan menu saat aktif */
        .dropdown-menu-custom.show {
          display: block;
          opacity: 1;
          visibility: visible;
          transform: translateY(0);
        }

        /* Gaya item menu */
        .dropdown-item-custom {
          display: block;
          width: 100%;
          padding: 0.5rem 1rem;
          clear: both;
          font-weight: 400;
          color: #212529;
          text-align: inherit;
          text-decoration: none;
          white-space: nowrap;
          background-color: transparent;
          border: 0;
          border-radius: 0; /* Hilangkan border item jika tidak diinginkan */
        }

        .dropdown-item-custom:hover {
          background-color: #f8f9fa;
          color: #1e2125;
        }

        .dropdown-item-custom.text-danger {
          color: #dc3545;
        }

        .dropdown-item-custom.text-danger:hover {
          background-color: #f8d7da;
          color: #a71e2a;
        }

        /* Gaya divider */
        .dropdown-divider-custom {
          height: 0;
          margin: 0.25rem 0;
          overflow: hidden;
          border-top: 1px solid rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="app-ui">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top p-0">
        <div class="container-fluid px-3">
            <a class="navbar-brand d-flex align-items-center gap-2" href="?action=dashboard">
                <span class="brand-badge d-inline-flex align-items-center justify-content-center"><i class="fas fa-tint fa-lg text-white"></i></span>
                <div class="d-none d-lg-block">
                    <div class="brand-title">Palang Merah Indonesia</div>
                    <div class="brand-sub">Sistem Manajemen Donor Darah</div>
                </div>
            </a>

            <!-- Navbar Collapse diubah menjadi selalu 'show' agar tautan selalu muncul -->
            <div class="navbar-collapse justify-content-center show" id="navbarNav">
                <ul class="navbar-nav gap-2 mx-auto">
                    <li class="nav-item"><a class="nav-link" href="?action=dashboard"><i class="fas fa-home me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="?action=pendonor"><i class="fas fa-users me-1"></i>Pendonor</a></li>
                    <li class="nav-item"><a class="nav-link" href="?action=transaksi"><i class="fas fa-hand-holding-heart me-1"></i>Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="?action=stok"><i class="fas fa-prescription-bottle me-1"></i>Stok Darah</a></li>
                    <li class="nav-item"><a class="nav-link" href="?action=distribusi"><i class="fas fa-truck me-1"></i>Distribusi</a></li>
                    <li class="nav-item d-none d-lg-block"><a class="nav-link" href="?action=kegiatan"><i class="fas fa-calendar-alt me-1"></i>Kegiatan</a></li>
                </ul>
            </div>
                <div class="d-flex align-items-center ms-auto gap-2">
                    <!-- Dropdown Profil Kustom -->
                    <div class="dropdown-custom-wrapper">
                        <?php $initial = isset($_SESSION['nama_petugas']) && strlen($_SESSION['nama_petugas']) ? strtoupper(substr($_SESSION['nama_petugas'], 0, 1)) : 'G'; ?>
                        <!-- Tombol Toggle Dropdown -->
                        <button type="button" class="nav-link dropdown-toggle-custom d-flex align-items-center gap-2 user-pill-custom" aria-haspopup="true" aria-expanded="false" id="profileDropdownButton" onclick="toggleCustomDropdown(event)">
                            <span class="avatar-custom"><?= htmlspecialchars($initial) ?></span>
                            <span class="d-none d-sm-inline"><?php echo isset($_SESSION['nama_petugas']) ? htmlspecialchars($_SESSION['nama_petugas']) : 'Guest'; ?></span>
                        </button>

                        <!-- Menu Dropdown -->
                        <div class="dropdown-menu-custom" id="profileDropdownMenu" role="menu">
                            <hr class="dropdown-divider-custom">
                            <?php if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']): ?>
                                <a class="dropdown-item-custom text-danger d-flex align-items-center gap-2" href="?action=logout">
                                    <i class="fas fa-right-from-bracket"></i> Logout
                                </a>
                            <?php else: ?>
                                <a class="dropdown-item-custom d-flex align-items-center gap-2" href="?action=login">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
        </div>
    </nav>

    <!-- Alerts & Toast containers -->
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <?php include __DIR__ . '/alerts.php'; ?>
                <?php include __DIR__ . '/toast.php'; ?>
            </div>
        </div>
    </div>

    <!-- Main content wrapper: full width with global page wrapper to avoid edge-to-edge content -->
    <main class="container-fluid mt-4 page-wrapper">
        <div class="row">
            <div class="col-12 main-content">
                <!-- Konten halaman akan dimasukkan di sini oleh file view masing-masing -->
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js  "></script>
    <!-- Custom JS -->
    <script src="View/template/assets/js/ui.js?v=<?php echo time(); ?>"></script>
    <script>
        // Fungsi untuk toggle dropdown kustom
        function toggleCustomDropdown(event) {
            event.preventDefault(); // Cegah perilaku default jika ada
            event.stopPropagation(); // Hentikan penyebaran event

            const button = event.currentTarget;
            const menu = document.getElementById('profileDropdownMenu');
            const isShown = menu.classList.contains('show');

            // Tutup semua dropdown kustom lain (jika ada)
            closeAllCustomDropdowns();

            // Toggle dropdown saat ini
            if (!isShown) {
                menu.classList.add('show');
                // Fokus ke menu untuk aksesibilitas (opsional)
                // menu.focus();
            }
        }

        // Fungsi untuk menutup semua dropdown kustom
        function closeAllCustomDropdowns() {
            const menus = document.querySelectorAll('.dropdown-menu-custom.show');
            menus.forEach(menu => {
                menu.classList.remove('show');
            });
        }

        // Fungsi untuk menutup dropdown saat klik di luar
        document.addEventListener('click', function(event) {
            const dropdownWrapper = event.target.closest('.dropdown-custom-wrapper');
            const isOpen = document.getElementById('profileDropdownMenu').classList.contains('show');

            if (isOpen && !dropdownWrapper) {
                closeAllCustomDropdowns();
            }
        });

        // Fungsi untuk menutup dropdown saat menekan Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAllCustomDropdowns();
            }
        });
    </script>
    <!-- Footer scripts -->
</body>
</html>