<?php
require_once '../includes/functions.php';
requireLogin('user', '../auth/login.php');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search History — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">Search History</span>
      </div>
      <div class="topbar-right">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
        <div class="topbar-user">
          <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <div><div class="user-name"><?= htmlspecialchars($user['name']) ?></div><div class="user-role">User</div></div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="page-header">
        <h2>🕐 Search History</h2>
        <span style="font-size:0.85rem;color:var(--text-muted)">Last 10 unique searches</span>
      </div>

      <div class="card" style="max-width:700px">
        <div class="card-header">
          <h3>Your Recent Searches</h3>
        </div>
        <div id="historyBody">
          <div style="padding:40px;text-align:center"><div class="spinner spinner-dark"></div></div>
        </div>
      </div>

      <div style="margin-top:20px;max-width:700px">
        <div class="card">
          <div class="card-body" style="text-align:center">
            <div style="font-size:2rem;margin-bottom:12px">🔍</div>
            <h4 style="font-family:var(--font-body);font-size:1rem;margin-bottom:6px">Quick Search</h4>
            <p style="font-size:0.875rem;margin-bottom:16px">Click any history item above to search again, or start a new search.</p>
            <a href="search.php" class="btn btn-primary">Go to Search →</a>
          </div>
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

async function loadHistory() {
  const res = await API.get('../api/search.php?action=history');
  const el = document.getElementById('historyBody');

  if (!res.success || !res.history.length) {
    el.innerHTML = `
      <div class="empty-state" style="padding:60px 20px">
        <div class="empty-icon">🕐</div>
        <h4>No search history yet</h4>
        <p>Your recent medicine searches will appear here. <a href="search.php">Start searching →</a></p>
      </div>`;
    return;
  }

  el.innerHTML = `
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Search Term</th>
            <th>Last Searched</th>
            <th>Results Found</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          ${res.history.map((h, i) => `
            <tr>
              <td style="color:var(--text-muted)">${i+1}</td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <span style="font-size:1rem">🔍</span>
                  <strong>${h.search_term}</strong>
                </div>
              </td>
              <td style="font-size:0.82rem;color:var(--text-muted)">${timeAgo(h.last_searched)}</td>
              <td>
                ${h.result_count > 0
                  ? `<span class="badge badge-success">${h.result_count} results</span>`
                  : `<span class="badge badge-neutral">No results</span>`}
              </td>
              <td>
                <a href="search.php?q=${encodeURIComponent(h.search_term)}" class="btn btn-primary btn-sm">Search Again →</a>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

loadHistory();
</script>
</body>
</html>
