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
  <title>Dashboard — Pharmacy | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Dashboard</span>
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
        <div>
          <h2>📊 Pharmacy Dashboard</h2>
          <p style="margin-top:4px;font-size:0.875rem">Welcome back, <strong><?= htmlspecialchars($user['name']) ?></strong></p>
        </div>
        <a href="stock.php" class="btn btn-primary">+ Add Stock</a>
      </div>

      <!-- Stats -->
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
          <div class="stat-icon">📦</div>
          <div class="stat-info"><h3 id="s-items">—</h3><p>Medicine Types in Stock</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🔢</div>
          <div class="stat-info"><h3 id="s-qty">—</h3><p>Total Units Available</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:var(--clr-warning-lt)">⚠️</div>
          <div class="stat-info"><h3 id="s-low">—</h3><p>Low Stock Items (&lt;10)</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:var(--clr-danger-lt)">📅</div>
          <div class="stat-info"><h3 id="s-exp">—</h3><p>Expiring Soon (30 days)</p></div>
        </div>
      </div>

      <!-- Quick Actions + Recent Stock -->
      <div class="grid-2" style="gap:20px">
        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header"><h3>⚡ Quick Actions</h3></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <a href="stock.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>📦</span> Manage My Stock
            </a>
            <a href="stock.php#add" class="btn btn-primary w-full" style="justify-content:flex-start;gap:12px">
              <span>➕</span> Add New Medicine to Stock
            </a>
            <a href="profile.php" class="btn btn-ghost w-full" style="justify-content:flex-start;gap:12px">
              <span>🏥</span> Update Pharmacy Profile
            </a>
          </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="card">
          <div class="card-header">
            <h3>⚠️ Low Stock Alerts</h3>
            <span class="badge badge-warning" id="lowBadge">Loading...</span>
          </div>
          <div class="card-body" id="lowStockList">
            <div class="empty-state"><div class="empty-icon">📊</div><p>Loading...</p></div>
          </div>
        </div>
      </div>

      <!-- Expiring Soon -->
      <div class="card mt">
        <div class="card-header">
          <h3>📅 Expiring Soon</h3>
          <span class="badge badge-danger" id="expBadge">Loading...</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Medicine</th>
                <th>Composition</th>
                <th>Quantity</th>
                <th>Expiry Date</th>
                <th>Days Left</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="expiryTable">
              <tr><td colspan="6" class="text-center" style="padding:30px"><div class="spinner spinner-dark"></div></td></tr>
            </tbody>
          </table>
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

async function loadStats() {
  const res = await API.get('../api/pharmacy.php?action=stats');
  if (!res.success) return;
  const s = res.stats;
  document.getElementById('s-items').textContent = s.total_items;
  document.getElementById('s-qty').textContent = Number(s.total_qty).toLocaleString();
  document.getElementById('s-low').textContent = s.low_stock;
  document.getElementById('s-exp').textContent = s.expiring_soon;
}

async function loadStock() {
  const res = await API.get('../api/pharmacy.php?action=stock&page=1');
  if (!res.success) return;
  const stock = res.stock;

  // Low stock (qty < 10)
  const low = stock.filter(s => s.quantity < 10);
  document.getElementById('lowBadge').textContent = `${low.length} items`;
  const lowList = document.getElementById('lowStockList');
  if (!low.length) {
    lowList.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><h4>All good!</h4><p>No items with low stock.</p></div>';
  } else {
    lowList.innerHTML = '<div style="display:flex;flex-direction:column;gap:8px">' +
      low.map(s => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:var(--bg-page);border-radius:8px">
          <div>
            <div style="font-size:0.875rem;font-weight:600">${s.name}</div>
            <div style="font-size:0.78rem;color:var(--text-muted)">${s.composition}</div>
          </div>
          <span class="badge ${s.quantity < 5 ? 'badge-danger' : 'badge-warning'}">${s.quantity} left</span>
        </div>`).join('') + '</div>';
  }

  // Expiring soon
  const today = new Date();
  const expiring = stock.filter(s => {
    if (!s.expiry_date) return false;
    const exp = new Date(s.expiry_date);
    const days = Math.ceil((exp - today) / 86400000);
    return days <= 30;
  });

  document.getElementById('expBadge').textContent = `${expiring.length} items`;
  const expiryTable = document.getElementById('expiryTable');
  if (!expiring.length) {
    expiryTable.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">✅</div><h4>No medicines expiring soon</h4></div></td></tr>';
  } else {
    expiryTable.innerHTML = expiring.map(s => {
      const exp = new Date(s.expiry_date);
      const days = Math.ceil((exp - today) / 86400000);
      const badge = days <= 7 ? 'badge-danger' : days <= 15 ? 'badge-warning' : 'badge-info';
      return `
        <tr>
          <td><strong>${s.name}</strong></td>
          <td style="font-size:0.82rem;color:var(--text-muted)">${s.composition}</td>
          <td>${s.quantity}</td>
          <td>${new Date(s.expiry_date).toLocaleDateString()}</td>
          <td><span class="badge ${badge}">${days} days</span></td>
          <td><a href="stock.php" class="btn btn-ghost btn-sm">Manage</a></td>
        </tr>`;
    }).join('');
  }
}

loadStats();
loadStock();
</script>
</body>
</html>
