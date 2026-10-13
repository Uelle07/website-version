<!-- Centered Upload Photo Layout Wrapper -->
<div style="max-width: 660px; margin: 0 auto 50px auto;">
    <!-- Back to Dashboard Navigation Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Error Display Container -->
    
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
                    <img id="photoLivePreview" src="http://localhost/uploads/photo_2_photo_1789558552.png" alt="Current Photo"
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
                <input type="hidden" name="csrf_token" value="a96b50bc18ebb730f6b088c6d17ff14532ccdba9b817c5ee71a826aa19f11059">

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