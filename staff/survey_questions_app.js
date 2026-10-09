import { db, auth } from '../assets/js/firebase-init.js';
import { collection, doc, getDoc, getDocs, updateDoc, deleteDoc, setDoc, addDoc, query, where, orderBy, onSnapshot } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

onAuthStateChanged(auth, (user) => {
    if (!user) window.location.href = '../login.php';
});

const surveyId = window.SURVEY_ID;
if (!surveyId) {
    window.location.href = 'surveys.php';
}

let currentSurvey = null;
let questionsData = [];
let choicesData = [];
let responsesData = [];
let membersData = {};

// Tabs functionality
window.switchQuestionnaireTab = function(tab) {
    document.querySelectorAll('.questionnaire-tab-btn').forEach(b => b.classList.remove('active-tab'));
    document.getElementById('tabQuestionsContent').style.display = 'none';
    document.getElementById('tabResponsesContent').style.display = 'none';
    document.getElementById('tabSettingsContent').style.display = 'none';
    
    if (tab === 'questions') {
        document.getElementById('btnTabQuestions').classList.add('active-tab');
        document.getElementById('tabQuestionsContent').style.display = 'block';
    } else if (tab === 'responses') {
        document.getElementById('btnTabResponses').classList.add('active-tab');
        document.getElementById('tabResponsesContent').style.display = 'block';
    } else if (tab === 'settings') {
        document.getElementById('btnTabSettings').classList.add('active-tab');
        document.getElementById('tabSettingsContent').style.display = 'block';
    }
};

window.switchResponsesSubtab = function(subtab) {
    document.querySelectorAll('.responses-subtab-btn').forEach(b => b.classList.remove('active-subtab'));
    document.getElementById('subtabSummaryContent').style.display = 'none';
    document.getElementById('subtabQuestionContent').style.display = 'none';
    document.getElementById('subtabIndividualContent').style.display = 'none';

    // Highlight active
    const btns = document.querySelectorAll('.responses-subtab-btn');
    if (subtab === 'summary') btns[0].classList.add('active-subtab');
    if (subtab === 'question') btns[1].classList.add('active-subtab');
    if (subtab === 'individual') btns[2].classList.add('active-subtab');

    if (subtab === 'summary') document.getElementById('subtabSummaryContent').style.display = 'block';
    if (subtab === 'question') document.getElementById('subtabQuestionContent').style.display = 'block';
    if (subtab === 'individual') document.getElementById('subtabIndividualContent').style.display = 'block';
};

// Fetch data
async function loadData() {
    try {
        // Members
        const memSnap = await getDocs(collection(db, 'member'));
        memSnap.forEach(d => {
            membersData[d.id] = d.data();
        });
        document.getElementById('statTotalMem').textContent = memSnap.size;

        // Survey
        onSnapshot(doc(db, 'surveys', surveyId), (docSnap) => {
            if (docSnap.exists()) {
                currentSurvey = { id: docSnap.id, ...docSnap.data() };
                renderHeader();
                renderSettings();
                updateStats(); // Ensure sync logic fires if survey data loads after questions/responses
            }
        });

        // Questions
        onSnapshot(query(collection(db, 'survey_questions'), where('survey_id', '==', surveyId)), (qSnap) => {
            questionsData = [];
            qSnap.forEach(d => questionsData.push({ id: d.id, ...d.data() }));
            questionsData.sort((a, b) => (a.question_order || 0) - (b.question_order || 0));
            updateStats();
            renderQuestions();
        }, (err) => {
            console.error("Error fetching survey_questions:", err);
        });

        // Choices
        onSnapshot(query(collection(db, 'survey_choices'), orderBy('choice_order')), (cSnap) => {
            choicesData = [];
            cSnap.forEach(d => choicesData.push({ id: d.id, ...d.data() }));
            renderQuestions(); // Re-render when choices change
            renderResponses();
        });

        // Responses
        onSnapshot(query(collection(db, 'responses'), where('survey_id', '==', surveyId)), (rSnap) => {
            responsesData = [];
            rSnap.forEach(d => responsesData.push({ id: d.id, ...d.data() }));
            updateStats();
            renderResponses();
        });
        
    } catch(err) {
        console.error(err);
    }
}

