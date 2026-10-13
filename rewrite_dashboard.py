import re

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/dashboard.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(r'// Fetch summary metrics from database.*?\$pageTitle = \'Staff Dashboard\';', '$pageTitle = \'Staff Dashboard\';', content, flags=re.DOTALL)

content = content.replace('<h3 class="metric-value"><?= number_format($totalMembers) ?></h3>', '<h3 class="metric-value" id="dashTotalMembers">0</h3>')
content = content.replace('<h3 class="metric-value"><?= number_format($recentEdits) ?></h3>', '<h3 class="metric-value" id="dashRecentEdits">0</h3>')
content = content.replace('<h3 class="metric-value"><?= number_format($newToday) ?></h3>', '<h3 class="metric-value" id="dashNewToday">0</h3>')
content = content.replace('<h3 class="metric-value"><?= number_format($totalSurveys) ?></h3>', '<h3 class="metric-value" id="dashTotalSurveys">0</h3>')
content = content.replace('<h3 class="metric-value"><?= number_format($activeSurveys) ?></h3>', '<h3 class="metric-value" id="dashActiveSurveys">0</h3>')
content = content.replace('<h3 class="metric-value"><?= number_format($totalResponses) ?></h3>', '<h3 class="metric-value" id="dashTotalResponses">0</h3>')
content = content.replace('<h3 class="metric-value"><?= number_format($draftSurveys) ?></h3>', '<h3 class="metric-value" id="dashDraftSurveys">0</h3>')

table_body_start = content.find('<tbody>')
table_body_end = content.find('</tbody>') + len('</tbody>')
new_tbody = '<tbody id="dashRecentRegistrationsTbody">\n        <tr>\n          <td colspan="7" class="text-center text-muted py-4">Loading registered members...</td>\n        </tr>\n      </tbody>'
content = content[:table_body_start] + new_tbody + content[table_body_end:]

script = """
<script type="module">
import { db, auth } from '../assets/js/firebase-init.js';
import { collection, getDocs } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, (user) => {
    if (!user) {
        window.location.href = '../login.php';
    } else {
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

        const updatesSnap = await getDocs(collection(db, 'update_history'));
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
"""

content = content.replace('<?php require_once __DIR__ . \'/../pages/footer_staff.php\'; ?>', script + '\n<?php require_once __DIR__ . \'/../pages/footer_staff.php\'; ?>')

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/dashboard.php', 'w', encoding='utf-8') as f:
    f.write(content)
