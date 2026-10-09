<?php
/**
 * staff/restore_member.php
 * -------------------------------------------------------
 * Staff Portal - Restore Member Handler (Firebase Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$accNo = $_POST['account_number'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restoring Member...</title>
</head>
<body>
    <p>Processing request...</p>
    <script type="module">
        import { app, db, auth } from '../assets/js/firebase-init.js';
        import { collection, query, where, getDocs, updateDoc, doc, deleteField } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
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

            try {
                const q = query(collection(db, 'member'), where('account_number', '==', accNo));
                const snapshot = await getDocs(q);
                if (!snapshot.empty) {
                    const memberDoc = snapshot.docs[0];
                    if (memberDoc.data().account_status === 'Inactive') {
                        await updateDoc(doc(db, 'member', memberDoc.id), {
                            account_status: 'Active',
                            deleted_at: deleteField()
                        });
                        alert('Member record ' + accNo + ' restored and labeled as Active.');
                    } else {
                        alert('Member account ' + accNo + ' is already active.');
                    }
                } else {
                    alert('Member record not found.');
                }
            } catch (error) {
                console.error("Error restoring member:", error);
                alert('Failed to update member record status.');
            }

            window.location.href = 'members.php';
        });
    </script>
</body>
</html>
