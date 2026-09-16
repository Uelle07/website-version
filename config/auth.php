<?php
/**
 * auth.php
 * -------------------------------------------------------
 * Everything related to "who is logged in and what can they do".
 *
 * Depends on: session.php (must already be started) and
 * database.php (needs $pdo).
 *
 * Session shape used across the app:
 *   $_SESSION['user_type']   -> 'member' | 'staff'
 *   $_SESSION['user_id']     -> member_id or staff_id
 *   $_SESSION['username']    -> display name
 *   $_SESSION['role']        -> only set for staff ('staff' | 'admin')
 * -------------------------------------------------------
 */

/**
 * Attempts to log a MEMBER in using account_number + password.
 * Members log in with their Account Number; the password is
 * checked against member_account.password_hash.
 *
 * Returns true on success, false on bad credentials.
 */
function authenticate_member(PDO $pdo, string $identifier, string $password, ?string &$authError = null): bool
{
    $authError = null;

    $stmt = $pdo->prepare(
        "SELECT m.member_id, m.account_number, m.firstname, m.lastname, m.account_status,
                ma.account_id, ma.username, ma.password, ma.first_login, ma.password_changed,
                ma.failed_attempts, ma.locked_until,
                TIMESTAMPDIFF(SECOND, NOW(), ma.locked_until) AS lock_seconds_left
         FROM member m
         INNER JOIN member_account ma ON ma.member_id = m.member_id
         WHERE m.account_number = ? OR ma.username = ?
         LIMIT 1"
    );
    $stmt->execute([$identifier, $identifier]);
    $account = $stmt->fetch();

    if (!$account) {
        return false;
    }

    // Check if account is inactive / removed
    if (strtolower($account['account_status'] ?? '') === 'inactive' || strtolower($account['account_status'] ?? '') === 'deleted') {
        $authError = "Your account is currently inactive. Please contact the cooperative administrator.";
        return false;
    }

    // 1. Check if account is currently locked out
    $isCurrentlyLocked = (!empty($account['locked_until']) && (int)($account['lock_seconds_left'] ?? 0) > 0);
    $isLockExpired     = (!empty($account['locked_until']) && (int)($account['lock_seconds_left'] ?? 0) <= 0);

    if ($isCurrentlyLocked) {
        $remainingMinutes = (int) ceil((int)$account['lock_seconds_left'] / 60);
        $timeStr = $remainingMinutes > 1 ? "{$remainingMinutes} minutes" : "1 minute";
        $authError = "Too many failed attempts. Account is temporarily locked. Please try again in {$timeStr}.";
        return false;
    }

    // 2. Verify password
    if (!password_verify($password, $account['password'])) {
        // If the lockout has passed, restart the failed attempt counter from 0
        $currentAttempts = $isLockExpired ? 0 : (int)$account['failed_attempts'];
        $newAttempts     = $currentAttempts + 1;

        if ($newAttempts >= 5) {
            $lockStmt = $pdo->prepare(
                "UPDATE member_account 
                 SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
                 WHERE member_id = ?"
            );
            $lockStmt->execute([$newAttempts, $account['member_id']]);

            $upStatus = $pdo->prepare("UPDATE member SET account_status = 'Flagged' WHERE member_id = ?");
            $upStatus->execute([$account['member_id']]);

            $authError = "Too many failed attempts. Account is temporarily locked. Please try again in 15 minutes.";
        } else {
            $upAttempts = $pdo->prepare("UPDATE member_account SET failed_attempts = ?, locked_until = NULL WHERE member_id = ?");
            $upAttempts->execute([$newAttempts, $account['member_id']]);
            $authError = "Invalid Account Number/Username or password.";
        }
        return false;
    }

    // 3. Successful login -> Reset failed attempts and clear lockout
    $update = $pdo->prepare(
        "UPDATE member_account 
         SET last_login = NOW(), failed_attempts = 0, locked_until = NULL 
         WHERE member_id = ?"
    );
    $update->execute([$account['member_id']]);

    $_SESSION['user_type']       = ROLE_MEMBER;
    $_SESSION['user_id']         = (int) $account['member_id'];
    $_SESSION['username']        = $account['firstname'] . ' ' . $account['lastname'];
    $_SESSION['account_number']  = $account['account_number'];
    $_SESSION['first_login']     = (bool) $account['first_login'];
    $_SESSION['password_changed']= (bool) $account['password_changed'];

    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
    return true;
}

