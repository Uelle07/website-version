<?php
/**
 * staff/reports.php
 * -------------------------------------------------------
 * Staff Portal — Generate System Reports View (Firebase Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$selectedCategory = $_GET['category'] ?? 'all_combined';
$selectedScope    = $_GET['scope'] ?? 'all_records';

$pageTitle = 'Generate Reports';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<!-- ================= PAGE 5: GENERATE REPORTS VIEW ================= -->
<div id="reportsView" class="page-view active">
  <div class="section-header-flex view-header-wrapper">
    <div class="section-title">
      <h2>Generate System Reports</h2>
      <p class="section-subtitle">Compile and export cooperative member demographic statistics, system audit logs, and account credentials security</p>
    </div>
  </div>

  <div class="metrics-grid outstanding-trackers-grid" id="reportTrackerCards" style="display: none; margin-bottom: 1.5rem;">
    <!-- Flashcard 1 -->
    <div class="flashcard-wrapper">
      <div class="flashcard-inner">
        <div class="flashcard-front metric-card tracker-card cyan-tracker">
          <div class="metric-icon cyan"><i class="fa-solid fa-file-lines"></i></div>
          <div class="metric-data">
            <span class="metric-label tracker-label-space">Selected Report Scope</span>
          </div>
        </div>
        <div class="flashcard-back">
          <div class="flashcard-back-content">
            <h3 class="metric-value" id="reportScopeCountDynamic">Loading...</h3>
            <span class="metric-subtext positive"><i class="fa-solid fa-check"></i> Actively Compiled</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Flashcard 2 -->
    <div class="flashcard-wrapper">
      <div class="flashcard-inner">
        <div class="flashcard-front metric-card tracker-card purple-tracker">
          <div class="metric-icon purple"><i class="fa-solid fa-clock"></i></div>
          <div class="metric-data">
            <span class="metric-label tracker-label-space">Last Generated</span>
          </div>
        </div>
        <div class="flashcard-back">
          <div class="flashcard-back-content">
            <h3 class="metric-value" id="lastGeneratedTime"><?= date('m/d/Y, h:i A') ?></h3>
            <span class="metric-subtext">By Staff Administrator</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Flashcard 3 -->
    <div class="flashcard-wrapper">
      <div class="flashcard-inner">
        <div class="flashcard-front metric-card tracker-card emerald-tracker">
          <div class="metric-icon emerald"><i class="fa-solid fa-shield-halved"></i></div>
          <div class="metric-data">
            <span class="metric-label tracker-label-space">Data Integrity Status</span>
          </div>
        </div>
        <div class="flashcard-back">
          <div class="flashcard-back-content">
            <h3 class="metric-value" id="verifiedStatusText">Verified</h3>
            <span class="metric-subtext positive"><i class="fa-solid fa-lock"></i> System Audit Verified</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="report-options-card" style="margin-bottom: 1.5rem;">
    <div class="report-options-header">
      <div class="criteria-header-flex">
        <div>
          <h3 class="criteria-main-title"><i class="fa-solid fa-sliders"></i> Compiled Report Criteria</h3>
          <p class="criteria-main-subtitle">Configure parameters below to filter and generate a structured report export.</p>
        </div>
      </div>
    </div>

    <div class="reports-filter-grid">
      <div class="filter-group-stacked">
        <label class="filter-label"><i class="fa-solid fa-folder-tree"></i> Filter by Category:</label>
        <select id="reportCategorySelect" class="table-filter-select-lg" onchange="updateReportPreview()">
          <option value="all_combined" <?= $selectedCategory === 'all_combined' ? 'selected' : '' ?>>Full Consolidated Export</option>
          <option value="member_summary" <?= ($selectedCategory === 'member_summary' || $selectedCategory === 'General Member Information') ? 'selected' : '' ?>>General Member Information</option>
          <option value="survey_analytics" <?= ($selectedCategory === 'survey_analytics' || $selectedCategory === 'General Member Survey Feedback Only' || $selectedCategory === 'Cooperative Surveys & Member Feedback') ? 'selected' : '' ?>>General Member Survey Feedback Only</option>
          <option value="audit_activity" <?= ($selectedCategory === 'audit_activity' || $selectedCategory === 'System Audit & Staff Action Log') ? 'selected' : '' ?>>System Audit & Staff Action Log</option>
          <option value="credentials_security" <?= ($selectedCategory === 'credentials_security' || $selectedCategory === 'Password Security' || $selectedCategory === 'Password Security & Account Lockouts') ? 'selected' : '' ?>>Password Security</option>
        </select>
      </div>

      <div class="filter-group-stacked">
        <label class="filter-label"><i class="fa-solid fa-calendar-days"></i> Timeline Option:</label>
        <select id="reportTimeRange" class="table-filter-select-lg" onchange="handleTimelineChange()">
          <option value="most_recent">Most Recent (within 30-day span)</option>
          <option value="past_30_days">Past 30 Days Span</option>
          <option value="past_q1">Past 1st Quarter Span</option>
          <option value="past_q2">Past 2nd Quarter Span</option>
          <option value="past_q3">Past 3rd Quarter Span</option>
          <option value="past_q4">Past 4th Quarter Span</option>
          <option value="past_1_year">Past 1 Year Span</option>
          <option value="custom">Custom Date Range</option>
        </select>

        <div id="customDateRangeBox" class="custom-date-container hidden">
          <div class="custom-date-header">
            <i class="fa-solid fa-calendar-week"></i> <span>Custom Timeline Range</span>
          </div>
          <div class="custom-date-inputs">
            <div class="date-input-group">
              <label for="customStartDate">Start Date:</label>
              <input type="date" id="customStartDate" class="custom-date-input" onchange="validateCustomDates()">
            </div>
            <div class="date-input-group">
              <label for="customEndDate">End Date:</label>
              <input type="date" id="customEndDate" class="custom-date-input" onchange="validateCustomDates()">
            </div>
          </div>
          <div id="dateErrorMessage" class="date-error-msg hidden">
            <i class="fa-solid fa-circle-exclamation"></i> <span id="dateErrorText">Invalid date parameters provided.</span>
          </div>
        </div>
      </div>

      <div class="filter-group-stacked">
        <label class="filter-label"><i class="fa-solid fa-layer-group"></i> Data Scope / Filter:</label>
        <select id="reportDataScopeSelect" class="table-filter-select-lg" onchange="updateReportPreview()">
          <option value="all_records">All Registered Records</option>
          <option value="active_only">Active Accounts Only</option>
          <option value="modified_only">Modified Records Only</option>
          <option value="deleted_only">Deleted/Removed Accounts Only</option>
        </select>
      </div>

      <div class="filter-group-stacked">
        <label class="filter-label"><i class="fa-solid fa-file-code"></i> Format Option:</label>
        <select id="reportFormatSelect" class="table-filter-select-lg">
          <option value="PDF Document (.pdf)">PDF Document (.pdf)</option>
          <option value="Word Document (.docx)">Word Document (.docx / .doc)</option>
        </select>
      </div>
    </div>

    <!-- Bottom Right Action Controls in Criteria Card -->
    <div class="report-options-footer" style="display: flex; justify-content: flex-end; align-items: center; gap: 0.85rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--bg-card-border); flex-wrap: wrap;">
      <button type="button" class="btn btn-secondary action-btn-preview" onclick="previewCompiledReport()" aria-label="Preview Compiled Report">
        <i class="fa-solid fa-eye"></i> <span>Preview Report</span>
      </button>
      <button type="button" class="btn btn-purple-accent" onclick="printCompiledReport()" aria-label="Print Live Report Preview">
        <i class="fa-solid fa-print"></i> <span>Print Report</span>
      </button>
      <button type="button" class="btn btn-primary action-btn-export" onclick="openExportValidationModal()" aria-label="Compile and Download Report">
        <i class="fa-solid fa-download"></i> <span>Download Report</span>
      </button>
    </div>
  </div>

  <!-- Live Preview Header -->
  <div class="section-header-flex view-header-wrapper" style="margin-top: 2rem; margin-bottom: 1.25rem;">
    <div class="section-title">
      <h3><i class="fa-solid fa-table-list text-cyan me-2"></i> Report Live Preview</h3>
      <p class="section-subtitle" id="reportScopeCount">Matching records based on compiled criteria</p>
    </div>
  </div>

  <!-- Side-by-Side Search Bar & Filter Button matching Member Records -->
  <div class="side-by-side-controls" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="reportPreviewSearch" placeholder="Search by date, Category, Report ID, and Staff..." onkeyup="filterPreviewTable()" aria-label="Search Report Live Preview">
    </div>

    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="previewFilterBtn" onclick="toggleUnifiedFilterMenu(event)" aria-label="Toggle preview filters">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="unifiedFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Options</h4>
          <button type="button" class="close-popover-btn" onclick="toggleUnifiedFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body">
          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-clock-rotate-left"></i> Timespan Hierarchy:</label>
            <select id="previewTimespanSpanFilter" class="table-filter-select-lg">
              <option value="most_recent">Most Recent (within 30-day span)</option>
              <option value="past_30_days">Past 30 Days Span</option>
              <option value="past_q1">Past 1st Quarter Span</option>
              <option value="past_q2">Past 2nd Quarter Span</option>
              <option value="past_q3">Past 3rd Quarter Span</option>
              <option value="past_q4">Past 4th Quarter Span</option>
              <option value="past_1_year">Past 1 Year Span</option>
            </select>
          </div>

          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-layer-group"></i> Category Filter:</label>
            <select id="previewFilterCategory" class="table-filter-select-lg">
              <option value="all">Full Consolidated Export</option>
              <option value="General Member Information">General Member Information</option>
              <option value="General Member Survey Feedback Only">General Member Survey Feedback Only</option>
              <option value="System Audit & Staff Action Log">System Audit & Staff Action Log</option>
              <option value="Password Security">Password Security</option>
            </select>
          </div>
        </div>

        <div class="filter-popover-footer">
          <button type="button" class="btn btn-primary btn-sm" onclick="applyUnifiedFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Live Preview Table Container Card -->
  <div class="table-card report-preview-card" style="margin-top: 1rem;">
    <table class="dashboard-table centered-preview-table" id="reportPreviewTable">
      <thead>
        <tr>
          <th style="width: 14%;">Report ID</th>
          <th style="width: 22%;">Category</th>
          <th style="width: 28%;">Description</th>
          <th style="width: 18%;">Inclusion Date (MM/DD/YYYY)</th>
          <th style="width: 10%;">Compilation Date (MM/DD/YYYY)</th>
          <th style="width: 8%;">Exported By</th>
        </tr>
      </thead>
      <tbody id="reportPreviewTableBody">
        <tr data-cat="General Member Information" data-inclusion="08/01/2026 - 08/31/2026" data-compilation="2026-08-02" data-days-ago="0">
          <td><strong class="account-tag">RPT-2026-081</strong></td>
          <td class="preview-cat-cell">General Member Information</td>
          <td class="preview-desc-cell">Includes all personal and profile information provided by the Member and accessible by Staff within the portal.</td>
          <td>
            <span class="desc-date-badge interactive-date-badge" id="previewInclusionBadge" onclick="showInclusionDateDetails(this)" title="Click to view Timeline Criteria details">
              <i class="fa-regular fa-calendar"></i> 08/01/2026 - 08/31/2026
              <span class="timeline-label-tag">(Current Month - August 2026)</span>
            </span>
          </td>
          <td>08/02/2026</td>
          <td>Admin Staff 1</td>
        </tr>
        <tr data-cat="General Member Survey Feedback Only" data-inclusion="08/01/2026 - 08/31/2026" data-compilation="2026-08-02" data-days-ago="0">
          <td><strong class="account-tag">RPT-2026-082</strong></td>
          <td class="preview-cat-cell">General Member Survey Feedback Only</td>
          <td class="preview-desc-cell">Survey feedback responses, ratings, and satisfaction metrics submitted by cooperative members.</td>
          <td>
            <span class="desc-date-badge interactive-date-badge" onclick="showInclusionDateDetails(this)" title="Click to view Timeline Criteria details">
              <i class="fa-regular fa-calendar"></i> 08/01/2026 - 08/31/2026
              <span class="timeline-label-tag">(Current Month - August 2026)</span>
            </span>
          </td>
          <td>08/02/2026</td>
          <td>Admin Staff 1</td>
        </tr>
        <tr data-cat="System Audit & Staff Action Log" data-inclusion="07/01/2026 - 09/30/2026" data-compilation="2026-08-01" data-days-ago="1">
          <td><strong class="account-tag">RPT-2026-080</strong></td>
          <td class="preview-cat-cell">System Audit & Staff Action Log</td>
          <td class="preview-desc-cell">Audit history tracing staff profile edits and record modifications within the system.</td>
          <td>
            <span class="desc-date-badge interactive-date-badge" onclick="showInclusionDateDetails(this)" title="Click to view Timeline Criteria details">
              <i class="fa-regular fa-calendar"></i> 07/01/2026 - 09/30/2026
              <span class="timeline-label-tag">(3rd Quarter of the Year)</span>
            </span>
          </td>
          <td>08/01/2026</td>
          <td>Staff Member 2</td>
        </tr>
        <tr data-cat="Password Security" data-inclusion="01/01/2026 - 12/31/2026" data-compilation="2026-07-30" data-days-ago="3">
          <td><strong class="account-tag">RPT-2026-079</strong></td>
          <td class="preview-cat-cell">Password Security</td>
          <td class="preview-desc-cell">Logs of password reset actions, credential compliance, and security lockouts.</td>
          <td>
            <span class="desc-date-badge interactive-date-badge" onclick="showInclusionDateDetails(this)" title="Click to view Timeline Criteria details">
              <i class="fa-regular fa-calendar"></i> 01/01/2026 - 12/31/2026
              <span class="timeline-label-tag">(Full Year 2026)</span>
            </span>
          </td>
          <td>07/30/2026</td>
          <td>Admin Staff 1</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
  window.CURRENT_STAFF_NAME = <?= json_encode($_SESSION['username'] ?? 'Staff Administrator') ?>;
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

<script type="module">
import { app, db, auth } from '../assets/js/firebase-init.js';
import { collection, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = '../login.php';
        return;
    }
    
    // Fetch count for the reports
    try {
        const snapshot = await getDocs(collection(db, 'member'));
        const count = snapshot.size;
        const el = document.getElementById('reportScopeCountDynamic');
        if (el) {
            el.innerText = count + ' Member Records';
        }
    } catch (error) {
        console.error("Error fetching report counts: ", error);
        const el = document.getElementById('reportScopeCountDynamic');
        if (el) {
            el.innerText = 'Error loading records';
        }
    }
});
</script>


