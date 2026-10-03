<?php
/**
 * member/update_info.php
 * -------------------------------------------------------
 * MODULE 4 — Member Information Update
 * Allows members to edit their personal information, contact 
 * details, spouse information, children, parents, references,
 * and signature with sticky form data & validation.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';

if (isset($_GET['ajax_upload'])) {
    header('Content-Type: application/json');
    $memberId = $_POST['member_id'] ?? 'unknown';
    $response = ['success' => true];
    
    if (!empty($_FILES['passport_photo']['name'])) {
        $res = handle_photo_upload($_FILES['passport_photo'], $memberId . '_photo');
        if ($res['success']) {
            $response['passport_photo'] = $res['filename'];
        } else {
            $response['success'] = false;
            $response['error'] = $res['error'];
        }
    }
    
    if (!empty($_FILES['signature_photo']['name'])) {
        $res = handle_photo_upload($_FILES['signature_photo'], $memberId . '_sig');
        if ($res['success']) {
            $response['signature_photo'] = $res['filename'];
        } else {
            $response['success'] = false;
            $response['error'] = $res['error'];
        }
    }
    
    echo json_encode($response);
    exit;
}

$pageTitle = 'Update Information';
require_once __DIR__ . '/../pages/header.php';
?>

<!-- Custom CSS for calendar icon visibility & layout enhancements -->
<style>
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }
    .text-danger { color: #ff6b6b !important; }
</style>

<!-- Centered Update Info Layout Wrapper -->
<div class="dashboard-wrapper" style="max-width: 1040px; margin: 20px auto 60px auto; display: none;" id="mainContent">
    <!-- Back to Dashboard Navigation Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Error Display Container -->
    <div id="errorBanner" class="alert alert-danger shadow-sm mb-4" style="display: none;">
        <ul id="errorList" class="mb-0 ps-3"></ul>
    </div>

    <!-- Main Form Card Container -->
    <div class="card profile-container p-4 p-md-5 mb-5">
      <div class="card-body p-0">

        <!-- Card Header Title -->
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: var(--badge-bg); border: 1px solid var(--badge-border); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--primary-teal); box-shadow: 0 4px 16px var(--accent-glow);">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h3 class="mb-1" style="font-weight: 800;">Update My Information</h3>
                <p class="mb-0 text-muted" style="color: var(--text-dim) !important; font-size: 0.9rem;">
                    Keep your cooperative membership information current and accurate.
                </p>
            </div>
        </div>

        <!-- Profile Update Form -->
    <form id="memberUpdateForm" novalidate>
        <!-- Profile Picture Section -->
        <h5 class="mb-3 text-white border-bottom pb-2"><i class="fa-solid fa-camera me-2"></i> Profile Picture (Passport Photo)</h5>
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-auto">
                <div class="profile-photo-preview-box" style="width: 100px; height: 100px; border-radius: 14px; border: 2px dashed var(--primary-teal); overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                    <img id="memberPhotoPreview" src="../assets/images/default-avatar.png" alt="Current Photo" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
            </div>
            <div class="col">
                <label class="form-label">Upload / Change Profile Photo</label>
                <input type="file" class="form-control" name="passport_photo" id="memberPhotoInput" accept="image/jpeg,image/png" onchange="previewMemberPhoto(this)">
                <div class="form-text" style="color: var(--text-dim) !important;">Standard 2x2 or passport size image (JPG/PNG, max 2MB).</div>
            </div>
        </div>

        <!-- Personal Information Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-id-card me-2 text-cyan"></i> Personal Information</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="firstname" id="firstname"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="middlename" id="middlename"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
            </div>
            <div class="col-md-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="lastname" id="lastname"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ext.</label>
                <select class="form-select" name="extension_name" id="extension_name">
                    <option value="">None</option>
                    <option value="Jr.">Jr.</option>
                    <option value="Sr.">Sr.</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Sex <span class="text-danger">*</span></label>
                <select class="form-select" name="sex" id="memberSex" required>
                    <option value="">Select Sex</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                <select class="form-select" id="civil_status_select" name="civil_status" required>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                    <option value="Divorced">Divorced</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nationality <span class="text-danger">*</span></label>
                <select class="form-select" name="nationality" id="memberNationality" required>
                    <option value="">Select Nationality</option>
                    <option value="Filipino">Filipino</option>
                    <option value="American">American</option>
                    <!-- other nationalities generally omitted for brevity -->
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Birthday <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="birthday" id="birthday"
                       min="1900-01-01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="contact_number" id="memberContactInput"
                       oninput="formatContactNumber(this)" placeholder="0917-123-4567" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Occupation</label>
                <input type="text" class="form-control" name="occupation" id="occupation"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer/Business Name</label>
                <input type="text" class="form-control" name="employer" id="employer">
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer Address</label>
                <input type="text" class="form-control" name="employer_address" id="employer_address">
            </div>
            <div class="col-md-12">
                <label class="form-label">Full Home Address <span class="text-danger">*</span></label>
                <textarea class="form-control" name="address" id="address" rows="2" required></textarea>
            </div>
        </div>

        <!-- Parents Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-users me-2 text-cyan"></i> Section V. Parents</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Father's Full Name</label>
                <input type="text" class="form-control" name="father_name" id="father_name"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Father's full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mother's Full Name</label>
                <input type="text" class="form-control" name="mother_name" id="mother_name"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Mother's maiden/full name">
            </div>
        </div>

        <!-- Spouse Section (Visible only if Married) -->
        <div id="spouse-section" style="display: none;">
            <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-ring me-2 text-cyan"></i> Spouse Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Spouse Name</label>
                    <input type="text" class="form-control" name="spouse_name" id="spouse_name"
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="spouse_occupation" id="spouse_occupation"
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employer</label>
                    <input type="text" class="form-control" name="spouse_employer" id="spouse_employer">
                </div>
            </div>
        </div>

        <!-- Children Section (Available always) -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-child me-2 text-cyan"></i> Children Information</h5>
        <div id="children-wrapper">
            <div id="noChildrenNotice" class="text-muted mb-3" style="color: var(--text-dim) !important; font-size: 0.9rem;">
                No children entries added. Click "Add Child" below if you wish to add a child.
            </div>
            <!-- Children rows will be dynamically injected here -->
        </div>
        <button type="button" id="add-child" class="btn btn-sm btn-outline-secondary mb-4" onclick="addNewChildRow()">
            <i class="fa-solid fa-plus"></i> Add Child
        </button>

        <!-- Section VI. References (Two Cooperative Members) -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-user-group me-2 text-cyan"></i> Section VI. References (Two Cooperative Members)</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Reference Member 1</label>
                <input type="text" class="form-control" name="reference_member_name[]" id="ref1"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Cooperative member full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Reference Member 2</label>
                <input type="text" class="form-control" name="reference_member_name[]" id="ref2"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Cooperative member full name">
            </div>
        </div>

        <!-- Signature Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-signature me-2 text-cyan"></i> Signature (Optional Photo)</h5>
        <div class="mb-4 d-flex align-items-center gap-3">
            <div id="currentSignatureWrapper" style="display:none;">
                <img id="currentSignatureImg" src="" alt="Current Signature"
                     style="height:60px;object-fit:contain;background:#fff;padding:4px;border-radius:6px;border:1px solid #ddd;">
            </div>
            <div>
                <input type="file" class="form-control" id="memberSignatureFileInput" name="signature_photo" accept="image/jpeg,image/png" onchange="handleMemberSigUpload(event)">
                <div id="memberSigPreviewSlot" style="margin-top: 0.35rem;"></div>
                <div class="form-text" style="color: #adb5bd !important;">Upload scanned signature or photo of signature (JPG/PNG, auto-cropped &amp; normalized).</div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-end mt-4 pt-2">
            <button type="button" class="btn btn-primary px-4 py-2" style="font-weight: 700;" onclick="promptMemberUpdateConfirm()">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
        </div>
    </form>
  </div>
</div>
</div>

<!-- Confirmation Modal Dialog (Theme-Aware Glassmorphism) -->
<div id="confirmMemberUpdateModal" class="modal-overlay hidden" onclick="if(event.target === this) closeMemberUpdateConfirm();" role="dialog" aria-hidden="true" style="z-index: 100001 !important;">
    <div class="modal-card" style="max-width: 490px; border: 1px solid var(--bg-card-border-glow); box-shadow: 0 20px 50px rgba(0,0,0,0.55), 0 0 25px var(--accent-glow);">
        <div class="modal-header d-flex justify-content-between align-items-center pb-3 border-bottom">
            <h3 class="m-0 d-flex align-items-center gap-2" style="font-size: 1.22rem; font-weight: 800; color: var(--text-main);">
                <i class="fa-solid fa-circle-question text-cyan"></i>
                <span>Save Profile Updates?</span>
            </h3>
            <button type="button" class="btn-close-modal" onclick="closeMemberUpdateConfirm();" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body py-3">
            <p style="color: var(--text-main); font-size: 0.96rem; line-height: 1.6; margin-bottom: 1.25rem;">
                Are you sure you want to commit these changes to your cooperative membership information?
            </p>
            <div class="p-3 rounded-3" style="background: var(--input-bg); border: 1px solid var(--bg-card-border); border-radius: 12px; font-size: 0.86rem;">
                <div class="d-flex align-items-center gap-2" style="color: var(--text-dim);">
                    <i class="fa-solid fa-circle-info text-cyan"></i>
                    <span>All modified personal and family details will be saved immediately to your official record.</span>
                </div>
            </div>
        </div>
        <div class="modal-actions d-flex justify-content-between align-items-center pt-3 border-top w-100">
            <button type="button" class="btn btn-secondary px-4 py-2" onclick="closeMemberUpdateConfirm();" style="border-radius: 12px; font-weight: 600;">
                Cancel
            </button>
            <button type="button" id="saveConfirmBtn" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" onclick="submitMemberUpdateForm();" style="border-radius: 12px; font-weight: 700;">
                <i class="fa-solid fa-floppy-disk"></i> <span>Save</span>
            </button>
        </div>
    </div>
</div>

<script type="module">
    import { auth, db } from '../assets/js/firebase-init.js';
    import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-auth.js";
    import { doc, getDoc, updateDoc, setDoc, deleteDoc, collection, query, where, getDocs, addDoc } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

    let currentUser = null;

    onAuthStateChanged(auth, async (user) => {
        if (!user) {
            window.location.href = '../login.php';
            return;
        }

        currentUser = user;
        document.getElementById('mainContent').style.display = 'block';

        const memberId = user.uid;

        try {
            // Load Member Data
            const memberRef = doc(db, 'member', memberId);
            const memberSnap = await getDoc(memberRef);
            if(memberSnap.exists()) {
                const data = memberSnap.data();
                if(data.passport_photo) document.getElementById('memberPhotoPreview').src = '/uploads/' + data.passport_photo;
                document.getElementById('firstname').value = data.firstname || '';
                document.getElementById('middlename').value = data.middlename || '';
                document.getElementById('lastname').value = data.lastname || '';
                document.getElementById('extension_name').value = data.extension_name || '';
                document.getElementById('memberSex').value = data.sex || '';
                document.getElementById('civil_status_select').value = data.civil_status || 'Single';
                document.getElementById('memberNationality').value = data.nationality || 'Filipino';
                document.getElementById('birthday').value = data.birthday || '';
                document.getElementById('memberContactInput').value = data.contact_number || '';
                document.getElementById('occupation').value = data.occupation || '';
                document.getElementById('employer').value = data.employer || '';
                document.getElementById('employer_address').value = data.employer_address || '';
                document.getElementById('address').value = data.address || '';
                if(data.signature) {
                    document.getElementById('currentSignatureImg').src = '/uploads/' + data.signature;
                    document.getElementById('currentSignatureWrapper').style.display = 'block';
                }
            }

            // Load Parents
            const parentsQuery = query(collection(db, 'parents'), where('member_id', '==', memberId));
            const parentsSnap = await getDocs(parentsQuery);
            if(!parentsSnap.empty) {
                const pData = parentsSnap.docs[0].data();
                document.getElementById('father_name').value = pData.father_name || '';
                document.getElementById('mother_name').value = pData.mother_name || '';
            }

            // Load Spouse
            const spouseQuery = query(collection(db, 'spouse'), where('member_id', '==', memberId));
            const spouseSnap = await getDocs(spouseQuery);
            if(!spouseSnap.empty) {
                const sData = spouseSnap.docs[0].data();
                document.getElementById('spouse_name').value = sData.spouse_name || '';
                document.getElementById('spouse_occupation').value = sData.occupation || '';
                document.getElementById('spouse_employer').value = sData.employer || '';
            }

            // Load Children
            const childrenQuery = query(collection(db, 'children'), where('member_id', '==', memberId));
            const childrenSnap = await getDocs(childrenQuery);
            if(!childrenSnap.empty) {
                document.getElementById('noChildrenNotice').style.display = 'none';
                childrenSnap.forEach(doc => {
                    const cData = doc.data();
                    addNewChildRow(cData.child_name, cData.birthday);
                });
            }

            // Load References
            const refQuery = query(collection(db, 'member_references'), where('member_id', '==', memberId));
            const refSnap = await getDocs(refQuery);
            let refCount = 1;
            refSnap.forEach(doc => {
                const rData = doc.data();
                if(refCount === 1) document.getElementById('ref1').value = rData.reference_member_name || '';
                if(refCount === 2) document.getElementById('ref2').value = rData.reference_member_name || '';
                refCount++;
            });

            toggleSpouseSection();

        } catch(e) {
            console.error(e);
        }
    });

    window.submitMemberUpdateForm = async function() {
        if (!currentUser) return;
        const memberId = currentUser.uid;
        document.getElementById('saveConfirmBtn').disabled = true;

        const form = document.getElementById('memberUpdateForm');
        let errors = [];

        // Simple client-side validation
        if(!form.checkValidity()) {
            form.reportValidity();
            closeMemberUpdateConfirm();
            document.getElementById('saveConfirmBtn').disabled = false;
            return;
        }

        try {
            const formData = new FormData(form);
            formData.append('member_id', memberId);

            // Upload files if any
            let passport_photo = null;
            let signature_photo = null;

            if (document.getElementById('memberPhotoInput').files.length > 0 || document.getElementById('memberSignatureFileInput').files.length > 0) {
                const uploadRes = await fetch('update_info.php?ajax_upload=1', {
                    method: 'POST',
                    body: formData
                });
                const result = await uploadRes.json();
                if(result.success) {
                    if(result.passport_photo) passport_photo = result.passport_photo;
                    if(result.signature_photo) signature_photo = result.signature_photo;
                } else {
                    errors.push(result.error);
                }
            }

            if(errors.length > 0) {
                const errList = document.getElementById('errorList');
                errList.innerHTML = '';
                errors.forEach(e => errList.insertAdjacentHTML('beforeend', `<li>${e}</li>`));
                document.getElementById('errorBanner').style.display = 'block';
                closeMemberUpdateConfirm();
                document.getElementById('saveConfirmBtn').disabled = false;
                window.scrollTo(0,0);
                return;
            }

            // Update member info
            const updateData = {
                firstname: document.getElementById('firstname').value,
                middlename: document.getElementById('middlename').value,
                lastname: document.getElementById('lastname').value,
                extension_name: document.getElementById('extension_name').value,
                sex: document.getElementById('memberSex').value,
                civil_status: document.getElementById('civil_status_select').value,
                nationality: document.getElementById('memberNationality').value,
                birthday: document.getElementById('birthday').value,
                contact_number: document.getElementById('memberContactInput').value,
                occupation: document.getElementById('occupation').value,
                employer: document.getElementById('employer').value,
                employer_address: document.getElementById('employer_address').value,
                address: document.getElementById('address').value
            };

            if(passport_photo) updateData.passport_photo = passport_photo;
            if(signature_photo) updateData.signature = signature_photo;

            await updateDoc(doc(db, 'member', memberId), updateData);

            // Update Parents
            const fName = document.getElementById('father_name').value;
            const mName = document.getElementById('mother_name').value;
            const parentsQ = await getDocs(query(collection(db, 'parents'), where('member_id', '==', memberId)));
            if(!parentsQ.empty) {
                await updateDoc(parentsQ.docs[0].ref, { father_name: fName, mother_name: mName });
            } else {
                await addDoc(collection(db, 'parents'), { member_id: memberId, father_name: fName, mother_name: mName });
            }

            // Update Spouse
            const cStatus = document.getElementById('civil_status_select').value;
            const spouseQ = await getDocs(query(collection(db, 'spouse'), where('member_id', '==', memberId)));
            if(cStatus === 'Married') {
                const sName = document.getElementById('spouse_name').value;
                const sOcc = document.getElementById('spouse_occupation').value;
                const sEmp = document.getElementById('spouse_employer').value;
                if(!spouseQ.empty) {
                    await updateDoc(spouseQ.docs[0].ref, { spouse_name: sName, occupation: sOcc, employer: sEmp });
                } else {
                    await addDoc(collection(db, 'spouse'), { member_id: memberId, spouse_name: sName, occupation: sOcc, employer: sEmp });
                }
            } else {
                if(!spouseQ.empty) {
                    await deleteDoc(spouseQ.docs[0].ref);
                }
            }

            // Update Children
            const childQ = await getDocs(query(collection(db, 'children'), where('member_id', '==', memberId)));
            childQ.forEach(async (d) => await deleteDoc(d.ref));

            const childNames = document.getElementsByName('child_name[]');
            const childBdays = document.getElementsByName('child_birthday[]');
            for(let i=0; i<childNames.length; i++) {
                let n = childNames[i].value.trim();
                let b = childBdays[i].value.trim();
                if(n !== '' || b !== '') {
                    await addDoc(collection(db, 'children'), { member_id: memberId, child_name: n, birthday: b });
                }
            }

            // Update References
            const refQ = await getDocs(query(collection(db, 'member_references'), where('member_id', '==', memberId)));
            refQ.forEach(async (d) => await deleteDoc(d.ref));
            
            const r1 = document.getElementById('ref1').value.trim();
            const r2 = document.getElementById('ref2').value.trim();
            if(r1) await addDoc(collection(db, 'member_references'), { member_id: memberId, reference_member_name: r1, signature: signature_photo || updateData.signature || null });
            if(r2) await addDoc(collection(db, 'member_references'), { member_id: memberId, reference_member_name: r2, signature: null });

            // History
            await addDoc(collection(db, 'member', memberId, 'update_history'), {
                member_id: memberId,
                updated_by_member_id: memberId,
                update_type: 'Information Update',
                remarks: 'Member updated their own profile information.',
                created_at: new Date().toISOString()
            });

            alert('Your information has been updated.');
            window.location.href = 'profile.php';

        } catch(err) {
            console.error(err);
            alert('Something went wrong while saving. Please try again.');
            document.getElementById('saveConfirmBtn').disabled = false;
        }
    };
</script>

<!-- Dynamic Form Scripts and Handlers -->
<script>
function formatContactNumber(input) {
    if (!input) return;
    let digits = input.value.replace(/\D/g, '');
    if (digits.length > 11) digits = digits.substring(0, 11);
    if (digits.length > 7) {
        input.value = digits.substring(0, 4) + '-' + digits.substring(4, 7) + '-' + digits.substring(7);
    } else if (digits.length > 4) {
        input.value = digits.substring(0, 4) + '-' + digits.substring(4);
    } else {
        input.value = digits;
    }
}

function previewMemberPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('memberPhotoPreview');
            if (preview) preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function promptMemberUpdateConfirm() {
    const form = document.getElementById('memberUpdateForm');
    if (form && !form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const modal = document.getElementById('confirmMemberUpdateModal');
    if (modal) modal.classList.remove('hidden');
}

function closeMemberUpdateConfirm() {
    const modal = document.getElementById('confirmMemberUpdateModal');
    if (modal) modal.classList.add('hidden');
}

function createChildRowElement(name = '', birthday = '') {
    const today = new Date().toISOString().split('T')[0];
    const row = document.createElement('div');
    row.className = 'row g-3 mb-2 child-row align-items-center';
    row.innerHTML = `
        <div class="col-md-6">
            <input type="text" class="form-control" name="child_name[]"
                   placeholder="Child's full name" value="${name}"
                   oninput="this.value = this.value.replace(/[^A-Za-z\\s.\\-]/g, '')">
        </div>
        <div class="col-md-4">
            <input type="date" class="form-control" name="child_birthday[]"
                   value="${birthday}" min="1900-01-01" max="${today}">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger remove-child w-100" onclick="removeChildRow(this)">
                <i class="fa-solid fa-trash-can me-1"></i> Remove
            </button>
        </div>
    `;
    return row;
}

window.addNewChildRow = function(name = '', birthday = '') {
    const wrapper = document.getElementById('children-wrapper');
    const notice = document.getElementById('noChildrenNotice');
    if (notice) notice.style.display = 'none';
    const row = createChildRowElement(name, birthday);
    wrapper.appendChild(row);
}

window.removeChildRow = function(btn) {
    if (!btn) return;
    const row = btn.closest('.child-row');
    if (row) {
        row.remove();
        const remaining = document.querySelectorAll('.child-row');
        const notice = document.getElementById('noChildrenNotice');
        if (remaining.length === 0 && notice) {
            notice.style.display = 'block';
        }
    }
}

function toggleSpouseSection() {
    const civilStatusSelect = document.getElementById('civil_status_select');
    const spouseSection = document.getElementById('spouse-section');
    if (civilStatusSelect.value === 'Married') {
        spouseSection.style.display = 'block';
    } else {
        spouseSection.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const civilStatusSelect = document.getElementById('civil_status_select');
    civilStatusSelect.addEventListener('change', toggleSpouseSection);
});

window.handleMemberSigUpload = function(event) {
    const file = event.target.files[0];
    const previewSlot = document.getElementById('memberSigPreviewSlot');
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        alert('Signature image must be 5 MB or smaller.');
        event.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const origCanvas = document.createElement('canvas');
            const origCtx = origCanvas.getContext('2d', { willReadFrequently: true });
            origCanvas.width = img.naturalWidth || img.width;
            origCanvas.height = img.naturalHeight || img.height;
            origCtx.drawImage(img, 0, 0);

            const imgData = origCtx.getImageData(0, 0, origCanvas.width, origCanvas.height);
            const data = imgData.data;
            const w = origCanvas.width;
            const h = origCanvas.height;

            let minX = w, minY = h, maxX = 0, maxY = 0;
            let inkFound = false;

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const idx = (y * w + x) * 4;
                    const r = data[idx];
                    const g = data[idx + 1];
                    const b = data[idx + 2];
                    const a = data[idx + 3];

                    const isInk = (a > 30) && ((r + g + b) / 3 < 225);
                    if (isInk) {
                        if (x < minX) minX = x;
                        if (x > maxX) maxX = x;
                        if (y < minY) minY = y;
                        if (y > maxY) maxY = y;
                        inkFound = true;
                    }
                }
            }

            if (!inkFound) {
                minX = 0; minY = 0; maxX = w; maxY = h;
            }

            const boxW = Math.max(maxX - minX + 1, 20);
            const boxH = Math.max(maxY - minY + 1, 20);
            const padX = Math.round(boxW * 0.12) + 12;
            const padY = Math.round(boxH * 0.12) + 12;

            const cropX = Math.max(0, minX - padX);
            const cropY = Math.max(0, minY - padY);
            const cropW = Math.min(w - cropX, boxW + (padX * 2));
            const cropH = Math.min(h - cropY, boxH + (padY * 2));

            const targetW = 400;
            const targetH = 140;
            const outCanvas = document.createElement('canvas');
            outCanvas.width = targetW;
            outCanvas.height = targetH;
            const outCtx = outCanvas.getContext('2d');

            outCtx.fillStyle = '#ffffff';
            outCtx.fillRect(0, 0, targetW, targetH);

            const scale = Math.min((targetW - 24) / cropW, (targetH - 24) / cropH, 1.8);
            const drawW = cropW * scale;
            const drawH = cropH * scale;
            const drawX = (targetW - drawW) / 2;
            const drawY = (targetH - drawH) / 2;

            outCtx.drawImage(origCanvas, cropX, cropY, cropW, cropH, drawX, drawY, drawW, drawH);

            outCanvas.toBlob(function(blob) {
                if (!blob) return;
                const croppedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + "_cropped.png", { type: 'image/png' });
                const croppedDataUrl = outCanvas.toDataURL('image/png');

                if (previewSlot) {
                    previewSlot.innerHTML = `
                        <div style="margin-top: 0.35rem;">
                            <span style="color: #34d399; font-size: 0.8rem; display: block; margin-bottom: 2px;">
                                <i class="fa-solid fa-circle-check"></i> Auto-Cropped Signature Ready:
                            </span>
                            <img src="${croppedDataUrl}" alt="Cropped Signature" style="height: 42px; background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; vertical-align: middle;">
                        </div>
                    `;
                }

                if (window.DataTransfer) {
                    try {
                        const dt = new DataTransfer();
                        dt.items.add(croppedFile);
                        event.target.files = dt.files;
                    } catch(e) {}
                }
            }, 'image/png');
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
</script>

<?php require_once __DIR__ . '/../pages/footer.php'; ?>
