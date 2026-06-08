<?php

require_once __DIR__ . '/../config/database.php';

$error = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');

    if ($email === '' && $no_hp === '') {
        $error = 'Masukkan Email atau Nomor HP.';
    } else {

        $stmt = $pdo->prepare("
            SELECT nama, kode_user, role
            FROM users
            WHERE email = :email
               OR no_hp = :no_hp
            LIMIT 1
        ");

        $stmt->execute([
            'email' => $email,
            'no_hp' => $no_hp
        ]);

        $user = $stmt->fetch();

        if (!$user) {

            $error = 'Akun tidak ditemukan.';

        } elseif ($user['role'] !== 'kurir') {

            $error = 'Maaf, Anda bukan kurir yang terdaftar.';

        } else {

            $result = $user;

        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lupa ID Kurir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">

    <div class="card mx-auto shadow" style="max-width:500px;">
        <div class="card-body">

            <h3 class="text-center mb-4">
                Lupa ID Kurir
            </h3>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($result): ?>
                <div class="alert alert-success">
                    <strong><?= htmlspecialchars($result['nama']) ?></strong><br>
                    ID Kurir Anda:
                    <strong><?= htmlspecialchars($result['kode_user']) ?></strong>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control">
                </div>

                <div class="mb-3">
                    <label>Nomor HP</label>
                    <input
                        type="text"
                        name="no_hp"
                        class="form-control">
                </div>

                <button class="btn btn-primary w-100">
                    Cari ID Kurir
                </button>

            </form>

            <div class="text-center mt-3">
                <a href="login.php">
                    Kembali ke Login
                </a>
            </div>

        </div>
    </div>

</div>

</body>
</html>