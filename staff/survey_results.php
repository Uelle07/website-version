<?php
/**
 * staff/survey_results.php
 * -------------------------------------------------------
 * Staff Portal — Survey Results & Analytics Dashboard (SET B)
 * [CONSOLIDATED]: This route has been consolidated into the unified
 * Questionnaire Management "Responses" tab (staff/survey_questions.php#responses).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_staff();

$surveyId = (int)($_GET['id'] ?? 0);

if ($surveyId > 0) {
    redirect("staff/survey_questions.php?id={$surveyId}#responses");
} else {
    // If no ID specified, redirect to the first available survey or to survey management
    $firstSurvey = $pdo->query("SELECT survey_id FROM surveys ORDER BY survey_id DESC LIMIT 1")->fetch();
    if ($firstSurvey) {
        redirect("staff/survey_questions.php?id=" . (int)$firstSurvey['survey_id'] . "#responses");
    } else {
        redirect('staff/surveys.php');
    }
}
