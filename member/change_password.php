<?php
/**
 * member/change_password.php
 * -------------------------------------------------------
 * MODULE 1 (Authentication) — Change Password
 * Handles user password modification logic, validation checks,
 * and first-login security updates.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_member();

$errors = [];
$fieldErrors = [
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => '',
];

// Handle form submission request via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Fetch the current password hash from database for the active user session
    $stmt = $pdo->prepare("SELECT password FROM member_account WHERE member_id = ?");
    $stmt->execute([current_user_id()]);
    $hash = $stmt->fetchColumn();

    // 1. Validate Current Password
    if (trim($currentPassword) === '') {
        $fieldErrors['current_password'] = 'Current password is required.';
        $errors[] = 'Current password is required.';
    } elseif (!$hash || !password_verify($currentPassword, $hash)) {
        $fieldErrors['current_password'] = 'Current password is incorrect.';
        $errors[] = 'Current password is incorrect.';
    }

    // 2. Validate New Password
    if (trim($newPassword) === '') {
        $fieldErrors['new_password'] = 'New password is required.';
        $errors[] = 'New password is required.';
    } elseif (strlen($newPassword) < 8) {
        $fieldErrors['new_password'] = 'New password must be at least 8 characters in length.';
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($currentPassword !== '' && $currentPassword === $newPassword) {
        $fieldErrors['new_password'] = 'New password cannot be the same as your current password.';
        $errors[] = 'New password cannot be the same as your current password.';
    }

    // 3. Validate Confirm Password
    if (trim($confirmPassword) === '') {
        $fieldErrors['confirm_password'] = 'Please confirm your new password.';
        $errors[] = 'Please confirm your new password.';
    } elseif ($newPassword !== $confirmPassword) {
        $fieldErrors['confirm_password'] = 'New password and confirmation do not match.';
        $errors[] = 'New password and confirmation do not match.';
    }

    // Proceed with database update if no validation errors exist
    if (empty($errors)) {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare(
            "UPDATE member_account
             SET password = ?, first_login = FALSE, password_changed = TRUE
             WHERE member_id = ?"
        );
        $update->execute([$newHash, current_user_id()]);

        $_SESSION['first_login'] = false;
        $_SESSION['password_changed'] = true;

        // Log action into update_history table
        $hist = $pdo->prepare(
            "INSERT INTO update_history (member_id, updated_by_member_id, update_type, remarks)
             VALUES (?, ?, 'password_change', 'Member changed their account password.')"
        );
        $hist->execute([current_user_id(), current_user_id()]);

        set_flash('success', 'Password changed successfully.');
        redirect('member/dashboard.php');
    }
}

$pageTitle = 'Change Password';
require_once __DIR__ . '/../pages/header.php';
?>

<!-- Change Password Centered Layout Wrapper -->
<div style="max-width: 640px; margin: 0 auto 50px auto;">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Render Top Alert Validation Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4" id="formErrorAlert">
            <div class="d-flex align-items-center gap-2 mb-2 fw-bold">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Please correct the following errors:</span>
            </div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Change Password Card Container -->
    <div class="card p-4 p-md-5">
        <div class="card-body p-0">
            <!-- Card Header Section -->
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--badge-bg); border: 1px solid var(--badge-border); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--primary-purple); box-shadow: 0 4px 16px var(--accent-glow);">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h3 class="mb-1" style="font-weight: 800;">Change Password</h3>
                    <p class="mb-0 text-muted" style="color: var(--text-dim) !important; font-size: 0.9rem;">
                        Update your account credentials to keep your member account secure.
                    </p>
                </div>
            </div>

            <!-- Password Update Form -->
            <form id="changePasswordForm" method="post" class="needs-validation" novalidate onsubmit="return validatePasswordFormSubmit(event)">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2" for="current_password">
                        <i class="fa-solid fa-lock text-cyan" style="font-size: 0.85rem;"></i>
                        <span>Current Password <span class="text-danger" style="color: #f87171 !important;">*</span></span>
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control <?= !empty($fieldErrors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" placeholder="Enter your current password" required oninput="validateCurrentPasswordInline()">
                        <button type="button" class="btn password-toggle-btn" onclick="togglePasswordVisibility('current_password', this)" title="Show/Hide Password" aria-label="Toggle password visibility" tabindex="-1">
                            <i class="fa-solid fa-eye text-cyan"></i>
                        </button>
                    </div>
                    <div id="currentPasswordError" class="invalid-feedback <?= !empty($fieldErrors['current_password']) ? 'd-block' : '' ?>" style="color: #f87171; font-size: 0.82rem; margin-top: 4px; display: <?= !empty($fieldErrors['current_password']) ? 'block' : 'none' ?>;">
                        <?= e($fieldErrors['current_password']) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex align-items-center gap-2" for="new_password">
                        <i class="fa-solid fa-shield-halved text-purple" style="font-size: 0.85rem;"></i>
                        <span>New Password <span class="text-danger" style="color: #f87171 !important;">*</span></span>
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control <?= !empty($fieldErrors['new_password']) ? 'is-invalid' : '' ?>" id="new_password" name="new_password" minlength="8" placeholder="Create a new password (min. 8 characters)" required oninput="validatePasswordInline()">
                        <button type="button" class="btn password-toggle-btn" onclick="togglePasswordVisibility('new_password', this)" title="Show/Hide Password" aria-label="Toggle password visibility" tabindex="-1">
                            <i class="fa-solid fa-eye text-cyan"></i>
                        </button>
                    </div>
                    <div id="newPasswordError" class="invalid-feedback <?= !empty($fieldErrors['new_password']) ? 'd-block' : '' ?>" style="color: #f87171; font-size: 0.82rem; margin-top: 4px; display: <?= !empty($fieldErrors['new_password']) ? 'block' : 'none' ?>;">
                        <?= e($fieldErrors['new_password']) ?>
                    </div>
                    <div id="pwdCriteriaBox" style="display: flex; flex-direction: column; gap: 4px; margin-top: 8px; font-size: 0.82rem;">
                        <span id="critLength" style="color: #f87171; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-circle-xmark"></i> At least 8 characters in length
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label d-flex align-items-center gap-2" for="confirm_password">
                        <i class="fa-solid fa-check-double text-cyan" style="font-size: 0.85rem;"></i>
                        <span>Confirm New Password <span class="text-danger" style="color: #f87171 !important;">*</span></span>
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control <?= !empty($fieldErrors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" minlength="8" placeholder="Re-type your new password" required oninput="validatePasswordInline()">
                        <button type="button" class="btn password-toggle-btn" onclick="togglePasswordVisibility('confirm_password', this)" title="Show/Hide Password" aria-label="Toggle password visibility" tabindex="-1">
                            <i class="fa-solid fa-eye text-cyan"></i>
                        </button>
                    </div>
                    <div id="confirmPasswordError" class="invalid-feedback <?= !empty($fieldErrors['confirm_password']) ? 'd-block' : '' ?>" style="color: #f87171; font-size: 0.82rem; margin-top: 4px; display: <?= !empty($fieldErrors['confirm_password']) ? 'block' : 'none' ?>;">
                        <?= e($fieldErrors['confirm_password']) ?>
                    </div>
                    <div id="pwdMatchBox" style="margin-top: 8px; font-size: 0.82rem;">
                        <span id="critMatch" style="color: var(--text-dim); display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-circle-info"></i> Passwords must match
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 pt-2">
                    <button type="submit" id="submitPasswordBtn" class="btn btn-primary px-4 py-2" style="font-weight: 700;">
                        <i class="fa-solid fa-check me-1"></i> Update Password
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 12px; font-weight: 600; border-color: rgba(255,255,255,0.2);">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validateCurrentPasswordInline() {
    const currentInput = document.getElementById('current_password');
    const errElem = document.getElementById('currentPasswordError');
    if (!currentInput || !errElem) return;

    if (currentInput.value.trim() === '') {
        currentInput.classList.add('is-invalid');
        currentInput.classList.remove('is-valid');
        errElem.innerText = 'Current password is required.';
        errElem.style.display = 'block';
    } else {
        currentInput.classList.remove('is-invalid');
        errElem.innerText = '';
        errElem.style.display = 'none';
    }
}

function validatePasswordInline() {
    const newPwdInput = document.getElementById('new_password');
    const confirmPwdInput = document.getElementById('confirm_password');
    const newErrElem = document.getElementById('newPasswordError');
    const confirmErrElem = document.getElementById('confirmPasswordError');

    const newPwd = newPwdInput?.value || '';
    const confirmPwd = confirmPwdInput?.value || '';

    const critLength = document.getElementById('critLength');
    const critMatch = document.getElementById('critMatch');

    const isNewPwdValid = (newPwd.length >= 8);

    // 1. Check New Password Length Criteria
    if (isNewPwdValid) {
        if (critLength) {
            critLength.style.color = '#34d399';
            critLength.innerHTML = '<i class="fa-solid fa-circle-check"></i> At least 8 characters in length';
        }
        if (newPwdInput) {
            newPwdInput.classList.remove('is-invalid');
            newPwdInput.classList.add('is-valid');
        }
        if (newErrElem) {
            newErrElem.innerText = '';
            newErrElem.style.display = 'none';
        }
    } else if (newPwd.length > 0) {
        if (critLength) {
            critLength.style.color = '#f87171';
            critLength.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> At least 8 characters in length (' + newPwd.length + '/8)';
        }
        if (newPwdInput) {
            newPwdInput.classList.add('is-invalid');
            newPwdInput.classList.remove('is-valid');
        }
        if (newErrElem) {
            newErrElem.innerText = 'New password must be at least 8 characters in length.';
            newErrElem.style.display = 'block';
        }
    } else {
        if (critLength) {
            critLength.style.color = '#f87171';
            critLength.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> At least 8 characters in length';
        }
        if (newPwdInput) {
            newPwdInput.classList.remove('is-valid', 'is-invalid');
        }
        if (newErrElem) {
            newErrElem.innerText = '';
            newErrElem.style.display = 'none';
        }
    }

    // 2. Check Password Matching (Item 14: ONLY show when new password meets criteria >= 8 chars)
    if (!isNewPwdValid) {
        if (critMatch) {
            critMatch.style.color = 'var(--text-dim)';
            critMatch.innerHTML = '<i class="fa-solid fa-circle-info"></i> Passwords must match';
        }
        if (confirmPwdInput) {
            confirmPwdInput.classList.remove('is-valid', 'is-invalid');
        }
        if (confirmErrElem) {
            confirmErrElem.innerText = '';
            confirmErrElem.style.display = 'none';
        }
    } else {
        if (!confirmPwd) {
            if (critMatch) {
                critMatch.style.color = 'var(--text-dim)';
                critMatch.innerHTML = '<i class="fa-solid fa-circle-info"></i> Passwords must match';
            }
            if (confirmPwdInput) {
                confirmPwdInput.classList.remove('is-valid', 'is-invalid');
            }
            if (confirmErrElem) {
                confirmErrElem.innerText = '';
                confirmErrElem.style.display = 'none';
            }
        } else if (newPwd === confirmPwd) {
            if (critMatch) {
                critMatch.style.color = '#34d399';
                critMatch.innerHTML = '<i class="fa-solid fa-circle-check"></i> Passwords match';
            }
            if (confirmPwdInput) {
                confirmPwdInput.classList.remove('is-invalid');
                confirmPwdInput.classList.add('is-valid');
            }
            if (confirmErrElem) {
                confirmErrElem.innerText = '';
                confirmErrElem.style.display = 'none';
            }
        } else {
            if (critMatch) {
                critMatch.style.color = '#f87171';
                critMatch.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Passwords do not match';
            }
            if (confirmPwdInput) {
                confirmPwdInput.classList.add('is-invalid');
                confirmPwdInput.classList.remove('is-valid');
            }
            if (confirmErrElem) {
                confirmErrElem.innerText = 'New password and confirmation do not match.';
                confirmErrElem.style.display = 'block';
            }
        }
    }
}

let passwordUpdateConfirmed = false;

function validatePasswordFormSubmit(event) {
    if (passwordUpdateConfirmed) {
        return true;
    }

    event.preventDefault();

    const currentInput = document.getElementById('current_password');
    const newPwdInput = document.getElementById('new_password');
    const confirmPwdInput = document.getElementById('confirm_password');

    const currentVal = currentInput?.value.trim() || '';
    const newVal = newPwdInput?.value || '';
    const confirmVal = confirmPwdInput?.value || '';

    if (currentVal === '') {
        showToast('Please enter your current password.', 'warning');
        currentInput?.classList.add('is-invalid');
        currentInput?.focus();
        return false;
    }

    if (newVal.length < 8) {
        showToast('New password must be at least 8 characters long.', 'warning');
        newPwdInput?.classList.add('is-invalid');
        newPwdInput?.focus();
        return false;
    }

    if (currentVal === newVal) {
        showToast('New password cannot be the same as your current password.', 'warning');
        newPwdInput?.classList.add('is-invalid');
        newPwdInput?.focus();
        return false;
    }

    if (confirmVal === '') {
        showToast('Please confirm your new password.', 'warning');
        confirmPwdInput?.classList.add('is-invalid');
        confirmPwdInput?.focus();
        return false;
    }

    if (newVal !== confirmVal) {
        showToast('New password and confirmation do not match.', 'warning');
        confirmPwdInput?.classList.add('is-invalid');
        confirmPwdInput?.focus();
        return false;
    }

    // Trigger popup confirmation window upon meeting all criteria
    showSystemConfirm({
        title: 'Confirm Password Change',
        message: 'Are you sure you want to update your password? You will need to use this new password for your next login sessions.',
        confirmText: 'Yes, Change Password',
        cancelText: 'Cancel',
        icon: 'fa-solid fa-key',
        iconColor: 'var(--primary-purple, #8b5cf6)',
        onConfirm: () => {
            passwordUpdateConfirmed = true;
            document.getElementById('changePasswordForm').submit();
        }
    });

    return false;
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
        btn.setAttribute('title', 'Hide Password');
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
        btn.setAttribute('title', 'Show Password');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    validatePasswordInline();
});
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>