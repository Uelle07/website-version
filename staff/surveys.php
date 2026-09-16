<?php
/**
 * staff/surveys.php
 * -------------------------------------------------------
 * Staff Portal — Survey Management Overview (SET B)
 * Create, edit, toggle status, and delete cooperative surveys.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$staffId = current_user_id();
$errors = [];

// =========================================================
// 1. HANDLE POST ACTIONS (CREATE, EDIT, STATUS, DELETE)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    // --- A. CREATE SURVEY ---
    if ($action === 'create_survey') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        $startDateDay  = trim($_POST['start_date'] ?? '');
        $startTime     = trim($_POST['start_time'] ?? '00:00');
        $endDateDay    = trim($_POST['end_date'] ?? '');
        $endTime       = trim($_POST['end_time'] ?? '23:59');

        if ($startDateDay !== '' && str_contains($startDateDay, 'T')) {
            $startDate = str_replace('T', ' ', $startDateDay);
        } else {
            $startDate = $startDateDay !== '' ? ($startDateDay . ' ' . ($startTime ?: '00:00') . ':00') : '';
        }

        if ($endDateDay !== '' && str_contains($endDateDay, 'T')) {
            $endDate = str_replace('T', ' ', $endDateDay);
        } else {
            $endDate = $endDateDay !== '' ? ($endDateDay . ' ' . ($endTime ?: '23:59') . ':00') : '';
        }

        // Logic Requirement: Survey automatically starts as a Draft upon creation
        $status = 'draft';

        if ($title === '') {
            $errors[] = 'Survey title is required.';
        }
        if ($startDate === '' || $endDate === '' || $startDateDay === '' || $endDateDay === '') {
            $errors[] = 'Opening date & time and closing date & time are required.';
        } else {
            $startTimestamp = strtotime($startDate);
            $endTimestamp   = strtotime($endDate);
            $currentDate    = date('Y-m-d');

            if ($startTimestamp === false || $endTimestamp === false) {
                $errors[] = 'Invalid date or time format provided.';
            } elseif ($startDateDay < $currentDate) {
                $errors[] = 'Invalid Starting Date: The starting date cannot be set before the current date (' . date('M d, Y') . ').';
            } elseif ($endTimestamp <= $startTimestamp) {
                $errors[] = 'Invalid Schedule: The closing date and time must be logically after the starting date and time.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "INSERT INTO surveys (title, description, start_date, end_date, status, created_by_staff_id)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$title, $description, $startDate, $endDate, $status, $staffId]);
            $newId = (int)$pdo->lastInsertId();

            set_flash('success', "Survey '{$title}' created as a Draft! Add questions and click Publish at the top right when ready.");
            redirect("staff/survey_questions.php?id={$newId}");
        }
    }

    // --- B. EDIT SURVEY ---
    elseif ($action === 'edit_survey') {
        $surveyId    = (int)($_POST['survey_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = trim($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'active', 'inactive', 'closed'], true)) {
            $status = 'draft';
        }

        $startDateDay  = trim($_POST['start_date'] ?? '');
        $startTime     = trim($_POST['start_time'] ?? '00:00');
        $endDateDay    = trim($_POST['end_date'] ?? '');
        $endTime       = trim($_POST['end_time'] ?? '23:59');

        if ($startDateDay !== '' && str_contains($startDateDay, 'T')) {
            $startDate = str_replace('T', ' ', $startDateDay);
        } else {
            $startDate = $startDateDay !== '' ? ($startDateDay . ' ' . ($startTime ?: '00:00') . ':00') : '';
        }

        if ($endDateDay !== '' && str_contains($endDateDay, 'T')) {
            $endDate = str_replace('T', ' ', $endDateDay);
        } else {
            $endDate = $endDateDay !== '' ? ($endDateDay . ' ' . ($endTime ?: '23:59') . ':00') : '';
        }

        if ($status === 'active' && $startDate !== '' && $startDate > date('Y-m-d H:i:s')) {
            $startDate = date('Y-m-d H:i:s');
        }

        if ($surveyId <= 0) {
            $errors[] = 'Invalid survey identifier.';
        }
        if ($title === '') {
            $errors[] = 'Survey title is required.';
        }
        if ($startDate === '' || $endDate === '' || $startDateDay === '' || $endDateDay === '') {
            $errors[] = 'Opening date & time and closing date & time are required.';
        } else {
            $startTimestamp = strtotime($startDate);
            $endTimestamp   = strtotime($endDate);

            if ($startTimestamp === false || $endTimestamp === false) {
                $errors[] = 'Invalid date or time format provided.';
            } elseif ($endTimestamp <= $startTimestamp) {
                $errors[] = 'Invalid Schedule: The closing date and time must be logically after the starting date and time.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "UPDATE surveys 
                 SET title = ?, description = ?, status = ?, start_date = ?, end_date = ?
                 WHERE survey_id = ?"
            );
            $stmt->execute([$title, $description, $status, $startDate, $endDate, $surveyId]);

            set_flash('success', "Survey '{$title}' updated successfully.");
            redirect('staff/surveys.php');
        }
    }

    // --- C. TOGGLE / QUICK UPDATE STATUS ---
    elseif ($action === 'update_status') {
        $surveyId  = (int)($_POST['survey_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        if ($surveyId > 0 && in_array($newStatus, ['draft', 'active', 'inactive', 'closed'], true)) {
            $stmt = $pdo->prepare("UPDATE surveys SET status = ? WHERE survey_id = ?");
            $stmt->execute([$newStatus, $surveyId]);

            set_flash('success', "Survey status updated to " . ucfirst($newStatus) . ".");
            redirect('staff/surveys.php');
        } else {
            $errors[] = 'Invalid status change request.';
        }
    }

    // --- D. DELETE SURVEY ---
    elseif ($action === 'delete_survey') {
        $surveyId = (int)($_POST['survey_id'] ?? 0);

        if ($surveyId > 0) {
            $stmt = $pdo->prepare("DELETE FROM surveys WHERE survey_id = ?");
            $stmt->execute([$surveyId]);

            set_flash('success', 'Survey and all related records deleted successfully.');
            redirect('staff/surveys.php');
        } else {
            $errors[] = 'Invalid survey ID for deletion.';
        }
    }
}

// =========================================================
// 2. FETCH SURVEYS & METRICS
// =========================================================
// Automatically sync and mark expired active surveys as closed
sync_expired_surveys($pdo);

$surveysStmt = $pdo->query(
    "SELECT s.*, 
            st.fullname AS staff_name,
            (SELECT COUNT(*) FROM survey_questions sq WHERE sq.survey_id = s.survey_id) AS questions_count,
            (SELECT COUNT(DISTINCT r.member_id) FROM responses r WHERE r.survey_id = s.survey_id) AS respondents_count
     FROM surveys s
     LEFT JOIN staff st ON st.staff_id = s.created_by_staff_id
     ORDER BY s.created_at DESC"
);
$surveys = $surveysStmt->fetchAll();

// Calculate Summary Metrics
$totalSurveys    = count($surveys);
$activeSurveys   = 0;
$totalResponses  = 0;
$draftSurveys    = 0;

foreach ($surveys as $s) {
    if ($s['status'] === 'active') $activeSurveys++;
    if ($s['status'] === 'draft' || $s['status'] === 'inactive' || $s['status'] === 'closed') $draftSurveys++;
    $totalResponses += (int)$s['respondents_count'];
}

$pageTitle = 'Survey Management';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div id="surveyManagementView" class="page-view active surveys-page">
  
  <style>
  /* ================= SURVEYS PAGE SCOPED RESPONSIVE LAYOUT ================= */
  .surveys-page {
    width: 100%;
  }

  .surveys-page .view-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  @media (max-width: 640px) {
    .surveys-page .view-header-wrapper {
      flex-direction: column;
      align-items: stretch;
      gap: 1rem;
    }
    .surveys-page .view-header-wrapper .btn-primary {
      width: 100%;
      justify-content: center;
    }
  }

  .surveys-page .surveys-metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    width: 100%;
  }

  @media (max-width: 991px) {
    .surveys-page .surveys-metrics-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 1.25rem;
    }
  }

  @media (max-width: 640px) {
    .surveys-page .surveys-metrics-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }
  }

  .surveys-page .surveys-controls-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    width: 100%;
  }

  .surveys-page .surveys-search-box {
    flex: 1;
    min-width: 260px;
    max-width: 560px;
  }

  .surveys-page .surveys-filter-group {
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }

  @media (max-width: 640px) {
    .surveys-page .surveys-controls-row {
      flex-direction: column;
      align-items: stretch;
      gap: 0.85rem;
    }
    .surveys-page .surveys-search-box {
      max-width: 100%;
      width: 100%;
    }
    .surveys-page .surveys-filter-group {
      width: 100%;
      justify-content: space-between;
    }
    .surveys-page .surveys-filter-group select {
      flex: 1;
      width: 100%;
    }
  }

  .surveys-page .surveys-table-container {
    margin-top: 0;
    margin-bottom: 1.5rem;
    width: 100%;
  }

  .surveys-page .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 16px;
    width: 100%;
  }

  .surveys-page .dashboard-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }

  .surveys-page .dashboard-table th,
  .surveys-page .dashboard-table td {
    padding: 1rem 1.15rem;
    vertical-align: middle;
  }

  .surveys-page .dashboard-table th {
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    color: var(--text-dim);
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--bg-card-border);
  }

  .surveys-page .dashboard-table td {
    border-bottom: 1px solid var(--bg-card-border);
    transition: background 0.2s ease;
  }

  .surveys-page .dashboard-table tbody tr:hover td {
    background: var(--badge-bg);
  }

  .surveys-page .survey-title-col {
    width: 27%;
    min-width: 200px;
    text-align: left;
  }

  .surveys-page .survey-status-col {
    width: 12%;
    min-width: 110px;
    text-align: center;
  }

  .surveys-page .survey-schedule-col {
    width: 24%;
    min-width: 215px;
    text-align: center;
  }

  .surveys-page .survey-questions-col {
    width: 10%;
    min-width: 90px;
    text-align: center;
  }

  .surveys-page .survey-respondents-col {
    width: 11%;
    min-width: 100px;
    text-align: center;
  }

  .surveys-page .survey-actions-col {
    width: 16%;
    min-width: 180px;
    text-align: center;
  }

  .surveys-page .table-actions-grid {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    margin: 0 auto;
    white-space: nowrap;
  }

  .surveys-page .table-actions-grid .icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.88rem;
    border: 1px solid transparent;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    flex-shrink: 0;
  }

  .surveys-page .table-actions-grid .icon-btn:hover {
    transform: translateY(-2px);
  }

  .surveys-page .survey-schedule-display {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-size: 0.83rem;
    line-height: 1.35;
  }
  </style>
  
  <!-- ================= 1. SURVEY MANAGEMENT HEADER ================= -->
  <div class="section-header-flex view-header-wrapper">
    <div class="section-title">
      <h2>Cooperative Survey Management</h2>
      <p class="section-subtitle">Create, monitor, and analyze member feedback surveys</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreateSurveyModal()" title="Create Survey">
      <i class="fa-solid fa-plus"></i> Create New Survey
    </button>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="modal-error-box" style="margin-bottom: 1.5rem;">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <?= implode('<br>', array_map('e', $errors)) ?>
    </div>
  <?php endif; ?>

  <!-- ================= 2. STATISTICS / SUMMARY METRICS ================= -->
  <div class="metrics-grid surveys-metrics-grid">
    <div class="metric-card hover-glow-card">
      <div class="metric-icon cyan">
        <i class="fa-solid fa-square-poll-vertical"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Total Surveys</span>
        <h3 class="metric-value"><?= number_format($totalSurveys) ?></h3>
        <span class="metric-subtext cyan"><i class="fa-solid fa-database"></i> All registered surveys</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald">
        <i class="fa-solid fa-tower-broadcast"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Active Surveys</span>
        <h3 class="metric-value"><?= number_format($activeSurveys) ?></h3>
        <span class="metric-subtext emerald positive"><i class="fa-solid fa-circle-check"></i> Open to members</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon purple">
        <i class="fa-solid fa-users"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Total Respondents</span>
        <h3 class="metric-value"><?= number_format($totalResponses) ?></h3>
        <span class="metric-subtext purple"><i class="fa-solid fa-user-check"></i> Unique submissions</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon amber" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
        <i class="fa-solid fa-file-pen"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Draft / Inactive</span>
        <h3 class="metric-value"><?= number_format($draftSurveys) ?></h3>
        <span class="metric-subtext amber"><i class="fa-solid fa-clock"></i> Unpublished / Closed</span>
      </div>
    </div>
  </div>

  <!-- ================= 3. SEARCH + FILTERS ================= -->
  <div class="side-by-side-controls surveys-controls-row" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search surveys-search-box" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="surveySearchInput" placeholder="Search surveys by title..." onkeyup="filterSurveysTable()" aria-label="Search surveys">
    </div>

    <!-- Themed Survey Status Filter Popover Menu -->
    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="surveysFilterBtn" onclick="toggleSurveysFilterMenu(event)" aria-label="Toggle filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="surveysFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders"></i> Filter Options</h4>
          <button type="button" class="close-popover-btn" onclick="toggleSurveysFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body">
          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-square-poll-vertical"></i> Survey Status:</label>
            <select id="surveyStatusFilter" class="table-filter-select-lg">
              <option value="all">All Surveys</option>
              <option value="draft">Draft</option>
              <option value="active">Active</option>
              <option value="closed">Closed/Inactive</option>
            </select>
          </div>
        </div>

        <div class="filter-popover-footer">
          <button type="button" class="btn btn-primary btn-sm" onclick="applySurveysFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= 4. SURVEY LIST / TABLE ================= -->
  <div class="table-card surveys-table-container">
    <div class="table-responsive">
      <table class="dashboard-table" id="surveysTable">
        <thead>
          <tr>
            <th class="survey-title-col">Survey Title</th>
            <th class="survey-status-col">Status</th>
            <th class="survey-schedule-col">Schedule Dates</th>
            <th class="survey-questions-col">Questions</th>
            <th class="survey-respondents-col">Respondents</th>
            <th class="survey-actions-col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($surveys)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-5">
                <i class="fa-solid fa-square-poll-vertical fs-2 mb-2 d-block" style="opacity: 0.35;"></i>
                No cooperative surveys found. Click <strong>"Create New Survey"</strong> above to launch your first survey!
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($surveys as $survey): ?>
              <?php
                $statusPill = match($survey['status']) {
                  'active'   => '<span class="status-pill status-active"><i class="fa-solid fa-circle-check" style="font-size:0.65rem;margin-right:4px;"></i> Active</span>',
                  'draft'    => '<span class="status-pill status-default"><i class="fa-solid fa-file-pen" style="font-size:0.65rem;margin-right:4px;"></i> Draft</span>',
                  'inactive' => '<span class="status-pill" style="background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.25); color: #cbd5e1;"><i class="fa-solid fa-pause" style="font-size:0.65rem;margin-right:4px;"></i> Inactive</span>',
                  'closed'   => '<span class="status-pill status-locked"><span class="locked-title">Closed</span></span>',
                  default    => '<span class="status-pill">' . e(ucfirst($survey['status'])) . '</span>'
                };
              ?>
              <tr data-status="<?= e($survey['status']) ?>">
                <td class="survey-title-col" style="vertical-align: middle;">
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); line-height: 1.35;">
                    <a href="survey_questions.php?id=<?= (int)$survey['survey_id'] ?>" style="color: var(--text-main); text-decoration: none; transition: color 0.2s ease;" onmouseover="this.style.color='var(--primary-teal)'" onmouseout="this.style.color='var(--text-main)'">
                      <?= e($survey['title']) ?>
                    </a>
                  </div>
                </td>
                <td class="survey-status-col" style="text-align: center; vertical-align: middle;">
                  <?= $statusPill ?>
                </td>
                <td class="survey-schedule-col" style="text-align: center; vertical-align: middle;">
                  <div class="survey-schedule-display">
                    <span style="font-size: 0.83rem; font-weight: 600; color: var(--text-main); white-space: nowrap;">
                      <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal); margin-right: 5px; font-size: 0.78rem;"></i>
                      <?= date('M d, Y · g:i A', strtotime($survey['start_date'])) ?>
                    </span>
                    <span style="font-size: 0.76rem; color: var(--text-dim); margin-top: 2px; white-space: nowrap;">
                      to <?= date('M d, Y · g:i A', strtotime($survey['end_date'])) ?>
                    </span>
                  </div>
                </td>
                <td class="survey-questions-col" style="text-align: center; vertical-align: middle;">
                  <span class="status-pill" style="background: rgba(0, 194, 168, 0.1); border: 1px solid rgba(0, 194, 168, 0.25); color: var(--primary-teal); font-weight: 700; padding: 4px 11px; font-size: 0.82rem; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="fa-solid fa-list-check" style="font-size: 0.74rem; margin-right: 4px;"></i> <?= (int)$survey['questions_count'] ?>
                  </span>
                </td>
                <td class="survey-respondents-col" style="text-align: center; vertical-align: middle;">
                  <span class="status-pill" style="background: rgba(139, 92, 246, 0.12); border: 1px solid rgba(139, 92, 246, 0.25); color: #c084fc; font-weight: 700; padding: 4px 11px; font-size: 0.82rem; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="fa-solid fa-users" style="font-size: 0.74rem; margin-right: 4px;"></i> <?= (int)$survey['respondents_count'] ?>
                  </span>
                </td>
                <td class="survey-actions-col" style="text-align: center; vertical-align: middle;">
                  <div class="table-actions-grid">
                    <a href="survey_questions.php?id=<?= (int)$survey['survey_id'] ?>" class="icon-btn btn-view-dynamic" title="Questionnaire Management (Questions)" aria-label="Questionnaire Management (Questions)"><i class="fa-solid fa-list-check"></i></a>
                    <a href="survey_questions.php?id=<?= (int)$survey['survey_id'] ?>#responses" class="icon-btn btn-print-dynamic" title="Survey Responses" aria-label="Survey Responses"><i class="fa-solid fa-square-poll-vertical"></i></a>
                    <a href="survey_questions.php?id=<?= (int)$survey['survey_id'] ?>#settings" class="icon-btn btn-edit-dynamic" title="Edit Survey (Settings)" aria-label="Edit Survey (Settings)"><i class="fa-solid fa-pen"></i></a>
                    <button type="button" class="icon-btn btn-delete-dynamic" title="Delete Survey" aria-label="Delete Survey" onclick="openDeleteSurveyModal(<?= (int)$survey['survey_id'] ?>, '<?= e(addslashes($survey['title'])) ?>')"><i class="fa-solid fa-trash"></i></button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="surveysNoMatchesRow" style="display: none;">
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> No cooperative survey records match your search or filter criteria.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ================= 5. SURVEY LIVE PREVIEW CARD ================= -->
  <div class="section-header-flex view-header-wrapper" style="margin-top: 2.25rem; margin-bottom: 1.25rem;">
    <div class="section-title">
      <h3><i class="fa-solid fa-eye text-cyan me-2"></i> Survey Live Preview</h3>
      <p class="section-subtitle">Real-time overview of active and draft surveys with live schedule status, respondent counts, and questionnaire details</p>
    </div>
  </div>

  <!-- Side-by-Side Search Bar & Filter Button for Live Preview -->
  <div class="side-by-side-controls surveys-controls-row" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search surveys-search-box" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="surveyLivePreviewSearch" placeholder="Search survey preview by title, schedule..." onkeyup="filterSurveyLivePreviewTable()" aria-label="Search Survey Live Preview">
    </div>

    <!-- Filter Popover Menu for Live Preview -->
    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="surveyLivePreviewFilterBtn" onclick="toggleSurveyLivePreviewFilterMenu(event)" aria-label="Toggle live preview filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="surveyLivePreviewFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Live Preview</h4>
          <button type="button" class="close-popover-btn" onclick="toggleSurveyLivePreviewFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body">
          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-square-poll-vertical"></i> Status Filter:</label>
            <select id="livePreviewStatusFilter" class="table-filter-select-lg">
              <option value="all">All Statuses</option>
              <option value="active">Active Only</option>
              <option value="draft">Draft Only</option>
              <option value="closed">Closed/Inactive Only</option>
            </select>
          </div>

          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-clock-rotate-left"></i> Timeline Window:</label>
            <select id="livePreviewTimelineFilter" class="table-filter-select-lg">
              <option value="all">All Timelines</option>
              <option value="currently_open">Currently Open (Within Schedule)</option>
              <option value="upcoming">Upcoming / Scheduled</option>
              <option value="ended">Ended / Expired</option>
            </select>
          </div>
        </div>

        <div class="filter-popover-footer">
          <button type="button" class="btn btn-primary btn-sm" onclick="applySurveyLivePreviewFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Survey Live Preview Table Card -->
  <div class="table-card survey-preview-card" style="margin-top: 0; margin-bottom: 2rem;">
    <div class="table-responsive">
      <table class="dashboard-table centered-preview-table" id="surveyLivePreviewTable">
        <thead>
          <tr>
            <th style="width: 14%; text-align: center;">Survey Code</th>
            <th style="width: 29%; text-align: left;">Survey Title</th>
            <th style="width: 12%; text-align: center;">Status</th>
            <th style="width: 23%; text-align: center;">Schedule Window</th>
            <th style="width: 11%; text-align: center;">Questions</th>
            <th style="width: 11%; text-align: center;">Submissions</th>
          </tr>
        </thead>
        <tbody id="surveyLivePreviewTableBody">
          <?php if (empty($surveys)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <i class="fa-solid fa-eye-slash fs-3 mb-2 d-block opacity-40"></i>
                No survey records available for live preview.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($surveys as $s): ?>
              <?php
                $nowTs = time();
                $startTs = strtotime($s['start_date']);
                $endTs = strtotime($s['end_date']);
                
                $timelineState = 'currently_open';
                if ($startTs > $nowTs) {
                    $timelineState = 'upcoming';
                } elseif ($endTs < $nowTs) {
                    $timelineState = 'ended';
                }

                $statusPillLive = match($s['status']) {
                  'active'   => '<span class="status-pill status-active"><i class="fa-solid fa-circle-check" style="font-size:0.65rem;margin-right:4px;"></i> Active</span>',
                  'draft'    => '<span class="status-pill status-default"><i class="fa-solid fa-file-pen" style="font-size:0.65rem;margin-right:4px;"></i> Draft</span>',
                  'inactive' => '<span class="status-pill" style="background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.25); color: #cbd5e1;"><i class="fa-solid fa-pause" style="font-size:0.65rem;margin-right:4px;"></i> Inactive</span>',
                  'closed'   => '<span class="status-pill status-locked"><span class="locked-title">Closed</span></span>',
                  default    => '<span class="status-pill">' . e(ucfirst($s['status'])) . '</span>'
                };

                $surveyCode = 'SRV-' . str_pad((string)$s['survey_id'], 3, '0', STR_PAD_LEFT);
                $searchData = strtolower($surveyCode . ' ' . $s['title'] . ' ' . ($s['staff_name'] ?? '') . ' ' . $s['status']);
              ?>
              <tr class="live-preview-row" data-status="<?= e($s['status']) ?>" data-timeline="<?= e($timelineState) ?>" data-search="<?= e($searchData) ?>">
                <td style="text-align: center; vertical-align: middle;"><strong class="account-tag"><?= e($surveyCode) ?></strong></td>
                <td style="vertical-align: middle;">
                  <div style="font-weight: 700; color: var(--text-main); font-size: 0.92rem; margin-bottom: 0;">
                    <a href="survey_questions.php?id=<?= (int)$s['survey_id'] ?>" style="color: var(--text-main); text-decoration: none; transition: color 0.2s ease;" onmouseover="this.style.color='var(--primary-teal)'" onmouseout="this.style.color='var(--text-main)'">
                      <?= e($s['title']) ?>
                    </a>
                  </div>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <?= $statusPillLive ?>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <div class="survey-schedule-display">
                    <span style="font-size: 0.81rem; font-weight: 600; color: var(--text-main); white-space: nowrap;">
                      <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal); margin-right: 4px; font-size: 0.76rem;"></i>
                      <?= date('M d, Y · g:i A', $startTs) ?>
                    </span>
                    <span style="color: var(--text-dim); font-size: 0.76rem; margin-top: 2px; white-space: nowrap;">
                      to <?= date('M d, Y · g:i A', $endTs) ?>
                    </span>
                  </div>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <span class="status-pill" style="background: rgba(0, 194, 168, 0.1); border: 1px solid rgba(0, 194, 168, 0.25); color: var(--primary-teal); font-weight: 700; padding: 4px 11px; font-size: 0.82rem; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="fa-solid fa-list-check" style="font-size: 0.72rem; margin-right: 3px;"></i> <?= (int)$s['questions_count'] ?>
                  </span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <span class="status-pill" style="background: rgba(139, 92, 246, 0.12); border: 1px solid rgba(139, 92, 246, 0.25); color: #c084fc; font-weight: 700; padding: 4px 11px; font-size: 0.82rem; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="fa-solid fa-users" style="font-size: 0.72rem; margin-right: 3px;"></i> <?= (int)$s['respondents_count'] ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr id="livePreviewNoMatchesRow" style="display: none;">
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> No live survey preview records match your search or filter criteria.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- =========================================================
     MODAL 1: CREATE SURVEY MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="createSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 650px;">
    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0;"><i class="fa-solid fa-plus" style="color: var(--primary-teal);"></i> Create New Survey</h3>
      <button type="button" class="close-popover-btn" onclick="closeModal('createSurveyModal')" aria-label="Close modal" style="background: none; border: none; color: var(--text-muted); font-size: 1.3rem; cursor: pointer; padding: 4px; display: inline-flex; align-items: center; justify-content: center; line-height: 1; transition: color 0.2s ease;" onmouseover="this.style.color='var(--text-main)'" onmouseout="this.style.color='var(--text-muted)'">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <form action="surveys.php" method="POST" onsubmit="return validateSurveySchedule(this);">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create_survey">

      <div class="form-grid">
        <div class="form-group full-width">
          <label>Survey Title <span style="color: #f87171;">*</span></label>
          <input type="text" name="title" placeholder="e.g., 2026 Member Satisfaction Survey" required>
        </div>

        <div class="form-group full-width">
          <label>Description / Instructions</label>
          <textarea name="description" rows="3" placeholder="Explain the purpose of this survey to cooperative members..."></textarea>
        </div>

        <div class="form-group">
          <label>Opening Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="start_date" id="createStartDate" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
          <label>Opening Time <span style="color: #f87171;">*</span></label>
          <input type="time" name="start_time" id="createStartTime" value="<?= date('H:i') ?>" required>
        </div>

        <div class="form-group">
          <label>Closing Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="end_date" id="createEndDate" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" min="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
          <label>Closing Time <span style="color: #f87171;">*</span></label>
          <input type="time" name="end_time" id="createEndTime" value="23:59" required>
        </div>
      </div>

      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary" style="padding: 9px 26px; font-weight: 700; border-radius: 8px;">
          Proceed
        </button>
      </div>
    </form>
  </div>
