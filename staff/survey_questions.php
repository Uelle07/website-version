<?php
/**
 * staff/survey_questions.php
 * -------------------------------------------------------
 * Staff Portal — Survey Question & Choice Builder (SET B)
 * Manage questions, choices, and question ordering for a survey.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$surveyId = (int)($_GET['id'] ?? $_POST['survey_id'] ?? 0);

if ($surveyId <= 0) {
    set_flash('error', 'Invalid survey requested.');
    redirect('staff/surveys.php');
}

// Automatically sync and mark expired active surveys as closed
sync_expired_surveys($pdo);

// 1. Fetch Survey Details
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE survey_id = ? LIMIT 1");
$stmt->execute([$surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    set_flash('error', 'Survey not found.');
    redirect('staff/surveys.php');
}

$isPublished = ($survey['status'] !== 'draft');
$errors = [];

// =========================================================
// 2. HANDLE POST ACTIONS (ADD, EDIT, DELETE QUESTIONS)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    // --- A. ADD NEW QUESTION ---
    if ($action === 'add_question') {
        if ($isPublished) {
            $errors[] = 'Questions cannot be added anymore once a survey has been published.';
        }
        $questionText = trim($_POST['question'] ?? '');
        $questionType = trim($_POST['question_type'] ?? 'multiple_choice');
        $rawChoices   = $_POST['choices'] ?? [];

        if ($questionText === '') {
            $errors[] = 'Question text is required.';
        }
        if (!in_array($questionType, ['multiple_choice', 'yes_no', 'rating', 'short_answer'], true)) {
            $questionType = 'multiple_choice';
        }

        // Clean choices
        $cleanChoices = [];
        if ($questionType === 'yes_no') {
            $cleanChoices = ['Yes', 'No'];
        } elseif ($questionType === 'rating') {
            $cleanChoices = ['1 - Poor', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent'];
        } elseif ($questionType === 'multiple_choice') {
            foreach ($rawChoices as $c) {
                $cVal = trim((string)$c);
                if ($cVal !== '') {
                    $cleanChoices[] = $cVal;
                }
            }
            if (count($cleanChoices) < 2) {
                $errors[] = 'Multiple choice questions require at least 2 option choices.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Compute next question order
                $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 FROM survey_questions WHERE survey_id = ?");
                $orderStmt->execute([$surveyId]);
                $nextOrder = (int)$orderStmt->fetchColumn();

                $qInsert = $pdo->prepare(
                    "INSERT INTO survey_questions (survey_id, question, question_type, question_order)
                     VALUES (?, ?, ?, ?)"
                );
                $qInsert->execute([$surveyId, $questionText, $questionType, $nextOrder]);
                $newQuestionId = (int)$pdo->lastInsertId();

                // Insert choices if applicable
                if (!empty($cleanChoices)) {
                    $cInsert = $pdo->prepare(
                        "INSERT INTO survey_choices (question_id, choice_text, choice_order)
                         VALUES (?, ?, ?)"
                    );
                    foreach ($cleanChoices as $cIdx => $choiceText) {
                        $cInsert->execute([$newQuestionId, $choiceText, $cIdx + 1]);
                    }
                }

                $pdo->commit();

                set_flash('success', 'Question added successfully.');
                redirect("staff/survey_questions.php?id={$surveyId}");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Failed to add question: ' . $e->getMessage();
            }
        }
    }

    // --- B. EDIT EXISTING QUESTION ---
    elseif ($action === 'edit_question') {
        if ($isPublished) {
            $errors[] = 'Questions cannot be edited anymore once a survey has been published.';
        }
        $questionId   = (int)($_POST['question_id'] ?? 0);
        $questionText = trim($_POST['question'] ?? '');
        $questionType = trim($_POST['question_type'] ?? 'multiple_choice');
        $rawChoices   = $_POST['choices'] ?? [];

        if ($questionId <= 0) {
            $errors[] = 'Invalid question specified.';
        }
        if ($questionText === '') {
            $errors[] = 'Question text is required.';
        }
        if (!in_array($questionType, ['multiple_choice', 'yes_no', 'rating', 'short_answer'], true)) {
            $questionType = 'multiple_choice';
        }

        $cleanChoices = [];
        if ($questionType === 'yes_no') {
            $cleanChoices = ['Yes', 'No'];
        } elseif ($questionType === 'rating') {
            $cleanChoices = ['1 - Poor', '2 - Fair', '3 - Good', '4 - Very Good', '5 - Excellent'];
        } elseif ($questionType === 'multiple_choice') {
            foreach ($rawChoices as $c) {
                $cVal = trim((string)$c);
                if ($cVal !== '') {
                    $cleanChoices[] = $cVal;
                }
            }
            if (count($cleanChoices) < 2) {
                $errors[] = 'Multiple choice questions require at least 2 option choices.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $qUpdate = $pdo->prepare(
                    "UPDATE survey_questions 
                     SET question = ?, question_type = ? 
                     WHERE question_id = ? AND survey_id = ?"
                );
                $qUpdate->execute([$questionText, $questionType, $questionId, $surveyId]);

                // Delete old choices and re-insert updated choices
                $delChoices = $pdo->prepare("DELETE FROM survey_choices WHERE question_id = ?");
                $delChoices->execute([$questionId]);

                if (!empty($cleanChoices)) {
                    $cInsert = $pdo->prepare(
                        "INSERT INTO survey_choices (question_id, choice_text, choice_order)
                         VALUES (?, ?, ?)"
                    );
                    foreach ($cleanChoices as $cIdx => $choiceText) {
                        $cInsert->execute([$questionId, $choiceText, $cIdx + 1]);
                    }
                }

                $pdo->commit();

                set_flash('success', 'Question updated successfully.');
                redirect("staff/survey_questions.php?id={$surveyId}");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Failed to update question: ' . $e->getMessage();
            }
        }
    }

    // --- C. DELETE QUESTION ---
    elseif ($action === 'delete_question') {
        if ($isPublished) {
            $errors[] = 'Questions cannot be deleted anymore once a survey has been published.';
        }
        $questionId = (int)($_POST['question_id'] ?? 0);

        if ($questionId > 0 && empty($errors)) {
            $qDel = $pdo->prepare("DELETE FROM survey_questions WHERE question_id = ? AND survey_id = ?");
            $qDel->execute([$questionId, $surveyId]);

            set_flash('success', 'Question deleted successfully.');
            redirect("staff/survey_questions.php?id={$surveyId}");
        } elseif (empty($errors)) {
            $errors[] = 'Invalid question identifier for deletion.';
        }
    }

    // --- D. EDIT SURVEY SETTINGS ---
    elseif ($action === 'edit_survey_settings') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status      = trim($_POST['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'active', 'inactive', 'closed'], true)) {
            $status = 'draft';
        }

        $startDateDay  = trim($_POST['start_date'] ?? '');
        $startTime     = trim($_POST['start_time'] ?? '00:00');
        $endDateDay    = trim($_POST['end_date'] ?? '');
        $endTime       = trim($_POST['end_time'] ?? '23:59');

        if ($startDateDay !== '' && str_contains($startDateDay, 'T')) {
            $startDate = str_replace('T', ' ', $startDateDay);
        } else {
            $startDate = $startDateDay !== '' ? ($startDateDay . ' ' . ($startTime ?: '00:00') . ':00') : '';
        }

        if ($endDateDay !== '' && str_contains($endDateDay, 'T')) {
            $endDate = str_replace('T', ' ', $endDateDay);
        } else {
            $endDate = $endDateDay !== '' ? ($endDateDay . ' ' . ($endTime ?: '23:59') . ':00') : '';
        }

        if ($status === 'active' && $startDate !== '' && $startDate > date('Y-m-d H:i:s')) {
            $startDate = date('Y-m-d H:i:s');
        }

        if ($title === '') {
            $errors[] = 'Survey title is required.';
        }
        if ($startDate === '' || $endDate === '' || $startDateDay === '' || $endDateDay === '') {
            $errors[] = 'Opening date & time and closing date & time are required.';
        } else {
            $startTimestamp = strtotime($startDate);
            $endTimestamp   = strtotime($endDate);

            if ($startTimestamp === false || $endTimestamp === false) {
                $errors[] = 'Invalid date or time format provided.';
            } elseif ($endTimestamp <= $startTimestamp) {
                $errors[] = 'Invalid Schedule: The closing date and time must be logically after the starting date and time.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                "UPDATE surveys 
                 SET title = ?, description = ?, status = ?, start_date = ?, end_date = ?
                 WHERE survey_id = ?"
            );
            $stmt->execute([$title, $description, $status, $startDate, $endDate, $surveyId]);

            set_flash('success', "Survey settings updated successfully.");
            redirect("staff/survey_questions.php?id={$surveyId}&tab=settings");
        }
    }

    // --- E. PUBLISH / DEPLOY SURVEY ---
    elseif ($action === 'publish_survey') {
        $qCountStmt = $pdo->prepare("SELECT COUNT(*) FROM survey_questions WHERE survey_id = ?");
        $qCountStmt->execute([$surveyId]);
        $qCount = (int)$qCountStmt->fetchColumn();

        if ($qCount === 0) {
            set_flash('error', 'Cannot publish a survey with no questions. Please add at least one question before publishing.');
            redirect("staff/survey_questions.php?id={$surveyId}");
        } elseif (strtotime($survey['end_date']) <= strtotime($survey['start_date'])) {
            set_flash('error', 'Cannot publish survey: Closing date must be after opening date. Please adjust the schedule in Settings.');
            redirect("staff/survey_questions.php?id={$surveyId}&tab=settings");
        } else {
            $stmt = $pdo->prepare("UPDATE surveys SET status = 'active', start_date = LEAST(start_date, NOW()) WHERE survey_id = ?");
            $stmt->execute([$surveyId]);

            set_flash('success', "Survey '{$survey['title']}' has been published and is now active for cooperative members to answer!");
            redirect("staff/survey_questions.php?id={$surveyId}");
        }
    }

    // --- F. DELETE SURVEY ---
    elseif ($action === 'delete_survey') {
        $delStmt = $pdo->prepare("DELETE FROM surveys WHERE survey_id = ?");
        $delStmt->execute([$surveyId]);

        set_flash('success', "Survey '{$survey['title']}' was deleted successfully.");
        redirect('staff/surveys.php');
    }
}

// =========================================================
// 3. FETCH QUESTIONS & CHOICES
// =========================================================
$questionsStmt = $pdo->prepare(
    "SELECT * FROM survey_questions 
     WHERE survey_id = ? 
     ORDER BY question_order ASC, question_id ASC"
);
$questionsStmt->execute([$surveyId]);
$questions = $questionsStmt->fetchAll();

$questionIds = array_column($questions, 'question_id');
$choicesByQuestion = [];

if (!empty($questionIds)) {
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $cStmt = $pdo->prepare(
        "SELECT * FROM survey_choices 
         WHERE question_id IN ($placeholders) 
         ORDER BY choice_order ASC, choice_id ASC"
    );
    $cStmt->execute($questionIds);
    $choices = $cStmt->fetchAll();

    foreach ($choices as $c) {
        $choicesByQuestion[$c['question_id']][] = $c;
    }
}

// =========================================================
// 4. FETCH LIVE ANALYTICS DATA FOR EMBEDDED ANALYTICS TAB
// =========================================================
$totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM member")->fetchColumn();
$respCountStmt = $pdo->prepare("SELECT COUNT(DISTINCT member_id) FROM responses WHERE survey_id = ?");
$respCountStmt->execute([$surveyId]);
$totalSurveyRespondents = (int)$respCountStmt->fetchColumn();
$participationRate = $totalMembers > 0 ? round(($totalSurveyRespondents / $totalMembers) * 100, 1) : 0;

$questionStats = [];
foreach ($questions as $q) {
    $qId = (int)$q['question_id'];
    $qType = $q['question_type'];

    if ($qType === 'short_answer') {
        $saStmt = $pdo->prepare(
            "SELECT r.*, m.account_number, m.firstname, m.lastname, m.passport_photo
             FROM responses r
             INNER JOIN member m ON m.member_id = r.member_id
             WHERE r.question_id = ?
             ORDER BY r.submitted_at DESC"
        );
        $saStmt->execute([$qId]);
        $questionStats[$qId] = [
            'type' => 'short_answer',
            'total_responses' => (int)$pdo->query("SELECT COUNT(*) FROM responses WHERE question_id = $qId")->fetchColumn(),
            'answers' => $saStmt->fetchAll(),
        ];
    } else {
        $cStatsStmt = $pdo->prepare(
            "SELECT sc.*,
                    (SELECT COUNT(*) FROM responses r WHERE r.question_id = sc.question_id AND r.choice_id = sc.choice_id) AS vote_count
             FROM survey_choices sc
             WHERE sc.question_id = ?
             ORDER BY sc.choice_order ASC, sc.choice_id ASC"
        );
        $cStatsStmt->execute([$qId]);
        $cList = $cStatsStmt->fetchAll();

        $qTotalVotes = 0;
        $ratingScoreSum = 0;
        $ratingVotesCount = 0;

        foreach ($cList as $c) {
            $votes = (int)$c['vote_count'];
            $qTotalVotes += $votes;
            if ($qType === 'rating') {
                preg_match('/^(\d+)/', $c['choice_text'], $matches);
                $numericVal = !empty($matches[1]) ? (int)$matches[1] : (int)$c['choice_order'];
                $ratingScoreSum += ($numericVal * $votes);
                $ratingVotesCount += $votes;
            }
        }

        $choicesWithPct = [];
        foreach ($cList as $c) {
            $votes = (int)$c['vote_count'];
            $pct = $qTotalVotes > 0 ? round(($votes / $qTotalVotes) * 100, 1) : 0.0;
            $c['percentage'] = $pct;
            $choicesWithPct[] = $c;
        }

        $avgRating = ($qType === 'rating' && $ratingVotesCount > 0) 
            ? round($ratingScoreSum / $ratingVotesCount, 2) 
            : 0.0;

        $questionStats[$qId] = [
            'type' => $qType,
            'total_responses' => $qTotalVotes,
            'choices' => $choicesWithPct,
            'avg_rating' => $avgRating,
        ];
    }
}

// 5. Fetch Distinct Member Respondent List & Answers Map for Individual Sub-Tab
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

$pageTitle = 'Manage Questions · ' . $survey['title'];
require_once __DIR__ . '/../pages/header_staff.php';
?>

<div id="surveyQuestionsView" class="page-view active">
  
  <style>
  /* ================= IN-CARD BACK BUTTON ================= */
  .back-nav-btn {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--bg-card-border);
    color: var(--text-main);
    font-size: 0.95rem;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
    outline: none !important;
  }

  .back-nav-btn i {
    font-size: 0.95rem;
    color: var(--text-main);
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .back-nav-btn:hover {
    background: rgba(0, 194, 168, 0.15);
    border-color: rgba(0, 194, 168, 0.45);
    color: #00e6c7;
    transform: translateX(-2px);
    box-shadow: 0 0 12px rgba(0, 194, 168, 0.25);
  }

  .back-nav-btn:hover i {
    color: #00e6c7;
  }

  .back-nav-btn:active {
    transform: scale(0.95);
  }

  html[data-theme="light"] .back-nav-btn {
    background: rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.12);
    color: #334155;
  }

  html[data-theme="light"] .back-nav-btn i {
    color: #334155;
  }

  html[data-theme="light"] .back-nav-btn:hover {
    background: rgba(0, 194, 168, 0.1);
    border-color: rgba(0, 194, 168, 0.4);
    color: #00897b;
  }

  html[data-theme="light"] .back-nav-btn:hover i {
    color: #00897b;
  }

  /* ================= QUESTIONNAIRE PILL TABNAV ================= */
  .questionnaire-tabnav-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 1.75rem;
    width: 100%;
  }

  .questionnaire-tab-track {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(8, 12, 22, 0.95) 100%);
    border: 1px solid rgba(0, 194, 168, 0.25);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), 0 0 20px rgba(0, 194, 168, 0.1);
    border-radius: 50px;
    padding: 6px 8px;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .questionnaire-tab-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid transparent;
    border-radius: 40px;
    padding: 9px 22px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    white-space: nowrap;
    outline: none !important;
  }

  .questionnaire-tab-btn i {
    color: var(--text-muted);
    font-size: 0.88rem;
    transition: color 0.25s ease;
  }

  .questionnaire-tab-btn:hover {
    color: var(--text-main);
    background: rgba(255, 255, 255, 0.08);
  }

  .questionnaire-tab-btn:hover i {
    color: var(--text-main);
  }

  .questionnaire-tab-btn.active-tab {
    background: rgba(0, 194, 168, 0.2) !important;
    color: #00e6c7 !important;
    font-weight: 700 !important;
    border: 1px solid rgba(0, 194, 168, 0.4) !important;
    box-shadow: none !important;
  }

  .questionnaire-tab-btn.active-tab i {
    color: #00e6c7 !important;
  }

  .responses-subtab-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid transparent;
  }
  .responses-subtab-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    color: var(--text-main);
  }
  .responses-subtab-btn.active-subtab {
    background: rgba(0, 194, 168, 0.2) !important;
    color: #00e6c7 !important;
    border-color: rgba(0, 194, 168, 0.4) !important;
    font-weight: 700 !important;
  }

  /* Question Count Indicator Pill */
  .questions-count-pill {
    background: rgba(0, 194, 168, 0.1);
    border: 1px solid rgba(0, 194, 168, 0.3);
    color: var(--primary-teal);
    font-weight: 700;
    border-radius: 8px;
    padding: 0 14px;
    height: 34px;
    font-size: 0.84rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    box-sizing: border-box;
    line-height: 1;
    transition: all 0.25s ease;
  }

  /* Published Status Badge (Clean, Symmetrical, No Glowing/Pulsing Effect) */
  .published-status-badge {
    height: 34px;
    padding: 0 14px;
    font-size: 0.84rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    background: rgba(16, 185, 129, 0.15) !important;
    border: 1px solid rgba(16, 185, 129, 0.35) !important;
    color: #34d399 !important;
    border-radius: 8px !important;
    box-sizing: border-box;
    line-height: 1;
    box-shadow: none !important;
    text-shadow: none !important;
    animation: none !important;
  }

  html[data-theme="light"] .published-status-badge {
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
    color: #047857 !important;
  }

  /* ================= ADD QUESTION MODAL SCOPED STYLES ================= */
  .add-question-modal .add-question-card {
    background: linear-gradient(145deg, #0b1120 0%, #060913 100%) !important;
    border: 1px solid rgba(0, 194, 168, 0.28) !important;
    border-radius: 20px !important;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75), 0 0 30px rgba(0, 194, 168, 0.12) !important;
    max-width: 720px;
    padding: 1.85rem 2rem;
  }
  .add-question-modal .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 1rem;
    margin-bottom: 1.4rem;
  }
  .add-question-modal .modal-header h3 {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }
  .add-question-modal .modal-header h3 i {
    color: #00e6c7;
    font-size: 1.1rem;
  }
  .add-question-modal .two-col-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 1.25rem;
    margin-bottom: 1.25rem;
  }
  @media (max-width: 640px) {
    .add-question-modal .two-col-grid {
      grid-template-columns: 1fr;
    }
  }
  .add-question-modal label {
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--text-dim);
    margin-bottom: 0.45rem;
    display: block;
  }
  .add-question-modal label .req-star {
    color: #f87171;
    font-weight: 700;
  }
  .add-question-modal input[type="text"],
  .add-question-modal select,
  .add-question-modal textarea {
    background: rgba(255, 255, 255, 0.04) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    color: #ffffff !important;
    border-radius: 10px !important;
    padding: 0.7rem 0.95rem !important;
    font-size: 0.9rem !important;
    outline: none !important;
    width: 100%;
    transition: all 0.2s ease;
  }
  .add-question-modal input[type="text"]::placeholder,
  .add-question-modal textarea::placeholder {
    color: rgba(255, 255, 255, 0.35) !important;
  }
  .add-question-modal input[type="text"]:focus,
  .add-question-modal select:focus,
  .add-question-modal textarea:focus {
    background: rgba(255, 255, 255, 0.07) !important;
    border-color: #00c2a8 !important;
    box-shadow: 0 0 16px rgba(0, 194, 168, 0.25) !important;
  }
  .add-question-modal select option {
    background-color: #0b1120 !important;
    color: #ffffff !important;
  }
  .add-question-modal .btn-pill-add-opt {
    background: rgba(0, 194, 168, 0.12);
    color: #00e6c7;
    border: 1px solid rgba(0, 194, 168, 0.35);
    border-radius: 20px;
    padding: 4px 13px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }
  .add-question-modal .btn-pill-add-opt:hover {
    background: rgba(0, 194, 168, 0.22);
    border-color: #00e6c7;
    box-shadow: 0 0 10px rgba(0, 194, 168, 0.3);
    color: #ffffff;
  }
  .add-question-modal .modal-footer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.6rem;
    padding-top: 1.25rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
  }
  .add-question-modal .btn-plain-cancel {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 8px 14px;
    cursor: pointer;
    transition: color 0.2s ease;
  }
  .add-question-modal .btn-plain-cancel:hover {
    color: #ffffff;
    text-decoration: underline;
  }
  .add-question-modal .btn-gradient-save {
    background: linear-gradient(135deg, #00c2a8 0%, #3b82f6 100%);
    color: #ffffff;
    border: none;
    font-weight: 700;
    font-size: 0.9rem;
    border-radius: 10px;
    padding: 9px 24px;
    box-shadow: 0 4px 16px rgba(0, 194, 168, 0.3);
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
  }
  .add-question-modal .btn-gradient-save:hover {
    box-shadow: 0 6px 20px rgba(0, 194, 168, 0.45);
    transform: translateY(-1px);
    filter: brightness(1.08);
  }
  </style>

  <!-- ================= SINGLE COHESIVE QUESTIONNAIRE HEADER CARD ================= -->
  <div class="table-card" style="margin-top: 0; margin-bottom: 1.5rem; padding: 1.5rem 1.75rem;">
    
    <!-- Row 1: Back Button + Eyebrow Heading + Top Right Publish Button & Question Count -->
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.95rem; flex-wrap: wrap;">
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <a href="surveys.php" class="back-nav-btn" title="Back to Survey Management" aria-label="Back to Survey Management">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; color: var(--primary-teal); letter-spacing: 0.8px; display: inline-flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-clipboard-list"></i> QUESTIONNAIRE MANAGEMENT
        </div>
      </div>

      <!-- Top Right: Question Count & Publish Action Trigger / Status Indicator -->
      <div class="d-inline-flex align-items-center gap-2">
        <span class="questions-count-pill" title="Total questions in this survey">
          <i class="fa-solid fa-list-check"></i>
          <span><?= count($questions) ?> Question<?= count($questions) === 1 ? '' : 's' ?></span>
        </span>
        <?php if ($survey['status'] === 'active'): ?>
          <span class="status-pill status-active published-status-badge">
            <i class="fa-solid fa-circle-check"></i> Published
          </span>
        <?php else: ?>
          <form action="survey_questions.php?id=<?= $surveyId ?>" method="POST" id="publishSurveyForm" style="margin: 0; display: inline;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="publish_survey">
            <button type="button" class="btn btn-primary btn-sm" onclick="showSystemConfirm({ title: 'Publish Survey', message: 'Publish and deploy this survey now? It will immediately become available for members to answer.', confirmText: 'Yes, Publish', isDanger: false, icon: 'fa-solid fa-paper-plane', onConfirm: () => document.getElementById('publishSurveyForm').submit() });" style="padding: 0 16px; height: 34px; font-size: 0.84rem; font-weight: 700; border-radius: 8px; background: linear-gradient(135deg, #00c2a8 0%, #3b82f6 100%); border: none; box-shadow: 0 4px 14px rgba(0, 194, 168, 0.35); display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem; line-height: 1; box-sizing: border-box;">
              <i class="fa-solid fa-paper-plane"></i> Publish
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Row 2: Survey Title (Strongest Visual Element) & Description -->
    <div style="margin-bottom: 1.25rem;">
      <h1 style="font-size: 1.65rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.45rem; line-height: 1.3; letter-spacing: -0.3px;">
        <?= e($survey['title']) ?>
      </h1>
      <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0; max-width: 820px; line-height: 1.55;">
        <?= e($survey['description'] ?: 'Define survey questions, response types, and choice options.') ?>
      </p>
    </div>

    <!-- Divider -->
    <hr style="border: 0; border-top: 1px solid rgba(255, 255, 255, 0.07); margin: 0 0 1.15rem 0;">

    <!-- Row 3: Schedule Dates Metadata -->
    <div class="d-flex justify-content-end align-items-center flex-wrap gap-3">
      <span style="color: var(--text-dim); font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.35rem;">
        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
        <?= date('M d, Y g:i A', strtotime($survey['start_date'])) ?> &mdash; <?= date('M d, Y g:i A', strtotime($survey['end_date'])) ?>
      </span>
    </div>

  </div>

  <!-- Navigation Tab Bar: [ Questions ] [ Responses ] [ Settings ] (Pill Track) -->
  <div class="questionnaire-tabnav-wrapper">
    <div class="questionnaire-tab-track">
      <button type="button" class="btn questionnaire-tab-btn active-tab" id="btnTabQuestions" onclick="switchQuestionnaireTab('questions')">
        <i class="fa-solid fa-list-check"></i> Questions (<?= count($questions) ?>)
      </button>
      <button type="button" class="btn questionnaire-tab-btn" id="btnTabResponses" onclick="switchQuestionnaireTab('responses')">
        <i class="fa-solid fa-chart-column"></i> Responses (<?= number_format($totalSurveyRespondents) ?>)
      </button>
      <button type="button" class="btn questionnaire-tab-btn" id="btnTabSettings" onclick="switchQuestionnaireTab('settings')">
        <i class="fa-solid fa-gear"></i> Settings
      </button>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="modal-error-box" style="margin-bottom: 1.5rem;">
      <i class="fa-solid fa-triangle-exclamation me-1"></i>
      <?= implode('<br>', array_map('e', $errors)) ?>
    </div>
  <?php endif; ?>

  <!-- =========================================================
       TAB 1: QUESTIONS CONTENT (DEFAULT ACTIVE)
       ========================================================= -->
  <div id="tabQuestionsContent">
    <div class="section-title" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
      <?php if (!empty($questions) && !$isPublished): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openAddQuestionModal()" style="padding: 7px 16px; font-size: 0.84rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.4rem; flex-shrink: 0;">
          <i class="fa-solid fa-plus"></i> Add Question
        </button>
      <?php endif; ?>
      <div>
        <h3 style="font-size: 1.1rem; color: var(--text-main); font-weight: 700; margin-bottom: 0.15rem;">Question List (<?= count($questions) ?>)</h3>
        <p class="section-subtitle" style="margin-bottom: 0;">Configured survey items and response options</p>
      </div>
    </div>

    <?php if (empty($questions)): ?>
      <div class="table-card text-center py-5" style="border: 1px dashed var(--bg-card-border); padding: 3rem 1.5rem; margin-bottom: 2rem;">
        <i class="fa-solid fa-clipboard-question fs-1 mb-3" style="color: var(--primary-teal); opacity: 0.4;"></i>
        <h4 style="color: var(--text-main); font-weight: 700; margin-bottom: 0.4rem;">No Questions in This Survey Yet</h4>
        <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 420px; margin: 0 auto 1.25rem auto; line-height: 1.5;">
          Build your questionnaire by adding multiple choice, yes/no, rating scale, or open-ended description type questions.
        </p>
        <?php if (!$isPublished): ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="openAddQuestionModal()" style="padding: 8px 20px; font-weight: 700; border-radius: 8px;">
            <i class="fa-solid fa-plus me-1"></i> Add Question
          </button>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 1.15rem; margin-bottom: 2.5rem;">
        <?php foreach ($questions as $index => $q): ?>
          <?php 
            $qId = (int)$q['question_id'];
            $qChoices = $choicesByQuestion[$qId] ?? [];
            $qNumber = $index + 1;

            $typeBadge = match($q['question_type']) {
              'multiple_choice' => '<span class="status-pill" style="background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8;"><i class="fa-solid fa-circle-dot" style="font-size:0.7rem;margin-right:4px;"></i> Multiple Choice</span>',
              'yes_no'          => '<span class="status-pill status-active"><i class="fa-solid fa-thumbs-up" style="font-size:0.7rem;margin-right:4px;"></i> Yes/No</span>',
              'rating'          => '<span class="status-pill status-default"><i class="fa-solid fa-star" style="font-size:0.7rem;margin-right:4px;"></i> Rating Scale (1-5)</span>',
              'short_answer'    => '<span class="status-pill" style="background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.35); color: #c084fc;"><i class="fa-solid fa-paragraph" style="font-size:0.7rem;margin-right:4px;"></i> Description Type Answer</span>',
              default           => '<span class="status-pill">' . e($q['question_type']) . '</span>'
            };
          ?>
          <div class="table-card hover-glow-card" style="margin-top: 0; padding: 1.4rem 1.6rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.85rem;">
              <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                <span style="background: rgba(0, 194, 168, 0.15); border: 1px solid rgba(0, 194, 168, 0.35); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 3px 8px; font-size: 0.8rem;">
                  Q<?= $qNumber ?>
                </span>
                <?= $typeBadge ?>
              </div>

              <?php if (!$isPublished): ?>
                <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                  <button type="button" class="icon-btn btn-edit-dynamic js-edit-question-btn" title="Edit Question" data-question-id="<?= (int)$qId ?>" aria-label="Edit Question">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <button type="button" class="icon-btn btn-delete-dynamic js-delete-question-btn" title="Delete Question" data-question-id="<?= (int)$qId ?>" aria-label="Delete Question">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              <?php endif; ?>
            </div>

            <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 700; margin-bottom: 0.85rem; line-height: 1.45;">
              <?= e($q['question']) ?>
            </h4>

            <!-- Choices View -->
            <?php if (!empty($qChoices)): ?>
              <div class="structured-box" style="margin-top: 0.75rem; padding: 0.95rem 1.15rem;">
                <div style="font-size: 0.74rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.55rem; letter-spacing: 0.5px;">
                  Configured Choices (<?= count($qChoices) ?>):
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.55rem;">
                  <?php foreach ($qChoices as $c): ?>
                    <span style="background: rgba(255, 255, 255, 0.05); border: 1px solid var(--bg-card-border); color: var(--text-main); border-radius: 6px; padding: 5px 11px; font-size: 0.82rem; font-weight: 500;">
                      <?= e($c['choice_text']) ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php elseif ($q['question_type'] === 'short_answer'): ?>
              <div style="color: var(--text-dim); font-size: 0.82rem; font-style: italic; margin-top: 0.35rem;">
                <i class="fa-solid fa-pen-fancy me-1" style="color: var(--primary-teal);"></i> Open-ended text field response.
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- =========================================================
       TAB 2: RESPONSES & ANALYTICS CONTENT
       ========================================================= -->
  <div id="tabResponsesContent" style="display: none;">
    
    <!-- Responses Header with Prominent Total Count -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
      <div>
        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem; letter-spacing: -0.2px;">
          <?= number_format($totalSurveyRespondents) ?> Responses
        </h3>
        <p class="section-subtitle" style="margin-bottom: 0; font-size: 0.88rem;">
          Aggregated response summaries, question-level analysis, and individual answer sheets
        </p>
      </div>

      <div class="d-inline-flex align-items-center gap-2 flex-wrap">
        <a href="survey_print_report.php?id=<?= $surveyId ?>" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration: none; padding: 6px 13px; font-size: 0.82rem;">
          <i class="fa-solid fa-print me-1"></i> Print Report
        </a>
      </div>
    </div>

    <!-- Secondary Responses Sub-Tabs: [ Summary ] [ Question ] [ Individual ] (Centered) -->
    <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
      <div style="display: inline-flex; gap: 0.4rem; background: rgba(255, 255, 255, 0.04); border: 1px solid var(--bg-card-border); padding: 4px; border-radius: 10px; flex-wrap: wrap; justify-content: center;">
        <button type="button" class="btn btn-sm responses-subtab-btn active-subtab" id="btnSubtabSummary" onclick="switchResponsesSubtab('summary')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem; cursor: pointer; transition: all 0.2s ease;">
          <i class="fa-solid fa-chart-pie me-1"></i> Summary
        </button>
        <button type="button" class="btn btn-sm responses-subtab-btn" id="btnSubtabQuestion" onclick="switchResponsesSubtab('question')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem; cursor: pointer; transition: all 0.2s ease;">
          <i class="fa-solid fa-list-ol me-1"></i> Question
        </button>
        <button type="button" class="btn btn-sm responses-subtab-btn" id="btnSubtabIndividual" onclick="switchResponsesSubtab('individual')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem; cursor: pointer; transition: all 0.2s ease;">
          <i class="fa-solid fa-user-check me-1"></i> Individual (<?= count($respondents) ?>)
        </button>
      </div>
    </div>

    <!-- ---------------------------------------------------------
         SUB-TAB 1: SUMMARY
         --------------------------------------------------------- -->
    <div id="subtabSummaryContent">
      <!-- Summary Metrics Grid -->
      <div class="metrics-grid" style="margin-bottom: 1.5rem;">
        <div class="metric-card hover-glow-card">
          <div class="metric-icon purple">
            <i class="fa-solid fa-users"></i>
          </div>
          <div class="metric-data">
            <span class="metric-label">Total Respondents</span>
            <h3 class="metric-value"><?= number_format($totalSurveyRespondents) ?></h3>
            <span class="metric-subtext"><i class="fa-solid fa-check"></i> Completed submissions</span>
          </div>
        </div>

        <div class="metric-card hover-glow-card">
          <div class="metric-icon cyan">
            <i class="fa-solid fa-chart-pie"></i>
          </div>
          <div class="metric-data">
            <span class="metric-label">Participation Rate</span>
            <h3 class="metric-value"><?= $participationRate ?>%</h3>
            <span class="metric-subtext positive"><i class="fa-solid fa-users-line"></i> Of <?= number_format($totalMembers) ?> active members</span>
          </div>
        </div>

        <div class="metric-card hover-glow-card">
          <div class="metric-icon emerald">
            <i class="fa-solid fa-list-check"></i>
          </div>
          <div class="metric-data">
            <span class="metric-label">Total Questions</span>
            <h3 class="metric-value"><?= count($questions) ?></h3>
            <span class="metric-subtext"><i class="fa-solid fa-database"></i> Live calculated items</span>
          </div>
        </div>
      </div>

      <!-- Quick Summary Overview Table with Search & Filter -->
      <div class="table-card" style="margin-top: 0; padding: 1.4rem 1.6rem; margin-bottom: 2rem; overflow: visible; position: relative; z-index: 50;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
          <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 700; margin: 0;">
            <i class="fa-solid fa-chart-simple" style="color: var(--primary-teal); margin-right: 6px;"></i> Response Summary
          </h4>
        </div>

        <!-- Search Bar and Filter Popover Controls -->
        <div class="side-by-side-controls mb-3" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
          <div class="search-bar-container view-search" style="flex: 1; max-width: 480px; position: relative;">
            <i class="fa-solid fa-magnifying-glass search-icon" style="position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none;"></i>
            <input type="text" class="search-input" id="summaryQuestionSearch" placeholder="Search questions in summary..." onkeyup="filterSummaryQuestions()" style="width: 100%; padding-left: 2.8rem; padding-right: 1rem; height: 38px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); font-size: 0.86rem; outline: none;">
          </div>

          <div class="unified-filter-wrapper" style="position: relative; z-index: 1050;">
            <button type="button" class="btn btn-secondary filter-btn-trigger btn-sm" id="summaryFilterBtn" onclick="toggleFilterMenu('summaryFilterPopover', event)" style="padding: 6px 14px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.45rem;">
              <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; opacity: 0.7;"></i>
            </button>

            <div id="summaryFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();" style="position: absolute; top: calc(100% + 8px); right: 0; width: 320px; z-index: 100000;">
              <div class="filter-popover-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--bg-card-border);">
                <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Summary</h4>
                <button type="button" class="close-popover-btn" onclick="toggleFilterMenu('summaryFilterPopover', event)" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1;">&times;</button>
              </div>

              <div class="filter-popover-group" style="margin-bottom: 1rem;">
                <label class="filter-label" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.45rem;">Question Type:</label>
                <select id="summaryTypeFilter" class="table-filter-select-lg" style="width: 100%; height: 42px; min-height: 42px; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); padding: 0.45rem 0.85rem; font-size: 0.88rem; line-height: 1.4; box-sizing: border-box;">
                  <option value="all">All Question Types</option>
                  <option value="multiple_choice">Multiple Choice</option>
                  <option value="yes_no">Yes/No</option>
                  <option value="rating">Rating Scale</option>
                  <option value="short_answer">Description Type Answer</option>
                </select>
              </div>

              <div class="filter-popover-group" style="margin-bottom: 1rem;">
                <label class="filter-label" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.45rem;">Response Status:</label>
                <select id="summaryStatusFilter" class="table-filter-select-lg" style="width: 100%; height: 42px; min-height: 42px; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); padding: 0.45rem 0.85rem; font-size: 0.88rem; line-height: 1.4; box-sizing: border-box;">
                  <option value="all">All Questions</option>
                  <option value="has_responses">With Responses (&gt; 0)</option>
                  <option value="zero_responses">Zero Responses (0)</option>
                </select>
              </div>

              <div class="filter-popover-footer" style="display: flex; justify-content: flex-end; padding-top: 0.75rem; border-top: 1px solid var(--bg-card-border); margin-top: 0.75rem;">
                <button type="button" class="btn btn-primary btn-sm" onclick="applySummaryQuestionsFilter(event)" style="padding: 6px 16px; font-size: 0.84rem; font-weight: 700; border-radius: 8px;">
                  <i class="fa-solid fa-check me-1"></i> Apply Filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="dashboard-table" id="summaryQuestionsTable">
            <thead>
              <tr>
                <th style="width: 60px; text-align: center;">#</th>
                <th style="min-width: 200px; text-align: center;">Question</th>
                <th style="min-width: 150px; text-align: center;">Type</th>
                <th style="width: 130px; text-align: center;">Total Responses</th>
                <th style="min-width: 320px; text-align: center;">Insights</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($questions)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No questions configured in this survey.</td></tr>
              <?php else: ?>
                <?php foreach ($questions as $idx => $q): ?>
                  <?php
                    $qId = (int)$q['question_id'];
                    $qType = $q['question_type'];
                    $stats = $questionStats[$qId] ?? null;
                    $qTotal = (int)($stats['total_responses'] ?? 0);

                    $typeDisplay = match($qType) {
                      'multiple_choice' => 'Multiple Choice',
                      'yes_no'          => 'Yes/No',
                      'rating'          => 'Rating Scale',
                      'short_answer'    => 'Description Type Answer',
                      default           => ucwords(str_replace('_', ' ', $qType))
                    };

                    $typePillStyle = match($qType) {
                      'multiple_choice' => 'background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8;',
                      'yes_no'          => 'background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399;',
                      'rating'          => 'background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24;',
                      'short_answer'    => 'background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.35); color: #c084fc;',
                      default           => ''
                    };
                  ?>
                  <tr class="summary-question-row" data-qtype="<?= e($qType) ?>" data-responses="<?= (int)$qTotal ?>" data-qtext="<?= e(strtolower($q['question'])) ?>">
                    <td style="font-weight: 700; color: var(--primary-teal); vertical-align: top; padding-top: 1rem; text-align: center;">Q<?= $idx + 1 ?></td>
                    <td style="font-weight: 600; color: var(--text-main); vertical-align: top; padding-top: 1rem; line-height: 1.45;"><?= e($q['question']) ?></td>
                    <td style="vertical-align: top; padding-top: 1rem; text-align: center;">
                      <span class="status-pill" style="<?= $typePillStyle ?>"><?= e($typeDisplay) ?></span>
                    </td>
                    <td class="text-center" style="font-weight: 700; color: var(--text-main); vertical-align: top; padding-top: 1rem; font-size: 0.95rem;">
                      <?= number_format($qTotal) ?>
                    </td>
                    <td style="vertical-align: top; padding-top: 0.85rem; padding-bottom: 0.85rem;">
                      <?php if ($qType === 'short_answer'): ?>
                        <!-- Description Type Answer Insight: Answered count out of total survey respondents -->
                        <div style="display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.5rem 0.75rem; background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 8px;">
                          <i class="fa-solid fa-pen-nib" style="color: #c084fc; font-size: 0.9rem; margin-top: 2px;"></i>
                          <div>
                            <div style="font-size: 0.84rem; font-weight: 700; color: var(--text-main);">
                              <?= number_format($qTotal) ?> of <?= number_format($totalSurveyRespondents) ?> survey respondents answered
                            </div>
                            <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                              <?= $totalSurveyRespondents > 0 ? round(($qTotal / $totalSurveyRespondents) * 100, 1) . '% completion rate among all respondents' : 'No survey responses recorded yet' ?>
                            </div>
                          </div>
                        </div>

                      <?php else: ?>
                        <!-- Choice-based Insight: Frequently selected answer & segregated 100% scale breakdown -->
                        <?php
                          $choices = $stats['choices'] ?? [];
                          $maxVotes = 0;
                          foreach ($choices as $c) {
                            $v = (int)($c['vote_count'] ?? 0);
                            if ($v > $maxVotes) {
                              $maxVotes = $v;
                            }
                          }
                        ?>
                        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                          
                          <?php if ($qType === 'rating' && !empty($stats['avg_rating'])): ?>
                            <div style="font-size: 0.78rem; font-weight: 700; color: #fbbf24; display: inline-flex; align-items: center; gap: 0.35rem;">
                              <i class="fa-solid fa-star"></i> Weighted Average: <?= $stats['avg_rating'] ?> / 5.0
                            </div>
                          <?php endif; ?>

                          <?php if ($qTotal > 0 && $maxVotes > 0): ?>
                            <?php
                              $topChoices = [];
                              foreach ($choices as $c) {
                                if ((int)($c['vote_count'] ?? 0) === $maxVotes) {
                                  $topChoices[] = '<strong>' . e($c['choice_text']) . '</strong> (' . number_format($c['percentage'], 1) . '%, ' . number_format($c['vote_count']) . ' ' . ((int)$c['vote_count'] === 1 ? 'response' : 'responses') . ')';
                                }
                              }
                            ?>
                            <div style="font-size: 0.78rem; color: var(--primary-teal); font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem; margin-bottom: 0.15rem; flex-wrap: wrap;">
                              <i class="fa-solid fa-fire text-cyan"></i> <span>Frequently Selected: <?= implode(' &bull; ', $topChoices) ?></span>
                            </div>
                          <?php endif; ?>

                          <!-- Segregated 100% scale list for choices -->
                          <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                            <?php foreach ($choices as $c): ?>
                              <?php
                                $cVotes = (int)($c['vote_count'] ?? 0);
                                $cPct = (float)($c['percentage'] ?? 0.0);
                                $isTop = ($qTotal > 0 && $maxVotes > 0 && $cVotes === $maxVotes);
                              ?>
                              <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.65rem; font-size: 0.78rem; background: <?= $isTop ? 'rgba(0, 194, 168, 0.1)' : 'rgba(255, 255, 255, 0.03)' ?>; border: 1px solid <?= $isTop ? 'rgba(0, 194, 168, 0.35)' : 'var(--bg-card-border)' ?>; border-radius: 6px; padding: 4px 8px;">
                                <div style="display: flex; align-items: center; gap: 0.4rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($c['choice_text']) ?>">
                                  <?php if ($isTop): ?>
                                    <i class="fa-solid fa-circle-check" style="color: var(--primary-teal); font-size: 0.7rem; flex-shrink: 0;"></i>
                                  <?php else: ?>
                                    <i class="fa-regular fa-circle" style="color: var(--text-dim); font-size: 0.65rem; flex-shrink: 0;"></i>
                                  <?php endif; ?>
                                  <span style="color: var(--text-main); font-weight: <?= $isTop ? '700' : '500' ?>; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= e($c['choice_text']) ?>
                                  </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; font-variant-numeric: tabular-nums;">
                                  <span style="font-weight: <?= $isTop ? '800' : '600' ?>; color: <?= $isTop ? 'var(--primary-teal)' : 'var(--text-main)' ?>;">
                                    <?= number_format($cVotes) ?> <?= $cVotes === 1 ? 'vote' : 'votes' ?>
                                  </span>
                                  <span style="background: rgba(255, 255, 255, 0.06); padding: 1px 6px; border-radius: 4px; font-size: 0.72rem; color: var(--text-muted); font-weight: 700; min-width: 48px; text-align: right;">
                                    <?= number_format($cPct, 1) ?>%
                                  </span>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          </div>

                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="summaryNoMatchesRow" style="display: none;">
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> No questions match your search or filter criteria.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ---------------------------------------------------------
         SUB-TAB 2: QUESTION BREAKDOWN
         --------------------------------------------------------- -->
    <div id="subtabQuestionContent" style="display: none;">
      <?php if (empty($questions)): ?>
        <div class="table-card text-center py-5">
          <p style="color: var(--text-muted); margin: 0;">This survey does not have any questions configured yet.</p>
        </div>
      <?php else: ?>
        <!-- Search Bar and Filter Popover for Question Breakdown -->
        <div class="side-by-side-controls mb-3" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
          <div class="search-bar-container view-search" style="flex: 1; max-width: 480px; position: relative;">
            <i class="fa-solid fa-magnifying-glass search-icon" style="position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none;"></i>
            <input type="text" class="search-input" id="breakdownQuestionSearch" placeholder="Search questions or choices in breakdown..." onkeyup="filterBreakdownQuestions()" style="width: 100%; padding-left: 2.8rem; padding-right: 1rem; height: 38px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); font-size: 0.86rem; outline: none;">
          </div>

          <div class="unified-filter-wrapper" style="position: relative; z-index: 1050;">
            <button type="button" class="btn btn-secondary filter-btn-trigger btn-sm" id="breakdownFilterBtn" onclick="toggleFilterMenu('breakdownFilterPopover', event)" style="padding: 6px 14px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.45rem;">
              <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; opacity: 0.7;"></i>
            </button>

            <div id="breakdownFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();" style="position: absolute; top: calc(100% + 8px); right: 0; width: 320px; z-index: 100000;">
              <div class="filter-popover-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--bg-card-border);">
                <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Question Breakdown</h4>
                <button type="button" class="close-popover-btn" onclick="toggleFilterMenu('breakdownFilterPopover', event)" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1;">&times;</button>
              </div>

              <div class="filter-popover-group" style="margin-bottom: 1rem;">
                <label class="filter-label" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.45rem;">Question Type:</label>
                <select id="breakdownTypeFilter" class="table-filter-select-lg" style="width: 100%; height: 42px; min-height: 42px; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); padding: 0.45rem 0.85rem; font-size: 0.88rem; line-height: 1.4; box-sizing: border-box;">
                  <option value="all">All Question Types</option>
                  <option value="multiple_choice">Multiple Choice</option>
                  <option value="yes_no">Yes/No</option>
                  <option value="rating">Rating Scale</option>
                  <option value="short_answer">Description Type Answer</option>
                </select>
              </div>

              <div class="filter-popover-footer" style="display: flex; justify-content: flex-end; padding-top: 0.75rem; border-top: 1px solid var(--bg-card-border); margin-top: 0.75rem;">
                <button type="button" class="btn btn-primary btn-sm" onclick="applyBreakdownQuestionsFilter(event)" style="padding: 6px 16px; font-size: 0.84rem; font-weight: 700; border-radius: 8px;">
                  <i class="fa-solid fa-check me-1"></i> Apply Filters
                </button>
              </div>
            </div>
          </div>
        </div>

        <div id="breakdownCardsContainer" style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2.5rem;">
          <?php foreach ($questions as $index => $q): ?>
            <?php
              $qId = (int)$q['question_id'];
              $stats = $questionStats[$qId] ?? null;
              $qNumber = $index + 1;
              $qType = $q['question_type'];
              $choiceTexts = [];
              if (!empty($stats['choices'])) {
                foreach ($stats['choices'] as $sc) {
                  $choiceTexts[] = $sc['choice_text'];
                }
              }
              $qSearchHaystack = strtolower($q['question'] . ' ' . implode(' ', $choiceTexts));

              $breakdownTypeDisplay = match($qType) {
                'multiple_choice' => 'Multiple Choice',
                'yes_no'          => 'Yes/No',
                'rating'          => 'Rating Scale',
                'short_answer'    => 'Description Type Answer',
                default           => ucwords(str_replace('_', ' ', $qType))
              };
            ?>
            <div class="table-card hover-glow-card breakdown-question-card" data-qtype="<?= e($qType) ?>" data-qtext="<?= e($qSearchHaystack) ?>" style="margin-top: 0; padding: 1.4rem 1.6rem;">
              
              <!-- Question Title & Header -->
              <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 0.65rem;">
                  <span style="background: rgba(0, 194, 168, 0.15); border: 1px solid rgba(0, 194, 168, 0.35); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 4px 10px; font-size: 0.82rem;">
                    Q<?= $qNumber ?>
                  </span>
                  <h4 style="color: var(--text-main); font-size: 1.08rem; font-weight: 700; margin: 0;">
                    <?= e($q['question']) ?>
                  </h4>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <span class="status-pill">
                    <?= e($breakdownTypeDisplay) ?>
                  </span>
                  <?php if ($qType === 'rating' && !empty($stats['avg_rating'])): ?>
                    <span class="status-pill status-default">
                      <i class="fa-solid fa-star me-1"></i> Avg: <?= $stats['avg_rating'] ?> / 5.0
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- CHOICE-BASED RESULTS -->
              <?php if ($qType !== 'short_answer'): ?>
                <?php
                  $choices = $stats['choices'] ?? [];
                  $qTotalVotes = $stats['total_responses'] ?? 0;
                ?>
                <div style="display: flex; flex-direction: column; gap: 0.85rem; padding-top: 0.35rem;">
                  <?php foreach ($choices as $c): ?>
                    <?php
                      $votes = (int)$c['vote_count'];
                      $pct = $c['percentage'];
                    ?>
                    <div>
                      <div style="display: flex; justify-content: space-between; font-size: 0.88rem; font-weight: 600; margin-bottom: 0.35rem;">
                        <span style="color: var(--text-main);"><?= e($c['choice_text']) ?></span>
                        <span style="color: var(--primary-teal); font-weight: 700;"><?= $votes ?> vote<?= $votes === 1 ? '' : 's' ?> (<?= $pct ?>%)</span>
                      </div>
                      <div style="height: 10px; background: rgba(255, 255, 255, 0.06); border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.08); overflow: hidden;">
                        <div style="width: <?= $pct ?>%; height: 100%; background: linear-gradient(90deg, #00c2a8 0%, #8b5cf6 100%); border-radius: 6px; transition: width 0.4s ease;"></div>
                      </div>
                    </div>
                  <?php endforeach; ?>

                  <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; color: var(--text-muted); padding-top: 0.75rem; border-top: 1px solid rgba(255, 255, 255, 0.05); margin-top: 0.5rem;">
                    <span><i class="fa-solid fa-chart-simple" style="color: var(--primary-teal); margin-right: 4px;"></i> Total Question Responses: <strong style="color: var(--text-main);"><?= $qTotalVotes ?></strong></span>
                    <?php if ($qType === 'yes_no' && count($choices) === 2): ?>
                      <span style="color: var(--primary-teal); font-weight: 600;">
                        <i class="fa-solid fa-scale-balanced me-1"></i> <?= $choices[0]['choice_text'] ?> (<?= $choices[0]['percentage'] ?>%) vs <?= $choices[1]['choice_text'] ?> (<?= $choices[1]['percentage'] ?>%)
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

              <!-- OPEN-ENDED SHORT ANSWER RESULTS -->
              <?php else: ?>
                <?php
                  $answers = $stats['answers'] ?? [];
                  $totalAnswers = $stats['total_responses'] ?? 0;
                ?>
                <div style="padding-top: 0.35rem;">
                  <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.65rem; letter-spacing: 0.5px;">
                    Submitted Member Responses (<?= $totalAnswers ?>):
                  </div>

                  <?php if (empty($answers)): ?>
                    <div class="structured-box text-center py-4" style="color: var(--text-muted);">
                      No text responses submitted yet for this question.
                    </div>
                  <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.65rem; max-height: 320px; overflow-y: auto;">
                      <?php foreach ($answers as $a): ?>
                        <div class="structured-box" style="padding: 0.85rem 1rem;">
                          <p style="color: var(--text-main); font-size: 0.92rem; line-height: 1.5; margin-bottom: 0.5rem;">
                            <?= nl2br(e($a['answer'])) ?>
                          </p>
                          <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.78rem; color: var(--text-muted);">
                            <span><i class="fa-solid fa-user" style="color: var(--primary-teal); margin-right: 4px;"></i> <?= e($a['firstname'] . ' ' . $a['lastname']) ?> (<strong style="color: var(--text-main);"><?= e($a['account_number']) ?></strong>)</span>
                            <span><i class="fa-solid fa-clock" style="color: var(--primary-teal); margin-right: 4px;"></i> <?= date('M d, Y · h:i A', strtotime($a['submitted_at'])) ?></span>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>

          <div id="breakdownNoMatchesCard" class="table-card text-center py-5" style="display: none;">
            <i class="fa-solid fa-magnifying-glass fs-2 mb-2 d-block opacity-40"></i>
            <p style="color: var(--text-muted); margin: 0;">No question breakdown items match your search or filter criteria.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- ---------------------------------------------------------
         SUB-TAB 3: INDIVIDUAL RESPONSES
         --------------------------------------------------------- -->
    <div id="subtabIndividualContent" style="display: none;">
      <div class="table-card" style="margin-top: 0; padding: 1.4rem 1.6rem; margin-bottom: 2rem; overflow: visible; position: relative; z-index: 50;">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <h4 style="color: var(--text-main); font-size: 1.05rem; font-weight: 700; margin-bottom: 0.2rem;">
              <i class="fa-solid fa-users" style="color: var(--primary-teal); margin-right: 6px;"></i> Individual Member Submissions
            </h4>
            <p class="section-subtitle" style="margin-bottom: 0; font-size: 0.84rem;">Review specific answers submitted by each cooperative member</p>
          </div>
        </div>

        <?php if (empty($respondents)): ?>
          <div class="structured-box text-center py-5" style="color: var(--text-muted);">
            <i class="fa-solid fa-inbox fs-2 mb-2 d-block" style="opacity: 0.4;"></i>
            No individual response sheets submitted yet for this survey.
          </div>
        <?php else: ?>
          <!-- Search Bar and Filter Popover for Individual Submissions -->
          <div class="side-by-side-controls mb-3" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div class="search-bar-container view-search" style="flex: 1; max-width: 480px; position: relative;">
              <i class="fa-solid fa-magnifying-glass search-icon" style="position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none;"></i>
              <input type="text" class="search-input" id="individualSearchInput" placeholder="Search respondents by name or account number..." onkeyup="filterIndividualRespondents()" style="width: 100%; padding-left: 2.8rem; padding-right: 1rem; height: 38px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); font-size: 0.86rem; outline: none;">
            </div>

            <div class="unified-filter-wrapper" style="position: relative; z-index: 1050;">
              <button type="button" class="btn btn-secondary filter-btn-trigger btn-sm" id="individualFilterBtn" onclick="toggleFilterMenu('individualFilterPopover', event)" style="padding: 6px 14px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.45rem;">
                <i class="fa-solid fa-filter"></i> <span>Filter</span> <i class="fa-solid fa-chevron-down" style="font-size: 0.7rem; opacity: 0.7;"></i>
              </button>

              <div id="individualFilterPopover" class="unified-filter-card hidden" onclick="event.stopPropagation();" style="position: absolute; top: calc(100% + 8px); right: 0; width: 320px; z-index: 100000;">
                <div class="filter-popover-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--bg-card-border);">
                  <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main);"><i class="fa-solid fa-sliders text-cyan me-1"></i> Filter Respondents</h4>
                  <button type="button" class="close-popover-btn" onclick="toggleFilterMenu('individualFilterPopover', event)" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; line-height: 1;">&times;</button>
                </div>

                <div class="filter-popover-group" style="margin-bottom: 1rem;">
                  <label class="filter-label" style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.45rem;">Completion Status:</label>
                  <select id="individualCompletionFilter" class="table-filter-select-lg" style="width: 100%; height: 42px; min-height: 42px; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); padding: 0.45rem 0.85rem; font-size: 0.88rem; line-height: 1.4; box-sizing: border-box;">
                    <option value="all">All Submissions</option>
                    <option value="complete">Fully Completed (<?= count($questions) ?>/<?= count($questions) ?>)</option>
                    <option value="partial">Partially Answered (&lt; <?= count($questions) ?>)</option>
                  </select>
                </div>

                <div class="filter-popover-footer" style="display: flex; justify-content: flex-end; padding-top: 0.75rem; border-top: 1px solid var(--bg-card-border); margin-top: 0.75rem;">
                  <button type="button" class="btn btn-primary btn-sm" onclick="applyIndividualRespondentsFilter(event)" style="padding: 6px 16px; font-size: 0.84rem; font-weight: 700; border-radius: 8px;">
                    <i class="fa-solid fa-check me-1"></i> Apply Filters
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="dashboard-table" id="individualRespondentsTable">
              <thead>
                <tr>
                  <th>Respondent Name</th>
                  <th>Account No.</th>
                  <th>Submission Date</th>
                  <th class="text-center">Questions Answered</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($respondents as $r): ?>
                  <?php
                    $mId = (int)$r['member_id'];
                    $fullName = $r['firstname'] . ' ' . $r['lastname'];
                    $accNo = $r['account_number'];
                    $answeredCount = (int)$r['answered_count'];
                    $isComplete = ($answeredCount >= count($questions));
                    $searchData = strtolower($fullName . ' ' . $accNo . ' ' . date('M d, Y', strtotime($r['submitted_at'])));
                  ?>
                  <tr class="individual-respondent-row" data-search="<?= e($searchData) ?>" data-completion="<?= $isComplete ? 'complete' : 'partial' ?>">
                    <td>
                      <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #00c2a8, #8b5cf6); display: flex; align-items: center; justify-content: center; color: #ffffff; font-weight: 700; font-size: 0.78rem;">
                          <?= strtoupper(substr($r['firstname'], 0, 1) . substr($r['lastname'], 0, 1)) ?>
                        </div>
                        <span style="font-weight: 600; color: var(--text-main);"><?= e($fullName) ?></span>
                      </div>
                    </td>
                    <td style="font-family: monospace; color: var(--primary-teal); font-weight: 600;"><?= e($accNo) ?></td>
                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?= date('M d, Y · h:i A', strtotime($r['submitted_at'])) ?></td>
                    <td class="text-center">
                      <span class="status-pill status-active"><?= $answeredCount ?> / <?= count($questions) ?></span>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-secondary btn-sm" onclick="viewMemberResponses(<?= $mId ?>, '<?= e(addslashes($fullName)) ?>', '<?= e(addslashes($accNo)) ?>')" style="padding: 5px 12px; font-size: 0.8rem;">
                        <i class="fa-solid fa-eye me-1"></i> View Answers
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="individualNoMatchesRow" style="display: none;">
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fa-solid fa-magnifying-glass me-1 opacity-50"></i> No member submissions match your search or filter criteria.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- =========================================================
       TAB 3: SETTINGS CONTENT
       ========================================================= -->
  <div id="tabSettingsContent" style="display: none;">
    
    <!-- Edit Survey Details Card (Matches Header Width) -->
    <div class="table-card" style="margin-top: 0; padding: 1.6rem 1.8rem; margin-bottom: 2rem;">
      <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 0.9rem; margin-bottom: 1.4rem;">
        <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-pen-to-square" style="color: var(--primary-teal);"></i> Edit Survey Details
        </h3>
        <span class="status-pill status-<?= ($survey['status'] === 'active' ? 'active' : ($survey['status'] === 'draft' ? 'default' : 'locked')) ?>">
          <?= e(ucfirst($survey['status'])) ?>
        </span>
      </div>

      <form action="survey_questions.php?id=<?= $surveyId ?>" method="POST" id="editSurveySettingsForm">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="edit_survey_settings">

        <div class="form-grid" style="gap: 1.25rem 1.5rem;">
          <!-- ROW 1: Survey Title & Survey Status -->
          <div class="form-group">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Survey Title <span style="color: #f87171;">*</span>
            </label>
            <input type="text" name="title" value="<?= e($survey['title']) ?>" placeholder="Enter survey title..." required style="height: 44px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
          </div>

          <div class="form-group">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Survey Status <span style="color: #f87171;">*</span>
            </label>
            <select name="status" id="settingsSurveyStatus" required style="height: 44px; width: 100%; box-sizing: border-box; font-weight: 500; border-radius: 10px; padding: 0.6rem 0.95rem; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
              <option value="active" <?= $survey['status'] === 'active' ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= in_array($survey['status'], ['inactive', 'closed']) ? 'selected' : '' ?>>Inactive / Closed</option>
              <option value="draft" <?= $survey['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            </select>
          </div>

          <!-- ROW 2: Description / Instructions -->
          <div class="form-group" style="grid-column: 1 / -1;">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Description / Instructions
            </label>
            <textarea name="description" rows="2" placeholder="Brief survey scope or instructions for respondents..." style="resize: vertical; min-height: 44px; line-height: 1.4; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);"><?= e($survey['description']) ?></textarea>
          </div>

          <!-- ROW 2: Opening Date & Opening Time -->
          <div class="form-group" style="min-width: 0;">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Opening Date <span style="color: #f87171;">*</span>
            </label>
            <input type="date" name="start_date" value="<?= date('Y-m-d', strtotime($survey['start_date'])) ?>" required style="height: 44px; width: 100%; box-sizing: border-box; cursor: pointer; font-weight: 500; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
          </div>

          <div class="form-group" style="min-width: 0;">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Opening Time <span style="color: #f87171;">*</span>
            </label>
            <input type="time" name="start_time" value="<?= date('H:i', strtotime($survey['start_date'])) ?>" required style="height: 44px; width: 100%; box-sizing: border-box; cursor: pointer; font-weight: 500; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
          </div>

          <!-- ROW 3: Closing Date & Closing Time -->
          <div class="form-group" style="min-width: 0;">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Closing Date <span style="color: #f87171;">*</span>
            </label>
            <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime($survey['end_date'])) ?>" required style="height: 44px; width: 100%; box-sizing: border-box; cursor: pointer; font-weight: 500; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
          </div>

          <div class="form-group" style="min-width: 0;">
            <label style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim); margin-bottom: 0.35rem; display: block;">
              Closing Time <span style="color: #f87171;">*</span>
            </label>
            <input type="time" name="end_time" value="<?= date('H:i', strtotime($survey['end_date'])) ?>" required style="height: 44px; width: 100%; box-sizing: border-box; cursor: pointer; font-weight: 500; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
          </div>
        </div>

        <!-- BOTTOM: Save Changes aligned to the bottom-right -->
        <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem; padding-top: 1.15rem; border-top: 1px solid rgba(255, 255, 255, 0.08);">
          <button type="button" class="btn btn-primary" onclick="promptConfirmEditSurveySettings()" style="padding: 9px 24px; font-weight: 700; border-radius: 8px;">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
          </button>
        </div>
      </form>
    </div>

  </div>

