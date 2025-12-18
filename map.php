<?php 
include 'koneksi.php'; 

// Handler Backend untuk Simpan Data Baru (POST)
$input = json_decode(file_get_contents('php://input'), true);
if ($input && !isset($input['id'])) {
    header('Content-Type: application/json');
    $nama = mysqli_real_escape_string($conn, $input['nama']);
    $luas = $input['luas_lahan'];
    $harga = $input['price_per_m2'];
    $geojson = mysqli_real_escape_string($conn, $input['geojson_data']);

    $query = "INSERT INTO poligon (nama, luas_lahan, price_per_m2, geojson_data) 
              VALUES ('$nama', '$luas', '$harga', '$geojson')";

    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta - GeoValue</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <link rel="stylesheet" href="css/styles.css">
    
    <style>
        html, body { height: 100%; overflow: hidden; }
        .tab-container { background: #f1f5f9; padding: 6px; border-radius: 16px; }
        .tab-item { cursor: pointer; padding: 12px; font-weight: 700; border-radius: 12px; color: #64748b; text-align: center; font-size: 0.9rem; transition: 0.2s; }
        .tab-item.active { background: white; color: var(--blue); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <nav class="topnav">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="brand"><i class="fa-solid fa-map-location-dot"></i><span>LAHANJOGJA</span></div>
            <ul class="nav-links">
                <li><a href="index.php">Beranda</a></li>
                <li><a href="list.php">Database</a></li>
                <li><a href="map.php" class="active">Peta</a></li>
            </ul>
        </div>
    </nav>

    <main class="map-wrapper">
        <div id="map"></div>
        <button id="mapToggleBtn"><i class="fas fa-layer-group fa-lg"></i></button>

        <aside id="sidebar" class="hidden">
            <div class="sidebar-header">
                <h6 class="m-0 fw-bold text-dark">PANEL ANALISIS</h6>
                <button id="closeSidebar" class="btn btn-sm btn-light rounded-circle shadow-sm border"><i class="fas fa-times"></i></button>
            </div>

            <div class="p-4 overflow-y-auto flex-fill">
                <!-- Tabs -->
                <div class="tab-container d-flex mb-4">
                    <div id="tabInput" class="tab-item flex-fill active" onclick="resetToInput()">
                        <i class="fas fa-pen-nib me-1"></i> Input Data
                    </div>
                    <div id="tabView" class="tab-item flex-fill" style="pointer-events: none; opacity: 0.5;">
                        <i class="fas fa-edit me-1"></i> Edit Mode
                    </div>
                </div>

                <div id="formSection">
                    <input type="hidden" id="editId">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">NAMA BIDANG TANAH</label>
                        <input type="text" id="areaName" class="form-control" placeholder="Contoh: Lahan Blok A..." style="padding: 12px; border-radius: 12px;">
                    </div>

                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted">Luas Terukur</span>
                            <span class="fw-bold text-dark"><span id="calcArea">0</span> m²</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small text-muted">NJOP (Est)</span>
                            <span class="badge bg-light text-dark border">Rp 3.500.000</span>
                        </div>
                        <hr class="my-2 opacity-10">
                        <div class="text-center">
                            <span class="small text-muted d-block">Total Valuasi Aset</span>
                            <span class="h3 fw-bold m-0" style="color: var(--green-dark);">
                                Rp <span id="calcTotal">0</span>
                            </span>
                        </div>
                    </div>

                    <!-- Input Controls -->
                    <div id="inputControls">
                        <button id="saveToDB" class="btn-action btn-blue w-100" disabled>
                            <i class="fas fa-save me-2"></i>SIMPAN KE DATABASE
                        </button>
                        <p class="text-muted text-center small mt-3 fst-italic">
                            *Gambar polygon di peta untuk mengaktifkan tombol.
                        </p>
                    </div>

                    <!-- Edit/Delete Controls -->
                    <div id="viewControls" class="d-none">
                        <div class="alert alert-warning border-0 shadow-sm small mb-3 rounded-3" style="background: #fffbeb; color: #b45309;">
                            <i class="fas fa-exclamation-circle me-1"></i> Edit bentuk area pada peta.
                        </div>
                        <button id="updateDB" class="btn-action btn-green w-100 mb-3">
                            <i class="fas fa-check-circle me-2"></i>SIMPAN PERUBAHAN
                        </button>
                        <button id="deleteDB" class="btn-action btn-red w-100">
                            <i class="fas fa-trash-alt me-2"></i>HAPUS DATA
                        </button>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

    <script>
        let mapInstance, parcelsLayer;
        let drawnItems = new L.FeatureGroup();
        let editLayer = null; 
        const HARGA_JOGJA = 3500000;
        let currentGeometry = null;

        function initMap() {
            // 1. BASEMAPS
            const voyager = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CARTO', maxZoom: 20
            });
            const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri'
            });
            const darkMatter = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CARTO', maxZoom: 20
            });
            const topoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap', maxZoom: 17
            });

            // 2. INIT MAP
            mapInstance = L.map('map', { 
                zoomControl: false, 
                layers: [voyager] 
            }).setView([-7.797, 110.369], 11);

            L.control.zoom({ position: 'bottomright' }).addTo(mapInstance);
            L.control.layers({
                "Peta Jalan": voyager,
                "Satelit": satellite,
                "Mode Gelap": darkMatter,
                "Topografi": topoMap
            }, null, { position: 'bottomleft' }).addTo(mapInstance);

            mapInstance.addLayer(drawnItems);
            
            // 3. LOAD DATA
            loadAdminBoundary(); // Load Batas DIY
            loadDataMySQL();     // Load Data User

            // 4. DRAW CONTROLS
            const drawControl = new L.Control.Draw({
                draw: { 
                    polygon: {
                        allowIntersection: false,
                        showArea: true,
                        shapeOptions: { color: '#3b82f6', weight: 3, fillOpacity: 0.2 } 
                    }, 
                    rectangle: { shapeOptions: { color: '#3b82f6', weight: 3, fillOpacity: 0.2 } }, 
                    polyline: false, circle: false, marker: false, circlemarker: false 
                }
            });
            mapInstance.addControl(drawControl);

            // Handler Saat Selesai Menggambar Baru
            mapInstance.on(L.Draw.Event.CREATED, (e) => {
                clearMapLayers();
                drawnItems.addLayer(e.layer);
                updateGeomInfo(e.layer);
                setMode('input');
                document.getElementById('saveToDB').disabled = false;
                openSidebar();
            });
        }

        // --- LOAD BATAS ADMINISTRASI (DIY) ---
        async function loadAdminBoundary() {
            try {
                const response = await fetch('data/Prov_DIY.json');
                if (!response.ok) throw new Error('File GeoJSON tidak ditemukan');
                const data = await response.json();
                
                const adminLayer = L.geoJSON(data, {
                    style: {
                        color: '#64748b',
                        weight: 2,
                        opacity: 0.8,
                        dashArray: '10, 5',
                        fillColor: 'transparent',
                        fillOpacity: 0
                    },
                    interactive: false // PENTING: Agar klik tembus ke layer di bawahnya
                }).addTo(mapInstance);

                mapInstance.fitBounds(adminLayer.getBounds());
            } catch (error) {
                console.error("Gagal memuat batas:", error);
            }
        }

        // --- LOAD DATA DATABASE & HANDLE CLICK (EDIT) ---
        async function loadDataMySQL() {
            const resp = await fetch('get_data.php');
            const data = await resp.json();
            
            if (parcelsLayer) mapInstance.removeLayer(parcelsLayer);
            
            parcelsLayer = L.geoJSON(data, {
                style: { color: '#3b82f6', weight: 2, fillOpacity: 0.15 },
                onEachFeature: (feature, layer) => {
                    // PENTING: Event Listener untuk Edit saat diklik
                    layer.on('click', (e) => {
                        L.DomEvent.stopPropagation(e); // Mencegah event bubbling
                        startEditMode(feature, layer);
                    });
                    
                    // Hover effect
                    layer.on('mouseover', function () { this.setStyle({ weight: 4, fillOpacity: 0.3 }); });
                    layer.on('mouseout', function () { this.setStyle({ weight: 2, fillOpacity: 0.15 }); });
                }
            }).addTo(mapInstance);
        }

        // --- FUNGSI MODE EDIT ---
        function startEditMode(feature, originalLayer) {
            console.log("Edit Mode Started for ID:", feature.properties.id); // Debugging
            
            clearMapLayers();
            setMode('view'); // Pindah tab ke Edit Mode
            
            // Buat layer baru yang bisa diedit (Warna Orange)
            editLayer = L.polygon(originalLayer.getLatLngs(), { 
                color: '#f59e0b', 
                weight: 3, 
                fillOpacity: 0.2, 
                dashArray: '5, 5' 
            }).addTo(mapInstance);
            
            editLayer.editing.enable(); // Aktifkan library Leaflet.Draw editing
            
            // Isi form dengan data yang diklik
            const p = feature.properties;
            document.getElementById('editId').value = p.id;
            
            // Update Kalkulasi
            updateGeomInfo(editLayer);
            updateUI(p.name, p.luas, p.luas * (p.price || HARGA_JOGJA));

            // Listener jika bentuk diubah saat mode edit
            editLayer.on('edit', () => updateGeomInfo(editLayer));
            
            openSidebar();
        }

        function updateGeomInfo(layer) {
            const geojson = layer.toGeoJSON();
            currentGeometry = JSON.stringify(geojson.geometry);
            const area = turf.area(geojson);
            updateUI(document.getElementById('areaName').value, area, area * HARGA_JOGJA);
        }

        function clearMapLayers() {
            drawnItems.clearLayers();
            if (editLayer) { mapInstance.removeLayer(editLayer); editLayer = null; }
        }

        function resetToInput() {
            clearMapLayers();
            setMode('input');
            updateUI("", 0, 0);
            document.getElementById('editId').value = ''; // Reset ID
            document.getElementById('saveToDB').disabled = true;
        }

        function setMode(mode) {
            const tabInput = document.getElementById('tabInput');
            const tabView = document.getElementById('tabView');
            
            if (mode === 'input') {
                tabInput.className = "tab-item flex-fill active";
                tabView.className = "tab-item flex-fill"; 
                tabView.style.opacity = "0.5";
                document.getElementById('inputControls').classList.remove('d-none');
                document.getElementById('viewControls').classList.add('d-none');
            } else {
                // View / Edit Mode
                tabInput.className = "tab-item flex-fill";
                tabView.className = "tab-item flex-fill active"; // Highlight tab edit
                tabView.style.opacity = "1";
                document.getElementById('inputControls').classList.add('d-none');
                document.getElementById('viewControls').classList.remove('d-none');
            }
        }

        function updateUI(nama, luas, total) {
            document.getElementById('areaName').value = nama;
            document.getElementById('calcArea').innerText = Math.round(luas).toLocaleString('id-ID');
            document.getElementById('calcTotal').innerText = Math.round(total).toLocaleString('id-ID');
        }

        // --- CRUD ACTIONS ---
        document.getElementById('saveToDB').onclick = async () => {
            const payload = {
                nama: document.getElementById('areaName').value,
                luas_lahan: parseInt(document.getElementById('calcArea').innerText.replace(/\./g, '')),
                price_per_m2: HARGA_JOGJA,
                geojson_data: currentGeometry
            };
            const resp = await fetch('map.php', { method: 'POST', body: JSON.stringify(payload) });
            if((await resp.json()).status === 'success') location.reload();
        };

        document.getElementById('updateDB').onclick = async () => {
            const payload = {
                id: document.getElementById('editId').value,
                nama: document.getElementById('areaName').value,
                luas_lahan: parseInt(document.getElementById('calcArea').innerText.replace(/\./g, '')),
                geojson_data: currentGeometry
            };
            const resp = await fetch('update_data.php', { method: 'POST', body: JSON.stringify(payload) });
            if((await resp.json()).status === 'success') location.reload();
        };

        document.getElementById('deleteDB').onclick = async () => {
            if(!confirm("Hapus data ini?")) return;
            const resp = await fetch('delete_data.php', { method: 'POST', body: JSON.stringify({id: document.getElementById('editId').value}) });
            if((await resp.json()).status === 'success') location.reload();
        };

        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('mapToggleBtn');

        function openSidebar() {
            sidebar.classList.remove('hidden');
            toggleBtn.style.opacity = '0';
            toggleBtn.style.pointerEvents = 'none';
        }
        function closeSidebar() {
            sidebar.classList.add('hidden');
            toggleBtn.style.opacity = '1';
            toggleBtn.style.pointerEvents = 'auto';
        }

        document.getElementById('mapToggleBtn').onclick = openSidebar;
        document.getElementById('closeSidebar').onclick = closeSidebar;

        initMap();
    </script>
</body>
</html>