<?php
/**
 * member/take_survey.php
 * -------------------------------------------------------
 * MODULE (SET B) — Take Member Survey
 * Renders the questionnaire form with clearly structured,
 * distinguishable question types (Multiple Choice, Yes or No,
 * Scale of 1 to 5, and Open Ended Description Form),
 * validates responses, prevents duplicate submissions,
 * and records answers in Firebase Firestore.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
$pageTitle = 'Take Survey';
require_once __DIR__ . '/../pages/header.php';
?>

<style>
/* ================= TAKE SURVEY STYLES ================= */
.take-survey-container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
}

/* Question Card */
.survey-question-card {
    background: var(--bg-card);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid var(--bg-card-border);
    border-radius: 16px;
    padding: 1.6rem 1.85rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}

.survey-question-card:hover {
    border-color: rgba(0, 194, 168, 0.35);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.45);
}

/* Top bar: Number badge, Type pill, Required badge */
.question-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
    flex-wrap: wrap;
}

.q-badge-group {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.q-number-pill {
    background: rgba(0, 194, 168, 0.15);
    border: 1px solid rgba(0, 194, 168, 0.45);
    color: var(--primary-teal);
    font-weight: 800;
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 0.84rem;
    letter-spacing: 0.5px;
    line-height: 1;
}

.qtype-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 8px;
    padding: 5px 12px;
    line-height: 1;
}

.qtype-pill.type-mc {
    background: rgba(56, 189, 248, 0.15);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #38bdf8;
}

.qtype-pill.type-yn {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
}

.qtype-pill.type-rating {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #fbbf24;
}

.qtype-pill.type-desc {
    background: rgba(168, 85, 247, 0.15);
    border: 1px solid rgba(168, 85, 247, 0.35);
    color: #c084fc;
}

.q-req-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.74rem;
    font-weight: 700;
    color: #f87171;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.28);
    padding: 3px 9px;
    border-radius: 6px;
    line-height: 1;
}

.q-prompt-text {
    color: var(--text-main);
    font-size: 1.15rem;
    font-weight: 700;
    margin: 0 0 0.45rem 0;
    line-height: 1.45;
}

.q-instruction-hint {
    color: var(--text-muted);
    font-size: 0.84rem;
    margin-bottom: 1.15rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

/* ================= 1. MULTIPLE CHOICE OPTIONS ================= */
.mc-options-container {
    display: flex;
    flex-direction: column;
    gap: 0.7rem;
}

.mc-card-option {
    display: flex;
    align-items: center;
    gap: 0.95rem;
    padding: 0.95rem 1.25rem;
    border-radius: 12px;
    background: var(--input-bg);
    border: 1px solid var(--bg-card-border);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    user-select: none;
    margin: 0;
}

.mc-card-option:hover {
    border-color: rgba(0, 194, 168, 0.5);
    background: rgba(255, 255, 255, 0.04);
    transform: translateX(3px);
}

.mc-card-option.is-selected {
    border-color: var(--primary-teal) !important;
    background: rgba(0, 194, 168, 0.12) !important;
    box-shadow: 0 0 14px rgba(0, 194, 168, 0.18);
}

.mc-card-option input[type="radio"] {
    accent-color: var(--primary-teal);
    width: 19px;
    height: 19px;
    cursor: pointer;
    flex-shrink: 0;
}

.mc-card-text {
    color: var(--text-main);
    font-size: 0.95rem;
    font-weight: 500;
    line-height: 1.4;
}

.mc-card-option.is-selected .mc-card-text {
    color: #ffffff;
    font-weight: 700;
}

/* ================= 2. YES / NO QUESTION CARDS ================= */
.yn-options-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 576px) {
    .yn-options-row {
        grid-template-columns: 1fr;
    }
}

.yn-card-option {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.85rem;
    padding: 1.15rem 1.25rem;
    border-radius: 12px;
    background: var(--input-bg);
    border: 1px solid var(--bg-card-border);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
    margin: 0;
}

.yn-card-option:hover {
    transform: translateY(-2px);
}

.yn-card-option input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin: 0;
}

