<?php
/**
 * staff/credentials.php
 * -------------------------------------------------------
 * Staff Portal — Member Password Management View (Firebase Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

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
      <tbody id="credentialsTableBody">
        <tr>
          <td colspan="5" class="text-center text-muted py-4">Loading credentials...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

<script type="module">
import { app, db, auth } from '../assets/js/firebase-init.js';
import { collection, getDocs, updateDoc, doc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = '../login.php';
        return;
    }
    loadCredentials();
});

async function loadCredentials() {
    const tbody = document.getElementById('credentialsTableBody');
    try {
        const snapshot = await getDocs(collection(db, 'member'));
        if (snapshot.empty) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No member credentials found in database.</td></tr>';
            return;
        }

        let html = '';
        snapshot.forEach(docSnap => {
            const c = docSnap.data();
            const fullName = `${c.firstname || ''} ${c.lastname || ''}`.trim();
            const accNo = c.account_number || 'Unknown';
            const isDefault = c.first_login !== false; // assuming true if not set explicitly
            const lastChange = isDefault ? 'None' : (c.last_login ? new Date(c.last_login.toMillis ? c.last_login.toMillis() : c.last_login).toLocaleDateString() : 'Unknown');
            const statusTag = isDefault ? 'temp' : 'active';
            const docId = docSnap.id;

            html += `
            <tr data-status="${statusTag}">
              <td><strong class="account-tag">${accNo}</strong></td>
              <td>
                <div class="user-meta-cell">
                  <span class="member-fullname">${fullName}</span>
                </div>
              </td>
              <td>${lastChange}</td>
              <td style="text-align: center;">
                ${isDefault 
                  ? '<span class="status-pill status-default">Default Password</span>' 
                  : '<span class="status-pill status-active">Active</span>'}
              </td>
              <td style="text-align: center;">
                ${isDefault
                  ? `<button type="button" class="btn btn-secondary btn-sm" disabled style="opacity: 0.45; cursor: not-allowed; pointer-events: none;" title="Password is already set to default account credential">
                      <i class="fa-solid fa-key"></i> Reset Password
                    </button>`
                  : `<button type="button" class="btn btn-secondary btn-sm" onclick="triggerReset('${docId}', '${accNo}')">
                      <i class="fa-solid fa-key"></i> Reset Password
                    </button>`}
              </td>
            </tr>`;
        });
        tbody.innerHTML = html;
    } catch (error) {
        console.error("Error loading credentials:", error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Error loading data.</td></tr>';
    }
}

window.triggerReset = function(docId, accNo) {
    if (typeof showSystemConfirm === 'function') {
        showSystemConfirm({
            title: 'Reset Member Password',
            message: `Are you sure you want to reset the password for ${accNo} to default?`,
            confirmText: 'Yes, Reset',
            isDanger: true,
            onConfirm: () => performReset(docId, accNo)
        });
    } else if (confirm(`Are you sure you want to reset the password for ${accNo} to default?`)) {
        performReset(docId, accNo);
    }
};

async function performReset(docId, accNo) {
    try {
        await updateDoc(doc(db, 'member', docId), {
            first_login: true,
            password_changed: false
        });
        alert(`Password reset successfully for Account ${accNo}.`);
        loadCredentials();
    } catch (error) {
        console.error("Error resetting password:", error);
        alert("Failed to reset password.");
    }
}
</script>

