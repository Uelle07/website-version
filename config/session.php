<?php
/**
 * session.php
 * -------------------------------------------------------
 * Starts a secure PHP session. Include this FIRST, before
 * any HTML/echo output, at the very top of every page:
 *
 *     require_once __DIR__ . '/../config/session.php';
 * -------------------------------------------------------
 */

require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {

    // Harden session cookie settings
    session_set_cookie_params([
        'lifetime' => 0,            // expires when the browser closes
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                      || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
                      || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
                      || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443),
        'httponly' => true,         // JS cannot read the cookie (helps against XSS)
        'samesite' => 'Lax',        // basic CSRF protection for cross-site requests
    ]);

    session_name('coop_portal_session');
    session_start();

    // Regenerate the session ID once per session to guard against session fixation
    if (empty($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// Simple inactivity timeout (30 minutes)
define('SESSION_TIMEOUT', 30 * 60);

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    // Session expired — wipe it and start clean
    $_SESSION = [];
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();
