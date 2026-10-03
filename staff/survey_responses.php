<?php
/**
 * staff/survey_responses.php
 * -------------------------------------------------------
 * Staff Portal — Survey Analytics & Respondent Monitoring (Firebase)
 * Monitor participation, view aggregated choice statistics,
 * inspect short-answer feedback, and review member submissions.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
$surveyId = $_GET['id'] ?? '';
if (empty($surveyId)) {
    header('Location: surveys.php');
    exit;
}

$pageTitle = 'Survey Results & Respondents';
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div id="surveyResponsesView" class="page-view active">
  <div class="section-header-flex view-header-wrapper mb-3">
    <div class="section-title">
      <h2>Survey Responses &amp; Respondent Log</h2>
      <p class="section-subtitle">Real-time participation tracking and individual submission review</p>
    </div>
    <div class="d-inline-flex gap-2 align-items-center">
      <a href="surveys.php" class="btn btn-secondary" style="width: 44px; height: 44px; padding: 0; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; text-decoration: none;" title="Back to Survey Management">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <a href="survey_questions.php?id=<?= htmlspecialchars($surveyId) ?>" class="btn btn-secondary btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.45rem; height: 44px; padding: 0 16px; border-radius: 12px;">
        <i class="fa-solid fa-list-check"></i> Manage Questions
      </a>
    </div>
  </div>

  <div class="table-card mb-4" style="margin-top: 0; padding: 1.25rem 1.5rem;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.35rem;">
          <span class="status-pill status-active" id="surveyStatusPill">Loading...</span>
          <span style="color: var(--text-muted); font-size: 0.82rem;" id="surveyDatesDisplay">
            <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal); margin-right: 4px;"></i> ...
          </span>
        </div>
        <h3 style="color: var(--text-main); font-size: 1.3rem; font-weight: 700; margin-bottom: 0.3rem;" id="surveyTitleDisplay">Loading...</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0; max-width: 750px; line-height: 1.5;" id="surveyDescDisplay">...</p>
      </div>
    </div>
  </div>

  <div class="metrics-grid" style="margin-bottom: 1.5rem;">
    <div class="metric-card hover-glow-card">
      <div class="metric-icon purple"><i class="fa-solid fa-users"></i></div>
      <div class="metric-data">
        <span class="metric-label">Total Respondents</span>
        <h3 class="metric-value" id="statTotalResp">0</h3>
        <span class="metric-subtext"><i class="fa-solid fa-user-check"></i> Completed surveys</span>
      </div>
    </div>
    <div class="metric-card hover-glow-card">
      <div class="metric-icon cyan"><i class="fa-solid fa-percent"></i></div>
      <div class="metric-data">
        <span class="metric-label">Participation Rate</span>
        <h3 class="metric-value" id="statPartRate">0%</h3>
        <span class="metric-subtext positive"><i class="fa-solid fa-chart-line"></i> Of <span id="statTotalMembers">0</span> active members</span>
      </div>
    </div>
    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald"><i class="fa-solid fa-list-check"></i></div>
      <div class="metric-data">
        <span class="metric-label">Total Questions</span>
        <h3 class="metric-value" id="statTotalQuestions">0</h3>
        <span class="metric-subtext"><i class="fa-solid fa-layer-group"></i> Active survey items</span>
      </div>
    </div>
  </div>

  <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.65rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.85rem; flex-wrap: wrap;">
    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
      <button type="button" class="btn btn-sm responses-tab-btn" id="btnTabRespondents" onclick="switchSurveyTab('respondents')" style="background: rgba(0, 194, 168, 0.2); color: #00e6c7; border: 1px solid rgba(0, 194, 168, 0.4); border-radius: 8px; padding: 8px 18px; font-weight: 700;">
        <i class="fa-solid fa-users-viewfinder me-1"></i> Member Respondents Log (<span id="btnRespondentsCount">0</span>)
      </button>
      <button type="button" class="btn btn-sm responses-tab-btn" id="btnTabAnalytics" onclick="switchSurveyTab('analytics')" style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid var(--bg-card-border); border-radius: 8px; padding: 8px 18px; font-weight: 600;">
        <i class="fa-solid fa-chart-pie me-1"></i> Fast Question Breakdown
      </button>
    </div>
    <div>
      <a href="survey_questions.php?id=<?= htmlspecialchars($surveyId) ?>#responses" class="btn btn-primary btn-sm" style="text-decoration: none; border-radius: 8px; padding: 7px 16px; font-weight: 600;">
        <i class="fa-solid fa-chart-column me-1"></i> Analytics Dashboard
      </a>
    </div>
  </div>

  <div id="tabRespondentsContent">
    <div class="table-card" style="margin-top: 0;">
      <table class="dashboard-table" id="respondentsTable">
        <thead>
          <tr>
            <th>Member Name</th>
            <th>Account No.</th>
            <th>Contact Number</th>
            <th style="text-align: center;">Questions Answered</th>
            <th>Submitted At</th>
            <th style="text-align: center;">Action</th>
          </tr>
        </thead>
        <tbody id="respondentsTableBody">
          <tr><td colspan="6" class="text-center py-5"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div id="tabAnalyticsContent" style="display: none;">
    <div id="analyticsContainer">
      <div class="table-card text-center py-5"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
    </div>
  </div>

</div>

<!-- Modal -->
<div id="memberResponseModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 700px;">
    <div class="modal-header">
      <div class="d-flex justify-content-between align-items-center w-100">
        <h3><i class="fa-solid fa-file-lines" style="color: var(--primary-teal);"></i> Member Survey Response Sheet</h3>
        <button type="button" class="close-popover-btn" onclick="closeModal('memberResponseModal')">&times;</button>
      </div>
    </div>
    <div class="modal-body">
      <div class="structured-box mb-3" style="padding: 1rem 1.25rem;">
        <div class="d-flex justify-content-between mb-1">
          <span style="color: var(--text-dim); font-size: 0.85rem;">Respondent:</span>
          <span style="color: #ffffff; font-weight: 700; font-size: 0.9rem;" id="modalRespondentName"></span>
        </div>
        <div class="d-flex justify-content-between">
          <span style="color: var(--text-dim); font-size: 0.85rem;">Account Number:</span>
          <span class="account-tag" id="modalRespondentAccount"></span>
        </div>
      </div>
      <div id="modalAnswersContainer" style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 420px; overflow-y: auto;"></div>
    </div>
    <div class="modal-actions" style="margin-top: 1.5rem;">
      <button type="button" class="btn btn-primary btn-sm" onclick="closeModal('memberResponseModal')"><i class="fa-solid fa-check me-1"></i> Close</button>
    </div>
  </div>
</div>

<script>
window.SURVEY_ID = "<?= htmlspecialchars($surveyId) ?>";

function openModal(id) { const el = document.getElementById(id); if (el) { el.classList.remove('hidden'); el.style.display = 'flex'; } }
function closeModal(id) { const el = document.getElementById(id); if (el) { el.classList.add('hidden'); el.style.display = 'none'; } }

function switchSurveyTab(tab) {
  const tabAnalytics = document.getElementById('tabAnalyticsContent');
  const tabRespondents = document.getElementById('tabRespondentsContent');
  const btnAnalytics = document.getElementById('btnTabAnalytics');
  const btnRespondents = document.getElementById('btnTabRespondents');

  if (tab === 'analytics') {
    tabAnalytics.style.display = 'block';
    tabRespondents.style.display = 'none';
    btnAnalytics.style.background = 'rgba(0, 194, 168, 0.2)';
    btnAnalytics.style.color = '#00e6c7';
    btnAnalytics.style.border = '1px solid rgba(0, 194, 168, 0.4)';
    btnAnalytics.style.fontWeight = '700';
    btnRespondents.style.background = 'rgba(255, 255, 255, 0.05)';
    btnRespondents.style.color = 'var(--text-muted)';
    btnRespondents.style.border = '1px solid var(--bg-card-border)';
    btnRespondents.style.fontWeight = '600';
  } else {
    tabAnalytics.style.display = 'none';
    tabRespondents.style.display = 'block';
    btnRespondents.style.background = 'rgba(0, 194, 168, 0.2)';
    btnRespondents.style.color = '#00e6c7';
    btnRespondents.style.border = '1px solid rgba(0, 194, 168, 0.4)';
    btnRespondents.style.fontWeight = '700';
    btnAnalytics.style.background = 'rgba(255, 255, 255, 0.05)';
    btnAnalytics.style.color = 'var(--text-muted)';
    btnAnalytics.style.border = '1px solid var(--bg-card-border)';
    btnAnalytics.style.fontWeight = '600';
  }
}
</script>

<script type="module">
import { db, auth } from '../assets/js/firebase-init.js';
import { collection, doc, getDoc, getDocs, query, where, orderBy, onSnapshot } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, (user) => { if (!user) window.location.href = '../login.php'; });

let membersData = {};
let surveyData = null;
let questionsData = [];
let choicesData = [];
let responsesData = [];

function escapeHtml(unsafe) {
    if(!unsafe) return '';
    return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

async function loadData() {
    const memSnap = await getDocs(collection(db, 'member'));
    memSnap.forEach(d => { membersData[d.id] = d.data(); });
    
    onSnapshot(doc(db, 'surveys', window.SURVEY_ID), (docSnap) => {
        if(docSnap.exists()){
            surveyData = { id: docSnap.id, ...docSnap.data() };
            renderHeader();
        }
    });
    
    onSnapshot(query(collection(db, 'survey_questions'), where('survey_id', '==', window.SURVEY_ID), orderBy('question_order')), (qSnap) => {
        questionsData = [];
        qSnap.forEach(d => questionsData.push({ id: d.id, ...d.data() }));
        renderAll();
    });
    
    onSnapshot(query(collection(db, 'survey_choices'), orderBy('choice_order')), (cSnap) => {
        choicesData = [];
        cSnap.forEach(d => choicesData.push({ id: d.id, ...d.data() }));
        renderAll();
    });
    
    onSnapshot(query(collection(db, 'responses'), where('survey_id', '==', window.SURVEY_ID)), (rSnap) => {
        responsesData = [];
        rSnap.forEach(d => responsesData.push({ id: d.id, ...d.data() }));
        renderAll();
    });
}

function renderHeader() {
    if(!surveyData) return;
    document.getElementById('surveyStatusPill').textContent = surveyData.status.toUpperCase();
    if(surveyData.status === 'draft') document.getElementById('surveyStatusPill').className = 'status-pill status-default';
    else if(surveyData.status === 'active') document.getElementById('surveyStatusPill').className = 'status-pill status-active';
    else document.getElementById('surveyStatusPill').className = 'status-pill status-locked';
    
    const formatD = (d) => d ? new Date(d).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : '';
    document.getElementById('surveyDatesDisplay').innerHTML = `<i class="fa-solid fa-calendar-days" style="color: var(--primary-teal); margin-right: 4px;"></i> ${formatD(surveyData.start_date)} &mdash; ${formatD(surveyData.end_date)}`;
    
    document.getElementById('surveyTitleDisplay').textContent = surveyData.title;
    document.getElementById('surveyDescDisplay').textContent = surveyData.description || 'No description provided.';
}

function renderAll() {
    const totalMem = Object.keys(membersData).length;
    document.getElementById('statTotalMembers').textContent = totalMem;
    
    const uniqueResps = new Set();
    responsesData.forEach(r => uniqueResps.add(r.member_id));
    const totalResp = uniqueResps.size;
    
    document.getElementById('statTotalResp').textContent = totalResp;
    document.getElementById('btnRespondentsCount').textContent = totalResp;
    
    const rate = totalMem > 0 ? ((totalResp/totalMem)*100).toFixed(1) : 0;
    document.getElementById('statPartRate').textContent = rate + '%';
    
    document.getElementById('statTotalQuestions').textContent = questionsData.length;
    
    renderRespondents();
    renderAnalytics();
}

function renderRespondents() {
    const tbody = document.getElementById('respondentsTableBody');
    const uniqueResps = new Set();
    responsesData.forEach(r => uniqueResps.add(r.member_id));
    
    if(uniqueResps.size === 0){
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5" style="color: var(--text-muted);"><i class="fa-solid fa-users-slash fs-2 mb-2 d-block opacity-50"></i>No members have submitted responses for this survey yet.</td></tr>`;
        return;
    }
    
    let html = '';
    const sortedResps = Array.from(uniqueResps).map(uid => {
        const rList = responsesData.filter(r => r.member_id === uid);
        const lastT = rList.reduce((acc, curr) => {
            const dt = curr.submitted_at || curr.created_at;
            return dt > acc ? dt : acc;
        }, '');
        return { uid, qCount: rList.length, lastT };
    }).sort((a,b) => new Date(b.lastT) - new Date(a.lastT));
    
    sortedResps.forEach(res => {
        const mem = membersData[res.uid] || {};
        const fname = escapeHtml(mem.firstname || 'Unknown');
        const lname = escapeHtml(mem.lastname || 'Member');
        const acc = escapeHtml(mem.account_number || '');
        const addr = escapeHtml(mem.address || '');
        const phone = escapeHtml(mem.contact_number || 'N/A');
        const photo = mem.passport_photo ? `../uploads/${mem.passport_photo}` : '../assets/images/default-avatar.png';
        const dStr = new Date(res.lastT).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
        
        html += `<tr>
            <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="${photo}" alt="Photo" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--primary-teal);">
                    <div><div style="font-weight: 700; color: var(--text-main); font-size: 0.92rem;">${fname} ${lname}</div><div style="color: var(--text-muted); font-size: 0.78rem;">${addr}</div></div>
                </div>
            </td>
            <td><strong class="account-tag">${acc}</strong></td>
            <td style="color: var(--text-dim); font-size: 0.85rem;">${phone}</td>
            <td style="text-align: center; color: var(--primary-teal); font-weight: 700;"><i class="fa-solid fa-circle-check me-1"></i> ${res.qCount} / ${questionsData.length}</td>
            <td style="font-size: 0.82rem; color: var(--text-muted);">${dStr}</td>
            <td style="text-align: center;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="viewResponses('${res.uid}', '${fname} ${lname}', '${acc}')" style="border-radius: 8px; padding: 6px 14px; font-size: 0.82rem;"><i class="fa-solid fa-eye me-1"></i> View</button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function renderAnalytics() {
    const container = document.getElementById('analyticsContainer');
    if(questionsData.length === 0){
        container.innerHTML = `<div class="table-card text-center py-5"><p style="color: var(--text-muted); margin: 0;">No questions found for this survey.</p></div>`;
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">';
    
    questionsData.forEach((q, idx) => {
        let typeDisplay = q.question_type;
        if(q.question_type === 'multiple_choice') typeDisplay = 'Multiple Choice';
        if(q.question_type === 'yes_no') typeDisplay = 'Yes/No';
        if(q.question_type === 'rating') typeDisplay = 'Rating Scale';
        if(q.question_type === 'short_answer') typeDisplay = 'Description Type Answer';
        
        let qResps = responsesData.filter(r => r.question_id === q.id);
        let qTotalVotes = qResps.length;
        
        let bodyHtml = '';
        if(q.question_type !== 'short_answer'){
            let choices = choicesData.filter(c => c.question_id === q.id);
            let chHtml = choices.map(c => {
                let votes = qResps.filter(r => r.choice_id === c.id).length;
                let pct = qTotalVotes > 0 ? (votes/qTotalVotes*100).toFixed(1) : 0;
                return `<div>
                  <div style="display: flex; justify-content: space-between; font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem;">
                    <span style="color: var(--text-main);">${escapeHtml(c.choice_text)}</span>
                    <span style="color: var(--primary-teal); font-weight: 700;">${votes} vote(s) (${pct}%)</span>
                  </div>
                  <div style="height: 10px; background: rgba(255, 255, 255, 0.06); border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.08); overflow: hidden;">
                    <div style="width: ${pct}%; height: 100%; background: linear-gradient(90deg, #00c2a8 0%, #8b5cf6 100%); border-radius: 6px;"></div>
                  </div>
                </div>`;
            }).join('');
            
            bodyHtml = `<div style="display: flex; flex-direction: column; gap: 0.85rem; padding-top: 0.35rem;">${chHtml}
              <div style="font-size: 0.82rem; color: var(--text-muted); padding-top: 0.75rem; border-top: 1px solid rgba(255, 255, 255, 0.05); margin-top: 0.5rem;">
                <i class="fa-solid fa-calculator" style="color: var(--primary-teal); margin-right: 4px;"></i> Total Answers: <strong style="color: var(--text-main);">${qTotalVotes}</strong>
              </div>
            </div>`;
        } else {
            if(qResps.length === 0){
                bodyHtml = `<div class="structured-box text-center py-4" style="color: var(--text-muted);">No text responses submitted yet.</div>`;
            } else {
                let ansHtml = qResps.map(sa => {
                    const mem = membersData[sa.member_id] || {};
                    const dStr = new Date(sa.submitted_at || sa.created_at).toLocaleString();
                    return `<div class="structured-box" style="padding: 0.85rem 1rem;">
                        <p style="color: var(--text-main); font-size: 0.92rem; line-height: 1.5; margin-bottom: 0.5rem;">${escapeHtml(sa.answer).replace(/\n/g, '<br>')}</p>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted);">
                          <span><i class="fa-solid fa-user" style="color: var(--primary-teal); margin-right: 4px;"></i> ${escapeHtml(mem.firstname)} ${escapeHtml(mem.lastname)} (<strong style="color: var(--text-main);">${escapeHtml(mem.account_number)}</strong>)</span>
                          <span><i class="fa-solid fa-clock" style="color: var(--primary-teal); margin-right: 4px;"></i> ${dStr}</span>
                        </div>
                    </div>`;
                }).join('');
                bodyHtml = `<div style="padding-top: 0.35rem;">
                    <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.65rem; letter-spacing: 0.5px;">Submitted Member Feedback (${qResps.length}):</div>
                    <div style="display: flex; flex-direction: column; gap: 0.65rem; max-height: 280px; overflow-y: auto;">${ansHtml}</div>
                </div>`;
            }
        }
        
        html += `<div class="table-card hover-glow-card" style="margin-top: 0; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <span style="background: rgba(0, 194, 168, 0.15); border: 1px solid rgba(0, 194, 168, 0.35); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 4px 10px; font-size: 0.82rem;">Q${idx+1}</span>
                    <h4 style="color: var(--text-main); font-size: 1.1rem; font-weight: 700; margin: 0;">${escapeHtml(q.question)}</h4>
                </div>
                <span class="status-pill">${typeDisplay}</span>
            </div>
            ${bodyHtml}
        </div>`;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

window.viewResponses = function(memberId, memberName, accountNumber) {
    document.getElementById('modalRespondentName').textContent = memberName;
    document.getElementById('modalRespondentAccount').textContent = accountNumber;
    
    const container = document.getElementById('modalAnswersContainer');
    const memResps = responsesData.filter(r => r.member_id === memberId);
    
    if (memResps.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">No recorded responses found for this member.</p>';
    } else {
        let html = '';
        questionsData.forEach((q, idx) => {
            const ans = memResps.find(r => r.question_id === q.id);
            if (ans) {
                let ansText = '';
                if(q.question_type === 'short_answer') ansText = escapeHtml(ans.answer || 'No answer');
                else {
                    let ch = choicesData.find(c => c.id === ans.choice_id);
                    ansText = ch ? escapeHtml(ch.choice_text) : 'Unknown choice';
                }
                
                html += `<div class="structured-box" style="padding: 0.85rem 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                      <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 2px 8px; font-size: 0.75rem;">Q${idx+1}</span>
                      <div style="color: #ffffff; font-weight: 700; font-size: 0.88rem;">${escapeHtml(q.question)}</div>
                    </div>
                    <div style="padding: 0.6rem 0.85rem; border-radius: 6px; background: rgba(0, 194, 168, 0.08); border-left: 3px solid var(--primary-teal); color: #ffffff; font-weight: 500; font-size: 0.9rem;">
                      <i class="fa-solid fa-arrow-right" style="color: var(--primary-teal); font-size: 0.75rem; margin-right: 6px;"></i> ${ansText}
                    </div>
                </div>`;
            }
        });
        container.innerHTML = html;
    }
    openModal('memberResponseModal');
}

loadData();
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>


