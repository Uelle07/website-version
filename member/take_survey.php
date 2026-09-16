<?php
/**
 * member/take_survey.php
 * -------------------------------------------------------
 * MODULE (SET B) — Take Member Survey
 * Renders the questionnaire form with dynamic question types
 * (multiple choice, yes/no, rating, short answer), validates responses,
 * prevents duplicate submissions, and records answers.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_member();

$memberId = current_user_id();
$surveyId = (int) ($_GET['id'] ?? $_POST['survey_id'] ?? 0);

if ($surveyId <= 0) {
    set_flash('error', 'Invalid survey requested.');
    redirect('member/surveys.php');
}

// 1. Fetch Survey Details
$stmt = $pdo->prepare(
    "SELECT * FROM surveys 
     WHERE survey_id = ? 
     LIMIT 1"
);
$stmt->execute([$surveyId]);
$survey = $stmt->fetch();

if (!$survey) {
    set_flash('error', 'The requested survey could not be found.');
    redirect('member/surveys.php');
}

// 2. Validate Active Status & Date Window
$isActive = is_survey_active($survey);

if (!$isActive) {
    set_flash('warning', 'This survey is currently closed or not active.');
    redirect('member/surveys.php');
}

// 3. Prevent Duplicate Submissions Check
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM responses WHERE survey_id = ? AND member_id = ?");
$checkStmt->execute([$surveyId, $memberId]);
$hasSubmitted = (int) $checkStmt->fetchColumn() > 0;

if ($hasSubmitted) {
    set_flash('info', 'You have already submitted your response for this survey. Thank you!');
    redirect('member/surveys.php');
}

// 4. Fetch Questions & Choices
$qStmt = $pdo->prepare(
    "SELECT * FROM survey_questions 
     WHERE survey_id = ? 
     ORDER BY question_order ASC, question_id ASC"
);
$qStmt->execute([$surveyId]);
$questions = $qStmt->fetchAll();

if (empty($questions)) {
    set_flash('warning', 'This survey does not have any questions yet.');
    redirect('member/surveys.php');
}

// Map Question IDs & Fetch Choices
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
    $allChoices = $cStmt->fetchAll();

    foreach ($allChoices as $choice) {
        $choicesByQuestion[$choice['question_id']][] = $choice;
    }
}

$errors = [];

// 5. Process Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    // Re-verify duplicate check to prevent race conditions
    $checkStmt->execute([$surveyId, $memberId]);
    if ((int) $checkStmt->fetchColumn() > 0) {
        set_flash('info', 'You have already submitted your response for this survey.');
        redirect('member/surveys.php');
    }

    $submittedAnswers = $_POST['answers'] ?? [];
    $validResponsesToInsert = [];

    // Validate each question
    foreach ($questions as $q) {
        $qId = $q['question_id'];
        $qType = $q['question_type'];
        $val = $submittedAnswers[$qId] ?? null;

        if ($qType === 'short_answer') {
            $textAnswer = trim((string) $val);
            if ($textAnswer === '') {
                $errors[] = "Please provide an answer for Question #" . $q['question_order'] . ".";
            } else {
                $validResponsesToInsert[] = [
                    'question_id' => $qId,
                    'choice_id'   => null,
                    'answer'      => $textAnswer,
                ];
            }
        } else {
            // multiple_choice, yes_no, rating
            $choiceId = (int) $val;
            $allowedChoices = $choicesByQuestion[$qId] ?? [];
            $validChoiceIds = array_column($allowedChoices, 'choice_id');

            if ($choiceId <= 0 || !in_array($choiceId, $validChoiceIds, true)) {
                $errors[] = "Please select an option for Question #" . $q['question_order'] . ".";
            } else {
                // Find choice text for answer string reference
                $choiceText = '';
                foreach ($allowedChoices as $c) {
                    if ($c['choice_id'] === $choiceId) {
                        $choiceText = $c['choice_text'];
                        break;
                    }
                }

                $validResponsesToInsert[] = [
                    'question_id' => $qId,
                    'choice_id'   => $choiceId,
                    'answer'      => $choiceText,
                ];
            }
        }
    }

    // Save if no validation errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $insertStmt = $pdo->prepare(
                "INSERT INTO responses (survey_id, question_id, member_id, choice_id, answer, submitted_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );

            foreach ($validResponsesToInsert as $resp) {
                $insertStmt->execute([
                    $surveyId,
                    $resp['question_id'],
                    $memberId,
                    $resp['choice_id'],
                    $resp['answer']
                ]);
            }

            // Recalculate survey_results statistics for choice-based questions in this survey
            $recalcStmt = $pdo->prepare(
                "SELECT sq.question_id, sc.choice_id,
                        (SELECT COUNT(*) FROM responses r WHERE r.question_id = sq.question_id AND r.choice_id = sc.choice_id) AS choice_count,
                        (SELECT COUNT(*) FROM responses r WHERE r.question_id = sq.question_id) AS total_question_responses
                 FROM survey_questions sq
                 INNER JOIN survey_choices sc ON sc.question_id = sq.question_id
                 WHERE sq.survey_id = ?"
            );
            $recalcStmt->execute([$surveyId]);
            $recalcRows = $recalcStmt->fetchAll();

            $upsertResult = $pdo->prepare(
                "INSERT INTO survey_results (survey_id, question_id, choice_id, response_count, percentage)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE response_count = VALUES(response_count), percentage = VALUES(percentage)"
            );

            foreach ($recalcRows as $row) {
                $count = (int) $row['choice_count'];
                $total = (int) $row['total_question_responses'];
                $pct   = $total > 0 ? round(($count / $total) * 100, 2) : 0.00;

                $upsertResult->execute([
                    $surveyId,
                    $row['question_id'],
                    $row['choice_id'],
                    $count,
                    $pct
                ]);
            }

            $pdo->commit();

            redirect('member/survey_success.php?id=' . $surveyId);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Failed to record your survey submission: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Take Survey · ' . $survey['title'];
require_once __DIR__ . '/../pages/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="surveys.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Back to Surveys
        </a>
    </div>

    <!-- Survey Hero Header Card -->
    <div class="hero-card" style="margin-bottom: 28px;">
        <div class="hero-profile">
            <div class="profile-avatar-wrapper" style="width: 72px; height: 72px;">
                <div style="width: 100%; height: 100%; border-radius: 50%; background: rgba(0, 194, 168, 0.15); border: 3px solid var(--primary-teal); display: flex; align-items: center; justify-content: center; color: var(--primary-teal); font-size: 1.8rem; box-shadow: 0 6px 16px rgba(0, 194, 168, 0.3);">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
            </div>
            <div class="hero-info">
                <div class="acc-number">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>ACTIVE MEMBER SURVEY</span>
                </div>
                <h2><?= e($survey['title']) ?></h2>
                <div class="meta-details">
                    <span><i class="fa-solid fa-calendar-days"></i> Closes: <?= date('F d, Y g:i A', strtotime($survey['end_date'])) ?></span>
                    <span><i class="fa-solid fa-list-check"></i> <?= count($questions) ?> Question<?= count($questions) === 1 ? '' : 's' ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($survey['description'])): ?>
        <div class="card mb-4" style="background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; padding: 1.25rem 1.5rem; box-shadow: 0 8px 24px rgba(0,0,0,0.35);">
            <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--primary-teal); letter-spacing: 0.5px; margin-bottom: 0.35rem;">
                <i class="fa-solid fa-circle-info me-1"></i> Survey Instructions:
            </div>
            <p style="color: var(--text-dim); font-size: 0.92rem; line-height: 1.6; margin: 0;">
                <?= nl2br(e($survey['description'])) ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Validation Errors Banner -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; border-radius: 12px; padding: 1rem 1.25rem;">
            <div style="font-weight: 700; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-exclamation" style="color: #ef4444;"></i> Please resolve the following required fields:
            </div>
            <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Survey Questions Form -->
    <form action="take_survey.php?id=<?= $surveyId ?>" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="survey_id" value="<?= $surveyId ?>">

        <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem;">
            <?php foreach ($questions as $index => $q): ?>
                <?php 
                    $qId = (int)$q['question_id'];
                    $qChoices = $choicesByQuestion[$qId] ?? [];
                    $qNumber = $index + 1;
                    $submittedVal = $_POST['answers'][$qId] ?? '';
                ?>
                <div class="card profile-container" style="background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; padding: 1.5rem; box-shadow: 0 8px 24px rgba(0,0,0,0.4);">
                    <!-- Question Header -->
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1.25rem;">
                        <span style="background: var(--badge-bg); border: 1px solid var(--badge-border); color: var(--primary-teal); font-weight: 800; border-radius: 8px; padding: 4px 10px; font-size: 0.85rem; flex-shrink: 0; margin-top: 2px;">
                            Q<?= $qNumber ?>
                        </span>
                        <h4 style="color: var(--text-main); font-size: 1.12rem; font-weight: 700; margin: 0; line-height: 1.45;">
                            <?= e($q['question']) ?>
                            <span style="color: #ff6b6b;" title="Required">*</span>
                        </h4>
                    </div>

                    <!-- Question Inputs by Type -->
                    <div style="padding-left: 0.25rem;">
                        <?php if ($q['question_type'] === 'multiple_choice'): ?>
                            <!-- MULTIPLE CHOICE (Radio buttons) -->
                            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                                <?php foreach ($qChoices as $choice): ?>
                                    <?php $choiceChecked = ($submittedVal == $choice['choice_id']); ?>
                                    <label style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1.15rem; border-radius: 10px; background: var(--input-bg); border: 1px solid <?= $choiceChecked ? 'var(--primary-teal)' : 'var(--bg-card-border)' ?>; cursor: pointer; transition: all 0.2s ease;">
                                        <input type="radio" name="answers[<?= $qId ?>]" value="<?= $choice['choice_id'] ?>" <?= $choiceChecked ? 'checked' : '' ?> style="accent-color: var(--primary-teal); width: 18px; height: 18px;" required>
                                        <span style="color: var(--text-main); font-size: 0.95rem; font-weight: 500;">
                                            <?= e($choice['choice_text']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($q['question_type'] === 'yes_no'): ?>
                            <!-- YES / NO (Two options grid) -->
                            <div class="row g-3">
                                <?php foreach ($qChoices as $choice): ?>
                                    <?php 
                                        $choiceChecked = ($submittedVal == $choice['choice_id']); 
                                        $isYes = stripos($choice['choice_text'], 'yes') !== false;
                                    ?>
                                    <div class="col-sm-6">
                                        <label style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 1rem; border-radius: 10px; background: var(--input-bg); border: 1px solid <?= $choiceChecked ? 'var(--primary-teal)' : 'var(--bg-card-border)' ?>; cursor: pointer; transition: all 0.2s ease; width: 100%;">
                                            <input type="radio" name="answers[<?= $qId ?>]" value="<?= $choice['choice_id'] ?>" <?= $choiceChecked ? 'checked' : '' ?> style="accent-color: var(--primary-teal); width: 18px; height: 18px;" required>
                                            <span style="color: var(--text-main); font-weight: 700; font-size: 1rem;">
                                                <i class="<?= $isYes ? 'fa-solid fa-thumbs-up text-success' : 'fa-solid fa-thumbs-down text-danger' ?> me-1"></i>
                                                <?= e($choice['choice_text']) ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($q['question_type'] === 'rating'): ?>
                            <!-- RATING SCALE (1 to 5 Stars) -->
                            <div class="row g-2">
                                <?php foreach ($qChoices as $choice): ?>
                                    <?php $choiceChecked = ($submittedVal == $choice['choice_id']); ?>
                                    <div class="col">
                                        <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 0.85rem 0.5rem; border-radius: 10px; background: var(--input-bg); border: 1px solid <?= $choiceChecked ? 'var(--primary-teal)' : 'var(--bg-card-border)' ?>; cursor: pointer; transition: all 0.2s ease; height: 100%;">
                                            <input type="radio" name="answers[<?= $qId ?>]" value="<?= $choice['choice_id'] ?>" <?= $choiceChecked ? 'checked' : '' ?> style="accent-color: var(--primary-teal); width: 16px; height: 16px; margin-bottom: 6px;" required>
                                            <div style="color: #fbbf24; font-size: 1.15rem; margin-bottom: 4px;">
                                                <i class="fa-solid fa-star"></i>
                                            </div>
                                            <span style="color: var(--text-main); font-size: 0.82rem; font-weight: 600;">
                                                <?= e($choice['choice_text']) ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($q['question_type'] === 'short_answer'): ?>
                            <!-- SHORT ANSWER (Textarea) -->
                            <div class="form-group">
                                <textarea name="answers[<?= $qId ?>]" class="form-control" rows="4" placeholder="Type your answer here..." style="background: var(--input-bg); color: var(--text-main); border: 1px solid var(--bg-card-border); border-radius: 10px; padding: 12px 14px; font-size: 0.95rem;" required><?= e($submittedVal) ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Submit Action Bar -->
        <div class="card p-4 mb-5" style="background: var(--bg-card); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border: 1px solid var(--bg-card-border); border-radius: 16px; box-shadow: var(--card-shadow);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div style="color: var(--text-dim); font-size: 0.85rem;">
                    <i class="fa-solid fa-shield-halved" style="color: var(--primary-teal); margin-right: 4px;"></i>
                    Responses are tied securely to your verified member profile.
                </div>
                <div class="d-flex gap-3">
                    <a href="surveys.php" class="btn btn-outline-light btn-sm px-4" style="border-radius: 10px; font-weight: 600; padding: 10px 20px;">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 d-inline-flex align-items-center gap-2" style="border-radius: 10px; font-weight: 700; padding: 10px 24px; box-shadow: 0 4px 15px rgba(0, 194, 168, 0.25);">
                        <i class="fa-solid fa-paper-plane"></i> Submit Survey
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>

