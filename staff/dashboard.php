<?php
/**
 * staff/dashboard.php
 * -------------------------------------------------------
 * Staff Portal — Main Dashboard Overview View (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';


$pageTitle = 'Staff Dashboard';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<!-- ================= PAGE 1: MAIN DASHBOARD VIEW ================= -->
<div id="dashboardView" class="page-view active">
  <header class="welcome-banner">
    <div class="user-avatar-wrapper avatar-uploadable" onclick="openAdminPhotoModal()" title="Click to Add/Update Profile Picture" role="button" tabindex="0">
      <img src="" alt="Admin Profile Photo" class="admin-banner-avatar-img" id="dashAdminAvatarImg" style="display:none;">
      <i class="fa-solid fa-user avatar-icon" id="dashAdminAvatarIcon"></i>
      <span class="avatar-upload-badge" title="Upload Photo"><i class="fa-solid fa-camera"></i></span>
      <span class="status-indicator" title="System Active"></span>
    </div>
    <div class="user-info">
      <div class="badge">STAFF ID: <span id="dashStaffId">Loading...</span></div>
      <h1>Welcome, <span id="dashWelcomeName">User</span>!</h1>
      <div class="meta-tags">
        <span><i class="fa-solid fa-id-badge"></i> Role: <span id="dashRole">Staff</span></span>
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
        <h3 class="metric-value" id="dashTotalMembers">0</h3>
        <span class="metric-subtext positive"><i class="fa-solid fa-check"></i> Live database count</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon amber">
        <i class="fa-solid fa-user-pen"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Recently Updated Profiles</span>
        <h3 class="metric-value" id="dashRecentEdits">0</h3>
        <span class="metric-subtext">Edits made this week</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald">
        <i class="fa-solid fa-id-card"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">New Accounts Added Today</span>
        <h3 class="metric-value" id="dashNewToday">0</h3>
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
        <h3 class="metric-value" id="dashTotalSurveys">0</h3>
        <span class="metric-subtext cyan"><i class="fa-solid fa-database"></i> All registered surveys</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald">
        <i class="fa-solid fa-tower-broadcast"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Active Surveys</span>
        <h3 class="metric-value" id="dashActiveSurveys">0</h3>
        <span class="metric-subtext emerald positive"><i class="fa-solid fa-circle-check"></i> Open to members</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon purple">
        <i class="fa-solid fa-users"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Total Respondents</span>
        <h3 class="metric-value" id="dashTotalResponses">0</h3>
        <span class="metric-subtext purple"><i class="fa-solid fa-user-check"></i> Unique submissions</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon amber" style="background: rgba(245, 158, 11, 0.15); color: #fbbf24;">
        <i class="fa-solid fa-file-pen"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Draft / Inactive</span>
        <h3 class="metric-value" id="dashDraftSurveys">0</h3>
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
      <tbody id="dashRecentRegistrationsTbody">
        <tr>
          <td colspan="7" class="text-center text-muted py-4">Loading registered members...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>



<script type="module">
import { db, auth } from '../assets/js/firebase-init.js';
import { collection, collectionGroup, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

import { doc, getDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

onAuthStateChanged(auth, async (user) => {
    if (!user) {
        window.location.href = '../login.php';
    } else {
        // Fetch staff profile
        try {
            const userDoc = await getDoc(doc(db, 'staff', user.uid));
            if (userDoc.exists()) {
                const data = userDoc.data();
                document.getElementById('dashWelcomeName').textContent = data.username || data.name || user.email;
                document.getElementById('dashRole').textContent = data.role ? data.role.charAt(0).toUpperCase() + data.role.slice(1) : 'Staff';
                if (data.profile_photo) {
                    const img = document.getElementById('dashAdminAvatarImg');
                    img.src = '../uploads/' + data.profile_photo;
                    img.style.display = 'block';
                    document.getElementById('dashAdminAvatarIcon').style.display = 'none';
                }
            } else {
                document.getElementById('dashWelcomeName').textContent = user.email;
            }
            document.getElementById('dashStaffId').textContent = user.uid.substring(0, 8).toUpperCase();
        } catch (e) {
            console.error('Error fetching staff profile:', e);
        }
        
        loadDashboardData();
    }
});

async function loadDashboardData() {
    try {
        const membersSnap = await getDocs(collection(db, 'member'));
        let totalMembers = 0;
        let newToday = 0;
        
        const now = new Date();
        const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        let recentMembers = [];
        
        window.membersDatabase = window.membersDatabase || {};

        membersSnap.forEach(doc => {
            totalMembers++;
            const data = doc.data();
            
            if (data.created_at) {
                let createdAtDate;
                if (data.created_at.toDate) createdAtDate = data.created_at.toDate();
                else if (typeof data.created_at === 'string') createdAtDate = new Date(data.created_at);
                else if (data.created_at.seconds) createdAtDate = new Date(data.created_at.seconds * 1000);

                if (createdAtDate && createdAtDate >= startOfToday) {
                    newToday++;
                }
            }
            recentMembers.push({ id: doc.id, ...data });

            const accNo = data.account_number || doc.id;
            window.membersDatabase[accNo] = {
                accNo: accNo,
                account_number: accNo,
                member_id: doc.id,
                firstname: data.firstname || '',
                middlename: data.middlename || '',
                lastname: data.lastname || '',
                name: ((data.firstname || '') + ' ' + (data.lastname || '')).trim(),
                fullName: ((data.firstname || '') + ' ' + (data.lastname || '')).trim(),
                age: data.age || 'N/A',
                sex: data.sex || 'Not Specified',
                nationality: data.nationality || 'Filipino',
                civil: data.civil_status || 'Single',
                civilStatus: data.civil_status || 'Single',
                status: data.account_status || 'Active',
                account_status: data.account_status || 'Active',
                contact: data.contact_number || 'N/A',
                contact_number: data.contact_number || 'N/A',
                address: data.address || 'N/A',
                occupation: data.occupation || 'None',
                employer: data.employer || 'None',
                employer_address: data.employer_address || data.office_address || 'None',
                email: data.email || 'N/A',
                passport_photo: data.passport_photo ? `../uploads/${data.passport_photo}` : null,
                signature_photo: data.signature ? `../uploads/${data.signature}` : null,
                father_name: data.father_name || 'Not Specified',
                mother_name: data.mother_name || 'Not Specified',
                spouseName: data.spouse_name || 'Not Specified',
                spouse: {
                    name: data.spouse_name || 'Not Specified',
                    occupation: data.spouse_occupation || 'Not Specified',
                    employer: data.spouse_employer || 'Not Specified',
                },
                childrenList: data.children || [],
                children: data.children || [],
                references: data.references || []
            };
        });

        document.getElementById('dashTotalMembers').textContent = totalMembers.toLocaleString();
        document.getElementById('dashNewToday').textContent = newToday.toLocaleString();

        recentMembers.sort((a, b) => {
            const dateA = a.created_at?.toDate ? a.created_at.toDate() : (new Date(a.created_at || 0));
            const dateB = b.created_at?.toDate ? b.created_at.toDate() : (new Date(b.created_at || 0));
            return dateB - dateA;
        });

        const top10 = recentMembers.slice(0, 10);
        const tbody = document.getElementById('dashRecentRegistrationsTbody');
        tbody.innerHTML = '';

        if (top10.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No registered members found.</td></tr>';
        } else {
            top10.forEach(m => {
                const fullName = (m.firstname || '') + ' ' + (m.lastname || '');
                const accNo = m.account_number || m.id;
                
                const createdDt = m.created_at?.toDate ? m.created_at.toDate() : new Date(m.created_at || Date.now());
                const updatedDt = m.updated_at?.toDate ? m.updated_at.toDate() : new Date(m.updated_at || createdDt);
                
                const created = createdDt.toLocaleDateString('en-US', {month:'2-digit', day:'2-digit', year:'numeric'});
                const timeReg = createdDt.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
                const updated = updatedDt.toLocaleDateString('en-US', {month:'2-digit', day:'2-digit', year:'numeric'}) + ', ' + updatedDt.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit'});
                
                const isEdited = (m.updated_at && m.updated_at !== m.created_at) || m.updated_at;
                const daysDiff = Math.floor((Date.now() - createdDt.getTime()) / (1000 * 3600 * 24));
                const hasPhoto = m.passport_photo && !m.passport_photo.includes('default-avatar');
                const photoSrc = hasPhoto ? `../uploads/${m.passport_photo}` : '';

                let photoHtml = hasPhoto ? 
                    `<img src="${photoSrc}" alt="Photo" class="table-avatar-thumb">` : 
                    `<i class="fa-solid fa-image"></i>`;

                let editHtml = isEdited ? 
                    `<span class="edited-tag"><i class="fa-solid fa-pen-to-square"></i> ${updated}</span>` : 
                    `<span class="text-muted-sm">No updates</span>`;

                tbody.innerHTML += `
                    <tr data-days="${daysDiff}" data-created="${createdDt.toISOString().split('T')[0]}" data-edited="${!!isEdited}">
                        <td style="text-align: center;">
                            <div class="table-avatar avatar-clickable" onclick="openPhotoModal('${accNo}')" title="Click to View Member Photo" role="button" tabindex="0">
                                ${photoHtml}
                            </div>
                        </td>
                        <td><strong class="account-tag">${accNo}</strong></td>
                        <td style="text-align: center; font-weight: 600;">${fullName.trim()}</td>
                        <td>
                            <span class="badge" style="background: rgba(14, 165, 233, 0.12); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); font-weight: 700; font-size: 0.78rem; padding: 3px 8px; border-radius: 6px;">
                            <i class="fa-regular fa-clock me-1"></i>${timeReg}
                            </span>
                        </td>
                        <td><span style="font-weight: 600;">${created}</span></td>
                        <td>${editHtml}</td>
                        <td style="text-align: center;">
                            <button class="btn btn-secondary btn-sm action-view-btn" onclick="viewMember('${accNo}')"><i class="fa-solid fa-eye"></i> View</button>
                        </td>
                    </tr>
                `;
            });
        }

        const updatesSnap = await getDocs(collectionGroup(db, 'update_history'));
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        const uniqueEditors = new Set();
        updatesSnap.forEach(doc => {
            const data = doc.data();
            let updateDate;
            if (data.update_date?.toDate) updateDate = data.update_date.toDate();
            else if (data.update_date) updateDate = new Date(data.update_date);
            if (updateDate && updateDate >= sevenDaysAgo) {
                uniqueEditors.add(data.member_id);
            }
        });
        document.getElementById('dashRecentEdits').textContent = uniqueEditors.size.toLocaleString();

        const surveysSnap = await getDocs(collection(db, 'surveys'));
        let totalSurveys = 0, activeSurveys = 0, draftSurveys = 0;
        surveysSnap.forEach(doc => {
            totalSurveys++;
            const data = doc.data();
            if (data.status === 'active') activeSurveys++;
            if (data.status === 'draft') draftSurveys++;
        });
        document.getElementById('dashTotalSurveys').textContent = totalSurveys.toLocaleString();
        document.getElementById('dashActiveSurveys').textContent = activeSurveys.toLocaleString();
        document.getElementById('dashDraftSurveys').textContent = draftSurveys.toLocaleString();

        const responsesSnap = await getDocs(collection(db, 'responses'));
        const uniqueRespondents = new Set();
        responsesSnap.forEach(doc => {
            const data = doc.data();
            if (data.member_id) uniqueRespondents.add(data.member_id);
        });
        document.getElementById('dashTotalResponses').textContent = uniqueRespondents.size.toLocaleString();

    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}
</script>


<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

