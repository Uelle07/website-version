<?php
/**
 * staff/survey_print_report.php
 * -------------------------------------------------------
 * Staff Portal — Printable Survey Summary & Analytics Report (SET B)
 * Standalone, high-contrast, print-optimized document layout.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$surveyId = (int)($_GET['id'] ?? 0);

if ($surveyId <= 0) {
    die('Invalid survey requested.');
}

// 1. Fetch Survey Details
$stmt = $pdo->prepare(
    "SELECT s.*, st.fullname AS author_name 
     FROM surveys s
     LEFT JOIN staff st ON st.staff_id = s.created_by_staff_id
     WHERE s.survey_id = ? 
     LIMIT 1"
);
$stmt->execute([$surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    die('Survey not found.');
}

// Determine safe fallback URL for back navigation
$fallbackUrl = 'surveys.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    $baseHost = parse_url(BASE_URL, PHP_URL_HOST);
    if (empty($refHost) || $refHost === $serverHost || $refHost === $baseHost || strpos($ref, 'cooperative-member-information-system') !== false) {
        $fallbackUrl = $ref;
    }
}

// 2. Compute Live Survey Metrics
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

// 4. Calculate Live Statistics per Question
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

$shortAnswersByQuestion = [];
$saStmt = $pdo->prepare(
    "SELECT r.*, m.account_number, m.firstname, m.lastname
     FROM responses r
     INNER JOIN survey_questions sq ON sq.question_id = r.question_id
     INNER JOIN member m ON m.member_id = r.member_id
     WHERE sq.survey_id = ? AND sq.question_type = 'short_answer'
     ORDER BY r.submitted_at ASC"
);
$saStmt->execute([$surveyId]);
$shortAnswers = $saStmt->fetchAll();

foreach ($shortAnswers as $sa) {
    $shortAnswersByQuestion[$sa['question_id']][] = $sa;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Survey Report &mdash; <?= e($survey['title']) ?></title>
  
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

  <!-- Floating Print Control Bar -->
  <div class="no-print">
    <a href="<?= e($fallbackUrl) ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="btn btn-secondary btn-icon" title="Go Back" aria-label="Go Back">
      <i class="fa-solid fa-arrow-left"></i>
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i class="fa-solid fa-print"></i> Print Report Now
    </button>
  </div>

  <div class="report-paper">
    <!-- Header -->
    <div class="report-header">
      <div>
        <div class="brand-title">SPACE MULTIPURPOSE COOPERATIVE</div>
        <div class="brand-sub">Official Survey Analytics &amp; Member Feedback Report</div>
      </div>
      <div>
        <span class="report-badge"><i class="fa-solid fa-file-circle-check me-1"></i> Official Report</span>
      </div>
    </div>

    <!-- Survey Meta Info Box -->
    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #111827;">General Information</h3>
    <div class="meta-box">
      <div class="meta-item">
        <label>Survey Title</label>
        <span><?= e($survey['title']) ?></span>
      </div>
      <div class="meta-item">
        <label>Schedule Duration</label>
        <span><?= date('M d, Y g:i A', strtotime($survey['start_date'])) ?> &mdash; <?= date('M d, Y g:i A', strtotime($survey['end_date'])) ?></span>
      </div>
      <div class="meta-item">
        <label>Current Status</label>
        <span style="text-transform: capitalize; color: #008775;"><?= e($survey['status']) ?></span>
      </div>
      <div class="meta-item">
        <label>Author / Staff</label>
        <span><?= e($survey['author_name'] ?: 'Staff Admin') ?></span>
      </div>
      <div class="meta-item">
        <label>General Overview of All Members</label>
        <span><?= number_format($totalMembers) ?> Registered Members</span>
      </div>
      <div class="meta-item">
        <label>Total Number of Responses</label>
        <span><?= number_format($totalRespondents) ?> Responses (<?= $participationRate ?>%)</span>
      </div>
      <div class="meta-item">
        <label>Date Compiled</label>
        <span><?= date('F d, Y \a\t h:i A') ?></span>
      </div>
    </div>

    <?php if (!empty($survey['description'])): ?>
      <div style="margin-bottom: 25px; padding: 12px 18px; background: #f8fafc; border-left: 3.5px solid #008775; font-size: 13px; color: #334155; border-radius: 0 6px 6px 0;">
        <strong style="color: #0f172a;">Survey Description:</strong> <?= nl2br(e($survey['description'])) ?>
      </div>
    <?php endif; ?>

    <!-- Questions & Results Breakdown -->
    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #111827;">Specific Survey Questions</h3>

    <?php foreach ($questions as $index => $q): ?>
      <?php 
        $qId = (int)$q['question_id'];
        $qChoices = $choicesByQuestion[$qId] ?? [];
        $qShortAnswers = $shortAnswersByQuestion[$qId] ?? [];
        $qNumber = $index + 1;
        $qType = $q['question_type'];

        // Compute total votes for this question
        $qTotalVotes = 0;
        $ratingSum = 0;
        foreach ($qChoices as $c) {
            $v = (int)$c['vote_count'];
            $qTotalVotes += $v;
            if ($qType === 'rating') {
                preg_match('/^(\d+)/', $c['choice_text'], $m);
                $num = !empty($m[1]) ? (int)$m[1] : (int)$c['choice_order'];
                $ratingSum += ($num * $v);
            }
        }
        $avgScore = ($qType === 'rating' && $qTotalVotes > 0) ? round($ratingSum / $qTotalVotes, 2) : null;
      ?>
      <div class="question-block">
        <div class="question-head">
          <div>
            <span class="q-number">Q<?= $qNumber ?>.</span>
            <span class="q-title"><?= e($q['question']) ?></span>
          </div>
          <div>
            <?php
              $printTypeDisplay = match($qType) {
                'multiple_choice' => 'Multiple Choice',
                'yes_no'          => 'Yes/No',
                'rating'          => 'Rating Scale',
                'short_answer'    => 'Description Type Answer',
                default           => ucwords(str_replace('_', ' ', $qType))
              };
            ?>
            <span class="q-type"><?= e($printTypeDisplay) ?></span>
            <?php if ($avgScore !== null): ?>
              <span class="q-type" style="background: #fef3c7; color: #92400e; margin-left: 5px;">Avg: <?= $avgScore ?> / 5.0</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($qType !== 'short_answer'): ?>
          <table class="results-table">
            <thead>
              <tr>
                <th style="width: 45%;">Selected Option / Choice</th>
                <th style="width: 20%; text-align: right;">Vote Count</th>
                <th style="width: 35%;">Percentage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($qChoices as $choice): ?>
                <?php
                  $votes = (int)$choice['vote_count'];
                  $pct = $qTotalVotes > 0 ? round(($votes / $qTotalVotes) * 100, 1) : 0.0;
                ?>
                <tr>
                  <td style="font-weight: 600;"><?= e($choice['choice_text']) ?></td>
                  <td style="text-align: right; font-weight: 700; color: #00897b;"><?= $votes ?></td>
                  <td>
                    <div class="bar-container">
                      <div class="bar-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                    <strong><?= $pct ?>%</strong>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr style="background: #f9fafb; font-weight: 700;">
                <td>Total Question Responses</td>
                <td style="text-align: right;"><?= $qTotalVotes ?></td>
                <td>100.0%</td>
              </tr>
            </tbody>
          </table>
        <?php else: ?>
          <table class="results-table">
            <thead>
              <tr>
                <th style="width: 25%;">Respondent Member</th>
                <th style="width: 55%;">Submitted Feedback / Response</th>
                <th style="width: 20%;">Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($qShortAnswers)): ?>
                <tr>
                  <td colspan="3" style="text-align: center; color: #9ca3af; padding: 15px;">No written responses submitted for this question.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($qShortAnswers as $sa): ?>
                  <tr>
                    <td style="font-weight: 600;">
                      <?= e($sa['firstname'] . ' ' . $sa['lastname']) ?><br>
                      <small style="color: #6b7280; font-weight: normal;"><?= e($sa['account_number']) ?></small>
                    </td>
                    <td><?= nl2br(e($sa['answer'])) ?></td>
                    <td style="font-size: 12px; color: #6b7280;"><?= date('M d, Y h:i A', strtotime($sa['submitted_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <!-- Sign-off Section -->
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

  </div>

</body>
</html>
