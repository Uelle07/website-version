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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging out...</title>
</head>
<body>
    <script type="module">
        import { auth } from '<?= e(BASE_URL) ?>/assets/js/firebase-init.js';
        import { signOut } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
        
        signOut(auth).then(() => {
            window.location.href = 'login.php';
        }).catch((error) => {
            console.error('Sign Out Error', error);
            window.location.href = 'login.php';
        });
    </script>
</body>
</html>

