<?php
require_once __DIR__ . '/../config/init.php';
$surveyId = $_GET['id'] ?? '';
$pageTitle = 'Manage Questions';

$isPublished = false;
$memberAnswersMap = [];
$questions = [];
$choicesByQuestion = [];

require_once __DIR__ . '/../pages/header_staff.php';
?>
<script>
  window.SURVEY_ID = "<?= htmlspecialchars($surveyId) ?>";
</script>
<div id="surveyQuestionsView" class="page-view active">
  
  <style>
  /* ================= IN-CARD BACK BUTTON ================= */
  .back-nav-btn {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--bg-card-border);
    color: var(--text-main);
    font-size: 0.95rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
    outline: none !important;
  }

  .back-nav-btn i {
    font-size: 0.95rem;
    color: var(--text-main);
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .back-nav-btn:hover {
    background: rgba(0, 194, 168, 0.15);
    border-color: rgba(0, 194, 168, 0.45);
    color: #00e6c7;
    transform: translateX(-2px);
    box-shadow: 0 0 12px rgba(0, 194, 168, 0.25);
  }

  .back-nav-btn:hover i {
    color: #00e6c7;
  }

  .back-nav-btn:active {
    transform: scale(0.95);
  }

  html[data-theme="light"] .back-nav-btn {
    background: rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.12);
    color: #334155;
  }

  html[data-theme="light"] .back-nav-btn i {
    color: #334155;
  }

  html[data-theme="light"] .back-nav-btn:hover {
    background: rgba(0, 194, 168, 0.1);
    border-color: rgba(0, 194, 168, 0.4);
    color: #00897b;
  }

  html[data-theme="light"] .back-nav-btn:hover i {
    color: #00897b;
  }

  /* ================= QUESTIONNAIRE PILL TABNAV ================= */
  .questionnaire-tabnav-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 1.75rem;
    width: 100%;
  }

  .questionnaire-tab-track {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(8, 12, 22, 0.95) 100%);
    border: 1px solid rgba(0, 194, 168, 0.25);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 194, 168, 0.1);
    border-radius: 50px;
    padding: 6px 8px;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .questionnaire-tab-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid transparent;
    border-radius: 40px;
    padding: 9px 22px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    white-space: nowrap;
    outline: none !important;
  }

  .questionnaire-tab-btn i {
    color: var(--text-muted);
    font-size: 0.88rem;
    transition: color 0.25s ease;
  }

  .questionnaire-tab-btn:hover {
    color: var(--text-main);
    background: rgba(255, 255, 255, 0.08);
  }

  .questionnaire-tab-btn:hover i {
    color: var(--text-main);
  }

  .questionnaire-tab-btn.active-tab {
    background: rgba(0, 194, 168, 0.2) !important;
    color: #00e6c7 !important;
    font-weight: 700 !important;
    border: 1px solid rgba(0, 194, 168, 0.4) !important;
    box-shadow: none !important;
  }

  .questionnaire-tab-btn.active-tab i {
    color: #00e6c7 !important;
  }

  .responses-subtab-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid transparent;
  }
  .responses-subtab-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-main);
  }
  .responses-subtab-btn.active-subtab {
    background: rgba(0, 194, 168, 0.2) !important;
    color: #00e6c7 !important;
    border-color: rgba(0, 194, 168, 0.4) !important;
    font-weight: 700 !important;
  }

  /* Question Count Indicator Pill */
  .questions-count-pill {
    background: rgba(0, 194, 168, 0.1);
    border: 1px solid rgba(0, 194, 168, 0.3);
    color: var(--primary-teal);
    font-weight: 700;
    border-radius: 8px;
    padding: 0 14px;
    height: 34px;
    font-size: 0.84rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    box-sizing: border-box;
    line-height: 1;
    transition: all 0.25s ease;
  }

  /* Published Status Badge (Clean, Symmetrical, No Glowing/Pulsing Effect) */
  .published-status-badge {
    height: 34px;
    padding: 0 14px;
    font-size: 0.84rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    background: rgba(16, 185, 129, 0.15) !important;
    border: 1px solid rgba(16, 185, 129, 0.35) !important;
    color: #34d399 !important;
    border-radius: 8px !important;
    box-sizing: border-box;
    line-height: 1;
    box-shadow: none !important;
    text-shadow: none !important;
    animation: none !important;
  }

  html[data-theme="light"] .published-status-badge {
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
    color: #047857 !important;
  }

  /* ================= ADD QUESTION MODAL SCOPED STYLES ================= */
  .add-question-modal .add-question-card {
    background: linear-gradient(145deg, #0b1120 0%, #060913 100%) !important;
    border: 1px solid rgba(0, 194, 168, 0.28) !important;
    border-radius: 20px !important;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75), 0 0 30px rgba(0, 194, 168, 0.12) !important;
    max-width: 720px;
    padding: 1.85rem 2rem;
  }
  .add-question-modal .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 1rem;
    margin-bottom: 1.4rem;
  }
  .add-question-modal .modal-header h3 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }
  .add-question-modal .modal-header h3 i {
    color: #00e6c7;
    font-size: 1.1rem;
  }
  .add-question-modal .two-col-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
  }
  @media (max-width: 640px) {
    .add-question-modal .two-col-grid {
      grid-template-columns: 1fr;
    }
  }
  .add-question-modal label {
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--text-dim);
    margin-bottom: 0.45rem;
    display: block;
  }
  .add-question-modal label .req-star {
    color: #f87171;
    font-weight: 700;
  }
  .add-question-modal input[type="text"],
  .add-question-modal select,
  .add-question-modal textarea {
    background: rgba(255, 255, 255, 0.04) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    color: #ffffff !important;
    border-radius: 10px !important;
    padding: 0.7rem 0.95rem !important;
    font-size: 0.9rem !important;
    outline: none !important;
    width: 100%;
    transition: all 0.2s ease;
  }
  .add-question-modal input[type="text"]::placeholder,
  .add-question-modal textarea::placeholder {
    color: rgba(255, 255, 255, 0.35) !important;
  }
  .add-question-modal input[type="text"]:focus,
  .add-question-modal select:focus,
  .add-question-modal textarea:focus {
    background: rgba(255, 255, 255, 0.07) !important;
    border-color: #00c2a8 !important;
    box-shadow: 0 0 16px rgba(0, 194, 168, 0.25) !important;
  }
  .add-question-modal select option {
    background-color: #0b1120 !important;
    color: #ffffff !important;
  }
  .add-question-modal .btn-pill-add-opt {
    background: rgba(0, 194, 168, 0.12);
    color: #00e6c7;
    border: 1px solid rgba(0, 194, 168, 0.35);
    border-radius: 20px;
    padding: 4px 13px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }
  .add-question-modal .btn-pill-add-opt:hover {
    background: rgba(0, 194, 168, 0.22);
    border-color: #00e6c7;
    box-shadow: 0 0 10px rgba(0, 194, 168, 0.3);
    color: #ffffff;
  }
  .add-question-modal .modal-footer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.6rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
  }
  .add-question-modal .btn-plain-cancel {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 8px 14px;
    cursor: pointer;
    transition: color 0.2s ease;
  }
  .add-question-modal .btn-plain-cancel:hover {
    color: #ffffff;
    text-decoration: underline;
  }
  .add-question-modal .btn-gradient-save {
    background: linear-gradient(135deg, #00c2a8 0%, #3b82f6 100%);
    color: #ffffff;
    border: none;
    font-weight: 700;
    font-size: 0.9rem;
    border-radius: 10px;
    padding: 9px 24px;
    box-shadow: 0 4px 16px rgba(0, 194, 168, 0.3);
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
  }
  .add-question-modal .btn-gradient-save:hover {
    box-shadow: 0 6px 20px rgba(0, 194, 168, 0.45);
    transform: translateY(-1px);
    filter: brightness(1.08);
  }
  </style>

  <!-- ================= SINGLE COHESIVE QUESTIONNAIRE HEADER CARD ================= -->
  <div class="table-card" style="margin-top: 0; margin-bottom: 1.5rem; padding: 1.5rem 1.75rem;">
    
    <!-- Row 1: Back Button + Eyebrow Heading + Top Right Publish Button & Question Count -->
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.95rem; flex-wrap: wrap;">
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <a href="surveys.php" class="back-nav-btn" title="Back to Survey Management" aria-label="Back to Survey Management">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; color: var(--primary-teal); letter-spacing: 0.8px; display: inline-flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-clipboard-list"></i> QUESTIONNAIRE MANAGEMENT
        </div>
      </div>

      <!-- Top Right: Question Count & Publish Action Trigger / Status Indicator -->
      <div class="d-inline-flex align-items-center gap-2">
        <span class="questions-count-pill" title="Total questions in this survey">
          <i class="fa-solid fa-list-check"></i>
          <span><span id="qCountTop">0</span> Questions</span>
        </span>
        <div id="publishStatusContainer">
          <span class="status-pill status-active published-status-badge">
            <i class="fa-solid fa-circle-check"></i> Published
          </span>
        </div><div id="publishFormContainer">
          <form>
            <input type="hidden" name="csrf_token" value="">
            <input type="hidden" name="action" value="publish_survey">
            <button type="button" class="btn btn-primary btn-sm" onclick="publishSurvey()" style="padding: 0 16px; height: 34px; font-size: 0.84rem; font-weight: 700; border-radius: 8px; background: linear-gradient(135deg, #00c2a8 0%, #3b82f6 100%); border: none; box-shadow: 0 4px 14px rgba(0, 194, 168, 0.35); display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem; line-height: 1; box-sizing: border-box;">
              <i class="fa-solid fa-paper-plane"></i> Publish
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Row 2: Survey Title (Strongest Visual Element) & Description -->
    <div style="margin-bottom: 1.25rem;">
      <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.45rem; line-height: 1.3; letter-spacing: -0.3px;">
        <span id="surveyTitleDisp">Loading...</span>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0; max-width: 820px; line-height: 1.55;">
        <span id="surveyDescDisp">Loading...</span>
      </p>
    </div>

    <!-- Divider -->
    <hr style="border: 0; border-top: 1px solid rgba(255, 255, 255, 0.07); margin: 0 0 1.15rem 0;">

    <!-- Row 3: Schedule Dates Metadata -->
    <div class="d-flex justify-content-end align-items-center flex-wrap gap-3">
      <span style="color: var(--text-dim); font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.35rem;">
        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
        <span id="surveyDatesDisp">Loading...</span>
      </span>
    </div>

  </div>

  <!-- Navigation Tab Bar: [ Questions ] [ Responses ] [ Settings ] (Pill Track) -->
  <div class="questionnaire-tabnav-wrapper">
    <div class="questionnaire-tab-track">
      <button type="button" class="btn questionnaire-tab-btn active-tab" id="btnTabQuestions" onclick="switchQuestionnaireTab('questions')">
        <i class="fa-solid fa-list-check"></i> Questions (<span id="qCountTop">0</span>)
      </button>
      <button type="button" class="btn questionnaire-tab-btn" id="btnTabResponses" onclick="switchQuestionnaireTab('responses')">
        <i class="fa-solid fa-chart-column"></i> Responses (<span id="respCountTab">0</span>)
      </button>
      <button type="button" class="btn questionnaire-tab-btn" id="btnTabSettings" onclick="switchQuestionnaireTab('settings')">
        <i class="fa-solid fa-gear"></i> Settings
      </button>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="modal-error-box" style="margin-bottom: 1.5rem;">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <?= implode('<br>', array_map('e', $errors)) ?>
    </div>
  <?php endif; ?>

  <!-- =========================================================
       TAB 1: QUESTIONS CONTENT (DEFAULT ACTIVE)
       ========================================================= -->
  <div id="tabQuestionsContent">
    <div class="section-title" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
      <div id="addQuestionBtnContainer"></div>
      <div>
        <h3 style="font-size: 1.1rem; color: var(--text-main); font-weight: 700; margin-bottom: 0.15rem;">Question List (<span class="qCountTab">0</span>)</h3>
        <p class="section-subtitle" style="margin-bottom: 0;">Configured survey items and response options</p>
      </div>
    </div>
    <div id="questionsListContainer">
        <div class="table-card text-center py-5"><i class="fa-solid fa-spinner fa-spin"></i> Loading questions...</div>
    </div>
  </div>
  <div id="tabResponsesContent" style="display: none;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
      <div>
        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem; letter-spacing: -0.2px;">
          <span id="respCountHeader">0</span> Responses
        </h3>
        <p class="section-subtitle" style="margin-bottom: 0; font-size: 0.88rem;">Aggregated response summaries, question-level analysis, and individual answer sheets</p>
      </div>
      <div class="d-inline-flex align-items-center gap-2 flex-wrap">
        <a id="printReportLink" href="#" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration: none; padding: 6px 13px; font-size: 0.82rem;">
          <i class="fa-solid fa-print me-1"></i> Print Report
        </a>
      </div>
    </div>

    <!-- Secondary Responses Sub-Tabs -->
    <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
      <div style="display: inline-flex; gap: 0.4rem; background: rgba(255, 255, 255, 0.04); border: 1px solid var(--bg-card-border); padding: 4px; border-radius: 10px; flex-wrap: wrap; justify-content: center;">
        <button type="button" class="btn btn-sm responses-subtab-btn active-subtab" onclick="switchResponsesSubtab('summary')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem;">
          <i class="fa-solid fa-chart-pie me-1"></i> Summary
        </button>
        <button type="button" class="btn btn-sm responses-subtab-btn" onclick="switchResponsesSubtab('question')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem;">
          <i class="fa-solid fa-list-ol me-1"></i> Question
        </button>
        <button type="button" class="btn btn-sm responses-subtab-btn" onclick="switchResponsesSubtab('individual')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem;">
          <i class="fa-solid fa-user-check me-1"></i> Individual (<span id="individualCount">0</span>)
        </button>
      </div>
    </div>

    <div id="subtabSummaryContent">
        <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="metric-card hover-glow-card"><div class="metric-icon purple"><i class="fa-solid fa-users"></i></div><div class="metric-data"><span class="metric-label">Total Respondents</span><h3 class="metric-value" id="statTotalResp">0</h3><span class="metric-subtext"><i class="fa-solid fa-check"></i> Completed submissions</span></div></div>
            <div class="metric-card hover-glow-card"><div class="metric-icon cyan"><i class="fa-solid fa-chart-pie"></i></div><div class="metric-data"><span class="metric-label">Participation Rate</span><h3 class="metric-value" id="statPartRate">0%</h3><span class="metric-subtext positive"><i class="fa-solid fa-users-line"></i> Of <span id="statTotalMem">0</span> active members</span></div></div>
            <div class="metric-card hover-glow-card"><div class="metric-icon emerald"><i class="fa-solid fa-list-check"></i></div><div class="metric-data"><span class="metric-label">Total Questions</span><h3 class="metric-value qCountTab">0</h3><span class="metric-subtext"><i class="fa-solid fa-database"></i> Live calculated items</span></div></div>
        </div>
        <div id="summaryTableContainer"></div>
    </div>

    <div id="subtabQuestionContent" style="display: none;">
        <div id="questionBreakdownContainer"></div>
    </div>

    <div id="subtabIndividualContent" style="display: none;">
        <div id="individualContainer"></div>
    </div>
  </div>
  <div id="tabSettingsContent" style="display: none;">
    <div class="table-card" style="margin-top: 0; padding: 1.85rem 2rem; max-width: 800px; margin-bottom: 2rem;">
      <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-bottom: 1.25rem;"><i class="fa-solid fa-sliders text-cyan me-2"></i> Survey Settings</h3>
      <form id="editSettingsForm">
        <div class="form-grid">
          <div class="form-group full-width">
            <label>Survey Title <span style="color: #f87171;">*</span></label>
            <input type="text" name="title" id="setSurveyTitle" required>
          </div>
          <div class="form-group full-width">
            <label>Survey Status <span style="color: #f87171;">*</span></label>
            <select name="status" id="setSurveyStatus" required style="height: 44px; width: 100%; border-radius: 10px; padding: 0.6rem 0.95rem; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
              <option value="active">Active (Published)</option>
              <option value="inactive">Inactive / Closed</option>
              <option value="draft">Draft (Unpublished)</option>
            </select>
          </div>
          <div class="form-group full-width">
            <label>Description / Instructions</label>
            <textarea name="description" id="setSurveyDesc" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Opening Date <span style="color: #f87171;">*</span></label>
            <input type="date" name="start_date" id="setStartDate" required>
          </div>
          <div class="form-group">
            <label>Opening Time <span style="color: #f87171;">*</span></label>
            <input type="time" name="start_time" id="setStartTime" required>
          </div>
          <div class="form-group">
            <label>Closing Date <span style="color: #f87171;">*</span></label>
            <input type="date" name="end_date" id="setEndDate" required>
          </div>
          <div class="form-group">
            <label>Closing Time <span style="color: #f87171;">*</span></label>
            <input type="time" name="end_time" id="setEndTime" required>
          </div>
        </div>
        <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: flex-end;">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
  <div id="addQuestionModal" class="modal-overlay hidden add-question-modal">
  <div class="modal-card add-question-card">
    <div class="modal-header">
      <h3 style="color: var(--text-main);"><i class="fa-solid fa-plus" style="color: var(--primary-teal);"></i> Add Question to Survey</h3>
    </div>
    <form id="addQuestionForm">
      <input type="hidden" name="csrf_token" value="">
      <input type="hidden" name="action" value="add_question">
      <input type="hidden" name="survey_id" value="<?= $surveyId ?>">

      <!-- Two-Column Grid on Desktop: Prompt on Left, Type on Right -->
      <div class="two-col-grid">
        <div class="form-group" style="min-width: 0;">
          <label>Question Prompt <span class="req-star">*</span></label>
          <textarea name="question" rows="3" placeholder="e.g., How satisfied are you with cooperative dividend disbursements?" required style="height: 100%; min-height: 86px; resize: vertical; line-height: 1.45;"></textarea>
        </div>

        <div class="form-group" style="min-width: 0;">
          <label>Question Type <span class="req-star">*</span></label>
          <select name="question_type" id="addQuestionType" onchange="toggleChoiceInputs('add')" style="height: 44px; cursor: pointer; font-weight: 500;">
            <option value="multiple_choice">Multiple Choice (Select from list of choices)</option>
            <option value="yes_no">Yes/No Question</option>
            <option value="rating">Rating Scale (1 to 5 Stars)</option>
            <option value="short_answer">Description Type Answer</option>
          </select>
        </div>
      </div>

      <!-- Multiple Choice Options Container -->
      <div id="addChoicesContainer" class="form-group full-width" style="margin-top: 0.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
          <label style="margin-bottom: 0;">Selectable Options <span class="req-star">*</span></label>
          <button type="button" class="btn-pill-add-opt" onclick="addChoiceRow('add')">
            <i class="fa-solid fa-plus"></i> Add Option
          </button>
        </div>
        <div id="addChoiceRows" style="display: flex; flex-direction: column; gap: 0.65rem;">
          <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="choices[]" placeholder="Choice 1 (e.g., Strongly Agree)">
          </div>
          <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="choices[]" placeholder="Choice 2 (e.g., Agree)">
          </div>
          <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="choices[]" placeholder="Choice 3 (e.g., Disagree)">
          </div>
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="modal-footer-actions">
        <button type="button" class="btn-plain-cancel" onclick="closeModal('addQuestionModal')">Cancel</button>
        <button type="submit" class="btn-gradient-save">
          <i class="fa-solid fa-check"></i> Save Question
        </button>
      </div>
    </form>
  </div>
</div>
</div>

<?php if (!$isPublished): ?>
<!-- =========================================================
     MODAL 2: EDIT QUESTION MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="editQuestionModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 650px;">
    <div class="modal-header">
      <h3 style="color: var(--text-main);"><i class="fa-solid fa-pen-to-square" style="color: var(--primary-teal);"></i> Edit Question</h3>
    </div>
    <form id="editQuestionForm">
      <input type="hidden" name="csrf_token" value="">
      <input type="hidden" name="action" value="edit_question">
      <input type="hidden" name="survey_id" value="<?= $surveyId ?>">
      <input type="hidden" name="question_id" id="editQuestionId" value="">

      <div class="form-grid" style="gap: 1.15rem;">
        <div class="form-group full-width">
          <label style="margin-bottom: 0.4rem; font-size: 0.84rem; font-weight: 600;">Question Prompt <span style="color: #f87171;">*</span></label>
          <textarea name="question" id="editQuestionText" rows="2" required></textarea>
        </div>

        <div class="form-group full-width">
          <label style="margin-bottom: 0.4rem; font-size: 0.84rem; font-weight: 600;">Question Type <span style="color: #f87171;">*</span></label>
          <select name="question_type" id="editQuestionType" onchange="toggleChoiceInputs('edit')" style="height: 44px; width: 100%; border-radius: 10px; padding: 0.6rem 0.95rem; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            <option value="multiple_choice">Multiple Choice (Select from list of choices)</option>
            <option value="yes_no">Yes/No Question</option>
            <option value="rating">Rating Scale (1 to 5 Stars)</option>
            <option value="short_answer">Description Type Answer</option>
          </select>
        </div>

        <!-- Edit Choices Container -->
        <div id="editChoicesContainer" class="form-group full-width">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.55rem;">
            <label style="margin-bottom: 0; font-size: 0.84rem; font-weight: 600;">Selectable Options <span style="color: #f87171;">*</span></label>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addChoiceRow('edit')" style="padding: 4px 10px; font-size: 0.75rem;">
              <i class="fa-solid fa-plus"></i> Add Option
            </button>
          </div>
          <div id="editChoiceRows" style="display: flex; flex-direction: column; gap: 0.55rem;">
            <!-- Dynamic choice rows populated via JS -->
          </div>
        </div>
      </div>

      <div class="modal-actions" style="margin-top: 1.6rem; gap: 0.75rem;">
        <button type="button" class="btn btn-cancel" onclick="closeModal('editQuestionModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Update Question
        </button>
      </div>
    </form>
  </div>
</div>

<!-- =========================================================
     MODAL 3: DELETE QUESTION MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="deleteQuestionModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header">
      <h3 style="color: #ef4444;"><i class="fa-solid fa-trash-can" style="color: #ef4444;"></i> Delete Question</h3>
    </div>
    <form>
      <input type="hidden" name="csrf_token" value="">
      <input type="hidden" name="action" value="delete_question">
      <input type="hidden" name="survey_id" value="<?= $surveyId ?>">
      <input type="hidden" name="question_id" id="deleteQuestionId" value="">

      <div class="modal-body">
        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.75rem;">
          Are you sure you want to delete this question?
        </p>
        <div class="structured-box" style="margin-bottom: 0.75rem; color: var(--text-main); font-weight: 600;" id="deleteQuestionText"></div>
        <p style="color: #f87171; font-size: 0.85rem; margin: 0; background: rgba(239, 68, 68, 0.12); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          This will also remove all associated choices and submitted responses for this question.
        </p>
      </div>

      <div class="modal-actions" style="margin-top: 1.6rem; gap: 0.75rem;">
        <button type="button" class="btn btn-cancel" onclick="closeModal('deleteQuestionModal')">Cancel</button>
        <button type="submit" class="btn btn-danger">
          <i class="fa-solid fa-trash-can"></i> Delete
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- =========================================================
     MODAL 4: INDIVIDUAL MEMBER RESPONSE DRILL-DOWN MODAL
     ========================================================= -->
<div id="memberResponseModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 680px; max-height: 85vh; display: flex; flex-direction: column;">
    <div class="modal-header" style="flex-shrink: 0;">
      <div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.2rem; color: var(--text-main);"><i class="fa-solid fa-file-lines" style="color: var(--primary-teal); margin-right: 6px;"></i> Member Submission Sheet</h3>
        <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0;">
          Respondent: <strong id="modalRespondentName" style="color: var(--text-main);"></strong> (<span id="modalRespondentAccount" style="color: var(--primary-teal); font-family: monospace;"></span>)
        </p>
      </div>
    </div>

    <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 1rem 0; display: flex; flex-direction: column; gap: 0.85rem;" id="modalAnswersContainer">
      <!-- Injected via JavaScript -->
    </div>

    <div class="modal-actions" style="margin-top: 1.25rem; flex-shrink: 0;">
      <button type="button" class="btn btn-primary btn-sm" onclick="closeModal('memberResponseModal')">
        <i class="fa-solid fa-check me-1"></i> Close
      </button>
    </div>
  </div>
</div>

<!-- =========================================================
     MODAL 5: DELETE SURVEY CONFIRMATION MODAL
     ========================================================= -->
<div id="deleteSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header">
      <h3 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Delete Survey</h3>
    </div>
    <form>
      <input type="hidden" name="csrf_token" value="">
      <input type="hidden" name="action" value="delete_survey">

      <div class="modal-body">
        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.75rem;">
          Are you sure you want to permanently delete <strong><span id="surveyTitleDisp">Loading...</span></strong>?
        </p>
        <p style="color: #f87171; font-size: 0.85rem; margin: 0; background: rgba(239, 68, 68, 0.12); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          This action cannot be undone. All configured questions, choices, and submitted responses will be permanently removed.
        </p>
      </div>

      <div class="modal-actions" style="margin-top: 1.6rem; display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
        <button type="button" class="btn btn-secondary btn-cancel" onclick="closeModal('deleteSurveyModal')">Cancel</button>
        <button type="submit" class="btn btn-danger" style="margin-left: auto;">
          <i class="fa-solid fa-trash-can"></i> Permanently Delete
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const memberAnswersData = <?= json_encode($memberAnswersMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const surveyQuestionsData = <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const surveyChoicesData = <?= json_encode($choicesByQuestion, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function switchQuestionnaireTab(tab) {
  const tabQuestions = document.getElementById('tabQuestionsContent');
  const tabResponses = document.getElementById('tabResponsesContent');
  const tabSettings = document.getElementById('tabSettingsContent');
  const btnQuestions = document.getElementById('btnTabQuestions');
  const btnResponses = document.getElementById('btnTabResponses');
  const btnSettings = document.getElementById('btnTabSettings');
  const headerAddBtn = document.getElementById('headerAddQuestionBtn');

  // Hide all tabs
  if (tabQuestions) tabQuestions.style.display = 'none';
  if (tabResponses) tabResponses.style.display = 'none';
  if (tabSettings) tabSettings.style.display = 'none';

  // Remove active class
  if (btnQuestions) btnQuestions.classList.remove('active-tab');
  if (btnResponses) btnResponses.classList.remove('active-tab');
  if (btnSettings) btnSettings.classList.remove('active-tab');

  if (tab === 'responses') {
    if (tabResponses) tabResponses.style.display = 'block';
    if (btnResponses) btnResponses.classList.add('active-tab');
    if (headerAddBtn) headerAddBtn.style.display = 'none';
    history.replaceState(null, null, '#responses');
  } else if (tab === 'settings') {
    if (tabSettings) tabSettings.style.display = 'block';
    if (btnSettings) btnSettings.classList.add('active-tab');
    if (headerAddBtn) headerAddBtn.style.display = 'none';
    history.replaceState(null, null, '#settings');
  } else {
    if (tabQuestions) tabQuestions.style.display = 'block';
    if (btnQuestions) btnQuestions.classList.add('active-tab');
    if (headerAddBtn) headerAddBtn.style.display = 'inline-flex';
    history.replaceState(null, null, '#questions');
  }
}

function switchResponsesSubtab(subtab) {
  const subSummary = document.getElementById('subtabSummaryContent');
  const subQuestion = document.getElementById('subtabQuestionContent');
  const subIndividual = document.getElementById('subtabIndividualContent');
  const btnSummary = document.getElementById('btnSubtabSummary');
  const btnQuestion = document.getElementById('btnSubtabQuestion');
  const btnIndividual = document.getElementById('btnSubtabIndividual');

  if (subSummary) subSummary.style.display = 'none';
  if (subQuestion) subQuestion.style.display = 'none';
  if (subIndividual) subIndividual.style.display = 'none';

  if (btnSummary) btnSummary.classList.remove('active-subtab');
  if (btnQuestion) btnQuestion.classList.remove('active-subtab');
  if (btnIndividual) btnIndividual.classList.remove('active-subtab');

  if (subtab === 'question') {
    if (subQuestion) subQuestion.style.display = 'block';
    if (btnQuestion) btnQuestion.classList.add('active-subtab');
  } else if (subtab === 'individual') {
    if (subIndividual) subIndividual.style.display = 'block';
    if (btnIndividual) btnIndividual.classList.add('active-subtab');
  } else {
    if (subSummary) subSummary.style.display = 'block';
    if (btnSummary) btnSummary.classList.add('active-subtab');
  }
}

function viewMemberResponses(memberId, memberName, accountNumber) {
  document.getElementById('modalRespondentName').textContent = memberName;
  document.getElementById('modalRespondentAccount').textContent = accountNumber;

  const container = document.getElementById('modalAnswersContainer');
  container.innerHTML = '';

  const answers = memberAnswersData[memberId] || [];

  if (answers.length === 0) {
    container.innerHTML = '<p class="text-muted text-center py-4">No recorded responses found for this member.</p>';
  } else {
    answers.forEach((ans, idx) => {
      const card = document.createElement('div');
      card.className = 'structured-box';
      card.style.padding = '0.85rem 1rem';

      const answerText = ans.choice_text || ans.answer || 'No answer provided';

      card.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
          <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 2px 8px; font-size: 0.75rem;">
            Q${idx + 1}
          </span>
          <div style="color: var(--text-main); font-weight: 700; font-size: 0.88rem;">${escapeHtml(ans.question)}</div>
        </div>
        <div style="padding: 0.6rem 0.85rem; border-radius: 6px; background: rgba(0, 194, 168, 0.08); border-left: 3px solid var(--primary-teal); color: var(--text-main); font-weight: 500; font-size: 0.9rem;">
          <i class="fa-solid fa-arrow-right" style="color: var(--primary-teal); font-size: 0.75rem; margin-right: 6px;"></i> ${escapeHtml(answerText)}
        </div>
      `;
      container.appendChild(card);
    });
  }

  openModal('memberResponseModal');
}

// Check on page load for hash or param
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const hash = window.location.hash;
  if (urlParams.get('tab') === 'responses' || hash === '#responses' || urlParams.get('tab') === 'analytics' || hash === '#analytics') {
    switchQuestionnaireTab('responses');
  } else if (urlParams.get('tab') === 'settings' || hash === '#settings') {
    switchQuestionnaireTab('settings');
  } else {
    switchQuestionnaireTab('questions');
  }
});

function openAddQuestionModal() {
  openModal('addQuestionModal');
  toggleChoiceInputs('add');
}

function openEditQuestionModal(question, choices) {
  document.getElementById('editQuestionId').value = question.question_id;
  document.getElementById('editQuestionText').value = question.question;
  document.getElementById('editQuestionType').value = question.question_type;

  const rows = document.getElementById('editChoiceRows');
  rows.innerHTML = '';

  if (question.question_type === 'multiple_choice' && choices && choices.length > 0) {
    choices.forEach((c, idx) => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.gap = '0.5rem';
      row.style.alignItems = 'center';
      row.innerHTML = `
        <input type="text" name="choices[]" value="${escapeHtml(c.choice_text)}" placeholder="Choice ${idx + 1}" required>
        <button type="button" class="icon-btn btn-delete-dynamic" onclick="this.parentElement.remove()" style="width: 34px; height: 34px; flex-shrink: 0;" title="Remove Option"><i class="fa-solid fa-xmark"></i></button>
      `;
      rows.appendChild(row);
    });
  } else {
    // Default 2 blank choice rows
    for (let i = 1; i <= 2; i++) {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.gap = '0.5rem';
      row.style.alignItems = 'center';
      row.innerHTML = `
        <input type="text" name="choices[]" placeholder="Choice ${i}">
        <button type="button" class="icon-btn btn-delete-dynamic" onclick="this.parentElement.remove()" style="width: 34px; height: 34px; flex-shrink: 0;" title="Remove Option"><i class="fa-solid fa-xmark"></i></button>
      `;
      rows.appendChild(row);
    }
  }

  toggleChoiceInputs('edit');
  openModal('editQuestionModal');
}

function openDeleteQuestionModal(id, text) {
  document.getElementById('deleteQuestionId').value = id;
  document.getElementById('deleteQuestionText').textContent = text;
  openModal('deleteQuestionModal');
}

// Delegated click listeners for Edit and Delete question buttons
document.addEventListener('click', function (e) {
  const editBtn = e.target.closest('.js-edit-question-btn');
  if (editBtn) {
    const qId = parseInt(editBtn.getAttribute('data-question-id'), 10);
    const q = Array.isArray(surveyQuestionsData) ? surveyQuestionsData.find(item => parseInt(item.question_id, 10) === qId) : null;
    const choices = (surveyChoicesData && surveyChoicesData[qId]) ? surveyChoicesData[qId] : [];
    if (q) {
      openEditQuestionModal(q, choices);
    }
    return;
  }

  const deleteBtn = e.target.closest('.js-delete-question-btn');
  if (deleteBtn) {
    const qId = parseInt(deleteBtn.getAttribute('data-question-id'), 10);
    const q = Array.isArray(surveyQuestionsData) ? surveyQuestionsData.find(item => parseInt(item.question_id, 10) === qId) : null;
    if (q) {
      openDeleteQuestionModal(qId, q.question);
    }
    return;
  }
});

function toggleChoiceInputs(mode) {
  const typeSelect = document.getElementById(mode === 'add' ? 'addQuestionType' : 'editQuestionType');
  const container = document.getElementById(mode === 'add' ? 'addChoicesContainer' : 'editChoicesContainer');
  const val = typeSelect.value;

  if (val === 'multiple_choice') {
    container.style.display = 'flex';
  } else {
    container.style.display = 'none';
  }
}

function addChoiceRow(mode) {
  const rows = document.getElementById(mode === 'add' ? 'addChoiceRows' : 'editChoiceRows');
  const idx = rows.children.length + 1;
  const row = document.createElement('div');
  row.style.display = 'flex';
  row.style.gap = '0.5rem';
  row.style.alignItems = 'center';
  row.innerHTML = `
    <input type="text" name="choices[]" placeholder="Choice ${idx}" required>
    <button type="button" class="icon-btn btn-delete-dynamic" onclick="this.parentElement.remove()" style="width: 34px; height: 34px; flex-shrink: 0;" title="Remove Option"><i class="fa-solid fa-xmark"></i></button>
  `;
  rows.appendChild(row);
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

function updateStatusDot(val) {
  const dot = document.getElementById('statusSelectDot');
  if (!dot) return;
  if (val === 'active') {
    dot.style.background = '#00c2a8';
    dot.style.boxShadow = '0 0 8px rgba(0, 194, 168, 0.65)';
  } else if (val === 'draft') {
    dot.style.background = '#94a3b8';
    dot.style.boxShadow = 'none';
  } else if (val === 'inactive') {
    dot.style.background = '#f59e0b';
    dot.style.boxShadow = 'none';
  } else if (val === 'closed') {
    dot.style.background = '#ef4444';
    dot.style.boxShadow = 'none';
  }
}

// Filter Popover Toggling
function toggleFilterMenu(popoverId, event) {
  if (event) event.stopPropagation();
  const target = document.getElementById(popoverId);
  const allPopovers = document.querySelectorAll('.unified-filter-card');
  allPopovers.forEach(p => {
    if (p.id !== popoverId) p.classList.add('hidden');
  });
  if (target) {
    target.classList.toggle('hidden');
  }
}

function applySummaryQuestionsFilter(event) {
  if (event) event.stopPropagation();
  const target = document.getElementById('summaryFilterPopover');
  if (target) target.classList.add('hidden');
  if (typeof filterSummaryQuestions === 'function') filterSummaryQuestions();
}

function applyBreakdownQuestionsFilter(event) {
  if (event) event.stopPropagation();
  const target = document.getElementById('breakdownFilterPopover');
  if (target) target.classList.add('hidden');
  if (typeof filterBreakdownQuestions === 'function') filterBreakdownQuestions();
}

function applyIndividualRespondentsFilter(event) {
  if (event) event.stopPropagation();
  const target = document.getElementById('individualFilterPopover');
  if (target) target.classList.add('hidden');
  if (typeof filterIndividualRespondents === 'function') filterIndividualRespondents();
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.unified-filter-wrapper')) {
    document.querySelectorAll('.unified-filter-card').forEach(p => p.classList.add('hidden'));
  }
});

// Live Filtering: Summary Sub-Tab Questions
function filterSummaryQuestions() {
  const searchInput = document.getElementById('summaryQuestionSearch');
  const typeFilter = document.getElementById('summaryTypeFilter');
  const statusFilter = document.getElementById('summaryStatusFilter');
  if (!searchInput || !typeFilter || !statusFilter) return;

  const query = searchInput.value.toLowerCase().trim();
  const selectedType = typeFilter.value;
  const selectedStatus = statusFilter.value;

  const rows = document.querySelectorAll('#summaryQuestionsTable tbody tr.summary-question-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const qText = row.getAttribute('data-qtext') || '';
    const qType = row.getAttribute('data-qtype') || '';
    const responses = parseInt(row.getAttribute('data-responses') || '0', 10);

    const matchesSearch = query === '' || qText.includes(query) || row.textContent.toLowerCase().includes(query);
    const matchesType = (selectedType === 'all' || qType === selectedType);
    let matchesStatus = true;
    if (selectedStatus === 'has_responses') {
      matchesStatus = (responses > 0);
    } else if (selectedStatus === 'zero_responses') {
      matchesStatus = (responses === 0);
    }

    if (matchesSearch && matchesType && matchesStatus) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noMatchesRow = document.getElementById('summaryNoMatchesRow');
  if (noMatchesRow) {
    noMatchesRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
  }
}

// Live Filtering: Question Breakdown Cards
function filterBreakdownQuestions() {
  const searchInput = document.getElementById('breakdownQuestionSearch');
  const typeFilter = document.getElementById('breakdownTypeFilter');
  if (!searchInput || !typeFilter) return;

  const query = searchInput.value.toLowerCase().trim();
  const selectedType = typeFilter.value;

  const cards = document.querySelectorAll('.breakdown-question-card');
  let visibleCount = 0;

  cards.forEach(card => {
    const qText = card.getAttribute('data-qtext') || '';
    const qType = card.getAttribute('data-qtype') || '';

    const matchesSearch = query === '' || qText.includes(query) || card.textContent.toLowerCase().includes(query);
    const matchesType = (selectedType === 'all' || qType === selectedType);

    if (matchesSearch && matchesType) {
      card.style.display = '';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });

  const noMatchesCard = document.getElementById('breakdownNoMatchesCard');
  if (noMatchesCard) {
    noMatchesCard.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
  }
}

// Live Filtering: Individual Respondents Table
function filterIndividualRespondents() {
  const searchInput = document.getElementById('individualSearchInput');
  const completionFilter = document.getElementById('individualCompletionFilter');
  if (!searchInput || !completionFilter) return;

  const query = searchInput.value.toLowerCase().trim();
  const selectedCompletion = completionFilter.value;

  const rows = document.querySelectorAll('#individualRespondentsTable tbody tr.individual-respondent-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const searchData = row.getAttribute('data-search') || '';
    const completion = row.getAttribute('data-completion') || '';

    const matchesSearch = query === '' || searchData.includes(query) || row.textContent.toLowerCase().includes(query);
    const matchesCompletion = (selectedCompletion === 'all' || completion === selectedCompletion);

    if (matchesSearch && matchesCompletion) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noMatchesRow = document.getElementById('individualNoMatchesRow');
  if (noMatchesRow) {
    noMatchesRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
  }
}

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

  const startDateTime = new Date(`${startDateVal}T${startTimeVal}`);
  const endDateTime = new Date(`${endDateVal}T${endTimeVal}`);

  if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
    showToast('Please enter valid dates and times.', 'warning', 'Invalid Date or Time');
    return false;
  }

  if (endDateTime <= startDateTime) {
    showToast('The closing date and time must be logically after the starting date and time.', 'error', 'Invalid Schedule');
    endDateInput.focus();
    return false;
  }

  return true;
}

function promptConfirmEditSurveySettings() {
  const form = document.getElementById('editSurveySettingsForm');
  if (!form) return;

  const titleInput = form.querySelector('[name="title"]');
  if (titleInput && !titleInput.value.trim()) {
    showToast('Survey title is required.', 'warning', 'Validation Warning');
    titleInput.focus();
    return;
  }

  if (typeof validateSurveySchedule === 'function') {
    if (!validateSurveySchedule(form)) return;
  }

  const title = titleInput ? titleInput.value.trim() : 'this survey';
  const statusSelect = form.querySelector('[name="status"]');
  const statusText = statusSelect ? statusSelect.options[statusSelect.selectedIndex].text : 'Active';

  showSystemConfirm({
    title: 'Confirm Save Changes',
    message: `Are you sure you want to save changes to <strong>"${title}"</strong>?<br><span style="color: var(--text-dim); font-size: 0.86rem; display: block; margin-top: 0.45rem;">Status will be updated to <strong style="color: var(--primary-teal);">${statusText}</strong> and all modified details will take effect immediately.</span>`,
    confirmText: 'Yes, Save Changes',
    cancelText: 'Cancel',
    icon: 'fa-solid fa-floppy-disk',
    iconColor: 'var(--primary-teal)',
    isDanger: false,
    onConfirm: () => {
      form.submit();
    }
  });
}
</script>

<script src="survey_questions_app.js" type="module"></script>
<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

