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
  <title>Users — Admin | MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">User Management</span>
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
        <h2>👥 Users</h2>
        <span id="userCount" style="font-size:0.85rem;color:var(--text-muted)"></span>
      </div>

      <!-- Search -->
      <div class="card mb">
        <div class="card-body" style="padding:16px 20px">
          <div class="search-wrap" style="max-width:400px">
            <span class="search-icon">🔍</span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search by name or email..." oninput="filterUsers()">
          </div>
        </div>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Joined</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <tr><td colspan="6" class="text-center" style="padding:40px"><div class="spinner spinner-dark"></div></td></tr>
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
let allUsers = [];

function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
});

async function loadUsers() {
  const res = await API.get('../api/admin.php?action=users');
  if (!res.success) return;
  allUsers = res.users;
  document.getElementById('userCount').textContent = `${allUsers.length} registered users`;
  renderUsers(allUsers);
}

function filterUsers() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  renderUsers(allUsers.filter(u =>
    u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  ));
}

function renderUsers(users) {
  const tbody = document.getElementById('usersTableBody');
  if (!users.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">👥</div><h4>No users found</h4></div></td></tr>`;
    return;
  }
  tbody.innerHTML = users.map((u, i) => `
    <tr>
      <td style="color:var(--text-muted)">${i+1}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem">${u.full_name[0].toUpperCase()}</div>
          <strong>${u.full_name}</strong>
        </div>
      </td>
      <td style="font-size:0.875rem">${u.email}</td>
      <td style="font-size:0.875rem;color:var(--text-muted)">${u.phone || '—'}</td>
      <td>${u.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'}</td>
      <td style="font-size:0.8rem;color:var(--text-muted)">${new Date(u.created_at).toLocaleDateString()}</td>
    </tr>`).join('');
}

loadUsers();
</script>
</body>
</html>
