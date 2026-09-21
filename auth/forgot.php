<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    /* ── Sent confirmation screen ── */
    .sent-screen {
      display: none;
      text-align: center;
      padding: 12px 0;
    }
    .sent-icon {
      width: 80px; height: 80px;
      background: var(--clr-primary-dim);
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
    .sent-screen h3 {
      font-family: var(--font-display);
      font-size: 1.6rem;
      color: var(--clr-primary);
      margin-bottom: 10px;
    }
    .sent-screen p {
      font-size: .9rem;
      color: var(--text-secondary);
      line-height: 1.7;
      max-width: 340px;
      margin: 0 auto 20px;
    }
    .sent-email-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--clr-primary-dim);
      color: var(--clr-primary);
      border: 1px solid rgba(10,110,79,.18);
      border-radius: 30px;
      padding: 8px 18px;
      font-size: .85rem;
      font-weight: 600;
      margin-bottom: 22px;
    }
    .checklist {
      text-align: left;
      background: var(--bg-page);
      border-radius: var(--border-radius-sm);
      padding: 14px 18px;
      margin-bottom: 22px;
      list-style: none;
    }
    .checklist li {
      font-size: .83rem;
      color: var(--text-secondary);
      padding: 5px 0;
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .checklist li::before {
      content: '';
      width: 18px; height: 18px;
      border-radius: 50%;
      background: var(--clr-primary-dim);
      color: var(--clr-primary);
      font-size: .7rem;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .checklist li:nth-child(1)::before { content: '1'; }
    .checklist li:nth-child(2)::before { content: '2'; }
    .checklist li:nth-child(3)::before { content: '3'; }

    /* ── Timer ── */
    .resend-row {
      font-size: .82rem;
      color: var(--text-muted);
      margin-top: 14px;
    }
    .resend-btn {
      background: none; border: none;
      color: var(--clr-primary);
      font-size: .82rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: underline;
      padding: 0;
    }
    .resend-btn:disabled { opacity: .45; cursor: not-allowed; text-decoration: none; }

    /* ── Role tabs ── */
    .role-tab-bar {
      display: flex; gap: 4px;
      background: var(--bg-page);
      border-radius: var(--border-radius-sm);
      padding: 4px;
      margin-bottom: 22px;
    }
    .role-tab {
      flex: 1; text-align: center; padding: 9px 12px;
      border-radius: 6px; cursor: pointer;
      font-size: .84rem; font-weight: 500;
      color: var(--text-muted);
      transition: var(--transition);
      border: none; background: transparent;
    }
    .role-tab.active {
      background: var(--bg-card);
      color: var(--clr-primary);
      font-weight: 600;
      box-shadow: var(--shadow-sm);
    }
  </style>
</head>
<body>
<div class="auth-page">

  <!-- Left panel -->
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-mark">🔑</div>
      <h1>Forgot Password?</h1>
      <p>No worries — we'll email you a secure link to reset your password in seconds.</p>
      <div class="auth-features">
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Reset link sent to your inbox</span>
        </div>
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Link expires after 60 minutes</span>
        </div>
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Works for users &amp; pharmacies</span>
        </div>
        <div class="auth-feature-item">
          <div class="check">✓</div>
          <span>Secure — powered by PHPMailer</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right panel -->
  <div class="auth-right">
    <div class="auth-form-wrap">

      <!-- Top bar -->
      <div class="flex-between mb">
        <a href="login.php" style="color:var(--text-muted);font-size:.85rem;display:flex;align-items:center;gap:5px">
          ← Back to Login
        </a>
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
      </div>

      <!-- ═══ FORM SCREEN ═══ -->
      <div id="formScreen">
        <div class="auth-header">
          <h2>Reset Password</h2>
          <p>Enter your registered email and we'll send you a reset link.</p>
        </div>

        <!-- Role selector -->
        <div class="role-tab-bar">
          <button class="role-tab active" onclick="selectRole('user')"    id="tab-user">👤 User</button>
          <button class="role-tab"        onclick="selectRole('pharmacy')" id="tab-pharmacy">🏥 Pharmacy</button>
        </div>

        <div id="alertBox"></div>

        <form id="forgotForm" novalidate>
          <input type="hidden" id="roleInput" value="user">

          <div class="form-group">
            <label for="emailInput">Registered Email Address <span class="required">*</span></label>
            <div class="search-wrap">
              <span class="search-icon" style="font-size:.95rem">✉️</span>
              <input type="email" class="form-control" id="emailInput"
                     placeholder="you@example.com" required autocomplete="email"
                     style="padding-left:38px">
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full btn-lg" id="submitBtn">
            Send Reset Link
          </button>
        </form>

        <div class="auth-link" style="margin-top:18px">
          Remembered it? <a href="login.php">Sign in →</a>
        </div>
        <div class="auth-link" style="margin-top:8px">
          New here? <a href="register.php">Create account →</a>
        </div>
      </div>

      <!-- ═══ SENT CONFIRMATION SCREEN ═══ -->
      <div id="sentScreen" class="sent-screen">
        <div class="sent-icon">📬</div>
        <h3>Check Your Inbox!</h3>
        <p>We sent a password reset link to:</p>
        <div class="sent-email-badge">
          <span>✉️</span>
          <span id="sentEmailDisplay"></span>
        </div>

        <ul class="checklist">
          <li>Open the email from <strong>MediFindr</strong></li>
          <li>Click <strong>"Reset My Password"</strong> in the email</li>
          <li>Set your new password on the next page</li>
        </ul>

        <div style="background:var(--clr-warning-lt);border-radius:var(--border-radius-sm);padding:11px 14px;font-size:.8rem;color:var(--clr-warning);margin-bottom:18px;text-align:left">
          📁 <strong>Can't find it?</strong> Check your <strong>Spam</strong> or <strong>Junk</strong> folder. The email arrives within a minute.
        </div>

        <div style="background:var(--clr-info-lt);border-radius:var(--border-radius-sm);padding:11px 14px;font-size:.8rem;color:var(--clr-info);margin-bottom:20px;text-align:left">
          ⏱ <strong>Link expires in 60 minutes.</strong> Request a new one below if it expires.
        </div>

        <div class="resend-row">
          Didn't receive it?
          <button class="resend-btn" id="resendBtn" disabled onclick="resendLink()">
            Resend in <span id="countdown">60</span>s
          </button>
        </div>

        <div style="margin-top:22px;display:flex;flex-direction:column;gap:10px">
          <a href="login.php" class="btn btn-primary w-full">← Back to Login</a>
          <button onclick="resetToForm()" class="btn btn-ghost w-full">Use a Different Email</button>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
let sentEmail = '';
let countdownTimer = null;

// ── Role tabs ──────────────────────────────────────────────────────────────────
function selectRole(role) {
  document.getElementById('roleInput').value = role;
  document.getElementById('tab-user').classList.toggle('active',     role === 'user');
  document.getElementById('tab-pharmacy').classList.toggle('active', role === 'pharmacy');
}

// ── Submit ─────────────────────────────────────────────────────────────────────
document.getElementById('forgotForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn      = document.getElementById('submitBtn');
  const email    = document.getElementById('emailInput').value.trim();
  const role     = document.getElementById('roleInput').value;
  const alertBox = document.getElementById('alertBox');
  alertBox.innerHTML = '';

  if (!email) {
    alertBox.innerHTML = alertHtml('error', 'Please enter your email address.');
    return;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    alertBox.innerHTML = alertHtml('error', 'Please enter a valid email address.');
    return;
  }

  Form.setLoading(btn, true, 'Sending…');
  const res = await API.post('../api/auth.php?action=forgot', { email, role });
  Form.setLoading(btn, false);

  if (res.success) {
    sentEmail = email;
    showSentScreen(email);
  } else {
    alertBox.innerHTML = alertHtml('error', res.message || 'Something went wrong. Please try again.');
  }
});

// ── Show confirmation screen ───────────────────────────────────────────────────
function showSentScreen(email) {
  document.getElementById('formScreen').style.display = 'none';
  document.getElementById('sentScreen').style.display  = 'block';
  document.getElementById('sentEmailDisplay').textContent = email;
  startCountdown(60);
}

function resetToForm() {
  clearInterval(countdownTimer);
  document.getElementById('sentScreen').style.display  = 'none';
  document.getElementById('formScreen').style.display  = 'block';
  document.getElementById('alertBox').innerHTML        = '';
  document.getElementById('emailInput').value          = '';
  document.getElementById('emailInput').focus();
}

// ── Countdown + resend ─────────────────────────────────────────────────────────
function startCountdown(seconds) {
  const btn   = document.getElementById('resendBtn');
  const timer = document.getElementById('countdown');
  let   left  = seconds;

  btn.disabled = true;
  timer.textContent = left;
  btn.textContent = `Resend in ${left}s`;

  clearInterval(countdownTimer);
  countdownTimer = setInterval(() => {
    left--;
    if (left <= 0) {
      clearInterval(countdownTimer);
      btn.disabled     = false;
      btn.textContent  = 'Resend email';
    } else {
      btn.textContent = `Resend in ${left}s`;
    }
  }, 1000);
}

async function resendLink() {
  const email = sentEmail;
  const role  = document.getElementById('roleInput').value;
  if (!email) return;

  const btn = document.getElementById('resendBtn');
  btn.disabled    = true;
  btn.textContent = 'Sending…';

  const res = await API.post('../api/auth.php?action=forgot', { email, role });

  if (res.success) {
    Toast.success('Reset link resent! Check your inbox.');
    startCountdown(60);
  } else {
    Toast.error(res.message || 'Failed to resend. Please try again.');
    btn.disabled    = false;
    btn.textContent = 'Resend email';
  }
}

// ── Helpers ────────────────────────────────────────────────────────────────────
function alertHtml(type, msg) {
  const icon = type === 'success' ? '✓' : '⚠';
  return `<div class="alert alert-${type}"><span class="alert-icon">${icon}</span><div class="alert-body"><div class="alert-msg">${msg}</div></div></div>`;
}
</script>
</body>
</html>
