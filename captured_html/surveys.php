<div class="dashboard-wrapper">
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
                    <span><i class="fa-solid fa-circle-check text-cyan"></i> 4 Available Surveys</span>
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
                <i class="fa-solid fa-border-all"></i> All (4)
            </button>
            <button type="button" class="filter-chip" data-filter="available" onclick="setSurveyFilter('available', this)">
                <i class="fa-solid fa-bolt text-cyan"></i> Available to Take (4)
            </button>
            <button type="button" class="filter-chip" data-filter="completed" onclick="setSurveyFilter('completed', this)">
                <i class="fa-solid fa-check-double text-success"></i> Completed (0)
            </button>
        </div>
    </div>    <!-- Section: Active & Available Surveys Grid -->
    <div class="mb-4">
        <div class="grid-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0; font-weight: 800; font-size: 1.25rem; color: var(--text-main);">Questionnaires</h3>
            <div class="available-surveys-badge" title="Total active questionnaires currently open for your participation">
                <i class="fa-solid fa-layer-group"></i>
                <span>Available to Take:</span>
                <span class="badge-count" id="availableBadgeCount">4</span>
            </div>
        </div>

                    <div class="row g-4" id="surveysContainer">
                                                        <div class="col-md-6 col-lg-6 survey-item-wrapper survey-available" 
                         data-status="available"
                         data-title="seb"
                         data-desc="seb">
                        <div class="tile-card h-100" style="min-height: 240px; cursor: default;">
                            <div>
                                <!-- Top Status & Dates Bar -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 8px; flex-wrap: wrap;">
                                                                            <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); border: 1px solid rgba(0, 194, 168, 0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-bolt"></i> Open for Responses
                                        </span>
                                    
                                    <div style="font-size: 0.78rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
                                        <span>Aug 19, 2026 12:00 AM &ndash; Sep 18, 2026 12:00 AM</span>
                                    </div>
                                </div>

                                <!-- Survey Title & Description -->
                                <div class="tile-content">
                                    <h4 class="survey-title-text" style="font-size: 1.15rem; margin-bottom: 8px; color: var(--text-main);">
                                        SEB                                    </h4>
                                    <p class="survey-desc-text" style="color: var(--text-dim); font-size: 0.88rem; line-height: 1.5; margin-bottom: 16px;">
                                        seb                                    </p>
                                </div>
                            </div>

                            <!-- Survey Footer Action -->
                            <div style="padding-top: 14px; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-list-check" style="color: var(--primary-teal);"></i>
                                    <span>2 Questions</span>
                                </div>

                                                                    <a href="take_survey.php?id=4" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px; padding: 7px 18px; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                                        <span>Answer Survey</span> <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                                            </div>
                        </div>
                    </div>
                                                        <div class="col-md-6 col-lg-6 survey-item-wrapper survey-available" 
                         data-status="available"
                         data-title="2026 cooperative member satisfaction survey"
                         data-desc="annual assessment of cooperative programs, loan services, and member satisfaction.">
                        <div class="tile-card h-100" style="min-height: 240px; cursor: default;">
                            <div>
                                <!-- Top Status & Dates Bar -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 8px; flex-wrap: wrap;">
                                                                            <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); border: 1px solid rgba(0, 194, 168, 0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-bolt"></i> Open for Responses
                                        </span>
                                    
                                    <div style="font-size: 0.78rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
                                        <span>Aug 19, 2026 12:00 AM &ndash; Sep 19, 2026 12:00 AM</span>
                                    </div>
                                </div>

                                <!-- Survey Title & Description -->
                                <div class="tile-content">
                                    <h4 class="survey-title-text" style="font-size: 1.15rem; margin-bottom: 8px; color: var(--text-main);">
                                        2026 Cooperative Member Satisfaction Survey                                    </h4>
                                    <p class="survey-desc-text" style="color: var(--text-dim); font-size: 0.88rem; line-height: 1.5; margin-bottom: 16px;">
                                        Annual assessment of cooperative programs, loan services, and member satisfaction.                                    </p>
                                </div>
                            </div>

                            <!-- Survey Footer Action -->
                            <div style="padding-top: 14px; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-list-check" style="color: var(--primary-teal);"></i>
                                    <span>4 Questions</span>
                                </div>

                                                                    <a href="take_survey.php?id=10" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px; padding: 7px 18px; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                                        <span>Answer Survey</span> <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                                            </div>
                        </div>
                    </div>
                                                        <div class="col-md-6 col-lg-6 survey-item-wrapper survey-available" 
                         data-status="available"
                         data-title="hello"
                         data-desc="asnwe his">
                        <div class="tile-card h-100" style="min-height: 240px; cursor: default;">
                            <div>
                                <!-- Top Status & Dates Bar -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 8px; flex-wrap: wrap;">
                                                                            <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); border: 1px solid rgba(0, 194, 168, 0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-bolt"></i> Open for Responses
                                        </span>
                                    
                                    <div style="font-size: 0.78rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
                                        <span>Aug 21, 2026 12:00 AM &ndash; Sep 20, 2026 12:00 AM</span>
                                    </div>
                                </div>

                                <!-- Survey Title & Description -->
                                <div class="tile-content">
                                    <h4 class="survey-title-text" style="font-size: 1.15rem; margin-bottom: 8px; color: var(--text-main);">
                                        hello                                    </h4>
                                    <p class="survey-desc-text" style="color: var(--text-dim); font-size: 0.88rem; line-height: 1.5; margin-bottom: 16px;">
                                        asnwe his                                    </p>
                                </div>
                            </div>

                            <!-- Survey Footer Action -->
                            <div style="padding-top: 14px; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-list-check" style="color: var(--primary-teal);"></i>
                                    <span>0 Questions</span>
                                </div>

                                                                    <a href="take_survey.php?id=15" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px; padding: 7px 18px; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                                        <span>Answer Survey</span> <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                                            </div>
                        </div>
                    </div>
                                                        <div class="col-md-6 col-lg-6 survey-item-wrapper survey-available" 
                         data-status="available"
                         data-title="asd"
                         data-desc="asd">
                        <div class="tile-card h-100" style="min-height: 240px; cursor: default;">
                            <div>
                                <!-- Top Status & Dates Bar -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 8px; flex-wrap: wrap;">
                                                                            <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); border: 1px solid rgba(0, 194, 168, 0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fa-solid fa-bolt"></i> Open for Responses
                                        </span>
                                    
                                    <div style="font-size: 0.78rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
                                        <span>Aug 19, 2026 12:00 AM &ndash; Sep 21, 2026 12:00 AM</span>
                                    </div>
                                </div>

                                <!-- Survey Title & Description -->
                                <div class="tile-content">
                                    <h4 class="survey-title-text" style="font-size: 1.15rem; margin-bottom: 8px; color: var(--text-main);">
                                        asd                                    </h4>
                                    <p class="survey-desc-text" style="color: var(--text-dim); font-size: 0.88rem; line-height: 1.5; margin-bottom: 16px;">
                                        asd                                    </p>
                                </div>
                            </div>

                            <!-- Survey Footer Action -->
                            <div style="padding-top: 14px; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                                <div style="font-size: 0.82rem; color: var(--text-dim); display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-list-check" style="color: var(--primary-teal);"></i>
                                    <span>3 Questions</span>
                                </div>

                                                                    <a href="take_survey.php?id=14" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 10px; padding: 7px 18px; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                                        <span>Answer Survey</span> <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                                            </div>
                        </div>
                    </div>
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
                    <i class="fa-solid fa-circle-check"></i> <span id="historyTotalCount">0</span> Submitted Surveys                </span>
            </div>
            <p class="survey-history-subtext">
                A complete chronological record of all your submitted cooperative questionnaires, feedback responses, and member inquiries.
            </p>
        </div>

                    <div class="card-body text-center py-5">
                <i class="fa-solid fa-inbox mb-3" style="font-size: 2.4rem; color: var(--primary-teal); opacity: 0.4;"></i>
                <h5 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.35rem;">No Survey History Yet</h5>
                <p style="color: var(--text-dim); font-size: 0.88rem; max-width: 440px; margin: 0 auto;">
                    You have not submitted any survey responses yet. Take an active questionnaire above to participate and your submission logs will appear here.
                </p>
            </div>
            </div>
</div>

<script>
let currentFilter = 'all';

function setSurveyFilter(filter, btn) {
    currentFilter = filter;
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filterSurveys();
}

function filterSurveys() {
    const query = (document.getElementById('surveySearchInput')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('.survey-item-wrapper');
    let visibleCount = 0;

    items.forEach(item => {
        const itemStatus = item.getAttribute('data-status');
        const itemTitle = item.getAttribute('data-title') || '';
        const itemDesc = item.getAttribute('data-desc') || '';

        const matchesFilter = (currentFilter === 'all') || (currentFilter === itemStatus);
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

let currentHistoryFilter = 'all';

function setHistoryFilter(filter, btn) {
    currentHistoryFilter = filter;
    document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filterHistorySurveys();
}

function filterHistorySurveys() {
    const query = (document.getElementById('historySearchInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('.history-row-item');
    let visibleCount = 0;

    rows.forEach(row => {
        const title = row.getAttribute('data-title') || '';
        const isRecent = row.getAttribute('data-recent') === 'true';
        const isYear = row.getAttribute('data-year') === 'true';

        let matchesFilter = true;
        if (currentHistoryFilter === 'recent') matchesFilter = isRecent;
        if (currentHistoryFilter === 'year') matchesFilter = isYear;

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