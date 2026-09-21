<?php
$currentFile = basename($_SERVER['PHP_SELF']);
function userNavLink($href, $icon, $label, $current) {
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
    <?= userNavLink('dashboard.php', '🏠', 'Dashboard',       $currentFile) ?>
    <?= userNavLink('search.php',    '🔍', 'Search Medicines', $currentFile) ?>
    <?= userNavLink('history.php',   '🕐', 'Search History',   $currentFile) ?>
    <?= userNavLink('profile.php',   '👤', 'My Profile',       $currentFile) ?>
  </nav>

  <div class="sidebar-footer">
    <a href="../api/auth.php?action=logout&role=user">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
