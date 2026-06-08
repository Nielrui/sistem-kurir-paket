<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: ../dashboard/admin.php');
        exit;
    }
    header('Location: ../dashboard/kurir.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE kode_user = ?
       OR email = ?
       OR no_hp = ?
    LIMIT 1
");

    $stmt->execute([
        $identifier,
        $identifier,
        $identifier
    ]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['kode_user'] = $user['kode_user'];
        $_SESSION['nama']      = $user['nama'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['no_hp']     = $user['no_hp'];
        $_SESSION['role']      = $user['role'];

        if ($user['role'] === 'admin') {
            header('Location: ../dashboard/admin.php');
        } else {
            header('Location: ../dashboard/kurir.php');
        }

        exit;
    } else {

        $error = 'ID Kurir / Email / No HP atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kurir Paket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Login</h3>

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">ID Kurir / Email / No HP</label>
                                <input type="text" name="identifier" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Masuk</button>
                        </form>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Admin default: <strong>admin@kurir.com</strong> / <strong>password</strong>
                            </small>
                        </div>
                        <hr>

                        <div class="d-grid gap-2">

                            <a
                                href="forgot_id.php"
                                class="btn btn-outline-secondary">

                                Lupa ID Kurir

                            </a>

                            <a
                                href="forgot_password.php"
                                class="btn btn-outline-secondary">

                                Lupa Password

                            </a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>