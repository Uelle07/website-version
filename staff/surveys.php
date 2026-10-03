<?php
/**
 * staff/surveys.php
 * -------------------------------------------------------
 * Staff Portal — Survey Management Overview (Firebase)
 * Create, edit, toggle status, and delete cooperative surveys.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$pageTitle = 'Survey Management';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div id="surveyManagementView" class="page-view active surveys-page">
  <style>
  /* Same styles as original */
  .surveys-page { width: 100%; }
  .surveys-page .view-header-wrapper { display: flex; justify-content: space-between; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
  @media (max-width: 640px) {
    .surveys-page .view-header-wrapper { flex-direction: column; align-items: stretch; gap: 1rem; }
    .surveys-page .view-header-wrapper .btn-primary { width: 100%; justify-content: center; }
  }
  .surveys-page .surveys-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.5rem; width: 100%; }
  @media (max-width: 991px) { .surveys-page .surveys-metrics-grid { grid-template-columns: repeat(2, 1fr); gap: 1.25rem; } }
  @media (max-width: 640px) { .surveys-page .surveys-metrics-grid { grid-template-columns: 1fr; gap: 1rem; } }
  .surveys-page .surveys-controls-row { display: flex; justify-content: space-between; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; flex-wrap: wrap; width: 100%; }
  .surveys-page .surveys-search-box { flex: 1; min-width: 260px; max-width: 560px; }
  .surveys-page .surveys-filter-group { display: flex; align-items: center; gap: 0.65rem; }
  @media (max-width: 640px) {
    .surveys-page .surveys-controls-row { flex-direction: column; align-items: stretch; gap: 0.85rem; }
    .surveys-page .surveys-search-box { max-width: 100%; width: 100%; }
    .surveys-page .surveys-filter-group { width: 100%; justify-content: space-between; }
    .surveys-page .surveys-filter-group select { flex: 1; width: 100%; }
  }
  .surveys-page .surveys-table-container { margin-top: 0; margin-bottom: 1.5rem; width: 100%; }
  .surveys-page .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 16px; width: 100%; }
  .surveys-page .dashboard-table { width: 100%; border-collapse: separate; border-spacing: 0; }
  .surveys-page .dashboard-table th, .surveys-page .dashboard-table td { padding: 1rem 1.15rem; vertical-align: middle; }
  .surveys-page .dashboard-table th { font-size: 0.78rem; font-weight: 800; text-transform: uppercase; color: var(--text-dim); letter-spacing: 0.5px; border-bottom: 1px solid var(--bg-card-border); }
  .surveys-page .dashboard-table td { border-bottom: 1px solid var(--bg-card-border); transition: background 0.2s ease; }
  .surveys-page .dashboard-table tbody tr:hover td { background: var(--badge-bg); }
  .surveys-page .survey-title-col { width: 27%; min-width: 200px; text-align: left; }
  .surveys-page .survey-status-col { width: 12%; min-width: 110px; text-align: center; }
  .surveys-page .survey-schedule-col { width: 24%; min-width: 215px; text-align: center; }
  .surveys-page .survey-questions-col { width: 10%; min-width: 90px; text-align: center; }
  .surveys-page .survey-respondents-col { width: 11%; min-width: 100px; text-align: center; }
  .surveys-page .survey-actions-col { width: 16%; min-width: 180px; text-align: center; }
  .surveys-page .table-actions-grid { display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem; margin: 0 auto; white-space: nowrap; }
  .surveys-page .table-actions-grid .icon-btn { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.88rem; border: 1px solid transparent; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; flex-shrink: 0; }
  .surveys-page .table-actions-grid .icon-btn:hover { transform: translateY(-2px); }
  .surveys-page .survey-schedule-display { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; font-size: 0.83rem; line-height: 1.35; }
  </style>

  <div class="section-header-flex view-header-wrapper">
    <div class="section-title">
      <h2>Cooperative Survey Management</h2>
      <p class="section-subtitle">Create, monitor, and analyze member feedback surveys</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openCreateSurveyModal()" title="Create Survey">
      <i class="fa-solid fa-plus"></i> Create New Survey
    </button>
  </div>

  <div class="metrics-grid surveys-metrics-grid">
    <div class="metric-card hover-glow-card">
      <div class="metric-icon cyan"><i class="fa-solid fa-square-poll-vertical"></i></div>
      <div class="metric-data">
        <span class="metric-label">Total Surveys</span>
        <h3 class="metric-value" id="statTotalSurveys">0</h3>
        <span class="metric-subtext cyan"><i class="fa-solid fa-database"></i> All registered surveys</span>
      </div>
    </div>
    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald"><i class="fa-solid fa-tower-broadcast"></i></div>
      <div class="metric-data">
        <span class="metric-label">Active Surveys</span>
        <h3 class="metric-value" id="statActiveSurveys">0</h3>
        <span class="metric-subtext emerald positive"><i class="fa-solid fa-circle-check"></i> Open to members</span>
      </div>
    </div>
    <div class="metric-card hover-glow-card">
      <div class="metric-icon purple"><i class="fa-solid fa-users"></i></div>
      <div class="metric-data">
        <span class="metric-label">Total Respondents</span>
        <h3 class="metric-value" id="statTotalResponses">0</h3>
        <span class="metric-subtext purple"><i class="fa-solid fa-user-check"></i> Unique submissions</span>
      </div>
    </div>
    <div class="metric-card hover-glow-card">
      <div class="metric-icon amber" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;"><i class="fa-solid fa-file-pen"></i></div>
      <div class="metric-data">
        <span class="metric-label">Draft / Inactive</span>
        <h3 class="metric-value" id="statDraftSurveys">0</h3>
        <span class="metric-subtext amber"><i class="fa-solid fa-clock"></i> Unpublished / Closed</span>
      </div>
    </div>
  </div>

  <div class="side-by-side-controls surveys-controls-row" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search surveys-search-box" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="surveySearchInput" placeholder="Search surveys by title..." onkeyup="filterSurveysTable()" aria-label="Search surveys">
    </div>

    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="surveysFilterBtn" onclick="toggleSurveysFilterMenu(event)" aria-label="Toggle filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="surveysFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders"></i> Filter Options</h4>
          <button type="button" class="close-popover-btn" onclick="toggleSurveysFilterMenu(event)">&times;</button>
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
        <tbody id="surveysTableBody">
            <tr><td colspan="6" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="section-header-flex view-header-wrapper" style="margin-top: 2.25rem; margin-bottom: 1.25rem;">
    <div class="section-title">
      <h3><i class="fa-solid fa-eye text-cyan me-2"></i> Survey Live Preview</h3>
      <p class="section-subtitle">Real-time overview of active and draft surveys with live schedule status, respondent counts, and questionnaire details</p>
    </div>
  </div>

  <div class="side-by-side-controls surveys-controls-row" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search surveys-search-box" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="surveyLivePreviewSearch" placeholder="Search survey preview by title, schedule..." onkeyup="filterSurveyLivePreviewTable()" aria-label="Search Survey Live Preview">
    </div>

    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="surveyLivePreviewFilterBtn" onclick="toggleSurveyLivePreviewFilterMenu(event)" aria-label="Toggle live preview filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="surveyLivePreviewFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Live Preview</h4>
          <button type="button" class="close-popover-btn" onclick="toggleSurveyLivePreviewFilterMenu(event)">&times;</button>
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
            <tr><td colspan="6" class="text-center py-4"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modals -->
