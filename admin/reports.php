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
  <title>User Reports — Admin | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .report-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 20px;
      margin-bottom: 16px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }
    .report-card:hover { box-shadow: var(--shadow-md); }
    .report-card.pending  { border-left: 4px solid var(--clr-danger); }
    .report-card.resolved { border-left: 4px solid var(--clr-accent); opacity:.75; }
    .report-card.dismissed{ border-left: 4px solid var(--text-muted); opacity:.65; }
    .report-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:12px; }
    .report-medicine h4 { font-size:.95rem; font-weight:700; margin-bottom:3px; }
    .report-medicine p  { font-size:.8rem; color:var(--text-muted); margin:0; }
    .reason-labels {
      wrong_composition: '🧪 Wrong Composition';
      fake_medicine: '❌ Fake Medicine';
      incorrect_information: '📋 Incorrect Info';
      duplicate_medicine: '🔀 Duplicate';
      other: '💬 Other';
    }
    .report-meta { display:flex; align-items:center; gap:16px; flex-wrap:wrap; font-size:.78rem; color:var(--text-muted); margin-top:10px; }
    .report-details { background:var(--bg-page); border-radius:8px; padding:10px 14px; font-size:.82rem; color:var(--text-secondary); margin-top:10px; font-style:italic; }
    .report-actions { display:flex; gap:8px; margin-top:14px; flex-wrap:wrap; }
    .admin-note { background:var(--clr-success-lt); border-radius:8px; padding:8px 12px; font-size:.78rem; color:var(--clr-success); margin-top:8px; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">User Reports</span>
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
        <h2>🚩 User Reports</h2>
      </div>

      <!-- Status filter tabs -->
      <div style="display:flex;gap:6px;margin-bottom:22px;flex-wrap:wrap">
        <button class="btn btn-primary btn-sm" onclick="filterStatus('pending')" id="tab-pending">⏳ Pending</button>
        <button class="btn btn-ghost btn-sm"   onclick="filterStatus('resolved')" id="tab-resolved">✅ Resolved</button>
        <button class="btn btn-ghost btn-sm"   onclick="filterStatus('dismissed')" id="tab-dismissed">🚫 Dismissed</button>
      </div>

      <div id="reportsContent">
        <div style="text-align:center;padding:60px">
          <div class="spinner spinner-dark" style="width:36px;height:36px;border-width:3px"></div>
        </div>
      </div>

      <div class="pagination" id="pagination" style="margin-top:16px"></div>
    </div>
  </div>
</div>

<!-- Resolve / Dismiss Modal -->
<div class="modal-overlay" id="resolveModal">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <h3 id="resolveTitle">✅ Resolve Report</h3>
      <button class="modal-close" onclick="Modal.close('resolveModal')">×</button>
    </div>
    <div class="modal-body">
      <div id="resolveAlert"></div>
      <p id="resolveDesc" style="margin-bottom:14px;color:var(--text-primary)"></p>
      <div class="form-group">
        <label>Admin Note (optional)</label>
        <textarea class="form-control" id="resolveNote" rows="3" placeholder="Leave a note explaining the action…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('resolveModal')">Cancel</button>
      <button class="btn btn-primary" id="resolveConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
let currentStatus = 'pending';
let currentPage   = 1;
let pendingResolve = null;

const reasonLabels = {
  wrong_composition:     '🧪 Wrong Composition',
  fake_medicine:         '❌ Fake Medicine',
  incorrect_information: '📋 Incorrect Information',
  duplicate_medicine:    '🔀 Duplicate Medicine',
  other:                 '💬 Other'
};

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

function filterStatus(status) {
  currentStatus = status;
  currentPage   = 1;
  ['pending','resolved','dismissed'].forEach(s => {
    const btn = document.getElementById('tab-' + s);
    btn.className = `btn btn-sm ${s === status ? 'btn-primary' : 'btn-ghost'}`;
  });
  loadReports();
}

async function loadReports() {
  document.getElementById('reportsContent').innerHTML = `<div style="text-align:center;padding:40px"><div class="spinner spinner-dark"></div></div>`;

  const res = await API.get(`../api/admin.php?action=reports&status=${currentStatus}&page=${currentPage}`);
  if (!res.success) { Toast.error('Failed to load reports.'); return; }

  const { reports, total, pages } = res;

  if (!reports.length) {
    document.getElementById('reportsContent').innerHTML = `
      <div class="empty-state" style="padding:60px 20px">
        <div class="empty-icon">🚩</div>
        <h4>No ${currentStatus} reports</h4>
        <p>No user reports with status "${currentStatus}".</p>
      </div>`;
    document.getElementById('pagination').innerHTML = '';
    return;
  }

  document.getElementById('reportsContent').innerHTML = reports.map(r => `
    <div class="report-card ${r.status}">
      <div class="report-header">
        <div class="report-medicine">
          <h4>💊 ${r.medicine_name}</h4>
          <p>${r.composition} &bull; ${r.manufacturer}</p>
          ${!r.is_active ? '<span class="badge badge-danger" style="margin-top:4px">Medicine Removed</span>' : ''}
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0">
          <span class="badge ${r.status==='pending'?'badge-danger':r.status==='resolved'?'badge-success':'badge-neutral'}">
            ${r.status.charAt(0).toUpperCase()+r.status.slice(1)}
          </span>
          <span class="badge badge-warning">${reasonLabels[r.reason] || r.reason}</span>
        </div>
      </div>

      ${r.details ? `<div class="report-details">"${r.details}"</div>` : ''}
      ${r.admin_note ? `<div class="admin-note">📝 Admin note: ${r.admin_note}</div>` : ''}

      <div class="report-meta">
        <span>👤 Reported by: <strong>${r.reported_by}</strong> (${r.reporter_email})</span>
        <span>📅 ${new Date(r.created_at).toLocaleString()}</span>
        ${r.updated_at !== r.created_at ? `<span>🕐 Updated: ${timeAgo(r.updated_at)}</span>` : ''}
      </div>

      ${r.status === 'pending' ? `
      <div class="report-actions">
        <button class="btn btn-primary btn-sm" onclick="openResolve(${r.id},'resolved','${esc(r.medicine_name)}')">✅ Mark Resolved</button>
        <button class="btn btn-ghost btn-sm"   onclick="openResolve(${r.id},'dismissed','${esc(r.medicine_name)}')">🚫 Dismiss</button>
        ${r.is_active ? `<button class="btn btn-danger btn-sm" onclick="removeMedicine(${r.medicine_id},'${esc(r.medicine_name)}',${r.id})">🗑 Remove Medicine</button>` : ''}
      </div>` : ''}
    </div>`).join('');

  renderPagination(document.getElementById('pagination'), currentPage, pages, p => { currentPage = p; loadReports(); });
}

function esc(s) { return (s||'').replace(/'/g,"\\'"); }

function openResolve(reportId, newStatus, medicineName) {
  pendingResolve = { reportId, newStatus };
  const isResolve = newStatus === 'resolved';
  document.getElementById('resolveTitle').textContent = isResolve ? '✅ Resolve Report' : '🚫 Dismiss Report';
  document.getElementById('resolveDesc').innerHTML = isResolve
    ? `Mark the report on <strong>${medicineName}</strong> as resolved. Add an optional note.`
    : `Dismiss the report on <strong>${medicineName}</strong> as not actionable.`;
  document.getElementById('resolveConfirmBtn').className = isResolve ? 'btn btn-primary' : 'btn btn-ghost';
  document.getElementById('resolveConfirmBtn').textContent = isResolve ? 'Mark Resolved' : 'Dismiss Report';
  document.getElementById('resolveNote').value = '';
  document.getElementById('resolveAlert').innerHTML = '';
  Modal.open('resolveModal');
}

document.getElementById('resolveConfirmBtn').addEventListener('click', async () => {
  if (!pendingResolve) return;
  const btn  = document.getElementById('resolveConfirmBtn');
  const note = document.getElementById('resolveNote').value.trim();

  Form.setLoading(btn, true, 'Saving…');
  const res = await API.post('../api/admin.php?action=resolve_report', {
    id: pendingResolve.reportId, status: pendingResolve.newStatus, note
  });
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(res.message);
    Modal.close('resolveModal');
    loadReports();
    pendingResolve = null;
  } else {
    document.getElementById('resolveAlert').innerHTML =
      `<div class="alert alert-error"><span>⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
});

function removeMedicine(medicineId, medicineName, reportId) {
  confirmAction(
    `Remove medicine <strong>${medicineName}</strong> from the system? This will also resolve the associated report.`,
    async () => {
      // Remove the medicine
      const r1 = await API.post('../api/admin.php?action=remove_medicine', { id: medicineId, reason: 'Removed via user report' });
      if (!r1.success) { Toast.error(r1.message); return; }
      // Auto-resolve the report
      await API.post('../api/admin.php?action=resolve_report', {
        id: reportId, status: 'resolved', note: 'Medicine removed from system.'
      });
      Toast.success('Medicine removed and report resolved.');
      loadReports();
    }
  );
}

loadReports();
</script>
</body>
</html>