</div>

<?php if (!$isPublished): ?>
<!-- =========================================================
     MODAL 1: ADD QUESTION MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="addQuestionModal" class="modal-overlay hidden add-question-modal">
  <div class="modal-card add-question-card">
    <div class="modal-header">
      <h3 style="color: var(--text-main);"><i class="fa-solid fa-plus" style="color: var(--primary-teal);"></i> Add Question to Survey</h3>
    </div>
    <form action="survey_questions.php?id=<?= $surveyId ?>" method="POST" id="addQuestionForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="add_question">
      <input type="hidden" name="survey_id" value="<?= $surveyId ?>">

      <!-- Two-Column Grid on Desktop: Prompt on Left, Type on Right -->
      <div class="two-col-grid">
        <div class="form-group" style="min-width: 0;">
          <label>Question Prompt <span class="req-star">*</span></label>
          <textarea name="question" rows="3" placeholder="e.g., How satisfied are you with cooperative dividend disbursements?" required style="height: 100%; min-height: 86px; resize: vertical; line-height: 1.45;"></textarea>
        </div>

        <div class="form-group" style="min-width: 0;">
          <label>Question Type <span class="req-star">*</span></label>
          <select name="question_type" id="addQuestionType" onchange="toggleChoiceInputs('add')" style="height: 44px; cursor: pointer; font-weight: 500;">
            <option value="multiple_choice">Multiple Choice (Select from list of choices)</option>
            <option value="yes_no">Yes/No Question</option>
            <option value="rating">Rating Scale (1 to 5 Stars)</option>
            <option value="short_answer">Description Type Answer</option>
          </select>
        </div>
      </div>

      <!-- Multiple Choice Options Container -->
      <div id="addChoicesContainer" class="form-group full-width" style="margin-top: 0.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
          <label style="margin-bottom: 0;">Selectable Options <span class="req-star">*</span></label>
          <button type="button" class="btn-pill-add-opt" onclick="addChoiceRow('add')">
            <i class="fa-solid fa-plus"></i> Add Option
          </button>
        </div>
        <div id="addChoiceRows" style="display: flex; flex-direction: column; gap: 0.65rem;">
          <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="choices[]" placeholder="Choice 1 (e.g., Strongly Agree)">
          </div>
          <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="choices[]" placeholder="Choice 2 (e.g., Agree)">
          </div>
          <div style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" name="choices[]" placeholder="Choice 3 (e.g., Disagree)">
          </div>
        </div>
      </div>

      <!-- Footer Buttons -->
      <div class="modal-footer-actions">
        <button type="button" class="btn-plain-cancel" onclick="closeModal('addQuestionModal')">Cancel</button>
        <button type="submit" class="btn-gradient-save">
          <i class="fa-solid fa-check"></i> Save Question
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if (!$isPublished): ?>
<!-- =========================================================
     MODAL 2: EDIT QUESTION MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="editQuestionModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 650px;">
    <div class="modal-header">
      <h3 style="color: var(--text-main);"><i class="fa-solid fa-pen-to-square" style="color: var(--primary-teal);"></i> Edit Question</h3>
    </div>
    <form action="survey_questions.php?id=<?= $surveyId ?>" method="POST" id="editQuestionForm">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="edit_question">
      <input type="hidden" name="survey_id" value="<?= $surveyId ?>">
      <input type="hidden" name="question_id" id="editQuestionId" value="">

      <div class="form-grid" style="gap: 1.15rem;">
        <div class="form-group full-width">
          <label style="margin-bottom: 0.4rem; font-size: 0.84rem; font-weight: 600;">Question Prompt <span style="color: #f87171;">*</span></label>
          <textarea name="question" id="editQuestionText" rows="2" required></textarea>
        </div>

        <div class="form-group full-width">
          <label style="margin-bottom: 0.4rem; font-size: 0.84rem; font-weight: 600;">Question Type <span style="color: #f87171;">*</span></label>
          <select name="question_type" id="editQuestionType" onchange="toggleChoiceInputs('edit')" style="height: 44px; width: 100%; border-radius: 10px; padding: 0.6rem 0.95rem; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
            <option value="multiple_choice">Multiple Choice (Select from list of choices)</option>
            <option value="yes_no">Yes/No Question</option>
            <option value="rating">Rating Scale (1 to 5 Stars)</option>
            <option value="short_answer">Description Type Answer</option>
          </select>
        </div>

        <!-- Edit Choices Container -->
        <div id="editChoicesContainer" class="form-group full-width">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.55rem;">
            <label style="margin-bottom: 0; font-size: 0.84rem; font-weight: 600;">Selectable Options <span style="color: #f87171;">*</span></label>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addChoiceRow('edit')" style="padding: 4px 10px; font-size: 0.75rem;">
              <i class="fa-solid fa-plus"></i> Add Option
            </button>
          </div>
          <div id="editChoiceRows" style="display: flex; flex-direction: column; gap: 0.55rem;">
            <!-- Dynamic choice rows populated via JS -->
          </div>
        </div>
      </div>

      <div class="modal-actions" style="margin-top: 1.6rem; gap: 0.75rem;">
        <button type="button" class="btn btn-cancel" onclick="closeModal('editQuestionModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-floppy-disk"></i> Update Question
        </button>
      </div>
    </form>
  </div>
