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
  <title>Dashboard — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    .hero-search {
      background: linear-gradient(135deg, var(--clr-primary) 0%, #0d7a5a 100%);
      border-radius: var(--border-radius-lg);
      padding: 40px;
      color: white;
      position: relative;
      overflow: hidden;
      margin-bottom: 28px;
    }
    .hero-search::before {
      content: '💊';
      position: absolute;
      right: 40px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 6rem;
      opacity: 0.12;
    }
    .hero-search h2 { color: white; font-size: 1.7rem; margin-bottom: 6px; }
    .hero-search p  { color: rgba(255,255,255,0.8); margin-bottom: 22px; }
    .hero-search-bar {
      display: flex;
      gap: 10px;
      max-width: 560px;
    }
    .hero-search-bar input {
      flex: 1;
      padding: 12px 18px;
      border-radius: 50px;
      border: none;
      outline: none;
      font-size: 0.95rem;
      background: rgba(255,255,255,0.95);
      color: black;
    }
    .hero-search-bar input::placeholder { color: var(--text-muted); }
  </style>
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
          <div class="user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <div><div class="user-name"><?= htmlspecialchars($user['name']) ?></div><div class="user-role">User</div></div>
        </div>
      </div>
    </div>

    <div class="page-content">
      <!-- Hero Search Block -->
      <div class="hero-search">
        <h2>Find Your Medicine 💊</h2>
        <p>Search across all partner pharmacies. Get alternatives if unavailable.</p>
        <div class="hero-search-bar">
          <input type="text" id="quickSearch" placeholder="Search by medicine name or composition..."
                 onkeydown="if(event.key==='Enter') goSearch()">
          <button class="btn btn-accent" onclick="goSearch()">Search →</button>
        </div>
      </div>

      <div class="grid-2" style="gap:20px;margin-bottom:24px">
        <!-- Recent Searches -->
        <div class="card">
          <div class="card-header">
            <h3>🕐 Recent Searches</h3>
            <a href="history.php" style="font-size:0.82rem;color:var(--clr-primary)">View all →</a>
          </div>
          <div class="card-body" id="recentSearches">
            <div class="empty-state"><div class="empty-icon">🔍</div><p>Loading...</p></div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
          <div class="card-header"><h3>⚡ Quick Actions</h3></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <a href="search.php" class="btn btn-primary w-full" style="justify-content:flex-start;gap:12px">
              <span>🔍</span> Advanced Medicine Search
            </a>
            <a href="history.php" class="btn btn-outline w-full" style="justify-content:flex-start;gap:12px">
              <span>🕐</span> View Search History
            </a>
            <a href="profile.php" class="btn btn-ghost w-full" style="justify-content:flex-start;gap:12px">
              <span>👤</span> My Profile
            </a>
          </div>
        </div>
      </div>

      <!-- How search works -->
      <div class="card">
        <div class="card-header"><h3>ℹ️ How Search Works</h3></div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px">
            <div style="text-align:center;padding:16px">
              <div style="font-size:2rem;margin-bottom:10px">1️⃣</div>
              <h5>Search Medicine</h5>
              <p style="font-size:0.82rem">Enter any medicine name or ingredient.</p>
            </div>
            <div style="text-align:center;padding:16px">
              <div style="font-size:2rem;margin-bottom:10px">2️⃣</div>
              <h5>View Pharmacies</h5>
              <p style="font-size:0.82rem">See all pharmacies with it in stock and their locations.</p>
            </div>
            <div style="text-align:center;padding:16px">
              <div style="font-size:2rem;margin-bottom:10px">3️⃣</div>
              <h5>Get Alternatives</h5>
              <p style="font-size:0.82rem">If unavailable, we show medicines with the same composition.</p>
            </div>
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

function goSearch() {
  const q = document.getElementById('quickSearch').value.trim();
  if (!q) { Toast.warning('Please enter a medicine name.'); return; }
  window.location = `search.php?q=${encodeURIComponent(q)}`;
}

async function loadRecentSearches() {
  const res = await API.get('../api/search.php?action=history');
  const el = document.getElementById('recentSearches');
  if (!res.success || !res.history.length) {
    el.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><h4>No searches yet</h4><p>Your recent searches will appear here.</p></div>';
    return;
  }
  el.innerHTML = '<div class="history-chips">' +
    res.history.slice(0,8).map(h =>
      `<div class="history-chip" onclick="window.location='search.php?q=${encodeURIComponent(h.search_term)}'">
        🔍 ${h.search_term}
      </div>`).join('') +
    '</div>' +
    `<div style="font-size:0.78rem;color:var(--text-muted);margin-top:8px">${res.history.length} recent searches stored</div>`;
}

loadRecentSearches();
</script>
</body>
</html>
