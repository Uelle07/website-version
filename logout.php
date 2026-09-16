<?php
/**
 * logout.php
 * -------------------------------------------------------
 * Ends the session for whoever is logged in (member or staff)
 * and sends them back to the login page.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/config/init.php';

logout_user();

// Need to start a fresh session just long enough to carry the flash message
session_start();
set_flash('success', 'You have been logged out.');
redirect('login.php');
