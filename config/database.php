<?php
/**
 * database.php
 * -------------------------------------------------------
 * Single PDO connection for the whole app.
 *
 * Usage in any module file:
 *     require_once __DIR__ . '/../config/database.php';
 *     $stmt = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
 *     $stmt->execute([$memberId]);
 *     $member = $stmt->fetch();
 *
 * Always use prepared statements ( ? or :named placeholders ).
 * Never concatenate user input directly into SQL.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/constants.php';

/** @var PDO $pdo  Available to any file that requires this one */
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // return rows as assoc arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // use real prepared statements
    ]);

    // Synchronize MySQL session timezone with application Asia/Manila timezone
    try {
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (Throwable $tzEx) {
        // Fallback silently if host disallows setting session time zone
    }
} catch (PDOException $e) {
    // Never leak DB credentials or raw error details to the browser
    if (APP_DEBUG) {
        die('Database connection failed: ' . $e->getMessage());
    }
    error_log('DB connection failed: ' . $e->getMessage());
    die('We are unable to connect right now. Please try again later.');
}
