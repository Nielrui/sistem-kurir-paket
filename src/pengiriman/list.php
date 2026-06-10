<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SESSION['role'] !== 'kurir') {
    header('Location: ../dashboard/admin.php');
    exit;
}

// Ambil data kurir
$stmtKurir = $pdo->prepare("SELECT * FROM kurir WHERE user_id = ?");
$stmtKurir->execute([$_SESSION['user_id']]);
$kurir = $stmtKurir->fetch();

if (!$kurir) {
    die('Data kurir tidak ditemukan. Hubungi admin.');
}

// Update status pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pengiriman_id = (int)$_POST['pengiriman_id'];
    $paket_id      = (int)$_POST['paket_id'];
    $status_baru   = $_POST['status_baru'];

    $allowed = ['dalam_pengiriman', 'selesai', 'gagal'];
    if (in_array($status_baru, $allowed)) {

        // Update status pengiriman
        $stmtUpd = $pdo->prepare("UPDATE pengiriman SET status = ? WHERE id = ? AND kurir_id = ?");
        $stmtUpd->execute([$status_baru, $pengiriman_id, $kurir['id']]);

        // Update status paket
        $statusPaket = match ($status_baru) {
            'dalam_pengiriman' => 'diantar',
            'selesai'          => 'terkirim',
            'gagal'            => 'gagal',
            default            => 'diantar'
        };
        $stmtPaket = $pdo->prepare("UPDATE paket SET status = ? WHERE id = ?");
        $stmtPaket->execute([$statusPaket, $paket_id]);

        // Insert tracking log
        $keterangan = match ($status_baru) {
            'dalam_pengiriman' => 'Paket sedang dalam perjalanan ke penerima',
            'selesai'          => 'Paket berhasil diterima oleh penerima',
            'gagal'            => 'Pengiriman gagal',
            default            => 'Status diperbarui'
        };
        $stmtLog = $pdo->prepare("
            INSERT INTO tracking_log (paket_id, status, keterangan)
            VALUES (?, ?, ?)
        ");
        $stmtLog->execute([$paket_id, $statusPaket, $keterangan]);
    }

    header('Location: list.php');
    exit;
}

