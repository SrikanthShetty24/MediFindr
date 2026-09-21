<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — MediFindr</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    /* ── Terms checkbox row ── */
    .terms-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 14px;
      background: var(--bg-page);
      border: 1.5px solid var(--border-color);
      border-radius: var(--border-radius-sm);
      margin-bottom: 20px;
      transition: border-color .2s;
    }
    .terms-row.accepted { border-color: var(--clr-accent); background: var(--clr-primary-dim); }
    .terms-row input[type=checkbox] {
      accent-color: var(--clr-primary);
      width: 17px; height: 17px;
      cursor: pointer;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .terms-row label {
      font-size: .84rem;
      color: var(--text-secondary);
      cursor: pointer;
      font-weight: 400;
      line-height: 1.5;
    }
    .terms-row label a {
      color: var(--clr-primary);
      font-weight: 600;
      text-decoration: underline;
    }

    /* ── Terms modal content ── */
    .terms-content {
      max-height: 420px;
      overflow-y: auto;
      font-size: .87rem;
      line-height: 1.75;
      color: var(--text-secondary);
    }
    .terms-content h4 {
      font-size: .95rem;
      font-weight: 700;
      color: var(--text-primary);
      margin: 18px 0 6px;
    }
    .terms-content h4:first-child { margin-top: 0; }
    .terms-content p  { margin-bottom: 10px; }
    .terms-content ul { padding-left: 18px; margin-bottom: 10px; }
    .terms-content ul li { margin-bottom: 5px; }
    .terms-content .highlight {
      background: var(--clr-warning-lt);
      border-left: 3px solid var(--clr-warning);
      border-radius: 4px;
      padding: 8px 12px;
      color: var(--clr-warning);
      font-size: .82rem;
      margin: 12px 0;
    }

    /* ── Scroll indicator ── */
    .scroll-hint {
      text-align: center;
      font-size: .75rem;
      color: var(--text-muted);
      padding: 8px;
      border-top: 1px solid var(--border-color);
      margin-top: 8px;
    }
  </style>
</head>
<body>
<div class="auth-page">
  <!-- Left panel -->
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo-mark">💊</div>
      <h1>Join MediFindr</h1>
      <p>Create your account and start finding medicines faster than ever before.</p>
      <div class="auth-features">
        <div class="auth-feature-item"><div class="check">✓</div><span>Free to join — always</span></div>
        <div class="auth-feature-item"><div class="check">✓</div><span>Search 500+ medicines instantly</span></div>
        <div class="auth-feature-item"><div class="check">✓</div><span>Pharmacy listings near you</span></div>
        <div class="auth-feature-item"><div class="check">✓</div><span>Smart composition-based alternatives</span></div>
      </div>
    </div>
  </div>

  <!-- Right panel -->
  <div class="auth-right">
    <div class="auth-form-wrap">
      <div class="flex-between mb">
        <a href="../index.php" style="color:var(--text-muted);font-size:.85rem">← Back to Home</a>
        <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()">🌙</button>
      </div>

      <div class="auth-header">
        <h2>Create Account</h2>
        <p>Choose your account type to get started.</p>
      </div>

      <!-- Role tabs -->
      <div class="auth-tabs">
        <div class="auth-tab active" data-role="user"     onclick="selectRole('user')">👤 User</div>
        <div class="auth-tab"        data-role="pharmacy" onclick="selectRole('pharmacy')">🏥 Pharmacy</div>
      </div>

      <div id="alertBox"></div>

      <!-- ═══ USER FORM ═══ -->
      <form id="userForm" novalidate style="display:block">
        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" class="form-control" name="full_name" placeholder="Your full name" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" class="form-control" name="email" placeholder="you@email.com" required>
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="tel" class="form-control" name="phone" placeholder="10-digit number">
          </div>
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <div style="position:relative">
            <input type="password" class="form-control" name="password" id="userPwd"
                   placeholder="Min. 8 chars, letters + numbers" required style="padding-right:44px">
            <button type="button" onclick="togglePwd('userPwd')"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
          </div>
          <div class="form-hint">Minimum 8 characters with at least one letter and one number.</div>
        </div>

        <!-- Terms & Conditions checkbox -->
        <div class="terms-row" id="userTermsRow">
          <input type="checkbox" id="userTermsCheck" onchange="onTermsChange('userTermsRow', 'userTermsCheck', 'userSubmitBtn')">
          <label for="userTermsCheck">
            I have read and agree to MediFindr's
            <a href="#" onclick="openTerms(event)">Terms &amp; Conditions</a>
            and
            <a href="#" onclick="openPrivacy(event)">Privacy Policy</a>.
            I understand that MediFindr is an information platform only and is
            <strong>not responsible</strong> for any medical decisions made based on the information provided.
          </label>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg" id="userSubmitBtn" disabled>
          Create Account
        </button>
      </form>

      <!-- ═══ PHARMACY FORM ═══ -->
      <form id="pharmacyForm" novalidate style="display:none">
        <div class="form-row">
          <div class="form-group">
            <label>Pharmacy Name <span class="required">*</span></label>
            <input type="text" class="form-control" name="pharmacy_name" placeholder="Apollo Pharmacy…" required>
          </div>
          <div class="form-group">
            <label>Owner / Manager Name <span class="required">*</span></label>
            <input type="text" class="form-control" name="owner_name" placeholder="Full name" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email <span class="required">*</span></label>
            <input type="email" class="form-control" name="email" placeholder="pharmacy@email.com" required>
          </div>
          <div class="form-group">
            <label>Phone <span class="required">*</span></label>
            <input type="tel" class="form-control" name="phone" placeholder="10-digit number" required>
          </div>
        </div>
        <div class="form-group">
          <label>Full Address <span class="required">*</span></label>
          <textarea class="form-control" name="address" placeholder="Street address, area…" rows="2" required></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>City <span class="required">*</span></label>
            <input type="text" class="form-control" name="city" placeholder="City" required>
          </div>
          <div class="form-group">
            <label>State</label>
            <input type="text" class="form-control" name="state" placeholder="State">
          </div>
          <div class="form-group">
            <label>Pincode</label>
            <input type="text" class="form-control" name="pincode" placeholder="6-digit" maxlength="6">
          </div>
        </div>
        <div class="form-group">
          <label>Drug License Number <span class="required">*</span></label>
          <input type="text" class="form-control" name="license_number" placeholder="e.g. KA-2024-APL-001" required>
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <div style="position:relative">
            <input type="password" class="form-control" name="password" id="pharmPwd"
                   placeholder="Min. 8 chars, letters + numbers" required style="padding-right:44px">
            <button type="button" onclick="togglePwd('pharmPwd')"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">👁</button>
          </div>
        </div>

        <div style="background:var(--clr-warning-lt);border-radius:var(--border-radius-sm);padding:12px 14px;margin-bottom:16px;font-size:.82rem;color:var(--clr-warning)">
          ⏳ <strong>Note:</strong> Pharmacy accounts require admin approval before you can log in.
        </div>

        <!-- Terms & Conditions checkbox -->
        <div class="terms-row" id="pharmTermsRow">
          <input type="checkbox" id="pharmTermsCheck" onchange="onTermsChange('pharmTermsRow', 'pharmTermsCheck', 'pharmSubmitBtn')">
          <label for="pharmTermsCheck">
            I have read and agree to MediFindr's
            <a href="#" onclick="openTerms(event)">Terms &amp; Conditions</a>
            and
            <a href="#" onclick="openPrivacy(event)">Privacy Policy</a>.
            I confirm that the pharmacy information provided is accurate and that I am authorised
            to register this pharmacy. I understand MediFindr is <strong>not liable</strong> for
            any inaccurate stock information or medical advice issues.
          </label>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg" id="pharmSubmitBtn" disabled>
          Submit Registration
        </button>
      </form>

      <div class="auth-link" style="margin-top:18px">
        Already have an account? <a href="login.php">Sign in →</a>
      </div>
    </div>
  </div>
</div>

<!-- ═══ TERMS & CONDITIONS MODAL ═══ -->
<div class="modal-overlay" id="termsModal">
  <div class="modal" style="max-width:620px">
    <div class="modal-header">
      <h3>📋 Terms &amp; Conditions</h3>
      <button class="modal-close" onclick="Modal.close('termsModal')">×</button>
    </div>
    <div class="modal-body">
      <div class="terms-content" id="termsContent">

        <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
          <strong>Last updated:</strong> January 2026 &nbsp;|&nbsp;
          <strong>Effective date:</strong> January 2026
        </p>

        <h4>1. Acceptance of Terms</h4>
        <p>By registering for and using MediFindr ("the Platform", "we", "us"), you agree to be bound
        by these Terms &amp; Conditions. If you do not agree, do not use this platform.</p>

        <h4>2. Nature of the Platform</h4>
        <p>MediFindr is an <strong>information aggregation platform</strong> only. We display
        medicine availability information as provided by registered pharmacies. We do not:</p>
        <ul>
          <li>Sell, supply, or distribute medicines</li>
          <li>Provide medical advice or prescriptions</li>
          <li>Guarantee the accuracy of pharmacy stock information</li>
          <li>Act as an intermediary for medicine transactions</li>
        </ul>

        <div class="highlight">
          ⚠️ <strong>Important Disclaimer:</strong> MediFindr is NOT a substitute for professional
          medical advice. Always consult a licensed pharmacist or physician before taking any medicine.
        </div>

        <h4>3. Limitation of Liability</h4>
        <p>MediFindr and its operators shall <strong>not be held responsible or liable</strong> for:</p>
        <ul>
          <li>Inaccurate, outdated, or incorrect medicine stock information provided by pharmacies</li>
          <li>Any medical decisions made based on information found on this platform</li>
          <li>Any adverse reactions, harm, or injury resulting from medicine use</li>
          <li>Unavailability of medicines at the time of your visit to a pharmacy</li>
          <li>Price discrepancies between what is shown on the platform and actual pharmacy prices</li>
          <li>Technical failures, server downtime, or data loss</li>
        </ul>

        <h4>4. User Responsibilities</h4>
        <ul>
          <li>You must provide accurate information during registration</li>
          <li>You are responsible for verifying medicine availability directly with the pharmacy</li>
          <li>You must not misuse the platform to spread false information</li>
          <li>You must not attempt to hack, scrape, or reverse-engineer the platform</li>
          <li>Reporting false or malicious medicine reports is prohibited</li>
        </ul>

        <h4>5. Pharmacy Responsibilities</h4>
        <ul>
          <li>Pharmacies must provide accurate, up-to-date stock information</li>
          <li>Pharmacies must hold valid drug licenses for their jurisdiction</li>
          <li>Pharmacies are solely responsible for the quality and authenticity of medicines they stock</li>
          <li>Pharmacies must not list counterfeit, expired, or prohibited substances</li>
          <li>MediFindr reserves the right to remove pharmacy listings at any time</li>
        </ul>

        <h4>6. Intellectual Property</h4>
        <p>All content, design, and code of MediFindr is the intellectual property of its creators.
        You may not copy, reproduce, or distribute any part of the platform without written permission.</p>

        <h4>7. Privacy</h4>
        <p>We collect minimal personal data (name, email, phone) solely for account operation.
        We do not sell your data to third parties. Location data (if shared) is used only for
        proximity-based pharmacy filtering and is never stored on our servers.</p>

        <h4>8. Changes to Terms</h4>
        <p>We may update these Terms at any time. Continued use of the platform after changes
        constitutes your acceptance of the updated Terms.</p>

        <h4>9. Governing Law</h4>
        <p>These Terms shall be governed by the laws of India. Any disputes shall be subject to
        the jurisdiction of courts in Mangalore, Karnataka, India.</p>

        <h4>10. Contact</h4>
        <p>For any queries regarding these Terms, contact us at:
        <strong>support@medifindr.com</strong></p>

      </div>
      <div class="scroll-hint">↕ Scroll to read the full terms</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('termsModal')">Close</button>
      <button class="btn btn-primary" onclick="acceptTermsFromModal()">I Agree &amp; Accept →</button>
    </div>
  </div>
</div>

<!-- ═══ PRIVACY POLICY MODAL ═══ -->
<div class="modal-overlay" id="privacyModal">
  <div class="modal" style="max-width:620px">
    <div class="modal-header">
      <h3>🔒 Privacy Policy</h3>
      <button class="modal-close" onclick="Modal.close('privacyModal')">×</button>
    </div>
    <div class="modal-body">
      <div class="terms-content">

        <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
          <strong>Last updated:</strong> January 2026
        </p>

        <h4>1. Information We Collect</h4>
        <ul>
          <li><strong>Account data:</strong> name, email address, phone number</li>
          <li><strong>Pharmacy data:</strong> pharmacy name, address, license number, GPS coordinates</li>
          <li><strong>Usage data:</strong> search terms (stored for search history feature)</li>
          <li><strong>Location data:</strong> GPS coordinates (if you grant permission) — used only for proximity filtering, never stored</li>
        </ul>

        <h4>2. How We Use Your Information</h4>
        <ul>
          <li>To operate your account and provide platform features</li>
          <li>To send password reset emails (only when requested)</li>
          <li>To send low-stock alerts to pharmacy owners</li>
          <li>To display your search history to you</li>
          <li>To show your pharmacy on the map to users searching for medicines</li>
        </ul>

        <h4>3. Data We Do NOT Collect</h4>
        <ul>
          <li>We do not store your device GPS location permanently</li>
          <li>We do not track your browsing behaviour outside our platform</li>
          <li>We do not use cookies for advertising</li>
          <li>We do not sell your data to any third party</li>
        </ul>

        <div class="highlight">
          📍 <strong>Location Privacy:</strong> When you grant location access, your coordinates
          are sent to our server only for the duration of your search request and are immediately
          discarded. We never store your GPS location in our database.
        </div>

        <h4>4. Data Security</h4>
        <p>Passwords are hashed using BCrypt. All database queries use prepared statements.
        We implement standard web security headers and access controls.</p>

        <h4>5. Data Retention</h4>
        <p>Your account data is retained as long as your account is active. Search history
        entries older than 90 days are automatically purged. You may request deletion of your
        account at any time by contacting support.</p>

        <h4>6. Your Rights</h4>
        <ul>
          <li>Right to access your personal data</li>
          <li>Right to correct inaccurate data</li>
          <li>Right to request deletion of your account</li>
          <li>Right to withdraw consent at any time</li>
        </ul>

        <h4>7. Contact</h4>
        <p>Privacy queries: <strong>privacy@medifindr.com</strong></p>

      </div>
      <div class="scroll-hint">↕ Scroll to read the full policy</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="Modal.close('privacyModal')">Close</button>
      <button class="btn btn-primary" onclick="acceptTermsFromModal()">I Agree &amp; Accept →</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script src="../assets/js/main.js"></script>
<script>
let currentRole = 'user';
const urlRole   = new URLSearchParams(window.location.search).get('role') || 'user';
selectRole(urlRole);

function selectRole(role) {
  currentRole = role;
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.toggle('active', t.dataset.role === role));
  document.getElementById('userForm').style.display     = role === 'user'     ? 'block' : 'none';
  document.getElementById('pharmacyForm').style.display = role === 'pharmacy' ? 'block' : 'none';
}

