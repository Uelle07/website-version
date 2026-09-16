<?php
/**
 * staff/dashboard.php
 * -------------------------------------------------------
 * Staff Portal — Main Dashboard Overview View (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

// Fetch summary metrics from database
$totalMembers = (int) $pdo->query("SELECT COUNT(*) FROM member")->fetchColumn();
$recentEdits  = (int) $pdo->query("SELECT COUNT(DISTINCT member_id) FROM update_history WHERE update_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$newToday     = (int) $pdo->query("SELECT COUNT(*) FROM member WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Automatically sync and mark expired active surveys as closed
sync_expired_surveys($pdo);

// Fetch survey summary metrics
$surveysStmt = $pdo->query(
    "SELECT s.*, 
            (SELECT COUNT(*) FROM survey_questions sq WHERE sq.survey_id = s.survey_id) AS questions_count,
            (SELECT COUNT(DISTINCT r.member_id) FROM responses r WHERE r.survey_id = s.survey_id) AS respondents_count
     FROM surveys s
     ORDER BY s.created_at DESC"
);
$surveys = $surveysStmt->fetchAll();

$totalSurveys    = count($surveys);
$activeSurveys   = 0;
$totalResponses  = 0;
$draftSurveys    = 0;
foreach ($surveys as $s) {
    if ($s['status'] === 'active') $activeSurveys++;
    if ($s['status'] === 'draft')  $draftSurveys++;
    $totalResponses += (int)$s['respondents_count'];
}

// Fetch recently registered accounts
$recentStmt = $pdo->query(
    "SELECT m.*, ma.last_login
     FROM member m
     LEFT JOIN member_account ma ON ma.member_id = m.member_id
     ORDER BY m.created_at DESC LIMIT 10"
);
$recentMembers = $recentStmt->fetchAll();

$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<!-- ================= PAGE 1: MAIN DASHBOARD VIEW ================= -->
<div id="dashboardView" class="page-view active">
  <header class="welcome-banner">
    <div class="user-avatar-wrapper avatar-uploadable" onclick="openAdminPhotoModal()" title="Click to Add/Update Profile Picture" role="button" tabindex="0">
      <?php if (!empty($_SESSION['profile_photo'])): ?>
        <img src="<?= e(photo_url($_SESSION['profile_photo'])) ?>" alt="Admin Profile Photo" class="admin-banner-avatar-img" id="dashAdminAvatarImg">
      <?php else: ?>
        <i class="fa-solid fa-user avatar-icon" id="dashAdminAvatarIcon"></i>
      <?php endif; ?>
      <span class="avatar-upload-badge" title="Upload Photo"><i class="fa-solid fa-camera"></i></span>
      <span class="status-indicator" title="System Active"></span>
    </div>
    <div class="user-info">
      <div class="badge">STAFF ID: STF-<?= e(str_pad((string)current_user_id(), 4, '0', STR_PAD_LEFT)) ?></div>
      <h1>Welcome, <?= e($_SESSION['username']) ?>!</h1>
      <div class="meta-tags">
        <span><i class="fa-solid fa-id-badge"></i> Role: <?= e(ucfirst($_SESSION['role'] ?? 'Staff')) ?></span>
        <span><i class="fa-solid fa-circle-check"></i> System Active</span>
      </div>
    </div>
  </header>

  <div class="section-title flex-title-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
    <h2>Main Panel Overview</h2>
  </div>

  <div class="metrics-grid">
    <div class="metric-card hover-glow-card">
      <div class="metric-icon cyan">
        <i class="fa-solid fa-users"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Total Registered Members</span>
        <h3 class="metric-value"><?= number_format($totalMembers) ?></h3>
        <span class="metric-subtext positive"><i class="fa-solid fa-check"></i> Live database count</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon amber">
        <i class="fa-solid fa-user-pen"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Recently Updated Profiles</span>
        <h3 class="metric-value"><?= number_format($recentEdits) ?></h3>
        <span class="metric-subtext">Edits made this week</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald">
        <i class="fa-solid fa-id-card"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">New Accounts Added Today</span>
        <h3 class="metric-value"><?= number_format($newToday) ?></h3>
        <span class="metric-subtext positive">Registered by Staff</span>
      </div>
    </div>
  </div>

  <!-- ================= SURVEY OVERVIEW SECTION ================= -->
  <div class="section-title" style="margin-top: 2.5rem; margin-bottom: 0.5rem;">
    <h2>Cooperative Survey Overview</h2>
  </div>

  <div class="metrics-grid">
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
        <span class="metric-subtext amber"><i class="fa-solid fa-clock"></i> Unpublished</span>
      </div>
    </div>
  </div>

  <div class="section-header-flex view-header-wrapper" style="margin-top: 2.5rem; margin-bottom: 1.25rem;">
    <div class="section-title">
      <h2>Recently Registered Accounts</h2>
      <p class="section-subtitle">Latest accounts registered in the system</p>
    </div>
  </div>

  <!-- Side-by-Side Search Bar & Filter Button matching Member Records -->
  <div class="side-by-side-controls" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="recentRegSearch" placeholder="Search recently registered accounts..." onkeyup="filterRecentRegistrations()" aria-label="Search recently registered accounts">
    </div>

    <!-- Filter Popover Menu -->
    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="dashFilterBtn" onclick="toggleDashFilterMenu(event)" aria-label="Toggle filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="dashFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders"></i> Filter Options</h4>
          <button type="button" class="close-popover-btn" onclick="toggleDashFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body">
          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-calendar-days"></i> Timeline:</label>
            <select id="regTimelineFilter" class="table-filter-select-lg">
              <option value="all">All Time</option>
              <option value="today">Added Today</option>
              <option value="7days">Past 7 Days</option>
              <option value="30days">Past 30 Days</option>
            </select>
          </div>

          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-sliders"></i> Show Only:</label>
            <select id="regTypeFilter" class="table-filter-select-lg">
              <option value="all">All Registrations</option>
              <option value="edited">Recently Edited Accounts</option>
            </select>
          </div>
        </div>

        <div class="filter-popover-footer">
          <button type="button" class="btn btn-primary btn-sm" onclick="applyDashFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="table-card">
    <table class="dashboard-table" id="recentRegistrationsTable">
      <thead>
        <tr>
          <th style="width: 54px; text-align: center;">Photo</th>
          <th>Account No.</th>
          <th style="text-align: center;">Member Name</th>
          <th>Time Registered</th>
          <th>Date Registered (mm/dd/yyyy)</th>
          <th>Last Modified</th>
          <th style="text-align: center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($recentMembers)): ?>
          <?php foreach ($recentMembers as $row): ?>
            <?php
              $fullName   = trim($row['firstname'] . ' ' . $row['lastname']);
              $accNo      = $row['account_number'];
              $createdDt  = !empty($row['created_at']) ? strtotime($row['created_at']) : time();
              $updatedDt  = !empty($row['updated_at']) ? strtotime($row['updated_at']) : $createdDt;
              $created    = date('m/d/Y', $createdDt);
              $timeReg    = date('h:i A', $createdDt);
              $updated    = date('m/d/Y, h:i A', $updatedDt);
              $isEdited   = ($row['updated_at'] !== $row['created_at']);
              $daysDiff   = floor((time() - $createdDt) / (60 * 60 * 24));
              $hasPhoto   = !empty($row['passport_photo']) && !str_contains($row['passport_photo'], 'default-avatar');
            ?>
            <tr data-days="<?= (int)$daysDiff ?>" data-created="<?= date('Y-m-d', $createdDt) ?>" data-edited="<?= $isEdited ? 'true' : 'false' ?>">
              <td style="text-align: center;">
                <div class="table-avatar avatar-clickable" onclick="openPhotoModal('<?= e($accNo) ?>')" title="Click to View Member Photo" role="button" tabindex="0" aria-label="View member photo">
                  <?php if ($hasPhoto): ?>
                    <img src="<?= e(photo_url($row['passport_photo'])) ?>" alt="Photo" class="table-avatar-thumb">
                  <?php else: ?>
                    <i class="fa-solid fa-image"></i>
                  <?php endif; ?>
                </div>
              </td>
              <td><strong class="account-tag"><?= e($accNo) ?></strong></td>
              <td style="text-align: center; font-weight: 600;"><?= e($fullName) ?></td>
              <td>
                <span class="badge" style="background: rgba(14, 165, 233, 0.12); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); font-weight: 700; font-size: 0.78rem; padding: 3px 8px; border-radius: 6px;">
                  <i class="fa-regular fa-clock me-1"></i><?= e($timeReg) ?>
                </span>
              </td>
              <td><span style="font-weight: 600;"><?= e($created) ?></span></td>
              <td>
                <?php if ($isEdited): ?>
                  <span class="edited-tag"><i class="fa-solid fa-pen-to-square"></i> <?= e($updated) ?></span>
                <?php else: ?>
                  <span class="text-muted-sm">No updates</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <button class="btn btn-secondary btn-sm action-view-btn" onclick="viewMember('<?= e($accNo) ?>')" aria-label="View profile for <?= e($accNo) ?>"><i class="fa-solid fa-eye"></i> View</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-4">No registered members found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
