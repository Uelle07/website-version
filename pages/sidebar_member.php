<?php
/**
 * pages/sidebar_member.php
 * -------------------------------------------------------
 * Sidebar links for logged-in MEMBERS.
 * Add a link here whenever Module 2/3/4 adds a new page.
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('sidebar_member.php cannot be accessed directly.');
}

$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<div class="sidebar-brand d-none d-lg-block">
    <i class="bi bi-people-fill"></i> Member Menu
</div>
<ul class="nav flex-column app-sidebar-nav">
    <li class="nav-item">
        <a class="nav-link <?= nav_active('dashboard.php', $currentPage) ?>" href="<?= BASE_URL ?>/member/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= nav_active('profile.php', $currentPage) ?>" href="<?= BASE_URL ?>/member/profile.php">
            <i class="bi bi-person-badge"></i> My Profile
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= nav_active('update_info.php', $currentPage) ?>" href="<?= BASE_URL ?>/member/update_info.php">
            <i class="bi bi-pencil-square"></i> Update Information
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= (nav_active('surveys.php', $currentPage) || nav_active('take_survey.php', $currentPage) || nav_active('survey_success.php', $currentPage)) ? 'active' : '' ?>" href="<?= BASE_URL ?>/member/surveys.php">
            <i class="bi bi-ui-checks"></i> Cooperative Surveys
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= nav_active('change_password.php', $currentPage) ?>" href="<?= BASE_URL ?>/member/change_password.php">
            <i class="bi bi-shield-lock"></i> Change Password
        </a>
    </li>
    <li class="nav-item mt-3">
        <a class="nav-link text-danger" href="<?= BASE_URL ?>/logout.php" onclick="event.preventDefault(); openMemberLogoutModal();">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </li>
</ul>