function togglePwd(id) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
}

// ── Terms checkbox logic ──────────────────────────────────────
function onTermsChange(rowId, checkId, btnId) {
  const checked = document.getElementById(checkId).checked;
  const row     = document.getElementById(rowId);
  const btn     = document.getElementById(btnId);
  row.classList.toggle('accepted', checked);
  btn.disabled = !checked;
  if (checked) Toast.success('Terms accepted. You can now register.');
}

// Called when user clicks "I Agree" button inside the modal
function acceptTermsFromModal() {
  Modal.closeAll();
  // Tick whichever checkbox belongs to the active role
  if (currentRole === 'user') {
    const chk = document.getElementById('userTermsCheck');
    chk.checked = true;
    onTermsChange('userTermsRow', 'userTermsCheck', 'userSubmitBtn');
  } else {
    const chk = document.getElementById('pharmTermsCheck');
    chk.checked = true;
    onTermsChange('pharmTermsRow', 'pharmTermsCheck', 'pharmSubmitBtn');
  }
}

function openTerms(e) {
  e.preventDefault();
  Modal.open('termsModal');
}
function openPrivacy(e) {
  e.preventDefault();
  Modal.open('privacyModal');
}

// ── Form submissions ──────────────────────────────────────────
async function handleSubmit(form, role) {
  const btn     = form.querySelector('button[type=submit]');
  const alertBox= document.getElementById('alertBox');
  alertBox.innerHTML = '';

  // Double-check terms accepted
  const checkId = role === 'user' ? 'userTermsCheck' : 'pharmTermsCheck';
  if (!document.getElementById(checkId).checked) {
    alertBox.innerHTML = alertHtml('error', 'You must accept the Terms &amp; Conditions to register.');
    return;
  }

  const data  = Form.getData(form);
  data.role   = role;
  data.terms_accepted = true;

  Form.setLoading(btn, true, 'Creating account…');
  const res = await API.post('../api/auth.php?action=register', data);
  Form.setLoading(btn, false);

  if (res.success) {
    alertBox.innerHTML = alertHtml('success', res.message);
    form.reset();
    // Reset checkbox and button
    document.getElementById(checkId).checked = false;
    onTermsChange(
      role === 'user' ? 'userTermsRow' : 'pharmTermsRow',
      checkId,
      role === 'user' ? 'userSubmitBtn' : 'pharmSubmitBtn'
    );
    setTimeout(() => window.location = 'login.php?role=' + role, 2500);
  } else {
    alertBox.innerHTML = alertHtml('error', res.message || 'Registration failed.');
  }
}

document.getElementById('userForm').addEventListener('submit', e => {
  e.preventDefault();
  handleSubmit(e.target, 'user');
});
document.getElementById('pharmacyForm').addEventListener('submit', e => {
  e.preventDefault();
  handleSubmit(e.target, 'pharmacy');
});

function alertHtml(type, msg) {
  const icon = type === 'success' ? '✓' : '⚠';
  return `<div class="alert alert-${type}"><span class="alert-icon">${icon}</span><div class="alert-body"><div class="alert-msg">${msg}</div></div></div>`;
}
</script>
</body>
</html>
