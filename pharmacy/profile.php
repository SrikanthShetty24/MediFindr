<?php
require_once '../includes/functions.php';
requireLogin('pharmacy', 'login.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile — Pharmacy | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Pharmacy Profile</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar">🏥</div>
          <div><div class="user-name"><?= htmlspecialchars($user['name']) ?></div><div class="user-role">Pharmacy</div></div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="page-header">
        <h2>🏥 Pharmacy Profile</h2>
      </div>

      <!-- Profile Card -->
      <div class="card mb-lg" style="max-width:700px">
        <div class="profile-banner"></div>
        <div style="padding:50px 28px 24px">
          <div class="profile-avatar">🏥</div>
          <div id="profileInfo" style="margin-top:8px">
            <div class="spinner spinner-dark"></div>
          </div>
        </div>
      </div>

      <!-- Edit Form -->
      <div class="card" style="max-width:700px">
        <div class="card-header"><h3>✏️ Update Information</h3></div>
        <div class="card-body">
          <div id="formAlert"></div>
          <form id="profileForm" novalidate>
            <div class="form-row">
              <div class="form-group">
                <label>Phone <span class="required">*</span></label>
                <input type="tel" class="form-control" name="phone" id="fPhone" placeholder="Contact number">
              </div>
              <div class="form-group">
                <label>Pincode</label>
                <input type="text" class="form-control" name="pincode" id="fPincode" maxlength="6" placeholder="6-digit pincode">
              </div>
            </div>
            <div class="form-group">
              <label>Address <span class="required">*</span></label>
              <textarea class="form-control" name="address" id="fAddress" rows="2" placeholder="Full street address"></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>City <span class="required">*</span></label>
                <input type="text" class="form-control" name="city" id="fCity" placeholder="City name">
              </div>
              <div class="form-group">
                <label>State</label>
                <input type="text" class="form-control" name="state" id="fState" placeholder="State name">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Latitude <span style="font-size:0.75rem;color:var(--text-muted)">(for map)</span></label>
                <input type="number" class="form-control" name="latitude" id="fLat" step="any" placeholder="e.g. 12.9716">
              </div>
              <div class="form-group">
                <label>Longitude <span style="font-size:0.75rem;color:var(--text-muted)">(for map)</span></label>
                <input type="number" class="form-control" name="longitude" id="fLng" step="any" placeholder="e.g. 77.5946">
              </div>
            </div>
            <div class="form-hint mb" style="margin-bottom:12px">
              💡 <strong>Tip:</strong> Find your coordinates at <a href="https://www.latlong.net/" target="_blank">latlong.net</a>. Coordinates help users find your pharmacy on the map.
            </div>
            <button type="submit" class="btn btn-primary" id="saveBtn">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<script src="../assets/js/main.js"></script>
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

async function loadProfile() {
  const res = await API.get('../api/pharmacy.php?action=profile');
  if (!res.success) return;
  const p = res.profile;

  document.getElementById('profileInfo').innerHTML = `
    <h3 style="font-family:var(--font-display);font-size:1.5rem">${p.pharmacy_name}</h3>
    <p style="color:var(--text-muted);margin-top:2px">Owner: ${p.owner_name} &bull; License: ${p.license_number}</p>
    <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap">
      <span class="badge ${p.status==='approved'?'badge-success':p.status==='pending'?'badge-warning':'badge-danger'}">${p.status}</span>
      <span style="font-size:0.82rem;color:var(--text-muted)">📧 ${p.email}</span>
      <span style="font-size:0.82rem;color:var(--text-muted)">📅 Joined ${new Date(p.created_at).toLocaleDateString()}</span>
    </div>`;

  // Populate form
  document.getElementById('fPhone').value   = p.phone || '';
  document.getElementById('fAddress').value = p.address || '';
  document.getElementById('fCity').value    = p.city || '';
  document.getElementById('fState').value   = p.state || '';
  document.getElementById('fPincode').value = p.pincode || '';
  document.getElementById('fLat').value     = p.latitude || '';
  document.getElementById('fLng').value     = p.longitude || '';
}

document.getElementById('profileForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('saveBtn');
  const alertEl = document.getElementById('formAlert');
  alertEl.innerHTML = '';

  const data = Form.getData(e.target);
  if (!data.phone || !data.address || !data.city) {
    alertEl.innerHTML = '<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">Phone, address and city are required.</div></div></div>';
    return;
  }

  Form.setLoading(btn, true, 'Saving...');
  const res = await API.put('../api/pharmacy.php?action=profile', data);
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(res.message);
    alertEl.innerHTML = `<div class="alert alert-success"><span class="alert-icon">✓</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  } else {
    alertEl.innerHTML = `<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
});

loadProfile();
</script>
</body>
</html>