/**
 * Attempts to log a STAFF member in using username + password.
 */
function authenticate_staff(PDO $pdo, string $username, string $password, ?string &$authError = null): bool
{
    $authError = null;

    $stmt = $pdo->prepare(
        "SELECT staff_id, username, password, fullname, role, profile_photo,
                failed_attempts, locked_until,
                TIMESTAMPDIFF(SECOND, NOW(), locked_until) AS lock_seconds_left
         FROM staff WHERE username = ? LIMIT 1"
    );
    $stmt->execute([$username]);
    $staff = $stmt->fetch();

    if (!$staff) {
        return false;
    }

    // 1. Check if staff account is currently locked out
    $isCurrentlyLocked = (!empty($staff['locked_until']) && (int)($staff['lock_seconds_left'] ?? 0) > 0);
    $isLockExpired     = (!empty($staff['locked_until']) && (int)($staff['lock_seconds_left'] ?? 0) <= 0);

    if ($isCurrentlyLocked) {
        $remainingMinutes = (int) ceil((int)$staff['lock_seconds_left'] / 60);
        $timeStr = $remainingMinutes > 1 ? "{$remainingMinutes} minutes" : "1 minute";
        $authError = "Too many failed attempts. Account is temporarily locked. Please try again in {$timeStr}.";
        return false;
    }

    // 2. Verify password
    if (!password_verify($password, $staff['password'])) {
        // If the lockout has passed, restart the failed attempt counter from 0
        $currentAttempts = $isLockExpired ? 0 : (int)$staff['failed_attempts'];
        $newAttempts     = $currentAttempts + 1;

        if ($newAttempts >= 5) {
            $lockStmt = $pdo->prepare(
                "UPDATE staff 
                 SET failed_attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
                 WHERE staff_id = ?"
            );
            $lockStmt->execute([$newAttempts, $staff['staff_id']]);
            $authError = "Too many failed attempts. Account is temporarily locked. Please try again in 15 minutes.";
        } else {
            $upAttempts = $pdo->prepare("UPDATE staff SET failed_attempts = ?, locked_until = NULL WHERE staff_id = ?");
            $upAttempts->execute([$newAttempts, $staff['staff_id']]);
            $authError = "Invalid Account Number/Username or password.";
        }
        return false;
    }

    // 3. Successful login -> Reset failed attempts and clear lockout
    $update = $pdo->prepare(
        "UPDATE staff 
         SET failed_attempts = 0, locked_until = NULL 
         WHERE staff_id = ?"
    );
    $update->execute([$staff['staff_id']]);

    $_SESSION['user_type']     = ROLE_STAFF;
    $_SESSION['user_id']       = (int) $staff['staff_id'];
    $_SESSION['username']      = $staff['fullname'];
    $_SESSION['role']          = $staff['role']; // 'staff' or 'admin'
    $_SESSION['profile_photo'] = $staff['profile_photo'] ?? null;

    if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
        session_regenerate_id(true);
    }
    return true;
}

/** Destroys the session completely (used by logout.php). */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_type']) && !empty($_SESSION['user_id']);
}

function is_member(): bool
{
    return is_logged_in() && $_SESSION['user_type'] === ROLE_MEMBER;
}

function is_staff(): bool
{
    return is_logged_in() && $_SESSION['user_type'] === ROLE_STAFF;
}

/** Staff-only: true if the logged-in staff account has the 'admin' role. */
function is_admin(): bool
{
    return is_staff() && ($_SESSION['role'] ?? '') === ROLE_ADMIN;
}

/**
 * Call at the top of any MEMBER-only page:
 *     require_once __DIR__ . '/../config/auth.php';
 *     require_member();
 */
function require_member(): void
{
    if (!is_member()) {
        set_flash('warning', 'Please log in as a member to continue.');
        redirect('login.php');
    }
}

/**
 * Call at the top of any STAFF-only page.
 * Pass 'admin' to additionally require the admin role.
 */
function require_staff(?string $mustBeRole = null): void
{
    if (!is_staff()) {
        set_flash('warning', 'Please log in as staff to continue.');
        redirect('login.php');
    }
    if ($mustBeRole !== null && ($_SESSION['role'] ?? '') !== $mustBeRole) {
        set_flash('error', 'You do not have permission to view that page.');
        redirect('staff/dashboard.php');
    }
}

/** Returns the id of whoever is currently logged in, or null. */
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}
