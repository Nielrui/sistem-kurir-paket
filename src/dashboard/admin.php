<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: kurir.php'); exit;
}

// KPI
$totalPaket    = $pdo->query("SELECT COUNT(*) FROM paket")->fetchColumn();
$totalKurir    = $pdo->query("SELECT COUNT(*) FROM kurir WHERE status='aktif'")->fetchColumn();
$paketPending  = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='pending'")->fetchColumn();
$paketTerkirim = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='terkirim'")->fetchColumn();
$paketDiantar  = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='diantar'")->fetchColumn();
$paketGagal    = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='gagal'")->fetchColumn();
$totalUser     = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Grafik pengiriman 7 hari terakhir
$stmtGrafik = $pdo->query("
    SELECT DATE(created_at) as tanggal, COUNT(*) as total
    FROM paket
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");
$grafikData = $stmtGrafik->fetchAll();

$grafikLabels = [];
$grafikValues = [];
foreach ($grafikData as $g) {
    $grafikLabels[] = date('d/m', strtotime($g['tanggal']));
    $grafikValues[] = $g['total'];
}

// Paket terbaru
$pakets = $pdo->query("
    SELECT * FROM paket
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll();

// Kurir aktif + jumlah tugas hari ini
$kurirs = $pdo->query("
    SELECT k.*, u.nama, u.kode_user,
           COUNT(pg.id) as tugas_hari_ini
    FROM kurir k
    JOIN users u ON k.user_id = u.id
    LEFT JOIN pengiriman pg ON pg.kurir_id = k.id
        AND pg.tanggal = CURDATE()
    WHERE k.status = 'aktif'
    GROUP BY k.id
    ORDER BY u.nama ASC
")->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">Dashboard Admin</h4>
    <span class="text-muted small">
        <i class="bi bi-calendar me-1"></i><?= date('d F Y, H:i') ?>
    </span>
</div>

<!-- KPI Cards Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-box-seam text-primary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Paket</div>
                    <div class="fw-bold fs-3"><?= $totalPaket ?></div>
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
                    <div class="fw-bold fs-3"><?= $paketPending ?></div>
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
                    <div class="fw-bold fs-3"><?= $paketTerkirim ?></div>
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
                    <div class="fw-bold fs-3"><?= $totalKurir ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Grafik + Donut -->
<div class="row g-3 mb-4">

    <!-- Grafik Line -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-graph-up me-2"></i>Paket Masuk 7 Hari Terakhir
            </div>
            <div class="card-body">
                <canvas id="grafikPaket" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Donut Status -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-pie-chart me-2"></i>Status Paket
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="donutStatus" height="180"></canvas>
                <div class="mt-3 w-100">
                    <?php
                    $statusList = [
                        ['label' => 'Pending',  'val' => $paketPending,  'color' => '#ffc107'],
                        ['label' => 'Diantar',  'val' => $paketDiantar,  'color' => '#0d6efd'],
                        ['label' => 'Terkirim', 'val' => $paketTerkirim, 'color' => '#198754'],
                        ['label' => 'Gagal',    'val' => $paketGagal,    'color' => '#dc3545'],
                    ];
                    foreach ($statusList as $s): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $s['color'] ?>;margin-right:6px;"></span>
                            <?= $s['label'] ?>
                        </span>
                        <span class="fw-semibold small"><?= $s['val'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Row 3: Paket Terbaru + Kurir -->
<div class="row g-3">

    <!-- Paket Terbaru -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-clock-history me-2"></i>Paket Terbaru
                </span>
                <a href="../paket/index.php" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body p-0">
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
                    <?php foreach ($pakets as $p):
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
                        <td class="fw-semibold"><?= htmlspecialchars($p['no_resi']) ?></td>
                        <td><?= htmlspecialchars($p['nama_penerima']) ?></td>
                        <td><?= htmlspecialchars($p['kota_tujuan'] ?: '-') ?></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= $p['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kurir Aktif -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-people me-2"></i>Kurir Aktif
            </div>
            <div class="card-body p-0">
                <?php foreach ($kurirs as $k): ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom">
                    <div class="rounded-circle bg-success bg-opacity-10 p-2">
                        <i class="bi bi-person text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small"><?= htmlspecialchars($k['nama']) ?></div>
                        <div class="text-muted" style="font-size:11px">
                            <?= htmlspecialchars($k['kode_user']) ?> ·
                            <?= htmlspecialchars($k['kendaraan']) ?>
                        </div>
                    </div>
                    <span class="badge bg-primary">
                        <?= $k['tugas_hari_ini'] ?> tugas
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($kurirs)): ?>
                    <div class="text-center text-muted py-3 small">Tidak ada kurir aktif</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Grafik line
new Chart(document.getElementById('grafikPaket'), {
    type: 'line',
    data: {
        labels: <?= json_encode($grafikLabels) ?>,
        datasets: [{
            label: 'Paket Masuk',
            data: <?= json_encode($grafikValues) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#0d6efd',
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Donut status
new Chart(document.getElementById('donutStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Diantar', 'Terkirim', 'Gagal'],
        datasets: [{
            data: [
                <?= $paketPending ?>,
                <?= $paketDiantar ?>,
                <?= $paketTerkirim ?>,
                <?= $paketGagal ?>
            ],
            backgroundColor: ['#ffc107','#0d6efd','#198754','#dc3545'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>

<!-- php
// require_once __DIR__ . '/../config/database.php';
// session_start();

// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../auth/login.php'); exit;
// }
// if ($_SESSION['role'] !== 'admin') {
//     header('Location: kurir.php'); exit;
// }

// Ambil statistik
// $totalPaket     = $pdo->query("SELECT COUNT(*) FROM paket")->fetchColumn();
// $totalKurir     = $pdo->query("SELECT COUNT(*) FROM kurir WHERE status='aktif'")->fetchColumn();
// $paketPending   = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='pending'")->fetchColumn();
// $paketTerkirim  = $pdo->query("SELECT COUNT(*) FROM paket WHERE status='terkirim'")->fetchColumn();

// require_once __DIR__ . '/../templates/header.php';
// ?>

<h4 class="mb-4 fw-semibold">Dashboard Admin</h4> -->

<!-- KPI Cards -->
<!-- <div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-box-seam text-primary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Paket</div>
                    <div class="fw-bold fs-4">?= $totalPaket ?></div>
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
                    <div class="fw-bold fs-4">?= $paketPending ?></div>
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
                    <div class="fw-bold fs-4">?= $paketTerkirim ?></div>
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
                    <div class="fw-bold fs-4"?= $totalKurir ?></div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- Tabel Paket Terbaru -->
<!-- <div class="card border-0 shadow-sm">
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
                <td><span class="fw-semibold">?= htmlspecialchars($p['no_resi']) ?></span></td>
                <td>= htmlspecialchars($p['nama_pengirim']) ?></td>
                <td>?= htmlspecialchars($p['nama_penerima']) ?></td>
                <td>?= htmlspecialchars($p['kota_tujuan']) ?></td>
                <td><span class="badge bg-?= $badge ?>">?= $p['status'] ?></span></td>
            </tr>
            </tbody>
        </table>
    </div>
</div> -->

<!-- UPDATE -->


