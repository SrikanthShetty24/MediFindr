<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    /* ── Password strength bar ── */
    .strength-bar-wrap {
      height: 5px;
      background: var(--border-color);
      border-radius: 3px;
      margin-top: 8px;
      overflow: hidden;
    }
    .strength-bar {
      height: 100%;
      border-radius: 3px;
      width: 0%;
      transition: width .35s ease, background .35s ease;
    }
    .strength-label {
      font-size: .75rem;
      margin-top: 5px;
      font-weight: 600;
      height: 16px;
    }
    .strength-0 { width:  0%;  background: transparent; }
    .strength-1 { width: 25%;  background: var(--clr-danger); }
    .strength-2 { width: 50%;  background: var(--clr-warning); }
    .strength-3 { width: 75%;  background: #3b82f6; }
    .strength-4 { width: 100%; background: var(--clr-accent); }

    /* ── Rules checklist ── */
    .pwd-rules {
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 8px;
    }
    .pwd-rules li {
      font-size: .75rem;
      padding: 3px 10px;
      border-radius: 20px;
      background: var(--bg-page);
      border: 1px solid var(--border-color);
      color: var(--text-muted);
      transition: all .2s;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .pwd-rules li.pass {
      background: var(--clr-success-lt);
      border-color: var(--clr-accent);
      color: var(--clr-success);
    }
    .pwd-rules li .rule-icon { font-size: .7rem; }

    /* ── Password eye button ── */
    .pwd-wrap { position: relative; }
    .pwd-wrap .form-control { padding-right: 44px; }
    .eye-btn {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: var(--text-muted);
      cursor: pointer; font-size: 1rem;
      padding: 4px; border-radius: 4px;
      transition: color .2s;
      line-height: 1;
    }
    .eye-btn:hover { color: var(--text-primary); }

    /* ── Success screen ── */
    .success-screen {
      display: none;
      text-align: center;
      padding: 12px 0;
    }
    .success-icon {
      width: 80px; height: 80px;
      background: var(--clr-success-lt);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.4rem;
      margin: 0 auto 22px;
      animation: popIn .4s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes popIn {
      from { transform: scale(0); opacity: 0; }
      to   { transform: scale(1); opacity: 1; }
    }
    .success-screen h3 {
      font-family: var(--font-display);
      font-size: 1.6rem;
      color: var(--clr-success);
      margin-bottom: 10px;
    }
    .success-screen p {
      font-size: .9rem; color: var(--text-secondary);
      line-height: 1.7; margin-bottom: 24px;
    }
    .countdown-redirect {
      font-size: .8rem; color: var(--text-muted); margin-bottom: 16px;
    }

    /* ── Invalid token screen ── */
    .invalid-screen {
      display: none;
      text-align: center;
      padding: 12px 0;
    }
    .invalid-icon {
      width: 72px; height: 72px;
      background: var(--clr-danger-lt);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      margin: 0 auto 20px;
    }
    .invalid-screen h3 { font-family:var(--font-display); font-size:1.5rem; color:var(--clr-danger); margin-bottom:10px; }
    .invalid-screen p  { font-size:.88rem; color:var(--text-secondary); line-height:1.7; margin-bottom:22px; }
  </style>
</head>
<body>
<div class="auth-page">

  <!-- Left panel -->
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-mark">🔐</div>
      <h1>Set New Password</h1>
      <p>Choose a strong password to keep your MediFindr account secure.</p>
      <div class="auth-features">
        <div class="auth-feature-item"><div class="check">✓</div><span>Minimum 8 characters</span></div>
        <div class="auth-feature-item"><div class="check">✓</div><span>Mix of letters and numbers</span></div>
        <div class="auth-feature-item"><div class="check">✓</div><span>Link is single-use only</span></div>
        <div class="auth-feature-item"><div class="check">✓</div><span>Your data stays secure</span></div>
      </div>
    </div>
  </div>

  <!-- Right panel -->
  <div class="auth-right">
    <div class="auth-form-wrap">

      <div class="flex-between mb">
        <a href="login.php" style="color:var(--text-muted);font-size:.85rem">← Back to Login</a>
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
      </div>

      <!-- ═══ INVALID TOKEN SCREEN ═══ -->
      <div id="invalidScreen" class="invalid-screen">
        <div class="invalid-icon">⛔</div>
        <h3>Link Invalid or Expired</h3>
        <p>
          This password reset link is either invalid or has expired (links expire after 60 minutes).
          Please request a fresh one.
        </p>
        <a href="forgot.php" class="btn btn-primary w-full btn-lg">Request New Reset Link</a>
        <div class="auth-link" style="margin-top:14px">
          <a href="login.php">← Back to Login</a>
        </div>
      </div>

      <!-- ═══ RESET FORM SCREEN ═══ -->
      <div id="formScreen">
        <div class="auth-header">
          <h2>Reset Password</h2>
          <p>Enter and confirm your new password below.</p>
        </div>

        <div id="alertBox"></div>

        <form id="resetForm" novalidate>

          <!-- New password -->
          <div class="form-group">
            <label for="pwd">New Password <span class="required">*</span></label>
            <div class="pwd-wrap">
              <input type="password" class="form-control" id="pwd"
                     placeholder="Min. 8 characters" required
                     oninput="updateStrength(this.value)">
              <button type="button" class="eye-btn" onclick="toggleEye('pwd', this)">👁</button>
            </div>
            <!-- Strength meter -->
            <div class="strength-bar-wrap">
              <div class="strength-bar" id="strengthBar"></div>
            </div>
            <div class="strength-label" id="strengthLabel" style="color:var(--text-muted)"></div>
            <!-- Rules -->
            <ul class="pwd-rules" id="pwdRules">
              <li id="rule-len"><span class="rule-icon">✗</span> 8+ characters</li>
              <li id="rule-letter"><span class="rule-icon">✗</span> Letter</li>
              <li id="rule-num"><span class="rule-icon">✗</span> Number</li>
              <li id="rule-special"><span class="rule-icon">✗</span> Special char</li>
            </ul>
          </div>

          <!-- Confirm password -->
          <div class="form-group">
            <label for="cpwd">Confirm Password <span class="required">*</span></label>
            <div class="pwd-wrap">
              <input type="password" class="form-control" id="cpwd"
                     placeholder="Repeat your new password" required
                     oninput="checkMatch()">
              <button type="button" class="eye-btn" onclick="toggleEye('cpwd', this)">👁</button>
            </div>
            <div id="matchMsg" style="font-size:.76rem;margin-top:5px;height:16px"></div>
          </div>

          <button type="submit" class="btn btn-primary w-full btn-lg" id="submitBtn">
            Reset Password
          </button>
        </form>
      </div>

      <!-- ═══ SUCCESS SCREEN ═══ -->
      <div id="successScreen" class="success-screen">
        <div class="success-icon">✅</div>
        <h3>Password Reset!</h3>
        <p>Your password has been updated successfully. You can now log in with your new password.</p>
        <div class="countdown-redirect" id="countdownMsg">Redirecting to login in <strong id="redirectCount">5</strong>s…</div>
        <a href="login.php" class="btn btn-primary w-full btn-lg">Go to Login →</a>
      </div>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
// ── Read URL params ────────────────────────────────────────────────────────────
const params = new URLSearchParams(window.location.search);
const token  = params.get('token') || '';
const role   = params.get('role')  || 'user';

// No token → show invalid screen immediately
if (!token) {
  document.getElementById('formScreen').style.display  = 'none';
  document.getElementById('invalidScreen').style.display = 'block';
}

// ── Eye toggle ─────────────────────────────────────────────────────────────────
function toggleEye(id, btn) {
  const inp = document.getElementById(id);
  const isText = inp.type === 'text';
  inp.type = isText ? 'password' : 'text';
  btn.textContent = isText ? '👁' : '🙈';
}

// ── Strength meter ─────────────────────────────────────────────────────────────
const rules = {
  len:     v => v.length >= 8,
  letter:  v => /[a-zA-Z]/.test(v),
  num:     v => /[0-9]/.test(v),
  special: v => /[^a-zA-Z0-9]/.test(v),
};
const levels = [
  { cls: 'strength-0', label: '',           color: 'var(--text-muted)' },
  { cls: 'strength-1', label: 'Weak',       color: 'var(--clr-danger)' },
  { cls: 'strength-2', label: 'Fair',       color: 'var(--clr-warning)' },
  { cls: 'strength-3', label: 'Good',       color: 'var(--clr-info)' },
  { cls: 'strength-4', label: 'Strong ✓',   color: 'var(--clr-accent)' },
];

function updateStrength(val) {
  const score = Object.values(rules).filter(fn => fn(val)).length;
  const bar   = document.getElementById('strengthBar');
  const lbl   = document.getElementById('strengthLabel');

  // Remove old classes
  levels.forEach(l => bar.classList.remove(l.cls));
  bar.classList.add(levels[val ? score : 0].cls);
  const lvl   = levels[val ? score : 0];
  lbl.textContent = val ? lvl.label : '';
  lbl.style.color = lvl.color;

  // Update rule chips
  Object.entries(rules).forEach(([key, fn]) => {
    const el = document.getElementById('rule-' + key);
    if (!el) return;
    const pass = fn(val);
    el.classList.toggle('pass', pass);
    el.querySelector('.rule-icon').textContent = pass ? '✓' : '✗';
  });

  checkMatch();
}

function checkMatch() {
  const pwd  = document.getElementById('pwd').value;
  const cpwd = document.getElementById('cpwd').value;
  const msg  = document.getElementById('matchMsg');
  if (!cpwd) { msg.textContent = ''; return; }
  if (pwd === cpwd) {
    msg.textContent = '✓ Passwords match';
    msg.style.color = 'var(--clr-success)';
  } else {
    msg.textContent = '✗ Passwords do not match';
    msg.style.color = 'var(--clr-danger)';
  }
}

// ── Submit ─────────────────────────────────────────────────────────────────────
document.getElementById('resetForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn      = document.getElementById('submitBtn');
  const pwd      = document.getElementById('pwd').value;
  const cpwd     = document.getElementById('cpwd').value;
  const alertBox = document.getElementById('alertBox');
  alertBox.innerHTML = '';

  // Client-side validation
  if (!pwd || !cpwd) {
    alertBox.innerHTML = alertHtml('error', 'Both password fields are required.');
    return;
  }
  if (pwd !== cpwd) {
    alertBox.innerHTML = alertHtml('error', 'Passwords do not match.');
    return;
  }
  if (pwd.length < 8) {
    alertBox.innerHTML = alertHtml('error', 'Password must be at least 8 characters.');
    return;
  }
  if (!rules.letter(pwd) || !rules.num(pwd)) {
    alertBox.innerHTML = alertHtml('error', 'Password must include at least one letter and one number.');
    return;
  }

  Form.setLoading(btn, true, 'Resetting…');
  const res = await API.post('../api/auth.php?action=reset', { token, role, password: pwd });
  Form.setLoading(btn, false);

  if (res.success) {
    showSuccessScreen();
  } else {
    // Expired / invalid token
    if (res.message && (res.message.toLowerCase().includes('invalid') || res.message.toLowerCase().includes('expired'))) {
      document.getElementById('formScreen').style.display   = 'none';
      document.getElementById('invalidScreen').style.display = 'block';
    } else {
      alertBox.innerHTML = alertHtml('error', res.message || 'Reset failed. Please try again.');
    }
  }
});

// ── Success screen + auto-redirect ────────────────────────────────────────────
function showSuccessScreen() {
  document.getElementById('formScreen').style.display    = 'none';
  document.getElementById('successScreen').style.display = 'block';

  let count = 5;
  const tick = setInterval(() => {
    count--;
    const el = document.getElementById('redirectCount');
    if (el) el.textContent = count;
    if (count <= 0) {
      clearInterval(tick);
      window.location = 'login.php?role=' + role;
    }
  }, 1000);
}

// ── Helper ────────────────────────────────────────────────────────────────────
function alertHtml(type, msg) {
  const icon = type === 'success' ? '✓' : '⚠';
  return `<div class="alert alert-${type}"><span class="alert-icon">${icon}</span><div class="alert-body"><div class="alert-msg">${msg}</div></div></div>`;
}
</script>
</body>
</html>