</div>

<!-- =========================================================
     MODAL 2: EDIT SURVEY MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="editSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 650px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen-to-square" style="color: var(--primary-teal);"></i> Edit Survey Details</h3>
    </div>
    <form action="surveys.php" method="POST" onsubmit="return validateSurveySchedule(this);">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="edit_survey">
      <input type="hidden" name="survey_id" id="editSurveyId" value="">

      <div class="form-grid">
        <div class="form-group full-width">
          <label>Survey Title <span style="color: #f87171;">*</span></label>
          <input type="text" name="title" id="editSurveyTitle" required>
        </div>

        <div class="form-group full-width">
          <label>Survey Status <span style="color: #f87171;">*</span></label>
          <select name="status" id="editSurveyStatus" required style="height: 44px; width: 100%; border-radius: 10px; padding: 0.6rem 0.95rem; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            <option value="active">Active</option>
            <option value="inactive">Inactive / Closed</option>
            <option value="draft">Draft</option>
          </select>
        </div>

        <div class="form-group full-width">
          <label>Description / Instructions</label>
          <textarea name="description" id="editSurveyDescription" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label>Opening Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="start_date" id="editSurveyStartDate" required>
        </div>

        <div class="form-group">
          <label>Opening Time <span style="color: #f87171;">*</span></label>
          <input type="time" name="start_time" id="editSurveyStartTime" required>
        </div>

        <div class="form-group">
          <label>Closing Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="end_date" id="editSurveyEndDate" required>
        </div>

        <div class="form-group">
          <label>Closing Time <span style="color: #f87171;">*</span></label>
          <input type="time" name="end_time" id="editSurveyEndTime" required>
        </div>
      </div>

      <div class="modal-actions" style="margin-top: 1.5rem;">
        <button type="button" class="btn btn-cancel" onclick="closeModal('editSurveyModal')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="promptConfirmEditSurveyModal()">
          <i class="fa-solid fa-floppy-disk"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<!-- =========================================================
     MODAL 3: DELETE SURVEY MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="deleteSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Delete Survey</h3>
    </div>
    <form action="surveys.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="delete_survey">
      <input type="hidden" name="survey_id" id="deleteSurveyId" value="">

      <div class="modal-body">
        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.75rem;">
          Are you sure you want to permanently delete <strong id="deleteSurveyTitle" style="color: var(--text-main);"></strong>?
        </p>
        <p style="color: #f87171; font-size: 0.85rem; margin: 0; background: rgba(239, 68, 68, 0.12); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
          <i class="fa-solid fa-circle-exclamation me-1"></i>
          This will remove all associated questions, choices, and submitted member responses. This action cannot be undone.
        </p>
      </div>

      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
        <button type="button" class="btn btn-secondary btn-cancel" onclick="closeModal('deleteSurveyModal')">Cancel</button>
        <button type="submit" class="btn btn-danger" style="margin-left: auto;">
          <i class="fa-solid fa-trash-can"></i> Yes, Delete Survey
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.remove('hidden');
    el.style.display = 'flex';
  }
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) {
    el.classList.add('hidden');
    el.style.display = 'none';
  }
}

