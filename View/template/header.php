<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Donor Darah PMI</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #dc3545;
        }
        .sidebar .nav-link {
            color: white;
        }
        .sidebar .nav-link:hover {
            background-color: #c82333;
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar-brand {
            font-weight: bold;
            color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="?action=dashboard">
                <i class="fas fa-tint me-2"></i>Sistem PMI
            </a>
            <div class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Logout</a></li>
                    </ul>
                </li>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <nav class="nav flex-column p-3">
                    <a class="nav-link active" href="?action=dashboard">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                    <a class="nav-link" href="?action=pendonor">
                        <i class="fas fa-users me-2"></i>Data Pendonor
                    </a>
                    <a class="nav-link" href="?action=transaksi">
                        <i class="fas fa-hand-holding-heart me-2"></i>Transaksi Donasi
                    </a>
                    <a class="nav-link" href="?action=stok">
                        <i class="fas fa-prescription-bottle me-2"></i>Stok Darah
                    </a>
                    <a class="nav-link" href="?action=distribusi">
                        <i class="fas fa-truck me-2"></i>Distribusi
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="container-fluid py-4">