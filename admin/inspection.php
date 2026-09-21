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
  <title>Inspection Panel — Admin | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Medicine Inspection Panel</span>
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
        <h2>🔬 Medicine Inspection Panel</h2>
        <div style="background:var(--clr-info-lt);color:var(--clr-info);border-radius:8px;padding:8px 14px;font-size:.82rem">
          ℹ️ Medicines are added directly by pharmacies and become <strong>immediately searchable</strong>. Intervene only for suspicious entries.
        </div>
      </div>

      <!-- Filters -->
      <div class="card mb">
        <div class="card-body" style="padding:16px 20px">
          <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
              <div class="search-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" class="form-control" id="searchInput" placeholder="Search medicines…" oninput="debounceSearch()">
              </div>
            </div>
            <div>
              <label style="font-size:.78rem;margin-bottom:4px;display:block">Filter by Pharmacy</label>
              <select class="form-control" id="pharmacyFilter" onchange="currentPage=1;loadMedicines()" style="min-width:200px">
                <option value="">All Pharmacies</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Medicine Name</th>
                <th>Composition</th>
                <th>Dosage</th>
                <th>Manufacturer</th>
                <th>Added By</th>
                <th>Stock</th>
                <th>Reports</th>
                <th>Added</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="medTableBody">
              <tr><td colspan="10" class="text-center" style="padding:40px"><div class="spinner spinner-dark"></div></td></tr>
            </tbody>
          </table>
        </div>
        <div class="card-footer flex-between">
          <span id="tableInfo" style="font-size:.82rem;color:var(--text-muted)"></span>
          <div class="pagination" id="pagination"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Medicine Detail Modal -->
<div class="modal-overlay" id="detailModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h3>💊 Medicine Details</h3>
      <button class="modal-close" onclick="Modal.close('detailModal')">×</button>
    </div>
    <div class="modal-body" id="detailBody"></div>
    <div class="modal-footer" id="detailFooter"></div>
  </div>
</div>

<!-- Flag / Remove Modal -->
<div class="modal-overlay" id="actionModal">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <h3 id="actionTitle">⚠️ Action Required</h3>
      <button class="modal-close" onclick="Modal.close('actionModal')">×</button>
    </div>
    <div class="modal-body">
      <div id="actionAlert"></div>
      <p id="actionDesc" style="margin-bottom:16px;color:var(--text-primary)"></p>
      <div class="form-group">
        <label>Reason / Note</label>
        <textarea class="form-control" id="actionReason" rows="3" placeholder="Describe why this medicine is suspicious…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('actionModal')">Cancel</button>
      <button class="btn btn-danger" id="actionConfirmBtn">Confirm</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
let currentPage = 1, searchTimer, pendingAction = null;

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentPage = 1; loadMedicines(); }, 400);
}

async function loadMedicines() {
  const search   = document.getElementById('searchInput').value.trim();
  const pharmaId = document.getElementById('pharmacyFilter').value;
  let url = `../api/admin.php?action=inspection&page=${currentPage}`;
  if (search)   url += `&search=${encodeURIComponent(search)}`;
  if (pharmaId) url += `&pharmacy_id=${pharmaId}`;

  const res = await API.get(url);
  if (!res.success) return;
  const { medicines, total, pages, pharmacies } = res;

  // Populate pharmacy filter (once)
  const sel = document.getElementById('pharmacyFilter');
  if (sel.options.length === 1 && pharmacies?.length) {
    pharmacies.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id; opt.textContent = p.pharmacy_name;
      sel.appendChild(opt);
    });
  }

  const tbody = document.getElementById('medTableBody');
  if (!medicines.length) {
    tbody.innerHTML = `<tr><td colspan="10"><div class="empty-state"><div class="empty-icon">💊</div><h4>No medicines found</h4><p>Adjust filters to see results.</p></div></td></tr>`;
    document.getElementById('tableInfo').textContent = '';
    document.getElementById('pagination').innerHTML  = '';
    return;
  }

  tbody.innerHTML = medicines.map((m, i) => {
    const flagBadge = m.is_flagged
      ? `<span class="badge badge-warning" title="${m.flag_reason||''}">⚑ Flagged</span>` : '';
    const rptBadge  = m.report_count > 0
      ? `<span class="badge badge-danger">🚩 ${m.report_count}</span>` : `<span style="color:var(--text-muted);font-size:.8rem">—</span>`;
    const stockStr  = m.total_stock > 0
      ? `<span class="qty-badge ${m.total_stock < 10 ? 'low' : 'high'}">${m.total_stock}</span>` : `<span class="badge badge-neutral">0</span>`;

    return `<tr>
      <td style="color:var(--text-muted)">${(currentPage-1)*15+i+1}</td>
      <td>
        <div style="font-weight:600">${m.name}</div>
        <div style="margin-top:3px">${flagBadge}</div>
      </td>
      <td style="font-size:.8rem;color:var(--text-muted);max-width:160px;white-space:normal">${m.composition}</td>
      <td style="font-size:.8rem">${m.dosage}</td>
      <td style="font-size:.8rem">${m.manufacturer}</td>
      <td style="font-size:.82rem">
        ${m.pharmacy_name
          ? `<span class="badge badge-primary">🏥 ${m.pharmacy_name}</span>`
          : `<span style="color:var(--text-muted);font-size:.78rem">System</span>`}
      </td>
      <td>${stockStr}</td>
      <td>${rptBadge}</td>
      <td style="font-size:.78rem;color:var(--text-muted)">${new Date(m.created_at).toLocaleDateString()}</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-ghost btn-sm" onclick="viewDetail(${JSON.stringify(m).replace(/"/g,'&quot;')})">👁</button>
          <button class="btn btn-ghost btn-sm" title="Flag as suspicious" onclick="openAction('flag',${m.id},'${m.name.replace(/'/g,"\\'")}')">⚑</button>
          <button class="btn btn-danger btn-sm" title="Remove medicine" onclick="openAction('remove',${m.id},'${m.name.replace(/'/g,"\\'")}')">🗑</button>
        </div>
      </td>
    </tr>`;
  }).join('');

  document.getElementById('tableInfo').textContent = `Showing ${(currentPage-1)*15+1}–${Math.min(currentPage*15,total)} of ${total} medicines`;
  renderPagination(document.getElementById('pagination'), currentPage, pages, p => { currentPage = p; loadMedicines(); });
}

