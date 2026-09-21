<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<div class="auth-page">
  <!-- Left Panel -->
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-mark">💊</div>
      <h1>MediFindr</h1>
      <p>Your trusted platform for medicine availability, real-time stock tracking, and smart alternatives.</p>
      <div class="auth-features">
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Search medicines across local pharmacies</span>
        </div>
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Get instant alternatives by composition</span>
        </div>
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>View pharmacy locations and contacts</span>
        </div>
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Track your search history</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="auth-right">
    <div class="auth-form-wrap">
      <div class="flex-between mb">
        <a href="../index.php" style="color:var(--text-muted);font-size:0.85rem">← Back to Home</a>
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
      </div>

      <div class="auth-header">
        <h2>Welcome Back</h2>
        <p>Sign in to your MediFindr account.</p>
      </div>

      <!-- Role Tabs -->
      <div class="auth-tabs">
        <div class="auth-tab active" data-role="user"     onclick="selectRole('user')">👤 User</div>
        <div class="auth-tab"        data-role="pharmacy" onclick="selectRole('pharmacy')">🏥 Pharmacy</div>
        <div class="auth-tab"        data-role="admin"    onclick="selectRole('admin')">⚙️ Admin</div>
      </div>

      <div id="alertBox"></div>

      <form id="loginForm" novalidate>
        <input type="hidden" name="role" id="roleInput" value="user">

        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input type="email" class="form-control" id="email" name="email"
                 placeholder="Enter your email" required autocomplete="email">
        </div>

        <div class="form-group">
          <label for="password">Password <span class="required">*</span></label>
          <div style="position:relative">
            <input type="password" class="form-control" id="password" name="password"
                   placeholder="Enter your password" required autocomplete="current-password"
                   style="padding-right:44px">
            <button type="button" onclick="togglePwd('password')"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem">
              👁
            </button>
          </div>
        </div>

        <div class="flex-between mb">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:400;font-size:0.85rem">
            <input type="checkbox" style="accent-color:var(--clr-primary)"> Remember me
          </label>
          <a href="forgot.php" style="font-size:0.85rem;color:var(--clr-primary)">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg" id="submitBtn">
          Sign In
        </button>
      </form>

      <div class="divider">or</div>
      <div class="auth-link" id="registerLink">
        Don't have an account? <a href="register.php">Create one →</a>
      </div>

      <!-- Demo Credentials -->
      <div style="margin-top:24px;padding:14px;background:var(--bg-page);border-radius:var(--border-radius-sm);font-size:0.78rem;color:var(--text-muted)">
        <strong style="color:var(--text-secondary)">🎯 Demo Credentials:</strong><br>
        <span id="demoCreds">User: Register a new account / Admin: admin@medifindr.com / Admin@123</span>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
let currentRole = 'user';

// Read URL param
const urlParams = new URLSearchParams(window.location.search);
const initRole = urlParams.get('role') || 'user';
selectRole(initRole);

function selectRole(role) {
  currentRole = role;
  document.getElementById('roleInput').value = role;
  document.querySelectorAll('.auth-tab').forEach(t => {
    t.classList.toggle('active', t.dataset.role === role);
  });

  const demoCreds = {
    user:     'Register a new account to try as user',
    pharmacy: 'Email: apollo.koramangala@medifindr.com | Pass: Admin@123 (approved)',
    admin:    'Email: admin@medifindr.com | Pass: Admin@123'
  };
  document.getElementById('demoCreds').textContent = demoCreds[role] || '';
}

function togglePwd(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn  = document.getElementById('submitBtn');
  const email= document.getElementById('email').value.trim();
  const pass = document.getElementById('password').value;
  const alert= document.getElementById('alertBox');

  alert.innerHTML = '';
  if (!email || !pass) {
    showAlert('error', 'Please fill in all fields.');
    return;
  }

  Form.setLoading(btn, true, 'Signing In...');

  const res = await API.post('../api/auth.php?action=login', {
    email, password: pass, role: currentRole
  });

  Form.setLoading(btn, false);

  if (res.success) {
    Toast.success(res.message);
    setTimeout(() => window.location = res.redirect, 600);
  } else {
    showAlert('error', res.message || 'Login failed.');
  }
});

function showAlert(type, msg) {
  document.getElementById('alertBox').innerHTML = `
    <div class="alert alert-${type}">
      <span class="alert-icon">${type === 'error' ? '⚠' : '✓'}</span>
      <div class="alert-body"><div class="alert-msg">${msg}</div></div>
    </div>`;
}
</script>
</body>
</html>
