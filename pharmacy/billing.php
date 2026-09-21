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
  <title>Billing — Pharmacy | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    /* ── Tab bar ── */
    .tab-bar{display:flex;gap:4px;background:var(--bg-page);border-radius:var(--border-radius-sm);padding:4px;margin-bottom:24px}
    .tab-btn{flex:1;text-align:center;padding:9px 14px;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:500;color:var(--text-muted);transition:var(--transition);border:none;background:transparent}
    .tab-btn.active{background:var(--bg-card);color:var(--clr-primary);font-weight:600;box-shadow:var(--shadow-sm)}
    .tab-panel{display:none}
    .tab-panel.active{display:block}

    /* ── Bill builder ── */
    .bill-item-row{display:grid;grid-template-columns:1fr 90px 110px 110px 38px;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-color)}
    .bill-item-row:last-child{border-bottom:none}
    .bill-total-box{background:var(--clr-primary);color:white;border-radius:var(--border-radius);padding:20px 24px;margin-top:16px}
    .total-row{display:flex;justify-content:space-between;align-items:center;font-size:.9rem;margin-bottom:8px}
    .total-row.grand{font-size:1.2rem;font-weight:700;padding-top:10px;margin-top:4px;border-top:1px solid rgba(255,255,255,.3)}
    .total-label{color:rgba(255,255,255,.8)}
    .total-val{font-weight:600}

    /* ── Transaction table ── */
    .status-paid{background:var(--clr-success-lt);color:var(--clr-success)}
    .status-cancelled{background:var(--clr-danger-lt);color:var(--clr-danger)}

    /* ── Stat cards ── */
    .billing-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px}
    .bstat{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--border-radius);padding:18px 20px;text-align:center}
    .bstat h3{font-size:1.6rem;font-weight:700;font-family:var(--font-body);color:var(--clr-primary);line-height:1}
    .bstat p{font-size:.78rem;color:var(--text-muted);margin-top:4px}

    /* ── Print styles ── */
    @media print {
      body * { visibility: hidden }
      #printArea, #printArea * { visibility: visible }
      #printArea { position: fixed; left:0; top:0; width:100%; padding:20px }
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
        <span class="page-title">Billing</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar">🏥</div>
          <div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="user-role">Pharmacy</div>
          </div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="page-header">
        <h2>🧾 Billing & Transactions</h2>
      </div>

      <!-- Stats -->
      <div class="billing-stats" id="billingStats">
        <div class="bstat"><h3 id="bs-today-bills">—</h3><p>Bills Today</p></div>
        <div class="bstat"><h3 id="bs-today-rev">—</h3><p>Today's Revenue</p></div>
        <div class="bstat"><h3 id="bs-total-bills">—</h3><p>Total Bills</p></div>
        <div class="bstat"><h3 id="bs-total-rev">—</h3><p>Total Revenue</p></div>
      </div>

      <!-- Tabs -->
      <div class="tab-bar">
        <button class="tab-btn active" onclick="switchTab('create')">🧾 Create Bill</button>
        <button class="tab-btn"        onclick="switchTab('history')">📋 Transaction History</button>
      </div>

      <!-- ═══ CREATE BILL TAB ═══ -->
      <div class="tab-panel active" id="tab-create">
        <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

          <!-- Left: Bill builder -->
          <div class="card">
            <div class="card-header"><h3>Add Medicines to Bill</h3></div>
            <div class="card-body">
              <div id="createAlert"></div>

              <!-- Patient details -->
              <div class="form-row" style="margin-bottom:16px">
                <div class="form-group" style="margin-bottom:0">
                  <label>Patient Name <span class="required">*</span></label>
                  <input type="text" class="form-control" id="patientName" placeholder="Full name or Walk-in">
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label>Patient Phone</label>
                  <input type="tel" class="form-control" id="patientPhone" placeholder="10-digit number">
                </div>
              </div>

              <!-- Medicine search -->
              <div class="form-group">
                <label>Search &amp; Add Medicine</label>
                <div class="autocomplete-wrap">
                  <input type="text" class="form-control" id="medSearchInput"
                         placeholder="Type medicine name to search your stock…"
                         autocomplete="off" oninput="searchBillingMeds()">
                  <div class="autocomplete-list" id="medSearchList" style="display:none"></div>
                </div>
              </div>

              <!-- Bill items table -->
              <div id="billItemsWrap" style="min-height:80px">
                <div class="empty-state" id="billEmpty" style="padding:30px 20px">
                  <div class="empty-icon">💊</div>
                  <p>No medicines added yet. Search above to add.</p>
                </div>
                <div id="billItemsTable" style="display:none">
                  <div style="display:grid;grid-template-columns:1fr 90px 110px 110px 38px;gap:8px;padding:8px 0;border-bottom:2px solid var(--border-color)">
                    <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase">Medicine</div>
                    <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase">Qty</div>
                    <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase">Unit Price</div>
                    <div style="font-size:.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase">Total</div>
                    <div></div>
                  </div>
                  <div id="billItemRows"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Summary & submit -->
          <div style="display:flex;flex-direction:column;gap:16px">
            <div class="card">
              <div class="card-header"><h3>Bill Summary</h3></div>
              <div class="card-body">
                <div class="form-row" style="margin-bottom:12px">
                  <div class="form-group" style="margin-bottom:0">
                    <label>Discount (%)</label>
                    <input type="number" class="form-control" id="discountPct"
                           min="0" max="100" step="0.5" value="0" oninput="recalcTotals()">
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label>Tax (%)</label>
                    <input type="number" class="form-control" id="taxPct"
                           min="0" step="0.5" value="0" oninput="recalcTotals()">
                  </div>
                </div>
                <div class="form-group">
                  <label>Payment Method</label>
                  <select class="form-control" id="paymentMethod">
                    <option value="cash">💵 Cash</option>
                    <option value="upi">📱 UPI</option>
                    <option value="card">💳 Card</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Notes (optional)</label>
                  <textarea class="form-control" id="billNotes" rows="2" placeholder="Any notes…"></textarea>
                </div>

                <div class="bill-total-box">
                  <div class="total-row"><span class="total-label">Subtotal</span><span class="total-val" id="tSubtotal">₹0.00</span></div>
                  <div class="total-row"><span class="total-label">Discount</span><span class="total-val" id="tDiscount">— ₹0.00</span></div>
                  <div class="total-row"><span class="total-label">Tax</span><span class="total-val" id="tTax">+ ₹0.00</span></div>
                  <div class="total-row grand"><span>TOTAL</span><span id="tTotal">₹0.00</span></div>
                </div>

                <button class="btn btn-accent w-full btn-lg" id="createBillBtn"
                        onclick="createBill()" style="margin-top:14px">
                  Generate Bill
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ HISTORY TAB ═══ -->
      <div class="tab-panel" id="tab-history">
        <!-- Filters -->
        <div class="card mb">
          <div class="card-body" style="padding:16px 20px">
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
              <div style="flex:1;min-width:200px">
                <div class="search-wrap">
                  <span class="search-icon">🔍</span>
                  <input type="text" class="form-control" id="histSearch"
                         placeholder="Search bill no., patient name…" oninput="debounceHistory()">
                </div>
              </div>
              <div>
                <label style="font-size:.78rem;margin-bottom:4px;display:block">From</label>
                <input type="date" class="form-control" id="histFrom" onchange="loadHistory()">
              </div>
              <div>
                <label style="font-size:.78rem;margin-bottom:4px;display:block">To</label>
                <input type="date" class="form-control" id="histTo" onchange="loadHistory()">
              </div>
              <button class="btn btn-ghost btn-sm" onclick="clearHistFilters()">Clear</button>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Bill No.</th>
                  <th>Patient</th>
                  <th>Items</th>
                  <th>Amount</th>
                  <th>Payment</th>
                  <th>Status</th>
                  <th>Date &amp; Time</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="histTableBody">
                <tr><td colspan="8" class="text-center" style="padding:40px">
                  <div class="spinner spinner-dark"></div>
                </td></tr>
              </tbody>
            </table>
          </div>
          <div class="card-footer flex-between">
            <span id="histInfo" style="font-size:.82rem;color:var(--text-muted)"></span>
            <div class="pagination" id="histPagination"></div>
          </div>
        </div>
      </div>

    </div><!-- /page-content -->
  </div>
</div>

<!-- ═══ BILL DETAIL / PRINT MODAL ═══ -->
<div class="modal-overlay" id="billDetailModal">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <h3>🧾 Bill Detail</h3>
      <div style="display:flex;gap:8px">
        <button class="btn btn-outline btn-sm" onclick="printBill()">🖨 Print</button>
        <button class="modal-close" onclick="Modal.close('billDetailModal')">×</button>
      </div>
    </div>
    <div class="modal-body" id="billDetailBody">Loading…</div>
    <div class="modal-footer" id="billDetailFooter"></div>
  </div>
</div>

<!-- Print area (hidden until print) -->
<div id="printArea" style="display:none"></div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
// ── Sidebar ───────────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

// ── Tab switching ─────────────────────────────────────────────
function switchTab(tab) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active', ['create','history'][i] === tab));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.add('active');
  if (tab === 'history') loadHistory();
}