.yn-card-option.yn-yes input[type="radio"] {
    accent-color: #10b981;
}

.yn-card-option.yn-no input[type="radio"] {
    accent-color: #ef4444;
}

.yn-card-option.yn-yes:hover {
    border-color: rgba(16, 185, 129, 0.5);
    background: rgba(16, 185, 129, 0.08);
}

.yn-card-option.yn-no:hover {
    border-color: rgba(239, 68, 68, 0.5);
    background: rgba(239, 68, 68, 0.08);
}

.yn-card-option.yn-yes.is-selected {
    border-color: #10b981 !important;
    background: rgba(16, 185, 129, 0.18) !important;
    box-shadow: 0 0 16px rgba(16, 185, 129, 0.22);
}

.yn-card-option.yn-no.is-selected {
    border-color: #ef4444 !important;
    background: rgba(239, 68, 68, 0.18) !important;
    box-shadow: 0 0 16px rgba(239, 68, 68, 0.22);
}

.yn-card-label {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1.05rem;
    font-weight: 700;
}

.yn-card-option.yn-yes .yn-card-label {
    color: #34d399;
}

.yn-card-option.yn-no .yn-card-label {
    color: #f87171;
}

.yn-card-option.is-selected .yn-card-label {
    color: #ffffff;
}

/* ================= 3. SCALE OF 1 TO 5 (RATING) ================= */
.rating-scale-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.75rem;
}

@media (max-width: 768px) {
    .rating-scale-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 0.45rem;
    }
}

@media (max-width: 520px) {
    .rating-scale-grid {
        grid-template-columns: 1fr;
    }
}

.rating-card-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.1rem 0.5rem;
    border-radius: 12px;
    background: var(--input-bg);
    border: 1px solid var(--bg-card-border);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
    text-align: center;
    height: 100%;
    margin: 0;
    position: relative;
}

.rating-card-option:hover {
    border-color: rgba(245, 158, 11, 0.5);
    background: rgba(245, 158, 11, 0.08);
    transform: translateY(-2px);
}

.rating-card-option.is-selected {
    border-color: #fbbf24 !important;
    background: rgba(245, 158, 11, 0.2) !important;
    box-shadow: 0 0 16px rgba(245, 158, 11, 0.25);
}

.rating-num-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid var(--bg-card-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.05rem;
    color: var(--text-main);
    margin-bottom: 8px;
    transition: all 0.2s ease;
}

.rating-card-option:hover .rating-num-circle {
    border-color: rgba(245, 158, 11, 0.5);
    color: #fbbf24;
}

.rating-card-option.is-selected .rating-num-circle {
    background: #fbbf24;
    border-color: #fbbf24;
    color: #111827;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
}

.rating-stars-row {
    color: #fbbf24;
    font-size: 0.85rem;
    margin-bottom: 6px;
    display: flex;
    gap: 2px;
}

.rating-label-desc {
    color: var(--text-muted);
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.25;
}

.rating-card-option.is-selected .rating-label-desc {
    color: #ffffff;
    font-weight: 700;
}

.rating-card-option input[type="radio"] {
    accent-color: #fbbf24;
    width: 16px;
    height: 16px;
    cursor: pointer;
    margin-top: 8px;
}

/* ================= 4. OPEN ENDED DESCRIPTION FORM ================= */
.desc-textarea-wrapper textarea {
    width: 100%;
    background: var(--input-bg);
    border: 1px solid var(--bg-card-border);
    border-radius: 12px;
    padding: 1rem 1.15rem;
    color: var(--text-main);
    font-size: 0.95rem;
    line-height: 1.55;
    resize: vertical;
    min-height: 120px;
    transition: all 0.2s ease;
}

.desc-textarea-wrapper textarea:focus {
    border-color: var(--primary-teal);
    outline: none;
    box-shadow: 0 0 14px rgba(0, 194, 168, 0.2);
    background: rgba(255, 255, 255, 0.03);
}

.desc-char-hint {
    display: flex;
    justify-content: flex-end;
    color: var(--text-dim);
    font-size: 0.78rem;
    margin-top: 0.4rem;
}
</style>