function openCreateSurveyModal() {
  openModal('createSurveyModal');
}

function openEditSurveyModal(survey) {
  document.getElementById('editSurveyId').value = survey.survey_id;
  document.getElementById('editSurveyTitle').value = survey.title;
  document.getElementById('editSurveyDescription').value = survey.description || '';
  if (document.getElementById('editSurveyStatus')) {
    const s = survey.status || 'draft';
    document.getElementById('editSurveyStatus').value = (s === 'closed' ? 'inactive' : s);
  }
  
  if (survey.start_date) {
    const sParts = survey.start_date.split(' ');
    document.getElementById('editSurveyStartDate').value = sParts[0] || '';
    document.getElementById('editSurveyStartTime').value = (sParts[1] || '00:00').slice(0, 5);
  } else {
    document.getElementById('editSurveyStartDate').value = '';
    document.getElementById('editSurveyStartTime').value = '';
  }

  if (survey.end_date) {
    const eParts = survey.end_date.split(' ');
    document.getElementById('editSurveyEndDate').value = eParts[0] || '';
    document.getElementById('editSurveyEndTime').value = (eParts[1] || '23:59').slice(0, 5);
  } else {
    document.getElementById('editSurveyEndDate').value = '';
    document.getElementById('editSurveyEndTime').value = '';
  }

  openModal('editSurveyModal');
}

