import re

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/members.php', 'r', encoding='utf-8') as f:
    content = f.read()

script_start = content.find('<script type="module">')
script_end = content.find('</script>', script_start) + len('</script>')

new_script = """
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
            const photoSrc = hasPhoto ? `../uploads/${m.passport_photo}` : '';

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
"""

content = content[:script_start] + new_script + content[script_end:]

with open('C:/xampp/htdocs/cooperative-member-information-system/staff/members.php', 'w', encoding='utf-8') as f:
    f.write(content)
