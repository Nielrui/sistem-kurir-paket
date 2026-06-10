<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}

$pengiriman_id = (int)($_POST['pengiriman_id'] ?? 0);
$paket_id      = (int)($_POST['paket_id'] ?? 0);
$status_baru   = $_POST['status_baru'] ?? '';
$redirect      = $_POST['redirect'] ?? '../pengiriman/list.php';

$allowed = ['dalam_pengiriman', 'selesai', 'gagal'];

if (!$pengiriman_id || !$paket_id || !in_array($status_baru, $allowed)) {
    header('Location: ' . $redirect); exit;
}

// Ambil data kurir kalau role kurir
$kurir = null;
if ($_SESSION['role'] === 'kurir') {
    $stmtKurir = $pdo->prepare("SELECT id FROM kurir WHERE user_id = ?");
    $stmtKurir->execute([$_SESSION['user_id']]);
    $kurir = $stmtKurir->fetch();

    if (!$kurir) {
        header('Location: ' . $redirect); exit;
    }

    // Validasi pengiriman milik kurir ini
    $stmtCek = $pdo->prepare("
        SELECT id FROM pengiriman
        WHERE id = ? AND kurir_id = ?
    ");
    $stmtCek->execute([$pengiriman_id, $kurir['id']]);
    if (!$stmtCek->fetch()) {
        header('Location: ' . $redirect); exit;
    }
}

// Update status pengiriman
$stmtUpd = $pdo->prepare("UPDATE pengiriman SET status = ? WHERE id = ?");
$stmtUpd->execute([$status_baru, $pengiriman_id]);

// Update status paket
$statusPaket = match($status_baru) {
    'dalam_pengiriman' => 'diantar',
    'selesai'          => 'terkirim',
    'gagal'            => 'gagal',
    default            => 'diantar'
};
$stmtPaket = $pdo->prepare("UPDATE paket SET status = ? WHERE id = ?");
$stmtPaket->execute([$statusPaket, $paket_id]);

// Insert tracking log
$keterangan = match($status_baru) {
    'dalam_pengiriman' => 'Paket sedang dalam perjalanan ke penerima',
    'selesai'          => 'Paket berhasil diterima oleh penerima',
    'gagal'            => 'Pengiriman gagal, akan dijadwalkan ulang',
    default            => 'Status diperbarui'
};

$stmtLog = $pdo->prepare("
    INSERT INTO tracking_log (paket_id, status, keterangan)
    VALUES (?, ?, ?)
");
$stmtLog->execute([$paket_id, $statusPaket, $keterangan]);

header('Location: ' . $redirect);
exit;