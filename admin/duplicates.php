<?php
require_once '../includes/functions.php';
requireLogin('admin', 'login.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Duplicate Medicines — Admin | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .dup-group {
      background: var(--bg-card);
      border: 1.5px solid var(--border-color);
      border-radius: var(--border-radius);
      margin-bottom: 20px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }
    .dup-group:hover { box-shadow: var(--shadow-md); }
    .dup-group-header {
      background: var(--clr-warning-lt);
      padding: 14px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border-color);
      flex-wrap: wrap;
      gap: 10px;
    }
    .dup-group-header h4 { font-size:.92rem; font-weight:700; color:var(--clr-warning); }
    .dup-group-header p  { font-size:.78rem; color:var(--text-muted); margin:0; }
    .dup-member {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 20px;
      border-bottom: 1px solid var(--border-color);
      flex-wrap: wrap;
    }
    .dup-member:last-child { border-bottom: none; }
    .dup-member-check { flex-shrink:0; }
    .dup-member-info  { flex:1; min-width:200px; }
    .dup-member-info h5 { font-size:.88rem; font-weight:700; margin-bottom:2px; }
    .dup-member-info p  { font-size:.76rem; color:var(--text-muted); margin:0; }
    .master-badge {
      background: var(--clr-primary-dim);
      color: var(--clr-primary);
      border: 1px solid rgba(10,110,79,.2);
      border-radius: 20px;
      font-size:.72rem; font-weight:700;
      padding: 3px 10px;
    }
    .empty-duplicates {
      text-align: center;
      padding: 80px 20px;
    }
    .radio-master {
      accent-color: var(--clr-primary);
      width:16px; height:16px; cursor:pointer;
    }
    .merge-btn-row {
      padding: 14px 20px;
      background: var(--bg-page);
      border-top: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .merge-hint { font-size:.78rem; color:var(--text-muted); }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Duplicate Medicine Detection</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="user-role">Administrator</div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="page-header">
        <h2>🔀 Duplicate Medicine Detection</h2>
        <button class="btn btn-outline" onclick="loadDuplicates()">🔄 Refresh</button>
      </div>

      <!-- Info banner -->
      <div class="alert alert-info mb">
        <span class="alert-icon">ℹ️</span>
        <div class="alert-body">
          <div class="alert-title">How Duplicate Detection Works</div>
          <div class="alert-msg">
            Names are normalized (lowercase, special characters removed) and grouped. Examples:
            <strong>Dolo 650</strong>, <strong>dolo-650</strong>, <strong>DOLO650</strong> are detected as the same medicine.
            Select a <strong>Master</strong> (the one to keep), then merge duplicates into it — stock quantities are combined.
          </div>
        </div>
      </div>

      <div id="dupContent">
        <div style="text-align:center;padding:60px">
          <div class="spinner spinner-dark" style="width:36px;height:36px;border-width:3px"></div>
          <p style="margin-top:14px;color:var(--text-muted)">Scanning for duplicates…</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Merge Confirm Modal -->
<div class="modal-overlay" id="mergeModal">
  <div class="modal" style="max-width:500px">
    <div class="modal-header">
      <h3>🔀 Confirm Merge</h3>
      <button class="modal-close" onclick="Modal.close('mergeModal')">×</button>
    </div>
    <div class="modal-body" id="mergeModalBody"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('mergeModal')">Cancel</button>
      <button class="btn btn-primary" id="mergeConfirmBtn">Merge Now</button>
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

let allGroups = [];

async function loadDuplicates() {
  document.getElementById('dupContent').innerHTML = `
    <div style="text-align:center;padding:60px">
      <div class="spinner spinner-dark" style="width:36px;height:36px;border-width:3px"></div>
      <p style="margin-top:14px;color:var(--text-muted)">Scanning for duplicates…</p>
    </div>`;

  const res = await API.get('../api/admin.php?action=duplicates');
  if (!res.success) { Toast.error('Failed to load duplicates.'); return; }

  allGroups = res.groups;

  if (!allGroups.length) {
    document.getElementById('dupContent').innerHTML = `
      <div class="empty-duplicates">
        <div style="font-size:4rem;margin-bottom:16px">✅</div>
        <h3 style="font-family:var(--font-body);font-size:1.2rem;margin-bottom:8px">No Duplicates Found</h3>
        <p>All medicines in the system have unique normalized names.</p>
      </div>`;
    return;
  }

  document.getElementById('dupContent').innerHTML =
    `<div style="margin-bottom:16px;font-size:.85rem;color:var(--text-muted)">
      Found <strong>${allGroups.length}</strong> duplicate group(s). Select a master for each group before merging.
    </div>` +
    allGroups.map((group, gi) => renderGroup(group, gi)).join('');
}

function renderGroup(group, gi) {
  const members = group.members;
  return `
    <div class="dup-group" id="group-${gi}">
      <div class="dup-group-header">
        <div>
          <h4>🔀 Possible Duplicates — "${group.norm}"</h4>
          <p>${group.count} medicines with the same normalized name</p>
        </div>
        <span class="badge badge-warning">${group.count} duplicates</span>
      </div>

      ${members.map((m, mi) => `
        <div class="dup-member" id="member-${gi}-${mi}">
          <div class="dup-member-check">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.78rem;font-weight:600;color:var(--clr-primary)">
              <input type="radio" class="radio-master" name="master-${gi}" value="${m.id}"
                     onchange="markMaster(${gi}, ${m.id})" ${mi === 0 ? 'checked' : ''}>
              Master
            </label>
          </div>
          <div class="dup-member-info">
            <h5>${m.name} ${mi === 0 ? '<span class="master-badge">★ Master</span>' : ''}</h5>
            <p>
              ${m.composition}
              &bull; ${m.manufacturer}
              ${m.pharmacy_name ? `&bull; Added by: <strong>${m.pharmacy_name}</strong>` : '&bull; System entry'}
            </p>
          </div>
          <div style="flex-shrink:0;text-align:right">
            <div class="badge ${m.total_stock > 0 ? 'badge-success' : 'badge-neutral'}">
              ${m.total_stock ?? 0} units
            </div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:4px">ID #${m.id}</div>
          </div>
        </div>`).join('')}

      <div class="merge-btn-row">
        <button class="btn btn-primary btn-sm" onclick="initMerge(${gi})">
          🔀 Merge Duplicates into Master
        </button>
        <span class="merge-hint">
          Stock quantities will be combined. Duplicate entries will be removed.
        </span>
      </div>
    </div>`;
}

function markMaster(gi, masterId) {
  const group = allGroups[gi];
  // Re-render master badge labels
  group.members.forEach((m, mi) => {
    const nameEl = document.querySelector(`#member-${gi}-${mi} h5`);
    if (!nameEl) return;
    // Remove existing master badge
    nameEl.querySelectorAll('.master-badge').forEach(b => b.remove());
    if (m.id === masterId) {
      const badge = document.createElement('span');
      badge.className = 'master-badge';
      badge.textContent = '★ Master';
      nameEl.appendChild(badge);
    }
  });
}

function initMerge(gi) {
  const group = allGroups[gi];
  const masterId = parseInt(document.querySelector(`input[name="master-${gi}"]:checked`)?.value);
  if (!masterId) { Toast.warning('Please select a master medicine.'); return; }

  const master     = group.members.find(m => m.id === masterId);
  const duplicates = group.members.filter(m => m.id !== masterId);

  if (!duplicates.length) { Toast.info('Nothing to merge — only one medicine in this group.'); return; }

  const totalStock = group.members.reduce((s, m) => s + (parseInt(m.total_stock) || 0), 0);

  document.getElementById('mergeModalBody').innerHTML = `
    <div style="margin-bottom:16px">
      <div style="font-weight:700;font-size:.95rem;color:var(--clr-primary);margin-bottom:4px">
        ★ Master: ${master.name}
      </div>
      <div style="font-size:.82rem;color:var(--text-muted)">${master.composition} &bull; ${master.manufacturer}</div>
    </div>

    <div class="alert alert-warning" style="margin-bottom:16px">
      <span class="alert-icon">⚠️</span>
      <div class="alert-body">
        <div class="alert-title">The following will be merged and deleted:</div>
        <div class="alert-msg">
          <ul style="margin-top:6px;padding-left:16px">
            ${duplicates.map(d => `<li><strong>${d.name}</strong> (ID #${d.id}, ${d.total_stock ?? 0} units stock)</li>`).join('')}
          </ul>
        </div>
      </div>
    </div>

    <div style="background:var(--clr-success-lt);border-radius:8px;padding:12px 14px;font-size:.82rem;color:var(--clr-success)">
      ✅ After merge: Master "<strong>${master.name}</strong>" will have <strong>${totalStock} total units</strong> across all pharmacies.
    </div>

    <div style="margin-top:14px;font-size:.78rem;color:var(--text-muted)">
      This action uses a database transaction and cannot be undone. A merge log will be recorded.
    </div>`;

  // Store merge data on confirm button
  document.getElementById('mergeConfirmBtn').dataset.masterId   = masterId;
  document.getElementById('mergeConfirmBtn').dataset.mergeIds   = duplicates.map(d => d.id).join(',');
  document.getElementById('mergeConfirmBtn').dataset.groupIndex = gi;

  Modal.open('mergeModal');
}

document.getElementById('mergeConfirmBtn').addEventListener('click', async () => {
  const btn       = document.getElementById('mergeConfirmBtn');
  const masterId  = parseInt(btn.dataset.masterId);
  const mergeIds  = btn.dataset.mergeIds.split(',').map(Number);
  const gi        = parseInt(btn.dataset.groupIndex);

  Form.setLoading(btn, true, 'Merging…');
  const res = await API.post('../api/admin.php?action=merge', { master_id: masterId, merge_ids: mergeIds });
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(res.message);
    Modal.close('mergeModal');
    // Remove the merged group from the UI
    const groupEl = document.getElementById(`group-${gi}`);
    if (groupEl) {
      groupEl.style.transition = 'opacity .3s, max-height .4s';
      groupEl.style.opacity = '0';
      setTimeout(() => groupEl.remove(), 350);
    }
    // Reload to reflect remaining groups
    setTimeout(() => loadDuplicates(), 600);
  } else {
    Toast.error(res.message);
  }
});

loadDuplicates();
</script>
</body>
</html>
