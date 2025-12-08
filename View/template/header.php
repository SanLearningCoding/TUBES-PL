<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Donor Darah PMI</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=unarchive" />
    <!-- Custom UI enhancements -->
    <link href="View/template/assets/css/ui.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Custom color overrides - uncomment CSS rules di file ini untuk customize warna -->
    <link href="View/template/assets/css/custom-overrides.css?v=<?php echo time(); ?>" rel="stylesheet">
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

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNavOffcanvas" aria-controls="mainNavOffcanvas" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"><i class="fas fa-bars text-danger"></i></span>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
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
                <div class="dropdown">
                    <?php $initial = isset($_SESSION['nama_petugas']) && strlen($_SESSION['nama_petugas']) ? strtoupper(substr($_SESSION['nama_petugas'], 0, 1)) : 'G'; ?>
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 user-pill" href="#" role="button" data-bs-toggle="dropdown">
                        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
                        <span class="d-none d-sm-inline"><?php echo isset($_SESSION['nama_petugas']) ? htmlspecialchars($_SESSION['nama_petugas']) : 'Guest'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><hr class="dropdown-divider"></li>
                        <?php if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']): ?>
                            <li><a class="dropdown-item text-danger d-flex align-items-center gap-2" href="?action=logout"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="?action=login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Offcanvas for smaller screens containing main nav -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="mainNavOffcanvas" aria-labelledby="mainNavOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mainNavOffcanvasLabel">Palang Merah Indonesia</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="?action=dashboard"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=pendonor"><i class="fas fa-users me-2"></i>Pendonor</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=transaksi"><i class="fas fa-hand-holding-heart me-2"></i>Transaksi</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=stok"><i class="fas fa-prescription-bottle me-2"></i>Stok Darah</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=distribusi"><i class="fas fa-truck me-2"></i>Distribusi</a></li>
                <li class="nav-item"><a class="nav-link" href="?action=kegiatan"><i class="fas fa-calendar-alt me-2"></i>Kegiatan</a></li>
                <li class="nav-item mt-3 d-lg-none"><a class="nav-link" href="?action=petugas_profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                <li class="nav-item d-lg-none"><?php if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn']): ?>
                    <a class="nav-link text-danger" href="?action=logout"><i class="fas fa-right-from-bracket me-2"></i>Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="?action=login"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
                <?php endif; ?></li>
            </ul>
        </div>
    </div>

    <!-- Main content wrapper: full width. Child views include content inside this block. -->
    <main class="container-fluid mt-4">
        <div class="row">
            <div class="col-12 main-content">
                <?php include __DIR__ . '/alerts.php'; ?>
                <?php include __DIR__ . '/toast.php'; ?>