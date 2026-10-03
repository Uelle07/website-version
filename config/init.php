<?php
/**
 * init.php
 * -------------------------------------------------------
 * The ONE file every page in the app should require at the
 * very top, before any HTML is echoed:
 *
 *     require_once __DIR__ . '/../config/init.php';
 *
 * It loads everything in the correct order so individual
 * module files never have to think about dependency order.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/constants.php';   // 1. constants first — everything else needs them
require_once __DIR__ . '/session.php';     // 2. session must start before any output
// require_once __DIR__ . '/database.php';    // 3. DB connection ($pdo) - Removed for Firebase
require_once __DIR__ . '/functions.php';   // 4. generic helpers (e(), redirect(), etc.)
require_once __DIR__ . '/flash.php';       // 5. flash messages (uses the session)
require_once __DIR__ . '/validation.php';  // 6. form validators
require_once __DIR__ . '/upload.php';      // 7. file upload helper
require_once __DIR__ . '/auth.php';        // 8. auth helpers (uses session + $pdo)
