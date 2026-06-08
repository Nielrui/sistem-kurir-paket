<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($nama === '' || $email === '' || $no_hp === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } else {
        $check = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = :email
               OR no_hp = :no_hp
            LIMIT 1
        ");

        $check->execute([
            'email' => $email,
            'no_hp' => $no_hp
        ]);

        if ($check->fetch()) {
            $error = 'Email atau Nomor HP sudah terdaftar.';
        } else {
            $stmtKode = $pdo->query("
                SELECT COUNT(*) AS total
                FROM users
                WHERE role = 'kurir'
            ");

            $total = (int)($stmtKode->fetch()['total'] ?? 0);

            $kode_user = 'KUR' . str_pad($total + 1, 3, '0', STR_PAD_LEFT);

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    kode_user,
                    nama,
                    email,
                    no_hp,
                    password,
                    role
                )
                VALUES (
                    :kode_user,
                    :nama,
                    :email,
                    :no_hp,
                    :password,
                    'kurir'
                )
            ");

            $stmt->execute([
                'kode_user' => $kode_user,
                'nama' => $nama,
                'email' => $email,
                'no_hp' => $no_hp,
                'password' => $hashedPassword
            ]);

            $success = "Registrasi berhasil. ID Kurir Anda: $kode_user";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sistem Kurir Paket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Register Kurir</h3>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <input type="text" name="nama" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nomor HP</label>
                                <input type="text" name="no_hp" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="login.php">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>