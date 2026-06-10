<?php
require_once __DIR__ . '/../config/database.php';

$resi   = trim($_GET['resi'] ?? $_POST['resi'] ?? '');
$paket  = null;
$logs   = [];
$error  = '';

if ($resi !== '') {
    $stmt = $pdo->prepare("SELECT * FROM paket WHERE no_resi = ?");
    $stmt->execute([$resi]);
    $paket = $stmt->fetch();

    if (!$paket) {
        $error = 'Nomor resi tidak ditemukan. Periksa kembali nomor resi kamu.';
    } else {
        $stmtLog = $pdo->prepare("
            SELECT * FROM tracking_log
            WHERE paket_id = ?
            ORDER BY waktu ASC
        ");
        $stmtLog->execute([$paket['id']]);
        $logs = $stmtLog->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Resi — KurirApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .hero { background: linear-gradient(135deg, #1a1a2e 0%, #0d6efd 100%); }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px; top: 0; bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px; top: 5px;
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #dee2e6;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #dee2e6;
        }
        .timeline-item.active::before {
            background: #0d6efd;
            box-shadow: 0 0 0 2px #0d6efd;
        }
        .timeline-item.success::before {
            background: #198754;
            box-shadow: 0 0 0 2px #198754;
        }
        .timeline-item.danger::before {
            background: #dc3545;
            box-shadow: 0 0 0 2px #dc3545;
        }
    </style>
</head>
<body>

<!-- Hero -->
<div class="hero text-white py-5">
    <div class="container text-center">
        <h1 class="fw-bold mb-2">
            <i class="bi bi-box-seam me-2"></i>KurirApp
        </h1>
        <p class="mb-4 opacity-75">Lacak paket kamu dengan mudah dan cepat</p>

        <form method="GET" class="row justify-content-center g-2">
            <div class="col-md-6">
                <input type="text" name="resi"
                       class="form-control form-control-lg"
                       placeholder="Masukkan nomor resi (contoh: PKT20260609001)"
                       value="<?= htmlspecialchars($resi) ?>"
                       required>
            </div>
            <div class="col-auto">
                <button class="btn btn-warning btn-lg fw-semibold px-4">
                    <i class="bi bi-search me-1"></i>Lacak
                </button>
            </div>
        </form>
    </div>
</div>

<div class="container py-5">

    <?php if ($error): ?>
        <div class="alert alert-danger text-center">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($paket): ?>

        <!-- Status Badge -->
        <?php
        $statusConfig = [
            'pending'  => ['color' => 'warning', 'icon' => 'clock',          'label' => 'Menunggu Diproses'],
            'diambil'  => ['color' => 'info',    'icon' => 'bag-check',       'label' => 'Diambil Kurir'],
            'diantar'  => ['color' => 'primary', 'icon' => 'truck',           'label' => 'Dalam Pengiriman'],
            'terkirim' => ['color' => 'success', 'icon' => 'check-circle',    'label' => 'Terkirim'],
            'gagal'    => ['color' => 'danger',  'icon' => 'x-circle',        'label' => 'Gagal Dikirim'],
        ];
        $sc = $statusConfig[$paket['status']] ?? ['color' => 'secondary', 'icon' => 'question', 'label' => $paket['status']];
        ?>

        <div class="row g-4">

            <!-- Info Paket -->
            <div class="col-md-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <span class="badge bg-<?= $sc['color'] ?> fs-6 px-4 py-2">
                                <i class="bi bi-<?= $sc['icon'] ?> me-2"></i>
                                <?= $sc['label'] ?>
                            </span>
                        </div>
                        <h5 class="fw-bold"><?= htmlspecialchars($paket['no_resi']) ?></h5>
                        <small class="text-muted">
                            Dikirim: <?= date('d F Y', strtotime($paket['created_at'])) ?>
                        </small>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-info-circle me-2"></i>Detail Paket
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:40%">Pengirim</td>
                                <td class="fw-semibold"><?= htmlspecialchars($paket['nama_pengirim']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Penerima</td>
                                <td class="fw-semibold"><?= htmlspecialchars($paket['nama_penerima']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Tujuan</td>
                                <td><?= htmlspecialchars($paket['alamat_tujuan']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Berat</td>
                                <td><?= $paket['berat'] ?> kg</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Ongkir</td>
                                <td>Rp <?= number_format($paket['ongkir'], 0, ',', '.') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="col-md-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Perjalanan Paket
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <p class="text-muted text-center py-3">Belum ada riwayat perjalanan</p>
                        <?php else: ?>
                        <div class="timeline">
                            <?php
                            $lastIndex = count($logs) - 1;
                            foreach ($logs as $idx => $log):
                                $isLast = ($idx === $lastIndex);
                                $cls = 'active';
                                if ($log['status'] === 'terkirim') $cls = 'success';
                                if ($log['status'] === 'gagal') $cls = 'danger';
                            ?>
                            <div class="timeline-item <?= $isLast ? $cls : '' ?>">
                                <div class="fw-semibold text-capitalize">
                                    <?= htmlspecialchars($log['status']) ?>
                                    <?php if ($isLast): ?>
                                        <span class="badge bg-<?= $sc['color'] ?> ms-1">Terkini</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($log['keterangan']) ?>
                                </div>
                                <?php if ($log['lokasi']): ?>
                                <div class="text-muted small">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= htmlspecialchars($log['lokasi']) ?>
                                </div>
                                <?php endif; ?>
                                <div class="text-muted small mt-1">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('d F Y, H:i', strtotime($log['waktu'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    <?php elseif ($resi === ''): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-search fs-1 d-block mb-3"></i>
            Masukkan nomor resi di atas untuk melacak paket kamu
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="../auth/login.php" class="text-muted small">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login sebagai Admin / Kurir
        </a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>