<div id="createSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 650px;">
    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="margin: 0;"><i class="fa-solid fa-plus" style="color: var(--primary-teal);"></i> Create New Survey</h3>
      <button type="button" class="close-popover-btn" onclick="closeModal('createSurveyModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form id="createSurveyForm">
      <div class="form-grid">
        <div class="form-group full-width">
          <label>Survey Title <span style="color: #f87171;">*</span></label>
          <input type="text" name="title" id="createTitle" placeholder="e.g., 2026 Member Satisfaction Survey" required>
        </div>
        <div class="form-group full-width">
          <label>Description / Instructions</label>
          <textarea name="description" id="createDescription" rows="3" placeholder="Explain the purpose of this survey to cooperative members..."></textarea>
        </div>
        <div class="form-group">
          <label>Opening Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="start_date" id="createStartDate" required>
        </div>
        <div class="form-group">
          <label>Opening Time <span style="color: #f87171;">*</span></label>
          <input type="time" name="start_time" id="createStartTime" required>
        </div>
        <div class="form-group">
          <label>Closing Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="end_date" id="createEndDate" required>
        </div>
        <div class="form-group">
          <label>Closing Time <span style="color: #f87171;">*</span></label>
          <input type="time" name="end_time" id="createEndTime" required>
        </div>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-primary" style="padding: 9px 26px; font-weight: 700; border-radius: 8px;">Proceed</button>
      </div>
    </form>
  </div>
