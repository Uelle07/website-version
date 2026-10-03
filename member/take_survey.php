<?php
/**
 * member/take_survey.php
 * -------------------------------------------------------
 * MODULE (SET B) — Take Member Survey
 * Renders the questionnaire form with dynamic question types
 * (multiple choice, yes/no, rating, short answer), validates responses,
 * prevents duplicate submissions, and records answers.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
$pageTitle = 'Take Survey';
require_once __DIR__ . '/../pages/header.php';
?>

<div class="dashboard-wrapper" style="display: none;" id="mainContent">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="surveys.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Back to Surveys
        </a>
    </div>

    <!-- Survey Hero Header Card -->
    <div class="hero-card" style="margin-bottom: 28px;">
        <div class="hero-profile">
            <div class="profile-avatar-wrapper" style="width: 72px; height: 72px;">
                <div style="width: 100%; height: 100%; border-radius: 50%; background: rgba(0, 194, 168, 0.15); border: 3px solid var(--primary-teal); display: flex; align-items: center; justify-content: center; color: var(--primary-teal); font-size: 1.8rem; box-shadow: 0 6px 16px rgba(0, 194, 168, 0.3);">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
            </div>
            <div class="hero-info">
                <div class="acc-number">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>ACTIVE MEMBER SURVEY</span>
                </div>
                <h2 id="surveyTitle">Loading...</h2>
                <div class="meta-details">
                    <span><i class="fa-solid fa-calendar-days"></i> Closes: <span id="surveyEndDate">...</span></span>
                    <span><i class="fa-solid fa-list-check"></i> <span id="surveyQuestionCount">0</span> Question(s)</span>
                </div>
            </div>
        </div>
    </div>

    <div id="surveyDescriptionBox" class="card mb-4" style="display: none; background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; padding: 1.25rem 1.5rem; box-shadow: 0 8px 24px rgba(0,0,0,0.35);">
        <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--primary-teal); letter-spacing: 0.5px; margin-bottom: 0.35rem;">
            <i class="fa-solid fa-circle-info me-1"></i> Survey Instructions:
        </div>
        <p id="surveyDescription" style="color: var(--text-dim); font-size: 0.92rem; line-height: 1.6; margin: 0;">
        </p>
    </div>

    <!-- Validation Errors Banner -->
    <div id="errorBanner" class="alert alert-danger mb-4" style="display: none; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; border-radius: 12px; padding: 1rem 1.25rem;">
        <div style="font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i> Please resolve the following required fields:
        </div>
        <ul id="errorList" style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
        </ul>
    </div>

    <!-- Survey Questions Form -->
    <form id="surveyForm" novalidate>
        <div id="questionsContainer" style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
            <!-- Questions will be injected here -->
        </div>

        <!-- Submit Action Bar -->
        <div class="card p-4 mb-5" style="background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; box-shadow: var(--card-shadow);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div style="color: var(--text-dim); font-size: 0.85rem;">
                    <i class="fa-solid fa-shield-halved" style="color: var(--primary-teal); margin-right: 4px;"></i>
                    Responses are tied securely to your verified member profile.
                </div>
                <div class="d-flex gap-3">
                    <a href="surveys.php" class="btn btn-outline-light btn-sm px-4" style="border-radius: 10px; font-weight: 600; padding: 10px 20px;">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2" style="border-radius: 10px; font-weight: 700; padding: 10px 24px; box-shadow: 0 4px 15px rgba(0, 194, 168, 0.25);">
                        <i class="fa-solid fa-paper-plane"></i> Submit Survey
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script type="module">
    import { auth, db } from '../assets/js/firebase-init.js';
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
    import { collection, query, where, getDocs, doc, getDoc, addDoc, serverTimestamp, setDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = '../login.php';
            return;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const surveyId = urlParams.get('id');

        if (!surveyId) {
            alert('Invalid survey requested.');
            window.location.href = 'surveys.php';
            return;
        }

        document.getElementById('mainContent').style.display = 'block';
        const memberId = user.uid;

        try {
            // Check if already submitted
            const respQuery = query(collection(db, 'responses'), where('survey_id', '==', surveyId), where('member_id', '==', memberId));
            const respSnap = await getDocs(respQuery);
            if (!respSnap.empty) {
                alert('You have already submitted your response for this survey. Thank you!');
                window.location.href = 'surveys.php';
                return;
            }

            // Fetch survey
            const surveyRef = doc(db, 'surveys', surveyId);
            const surveySnap = await getDoc(surveyRef);

            if (!surveySnap.exists()) {
                alert('The requested survey could not be found.');
                window.location.href = 'surveys.php';
                return;
            }

            const survey = surveySnap.data();
            
            // Validate Active Status
            const now = new Date();
            if (survey.status !== 'active' || (survey.end_date && new Date(survey.end_date) < now)) {
                alert('This survey is currently closed or not active.');
                window.location.href = 'surveys.php';
                return;
            }

            document.getElementById('surveyTitle').textContent = survey.title;
            if (survey.end_date) {
                document.getElementById('surveyEndDate').textContent = new Date(survey.end_date).toLocaleString();
            } else {
                document.getElementById('surveyEndDate').textContent = 'No End Date';
            }

            if (survey.description) {
                document.getElementById('surveyDescription').innerHTML = survey.description.replace(/\\n/g, '<br>');
                document.getElementById('surveyDescriptionBox').style.display = 'block';
            }

            // Fetch questions
            const qQuery = query(collection(db, 'survey_questions'), where('survey_id', '==', surveyId));
            const qSnap = await getDocs(qQuery);
            
            let questions = [];
            qSnap.forEach(d => {
                const data = d.data();
                data.id = d.id;
                questions.push(data);
            });
            
            questions.sort((a, b) => (a.question_order || 0) - (b.question_order || 0));

            if (questions.length === 0) {
                alert('This survey does not have any questions yet.');
                window.location.href = 'surveys.php';
                return;
            }

            document.getElementById('surveyQuestionCount').textContent = questions.length;

            // Fetch choices
            const choicesQuery = query(collection(db, 'survey_choices'), where('survey_id', '==', surveyId));
            const cSnap = await getDocs(choicesQuery);
            let choicesByQuestion = {};
            cSnap.forEach(d => {
                const c = d.data();
                c.id = d.id;
                if (!choicesByQuestion[c.question_id]) choicesByQuestion[c.question_id] = [];
                choicesByQuestion[c.question_id].push(c);
            });

            for(let qid in choicesByQuestion) {
                choicesByQuestion[qid].sort((a, b) => (a.choice_order || 0) - (b.choice_order || 0));
            }

            // Render questions
            const container = document.getElementById('questionsContainer');
            questions.forEach((q, index) => {
                const qNumber = index + 1;
                const choices = choicesByQuestion[q.id] || [];
                
                let inputsHtml = '';

                if (q.question_type === 'multiple_choice') {
                    inputsHtml = '<div style="display: flex; flex-direction: column; gap: 0.65rem;">';
                    choices.forEach(c => {
                        inputsHtml += `
                        <label style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1.15rem; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--bg-card-border); cursor: pointer; transition: all 0.2s ease;" onclick="this.style.borderColor='var(--primary-teal)'; Array.from(this.parentElement.children).forEach(el => {if(el!==this)el.style.borderColor='var(--bg-card-border)'})">
                            <input type="radio" name="ans_${q.id}" value="${c.id}" data-text="${c.choice_text.replace(/"/g, '&quot;')}" style="accent-color: var(--primary-teal); width: 18px; height: 18px;" required>
                            <span style="color: var(--text-main); font-size: 0.95rem; font-weight: 500;">
                                ${c.choice_text}
                            </span>
                        </label>`;
                    });
                    inputsHtml += '</div>';
                } else if (q.question_type === 'yes_no') {
                    inputsHtml = '<div class="row g-3">';
                    choices.forEach(c => {
                        const isYes = c.choice_text.toLowerCase().includes('yes');
                        const icon = isYes ? 'fa-thumbs-up text-success' : 'fa-thumbs-down text-danger';
                        inputsHtml += `
                        <div class="col-sm-6">
                            <label style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 1rem; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--bg-card-border); cursor: pointer; transition: all 0.2s ease; width: 100%;" onclick="this.style.borderColor='var(--primary-teal)'; Array.from(this.closest('.row').querySelectorAll('label')).forEach(el => {if(el!==this)el.style.borderColor='var(--bg-card-border)'})">
                                <input type="radio" name="ans_${q.id}" value="${c.id}" data-text="${c.choice_text.replace(/"/g, '&quot;')}" style="accent-color: var(--primary-teal); width: 18px; height: 18px;" required>
                                <span style="color: var(--text-main); font-weight: 700; font-size: 1rem;">
                                    <i class="fa-solid ${icon} me-1"></i>
                                    ${c.choice_text}
                                </span>
                            </label>
                        </div>`;
                    });
                    inputsHtml += '</div>';
                } else if (q.question_type === 'rating') {
                    inputsHtml = '<div class="row g-2">';
                    choices.forEach(c => {
                        inputsHtml += `
                        <div class="col">
                            <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 0.85rem 0.5rem; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--bg-card-border); cursor: pointer; transition: all 0.2s ease; height: 100%;" onclick="this.style.borderColor='var(--primary-teal)'; Array.from(this.closest('.row').querySelectorAll('label')).forEach(el => {if(el!==this)el.style.borderColor='var(--bg-card-border)'})">
                                <input type="radio" name="ans_${q.id}" value="${c.id}" data-text="${c.choice_text.replace(/"/g, '&quot;')}" style="accent-color: var(--primary-teal); width: 16px; height: 16px; margin-bottom: 6px;" required>
                                <div style="color: #fbbf24; font-size: 1.15rem; margin-bottom: 4px;">
                                    <i class="fa-solid fa-star"></i>
                                </div>
                                <span style="color: var(--text-main); font-size: 0.82rem; font-weight: 600;">
                                    ${c.choice_text}
                                </span>
                            </label>
                        </div>`;
                    });
                    inputsHtml += '</div>';
                } else if (q.question_type === 'short_answer') {
                    inputsHtml = `
                    <div class="form-group">
                        <textarea name="ans_${q.id}" class="form-control" rows="4" placeholder="Type your answer here..." style="background: var(--input-bg); color: var(--text-main); border: 1px solid var(--bg-card-border); border-radius: 10px; padding: 12px 14px; font-size: 0.95rem;" required></textarea>
                    </div>`;
                }

                const html = `
                <div class="card profile-container" style="background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; padding: 1.5rem; box-shadow: 0 8px 24px rgba(0,0,0,0.4);">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1.25rem;">
                        <span style="background: var(--badge-bg); border: 1px solid var(--badge-border); color: var(--primary-teal); font-weight: 800; border-radius: 8px; padding: 4px 10px; font-size: 0.85rem; flex-shrink: 0; margin-top: 2px;">
                            Q${qNumber}
                        </span>
                        <h4 style="color: var(--text-main); font-size: 1.12rem; font-weight: 700; margin: 0; line-height: 1.45;">
                            ${q.question}
                            <span style="color: #ff6b6b;" title="Required">*</span>
                        </h4>
                    </div>
                    <div style="padding-left: 0.25rem;">
                        ${inputsHtml}
                    </div>
                </div>`;
                
                container.insertAdjacentHTML('beforeend', html);
            });

            // Form Submit handler
            document.getElementById('surveyForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = e.target;
                
                let errors = [];
                let responsesToInsert = [];

                questions.forEach((q, index) => {
                    const qNumber = index + 1;
                    if (q.question_type === 'short_answer') {
                        const val = form.elements['ans_' + q.id].value.trim();
                        if (!val) {
                            errors.push(`Please provide an answer for Question #${qNumber}.`);
                        } else {
                            responsesToInsert.push({
                                survey_id: surveyId,
                                question_id: q.id,
                                member_id: memberId,
                                choice_id: null,
                                answer: val
                            });
                        }
                    } else {
                        const selected = form.elements['ans_' + q.id];
                        let val = null;
                        let text = '';
                        if (selected.length) {
                            for (let i = 0; i < selected.length; i++) {
                                if (selected[i].checked) {
                                    val = selected[i].value;
                                    text = selected[i].getAttribute('data-text');
                                    break;
                                }
                            }
                        } else if (selected.checked) {
                            val = selected.value;
                            text = selected.getAttribute('data-text');
                        }

                        if (!val) {
                            errors.push(`Please select an option for Question #${qNumber}.`);
                        } else {
                            responsesToInsert.push({
                                survey_id: surveyId,
                                question_id: q.id,
                                member_id: memberId,
                                choice_id: val,
                                answer: text
                            });
                        }
                    }
                });

                const errorBanner = document.getElementById('errorBanner');
                const errorList = document.getElementById('errorList');
                if (errors.length > 0) {
                    errorList.innerHTML = '';
                    errors.forEach(err => {
                        errorList.insertAdjacentHTML('beforeend', `<li>${err}</li>`);
                    });
                    errorBanner.style.display = 'block';
                    window.scrollTo(0, 0);
                    return;
                }
                
                errorBanner.style.display = 'none';
                document.getElementById('submitBtn').disabled = true;
                
                try {
                    const submittedAt = new Date().toISOString();
                    // Add responses
                    for (const resp of responsesToInsert) {
                        resp.submitted_at = submittedAt;
                        await addDoc(collection(db, 'responses'), resp);
                        
                        // If it's a choice, update survey_results
                        if (resp.choice_id) {
                            // Note: For true consistency we should use a transaction, but we will use a simple update for migration logic.
                            // The backend uses a complex query to recalculate. We'll simply query all responses for this choice and update.
                            const cQuery = query(collection(db, 'responses'), where('question_id', '==', resp.question_id), where('choice_id', '==', resp.choice_id));
                            const cSnap = await getDocs(cQuery);
                            const choiceCount = cSnap.size;
                            
                            const qAllQuery = query(collection(db, 'responses'), where('question_id', '==', resp.question_id));
                            const qAllSnap = await getDocs(qAllQuery);
                            const totalQuestionResponses = qAllSnap.size;
                            
                            const pct = totalQuestionResponses > 0 ? (choiceCount / totalQuestionResponses) * 100 : 0;
                            
                            const resRef = doc(db, 'survey_results', `${surveyId}_${resp.question_id}_${resp.choice_id}`);
                            await setDoc(resRef, {
                                survey_id: surveyId,
                                question_id: resp.question_id,
                                choice_id: resp.choice_id,
                                response_count: choiceCount,
                                percentage: pct
                            }, { merge: true });
                        }
                    }
                    window.location.href = `survey_success.php?id=${surveyId}`;
                } catch(err) {
                    console.error("Error submitting survey:", err);
                    alert("Failed to submit survey. Please try again.");
                    document.getElementById('submitBtn').disabled = false;
                }
            });

        } catch(e) {
            console.error(e);
        }
    });
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>

