<?php
/**
 * pages/navbar.php
 * -------------------------------------------------------
 * Top navigation bar only (the <nav> element). The sidebar
 * offcanvas is opened/closed by header.php/footer.php so the
 * overall <div class="app-layout"> markup stays in one place.
 * Only included by header.php when someone is logged in.
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('navbar.php cannot be accessed directly.');
}
?>
<nav class="navbar navbar-expand-lg app-navbar sticky-top glass-navbar">
    <div class="container-fluid px-3">
        <button class="btn sidebar-toggle d-lg-none me-2 text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-label="Toggle menu">
            <i class="bi bi-list fs-4"></i>
        </button>

       <a class="navbar-brand d-flex align-items-center fw-bold brand-link"
          href="<?= BASE_URL ?>/index.php"
          style="font-family:'Poppins',sans-serif; letter-spacing:.5px;">

            <div class="logo-orb-wrapper me-2">
                <img src="<?= BASE_URL ?>/assets/images/space-logo.png"
                     alt="SPACE Logo"
                     class="space-logo">
            </div>

            <span class="portal-brand-title"><?= is_member() ? 'SPACE Member Portal' : 'SPACE Cooperative Portal' ?></span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn user-menu-btn dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="user-avatar-dot"><i class="fa-solid fa-user-astronaut"></i></span> 
                    <span class="user-name-text"><?= e($_SESSION['username'] ?? '') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown-menu shadow border-0 py-2">
                    <li class="dropdown-header px-3 py-1 text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.8px; opacity: 0.65;">
                        Member Options
                    </li>
                    <?php if (is_member()): ?>
                        <li><a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/member/profile.php"><i class="fa-solid fa-id-badge text-cyan"></i> <span>My Profile</span></a></li>
                        <li><a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/member/change_password.php"><i class="fa-solid fa-key text-purple"></i> <span>Change Password</span></a></li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item py-2 px-3 d-flex align-items-center gap-2" href="javascript:void(0);" onclick="openSettingsModal();">
                            <i class="fa-solid fa-gear text-accent-glow"></i> <span>Settings &amp; Theme</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider opacity-25"></li>
                    <li><a class="dropdown-item text-danger py-2 px-3 d-flex align-items-center gap-2" href="<?= BASE_URL ?>/logout.php" onclick="event.preventDefault(); openMemberLogoutModal();">
                        <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>