// ── Bill state ────────────────────────────────────────────────
let billItems = [];   // [{medicine_id, name, qty, stock, unit_price, line_total}]

function esc(s) { return (s||'').replace(/'/g, "\\'"); }

// ── Medicine autocomplete ─────────────────────────────────────
let medTimer;
function searchBillingMeds() {
  clearTimeout(medTimer);
  medTimer = setTimeout(async () => {
    const q = document.getElementById('medSearchInput').value.trim();
    if (q.length < 2) { document.getElementById('medSearchList').style.display = 'none'; return; }
    const res = await API.get(`../api/billing.php?action=search_medicine&q=${encodeURIComponent(q)}`);
    const list = document.getElementById('medSearchList');
    if (!res.success || !res.medicines.length) {
      list.innerHTML = '<div class="autocomplete-item" style="color:var(--text-muted)">No medicines in stock found</div>';
      list.style.display = 'block';
      return;
    }
    list.innerHTML = res.medicines.map(m =>
      `<div class="autocomplete-item" onclick="addBillItem(${m.id},'${esc(m.name)}',${m.quantity},${m.price||0})">
        <strong>${m.name}</strong>
        <small>${m.composition} — ${m.quantity} units in stock ${m.price ? '· ₹' + parseFloat(m.price).toFixed(2) : ''}</small>
      </div>`
    ).join('');
    list.style.display = 'block';
  }, 300);
}
document.addEventListener('click', e => {
  if (!e.target.closest('.autocomplete-wrap')) document.getElementById('medSearchList').style.display = 'none';
});

function addBillItem(id, name, stock, price) {
  document.getElementById('medSearchInput').value = '';
  document.getElementById('medSearchList').style.display = 'none';
  // Increment if already in bill
  const existing = billItems.find(i => i.medicine_id === id);
  if (existing) {
    if (existing.qty >= existing.stock) { Toast.warning(`Only ${existing.stock} units in stock.`); return; }
    existing.qty++;
    existing.line_total = existing.qty * existing.unit_price;
  } else {
    billItems.push({ medicine_id: id, name, qty: 1, stock, unit_price: price || 0, line_total: price || 0 });
  }
  renderBillItems();
}

function removeBillItem(id) {
  billItems = billItems.filter(i => i.medicine_id !== id);
  renderBillItems();
}

function updateQty(id, val) {
  const item = billItems.find(i => i.medicine_id === id);
  if (!item) return;
  const qty = Math.max(1, Math.min(parseInt(val)||1, item.stock));
  item.qty        = qty;
  item.line_total = qty * item.unit_price;
  renderBillItems();
}

function updatePrice(id, val) {
  const item = billItems.find(i => i.medicine_id === id);
  if (!item) return;
  item.unit_price = Math.max(0, parseFloat(val)||0);
  item.line_total = item.qty * item.unit_price;
  renderBillItems();
}

function renderBillItems() {
  const empty = document.getElementById('billEmpty');
  const table = document.getElementById('billItemsTable');
  const rows  = document.getElementById('billItemRows');
  if (!billItems.length) {
    empty.style.display = 'block';
    table.style.display = 'none';
    recalcTotals();
    return;
  }
  empty.style.display = 'none';
  table.style.display = 'block';
  rows.innerHTML = billItems.map(item => `
    <div class="bill-item-row">
      <div>
        <div style="font-size:.875rem;font-weight:600">${item.name}</div>
        <div style="font-size:.75rem;color:var(--text-muted)">${item.stock} in stock</div>
      </div>
      <input type="number" class="form-control" value="${item.qty}" min="1" max="${item.stock}"
             style="padding:6px 8px;font-size:.85rem"
             onchange="updateQty(${item.medicine_id}, this.value)">
      <input type="number" class="form-control" value="${item.unit_price.toFixed(2)}" min="0" step="0.5"
             style="padding:6px 8px;font-size:.85rem"
             onchange="updatePrice(${item.medicine_id}, this.value)">
      <div style="font-weight:700;font-size:.9rem;color:var(--clr-primary)">
        ₹${item.line_total.toFixed(2)}
      </div>
      <button onclick="removeBillItem(${item.medicine_id})"
              style="background:none;border:none;color:var(--clr-danger);cursor:pointer;font-size:1.1rem;padding:4px">×</button>
    </div>`).join('');
  recalcTotals();
}

function recalcTotals() {
  const subtotal    = billItems.reduce((s, i) => s + i.line_total, 0);
  const discPct     = parseFloat(document.getElementById('discountPct').value) || 0;
  const taxPct      = parseFloat(document.getElementById('taxPct').value)      || 0;
  const discAmt     = subtotal * discPct / 100;
  const afterDisc   = subtotal - discAmt;
  const taxAmt      = afterDisc * taxPct / 100;
  const total       = afterDisc + taxAmt;
  document.getElementById('tSubtotal').textContent = '₹' + subtotal.toFixed(2);
  document.getElementById('tDiscount').textContent = '— ₹' + discAmt.toFixed(2);
  document.getElementById('tTax').textContent      = '+ ₹' + taxAmt.toFixed(2);
  document.getElementById('tTotal').textContent    = '₹' + total.toFixed(2);
}

// ── Create bill ───────────────────────────────────────────────
async function createBill() {
  const btn  = document.getElementById('createBillBtn');
  const alrt = document.getElementById('createAlert');
  alrt.innerHTML = '';

  const patientName = document.getElementById('patientName').value.trim() || 'Walk-in';
  if (!billItems.length) {
    alrt.innerHTML = alertHtml('error', 'Add at least one medicine to the bill.');
    return;
  }
  for (const item of billItems) {
    if (item.unit_price <= 0) {
      alrt.innerHTML = alertHtml('warning', `Please set a price for <strong>${item.name}</strong>.`);
      return;
    }
  }

  const payload = {
    patient_name:   patientName,
    patient_phone:  document.getElementById('patientPhone').value.trim(),
    discount_pct:   parseFloat(document.getElementById('discountPct').value) || 0,
    tax_pct:        parseFloat(document.getElementById('taxPct').value)      || 0,
    payment_method: document.getElementById('paymentMethod').value,
    notes:          document.getElementById('billNotes').value.trim(),
    items:          billItems.map(i => ({
      medicine_id: i.medicine_id,
      quantity:    i.qty,
      unit_price:  i.unit_price,
    })),
  };

  Form.setLoading(btn, true, 'Creating bill…');
  const res = await API.post('../api/billing.php?action=create', payload);
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(`Bill ${res.bill_number} created! Total: ₹${parseFloat(res.total).toFixed(2)}`);
    // Clear form
    billItems = [];
    renderBillItems();
    document.getElementById('patientName').value  = '';
    document.getElementById('patientPhone').value = '';
    document.getElementById('discountPct').value  = '0';
    document.getElementById('taxPct').value       = '0';
    document.getElementById('billNotes').value    = '';
    recalcTotals();
    loadStats();
    // View the bill
    setTimeout(() => { viewBill(res.bill_id); }, 400);
  } else {
    alrt.innerHTML = alertHtml('error', res.message || 'Failed to create bill.');
  }
}

// ── Stats ─────────────────────────────────────────────────────
async function loadStats() {
  const res = await API.get('../api/billing.php?action=list&page=1');
  if (!res.success) return;
  const s = res.stats;
  document.getElementById('bs-today-bills').textContent = s.today_bills || 0;
  document.getElementById('bs-today-rev').textContent   = '₹' + parseFloat(s.today_revenue  || 0).toFixed(2);
  document.getElementById('bs-total-bills').textContent = s.total_bills || 0;
  document.getElementById('bs-total-rev').textContent   = '₹' + parseFloat(s.total_revenue  || 0).toFixed(2);
}

// ── Transaction history ───────────────────────────────────────
let histPage = 1, histTimer;
function debounceHistory() {
  clearTimeout(histTimer);
  histTimer = setTimeout(() => { histPage = 1; loadHistory(); }, 400);
}
function clearHistFilters() {
  document.getElementById('histSearch').value = '';
  document.getElementById('histFrom').value   = '';
  document.getElementById('histTo').value     = '';
  histPage = 1;
  loadHistory();
}

async function loadHistory() {
  const search = document.getElementById('histSearch').value.trim();
  const from   = document.getElementById('histFrom').value;
  const to     = document.getElementById('histTo').value;
  let url = `../api/billing.php?action=list&page=${histPage}`;
  if (search) url += `&search=${encodeURIComponent(search)}`;
  if (from)   url += `&from=${from}`;
  if (to)     url += `&to=${to}`;

  const res = await API.get(url);
  if (!res.success) return;
  const { bills, total, pages, stats } = res;

  // Update stats
  const s = stats;
  document.getElementById('bs-today-bills').textContent = s.today_bills || 0;
  document.getElementById('bs-today-rev').textContent   = '₹' + parseFloat(s.today_revenue  || 0).toFixed(2);
  document.getElementById('bs-total-bills').textContent = s.total_bills || 0;
  document.getElementById('bs-total-rev').textContent   = '₹' + parseFloat(s.total_revenue  || 0).toFixed(2);

  const tbody = document.getElementById('histTableBody');
  if (!bills.length) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🧾</div><h4>No bills found</h4><p>Create a bill using the "Create Bill" tab.</p></div></td></tr>`;
    document.getElementById('histInfo').textContent = '';
    document.getElementById('histPagination').innerHTML = '';
    return;
  }

  tbody.innerHTML = bills.map((b, i) => `
    <tr>
      <td><strong style="font-family:monospace;font-size:.85rem">${b.bill_number}</strong></td>
      <td>
        <div style="font-size:.875rem;font-weight:600">${b.patient_name}</div>
        <div style="font-size:.75rem;color:var(--text-muted)">${b.patient_phone || ''}</div>
      </td>
      <td><span class="badge badge-primary">${b.item_count} items</span></td>
      <td><strong style="color:var(--clr-primary)">₹${parseFloat(b.total_amount).toFixed(2)}</strong></td>
      <td style="font-size:.82rem;text-transform:capitalize">${b.payment_method}</td>
      <td><span class="badge status-${b.status}">${b.status}</span></td>
      <td style="font-size:.78rem;color:var(--text-muted)">${new Date(b.created_at).toLocaleString()}</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-ghost btn-sm" onclick="viewBill(${b.id})">👁 View</button>
          ${b.status === 'paid' ? `<button class="btn btn-ghost btn-sm" style="color:var(--clr-danger)" onclick="cancelBill(${b.id},'${b.bill_number}')">✕</button>` : ''}
        </div>
      </td>
    </tr>`).join('');

  document.getElementById('histInfo').textContent =
    `Showing ${(histPage-1)*15+1}–${Math.min(histPage*15, total)} of ${total} bills`;
  renderPagination(document.getElementById('histPagination'), histPage, pages, p => { histPage = p; loadHistory(); });
}

// ── View bill detail ──────────────────────────────────────────
async function viewBill(id) {
  const res = await API.get(`../api/billing.php?action=detail&id=${id}`);
  if (!res.success) { Toast.error('Failed to load bill.'); return; }
  const { bill, items, pharmacy } = res;

  const itemRows = items.map(i => `
    <tr>
      <td style="padding:8px 12px;border-bottom:1px solid #eee">${i.medicine_name}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee;text-align:center">${i.quantity}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee;text-align:right">₹${parseFloat(i.unit_price).toFixed(2)}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee;text-align:right;font-weight:700">₹${parseFloat(i.line_total).toFixed(2)}</td>
    </tr>`).join('');

  const html = `
    <div style="font-size:.85rem">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">
        <div>
          <div style="font-weight:700;font-size:1rem;color:var(--clr-primary)">${pharmacy.pharmacy_name}</div>
          <div style="color:var(--text-muted);font-size:.8rem;margin-top:2px">${pharmacy.address}, ${pharmacy.city}</div>
          <div style="color:var(--text-muted);font-size:.8rem">📞 ${pharmacy.phone}</div>
        </div>
        <div style="text-align:right">
          <div style="font-size:1.2rem;font-weight:700;color:var(--clr-primary);font-family:monospace">${bill.bill_number}</div>
          <div style="color:var(--text-muted);font-size:.78rem">${new Date(bill.created_at).toLocaleString()}</div>
          <span class="badge status-${bill.status}" style="margin-top:4px">${bill.status}</span>
        </div>
      </div>
      <div style="background:var(--bg-page);border-radius:8px;padding:10px 14px;margin-bottom:14px">
        <strong>Patient:</strong> ${bill.patient_name}
        ${bill.patient_phone ? ` &bull; 📞 ${bill.patient_phone}` : ''}
        &bull; Payment: <strong style="text-transform:capitalize">${bill.payment_method}</strong>
      </div>
      <table style="width:100%;border-collapse:collapse;border:1px solid #ddd;border-radius:8px;overflow:hidden">
        <thead>
          <tr style="background:#0A6E4F;color:white">
            <th style="padding:8px 12px;text-align:left;font-size:.8rem">Medicine</th>
            <th style="padding:8px 12px;text-align:center;font-size:.8rem">Qty</th>
            <th style="padding:8px 12px;text-align:right;font-size:.8rem">Unit Price</th>
            <th style="padding:8px 12px;text-align:right;font-size:.8rem">Total</th>
          </tr>
        </thead>
        <tbody>${itemRows}</tbody>
      </table>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;margin-top:12px;padding-top:10px;border-top:2px solid var(--border-color)">
        <div style="display:flex;gap:40px"><span style="color:var(--text-muted)">Subtotal</span><span>₹${parseFloat(bill.subtotal).toFixed(2)}</span></div>
        ${parseFloat(bill.discount_amt) > 0 ? `<div style="display:flex;gap:40px"><span style="color:var(--clr-danger)">Discount (${bill.discount_pct}%)</span><span style="color:var(--clr-danger)">— ₹${parseFloat(bill.discount_amt).toFixed(2)}</span></div>` : ''}
        ${parseFloat(bill.tax_amt) > 0 ? `<div style="display:flex;gap:40px"><span style="color:var(--text-muted)">Tax (${bill.tax_pct}%)</span><span>+ ₹${parseFloat(bill.tax_amt).toFixed(2)}</span></div>` : ''}
        <div style="display:flex;gap:40px;font-size:1.1rem;font-weight:700;color:var(--clr-primary);border-top:1px solid var(--border-color);padding-top:8px;margin-top:4px">
          <span>TOTAL</span><span>₹${parseFloat(bill.total_amount).toFixed(2)}</span>
        </div>
      </div>
      ${bill.notes ? `<div style="background:var(--clr-info-lt);border-radius:8px;padding:10px 14px;margin-top:14px;font-size:.82rem;color:var(--clr-info)">📝 ${bill.notes}</div>` : ''}
    </div>`;

  document.getElementById('billDetailBody').innerHTML = html;
  // Store print content
  document.getElementById('printArea').innerHTML = html;

  document.getElementById('billDetailFooter').innerHTML = `
    <div style="display:flex;gap:8px">
      <button class="btn btn-outline btn-sm" onclick="printBill()">🖨 Print</button>
      ${bill.status === 'paid' ? `<button class="btn btn-ghost btn-sm" style="color:var(--clr-danger)" onclick="cancelBill(${bill.id},'${bill.bill_number}')">Cancel Bill</button>` : ''}
      <button class="btn btn-ghost" onclick="Modal.close('billDetailModal')">Close</button>
    </div>`;

  Modal.open('billDetailModal');
}

function printBill() {
  document.getElementById('printArea').style.display = 'block';
  window.print();
  document.getElementById('printArea').style.display = 'none';
}

async function cancelBill(id, billNo) {
  confirmAction(`Cancel bill <strong>${billNo}</strong>? Stock will be restored.`, async () => {
    const res = await API.post('../api/billing.php?action=cancel', { id });
    if (res.success) {
      Toast.success(res.message);
      Modal.close('billDetailModal');
      loadHistory();
    } else Toast.error(res.message);
  });
}

function alertHtml(type, msg) {
  return `<div class="alert alert-${type}"><span class="alert-icon">${type==='error'?'⚠':'ℹ'}</span><div class="alert-body"><div class="alert-msg">${msg}</div></div></div>`;
}

loadStats();
</script>
</body>
</html>
