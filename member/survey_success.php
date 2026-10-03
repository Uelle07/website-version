<?php
/**
 * member/survey_success.php
 * -------------------------------------------------------
 * MODULE (SET B) — Survey Submission Confirmation
 * Displays a clean, professional confirmation receipt after
 * a member successfully completes a survey questionnaire.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
$pageTitle = 'Survey Submitted Successfully';
require_once __DIR__ . '/../pages/header.php';
?>

<div class="dashboard-wrapper" style="display: none;" id="mainContent">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Success Confirmation Container -->
    <div class="row justify-content-center py-3">
        <div class="col-md-9 col-lg-7">
            <div class="card profile-container text-center p-4 p-md-5" style="background: var(--bg-card); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--bg-card-border); border-radius: 20px; box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);">
                <div class="card-body p-0">
                    <!-- Success Check Icon Badge -->
                    <div class="mx-auto mb-4" style="width: 80px; height: 80px; border-radius: 50%; background: rgba(0, 194, 168, 0.15); border: 2.5px solid var(--primary-teal); display: flex; align-items: center; justify-content: center; color: var(--primary-teal); font-size: 2.5rem; box-shadow: 0 0 25px rgba(0, 194, 168, 0.35);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <h2 style="color: var(--text-main); font-weight: 800; font-size: 1.65rem; margin-bottom: 0.5rem; letter-spacing: -0.3px;">
                        Survey Submitted Successfully
                    </h2>

                    <p style="color: var(--text-dim); font-size: 0.95rem; line-height: 1.6; max-width: 480px; margin: 0 auto 1.75rem auto;">
                        Thank you for your valuable participation. Your feedback has been securely registered and recorded into the cooperative system.
                    </p>

                    <div id="receiptBox" class="structured-box text-start mb-4" style="display: none; background: var(--input-bg); border: 1px solid var(--bg-card-border); border-radius: 12px; padding: 1.25rem 1.5rem;">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
                            <span style="color: var(--text-dim); font-size: 0.85rem;">Survey Title:</span>
                            <span id="receiptTitle" style="color: var(--text-main); font-weight: 700; font-size: 0.9rem; max-width: 280px; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
                            <span style="color: var(--text-dim); font-size: 0.85rem;">Completed Items:</span>
                            <span style="color: var(--primary-teal); font-weight: 700; font-size: 0.88rem;">
                                <i class="fa-solid fa-list-check me-1"></i> <span id="receiptTotalAnswered">0</span> Questions
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
                            <span style="color: var(--text-dim); font-size: 0.85rem;">Submission Time:</span>
                            <span style="color: var(--text-main); font-size: 0.85rem;">
                                <i class="fa-solid fa-clock me-1" style="color: var(--primary-teal);"></i> <span id="receiptSubmittedAt"></span>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="color: var(--text-dim); font-size: 0.85rem;">Record Status:</span>
                            <span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-shield-halved"></i> Verified &amp; Saved
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="surveys.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 12px; font-weight: 600; background: var(--input-bg); border-color: var(--bg-card-border); font-size: 0.9rem;">
                            <i class="fa-solid fa-clipboard-question me-1"></i> Available Surveys
                        </a>
                        <a href="dashboard.php" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 12px; font-weight: 700; font-size: 0.9rem; box-shadow: 0 4px 15px rgba(0, 194, 168, 0.25);">
                            <i class="fa-solid fa-gauge-high"></i> Member Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import { auth, db } from '../assets/js/firebase-init.js';
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
    import { doc, getDoc, collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = '../login.php';
            return;
        }

        document.getElementById('mainContent').style.display = 'block';

        const urlParams = new URLSearchParams(window.location.search);
        const surveyId = urlParams.get('id');

        if (surveyId) {
            try {
                // Fetch survey details
                const surveyRef = doc(db, 'surveys', surveyId);
                const surveySnap = await getDoc(surveyRef);

                if (surveySnap.exists()) {
                    const surveyData = surveySnap.data();
                    
                    // Fetch responses
                    const responsesRef = collection(db, 'responses');
                    const q = query(responsesRef, where('survey_id', '==', surveyId), where('member_id', '==', user.uid));
                    const responsesSnap = await getDocs(q);

                    if (!responsesSnap.empty) {
                        let maxSubmittedAt = null;
                        const answeredQuestionIds = new Set();
                        
                        responsesSnap.forEach(docSnap => {
                            const data = docSnap.data();
                            answeredQuestionIds.add(data.question_id);
                            
                            if (!maxSubmittedAt || new Date(data.submitted_at) > maxSubmittedAt) {
                                maxSubmittedAt = new Date(data.submitted_at);
                            }
                        });

                        document.getElementById('receiptTitle').textContent = surveyData.title;
                        document.getElementById('receiptTotalAnswered').textContent = answeredQuestionIds.size;
                        document.getElementById('receiptSubmittedAt').textContent = maxSubmittedAt.toLocaleString();
                        
                        document.getElementById('receiptBox').style.display = 'block';
                    }
                }
            } catch(e) {
                console.error("Error fetching receipt details", e);
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>

