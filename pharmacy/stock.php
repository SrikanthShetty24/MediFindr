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
  <title>Stock Management — Pharmacy | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .tab-bar { display:flex; gap:4px; background:var(--bg-page); border-radius:var(--border-radius-sm); padding:4px; margin-bottom:24px; }
    .tab-btn  { flex:1; text-align:center; padding:9px 14px; border-radius:6px; cursor:pointer; font-size:.85rem; font-weight:500; color:var(--text-muted); transition:var(--transition); border:none; background:transparent; }
    .tab-btn.active { background:var(--bg-card); color:var(--clr-primary); font-weight:600; box-shadow:var(--shadow-sm); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .reuse-banner { background:var(--clr-info-lt); border:1px solid var(--clr-info); border-radius:var(--border-radius-sm); padding:10px 14px; font-size:.82rem; color:var(--clr-info); margin-bottom:14px; display:none; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Stock Management</span>
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
        <h2>📦 Stock Management</h2>
      </div>

      <!-- Tab Navigation -->
      <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('add')">➕ Add Medicine</button>
        <button class="tab-btn" onclick="switchTab('stock')">📦 My Stock</button>
      </div>

      <!-- ═══ TAB 1: ADD MEDICINE ═══ -->
      <div class="tab-panel active" id="tab-add">
        <div class="card" style="max-width:700px">
          <div class="card-header">
            <h3>➕ Add Medicine to Your Stock</h3>
          </div>
          <div class="card-body">
            <div id="addAlert"></div>

            <!-- Quick-add from existing system medicines -->
            <div style="background:var(--clr-primary-dim);border-radius:var(--border-radius-sm);padding:14px 16px;margin-bottom:22px">
              <div style="font-weight:600;font-size:.88rem;color:var(--clr-primary);margin-bottom:8px">🔍 Already in system? Add stock to existing medicine</div>
              <div class="autocomplete-wrap">
                <input type="text" class="form-control" id="quickSearch" placeholder="Search existing medicines…" autocomplete="off" oninput="searchExisting()">
                <div class="autocomplete-list" id="quickList" style="display:none"></div>
              </div>
              <div class="reuse-banner" id="reuseBanner"></div>
            </div>

            <div style="text-align:center;color:var(--text-muted);font-size:.82rem;margin-bottom:18px">— or fill in full details below to add a new medicine —</div>

            <form id="addMedForm" novalidate>
              <div class="form-row">
                <div class="form-group">
                  <label>Medicine Name <span class="required">*</span></label>
                  <input type="text" class="form-control" name="name" id="fName" placeholder="e.g. Dolo 650" required>
                </div>
                <div class="form-group">
                  <label>Category</label>
                  <select class="form-control" name="category" id="fCategory">
                    <option>General</option>
                    <option>Analgesic/Antipyretic</option>
                    <option>Antibiotic</option>
                    <option>NSAID</option>
                    <option>Antacid/PPI</option>
                    <option>Antidiabetic</option>
                    <option>Antihistamine</option>
                    <option>Cardiovascular</option>
                    <option>Respiratory</option>
                    <option>Vitamin/Supplement</option>
                    <option>Other</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label>Composition <span class="required">*</span></label>
                <input type="text" class="form-control" name="composition" id="fComposition" placeholder="e.g. Paracetamol 650mg" required>
                <div class="form-hint">Active ingredient(s) and strength. Used for alternative matching.</div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Dosage <span class="required">*</span></label>
                  <input type="text" class="form-control" name="dosage" id="fDosage" placeholder="e.g. 1 tablet every 6 hours" required>
                </div>
                <div class="form-group">
                  <label>Manufacturer <span class="required">*</span></label>
                  <input type="text" class="form-control" name="manufacturer" id="fManufacturer" placeholder="e.g. Micro Labs" required>
                </div>
              </div>
              <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" id="fDescription" placeholder="Brief description (optional)…" rows="2"></textarea>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label>Quantity <span class="required">*</span></label>
                  <input type="number" class="form-control" name="quantity" id="fQuantity" min="1" placeholder="Units available" required>
                </div>
                <div class="form-group">
                  <label>Price per unit (₹)</label>
                  <input type="number" class="form-control" name="price" id="fPrice" min="0" step="0.01" placeholder="e.g. 15.50">
                </div>
                <div class="form-group">
                  <label>Expiry Date</label>
                  <input type="date" class="form-control" name="expiry_date" id="fExpiry">
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-lg" id="addMedBtn">Add to My Stock</button>
            </form>
          </div>
        </div>
      </div>

      <!-- ═══ TAB 2: MY STOCK ═══ -->
      <div class="tab-panel" id="tab-stock">
        <div class="card mb">
          <div class="card-body" style="padding:16px 20px">
            <div class="search-wrap" style="max-width:400px">
              <span class="search-icon">🔍</span>
              <input type="text" class="form-control" id="searchInput" placeholder="Search medicines in your stock…" oninput="debounceSearch()">
            </div>
          </div>
        </div>

        <div class="card">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Medicine Name</th>
                  <th>Composition</th>
                  <th>Qty</th>
                  <th>Price</th>
                  <th>Expiry</th>
                  <th>Last Updated</th>
                  <th>Source</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="stockTableBody">
                <tr><td colspan="9" class="text-center" style="padding:40px"><div class="spinner spinner-dark"></div></td></tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer flex-between">
            <span id="tableInfo" style="font-size:.82rem;color:var(--text-muted)"></span>
            <div class="pagination" id="pagination"></div>
          </div>
        </div>
      </div>
    </div><!-- /page-content -->
  </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header">
      <h3>✏️ Update Stock</h3>
      <button class="modal-close" onclick="Modal.close('editModal')">×</button>
    </div>
    <div class="modal-body">
      <div id="editAlert"></div>
      <div style="margin-bottom:16px;padding:12px;background:var(--clr-primary-dim);border-radius:8px">
        <div style="font-weight:600;color:var(--clr-primary)" id="editMedName"></div>
        <div style="font-size:.82rem;color:var(--text-secondary)" id="editMedComp"></div>
      </div>
      <input type="hidden" id="editStockId">
      <div class="form-row">
        <div class="form-group">
          <label>Quantity <span class="required">*</span></label>
          <input type="number" class="form-control" id="editQty" min="0" placeholder="Units">
        </div>
        <div class="form-group">
          <label>Price per unit (₹)</label>
          <input type="number" class="form-control" id="editPrice" min="0" step="0.01">
        </div>
      </div>
      <div class="form-group">
        <label>Expiry Date</label>
        <input type="date" class="form-control" id="editExpiry">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('editModal')">Cancel</button>
      <button class="btn btn-primary" id="updateStockBtn" onclick="updateStock()">Update Stock</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
let currentPage = 1, searchTimer;

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active', ['add','stock'][i] === tab));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  if (tab === 'stock') loadStock();
}

// ── Quick search existing medicines ──
let qTimer;
function searchExisting() {
  clearTimeout(qTimer);
  qTimer = setTimeout(async () => {
    const q = document.getElementById('quickSearch').value.trim();
    document.getElementById('reuseBanner').style.display = 'none';
    if (q.length < 2) { document.getElementById('quickList').style.display = 'none'; return; }
    const res = await API.get(`../api/pharmacy.php?action=medicines_list&q=${encodeURIComponent(q)}`);
    if (!res.medicines?.length) {
      document.getElementById('quickList').innerHTML = '<div class="autocomplete-item" style="color:var(--text-muted)">Not found — fill the form below to add new.</div>';
      document.getElementById('quickList').style.display = 'block';
      return;
    }
    document.getElementById('quickList').innerHTML = res.medicines.map(m =>
      `<div class="autocomplete-item" onclick="selectExisting(${m.id},'${esc(m.name)}','${esc(m.composition)}','${esc(m.dosage)}','${esc(m.manufacturer)}')">
        <strong>${m.name}</strong>
        <small>${m.composition} — ${m.dosage}</small>
      </div>`).join('');
    document.getElementById('quickList').style.display = 'block';
  }, 300);
}

function esc(s) { return (s||'').replace(/'/g, "\\'").replace(/"/g, '\\"'); }

function selectExisting(id, name, comp, dosage, manf) {
  document.getElementById('quickList').style.display = 'none';
  document.getElementById('quickSearch').value = name;
  // Pre-fill form
  document.getElementById('fName').value = name;
  document.getElementById('fComposition').value = comp;
  document.getElementById('fDosage').value = dosage;
  document.getElementById('fManufacturer').value = manf;
  document.getElementById('reuseBanner').style.display = 'block';
  document.getElementById('reuseBanner').textContent = `ℹ️ This will add stock to the existing medicine "${name}". Fill in quantity below.`;
  document.getElementById('fQuantity').focus();
}

document.addEventListener('click', e => {
  if (!e.target.closest('.autocomplete-wrap')) document.getElementById('quickList').style.display = 'none';
});

// ── Add medicine form ──
document.getElementById('addMedForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn  = document.getElementById('addMedBtn');
  const alrt = document.getElementById('addAlert');
  alrt.innerHTML = '';

  const payload = {
    name:         document.getElementById('fName').value.trim(),
    composition:  document.getElementById('fComposition').value.trim(),
    dosage:       document.getElementById('fDosage').value.trim(),
    manufacturer: document.getElementById('fManufacturer').value.trim(),
    category:     document.getElementById('fCategory').value,
    description:  document.getElementById('fDescription').value.trim(),
    quantity:     parseInt(document.getElementById('fQuantity').value) || 0,
    price:        document.getElementById('fPrice').value || null,
    expiry_date:  document.getElementById('fExpiry').value || null,
  };

  if (!payload.name || !payload.composition || !payload.dosage || !payload.manufacturer) {
    alrt.innerHTML = alertHtml('error','Name, composition, dosage and manufacturer are required.'); return;
  }
  if (payload.quantity < 1) {
    alrt.innerHTML = alertHtml('error','Quantity must be at least 1.'); return;
  }

  Form.setLoading(btn, true, 'Adding…');
  const res = await API.post('../api/pharmacy.php?action=add_medicine', payload);
  Form.setLoading(btn, false);

  if (res.success) {
    alrt.innerHTML = alertHtml('success', res.message + (res.reused ? ' <em>(linked to existing medicine)</em>' : ''));
    e.target.reset();
    document.getElementById('quickSearch').value = '';
    document.getElementById('reuseBanner').style.display = 'none';
    Toast.success(res.message);
  } else {
    alrt.innerHTML = alertHtml('error', res.message);
  }
});