function viewDetail(m) {
  document.getElementById('detailBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div><div class="text-muted text-sm">Name</div><div class="fw-semibold">${m.name}</div></div>
      <div><div class="text-muted text-sm">Category</div><div>${m.category||'—'}</div></div>
      <div style="grid-column:1/-1"><div class="text-muted text-sm">Composition</div><div>${m.composition}</div></div>
      <div><div class="text-muted text-sm">Dosage</div><div style="font-size:.875rem">${m.dosage}</div></div>
      <div><div class="text-muted text-sm">Manufacturer</div><div>${m.manufacturer}</div></div>
      <div><div class="text-muted text-sm">Added by Pharmacy</div><div>${m.pharmacy_name || 'System / Seeded'}</div></div>
      <div><div class="text-muted text-sm">Added on</div><div>${new Date(m.created_at).toLocaleString()}</div></div>
      <div><div class="text-muted text-sm">Total Stock</div><div>${m.total_stock ?? 0} units</div></div>
      <div><div class="text-muted text-sm">Pending Reports</div><div>${m.report_count ?? 0}</div></div>
      ${m.is_flagged ? `<div style="grid-column:1/-1"><div class="text-muted text-sm">Flag Reason</div><div style="color:var(--clr-warning)">${m.flag_reason||'—'}</div></div>` : ''}
    </div>`;
  document.getElementById('detailFooter').innerHTML = `
    <div style="display:flex;gap:10px">
      <button class="btn btn-ghost" onclick="openAction('flag',${m.id},'${m.name.replace(/'/g,"\\'")}');Modal.close('detailModal')">⚑ Flag</button>
      <button class="btn btn-danger" onclick="openAction('remove',${m.id},'${m.name.replace(/'/g,"\\'")}');Modal.close('detailModal')">🗑 Remove</button>
      <button class="btn btn-ghost" onclick="Modal.close('detailModal')">Close</button>
    </div>`;
  Modal.open('detailModal');
}

function openAction(type, id, name) {
  pendingAction = { type, id };
  const isRemove = type === 'remove';
  document.getElementById('actionTitle').textContent   = isRemove ? '🗑️ Remove Medicine' : '⚑ Flag Medicine';
  document.getElementById('actionDesc').innerHTML      = isRemove
    ? `Remove <strong>${name}</strong> from the system? This will hide it from all searches. Stock data is preserved.`
    : `Flag <strong>${name}</strong> as suspicious. It will remain in the system but you can remove it after review.`;
  document.getElementById('actionConfirmBtn').className = isRemove ? 'btn btn-danger' : 'btn btn-warning';
  document.getElementById('actionConfirmBtn').textContent = isRemove ? 'Remove Medicine' : 'Flag Medicine';
  document.getElementById('actionReason').value        = '';
  document.getElementById('actionAlert').innerHTML     = '';
  Modal.open('actionModal');
}

document.getElementById('actionConfirmBtn').addEventListener('click', async () => {
  if (!pendingAction) return;
  const btn    = document.getElementById('actionConfirmBtn');
  const reason = document.getElementById('actionReason').value.trim();
  const alrt   = document.getElementById('actionAlert');
  alrt.innerHTML = '';

  Form.setLoading(btn, true, 'Processing…');
  const endpoint = pendingAction.type === 'remove' ? 'remove_medicine' : 'flag_medicine';
  const res = await API.post(`../api/admin.php?action=${endpoint}`, { id: pendingAction.id, reason });
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(res.message);
    Modal.close('actionModal');
    loadMedicines();
    pendingAction = null;
  } else {
    alrt.innerHTML = `<div class="alert alert-error"><span>⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
});

loadMedicines();
</script>
</body>
</html>
