<?php
/**
 * staff/survey_print_report.php
 * -------------------------------------------------------
 * Staff Portal — Printable Survey Summary & Analytics Report (Firebase)
 * Standalone, high-contrast, print-optimized document layout.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

$surveyId = $_GET['id'] ?? '';
if (empty($surveyId)) {
    die('Invalid survey requested.');
}

// Determine safe fallback URL for back navigation
$fallbackUrl = 'surveys.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    if (strpos($ref, 'cooperative-member-information-system') !== false) {
        $fallbackUrl = $ref;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Survey Report</title>
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    body {
      background: #0d081e;
      color: #1f2937;
      line-height: 1.5;
      padding: 24px 20px;
    }
    .report-paper {
      max-width: 920px;
      margin: 0 auto;
      background: #ffffff;
      padding: 45px 50px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.35);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .no-print {
      max-width: 920px;
      margin: 0 auto 20px auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      border: none;
      transition: all 0.2s ease;
    }
    .btn-primary {
      background: linear-gradient(135deg, #00c2a8 0%, #6b21a8 100%);
      color: #ffffff;
      box-shadow: 0 4px 15px rgba(0, 194, 168, 0.3);
    }
    .btn-secondary {
      background: rgba(255, 255, 255, 0.08);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .btn-icon {
      width: 40px;
      height: 40px;
      padding: 0;
      justify-content: center;
      border-radius: 8px;
    }
    .btn:hover {
      opacity: 0.92;
      transform: translateY(-1px);
    }
    
    /* Header */
    .report-header {
      border-bottom: 2.5px solid #008775;
      padding-bottom: 20px;
      margin-bottom: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .brand-title {
      font-size: 22px;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: 0.5px;
    }
    .brand-sub {
      font-size: 13px;
      color: #64748b;
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.8px;
      margin-top: 2px;
    }
    .report-badge {
      background: #e6f7f5;
      color: #008775;
      border: 1px solid #b2dfdb;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Meta Grid */
    .meta-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 18px 20px;
      margin-bottom: 25px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }
    .meta-item label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 3px;
      letter-spacing: 0.5px;
    }
    .meta-item span {
      font-size: 14px;
      font-weight: 700;
      color: #0f172a;
    }

    /* Question Cards */
    .question-block {
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 22px;
      page-break-inside: avoid;
      background: #ffffff;
    }
    .question-head {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 12px;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 10px;
    }
    .q-number {
      font-weight: 800;
      color: #008775;
      margin-right: 6px;
      font-size: 15px;
    }
    .q-title {
      font-size: 15px;
      font-weight: 700;
      color: #0f172a;
    }
    .q-type {
      font-size: 11px;
      font-weight: 700;
      color: #475569;
      background: #f1f5f9;
      padding: 3px 10px;
      border-radius: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Results Table */
    table.results-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
      margin-top: 8px;
    }
    table.results-table th,
    table.results-table td {
      padding: 9px 12px;
      text-align: left;
      border-bottom: 1px solid #e2e8f0;
    }
    table.results-table th {
      background: #f8fafc;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
    }
    .bar-container {
      width: 140px;
      height: 9px;
      background: #e2e8f0;
      border-radius: 5px;
      overflow: hidden;
      display: inline-block;
      vertical-align: middle;
      margin-right: 8px;
    }
    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #008775 0%, #6b21a8 100%);
      border-radius: 5px;
    }

    /* Sign-off */
    .report-footer {
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      page-break-inside: avoid;
    }
    .sign-box {
      width: 220px;
      text-align: center;
    }
    .sign-line {
      border-bottom: 1.5px solid #334155;
      margin-bottom: 8px;
      height: 45px;
    }
    .sign-label {
      font-size: 12px;
      color: #475569;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
      }
      .no-print {
        display: none !important;
      }
      .report-paper {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        max-width: 100% !important;
      }
    }
  </style>
