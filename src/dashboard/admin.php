<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: kurir.php'); exit;
}

// Ambil statistik
$totalPaket     = $pdo->query("SELECT COUNT(*) FROM paket")->fetchColumn();
$totalKurir     = $pdo->query("SELECT COUNT(*) FROM kurir WHERE status='aktif'")->fetchColumn();
$paketPending   = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='pending'")->fetchColumn();
$paketTerkirim  = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='terkirim'")->fetchColumn();

require_once __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 fw-semibold">Dashboard Admin</h4>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-box-seam text-primary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Paket</div>
                    <div class="fw-bold fs-4"><?= $totalPaket ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-clock text-warning fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending</div>
                    <div class="fw-bold fs-4"><?= $paketPending ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle text-success fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Terkirim</div>
                    <div class="fw-bold fs-4"><?= $paketTerkirim ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="bi bi-people text-info fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Kurir Aktif</div>
                    <div class="fw-bold fs-4"><?= $totalKurir ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Paket Terbaru -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-2"></i>Paket Terbaru
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>No Resi</th>
                    <th>Pengirim</th>
                    <th>Penerima</th>
                    <th>Tujuan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $pakets = $pdo->query("SELECT * FROM paket ORDER BY created_at DESC LIMIT 5")->fetchAll();
            foreach ($pakets as $p):
                $badge = match($p['status']) {
                    'pending'  => 'warning',
                    'diambil'  => 'info',
                    'diantar'  => 'primary',
                    'terkirim' => 'success',
                    'gagal'    => 'danger',
                    default    => 'secondary'
                };
            ?>
            <tr>
                <td><span class="fw-semibold"><?= htmlspecialchars($p['no_resi']) ?></span></td>
                <td><?= htmlspecialchars($p['nama_pengirim']) ?></td>
                <td><?= htmlspecialchars($p['nama_penerima']) ?></td>
                <td><?= htmlspecialchars($p['kota_tujuan']) ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= $p['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>