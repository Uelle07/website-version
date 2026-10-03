<?php
/**
 * staff/updated_records.php
 * -------------------------------------------------------
 * Staff Portal — System Audit Log View (Firebase Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$actionMade = $_GET['action_made'] ?? 'all';
$sortOrder  = $_GET['sort'] ?? 'recent';

// Action options in the filter
$displayOptions = [
    'Information Update'         => 'Information Update',
    'New Member Created'         => 'New Member Created',
    'Password Changed'           => 'Password Changed',
    'Password Reset'             => 'Password Reset',
    'Deleted Member Information' => 'Deleted Member Information'
];

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
        <tr>
          <td colspan="5" class="text-center text-muted py-4">Loading audit log records...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script type="module">
import { db, auth } from '../assets/js/firebase-init.js';
import { collectionGroup, query, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = '../login.php';
        return;
    }

    const tbody = document.getElementById('auditTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Loading audit log records...</td></tr>';

    try {
        const q = query(collectionGroup(db, 'update_history'));
        const snapshot = await getDocs(q);

        if (snapshot.empty) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No audit log records found.</td></tr>';
            return;
        }

        let logs = [];
        snapshot.forEach(doc => logs.push(doc.data()));
        logs.sort((a, b) => {
            const dateA = a.update_date?.toDate ? a.update_date.toDate().getTime() : (new Date(a.update_date || 0)).getTime();
            const dateB = b.update_date?.toDate ? b.update_date.toDate().getTime() : (new Date(b.update_date || 0)).getTime();
            return dateB - dateA;
        });

        let html = '';
        logs.forEach(log => {
            const dateObj = log.update_date?.toDate ? log.update_date.toDate() : new Date(log.update_date);
            const timestamp = dateObj.toLocaleString('en-US', {
                month: '2-digit', day: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });
            const staffName = log.staff_fullname || 'Member Self-Service';
            const accNo = log.account_number || ('ACC-' + log.member_id);
            const rawAction = log.update_type || '';
            let action = rawAction;
            if (['profile_update', 'Information Update'].includes(rawAction)) action = 'Information Update';
            if (['password_change', 'Password Changed'].includes(rawAction)) action = 'Password Changed';
            if (['photo_upload', 'Passport Photo Updated'].includes(rawAction)) action = 'Passport Photo Updated';
            if (['Password Reset', 'Reset Password'].includes(rawAction)) action = 'Password Reset';
            
            const actionLower = action.toLowerCase();
            const badgeClass = actionLower.includes('delete') ? 'delete' : (actionLower.includes('create') ? 'create' : 'update');

            // Escaping for remarks JSON
            const remarksJson = JSON.stringify(log.remarks || '').replace(/"/g, '&quot;');

            html += `<tr data-action="${action}" data-raw-action="${rawAction}" data-timestamp="${dateObj.getTime()}" data-staff="${staffName}" data-target="${accNo}">
              <td>${timestamp}</td>
              <td><span class="staff-tag"><i class="fa-solid fa-user-shield"></i> ${staffName}</span></td>
              <td><strong class="account-tag stretched-tag"><i class="fa-solid fa-id-card"></i> ${accNo}</strong></td>
              <td style="text-align: center;"><span class="action-badge ${badgeClass} stretched-badge">${action}</span></td>
              <td style="text-align: center;">
                <button class="btn btn-secondary btn-sm" onclick="openAuditDetailsModal('${accNo}', '${action}', '${timestamp}', '${staffName}', ${remarksJson})">
                  <i class="fa-solid fa-file-lines"></i> View Details
                </button>
              </td>
            </tr>`;
        });
        tbody.innerHTML = html;
        
        // Trigger sort/filter after load if there's a global function
        if (typeof applyAuditFilters === 'function') {
            // we have to simulate a click or run the logic manually, but usually it relies on the loaded tr elements
            // The global script might already handle the initial state on load, but we can leave it to the user.
        }

    } catch (e) {
        console.error("Error fetching audit logs:", e);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Failed to load audit logs.</td></tr>';
    }
});
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

