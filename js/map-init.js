// Map initialization with toggle sidebar
let mapInstance;
let selectedIds = new Set();
let parcelsLayer;
let parcelsGeoJSON;

function initMapPage() {
  const mapEl = document.getElementById('map');
  if (!mapEl) return;
  
  mapInstance = L.map('map').setView([-7.797, 110.369], 14);
  
  const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  });
  
  const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri'
  });
  
  osmLayer.addTo(mapInstance);
  L.control.layers({'OpenStreetMap': osmLayer, 'Satelit': esriSat}, null, {collapsed: false}).addTo(mapInstance);
  
  fetch('data/parcels.geojson').then(r => r.json()).then(gj => {
    parcelsGeoJSON = gj;
    parcelsLayer = L.geoJSON(gj, {
      style: {color: '#2262CC', weight: 1, fillColor: '#3388ff', fillOpacity: 0.4},
      onEachFeature: (f, l) => l.on('click', () => {
        const id = f.properties.id;
        selectedIds.has(id) ? selectedIds.delete(id) : selectedIds.add(id);
        updateMapStyles();
        renderParcelList();
        updateCalculations();
      })
    }).addTo(mapInstance);
    
    mapInstance.fitBounds(parcelsLayer.getBounds(), {padding: [50, 50]});
    renderParcelList();
    updateCalculations();
    setTimeout(() => mapInstance.invalidateSize(), 300);
  });
}

function updateMapStyles() {
  if (!parcelsLayer) return;
  parcelsLayer.eachLayer(l => {
    const id = l.feature.properties.id;
    l.setStyle(selectedIds.has(id) ? {fillColor: '#ff7800', fillOpacity: 0.6, color: '#ff4500'} : {});
  });
  parcelsLayer.resetStyle();
}

function updateCalculations() {
  let totalArea = 0;
  if (parcelsLayer) parcelsLayer.eachLayer(l => {
    if (selectedIds.has(l.feature.properties.id)) totalArea += turf.area(l.feature);
  });
  const price = parseFloat(document.getElementById('pricePerM2')?.value) || 0;
  const total = Math.round(totalArea * price);
  const areaEl = document.getElementById('area');
  const totalEl = document.getElementById('total');
  if (areaEl) areaEl.innerText = Math.round(totalArea);
  if (totalEl) totalEl.innerText = new Intl.NumberFormat('id-ID').format(total);
}

function renderParcelList() {
  const ul = document.getElementById('list');
  if (!ul || !parcelsGeoJSON) return;
  ul.innerHTML = '';
  parcelsGeoJSON.features.forEach(f => {
    const li = document.createElement('li');
    li.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 8px; border-radius: 6px; border: 1px solid #f1f5f9; background: white; cursor: pointer;';
    
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = selectedIds.has(f.properties.id);
    cb.style.width = '18px';
    cb.addEventListener('change', () => {
      cb.checked ? selectedIds.add(f.properties.id) : selectedIds.delete(f.properties.id);
      updateMapStyles();
      updateCalculations();
    });
    
    const meta = document.createElement('div');
    meta.style.flex = '1';
    const name = document.createElement('div');
    name.style.cssText = 'font-weight: 600; font-size: 13px;';
    name.innerText = f.properties.name || ('Bidang ' + f.properties.id);
    const area = document.createElement('div');
    area.style.cssText = 'font-size: 12px; color: #94a3b8;';
    area.innerText = new Intl.NumberFormat('id-ID').format(Math.round(turf.area(f))) + ' m²';
    meta.appendChild(name);
    meta.appendChild(area);
    li.appendChild(cb);
    li.appendChild(meta);
    ul.appendChild(li);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initMapPage();
  
  // Reset button
  const resetBtn = document.getElementById('resetSelection');
  if (resetBtn) resetBtn.addEventListener('click', () => {
    selectedIds.clear();
    updateMapStyles();
    renderParcelList();
    updateCalculations();
  });
  
  // Zoom button
  const zoomBtn = document.getElementById('zoomToSelection');
  if (zoomBtn) zoomBtn.addEventListener('click', () => {
    const layers = [];
    if (parcelsLayer) parcelsLayer.eachLayer(l => {
      if (selectedIds.has(l.feature.properties.id)) layers.push(l);
    });
    if (layers.length && mapInstance) {
      const group = L.featureGroup(layers);
      mapInstance.fitBounds(group.getBounds(), {padding: [50, 50]});
    }
  });
  
  // Price input
  const priceInput = document.getElementById('pricePerM2');
  if (priceInput) priceInput.addEventListener('input', updateCalculations);
  
  // Toggle sidebar with single button that always stays visible
  const mapToggleBtn = document.getElementById('mapToggleBtn');
  const sidebar = document.getElementById('sidebar');
  const toggleSidebarBtn = document.getElementById('toggleSidebar');
  
  if (mapToggleBtn && sidebar) {
    mapToggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('hidden');
      // Update button icon - change between menu and close icon
      const isHidden = sidebar.classList.contains('hidden');
      mapToggleBtn.innerHTML = isHidden ? '<i class="fas fa-sliders-h"></i>' : '<i class="fas fa-times"></i>';
    });
  }
  
  // Close button inside sidebar also toggles the button back
  if (toggleSidebarBtn && sidebar) {
    toggleSidebarBtn.addEventListener('click', () => {
      sidebar.classList.add('hidden');
      if (mapToggleBtn) mapToggleBtn.innerHTML = '<i class="fas fa-sliders-h"></i>';
    });
  }
});
