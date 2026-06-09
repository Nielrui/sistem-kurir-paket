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

// Load data paket
$stmt = $pdo->prepare("SELECT * FROM paket WHERE id = ?");
$stmt->execute([$id]);
$paket = $stmt->fetch();

if (!$paket) {
    header('Location: index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama_pengirim = trim($_POST['nama_pengirim'] ?? '');
    $telp_pengirim = trim($_POST['telp_pengirim'] ?? '');
    $nama_penerima = trim($_POST['nama_penerima'] ?? '');
    $telp_penerima = trim($_POST['telp_penerima'] ?? '');
    $alamat_tujuan = trim($_POST['alamat_tujuan'] ?? '');
    $kota_tujuan   = trim($_POST['kota_tujuan'] ?? '');
    $berat         = (float)($_POST['berat'] ?? 0);
    $jenis_paket   = $_POST['jenis_paket'] ?? 'reguler';
    $status_baru   = $_POST['status'] ?? $paket['status'];

    if (!$nama_pengirim || !$nama_penerima || !$alamat_tujuan || !$berat) {
        $error = 'Semua field wajib diisi.';
    } else {

        $ongkir = max(10000, $berat * 10000);

        $stmtUpdate = $pdo->prepare("
            UPDATE paket SET
                nama_pengirim = ?, telp_pengirim = ?,
                nama_penerima = ?, telp_penerima = ?,
                alamat_tujuan = ?, kota_tujuan = ?,
                berat = ?, jenis_paket = ?,
                ongkir = ?, status = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([
            $nama_pengirim, $telp_pengirim,
            $nama_penerima, $telp_penerima,
            $alamat_tujuan, $kota_tujuan,
            $berat, $jenis_paket,
            $ongkir, $status_baru, $id
        ]);

        // Kalau status berubah, insert tracking log
        if ($status_baru !== $paket['status']) {
            $keterangan = match($status_baru) {
                'diambil'  => 'Paket diambil oleh kurir',
                'diantar'  => 'Paket sedang dalam pengiriman',
                'terkirim' => 'Paket berhasil diterima penerima',
                'gagal'    => 'Pengiriman gagal',
                default    => 'Status diperbarui'
            };

            $stmtLog = $pdo->prepare("
                INSERT INTO tracking_log (paket_id, status, keterangan)
                VALUES (?, ?, ?)
            ");
            $stmtLog->execute([$id, $status_baru, $keterangan]);
        }

        header('Location: index.php?success=Paket+berhasil+diupdate');
        exit;
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">Edit Paket</h4>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Info Resi -->
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>
    No Resi: <strong><?= htmlspecialchars($paket['no_resi']) ?></strong>
    &nbsp;·&nbsp; Dibuat: <?= date('d/m/Y H:i', strtotime($paket['created_at'])) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST">

            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-person me-1"></i>Data Pengirim
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Pengirim <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pengirim" class="form-control"
                           value="<?= htmlspecialchars($paket['nama_pengirim']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Telp Pengirim</label>
                    <input type="text" name="telp_pengirim" class="form-control"
                           value="<?= htmlspecialchars($paket['telp_pengirim']) ?>">
                </div>
            </div>

            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-person-check me-1"></i>Data Penerima
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                    <input type="text" name="nama_penerima" class="form-control"
                           value="<?= htmlspecialchars($paket['nama_penerima']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Telp Penerima</label>
                    <input type="text" name="telp_penerima" class="form-control"
                           value="<?= htmlspecialchars($paket['telp_penerima']) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Alamat Tujuan <span class="text-danger">*</span></label>
                    <textarea name="alamat_tujuan" class="form-control" rows="2" required
                    ><?= htmlspecialchars($paket['alamat_tujuan']) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kota Tujuan</label>
                    <input type="text" name="kota_tujuan" class="form-control"
                           value="<?= htmlspecialchars($paket['kota_tujuan']) ?>">
                </div>
            </div>

            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-box me-1"></i>Detail Paket
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Berat (kg) <span class="text-danger">*</span></label>
                    <input type="number" name="berat" id="berat" class="form-control"
                           step="0.1" min="0.1"
                           value="<?= $paket['berat'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Paket</label>
                    <select name="jenis_paket" class="form-select">
                        <?php foreach (['reguler','dokumen','berat','fragile'] as $j): ?>
                        <option value="<?= $j ?>"
                            <?= $paket['jenis_paket'] === $j ? 'selected' : '' ?>>
                            <?= ucfirst($j) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estimasi Ongkir</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="ongkir_preview" class="form-control bg-light" readonly
                               value="<?= number_format($paket['ongkir'], 0, ',', '.') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['pending','diambil','diantar','terkirim','gagal'] as $s): ?>
                        <option value="<?= $s ?>"
                            <?= $paket['status'] === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i>Update Paket
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>

        </form>
    </div>
</div>

<script>
document.getElementById('berat').addEventListener('input', function () {
    const berat  = parseFloat(this.value) || 0;
    const ongkir = Math.max(10000, berat * 10000);
    document.getElementById('ongkir_preview').value =
        ongkir.toLocaleString('id-ID');
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>