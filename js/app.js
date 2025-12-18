// Single-file JS: map, selection, pages, and price list
let map;
const selectedIds = new Set();
let parcelsLayer;
let parcelsGeoJSON;
const state = {
  pricePerM2: 100000
};

// Format IDR currency
function formatIDR(value) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
}

// Render price table for list.html
function renderPriceTable() {
  const tbody = document.querySelector('#priceTable tbody');
  if (!tbody || !parcelsGeoJSON) return;
  
  tbody.innerHTML = '';
  parcelsGeoJSON.features.forEach((feature) => {
    const { id, name } = feature.properties;
    const area_m2 = turf.area(feature); // in m²
    const total = area_m2 * state.pricePerM2;
    
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${id}</td>
      <td>${name}</td>
      <td>${Math.round(area_m2).toLocaleString('id-ID')}</td>
      <td>${formatIDR(state.pricePerM2)}</td>
      <td>${formatIDR(total)}</td>
    `;
    tbody.appendChild(row);
  });
}

function loadParcels(){
  // returns a promise that resolves when parcelsGeoJSON is loaded
  if(parcelsGeoJSON) return Promise.resolve(parcelsGeoJSON);
  return fetch('data/parcels.geojson').then(r=>r.json()).then(gj=>{ 
    parcelsGeoJSON = gj; 
    renderPriceTable(); // Re-render if table exists
    return parcelsGeoJSON; 
  });
}

function initMap(){
  if(map) return loadParcels();
  map = L.map('map').setView([-7.797, 110.369], 14);
  // Base layers: OpenStreetMap and Esri World Imagery (satellite)
  const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  });

  const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri &mdash; Source: Esri, USGS, NOAA'
  });

  // show warning helper (creates element if missing)
  function showMapWarning(msg){
    let w = document.getElementById('mapWarning');
    if(!w){
      w = document.createElement('div');
      w.id = 'mapWarning';
      w.className = 'map-warning';
      const mapContainer = document.getElementById('map') || document.body;
      mapContainer.parentElement && mapContainer.parentElement.appendChild(w);
    }
    w.innerText = msg;
    w.style.display = 'block';
  }
  function clearMapWarning(){ const w = document.getElementById('mapWarning'); if(w) w.style.display='none'; }

  // add default basemap
  osmLayer.addTo(map);

  // layer control (basemaps only)
  const baseMaps = {
    'OpenStreetMap': osmLayer,
    'Satelit (Esri WorldImagery)': esriSat
  };
  L.control.layers(baseMaps, null, {collapsed: false}).addTo(map);

  // handle tile errors for satellite layer (some browsers block third-party storage)
  esriSat.on('tileerror', function(e){
    console.warn('Esri tile error', e);
    showMapWarning('Peta satelit diblokir oleh perlindungan pelacakan browser. Izinkan storage atau gunakan browser lain untuk melihat citra satelit. Basemap kembali ke OpenStreetMap.');
    // ensure OSM is visible
    if(!map.hasLayer(osmLayer)) osmLayer.addTo(map);
  });
  // clear warning when a tile successfully loads
  esriSat.on('tileload', function(){ clearMapWarning(); });

  function styleFeature(feature){ return {color:'#2262CC',weight:1,fillColor:'#3388ff',fillOpacity:0.4}; }
  function onEachFeature(feature, layer){
    layer.on('click', ()=> toggleFeatureSelection(feature));
    layer.on('mouseover', ()=> layer.setStyle({weight:3}));
    layer.on('mouseout', ()=> parcelsLayer.resetStyle(layer));
  }

  // load geojson and add to map
  return loadParcels().then(gj=>{
    parcelsLayer = L.geoJSON(gj, {style:styleFeature, onEachFeature:onEachFeature}).addTo(map);
    map.fitBounds(parcelsLayer.getBounds(), {padding:[40,40]});
    renderParcelList(); renderPriceTable(); updateCalculations();

    const drawnItems = new L.FeatureGroup().addTo(map);
    const drawControl = new L.Control.Draw({ draw:{polyline:false,rectangle:true,circle:false,marker:false,circlemarker:false,polygon:true}, edit:{featureGroup:drawnItems,edit:false,remove:true} });
    map.addControl(drawControl);
    map.on(L.Draw.Event.CREATED, function(e){
      const layer = e.layer; drawnItems.addLayer(layer);
      parcelsLayer.eachLayer(l=>{ try{ if(turf.booleanIntersects(layer.toGeoJSON(), l.feature)) selectedIds.add(l.feature.properties.id); }catch(e){} });
      updateSelectionStyles(); updateCalculations(); renderParcelList(); renderPriceTable();
    });
  });
}

function updateSelectionStyles(){
  parcelsLayer && parcelsLayer.eachLayer(l=>{
    const id = l.feature.properties.id;
    if(selectedIds.has(id)) l.setStyle({fillColor:'#ff7800',fillOpacity:0.6,color:'#ff4500'});
    else parcelsLayer.resetStyle(l);
  });
}

function updateCalculations(){
  let totalArea = 0;
  parcelsLayer && parcelsLayer.eachLayer(l=>{ if(selectedIds.has(l.feature.properties.id)) totalArea += turf.area(l.feature); });
  const priceEl = document.getElementById('pricePerM2');
  const price = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
  const total = Math.round(totalArea * price);
  const areaEl = document.getElementById('area'); if(areaEl) areaEl.innerText = Math.round(totalArea);
  const totalEl = document.getElementById('total'); if(totalEl) totalEl.innerText = new Intl.NumberFormat('id-ID').format(total);
  renderPriceTable();
}

function resetSelection(){ selectedIds.clear(); updateSelectionStyles(); updateCalculations(); renderParcelList(); renderPriceTable(); }

function toggleFeatureSelection(feature){ const id = feature.properties.id; if(selectedIds.has(id)) selectedIds.delete(id); else selectedIds.add(id); updateSelectionStyles(); updateCalculations(); renderParcelList(); }

function formatNumber(n){ return new Intl.NumberFormat('id-ID').format(n); }

function renderParcelList(){
  const ul = document.getElementById('list'); if(!ul || !parcelsGeoJSON) return; ul.innerHTML='';
  parcelsGeoJSON.features.forEach(f=>{
    const li = document.createElement('li'); li.className='parcel-item';
    const cb = document.createElement('input'); cb.type='checkbox'; cb.checked = selectedIds.has(f.properties.id);
    cb.addEventListener('change', ()=>{ if(cb.checked) selectedIds.add(f.properties.id); else selectedIds.delete(f.properties.id); updateSelectionStyles(); updateCalculations(); });
    const meta = document.createElement('div'); meta.className='meta';
    const name = document.createElement('div'); name.className='name'; name.innerText = f.properties.name || ('Bidang '+f.properties.id);
    const area = document.createElement('div'); area.className='area'; area.innerText = formatNumber(Math.round(turf.area(f)))+' m²';
    meta.appendChild(name); meta.appendChild(area);
    li.appendChild(cb); li.appendChild(meta);
    li.addEventListener('mouseenter', ()=> highlightFeatureById(f.properties.id));
    li.addEventListener('mouseleave', ()=> parcelsLayer.resetStyle());
    ul.appendChild(li);
  });
}

function highlightFeatureById(id){ parcelsLayer && parcelsLayer.eachLayer(l=>{ if(l.feature.properties.id===id) l.setStyle({fillColor:'#fde68a',fillOpacity:0.7,color:'#f59e0b'}); else parcelsLayer.resetStyle(l); }); }

function renderPriceTable(){
  const tbody = document.querySelector('#priceTable tbody'); if(!tbody || !parcelsGeoJSON) return; tbody.innerHTML='';
  const priceEl = document.getElementById('pricePerM2');
  const globalPrice = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
  parcelsGeoJSON.features.forEach(f=>{
    const tr = document.createElement('tr');
    const idTd = document.createElement('td'); idTd.innerText = f.properties.id;
    const nameTd = document.createElement('td'); nameTd.innerText = f.properties.name || '';
    const areaVal = Math.round(turf.area(f));
    const areaTd = document.createElement('td'); areaTd.innerText = formatNumber(areaVal);
    const priceTd = document.createElement('td'); priceTd.innerText = formatNumber(globalPrice);
    const totalTd = document.createElement('td'); totalTd.innerText = formatNumber(Math.round(areaVal * globalPrice));
    tr.appendChild(idTd); tr.appendChild(nameTd); tr.appendChild(areaTd); tr.appendChild(priceTd); tr.appendChild(totalTd);
    tbody.appendChild(tr);
  });
}

// UI bindings
document.addEventListener('DOMContentLoaded', ()=>{
  // page routing
  function showPage(name){
    if(!name) name='home';
    document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
    const pageEl = document.getElementById('page-'+name);
    if(pageEl) pageEl.classList.add('active');
    document.querySelectorAll('.nav-links a').forEach(a=>a.classList.toggle('active', a.dataset.page===name));
    if(name==='map'){ initMap(); setTimeout(()=>map.invalidateSize(),300); }
  }

  // delegate clicks for any element with data-page (nav links, buttons)
  document.body.addEventListener('click', (ev)=>{
    const btn = ev.target.closest('[data-page]');
    if(btn){
      ev.preventDefault();
      const page = btn.dataset.page;
      if(page){ location.hash = page; showPage(page); }
      return;
    }

    // actions: reset, zoom (use data-action)
    const action = ev.target.closest('[data-action]');
    if(action){
      const act = action.dataset.action;
      if(act==='reset') resetSelection();
      if(act==='zoom'){
        const layers = []; parcelsLayer && parcelsLayer.eachLayer(l=>{ if(selectedIds.has(l.feature.properties.id)) layers.push(l); });
        if(layers.length){ const group = L.featureGroup(layers); map.fitBounds(group.getBounds(), {padding:[40,40]}); }
      }
    }
  });

  // hash routing
  window.addEventListener('hashchange', ()=>{ const name = (location.hash||'#home').replace('#',''); showPage(name); });

  // init first page based on hash
  const start = (location.hash||'#home').replace('#',''); showPage(start);

  // bind toggle sidebar via delegation (button exists in map page)
  document.body.addEventListener('click', (ev)=>{
    const t = ev.target.closest('#toggleSidebar'); if(t){ const sb = document.getElementById('sidebar'); sb && sb.classList.toggle('collapsed'); document.querySelector('#toggleSidebar i')?.classList.toggle('fa-chevron-left'); }
  });

  // price input change handled via event delegation
  document.body.addEventListener('input', (ev)=>{
    const el = ev.target; if(el && el.id==='pricePerM2'){ updateCalculations(); const totalEl = document.getElementById('total'); if(totalEl){ totalEl.style.transition='transform .12s'; totalEl.style.transform='scale(1.05)'; setTimeout(()=>totalEl.style.transform='scale(1)',140); } }
  });
});

// If loaded directly on map.html or list.html, initialize appropriate parts
document.addEventListener('DOMContentLoaded', ()=>{
  // if map container exists on the page, init map
  if(document.getElementById('map')){
    initMap().then(()=> setTimeout(()=>map.invalidateSize(),300)).catch(()=>{});
  }
  // if price table exists, ensure parcels loaded and render
  if(document.querySelector('#priceTable')){
    loadParcels().then(()=>{ renderPriceTable(); }).catch(()=>{});
  }
});