</head>
<body>

  <div class="no-print">
    <a href="<?= e($fallbackUrl) ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="btn btn-secondary btn-icon" title="Go Back">
      <i class="fa-solid fa-arrow-left"></i>
    </a>
    <button onclick="window.print()" class="btn btn-primary" id="printBtn" disabled>
      <i class="fa-solid fa-print"></i> Loading Report...
    </button>
  </div>

  <div class="report-paper" id="reportPaper">
    <div style="text-align:center; padding: 50px;"><i class="fa-solid fa-spinner fa-spin fa-2x"></i><br><br>Generating Report...</div>
  </div>

  <script type="module">
    import { db, auth } from '../assets/js/firebase-init.js';
    import { doc, getDoc, collection, getDocs, query, where, orderBy } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";

    onAuthStateChanged(auth, (user) => {
      if (!user) window.location.href = '../login.php';
    });

    const surveyId = "<?= e($surveyId) ?>";
    const reportPaper = document.getElementById('reportPaper');
    const printBtn = document.getElementById('printBtn');

    async function loadReport() {
        try {
            const surveyDoc = await getDoc(doc(db, 'surveys', surveyId));
            if (!surveyDoc.exists()) {
                reportPaper.innerHTML = '<h2>Survey not found.</h2>';
                return;
            }
            const survey = surveyDoc.data();
            
            // Total members
            const membersSnap = await getDocs(collection(db, 'member'));
            const totalMembers = membersSnap.size;

            // Responses & unique respondents
            const responsesSnap = await getDocs(query(collection(db, 'responses'), where('survey_id', '==', surveyId)));
            const uniqueRespondents = new Set();
            const allResponses = [];
            responsesSnap.forEach(docSnap => {
                const data = docSnap.data();
                uniqueRespondents.add(data.member_id);
                allResponses.push(data);
            });
            const totalRespondents = uniqueRespondents.size;
            const participationRate = totalMembers > 0 ? ((totalRespondents / totalMembers) * 100).toFixed(1) : 0;

            // Fetch questions
            const questionsSnap = await getDocs(query(collection(db, 'survey_questions'), where('survey_id', '==', surveyId), orderBy('question_order')));
            const questions = [];
            questionsSnap.forEach(docSnap => {
                questions.push({ id: docSnap.id, ...docSnap.data() });
            });

            // Fetch choices
            const choicesSnap = await getDocs(query(collection(db, 'survey_choices'), orderBy('choice_order')));
            const choicesByQuestion = {};
            choicesSnap.forEach(docSnap => {
                const data = docSnap.data();
                if (!choicesByQuestion[data.question_id]) choicesByQuestion[data.question_id] = [];
                choicesByQuestion[data.question_id].push({ id: docSnap.id, ...data });
            });

            // Member data map for short answers
            const membersMap = {};
            membersSnap.forEach(docSnap => {
                membersMap[docSnap.id] = docSnap.data();
            });

            document.title = `Survey Report - ${survey.title}`;

            const formatDate = (dateStr) => {
                if(!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
            };

            const compileDate = new Date().toLocaleString('en-US', { month: 'long', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });

            let html = `
                <div class="report-header">
                  <div>
                    <div class="brand-title">SPACE MULTIPURPOSE COOPERATIVE</div>
                    <div class="brand-sub">Official Survey Analytics &amp; Member Feedback Report</div>
                  </div>
                  <div>
                    <span class="report-badge"><i class="fa-solid fa-file-circle-check me-1"></i> Official Report</span>
                  </div>
                </div>

                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #111827;">General Information</h3>
                <div class="meta-box">
                  <div class="meta-item"><label>Survey Title</label><span>${escapeHtml(survey.title)}</span></div>
                  <div class="meta-item"><label>Schedule Duration</label><span>${formatDate(survey.start_date)} &mdash; ${formatDate(survey.end_date)}</span></div>
                  <div class="meta-item"><label>Current Status</label><span style="text-transform: capitalize; color: #008775;">${escapeHtml(survey.status)}</span></div>
                  <div class="meta-item"><label>Author / Staff</label><span>${escapeHtml(survey.created_by_staff_id ? 'Staff Admin' : 'Staff Admin')}</span></div>
                  <div class="meta-item"><label>General Overview of All Members</label><span>${totalMembers} Registered Members</span></div>
                  <div class="meta-item"><label>Total Number of Responses</label><span>${totalRespondents} Responses (${participationRate}%)</span></div>
                  <div class="meta-item"><label>Date Compiled</label><span>${compileDate}</span></div>
                </div>
            `;

            if (survey.description) {
                html += `
                  <div style="margin-bottom: 25px; padding: 12px 18px; background: #f8fafc; border-left: 3.5px solid #008775; font-size: 13px; color: #334155; border-radius: 0 6px 6px 0;">
                    <strong style="color: #0f172a;">Survey Description:</strong> ${escapeHtml(survey.description).replace(/\n/g, '<br>')}
                  </div>
                `;
            }

            html += `<h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #111827;">Specific Survey Questions</h3>`;

            questions.forEach((q, index) => {
                const qId = q.id;
                const qType = normalizeQuestionType(q.type);
                const qChoices = choicesByQuestion[qId] || [];
                
                let printTypeDisplay = 'Multiple Choice';
                if (qType === 'yes_no') printTypeDisplay = 'Yes/No';
                else if (qType === 'rating') printTypeDisplay = 'Rating Scale';
                else if (qType === 'short_answer') printTypeDisplay = 'Description Type Answer';

                let qTotalVotes = 0;
                let ratingSum = 0;

                // Compute votes per choice for choices questions
                if (qType !== 'short_answer') {
                    qChoices.forEach(c => {
                        let count = allResponses.filter(r => r.question_id === qId && r.choice_id === c.id).length;
                        c.vote_count = count;
                        qTotalVotes += count;
                        if (qType === 'rating') {
                            const match = c.choice_text.match(/^(\d+)/);
                            const num = match ? parseInt(match[1]) : c.choice_order;
                            ratingSum += (num * count);
                        }
                    });
                }

                const avgScore = (qType === 'rating' && qTotalVotes > 0) ? (ratingSum / qTotalVotes).toFixed(2) : null;

                html += `
                  <div class="question-block">
                    <div class="question-head">
                      <div>
                        <span class="q-number">Q${index + 1}.</span>
                        <span class="q-title">${escapeHtml(q.question)}</span>
                      </div>
                      <div>
                        <span class="q-type">${escapeHtml(printTypeDisplay)}</span>
                        ${avgScore !== null ? `<span class="q-type" style="background: #fef3c7; color: #92400e; margin-left: 5px;">Avg: ${avgScore} / 5.0</span>` : ''}
                      </div>
                    </div>
                `;

                if (qType !== 'short_answer') {
                    html += `
                      <table class="results-table">
                        <thead>
                          <tr>
                            <th style="width: 45%;">Selected Option / Choice</th>
                            <th style="width: 20%; text-align: right;">Vote Count</th>
                            <th style="width: 35%;">Percentage</th>
                          </tr>
                        </thead>
                        <tbody>
                    `;
                    qChoices.forEach(choice => {
                        const votes = choice.vote_count || 0;
                        const pct = qTotalVotes > 0 ? ((votes / qTotalVotes) * 100).toFixed(1) : '0.0';
                        html += `
                          <tr>
                            <td style="font-weight: 600;">${escapeHtml(choice.choice_text)}</td>
                            <td style="text-align: right; font-weight: 700; color: #00897b;">${votes}</td>
                            <td>
                              <div class="bar-container">
                                <div class="bar-fill" style="width: ${pct}%;"></div>
                              </div>
                              <strong>${pct}%</strong>
                            </td>
                          </tr>
                        `;
                    });
                    html += `
                          <tr style="background: #f9fafb; font-weight: 700;">
                            <td>Total Question Responses</td>
                            <td style="text-align: right;">${qTotalVotes}</td>
                            <td>100.0%</td>
                          </tr>
                        </tbody>
                      </table>
                    `;
                } else {
                    html += `
                      <table class="results-table">
                        <thead>
                          <tr>
                            <th style="width: 25%;">Respondent Member</th>
                            <th style="width: 55%;">Submitted Feedback / Response</th>
                            <th style="width: 20%;">Timestamp</th>
                          </tr>
                        </thead>
                        <tbody>
                    `;
                    const shortAnsResp = allResponses.filter(r => r.question_id === qId);
                    if (shortAnsResp.length === 0) {
                        html += `<tr><td colspan="3" style="text-align: center; color: #9ca3af; padding: 15px;">No written responses submitted for this question.</td></tr>`;
                    } else {
                        shortAnsResp.forEach(sa => {
                            const member = membersMap[sa.member_id] || {};
                            const fname = member.firstname || 'Unknown';
                            const lname = member.lastname || 'Member';
                            const accNo = member.account_number || '';
                            const ts = formatDate(sa.submitted_at || sa.created_at);
                            html += `
                              <tr>
                                <td style="font-weight: 600;">
                                  ${escapeHtml(fname + ' ' + lname)}<br>
                                  <small style="color: #6b7280; font-weight: normal;">${escapeHtml(accNo)}</small>
                                </td>
                                <td>${escapeHtml(sa.answer).replace(/\n/g, '<br>')}</td>
                                <td style="font-size: 12px; color: #6b7280;">${ts}</td>
                              </tr>
                            `;
                        });
                    }
                    html += `</tbody></table>`;
                }

                html += `</div>`; // End question block
            });

            html += `
                <div class="report-footer">
                  <div class="sign-box">
                    <div class="sign-line"></div>
                    <div class="sign-label">Prepared By (Staff in Charge)</div>
                  </div>
                  <div class="sign-box">
                    <div class="sign-line"></div>
                    <div class="sign-label">Certified Correct (Admin / BOD)</div>
                  </div>
                </div>
            `;

            reportPaper.innerHTML = html;
            printBtn.innerHTML = '<i class="fa-solid fa-print"></i> Print Report Now';
            printBtn.disabled = false;

        } catch (error) {
            console.error("Error loading report:", error);
            reportPaper.innerHTML = '<h3 style="color:red;">Error loading report data.</h3>';
        }
    }

    function escapeHtml(unsafe) {
        if(!unsafe) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function normalizeQuestionType(type) {
        if (!type) return 'short_answer';
        const t = type.toString().toLowerCase().trim();
        if (t === 'multiple_choice' || t === 'mcq' || t === 'choice' || t === 'single_choice') return 'multiple_choice';
        if (t === 'yes_no' || t === 'yesno' || t === 'boolean' || t === 'yes_or_no') return 'yes_no';
        if (t === 'rating' || t === 'scale' || t === 'rating_scale' || t === 'scale_1_5' || t === 'scale_of_1_to_5') return 'rating';
        if (t === 'short_answer' || t === 'description' || t === 'open_ended' || t === 'text' || t === 'paragraph' || t === 'long_answer') return 'short_answer';
        return t;
    }

    loadReport();
  </script>
</body>
</html>



