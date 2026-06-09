<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/kurir.php'); exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php'); exit;
}

// Cek paket ada
$stmt = $pdo->prepare("SELECT * FROM paket WHERE id = ?");
$stmt->execute([$id]);
$paket = $stmt->fetch();

if (!$paket) {
    header('Location: index.php?error=Paket+tidak+ditemukan'); exit;
}

// Hanya paket pending yang boleh dihapus
if ($paket['status'] !== 'pending') {
    header('Location: index.php?error=Paket+yang+sudah+diproses+tidak+bisa+dihapus'); exit;
}

// Hapus tracking_log dulu (foreign key)
$stmtLog = $pdo->prepare("DELETE FROM tracking_log WHERE paket_id = ?");
$stmtLog->execute([$id]);

// Hapus paket
$stmtDel = $pdo->prepare("DELETE FROM paket WHERE id = ?");
$stmtDel->execute([$id]);

header('Location: index.php?success=Paket+berhasil+dihapus');
exit;