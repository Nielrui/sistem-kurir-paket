<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kurir Paket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #1a1a2e; }
        .sidebar .nav-link { color: #a0a0b0; border-radius: 8px; margin: 2px 8px; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar .nav-link.active { background: #0d6efd; color: #fff; }
        .sidebar .nav-link i { width: 20px; }
        .brand-title { color: #fff; font-weight: 600; font-size: 1.1rem; }
        .brand-sub { color: #a0a0b0; font-size: 0.75rem; }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .topbar { background: #fff; border-bottom: 1px solid #e9ecef; padding: 12px 24px; }
        .user-badge { background: #e8f0fe; color: #1a73e8; padding: 4px 12px; border-radius: 20px; font-size: 13px; }
    </style>
</head>
<body>
<div class="d-flex">

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column p-3" style="width:240px; flex-shrink:0;">

        <div class="mb-4 px-2 pt-2">
            <div class="brand-title"><i class="bi bi-box-seam me-2"></i>KurirApp</div>
            <div class="brand-sub">Sistem Manajemen Kurir</div>
        </div>

        <nav class="nav flex-column gap-1">

            <?php if ($role === 'admin'): ?>
                <a href="/dashboard/admin.php"
                   class="nav-link <?= $current_page === 'admin.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="/paket/index.php"
                   class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-box me-2"></i>Manajemen Paket
                </a>
                <a href="/pengiriman/assign.php"
                   class="nav-link <?= $current_page === 'assign.php' ? 'active' : '' ?>">
                    <i class="bi bi-truck me-2"></i>Pengiriman
                </a>
                <a href="/tracking/cek_resi.php"
                   class="nav-link <?= $current_page === 'cek_resi.php' ? 'active' : '' ?>">
                    <i class="bi bi-geo-alt me-2"></i>Tracking
                </a>
                <a href="/laporan/harian.php"
                   class="nav-link <?= $current_page === 'harian.php' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart me-2"></i>Laporan
                </a>

            <?php elseif ($role === 'kurir'): ?>
                <a href="/dashboard/kurir.php"
                   class="nav-link <?= $current_page === 'kurir.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="/pengiriman/list.php"
                   class="nav-link <?= $current_page === 'list.php' ? 'active' : '' ?>">
                    <i class="bi bi-list-check me-2"></i>Tugas Hari Ini
                </a>
                <a href="/tracking/cek_resi.php"
                   class="nav-link <?= $current_page === 'cek_resi.php' ? 'active' : '' ?>">
                    <i class="bi bi-geo-alt me-2"></i>Tracking
                </a>

            <?php endif; ?>

        </nav>

        <div class="mt-auto px-2 pb-2">
            <a href="/auth/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-left me-2"></i>Logout
            </a>
        </div>

    </div>
    <!-- End Sidebar -->

    <!-- Main Content -->
    <div class="main-content flex-grow-1">

        <!-- Topbar -->
        <div class="topbar d-flex align-items-center justify-content-between">
            <div class="fw-500 text-secondary" id="page-title">Sistem Kurir Paket</div>
            <div class="d-flex align-items-center gap-3">
                <span class="user-badge">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
                    &nbsp;·&nbsp;
                    <?= htmlspecialchars($_SESSION['kode_user'] ?? '') ?>
                </span>
            </div>
        </div>
        <!-- End Topbar -->

        <div class="p-4">