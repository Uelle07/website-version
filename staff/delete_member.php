<?php
/**
 * staff/delete_member.php
 * -------------------------------------------------------
 * Staff Portal — Delete Member Handler.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $accNo = clean($_POST['account_number'] ?? '');

    // Fetch member by account number
    $stmt = $pdo->prepare("SELECT member_id, firstname, lastname, account_status FROM member WHERE account_number = ?");
    $stmt->execute([$accNo]);
    $member = $stmt->fetch();

    if ($member) {
        $rawStatus = strtolower($member['account_status'] ?? '');
        if ($rawStatus === 'inactive' || $rawStatus === 'deleted') {
            set_flash('error', 'Member account ' . $accNo . ' is already inactive and cannot be deleted.');
            redirect('staff/members.php');
        }

        $memberId = (int) $member['member_id'];

        try {
            $pdo->beginTransaction();

            // Log removal to update_history before marking as inactive
            $hist = $pdo->prepare(
                "INSERT INTO update_history (member_id, updated_by_staff_id, update_type, remarks)
                 VALUES (?, ?, 'Removed Member from Portal', 'Staff removed member record and marked status as Inactive.')"
            );
            $hist->execute([$memberId, current_user_id()]);

            // Set member account_status to Inactive
            $del = $pdo->prepare("UPDATE member SET account_status = 'Inactive' WHERE member_id = ?");
            $del->execute([$memberId]);

            // Clear any active lockout and failed attempts
            $clearLock = $pdo->prepare("UPDATE member_account SET failed_attempts = 0, locked_until = NULL WHERE member_id = ?");
            $clearLock->execute([$memberId]);

            $pdo->commit();

            set_flash('success', 'Member record ' . $accNo . ' removed and labeled as Inactive.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash('error', 'Failed to update member record status.');
        }
    } else {
        set_flash('error', 'Member record not found.');
    }
}

redirect('staff/members.php');
