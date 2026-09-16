<?php
/**
 * member/survey_success.php
 * -------------------------------------------------------
 * MODULE (SET B) — Survey Submission Confirmation
 * Displays a clean, professional confirmation receipt after
 * a member successfully completes a survey questionnaire.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_member();

$memberId = current_user_id();
$surveyId = (int) ($_GET['id'] ?? 0);

// Fetch survey submission receipt
$stmt = $pdo->prepare(
    "SELECT s.title, s.description, MAX(r.submitted_at) AS submitted_at, COUNT(DISTINCT r.question_id) AS total_answered
     FROM surveys s
     INNER JOIN responses r ON r.survey_id = s.survey_id AND r.member_id = ?
     WHERE s.survey_id = ?
     GROUP BY s.survey_id"
);
$stmt->execute([$memberId, $surveyId]);
$receipt = $stmt->fetch();

$pageTitle = 'Survey Submitted Successfully';
require_once __DIR__ . '/../pages/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Top Action Navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Success Confirmation Container -->
    <div class="row justify-content-center py-3">
        <div class="col-md-9 col-lg-7">
            <div class="card profile-container text-center p-4 p-md-5" style="background: var(--bg-card); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--bg-card-border); border-radius: 20px; box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);">
                <div class="card-body p-0">
                    <!-- Success Check Icon Badge -->
                    <div class="mx-auto mb-4" style="width: 80px; height: 80px; border-radius: 50%; background: rgba(0, 194, 168, 0.15); border: 2.5px solid var(--primary-teal); display: flex; align-items: center; justify-content: center; color: var(--primary-teal); font-size: 2.5rem; box-shadow: 0 0 25px rgba(0, 194, 168, 0.35);">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <h2 style="color: var(--text-main); font-weight: 800; font-size: 1.65rem; margin-bottom: 0.5rem; letter-spacing: -0.3px;">
                        Survey Submitted Successfully
                    </h2>

                    <p style="color: var(--text-dim); font-size: 0.95rem; line-height: 1.6; max-width: 480px; margin: 0 auto 1.75rem auto;">
                        Thank you for your valuable participation. Your feedback has been securely registered and recorded into the cooperative system.
                    </p>

                    <?php if ($receipt): ?>
                        <div class="structured-box text-start mb-4" style="background: var(--input-bg); border: 1px solid var(--bg-card-border); border-radius: 12px; padding: 1.25rem 1.5rem;">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
                                <span style="color: var(--text-dim); font-size: 0.85rem;">Survey Title:</span>
                                <span style="color: var(--text-main); font-weight: 700; font-size: 0.9rem; max-width: 280px; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($receipt['title']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
                                <span style="color: var(--text-dim); font-size: 0.85rem;">Completed Items:</span>
                                <span style="color: var(--primary-teal); font-weight: 700; font-size: 0.88rem;">
                                    <i class="fa-solid fa-list-check me-1"></i> <?= (int)$receipt['total_answered'] ?> Questions
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
                                <span style="color: var(--text-dim); font-size: 0.85rem;">Submission Time:</span>
                                <span style="color: var(--text-main); font-size: 0.85rem;">
                                    <i class="fa-solid fa-clock me-1" style="color: var(--primary-teal);"></i> <?= date('M d, Y · h:i A', strtotime($receipt['submitted_at'])) ?>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="color: var(--text-dim); font-size: 0.85rem;">Record Status:</span>
                                <span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 20px; padding: 3px 10px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-shield-halved"></i> Verified &amp; Saved
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="surveys.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 12px; font-weight: 600; background: var(--input-bg); border-color: var(--bg-card-border); font-size: 0.9rem;">
                            <i class="fa-solid fa-clipboard-question me-1"></i> Available Surveys
                        </a>
                        <a href="dashboard.php" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 12px; font-weight: 700; font-size: 0.9rem; box-shadow: 0 4px 15px rgba(0, 194, 168, 0.25);">
                            <i class="fa-solid fa-gauge-high"></i> Member Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>