// ── Stock table ──
function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { currentPage = 1; loadStock(); }, 400);
}

async function loadStock() {
  const search = document.getElementById('searchInput')?.value.trim() || '';
  const res    = await API.get(`../api/pharmacy.php?action=stock&page=${currentPage}&search=${encodeURIComponent(search)}`);
  if (!res.success) return;
  const { stock, total, pages } = res;
  const tbody = document.getElementById('stockTableBody');

  if (!stock.length) {
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📦</div><h4>No stock yet</h4><p>Switch to the "Add Medicine" tab to add medicines.</p></div></td></tr>`;
    document.getElementById('tableInfo').textContent = '';
    document.getElementById('pagination').innerHTML  = '';
    return;
  }

  const today = new Date();
  tbody.innerHTML = stock.map((s, i) => {
    const exp     = s.expiry_date ? new Date(s.expiry_date) : null;
    const days    = exp ? Math.ceil((exp - today) / 86400000) : null;
    const expCell = !exp ? '—'
      : days < 0   ? `<span class="badge badge-danger">Expired</span>`
      : days <= 7  ? `<span class="badge badge-danger">${days}d</span>`
      : days <= 30 ? `<span class="badge badge-warning">${days}d left</span>`
      : exp.toLocaleDateString();

    const qC = s.quantity === 0 ? `<span class="badge badge-danger">Out</span>`
             : s.quantity < 5  ? `<span class="qty-badge low">${s.quantity}</span>`
             : s.quantity < 20 ? `<span class="qty-badge mid">${s.quantity}</span>`
             : `<span class="qty-badge high">${s.quantity}</span>`;

    const src = s.is_my_medicine
      ? `<span class="badge badge-primary" title="You added this medicine">✦ Mine</span>`
      : `<span class="badge badge-neutral" title="Added by another pharmacy">Shared</span>`;

    return `<tr>
      <td style="color:var(--text-muted)">${(currentPage-1)*15+i+1}</td>
      <td><strong>${s.name}</strong></td>
      <td style="font-size:.8rem;color:var(--text-muted);max-width:160px;white-space:normal">${s.composition}</td>
      <td>${qC}</td>
      <td style="font-size:.85rem">${s.price ? '₹'+parseFloat(s.price).toFixed(2) : '—'}</td>
      <td style="font-size:.8rem">${expCell}</td>
      <td style="font-size:.75rem;color:var(--text-muted)">${timeAgo(s.updated_at)}</td>
      <td>${src}</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-ghost btn-sm btn-icon" title="Edit"
            onclick="openEdit(${s.id},'${esc(s.name)}','${esc(s.composition)}',${s.quantity},${s.price||'null'},'${s.expiry_date||''}')">✏️</button>
          <button class="btn btn-ghost btn-sm btn-icon" title="Remove"
            onclick="deleteStock(${s.id},'${esc(s.name)}')">🗑️</button>
        </div>
      </td>
    </tr>`;
  }).join('');

  document.getElementById('tableInfo').textContent = `Showing ${(currentPage-1)*15+1}–${Math.min(currentPage*15,total)} of ${total}`;
  renderPagination(document.getElementById('pagination'), currentPage, pages, p => { currentPage = p; loadStock(); });
}

function openEdit(id, name, comp, qty, price, expiry) {
  document.getElementById('editStockId').value = id;
  document.getElementById('editMedName').textContent  = name;
  document.getElementById('editMedComp').textContent  = comp;
  document.getElementById('editQty').value    = qty;
  document.getElementById('editPrice').value  = price !== 'null' && price ? price : '';
  document.getElementById('editExpiry').value = expiry || '';
  document.getElementById('editAlert').innerHTML = '';
  Modal.open('editModal');
}

async function updateStock() {
  const btn = document.getElementById('updateStockBtn');
  const alrt = document.getElementById('editAlert');
  alrt.innerHTML = '';
  const id     = parseInt(document.getElementById('editStockId').value);
  const qty    = parseInt(document.getElementById('editQty').value);
  const price  = document.getElementById('editPrice').value;
  const expiry = document.getElementById('editExpiry').value;
  if (!id || qty < 0) { alrt.innerHTML = alertHtml('error','Enter a valid quantity (0 or more).'); return; }

  Form.setLoading(btn, true, 'Updating…');
  const res = await API.put('../api/pharmacy.php?action=stock', { id, quantity:qty, price:price||null, expiry_date:expiry||null });
  Form.setLoading(btn, false);
  if (res.success) { Toast.success(res.message); Modal.close('editModal'); loadStock(); }
  else alrt.innerHTML = alertHtml('error', res.message);
}

function deleteStock(id, name) {
  confirmAction(`Remove <strong>${name}</strong> from your stock?`, async () => {
    const res = await API.delete(`../api/pharmacy.php?action=stock&id=${id}`);
    if (res.success) { Toast.success(res.message); loadStock(); }
    else Toast.error(res.message);
  });
}

function alertHtml(type, msg) {
  const icon = type === 'success' ? '✓' : '⚠';
  return `<div class="alert alert-${type}"><span class="alert-icon">${icon}</span><div class="alert-body"><div class="alert-msg">${msg}</div></div></div>`;
}

// Open stock tab if URL has #stock
if (window.location.hash === '#stock') switchTab('stock');
</script>
</body>
</html>