</div>

<!-- =========================================================
     MODAL 3: DELETE QUESTION MODAL (SET A UI MATCHED)
     ========================================================= -->
<div id="deleteQuestionModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header">
      <h3 style="color: #ef4444;"><i class="fa-solid fa-trash-can" style="color: #ef4444;"></i> Delete Question</h3>
    </div>
    <form action="survey_questions.php?id=<?= $surveyId ?>" method="POST">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="delete_question">
      <input type="hidden" name="survey_id" value="<?= $surveyId ?>">
      <input type="hidden" name="question_id" id="deleteQuestionId" value="">

      <div class="modal-body">
        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.75rem;">
          Are you sure you want to delete this question?
        </p>
        <div class="structured-box" style="margin-bottom: 0.75rem; color: var(--text-main); font-weight: 600;" id="deleteQuestionText"></div>
        <p style="color: #f87171; font-size: 0.85rem; margin: 0; background: rgba(239, 68, 68, 0.12); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          This will also remove all associated choices and submitted responses for this question.
        </p>
      </div>

      <div class="modal-actions" style="margin-top: 1.6rem; gap: 0.75rem;">
        <button type="button" class="btn btn-cancel" onclick="closeModal('deleteQuestionModal')">Cancel</button>
        <button type="submit" class="btn btn-danger">
          <i class="fa-solid fa-trash-can"></i> Delete
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- =========================================================
     MODAL 4: INDIVIDUAL MEMBER RESPONSE DRILL-DOWN MODAL
     ========================================================= -->
