<?php
/**
 * staff/members.php
 * -------------------------------------------------------
 * Staff Portal — Member Records Management View (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

// Fetch member records with spouse name, children count, and lockout status from database
$stmt = $pdo->query(
    "SELECT m.*, s.spouse_name,
            (SELECT COUNT(*) FROM children c WHERE c.member_id = m.member_id) AS children_count,
            ma.failed_attempts, ma.locked_until,
            TIMESTAMPDIFF(SECOND, NOW(), ma.locked_until) AS lock_seconds_left
     FROM member m
     LEFT JOIN member_account ma ON ma.member_id = m.member_id
     LEFT JOIN spouse s ON s.member_id = m.member_id
     ORDER BY m.created_at DESC"
);
$members = $stmt->fetchAll();

$pageTitle = 'Member Records';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<!-- ================= PAGE 2: MEMBER RECORDS VIEW ================= -->
<div id="recordsView" class="page-view active">
  <div class="section-header-flex view-header-wrapper">
    <div class="section-title">
      <h2>Cooperative Member Records</h2>
      <p class="section-subtitle">Manage and Edit Full Member Information</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="openAddMemberModal()" title="Add Member">
      <i class="fa-solid fa-user-plus"></i> Add Member
    </button>
  </div>

  <div class="side-by-side-controls" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="memberRecordsSearch" placeholder="Search Cooperative Member Records" onkeyup="searchMemberRecords(this.value)" aria-label="Search Cooperative Member Records">
    </div>

    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="recordsFilterBtn" onclick="toggleRecordsFilterMenu(event)" aria-label="Toggle filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="recordsFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders"></i> Filter Options</h4>
          <button type="button" class="close-popover-btn" onclick="toggleRecordsFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body">
          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-layer-group"></i> Account Status / Scope:</label>
            <select id="recordsDataScopeFilter" class="table-filter-select-lg">
              <option value="all">All Registered Records</option>
              <option value="active">Active Accounts Only</option>
              <option value="inactive">Inactive Accounts Only</option>
              <option value="flagged">Flagged Accounts Only</option>
            </select>
          </div>

          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-calendar-days"></i> Timeline Added:</label>
            <select id="recordsTimelineFilter" class="table-filter-select-lg" onchange="handleMemberTimelineChange(this.value)">
              <option value="all">All Registered Timeline</option>
              <option value="most_recent">Most Recent (within 30-day span)</option>
              <option value="past_30_days">Past 30 Days Span</option>
              <option value="past_q1">Past 1st Quarter Span</option>
              <option value="past_q2">Past 2nd Quarter Span</option>
              <option value="past_q3">Past 3rd Quarter Span</option>
              <option value="past_q4">Past 4th Quarter Span</option>
              <option value="past_1_year">Past 1 Year Span</option>
            </select>
          </div>
        </div>

        <div class="filter-popover-footer">
          <button type="button" class="btn btn-primary btn-sm" onclick="applyRecordsFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="table-card" style="margin-top: 1rem;">
    <table class="dashboard-table member-records-table" id="memberRecordsTable">
      <thead>
        <tr>
          <th style="width: 70px; text-align: center;">Photo</th>
          <th>Account Number</th>
          <th>Full Name</th>
          <th style="text-align: center;">Status</th>
          <th style="text-align: center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($members)): ?>
          <?php foreach ($members as $m): ?>
            <?php
              $fullName  = trim($m['firstname'] . ' ' . $m['lastname']);
              $accNo     = $m['account_number'];
              $rawStatus = $m['account_status'] ?? 'Active';
              $isLockedOut = (!empty($m['locked_until']) && (int)($m['lock_seconds_left'] ?? 0) > 0);
              if ($isLockedOut || strtolower($rawStatus) === 'flagged') {
                  $status = 'Flagged';
              } elseif (strtolower($rawStatus) === 'inactive' || strtolower($rawStatus) === 'deleted') {
                  $status = 'Inactive';
              } else {
                  $status = 'Active';
              }
              $statusClass = strtolower($status) === 'active' ? 'status-active' : (strtolower($status) === 'inactive' ? 'status-inactive' : 'status-flagged');
              $createdDt = !empty($m['created_at']) ? strtotime($m['created_at']) : time();
              $daysDiff  = floor((time() - $createdDt) / (60 * 60 * 24));
              $hasPhoto  = !empty($m['passport_photo']) && !str_contains($m['passport_photo'], 'default-avatar');
            ?>
            <tr data-days="<?= (int)$daysDiff ?>" data-status="<?= e(strtolower($status)) ?>" data-created="<?= e(date('Y-m-d', $createdDt)) ?>">
              <td style="text-align: center; vertical-align: middle;">
                <div class="table-avatar avatar-clickable" onclick="openPhotoModal('<?= e($accNo) ?>')" title="Click to View Member Photo" role="button" tabindex="0" aria-label="View member photo" style="margin: 0 auto;">
                  <?php if ($hasPhoto): ?>
                    <img src="<?= e(photo_url($m['passport_photo'])) ?>" alt="Photo" class="table-avatar-thumb">
                  <?php else: ?>
                    <i class="fa-solid fa-image"></i>
                  <?php endif; ?>
                </div>
              </td>
              <td style="vertical-align: middle;"><strong class="account-tag"><i class="fa-solid fa-id-card me-1"></i><?= e($accNo) ?></strong></td>
              <td style="vertical-align: middle; font-weight: 600;"><?= e($fullName) ?></td>
              <td style="text-align: center; vertical-align: middle;"><span class="status-pill <?= $statusClass ?>"><?= e($status) ?></span></td>
              <td style="text-align: center; vertical-align: middle;">
                <div class="table-actions-grid" style="justify-content: center; margin: 0 auto;">
                  <button type="button" class="icon-btn btn-view-dynamic" title="View Profile" aria-label="View profile for <?= e($accNo) ?>" onclick="viewMember('<?= e($accNo) ?>')"><i class="fa-solid fa-eye"></i></button>
                  <button type="button" class="icon-btn btn-edit-dynamic" title="Edit Member" aria-label="Edit member information for <?= e($accNo) ?>" onclick="openEditMember('<?= e($accNo) ?>')"><i class="fa-solid fa-pen"></i></button>
                  <button type="button" class="icon-btn btn-print-dynamic" title="Print Member Record" aria-label="Print record for <?= e($accNo) ?>" onclick="printMemberRecord('<?= e($accNo) ?>')"><i class="fa-solid fa-print"></i></button>
                  <button type="button" class="icon-btn btn-download-dynamic" title="Download Record (PDF / Word)" aria-label="Download document for <?= e($accNo) ?>" onclick="openDownloadMemberModal('<?= e($accNo) ?>')"><i class="fa-solid fa-file-arrow-down"></i></button>
                  <?php if (strtolower($status) !== 'inactive'): ?>
                    <button type="button" class="icon-btn btn-delete-dynamic" title="Delete Member" aria-label="Delete member <?= e($accNo) ?>" onclick="openDeleteMember('<?= e($accNo) ?>')"><i class="fa-solid fa-trash"></i></button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">No member records found in database.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