function openDeleteSurveyModal(id, title) {
  document.getElementById('deleteSurveyId').value = id;
  document.getElementById('deleteSurveyTitle').textContent = `"${title}"`;
  openModal('deleteSurveyModal');
}

window.appliedSurveyStatusFilter = window.appliedSurveyStatusFilter || 'all';
window.appliedLivePreviewStatusFilter = window.appliedLivePreviewStatusFilter || 'all';
window.appliedLivePreviewTimelineFilter = window.appliedLivePreviewTimelineFilter || 'all';

function toggleSurveysFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('surveysFilterPopover');
  if (popover) {
    const isOpening = popover.classList.contains('hidden');
    if (isOpening) {
      const select = document.getElementById('surveyStatusFilter');
      if (select && window.appliedSurveyStatusFilter) {
        select.value = window.appliedSurveyStatusFilter;
      }
    }
    popover.classList.toggle('hidden');
  }
}

function applySurveysFilters(event) {
  if (event) event.stopPropagation();
  const statusSelect = document.getElementById('surveyStatusFilter');
  if (statusSelect) {
    window.appliedSurveyStatusFilter = (statusSelect.value || 'all').toLowerCase().trim();
  }
  const popover = document.getElementById('surveysFilterPopover');
  if (popover) popover.classList.add('hidden');
  filterSurveysTable();
}

