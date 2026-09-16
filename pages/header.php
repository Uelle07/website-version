<?php
/**
 * pages/header.php
 * -------------------------------------------------------
 * Shared page head + top navbar.
 *
 * The including page MUST already have required config/init.php
 * and, optionally, set $pageTitle before including this file:
 *
 *     $pageTitle = 'My Profile';
 *     require_once __DIR__ . '/../pages/header.php';
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('header.php cannot be accessed directly.');
}

$pageTitle = $pageTitle ?? 'Cooperative Member Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · Cooperative Member Portal</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts: Plus Jakarta Sans, Inter, Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!-- Color scheme & theme bootstrapper (prevents FOUC) -->
    <meta name="color-scheme" content="dark light">
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('portal_theme') || 'dark';
                const savedAccent = localStorage.getItem('portal_accent') || 'nebula';
                document.documentElement.setAttribute('data-theme', savedTheme);
                document.documentElement.setAttribute('data-accent', savedAccent);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-accent', 'nebula');
            }
        })();
    </script>
    <!-- App styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/dashboard.css"> 
</head>
<body class="portal-body">

<!-- ================= 3D COSMIC STARFIELD BACKGROUND CANVAS ================= -->
<canvas id="admin-space-canvas" aria-hidden="true"></canvas>

<?php if (is_logged_in()): ?>
    <?php require_once __DIR__ . '/navbar.php'; ?>

    <div class="app-layout">
        <!-- Sidebar: offcanvas on mobile, fixed column on desktop (see style.css) -->
        <div class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="appSidebar">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar"></button>
            </div>
            <div class="offcanvas-body d-block p-0">
                <?php if (is_member()): ?>
                    <?php require_once __DIR__ . '/sidebar_member.php'; ?>
                <?php elseif (is_staff()): ?>
                    <?php require_once __DIR__ . '/sidebar_staff.php'; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="app-content">
<?php endif; ?>

<main class="app-main">
    <div class="container-fluid py-4">
        <?php render_flash_messages(); ?>
