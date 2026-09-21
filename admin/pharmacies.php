<?php
require_once '../includes/functions.php';
requireLogin('admin', 'login.php');
$user = getCurrentUser();
$filterStatus = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pharmacies — Admin | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Pharmacy Management</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <div><div class="user-name"><?= htmlspecialchars($user['name']) ?></div><div class="user-role">Administrator</div></div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="page-header">
        <h2>🏥 Pharmacies</h2>
      </div>

      <!-- Filter Tabs -->
      <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
        <?php foreach([''=>'All','approved'=>'Approved','pending'=>'Pending','rejected'=>'Rejected','inactive'=>'Inactive'] as $k=>$label): ?>
          <button class="btn <?= $filterStatus===$k ? 'btn-primary' : 'btn-ghost' ?> btn-sm"
                  onclick="filterBy('<?= $k ?>')">
            <?php if($k==='pending') echo '⏳ '; ?>
            <?= $label ?>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Pharmacy Name</th>
                <th>Owner</th>
                <th>City / State</th>
                <th>Phone</th>
                <th>License</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="pharmTableBody">
              <tr><td colspan="9" class="text-center" style="padding:40px"><div class="spinner spinner-dark"></div></td></tr>
            </tbody>
          </table>
        </div>
        <div class="card-footer flex-between">
          <span id="tableInfo" style="font-size:0.82rem;color:var(--text-muted)"></span>
          <div class="pagination" id="pagination"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="detailModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <h3>🏥 Pharmacy Details</h3>
      <button class="modal-close" onclick="Modal.close('detailModal')">×</button>
    </div>
    <div class="modal-body" id="detailBody">Loading...</div>
    <div class="modal-footer" id="detailFooter"></div>
  </div>
</div>

<div id="toast-container"></div>

<script src="../assets/js/main.js"></script>
<script>
let currentPage = 1;
let currentFilter = '<?= $filterStatus ?>';

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

function filterBy(status) {
  currentFilter = status;
  currentPage = 1;
  // Update button styles
  document.querySelectorAll('.page-content .btn').forEach(btn => {
    const isActive = btn.textContent.trim().replace('⏳ ','').toLowerCase() === (status === '' ? 'all' : status);
    btn.className = `btn ${isActive ? 'btn-primary' : 'btn-ghost'} btn-sm`;
  });
  loadPharmacies();
}

const statusBadge = {
  approved: '<span class="badge badge-success">✓ Approved</span>',
  pending:  '<span class="badge badge-warning">⏳ Pending</span>',
  rejected: '<span class="badge badge-danger">✕ Rejected</span>',
  inactive: '<span class="badge badge-neutral">Inactive</span>'
};

