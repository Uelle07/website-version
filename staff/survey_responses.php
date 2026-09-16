<?php
/**
 * staff/survey_responses.php
 * -------------------------------------------------------
 * Staff Portal — Survey Analytics & Respondent Monitoring (SET B)
 * Monitor participation, view aggregated choice statistics,
 * inspect short-answer feedback, and review member submissions.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$surveyId = (int)($_GET['id'] ?? 0);

if ($surveyId <= 0) {
    set_flash('error', 'Invalid survey requested.');
    redirect('staff/surveys.php');
}

// 1. Fetch Survey Details
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE survey_id = ? LIMIT 1");
$stmt->execute([$surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    set_flash('error', 'Survey not found.');
    redirect('staff/surveys.php');
}

// 2. Fetch Metrics
$totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM member")->fetchColumn();

$respCountStmt = $pdo->prepare("SELECT COUNT(DISTINCT member_id) FROM responses WHERE survey_id = ?");
$respCountStmt->execute([$surveyId]);
$totalRespondents = (int)$respCountStmt->fetchColumn();

$participationRate = $totalMembers > 0 ? round(($totalRespondents / $totalMembers) * 100, 1) : 0;

// 3. Fetch Questions
$qStmt = $pdo->prepare(
    "SELECT * FROM survey_questions 
     WHERE survey_id = ? 
     ORDER BY question_order ASC, question_id ASC"
);
$qStmt->execute([$surveyId]);
$questions = $qStmt->fetchAll();

// 4. Fetch Choices with live response counts and percentages
$choicesByQuestion = [];
$cStmt = $pdo->prepare(
    "SELECT sc.*,
            (SELECT COUNT(*) FROM responses r WHERE r.question_id = sc.question_id AND r.choice_id = sc.choice_id) AS vote_count
     FROM survey_choices sc
     INNER JOIN survey_questions sq ON sq.question_id = sc.question_id
     WHERE sq.survey_id = ?
     ORDER BY sc.choice_order ASC, sc.choice_id ASC"
);
$cStmt->execute([$surveyId]);
$allChoices = $cStmt->fetchAll();

foreach ($allChoices as $c) {
    $choicesByQuestion[$c['question_id']][] = $c;
}

// 5. Fetch Short Answers per Question
$shortAnswersByQuestion = [];
$saStmt = $pdo->prepare(
    "SELECT r.*, m.account_number, m.firstname, m.lastname, m.passport_photo
     FROM responses r
     INNER JOIN survey_questions sq ON sq.question_id = r.question_id
     INNER JOIN member m ON m.member_id = r.member_id
     WHERE sq.survey_id = ? AND sq.question_type = 'short_answer'
     ORDER BY r.submitted_at DESC"
);
$saStmt->execute([$surveyId]);
$shortAnswers = $saStmt->fetchAll();

foreach ($shortAnswers as $sa) {
    $shortAnswersByQuestion[$sa['question_id']][] = $sa;
}

// 6. Fetch Distinct Member Respondent List
$respondentsStmt = $pdo->prepare(
    "SELECT m.member_id, m.account_number, m.firstname, m.lastname, m.contact_number, m.address, m.passport_photo,
            MAX(r.submitted_at) AS submitted_at,
            COUNT(DISTINCT r.question_id) AS answered_count
     FROM member m
     INNER JOIN responses r ON r.member_id = m.member_id
     WHERE r.survey_id = ?
     GROUP BY m.member_id
     ORDER BY submitted_at DESC"
);
$respondentsStmt->execute([$surveyId]);
$respondents = $respondentsStmt->fetchAll();

// 7. Fetch All Raw Responses for Member Drill-down Modal
$allResponsesStmt = $pdo->prepare(
    "SELECT r.member_id, r.question_id, r.choice_id, r.answer, r.submitted_at,
            sq.question, sq.question_type, sq.question_order,
            sc.choice_text
     FROM responses r
     INNER JOIN survey_questions sq ON sq.question_id = r.question_id
     LEFT JOIN survey_choices sc ON sc.choice_id = r.choice_id
     WHERE r.survey_id = ?
     ORDER BY sq.question_order ASC"
);
$allResponsesStmt->execute([$surveyId]);
$rawResponses = $allResponsesStmt->fetchAll();

$memberAnswersMap = [];
foreach ($rawResponses as $row) {
    $memberAnswersMap[$row['member_id']][] = $row;
}

$pageTitle = 'Survey Results & Respondents · ' . $survey['title'];
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div id="surveyResponsesView" class="page-view active">
  
  <!-- Back Link & Action Bar -->
  <div class="section-header-flex view-header-wrapper mb-3">
    <div class="section-title">
      <h2>Survey Responses &amp; Respondent Log</h2>
      <p class="section-subtitle">Real-time participation tracking and individual submission review</p>
    </div>

    <div class="d-inline-flex gap-2 align-items-center">
      <a href="surveys.php" class="btn btn-secondary" style="width: 44px; height: 44px; padding: 0; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; text-decoration: none;" title="Back to Survey Management" aria-label="Back to Survey Management">
        <i class="fa-solid fa-arrow-left"></i>
      </a>
      <a href="survey_questions.php?id=<?= $surveyId ?>" class="btn btn-secondary btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.45rem; height: 44px; padding: 0 16px; border-radius: 12px;">
        <i class="fa-solid fa-list-check"></i> Manage Questions
      </a>
    </div>
  </div>

  <!-- Survey Summary Header Card -->
  <div class="table-card mb-4" style="margin-top: 0; padding: 1.25rem 1.5rem;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.35rem;">
          <span class="status-pill status-<?= ($survey['status'] === 'active' ? 'active' : ($survey['status'] === 'draft' ? 'default' : 'locked')) ?>">
            <?= e(ucfirst($survey['status'])) ?>
          </span>
          <span style="color: var(--text-muted); font-size: 0.82rem;">
            <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal); margin-right: 4px;"></i>
            <?= date('M d, Y g:i A', strtotime($survey['start_date'])) ?> &mdash; <?= date('M d, Y g:i A', strtotime($survey['end_date'])) ?>
          </span>
        </div>
        <h3 style="color: var(--text-main); font-size: 1.3rem; font-weight: 700; margin-bottom: 0.3rem;">
          <?= e($survey['title']) ?>
        </h3>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0; max-width: 750px; line-height: 1.5;">
          <?= e($survey['description'] ?: 'No description provided.') ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Metrics Grid -->
  <div class="metrics-grid" style="margin-bottom: 1.5rem;">
    <div class="metric-card hover-glow-card">
      <div class="metric-icon purple">
        <i class="fa-solid fa-users"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Total Respondents</span>
        <h3 class="metric-value"><?= number_format($totalRespondents) ?></h3>
        <span class="metric-subtext"><i class="fa-solid fa-user-check"></i> Completed surveys</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon cyan">
        <i class="fa-solid fa-percent"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Participation Rate</span>
        <h3 class="metric-value"><?= $participationRate ?>%</h3>
        <span class="metric-subtext positive"><i class="fa-solid fa-chart-line"></i> Of <?= number_format($totalMembers) ?> active members</span>
      </div>
    </div>

    <div class="metric-card hover-glow-card">
      <div class="metric-icon emerald">
        <i class="fa-solid fa-list-check"></i>
      </div>
      <div class="metric-data">
        <span class="metric-label">Total Questions</span>
        <h3 class="metric-value"><?= count($questions) ?></h3>
        <span class="metric-subtext"><i class="fa-solid fa-layer-group"></i> Active survey items</span>
      </div>
    </div>
  </div>

  <!-- Tabs Navigation & Response Actions -->
  <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.65rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.85rem; flex-wrap: wrap;">
    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
      <button type="button" class="btn btn-sm" id="btnTabRespondents" onclick="switchSurveyTab('respondents')" style="background: rgba(0, 194, 168, 0.2); color: #00e6c7; border: 1px solid rgba(0, 194, 168, 0.4); border-radius: 8px; padding: 8px 18px; font-weight: 700;">
        <i class="fa-solid fa-users-viewfinder me-1"></i> Member Respondents Log (<?= count($respondents) ?>)
      </button>
      <button type="button" class="btn btn-sm" id="btnTabAnalytics" onclick="switchSurveyTab('analytics')" style="background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid var(--bg-card-border); border-radius: 8px; padding: 8px 18px; font-weight: 600;">
        <i class="fa-solid fa-chart-pie me-1"></i> Fast Question Breakdown
      </button>
    </div>

    <div>
      <a href="survey_questions.php?id=<?= $surveyId ?>#responses" class="btn btn-primary btn-sm" style="text-decoration: none; border-radius: 8px; padding: 7px 16px; font-weight: 600;">
        <i class="fa-solid fa-chart-column me-1"></i> Analytics Dashboard
      </a>
    </div>
  </div>

  <!-- =========================================================
       TAB 1: MEMBER RESPONDENTS LOG
       ========================================================= -->
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
        <tbody>
          <?php if (empty($respondents)): ?>
            <tr>
              <td colspan="6" class="text-center py-5" style="color: var(--text-muted);">
                <i class="fa-solid fa-users-slash fs-2 mb-2 d-block opacity-50"></i>
                No members have submitted responses for this survey yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($respondents as $resp): ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="<?= photo_url($resp['passport_photo']) ?>" alt="Member Photo" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--primary-teal);">
                    <div>
                      <div style="font-weight: 700; color: var(--text-main); font-size: 0.92rem;"><?= e($resp['firstname'] . ' ' . $resp['lastname']) ?></div>
                      <div style="color: var(--text-muted); font-size: 0.78rem;"><?= e($resp['address']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <strong class="account-tag"><?= e($resp['account_number']) ?></strong>
                </td>
                <td style="color: var(--text-dim); font-size: 0.85rem;">
                  <?= e($resp['contact_number'] ?: 'N/A') ?>
                </td>
                <td style="text-align: center; color: var(--primary-teal); font-weight: 700;">
                  <i class="fa-solid fa-circle-check me-1"></i> <?= (int)$resp['answered_count'] ?> / <?= count($questions) ?>
                </td>
                <td style="font-size: 0.82rem; color: var(--text-muted);">
                  <?= date('M d, Y · h:i A', strtotime($resp['submitted_at'])) ?>
                </td>
                <td style="text-align: center;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick='viewMemberResponses(<?= (int)$resp['member_id'] ?>, <?= json_encode($resp['firstname'] . " " . $resp['lastname']) ?>, <?= json_encode($resp['account_number']) ?>)' style="border-radius: 8px; padding: 6px 14px; font-size: 0.82rem;">
                    <i class="fa-solid fa-eye me-1"></i> View Answers
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- =========================================================
       TAB 2: QUESTION ANALYTICS BREAKDOWN
       ========================================================= -->
  <div id="tabAnalyticsContent" style="display: none;">
    <?php if (empty($questions)): ?>
      <div class="table-card text-center py-5">
        <p style="color: var(--text-muted); margin: 0;">No questions found for this survey.</p>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
        <?php foreach ($questions as $index => $q): ?>
          <?php
            $qId = $q['question_id'];
            $qChoices = $choicesByQuestion[$qId] ?? [];
            $qShortAnswers = $shortAnswersByQuestion[$qId] ?? [];
            $qNumber = $index + 1;

            // Compute total votes for this question
            $qTotalVotes = 0;
            foreach ($qChoices as $c) {
                $qTotalVotes += (int)$c['vote_count'];
            }
          ?>
          <div class="table-card hover-glow-card" style="margin-top: 0; padding: 1.5rem;">
            <!-- Question Title Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 0.85rem;">
              <div style="display: flex; align-items: center; gap: 0.65rem;">
                <span style="background: rgba(0, 194, 168, 0.15); border: 1px solid rgba(0, 194, 168, 0.35); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 4px 10px; font-size: 0.82rem;">
                  Q<?= $qNumber ?>
                </span>
                <h4 style="color: var(--text-main); font-size: 1.1rem; font-weight: 700; margin: 0;">
                  <?= e($q['question']) ?>
                </h4>
              </div>
              <span class="status-pill">
                <?= match($q['question_type']) {
                  'multiple_choice' => 'Multiple Choice',
                  'yes_no'          => 'Yes/No',
                  'rating'          => 'Rating Scale',
                  'short_answer'    => 'Description Type Answer',
                  default           => ucwords(str_replace('_', ' ', $q['question_type']))
                } ?>
              </span>
            </div>

            <!-- CHOICE-BASED QUESTION ANALYTICS -->
            <?php if ($q['question_type'] !== 'short_answer'): ?>
              <div style="display: flex; flex-direction: column; gap: 0.85rem; padding-top: 0.35rem;">
                <?php foreach ($qChoices as $choice): ?>
                  <?php
                    $votes = (int)$choice['vote_count'];
                    $pct = $qTotalVotes > 0 ? round(($votes / $qTotalVotes) * 100, 1) : 0.0;
                  ?>
                  <div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem;">
                      <span style="color: var(--text-main);"><?= e($choice['choice_text']) ?></span>
                      <span style="color: var(--primary-teal); font-weight: 700;"><?= $votes ?> vote<?= $votes === 1 ? '' : 's' ?> (<?= $pct ?>%)</span>
                    </div>
                    <div style="height: 10px; background: rgba(255, 255, 255, 0.06); border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.08); overflow: hidden;">
                      <div style="width: <?= $pct ?>%; height: 100%; background: linear-gradient(90deg, #00c2a8 0%, #8b5cf6 100%); border-radius: 6px;"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
                
                <div style="font-size: 0.82rem; color: var(--text-muted); padding-top: 0.75rem; border-top: 1px solid rgba(255, 255, 255, 0.05); margin-top: 0.5rem;">
                  <i class="fa-solid fa-calculator" style="color: var(--primary-teal); margin-right: 4px;"></i> Total Answers: <strong style="color: var(--text-main);"><?= $qTotalVotes ?></strong>
                </div>
              </div>

            <!-- SHORT ANSWER RESPONSES -->
            <?php else: ?>
              <div style="padding-top: 0.35rem;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.65rem; letter-spacing: 0.5px;">
                  Submitted Member Feedback (<?= count($qShortAnswers) ?>):
                </div>

                <?php if (empty($qShortAnswers)): ?>
                  <div class="structured-box text-center py-4" style="color: var(--text-muted);">
                    No text responses submitted yet for this question.
                  </div>
                <?php else: ?>
                  <div style="display: flex; flex-direction: column; gap: 0.65rem; max-height: 280px; overflow-y: auto;">
                    <?php foreach ($qShortAnswers as $sa): ?>
                      <div class="structured-box" style="padding: 0.85rem 1rem;">
                        <p style="color: var(--text-main); font-size: 0.92rem; line-height: 1.5; margin-bottom: 0.5rem;">
                          <?= nl2br(e($sa['answer'])) ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted);">
                          <span><i class="fa-solid fa-user" style="color: var(--primary-teal); margin-right: 4px;"></i> <?= e($sa['firstname'] . ' ' . $sa['lastname']) ?> (<strong style="color: var(--text-main);"><?= e($sa['account_number']) ?></strong>)</span>
                          <span><i class="fa-solid fa-clock" style="color: var(--primary-teal); margin-right: 4px;"></i> <?= date('M d, Y · h:i A', strtotime($sa['submitted_at'])) ?></span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- =========================================================
     MODAL: MEMBER INDIVIDUAL SURVEY RESPONSE SHEET (SET A MATCHED)
     ========================================================= -->
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

      <div id="modalAnswersContainer" style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 420px; overflow-y: auto;">
        <!-- Answers populated dynamically via JS -->
      </div>
    </div>

    <div class="modal-actions" style="margin-top: 1.5rem;">
      <button type="button" class="btn btn-primary btn-sm" onclick="closeModal('memberResponseModal')">
        <i class="fa-solid fa-check me-1"></i> Close
      </button>
    </div>
  </div>
</div>

<script>
const memberAnswersData = <?= json_encode($memberAnswersMap) ?>;

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

function viewMemberResponses(memberId, memberName, accountNumber) {
  document.getElementById('modalRespondentName').textContent = memberName;
  document.getElementById('modalRespondentAccount').textContent = accountNumber;

  const container = document.getElementById('modalAnswersContainer');
  container.innerHTML = '';

  const answers = memberAnswersData[memberId] || [];

  if (answers.length === 0) {
    container.innerHTML = '<p class="text-muted text-center py-3">No recorded responses found for this member.</p>';
  } else {
    answers.forEach((ans, idx) => {
      const card = document.createElement('div');
      card.className = 'structured-box';
      card.style.padding = '0.85rem 1rem';

      card.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
          <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 2px 8px; font-size: 0.75rem;">
            Q${idx + 1}
          </span>
          <div style="color: #ffffff; font-weight: 700; font-size: 0.88rem;">${escapeHtml(ans.question)}</div>
        </div>
        <div style="padding: 0.6rem 0.85rem; border-radius: 6px; background: rgba(0, 194, 168, 0.08); border-left: 3px solid var(--primary-teal); color: #ffffff; font-weight: 500; font-size: 0.9rem;">
          <i class="fa-solid fa-arrow-right" style="color: var(--primary-teal); font-size: 0.75rem; margin-right: 6px;"></i> ${escapeHtml(ans.answer || 'No answer provided')}
        </div>
      `;
      container.appendChild(card);
    });
  }

  openModal('memberResponseModal');
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>