</div>

<div id="editSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 650px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen-to-square" style="color: var(--primary-teal);"></i> Edit Survey Details</h3>
    </div>
    <form id="editSurveyForm">
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
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div id="deleteSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header">
      <h3><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Delete Survey</h3>
    </div>
    <form id="deleteSurveyForm">
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
  function openModal(id) { const el = document.getElementById(id); if (el) { el.classList.remove('hidden'); el.style.display = 'flex'; } }
  function closeModal(id) { const el = document.getElementById(id); if (el) { el.classList.add('hidden'); el.style.display = 'none'; } }
  function openCreateSurveyModal() { 
      const now = new Date();
      document.getElementById('createStartDate').value = now.toISOString().split('T')[0];
      document.getElementById('createStartTime').value = '00:00';
      const end = new Date(now.getTime() + 30*24*60*60*1000);
      document.getElementById('createEndDate').value = end.toISOString().split('T')[0];
      document.getElementById('createEndTime').value = '23:59';
      openModal('createSurveyModal'); 
  }
  function openEditSurveyModal(id) { window.dispatchEditSurvey(id); }
  function openDeleteSurveyModal(id, title) { 
      document.getElementById('deleteSurveyId').value = id; 
      document.getElementById('deleteSurveyTitle').textContent = `"${title}"`; 
      openModal('deleteSurveyModal'); 
  }
  function toggleSurveysFilterMenu(event) { if (event) event.stopPropagation(); const popover = document.getElementById('surveysFilterPopover'); if (popover) popover.classList.toggle('hidden'); }
  function applySurveysFilters(event) { if (event) event.stopPropagation(); window.filterSurveysTable(); const popover = document.getElementById('surveysFilterPopover'); if (popover) popover.classList.add('hidden'); }
  function toggleSurveyLivePreviewFilterMenu(event) { if (event) event.stopPropagation(); const popover = document.getElementById('surveyLivePreviewFilterPopover'); if (popover) popover.classList.toggle('hidden'); }
  function applySurveyLivePreviewFilters(event) { if (event) event.stopPropagation(); window.filterSurveyLivePreviewTable(); const popover = document.getElementById('surveyLivePreviewFilterPopover'); if (popover) popover.classList.add('hidden'); }
  document.addEventListener('click', function(e) { if (!e.target.closest('.unified-filter-wrapper')) { document.querySelectorAll('.unified-filter-card').forEach(p => p.classList.add('hidden')); } });
</script>

