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
        <div class="card-header bg-success text-white">
            Dashboard Kurir
        </div>
        <div class="card-body">
            <h3>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?></h3>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>ID Kurir :</strong><br>
                        <?= htmlspecialchars($_SESSION['kode_user'] ?? '-') ?></p>
                    <p><strong>Email :</strong><br>
                        <?= htmlspecialchars($_SESSION['email'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>No HP :</strong><br>
                        <?= htmlspecialchars($_SESSION['no_hp'] ?? '-') ?></p>
                    <p><strong>Role :</strong><br>
                        <?= htmlspecialchars($_SESSION['role'] ?? '-') ?></p>
                </div>
            </div>
            <hr>
            <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</div>

</body>
</html>