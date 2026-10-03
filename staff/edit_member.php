<?php
/**
 * staff/edit_member.php
 * -------------------------------------------------------
 * Staff Portal — Comprehensive Edit Member Handler (Firebase Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$accNo       = $_POST['account_number'] ?? '';
$firstname   = $_POST['firstname'] ?? '';
$middlename  = $_POST['middlename'] ?? '';
$lastname    = $_POST['lastname'] ?? '';
$civilStatus = $_POST['civil_status'] ?? 'Single';
$accountStatus = $_POST['account_status'] ?? 'Active';
$sex         = $_POST['sex'] ?? 'Male';
$nationality = $_POST['nationality'] ?? 'Filipino';
$birthday    = $_POST['birthday'] ?? '';
$contact     = $_POST['contact'] ?? '';
$email       = $_POST['email'] ?? '';
$address     = $_POST['address'] ?? '';

// Handle file uploads
$uploadsDir = __DIR__ . '/../uploads/';
$passportPhotoName = '';
$signaturePhotoName = '';

if (isset($_FILES['passport_photo']) && $_FILES['passport_photo']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['passport_photo']['name'], PATHINFO_EXTENSION);
    $passportPhotoName = 'member_photo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    move_uploaded_file($_FILES['passport_photo']['tmp_name'], $uploadsDir . $passportPhotoName);
}

if (isset($_FILES['signature_photo']) && $_FILES['signature_photo']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['signature_photo']['name'], PATHINFO_EXTENSION);
    $signaturePhotoName = 'member_sig_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    move_uploaded_file($_FILES['signature_photo']['tmp_name'], $uploadsDir . $signaturePhotoName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Updating Member...</title>
</head>
<body>
    <p>Processing update request...</p>
    <script type="module">
        import { app, db, auth } from '../assets/js/firebase-init.js';
        import { collection, query, where, getDocs, updateDoc, doc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

        onAuthStateChanged(auth, async (user) => {
            if (!user) {
                window.location.href = '../login.php';
                return;
            }

            const accNo = "<?= htmlspecialchars($accNo, ENT_QUOTES) ?>";
            if (!accNo) {
                window.location.href = 'members.php';
                return;
            }

            const updatedData = {
                firstname: "<?= htmlspecialchars($firstname, ENT_QUOTES) ?>",
                middlename: "<?= htmlspecialchars($middlename, ENT_QUOTES) ?>",
                lastname: "<?= htmlspecialchars($lastname, ENT_QUOTES) ?>",
                civil_status: "<?= htmlspecialchars($civilStatus, ENT_QUOTES) ?>",
                account_status: "<?= htmlspecialchars($accountStatus, ENT_QUOTES) ?>",
                sex: "<?= htmlspecialchars($sex, ENT_QUOTES) ?>",
                nationality: "<?= htmlspecialchars($nationality, ENT_QUOTES) ?>",
                birthday: "<?= htmlspecialchars($birthday, ENT_QUOTES) ?>",
                contact_number: "<?= htmlspecialchars($contact, ENT_QUOTES) ?>",
                email: "<?= htmlspecialchars($email, ENT_QUOTES) ?>",
                address: "<?= htmlspecialchars($address, ENT_QUOTES) ?>"
            };
            
            const passportPhoto = "<?= htmlspecialchars($passportPhotoName, ENT_QUOTES) ?>";
            const signaturePhoto = "<?= htmlspecialchars($signaturePhotoName, ENT_QUOTES) ?>";
            
            if (passportPhoto) {
                updatedData.passport_photo = passportPhoto;
            }
            if (signaturePhoto) {
                updatedData.signature = signaturePhoto;
            }

            try {
                const q = query(collection(db, 'member'), where('account_number', '==', accNo));
                const snapshot = await getDocs(q);
                if (!snapshot.empty) {
                    const memberDoc = snapshot.docs[0];
                    await updateDoc(doc(db, 'member', memberDoc.id), updatedData);
                    alert('Member record updated successfully for Account ' + accNo);
                } else {
                    alert('Member record not found.');
                }
            } catch (error) {
                console.error("Error updating member:", error);
                alert('Failed to update member record: ' + error.message);
            }

            window.location.href = 'members.php';
        });
    </script>
</body>
</html>