async function loadPharmacies() {
  const url = `../api/admin.php?action=pharmacies&page=${currentPage}` + (currentFilter ? `&status=${currentFilter}` : '');
  const res = await API.get(url);
  if (!res.success) return;
  const { pharmacies, total, pages } = res;
  const tbody = document.getElementById('pharmTableBody');

  if (!pharmacies.length) {
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><div class="empty-icon">🏥</div><h4>No pharmacies found</h4><p>No pharmacies match the selected filter.</p></div></td></tr>`;
    document.getElementById('tableInfo').textContent = '';
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  tbody.innerHTML = pharmacies.map((p, i) => `
    <tr>
      <td style="color:var(--text-muted)">${(currentPage-1)*10 + i + 1}</td>
      <td><strong>${p.pharmacy_name}</strong></td>
      <td>${p.owner_name}</td>
      <td>${p.city}, ${p.state}</td>
      <td style="font-size:0.82rem">${p.phone}</td>
      <td style="font-size:0.78rem;color:var(--text-muted)">${p.license_number}</td>
      <td>${statusBadge[p.status] || p.status}</td>
      <td style="font-size:0.8rem;color:var(--text-muted)">${new Date(p.created_at).toLocaleDateString()}</td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn btn-ghost btn-sm" onclick="viewPharmacy(${p.id})">👁 View</button>
          ${p.status === 'pending' ? `
            <button class="btn btn-primary btn-sm" onclick="changeStatus(${p.id},'approved','${p.pharmacy_name}')">✓ Approve</button>
            <button class="btn btn-danger btn-sm" onclick="changeStatus(${p.id},'rejected','${p.pharmacy_name}')">✕ Reject</button>` : ''}
          ${p.status === 'approved' ? `<button class="btn btn-ghost btn-sm" onclick="changeStatus(${p.id},'inactive','${p.pharmacy_name}')">⏸ Deactivate</button>` : ''}
          ${p.status === 'inactive' ? `<button class="btn btn-outline btn-sm" onclick="changeStatus(${p.id},'approved','${p.pharmacy_name}')">▶ Activate</button>` : ''}
        </div>
      </td>
    </tr>`).join('');

  document.getElementById('tableInfo').textContent = `Showing ${(currentPage-1)*10+1}–${Math.min(currentPage*10, total)} of ${total} pharmacies`;
  renderPagination(document.getElementById('pagination'), currentPage, pages, p => { currentPage = p; loadPharmacies(); });
}

async function viewPharmacy(id) {
  const res = await API.get(`../api/admin.php?action=pharmacy_detail&id=${id}`);
  if (!res.success) { Toast.error('Failed to load details.'); return; }
  const p = res.pharmacy;

  const mapLink = p.latitude && p.longitude
    ? `<a href="https://maps.google.com/?q=${p.latitude},${p.longitude}" target="_blank" class="btn btn-outline btn-sm">📍 View on Map</a>` : '';

  document.getElementById('detailBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div><div class="text-muted text-sm">Pharmacy Name</div><div class="fw-semibold">${p.pharmacy_name}</div></div>
      <div><div class="text-muted text-sm">Owner / Manager</div><div>${p.owner_name}</div></div>
      <div><div class="text-muted text-sm">Email</div><div style="font-size:0.875rem">${p.email}</div></div>
      <div><div class="text-muted text-sm">Phone</div><div>${p.phone}</div></div>
      <div style="grid-column:1/-1"><div class="text-muted text-sm">Address</div><div>${p.address}, ${p.city}, ${p.state} — ${p.pincode}</div></div>
      <div><div class="text-muted text-sm">License Number</div><div style="font-size:0.82rem">${p.license_number}</div></div>
      <div><div class="text-muted text-sm">Status</div><div>${statusBadge[p.status]}</div></div>
      <div><div class="text-muted text-sm">Registered On</div><div style="font-size:0.875rem">${new Date(p.created_at).toLocaleDateString()}</div></div>
      ${p.latitude ? `<div><div class="text-muted text-sm">Coordinates</div><div style="font-size:0.82rem">${p.latitude}, ${p.longitude}</div></div>` : ''}
    </div>`;

  document.getElementById('detailFooter').innerHTML = `
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      ${mapLink}
      ${p.status==='pending' ? `<button class="btn btn-primary" onclick="changeStatus(${p.id},'approved','${p.pharmacy_name}',true)">✓ Approve</button><button class="btn btn-danger" onclick="changeStatus(${p.id},'rejected','${p.pharmacy_name}',true)">✕ Reject</button>` : ''}
      ${p.status==='approved' ? `<button class="btn btn-ghost" onclick="changeStatus(${p.id},'inactive','${p.pharmacy_name}',true)">⏸ Deactivate</button>` : ''}
      ${p.status==='inactive' ? `<button class="btn btn-outline" onclick="changeStatus(${p.id},'approved','${p.pharmacy_name}',true)">▶ Activate</button>` : ''}
      <button class="btn btn-ghost" onclick="Modal.close('detailModal')">Close</button>
    </div>`;

  Modal.open('detailModal');
}

async function changeStatus(id, action, name, fromModal=false) {
  const labels = {approved:'approve', rejected:'reject', inactive:'deactivate', pending:'reset to pending'};
  confirmAction(`${labels[action]?.charAt(0).toUpperCase()+labels[action]?.slice(1)} <strong>${name}</strong>?`, async () => {
    const res = await API.post('../api/admin.php?action=pharmacy_action', { id, action });
    if (res.success) {
      Toast.success(res.message);
      if (fromModal) Modal.close('detailModal');
      loadPharmacies();
    } else Toast.error(res.message);
  });
}

loadPharmacies();
</script>
</body>
</html>
