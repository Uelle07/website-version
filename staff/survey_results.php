<?php
/**
 * staff/survey_results.php
 * -------------------------------------------------------
 * Staff Portal — Survey Results & Analytics Dashboard (SET B)
 * [CONSOLIDATED]: This route has been consolidated into the unified
 * Questionnaire Management "Responses" tab (staff/survey_questions.php#responses).
 * -------------------------------------------------------
 */
require_once __DIR__ . '/../config/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting...</title>
    <script type="module">
        import { db, auth } from '../assets/js/firebase-init.js';
        import { collection, query, orderBy, limit, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
        import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

        onAuthStateChanged(auth, async (user) => {
            if (!user) {
                window.location.href = '../login.php';
                return;
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            const surveyId = urlParams.get('id');
            
            if (surveyId && parseInt(surveyId) > 0) {
                window.location.href = `survey_questions.php?id=${parseInt(surveyId)}#responses`;
            } else {
                try {
                    const q = query(collection(db, "surveys"), orderBy("survey_id", "desc"), limit(1));
                    const querySnapshot = await getDocs(q);
                    if (!querySnapshot.empty) {
                        const firstSurvey = querySnapshot.docs[0].data();
                        window.location.href = `survey_questions.php?id=${firstSurvey.survey_id}#responses`;
                    } else {
                        window.location.href = 'surveys.php';
                    }
                } catch (e) {
                    console.error("Error fetching latest survey:", e);
                    window.location.href = 'surveys.php';
                }
            }
        });
    </script>
</head>
<body style="font-family: sans-serif; padding: 2rem; text-align: center;">
    <p>Redirecting to survey responses...</p>
</body>
</html>

