<?php
/**
 * member/upload_photo.php
 * -------------------------------------------------------
 * Dedicated "Upload Photo" page allowing members to update 
 * their profile/passport photo.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

if (isset($_GET['ajax_upload'])) {
    header('Content-Type: application/json');
    $memberId = $_POST['member_id'] ?? 'unknown';
    $result = handle_photo_upload($_FILES['passport_photo'] ?? null, $memberId);
    echo json_encode($result);
    exit;
}

$pageTitle = 'Upload Photo';
require_once __DIR__ . '/../pages/header.php';
?>

<!-- Centered Upload Photo Layout Wrapper -->
<div style="max-width: 660px; margin: 0 auto 50px auto; display: none;" id="mainContent">
    <!-- Back to Dashboard Navigation Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Error Display Container -->
    <div id="errorBanner" class="alert alert-danger shadow-sm mb-4" style="display: none;">
        <div class="d-flex align-items-center gap-2 mb-2 fw-bold">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Upload Failed:</span>
        </div>
        <ul id="errorList" class="mb-0 ps-3">
        </ul>
    </div>

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
                    <img id="photoLivePreview" src="../assets/images/default-avatar.png" alt="Current Photo"
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
            <form id="photoUploadForm" novalidate>
                <div class="mb-4">
                    <label class="form-label d-flex align-items-center gap-2" for="passport_photo_input">
                        <i class="fa-solid fa-cloud-arrow-up text-cyan" style="font-size: 0.85rem;"></i>
                        <span>Select New Photo File</span>
                    </label>
                    <input type="file" class="form-control" id="passport_photo_input" name="passport_photo" accept="image/jpeg,image/png">
                    <div class="form-text" style="color: var(--text-dim); font-size: 0.82rem; margin-top: 6px;">
                        <i class="fa-solid fa-circle-info me-1"></i> Accepted formats: JPG, JPEG, PNG. Maximum file size: 2 MB.
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 pt-2">
                    <button type="submit" id="submitBtn" class="btn btn-primary px-4 py-2" style="font-weight: 700;">
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

<script type="module">
    import { auth, db } from '../assets/js/firebase-init.js';
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
    import { doc, getDoc, updateDoc, collection, addDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

    let currentUser = null;

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = '../login.php';
            return;
        }

        currentUser = user;
        document.getElementById('mainContent').style.display = 'block';
        
        try {
            const memberRef = doc(db, 'member', user.uid);
            const memberSnap = await getDoc(memberRef);
            if (memberSnap.exists()) {
                const data = memberSnap.data();
                if (data.passport_photo) {
                    document.getElementById('photoLivePreview').src = '/uploads/' + data.passport_photo;
                }
            } else {
                alert('Member record not found.');
                window.location.href = 'dashboard.php';
            }
        } catch(e) {
            console.error("Error fetching member", e);
        }
    });

    document.getElementById('passport_photo_input').addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photoLivePreview').src = e.target.result;
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    document.getElementById('photoUploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('passport_photo_input');
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Please select a photo file to proceed with your upload.');
            return;
        }

        const formData = new FormData();
        formData.append('passport_photo', fileInput.files[0]);
        formData.append('member_id', currentUser.uid);

        document.getElementById('submitBtn').disabled = true;

        try {
            // Upload file via local PHP handler
            const uploadRes = await fetch('upload_photo.php?ajax_upload=1', {
                method: 'POST',
                body: formData
            });
            const result = await uploadRes.json();

            if (result.success) {
                // Update Firestore
                const memberRef = doc(db, 'member', currentUser.uid);
                await updateDoc(memberRef, {
                    passport_photo: result.filename
                });

                // Add to history
                await addDoc(collection(db, 'member', memberId, 'update_history'), {
                    member_id: currentUser.uid,
                    updated_by_member_id: currentUser.uid,
                    update_type: 'photo_upload',
                    remarks: 'Member uploaded a new passport photo.',
                    created_at: new Date().toISOString()
                });

                alert('Photo updated successfully.');
                window.location.href = 'dashboard.php';
            } else {
                const errorBanner = document.getElementById('errorBanner');
                const errorList = document.getElementById('errorList');
                errorList.innerHTML = `<li>${result.error}</li>`;
                errorBanner.style.display = 'block';
                document.getElementById('submitBtn').disabled = false;
            }
        } catch(err) {
            console.error(err);
            alert('An error occurred during upload.');
            document.getElementById('submitBtn').disabled = false;
        }
    });
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>
