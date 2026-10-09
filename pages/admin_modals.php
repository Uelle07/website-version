<?php
/**
 * pages/admin_modals.php
 * -------------------------------------------------------
 * Shared Admin UI Modals for Staff Portal pages.
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('admin_modals.php cannot be accessed directly.');
}
?>

  <!-- Add Member Modal -->
  <div id="addMemberModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 750px; max-height: 85vh; overflow-y: auto;">
      <div class="modal-header">
        <h3 id="addMemberModalTitle"><i class="fa-solid fa-user-plus" style="color: var(--primary-teal);"></i> Add New Cooperative Member</h3>
      </div>
      <form id="addMemberForm" action="<?= e(BASE_URL) ?>/staff/add_member.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div id="addMemberError" class="modal-error-box hidden" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.85rem;"></div>
        
        <div style="display: flex; gap: 1.5rem; align-items: flex-start; margin-bottom: 1.5rem;">
          <!-- LEFT COLUMN: Passport Photo Upload Slot -->
          <div style="flex-shrink: 0; width: 140px; text-align: center;">
            <label style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.4rem; font-weight: 600;">Passport Photo</label>
            <div id="addPhotoSlot" class="photo-outline-placeholder" style="width: 140px; height: 160px; cursor: pointer; position: relative; overflow: hidden; border-radius: 12px; border: 2px dashed var(--primary-teal); transition: all 0.3s;" onclick="document.getElementById('addPhotoInput').click()">
              <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: var(--primary-teal); margin-bottom: 0.4rem;"></i>
              <span style="font-size: 0.75rem; color: var(--text-muted); padding: 0 0.5rem;">Click to attach picture</span>
              <img id="addPhotoPreview" src="" class="uploaded-passport-img hidden" alt="Passport Photo" style="width: 100%; height: 100%; object-fit: cover; position: absolute; top:0; left:0;">
            </div>
            <input type="file" id="addPhotoInput" name="passport_photo" accept="image/*" class="hidden" onchange="handlePhotoUpload(event)">
            <button type="button" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem; width: 100%; font-size: 0.72rem;" onclick="document.getElementById('addPhotoInput').click()">
              <i class="fa-solid fa-paperclip"></i> Attach File
            </button>
          </div>

          <!-- RIGHT COLUMN: Fill-up Form Fields -->
          <div class="form-grid" style="flex-grow: 1; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
            <div class="form-group">
              <label>First Name <span style="color: #f87171;">*</span></label>
              <input type="text" id="addFirstName" name="firstname" placeholder="e.g., Juan" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" id="addMiddleName" name="middlename" placeholder="e.g., Silayan">
            </div>
            <div class="form-group">
              <label>Last Name <span style="color: #f87171;">*</span></label>
              <input type="text" id="addLastName" name="lastname" placeholder="e.g., Dela Cruz" required>
            </div>
            <div class="form-group">
              <label>Ext.</label>
              <select id="addSuffix" name="extension_name">
                <option value="">None</option>
                <option value="Jr.">Jr.</option>
                <option value="Sr.">Sr.</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
                <option value="V">V</option>
              </select>
            </div>

            <div class="form-group">
              <label>Sex <span style="color: #f87171;">*</span></label>
              <select id="addSex" name="sex" required>
                <option value="">-- Select Sex --</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
            <div class="form-group">
              <label>Civil Status <span style="color: #f87171;">*</span></label>
              <select id="addCivilStatus" name="civil_status" onchange="toggleSpouseFields()" required>
                <option value="Single">Single</option>
                <option value="Married">Married</option>
                <option value="Widowed">Widowed</option>
                <option value="Separated">Separated</option>
                <option value="Divorced">Divorced</option>
              </select>
            </div>


            <div class="form-group">
              <label>Birthday <span style="color: #f87171;">*</span></label>
              <input type="date" id="addBirthday" name="birthday" min="1900-01-01" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label>Nationality <span style="color: #f87171;">*</span></label>
              <select id="addNationality" name="nationality" required>
                <?php foreach (get_nationalities_list() as $nat): ?>
                  <option value="<?= e($nat) ?>" <?= $nat === 'Filipino' ? 'selected' : '' ?>><?= e($nat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Contact Information <span style="color: #f87171;">*</span></label>
              <div style="display: flex; gap: 0.5rem;">
                <select name="contact_country_code" style="width: 100px; padding: 0.45rem 0.5rem; border-radius: 8px; border: 1px solid var(--bg-card-border); background: var(--bg-input, transparent); color: var(--text-main);">
                  <option value="+63">+63 (PH)</option>
                  <option value="+1">+1 (US)</option>
                </select>
                <input type="tel" id="addContact" name="contact_number" placeholder="9XX-XXX-XXXX" required oninput="formatContactNumber(this)" style="flex: 1;">
              </div>
            </div>
            <div class="form-group">
              <label>Email Address <span style="color: #f87171;">*</span></label>
              <input type="email" id="addEmail" name="email" placeholder="e.g., sample@gmail.com" required>
            </div>
            <div class="form-group">
              <label>Occupation <span style="color: #f87171;">*</span></label>
              <input type="text" id="addOccupation" name="occupation" placeholder="e.g., Accountant" required>
            </div>

            <div class="form-group">
              <label>Employer/Business Name</label>
              <input type="text" id="addEmployer" name="employer" placeholder="Company Name">
            </div>
            <div class="form-group">
              <label>Employer Address</label>
              <input type="text" id="addEmployerAddress" name="employer_address" placeholder="Employer Business Address">
            </div>

            <div class="form-group" style="grid-column: span 2;">
              <label>Home Address <span style="color: #f87171;">*</span></label>
              <input type="text" id="addHomeAddress" name="address" placeholder="House No., Street, Barangay, City, Province" required>
            </div>
          </div>
        </div>

        <!-- Parents Section -->
        <div style="margin-top: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
          <h4 style="color: var(--primary-teal); margin-bottom: 0.75rem;"><i class="fa-solid fa-people-roof"></i> Parents Information <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: normal;">(Optional)</span></h4>
          <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
            <div class="form-group">
              <label>Father's Full Name <span style="font-size: 0.76rem; color: var(--text-muted);">(Optional)</span></label>
              <input type="text" id="addFatherName" name="father_name" placeholder="Father's Full Name">
            </div>
            <div class="form-group">
              <label>Mother's Full Name <span style="font-size: 0.76rem; color: var(--text-muted);">(Optional)</span></label>
              <input type="text" id="addMotherName" name="mother_name" placeholder="Mother's Full Name">
            </div>
          </div>
        </div>

        <!-- Spouse Section (Visible & Required if Married) -->
        <div id="addSpouseSection" style="display: none; margin-top: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
          <h4 style="color: var(--primary-teal); margin-bottom: 0.75rem;"><i class="fa-solid fa-heart" style="color: #f43f5e;"></i> Spouse Information</h4>
          <div class="form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.85rem;">
            <div class="form-group">
              <label>Spouse First Name <span id="addSpouseRequiredIndicatorF" style="color: #f87171;">*</span></label>
              <input type="text" id="addSpouseFname" name="spouse_firstname" placeholder="First Name">
            </div>
            <div class="form-group">
              <label>Spouse Middle Name</label>
              <input type="text" id="addSpouseMname" name="spouse_middlename" placeholder="Middle Name">
            </div>
            <div class="form-group">
              <label>Spouse Last Name <span id="addSpouseRequiredIndicatorL" style="color: #f87171;">*</span></label>
              <input type="text" id="addSpouseLname" name="spouse_lastname" placeholder="Last Name">
            </div>
            <div class="form-group">
              <label>Spouse Ext</label>
              <input type="text" id="addSpouseExt" name="spouse_extension" placeholder="Jr/Sr">
            </div>
            <div class="form-group">
              <label>Spouse Occupation <span style="font-size: 0.76rem; color: var(--text-muted);">(Optional)</span></label>
              <input type="text" id="addSpouseOccupation" name="spouse_occupation" placeholder="e.g., Engineer">
            </div>
            <div class="form-group">
              <label>Spouse Employer <span style="font-size: 0.76rem; color: var(--text-muted);">(Optional)</span></label>
              <input type="text" id="addSpouseEmployer" name="spouse_employer" placeholder="Company Name">
            </div>
          </div>
        </div>

        <!-- Children Section (Empty by default, appears only when Add Child is clicked) -->
        <div style="margin-top: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
            <h4 style="color: var(--primary-teal); margin: 0;">
              <i class="fa-solid fa-child"></i> Children Information <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: normal;">(Optional)</span>
            </h4>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addChildForm()" style="font-size: 0.78rem; padding: 0.28rem 0.75rem;">
              <i class="fa-solid fa-plus"></i> Add Child
            </button>
          </div>
          <div id="dynamicChildrenFormsContainer" style="display: flex; flex-direction: column; gap: 0.65rem;"></div>
        </div>

        <!-- References Section -->
        <div style="margin-top: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
          <h4 style="color: var(--primary-teal); margin-bottom: 0.75rem;"><i class="fa-solid fa-users"></i> Character References <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: normal;">(Optional)</span></h4>
          <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
            <div class="form-group">
              <label>Reference Member 1 Name <span style="font-size: 0.76rem; color: var(--text-muted);">(Optional)</span></label>
              <input type="text" id="addRef1Name" name="reference_member_name[]" placeholder="Reference Member Full Name">
            </div>
            <div class="form-group">
              <label>Reference Member 2 Name <span style="font-size: 0.76rem; color: var(--text-muted);">(Optional)</span></label>
              <input type="text" id="addRef2Name" name="reference_member_name[]" placeholder="Reference Member Full Name">
            </div>
          </div>
        </div>

        <!-- Signature Upload Section -->
        <div style="margin-top: 1rem; border-top: 1px solid var(--bg-card-border); padding-top: 1rem;">
          <h4 style="color: var(--primary-teal); margin-bottom: 0.75rem;"><i class="fa-solid fa-signature"></i> Member Signature <span style="font-size:0.8rem; color:var(--text-muted); font-weight:400;">(Optional)</span></h4>
          <div style="display: flex; align-items: flex-start; gap: 1.25rem; flex-wrap: wrap;">
            <!-- Live signature preview box -->
            <div id="addSigSlot" style="flex-shrink:0; width:180px; min-height:72px; border:2px dashed var(--primary-teal); border-radius:10px; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:pointer; background:rgba(0,194,168,0.04); transition:border-color 0.2s;" onclick="document.getElementById('addSigInput').click()" title="Click to choose signature image">
              <img id="addSigPreview" src="" alt="Signature Preview" style="display:none; max-width:100%; max-height:68px; object-fit:contain; padding:4px;">
              <span id="addSigPlaceholder" style="font-size:0.78rem; color:var(--text-muted); text-align:center; padding:0.5rem;"><i class="fa-solid fa-pen-nib" style="display:block; font-size:1.4rem; margin-bottom:0.3rem; color:var(--primary-teal);"></i>Click to attach<br>signature image</span>
            </div>
            <!-- File input + helper text -->
            <div style="flex:1; min-width:180px;">
              <input type="file" id="addSigInput" name="signature_photo" accept="image/jpeg,image/png" class="hidden" onchange="handleSigUpload(event)">
              <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.78rem; margin-bottom:0.5rem;" onclick="document.getElementById('addSigInput').click()">
                <i class="fa-solid fa-paperclip"></i> Choose Signature File
              </button>
              <p style="font-size:0.76rem; color:var(--text-muted); margin:0;">Scanned image or photo of the member's handwritten signature.<br>Accepted: JPG or PNG &middot; Max 2 MB.</p>
            </div>
          </div>
        </div>

        <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('addMemberModal')">Cancel</button>
          <button type="button" id="saveMemberBtn" class="btn btn-primary" onclick="promptConfirmAddMember()"><i class="fa-solid fa-check"></i> Register Member</button>
        </div>
      </form>
    </div>
  </div>



  <!-- Print File Format Selection Modal (Direct Downloads) -->
  <div id="printOptionsModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 500px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-file-arrow-down" style="color: #60a5fa;"></i> Download Member Record</h3>
      </div>
      <div class="export-validation-body">
        <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.25rem;">
          Select output format for <strong id="printTargetName" style="color: var(--primary-teal);">Member Record</strong> to download immediately:
        </p>
        <div class="form-group full-width">
          <label><i class="fa-solid fa-file-export"></i> Document Format:</label>
          <select id="printFormatSelect" class="table-filter-select-lg">
            <option value="PDF Document (.pdf)">PDF Document (.pdf)</option>
            <option value="Word Document (.docx)">Word Document (.docx / .doc)</option>
          </select>
        </div>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('printOptionsModal')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="confirmPrintAction()">
          <i class="fa-solid fa-download"></i> Download File
        </button>
      </div>
    </div>
  </div>

  <!-- Edit Member Modal -->
  <div id="editModal" class="modal-overlay hidden">
    <div class="modal-card scrollable-modal-card" style="max-width: 760px; max-height: 90vh;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-user-pen" style="color: var(--primary-teal);"></i> Edit Member Information</h3>
      </div>
      <form id="editMemberForm" action="<?= e(BASE_URL) ?>/staff/edit_member.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" id="editAccNoHidden" name="account_number">
        <div id="editMemberError" class="modal-error-box hidden"></div>

        <div class="scrollable-modal-body" style="display: flex; flex-direction: column; gap: 1.25rem;">
          
          <!-- 1. Personal Information -->
          <div class="form-section">
            <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <i class="fa-solid fa-id-card"></i> Personal Information
            </h4>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
              <div class="form-group">
                <label>First Name <span style="color: #f87171;">*</span></label>
                <input type="text" id="editFirstName" name="firstname" placeholder="e.g., Juan" required>
              </div>
              <div class="form-group">
                <label>Middle Name</label>
                <input type="text" id="editMiddleName" name="middlename" placeholder="e.g., Silayan">
              </div>
              <div class="form-group">
                <label>Last Name <span style="color: #f87171;">*</span></label>
                <input type="text" id="editLastName" name="lastname" placeholder="e.g., Dela Cruz" required>
              </div>
              <div class="form-group">
                <label>Extension Name</label>
                <input type="text" id="editExtensionName" name="extension_name" placeholder="e.g., Jr., Sr., III">
              </div>
              <div class="form-group">
                <label>Civil Status <span style="color: #f87171;">*</span></label>
                <select id="editCivilStatus" name="civil_status" required>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                  <option value="Separated">Separated</option>
                  <option value="Divorced">Divorced</option>
                </select>
              </div>
              <div class="form-group">
                <label>Account Status <span style="color: #f87171;">*</span></label>
                <select id="editAccountStatus" name="account_status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                  <option value="Flagged">Flagged</option>
                </select>
              </div>
              <div class="form-group">
                <label>Sex <span style="color: #f87171;">*</span></label>
                <select id="editSex" name="sex" required>
                  <option value="">-- Select Sex --</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="form-group">
                <label>Nationality <span style="color: #f87171;">*</span></label>
                <select id="editNationality" name="nationality" required>
                  <option value="">-- Select Nationality --</option>
                  <?php foreach (get_nationalities_list() as $nat): ?>
                    <option value="<?= e($nat) ?>"><?= e($nat) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Birthday</label>
                <input type="date" id="editBirthday" name="birthday" min="1900-01-01" max="<?= date('Y-m-d') ?>">
              </div>
            </div>
          </div>

          <!-- 2. Contact Information -->
          <div class="form-section">
            <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <i class="fa-solid fa-phone"></i> Contact Information
            </h4>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
              <div class="form-group">
                <label>Contact Number <span style="color: #f87171;">*</span></label>
                <input type="tel" id="editContact" name="contact" required oninput="formatContactNumber(this)">
              </div>
                <div class="form-group">
                  <label>Email Address <span style="color: #f87171;">*</span></label>
                  <input type="email" id="editEmail" name="email" placeholder="e.g., sample@gmail.com" required>
                </div>
              <div class="form-group full-width" style="grid-column: span 2;">
                <label>Full Home Address <span style="color: #f87171;">*</span></label>
                <input type="text" id="editAddress" name="address" placeholder="House No., Street, Barangay, City" required>
              </div>
            </div>
          </div>

          <!-- 3. Employment Information -->
          <div class="form-section">
            <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <i class="fa-solid fa-briefcase"></i> Employment Information
            </h4>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
              <div class="form-group">
                <label>Employer/Business Name</label>
                <input type="text" id="editEmployer" name="employer" placeholder="Company Name">
              </div>
              <div class="form-group">
                <label>Employer Address</label>
                <input type="text" id="editEmployerAddress" name="employer_address" placeholder="Company Address">
              </div>
            </div>
          </div>

          <!-- 4. Parents Information -->
          <div class="form-section">
            <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <i class="fa-solid fa-people-roof"></i> Parents Information
            </h4>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
              <div class="form-group">
                <label>Father's Full Name</label>
                <input type="text" id="editFatherName" name="father_name" placeholder="Father's Full Name">
              </div>
              <div class="form-group">
                <label>Mother's Full Name</label>
                <input type="text" id="editMotherName" name="mother_name" placeholder="Mother's Full Name">
              </div>
            </div>
          </div>

          <!-- 5. Spouse Information -->
          <div class="form-section" id="editSpouseSection">
            <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <i class="fa-solid fa-heart"></i> Spouse Information
            </h4>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
              <div class="form-group">
                <label>First Name <span class="req">*</span></label>
                <input type="text" id="editSpouseFirstName" name="spouse_firstname" placeholder="First Name">
              </div>
              <div class="form-group">
                <label>Middle Name</label>
                <input type="text" id="editSpouseMiddleName" name="spouse_middlename" placeholder="Middle Name">
              </div>
              <div class="form-group">
                <label>Last Name <span class="req">*</span></label>
                <input type="text" id="editSpouseLastName" name="spouse_lastname" placeholder="Last Name">
              </div>
              <div class="form-group">
                <label>Extension (e.g. Jr)</label>
                <input type="text" id="editSpouseExtensionName" name="spouse_extension" placeholder="Ext (Optional)">
              </div>
              <div class="form-group">
                <label>Spouse Occupation</label>
                <input type="text" id="editSpouseOccupation" name="spouse_occupation" placeholder="Spouse Occupation">
              </div>
              <div class="form-group">
                <label>Spouse Employer</label>
                <input type="text" id="editSpouseEmployer" name="spouse_employer" placeholder="Spouse Employer">
              </div>
            </div>
          </div>

          <!-- 6. Children Information -->
          <div class="form-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin: 0;">
                <i class="fa-solid fa-children"></i> Children Information
              </h4>
              <button type="button" class="btn btn-secondary btn-sm" onclick="addEditChildRow()" style="font-size: 0.78rem; padding: 0.25rem 0.65rem;">
                <i class="fa-solid fa-plus"></i> Add Child
              </button>
            </div>
            <div id="editChildrenContainer" style="display: flex; flex-direction: column; gap: 0.6rem;"></div>
          </div>

          <!-- 7. Documents (Passport Photo & E-Signature) -->
          <div class="form-section">
            <h4 style="color: var(--primary-teal); font-size: 0.95rem; margin-bottom: 0.75rem; border-bottom: 1px solid var(--bg-card-border); padding-bottom: 0.4rem;">
              <i class="fa-solid fa-folder-open"></i> Documents (Passport Photo & E-Signature)
            </h4>
            <div class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem;">
              <div class="form-group">
                <label>Passport Photo (Optional)</label>
                <input type="file" id="editPassportPhotoInput" name="passport_photo" accept="image/jpeg,image/png" onchange="handleEditPhotoUpload(event)">
                <div id="editPhotoPreviewSlot" style="margin-top: 0.4rem; font-size: 0.8rem; color: var(--text-muted);"></div>
              </div>
              <div class="form-group">
                <label>E-Signature (Optional)</label>
                <input type="file" id="editSignatureInput" name="signature_photo" accept="image/jpeg,image/png" onchange="handleEditSigUpload(event)">
                <div id="editSigPreviewSlot" style="margin-top: 0.4rem; font-size: 0.8rem; color: var(--text-muted);"></div>
              </div>
            </div>
          </div>

        </div>

        <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="promptConfirmEditMember()"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Secondary Confirmation Modal for Add Member -->
  <div id="confirmAddMemberModal" class="modal-overlay hidden" style="z-index: 1060;">
    <div class="modal-card" style="max-width: 460px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-user-plus" style="color: var(--primary-teal);"></i> Confirm Registration</h3>
      </div>
      <div class="modal-body" style="padding-top: 0.5rem;">
        <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem;">
          Are you sure you want to register this new member record?
        </p>
        <p style="color: var(--text-dim); font-size: 0.85rem; margin: 0;">
          A unique Member Account ID will be generated and credentials prepared.
        </p>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('confirmAddMemberModal')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="executeAddMemberSubmit()">
          <i class="fa-solid fa-check"></i> Yes, Register Member
        </button>
      </div>
    </div>
  </div>

  <!-- Secondary Confirmation Modal for Edit Member -->
  <div id="confirmEditMemberModal" class="modal-overlay hidden" style="z-index: 1060;">
    <div class="modal-card" style="max-width: 500px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-circle-question" style="color: var(--primary-teal);"></i> Confirm Changes</h3>
      </div>
      <div class="confirm-modal-body" style="padding: 1rem 0;">
        <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.75rem;">
          Are you sure you want to apply the modifications made to this member's profile?
        </p>
        <div id="editChangesSummary" style="background: var(--input-bg); border: 1px solid var(--bg-card-border); border-radius: 10px; padding: 0.85rem; max-height: 180px; overflow-y: auto; font-size: 0.82rem; color: var(--text-dim);">
          <!-- Dynamic diff populated by JS -->
        </div>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('confirmEditMemberModal')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="executeEditMemberSubmit()">
          <i class="fa-solid fa-check"></i> Yes, Save Changes
        </button>
      </div>
    </div>
  </div>

  <!-- Delete Member Confirmation Modal -->
  <div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-user-minus" style="color: #ef4444;"></i> Remove Member Record</h3>
      </div>
      <form id="deleteMemberForm" action="<?= e(BASE_URL) ?>/staff/delete_member.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" id="deleteAccNoHidden" name="account_number">
        <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem;">
          Are you sure you want to remove <strong id="deleteTargetName" style="color: #f87171;">this member</strong> (<span id="deleteTargetAcc"></span>)?
        </p>
        <p style="color: #94a3b8; font-size: 0.85rem;">
          ⚠️ This action will remove the member from the portal and set their account status to Inactive.
        </p>
        <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Confirm</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Update Admin Profile Picture Modal -->
  <div id="adminPhotoModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-camera-rotate" style="color: var(--primary-teal);"></i> Update Profile Picture</h3>
      </div>
      <form id="adminPhotoForm" action="<?= e(BASE_URL) ?>/staff/update_admin_photo.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" id="adminPhotoAction" value="upload">
        <input type="hidden" name="staff_id" id="adminPhotoStaffId" value="">
        <input type="hidden" name="current_photo" id="adminPhotoCurrentPhoto" value="">
        
        <div class="admin-photo-edit-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem 0;">
          <div class="admin-photo-preview-slot" id="adminPhotoSlot" style="width: 130px; height: 130px; border-radius: 50%; border: 3px solid var(--primary-teal); overflow: hidden; position: relative; cursor: pointer; box-shadow: 0 0 25px var(--accent-glow); background: var(--badge-bg); display: flex; align-items: center; justify-content: center;" onclick="document.getElementById('adminPhotoFileInput').click()">
            <img id="adminPhotoPreview" src="" class="hidden" alt="Admin Preview" style="width: 100%; height: 100%; object-fit: cover;">
            <i id="adminPhotoPlaceholderIcon" class="fa-solid fa-user" style="font-size: 3.5rem; color: var(--primary-teal);"></i>
            <div class="avatar-hover-overlay"><i class="fa-solid fa-camera"></i><br><span style="font-size: 0.72rem;">Change</span></div>
          </div>
          <input type="file" id="adminPhotoFileInput" name="admin_profile_photo" accept="image/jpeg,image/png" class="hidden" onchange="previewAdminPhoto(event)">
          
          <p style="font-size: 0.82rem; color: var(--text-dim); margin-top: 0.85rem; text-align: center;">
            Recommended: Square photo &bull; Max 5 MB (JPG or PNG)
          </p>

          <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('adminPhotoFileInput').click()">
              <i class="fa-solid fa-folder-open"></i> Browse Picture
            </button>
            <button type="button" class="btn btn-danger btn-sm" id="btnRemoveAdminPhoto" onclick="removeAdminPhoto()" style="display: none;">
              <i class="fa-solid fa-trash"></i> Remove Photo
            </button>
          </div>
        </div>

        <div class="modal-actions" style="margin-top: 1.5rem; justify-content: flex-end; display: flex; gap: 0.75rem;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('adminPhotoModal')">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveAdminPhotoBtn"><i class="fa-solid fa-check"></i> Save</button>
        </div>
      </form>
    </div>
  </div>

<script type="module">
import { auth, db } from '../assets/js/firebase-init.js';
import { doc, getDoc, collection, addDoc, serverTimestamp } from "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js";

window.executeAddMemberFirebase = async function() {
    const btn = document.getElementById('saveMemberBtn');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registering...';
    
    const form = document.getElementById('addMemberForm');
    const formData = new FormData(form);
    const account_number = '2026-' + Math.floor(1000 + Math.random() * 9000);
    
    const memberData = {
        account_number: account_number,
        firstname: formData.get('firstname'),
        middlename: formData.get('middlename'),
        lastname: formData.get('lastname'),
        extension_name: formData.get('extension_name') || '',
        sex: formData.get('sex'),
        nationality: formData.get('nationality'),
        civil_status: formData.get('civil_status'),
        birthday: formData.get('birthday'),
        contact_number: (formData.get('contact_country_code') || '') + ' ' + (formData.get('contact_number') || ''),
        email: formData.get('email'),
        address: formData.get('address'),
        occupation: formData.get('occupation') || 'Not Specified',
        employer: formData.get('employer') || 'Not Specified',
        employer_address: formData.get('employer_address') || 'Not Specified',
        father_name: formData.get('father_name') || 'Not Specified',
        mother_name: formData.get('mother_name') || 'Not Specified',
        spouse_name: ((formData.get('spouse_firstname') || '') + ' ' + (formData.get('spouse_lastname') || '')).trim() || 'Not Specified',
        spouse_occupation: formData.get('spouse_occupation') || 'Not Specified',
        spouse_employer: formData.get('spouse_employer') || 'Not Specified',
        account_status: 'Active',
        created_at: serverTimestamp()
    };
    
    let children = [];
    const childFirsts = formData.getAll('child_firstname[]');
    const childLasts = formData.getAll('child_lastname[]');
    const childBdays = formData.getAll('child_birthday[]');
    for(let i=0; i<childFirsts.length; i++) {
        if(childFirsts[i].trim() !== '') {
            children.push({
                name: (childFirsts[i] + ' ' + childLasts[i]).trim(),
                birthday: childBdays[i] || ''
            });
        }
    }
    memberData.children = children;
    
    let references = [];
    const refNames = formData.getAll('reference_member_name[]');
    for(let i = 0; i < refNames.length; i++) {
        if(refNames[i].trim() !== '') {
            references.push({ name: refNames[i].trim() });
        }
    }
    memberData.references = references;
    
    
    const sigImg = document.getElementById('addSigPreview');
    if(sigImg && sigImg.src && sigImg.src.startsWith('data:')) {
         memberData.signature = sigImg.src;
    }
    
    const ppImg = document.getElementById('addPhotoPreview');
    if(ppImg && ppImg.src && ppImg.src.startsWith('data:')) {
         memberData.passport_photo = ppImg.src;
    }
    
    try {
        await addDoc(collection(db, 'member'), memberData);
        if (typeof showToast === 'function') {
            showToast('success', 'Member Registered', 'Successfully added member account: ' + account_number);
        } else {
            alert('New member registered successfully! Account Number: ' + account_number);
        }
        closeModal('addMemberModal');
        if(typeof loadMembersData === 'function') {
             loadMembersData();
        } else if (typeof loadDashboardData === 'function') {
             loadDashboardData();
        } else {
             window.location.reload();
        }
    } catch(e) {
        console.error(e);
        if (typeof showToast === 'function') {
            showToast('error', 'Registration Failed', e.message);
        } else {
            alert("Error: " + e.message);
        }
    } finally {
        if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Register Member';
    }
};

auth.onAuthStateChanged(async (user) => {
    if (user) {
        document.getElementById('adminPhotoStaffId').value = user.uid;
        try {
            const staffDoc = await getDoc(doc(db, 'staff', user.uid));
            if (staffDoc.exists()) {
                const data = staffDoc.data();
                if (data.profile_photo) {
                    const img = document.getElementById('adminPhotoPreview');
                    img.src = '../uploads/' + data.profile_photo;
                    img.classList.remove('hidden');
                    document.getElementById('adminPhotoPlaceholderIcon').classList.add('hidden');
                    document.getElementById('adminPhotoCurrentPhoto').value = data.profile_photo;
                    document.getElementById('btnRemoveAdminPhoto').style.display = 'inline-block';
                }
            }
        } catch(e) {
            console.error(e);
        }
    }
});
</script>

  <!-- Pure Member Photo Lightbox Modal (Displays Image Only) -->
  <div id="photoPreviewModal" class="modal-overlay photo-pure-overlay hidden" onclick="if(event.target === this) closeModal('photoPreviewModal');">
    <div class="pure-photo-lightbox-card">
      <button type="button" class="pure-lightbox-close-btn" onclick="closeModal('photoPreviewModal')" aria-label="Close Preview" title="Close Preview">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <img id="lightboxPhotoImg" src="" alt="Member Photo" class="pure-lightbox-img">
      <div id="lightboxNoPhotoFallback" class="pure-lightbox-fallback hidden">
        <i class="fa-solid fa-image-slash"></i>
        <span>No image available</span>
      </div>
    </div>
  </div>

  <!-- View Member Details Modal Structured View -->
  <div id="viewMemberModal" class="modal-overlay hidden">
    <div class="modal-card modal-view-member-card" style="max-width: 780px; max-height: 88vh; overflow-y: auto;">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-id-card-clip" style="color: var(--primary-teal); font-size: 1.3rem;"></i>
          <h3 style="margin: 0; font-size: 1.25rem;">Member Profile Record</h3>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <span id="viewStatusBadge"></span>
        </div>
      </div>
      
      <div class="member-profile-modal-container">
        
        <!-- Top Section: Photo (2x2) & Identity -->
        <div class="view-modal-hero-section">
          <div class="passport-photo-frame-2x2">
            <div class="passport-2x2-slot" id="viewPassportPhotoSlot">
              <i class="fa-solid fa-user placeholder-photo"></i>
            </div>
            <div class="passport-2x2-badge">Official 2x2 Photo</div>
          </div>

          <div class="view-modal-hero-info">
            <div class="hero-name-row">
              <h2 id="viewName" class="hero-member-name"></h2>
              <div class="hero-account-badge">
                <span class="account-badge-label">Account No:</span>
                <strong id="viewAccNo" class="account-tag-lg"></strong>
              </div>
            </div>

            <div class="hero-details-grid">
              <div class="hero-detail-item">
                <span class="detail-label">Civil Status</span>
                <span class="detail-val" id="viewCivil"></span>
              </div>
              <div class="hero-detail-item">
                <span class="detail-label">Sex / Gender</span>
                <span class="detail-val" id="viewMemberSex"></span>
              </div>
              <div class="hero-detail-item">
                <span class="detail-label">Date of Birth &amp; Age</span>
                <span class="detail-val" id="viewAge"></span>
              </div>
              <div class="hero-detail-item">
                <span class="detail-label">Nationality</span>
                <span class="detail-val" id="viewMemberNationality"></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 1: Contact & Address Information -->
        <div class="view-section-card">
          <h5 class="view-section-heading"><i class="fa-solid fa-address-book text-cyan"></i> Contact &amp; Residence Information</h5>
          <div class="view-section-grid">
            <div class="view-grid-field">
              <span class="detail-label">Contact Number</span>
              <span class="detail-val" id="viewContact"></span>
            </div>
            <div class="view-grid-field">
              <span class="detail-label">Email Address</span>
              <span class="detail-val" id="viewEmail"></span>
            </div>
            <div class="view-grid-field full-col">
              <span class="detail-label">Home Address</span>
              <span class="detail-val" id="viewAddress"></span>
            </div>
          </div>
        </div>

        <!-- Section 2: Employment & Business -->
        <div class="view-section-card">
          <h5 class="view-section-heading"><i class="fa-solid fa-briefcase text-purple"></i> Employment &amp; Business Information</h5>
          <div class="view-section-grid">
            <div class="view-grid-field">
              <span class="detail-label">Occupation / Profession</span>
              <span class="detail-val" id="viewEmployerOccupation"></span>
            </div>
            <div class="view-grid-field">
              <span class="detail-label">Employer / Business Name</span>
              <span class="detail-val" id="viewEmployer"></span>
            </div>
            <div class="view-grid-field full-col">
              <span class="detail-label">Employer Business Address</span>
              <span class="detail-val" id="viewEmployerAddress"></span>
            </div>
          </div>
        </div>

        <!-- Section 3: Family Information -->
        <div class="view-section-card" id="viewFamilyInfoSection">
          <h5 class="view-section-heading"><i class="fa-solid fa-people-roof text-emerald"></i> Family Information</h5>
          <div class="family-structured-grid" id="viewFamilyStructuredGrid">
            <div class="family-subcard" id="viewParentsSubcard">
              <span class="subcard-title"><i class="fa-solid fa-users text-cyan me-1"></i> Parents Information</span>
              <div id="viewParentsBox" class="subcard-content"></div>
            </div>
            <div class="family-subcard" id="viewSpouseSubcard">
              <span class="subcard-title"><i class="fa-solid fa-heart text-danger me-1"></i> Spouse Information</span>
              <div id="viewSpouseBox" class="subcard-content"></div>
            </div>
          </div>

          <div style="margin-top: 0.85rem;" id="viewChildrenSection">
            <span class="subcard-title"><i class="fa-solid fa-children text-cyan me-1"></i> Children Registered</span>
            <div id="viewChildrenBox" class="mt-1"></div>
          </div>
        </div>

        <!-- Section 4: Signature Verification -->
        <div class="view-section-card">
          <h5 class="view-section-heading"><i class="fa-solid fa-signature text-amber"></i> Handwritten E-Signature</h5>
          <div id="viewSignatureBox" class="view-signature-container"></div>
        </div>

      </div>

      <!-- Action Buttons with Distinct Theme Colors & Proper Spacing -->
      <div class="modal-actions view-modal-actions">
        <button type="button" class="btn btn-action-print" onclick="printMemberRecord(document.getElementById('viewAccNo').innerText.trim())">
          <i class="fa-solid fa-print"></i> Print Record
        </button>
        <button type="button" class="btn btn-action-download" onclick="closeModal('viewMemberModal'); openDownloadMemberModal(document.getElementById('viewAccNo').innerText.trim());">
          <i class="fa-solid fa-file-arrow-down"></i> Download File
        </button>
        <button type="button" class="btn btn-secondary btn-action-close" onclick="closeModal('viewMemberModal')">
          <i class="fa-solid fa-xmark"></i> Close View
        </button>
      </div>
    </div>
  </div>

  <!-- Audit Modification Details Modal -->
  <div id="auditDetailsModal" class="modal-overlay hidden">
    <div class="modal-card scrollable-modal-card audit-details-modal-card">
      <div class="modal-header">
        <h3><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary-teal);"></i> Audit Modification Record</h3>
      </div>
      
      <div class="audit-modal-body scrollable-modal-body">
        <!-- Header Metadata Card -->
        <div class="audit-header-banner">
          <div class="audit-avatar-frame" id="auditMemberPhotoSlot">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="audit-meta-banner">
            <div class="meta-item">
              <span class="meta-label">Account No:</span>
              <div><span id="auditModalAccNo" class="account-tag"></span></div>
            </div>
            <div class="meta-item">
              <span class="meta-label">Member Name:</span>
              <div><span id="auditModalName" class="audit-member-name"></span></div>
            </div>
            <div class="meta-item">
              <span class="meta-label">Action Made:</span>
              <div><span id="auditModalAction" class="status-pill status-active"></span></div>
            </div>
            <div class="meta-item">
              <span class="meta-label">Timestamp:</span>
              <div><span id="auditModalTime" class="meta-val-time"></span></div>
            </div>
            <div class="meta-item full-col">
              <span class="meta-label">Staff Responsible:</span>
              <span id="auditModalStaff" class="meta-val-staff"></span>
            </div>
          </div>
        </div>

        <!-- Modification Record Content -->
        <div class="audit-modification-card">
          <h4 class="audit-mod-title">
            <i class="fa-solid fa-file-pen"></i> Modifications &amp; Edits Record
          </h4>
          <div id="auditModificationContent">
            <!-- Dynamic modifications content injected by JS -->
          </div>
        </div>
      </div>
      
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" class="btn btn-secondary btn-sm" id="auditViewProfileBtn" onclick="closeModal('auditDetailsModal'); viewMember(activeAuditAccNo);" style="color: var(--primary-teal); border-color: var(--badge-border); font-weight: 600;">
          <i class="fa-solid fa-id-card"></i> View Full Member Profile
        </button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('auditDetailsModal')">
          <i class="fa-solid fa-xmark"></i> Close Details
        </button>
      </div>
    </div>
  </div>

  <!-- Export Validation Modal -->
  <div id="exportValidationModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 520px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-clipboard-check" style="color: var(--primary-teal);"></i> Confirm Report Download Criteria</h3>
      </div>
      <div class="export-validation-body">
        <p style="color: var(--text-main); font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem;">
          Are you sure you want to download this compiled report?
        </p>
        
        <div class="criteria-summary-card">
          <div class="criteria-row">
            <span class="criteria-label"><i class="fa-solid fa-folder-tree"></i> Report Type:</span>
            <strong class="criteria-val" id="valReportType">General Member Information</strong>
          </div>
          <div class="criteria-row">
            <span class="criteria-label"><i class="fa-solid fa-calendar-days"></i> Timeline Option:</span>
            <strong class="criteria-val" id="valTimeline">Most Recent (within 30-day span)</strong>
          </div>
          <div class="criteria-row">
            <span class="criteria-label"><i class="fa-solid fa-layer-group"></i> Data Scope:</span>
            <strong class="criteria-val" id="valScope">All Registered Records</strong>
          </div>
          <div class="criteria-row">
            <span class="criteria-label"><i class="fa-solid fa-file-code"></i> Selected Format:</span>
            <strong class="criteria-val" id="valFormat">PDF Document (.pdf)</strong>
          </div>
        </div>

        <p style="color: #fbbf24; font-size: 0.8rem; margin-top: 1rem; display: flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-triangle-exclamation"></i> Action will download the report and log an entry in the system live preview.
        </p>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; align-items: center; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--bg-card-border);">
        <button type="button" class="btn btn-secondary btn-cancel" onclick="closeModal('exportValidationModal')">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="confirmExportAction()" style="display: inline-flex; align-items: center; gap: 0.45rem;">
          <i class="fa-solid fa-download"></i> <span>Download Report</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Report Custom Date Range Validation Error Modal -->
  <div id="reportDateValidationModal" class="modal-overlay hidden" style="z-index: 100008 !important;">
    <div class="modal-card" style="max-width: 520px;">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #f43f5e; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span id="reportDateErrorHeader">Invalid Custom Date Range</span>
        </h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('reportDateValidationModal')" aria-label="Close modal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="modal-body" style="padding-top: 0.85rem;">
        <!-- Alert Box -->
        <div style="background: rgba(244, 63, 94, 0.12); border: 1px solid rgba(244, 63, 94, 0.35); border-radius: 12px; padding: 1rem; margin-bottom: 1rem;">
          <h4 id="reportDateErrorTitle" style="color: #f87171; font-size: 0.95rem; font-weight: 700; margin: 0 0 0.45rem 0; display: flex; align-items: center; gap: 0.45rem;">
            <i class="fa-solid fa-circle-xmark"></i> Cannot Proceed with Report Action
          </h4>
          <p id="reportDateErrorMessage" style="color: var(--text-main); font-size: 0.88rem; line-height: 1.5; margin: 0;">
            Both the Start Date and End Date inputs are invalid or missing.
          </p>
        </div>

        <!-- Reason Card -->
        <div style="background: var(--bg-card-subtle, rgba(255,255,255,0.03)); border: 1px solid var(--bg-card-border); border-radius: 10px; padding: 0.85rem 1rem;">
          <div style="font-size: 0.82rem; font-weight: 700; color: var(--primary-teal); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-circle-info"></i> Why you cannot proceed:
          </div>
          <p id="reportDateErrorReason" style="font-size: 0.82rem; color: var(--text-dim); line-height: 1.45; margin: 0;">
            The system requires chronological start and end boundaries to compile member records and audit entries accurately.
          </p>
        </div>
      </div>

      <div class="modal-actions" style="margin-top: 1.25rem; display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--bg-card-border);">
        <button type="button" class="btn btn-primary" onclick="closeModal('reportDateValidationModal'); focusReportCustomDates();">
          <i class="fa-solid fa-calendar-check me-1"></i> Adjust Date Range
        </button>
      </div>
    </div>
  </div>

  <!-- Document Live Preview Modal -->
  <div id="reportDocumentPreviewModal" class="modal-overlay hidden" style="z-index: 100007 !important;">
    <div class="modal-card report-doc-preview-modal-card" style="max-width: 920px; width: 95vw; max-height: 90vh; display: flex; flex-direction: column; padding: 1.5rem;">
      <div class="modal-header d-flex justify-content-between align-items-center pb-2" style="border-bottom: 1px solid var(--bg-card-border);">
        <div class="d-flex align-items-center gap-2">
          <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-file-lines text-cyan"></i>
            <span>Document Preview</span>
          </h3>
          <span class="preview-file-badge" id="previewDocFileBadge" style="background: rgba(15, 118, 110, 0.2); border: 1px solid var(--primary-teal); color: var(--primary-teal); font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 6px;">
            Cooperative_Report.pdf
          </span>
        </div>
        <button type="button" class="btn-close-modal" onclick="closeModal('reportDocumentPreviewModal')" aria-label="Close preview" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Scrollable Document View Canvas -->
      <div class="modal-body doc-preview-scroll-container" style="overflow-y: auto; padding: 1.25rem 0.5rem; flex: 1;">
        <!-- Preview Meta Info Bar -->
        <div class="doc-preview-meta-bar" style="background: var(--bg-card-subtle, rgba(255,255,255,0.03)); border: 1px solid var(--bg-card-border); border-radius: 10px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; font-size: 0.82rem;">
          <div><span style="color: var(--text-dim);">Report ID:</span> <strong id="docPreviewReportId" style="color: var(--primary-teal);">RPT-2026-000</strong></div>
          <div><span style="color: var(--text-dim);">Category:</span> <strong id="docPreviewCategory" style="color: var(--text-main);">General Member Information</strong></div>
          <div><span style="color: var(--text-dim);">Timeline:</span> <strong id="docPreviewTimeline" style="color: var(--text-main);">Most Recent</strong></div>
          <div><span style="color: var(--text-dim);">Data Scope:</span> <strong id="docPreviewScope" style="color: var(--text-main);">All Records</strong></div>
          <div><span style="color: var(--text-dim);">Format:</span> <strong id="docPreviewFormat" style="color: var(--primary-teal);">PDF Document</strong></div>
        </div>

        <!-- Rendered Sheet Canvas (A4 Document Look) -->
        <div class="doc-sheet-canvas" id="docSheetCanvas" style="background: #ffffff; color: #111827; border-radius: 8px; padding: 2rem 2.25rem; box-shadow: 0 10px 30px rgba(0,0,0,0.35); font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4;">
          <!-- Document Header -->
          <div style="border-bottom: 2.5px solid #0f766e; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h2 style="font-size: 16px; margin: 0 0 3px 0; color: #0f766e; font-weight: 800; text-transform: uppercase;">SPACE COOPERATIVE - SYSTEM COMPILED REPORT</h2>
              <p style="margin: 0; font-size: 10px; color: #374151; font-weight: 600;">Official Cooperative System Report Export &bull; Confidential Record</p>
            </div>
            <div style="text-align: right;">
              <div id="sheetReportId" style="font-weight: bold; color: #0f766e; font-size: 12px;">REPORT ID: RPT-2026-000</div>
              <div id="sheetGeneratedTime" style="font-size: 9.5px; color: #4b5563;">Generated: <?= date('m/d/Y, h:i A') ?></div>
            </div>
          </div>

          <!-- Document Meta Box -->
          <div style="background: #f0fdfa; border: 1px solid #ccfbf1; padding: 10px 14px; margin-bottom: 14px; border-radius: 6px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; font-size: 10.5px; color: #1e293b;">
            <div><strong style="color: #0f766e;">Category:</strong> <span id="sheetMetaCategory">General Member Information</span></div>
            <div><strong style="color: #0f766e;">Timeline:</strong> <span id="sheetMetaTimeline">Most Recent (within 30-day span)</span></div>
            <div><strong style="color: #0f766e;">Data Scope:</strong> <span id="sheetMetaScope">All Registered Records</span></div>
            <div><strong style="color: #0f766e;">Integrity:</strong> <span style="color: #059669; font-weight: 600;">Audit Verified &amp; Active</span></div>
          </div>

          <!-- Table Container -->
          <div style="overflow-x: auto; margin-top: 10px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 10.5px;" id="docPreviewContentTable">
              <thead>
                <tr style="background: #f3f4f6; color: #111827; text-align: left;">
                  <th style="border: 1px solid #d1d5db; padding: 7px 9px; width: 14%;">Report ID</th>
                  <th style="border: 1px solid #d1d5db; padding: 7px 9px; width: 22%;">Category</th>
                  <th style="border: 1px solid #d1d5db; padding: 7px 9px; width: 28%;">Description</th>
                  <th style="border: 1px solid #d1d5db; padding: 7px 9px; width: 18%;">Inclusion Date</th>
                  <th style="border: 1px solid #d1d5db; padding: 7px 9px; width: 10%;">Compilation Date</th>
                  <th style="border: 1px solid #d1d5db; padding: 7px 9px; width: 8%;">Exported By</th>
                </tr>
              </thead>
              <tbody id="docPreviewTableBody">
                <!-- Dynamically populated from active preview data -->
              </tbody>
            </table>
          </div>

          <!-- Document Footer Note -->
          <div style="margin-top: 18px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #6b7280; text-align: center; display: flex; justify-content: space-between;">
            <span>SPACE Multipurpose Cooperative Portal &bull; Official System Export</span>
            <span>Document Status: <strong style="color: #059669;">Verified Active Record</strong></span>
          </div>
        </div>
      </div>

      <div class="modal-actions d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid var(--bg-card-border); gap: 0.75rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('reportDocumentPreviewModal')">
          <i class="fa-solid fa-arrow-left me-1"></i> Close Preview
        </button>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-purple-accent" onclick="printFromPreviewModal()">
            <i class="fa-solid fa-print me-1"></i> Print This Report
          </button>
          <button type="button" class="btn btn-primary" onclick="downloadFromPreviewModal()">
            <i class="fa-solid fa-download me-1"></i> Download File
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Report Inclusion Date Details Modal -->
  <div id="reportInclusionDetailsModal" class="modal-overlay hidden" style="z-index: 100006 !important;">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-calendar-days text-cyan"></i>
          <span>Timeline Inclusion Details</span>
        </h3>
        <button type="button" class="btn-close-modal" onclick="closeModal('reportInclusionDetailsModal')" aria-label="Close modal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="modal-body" style="padding-top: 0.85rem;">
        <div style="background: var(--bg-card-subtle, rgba(255,255,255,0.03)); border: 1px solid var(--bg-card-border); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
          <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 0.35rem;">Inclusion Window:</div>
          <div id="inclusionModalDateRange" style="font-size: 1.05rem; font-weight: 800; color: var(--primary-teal); margin-bottom: 0.4rem;">08/01/2026 - 08/31/2026</div>
          <div id="inclusionModalTag" style="font-size: 0.82rem; color: var(--text-main);">Current Month - August 2026</div>
        </div>
        <p style="font-size: 0.82rem; color: var(--text-dim); line-height: 1.5; margin: 0;">
          All member profile modifications, survey answers, or audit activity recorded within this specified date span are encompassed in this compiled report.
        </p>
      </div>
      <div class="modal-actions" style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
        <button type="button" class="btn btn-secondary" onclick="closeModal('reportInclusionDetailsModal')">Close</button>
      </div>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div id="logoutModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-arrow-right-from-bracket" style="color: #f43f5e;"></i> Confirm Logout</h3>
      </div>
      <div class="modal-body" style="padding-top: 0.5rem;">
        <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem;">
          Are you sure you want to log out?
        </p>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
          You will need to sign in again to access the Staff Portal.
        </p>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <button type="button" class="btn btn-cancel" onclick="closeModal('logoutModal')">Cancel</button>
        <a href="<?= e(BASE_URL) ?>/logout.php" class="btn btn-danger" style="display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
      </div>
    </div>
  </div>

  <!-- Member Records Custom Date Range Modal -->
  <div id="memberCustomDateModal" class="modal-overlay hidden" style="z-index: 100005 !important;">
    <div class="modal-card" style="max-width: 520px;">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i>
          <span>Custom Timeline Filter</span>
        </h3>
        <button type="button" class="btn-close-modal" onclick="closeMemberCustomDateModal()" aria-label="Close modal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem;">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="modal-body" style="padding-top: 0.85rem;">
        <p style="color: var(--text-dim); font-size: 0.86rem; margin-bottom: 1.25rem; line-height: 1.5;">
          Select the start and end date boundaries to filter cooperative member records registered within that specific timeframe.
        </p>

        <div id="memberCustomDateError" class="modal-error-box hidden" style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 0.65rem 0.9rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.82rem; display: flex; align-items: center; gap: 0.45rem;">
          <i class="fa-solid fa-circle-exclamation"></i> <span id="memberCustomDateErrorText">Start date cannot be after end date.</span>
        </div>

        <div class="custom-date-inputs" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
          <div class="date-input-group" style="display: flex; flex-direction: column; gap: 0.4rem;">
            <label for="memberCustomStartDate" style="font-size: 0.82rem; font-weight: 700; color: var(--text-main);">Start Date:</label>
            <input type="date" id="memberCustomStartDate" class="custom-date-input" style="width: 100%; height: 42px; padding: 0.45rem 0.75rem; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); font-size: 0.88rem; box-sizing: border-box;">
          </div>
          <div class="date-input-group" style="display: flex; flex-direction: column; gap: 0.4rem;">
            <label for="memberCustomEndDate" style="font-size: 0.82rem; font-weight: 700; color: var(--text-main);">End Date:</label>
            <input type="date" id="memberCustomEndDate" class="custom-date-input" style="width: 100%; height: 42px; padding: 0.45rem 0.75rem; border-radius: 10px; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main); font-size: 0.88rem; box-sizing: border-box;">
          </div>
        </div>
      </div>

      <div class="modal-actions" style="margin-top: 0.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid var(--bg-card-border);">
        <button type="button" class="btn btn-cancel" onclick="closeMemberCustomDateModal()">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="applyMemberCustomDateRange()">
          <i class="fa-solid fa-check"></i> Apply Date Range
        </button>
      </div>
    </div>
  </div>

  <!-- Reusable System Confirmation Modal -->
  <div id="systemConfirmModal" class="modal-overlay hidden" style="z-index: 100001 !important;">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3 id="systemConfirmTitle"><i class="fa-solid fa-circle-question" style="color: var(--primary-teal);"></i> Confirmation</h3>
      </div>
      <div class="modal-body" style="padding-top: 0.5rem;">
        <p id="systemConfirmMessage" style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem; line-height: 1.5;">
          Are you sure you want to proceed?
        </p>
      </div>
      <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="button" class="btn btn-cancel" id="systemConfirmCancelBtn" onclick="closeModal('systemConfirmModal')">Cancel</button>
        <button type="button" class="btn btn-primary" id="systemConfirmProceedBtn">Proceed</button>
      </div>
    </div>
  </div>


  <!-- Restore Member Confirmation Modal -->
  <div id="restoreModal" class="modal-overlay hidden">
    <div class="modal-card" style="max-width: 480px;">
      <div class="modal-header">
        <h3><i class="fa-solid fa-rotate-left" style="color: #4ade80;"></i> Restore Member Record</h3>
      </div>
      <form id="restoreMemberForm" action="<?= e(BASE_URL) ?>/staff/restore_member.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" id="restoreAccNoHidden" name="account_number">
        <p style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem;">
          Are you sure you truly want to restore <strong id="restoreTargetName" style="color: #4ade80;">this member</strong> (<span id="restoreTargetAcc"></span>)?
        </p>
        <p style="color: #94a3b8; font-size: 0.85rem;">
          This action will restore the member to the portal and set their account status back to Active. All Action buttons will return.
        </p>
        <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('restoreModal')">Cancel</button>
          <button type="submit" class="btn btn-primary" style="background-color: #4ade80; border-color: #4ade80; color: #fff;"><i class="fa-solid fa-rotate-left"></i> Confirm</button>
        </div>
      </form>
    </div>
  </div>

