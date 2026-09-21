<?php
require_once '../includes/functions.php';
requireLogin('user', '../auth/login.php');
$user = getCurrentUser();
$db   = getDB();

// Fetch full user data
$stmt = $db->prepare("SELECT id, full_name, email, phone, is_active, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
        <span class="page-title">My Profile</span>
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
        <h2>👤 My Profile</h2>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:800px">

        <!-- Profile Card -->
        <div class="card" style="grid-column:1/-1">
          <div class="profile-banner"></div>
          <div style="padding:50px 28px 24px">
            <div class="profile-avatar"><?= strtoupper(substr($userData['full_name'],0,1)) ?></div>
            <h3 style="font-family:var(--font-display);font-size:1.5rem;margin-bottom:4px"><?= htmlspecialchars($userData['full_name']) ?></h3>
            <p style="color:var(--text-muted);font-size:0.875rem">
              📧 <?= htmlspecialchars($userData['email']) ?>
              <?php if ($userData['phone']): ?> &bull; 📞 <?= htmlspecialchars($userData['phone']) ?><?php endif; ?>
            </p>
            <div style="display:flex;gap:10px;margin-top:12px;flex-wrap:wrap">
              <span class="badge badge-success">Active</span>
              <span style="font-size:0.82rem;color:var(--text-muted)">Joined <?= date('d M Y', strtotime($userData['created_at'])) ?></span>
            </div>
          </div>
        </div>

        <!-- Update Info -->
        <div class="card">
          <div class="card-header"><h3>✏️ Update Information</h3></div>
          <div class="card-body">
            <div id="infoAlert"></div>
            <form id="infoForm" novalidate>
              <div class="form-group">
                <label>Full Name <span class="required">*</span></label>
                <input type="text" class="form-control" name="full_name" id="fName"
                       value="<?= htmlspecialchars($userData['full_name']) ?>" required>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" class="form-control" name="phone" id="fPhone"
                       value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" placeholder="10-digit number">
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" disabled
                       style="opacity:0.6;cursor:not-allowed">
                <div class="form-hint">Email cannot be changed. Contact support if needed.</div>
              </div>
              <button type="submit" class="btn btn-primary" id="saveInfoBtn">Save Changes</button>
            </form>
          </div>
        </div>

        <!-- Change Password -->
        <div class="card">
          <div class="card-header"><h3>🔐 Change Password</h3></div>
          <div class="card-body">
            <div id="pwdAlert"></div>
            <form id="pwdForm" novalidate>
              <div class="form-group">
                <label>Current Password <span class="required">*</span></label>
                <div style="position:relative">
                  <input type="password" class="form-control" name="current_password" id="curPwd"
                         placeholder="Enter current password" style="padding-right:44px">
                  <button type="button" onclick="togglePwd('curPwd')"
                          style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
                </div>
              </div>
              <div class="form-group">
                <label>New Password <span class="required">*</span></label>
                <div style="position:relative">
                  <input type="password" class="form-control" name="new_password" id="newPwd"
                         placeholder="Min. 8 chars, letters + numbers" style="padding-right:44px">
                  <button type="button" onclick="togglePwd('newPwd')"
                          style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
                </div>
              </div>
              <div class="form-group">
                <label>Confirm New Password <span class="required">*</span></label>
                <div style="position:relative">
                  <input type="password" class="form-control" name="confirm_password" id="confPwd"
                         placeholder="Repeat new password" style="padding-right:44px">
                  <button type="button" onclick="togglePwd('confPwd')"
                          style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
                </div>
              </div>
              <button type="submit" class="btn btn-primary" id="savePwdBtn">Update Password</button>
            </form>
          </div>
        </div>

      </div><!-- /grid -->
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

function togglePwd(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

document.getElementById('infoForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('saveInfoBtn');
  const alertEl = document.getElementById('infoAlert');
  alertEl.innerHTML = '';
  const name  = document.getElementById('fName').value.trim();
  const phone = document.getElementById('fPhone').value.trim();

  if (!name) {
    alertEl.innerHTML = '<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">Name is required.</div></div></div>';
    return;
  }

  Form.setLoading(btn, true, 'Saving...');
  const res = await API.put('../api/user.php?action=profile', { full_name: name, phone });
  Form.setLoading(btn, false);

  if (res.success) {
    alertEl.innerHTML = `<div class="alert alert-success"><span class="alert-icon">✓</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
    Toast.success(res.message);
  } else {
    alertEl.innerHTML = `<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
});

document.getElementById('pwdForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('savePwdBtn');
  const alertEl = document.getElementById('pwdAlert');
  alertEl.innerHTML = '';

  const curPwd  = document.getElementById('curPwd').value;
  const newPwd  = document.getElementById('newPwd').value;
  const confPwd = document.getElementById('confPwd').value;

  if (!curPwd || !newPwd || !confPwd) {
    alertEl.innerHTML = '<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">All password fields are required.</div></div></div>';
    return;
  }
  if (newPwd !== confPwd) {
    alertEl.innerHTML = '<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">New passwords do not match.</div></div></div>';
    return;
  }
  if (newPwd.length < 8) {
    alertEl.innerHTML = '<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">Password must be at least 8 characters.</div></div></div>';
    return;
  }

  Form.setLoading(btn, true, 'Updating...');
  const res = await API.put('../api/user.php?action=password', {
    current_password: curPwd,
    new_password: newPwd
  });
  Form.setLoading(btn, false);

  if (res.success) {
    alertEl.innerHTML = `<div class="alert alert-success"><span class="alert-icon">✓</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
    e.target.reset();
    Toast.success(res.message);
  } else {
    alertEl.innerHTML = `<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
});
</script>
</body>
</html>
