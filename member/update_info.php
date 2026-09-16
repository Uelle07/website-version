<?php
/**
 * member/update_info.php
 * -------------------------------------------------------
 * MODULE 4 — Member Information Update
 * Allows members to edit their personal information, contact 
 * details, spouse information, children, parents, references,
 * and signature with sticky form data & validation.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_member();

// Get current member ID from session
$memberId = current_user_id();

// Fetch primary member record from database
$stmt = $pdo->prepare("SELECT * FROM member WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

// Redirect if member record does not exist
if (!$member) {
    set_flash('error', 'Member record not found.');
    redirect('member/dashboard.php');
}

// Fetch spouse record if available
$stmt = $pdo->prepare("SELECT * FROM spouse WHERE member_id = ?");
$stmt->execute([$memberId]);
$spouse = $stmt->fetch() ?: [];

// Fetch parents record if available
$stmt = $pdo->prepare("SELECT * FROM parents WHERE member_id = ?");
$stmt->execute([$memberId]);
$parents = $stmt->fetch() ?: [];

// Fetch children records ordered by child ID
$stmt = $pdo->prepare("SELECT * FROM children WHERE member_id = ? ORDER BY child_id ASC");
$stmt->execute([$memberId]);
$children = $stmt->fetchAll();

// Fetch character references records
$stmt = $pdo->prepare("SELECT * FROM member_references WHERE member_id = ?");
$stmt->execute([$memberId]);
$references = $stmt->fetchAll();

$errors = [];

// Handle form submission for profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for security
    verify_csrf($_POST['csrf_token'] ?? '');

    // 1. Sanitize and clean user input fields
    $firstname       = ucwords(strtolower(clean($_POST['firstname'] ?? '')));
    $middlename      = ucwords(strtolower(clean($_POST['middlename'] ?? '')));
    $lastname        = ucwords(strtolower(clean($_POST['lastname'] ?? '')));
    $extensionName   = clean($_POST['extension_name'] ?? '');
    $sex             = clean($_POST['sex'] ?? '');
    $civilStatus     = clean($_POST['civil_status'] ?? '');
    $nationality     = ucwords(strtolower(clean($_POST['nationality'] ?? '')));
    $birthday        = clean($_POST['birthday'] ?? '');
    $occupation      = clean($_POST['occupation'] ?? '');
    $employer        = clean($_POST['employer'] ?? '');
    $employerAddress = clean($_POST['employer_address'] ?? '');
    $address         = clean($_POST['address'] ?? '');
    $contactNumber   = clean($_POST['contact_number'] ?? '');

    $fatherName      = ucwords(strtolower(clean($_POST['father_name'] ?? '')));
    $motherName      = ucwords(strtolower(clean($_POST['mother_name'] ?? '')));

    $spouseName      = ucwords(strtolower(clean($_POST['spouse_name'] ?? '')));
    $spouseOccupation= clean($_POST['spouse_occupation'] ?? '');
    $spouseEmployer  = clean($_POST['spouse_employer'] ?? '');

    $childNames      = $_POST['child_name'] ?? [];
    $childBdays      = $_POST['child_birthday'] ?? [];

    $refNames        = $_POST['reference_member_name'] ?? [];

    // 2. Validate form inputs using validation helpers
    $errors = array_values(array_filter([
        validate_name($firstname, 'First name'),
        validate_name($lastname, 'Last name'),
        validate_text_only($firstname, 'First name'),
        !empty($middlename) ? validate_text_only($middlename, 'Middle name') : null,
        validate_text_only($lastname, 'Last name'),
        validate_required($sex, 'Sex'),
        validate_required($nationality, 'Nationality'),
        validate_civil_status($civilStatus),
        validate_birthdate($birthday, 'Birthday', 0, 130),
        validate_contact_number($contactNumber),
        validate_required($address, 'Full Home Address'),
        validate_max_length($address, 255, 'Full Home Address'),
    ]));

    if (!empty($sex) && !in_array($sex, ['Male', 'Female'])) {
        $errors[] = 'Please select a valid sex (Male or Female).';
    }

    if (!empty($fatherName)) {
        $v = validate_text_only($fatherName, "Father's Full Name");
        if ($v) $errors[] = $v;
    }
    if (!empty($motherName)) {
        $v = validate_text_only($motherName, "Mother's Full Name");
        if ($v) $errors[] = $v;
    }
    if ($civilStatus === 'Married' && !empty($spouseName)) {
        $v = validate_text_only($spouseName, "Spouse Full Name");
        if ($v) $errors[] = $v;
    }

    // Validate children entries
    foreach ($childNames as $i => $cname) {
        $cname = trim($cname);
        $cbday = trim($childBdays[$i] ?? '');
        if (!empty($cname)) {
            $vName = validate_text_only($cname, "Child " . ($i + 1) . " Name");
            if ($vName) $errors[] = $vName;
        }
        if (!empty($cbday)) {
            $vBday = validate_birthdate($cbday, "Child " . ($i + 1) . " Birthday", 0, 100);
            if ($vBday) $errors[] = $vBday;
        }
    }

    // Validate reference member names
    foreach ($refNames as $i => $refName) {
        $refName = trim($refName);
        if (!empty($refName)) {
            $vRef = validate_text_only($refName, "Reference Member " . ($i + 1));
            if ($vRef) $errors[] = $vRef;
        }
    }

    // 3. Handle optional passport photo upload
    if (!empty($_FILES['passport_photo']['name'])) {
        $photoResult = handle_photo_upload($_FILES['passport_photo'], $memberId . '_photo');
        if ($photoResult['success']) {
            $upPhoto = $pdo->prepare("UPDATE member SET passport_photo = ? WHERE member_id = ?");
            $upPhoto->execute([$photoResult['filename'], $memberId]);
        } else {
            $errors[] = $photoResult['error'];
        }
    }

    // 4. Handle optional signature photo upload
    $newSignatureFilename = null;
    if (!empty($_FILES['signature_photo']['name'])) {
        $sigResult = handle_photo_upload($_FILES['signature_photo'], $memberId . '_sig');
        if ($sigResult['success']) {
            $newSignatureFilename = $sigResult['filename'];
        } else {
            $errors[] = $sigResult['error'];
        }
    }

    // Proceed with database update if there are no validation errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 4. Update member details in the database
            $sql = "UPDATE member SET
                    firstname = ?, middlename = ?, lastname = ?, extension_name = ?,
                    sex = ?, civil_status = ?, nationality = ?, birthday = ?, occupation = ?, 
                    employer = ?, employer_address = ?, address = ?, contact_number = ?
                    WHERE member_id = ?";
            $params = [
                $firstname, $middlename, $lastname, $extensionName,
                $sex, $civilStatus, $nationality, $birthday, $occupation, 
                $employer, $employerAddress, $address, $contactNumber,
                $memberId
            ];

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // 5. Upsert parents information
            $checkParents = $pdo->prepare("SELECT * FROM parents WHERE member_id = ?");
            $checkParents->execute([$memberId]);
            if ($checkParents->fetch()) {
                $upParents = $pdo->prepare("UPDATE parents SET father_name = ?, mother_name = ? WHERE member_id = ?");
                $upParents->execute([$fatherName, $motherName, $memberId]);
            } else {
                $insParents = $pdo->prepare("INSERT INTO parents (member_id, father_name, mother_name) VALUES (?, ?, ?)");
                $insParents->execute([$memberId, $fatherName, $motherName]);
            }

            // 6. Upsert spouse information if married, otherwise remove it
            if ($civilStatus === 'Married') {
                $check = $pdo->prepare("SELECT spouse_id FROM spouse WHERE member_id = ?");
                $check->execute([$memberId]);
                if ($check->fetch()) {
                    $up = $pdo->prepare(
                        "UPDATE spouse SET spouse_name = ?, occupation = ?, employer = ? WHERE member_id = ?"
                    );
                    $up->execute([$spouseName, $spouseOccupation, $spouseEmployer, $memberId]);
                } else {
                    $ins = $pdo->prepare(
                        "INSERT INTO spouse (member_id, spouse_name, occupation, employer) VALUES (?,?,?,?)"
                    );
                    $ins->execute([$memberId, $spouseName, $spouseOccupation, $spouseEmployer]);
                }
            } else {
                $delSpouse = $pdo->prepare("DELETE FROM spouse WHERE member_id = ?");
                $delSpouse->execute([$memberId]);
            }

            // 7. Refresh children records by replacing the full set
            $del = $pdo->prepare("DELETE FROM children WHERE member_id = ?");
            $del->execute([$memberId]);

            $insChild = $pdo->prepare(
                "INSERT INTO children (member_id, child_name, birthday) VALUES (?,?,?)"
            );
            foreach ($childNames as $i => $name) {
                $name = clean($name);
                $bday = clean($childBdays[$i] ?? '');
                if ($name !== '') {
                    $insChild->execute([$memberId, $name, $bday !== '' ? $bday : null]);
                }
            }

            // 8. Refresh character references records (retaining signature if available)
            $existingSignature = $references[0]['signature'] ?? null;
            $signatureToSave = $newSignatureFilename ?: $existingSignature;

            $delRef = $pdo->prepare("DELETE FROM member_references WHERE member_id = ?");
            $delRef->execute([$memberId]);

            $insRef = $pdo->prepare(
                "INSERT INTO member_references (member_id, reference_member_name, signature) VALUES (?, ?, ?)"
            );
            foreach ($refNames as $i => $refName) {
                $refName = clean($refName);
                if ($refName !== '') {
                    $sigVal = ($i === 0) ? $signatureToSave : null;
                    $insRef->execute([$memberId, $refName, $sigVal]);
                }
            }

            // 9. Log profile update action into history table
            $hist = $pdo->prepare(
                "INSERT INTO update_history (member_id, updated_by_member_id, update_type, remarks)
                 VALUES (?, ?, 'Information Update', 'Member updated their own profile information.')"
            );
            $hist->execute([$memberId, $memberId]);

            $pdo->commit();

            set_flash('success', 'Your information has been updated.');
            redirect('member/profile.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong while saving. Please try again.';
            if (APP_DEBUG) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Update Information';
require_once __DIR__ . '/../pages/header.php';
?>

<!-- Custom CSS for calendar icon visibility & layout enhancements -->
<style>
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }
    .text-danger { color: #ff6b6b !important; }
</style>

<!-- Centered Update Info Layout Wrapper -->
<div class="dashboard-wrapper" style="max-width: 1040px; margin: 20px auto 60px auto;">
    <!-- Back to Dashboard Navigation Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Error Display Container -->
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Main Form Card Container -->
    <div class="card profile-container p-4 p-md-5 mb-5">
      <div class="card-body p-0">

        <!-- Card Header Title -->
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--badge-bg); border: 1px solid var(--badge-border); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--primary-teal); box-shadow: 0 4px 16px var(--accent-glow);">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h3 class="mb-1" style="font-weight: 800;">Update My Information</h3>
                <p class="mb-0 text-muted" style="color: var(--text-dim) !important; font-size: 0.9rem;">
                    Keep your cooperative membership information current and accurate.
                </p>
            </div>
        </div>

        <!-- Profile Update Form -->
    <form id="memberUpdateForm" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <!-- Profile Picture Section -->
        <h5 class="mb-3 text-white border-bottom pb-2"><i class="fa-solid fa-camera me-2"></i> Profile Picture (Passport Photo)</h5>
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-auto">
                <div class="profile-photo-preview-box" style="width: 100px; height: 100px; border-radius: 14px; border: 2px dashed var(--primary-teal); overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                    <img id="memberPhotoPreview" src="<?= e(photo_url($member['passport_photo'] ?? '')) ?>" alt="Current Photo" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            <div class="col">
                <label class="form-label">Upload / Change Profile Photo</label>
                <input type="file" class="form-control" name="passport_photo" id="memberPhotoInput" accept="image/jpeg,image/png" onchange="previewMemberPhoto(this)">
                <div class="form-text" style="color: var(--text-dim) !important;">Standard 2x2 or passport size image (JPG/PNG, max 2MB).</div>
            </div>
        </div>

        <!-- Personal Information Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-id-card me-2 text-cyan"></i> Personal Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="firstname"
                       value="<?= e($_POST['firstname'] ?? $member['firstname']) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="middlename"
                       value="<?= e($_POST['middlename'] ?? $member['middlename']) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
            </div>
            <div class="col-md-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="lastname"
                       value="<?= e($_POST['lastname'] ?? $member['lastname']) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ext.</label>
                <select class="form-select" name="extension_name">
                    <option value="">None</option>
                    <?php 
                        $currentExt = $_POST['extension_name'] ?? ($member['extension_name'] ?? '');
                        foreach (['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $ext): 
                    ?>
                        <option value="<?= e($ext) ?>" <?= $currentExt === $ext ? 'selected' : '' ?>>
                            <?= e($ext) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Sex <span class="text-danger">*</span></label>
                <select class="form-select" name="sex" id="memberSex" required>
                    <option value="">Select Sex</option>
                    <?php 
                        $currentSex = $_POST['sex'] ?? ($member['sex'] ?? '');
                        foreach (['Male', 'Female'] as $s): 
                    ?>
                        <option value="<?= e($s) ?>" <?= $currentSex === $s ? 'selected' : '' ?>>
                            <?= e($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                <select class="form-select" id="civil_status_select" name="civil_status" required>
                    <?php 
                        $currentStatus = $_POST['civil_status'] ?? ($member['civil_status'] ?? '');
                        foreach (['Single', 'Married', 'Widowed', 'Separated', 'Divorced'] as $status): 
                    ?>
                        <option value="<?= e($status) ?>" <?= $currentStatus === $status ? 'selected' : '' ?>>
                            <?= e($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nationality <span class="text-danger">*</span></label>
                <select class="form-select" name="nationality" id="memberNationality" required>
                    <option value="">Select Nationality</option>
                    <?php 
                        $currNation = $_POST['nationality'] ?? ($member['nationality'] ?? 'Filipino');
                        foreach (get_nationalities_list() as $nat): 
                    ?>
                        <option value="<?= e($nat) ?>" <?= strcasecmp($currNation, $nat) === 0 ? 'selected' : '' ?>><?= e($nat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Birthday <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="birthday"
                       value="<?= e($_POST['birthday'] ?? $member['birthday']) ?>"
                       min="1900-01-01" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="contact_number" id="memberContactInput"
                       value="<?= e($_POST['contact_number'] ?? $member['contact_number']) ?>" oninput="formatContactNumber(this)" placeholder="0917-123-4567" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Occupation</label>
                <input type="text" class="form-control" name="occupation"
                       value="<?= e($_POST['occupation'] ?? $member['occupation']) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer/Business Name</label>
                <input type="text" class="form-control" name="employer"
                       value="<?= e($_POST['employer'] ?? $member['employer']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer Address</label>
                <input type="text" class="form-control" name="employer_address"
                       value="<?= e($_POST['employer_address'] ?? $member['employer_address']) ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">Full Home Address <span class="text-danger">*</span></label>
                <textarea class="form-control" name="address" rows="2" required><?= e($_POST['address'] ?? $member['address']) ?></textarea>
            </div>
        </div>

        <!-- Parents Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-users me-2 text-cyan"></i> Section V. Parents</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Father's Full Name</label>
                <input type="text" class="form-control" name="father_name"
                       value="<?= e($_POST['father_name'] ?? ($parents['father_name'] ?? '')) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Father's full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mother's Full Name</label>
                <input type="text" class="form-control" name="mother_name"
                       value="<?= e($_POST['mother_name'] ?? ($parents['mother_name'] ?? '')) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Mother's maiden/full name">
            </div>
        </div>

        <!-- Spouse Section (Visible only if Married) -->
        <div id="spouse-section" style="display: none;">
            <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-ring me-2 text-cyan"></i> Spouse Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Spouse Name</label>
                    <input type="text" class="form-control" name="spouse_name"
                           value="<?= e($_POST['spouse_name'] ?? ($spouse['spouse_name'] ?? '')) ?>"
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="spouse_occupation"
                           value="<?= e($_POST['spouse_occupation'] ?? ($spouse['occupation'] ?? '')) ?>"
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employer</label>
                    <input type="text" class="form-control" name="spouse_employer"
                           value="<?= e($_POST['spouse_employer'] ?? ($spouse['employer'] ?? '')) ?>">
                </div>
            </div>
        </div>

        <!-- Children Section (Available always) -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-child me-2 text-cyan"></i> Children Information</h5>
        <div id="children-wrapper">
            <?php 
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['child_name'])) {
                    $childRows = [];
                    foreach ($_POST['child_name'] as $idx => $cname) {
                        $cleanN = clean($cname);
                        $cleanB = clean($_POST['child_birthday'][$idx] ?? '');
                        if ($cleanN !== '' || $cleanB !== '') {
                            $childRows[] = [
                                'child_name' => $cleanN,
                                'birthday' => $cleanB
                            ];
                        }
                    }
                } else {
                    $childRows = !empty($children) ? $children : [];
                }
                $hasChildren = !empty($childRows);
            ?>
            <div id="noChildrenNotice" class="text-muted mb-3" style="color: var(--text-dim) !important; font-size: 0.9rem; display: <?= $hasChildren ? 'none' : 'block' ?>;">
                No children entries added. Click "Add Child" below if you wish to add a child.
            </div>
            <?php foreach ($childRows as $child): ?>
            <div class="row g-3 mb-2 child-row align-items-center">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="child_name[]"
                           placeholder="Child's full name" value="<?= e($child['child_name']) ?>"
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <input type="date" class="form-control" name="child_birthday[]"
                           value="<?= e($child['birthday'] ?? '') ?>"
                           min="1900-01-01" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger remove-child w-100" onclick="removeChildRow(this)">
                        <i class="fa-solid fa-trash-can me-1"></i> Remove
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-child" class="btn btn-sm btn-outline-secondary mb-4" onclick="addNewChildRow()">
            <i class="fa-solid fa-plus"></i> Add Child
        </button>

        <!-- Section VI. References (Two Cooperative Members) -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-user-group me-2 text-cyan"></i> Section VI. References (Two Cooperative Members)</h5>
        <div class="row g-3 mb-4">
            <?php 
                $postRefs = $_POST['reference_member_name'] ?? [];
                $ref1 = $postRefs[0] ?? ($references[0]['reference_member_name'] ?? '');
                $ref2 = $postRefs[1] ?? ($references[1]['reference_member_name'] ?? '');
            ?>
            <div class="col-md-6">
                <label class="form-label">Reference Member 1</label>
                <input type="text" class="form-control" name="reference_member_name[]" value="<?= e($ref1) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Cooperative member full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Reference Member 2</label>
                <input type="text" class="form-control" name="reference_member_name[]" value="<?= e($ref2) ?>"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Cooperative member full name">
            </div>
        </div>

        <!-- Signature Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-signature me-2 text-cyan"></i> Signature (Optional Photo)</h5>
        <div class="mb-4 d-flex align-items-center gap-3">
            <?php if (!empty($member['signature'])): ?>
                <img src="<?= e(photo_url($member['signature'])) ?>" alt="Current Signature"
                     style="height:60px;object-fit:contain;background:#fff;padding:4px;border-radius:6px;border:1px solid #ddd;">
            <?php endif; ?>
            <div>
                <input type="file" class="form-control" id="memberSignatureFileInput" name="signature_photo" accept="image/jpeg,image/png" onchange="handleMemberSigUpload(event)">
                <div id="memberSigPreviewSlot" style="margin-top: 0.35rem;"></div>
                <div class="form-text" style="color: #adb5bd !important;">Upload scanned signature or photo of signature (JPG/PNG, auto-cropped &amp; normalized).</div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end mt-4 pt-2">
            <button type="button" class="btn btn-primary px-4 py-2" style="font-weight: 700;" onclick="promptMemberUpdateConfirm()">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
        </div>
    </form>
  </div>
</div>
</div>

<!-- Confirmation Modal Dialog (Theme-Aware Glassmorphism) -->
<div id="confirmMemberUpdateModal" class="modal-overlay hidden" onclick="if(event.target === this) closeMemberUpdateConfirm();" role="dialog" aria-hidden="true" style="z-index: 100001 !important;">
    <div class="modal-card" style="max-width: 490px; border: 1px solid var(--bg-card-border-glow); box-shadow: 0 20px 50px rgba(0,0,0,0.55), 0 0 25px var(--accent-glow);">
        <div class="modal-header d-flex justify-content-between align-items-center pb-3 border-bottom">
            <h3 class="m-0 d-flex align-items-center gap-2" style="font-size: 1.22rem; font-weight: 800; color: var(--text-main);">
                <i class="fa-solid fa-circle-question text-cyan"></i>
                <span>Save Profile Updates?</span>
            </h3>
            <button type="button" class="btn-close-modal" onclick="closeMemberUpdateConfirm();" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body py-3">
            <p style="color: var(--text-main); font-size: 0.96rem; line-height: 1.6; margin-bottom: 1.25rem;">
                Are you sure you want to commit these changes to your cooperative membership information?
            </p>
            <div class="p-3 rounded-3" style="background: var(--input-bg); border: 1px solid var(--bg-card-border); border-radius: 12px; font-size: 0.86rem;">
                <div class="d-flex align-items-center gap-2" style="color: var(--text-dim);">
                    <i class="fa-solid fa-circle-info text-cyan"></i>
                    <span>All modified personal and family details will be saved immediately to your official record.</span>
                </div>
            </div>
        </div>
        <div class="modal-actions d-flex justify-content-between align-items-center pt-3 border-top w-100">
            <button type="button" class="btn btn-secondary px-4 py-2" onclick="closeMemberUpdateConfirm();" style="border-radius: 12px; font-weight: 600;">
                Cancel
            </button>
            <button type="button" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" onclick="submitMemberUpdateForm();" style="border-radius: 12px; font-weight: 700;">
                <i class="fa-solid fa-floppy-disk"></i> <span>Save</span>
            </button>
        </div>
    </div>
</div>

<!-- Dynamic Form Scripts and Handlers -->
<script>
function formatContactNumber(input) {
    if (!input) return;
    let digits = input.value.replace(/\D/g, '');
    if (digits.length > 11) digits = digits.substring(0, 11);
    if (digits.length > 7) {
        input.value = digits.substring(0, 4) + '-' + digits.substring(4, 7) + '-' + digits.substring(7);
    } else if (digits.length > 4) {
        input.value = digits.substring(0, 4) + '-' + digits.substring(4);
    } else {
        input.value = digits;
    }
}

function previewMemberPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('memberPhotoPreview');
            if (preview) preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function promptMemberUpdateConfirm() {
    const form = document.getElementById('memberUpdateForm');
    if (form && !form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const modal = document.getElementById('confirmMemberUpdateModal');
    if (modal) modal.classList.remove('hidden');
}

function closeMemberUpdateConfirm() {
    const modal = document.getElementById('confirmMemberUpdateModal');
    if (modal) modal.classList.add('hidden');
}

function submitMemberUpdateForm() {
    closeMemberUpdateConfirm();
    document.getElementById('memberUpdateForm').submit();
}

function createChildRowElement(name = '', birthday = '') {
    const today = new Date().toISOString().split('T')[0];
    const row = document.createElement('div');
    row.className = 'row g-3 mb-2 child-row align-items-center';
    row.innerHTML = `
        <div class="col-md-6">
            <input type="text" class="form-control" name="child_name[]"
                   placeholder="Child's full name" value="${name}"
                   oninput="this.value = this.value.replace(/[^A-Za-z\\s.\\-]/g, '')">
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control" name="child_birthday[]"
                   value="${birthday}" min="1900-01-01" max="${today}">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger remove-child w-100" onclick="removeChildRow(this)">
                <i class="fa-solid fa-trash-can me-1"></i> Remove
            </button>
        </div>
    `;
    return row;
}

function addNewChildRow(name = '', birthday = '') {
    const wrapper = document.getElementById('children-wrapper');
    const notice = document.getElementById('noChildrenNotice');
    if (notice) notice.style.display = 'none';
    const row = createChildRowElement(name, birthday);
    wrapper.appendChild(row);
}

function removeChildRow(btn) {
    if (!btn) return;
    const row = btn.closest('.child-row');
    if (row) {
        row.remove();
        const remaining = document.querySelectorAll('.child-row');
        const notice = document.getElementById('noChildrenNotice');
        if (remaining.length === 0 && notice) {
            notice.style.display = 'block';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const civilStatusSelect = document.getElementById('civil_status_select');
    const spouseSection = document.getElementById('spouse-section');

    function toggleSpouseSection() {
        if (civilStatusSelect.value === 'Married') {
            spouseSection.style.display = 'block';
        } else {
            spouseSection.style.display = 'none';
        }
    }

    civilStatusSelect.addEventListener('change', toggleSpouseSection);
    toggleSpouseSection();
});

function handleMemberSigUpload(event) {
    const file = event.target.files[0];
    const previewSlot = document.getElementById('memberSigPreviewSlot');
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showToast('Signature image must be 5 MB or smaller.', 'warning', 'File Size Limit Exceeded');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const origCanvas = document.createElement('canvas');
            const origCtx = origCanvas.getContext('2d', { willReadFrequently: true });
            origCanvas.width = img.naturalWidth || img.width;
            origCanvas.height = img.naturalHeight || img.height;
            origCtx.drawImage(img, 0, 0);

            const imgData = origCtx.getImageData(0, 0, origCanvas.width, origCanvas.height);
            const data = imgData.data;
            const w = origCanvas.width;
            const h = origCanvas.height;

            let minX = w, minY = h, maxX = 0, maxY = 0;
            let inkFound = false;

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const idx = (y * w + x) * 4;
                    const r = data[idx];
                    const g = data[idx + 1];
                    const b = data[idx + 2];
                    const a = data[idx + 3];

                    const isInk = (a > 30) && ((r + g + b) / 3 < 225);
                    if (isInk) {
                        if (x < minX) minX = x;
                        if (x > maxX) maxX = x;
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                        inkFound = true;
                    }
                }
            }

            if (!inkFound) {
                minX = 0; minY = 0; maxX = w; maxY = h;
            }

            const boxW = Math.max(maxX - minX + 1, 20);
            const boxH = Math.max(maxY - minY + 1, 20);
            const padX = Math.round(boxW * 0.12) + 12;
            const padY = Math.round(boxH * 0.12) + 12;

            const cropX = Math.max(0, minX - padX);
            const cropY = Math.max(0, minY - padY);
            const cropW = Math.min(w - cropX, boxW + (padX * 2));
            const cropH = Math.min(h - cropY, boxH + (padY * 2));

            const targetW = 400;
            const targetH = 140;
            const outCanvas = document.createElement('canvas');
            outCanvas.width = targetW;
            outCanvas.height = targetH;
            const outCtx = outCanvas.getContext('2d');

            outCtx.fillStyle = '#ffffff';
            outCtx.fillRect(0, 0, targetW, targetH);

            const scale = Math.min((targetW - 24) / cropW, (targetH - 24) / cropH, 1.8);
            const drawW = cropW * scale;
            const drawH = cropH * scale;
            const drawX = (targetW - drawW) / 2;
            const drawY = (targetH - drawH) / 2;

            outCtx.drawImage(origCanvas, cropX, cropY, cropW, cropH, drawX, drawY, drawW, drawH);

            outCanvas.toBlob(function(blob) {
                if (!blob) return;
                const croppedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + "_cropped.png", { type: 'image/png' });
                const croppedDataUrl = outCanvas.toDataURL('image/png');

                if (previewSlot) {
                    previewSlot.innerHTML = `
                        <div style="margin-top: 0.35rem;">
                            <span style="color: #34d399; font-size: 0.8rem; display: block; margin-bottom: 2px;">
                                <i class="fa-solid fa-circle-check"></i> Auto-Cropped Signature Ready:
                            </span>
                            <img src="${croppedDataUrl}" alt="Cropped Signature" style="height: 42px; background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; vertical-align: middle;">
                        </div>
                    `;
                }

                if (window.DataTransfer) {
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(croppedFile);
                        event.target.files = dt.files;
                    } catch(e) {}
                }
            }, 'image/png');
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>