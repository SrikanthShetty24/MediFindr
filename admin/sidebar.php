<?php
// Admin Sidebar Partial
$currentFile = basename($_SERVER['PHP_SELF']);
function navLink($href, $icon, $label, $current) {
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
    <?= navLink('dashboard.php',  '📊', 'Dashboard',          $currentFile) ?>

    <div class="sidebar-section">Medicine Moderation</div>
    <?= navLink('inspection.php', '🔬', 'Inspection Panel',   $currentFile) ?>
    <?= navLink('duplicates.php', '🔀', 'Duplicate Medicines', $currentFile) ?>
    <?= navLink('reports.php',    '🚩', 'User Reports',        $currentFile) ?>

    <div class="sidebar-section">Pharmacies</div>
    <?= navLink('pharmacies.php',               '🏥', 'All Pharmacies',  $currentFile) ?>
    <?= navLink('pharmacies.php?status=pending','⏳', 'Pending Approval',$currentFile) ?>

    <div class="sidebar-section">Users</div>
    <?= navLink('users.php', '👥', 'All Users', $currentFile) ?>
  </nav>

  <div class="sidebar-footer">
    <a href="../api/auth.php?action=logout&role=admin">
      <span class="nav-icon">🚪</span> Logout
    </a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
