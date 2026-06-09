<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/kurir.php'); exit;
}

// Filter status
$statusFilter = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

$where  = [];
$params = [];

if ($statusFilter !== '') {
    $where[]  = "status = ?";
    $params[] = $statusFilter;
}

if ($search !== '') {
    $where[]  = "(no_resi LIKE ? OR nama_pengirim LIKE ? OR nama_penerima LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "SELECT * FROM paket";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pakets = $stmt->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">Manajemen Paket</h4>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Tambah Paket
    </a>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    class="form-control" placeholder="Cari resi, pengirim, penerima...">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <?php foreach (['pending','diambil','diantar','terkirim','gagal'] as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>No Resi</th>
                    <th>Pengirim</th>
                    <th>Penerima</th>
                    <th>Berat</th>
                    <th>Ongkir</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pakets)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        Belum ada data paket
                    </td>
                </tr>
            <?php else: ?>
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
                    <td><span class="fw-semibold"><?= htmlspecialchars($p['no_resi']) ?></span></td>
                    <td><?= htmlspecialchars($p['nama_pengirim']) ?></td>
                    <td><?= htmlspecialchars($p['nama_penerima']) ?></td>
                    <td><?= $p['berat'] ?> kg</td>
                    <td>Rp <?= number_format($p['ongkir'], 0, ',', '.') ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= $p['status'] ?></span></td>
                    <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="edit.php?id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="delete.php?id=<?= $p['id'] ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Hapus paket <?= $p['no_resi'] ?>?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- require_once __DIR__ . '/../templates/footer.php';  -->

<?php require_once __DIR__ . '/../templates/header.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>
        <?= htmlspecialchars($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>