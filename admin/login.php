<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body style="background:var(--bg-page);display:flex;align-items:center;justify-content:center;min-height:100vh">

<div style="width:100%;max-width:420px;padding:20px">
  <div style="text-align:center;margin-bottom:32px">
    <div style="width:64px;height:64px;background:var(--clr-primary);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px">⚙️</div>
    <h2 style="font-family:var(--font-display);font-size:1.8rem">Admin Portal</h2>
    <p style="color:var(--text-muted);font-size:0.875rem">MediFindr Administration</p>
  </div>

  <div class="card">
    <div class="card-body">
      <div style="position:absolute;top:16px;right:16px">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
      </div>

      <div id="alertBox"></div>

      <form id="adminLoginForm" novalidate>
        <div class="form-group">
          <label>Admin Email <span class="required">*</span></label>
          <input type="email" class="form-control" name="email" placeholder="admin@medifindr.com" required autocomplete="email">
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <div style="position:relative">
            <input type="password" class="form-control" name="password" id="pwd"
                   placeholder="Enter admin password" required style="padding-right:44px">
            <button type="button" onclick="togglePwd()"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-full btn-lg" id="submitBtn">Sign In as Admin</button>
      </form>

      <div style="text-align:center;margin-top:16px">
        <a href="../index.php" style="font-size:0.85rem;color:var(--text-muted)">← Back to Home</a>
      </div>
    </div>
  </div>

  <div style="margin-top:16px;padding:12px 14px;background:var(--clr-primary-dim);border-radius:var(--border-radius-sm);font-size:0.78rem;color:var(--clr-primary)">
    <strong>Demo:</strong> admin@medifindr.com / Admin@123
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function togglePwd() {
  const inp = document.getElementById('pwd');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

document.getElementById('adminLoginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn  = document.getElementById('submitBtn');
  const data = Form.getData(e.target);
  data.role  = 'admin';
  const alertBox = document.getElementById('alertBox');
  alertBox.innerHTML = '';

  Form.setLoading(btn, true, 'Signing In...');
  const res = await API.post('../api/auth.php?action=login', data);
  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(res.message);
    setTimeout(() => window.location = res.redirect, 600);
  } else {
    alertBox.innerHTML = `<div class="alert alert-error"><span class="alert-icon">⚠</span><div class="alert-body"><div class="alert-msg">${res.message}</div></div></div>`;
  }
});
</script>
</body>
</html>
