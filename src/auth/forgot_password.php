<?php

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';
$user = null;

if (isset($_POST['search'])) {

    $identifier = trim($_POST['identifier']);

    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE kode_user = :id
           OR email = :id
           OR no_hp = :id
        LIMIT 1
    ");

    $stmt->execute([
        'id' => $identifier
    ]);

    $user = $stmt->fetch();

    if (!$user) {

        $error = 'Akun tidak ditemukan.';

    } elseif ($user['role'] !== 'kurir') {

        $error = 'Maaf, Anda bukan kurir yang terdaftar.';
    }
}

if (isset($_POST['reset'])) {

    $user_id = (int)$_POST['user_id'];

    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {

        $error = 'Konfirmasi password tidak cocok.';

    } else {

        $hash = password_hash(
            $new_password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            UPDATE users
            SET password = :password
            WHERE id = :id
        ");

        $stmt->execute([
            'password' => $hash,
            'id' => $user_id
        ]);

        $success = 'Password berhasil diperbarui.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lupa Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">

<div class="card mx-auto shadow" style="max-width:500px;">
<div class="card-body">

<h3 class="text-center mb-4">
Reset Password Kurir
</h3>

<?php if ($error): ?>
<div class="alert alert-danger">
<?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success">
<?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<?php if (!$user && !$success): ?>

<form method="POST">

<div class="mb-3">
<label>ID Kurir / Email / No HP</label>
<input
type="text"
name="identifier"
class="form-control"
required>
</div>

<button
name="search"
class="btn btn-primary w-100">

Cari Akun

</button>

</form>

<?php endif; ?>

<?php if ($user): ?>

<div class="alert alert-info">

Nama:
<strong>
<?= htmlspecialchars($user['nama']) ?>
</strong>

<br>

ID Kurir:
<strong>
<?= htmlspecialchars($user['kode_user']) ?>
</strong>

</div>

<form method="POST">

<input
type="hidden"
name="user_id"
value="<?= $user['id'] ?>">

<div class="mb-3">

<label>Password Baru</label>

<input
type="password"
name="new_password"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Konfirmasi Password</label>

<input
type="password"
name="confirm_password"
class="form-control"
required>

</div>

<button
name="reset"
class="btn btn-success w-100">

Reset Password

</button>

</form>

<?php endif; ?>

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