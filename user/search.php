<?php
require_once '../includes/functions.php';
requireLogin('user', '../auth/login.php');
$user      = getCurrentUser();
$initQuery = htmlspecialchars($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Medicines — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    /* ── Search hero ── */
    .search-hero {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 28px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .big-search {
      display: flex; gap: 10px; align-items: center;
    }
    .big-search input {
      flex: 1; padding: 14px 20px; font-size: 1.05rem;
      border: 2px solid var(--border-color); border-radius: 50px;
      background: var(--bg-page); color: var(--text-primary);
      outline: none; transition: var(--transition);
    }
    .big-search input:focus {
      border-color: var(--clr-primary);
      box-shadow: 0 0 0 4px rgba(10,110,79,.1);
    }
    .big-search input::placeholder { color: var(--text-muted); }

    /* ── Location bar ── */
    .location-bar {
      display: flex; align-items: center; gap: 10px;
      margin-top: 14px; flex-wrap: wrap;
    }
    .loc-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 12px; border-radius: 20px; font-size: .8rem;
      font-weight: 600; cursor: pointer; transition: var(--transition);
      border: 1.5px solid;
    }
    .loc-badge.detecting {
      background: var(--clr-warning-lt); color: var(--clr-warning);
      border-color: var(--clr-warning); animation: pulse-border 1.5s infinite;
    }
    .loc-badge.active {
      background: var(--clr-primary-dim); color: var(--clr-primary);
      border-color: var(--clr-primary);
    }
    .loc-badge.inactive {
      background: var(--bg-page); color: var(--text-muted);
      border-color: var(--border-color);
    }
    .loc-badge.error {
      background: var(--clr-danger-lt); color: var(--clr-danger);
      border-color: var(--clr-danger);
    }
    @keyframes pulse-border {
      0%,100% { opacity:1; } 50% { opacity:.5; }
    }
    /* Change location link */
    .change-loc-btn {
      background: none; border: none; padding: 0;
      font-size: .78rem; color: var(--clr-primary);
      cursor: pointer; text-decoration: underline; font-weight: 600;
    }
    /* ── Location map modal ── */
    #locMapModal .modal { max-width: 680px; }
    #locMapEl { height: 400px; border-radius: 8px; z-index: 1; }
    .map-search-bar {
      display: flex; gap: 8px; margin-bottom: 12px;
    }
    .map-search-bar input {
      flex: 1; padding: 8px 14px; border: 1.5px solid var(--border-color);
      border-radius: 8px; font-size: .88rem; background: var(--bg-page);
      color: var(--text-primary); outline: none;
    }
    .map-search-bar input:focus { border-color: var(--clr-primary); }
    .map-pin-hint {
      font-size: .8rem; color: var(--text-muted);
      margin-bottom: 10px; display: flex; align-items: center; gap: 6px;
    }
    .saved-loc-label {
      font-size: .78rem; color: var(--clr-primary); font-weight: 600;
      max-width: 220px; white-space: nowrap; overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Radius selector */
    .radius-select {
      padding: 5px 12px; border-radius: 20px;
      border: 1.5px solid var(--border-color);
      background: var(--bg-card); font-size: .8rem;
      color: var(--text-secondary); cursor: pointer;
      appearance: none; padding-right: 28px;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 10 6'%3E%3Cpath fill='%234A6355' d='M1 1l4 4 4-4'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 8px center;
      background-size: 8px;
    }

    /* ── Mode banner ── */
    .mode-banner {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: var(--border-radius-sm);
      margin-bottom: 20px; font-size: .875rem; font-weight: 500;
    }
    .mode-exact { background: var(--clr-success-lt); color: var(--clr-success); }
    .mode-alt   { background: var(--clr-warning-lt); color: var(--clr-warning); }
    .mode-part  { background: var(--clr-info-lt);    color: var(--clr-info); }

    /* ── Radius notice banner ── */
    .radius-notice {
      display: flex; align-items: center; gap: 8px;
      padding: 10px 14px; border-radius: var(--border-radius-sm);
      background: var(--clr-primary-dim); color: var(--clr-primary);
      font-size: .82rem; font-weight: 500; margin-bottom: 16px;
    }

    /* ── Alt divider ── */
    .alt-divider {
      display: flex; align-items: center; gap: 12px; margin: 28px 0 18px;
    }
    .alt-divider::before, .alt-divider::after {
      content: ''; flex: 1; height: 2px; background: var(--clr-warning-lt);
    }
    .alt-divider span {
      background: var(--clr-warning-lt); color: var(--clr-warning);
      padding: 5px 14px; border-radius: 20px;
      font-size: .8rem; font-weight: 600; white-space: nowrap;
    }

    /* ── Medicine card ── */
    .medicine-card {
      background: var(--bg-card); border: 1px solid var(--border-color);
      border-radius: var(--border-radius); padding: 20px;
      margin-bottom: 16px; transition: var(--transition); box-shadow: var(--shadow-sm);
    }
    .medicine-card:hover { box-shadow: var(--shadow-md); }
    .medicine-card-header {
      display: flex; align-items: flex-start;
      justify-content: space-between; gap: 12px;
      margin-bottom: 12px; flex-wrap: wrap;
    }
    .medicine-card-title h4 { font-size: 1rem; font-weight: 600; }
    .medicine-card-title p  { font-size: .8rem; color: var(--text-muted); margin-top: 2px; }

    /* ── Pharmacy item ── */
    .pharmacy-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px 14px; background: var(--bg-page);
      border-radius: var(--border-radius-sm); margin-bottom: 8px;
      flex-wrap: wrap; gap: 8px; font-size: .85rem;
    }
    .pharmacy-item:last-child { margin-bottom: 0; }
    .pharmacy-item-left h5  { font-size: .88rem; font-weight: 600; }
    .pharmacy-item-left p   { font-size: .76rem; color: var(--text-muted); }
    .pharmacy-item-right    { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* ── Distance badge ── */
    .dist-badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 9px; border-radius: 20px;
      font-size: .72rem; font-weight: 700;
    }
    .dist-near    { background: var(--clr-success-lt); color: var(--clr-success); }
    .dist-mid     { background: var(--clr-warning-lt); color: var(--clr-warning); }
    .dist-far     { background: var(--clr-danger-lt);  color: var(--clr-danger); }
    .dist-unknown { background: var(--bg-hover);        color: var(--text-muted); }
    .dist-outside { background: var(--clr-danger-lt);  color: var(--clr-danger);
                    opacity: .7; font-style: italic; }

    /* ── Qty badge ── */
    .qty-badge { padding: 3px 10px; border-radius: 6px; font-weight: 700; font-size: .8rem; }
    .qty-badge.high { background: var(--clr-success-lt); color: var(--clr-success); }
    .qty-badge.mid  { background: var(--clr-warning-lt); color: var(--clr-warning); }
    .qty-badge.low  { background: var(--clr-danger-lt);  color: var(--clr-danger); }
    .price-tag { font-weight: 700; color: var(--clr-primary); font-size: .88rem; }

    /* ── Out-of-range pharmacy (dimmed) ── */
    .pharmacy-item.out-of-range { opacity: .55; }
    .pharmacy-item.out-of-range::after {
      content: 'Outside ' attr(data-radius) ' km range';
      font-size: .7rem; color: var(--clr-danger);
      width: 100%; text-align: right; font-style: italic;
    }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Search Medicines</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="user-role">User</div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-content">

      <!-- ── Search Box ── -->
      <div class="search-hero">
        <div style="margin-bottom:16px">
          <h2 style="font-size:1.4rem;margin-bottom:4px">🔍 Medicine Search</h2>
          <p style="font-size:.875rem">Search by name or composition. We find availability across all pharmacies.</p>
        </div>

        <div class="big-search">
          <input type="text" id="searchInput"
                 placeholder="e.g. Paracetamol, Ibuprofen, Azithromycin…"
                 value="<?= $initQuery ?>"
                 onkeydown="if(event.key==='Enter') doSearch()">
          <button class="btn btn-primary btn-lg" id="searchBtn" onclick="doSearch()">Search</button>
          <button class="btn btn-ghost" onclick="clearSearch()" title="Clear">✕</button>
        </div>

        <!-- Location bar -->
        <div class="location-bar">
          <span style="font-size:.8rem;color:var(--text-muted)">Pharmacy filter:</span>

          <div class="loc-badge inactive" id="locBadge" onclick="toggleLocation()">
            <span id="locIcon">📍</span>
            <span id="locText">Set My Location</span>
          </div>

          <span id="savedLocLabel" class="saved-loc-label" style="display:none"></span>
          <button class="change-loc-btn" id="changeLocBtn" style="display:none" onclick="openLocModal()">✎ Change</button>

          <select class="radius-select" id="radiusSelect" style="display:none" onchange="onRadiusChange()">
            <option value="5">Within 5 km</option>
            <option value="10">Within 10 km</option>
            <option value="15" selected>Within 15 km</option>
            <option value="20">Within 20 km</option>
            <option value="50">Within 50 km</option>
          </select>
        </div>

        <!-- History chips -->
        <div id="historyChips" style="margin-top:14px;display:none">
          <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:6px">Recent searches:</div>
          <div class="history-chips" id="chipsContainer"></div>
        </div>
      </div>

      <!-- ── Results ── -->
      <div id="resultsArea">
        <div class="empty-state" style="padding:60px 20px">
          <div class="empty-icon">🔍</div>
          <h4>Search for a Medicine</h4>
          <p>Enter a medicine name or ingredient above to find availability across pharmacies.</p>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ── Pharmacy Map Modal ── -->
<div class="modal-overlay" id="mapModal">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h3 id="mapModalTitle">📍 Pharmacy Location</h3>
      <button class="modal-close" onclick="Modal.close('mapModal')">×</button>
    </div>
    <div class="modal-body" id="mapModalBody"></div>
  </div>
</div>

<!-- ── Location Pin Modal ── -->
<div class="modal-overlay" id="locMapModal">
  <div class="modal" style="max-width:680px">
    <div class="modal-header">
      <h3>📍 Set Your Location</h3>
      <button class="modal-close" onclick="closeLocModal()">×</button>
    </div>
    <div class="modal-body">
      <div class="map-pin-hint">
        🖱️ <span>Click anywhere on the map to drop your pin. Drag the pin to adjust.</span>
      </div>
      <div class="map-search-bar">
        <input type="text" id="mapSearchInput" placeholder="Search for your village, town, or area…"
               onkeydown="if(event.key==='Enter') searchMapLocation()">
        <button class="btn btn-primary" onclick="searchMapLocation()">Go</button>
      </div>
      <div id="locMapEl"></div>
      <div style="margin-top:12px;font-size:.82rem;color:var(--text-muted)" id="pinCoordDisplay">
        No pin dropped yet.
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeLocModal()">Cancel</button>
      <button class="btn btn-danger" id="clearLocBtn" onclick="clearSavedLocation()" style="display:none">🗑 Remove Location</button>
      <button class="btn btn-primary" id="confirmLocBtn" onclick="confirmLocation()" disabled>✓ Use This Location</button>
    </div>
  </div>
</div>

<!-- ── Report Medicine Modal ── -->
<div class="modal-overlay" id="reportModal">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <h3>🚩 Report Medicine</h3>
      <button class="modal-close" onclick="Modal.close('reportModal')">×</button>
    </div>
    <div class="modal-body">
      <div id="reportAlert"></div>
      <div style="margin-bottom:16px;padding:12px;background:var(--clr-primary-dim);border-radius:8px">
        <div style="font-weight:600;font-size:.9rem;color:var(--clr-primary)" id="reportMedName"></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">This report will be reviewed by an admin.</div>
      </div>
      <input type="hidden" id="reportMedId">
      <div class="form-group">
        <label>Reason <span class="required">*</span></label>
        <select class="form-control" id="reportReason">
          <option value="">Select a reason…</option>
          <option value="wrong_composition">🧪 Wrong Composition</option>
          <option value="fake_medicine">❌ Fake / Non-existent Medicine</option>
          <option value="incorrect_information">📋 Incorrect Information</option>
          <option value="duplicate_medicine">🔀 Duplicate Medicine Entry</option>
          <option value="other">💬 Other</option>
        </select>
      </div>
      <div class="form-group">
        <label>Additional Details</label>
        <textarea class="form-control" id="reportDetails" rows="3"
                  placeholder="Describe the issue in more detail (optional)…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('reportModal')">Cancel</button>
      <button class="btn btn-danger" id="reportSubmitBtn" onclick="submitReport()">Submit Report</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
// ── Sidebar ────────────────────────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

// ── Location state ─────────────────────────────────────────────────────────
let userLat    = null;
let userLng    = null;
let locEnabled = false;

// Leaflet map objects
let locMap       = null;
let locMarker    = null;
let pendingLat   = null;
let pendingLng   = null;
let pendingLabel = '';

// ── Load saved location on page load ──────────────────────────────────────
async function loadSavedLocation() {
  const res = await API.get('../api/location.php?action=load');
  if (res.success && res.has_location) {
    userLat    = res.lat;
    userLng    = res.lng;
    locEnabled = true;
    applyLocationUI(res.label || `${res.lat.toFixed(4)}, ${res.lng.toFixed(4)}`);
  }
}

function applyLocationUI(label) {
  const radius = parseInt(document.getElementById('radiusSelect').value);
  setLocBadge('active', '📍', `Within ${radius} km`);
  document.getElementById('radiusSelect').style.display  = '';
  document.getElementById('savedLocLabel').style.display = '';
  document.getElementById('savedLocLabel').textContent   = '📌 ' + label;
  document.getElementById('changeLocBtn').style.display  = '';
}

function toggleLocation() {
  if (locEnabled) {
    // Already set — clicking badge disables it
    userLat    = null;
    userLng    = null;
    locEnabled = false;
    setLocBadge('inactive', '📍', 'Set My Location');
    document.getElementById('radiusSelect').style.display  = 'none';
    document.getElementById('savedLocLabel').style.display = 'none';
    document.getElementById('changeLocBtn').style.display  = 'none';
    Toast.info('Location filter disabled.');
    return;
  }
  openLocModal();
}

function onRadiusChange() {
  if (!locEnabled) return;
  const radius = parseInt(document.getElementById('radiusSelect').value);
  setLocBadge('active', '📍', `Within ${radius} km`);
  const q = document.getElementById('searchInput').value.trim();
  if (q.length >= 2) doSearch();
}

function setLocBadge(state, icon, text) {
  const badge = document.getElementById('locBadge');
  badge.className = `loc-badge ${state}`;
  document.getElementById('locIcon').textContent = icon;
  document.getElementById('locText').textContent = text;
}

// ── Map modal ──────────────────────────────────────────────────────────────
function openLocModal() {
  document.getElementById('locMapModal').classList.add('open');
  document.getElementById('confirmLocBtn').disabled = true;
  document.getElementById('clearLocBtn').style.display = locEnabled ? '' : 'none';

  // Init map once
  setTimeout(() => {
    if (!locMap) {
      // Default center: Karnataka, India
      const startLat = userLat || 13.0827;
      const startLng = userLng || 77.5877;

      locMap = L.map('locMapEl').setView([startLat, startLng], userLat ? 13 : 7);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
      }).addTo(locMap);

      locMap.on('click', onMapClick);
    } else {
      locMap.invalidateSize();
    }

    // If location already saved, show existing pin
    if (userLat && userLng) {
      dropPin(userLat, userLng);
      locMap.setView([userLat, userLng], 13);
    }
  }, 100);
}

