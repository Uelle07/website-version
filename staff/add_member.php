<?php
/**
 * staff/add_member.php
 * -------------------------------------------------------
 * Staff Portal — Add New Member Page (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $firstname       = ucwords(strtolower(clean($_POST['firstname'] ?? '')));
    $middlename      = ucwords(strtolower(clean($_POST['middlename'] ?? '')));
    $lastname        = ucwords(strtolower(clean($_POST['lastname'] ?? '')));
    $extensionName   = clean($_POST['extension_name'] ?? '');
    $civilStatus     = clean($_POST['civil_status'] ?? '');
    $accountStatus   = clean($_POST['account_status'] ?? 'Active');
    if (!in_array($accountStatus, ['Active', 'Locked', 'Flagged'])) {
        $accountStatus = 'Active';
    }
    $sex             = clean($_POST['sex'] ?? '');
    $nationality     = ucwords(strtolower(clean($_POST['nationality'] ?? 'Filipino')));
    $birthday        = clean($_POST['birthday'] ?? '');
    $occupation      = clean($_POST['occupation'] ?? '');
    $employer        = clean($_POST['employer'] ?? '');
    $employerAddress = clean($_POST['employer_address'] ?? '');
    $address         = clean($_POST['address'] ?? '');
    $contactCountryCode = clean($_POST['contact_country_code'] ?? '+63');
    $contactNumber   = $contactCountryCode . ' ' . clean($_POST['contact_number'] ?? '');

    $fatherName      = ucwords(strtolower(clean($_POST['father_name'] ?? '')));
    $motherName      = ucwords(strtolower(clean($_POST['mother_name'] ?? '')));

    $spouseFname     = clean($_POST['spouse_firstname'] ?? '');
    $spouseMname     = clean($_POST['spouse_middlename'] ?? '');
    $spouseLname     = clean($_POST['spouse_lastname'] ?? '');
    $spouseExt       = clean($_POST['spouse_extension'] ?? '');
    $spouseName      = ucwords(strtolower(trim("$spouseFname $spouseMname $spouseLname $spouseExt")));
    
    $spouseOccupation= clean($_POST['spouse_occupation'] ?? '');
    $spouseEmployer  = clean($_POST['spouse_employer'] ?? '');

    $childFnames     = $_POST['child_firstname'] ?? [];
    $childMnames     = $_POST['child_middlename'] ?? [];
    $childLnames     = $_POST['child_lastname'] ?? [];
    $childExts       = $_POST['child_extension'] ?? [];
    $childBdays      = $_POST['child_birthday'] ?? [];
    $refNames        = $_POST['reference_member_name'] ?? [];

    $errors = array_values(array_filter([
        validate_name($firstname, 'First name'),
        validate_name($lastname, 'Last name'),
        validate_required($sex, 'Sex'),
        validate_required($nationality, 'Nationality'),
        validate_civil_status($civilStatus),
        validate_contact_number($contactNumber),
        validate_required($address, 'Address'),
    ]));

    if (!empty($sex) && !in_array($sex, ['Male', 'Female'])) {
        $errors[] = 'Please select a valid sex (Male or Female).';
    }

    if (empty($birthday)) {
        $birthday = '1990-01-01'; // Default if unspecified
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Generate unique account number (e.g., 2026-0002)
            $accountNumber = generate_account_number($pdo);

            // 2. Insert into member table
            $insMember = $pdo->prepare(
                "INSERT INTO member (
                    account_number, lastname, firstname, middlename, extension_name,
                    civil_status, account_status, sex, nationality, birthday, occupation,
                    employer, employer_address, address, contact_number, registered_by_staff_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insMember->execute([
                $accountNumber, $lastname, $firstname, $middlename, $extensionName,
                $civilStatus, $accountStatus, $sex, $nationality, $birthday, $occupation,
                $employer, $employerAddress, $address, $contactNumber, current_user_id()
            ]);
            $memberId = (int) $pdo->lastInsertId();

            // 3. Create member_account record (default password = account number)
            $defaultPassword = $accountNumber;
            $passwordHash    = password_hash($defaultPassword, PASSWORD_DEFAULT);

            $insAccount = $pdo->prepare(
                "INSERT INTO member_account (member_id, username, password, first_login, password_changed)
                 VALUES (?, ?, ?, TRUE, FALSE)"
            );
            $insAccount->execute([$memberId, $accountNumber, $passwordHash]);

            // 3b. Process passport_photo upload if attached
            require_once __DIR__ . '/../config/upload.php';
            $photoFile = $_FILES['passport_photo'] ?? $_FILES['photo'] ?? null;
            if ($photoFile && $photoFile['error'] === UPLOAD_ERR_OK) {
                $uploadRes = handle_photo_upload($photoFile, $memberId);
                if ($uploadRes['success']) {
                    $upPhoto = $pdo->prepare("UPDATE member SET passport_photo = ? WHERE member_id = ?");
                    $upPhoto->execute([$uploadRes['filename'], $memberId]);
                }
            }

            // 3c. Process signature_photo upload if attached (same helper as Member module)
            $sigFilename = null;
            $sigFile = $_FILES['signature_photo'] ?? null;
            if ($sigFile && $sigFile['error'] === UPLOAD_ERR_OK) {
                $sigRes = handle_photo_upload($sigFile, $memberId . '_sig');
                if ($sigRes['success']) {
                    $sigFilename = $sigRes['filename'];
                }
                // non-fatal: skip signature if upload fails, don't block registration
            }

            // 4. Insert parents if provided
            if ($fatherName !== '' || $motherName !== '') {
                $insParents = $pdo->prepare("INSERT INTO parents (member_id, father_name, mother_name) VALUES (?, ?, ?)");
                $insParents->execute([$memberId, $fatherName, $motherName]);
            }

            // 5. Insert spouse if married
            if ($civilStatus === 'Married' && $spouseName !== '') {
                $insSpouse = $pdo->prepare("INSERT INTO spouse (member_id, spouse_name, occupation, employer) VALUES (?, ?, ?, ?)");
                $insSpouse->execute([$memberId, $spouseName, $spouseOccupation, $spouseEmployer]);
            }

            // 6. Insert children if provided
            if (!empty($childFnames)) {
                $insChild = $pdo->prepare("INSERT INTO children (member_id, child_name, birthday) VALUES (?, ?, ?)");
                foreach ($childFnames as $idx => $cfname) {
                    $cf = clean($cfname);
                    $cm = clean($childMnames[$idx] ?? '');
                    $cl = clean($childLnames[$idx] ?? '');
                    $ce = clean($childExts[$idx] ?? '');
                    $cname = ucwords(strtolower(trim("$cf $cm $cl $ce")));
                    $cbday = clean($childBdays[$idx] ?? '');
                    if ($cname !== '') {
                        $insChild->execute([$memberId, $cname, $cbday !== '' ? $cbday : null]);
                    }
                }
            }

            // 7. Insert references if provided.
            //    The signature goes on the FIRST reference row (member_references[0].signature)
            //    — the same column read by member/profile.php and member/update_info.php.
            if (!empty($refNames)) {
                $insRef = $pdo->prepare(
                    "INSERT INTO member_references (member_id, reference_member_name, signature) VALUES (?, ?, ?)"
                );
                foreach ($refNames as $i => $rname) {
                    $rname = clean($rname);
                    if ($rname !== '') {
                        // Attach signature only to the first reference row
                        $sigVal = ($i === 0) ? $sigFilename : null;
                        $insRef->execute([$memberId, $rname, $sigVal]);
                    }
                }
            } elseif ($sigFilename !== null) {
                // No reference names supplied but a signature was uploaded:
                // create a placeholder row so the signature is still stored.
                $insRef = $pdo->prepare(
                    "INSERT INTO member_references (member_id, reference_member_name, signature) VALUES (?, ?, ?)"
                );
                $insRef->execute([$memberId, '', $sigFilename]);
            }

            // 8. Log creation in update_history
            $hist = $pdo->prepare(
                "INSERT INTO update_history (member_id, updated_by_staff_id, update_type, remarks)
                 VALUES (?, ?, 'New Member Created', 'Staff registered a new cooperative member.')"
            );
            $hist->execute([$memberId, current_user_id()]);

            $pdo->commit();

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                   || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'New member registered successfully!',
                    'account_number' => $accountNumber,
                    'member_id' => $memberId
                ]);
                return;
            }

            set_flash('success', 'New member registered successfully! Account Number: ' . $accountNumber);
            redirect('staff/members.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = 'Failed to register member. Please try again.';
            if (APP_DEBUG) {
                $msg .= ' Error: ' . $e->getMessage();
            }

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                   || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $msg
                ]);
                return;
            }

            $errors[] = $msg;
        }
    } else {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => implode(' ', $errors)
            ]);
            return;
        }
    }
}

$pageTitle = 'Add New Member';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div class="section-title flex-title-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
  <h2>Add New Cooperative Member</h2>
  <a href="<?= e(BASE_URL) ?>/staff/members.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Members</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 0.75rem 1rem; border-radius: 8px;">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="table-card" style="padding: 1.5rem;">
  <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
    <i class="fa-solid fa-circle-info" style="color: var(--primary-teal);"></i> Use the form below or open the registration modal to add a new member.
  </p>

  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label text-white">First Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="firstname" value="<?= e($_POST['firstname'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label text-white">Middle Name</label>
        <input type="text" class="form-control" name="middlename" value="<?= e($_POST['middlename'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label text-white">Last Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="lastname" value="<?= e($_POST['lastname'] ?? '') ?>" required>
      </div>

      <div class="col-md-3">
        <label class="form-label text-white">Sex <span class="text-danger">*</span></label>
        <select class="form-select" name="sex" required>
          <option value="">Select Sex</option>
          <option value="Male" <?= ($_POST['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= ($_POST['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label text-white">Nationality <span class="text-danger">*</span></label>
        <select class="form-select" name="nationality" required>
          <?php foreach (get_nationalities_list() as $nat): ?>
            <option value="<?= e($nat) ?>" <?= ($_POST['nationality'] ?? 'Filipino') === $nat ? 'selected' : '' ?>><?= e($nat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label text-white">Civil Status <span class="text-danger">*</span></label>
        <select class="form-select" name="civil_status" required>
          <?php foreach (['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $st): ?>
            <option value="<?= e($st) ?>" <?= ($_POST['civil_status'] ?? '') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label text-white">Birthday</label>
        <input type="date" class="form-control" name="birthday" value="<?= e($_POST['birthday'] ?? '') ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label text-white">Contact Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="contact_number" value="<?= e($_POST['contact_number'] ?? '') ?>" required>
      </div>

      <div class="col-md-12">
        <label class="form-label text-white">Home Address <span class="text-danger">*</span></label>
        <textarea class="form-control" name="address" rows="2" required><?= e($_POST['address'] ?? '') ?></textarea>
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Register Member</button>
  </form>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
