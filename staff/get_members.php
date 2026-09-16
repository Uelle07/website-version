<?php
/**
 * staff/get_members.php
 * -------------------------------------------------------
 * JSON Endpoint — Returns active member records for Admin UI JS.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

// Access restricted to logged-in staff members
if (!is_staff()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

try {
    $stmt = $pdo->query(
        "SELECT m.*, ma.username AS email_account,
                ma.failed_attempts, ma.locked_until,
                TIMESTAMPDIFF(SECOND, NOW(), ma.locked_until) AS lock_seconds_left,
                s.spouse_name, s.occupation AS spouse_occupation, s.employer AS spouse_employer,
                p.father_name, p.mother_name
         FROM member m
         LEFT JOIN member_account ma ON ma.member_id = m.member_id
         LEFT JOIN spouse s ON s.member_id = m.member_id
         LEFT JOIN parents p ON p.member_id = m.member_id
         ORDER BY m.created_at DESC"
    );
    $members = $stmt->fetchAll();

    $result = [];
    foreach ($members as $m) {
        $memberId = (int) $m['member_id'];
        $accNo    = $m['account_number'];

        // Fetch children for this member
        $childStmt = $pdo->prepare("SELECT child_name, birthday FROM children WHERE member_id = ?");
        $childStmt->execute([$memberId]);
        $children = $childStmt->fetchAll();

        // Fetch character references and signature for this member
        $refStmt = $pdo->prepare("SELECT reference_member_name, signature FROM member_references WHERE member_id = ?");
        $refStmt->execute([$memberId]);
        $refRows = $refStmt->fetchAll();
        
        $references = [];
        $signatureFilename = null;
        foreach ($refRows as $r) {
            if (!empty($r['reference_member_name'])) {
                $references[] = $r['reference_member_name'];
            }
            if (empty($signatureFilename) && !empty($r['signature'])) {
                $signatureFilename = $r['signature'];
            }
        }
        if (empty($signatureFilename) && !empty($m['signature'])) {
            $signatureFilename = $m['signature'];
        }

        // Calculate age from birthday
        $age = calculate_age($m['birthday'] ?? '') ?? 'N/A';

        $fullName = trim($m['firstname'] . ' ' . $m['lastname']);

        $childrenFormatted = array_map(function ($c) {
            $childAge = !empty($c['birthday']) ? calculate_age($c['birthday']) : null;
            $ageDisplay = ($childAge !== null && $childAge >= 0) ? ($childAge . ' Years Old') : 'N/A';
            return [
                'name'     => $c['child_name'],
                'birthday' => $c['birthday'] ? format_date($c['birthday'], 'm/d/Y') : 'Not Specified',
                'age'      => $ageDisplay,
            ];
        }, $children);

        $rawStatus = $m['account_status'] ?? 'Active';
        $isLockedOut = (!empty($m['locked_until']) && (int)($m['lock_seconds_left'] ?? 0) > 0);
        if ($isLockedOut || strtolower($rawStatus) === 'flagged') {
            $status = 'Flagged';
        } elseif (strtolower($rawStatus) === 'inactive' || strtolower($rawStatus) === 'deleted') {
            $status = 'Inactive';
        } else {
            $status = 'Active';
        }

        $result[$accNo] = [
            'accNo'         => $accNo,
            'account_number'=> $accNo,
            'member_id'     => $memberId,
            'firstname'     => $m['firstname'],
            'middlename'    => $m['middlename'],
            'lastname'      => $m['lastname'],
            'name'          => $fullName,
            'fullName'      => $fullName,
            'age'           => (string) $age,
            'sex'           => $m['sex'] ?: 'Not Specified',
            'nationality'   => $m['nationality'] ?: 'Filipino',
            'civil'         => $m['civil_status'] ?: 'Single',
            'civilStatus'   => $m['civil_status'] ?: 'Single',
            'status'        => $status,
            'account_status'=> $status,
            'contact'       => $m['contact_number'] ?: 'N/A',
            'contact_number'=> $m['contact_number'] ?: 'N/A',
            'address'       => $m['address'] ?: 'N/A',
            'occupation'    => $m['occupation'] ?: 'None',
            'employer'      => $m['employer'] ?: 'None',
            'employer_address' => $m['employer_address'] ?? ($m['office_address'] ?? 'None'),
            'email'         => !empty($m['email']) ? $m['email'] : ($m['email_account'] ?? 'N/A'),
            'passport_photo'=> photo_url($m['passport_photo']),
            'signature_photo'=> !empty($signatureFilename) ? photo_url($signatureFilename) : null,
            'father_name'   => $m['father_name'] ?: 'Not Specified',
            'mother_name'   => $m['mother_name'] ?: 'Not Specified',
            'spouseName'    => $m['spouse_name'] ?: 'Not Specified',
            'spouse'        => [
                'name'       => $m['spouse_name'] ?: 'Not Specified',
                'occupation' => $m['spouse_occupation'] ?: 'Not Specified',
                'employer'   => $m['spouse_employer'] ?: 'Not Specified',
            ],
            'childrenList'  => $childrenFormatted,
            'children'      => $childrenFormatted,
            'references'    => $references ?: [],
        ];
    }

    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
