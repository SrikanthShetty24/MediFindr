<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pharmacy Login — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body style="background:var(--bg-page);display:flex;align-items:center;justify-content:center;min-height:100vh">

<div style="width:100%;max-width:440px;padding:20px">
  <div style="text-align:center;margin-bottom:32px">
    <div style="width:64px;height:64px;background:var(--clr-primary);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px">🏥</div>
    <h2 style="font-family:var(--font-display);font-size:1.8rem">Pharmacy Portal</h2>
    <p style="color:var(--text-muted);font-size:0.875rem">Manage your medicine inventory</p>
  </div>

  <div class="card">
    <div class="card-body">
      <div style="position:absolute;top:16px;right:16px">
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
      </div>

      <div id="alertBox"></div>

      <form id="loginForm" novalidate>
        <div class="form-group">
          <label>Pharmacy Email <span class="required">*</span></label>
          <input type="email" class="form-control" name="email" placeholder="pharmacy@email.com" required>
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <div style="position:relative">
            <input type="password" class="form-control" name="password" id="pwd"
                   placeholder="Your password" required style="padding-right:44px">
            <button type="button" onclick="togglePwd()"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
          </div>
        </div>
        <div class="flex-between mb">
          <span></span>
          <a href="../auth/forgot.php?role=pharmacy" style="font-size:0.85rem;color:var(--clr-primary)">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-full btn-lg" id="submitBtn">Sign In</button>
      </form>

      <div style="text-align:center;margin-top:16px;font-size:0.875rem;color:var(--text-muted)">
        New pharmacy? <a href="../auth/register.php?role=pharmacy" style="color:var(--clr-primary)">Register here →</a>
      </div>
      <div style="text-align:center;margin-top:8px">
        <a href="../index.php" style="font-size:0.82rem;color:var(--text-muted)">← Back to Home</a>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
function togglePwd() {
  const inp = document.getElementById('pwd');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('submitBtn');
  const data = Form.getData(e.target);
  data.role = 'pharmacy';
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
