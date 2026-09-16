<?php
/**
 * staff/edit_member.php
 * -------------------------------------------------------
 * Staff Portal — Comprehensive Edit Member Handler.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $accNo       = clean($_POST['account_number'] ?? '');
    $firstname   = clean($_POST['firstname'] ?? '');
    $middlename  = clean($_POST['middlename'] ?? '');
    $lastname    = clean($_POST['lastname'] ?? '');
    $extName     = clean($_POST['extension_name'] ?? '');
    $fullName    = clean($_POST['fullname'] ?? '');
    $civilStatus = clean($_POST['civil_status'] ?? 'Single');
    $accountStatus = clean($_POST['account_status'] ?? 'Active');
    if (!in_array($accountStatus, ['Active', 'Inactive', 'Flagged'])) {
        $accountStatus = 'Active';
    }
    $sex         = clean($_POST['sex'] ?? 'Male');
    $nationality = clean($_POST['nationality'] ?? 'Filipino');
    $birthday    = clean($_POST['birthday'] ?? '');
    $contact     = clean($_POST['contact'] ?? '');
    $address     = clean($_POST['address'] ?? '');
    $employer    = clean($_POST['employer'] ?? '');
    $empAddress  = clean($_POST['employer_address'] ?? '');

    $spouseFirst = clean($_POST['spouse_firstname'] ?? '');
    $spouseMid   = clean($_POST['spouse_middlename'] ?? '');
    $spouseLast  = clean($_POST['spouse_lastname'] ?? '');
    $spouseExt   = clean($_POST['spouse_extension'] ?? '');
    $spouseName  = trim(implode(' ', array_filter([$spouseFirst, $spouseMid, $spouseLast, $spouseExt])));
    $spouseOcc   = clean($_POST['spouse_occupation'] ?? '');
    $spouseEmp   = clean($_POST['spouse_employer'] ?? '');

    $fatherName  = clean($_POST['father_name'] ?? '');
    $motherName  = clean($_POST['mother_name'] ?? '');

    $childFirsts = $_POST['child_firstname'] ?? [];
    $childMids   = $_POST['child_middlename'] ?? [];
    $childLasts  = $_POST['child_lastname'] ?? [];
    $childExts   = $_POST['child_extension'] ?? [];
    $childBdays  = $_POST['child_birthday'] ?? [];
    $childrenTxt = clean($_POST['children'] ?? '');

    // Fetch member by account number with spouse, parents, and children info
    $stmt = $pdo->prepare("
        SELECT m.*, s.spouse_name, s.occupation AS spouse_occupation, s.employer AS spouse_employer,
               p.father_name, p.mother_name
        FROM member m
        LEFT JOIN spouse s ON s.member_id = m.member_id
        LEFT JOIN parents p ON p.member_id = m.member_id
        WHERE m.account_number = ?
    ");
    $stmt->execute([$accNo]);
    $member = $stmt->fetch();

    if ($member) {
        $memberId = (int) $member['member_id'];

        // Fallback for single full name if individual fields not provided
        if ($firstname === '' && $lastname === '' && $fullName !== '') {
            $nameParts = explode(' ', $fullName, 2);
            $firstname = !empty($nameParts[0]) ? ucwords(strtolower($nameParts[0])) : ($member['firstname'] ?? '');
            $lastname  = !empty($nameParts[1]) ? ucwords(strtolower($nameParts[1])) : ($member['lastname'] ?? '');
        } else {
            if ($firstname === '') $firstname = $member['firstname'] ?? '';
            if ($lastname === '')  $lastname  = $member['lastname'] ?? '';
        }
        if ($birthday === '') $birthday = $member['birthday'] ?? '';
        if ($address === '')  $address  = $member['address'] ?? '';

        $errors = array_values(array_filter([
            validate_name($firstname, 'First name'),
            validate_name($lastname, 'Last name'),
            validate_required($sex, 'Sex'),
            validate_required($nationality, 'Nationality'),
            validate_civil_status($civilStatus),
            validate_contact_number($contact),
            validate_required($address, 'Home Address'),
        ]));

        if (!empty($sex) && !in_array($sex, ['Male', 'Female'])) {
            $errors[] = 'Please select a valid sex (Male or Female).';
        }

        if (!empty($errors)) {
            set_flash('error', implode(' ', $errors));
            redirect('staff/members.php');
        }

        // Track changes for audit log
        $changes = [];
        if (($member['firstname'] ?? '') !== $firstname && $firstname !== '') {
            $changes['First Name'] = ['old' => $member['firstname'] ?: 'None', 'new' => $firstname];
        }
        if (($member['middlename'] ?? '') !== $middlename) {
            $changes['Middle Name'] = ['old' => $member['middlename'] ?: 'None', 'new' => $middlename ?: 'None'];
        }
        if (($member['lastname'] ?? '') !== $lastname && $lastname !== '') {
            $changes['Last Name'] = ['old' => $member['lastname'] ?: 'None', 'new' => $lastname];
        }
        if (($member['extension_name'] ?? '') !== $extName) {
            $changes['Extension Name'] = ['old' => $member['extension_name'] ?: 'None', 'new' => $extName ?: 'None'];
        }
        if (($member['civil_status'] ?? '') !== $civilStatus && $civilStatus !== '') {
            $changes['Civil Status'] = ['old' => $member['civil_status'] ?: 'None', 'new' => $civilStatus];
        }
        if (($member['account_status'] ?? '') !== $accountStatus && $accountStatus !== '') {
            $changes['Account Status'] = ['old' => $member['account_status'] ?: 'Active', 'new' => $accountStatus];
        }
        if (($member['contact_number'] ?? '') !== $contact && $contact !== '') {
            $changes['Contact Number'] = ['old' => $member['contact_number'] ?: 'None', 'new' => $contact];
        }
        if (($member['address'] ?? '') !== $address && $address !== '') {
            $changes['Home Address'] = ['old' => $member['address'] ?: 'None', 'new' => $address];
        }
        if (($member['spouse_name'] ?? '') !== $spouseName && ($civilStatus === 'Married' || $spouseName !== '')) {
            $changes['Spouse Name'] = ['old' => $member['spouse_name'] ?: 'None', 'new' => $spouseName ?: 'None'];
        }

        try {
            $pdo->beginTransaction();

            // 1. Update member table
            $up = $pdo->prepare(
                "UPDATE member
                 SET firstname = ?, middlename = ?, lastname = ?, extension_name = ?, civil_status = ?, account_status = ?, sex = ?, nationality = ?, birthday = ?, contact_number = ?, address = ?, employer = ?, employer_address = ?
                 WHERE member_id = ?"
            );
            $up->execute([$firstname, $middlename, $lastname, $extName, $civilStatus, $accountStatus, $sex, $nationality, $birthday, $contact, $address, $employer, $empAddress, $memberId]);

            // 1.1 Sync lockout state in member_account
            if ($accountStatus === 'Active' || $accountStatus === 'Inactive') {
                $clearLock = $pdo->prepare("UPDATE member_account SET failed_attempts = 0, locked_until = NULL WHERE member_id = ?");
                $clearLock->execute([$memberId]);
            } elseif ($accountStatus === 'Flagged') {
                $flagLock = $pdo->prepare("UPDATE member_account SET failed_attempts = 5, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE member_id = ?");
                $flagLock->execute([$memberId]);
            }

            // 2. Update spouse information
            if ($civilStatus === 'Single') {
                $delSpouse = $pdo->prepare("DELETE FROM spouse WHERE member_id = ?");
                $delSpouse->execute([$memberId]);
            } elseif ($civilStatus === 'Married' && $spouseName !== '') {
                $checkSpouse = $pdo->prepare("SELECT spouse_id FROM spouse WHERE member_id = ?");
                $checkSpouse->execute([$memberId]);
                if ($checkSpouse->fetch()) {
                    $upSpouse = $pdo->prepare("UPDATE spouse SET spouse_name = ?, occupation = ?, employer = ? WHERE member_id = ?");
                    $upSpouse->execute([$spouseName, $spouseOcc, $spouseEmp, $memberId]);
                } else {
                    $insSpouse = $pdo->prepare("INSERT INTO spouse (member_id, spouse_name, occupation, employer) VALUES (?, ?, ?, ?)");
                    $insSpouse->execute([$memberId, $spouseName, $spouseOcc, $spouseEmp]);
                }
            } elseif ($civilStatus !== 'Married' && $spouseName === '') {
                $delSpouse = $pdo->prepare("DELETE FROM spouse WHERE member_id = ?");
                $delSpouse->execute([$memberId]);
            }

            // 3. Update parents information
            if ($fatherName !== '' || $motherName !== '') {
                $checkPar = $pdo->prepare("SELECT parent_id FROM parents WHERE member_id = ?");
                $checkPar->execute([$memberId]);
                if ($checkPar->fetch()) {
                    $upPar = $pdo->prepare("UPDATE parents SET father_name = ?, mother_name = ? WHERE member_id = ?");
                    $upPar->execute([$fatherName, $motherName, $memberId]);
                } else {
                    $insPar = $pdo->prepare("INSERT INTO parents (member_id, father_name, mother_name) VALUES (?, ?, ?)");
                    $insPar->execute([$memberId, $fatherName, $motherName]);
                }
            }

            // 4. Update children information
            $delChildren = $pdo->prepare("DELETE FROM children WHERE member_id = ?");
            $delChildren->execute([$memberId]);

            if (is_array($childFirsts) && count($childFirsts) > 0) {
                $insChild = $pdo->prepare("INSERT INTO children (member_id, child_name, birthday) VALUES (?, ?, ?)");
                foreach ($childFirsts as $i => $first) {
                    $first  = clean($first);
                    $middle = clean($childMids[$i] ?? '');
                    $last   = clean($childLasts[$i] ?? '');
                    $ext    = clean($childExts[$i] ?? '');
                    
                    $cname = trim(implode(' ', array_filter([$first, $middle, $last, $ext])));
                    $cbday = clean($childBdays[$i] ?? '');
                    if ($cname !== '') {
                        $insChild->execute([$memberId, $cname, !empty($cbday) ? $cbday : null]);
                    }
                }
            } elseif ($childrenTxt !== '') {
                $insChild = $pdo->prepare("INSERT INTO children (member_id, child_name) VALUES (?, ?)");
                $cList = array_map('trim', explode(',', $childrenTxt));
                foreach ($cList as $cname) {
                    if ($cname !== '') {
                        $insChild->execute([$memberId, $cname]);
                    }
                }
            }

            // 5. Handle Passport Photo upload if provided
            if (!empty($_FILES['passport_photo']['name'])) {
                $pResult = handle_photo_upload($_FILES['passport_photo'], $memberId . '_photo');
                if ($pResult['success']) {
                    $upPhoto = $pdo->prepare("UPDATE member SET passport_photo = ? WHERE member_id = ?");
                    $upPhoto->execute([$pResult['filename'], $memberId]);
                }
            }

            // 6. Handle E-Signature upload if provided
            if (!empty($_FILES['signature_photo']['name'])) {
                $sResult = handle_photo_upload($_FILES['signature_photo'], $memberId . '_sig');
                if ($sResult['success']) {
                    $checkRef = $pdo->prepare("SELECT reference_id FROM member_references WHERE member_id = ? LIMIT 1");
                    $checkRef->execute([$memberId]);
                    if ($checkRef->fetch()) {
                        $upSig = $pdo->prepare("UPDATE member_references SET signature = ? WHERE member_id = ? LIMIT 1");
                        $upSig->execute([$sResult['filename'], $memberId]);
                    } else {
                        $insSig = $pdo->prepare("INSERT INTO member_references (member_id, reference_member_name, signature) VALUES (?, 'Self', ?)");
                        $insSig->execute([$memberId, $sResult['filename']]);
                    }
                }
            }

            // 7. Log to update_history with JSON change diff
            $remarksJSON = json_encode([
                'summary' => 'Staff updated member profile information.',
                'changes' => $changes
            ]);

            $hist = $pdo->prepare(
                "INSERT INTO update_history (member_id, updated_by_staff_id, update_type, remarks)
                 VALUES (?, ?, 'Information Update', ?)"
            );
            $hist->execute([$memberId, current_user_id(), $remarksJSON]);

            $pdo->commit();

            set_flash('success', 'Member record updated successfully for Account ' . $accNo);
        } catch (PDOException $e) {
            $pdo->rollBack();
            set_flash('error', 'Failed to update member record: ' . $e->getMessage());
        }
    } else {
        set_flash('error', 'Member record not found.');
    }
}

redirect('staff/members.php');

