<?php
/**
 * staff/search.php
 * -------------------------------------------------------
 * Staff Portal — Search Member Page (Firebase Integrated).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$pageTitle = 'Search Member';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div class="section-title flex-title-bar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
  <h2>Search Cooperative Members</h2>
  <a href="<?= e(BASE_URL) ?>/staff/members.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> View All Members</a>
</div>

<div class="table-card" style="padding: 1.5rem;">
  <form id="searchForm" class="side-by-side-controls mb-4" onsubmit="event.preventDefault();">
    <div class="search-bar-container view-search" style="flex-grow: 1;">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
      <input type="text" id="searchInput" class="search-input" placeholder="Search by Account Number, Name, or Contact...">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
  </form>

  <div id="resultsContainer">
    <p style="color: var(--text-muted);">Enter an Account Number, Name, or Contact Number above to search member records.</p>
  </div>
</div>

<script type="module">
  import { db, auth } from '../assets/js/firebase-init.js';
  import { collection, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
  import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

  onAuthStateChanged(auth, (user) => {
    if (!user) {
      window.location.href = '../login.php';
    }
  });

  const searchForm = document.getElementById('searchForm');
  const searchInput = document.getElementById('searchInput');
  const resultsContainer = document.getElementById('resultsContainer');

  searchForm.addEventListener('submit', async () => {
    const query = searchInput.value.trim().toLowerCase();
    
    if (query === '') {
      resultsContainer.innerHTML = '<p style="color: var(--text-muted);">Enter an Account Number, Name, or Contact Number above to search member records.</p>';
      return;
    }

    resultsContainer.innerHTML = '<div class="p-3 text-muted text-center"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';

    try {
      const membersRef = collection(db, 'member');
      const snapshot = await getDocs(membersRef);
      const results = [];

      snapshot.forEach(doc => {
        const data = doc.data();
        const accNo = (data.account_number || '').toLowerCase();
        const fname = (data.firstname || '').toLowerCase();
        const lname = (data.lastname || '').toLowerCase();
        const contact = (data.contact_number || '').toLowerCase();

        if (accNo.includes(query) || fname.includes(query) || lname.includes(query) || contact.includes(query)) {
            results.push({ id: doc.id, ...data });
        }
      });

      if (results.length > 0) {
          results.sort((a, b) => {
              const aTime = a.created_at ? (a.created_at.seconds || a.created_at) : 0;
              const bTime = b.created_at ? (b.created_at.seconds || b.created_at) : 0;
              return bTime - aTime;
          });

          let html = `
            <h4 style="color: var(--primary-teal); margin-bottom: 1rem;">
              <i class="fa-solid fa-list-check"></i> Results for: "${escapeHtml(searchInput.value)}" (${results.length} found)
            </h4>
            <table class="dashboard-table">
              <thead>
                <tr>
                  <th>Account No.</th>
                  <th>Full Name</th>
                  <th>Civil Status</th>
                  <th>Spouse Name</th>
                  <th>Contact Number</th>
                  <th style="text-align: center;">Action</th>
                </tr>
              </thead>
              <tbody>
          `;

          results.forEach(m => {
            const fullName = `${m.firstname || ''} ${m.lastname || ''}`.trim();
            const accNo = m.account_number || '';
            const civilStatus = m.civil_status || 'Single';
            const spouseName = m.spouse_name || 'None';
            const contactNumber = m.contact_number || 'N/A';

            html += `
              <tr>
                <td><strong class="account-tag">${escapeHtml(accNo)}</strong></td>
                <td>${escapeHtml(fullName)}</td>
                <td>${escapeHtml(civilStatus)}</td>
                <td>${escapeHtml(spouseName)}</td>
                <td>${escapeHtml(contactNumber)}</td>
                <td style="text-align: center;">
                  <button class="btn btn-secondary btn-sm" onclick="viewMember('${escapeHtml(accNo)}')">
                    <i class="fa-solid fa-eye"></i> View
                  </button>
                </td>
              </tr>
            `;
          });
          html += '</tbody></table>';
          resultsContainer.innerHTML = html;
      } else {
        resultsContainer.innerHTML = `
          <h4 style="color: var(--primary-teal); margin-bottom: 1rem;">
              <i class="fa-solid fa-list-check"></i> Results for: "${escapeHtml(searchInput.value)}" (0 found)
          </h4>
          <div class="p-3 text-muted text-center">No member records match your search criteria.</div>
        `;
      }
    } catch (error) {
        console.error("Error searching members:", error);
        resultsContainer.innerHTML = '<div class="p-3 text-danger text-center">An error occurred while searching.</div>';
    }
  });

  function escapeHtml(unsafe) {
      if(!unsafe) return '';
      return unsafe
          .toString()
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&#039;");
  }
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>