function filterSurveysTable() {
  const search = (document.getElementById('surveySearchInput')?.value || '').toLowerCase().trim();
  const status = (window.appliedSurveyStatusFilter || 'all').toLowerCase().trim();
  const rows = document.querySelectorAll('#surveysTable tbody tr[data-status]');
  let matchCount = 0;

  rows.forEach(row => {
    const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
    const text = row.textContent.toLowerCase();

    const matchesSearch = !search || text.includes(search);
    const isClosedOrInactive = (rowStatus === 'closed' || rowStatus === 'inactive');
    const matchesStatus = (
      status === 'all' || 
      ((status === 'closed' || status === 'inactive') && isClosedOrInactive) ||
      rowStatus === status
    );

    if (matchesSearch && matchesStatus) {
      row.style.display = '';
      matchCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noMatches = document.getElementById('surveysNoMatchesRow');
  if (noMatches) {
    noMatches.style.display = (matchCount === 0 && rows.length > 0) ? '' : 'none';
  }
}

function toggleSurveyLivePreviewFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('surveyLivePreviewFilterPopover');
  if (popover) {
    const isOpening = popover.classList.contains('hidden');
    if (isOpening) {
      const statusSel = document.getElementById('livePreviewStatusFilter');
      if (statusSel && window.appliedLivePreviewStatusFilter) {
        statusSel.value = window.appliedLivePreviewStatusFilter;
      }
      const timelineSel = document.getElementById('livePreviewTimelineFilter');
      if (timelineSel && window.appliedLivePreviewTimelineFilter) {
        timelineSel.value = window.appliedLivePreviewTimelineFilter;
      }
    }
    popover.classList.toggle('hidden');
  }
}

function applySurveyLivePreviewFilters(event) {
  if (event) event.stopPropagation();
  const statusSel = document.getElementById('livePreviewStatusFilter');
  if (statusSel) {
    window.appliedLivePreviewStatusFilter = (statusSel.value || 'all').toLowerCase().trim();
  }
  const timelineSel = document.getElementById('livePreviewTimelineFilter');
  if (timelineSel) {
    window.appliedLivePreviewTimelineFilter = (timelineSel.value || 'all').toLowerCase().trim();
  }
  const popover = document.getElementById('surveyLivePreviewFilterPopover');
  if (popover) popover.classList.add('hidden');
  filterSurveyLivePreviewTable();
}

function filterSurveyLivePreviewTable() {
  const search = (document.getElementById('surveyLivePreviewSearch')?.value || '').toLowerCase().trim();
  const status = (window.appliedLivePreviewStatusFilter || 'all').toLowerCase().trim();
  const timeline = (window.appliedLivePreviewTimelineFilter || 'all').toLowerCase().trim();
  const rows = document.querySelectorAll('#surveyLivePreviewTable tbody tr.live-preview-row');
  let matchCount = 0;

  rows.forEach(row => {
    const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
    const rowTimeline = (row.getAttribute('data-timeline') || '').toLowerCase().trim();
    const rowSearch = (row.getAttribute('data-search') || row.textContent).toLowerCase();

    const matchesSearch = !search || rowSearch.includes(search);
    const isClosedOrInactive = (rowStatus === 'closed' || rowStatus === 'inactive');
    const matchesStatus = (
      status === 'all' || 
      ((status === 'closed' || status === 'inactive') && isClosedOrInactive) ||
      rowStatus === status
    );
    const matchesTimeline = (timeline === 'all' || rowTimeline === timeline);

    if (matchesSearch && matchesStatus && matchesTimeline) {
      row.style.display = '';
      matchCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noMatches = document.getElementById('livePreviewNoMatchesRow');
  if (noMatches) {
    noMatches.style.display = (matchCount === 0 && rows.length > 0) ? '' : 'none';
  }
}

// Close popovers on document click
document.addEventListener('click', function(e) {
  if (!e.target.closest('.unified-filter-wrapper')) {
    document.querySelectorAll('.unified-filter-card').forEach(p => p.classList.add('hidden'));
  }
});

function validateSurveySchedule(form) {
  const startDateInput = form.querySelector('[name="start_date"]');
  const startTimeInput = form.querySelector('[name="start_time"]');
  const endDateInput = form.querySelector('[name="end_date"]');
  const endTimeInput = form.querySelector('[name="end_time"]');

  if (!startDateInput || !endDateInput) return true;

  const startDateVal = startDateInput.value;
  const startTimeVal = startTimeInput ? (startTimeInput.value || '00:00') : '00:00';
  const endDateVal = endDateInput.value;
  const endTimeVal = endTimeInput ? (endTimeInput.value || '23:59') : '23:59';

  // Get local date YYYY-MM-DD
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const today = `${year}-${month}-${day}`;
  
  if (startDateVal < today) {
    if (typeof showToast === 'function') {
      showToast('error', 'Invalid Starting Date', 'The starting date cannot be set before the current date (' + today + ').');
    } else {
      alert('The starting date cannot be set before the current date (' + today + ').');
    }
    startDateInput.focus();
    return false;
  }

  const startDateTime = new Date(`${startDateVal}T${startTimeVal}`);
  const endDateTime = new Date(`${endDateVal}T${endTimeVal}`);

  if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
    if (typeof showToast === 'function') {
      showToast('error', 'Invalid Date or Time', 'Please enter valid dates and times.');
    } else {
      alert('Please enter valid dates and times.');
    }
    return false;
  }

  if (endDateTime <= startDateTime) {
    if (typeof showToast === 'function') {
      showToast('error', 'Invalid Schedule', 'The closing date and time must be logically after the starting date and time.');
    } else {
      alert('The closing date and time must be logically after the starting date and time.');
    }
    endDateInput.focus();
    return false;
  }

  return true;
}

function promptConfirmEditSurveyModal() {
  const form = document.querySelector('#editSurveyModal form');
  if (!form) return;

  const titleInput = form.querySelector('#editSurveyTitle');
  if (titleInput && !titleInput.value.trim()) {
    if (typeof showToast === 'function') {
      showToast('error', 'Validation Warning', 'Survey title is required.');
    } else {
      alert('Survey title is required.');
    }
    titleInput.focus();
    return;
  }

  if (typeof validateSurveySchedule === 'function') {
    if (!validateSurveySchedule(form)) return;
  }

  const title = titleInput ? titleInput.value.trim() : 'this survey';
  const statusSelect = form.querySelector('#editSurveyStatus');
  const statusText = statusSelect ? statusSelect.options[statusSelect.selectedIndex].text : 'Active';

  if (typeof showSystemConfirm === 'function') {
    showSystemConfirm({
      title: 'Confirm Save Changes',
      message: `Are you sure you want to save changes to survey <strong>"${title}"</strong>?<br><span style="color: var(--text-dim); font-size: 0.86rem; display: block; margin-top: 0.45rem;">Status will be updated to <strong style="color: var(--primary-teal);">${statusText}</strong> and all modified details will be updated in the system.</span>`,
      confirmText: 'Yes, Save Changes',
      cancelText: 'Cancel',
      icon: 'fa-solid fa-floppy-disk',
      iconColor: 'var(--primary-teal)',
      isDanger: false,
      onConfirm: () => {
        form.submit();
      }
    });
  } else {
    if (confirm(`Are you sure you want to save changes to survey "${title}"?`)) {
      form.submit();
    }
  }
}
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

