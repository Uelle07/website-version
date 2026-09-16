<?php
/**
 * staff/updated_records.php
 * -------------------------------------------------------
 * Staff Portal — System Audit Log View (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

if (!function_exists('get_action_display_label')) {
    function get_action_display_label(string $action): string {
        return match($action) {
            'profile_update', 'Information Update'  => 'Information Update',
            'password_change', 'Password Changed'    => 'Password Changed',
            'photo_upload', 'Passport Photo Updated' => 'Passport Photo Updated',
            'Password Reset', 'Reset Password'       => 'Password Reset',
            'New Member Created'                     => 'New Member Created',
            'Deleted Member Information'            => 'Deleted Member Information',
            'Unlocked Account'                       => 'Unlocked Account',
            default => ucwords(str_replace('_', ' ', $action))
        };
    }
}

$actionMade = clean($_GET['action_made'] ?? 'all');
$sortOrder  = clean($_GET['sort'] ?? 'recent');

// Action options in the filter
$displayOptions = [
    'Information Update'         => 'Information Update',
    'New Member Created'         => 'New Member Created',
    'Password Changed'           => 'Password Changed',
    'Password Reset'             => 'Password Reset',
    'Deleted Member Information' => 'Deleted Member Information'
];

// Fetch audit log entries from update_history table joined with member and staff
$query = "SELECT h.*, m.account_number, m.firstname, m.lastname,
                 s.fullname AS staff_fullname
          FROM update_history h
          LEFT JOIN member m ON m.member_id = h.member_id
          LEFT JOIN staff s ON s.staff_id = h.updated_by_staff_id";

$params = [];
if ($actionMade !== 'all' && $actionMade !== '') {
    if ($actionMade === 'Information Update') {
        $query .= " WHERE (h.update_type = 'Information Update' OR h.update_type = 'profile_update')";
    } elseif ($actionMade === 'Password Changed' || $actionMade === 'password_change') {
        $query .= " WHERE (h.update_type = 'Password Changed' OR h.update_type = 'password_change')";
    } elseif ($actionMade === 'Passport Photo Updated' || $actionMade === 'photo_upload') {
        $query .= " WHERE (h.update_type = 'Passport Photo Updated' OR h.update_type = 'photo_upload')";
    } elseif ($actionMade === 'Password Reset') {
        $query .= " WHERE (h.update_type = 'Password Reset' OR h.update_type = 'Reset Password')";
    } elseif ($actionMade === 'Deleted Member Information') {
        $query .= " WHERE h.update_type = 'Deleted Member Information'";
    } else {
        $query .= " WHERE h.update_type = ?";
        $params[] = $actionMade;
    }
}

if ($sortOrder === 'oldest') {
    $query .= " ORDER BY h.update_date ASC";
} elseif ($sortOrder === 'staff_asc') {
    $query .= " ORDER BY staff_fullname ASC";
} elseif ($sortOrder === 'target_asc') {
    $query .= " ORDER BY m.account_number ASC";
} else {
    $query .= " ORDER BY h.update_date DESC";
}

$query .= " LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$auditLogs = $stmt->fetchAll();

$pageTitle = 'System Audit Log';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<!-- ================= PAGE 3: AUDIT LOG VIEW ================= -->
<div id="auditView" class="page-view active">
  <div class="section-header-flex view-header-wrapper" style="display: flex; justify-content: space-between; align-items: center;">
    <div class="section-title">
      <h2>System Audit Log</h2>
      <p class="section-subtitle">Trace modified member records, staff actions, timestamps, and details</p>
    </div>
  </div>

  <div class="side-by-side-controls" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="auditLogSearch" placeholder="Search Action, Staff, or Target..." onkeyup="searchAuditLog(this.value)" aria-label="Search audit log">
    </div>

    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="auditFilterBtn" onclick="toggleAuditFilterMenu(event)" aria-label="Toggle audit filters">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="auditFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();">
        <div class="filter-popover-header">
          <h4><i class="fa-solid fa-sliders"></i> Filter Options</h4>
          <button type="button" class="close-popover-btn" onclick="toggleAuditFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body" style="padding: 1rem 0;">
          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-arrow-down-short-wide"></i> Sort By:</label>
            <select id="auditSortOrder" class="table-filter-select-lg">
              <option value="recent" <?= $sortOrder === 'recent' ? 'selected' : '' ?>>Newest to Oldest</option>
              <option value="oldest" <?= $sortOrder === 'oldest' ? 'selected' : '' ?>>Oldest to Newest</option>
              <option value="staff_asc" <?= $sortOrder === 'staff_asc' ? 'selected' : '' ?>>Staff Name (A-Z)</option>
              <option value="target_asc" <?= $sortOrder === 'target_asc' ? 'selected' : '' ?>>Target Account (A-Z)</option>
            </select>
          </div>

          <div class="filter-popover-group">
            <label class="filter-label"><i class="fa-solid fa-list-check"></i> Action Made:</label>
            <select id="auditActionFilter" class="table-filter-select-lg">
              <option value="all" <?= $actionMade === 'all' ? 'selected' : '' ?>>All Actions</option>
              <?php foreach ($displayOptions as $displayLabel => $rawVal): ?>
                <option value="<?= e($displayLabel) ?>" <?= ($actionMade === $displayLabel || $actionMade === $rawVal) ? 'selected' : '' ?>><?= e($displayLabel) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="filter-popover-footer">
          <button type="button" class="btn btn-primary btn-sm" onclick="applyAuditFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="table-card" style="margin-top: 1rem;">
    <table class="dashboard-table audit-log-table" id="auditLogTable">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Updated By</th>
          <th>Target Account</th>
          <th style="text-align: center;">Action Made</th>
          <th style="text-align: center;">Modification Details</th>
        </tr>
      </thead>
      <tbody id="auditTableBody">
        <?php if (!empty($auditLogs)): ?>
          <?php foreach ($auditLogs as $log): ?>
            <?php
              $timestamp  = format_date($log['update_date'], 'm/d/Y - h:i A');
              $staffName  = !empty($log['staff_fullname']) ? $log['staff_fullname'] : 'Member Self-Service';
              $accNo      = !empty($log['account_number']) ? $log['account_number'] : 'ACC-' . $log['member_id'];
              $rawAction  = $log['update_type'];
              $action     = get_action_display_label($rawAction);
              $badgeClass = str_contains(strtolower($action), 'delete') ? 'delete' : (str_contains(strtolower($action), 'create') ? 'create' : 'update');
            ?>
            <tr data-action="<?= e($action) ?>" data-raw-action="<?= e($rawAction) ?>" data-timestamp="<?= e($log['update_date']) ?>" data-staff="<?= e($staffName) ?>" data-target="<?= e($accNo) ?>">
              <td><?= e($timestamp) ?></td>
              <td><span class="staff-tag"><i class="fa-solid fa-user-shield"></i> <?= e($staffName) ?></span></td>
              <td><strong class="account-tag stretched-tag"><i class="fa-solid fa-id-card"></i> <?= e($accNo) ?></strong></td>
              <td style="text-align: center;"><span class="action-badge <?= $badgeClass ?> stretched-badge"><?= e($action) ?></span></td>
              <td style="text-align: center;">
                <button class="btn btn-secondary btn-sm" onclick="openAuditDetailsModal('<?= e($accNo) ?>', '<?= e($action) ?>', '<?= e($timestamp) ?>', '<?= e($staffName) ?>', <?= htmlspecialchars(json_encode($log['remarks']), ENT_QUOTES, 'UTF-8') ?>)">
                  <i class="fa-solid fa-file-lines"></i> View Details
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">No audit log records found in database.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
