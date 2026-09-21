<?php
$currentFile = basename($_SERVER['PHP_SELF']);
function pharmNavLink($href, $icon, $label, $current) {
    $active = ($current === basename($href)) ? 'active' : '';
    return "<a href=\"$href\" class=\"$active\"><span class=\"nav-icon\">$icon</span>$label</a>";
}
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">💊</div>
    <h1>Medi<span>Findr</span></h1>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <?= pharmNavLink('dashboard.php', '📊', 'Dashboard',  $currentFile) ?>
    <?= pharmNavLink('stock.php',     '📦', 'My Stock',   $currentFile) ?>

    <div class="sidebar-section">Transactions</div>
    <?= pharmNavLink('billing.php',   '🧾', 'Billing',    $currentFile) ?>

    <div class="sidebar-section">Account</div>
    <?= pharmNavLink('profile.php',   '🏥', 'Profile',    $currentFile) ?>
  </nav>

  <div class="sidebar-footer">
    <a href="../api/auth.php?action=logout&role=pharmacy">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
