<?php
/**
 * staff/credentials.php
 * -------------------------------------------------------
 * Staff Portal — Member Password Management View (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

// Handle Password Reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    verify_csrf($_POST['csrf_token'] ?? '');
    $targetId = (int) ($_POST['member_id'] ?? 0);

    $stmt = $pdo->prepare("SELECT m.member_id, m.account_number, m.firstname, m.lastname FROM member m WHERE m.member_id = ?");
    $stmt->execute([$targetId]);
    $mem = $stmt->fetch();

    if ($mem) {
        // Reset password to default (matches account_number rule)
        $defaultPassword = $mem['account_number'];
        $newHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $up = $pdo->prepare("UPDATE member_account SET password = ?, first_login = TRUE, password_changed = FALSE WHERE member_id = ?");
        $up->execute([$newHash, $targetId]);

        // Log reset into update_history
        $hist = $pdo->prepare(
            "INSERT INTO update_history (member_id, updated_by_staff_id, update_type, remarks)
             VALUES (?, ?, 'Password Reset', 'Staff reset member password to default account number.')"
        );
        $hist->execute([$targetId, current_user_id()]);

        set_flash('success', 'Password reset successfully for Account ' . $mem['account_number'] . ' (Default password: ' . $defaultPassword . ').');
        redirect('staff/credentials.php');
    }
}

// Fetch member accounts with member details
$stmt = $pdo->query(
    "SELECT ma.*, m.account_number, m.firstname, m.lastname, m.created_at AS member_created_at
     FROM member_account ma
     JOIN member m ON m.member_id = ma.member_id
     ORDER BY m.created_at DESC"
);
$credentials = $stmt->fetchAll();

$pageTitle = 'Password Management';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<!-- ================= PAGE 4: CREDENTIALS & PASSWORD MANAGEMENT ================= -->
<div id="credentialsView" class="page-view active">
  <div class="section-header-flex view-header-wrapper">
    <div class="section-title">
      <h2>Member Password Management</h2>
      <p class="section-subtitle">Search, view, edit, and reset portal passwords for active registered members</p>
    </div>
  </div>

  <div class="timeline-collapsible table-card" style="margin-bottom: 1.25rem;">
    <button class="timeline-summary" type="button" onclick="toggleCredentialsTimeline(this)" aria-expanded="false">
      <div class="summary-content">
        <i class="fa-solid fa-shield-halved summary-icon" style="color: var(--primary-teal); text-shadow: 0 0 8px rgba(0, 194, 168, 0.8);"></i>
        <span>Credentials Lifecycle &amp; Security Timeline</span>
      </div>
      <i class="fa-solid fa-chevron-down chevron-icon"></i>
    </button>

    <div class="timeline-collapsible-content" id="credentialsTimelineContent">
      <div class="cyber-timeline-track">
        <div class="cyber-timeline-line"></div>
        <ul class="timeline-list cyber-timeline-list">
          <li class="timeline-item cyber-item">
            <span class="cyber-bullet"></span>
            <div class="timeline-body">
              <span class="timeline-date">Stage 1</span>
              <strong>Account Registration &amp; First-Time Activation</strong>
              <p>Initial system credential is generated and dispatched to member upon onboarding.</p>
            </div>
          </li>
          <li class="timeline-item cyber-item">
            <span class="cyber-bullet"></span>
            <div class="timeline-body">
              <span class="timeline-date">Stage 2</span>
              <strong>Self-Service / Staff Password Updates</strong>
              <p>Staff triggers manual reset or user updates credentials through portal settings.</p>
            </div>
          </li>
          <li class="timeline-item cyber-item">
            <span class="cyber-bullet"></span>
            <div class="timeline-body">
              <span class="timeline-date">Stage 3</span>
              <strong>Security Audit &amp; Auto-Lockout Protocols</strong>
              <p>Accounts with excessive failed logins are temporarily locked for up to 30 days until staff verification.</p>
            </div>
          </li>
          <li class="timeline-item cyber-item">
            <span class="cyber-bullet"></span>
            <div class="timeline-body">
              <span class="timeline-date">Stage 4</span>
              <strong>Account Revocation &amp; Decommissioning</strong>
              <p>Inactive or offboarded member credentials are permanently purged and logged to audit archives.</p>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <script>
  function toggleCredentialsTimeline(btn) {
    const content = document.getElementById('credentialsTimelineContent');
    const chevron = btn.querySelector('.chevron-icon');
    const expanded = btn.getAttribute('aria-expanded') === 'true';

    if (expanded) {
      content.style.maxHeight = content.scrollHeight + 'px';
      requestAnimationFrame(() => {
        content.style.maxHeight = '0';
        content.style.opacity = '0';
      });
      btn.setAttribute('aria-expanded', 'false');
      chevron.style.transform = 'rotate(0deg)';
    } else {
      content.style.maxHeight = content.scrollHeight + 'px';
      content.style.opacity = '1';
      btn.setAttribute('aria-expanded', 'true');
      chevron.style.transform = 'rotate(180deg)';
      content.addEventListener('transitionend', function onEnd() {
        content.style.maxHeight = 'none';
        content.removeEventListener('transitionend', onEnd);
      }, { once: true });
    }
  }
  </script>

  <div class="side-by-side-controls" style="margin-bottom: 1.25rem;">
    <div class="search-bar-container view-search" style="flex: 1; max-width: 560px;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" class="search-input" id="credentialsSearchInput" placeholder="Search Member ID or Name..." onkeyup="searchCredentialsTable(this.value)" aria-label="Search Member ID or Name">
    </div>

    <!-- Themed Credential Status Filter Popover Menu -->
    <div class="unified-filter-wrapper">
      <button type="button" class="btn btn-secondary filter-btn-trigger" id="credFilterBtn" onclick="toggleCredFilterMenu(event)" aria-label="Toggle filter options">
        <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down caret-icon"></i>
      </button>

      <div id="credFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();" style="width: 340px;">
        <div class="filter-popover-header">
          <div>
            <h4><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Credentials</h4>
            <span style="font-size: 0.76rem; color: var(--text-dim); display: block; margin-top: 2px;">Filter member password status</span>
          </div>
          <button type="button" class="close-popover-btn" onclick="toggleCredFilterMenu(event)" aria-label="Close filter menu">&times;</button>
        </div>
        
        <div class="filter-popover-body space-decluttered-body">
          <input type="hidden" id="credStatusFilter" value="all">
          <div class="filter-popover-group" style="margin-bottom: 0;">
            <label class="filter-label"><i class="fa-solid fa-key text-cyan me-1"></i> Account Security Status:</label>
            <div class="cred-status-grid">
              <!-- Option 1: All -->
              <div class="cred-status-chip active" data-status-val="all" onclick="setCredentialStatusFilter('all')">
                <div class="chip-label-box">
                  <span class="chip-title">
                    <i class="fa-solid fa-earth-americas" style="color: #38bdf8;"></i> All Account Statuses
                  </span>
                  <span class="chip-desc">View all member accounts</span>
                </div>
                <span class="chip-check"><i class="fa-solid fa-check"></i></span>
              </div>

              <!-- Option 2: Active Verified -->
              <div class="cred-status-chip" data-status-val="active" onclick="setCredentialStatusFilter('active')">
                <div class="chip-label-box">
                  <span class="chip-title">
                    <i class="fa-solid fa-circle-check" style="color: #34d399;"></i> Active Verified Credentials
                  </span>
                  <span class="chip-desc">Password changed &amp; secure</span>
                </div>
                <span class="chip-check"><i class="fa-solid fa-check"></i></span>
              </div>

              <!-- Option 3: Default Password -->
              <div class="cred-status-chip" data-status-val="temp" onclick="setCredentialStatusFilter('temp')">
                <div class="chip-label-box">
                  <span class="chip-title">
                    <i class="fa-solid fa-key" style="color: #fbbf24;"></i> Default Password
                  </span>
                  <span class="chip-desc">Pending initial password change</span>
                </div>
                <span class="chip-check"><i class="fa-solid fa-check"></i></span>
              </div>
            </div>
          </div>
        </div>

        <div class="filter-popover-footer" style="display: flex; justify-content: flex-end;">
          <button type="button" class="btn btn-primary btn-sm" onclick="applyCredFilters(event)">
            <i class="fa-solid fa-check"></i> Apply Filters
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="table-card">
    <table class="dashboard-table" id="credentialsTable">
      <thead>
        <tr>
          <th>Member ID</th>
          <th>Full Name</th>
          <th>Last Password Change</th>
          <th style="text-align: center;">Account Status</th>
          <th style="text-align: center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($credentials)): ?>
          <?php foreach ($credentials as $c): ?>
            <?php
              $fullName   = trim($c['firstname'] . ' ' . $c['lastname']);
              $accNo      = $c['account_number'];
              $isDefault  = (bool) $c['first_login'];
              $lastChange = $c['password_changed'] ? format_date($c['last_login'] ?? $c['member_created_at'], 'm/d/Y') : 'None';
              $statusTag  = $isDefault ? 'temp' : 'active';
            ?>
            <tr data-status="<?= e($statusTag) ?>">
              <td><strong class="account-tag"><?= e($accNo) ?></strong></td>
              <td>
                <div class="user-meta-cell">
                  <span class="member-fullname"><?= e($fullName) ?></span>
                </div>
              </td>
              <td><?= e($lastChange) ?></td>
              <td style="text-align: center;">
                <?php if ($isDefault): ?>
                  <span class="status-pill status-default">Default Password</span>
                <?php else: ?>
                  <span class="status-pill status-active">Active</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <?php if ($isDefault): ?>
                  <button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.45; cursor: not-allowed; pointer-events: none;" title="Password is already set to default account credential">
                    <i class="fa-solid fa-key"></i> Reset Password
                  </button>
                <?php else: ?>
                  <form method="post" style="display:inline-block;" id="resetPwdForm_<?= e($c['member_id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="member_id" value="<?= e($c['member_id']) ?>">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="showSystemConfirm({ title: 'Reset Member Password', message: 'Are you sure you want to reset the password for <?= e(addslashes($accNo)) ?> to default?', confirmText: 'Yes, Reset', isDanger: true, onConfirm: () => document.getElementById('resetPwdForm_<?= e($c['member_id']) ?>').submit() });">
                      <i class="fa-solid fa-key"></i> Reset Password
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">No member credentials found in database.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