function closeLocModal() {
  document.getElementById('locMapModal').classList.remove('open');
  pendingLat = null; pendingLng = null;
}

function onMapClick(e) {
  dropPin(e.latlng.lat, e.latlng.lng);
}

function dropPin(lat, lng) {
  pendingLat = lat;
  pendingLng = lng;

  if (locMarker) {
    locMarker.setLatLng([lat, lng]);
  } else {
    locMarker = L.marker([lat, lng], { draggable: true }).addTo(locMap);
    locMarker.on('dragend', e => {
      const pos = e.target.getLatLng();
      dropPin(pos.lat, pos.lng);
    });
  }

  document.getElementById('pinCoordDisplay').textContent =
    `📍 Pin: ${lat.toFixed(5)}, ${lng.toFixed(5)} — Click "Use This Location" to confirm.`;
  document.getElementById('confirmLocBtn').disabled = false;

  // Reverse geocode via Nominatim for a human-readable label
  fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
    .then(r => r.json())
    .then(data => {
      const addr   = data.address || {};
      const parts  = [
        addr.village || addr.suburb || addr.neighbourhood || addr.town || addr.city_district || '',
        addr.county  || addr.state_district || '',
        addr.state   || ''
      ].filter(Boolean);
      pendingLabel = parts.slice(0, 2).join(', ') || data.display_name?.split(',')[0] || '';
      document.getElementById('pinCoordDisplay').textContent =
        `📍 ${pendingLabel || 'Pin dropped'} (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
    })
    .catch(() => {
      pendingLabel = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
    });
}

async function searchMapLocation() {
  const q = document.getElementById('mapSearchInput').value.trim();
  if (!q) return;
  try {
    const res = await fetch(
      `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=1&countrycodes=in`
    );
    const data = await res.json();
    if (data.length === 0) { Toast.error('Place not found. Try a different name.'); return; }
    const lat = parseFloat(data[0].lat);
    const lng = parseFloat(data[0].lon);
    locMap.setView([lat, lng], 14);
    dropPin(lat, lng);
  } catch {
    Toast.error('Search failed. Check your internet connection.');
  }
}

async function confirmLocation() {
  if (pendingLat === null) return;
  const btn = document.getElementById('confirmLocBtn');
  btn.disabled    = true;
  btn.textContent = 'Saving…';

  const res = await API.post('../api/location.php?action=save', {
    lat:   pendingLat,
    lng:   pendingLng,
    label: pendingLabel,
  });

  btn.textContent = '✓ Use This Location';
  if (res.success) {
    userLat    = pendingLat;
    userLng    = pendingLng;
    locEnabled = true;
    closeLocModal();
    applyLocationUI(pendingLabel || `${pendingLat.toFixed(4)}, ${pendingLng.toFixed(4)}`);
    Toast.success('Location saved! Pharmacies near you will appear first.');
    const q = document.getElementById('searchInput').value.trim();
    if (q.length >= 2) doSearch();
  } else {
    Toast.error(res.message || 'Failed to save location.');
    btn.disabled = false;
  }
}

async function clearSavedLocation() {
  const res = await API.post('../api/location.php?action=save', { lat: null, lng: null, label: '' });
  if (res.success) {
    userLat = null; userLng = null; locEnabled = false;
    locMarker = null;
    if (locMap) { locMap.remove(); locMap = null; }
    closeLocModal();
    setLocBadge('inactive', '📍', 'Set My Location');
    document.getElementById('radiusSelect').style.display  = 'none';
    document.getElementById('savedLocLabel').style.display = 'none';
    document.getElementById('changeLocBtn').style.display  = 'none';
    Toast.info('Location removed.');
  }
}

// ── History chips ──────────────────────────────────────────────────────────
async function loadHistoryChips() {
  const res = await API.get('../api/search.php?action=history');
  if (!res.success || !res.history.length) return;
  const container = document.getElementById('chipsContainer');
  container.innerHTML = res.history.map(h =>
    `<div class="history-chip" onclick="setSearch('${h.search_term.replace(/'/g,"\\'")}')">
      🕐 ${h.search_term}
    </div>`
  ).join('');
  document.getElementById('historyChips').style.display = 'block';
}

function setSearch(term) {
  document.getElementById('searchInput').value = term;
  doSearch();
}

function clearSearch() {
  document.getElementById('searchInput').value = '';
  document.getElementById('resultsArea').innerHTML = `
    <div class="empty-state" style="padding:60px 20px">
      <div class="empty-icon">🔍</div>
      <h4>Search for a Medicine</h4>
      <p>Enter a medicine name or ingredient above to find availability across pharmacies.</p>
    </div>`;
  loadHistoryChips();
}

// ── Main search ────────────────────────────────────────────────────────────
async function doSearch() {
  const q = document.getElementById('searchInput').value.trim();
  if (!q || q.length < 2) { Toast.warning('Enter at least 2 characters to search.'); return; }

  const btn = document.getElementById('searchBtn');
  Form.setLoading(btn, true, 'Searching…');

  let url = `../api/search.php?action=search&q=${encodeURIComponent(q)}`;
  if (locEnabled && userLat !== null && userLng !== null) {
    const radius = parseInt(document.getElementById('radiusSelect').value);
    url += `&lat=${userLat}&lng=${userLng}&radius=${radius}`;
  }

  const res = await API.get(url);
  Form.setLoading(btn, false);

  history.pushState({}, '', `?q=${encodeURIComponent(q)}`);
  loadHistoryChips();

  if (!res.success) { Toast.error(res.message || 'Search failed.'); return; }
  renderResults(res);
}

// ── Render results ─────────────────────────────────────────────────────────
function renderResults(res) {
  const area = document.getElementById('resultsArea');
  const { mode, results, alternatives, query, location_used, radius_km } = res;

  if (!results || !results.length) {
    area.innerHTML = `
      <div class="empty-state" style="padding:60px 20px">
        <div class="empty-icon">😔</div>
        <h4>No medicines found</h4>
        <p>No results for "<strong>${query}</strong>". Try a different name or composition.</p>
      </div>`;
    return;
  }

  let html = '';

  // Location notice
  if (location_used) {
    const radius = parseInt(document.getElementById('radiusSelect').value);
    html += `<div class="radius-notice">
      📍 Showing pharmacies within <strong>${radius} km</strong> of your location first.
      Pharmacies outside the range are shown below (dimmed).
    </div>`;
  }

  // Mode banner
  const banners = {
    exact:        `<div class="mode-banner mode-exact">✅ Found <strong>${results.length}</strong> medicine(s) matching "<strong>${query}</strong>" — available in stock.</div>`,
    alternatives: `<div class="mode-banner mode-alt">⚠️ "<strong>${query}</strong>" found but out of stock everywhere. Showing alternatives below.</div>`,
    partial:      `<div class="mode-banner mode-part">🔍 Showing partial matches for "<strong>${query}</strong>".</div>`,
  };
  html += banners[mode] || '';

  // Main results
  if (mode === 'alternatives') {
    html += '<div style="margin-bottom:16px"><div style="font-weight:600;font-size:.95rem;color:var(--clr-danger);margin-bottom:12px">🔴 Searched Medicine — Out of Stock</div>';
  } else {
    html += `<div style="margin-bottom:8px;font-weight:600;font-size:.95rem">💊 Results for "${query}"</div>`;
  }
  html += results.map(e => renderMedicineCard(e, mode === 'alternatives', location_used, radius_km)).join('');
  html += '</div>';

  // Alternatives
  if (alternatives && alternatives.length) {
    html += `<div class="alt-divider"><span>🔄 ${alternatives.length} Alternative(s) — Same Composition, In Stock</span></div>`;
    html += alternatives.map(e => renderMedicineCard(e, false, location_used, radius_km)).join('');
  } else if (mode === 'alternatives') {
    html += `<div class="alt-divider"><span>🔄 Alternatives</span></div>
      <div class="empty-state" style="padding:30px 20px">
        <div class="empty-icon">😔</div>
        <h4>No alternatives available</h4>
        <p>No medicines with the same composition found in stock at this time.</p>
      </div>`;
  }

  area.innerHTML = `<div class="fade-in">${html}</div>`;
}

function renderMedicineCard(entry, outOfStock, locationUsed, radiusKm) {
  const m = entry.medicine;
  const pharmacies = entry.pharmacies || [];
  const totalQty   = entry.total_qty  || 0;

  const availBadge = outOfStock
    ? '<span class="badge badge-danger">Out of Stock</span>'
    : totalQty > 0
      ? `<span class="badge badge-success">✓ Available (${totalQty} units)</span>`
      : '<span class="badge badge-danger">Out of Stock</span>';

  let pharmacyHtml = '';
  if (!outOfStock && pharmacies.length > 0) {
    // Separate in-range and out-of-range
    const inRange  = pharmacies.filter(p => p.within_range === true  || p.within_range === null);
    const outRange = pharmacies.filter(p => p.within_range === false);

    const renderPharm = (p, isOutOfRange) => {
      const qtyClass = p.quantity >= 50 ? 'high' : p.quantity >= 10 ? 'mid' : 'low';
      const mapBtn   = p.latitude && p.longitude
        ? `<button class="btn btn-ghost btn-sm" onclick="showMap(${p.id},'${esc(p.pharmacy_name)}',${p.latitude},${p.longitude},'${esc(p.address)}')">📍 Map</button>`
        : '';

      let distBadge = '';
      if (locationUsed && p.distance_km !== null) {
        const d    = p.distance_km;
        const cls  = isOutOfRange ? 'dist-outside' : (d <= 5 ? 'dist-near' : d <= 10 ? 'dist-mid' : 'dist-far');
        distBadge  = `<span class="dist-badge ${cls}">📍 ${d} km</span>`;
      } else if (locationUsed && p.within_range === null) {
        distBadge = `<span class="dist-badge dist-unknown">📍 No GPS</span>`;
      }

      const radius = radiusKm || parseInt(document.getElementById('radiusSelect')?.value || 15);
      return `
        <div class="pharmacy-item ${isOutOfRange ? 'out-of-range' : ''}" ${isOutOfRange ? `data-radius="${radius}"` : ''}>
          <div class="pharmacy-item-left">
            <h5>🏥 ${p.pharmacy_name}</h5>
            <p>📍 ${p.address}, ${p.city} &bull; 📞 ${p.phone}</p>
            ${p.updated_at ? `<p style="font-size:.72rem;margin-top:2px">Updated: ${timeAgo(p.updated_at)}</p>` : ''}
          </div>
          <div class="pharmacy-item-right">
            ${distBadge}
            ${p.price ? `<span class="price-tag">₹${parseFloat(p.price).toFixed(2)}</span>` : ''}
            <span class="qty-badge ${qtyClass}">${p.quantity} units</span>
            ${mapBtn}
          </div>
        </div>`;
    };

    const allRendered = [
      ...inRange.map(p => renderPharm(p, false)),
      ...(locationUsed && outRange.length > 0
        ? [`<div style="margin:10px 0 4px;font-size:.75rem;color:var(--text-muted);font-weight:600">Outside your range (${outRange.length} pharmacies):</div>`,
           ...outRange.map(p => renderPharm(p, true))]
        : []),
    ];

    pharmacyHtml = `<div class="pharmacy-list">${allRendered.join('')}</div>`;
  } else if (!outOfStock) {
    pharmacyHtml = '<div style="padding:12px;text-align:center;color:var(--text-muted);font-size:.85rem">No pharmacy currently has this in stock.</div>';
  }

  return `
    <div class="medicine-card">
      <div class="medicine-card-header">
        <div class="medicine-card-title">
          <h4>${m.name}</h4>
          <p>🧪 ${m.composition} &bull; 💊 ${m.dosage}</p>
          <p style="margin-top:2px">🏭 ${m.manufacturer} &bull; <span class="badge badge-primary" style="font-size:.72rem">${m.category}</span></p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
          ${availBadge}
          <button class="btn btn-ghost btn-sm" style="font-size:.72rem;color:var(--clr-danger);padding:3px 8px"
            onclick="openReport(${m.id},'${esc(m.name)}')">🚩 Report</button>
        </div>
      </div>
      ${pharmacyHtml}
    </div>`;
}

function esc(s) { return (s||'').replace(/'/g,"\\'"); }

// ── Map modal ──────────────────────────────────────────────────────────────
function showMap(id, name, lat, lng, address) {
  document.getElementById('mapModalTitle').textContent = `📍 ${name}`;
  document.getElementById('mapModalBody').innerHTML = `
    <div style="margin-bottom:12px">
      <strong>${name}</strong><br>
      <span style="font-size:.875rem;color:var(--text-muted)">${address}</span>
    </div>
    <iframe width="100%" height="300"
      style="border:0;border-radius:var(--border-radius-sm)"
      loading="lazy"
      src="https://www.google.com/maps?q=${lat},${lng}&z=15&output=embed">
    </iframe>
    <div style="margin-top:12px;display:flex;gap:10px">
      <a href="https://maps.google.com/?q=${lat},${lng}" target="_blank" class="btn btn-primary btn-sm">Open in Google Maps →</a>
      <a href="https://maps.google.com/maps/dir/?api=1&destination=${lat},${lng}" target="_blank" class="btn btn-outline btn-sm">Get Directions →</a>
    </div>`;
  Modal.open('mapModal');
}

// ── Report modal ───────────────────────────────────────────────────────────
function openReport(medicineId, medicineName) {
  document.getElementById('reportMedId').value            = medicineId;
  document.getElementById('reportMedName').textContent    = medicineName;
  document.getElementById('reportReason').value           = '';
  document.getElementById('reportDetails').value          = '';
  document.getElementById('reportAlert').innerHTML        = '';
  Modal.open('reportModal');
}

async function submitReport() {
  const btn      = document.getElementById('reportSubmitBtn');
  const alertEl  = document.getElementById('reportAlert');
  alertEl.innerHTML = '';
  const medicineId = parseInt(document.getElementById('reportMedId').value);
  const reason     = document.getElementById('reportReason').value;
  const details    = document.getElementById('reportDetails').value.trim();

  if (!reason) {
    alertEl.innerHTML = `<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">Please select a reason.</div></div></div>`;
    return;
  }

  Form.setLoading(btn, true, 'Submitting…');
  const res = await API.post('../api/search.php?action=report', { medicine_id: medicineId, reason, details });
  Form.setLoading(btn, false);

  if (res.success) {
    alertEl.innerHTML = `<div class="alert alert-success"><span class="alert-icon">✓</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
    setTimeout(() => Modal.close('reportModal'), 2000);
  } else {
    alertEl.innerHTML = `<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
}

// ── Init ───────────────────────────────────────────────────────────────────
loadHistoryChips();
loadSavedLocation();
<?php if ($initQuery): ?>
window.addEventListener('load', () => doSearch());
<?php endif; ?>
</script>
</body>
</html>
