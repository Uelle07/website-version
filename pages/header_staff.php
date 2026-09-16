<?php
/**
 * pages/header_staff.php
 * -------------------------------------------------------
 * Shared HTML Head & Header layout for Staff Portal.
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('header_staff.php cannot be accessed directly.');
}

$pageTitle = $pageTitle ?? 'Staff Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · SPACE Cooperative Staff Portal</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts: Plus Jakarta Sans, Inter, Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Color scheme & theme bootstrapper (prevents FOUC) -->
    <meta name="color-scheme" content="dark light">
    <script>
        (function() {
            try {
                window.BASE_URL = '<?= e(BASE_URL) ?>';
                const savedTheme = localStorage.getItem('portal_theme') || 'dark';
                const savedAccent = localStorage.getItem('portal_accent') || 'nebula';
                document.documentElement.setAttribute('data-theme', savedTheme);
                document.documentElement.setAttribute('data-accent', savedAccent);
                if (localStorage.getItem('admin_sidebar_collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-accent', 'nebula');
            }
        })();
    </script>

    <!-- Shared Dashboard & Glassmorphism Design Tokens -->
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/dashboard.css?v=<?= file_exists(__DIR__ . '/../assets/css/dashboard.css') ? filemtime(__DIR__ . '/../assets/css/dashboard.css') : time() ?>">
    <!-- Admin UI CSS -->
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/admin.css?v=<?= file_exists(__DIR__ . '/../assets/css/admin.css') ? filemtime(__DIR__ . '/../assets/css/admin.css') : time() ?>">
</head>
<body class="portal-body">

  <!-- ================= 3D COSMIC STARFIELD BACKGROUND CANVAS ================= -->
  <canvas id="admin-space-canvas" aria-hidden="true"></canvas>

  <!-- ================= LEFT SIDEBAR NAVIGATION ================= -->
  <?php require_once __DIR__ . '/sidebar_staff.php'; ?>

  <!-- ================= MAIN CONTENT AREA ================= -->
  <main class="main-content">
    
    <!-- Top Navbar -->
    <header class="top-navbar">
      <div class="top-navbar-left d-flex align-items-center">
        <button class="sidebar-mobile-toggle-btn d-lg-none me-2" type="button" onclick="toggleSidebarCollapse()" aria-label="Toggle Navigation Menu">
          <span class="hamburger-box" aria-hidden="true">
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
            <span class="hamburger-bar"></span>
          </span>
        </button>
        <div class="portal-console-heading d-flex align-items-center gap-2">
          <i class="fa-solid fa-shield-halved text-cyan"></i>
          <span>Management Console</span>
        </div>
      </div>

      <div class="top-navbar-right d-flex align-items-center">
        <div class="admin-user-dropdown-container">
          <button class="user-menu-btn" id="adminUserDropdownBtn" type="button" onclick="toggleAdminUserMenu(event)" aria-expanded="false" aria-label="Administrator Menu">
            <div class="user-avatar-sm">
              <?php if (!empty($_SESSION['profile_photo'])): ?>
                <img src="<?= e(photo_url($_SESSION['profile_photo'])) ?>" alt="Admin Avatar" class="top-nav-avatar-img">
              <?php else: ?>
                <i class="fa-solid fa-user-tie"></i>
              <?php endif; ?>
            </div>
            <span class="user-name">System Administrator</span>
            <i class="fa-solid fa-chevron-down caret-icon" id="adminDropdownCaret"></i>
          </button>

          <div id="adminUserDropdownMenu" class="admin-dropdown-menu hidden" onclick="event.stopPropagation();">
            <div class="dropdown-header" style="padding: 0.4rem 0.85rem; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.08em; color: var(--text-dim); text-transform: uppercase;">
              <i class="fa-solid fa-shield-halved" style="color: var(--primary-teal); margin-right: 0.4rem;"></i> Administrator Actions
            </div>
            
            <button type="button" class="dropdown-item" id="adminMenuPhotoBtn" onclick="closeAdminUserMenu(); openAdminPhotoModal();">
              <i class="fa-solid fa-camera-rotate" style="color: #38bdf8; width: 20px; margin-right: 0.6rem;"></i>
              <span>Update Profile Picture</span>
            </button>

            <button type="button" class="dropdown-item" id="adminMenuAddMemberBtn" onclick="closeAdminUserMenu(); openAddMemberModal();">
              <i class="fa-solid fa-user-plus" style="color: #34d399; width: 20px; margin-right: 0.6rem;"></i>
              <span>Quick Register Member</span>
            </button>

            <button type="button" class="dropdown-item" id="adminMenuSettingsBtn" onclick="closeAdminUserMenu(); openSettingsModal();">
              <i class="fa-solid fa-sliders" style="color: #00f2fe; width: 20px; margin-right: 0.6rem;"></i>
              <span>Display &amp; Theme Settings</span>
            </button>

            <a href="<?= e(BASE_URL) ?>/staff/reports.php" class="dropdown-item" id="adminMenuReportsLink" onclick="closeAdminUserMenu();">
              <i class="fa-solid fa-file-export" style="color: #c084fc; width: 20px; margin-right: 0.6rem;"></i>
              <span>Export System Reports</span>
            </a>
          </div>
        </div>
      </div>
    </header>

    <div class="dashboard-container">
      <?php render_flash_messages(); ?>
