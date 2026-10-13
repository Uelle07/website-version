<!-- Custom CSS for calendar icon visibility & layout enhancements -->
<style>
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }
    .text-danger { color: #ff6b6b !important; }
</style>

<!-- Centered Update Info Layout Wrapper -->
<div class="dashboard-wrapper" style="max-width: 1040px; margin: 20px auto 60px auto;">
    <!-- Back to Dashboard Navigation Button -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <a href="dashboard.php" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-2" style="border-radius: 20px; padding: 6px 16px; backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.08); border-color: rgba(255,255,255,0.2);">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <!-- Error Display Container -->
    
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
    <form id="memberUpdateForm" method="post" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="4479227576fd31ffddf3ba6c9eb92eeee3902278fe4d678ed2ce9c73f66145c6">

        <!-- Profile Picture Section -->
        <h5 class="mb-3 text-white border-bottom pb-2"><i class="fa-solid fa-camera me-2"></i> Profile Picture (Passport Photo)</h5>
        <div class="row g-3 mb-4 align-items-center">
            <div class="col-auto">
                <div class="profile-photo-preview-box" style="width: 100px; height: 100px; border-radius: 14px; border: 2px dashed var(--primary-teal); overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                    <img id="memberPhotoPreview" src="http://localhost/uploads/photo_2_photo_1789558552.png" alt="Current Photo" style="width: 100%; height: 100%; object-fit: cover;">
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
                <input type="text" class="form-control" name="firstname"
                       value="Mary Elizabeth"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="middlename"
                       value="T."
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
            </div>
            <div class="col-md-3">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="lastname"
                       value="De Luna"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Ext.</label>
                <select class="form-select" name="extension_name">
                    <option value="">None</option>
                                            <option value="Jr." >
                            Jr.                        </option>
                                            <option value="Sr." >
                            Sr.                        </option>
                                            <option value="II" >
                            II                        </option>
                                            <option value="III" >
                            III                        </option>
                                            <option value="IV" >
                            IV                        </option>
                                            <option value="V" >
                            V                        </option>
                                    </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Sex <span class="text-danger">*</span></label>
                <select class="form-select" name="sex" id="memberSex" required>
                    <option value="">Select Sex</option>
                                            <option value="Male" >
                            Male                        </option>
                                            <option value="Female" selected>
                            Female                        </option>
                                    </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Civil Status <span class="text-danger">*</span></label>
                <select class="form-select" id="civil_status_select" name="civil_status" required>
                                            <option value="Single" selected>
                            Single                        </option>
                                            <option value="Married" >
                            Married                        </option>
                                            <option value="Widowed" >
                            Widowed                        </option>
                                            <option value="Separated" >
                            Separated                        </option>
                                            <option value="Divorced" >
                            Divorced                        </option>
                                    </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nationality <span class="text-danger">*</span></label>
                <select class="form-select" name="nationality" id="memberNationality" required>
                    <option value="">Select Nationality</option>
                                            <option value="Filipino" selected>Filipino</option>
                                            <option value="Afghan" >Afghan</option>
                                            <option value="Albanian" >Albanian</option>
                                            <option value="Algerian" >Algerian</option>
                                            <option value="American" >American</option>
                                            <option value="Andorran" >Andorran</option>
                                            <option value="Angolan" >Angolan</option>
                                            <option value="Argentine" >Argentine</option>
                                            <option value="Armenian" >Armenian</option>
                                            <option value="Australian" >Australian</option>
                                            <option value="Austrian" >Austrian</option>
                                            <option value="Azerbaijani" >Azerbaijani</option>
                                            <option value="Bahamian" >Bahamian</option>
                                            <option value="Bahraini" >Bahraini</option>
                                            <option value="Bangladeshi" >Bangladeshi</option>
                                            <option value="Barbadian" >Barbadian</option>
                                            <option value="Belarusian" >Belarusian</option>
                                            <option value="Belgian" >Belgian</option>
                                            <option value="Belizean" >Belizean</option>
                                            <option value="Beninese" >Beninese</option>
                                            <option value="Bhutanese" >Bhutanese</option>
                                            <option value="Bolivian" >Bolivian</option>
                                            <option value="Bosnian" >Bosnian</option>
                                            <option value="Botswanan" >Botswanan</option>
                                            <option value="Brazilian" >Brazilian</option>
                                            <option value="British" >British</option>
                                            <option value="Bruneian" >Bruneian</option>
                                            <option value="Bulgarian" >Bulgarian</option>
                                            <option value="Burkinabe" >Burkinabe</option>
                                            <option value="Burmese" >Burmese</option>
                                            <option value="Burundian" >Burundian</option>
                                            <option value="Cambodian" >Cambodian</option>
                                            <option value="Cameroonian" >Cameroonian</option>
                                            <option value="Canadian" >Canadian</option>
                                            <option value="Cape Verdean" >Cape Verdean</option>
                                            <option value="Central African" >Central African</option>
                                            <option value="Chadian" >Chadian</option>
                                            <option value="Chilean" >Chilean</option>
                                            <option value="Chinese" >Chinese</option>
                                            <option value="Colombian" >Colombian</option>
                                            <option value="Comoran" >Comoran</option>
                                            <option value="Congolese" >Congolese</option>
                                            <option value="Costa Rican" >Costa Rican</option>
                                            <option value="Croatian" >Croatian</option>
                                            <option value="Cuban" >Cuban</option>
                                            <option value="Cypriot" >Cypriot</option>
                                            <option value="Czech" >Czech</option>
                                            <option value="Danish" >Danish</option>
                                            <option value="Djiboutian" >Djiboutian</option>
                                            <option value="Dominican" >Dominican</option>
                                            <option value="Dutch" >Dutch</option>
                                            <option value="East Timorese" >East Timorese</option>
                                            <option value="Ecuadorean" >Ecuadorean</option>
                                            <option value="Egyptian" >Egyptian</option>
                                            <option value="Emirati" >Emirati</option>
                                            <option value="Equatorial Guinean" >Equatorial Guinean</option>
                                            <option value="Eritrean" >Eritrean</option>
                                            <option value="Estonian" >Estonian</option>
                                            <option value="Ethiopian" >Ethiopian</option>
                                            <option value="Fijian" >Fijian</option>
                                            <option value="Finnish" >Finnish</option>
                                            <option value="French" >French</option>
                                            <option value="Gabonese" >Gabonese</option>
                                            <option value="Gambian" >Gambian</option>
                                            <option value="Georgian" >Georgian</option>
                                            <option value="German" >German</option>
                                            <option value="Ghanaian" >Ghanaian</option>
                                            <option value="Greek" >Greek</option>
                                            <option value="Grenadian" >Grenadian</option>
                                            <option value="Guatemalan" >Guatemalan</option>
                                            <option value="Guinean" >Guinean</option>
                                            <option value="Guyanese" >Guyanese</option>
                                            <option value="Haitian" >Haitian</option>
                                            <option value="Honduran" >Honduran</option>
                                            <option value="Hungarian" >Hungarian</option>
                                            <option value="Icelander" >Icelander</option>
                                            <option value="Indian" >Indian</option>
                                            <option value="Indonesian" >Indonesian</option>
                                            <option value="Iranian" >Iranian</option>
                                            <option value="Iraqi" >Iraqi</option>
                                            <option value="Irish" >Irish</option>
                                            <option value="Israeli" >Israeli</option>
                                            <option value="Italian" >Italian</option>
                                            <option value="Ivorian" >Ivorian</option>
                                            <option value="Jamaican" >Jamaican</option>
                                            <option value="Japanese" >Japanese</option>
                                            <option value="Jordanian" >Jordanian</option>
                                            <option value="Kazakh" >Kazakh</option>
                                            <option value="Kenyan" >Kenyan</option>
                                            <option value="Korean (South)" >Korean (South)</option>
                                            <option value="Korean (North)" >Korean (North)</option>
                                            <option value="Kuwaiti" >Kuwaiti</option>
                                            <option value="Kyrgyz" >Kyrgyz</option>
                                            <option value="Laotian" >Laotian</option>
                                            <option value="Latvian" >Latvian</option>
                                            <option value="Lebanese" >Lebanese</option>
                                            <option value="Liberian" >Liberian</option>
                                            <option value="Libyan" >Libyan</option>
                                            <option value="Liechtensteiner" >Liechtensteiner</option>
                                            <option value="Lithuanian" >Lithuanian</option>
                                            <option value="Luxembourgish" >Luxembourgish</option>
                                            <option value="Macedonian" >Macedonian</option>
                                            <option value="Malagasy" >Malagasy</option>
                                            <option value="Malawian" >Malawian</option>
                                            <option value="Malaysian" >Malaysian</option>
                                            <option value="Maldivian" >Maldivian</option>
                                            <option value="Malian" >Malian</option>
                                            <option value="Maltese" >Maltese</option>
                                            <option value="Mauritanian" >Mauritanian</option>
                                            <option value="Mauritian" >Mauritian</option>
                                            <option value="Mexican" >Mexican</option>
                                            <option value="Micronesian" >Micronesian</option>
                                            <option value="Moldovan" >Moldovan</option>
                                            <option value="Monacan" >Monacan</option>
                                            <option value="Mongolian" >Mongolian</option>
                                            <option value="Montenegrin" >Montenegrin</option>
                                            <option value="Moroccan" >Moroccan</option>
                                            <option value="Mozambican" >Mozambican</option>
                                            <option value="Namibian" >Namibian</option>
                                            <option value="Nauruan" >Nauruan</option>
                                            <option value="Nepalese" >Nepalese</option>
                                            <option value="New Zealander" >New Zealander</option>
                                            <option value="Nicaraguan" >Nicaraguan</option>
                                            <option value="Nigerian" >Nigerian</option>
                                            <option value="Nigerien" >Nigerien</option>
                                            <option value="Norwegian" >Norwegian</option>
                                            <option value="Omani" >Omani</option>
                                            <option value="Pakistani" >Pakistani</option>
                                            <option value="Palauan" >Palauan</option>
                                            <option value="Palestinian" >Palestinian</option>
                                            <option value="Panamanian" >Panamanian</option>
                                            <option value="Papua New Guinean" >Papua New Guinean</option>
                                            <option value="Paraguayan" >Paraguayan</option>
                                            <option value="Peruvian" >Peruvian</option>
                                            <option value="Polish" >Polish</option>
                                            <option value="Portuguese" >Portuguese</option>
                                            <option value="Qatari" >Qatari</option>
                                            <option value="Romanian" >Romanian</option>
                                            <option value="Russian" >Russian</option>
                                            <option value="Rwandan" >Rwandan</option>
                                            <option value="Saint Lucian" >Saint Lucian</option>
                                            <option value="Salvadoran" >Salvadoran</option>
                                            <option value="Samoan" >Samoan</option>
                                            <option value="Saudi Arabian" >Saudi Arabian</option>
                                            <option value="Senegalese" >Senegalese</option>
                                            <option value="Serbian" >Serbian</option>
                                            <option value="Seychellois" >Seychellois</option>
                                            <option value="Sierra Leonean" >Sierra Leonean</option>
                                            <option value="Singaporean" >Singaporean</option>
                                            <option value="Slovak" >Slovak</option>
                                            <option value="Slovenian" >Slovenian</option>
                                            <option value="Solomon Islander" >Solomon Islander</option>
                                            <option value="Somali" >Somali</option>
                                            <option value="South African" >South African</option>
                                            <option value="Spanish" >Spanish</option>
                                            <option value="Sri Lankan" >Sri Lankan</option>
                                            <option value="Sudanese" >Sudanese</option>
                                            <option value="Surinamese" >Surinamese</option>
                                            <option value="Swazi" >Swazi</option>
                                            <option value="Swedish" >Swedish</option>
                                            <option value="Swiss" >Swiss</option>
                                            <option value="Syrian" >Syrian</option>
                                            <option value="Taiwanese" >Taiwanese</option>
                                            <option value="Tajik" >Tajik</option>
                                            <option value="Tanzanian" >Tanzanian</option>
                                            <option value="Thai" >Thai</option>
                                            <option value="Togolese" >Togolese</option>
                                            <option value="Tongan" >Tongan</option>
                                            <option value="Trinidadian" >Trinidadian</option>
                                            <option value="Tunisian" >Tunisian</option>
                                            <option value="Turkish" >Turkish</option>
                                            <option value="Turkmen" >Turkmen</option>
                                            <option value="Tuvaluan" >Tuvaluan</option>
                                            <option value="Ugandan" >Ugandan</option>
                                            <option value="Ukrainian" >Ukrainian</option>
                                            <option value="Uruguayan" >Uruguayan</option>
                                            <option value="Uzbek" >Uzbek</option>
                                            <option value="Vanuatu" >Vanuatu</option>
                                            <option value="Venezuelan" >Venezuelan</option>
                                            <option value="Vietnamese" >Vietnamese</option>
                                            <option value="Yemeni" >Yemeni</option>
                                            <option value="Zambian" >Zambian</option>
                                            <option value="Zimbabwean" >Zimbabwean</option>
                                    </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Birthday <span class="text-danger">*</span></label>
                <input type="date" class="form-control" name="birthday"
                       value="2000-01-01"
                       min="1900-01-01" max="2026-09-16" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="contact_number" id="memberContactInput"
                       value="+63 9978-798-7894" oninput="formatContactNumber(this)" placeholder="0917-123-4567" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Occupation</label>
                <input type="text" class="form-control" name="occupation"
                       value="Data Engineer"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer/Business Name</label>
                <input type="text" class="form-control" name="employer"
                       value="Sunnies Technology Inc.">
            </div>
            <div class="col-md-6">
                <label class="form-label">Employer Address</label>
                <input type="text" class="form-control" name="employer_address"
                       value="Maunlad Avenue, Malolos City, Bulacan">
            </div>
            <div class="col-md-12">
                <label class="form-label">Full Home Address <span class="text-danger">*</span></label>
                <textarea class="form-control" name="address" rows="2" required>BLK 22 L4 PH4A Grand Royale Subdv. Malolos City, Bulacan</textarea>
            </div>
        </div>

        <!-- Parents Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-users me-2 text-cyan"></i> Section V. Parents</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Father's Full Name</label>
                <input type="text" class="form-control" name="father_name"
                       value="Romeo G. De Luna"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Father's full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Mother's Full Name</label>
                <input type="text" class="form-control" name="mother_name"
                       value="Ana Constantine F. De Luna"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Mother's maiden/full name">
            </div>
        </div>

        <!-- Spouse Section (Visible only if Married) -->
        <div id="spouse-section" style="display: none;">
            <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-ring me-2 text-cyan"></i> Spouse Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Spouse Name</label>
                    <input type="text" class="form-control" name="spouse_name"
                           value=""
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Occupation</label>
                    <input type="text" class="form-control" name="spouse_occupation"
                           value=""
                           oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Employer</label>
                    <input type="text" class="form-control" name="spouse_employer"
                           value="">
                </div>
            </div>
        </div>

        <!-- Children Section (Available always) -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-child me-2 text-cyan"></i> Children Information</h5>
        <div id="children-wrapper">
            
