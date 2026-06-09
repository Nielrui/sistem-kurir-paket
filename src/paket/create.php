<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php'); exit;
}
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/kurir.php'); exit;
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
    $lat           = $_POST['lat'] ?? null;
    $lng           = $_POST['lng'] ?? null;

    if (!$nama_pengirim || !$nama_penerima || !$alamat_tujuan || !$berat) {
        $error = 'Semua field wajib diisi.';
    } else {
        // Generate nomor resi
        $today    = date('Ymd');
        $stmtUrut = $pdo->prepare("SELECT COUNT(*) FROM paket WHERE DATE(created_at) = CURDATE()");
        $stmtUrut->execute();
        $urutan  = (int)$stmtUrut->fetchColumn() + 1;
        $no_resi = 'PKT' . $today . str_pad($urutan, 3, '0', STR_PAD_LEFT);

        // Hitung ongkir
        $ongkir = max(10000, $berat * 10000);

        $stmt = $pdo->prepare("
            INSERT INTO paket (
                no_resi, nama_pengirim, telp_pengirim,
                nama_penerima, telp_penerima,
                alamat_tujuan, kota_tujuan,
                berat, jenis_paket, ongkir,
                lat, lng, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $no_resi, $nama_pengirim, $telp_pengirim,
            $nama_penerima, $telp_penerima,
            $alamat_tujuan, $kota_tujuan,
            $berat, $jenis_paket, $ongkir,
            $lat ?: null, $lng ?: null
        ]);

        $paketId = $pdo->lastInsertId();

        // Insert tracking log awal
        $stmtLog = $pdo->prepare("
            INSERT INTO tracking_log (paket_id, status, keterangan, lokasi)
            VALUES (?, 'pending', 'Paket diterima di gudang', 'Gudang Utama')
        ");
        $stmtLog->execute([$paketId]);

        header('Location: index.php?success=Paket+berhasil+ditambahkan');
        exit;
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-semibold mb-0">Tambah Paket Baru</h4>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" id="formPaket">

            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-person me-1"></i>Data Pengirim
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Pengirim <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pengirim" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Telp Pengirim</label>
                    <input type="text" name="telp_pengirim" class="form-control">
                </div>
            </div>

            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-person-check me-1"></i>Data Penerima
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                    <input type="text" name="nama_penerima" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No Telp Penerima</label>
                    <input type="text" name="telp_penerima" class="form-control">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Alamat Tujuan <span class="text-danger">*</span></label>
                    <textarea name="alamat_tujuan" class="form-control" rows="2" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kota Tujuan</label>
                    <input type="text" name="kota_tujuan" class="form-control">
                </div>
            </div>

            <!-- Peta Lokasi -->
            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-geo-alt me-1"></i>Lokasi Tujuan
            </h6>
            <div class="mb-3">
                <div class="alert alert-info py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Klik pada peta untuk menandai lokasi tujuan pengiriman
                </div>
                <div id="map" style="height:350px; border-radius:10px; border:1px solid #dee2e6;"></div>
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
                <div class="mt-2 text-muted small" id="koordinat-info">
                    Belum ada lokasi dipilih
                </div>
            </div>

            <h6 class="fw-semibold mb-3 text-muted">
                <i class="bi bi-box me-1"></i>Detail Paket
            </h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Berat (kg) <span class="text-danger">*</span></label>
                    <input type="number" name="berat" id="berat"
                           class="form-control" step="0.1" min="0.1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Paket</label>
                    <select name="jenis_paket" class="form-select">
                        <option value="reguler">Reguler</option>
                        <option value="dokumen">Dokumen</option>
                        <option value="berat">Paket Berat</option>
                        <option value="fragile">Fragile</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estimasi Ongkir</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" id="ongkir_preview" class="form-control bg-light"
                               readonly placeholder="Isi berat dulu">
                    </div>
                    <small class="text-muted">Rp 10.000/kg, min Rp 10.000</small>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-1"></i>Simpan Paket
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
            </div>

        </form>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Init peta — default center Surabaya
const map = L.map('map').setView([-7.9797, 112.6304], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

let marker = null;

map.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(7);
    const lng = e.latlng.lng.toFixed(7);

    // Update hidden input
    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;
    document.getElementById('koordinat-info').innerHTML =
        `<i class="bi bi-geo-alt-fill text-danger"></i> Lokasi dipilih: <strong>${lat}, ${lng}</strong>`;

    // Update marker
    if (marker) {
        marker.setLatLng(e.latlng);
    } else {
        marker = L.marker(e.latlng, {draggable: true}).addTo(map);
        marker.on('dragend', function(ev) {
            const pos = ev.target.getLatLng();
            document.getElementById('lat').value = pos.lat.toFixed(7);
            document.getElementById('lng').value = pos.lng.toFixed(7);
            document.getElementById('koordinat-info').innerHTML =
                `<i class="bi bi-geo-alt-fill text-danger"></i> Lokasi dipilih: <strong>${pos.lat.toFixed(7)}, ${pos.lng.toFixed(7)}</strong>`;
        });
    }
});

// Hitung ongkir
document.getElementById('berat').addEventListener('input', function() {
    const berat  = parseFloat(this.value) || 0;
    const ongkir = Math.max(10000, berat * 10000);
    document.getElementById('ongkir_preview').value =
        ongkir.toLocaleString('id-ID');
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>