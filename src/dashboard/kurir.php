<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'kurir') {
    header('Location: admin.php'); exit;
}

// Ambil data kurir berdasarkan user_id
$stmtKurir = $pdo->prepare("SELECT * FROM kurir WHERE user_id = ?");
$stmtKurir->execute([$_SESSION['user_id']]);
$kurir = $stmtKurir->fetch();

// Statistik pengiriman kurir ini
$totalTugas = 0;
$selesai    = 0;
$proses     = 0;

if ($kurir) {
    $totalTugas = $pdo->prepare("SELECT COUNT(*) FROM pengiriman WHERE kurir_id = ?");
    $totalTugas->execute([$kurir['id']]);
    $totalTugas = $totalTugas->fetchColumn();

    $selesai = $pdo->prepare("SELECT COUNT(*) FROM pengiriman WHERE kurir_id = ? AND status = 'selesai'");
    $selesai->execute([$kurir['id']]);
    $selesai = $selesai->fetchColumn();

    $proses = $pdo->prepare("SELECT COUNT(*) FROM pengiriman WHERE kurir_id = ? AND status = 'dalam_pengiriman'");
    $proses->execute([$kurir['id']]);
    $proses = $proses->fetchColumn();
}

// Ambil tugas hari ini
$hariIni = date('Y-m-d');
$stmtTugas = $pdo->prepare("
    SELECT p.no_resi, p.nama_penerima, p.alamat_tujuan,
           p.status, pg.status as status_pengiriman
    FROM pengiriman pg
    JOIN paket p ON pg.paket_id = p.id
    JOIN kurir k ON pg.kurir_id = k.id
    WHERE k.user_id = ? AND pg.tanggal = ?
    ORDER BY pg.id DESC
");
$stmtTugas->execute([$_SESSION['user_id'], $hariIni]);
$tugasHariIni = $stmtTugas->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 fw-semibold">Dashboard Kurir</h4>

<!-- Info Kurir -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-circle bg-success bg-opacity-10 p-3">
            <i class="bi bi-person-badge text-success fs-3"></i>
        </div>
        <div>
            <div class="fw-semibold fs-5"><?= htmlspecialchars($_SESSION['nama']) ?></div>
            <div class="text-muted small">
                ID: <?= htmlspecialchars($_SESSION['kode_user']) ?> &nbsp;·&nbsp;
                <?= $kurir ? htmlspecialchars($kurir['kendaraan'] . ' · ' . $kurir['plat_nomor']) : 'Data kendaraan belum diisi' ?>
            </div>
        </div>
        <span class="ms-auto badge bg-success">Aktif</span>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-list-check text-primary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Tugas</div>
                    <div class="fw-bold fs-4"><?= $totalTugas ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-truck text-warning fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Dalam Proses</div>
                    <div class="fw-bold fs-4"><?= $proses ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-check-circle text-success fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Selesai</div>
                    <div class="fw-bold fs-4"><?= $selesai ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tugas Hari Ini -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-calendar-check me-2"></i>Tugas Hari Ini —
        <?= date('d F Y') ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($tugasHariIni)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Tidak ada tugas hari ini
            </div>
        <?php else: ?>
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>No Resi</th>
                    <th>Penerima</th>
                    <th>Tujuan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tugasHariIni as $t):
                $badge = match($t['status']) {
                    'pending'  => 'warning',
                    'diambil'  => 'info',
                    'diantar'  => 'primary',
                    'terkirim' => 'success',
                    'gagal'    => 'danger',
                    default    => 'secondary'
                };
            ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($t['no_resi']) ?></td>
                <td><?= htmlspecialchars($t['nama_penerima']) ?></td>
                <td><?= htmlspecialchars($t['alamat_tujuan']) ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= $t['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>