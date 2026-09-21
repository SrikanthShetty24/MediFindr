<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediFindr — Find Medicines Near You</title>
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    .hero { min-height: 100vh; }
    .counter-section {
      padding: 60px 5%;
      background: var(--clr-primary);
      color: white;
    }
    .counters {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 30px;
      max-width: 900px;
      margin: 0 auto;
      text-align: center;
    }
    .counter h2 { font-size: 2.8rem; color: var(--clr-accent); font-family: var(--font-body); font-weight: 700; }
    .counter p  { color: rgba(255,255,255,0.75); font-size: 0.9rem; }
    .cta-section {
      padding: 80px 5%;
      text-align: center;
      background: var(--clr-primary-dim);
    }
    .cta-section h2 { margin-bottom: 12px; }
    .cta-section p  { margin-bottom: 30px; max-width: 500px; margin-inline: auto; margin-bottom: 30px; }
  </style>
</head>
<body>
  <!-- ─── NAVBAR ─── -->
  <nav class="landing-nav" id="navbar">
    <a href="index.php" class="landing-brand">
      <div class="logo-mark">💊</div>
      Medi<span style="color:var(--clr-accent)">Findr</span>
    </a>
    <div class="landing-nav-links">
      <button class="theme-toggle" id="themeToggle" onclick="Theme.toggle()" title="Toggle theme">🌙</button>
      <a href="auth/login.php" class="btn btn-ghost btn-sm">Log In</a>
      <a href="auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
    </div>
  </nav>

  <!-- ─── HERO ─── -->
  <section class="hero">
    <div class="hero-content">
      <div class="hero-tag">🏥 Healthcare Made Simple</div>
      <h1>Find the <span>Right Medicine</span>, Right Now</h1>
      <p class="hero-desc">
        Search medicines across hundreds of local pharmacies. If your medicine isn't available,
        we instantly suggest alternatives with the same composition.
      </p>
      <div class="hero-actions">
        <a href="auth/register.php?role=user" class="btn btn-accent btn-lg">Search Medicines →</a>
        <a href="auth/register.php?role=pharmacy" class="btn btn-outline btn-lg">List Your Pharmacy</a>
      </div>
      <div class="hero-search-box" onclick="document.getElementById('heroSearch').focus()">
        <span style="font-size:1.1rem">🔍</span>
        <input type="text" id="heroSearch" placeholder="Search for Paracetamol, Ibuprofen..." />
        <a href="auth/login.php" class="btn btn-primary" style="border-radius:40px">Search</a>
      </div>
    </div>
  </section>

  <!-- ─── COUNTERS ─── -->
  <section class="counter-section">
    <div class="counters">
      <div class="counter">
        <h2 id="cnt-med">500+</h2>
        <p>Medicines Listed</p>
      </div>
      <div class="counter">
        <h2 id="cnt-pha">120+</h2>
        <p>Partner Pharmacies</p>
      </div>
      <div class="counter">
        <h2 id="cnt-usr">5K+</h2>
        <p>Happy Users</p>
      </div>
      <div class="counter">
        <h2 id="cnt-alt">95%</h2>
        <p>Alternative Match Rate</p>
      </div>
    </div>
  </section>

  <!-- ─── FEATURES ─── -->
  <section class="features" id="features">
    <div class="section-header">
      <h2>Everything You Need</h2>
      <p>A complete medicine availability platform for users, pharmacies and healthcare administrators.</p>
    </div>
    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h3>Smart Search</h3>
        <p>Find medicines by name, composition, or brand. Get results from all nearby pharmacies instantly.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔄</div>
        <h3>Smart Alternatives</h3>
        <p>When your medicine is out of stock, we suggest equivalent alternatives with the same active composition.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📍</div>
        <h3>Pharmacy Locations</h3>
        <p>View pharmacy locations on a map, get addresses, contact numbers, and real-time stock levels.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📦</div>
        <h3>Live Stock Tracking</h3>
        <p>Pharmacies update their inventory in real-time so you always see accurate availability data.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🕐</div>
        <h3>Search History</h3>
        <p>Your last 10 searches are saved for quick re-searches. One click to search again.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔒</div>
        <h3>Secure & Private</h3>
        <p>Your health data is encrypted. We use industry-standard security practices to keep your data safe.</p>
      </div>
    </div>
  </section>

  <!-- ─── HOW IT WORKS ─── -->
  <section class="how-it-works" id="how">
    <div class="section-header">
      <h2>How It Works</h2>
      <p>Finding your medicine takes less than 30 seconds.</p>
    </div>
    <div class="steps-grid">
      <div class="step">
        <div class="step-num">1</div>
        <h4>Create Account</h4>
        <p>Register in seconds with your name and email. No complex forms required.</p>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <h4>Search Medicine</h4>
        <p>Type your medicine name. We search across all partner pharmacies simultaneously.</p>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <h4>View Results</h4>
        <p>See all pharmacies with stock levels, prices, and locations on a map.</p>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <h4>Get Alternatives</h4>
        <p>If unavailable, we suggest medicines with identical composition automatically.</p>
      </div>
    </div>
  </section>

  <!-- ─── ROLES ─── -->
  <section class="roles-section" id="roles">
    <div class="section-header">
      <h2>Who Is MediFindr For?</h2>
    </div>
    <div class="roles-grid">
      <div class="role-card" onclick="window.location='auth/register.php?role=user'">
        <div style="font-size:2.5rem">👤</div>
        <h3>For Users</h3>
        <p>Search medicines, view availability, discover alternatives, track search history.</p>
        <a href="auth/register.php?role=user" class="btn btn-primary btn-sm">Sign Up Free →</a>
      </div>
      <div class="role-card" onclick="window.location='auth/register.php?role=pharmacy'">
        <div style="font-size:2.5rem">🏥</div>
        <h3>For Pharmacies</h3>
        <p>List your pharmacy, manage stock inventory, reach more customers online.</p>
        <a href="auth/register.php?role=pharmacy" class="btn btn-primary btn-sm">Register Pharmacy →</a>
      </div>
      <div class="role-card" onclick="window.location='admin/login.php'">
        <div style="font-size:2.5rem">⚙️</div>
        <h3>For Admins</h3>
        <p>Manage the entire platform, approve pharmacies, maintain medicine database.</p>
        <a href="admin/login.php" class="btn btn-ghost btn-sm">Admin Login →</a>
      </div>
    </div>
  </section>

  <!-- ─── CTA ─── -->
  <section class="cta-section">
    <h2>Ready to Find Your Medicine?</h2>
    <p>Join thousands of users who never waste time searching for medicines anymore.</p>
    <a href="auth/register.php" class="btn btn-primary btn-lg">Create Free Account →</a>
  </section>

  <!-- ─── FOOTER ─── -->
  <footer class="landing-footer">
    <p>© 2026 <span>MediFindr</span>. Built with ❤️ for better healthcare access.</p>
    <p style="margin-top:6px">
      <a href="auth/login.php" style="color:rgba(255,255,255,0.5);margin:0 10px">Login</a>
      <a href="auth/register.php" style="color:rgba(255,255,255,0.5);margin:0 10px">Register</a>
      <a href="admin/login.php" style="color:rgba(255,255,255,0.5);margin:0 10px">Admin</a>
    </p>
  </footer>

  <script src="assets/js/main.js"></script>
  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
      document.getElementById('navbar').style.boxShadow =
        window.scrollY > 20 ? 'var(--shadow-md)' : 'none';
    });

    // Hero search redirect
    document.getElementById('heroSearch').addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        const q = e.target.value.trim();
        window.location = 'auth/login.php' + (q ? '?next=search&q=' + encodeURIComponent(q) : '');
      }
    });
  </script>
</body>
</html>