<div class="dashboard-wrapper" style="display: none;" id="mainContent">
    <div class="take-survey-container">
        <!-- Top Action Navigation -->
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <a href="surveys.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
                <i class="fa-solid fa-arrow-left"></i> Back to Surveys
            </a>
        </div>

        <!-- Survey Hero Header Card -->
        <div class="hero-card" style="margin-bottom: 24px;">
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
                    <p id="surveyDescHeader" style="display: none; color: var(--text-muted); font-size: 0.95rem; margin-top: 0.5rem; margin-bottom: 0.8rem; line-height: 1.5;"></p>
                    <div class="meta-details">
                        <span><i class="fa-solid fa-calendar-days"></i> Closes: <span id="surveyEndDate">...</span></span>
                        <span><i class="fa-solid fa-list-check"></i> <span id="surveyQuestionCount">0</span> Question(s)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Survey Instructions Box -->
        <div id="surveyInstructionsBox" class="card mb-4" style="display: none; background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; padding: 1.25rem 1.5rem; box-shadow: 0 8px 24px rgba(0,0,0,0.35);">
            <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--primary-teal); letter-spacing: 0.5px; margin-bottom: 0.35rem;">
                <i class="fa-solid fa-circle-info me-1"></i> Survey Instructions:
            </div>
            <p id="surveyInstructionsText" style="color: var(--text-dim); font-size: 0.92rem; line-height: 1.6; margin: 0;"></p>
        </div>

        <!-- Validation Errors Banner -->
        <div id="errorBanner" class="alert alert-danger mb-4" style="display: none; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; border-radius: 12px; padding: 1rem 1.25rem;">
            <div style="font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i> Please answer all required survey questions:
            </div>
            <ul id="errorList" style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;"></ul>
        </div>

        <!-- Survey Questions Form -->
        <form id="surveyForm" novalidate>
            <div id="questionsContainer" style="display: flex; flex-direction: column; gap: 1.35rem; margin-bottom: 2rem;">
                <div class="text-center py-5" style="color: var(--text-muted);">
                    <i class="fa-solid fa-spinner fa-spin fa-2x mb-3" style="color: var(--primary-teal);"></i>
                    <p>Loading survey questions...</p>
                </div>
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
</div>