// Ambil tugas hari ini
$hariIni = date('Y-m-d');
$stmtTugas = $pdo->prepare("
    SELECT pg.id as pengiriman_id, pg.status as status_pengiriman,
           p.id as paket_id, p.no_resi, p.nama_penerima,
           p.telp_penerima, p.alamat_tujuan, p.kota_tujuan,
           p.berat, p.status as status_paket,
           p.lat, p.lng
    FROM pengiriman pg
    JOIN paket p ON pg.paket_id = p.id
    WHERE pg.kurir_id = ? AND pg.tanggal = ?
    ORDER BY pg.id ASC
");
$stmtTugas->execute([$kurir['id'], $hariIni]);
$tugas = $stmtTugas->fetchAll();

// Hitung rute optimal (Nearest Neighbor Algorithm)
// Titik awal: Gudang Utama Malang
$gudang = ['lat' => -7.9797, 'lng' => 112.6304, 'nama' => 'Gudang Utama'];

function hitungJarak($lat1, $lng1, $lat2, $lng2)
{
    // Haversine formula — jarak dalam km
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

// Sort paket yang punya koordinat
$tugasDenganKoordinat = array_filter($tugas, fn($t) => $t['lat'] && $t['lng']);
$tugasTanpaKoordinat  = array_filter($tugas, fn($t) => !$t['lat'] || !$t['lng']);

// Nearest neighbor sorting
$ruteOptimal = [];
$belumDikunjungi = array_values($tugasDenganKoordinat);
$currentLat = $gudang['lat'];
$currentLng = $gudang['lng'];

while (!empty($belumDikunjungi)) {
    $jarakMin = PHP_FLOAT_MAX;
    $indexTerdekat = 0;

    foreach ($belumDikunjungi as $i => $t) {
        $jarak = hitungJarak($currentLat, $currentLng, $t['lat'], $t['lng']);
        if ($jarak < $jarakMin) {
            $jarakMin = $jarak;
            $indexTerdekat = $i;
        }
    }

    $ruteOptimal[] = array_merge($belumDikunjungi[$indexTerdekat], [
        'jarak_dari_sebelumnya' => round($jarakMin, 2)
    ]);
    $currentLat = $belumDikunjungi[$indexTerdekat]['lat'];
    $currentLng = $belumDikunjungi[$indexTerdekat]['lng'];
    array_splice($belumDikunjungi, $indexTerdekat, 1);
}

// Gabungkan — yang punya koordinat (sudah diurutkan) + yang tidak punya
$tugasTerurut = array_merge($ruteOptimal, array_values($tugasTanpaKoordinat));

require_once __DIR__ . '/../templates/header.php';
?>

<h4 class="fw-semibold mb-4">
    Tugas Pengiriman Hari Ini —
    <span class="text-muted fw-normal"><?= date('d F Y') ?></span>
</h4>

<?php if (empty($tugas)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <h5 class="text-muted">Tidak ada tugas pengiriman hari ini</h5>
        </div>
    </div>
<?php else: ?>

    <!-- Peta Rute Optimal -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-map me-2"></i>Peta Rute Pengiriman Optimal
            <span class="badge bg-primary ms-2"><?= count($ruteOptimal) ?> titik</span>
        </div>
        <div class="card-body p-0">
            <div id="map" style="height:400px; border-radius:0 0 8px 8px;"></div>
        </div>
    </div>

    <!-- Daftar Tugas Terurut -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-list-ol me-2"></i>Urutan Pengiriman
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px">Urutan</th>
                        <th>No Resi</th>
                        <th>Penerima</th>
                        <th>Alamat</th>
                        <th>Jarak</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tugasTerurut as $i => $t):
                        $badge = match ($t['status_pengiriman']) {
                            'assigned'         => 'warning',
                            'dalam_pengiriman' => 'primary',
                            'selesai'          => 'success',
                            'gagal'            => 'danger',
                            default            => 'secondary'
                        };
                    ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-dark rounded-circle"
                                    style="width:28px;height:28px;line-height:18px;">
                                    <?= $i + 1 ?>
                                </span>
                            </td>
                            <td class="fw-semibold"><?= htmlspecialchars($t['no_resi']) ?></td>
                            <td>
                                <?= htmlspecialchars($t['nama_penerima']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($t['telp_penerima'] ?? '-') ?></small>
                            </td>
                            <td>
                                <?= htmlspecialchars($t['alamat_tujuan']) ?>
                                <?php if ($t['kota_tujuan']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($t['kota_tujuan']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($t['jarak_dari_sebelumnya'])): ?>
                                    <span class="badge bg-light text-dark">
                                        <?= $t['jarak_dari_sebelumnya'] ?> km
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $badge ?>">
                                    <?= $t['status_pengiriman'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($t['status_pengiriman'] === 'assigned'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="pengiriman_id" value="<?= $t['pengiriman_id'] ?>">
                                        <input type="hidden" name="paket_id" value="<?= $t['paket_id'] ?>">
                                        <input type="hidden" name="status_baru" value="dalam_pengiriman">
                                        <button name="update_status" class="btn btn-sm btn-primary">
                                            <i class="bi bi-truck"></i> Ambil
                                        </button>
                                    </form>
                                <?php elseif ($t['status_pengiriman'] === 'dalam_pengiriman'): ?>
                                    <div class="d-flex gap-1">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="pengiriman_id" value="<?= $t['pengiriman_id'] ?>">
                                            <input type="hidden" name="paket_id" value="<?= $t['paket_id'] ?>">
                                            <input type="hidden" name="status_baru" value="selesai">
                                            <button name="update_status" class="btn btn-sm btn-success">
                                                <i class="bi bi-check"></i> Terkirim
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="pengiriman_id" value="<?= $t['pengiriman_id'] ?>">
                                            <input type="hidden" name="paket_id" value="<?= $t['paket_id'] ?>">
                                            <input type="hidden" name="status_baru" value="gagal">
                                            <button name="update_status" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x"></i> Gagal
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
const map = L.map('map').setView([-7.9797, 112.6304], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

const rute = <?= json_encode(array_values($ruteOptimal)) ?>;
let lokasiKurir = [-7.9797, 112.6304];

async function gambarRuteJalan(coords) {
    const coordStr = coords.map(c => `${c[1]},${c[0]}`).join(';');
    const url = `https://router.project-osrm.org/route/v1/driving/${coordStr}?overview=full&geometries=geojson`;
    try {
        const res  = await fetch(url);
        const data = await res.json();
        if (data.code === 'Ok') {
            const jarak  = (data.routes[0].distance / 1000).toFixed(1);
            const durasi = Math.round(data.routes[0].duration / 60);
            L.geoJSON(data.routes[0].geometry, {
                style: { color: '#0d6efd', weight: 4, opacity: 0.8 }
            }).addTo(map);
            L.popup()
             .setLatLng(coords[0])
             .setContent(`🗺️ Total rute: <b>${jarak} km</b> · <b>${durasi} menit</b>`)
             .openOn(map);
        }
    } catch(e) {
        L.polyline(coords, {
            color: '#0d6efd', weight: 3, opacity: 0.8, dashArray: '8, 4'
        }).addTo(map);
    }
}

function tampilkanPeta(startCoords) {
    const kurirIcon = L.divIcon({
        html: `<div style="background:#198754;color:#fff;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600;white-space:nowrap;">📍 Lokasi Kamu</div>`,
        className: ''
    });
    L.marker(startCoords, {icon: kurirIcon})
     .addTo(map)
     .bindPopup('<b>Lokasi Kamu Sekarang</b>');

    if (rute.length > 0) {
        const ruteCoords = [startCoords];
        rute.forEach((t, i) => {
            const lat = parseFloat(t.lat);
            const lng = parseFloat(t.lng);
            ruteCoords.push([lat, lng]);
            const icon = L.divIcon({
                html: `<div style="background:#0d6efd;color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,0.3);">${i+1}</div>`,
                className: '', iconSize: [28,28], iconAnchor: [14,14]
            });
            L.marker([lat, lng], {icon})
             .addTo(map)
             .bindPopup(`<b>${i+1}. ${t.no_resi}</b><br>${t.nama_penerima}<br>${t.alamat_tujuan}<br><span style="font-size:11px">${t.jarak_dari_sebelumnya} km dari titik sebelumnya</span>`);
        });
        gambarRuteJalan(ruteCoords);
        map.fitBounds(ruteCoords);
    }
}

function setLokasiManual(e) {
    lokasiKurir = [e.latlng.lat, e.latlng.lng];
    map.eachLayer(l => { if (!(l instanceof L.TileLayer)) map.removeLayer(l); });
    tampilkanPeta(lokasiKurir);
    map.off('click', setLokasiManual);
}

if (navigator.geolocation) {
    const loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(255,255,255,0.95);padding:12px 20px;border-radius:8px;z-index:999;font-size:13px;box-shadow:0 2px 12px rgba(0,0,0,0.15);text-align:center;';
    loadingDiv.innerHTML = '📍 Mendapatkan lokasi...';
    document.getElementById('map').style.position = 'relative';
    document.getElementById('map').appendChild(loadingDiv);

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            loadingDiv.remove();
            lokasiKurir = [pos.coords.latitude, pos.coords.longitude];
            tampilkanPeta(lokasiKurir);
        },
        function(err) {
            loadingDiv.innerHTML = `
                <div style="font-weight:600;margin-bottom:8px;">📍 Lokasi otomatis tidak tersedia</div>
                <div style="font-size:12px;color:#666;margin-bottom:10px;">Klik peta untuk set lokasi, atau:</div>
                <button id="btnGudang" style="background:#0d6efd;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-size:12px;cursor:pointer;">
                    Mulai dari Gudang
                </button>`;
            document.getElementById('btnGudang').onclick = function() {
                loadingDiv.remove();
                map.off('click', setLokasiManual);
                tampilkanPeta(lokasiKurir);
            };
            map.on('click', setLokasiManual);
        },
        { timeout: 5000 }
    );
} else {
    tampilkanPeta(lokasiKurir);
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>