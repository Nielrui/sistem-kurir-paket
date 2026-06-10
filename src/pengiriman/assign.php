<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/kurir.php'); exit;
}

$error   = '';
$success = '';

// Proses assign
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paket_id  = (int)($_POST['paket_id'] ?? 0);
    $kurir_id  = (int)($_POST['kurir_id'] ?? 0);
    $tanggal   = $_POST['tanggal'] ?? date('Y-m-d');

    if (!$paket_id || !$kurir_id) {
        $error = 'Pilih paket dan kurir terlebih dahulu.';
    } else {
        // Cek apakah paket sudah diassign
        $cek = $pdo->prepare("SELECT id FROM pengiriman WHERE paket_id = ? AND status != 'gagal'");
        $cek->execute([$paket_id]);
        if ($cek->fetch()) {
            $error = 'Paket ini sudah diassign ke kurir.';
        } else {
            // Insert pengiriman
            $stmt = $pdo->prepare("
                INSERT INTO pengiriman (paket_id, kurir_id, tanggal, status)
                VALUES (?, ?, ?, 'assigned')
            ");
            $stmt->execute([$paket_id, $kurir_id, $tanggal]);

            // Update status paket
            $stmtPaket = $pdo->prepare("UPDATE paket SET status = 'diambil' WHERE id = ?");
            $stmtPaket->execute([$paket_id]);

            // Insert tracking log
            $stmtLog = $pdo->prepare("
                INSERT INTO tracking_log (paket_id, status, keterangan, lokasi)
                VALUES (?, 'diambil', 'Paket diassign dan diambil kurir', 'Gudang Utama')
            ");
            $stmtLog->execute([$paket_id]);

            $success = 'Paket berhasil diassign ke kurir.';
        }
    }
}

// Ambil paket pending
$pakets = $pdo->query("
    SELECT * FROM paket
    WHERE status = 'pending'
    ORDER BY created_at ASC
")->fetchAll();

// Ambil kurir aktif
$kurirs = $pdo->query("
    SELECT k.*, u.nama, u.kode_user
    FROM kurir k
    JOIN users u ON k.user_id = u.id
    WHERE k.status = 'aktif'
    ORDER BY u.nama ASC
")->fetchAll();

// Ambil riwayat pengiriman
$pengiriman = $pdo->query("
    SELECT pg.*, p.no_resi, p.nama_penerima, p.alamat_tujuan,
           p.status as status_paket, u.nama as nama_kurir,
           u.kode_user, pg.tanggal
    FROM pengiriman pg
    JOIN paket p ON pg.paket_id = p.id
    JOIN kurir k ON pg.kurir_id = k.id
    JOIN users u ON k.user_id = u.id
    ORDER BY pg.id DESC
    LIMIT 20
")->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<h4 class="fw-semibold mb-4">Assign Pengiriman</h4>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- Form Assign -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-plus-circle me-2"></i>Assign Paket ke Kurir
            </div>
            <div class="card-body">
                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label">Pilih Paket <span class="text-danger">*</span></label>
                        <?php if (empty($pakets)): ?>
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                Tidak ada paket pending
                            </div>
                        <?php else: ?>
                        <select name="paket_id" class="form-select" required>
                            <option value="">-- Pilih Paket --</option>
                            <?php foreach ($pakets as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['no_resi']) ?> —
                                <?= htmlspecialchars($p['nama_penerima']) ?>
                                (<?= htmlspecialchars($p['kota_tujuan'] ?: $p['alamat_tujuan']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pilih Kurir <span class="text-danger">*</span></label>
                        <?php if (empty($kurirs)): ?>
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Tidak ada kurir aktif
                            </div>
                        <?php else: ?>
                        <select name="kurir_id" class="form-select" required>
                            <option value="">-- Pilih Kurir --</option>
                            <?php foreach ($kurirs as $k): ?>
                            <option value="<?= $k['id'] ?>">
                                <?= htmlspecialchars($k['kode_user']) ?> —
                                <?= htmlspecialchars($k['nama']) ?>
                                (<?= htmlspecialchars($k['kendaraan']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Pengiriman</label>
                        <input type="date" name="tanggal" class="form-control"
                               value="<?= date('Y-m-d') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100"
                        <?= empty($pakets) || empty($kurirs) ? 'disabled' : '' ?>>
                        <i class="bi bi-send me-1"></i>Assign Sekarang
                    </button>

                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Pengiriman -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-clock-history me-2"></i>Riwayat Pengiriman
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No Resi</th>
                            <th>Penerima</th>
                            <th>Kurir</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pengiriman)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Belum ada data pengiriman
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pengiriman as $pg):
                            $badge = match($pg['status']) {
                                'assigned'         => 'info',
                                'dalam_pengiriman' => 'primary',
                                'selesai'          => 'success',
                                'gagal'            => 'danger',
                                default            => 'secondary'
                            };
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($pg['no_resi']) ?></td>
                            <td><?= htmlspecialchars($pg['nama_penerima']) ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($pg['kode_user']) ?>
                                </span>
                                <?= htmlspecialchars($pg['nama_kurir']) ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($pg['tanggal'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= $pg['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>