<?php
/**
 * member/upload_photo.php
 * -------------------------------------------------------
 * Dedicated "Upload Photo" page allowing members to update 
 * their profile/passport photo.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
require_member();

// Get current member ID from session
$memberId = current_user_id();

// Fetch current member photo record from database
$stmt = $pdo->prepare("SELECT passport_photo FROM member WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

// Redirect if member record does not exist
if (!$member) {
    set_flash('error', 'Member record not found.');
    redirect('member/dashboard.php');
}

$errors = [];

// Handle form submission for photo uploading
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token for security
    verify_csrf($_POST['csrf_token'] ?? '');

    // Process the uploaded photo file using helper function
    $result = handle_photo_upload($_FILES['passport_photo'] ?? null, $memberId);

    if ($result['success']) {
        // Update database with the new photo filename
        $update = $pdo->prepare("UPDATE member SET passport_photo = ? WHERE member_id = ?");
        $update->execute([$result['filename'], $memberId]);

        // Delete old photo file if it exists
        if (!empty($member['passport_photo'])) {
            delete_uploaded_photo($member['passport_photo']);
        }

        // Log the photo update action into history table
        $hist = $pdo->prepare(
            "INSERT INTO update_history (member_id, updated_by_member_id, update_type, remarks)
             VALUES (?, ?, 'photo_upload', 'Member uploaded a new passport photo.')"
        );
        $hist->execute([$memberId, $memberId]);

        // Set success message and redirect to dashboard
        set_flash('success', 'Photo updated successfully.');
        redirect('member/dashboard.php');
    } else {
        // Capture upload error messages
        $errors[] = $result['error'];
    }
}

$pageTitle = 'Upload Photo';
require_once __DIR__ . '/../pages/header.php';
?>

<!-- Centered Upload Photo Layout Wrapper -->
<div style="max-width: 660px; margin: 0 auto 50px auto;">
    <!-- Back to Dashboard Navigation Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Error Display Container -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <div class="d-flex align-items-center gap-2 mb-2 fw-bold">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Upload Failed:</span>
            </div>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Photo Upload Form Card -->
    <div class="card p-4 p-md-5">
        <div class="card-body p-0">
            <!-- Card Header Section -->
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--badge-bg); border: 1px solid var(--badge-border); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--primary-teal); box-shadow: 0 4px 16px var(--accent-glow);">
                    <i class="fa-solid fa-camera"></i>
                </div>
                <div>
                    <h3 class="mb-1" style="font-weight: 800;">Upload Profile Photo</h3>
                    <p class="mb-0 text-muted" style="color: var(--text-dim) !important; font-size: 0.9rem;">
                        Upload or replace your official 2x2 / passport-size member photo.
                    </p>
                </div>
            </div>

            <!-- Profile Photo Preview Section -->
            <div class="text-center my-4 py-2">
                <div style="position: relative; display: inline-block;">
                    <img id="photoLivePreview" src="<?= e(photo_url($member['passport_photo'])) ?>" alt="Current Photo"
                         style="width: 140px; height: 140px; object-fit: cover; border-radius: 20px; border: 3.5px solid var(--primary-teal); box-shadow: 0 8px 24px var(--accent-glow); background: #ffffff;">
                    <div style="position: absolute; bottom: -8px; right: -8px; width: 34px; height: 34px; border-radius: 50%; background: var(--accent-gradient); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; border: 2.5px solid var(--bg-dark); box-shadow: 0 2px 8px rgba(0,0,0,0.4);">
                        <i class="fa-solid fa-image"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted" style="color: var(--text-dim) !important; font-size: 0.84rem;">
                    Active Passport Photo on Record
                </div>
            </div>

            <!-- Upload Form -->
            <form id="photoUploadForm" method="post" enctype="multipart/form-data" onsubmit="return validatePhotoUpload(event)">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="mb-4">
                    <label class="form-label d-flex align-items-center gap-2" for="passport_photo_input">
                        <i class="fa-solid fa-cloud-arrow-up text-cyan" style="font-size: 0.85rem;"></i>
                        <span>Select New Photo File</span>
                    </label>
                    <input type="file" class="form-control" id="passport_photo_input" name="passport_photo" accept="image/jpeg,image/png" data-preview="#photoLivePreview">
                    <div class="form-text" style="color: var(--text-dim); font-size: 0.82rem; margin-top: 6px;">
                        <i class="fa-solid fa-circle-info me-1"></i> Accepted formats: JPG, JPEG, PNG. Maximum file size: 2 MB.
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 pt-2">
                    <button type="submit" class="btn btn-primary px-4 py-2" style="font-weight: 700;">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Photo
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 12px; font-weight: 600; border-color: rgba(255,255,255,0.2);">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function validatePhotoUpload(e) {
    const fileInput = document.getElementById('passport_photo_input');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0 || !fileInput.value) {
        e.preventDefault();
        showToast('Please select a photo file to proceed with your upload.', 'warning');
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>