<div id="memberResponseModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 680px; max-height: 85vh; display: flex; flex-direction: column;">
    <div class="modal-header" style="flex-shrink: 0;">
      <div>
        <h3 style="font-size: 1.15rem; margin-bottom: 0.2rem; color: var(--text-main);"><i class="fa-solid fa-file-lines" style="color: var(--primary-teal); margin-right: 6px;"></i> Member Submission Sheet</h3>
        <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0;">
          Respondent: <strong id="modalRespondentName" style="color: var(--text-main);"></strong> (<span id="modalRespondentAccount" style="color: var(--primary-teal); font-family: monospace;"></span>)
        </p>
      </div>
    </div>

    <div class="modal-body" style="overflow-y: auto; flex: 1; padding: 1rem 0; display: flex; flex-direction: column; gap: 0.85rem;" id="modalAnswersContainer">
      <!-- Injected via JavaScript -->
    </div>

    <div class="modal-actions" style="margin-top: 1.25rem; flex-shrink: 0;">
      <button type="button" class="btn btn-primary btn-sm" onclick="closeModal('memberResponseModal')">
        <i class="fa-solid fa-check me-1"></i> Close
      </button>
    </div>
  </div>
</div>

<!-- =========================================================
     MODAL 5: DELETE SURVEY CONFIRMATION MODAL
     ========================================================= -->