function renderHeader() {
    if (!currentSurvey) return;
    if (document.getElementById('hdrSurveyTitle')) document.getElementById('hdrSurveyTitle').textContent = currentSurvey.title;
    if (document.getElementById('surveyTitleDisp')) document.getElementById('surveyTitleDisp').textContent = currentSurvey.title;
    if (document.getElementById('hdrSurveyDesc')) document.getElementById('hdrSurveyDesc').textContent = currentSurvey.description || 'Define survey questions, response types, and choice options.';
    if (document.getElementById('surveyDescDisp')) document.getElementById('surveyDescDisp').textContent = currentSurvey.description || 'Define survey questions, response types, and choice options.';
    
    const sD = currentSurvey.start_date ? new Date(currentSurvey.start_date).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : '';
    const eD = currentSurvey.end_date ? new Date(currentSurvey.end_date).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : '';
    if (document.getElementById('hdrSurveyDates')) document.getElementById('hdrSurveyDates').innerHTML = `<i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i> ${sD} &mdash; ${eD}`;
    if (document.getElementById('surveyDatesDisp')) document.getElementById('surveyDatesDisp').innerHTML = `${sD} &mdash; ${eD}`;
    
    const isPublished = currentSurvey.status === 'active';
    const statusContainer = document.getElementById('publishStatusContainer');
    const formContainer = document.getElementById('publishFormContainer');
    if (statusContainer && formContainer) {
        if (isPublished) {
            statusContainer.style.display = 'block';
            formContainer.style.display = 'none';
        } else {
            statusContainer.style.display = 'none';
            formContainer.style.display = 'block';
        }
    }
    
    const addQContainer = document.getElementById('addQuestionBtnContainer');
    if (addQContainer) {
        if (!isPublished) {
            addQContainer.innerHTML = `<button type="button" class="btn btn-primary btn-sm" onclick="openAddQuestionModal()" style="padding: 7px 16px; font-size: 0.84rem; font-weight: 700; border-radius: 8px;"><i class="fa-solid fa-plus"></i> Add Question</button>`;
        } else {
            addQContainer.innerHTML = ``;
        }
    }
    
    document.getElementById('printReportLink').href = `survey_print_report.php?id=${surveyId}`;
}

function updateStats() {
    const qCount = questionsData.length;
    document.querySelectorAll('.qCountTab').forEach(el => el.textContent = qCount);
    if (document.getElementById('hdrQCount')) document.getElementById('hdrQCount').textContent = qCount + (qCount === 1 ? ' Question' : ' Questions');
    if (document.getElementById('qCountTop')) document.getElementById('qCountTop').textContent = qCount;
    if (document.getElementById('tabQCount')) document.getElementById('tabQCount').textContent = qCount;
    
    const uniqueResps = new Set();
    responsesData.forEach(r => uniqueResps.add(r.member_id));
    const rCount = uniqueResps.size;
    
    if (document.getElementById('tabRCount')) document.getElementById('tabRCount').textContent = rCount;
    if (document.getElementById('respCountTab')) document.getElementById('respCountTab').textContent = rCount;
    if (document.getElementById('respCountHeader')) document.getElementById('respCountHeader').textContent = rCount;
    if (document.getElementById('statTotalResp')) document.getElementById('statTotalResp').textContent = rCount;
    
    const totalMem = Object.keys(membersData).length;
    const rate = totalMem > 0 ? ((rCount / totalMem) * 100).toFixed(1) : 0;
    if (document.getElementById('statPartRate')) document.getElementById('statPartRate').textContent = rate + '%';
    if (document.getElementById('statTotalMem')) document.getElementById('statTotalMem').textContent = totalMem;
    if (document.getElementById('individualCount')) document.getElementById('individualCount').textContent = rCount;
    
    // Automatically repair and sync denormalized counts in the survey document if they are out of sync
    if (currentSurvey && (currentSurvey.questionsCount !== qCount || currentSurvey.respondentsCount !== rCount)) {
        currentSurvey.questionsCount = qCount;
        currentSurvey.respondentsCount = rCount;
        updateDoc(doc(db, 'surveys', surveyId), { 
            questionsCount: qCount,
            respondentsCount: rCount 
        }).catch(e => console.error("Failed to sync counts:", e));
    }
}

