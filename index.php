<?php
/**
 * index.php
 * -------------------------------------------------------
 * Entry point. Not a page itself — just routes the visitor
 * to the right place based on session state.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/config/init.php';

if (is_member()) {
    redirect('member/dashboard.php');
} elseif (is_staff()) {
    redirect('staff/dashboard.php');
} else {
    redirect('login.php');
}
