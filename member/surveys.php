<?php
/**
 * member/surveys.php
 * -------------------------------------------------------
 * MODULE (SET B) — Member Cooperative Surveys Overview
 * Lists available cooperative surveys for the logged-in member
 * to participate in, and tracks completed submissions.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
$pageTitle = 'Cooperative Surveys';
require_once __DIR__ . '/../pages/header.php';
?>

<div class="dashboard-wrapper" style="display: none;" id="mainContent">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Member Survey Hero Banner -->
    <div class="hero-card" style="margin-bottom: 28px;">
        <div class="hero-profile">
            <div class="profile-avatar-wrapper" style="width: 72px; height: 72px;">
                <div style="width: 100%; height: 100%; border-radius: 50%; background: rgba(0, 194, 168, 0.15); border: 3px solid var(--primary-teal); display: flex; align-items: center; justify-content: center; color: var(--primary-teal); font-size: 1.8rem; box-shadow: 0 6px 16px rgba(0, 194, 168, 0.3);">
                    <i class="fa-solid fa-square-poll-vertical"></i>
                </div>
            </div>
            <div class="hero-info">
                <div class="acc-number">
                    <i class="fa-solid fa-clipboard-question"></i>
                    <span>MEMBER FEEDBACK &amp; INQUIRIES</span>
                </div>
                <h2>Cooperative Surveys</h2>
                <div class="meta-details">
                    <span><i class="fa-solid fa-circle-check text-cyan"></i> <span id="availableCount">0</span> Available Survey(s)</span>
                    <span><i class="fa-solid fa-shield-halved" style="color: #10b981;"></i> Member Participation Portal</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Search & Filter Toolbar -->
    <div class="survey-filter-bar">
        <div class="survey-search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="surveySearchInput" class="survey-search-input" placeholder="Search surveys by title or description..." oninput="filterSurveys()">
        </div>
        <div class="survey-filter-chips">
            <button type="button" class="filter-chip active" data-filter="all" onclick="setSurveyFilter('all', this)">
                <i class="fa-solid fa-border-all"></i> All (<span id="allCount">0</span>)
            </button>
            <button type="button" class="filter-chip" data-filter="available" onclick="setSurveyFilter('available', this)">
                <i class="fa-solid fa-bolt text-cyan"></i> Available to Take (<span id="filterAvailableCount">0</span>)
            </button>
            <button type="button" class="filter-chip" data-filter="completed" onclick="setSurveyFilter('completed', this)">
                <i class="fa-solid fa-check-double text-success"></i> Completed (<span id="completedActiveCount">0</span>)
            </button>
        </div>
    </div>    

    <!-- Section: Active & Available Surveys Grid -->
    <div class="mb-4">
        <div class="grid-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-weight: 800; font-size: 1.25rem; color: var(--text-main);">Questionnaires</h3>
            <div class="available-surveys-badge" title="Total active questionnaires currently open for your participation">
                <i class="fa-solid fa-layer-group"></i>
                <span>Available to Take:</span>
                <span class="badge-count" id="availableBadgeCount">0</span>
            </div>
        </div>

        <div id="noActiveSurveys" class="card text-center py-5" style="display: none; background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px dashed var(--bg-card-border); border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.4);">
            <div class="card-body">
                <i class="fa-solid fa-clipboard-check mb-3" style="font-size: 2.8rem; color: var(--primary-teal); opacity: 0.4;"></i>
                <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.4rem;">No Active Surveys Available</h4>
                <p style="color: var(--text-dim); font-size: 0.9rem; max-width: 450px; margin: 0 auto;">
                    There are currently no active surveys scheduled for member participation. Please check back later for new cooperative inquiries.
                </p>
            </div>
        </div>
        
        <div class="row g-4" id="surveysContainer">
            <!-- Active surveys will be injected here -->
        </div>

        <!-- Empty Search Results Message -->
        <div id="noSearchSurveysFound" class="card text-center py-5" style="display: none; background: var(--bg-card); backdrop-filter: blur(14px); border: 1px dashed var(--bg-card-border); border-radius: 16px;">
            <div class="card-body">
                <i class="fa-solid fa-magnifying-glass mb-3" style="font-size: 2.2rem; color: var(--primary-teal); opacity: 0.5;"></i>
                <h5 style="color: var(--text-main); font-weight: 700;">No Matching Surveys Found</h5>
                <p style="color: var(--text-dim); font-size: 0.88rem; margin: 0;">Try adjusting your keyword search or active filter chip.</p>
            </div>
        </div>
    </div>

    <!-- Section Divider separating Questionnaires and Survey History Log -->
    <div class="survey-section-divider">
        <div class="divider-line"></div>
        <div class="divider-badge">
            <i class="fa-solid fa-clock-rotate-left"></i> Submission Records
        </div>
        <div class="divider-line"></div>
    </div>

    <!-- Section: Survey History Log -->
    <div class="survey-history-card">
        <div class="survey-history-header-box">
            <div class="survey-history-title-row">
                <h3 class="survey-history-title">
                    <i class="fa-solid fa-clock-rotate-left text-cyan"></i> Survey History Log
                </h3>
                <span class="history-status-badge">
                    <i class="fa-solid fa-circle-check"></i> <span id="historyTotalCount">0</span> Submitted Survey(s)
                </span>
            </div>
            <p class="survey-history-subtext">
                A complete chronological record of all your submitted cooperative questionnaires, feedback responses, and member inquiries.
            </p>
        </div>

        <div id="noHistory" class="card-body text-center py-5" style="display: none;">
            <i class="fa-solid fa-inbox mb-3" style="font-size: 2.4rem; color: var(--primary-teal); opacity: 0.4;"></i>
            <h5 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.35rem;">No Survey History Yet</h5>
            <p style="color: var(--text-dim); font-size: 0.88rem; max-width: 440px; margin: 0 auto;">
                You have not submitted any survey responses yet. Take an active questionnaire above to participate and your submission logs will appear here.
            </p>
        </div>
        
        <div id="historyContainer" style="display: none;">
            <!-- History Toolbar with Search & Filter -->
            <div class="history-filter-toolbar">
                <div class="history-search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="historySearchInput" class="history-search-input" placeholder="Search submitted history..." oninput="filterHistorySurveys()">
                </div>
                <div class="history-filter-btn-group">
                    <button type="button" class="history-filter-btn active" data-hfilter="all" onclick="setHistoryFilter('all', this)">
                        <i class="fa-solid fa-list-ul"></i> All History (<span id="historyAllCount">0</span>)
                    </button>
                    <button type="button" class="history-filter-btn" data-hfilter="recent" onclick="setHistoryFilter('recent', this)">
                        <i class="fa-solid fa-calendar-day text-cyan"></i> Last 30 Days
                    </button>
                    <button type="button" class="history-filter-btn" data-hfilter="year" onclick="setHistoryFilter('year', this)">
                        <i class="fa-solid fa-calendar text-cyan"></i> This Year
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="survey-history-table">
                    <thead>
                        <tr>
                            <th style="width: 58%;">Survey Title &amp; Details</th>
                            <th style="width: 20%;">Status</th>
                            <th class="text-end" style="width: 22%;">Submission Date</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- History items will be injected here -->
                    </tbody>
                </table>
            </div>

            <!-- Empty Filter Results Message -->
            <div id="noHistoryFilterFound" class="text-center py-4" style="display: none; background: var(--input-bg); border-top: 1px dashed var(--bg-card-border);">
                <p style="color: var(--text-dim); font-size: 0.88rem; margin: 0;">
                    <i class="fa-solid fa-filter-circle-xmark me-1 text-cyan"></i> No submitted surveys match your current history filter.
                </p>
            </div>
        </div>
    </div>
</div>

<script type="module">
    import { auth, db } from '../assets/js/firebase-init.js';
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
    import { collection, query, where, getDocs, orderBy, doc, getDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = '../login.php';
            return;
        }

        document.getElementById('mainContent').style.display = 'block';

        const memberId = user.uid;

        try {
            // Fetch surveys
            const surveysRef = collection(db, 'surveys');
            const surveysSnapshot = await getDocs(surveysRef);
            let activeSurveys = [];
            let allSurveys = {};

            const now = new Date();
            
            surveysSnapshot.forEach(doc => {
                const data = doc.data();
                data.id = doc.id;
                allSurveys[doc.id] = data;
                if (data.status === 'active' && (!data.end_date || new Date(data.end_date) >= now)) {
                    activeSurveys.push(data);
                }
            });

            // Sort active surveys by end date asc, then id desc
            activeSurveys.sort((a, b) => {
                if(a.end_date && b.end_date) {
                    return new Date(a.end_date) - new Date(b.end_date);
                }
                return 0;
            });

            // Fetch responses for this member
            const responsesRef = collection(db, 'responses');
            const responsesQuery = query(responsesRef, where('member_id', '==', memberId));
            const responsesSnapshot = await getDocs(responsesQuery);
            
            let respondedSurveyIds = new Set();
            let responsesBySurvey = {};

            responsesSnapshot.forEach(docSnap => {
                const data = docSnap.data();
                respondedSurveyIds.add(data.survey_id);
                
                if (!responsesBySurvey[data.survey_id]) {
                    responsesBySurvey[data.survey_id] = { submitted_at: data.submitted_at };
                } else if (data.submitted_at > responsesBySurvey[data.survey_id].submitted_at) {
                    responsesBySurvey[data.survey_id].submitted_at = data.submitted_at;
                }
            });

            let availableCount = 0;
            let completedActiveCount = 0;
            const surveysContainer = document.getElementById('surveysContainer');
            
            if (activeSurveys.length === 0) {
                document.getElementById('noActiveSurveys').style.display = 'block';
            } else {
                for (const survey of activeSurveys) {
                    const isAnswered = respondedSurveyIds.has(survey.id);
                    if (isAnswered) {
                        completedActiveCount++;
                    } else {
                        availableCount++;
                    }

                    // Fetch total questions for this survey
                    const questionsRef = collection(db, 'survey_questions');
                    const qQuery = query(questionsRef, where('survey_id', '==', survey.id));
                    const qSnapshot = await getDocs(qQuery);
                    const totalQuestions = qSnapshot.size;

                    const statusClass = isAnswered ? 'survey-completed' : 'survey-available';
                    const sDataStatus = isAnswered ? 'completed' : 'available';
                    const titleEsc = survey.title.replace(/"/g, '&quot;');
                    const descEsc = (survey.description || '').replace(/"/g, '&quot;');
                    const startDateFormatted = new Date(survey.start_date).toLocaleString();
                    const endDateFormatted = survey.end_date ? new Date(survey.end_date).toLocaleString() : 'No End Date';

                    let statusBadge = isAnswered 
                        ? `<span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;"><i class="fa-solid fa-circle-check"></i> Completed</span>`
                        : `<span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); border: 1px solid rgba(0, 194, 168, 0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;"><i class="fa-solid fa-bolt"></i> Open for Responses</span>`;
                    
                    let actionButton = isAnswered
                        ? `<button type="button" class="btn btn-secondary btn-sm" disabled aria-disabled="true" tabindex="-1" style="opacity: 0.75; border-radius: 10px; padding: 7px 16px; font-weight: 600; font-size: 0.82rem; cursor: not-allowed; pointer-events: none;"><i class="fa-solid fa-check-double me-1"></i> Already Submitted</button>`
                        : `<a href="take_survey.php?id=${survey.id}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px; padding: 7px 18px; font-weight: 700; font-size: 0.85rem; text-decoration: none;"><span>Answer Survey</span> <i class="fa-solid fa-arrow-right"></i></a>`;

                    const html = `
                    <div class="col-md-6 col-lg-6 survey-item-wrapper ${statusClass}" 
                         data-status="${sDataStatus}"
                         data-title="${survey.title.toLowerCase()}"
                         data-desc="${(survey.description || '').toLowerCase()}">
                        <div class="tile-card h-100" style="min-height: 240px; cursor: default;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 8px; flex-wrap: wrap;">
                                    ${statusBadge}
                                    <div style="font-size: 0.78rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
                                        <span>${startDateFormatted} &ndash; ${endDateFormatted}</span>
                                    </div>
                                </div>
                                <div class="tile-content">
                                    <h4 class="survey-title-text" style="font-size: 1.15rem; margin-bottom: 8px; color: var(--text-main);">
                                        ${survey.title}
                                    </h4>
                                    <p class="survey-desc-text" style="color: var(--text-dim); font-size: 0.88rem; line-height: 1.5; margin-bottom: 16px;">
                                        ${survey.description || 'No additional description provided for this survey.'}
                                    </p>
                                </div>
                            </div>
                            <div style="padding-top: 14px; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-list-check" style="color: var(--primary-teal);"></i>
                                    <span>${totalQuestions} Question${totalQuestions === 1 ? '' : 's'}</span>
                                </div>
                                ${actionButton}
                            </div>
                        </div>
                    </div>`;
                    surveysContainer.insertAdjacentHTML('beforeend', html);
                }
            }

            document.getElementById('availableCount').textContent = availableCount;
            document.getElementById('allCount').textContent = activeSurveys.length;
            document.getElementById('filterAvailableCount').textContent = availableCount;
            document.getElementById('completedActiveCount').textContent = completedActiveCount;
            document.getElementById('availableBadgeCount').textContent = availableCount;

            // Generate history logs
            let completedSurveysCount = 0;
            const historyTableBody = document.getElementById('historyTableBody');
            
            const historyKeys = Object.keys(responsesBySurvey);
            
            if (historyKeys.length === 0) {
                document.getElementById('noHistory').style.display = 'block';
            } else {
                document.getElementById('historyContainer').style.display = 'block';
                
                let historyArray = historyKeys.map(sid => {
                    return {
                        survey_id: sid,
                        submitted_at: responsesBySurvey[sid].submitted_at,
                        surveyData: allSurveys[sid]
                    };
                }).filter(h => h.surveyData != null);
                
                historyArray.sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
                
                completedSurveysCount = historyArray.length;
                document.getElementById('historyTotalCount').textContent = completedSurveysCount;
                document.getElementById('historyAllCount').textContent = completedSurveysCount;

                for (const h of historyArray) {
                    const subTime = new Date(h.submitted_at);
                    const isRecent = (now - subTime) <= (30 * 86400 * 1000);
                    const isThisYear = subTime.getFullYear() === now.getFullYear();

                    let descHtml = '';
                    if (h.surveyData.description) {
                        const truncDesc = h.surveyData.description.length > 90 ? h.surveyData.description.substring(0, 90) + '...' : h.surveyData.description;
                        descHtml = `<div style="font-size: 0.8rem; color: var(--text-dim); margin-top: 3px; padding-left: 24px;">${truncDesc}</div>`;
                    }

                    const html = `
                    <tr class="history-row-item" 
                        data-title="${h.surveyData.title.toLowerCase()}"
                        data-recent="${isRecent ? 'true' : 'false'}"
                        data-year="${isThisYear ? 'true' : 'false'}">
                        <td>
                            <div class="history-survey-title">
                                <i class="fa-solid fa-file-lines text-cyan" style="font-size: 0.95rem;"></i>
                                <span>${h.surveyData.title}</span>
                            </div>
                            ${descHtml}
                        </td>
                        <td>
                            <span class="history-status-badge">
                                <i class="fa-solid fa-check-double"></i> Completed
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="history-date-badge">
                                <i class="fa-solid fa-calendar-check text-cyan"></i>
                                <span>${subTime.toLocaleString()}</span>
                            </div>
                        </td>
                    </tr>`;
                    historyTableBody.insertAdjacentHTML('beforeend', html);
                }
            }

        } catch(e) {
            console.error("Error fetching surveys: ", e);
        }
    });

window.currentFilter = 'all';

window.setSurveyFilter = function(filter, btn) {
    window.currentFilter = filter;
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    if (btn) btn.classList.add('active');
    window.filterSurveys();
}

window.filterSurveys = function() {
    const query = (document.getElementById('surveySearchInput')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('.survey-item-wrapper');
    let visibleCount = 0;

    items.forEach(item => {
        const itemStatus = item.getAttribute('data-status');
        const itemTitle = item.getAttribute('data-title') || '';
        const itemDesc = item.getAttribute('data-desc') || '';

        const matchesFilter = (window.currentFilter === 'all') || (window.currentFilter === itemStatus);
        const matchesQuery = !query || itemTitle.includes(query) || itemDesc.includes(query);

        if (matchesFilter && matchesQuery) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const noMsg = document.getElementById('noSearchSurveysFound');
    if (noMsg) {
        noMsg.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
    }
}

window.currentHistoryFilter = 'all';

window.setHistoryFilter = function(filter, btn) {
    window.currentHistoryFilter = filter;
    document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    window.filterHistorySurveys();
}

window.filterHistorySurveys = function() {
    const query = (document.getElementById('historySearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.history-row-item');
    let visibleCount = 0;

    rows.forEach(row => {
        const title = row.getAttribute('data-title') || '';
        const isRecent = row.getAttribute('data-recent') === 'true';
        const isYear = row.getAttribute('data-year') === 'true';

        let matchesFilter = true;
        if (window.currentHistoryFilter === 'recent') matchesFilter = isRecent;
        if (window.currentHistoryFilter === 'year') matchesFilter = isYear;

        const matchesQuery = !query || title.includes(query);

        if (matchesFilter && matchesQuery) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noMsg = document.getElementById('noHistoryFilterFound');
    if (noMsg) {
        noMsg.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>