Warning: Undefined array key "REQUEST_METHOD" in C:\xampp\htdocs\cooperative-member-information-system\member\update_info.php on line 490
            <div id="noChildrenNotice" class="text-muted mb-3" style="color: var(--text-dim) !important; font-size: 0.9rem; display: block;">
                No children entries added. Click "Add Child" below if you wish to add a child.
            </div>
                    </div>
        <button type="button" id="add-child" class="btn btn-sm btn-outline-secondary mb-4" onclick="addNewChildRow()">
            <i class="fa-solid fa-plus"></i> Add Child
        </button>

        <!-- Section VI. References (Two Cooperative Members) -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-user-group me-2 text-cyan"></i> Section VI. References (Two Cooperative Members)</h5>
        <div class="row g-3 mb-4">
                        <div class="col-md-6">
                <label class="form-label">Reference Member 1</label>
                <input type="text" class="form-control" name="reference_member_name[]" value="Ayesha Marquez"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Cooperative member full name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Reference Member 2</label>
                <input type="text" class="form-control" name="reference_member_name[]" value="Cynthia Del Mundo"
                       oninput="this.value = this.value.replace(/[^A-Za-z\s.\-]/g, '')" placeholder="Cooperative member full name">
            </div>
        </div>

        <!-- Signature Section -->
        <h5 class="mb-3 border-bottom pb-2" style="color: var(--text-main); font-weight: 700;"><i class="fa-solid fa-signature me-2 text-cyan"></i> Signature (Optional Photo)</h5>
        <div class="mb-4 d-flex align-items-center gap-3">
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
            <button type="button" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2" onclick="submitMemberUpdateForm();" style="border-radius: 12px; font-weight: 700;">
                <i class="fa-solid fa-floppy-disk"></i> <span>Save</span>
            </button>
        </div>
    </div>
</div>

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

function submitMemberUpdateForm() {
    closeMemberUpdateConfirm();
    document.getElementById('memberUpdateForm').submit();
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

function addNewChildRow(name = '', birthday = '') {
    const wrapper = document.getElementById('children-wrapper');
    const notice = document.getElementById('noChildrenNotice');
    if (notice) notice.style.display = 'none';
    const row = createChildRowElement(name, birthday);
    wrapper.appendChild(row);
}

function removeChildRow(btn) {
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

document.addEventListener('DOMContentLoaded', function () {
    const civilStatusSelect = document.getElementById('civil_status_select');
    const spouseSection = document.getElementById('spouse-section');

    function toggleSpouseSection() {
        if (civilStatusSelect.value === 'Married') {
            spouseSection.style.display = 'block';
        } else {
            spouseSection.style.display = 'none';
        }
    }

    civilStatusSelect.addEventListener('change', toggleSpouseSection);
    toggleSpouseSection();
});

function handleMemberSigUpload(event) {
    const file = event.target.files[0];
    const previewSlot = document.getElementById('memberSigPreviewSlot');
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showToast('Signature image must be 5 MB or smaller.', 'warning', 'File Size Limit Exceeded');
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