<script type="module">
  import { db, auth } from '../assets/js/firebase-init.js';
  import { collection, getDocs, doc, setDoc, deleteDoc, updateDoc, query, orderBy, onSnapshot, serverTimestamp, getDoc, getCountFromServer, where } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
  import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

  let currentUser = null;
  onAuthStateChanged(auth, (user) => {
    if (!user) {
      window.location.href = '../login.php';
    } else {
      currentUser = user;
    }
  });

  const surveysRef = collection(db, 'surveys');
  let surveysData = [];

  function renderTable() {
    const tableBody = document.getElementById('surveysTableBody');
    const liveBody = document.getElementById('surveyLivePreviewTableBody');
    let html = '';
    let liveHtml = '';
    let totalSurveys = 0, activeSurveys = 0, totalResponses = 0, draftSurveys = 0;
    const nowTs = new Date().getTime();

    surveysData.forEach((s) => {
      totalSurveys++;
      if (s.status === 'active') activeSurveys++;
      if (s.status === 'draft' || s.status === 'inactive' || s.status === 'closed') draftSurveys++;
      const respondentsCount = s.respondentsCount || 0;
      totalResponses += respondentsCount;
      const questionsCount = s.questionsCount || 0;

      const startDateStr = s.start_date || '';
      const endDateStr = s.end_date || '';
      const startD = startDateStr ? new Date(startDateStr) : new Date();
      const endD = endDateStr ? new Date(endDateStr) : new Date();

      const formatD = (d) => {
          return d.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
      };

      const startTs = startD.getTime();
      const endTs = endD.getTime();
      
      let timelineState = 'currently_open';
      if (startTs > nowTs) timelineState = 'upcoming';
      else if (endTs < nowTs) timelineState = 'ended';

      let statusPill = '';
      if (s.status === 'active') statusPill = '<span class="status-pill status-active"><i class="fa-solid fa-circle-check"></i> Active</span>';
      else if (s.status === 'draft') statusPill = '<span class="status-pill status-default"><i class="fa-solid fa-file-pen"></i> Draft</span>';
      else if (s.status === 'inactive') statusPill = '<span class="status-pill" style="background: rgba(148, 163, 184, 0.12); border: 1px solid rgba(148, 163, 184, 0.25); color: #cbd5e1;"><i class="fa-solid fa-pause"></i> Inactive</span>';
      else if (s.status === 'closed') statusPill = '<span class="status-pill status-locked"><span class="locked-title">Closed</span></span>';
      else statusPill = `<span class="status-pill">${s.status}</span>`;

      const escapedTitle = s.title.replace(/"/g, '&quot;').replace(/'/g, "\\'");
      const idCode = s.id; // Just use document ID since survey_id auto increment is no longer there.
      const displayCode = s.id.substring(0, 8);
      const searchData = `${displayCode} ${s.title} ${s.status}`.toLowerCase();

      html += `
        <tr data-status="${s.status}">
          <td class="survey-title-col" style="vertical-align: middle;">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-main); line-height: 1.35;">
              <a href="survey_questions.php?id=${s.id}" style="color: var(--text-main); text-decoration: none;">${s.title}</a>
            </div>
          </td>
          <td class="survey-status-col" style="text-align: center; vertical-align: middle;">${statusPill}</td>
          <td class="survey-schedule-col" style="text-align: center; vertical-align: middle;">
            <div class="survey-schedule-display">
              <span style="font-size: 0.83rem; font-weight: 600; color: var(--text-main); white-space: nowrap;">
                <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal); margin-right: 5px; font-size: 0.78rem;"></i> ${formatD(startD)}
              </span>
              <span style="font-size: 0.76rem; color: var(--text-dim); margin-top: 2px; white-space: nowrap;">to ${formatD(endD)}</span>
            </div>
          </td>
          <td class="survey-questions-col" style="text-align: center; vertical-align: middle;">
            <span class="status-pill" style="background: rgba(0, 194, 168, 0.1); border: 1px solid rgba(0, 194, 168, 0.25); color: var(--primary-teal); font-weight: 700;">
              <i class="fa-solid fa-list-check"></i> ${questionsCount}
            </span>
          </td>
          <td class="survey-respondents-col" style="text-align: center; vertical-align: middle;">
            <span class="status-pill" style="background: rgba(139, 92, 246, 0.12); border: 1px solid rgba(139, 92, 246, 0.25); color: #c084fc; font-weight: 700;">
              <i class="fa-solid fa-users"></i> ${respondentsCount}
            </span>
          </td>
          <td class="survey-actions-col" style="text-align: center; vertical-align: middle;">
            <div class="table-actions-grid">
              <a href="survey_questions.php?id=${s.id}" class="icon-btn btn-view-dynamic" title="Questions"><i class="fa-solid fa-list-check"></i></a>
              <a href="survey_responses.php?id=${s.id}" class="icon-btn btn-print-dynamic" title="Responses"><i class="fa-solid fa-square-poll-vertical"></i></a>
              <a href="javascript:void(0)" onclick="openEditSurveyModal('${s.id}')" class="icon-btn btn-edit-dynamic" title="Edit"><i class="fa-solid fa-pen"></i></a>
              <button type="button" class="icon-btn btn-delete-dynamic" title="Delete" onclick="openDeleteSurveyModal('${s.id}', '${escapedTitle}')"><i class="fa-solid fa-trash"></i></button>
            </div>
          </td>
        </tr>
      `;

      liveHtml += `
        <tr class="live-preview-row" data-status="${s.status}" data-timeline="${timelineState}" data-search="${searchData}">
          <td style="text-align: center; vertical-align: middle;"><strong class="account-tag">${displayCode}</strong></td>
          <td style="vertical-align: middle;"><a href="survey_questions.php?id=${s.id}" style="color: var(--text-main); font-weight: 700; text-decoration: none;">${s.title}</a></td>
          <td style="text-align: center; vertical-align: middle;">${statusPill}</td>
          <td style="text-align: center; vertical-align: middle;">
            <div class="survey-schedule-display">
              <span style="font-size: 0.81rem; font-weight: 600; color: var(--text-main);"><i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i> ${formatD(startD)}</span>
              <span style="color: var(--text-dim); font-size: 0.76rem;">to ${formatD(endD)}</span>
            </div>
          </td>
          <td style="text-align: center; vertical-align: middle;"><span class="status-pill" style="color: var(--primary-teal); background: rgba(0, 194, 168, 0.1); font-weight: 700;">${questionsCount}</span></td>
          <td style="text-align: center; vertical-align: middle;"><span class="status-pill" style="color: #c084fc; background: rgba(139, 92, 246, 0.12); font-weight: 700;">${respondentsCount}</span></td>
        </tr>
      `;
    });

    if (surveysData.length === 0) {
      html = '<tr><td colspan="6" class="text-center text-muted py-5">No cooperative surveys found.</td></tr>';
      liveHtml = '<tr><td colspan="6" class="text-center text-muted py-4">No survey records available for live preview.</td></tr>';
    } else {
      html += '<tr id="surveysNoMatchesRow" style="display: none;"><td colspan="6" class="text-center py-4 text-muted">No records match criteria.</td></tr>';
      liveHtml += '<tr id="livePreviewNoMatchesRow" style="display: none;"><td colspan="6" class="text-center py-4 text-muted">No records match criteria.</td></tr>';
    }

    tableBody.innerHTML = html;
    liveBody.innerHTML = liveHtml;
    
    document.getElementById('statTotalSurveys').textContent = totalSurveys;
    document.getElementById('statActiveSurveys').textContent = activeSurveys;
    document.getElementById('statTotalResponses').textContent = totalResponses;
    document.getElementById('statDraftSurveys').textContent = draftSurveys;

    window.filterSurveysTable();
    window.filterSurveyLivePreviewTable();
  }

  onSnapshot(query(surveysRef, orderBy("created_at", "desc")), async (snapshot) => {
    const arr = [];
    for (const docSnap of snapshot.docs) {
      const data = docSnap.data();
      const sId = docSnap.id;
      
      try {
          const qSnap = await getCountFromServer(query(collection(db, 'survey_questions'), where('survey_id', '==', sId)));
          const qCount = qSnap.data().count;
          
          if (data.questionsCount !== qCount) {
              data.questionsCount = qCount;
              updateDoc(docSnap.ref, { questionsCount: qCount }).catch(console.error);
          }
      } catch (err) {
          console.error("Count sync failed:", err);
      }
      
      arr.push({ id: sId, ...data });
    }
    surveysData = arr;
    renderTable();
  });

  document.getElementById('createSurveyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const title = document.getElementById('createTitle').value.trim();
    const description = document.getElementById('createDescription').value.trim();
    const sDate = document.getElementById('createStartDate').value;
    const sTime = document.getElementById('createStartTime').value;
    const eDate = document.getElementById('createEndDate').value;
    const eTime = document.getElementById('createEndTime').value;

    const startDateStr = `${sDate}T${sTime}:00`;
    const endDateStr = `${eDate}T${eTime}:00`;

    try {
      const newSurveyRef = doc(surveysRef);
      await setDoc(newSurveyRef, {
        title: title,
        description: description,
        start_date: startDateStr,
        end_date: endDateStr,
        status: 'draft',
        created_by_staff_id: currentUser ? currentUser.uid : '',
        created_at: serverTimestamp(),
        questionsCount: 0,
        respondentsCount: 0
      });
      closeModal('createSurveyModal');
      window.location.href = `survey_questions.php?id=${newSurveyRef.id}`;
    } catch (err) {
      console.error(err);
      alert('Error creating survey: ' + err.message);
    }
  });

  window.dispatchEditSurvey = (id) => {
    const survey = surveysData.find(s => s.id === id);
    if (!survey) return;
    document.getElementById('editSurveyId').value = survey.id;
    document.getElementById('editSurveyTitle').value = survey.title;
    document.getElementById('editSurveyDescription').value = survey.description || '';
    document.getElementById('editSurveyStatus').value = survey.status;
    
    if (survey.start_date) {
        const d = new Date(survey.start_date);
        document.getElementById('editSurveyStartDate').value = d.toISOString().split('T')[0];
        document.getElementById('editSurveyStartTime').value = d.toTimeString().slice(0,5);
    }
    if (survey.end_date) {
        const d = new Date(survey.end_date);
        document.getElementById('editSurveyEndDate').value = d.toISOString().split('T')[0];
        document.getElementById('editSurveyEndTime').value = d.toTimeString().slice(0,5);
    }
    openModal('editSurveyModal');
  };

  document.getElementById('editSurveyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('editSurveyId').value;
    const title = document.getElementById('editSurveyTitle').value.trim();
    const description = document.getElementById('editSurveyDescription').value.trim();
    const status = document.getElementById('editSurveyStatus').value;
    const sDate = document.getElementById('editSurveyStartDate').value;
    const sTime = document.getElementById('editSurveyStartTime').value;
    const eDate = document.getElementById('editSurveyEndDate').value;
    const eTime = document.getElementById('editSurveyEndTime').value;

    const startDateStr = `${sDate}T${sTime}:00`;
    const endDateStr = `${eDate}T${eTime}:00`;

    try {
      await updateDoc(doc(db, 'surveys', id), {
        title: title,
        description: description,
        status: status,
        start_date: startDateStr,
        end_date: endDateStr
      });
      closeModal('editSurveyModal');
    } catch(err) {
      console.error(err);
      alert('Error updating survey: ' + err.message);
    }
  });

  document.getElementById('deleteSurveyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('deleteSurveyId').value;
    try {
      await deleteDoc(doc(db, 'surveys', id));
      closeModal('deleteSurveyModal');
    } catch(err) {
      console.error(err);
      alert('Error deleting survey: ' + err.message);
    }
  });

  window.filterSurveysTable = () => {
    const search = (document.getElementById('surveySearchInput')?.value || '').toLowerCase().trim();
    const statusSel = document.getElementById('surveyStatusFilter');
    const status = statusSel ? statusSel.value.toLowerCase().trim() : 'all';
    
    let matchCount = 0;
    const rows = document.querySelectorAll('#surveysTableBody tr[data-status]');
    rows.forEach(row => {
      const rowStatus = row.getAttribute('data-status') || '';
      const text = row.textContent.toLowerCase();
      const matchesSearch = !search || text.includes(search);
      const isClosedOrInactive = (rowStatus === 'closed' || rowStatus === 'inactive');
      const matchesStatus = (status === 'all' || ((status === 'closed' || status === 'inactive') && isClosedOrInactive) || rowStatus === status);
      if (matchesSearch && matchesStatus) {
        row.style.display = '';
        matchCount++;
      } else {
        row.style.display = 'none';
      }
    });
    const nm = document.getElementById('surveysNoMatchesRow');
    if (nm) nm.style.display = (matchCount === 0 && rows.length > 0) ? '' : 'none';
  };

  window.filterSurveyLivePreviewTable = () => {
    const search = (document.getElementById('surveyLivePreviewSearch')?.value || '').toLowerCase().trim();
    const statusSel = document.getElementById('livePreviewStatusFilter');
    const status = statusSel ? statusSel.value.toLowerCase().trim() : 'all';
    const timeSel = document.getElementById('livePreviewTimelineFilter');
    const timeline = timeSel ? timeSel.value.toLowerCase().trim() : 'all';

    let matchCount = 0;
    const rows = document.querySelectorAll('#surveyLivePreviewTableBody tr.live-preview-row');
    rows.forEach(row => {
      const rowStatus = row.getAttribute('data-status') || '';
      const rowTimeline = row.getAttribute('data-timeline') || '';
      const rowSearch = row.getAttribute('data-search') || row.textContent.toLowerCase();

      const matchesSearch = !search || rowSearch.includes(search);
      const isClosedOrInactive = (rowStatus === 'closed' || rowStatus === 'inactive');
      const matchesStatus = (status === 'all' || ((status === 'closed' || status === 'inactive') && isClosedOrInactive) || rowStatus === status);
      const matchesTimeline = (timeline === 'all' || rowTimeline === timeline);

      if (matchesSearch && matchesStatus && matchesTimeline) {
        row.style.display = '';
        matchCount++;
      } else {
        row.style.display = 'none';
      }
    });
    const nm = document.getElementById('livePreviewNoMatchesRow');
    if (nm) nm.style.display = (matchCount === 0 && rows.length > 0) ? '' : 'none';
  };
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

