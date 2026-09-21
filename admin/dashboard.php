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
  <title>Dashboard — Admin | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .chart-bar-wrap { display:flex; flex-direction:column; gap:10px; }
    .chart-bar-row  { display:flex; align-items:center; gap:10px; font-size:0.82rem; }
    .chart-bar-label{ width:120px; flex-shrink:0; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .chart-bar-track{ flex:1; height:22px; background:var(--bg-page); border-radius:6px; overflow:hidden; }
    .chart-bar-fill { height:100%; background:var(--clr-primary); border-radius:6px; transition:width 0.6s ease; display:flex; align-items:center; padding-left:8px; }
    .chart-bar-val  { width:40px; text-align:right; font-weight:600; color:var(--text-primary); }
    .trend-chart    { display:flex; align-items:flex-end; gap:8px; height:120px; padding-top:10px; }
    .trend-bar      { flex:1; background:var(--clr-primary-dim); border-radius:4px 4px 0 0; position:relative; min-width:28px; transition:height 0.6s ease; cursor:default; }
    .trend-bar:hover{ background:var(--clr-primary); }
    .trend-bar span { position:absolute; bottom:-22px; left:50%; transform:translateX(-50%); font-size:0.65rem; color:var(--text-muted); white-space:nowrap; }
    .trend-bar::before { content:attr(data-val); position:absolute; top:-22px; left:50%; transform:translateX(-50%); font-size:0.72rem; font-weight:600; color:var(--clr-primary); }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Dashboard</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
          <div>
            <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
            <div class="user-role">Administrator</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
      <div class="page-header">
        <h2>📊 Dashboard Overview</h2>
        <span style="font-size:0.85rem;color:var(--text-muted)" id="lastUpdated"></span>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid" id="statsGrid">
        <div class="stat-card"><div class="stat-icon">💊</div><div class="stat-info"><h3 id="s-med">—</h3><p>Total Medicines</p></div></div>
        <div class="stat-card"><div class="stat-icon">🏥</div><div class="stat-info"><h3 id="s-pha">—</h3><p>Active Pharmacies</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:var(--clr-warning-lt)">⏳</div><div class="stat-info"><h3 id="s-pen">—</h3><p>Pending Approvals</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:var(--clr-info-lt)">👥</div><div class="stat-info"><h3 id="s-usr">—</h3><p>Registered Users</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:var(--clr-danger-lt)">🚩</div><div class="stat-info"><h3 id="s-rpt">—</h3><p>Pending Reports</p></div></div>
        <div class="stat-card"><div class="stat-icon" style="background:var(--clr-warning-lt)">🔀</div><div class="stat-info"><h3 id="s-dup">—</h3><p>Duplicate Groups</p></div></div>
      </div>

      <!-- Charts Row -->
      <div class="grid-2" style="gap:20px;margin-bottom:24px">
        <!-- Most Searched -->
        <div class="card">
          <div class="card-header"><h3>🔍 Most Searched Medicines</h3></div>
          <div class="card-body" id="mostSearched">
            <div class="empty-state"><div class="empty-icon">📊</div><p>Loading...</p></div>
          </div>
        </div>

        <!-- Search Trend -->
        <div class="card">
          <div class="card-header"><h3>📈 Search Trend (6 Months)</h3></div>
          <div class="card-body">
            <div class="trend-chart" id="trendChart">
              <div class="empty-state" style="padding:20px"><p>Loading...</p></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Low Stock + Quick Actions -->
      <div class="grid-2" style="gap:20px">
        <!-- Low Stock -->
        <div class="card">
          <div class="card-header">
            <h3>⚠️ Low Availability Medicines</h3>
            <span class="badge badge-warning">Total qty &lt; 20</span>
          </div>
          <div class="card-body" id="lowStock">
            <div class="empty-state"><div class="empty-icon">📦</div><p>Loading...</p></div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <div class="card-header"><h3>⚡ Quick Actions</h3></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <a href="inspection.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>🔬</span> Medicine Inspection Panel
            </a>
            <a href="duplicates.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>🔀</span> Detect &amp; Merge Duplicates
            </a>
            <a href="reports.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>🚩</span> View User Reports
            </a>
            <a href="pharmacies.php?status=pending" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>⏳</span> Review Pending Pharmacies
            </a>
            <a href="pharmacies.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>🏥</span> All Pharmacies
            </a>
            <a href="users.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>👥</span> User Management
            </a>
          </div>
        </div>
      </div>
    </div><!-- /page-content -->
  </div><!-- /main-content -->
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
  const res = await API.get('../api/admin.php?action=stats');
  if (!res.success) return;
  const s = res.stats;

  document.getElementById('s-med').textContent = s.total_medicines;
  document.getElementById('s-pha').textContent = s.total_pharmacies;
  document.getElementById('s-pen').textContent = s.pending_pharmacies;
  document.getElementById('s-usr').textContent = s.total_users;
  document.getElementById('s-rpt').textContent = s.pending_reports ?? 0;
  document.getElementById('s-dup').textContent = s.duplicate_groups ?? 0;
  document.getElementById('lastUpdated').textContent = 'Updated: ' + new Date().toLocaleTimeString();

  // Most searched bar chart
  const ms = document.getElementById('mostSearched');
  if (!s.most_searched.length) {
    ms.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><p>No searches yet.</p></div>';
  } else {
    const max = Math.max(...s.most_searched.map(x => x.count));
    ms.innerHTML = '<div class="chart-bar-wrap">' +
      s.most_searched.map(item => `
        <div class="chart-bar-row">
          <div class="chart-bar-label" title="${item.search_term}">${item.search_term}</div>
          <div class="chart-bar-track">
            <div class="chart-bar-fill" style="width:${Math.round(item.count/max*100)}%;color:white;font-size:0.72rem">${item.count > 3 ? item.count + 'x' : ''}</div>
          </div>
          <div class="chart-bar-val">${item.count}</div>
        </div>`).join('') + '</div>';
  }

  // Search trend
  const tc = document.getElementById('trendChart');
  if (!s.search_trend.length) {
    tc.innerHTML = '<div class="empty-state" style="padding:20px;width:100%"><p>No data yet.</p></div>';
  } else {
    const maxT = Math.max(...s.search_trend.map(x => x.count), 1);
    tc.innerHTML = s.search_trend.map(item => {
      const pct = Math.round(item.count / maxT * 100);
      const label = item.month.slice(5); // MM
      return `<div class="trend-bar" style="height:${Math.max(pct, 8)}%" data-val="${item.count}"><span>${label}</span></div>`;
    }).join('');
  }

  // Low stock
  const ls = document.getElementById('lowStock');
  if (!s.low_stock.length) {
    ls.innerHTML = '<div class="empty-state"><div class="empty-icon">✅</div><h4>All stocked up!</h4><p>No medicines with critically low availability.</p></div>';
  } else {
    ls.innerHTML = '<div style="display:flex;flex-direction:column;gap:10px">' +
      s.low_stock.map(item => `
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--bg-page);border-radius:8px">
          <span style="font-size:0.875rem;font-weight:500">${item.name}</span>
          <span class="badge ${item.total_qty < 5 ? 'badge-danger' : 'badge-warning'}">${item.total_qty} units</span>
        </div>`).join('') + '</div>';
  }
}

loadStats();
// Auto-refresh every 60 seconds
setInterval(loadStats, 60000);
</script>
</body>
</html>
