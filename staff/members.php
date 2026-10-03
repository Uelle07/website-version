<?php
/**
 * staff/members.php
 * -------------------------------------------------------
 * Staff Portal — Member Records Management View (Database Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';


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
      <tbody id="memberRecordsTbody">
        <tr>
          <td colspan="5" class="text-center text-muted py-4">Loading member records...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>



<script type="module">
import { db, auth } from '../assets/js/firebase-init.js';
import { collection, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, (user) => {
    if (!user) {
        window.location.href = '../login.php';
    } else {
        loadMembersData();
    }
});

async function loadMembersData() {
    try {
        const membersSnap = await getDocs(collection(db, 'member'));
        let members = [];
        
        window.membersDatabase = window.membersDatabase || {};

        membersSnap.forEach(doc => {
            const data = doc.data();
            members.push({ id: doc.id, ...data });

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
                passport_photo: data.passport_photo ? (data.passport_photo.startsWith('data:') || data.passport_photo.startsWith('http') ? data.passport_photo : `../uploads/${data.passport_photo}`) : null,
                signature_photo: data.signature ? (data.signature.startsWith('data:') || data.signature.startsWith('http') ? data.signature : `../uploads/${data.signature}`) : null,
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

        // Sort by created_at DESC
        members.sort((a, b) => {
            const dateA = a.created_at?.toDate ? a.created_at.toDate() : (new Date(a.created_at || 0));
            const dateB = b.created_at?.toDate ? b.created_at.toDate() : (new Date(b.created_at || 0));
            return dateB - dateA;
        });

        const tbody = document.getElementById('memberRecordsTbody');
        tbody.innerHTML = '';

        if (members.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No member records found in database.</td></tr>';
            return;
        }

        members.forEach(m => {
            const fullName = (m.firstname || '') + ' ' + (m.lastname || '');
            const accNo = m.account_number || m.id;
            
            let rawStatus = m.account_status || 'Active';
            let status = 'Active';
            if (rawStatus.toLowerCase() === 'flagged') status = 'Flagged';
            else if (rawStatus.toLowerCase() === 'inactive' || rawStatus.toLowerCase() === 'deleted') status = 'Inactive';
            
            const statusClass = status.toLowerCase() === 'active' ? 'status-active' : (status.toLowerCase() === 'inactive' ? 'status-inactive' : 'status-flagged');
            
            const createdDt = m.created_at?.toDate ? m.created_at.toDate() : new Date(m.created_at || Date.now());
            const daysDiff = Math.floor((Date.now() - createdDt.getTime()) / (1000 * 3600 * 24));
            
            const hasPhoto = m.passport_photo && !m.passport_photo.includes('default-avatar');
            const photoSrc = hasPhoto ? (m.passport_photo.startsWith('data:') || m.passport_photo.startsWith('http') ? m.passport_photo : `../uploads/${m.passport_photo}`) : '';

            let photoHtml = hasPhoto ? 
                `<img src="${photoSrc}" alt="Photo" class="table-avatar-thumb">` : 
                `<i class="fa-solid fa-image"></i>`;

            let actionBtns = `
                <button type="button" class="icon-btn btn-view-dynamic" title="View Profile" aria-label="View profile for ${accNo}" onclick="viewMember('${accNo}')"><i class="fa-solid fa-eye"></i></button>
                <button type="button" class="icon-btn btn-edit-dynamic" title="Edit Member" aria-label="Edit member information for ${accNo}" onclick="openEditMember('${accNo}')"><i class="fa-solid fa-pen"></i></button>
                <button type="button" class="icon-btn btn-print-dynamic" title="Print Member Record" aria-label="Print record for ${accNo}" onclick="printMemberRecord('${accNo}')"><i class="fa-solid fa-print"></i></button>
                <button type="button" class="icon-btn btn-download-dynamic" title="Download Record (PDF / Word)" aria-label="Download document for ${accNo}" onclick="openDownloadMemberModal('${accNo}')"><i class="fa-solid fa-file-arrow-down"></i></button>
            `;
            if (status.toLowerCase() !== 'inactive') {
                actionBtns += `<button type="button" class="icon-btn btn-delete-dynamic" title="Delete Member" aria-label="Delete member ${accNo}" onclick="openDeleteMember('${accNo}')"><i class="fa-solid fa-trash"></i></button>`;
            }

            tbody.innerHTML += `
                <tr data-days="${daysDiff}" data-status="${status.toLowerCase()}" data-created="${createdDt.toISOString().split('T')[0]}">
                    <td style="text-align: center; vertical-align: middle;">
                        <div class="table-avatar avatar-clickable" onclick="openPhotoModal('${accNo}')" title="Click to View Member Photo" role="button" tabindex="0" style="margin: 0 auto;">
                            ${photoHtml}
                        </div>
                    </td>
                    <td style="vertical-align: middle;"><strong class="account-tag"><i class="fa-solid fa-id-card me-1"></i>${accNo}</strong></td>
                    <td style="vertical-align: middle; font-weight: 600;">${fullName.trim()}</td>
                    <td style="text-align: center; vertical-align: middle;"><span class="status-pill ${statusClass}">${status}</span></td>
                    <td style="text-align: center; vertical-align: middle;">
                        <div class="table-actions-grid" style="justify-content: center; margin: 0 auto;">
                            ${actionBtns}
                        </div>
                    </td>
                </tr>
            `;
        });
    } catch (error) {
        console.error('Error loading members data:', error);
    }
}
</script>


<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