function escapeHtml(unsafe) {
    if(!unsafe) return '';
    return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function renderQuestions() {
    const container = document.getElementById('questionsListContainer');
    if (!container) return;
    
    if (questionsData.length === 0) {
        container.innerHTML = `<div class="table-card text-center py-5" style="border: 1px dashed var(--bg-card-border); padding: 3rem 1.5rem; margin-bottom: 2rem;">
        <i class="fa-solid fa-clipboard-question fs-1 mb-3" style="color: var(--primary-teal); opacity: 0.4;"></i>
        <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.4rem;">No Questions in This Survey Yet</h4>
        <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 420px; margin: 0 auto 1.25rem auto; line-height: 1.5;">Build your questionnaire by adding multiple choice, yes/no, rating scale, or open-ended description type questions.</p>
        </div>`;
        return;
    }
    
    const isPublished = currentSurvey && currentSurvey.status !== 'draft';
    let html = '<div style="display: flex; flex-direction: column; gap: 1.15rem; margin-bottom: 2.5rem;">';
    
    questionsData.forEach((q, idx) => {
        let typeBadge = '';
        if (q.type === 'multiple_choice') typeBadge = '<span class="status-pill" style="background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8;"><i class="fa-solid fa-circle-dot"></i> Multiple Choice</span>';
        else if (q.type === 'yes_no') typeBadge = '<span class="status-pill status-active"><i class="fa-solid fa-thumbs-up"></i> Yes/No</span>';
        else if (q.type === 'rating') typeBadge = '<span class="status-pill status-default"><i class="fa-solid fa-star"></i> Rating Scale (1-5)</span>';
        else if (q.type === 'short_answer') typeBadge = '<span class="status-pill" style="background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.35); color: #c084fc;"><i class="fa-solid fa-paragraph"></i> Description Type Answer</span>';
        
        let qChoices = choicesData.filter(c => c.question_id === q.id);
        
        let actionsHtml = '';
        if (!isPublished) {
            actionsHtml = `
            <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <button type="button" class="icon-btn btn-edit-dynamic" onclick="openEditQuestionModal('${q.id}')"><i class="fa-solid fa-pen"></i></button>
                <button type="button" class="icon-btn btn-delete-dynamic" onclick="deleteQuestion('${q.id}')"><i class="fa-solid fa-trash"></i></button>
            </div>`;
        }
        
        let bodyHtml = '';
        if (qChoices.length > 0) {
            let chHtml = qChoices.map(c => `<span style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--bg-card-border); color: var(--text-main); border-radius: 6px; padding: 5px 11px; font-size: 0.82rem; font-weight: 500;">${escapeHtml(c.choice_text)}</span>`).join('');
            bodyHtml = `<div class="structured-box" style="margin-top: 0.75rem; padding: 0.95rem 1.15rem;">
              <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.55rem;">Configured Choices (${qChoices.length}):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 0.55rem;">${chHtml}</div>
            </div>`;
        } else if (q.type === 'short_answer') {
            bodyHtml = `<div style="color: var(--text-dim); font-size: 0.82rem; font-style: italic; margin-top: 0.35rem;"><i class="fa-solid fa-pen-fancy me-1" style="color: var(--primary-teal);"></i> Open-ended text field response.</div>`;
        }
        
        html += `
        <div class="table-card hover-glow-card" style="margin-top: 0; padding: 1.4rem 1.6rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.85rem;">
            <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
              <span style="background: rgba(0, 194, 168, 0.15); border: 1px solid rgba(0, 194, 168, 0.35); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 3px 8px; font-size: 0.8rem;">Q${idx + 1}</span>
              ${typeBadge}
            </div>
            ${actionsHtml}
          </div>
          <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 700; margin-bottom: 0.85rem;">${escapeHtml(q.question)}</h4>
          ${bodyHtml}
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function renderResponses() {
    renderSummary();
    renderQuestionBreakdown();
    renderIndividual();
}

function renderSummary() {
    const container = document.getElementById('summaryTableContainer');
    if (!container) return;
    
    let totalResp = 0;
    const uniqueResps = new Set();
    responsesData.forEach(r => uniqueResps.add(r.member_id));
    totalResp = uniqueResps.size;
    
    let html = `<table class="dashboard-table">
        <thead>
            <tr>
            <th style="width: 60px; text-align: center;">#</th>
            <th style="min-width: 200px; text-align: center;">Question</th>
            <th style="min-width: 150px; text-align: center;">Type</th>
            <th style="width: 130px; text-align: center;">Total Responses</th>
            <th style="min-width: 320px; text-align: center;">Insights</th>
            </tr>
        </thead>
        <tbody>`;
        
    if (questionsData.length === 0) {
        html += `<tr><td colspan="5" class="text-center py-4 text-muted">No questions configured.</td></tr>`;
    } else {
        questionsData.forEach((q, idx) => {
            const qResps = responsesData.filter(r => r.question_id === q.id);
            const qTotal = qResps.length;
            
            let typeDisplay = q.type;
            let typePillStyle = '';
            if (q.type === 'multiple_choice') { typeDisplay = 'Multiple Choice'; typePillStyle = 'background: rgba(56, 189, 248, 0.15); color: #38bdf8;'; }
            if (q.type === 'yes_no') { typeDisplay = 'Yes/No'; typePillStyle = 'background: rgba(16, 185, 129, 0.15); color: #34d399;'; }
            if (q.type === 'rating') { typeDisplay = 'Rating Scale'; typePillStyle = 'background: rgba(245, 158, 11, 0.15); color: #fbbf24;'; }
            if (q.type === 'short_answer') { typeDisplay = 'Description Type Answer'; typePillStyle = 'background: rgba(168, 85, 247, 0.15); color: #c084fc;'; }
            
            let insightsHtml = '';
            if (q.type === 'short_answer') {
                const pct = totalResp > 0 ? ((qTotal / totalResp) * 100).toFixed(1) : 0;
                insightsHtml = `<div style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.5rem 0.75rem; background: rgba(168, 85, 247, 0.08); border-radius: 8px;">
                  <i class="fa-solid fa-pen-nib" style="color: #c084fc;"></i>
                  <div>
                    <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-main);">${qTotal} of ${totalResp} answered</div>
                    <div style="font-size: 0.76rem; color: var(--text-muted);">${pct}% completion</div>
                  </div>
                </div>`;
            } else {
                let choices = choicesData.filter(c => c.question_id === q.id);
                let choiceVotes = choices.map(c => {
                    const ccount = qResps.filter(r => r.choice_id === c.id).length;
                    return { ...c, count: ccount, pct: qTotal > 0 ? (ccount / qTotal * 100).toFixed(1) : 0 };
                });
                
                const maxVotes = Math.max(...choiceVotes.map(c => c.count), 0);
                let topChoices = choiceVotes.filter(c => c.count === maxVotes && maxVotes > 0);
                
                let listHtml = choiceVotes.map(c => {
                    const isTop = c.count === maxVotes && maxVotes > 0;
                    return `<div style="display: flex; justify-content: space-between; background: ${isTop?'rgba(0,194,168,0.1)':'rgba(255,255,255,0.03)'}; padding: 4px 8px; border-radius: 6px; margin-bottom: 3px; font-size: 0.78rem;">
                      <span style="color: var(--text-main); font-weight: ${isTop?'700':'500'};">${escapeHtml(c.choice_text)}</span>
                      <span style="color: ${isTop?'var(--primary-teal)':'var(--text-muted)'};">${c.count} (${c.pct}%)</span>
                    </div>`;
                }).join('');
                
                insightsHtml = `<div style="display: flex; flex-direction: column; gap: 0.4rem;">${listHtml}</div>`;
            }
            
            html += `<tr>
                <td style="font-weight: 700; color: var(--primary-teal); text-align: center;">Q${idx+1}</td>
                <td style="font-weight: 600; color: var(--text-main);">${escapeHtml(q.question)}</td>
                <td style="text-align: center;"><span class="status-pill" style="${typePillStyle}">${typeDisplay}</span></td>
                <td class="text-center" style="font-weight: 700; font-size: 0.95rem;">${qTotal}</td>
                <td>${insightsHtml}</td>
            </tr>`;
        });
    }
    html += `</tbody></table>`;
    container.innerHTML = html;
}

function renderQuestionBreakdown() {
    const container = document.getElementById('questionBreakdownContainer');
    if (!container) return;
    
    if (questionsData.length === 0) {
        container.innerHTML = `<div class="table-card text-center py-5"><p style="color: var(--text-muted);">No questions configured.</p></div>`;
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 1.25rem;">';
    questionsData.forEach((q, idx) => {
        let typeDisplay = q.type;
        if (q.type === 'multiple_choice') typeDisplay = 'Multiple Choice';
        if (q.type === 'yes_no') typeDisplay = 'Yes/No';
        if (q.type === 'rating') typeDisplay = 'Rating Scale';
        if (q.type === 'short_answer') typeDisplay = 'Description Type Answer';
        
        const qResps = responsesData.filter(r => r.question_id === q.id);
        const qTotal = qResps.length;
        
        let bodyHtml = '';
        if (q.type === 'short_answer') {
            if (qResps.length === 0) {
                bodyHtml = `<div class="structured-box text-center py-4" style="color: var(--text-muted);">No text responses submitted yet.</div>`;
            } else {
                let ansHtml = qResps.map(r => {
                    const mem = membersData[r.member_id] || {};
                    return `<div class="structured-box" style="padding: 0.85rem 1rem; margin-bottom: 0.65rem;">
                        <p style="color: var(--text-main); font-size: 0.92rem; margin-bottom: 0.5rem;">${escapeHtml(r.answer).replace(/\n/g, '<br>')}</p>
                        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted);">
                            <span><i class="fa-solid fa-user" style="color: var(--primary-teal);"></i> ${escapeHtml(mem.firstname||'')} ${escapeHtml(mem.lastname||'')} (${escapeHtml(mem.account_number||'')})</span>
                            <span>${new Date(r.submitted_at || r.created_at).toLocaleString()}</span>
                        </div>
                    </div>`;
                }).join('');
                bodyHtml = `<div>${ansHtml}</div>`;
            }
        } else {
            let choices = choicesData.filter(c => c.question_id === q.id);
            let chHtml = choices.map(c => {
                const ccount = qResps.filter(r => r.choice_id === c.id).length;
                const pct = qTotal > 0 ? (ccount / qTotal * 100).toFixed(1) : 0;
                return `<div>
                  <div style="display: flex; justify-content: space-between; font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem;">
                    <span style="color: var(--text-main);">${escapeHtml(c.choice_text)}</span>
                    <span style="color: var(--primary-teal); font-weight: 700;">${ccount} vote(s) (${pct}%)</span>
                  </div>
                  <div style="height: 10px; background: rgba(255, 255, 255, 0.06); border-radius: 6px; overflow: hidden;">
                    <div style="width: ${pct}%; height: 100%; background: linear-gradient(90deg, #00c2a8 0%, #8b5cf6 100%);"></div>
                  </div>
                </div>`;
            }).join('');
            bodyHtml = `<div style="display: flex; flex-direction: column; gap: 0.85rem;">${chHtml}</div>`;
        }
        
        html += `<div class="table-card hover-glow-card" style="margin-top: 0; padding: 1.4rem 1.6rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 0.85rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                    <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 4px 10px; font-size: 0.82rem;">Q${idx+1}</span>
                    <h4 style="color: var(--text-main); font-size: 1.08rem; font-weight: 700; margin: 0;">${escapeHtml(q.question)}</h4>
                </div>
                <span class="status-pill">${typeDisplay}</span>
            </div>
            ${bodyHtml}
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function renderIndividual() {
    const container = document.getElementById('individualContainer');
    if (!container) return;
    
    const uniqueResps = new Set();
    responsesData.forEach(r => uniqueResps.add(r.member_id));
    
    if (uniqueResps.size === 0) {
        container.innerHTML = `<div class="structured-box text-center py-5" style="color: var(--text-muted);">No individual response sheets submitted yet for this survey.</div>`;
        return;
    }
    
    let html = '<div style="display: flex; flex-direction: column; gap: 1rem;">';
    uniqueResps.forEach(memberId => {
        const mem = membersData[memberId] || { firstname: 'Unknown', lastname: 'Member', account_number: '' };
        const memResps = responsesData.filter(r => r.member_id === memberId);
        const lastResp = memResps.reduce((latest, r) => {
            const dt = r.submitted_at || r.created_at;
            if (!latest) return dt;
            return dt > latest ? dt : latest;
        }, null);
        
        let answersHtml = '';
        questionsData.forEach((q, idx) => {
            const ans = memResps.find(r => r.question_id === q.id);
            let ansText = '<i style="color: #666;">No answer</i>';
            if (ans) {
                if (q.type === 'short_answer') {
                    ansText = escapeHtml(ans.answer).replace(/\n/g, '<br>');
                } else {
                    const c = choicesData.find(ch => ch.id === ans.choice_id);
                    ansText = c ? escapeHtml(c.choice_text) : 'Unknown choice';
                }
            }
            answersHtml += `<div style="margin-bottom: 0.5rem;"><strong style="color: var(--primary-teal);">Q${idx+1}:</strong> <span style="color: var(--text-main);">${ansText}</span></div>`;
        });
        
        html += `<div class="table-card" style="padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">
                <strong style="color: var(--text-main); font-size: 1.1rem;">${escapeHtml(mem.firstname)} ${escapeHtml(mem.lastname)} (${escapeHtml(mem.account_number)})</strong>
                <span style="font-size: 0.85rem; color: var(--text-muted);">${new Date(lastResp).toLocaleString()}</span>
            </div>
            <div>${answersHtml}</div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function renderSettings() {
    if (!currentSurvey) return;
    document.getElementById('setSurveyTitle').value = currentSurvey.title;
    document.getElementById('setSurveyStatus').value = currentSurvey.status === 'closed' ? 'inactive' : currentSurvey.status;
    document.getElementById('setSurveyDesc').value = currentSurvey.description || '';
    if (currentSurvey.start_date) {
        const d = new Date(currentSurvey.start_date);
        document.getElementById('setStartDate').value = d.toISOString().split('T')[0];
        document.getElementById('setStartTime').value = d.toTimeString().slice(0,5);
    }
    if (currentSurvey.end_date) {
        const d = new Date(currentSurvey.end_date);
        document.getElementById('setEndDate').value = d.toISOString().split('T')[0];
        document.getElementById('setEndTime').value = d.toTimeString().slice(0,5);
    }
}

document.getElementById('editSettingsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        const title = document.getElementById('setSurveyTitle').value.trim();
        const status = document.getElementById('setSurveyStatus').value;
        const description = document.getElementById('setSurveyDesc').value.trim();
        const sd = document.getElementById('setStartDate').value;
        const st = document.getElementById('setStartTime').value;
        const ed = document.getElementById('setEndDate').value;
        const et = document.getElementById('setEndTime').value;
        
        await updateDoc(doc(db, 'surveys', surveyId), {
            title, status, description,
            start_date: `${sd}T${st}:00`,
            end_date: `${ed}T${et}:00`
        });
        alert('Survey settings updated.');
    } catch(err) {
        console.error(err);
        alert('Error updating settings: ' + err.message);
    }
});

window.publishSurvey = async function() {
    if (questionsData.length === 0) {
        if(typeof showToast === 'function') showToast('Cannot publish a survey with no questions.', 'warning', 'Warning');
        else alert('Cannot publish a survey with no questions.');
        return;
    }
    if (confirm('Publish and deploy this survey now?')) {
        try {
            await updateDoc(doc(db, 'surveys', surveyId), { status: 'active' });
            if(typeof showToast === 'function') showToast('Survey published successfully!', 'success', 'Success');
            else alert('Survey published successfully!');
        } catch(err) {
            console.error(err);
            if(typeof showToast === 'function') showToast('Error: ' + err.message, 'danger', 'Error');
            else alert('Error: ' + err.message);
        }
    }
};

window.deleteQuestion = async function(qId) {
    if(confirm('Are you sure you want to delete this question?')) {
        try {
            await deleteDoc(doc(db, 'survey_questions', qId));
            // Choices will ideally be deleted by a cloud function or batch, skipping for now
        } catch(err) {
            alert('Error deleting question: ' + err.message);
        }
    }
};

window.openEditQuestionModal = function(qId) {
    const q = questionsData.find(x => x.id === qId);
    if (!q) return;
    
    document.getElementById('editQuestionId').value = q.id;
    document.getElementById('editQuestionText').value = q.question;
    document.getElementById('editQuestionType').value = q.type;
    
    const rows = document.getElementById('editChoiceRows');
    if (rows) {
        rows.innerHTML = '';
        if (q.type === 'multiple_choice') {
            const choices = choicesData.filter(c => c.question_id === qId);
            choices.sort((a, b) => a.choice_order - b.choice_order);
            choices.forEach((c) => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.gap = '0.5rem';
                row.style.alignItems = 'center';
                row.innerHTML = `
                  <input type="text" name="choices[]" value="${escapeHtml(c.choice_text)}" required style="flex:1;">
                  <button type="button" class="btn-pill-remove-opt" onclick="this.parentElement.remove()" style="border:none;background:none;color:red;cursor:pointer;">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                `;
                rows.appendChild(row);
            });
            document.getElementById('editChoicesContainer').style.display = 'block';
        } else {
            document.getElementById('editChoicesContainer').style.display = 'none';
        }
    }
    if (typeof openModal === 'function') openModal('editQuestionModal');
};

document.addEventListener('DOMContentLoaded', () => {
    const addForm = document.getElementById('addQuestionForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addForm);
            const questionText = formData.get('question');
            const type = formData.get('question_type');
            
            let choices = [];
            if (type === 'multiple_choice') {
                const choiceInputs = addForm.querySelectorAll('input[name="choices[]"]');
                choiceInputs.forEach((inp) => {
                    if (inp.value.trim()) choices.push(inp.value.trim());
                });
            }
            
            try {
                let nextOrder = 1;
                if (questionsData.length > 0) {
                    const maxOrder = Math.max(...questionsData.map(q => q.question_order || 0));
                    nextOrder = maxOrder + 1;
                }
                
                const newQRef = doc(collection(db, 'survey_questions'));
                const newQ = {
                    survey_id: surveyId,
                    question: questionText,
                    type: type,
                    question_order: nextOrder,
                    created_at: new Date().toISOString()
                };
                
                await setDoc(newQRef, newQ);
                
                if (type === 'multiple_choice' && choices.length > 0) {
                    for (let i = 0; i < choices.length; i++) {
                        const choiceRef = doc(collection(db, 'survey_choices'));
                        await setDoc(choiceRef, {
                            question_id: newQRef.id,
                            choice_text: choices[i],
                            choice_order: i + 1,
                            created_at: new Date().toISOString()
                        });
                    }
                }
                
                if(typeof closeModal === 'function') closeModal('addQuestionModal');
                addForm.reset();
                if(typeof showToast === 'function') showToast('Question added successfully', 'success', 'Success');
            } catch (err) {
                console.error(err);
                if(typeof showToast === 'function') showToast('Error adding question.', 'danger', 'Error');
            }
        });
    }

    const editForm = document.getElementById('editQuestionForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);
            const questionId = formData.get('question_id');
            const questionText = formData.get('question');
            const type = formData.get('question_type');
            
            let choices = [];
            if (type === 'multiple_choice') {
                const choiceInputs = editForm.querySelectorAll('input[name="choices[]"]');
                choiceInputs.forEach((inp) => {
                    if (inp.value.trim()) choices.push(inp.value.trim());
                });
            }
            
            try {
                const qRef = doc(db, 'survey_questions', questionId);
                await updateDoc(qRef, {
                    question: questionText,
                    type: type,
                    updated_at: new Date().toISOString()
                });
                
                if (type === 'multiple_choice') {
                    const cSnap = choicesData.filter(c => c.question_id === questionId);
                    const batchDel = [];
                    cSnap.forEach(c => batchDel.push(deleteDoc(doc(db, 'survey_choices', c.id))));
                    await Promise.all(batchDel);
                    
                    if (choices.length > 0) {
                        for (let i = 0; i < choices.length; i++) {
                            const choiceRef = doc(collection(db, 'survey_choices'));
                            await setDoc(choiceRef, {
                                question_id: questionId,
                                choice_text: choices[i],
                                choice_order: i + 1,
                                created_at: new Date().toISOString()
                            });
                        }
                    }
                }
                
                if(typeof closeModal === 'function') closeModal('editQuestionModal');
                if(typeof showToast === 'function') showToast('Question updated successfully', 'success', 'Success');
            } catch(err) {
                console.error(err);
                if(typeof showToast === 'function') showToast('Error updating question.', 'danger', 'Error');
            }
        });
    }
});

loadData();



