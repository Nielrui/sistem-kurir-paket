<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'kurir') {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kurir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow">
        <div class="card-body">
            <h1 class="h3 mb-3">Dashboard Kurir</h1>
            <p>Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'Kurir') ?>. Kamu login sebagai <strong>kurir</strong>.</p>

            <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</div>
</body>
</html>