<script type="module">
    import { auth, db } from '../assets/js/firebase-init.js';
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
    import { collection, query, where, getDocs, doc, getDoc, addDoc, setDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Helper: Normalize question types across different sources (web, mobile, manual)
    function normalizeQuestionType(type) {
        if (!type) return 'short_answer';
        const t = type.toString().toLowerCase().trim();
        if (t === 'multiple_choice' || t === 'mcq' || t === 'choice' || t === 'single_choice') return 'multiple_choice';
        if (t === 'yes_no' || t === 'yesno' || t === 'boolean' || t === 'yes_or_no') return 'yes_no';
        if (t === 'rating' || t === 'scale' || t === 'rating_scale' || t === 'scale_1_5' || t === 'scale_of_1_to_5') return 'rating';
        if (t === 'short_answer' || t === 'description' || t === 'open_ended' || t === 'text' || t === 'paragraph' || t === 'long_answer') return 'short_answer';
        return t;
    }

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

            // Fetch survey details
            const surveyRef = doc(db, 'surveys', surveyId);
            const surveySnap = await getDoc(surveyRef);

            if (!surveySnap.exists()) {
                alert('The requested survey could not be found.');
                window.location.href = 'surveys.php';
                return;
            }

            const survey = surveySnap.data();
            
            // Validate Active Status & Schedule Window
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
                document.getElementById('surveyDescHeader').innerHTML = survey.description.replace(/\n|\\n/g, '<br>');
                document.getElementById('surveyDescHeader').style.display = 'block';
            }

            if (survey.instructions) {
                document.getElementById('surveyInstructionsText').innerHTML = survey.instructions.replace(/\n|\\n/g, '<br>');
                document.getElementById('surveyInstructionsBox').style.display = 'block';
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

            // Fetch choices (Robust Dual Strategy: by survey_id and by question_id fallback)
            let choicesByQuestion = {};
            const questionIds = new Set(questions.map(q => q.id));

            // Strategy 1: query survey_choices by survey_id
            try {
                const cSnap = await getDocs(query(collection(db, 'survey_choices'), where('survey_id', '==', surveyId)));
                cSnap.forEach(d => {
                    const c = d.data();
                    c.id = d.id;
                    if (!choicesByQuestion[c.question_id]) choicesByQuestion[c.question_id] = [];
                    choicesByQuestion[c.question_id].push(c);
                });
            } catch (err) {
                console.warn('survey_id query on choices skipped:', err);
            }

            // Strategy 2: If any question that requires choices has 0 choices, query whole collection and match question_id
            let needsFallback = false;
            for (const q of questions) {
                const nType = normalizeQuestionType(q.type);
                if ((nType === 'multiple_choice' || nType === 'yes_no' || nType === 'rating') && (!choicesByQuestion[q.id] || choicesByQuestion[q.id].length === 0)) {
                    needsFallback = true;
                    break;
                }
            }

            if (needsFallback) {
                const allChoicesSnap = await getDocs(collection(db, 'survey_choices'));
                allChoicesSnap.forEach(d => {
                    const c = d.data();
                    c.id = d.id;
                    if (questionIds.has(c.question_id)) {
                        if (!choicesByQuestion[c.question_id]) choicesByQuestion[c.question_id] = [];
                        // Avoid duplicates if already populated
                        if (!choicesByQuestion[c.question_id].some(existing => existing.id === c.id)) {
                            choicesByQuestion[c.question_id].push(c);
                        }
                    }
                });
            }

            // Sort choices for each question
            for (let qid in choicesByQuestion) {
                choicesByQuestion[qid].sort((a, b) => (a.choice_order || 0) - (b.choice_order || 0));
            }

            // Render questions
            const container = document.getElementById('questionsContainer');
            container.innerHTML = '';

            questions.forEach((q, index) => {
                const qNumber = index + 1;
                const rawChoices = choicesByQuestion[q.id] || [];
                const normType = normalizeQuestionType(q.type);

                let typeBadge = '';
                let instructionHint = '';
                let inputsHtml = '';

                // ================= TYPE 1: MULTIPLE CHOICE =================
                if (normType === 'multiple_choice') {
                    typeBadge = `<span class="qtype-pill type-mc"><i class="fa-solid fa-list-ul"></i> Multiple Choice</span>`;
                    instructionHint = `<div class="q-instruction-hint"><i class="fa-solid fa-circle-info"></i> Select one option from the choices below:</div>`;
                    
                    if (rawChoices.length === 0) {
                        inputsHtml = `<div class="p-3 text-center" style="background: rgba(255,255,255,0.02); border: 1px dashed var(--bg-card-border); border-radius: 10px; color: var(--text-muted); font-size: 0.88rem;">No choice options were configured for this question.</div>`;
                    } else {
                        inputsHtml = `<div class="mc-options-container">`;
                        rawChoices.forEach(c => {
                            inputsHtml += `
                            <label class="mc-card-option" for="choice_${c.id}">
                                <input type="radio" id="choice_${c.id}" name="ans_${q.id}" value="${c.id}" data-text="${escapeHtml(c.choice_text)}" required>
                                <span class="mc-card-text">${escapeHtml(c.choice_text)}</span>
                            </label>`;
                        });
                        inputsHtml += `</div>`;
                    }

                // ================= TYPE 2: YES OR NO =================
                } else if (normType === 'yes_no') {
                    typeBadge = `<span class="qtype-pill type-yn"><i class="fa-solid fa-scale-balanced"></i> Yes or No</span>`;
                    instructionHint = `<div class="q-instruction-hint"><i class="fa-solid fa-circle-info"></i> Please select either Yes or No:</div>`;

                    // Default choices if none in DB
                    let ynChoices = rawChoices;
                    if (ynChoices.length === 0) {
                        ynChoices = [
                            { id: `yn_yes_${q.id}`, choice_text: 'Yes' },
                            { id: `yn_no_${q.id}`, choice_text: 'No' }
                        ];
                    }

                    inputsHtml = `<div class="yn-options-row">`;
                    ynChoices.forEach(c => {
                        const isYes = c.choice_text.toLowerCase().includes('yes');
                        const cardClass = isYes ? 'yn-yes' : 'yn-no';
                        const icon = isYes ? 'fa-thumbs-up' : 'fa-thumbs-down';
                        inputsHtml += `
                        <label class="yn-card-option ${cardClass}" for="choice_${c.id}">
                            <input type="radio" id="choice_${c.id}" name="ans_${q.id}" value="${c.id}" data-text="${escapeHtml(c.choice_text)}" required>
                            <span class="yn-card-label">
                                <i class="fa-solid ${icon}"></i>
                                <span>${escapeHtml(c.choice_text)}</span>
                            </span>
                        </label>`;
                    });
                    inputsHtml += `</div>`;

                // ================= TYPE 3: SCALE OF 1 TO 5 (RATING) =================
                } else if (normType === 'rating') {
                    typeBadge = `<span class="qtype-pill type-rating"><i class="fa-solid fa-star"></i> Scale of 1 to 5</span>`;
                    instructionHint = `<div class="q-instruction-hint"><i class="fa-solid fa-circle-info"></i> Rate on a scale from 1 (Poor) to 5 (Excellent):</div>`;

                    // Default scale options if not in DB
                    let ratingChoices = rawChoices;
                    if (ratingChoices.length === 0) {
                        ratingChoices = [
                            { id: `rate_1_${q.id}`, choice_text: '1 (Poor)', num: 1, label: 'Poor' },
                            { id: `rate_2_${q.id}`, choice_text: '2 (Fair)', num: 2, label: 'Fair' },
                            { id: `rate_3_${q.id}`, choice_text: '3 (Good)', num: 3, label: 'Good' },
                            { id: `rate_4_${q.id}`, choice_text: '4 (Very Good)', num: 4, label: 'Very Good' },
                            { id: `rate_5_${q.id}`, choice_text: '5 (Excellent)', num: 5, label: 'Excellent' }
                        ];
                    }

                    inputsHtml = `<div class="rating-scale-grid">`;
                    ratingChoices.forEach((c, cIdx) => {
                        const matchNum = c.choice_text ? c.choice_text.match(/^(\d+)/) : null;
                        const num = matchNum ? parseInt(matchNum[1]) : (c.num || (cIdx + 1));
                        
                        let starIcons = '';
                        for (let s = 1; s <= num; s++) {
                            starIcons += '<i class="fa-solid fa-star"></i>';
                        }

                        // Clean descriptive text
                        let descLabel = c.label || c.choice_text.replace(/^\d+\s*[\(\-]?/, '').replace(/[\)]$/, '').trim();
                        if (!descLabel) {
                            if (num === 1) descLabel = 'Poor';
                            else if (num === 2) descLabel = 'Fair';
                            else if (num === 3) descLabel = 'Good';
                            else if (num === 4) descLabel = 'Very Good';
                            else if (num === 5) descLabel = 'Excellent';
                        }

                        inputsHtml += `
                        <label class="rating-card-option" for="choice_${c.id}">
                            <div class="rating-num-circle">${num}</div>
                            <div class="rating-stars-row">${starIcons}</div>
                            <span class="rating-label-desc">${escapeHtml(descLabel)}</span>
                            <input type="radio" id="choice_${c.id}" name="ans_${q.id}" value="${c.id}" data-text="${escapeHtml(c.choice_text)}" required>
                        </label>`;
                    });
                    inputsHtml += `</div>`;

                // ================= TYPE 4: OPEN ENDED DESCRIPTION FORM =================
                } else {
                    typeBadge = `<span class="qtype-pill type-desc"><i class="fa-solid fa-align-left"></i> Description Form</span>`;
                    instructionHint = `<div class="q-instruction-hint"><i class="fa-solid fa-circle-info"></i> Please provide your detailed response or comments in the box below:</div>`;
                    inputsHtml = `
                    <div class="desc-textarea-wrapper">
                        <textarea name="ans_${q.id}" rows="4" placeholder="Type your detailed answer here..." required></textarea>
                        <div class="desc-char-hint"><i class="fa-solid fa-pen-nib me-1"></i> Open-ended feedback field</div>
                    </div>`;
                }

                const cardHtml = `
                <div class="survey-question-card" id="qCard_${q.id}">
                    <div class="question-top-bar">
                        <div class="q-badge-group">
                            <span class="q-number-pill">Q${qNumber}</span>
                            ${typeBadge}
                        </div>
                        <span class="q-req-badge"><i class="fa-solid fa-asterisk"></i> Required</span>
                    </div>
                    <h3 class="q-prompt-text">${escapeHtml(q.question)}</h3>
                    ${instructionHint}
                    <div>
                        ${inputsHtml}
                    </div>
                </div>`;

                container.insertAdjacentHTML('beforeend', cardHtml);
            });

            // Interactive Radio Highlighting
            container.addEventListener('change', (e) => {
                if (e.target && e.target.type === 'radio') {
                    const name = e.target.name;
                    const radios = container.querySelectorAll(`input[name="${name}"]`);
                    radios.forEach(r => {
                        const label = r.closest('label');
                        if (label) {
                            if (r.checked) {
                                label.classList.add('is-selected');
                            } else {
                                label.classList.remove('is-selected');
                            }
                        }
                    });
                }
            });

            // Form Submit handler
            document.getElementById('surveyForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = e.target;
                
                let errors = [];
                let responsesToInsert = [];

                questions.forEach((q, index) => {
                    const qNumber = index + 1;
                    const normType = normalizeQuestionType(q.type);

                    if (normType === 'short_answer') {
                        const inputEl = form.elements['ans_' + q.id];
                        const val = inputEl ? inputEl.value.trim() : '';
                        if (!val) {
                            errors.push(`Question #${qNumber} (${escapeHtml(q.question)}) requires an answer.`);
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
                        if (selected) {
                            if (selected.length !== undefined) {
                                for (let i = 0; i < selected.length; i++) {
                                    if (selected[i].checked) {
                                        val = selected[i].value;
                                        text = selected[i].getAttribute('data-text') || selected[i].value;
                                        break;
                                    }
                                }
                            } else if (selected.checked) {
                                val = selected.value;
                                text = selected.getAttribute('data-text') || selected.value;
                            }
                        }

                        if (!val) {
                            let typeName = 'an option';
                            if (normType === 'yes_no') typeName = 'Yes or No';
                            if (normType === 'rating') typeName = 'a rating (1 to 5)';
                            errors.push(`Please select ${typeName} for Question #${qNumber} (${escapeHtml(q.question)}).`);
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
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                
                errorBanner.style.display = 'none';
                const submitBtn = document.getElementById('submitBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Submitting Survey...`;
                
                try {
                    const submittedAt = new Date().toISOString();
                    
                    for (const resp of responsesToInsert) {
                        resp.submitted_at = submittedAt;
                        await addDoc(collection(db, 'responses'), resp);
                        
                        // If it's a choice, update survey_results for aggregated counts
                        if (resp.choice_id) {
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

                    // Update respondents count on the survey document
                    try {
                        const rQuery = query(collection(db, 'responses'), where('survey_id', '==', surveyId));
                        const allRespSnap = await getDocs(rQuery);
                        const uniqueMembers = new Set();
                        allRespSnap.forEach(d => uniqueMembers.add(d.data().member_id));
                        await setDoc(surveyRef, { respondentsCount: uniqueMembers.size }, { merge: true });
                    } catch(cntErr) {
                        console.warn('Failed to update respondent count:', cntErr);
                    }

                    window.location.href = `survey_success.php?id=${surveyId}`;
                } catch(err) {
                    console.error("Error submitting survey:", err);
                    alert("Failed to submit survey. Please try again.");
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i class="fa-solid fa-paper-plane"></i> Submit Survey`;
                }
            });

        } catch(e) {
            console.error(e);
            alert("Error loading survey. Please refresh the page.");
        }
    });
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>