<div id="deleteSurveyModal" class="modal-overlay hidden">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header">
      <h3 style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation" style="color: #ef4444;"></i> Delete Survey</h3>
    </div>
    <form action="survey_questions.php?id=<?= $surveyId ?>" method="POST">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="delete_survey">

      <div class="modal-body">
        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5; margin-bottom: 0.75rem;">
          Are you sure you want to permanently delete <strong><?= e($survey['title']) ?></strong>?
        </p>
        <p style="color: #f87171; font-size: 0.85rem; margin: 0; background: rgba(239, 68, 68, 0.12); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          This action cannot be undone. All configured questions, choices, and submitted responses will be permanently removed.
        </p>
      </div>

      <div class="modal-actions" style="margin-top: 1.6rem; display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
        <button type="button" class="btn btn-secondary btn-cancel" onclick="closeModal('deleteSurveyModal')">Cancel</button>
        <button type="submit" class="btn btn-danger" style="margin-left: auto;">
          <i class="fa-solid fa-trash-can"></i> Permanently Delete
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const memberAnswersData = <?= json_encode($memberAnswersMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const surveyQuestionsData = <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const surveyChoicesData = <?= json_encode($choicesByQuestion, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function switchQuestionnaireTab(tab) {
  const tabQuestions = document.getElementById('tabQuestionsContent');
  const tabResponses = document.getElementById('tabResponsesContent');
  const tabSettings = document.getElementById('tabSettingsContent');
  const btnQuestions = document.getElementById('btnTabQuestions');
  const btnResponses = document.getElementById('btnTabResponses');
  const btnSettings = document.getElementById('btnTabSettings');
  const headerAddBtn = document.getElementById('headerAddQuestionBtn');

  // Hide all tabs
  if (tabQuestions) tabQuestions.style.display = 'none';
  if (tabResponses) tabResponses.style.display = 'none';
  if (tabSettings) tabSettings.style.display = 'none';

  // Remove active class
  if (btnQuestions) btnQuestions.classList.remove('active-tab');
  if (btnResponses) btnResponses.classList.remove('active-tab');
  if (btnSettings) btnSettings.classList.remove('active-tab');

  if (tab === 'responses') {
    if (tabResponses) tabResponses.style.display = 'block';
    if (btnResponses) btnResponses.classList.add('active-tab');
    if (headerAddBtn) headerAddBtn.style.display = 'none';
    history.replaceState(null, null, '#responses');
  } else if (tab === 'settings') {
    if (tabSettings) tabSettings.style.display = 'block';
    if (btnSettings) btnSettings.classList.add('active-tab');
    if (headerAddBtn) headerAddBtn.style.display = 'none';
    history.replaceState(null, null, '#settings');
  } else {
    if (tabQuestions) tabQuestions.style.display = 'block';
    if (btnQuestions) btnQuestions.classList.add('active-tab');
    if (headerAddBtn) headerAddBtn.style.display = 'inline-flex';
    history.replaceState(null, null, '#questions');
  }
}

function switchResponsesSubtab(subtab) {
  const subSummary = document.getElementById('subtabSummaryContent');
  const subQuestion = document.getElementById('subtabQuestionContent');
  const subIndividual = document.getElementById('subtabIndividualContent');
  const btnSummary = document.getElementById('btnSubtabSummary');
  const btnQuestion = document.getElementById('btnSubtabQuestion');
  const btnIndividual = document.getElementById('btnSubtabIndividual');

  if (subSummary) subSummary.style.display = 'none';
  if (subQuestion) subQuestion.style.display = 'none';
  if (subIndividual) subIndividual.style.display = 'none';

  if (btnSummary) btnSummary.classList.remove('active-subtab');
  if (btnQuestion) btnQuestion.classList.remove('active-subtab');
  if (btnIndividual) btnIndividual.classList.remove('active-subtab');

  if (subtab === 'question') {
    if (subQuestion) subQuestion.style.display = 'block';
    if (btnQuestion) btnQuestion.classList.add('active-subtab');
  } else if (subtab === 'individual') {
    if (subIndividual) subIndividual.style.display = 'block';
    if (btnIndividual) btnIndividual.classList.add('active-subtab');
  } else {
    if (subSummary) subSummary.style.display = 'block';
    if (btnSummary) btnSummary.classList.add('active-subtab');
  }
}

function viewMemberResponses(memberId, memberName, accountNumber) {
  document.getElementById('modalRespondentName').textContent = memberName;
  document.getElementById('modalRespondentAccount').textContent = accountNumber;

  const container = document.getElementById('modalAnswersContainer');
  container.innerHTML = '';

  const answers = memberAnswersData[memberId] || [];

  if (answers.length === 0) {
    container.innerHTML = '<p class="text-muted text-center py-4">No recorded responses found for this member.</p>';
  } else {
    answers.forEach((ans, idx) => {
      const card = document.createElement('div');
      card.className = 'structured-box';
      card.style.padding = '0.85rem 1rem';

      const answerText = ans.choice_text || ans.answer || 'No answer provided';

      card.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
          <span style="background: rgba(0, 194, 168, 0.15); color: var(--primary-teal); font-weight: 800; border-radius: 6px; padding: 2px 8px; font-size: 0.75rem;">
            Q${idx + 1}
          </span>
          <div style="color: var(--text-main); font-weight: 700; font-size: 0.88rem;">${escapeHtml(ans.question)}</div>
        </div>
        <div style="padding: 0.6rem 0.85rem; border-radius: 6px; background: rgba(0, 194, 168, 0.08); border-left: 3px solid var(--primary-teal); color: var(--text-main); font-weight: 500; font-size: 0.9rem;">
          <i class="fa-solid fa-arrow-right" style="color: var(--primary-teal); font-size: 0.75rem; margin-right: 6px;"></i> ${escapeHtml(answerText)}
        </div>
      `;
      container.appendChild(card);
    });
  }

  openModal('memberResponseModal');
}

// Check on page load for hash or param
document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const hash = window.location.hash;
  if (urlParams.get('tab') === 'responses' || hash === '#responses' || urlParams.get('tab') === 'analytics' || hash === '#analytics') {
    switchQuestionnaireTab('responses');
  } else if (urlParams.get('tab') === 'settings' || hash === '#settings') {
    switchQuestionnaireTab('settings');
  } else {
    switchQuestionnaireTab('questions');
  }
});

function openAddQuestionModal() {
  openModal('addQuestionModal');
  toggleChoiceInputs('add');
}

function openEditQuestionModal(question, choices) {
  document.getElementById('editQuestionId').value = question.question_id;
  document.getElementById('editQuestionText').value = question.question;
  document.getElementById('editQuestionType').value = question.question_type;

  const rows = document.getElementById('editChoiceRows');
  rows.innerHTML = '';

  if (question.question_type === 'multiple_choice' && choices && choices.length > 0) {
    choices.forEach((c, idx) => {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.gap = '0.5rem';
      row.style.alignItems = 'center';
      row.innerHTML = `
        <input type="text" name="choices[]" value="${escapeHtml(c.choice_text)}" placeholder="Choice ${idx + 1}" required>
        <button type="button" class="icon-btn btn-delete-dynamic" onclick="this.parentElement.remove()" style="width: 34px; height: 34px; flex-shrink: 0;" title="Remove Option"><i class="fa-solid fa-xmark"></i></button>
      `;
      rows.appendChild(row);
    });
  } else {
    // Default 2 blank choice rows
    for (let i = 1; i <= 2; i++) {
      const row = document.createElement('div');
      row.style.display = 'flex';
      row.style.gap = '0.5rem';
      row.style.alignItems = 'center';
      row.innerHTML = `
        <input type="text" name="choices[]" placeholder="Choice ${i}">
        <button type="button" class="icon-btn btn-delete-dynamic" onclick="this.parentElement.remove()" style="width: 34px; height: 34px; flex-shrink: 0;" title="Remove Option"><i class="fa-solid fa-xmark"></i></button>
      `;
      rows.appendChild(row);
    }
  }

  toggleChoiceInputs('edit');
  openModal('editQuestionModal');
}

function openDeleteQuestionModal(id, text) {
  document.getElementById('deleteQuestionId').value = id;
  document.getElementById('deleteQuestionText').textContent = text;
  openModal('deleteQuestionModal');
}

// Delegated click listeners for Edit and Delete question buttons
document.addEventListener('click', function (e) {
  const editBtn = e.target.closest('.js-edit-question-btn');
  if (editBtn) {
    const qId = parseInt(editBtn.getAttribute('data-question-id'), 10);
    const q = Array.isArray(surveyQuestionsData) ? surveyQuestionsData.find(item => parseInt(item.question_id, 10) === qId) : null;
    const choices = (surveyChoicesData && surveyChoicesData[qId]) ? surveyChoicesData[qId] : [];
    if (q) {
      openEditQuestionModal(q, choices);
    }
    return;
  }

  const deleteBtn = e.target.closest('.js-delete-question-btn');
  if (deleteBtn) {
    const qId = parseInt(deleteBtn.getAttribute('data-question-id'), 10);
    const q = Array.isArray(surveyQuestionsData) ? surveyQuestionsData.find(item => parseInt(item.question_id, 10) === qId) : null;
    if (q) {
      openDeleteQuestionModal(qId, q.question);
    }
    return;
  }
});

function toggleChoiceInputs(mode) {
  const typeSelect = document.getElementById(mode === 'add' ? 'addQuestionType' : 'editQuestionType');
  const container = document.getElementById(mode === 'add' ? 'addChoicesContainer' : 'editChoicesContainer');
  const val = typeSelect.value;

  if (val === 'multiple_choice') {
    container.style.display = 'flex';
  } else {
    container.style.display = 'none';
  }
}

function addChoiceRow(mode) {
  const rows = document.getElementById(mode === 'add' ? 'addChoiceRows' : 'editChoiceRows');
  const idx = rows.children.length + 1;
  const row = document.createElement('div');
  row.style.display = 'flex';
  row.style.gap = '0.5rem';
  row.style.alignItems = 'center';
  row.innerHTML = `
    <input type="text" name="choices[]" placeholder="Choice ${idx}" required>
    <button type="button" class="icon-btn btn-delete-dynamic" onclick="this.parentElement.remove()" style="width: 34px; height: 34px; flex-shrink: 0;" title="Remove Option"><i class="fa-solid fa-xmark"></i></button>
  `;
  rows.appendChild(row);
}

function escapeHtml(text) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

function updateStatusDot(val) {
  const dot = document.getElementById('statusSelectDot');
  if (!dot) return;
  if (val === 'active') {
    dot.style.background = '#00c2a8';
    dot.style.boxShadow = '0 0 8px rgba(0, 194, 168, 0.65)';
  } else if (val === 'draft') {
    dot.style.background = '#94a3b8';
    dot.style.boxShadow = 'none';
  } else if (val === 'inactive') {
    dot.style.background = '#f59e0b';
    dot.style.boxShadow = 'none';
  } else if (val === 'closed') {
    dot.style.background = '#ef4444';
    dot.style.boxShadow = 'none';
  }
}

// Filter Popover Toggling
function toggleFilterMenu(popoverId, event) {
  if (event) event.stopPropagation();
  const target = document.getElementById(popoverId);
  const allPopovers = document.querySelectorAll('.unified-filter-card');
  allPopovers.forEach(p => {
    if (p.id !== popoverId) p.classList.add('hidden');
  });
  if (target) {
    target.classList.toggle('hidden');
  }
}

function applySummaryQuestionsFilter(event) {
  if (event) event.stopPropagation();
  const target = document.getElementById('summaryFilterPopover');
  if (target) target.classList.add('hidden');
  if (typeof filterSummaryQuestions === 'function') filterSummaryQuestions();
}

function applyBreakdownQuestionsFilter(event) {
  if (event) event.stopPropagation();
  const target = document.getElementById('breakdownFilterPopover');
  if (target) target.classList.add('hidden');
  if (typeof filterBreakdownQuestions === 'function') filterBreakdownQuestions();
}

function applyIndividualRespondentsFilter(event) {
  if (event) event.stopPropagation();
  const target = document.getElementById('individualFilterPopover');
  if (target) target.classList.add('hidden');
  if (typeof filterIndividualRespondents === 'function') filterIndividualRespondents();
}

document.addEventListener('click', function(e) {
  if (!e.target.closest('.unified-filter-wrapper')) {
    document.querySelectorAll('.unified-filter-card').forEach(p => p.classList.add('hidden'));
  }
});

// Live Filtering: Summary Sub-Tab Questions
function filterSummaryQuestions() {
  const searchInput = document.getElementById('summaryQuestionSearch');
  const typeFilter = document.getElementById('summaryTypeFilter');
  const statusFilter = document.getElementById('summaryStatusFilter');
  if (!searchInput || !typeFilter || !statusFilter) return;

  const query = searchInput.value.toLowerCase().trim();
  const selectedType = typeFilter.value;
  const selectedStatus = statusFilter.value;

  const rows = document.querySelectorAll('#summaryQuestionsTable tbody tr.summary-question-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const qText = row.getAttribute('data-qtext') || '';
    const qType = row.getAttribute('data-qtype') || '';
    const responses = parseInt(row.getAttribute('data-responses') || '0', 10);

    const matchesSearch = query === '' || qText.includes(query) || row.textContent.toLowerCase().includes(query);
    const matchesType = (selectedType === 'all' || qType === selectedType);
    let matchesStatus = true;
    if (selectedStatus === 'has_responses') {
      matchesStatus = (responses > 0);
    } else if (selectedStatus === 'zero_responses') {
      matchesStatus = (responses === 0);
    }

    if (matchesSearch && matchesType && matchesStatus) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noMatchesRow = document.getElementById('summaryNoMatchesRow');
  if (noMatchesRow) {
    noMatchesRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
  }
}

// Live Filtering: Question Breakdown Cards
function filterBreakdownQuestions() {
  const searchInput = document.getElementById('breakdownQuestionSearch');
  const typeFilter = document.getElementById('breakdownTypeFilter');
  if (!searchInput || !typeFilter) return;

  const query = searchInput.value.toLowerCase().trim();
  const selectedType = typeFilter.value;

  const cards = document.querySelectorAll('.breakdown-question-card');
  let visibleCount = 0;

  cards.forEach(card => {
    const qText = card.getAttribute('data-qtext') || '';
    const qType = card.getAttribute('data-qtype') || '';

    const matchesSearch = query === '' || qText.includes(query) || card.textContent.toLowerCase().includes(query);
    const matchesType = (selectedType === 'all' || qType === selectedType);

    if (matchesSearch && matchesType) {
      card.style.display = '';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });

  const noMatchesCard = document.getElementById('breakdownNoMatchesCard');
  if (noMatchesCard) {
    noMatchesCard.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
  }
}

// Live Filtering: Individual Respondents Table
function filterIndividualRespondents() {
  const searchInput = document.getElementById('individualSearchInput');
  const completionFilter = document.getElementById('individualCompletionFilter');
  if (!searchInput || !completionFilter) return;

  const query = searchInput.value.toLowerCase().trim();
  const selectedCompletion = completionFilter.value;

  const rows = document.querySelectorAll('#individualRespondentsTable tbody tr.individual-respondent-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const searchData = row.getAttribute('data-search') || '';
    const completion = row.getAttribute('data-completion') || '';

    const matchesSearch = query === '' || searchData.includes(query) || row.textContent.toLowerCase().includes(query);
    const matchesCompletion = (selectedCompletion === 'all' || completion === selectedCompletion);

    if (matchesSearch && matchesCompletion) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const noMatchesRow = document.getElementById('individualNoMatchesRow');
  if (noMatchesRow) {
    noMatchesRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
  }
}

function validateSurveySchedule(form) {
  const startDateInput = form.querySelector('[name="start_date"]');
  const startTimeInput = form.querySelector('[name="start_time"]');
  const endDateInput = form.querySelector('[name="end_date"]');
  const endTimeInput = form.querySelector('[name="end_time"]');

  if (!startDateInput || !endDateInput) return true;

  const startDateVal = startDateInput.value;
  const startTimeVal = startTimeInput ? (startTimeInput.value || '00:00') : '00:00';
  const endDateVal = endDateInput.value;
  const endTimeVal = endTimeInput ? (endTimeInput.value || '23:59') : '23:59';

  const startDateTime = new Date(`${startDateVal}T${startTimeVal}`);
  const endDateTime = new Date(`${endDateVal}T${endTimeVal}`);

  if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
    showToast('Please enter valid dates and times.', 'warning', 'Invalid Date or Time');
    return false;
  }

  if (endDateTime <= startDateTime) {
    showToast('The closing date and time must be logically after the starting date and time.', 'error', 'Invalid Schedule');
    endDateInput.focus();
    return false;
  }

  return true;
}

function promptConfirmEditSurveySettings() {
  const form = document.getElementById('editSurveySettingsForm');
  if (!form) return;

  const titleInput = form.querySelector('[name="title"]');
  if (titleInput && !titleInput.value.trim()) {
    showToast('Survey title is required.', 'warning', 'Validation Warning');
    titleInput.focus();
    return;
  }

  if (typeof validateSurveySchedule === 'function') {
    if (!validateSurveySchedule(form)) return;
  }

  const title = titleInput ? titleInput.value.trim() : 'this survey';
  const statusSelect = form.querySelector('[name="status"]');
  const statusText = statusSelect ? statusSelect.options[statusSelect.selectedIndex].text : 'Active';

  showSystemConfirm({
    title: 'Confirm Save Changes',
    message: `Are you sure you want to save changes to <strong>"${title}"</strong>?<br><span style="color: var(--text-dim); font-size: 0.86rem; display: block; margin-top: 0.45rem;">Status will be updated to <strong style="color: var(--primary-teal);">${statusText}</strong> and all modified details will take effect immediately.</span>`,
    confirmText: 'Yes, Save Changes',
    cancelText: 'Cancel',
    icon: 'fa-solid fa-floppy-disk',
    iconColor: 'var(--primary-teal)',
    isDanger: false,
    onConfirm: () => {
      form.submit();
    }
  });
}
</script>

<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>

