<?php
/**
 * pages/sidebar_staff.php
 * -------------------------------------------------------
 * Sidebar links for logged-in STAFF / Admin Portal.
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('sidebar_staff.php cannot be accessed directly.');
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-brand-wrapper">
    <div class="sidebar-brand brand-clickable" onclick="window.location.href='<?= e(BASE_URL) ?>/staff/dashboard.php';" title="Return to Dashboard">
      <div class="logo-frame">
        <div class="logo-card-inner">
          <img src="<?= e(BASE_URL) ?>/assets/images/space-logo.png" alt="SPACE Planet Logo" class="brand-logo">
        </div>
      </div>
      <div class="brand-text-container">
        <span class="brand-text">SPACE</span>
        <span class="brand-subtext">Cooperative Portal</span>
      </div>
    </div>
    <button type="button" class="sidebar-collapse-toggle-btn" id="sidebarCollapseBtn" onclick="toggleSidebarCollapse()" aria-label="Toggle Sidebar Navigation" title="Collapse / Expand Sidebar">
      <i class="fa-solid fa-angles-left toggle-arrow-expanded" id="sidebarToggleIconExpanded" aria-hidden="true"></i>
      <i class="fa-solid fa-angles-right toggle-arrow-collapsed" id="sidebarToggleIconCollapsed" aria-hidden="true"></i>
    </button>
  </div>
  
  <nav class="sidebar-menu">
    <a href="<?= e(BASE_URL) ?>/staff/dashboard.php" class="menu-item <?= nav_active('dashboard.php', $currentPage) ?>" id="nav-dashboard" title="Dashboard">
      <i class="fa-solid fa-gauge-high"></i>
      <span class="menu-label">Dashboard</span>
    </a>
    <a href="<?= e(BASE_URL) ?>/staff/members.php" class="menu-item <?= nav_active('members.php', $currentPage) ?>" id="nav-records" title="Member Records">
      <i class="fa-solid fa-address-book"></i>
      <span class="menu-label">Member Records</span>
    </a>
    <a href="<?= e(BASE_URL) ?>/staff/updated_records.php" class="menu-item <?= nav_active('updated_records.php', $currentPage) ?>" id="nav-audit" title="Audit Log">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <span class="menu-label">Audit Log</span>
    </a>
    <a href="<?= e(BASE_URL) ?>/staff/credentials.php" class="menu-item <?= nav_active('credentials.php', $currentPage) ?>" id="nav-credentials" title="Password Management">
      <i class="fa-solid fa-key"></i>
      <span class="menu-label">Password Management</span>
    </a>
    <a href="<?= e(BASE_URL) ?>/staff/surveys.php" class="menu-item <?= (nav_active('surveys.php', $currentPage) || nav_active('survey_questions.php', $currentPage) || nav_active('survey_responses.php', $currentPage) || nav_active('survey_results.php', $currentPage)) ? 'active' : '' ?>" id="nav-surveys" title="Survey Management">
      <i class="fa-solid fa-square-poll-vertical"></i>
      <span class="menu-label">Survey Management</span>
    </a>
    <a href="<?= e(BASE_URL) ?>/staff/reports.php" class="menu-item <?= nav_active('reports.php', $currentPage) ?>" id="nav-reports" title="Generate Reports">
      <i class="fa-solid fa-chart-pie"></i>
      <span class="menu-label">Generate Reports</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= e(BASE_URL) ?>/logout.php" class="menu-item logout-link" onclick="event.preventDefault(); openAdminLogoutModal();" title="Sign Out">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>
      <span class="menu-label">Logout</span>
    </a>
  </div>
</aside>
