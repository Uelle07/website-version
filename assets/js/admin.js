/**
 * assets/js/admin.js
 * -------------------------------------------------------
 * Staff Portal — Core Interactive Logic & State Management.
 * Glassmorphic physics, database sync, interactive modals,
 * data validation, audit log diffs, direct file exports & toasts.
 * -------------------------------------------------------
 */

let showingPastInfo = false;
let printingTargetAcc = '';
let currentPhotoAcc = '';
let membersDatabase = {};
let activeAuditAccNo = '';
let activeAuditAction = '';
let activeAuditRemarks = '';

// ================= 1. DATABASE SYNCHRONIZATION =================
async function fetchMembersFromDatabase() {
  try {
    const endpoint = (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) 
      ? (window.BASE_URL.replace(/\/+$/, '') + '/staff/get_members.php') 
      : 'get_members.php';
    const response = await fetch(endpoint);
    if (!response.ok) {
      console.warn('Backend not connected yet. Running in offline/mock mode.');
      return;
    }
    membersDatabase = await response.json();
  } catch (error) {
    console.warn("Database sync notice:", error.message);
  }
}

// ================= 2. SIDEBAR COLLAPSE CONTROL =================
function updateSidebarToggleVisuals(isCollapsed) {
  const btn = document.getElementById('sidebarCollapseBtn');
  if (btn) {
    btn.title = isCollapsed ? 'Expand Sidebar (Stretch)' : 'Collapse Sidebar';
    btn.setAttribute('aria-label', isCollapsed ? 'Expand Sidebar Navigation' : 'Collapse Sidebar Navigation');
  }
}

function toggleSidebarCollapse() {
  if (window.innerWidth <= 991) {
    const isMobileOpen = document.body.classList.toggle('sidebar-mobile-open');
    document.documentElement.classList.toggle('sidebar-mobile-open', isMobileOpen);
    return;
  }
  const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
  document.body.classList.toggle('sidebar-collapsed', isCollapsed);
  try {
    localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? '1' : '0');
  } catch (e) {}
  
  updateSidebarToggleVisuals(isCollapsed);
}
window.toggleSidebarCollapse = toggleSidebarCollapse;
window.updateSidebarToggleVisuals = updateSidebarToggleVisuals;

// ================= 3. SYSTEM TOAST NOTIFICATIONS =================
function showToast(arg1, arg2 = 'info', arg3 = '', duration = 4500) {
  const validTypes = ['success', 'error', 'danger', 'warning', 'info'];
  let type = 'info';
  let title = '';
  let message = '';

  if (typeof arg1 === 'object' && arg1 !== null) {
    type = arg1.type || 'info';
    title = arg1.title || '';
    message = arg1.message || '';
  } else if (validTypes.includes(String(arg1).toLowerCase())) {
    type = String(arg1).toLowerCase();
    if (arg3 !== undefined && arg3 !== '') {
      title = String(arg2 || '');
      message = String(arg3 || '');
    } else {
      message = String(arg2 || '');
    }
  } else if (arg2 && validTypes.includes(String(arg2).toLowerCase())) {
    type = String(arg2).toLowerCase();
    message = String(arg1 || '');
    title = String(arg3 || '');
  } else {
    message = String(arg1 || '');
    if (arg2) title = String(arg2);
  }

  if (type === 'danger') type = 'error';

  if (!title) {
    title = type === 'success' ? 'Success' :
            type === 'error' ? 'Attention Required' :
            type === 'warning' ? 'Notice' : 'Information';
  }

  let container = document.getElementById('portalToastContainer') || document.querySelector('.portal-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'portalToastContainer';
    container.className = 'portal-toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `portal-toast portal-toast-${type} fade show`;
  toast.setAttribute('role', 'alert');

  let iconClass = 'fa-solid fa-circle-check';
  if (type === 'error') iconClass = 'fa-solid fa-triangle-exclamation';
  else if (type === 'warning') iconClass = 'fa-solid fa-triangle-exclamation';
  else if (type === 'info') iconClass = 'fa-solid fa-circle-info';

  toast.innerHTML = `
    <div class="toast-icon-wrapper">
      <i class="${iconClass}"></i>
    </div>
    <div class="toast-content">
      <h5 class="toast-title">${title}</h5>
      <p class="toast-message">${message}</p>
    </div>
    <button type="button" class="toast-close-btn" aria-label="Close notification" onclick="dismissToast(this.closest('.portal-toast'))">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="toast-progress"></div>
  `;

  container.appendChild(toast);

  const progress = toast.querySelector('.toast-progress');
  if (progress) {
    progress.style.transition = 'width 4.5s linear';
    requestAnimationFrame(() => { progress.style.width = '0%'; });
  }

  const autoDismissTimer = setTimeout(() => {
    dismissToast(toast);
  }, duration || 4800);

  toast._timer = autoDismissTimer;
  return toast;
}

function dismissToast(toastElem) {
  if (!toastElem) return;
  clearTimeout(toastElem._timer);
  toastElem.classList.add('toast-hiding');
  setTimeout(() => {
    toastElem.remove();
  }, 300);
}

// ================= 4. CONTACT NUMBER NUMERIC MASK =================
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

// ================= 5. MODAL HELPERS =================
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('hidden');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('hidden');
}

function openAdminLogoutModal() {
  const modal = document.getElementById('logoutModal');
  if (modal) {
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
  } else {
    if (confirm('Are you sure you want to log out?')) {
      window.location.href = (window.BASE_URL ? window.BASE_URL + '/logout.php' : '../logout.php');
    }
  }
}

function closeAdminLogoutModal() {
  const modal = document.getElementById('logoutModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.style.display = 'none';
  }
}

window.openModal = openModal;
window.closeModal = closeModal;
window.openAdminLogoutModal = openAdminLogoutModal;
window.closeAdminLogoutModal = closeAdminLogoutModal;

// ================= 6. ADD MEMBER WORKFLOW =================
function openAddMemberModal() {
  const errBox = document.getElementById('addMemberError');
  if (errBox) errBox.classList.add('hidden');

  const form = document.getElementById('addMemberForm');
  if (form) form.reset();
  
  const photoInput = document.getElementById('addPhotoInput');
  if (photoInput) photoInput.value = '';
  const preview = document.getElementById('addPhotoPreview');
  if (preview) {
    preview.src = '';
    preview.classList.add('hidden');
  }

  toggleSpouseFields();
  const childrenContainer = document.getElementById('dynamicChildrenFormsContainer');
  if (childrenContainer) childrenContainer.innerHTML = '';

  const sigInput = document.getElementById('addSigInput');
  if (sigInput) sigInput.value = '';
  const sigPreview = document.getElementById('addSigPreview');
  if (sigPreview) { sigPreview.src = ''; sigPreview.style.display = 'none'; }
  const sigPlaceholder = document.getElementById('addSigPlaceholder');
  if (sigPlaceholder) sigPlaceholder.style.display = '';

  openModal('addMemberModal');
}

function toggleSpouseFields() {
  const civilStatusElem = document.getElementById('addCivilStatus');
  const spouseSection = document.getElementById('addSpouseSection');
  const spouseFnameInput = document.getElementById('addSpouseFname');
  const spouseLnameInput = document.getElementById('addSpouseLname');
  
  if (!civilStatusElem || !spouseSection) return;
  
  if (civilStatusElem.value === 'Married') {
    spouseSection.style.display = 'block';
    if (spouseFnameInput) spouseFnameInput.required = true;
    if (spouseLnameInput) spouseLnameInput.required = true;
  } else {
    spouseSection.style.display = 'none';
    if (spouseFnameInput) {
      spouseFnameInput.required = false;
      spouseFnameInput.value = '';
    }
    if (spouseLnameInput) {
      spouseLnameInput.required = false;
      spouseLnameInput.value = '';
    }
    const mname = document.getElementById('addSpouseMname');
    const ext = document.getElementById('addSpouseExt');
    if (mname) mname.value = '';
    if (ext) ext.value = '';
  }
}

function addChildForm() {
  const container = document.getElementById('dynamicChildrenFormsContainer');
  if (!container) return;

  const childIndex = container.children.length + 1;
  const childRow = document.createElement('div');
  childRow.className = 'child-dynamic-row';
  childRow.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap: 0.75rem; align-items: flex-end; background: rgba(0, 242, 254, 0.04); border: 1px solid var(--bg-card-border); padding: 0.65rem 0.85rem; border-radius: 10px; margin-top: 0.35rem;';
  childRow.innerHTML = `
    <div class="form-group" style="margin: 0;">
      <label style="font-size: 0.78rem;">First Name <span style="color: #f87171;">*</span></label>
      <input type="text" name="child_firstname[]" placeholder="First" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" required>
    </div>
    <div class="form-group" style="margin: 0;">
      <label style="font-size: 0.78rem;">Middle Name</label>
      <input type="text" name="child_middlename[]" placeholder="Middle" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
    </div>
    <div class="form-group" style="margin: 0;">
      <label style="font-size: 0.78rem;">Last Name <span style="color: #f87171;">*</span></label>
      <input type="text" name="child_lastname[]" placeholder="Last" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" required>
    </div>
    <div class="form-group" style="margin: 0;">
      <label style="font-size: 0.78rem;">Ext</label>
      <input type="text" name="child_extension[]" placeholder="Jr/Sr" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
    </div>
    <div class="form-group" style="margin: 0;">
      <label style="font-size: 0.78rem;">Date of Birth</label>
      <input type="date" name="child_birthday[]" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="removeChildForm(this)" title="Remove Child" style="color: #ef4444; border-color: rgba(239,68,68,0.3); height: 38px; padding: 0 0.65rem;">
      <i class="fa-solid fa-trash"></i>
    </button>
  `;
  container.appendChild(childRow);
}

function removeChildForm(btn) {
  const row = btn.closest('.child-dynamic-row');
  if (row) row.remove();
}

function validateAddMemberForm() {
  const form = document.getElementById('addMemberForm');
  if (form && !form.reportValidity()) {
    return false;
  }

  const errBox = document.getElementById('addMemberError');
  if (errBox) errBox.classList.add('hidden');

  const fn = (document.getElementById('addFirstName')?.value || '').trim();
  const ln = (document.getElementById('addLastName')?.value || '').trim();
  const civil = (document.getElementById('addCivilStatus')?.value || 'Single').trim();

  if (!fn || !ln) {
    if (errBox) {
      errBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>Validation Error:</strong> First Name and Last Name are required.';
      errBox.classList.remove('hidden');
    }
    return false;
  }

  if (/\d/.test(fn) || /\d/.test(ln)) {
    if (errBox) {
      errBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>Validation Error:</strong> First Name and Last Name cannot contain numbers.';
      errBox.classList.remove('hidden');
    }
    return false;
  }

  return true;
}

function promptConfirmAddMember() {
  if (!validateAddMemberForm()) return;
  openModal('confirmAddMemberModal');
}

function executeAddMemberSubmit() {
  closeModal('confirmAddMemberModal');
  const form = document.getElementById('addMemberForm');
  if (form) {
    form.submit();
  }
}

function handlePhotoUpload(event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('addPhotoPreview');
      if (preview) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
      }
    };
    reader.readAsDataURL(file);
  }
}

/**
 * Intelligent Signature Auto-Crop & Normalization Pipeline.
 * Scans image pixel brightness / alpha to detect handwritten ink strokes,
 * calculates bounding box, applies a 15% safe margin padding (so loops/dots are never cut),
 * crops and scales into standard 400x140 canvas, and returns a clean Blob & DataURL.
 */
function cropAndNormalizeSignature(file, callback) {
  if (!file) return;
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

          // Detect ink pixels (darker than white/paper threshold or non-transparent)
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

      // Add 15% safe padding around detected ink bounds to guarantee zero clipping
      const boxW = Math.max(maxX - minX + 1, 20);
      const boxH = Math.max(maxY - minY + 1, 20);
      const padX = Math.round(boxW * 0.12) + 12;
      const padY = Math.round(boxH * 0.12) + 12;

      const cropX = Math.max(0, minX - padX);
      const cropY = Math.max(0, minY - padY);
      const cropW = Math.min(w - cropX, boxW + (padX * 2));
      const cropH = Math.min(h - cropY, boxH + (padY * 2));

      // Output normalized canvas (fixed standard crisp bounds: 400 x 140)
      const targetW = 400;
      const targetH = 140;
      const outCanvas = document.createElement('canvas');
      outCanvas.width = targetW;
      outCanvas.height = targetH;
      const outCtx = outCanvas.getContext('2d');

      // Transparent/clean white background
      outCtx.fillStyle = '#ffffff';
      outCtx.fillRect(0, 0, targetW, targetH);

      // Fit cropped signature maintaining aspect ratio with centered placement
      const scale = Math.min((targetW - 24) / cropW, (targetH - 24) / cropH, 1.8);
      const drawW = cropW * scale;
      const drawH = cropH * scale;
      const drawX = (targetW - drawW) / 2;
      const drawY = (targetH - drawH) / 2;

      outCtx.drawImage(origCanvas, cropX, cropY, cropW, cropH, drawX, drawY, drawW, drawH);

      outCanvas.toBlob(function(blob) {
        if (!blob) {
          callback(e.target.result, file);
          return;
        }
        const croppedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + "_cropped.png", { type: 'image/png' });
        const croppedDataUrl = outCanvas.toDataURL('image/png');
        callback(croppedDataUrl, croppedFile);
      }, 'image/png');
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function handleSigUpload(event) {
  const file = event.target.files[0];
  const preview     = document.getElementById('addSigPreview');
  const placeholder = document.getElementById('addSigPlaceholder');
  const slot        = document.getElementById('addSigSlot');
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showToast('error', 'File Too Large', 'Signature image must be 5 MB or smaller.');
      event.target.value = '';
      return;
    }
    cropAndNormalizeSignature(file, function(dataUrl, croppedFile) {
      if (preview) {
        preview.src = dataUrl;
        preview.style.display = 'block';
      }
      if (placeholder) placeholder.style.display = 'none';
      if (slot) slot.style.borderColor = 'var(--primary-teal)';

      if (window.DataTransfer) {
        try {
          const dt = new DataTransfer();
          dt.items.add(croppedFile);
          event.target.files = dt.files;
        } catch(e) {}
      }
    });
  } else {
    if (preview) {
      preview.src = '';
      preview.style.display = 'none';
    }
    if (placeholder) placeholder.style.display = '';
  }
}

function handleEditSigUpload(event) {
  const file = event.target.files[0];
  const previewSlot = document.getElementById('editSigPreviewSlot');
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showToast('error', 'File Too Large', 'Signature image must be 5 MB or smaller.');
      event.target.value = '';
      return;
    }
    cropAndNormalizeSignature(file, function(dataUrl, croppedFile) {
      if (previewSlot) {
        previewSlot.innerHTML = `
          <div style="margin-top: 0.35rem;">
            <span style="color: #34d399; font-size: 0.8rem; display: block; margin-bottom: 2px;">
              <i class="fa-solid fa-check-circle"></i> Auto-Cropped Signature Ready:
            </span>
            <img src="${dataUrl}" alt="Cropped Signature" style="height: 42px; background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; vertical-align: middle;">
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
    });
  }
}

// ================= 7. ADMIN PHOTO MANAGEMENT =================
function openAdminPhotoModal() {
  openModal('adminPhotoModal');
}

function previewAdminPhoto(event) {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      showToast('error', 'File Too Large', 'Profile photo must be 5 MB or smaller.');
      event.target.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('adminPhotoPreview');
      const icon = document.getElementById('adminPhotoPlaceholderIcon');
      if (preview) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
      }
      if (icon) icon.classList.add('hidden');
    };
    reader.readAsDataURL(file);
  }
}

function removeAdminPhoto() {
  showSystemConfirm({
    title: 'Remove Profile Picture',
    message: 'Are you sure you want to remove your profile picture?',
    confirmText: 'Yes, Remove',
    isDanger: true,
    onConfirm: () => {
      const act = document.getElementById('adminPhotoAction');
      if (act) act.value = 'remove';
      const form = document.getElementById('adminPhotoForm');
      if (form) form.submit();
    }
  });
}

// ================= 7B. PURE PHOTO LIGHTBOX PREVIEW (IMAGE ONLY) =================
function openPhotoModal(accNoOrUrl) {
  let photoUrl = '';

  if (typeof accNoOrUrl === 'string' && (
      accNoOrUrl.startsWith('http') ||
      accNoOrUrl.startsWith('data:') ||
      accNoOrUrl.includes('/uploads/') ||
      accNoOrUrl.includes('assets/images/') ||
      /\.(jpg|jpeg|png|webp|svg)(\?.*)?$/i.test(accNoOrUrl)
  )) {
    photoUrl = accNoOrUrl;
  } else {
    const accNo = String(accNoOrUrl || '').trim();
    const member = membersDatabase[accNo] || {};
    if (member.passport_photo && !member.passport_photo.includes('default-avatar')) {
      photoUrl = member.passport_photo.startsWith('http') || member.passport_photo.startsWith('/') ? member.passport_photo : (window.BASE_URL ? window.BASE_URL + '/' + member.passport_photo : member.passport_photo);
    } else {
      // Look for thumbnail inside table row if available
      const tableRows = document.querySelectorAll('tr');
      for (const row of tableRows) {
        if (row.innerText.includes(accNo)) {
          const img = row.querySelector('.table-avatar img, .table-avatar-thumb');
          if (img && img.src && !img.src.includes('default-avatar.svg')) {
            photoUrl = img.src;
            break;
          }
        }
      }
    }
  }

  const imgEl = document.getElementById('lightboxPhotoImg');
  const fallbackEl = document.getElementById('lightboxNoPhotoFallback');

  if (imgEl) {
    if (photoUrl) {
      imgEl.src = photoUrl;
      imgEl.classList.remove('hidden');
      if (fallbackEl) fallbackEl.classList.add('hidden');
    } else {
      imgEl.src = '';
      imgEl.classList.add('hidden');
      if (fallbackEl) fallbackEl.classList.remove('hidden');
    }
  }

  openModal('photoPreviewModal');
}

// ================= 7C. VIEW MEMBER MODAL (RESTRUCTURED 2x2 & GRID) =================
function viewMember(accNo) {
  const data = membersDatabase[accNo] || {
    accNo: accNo, fullName: 'Unknown', civilStatus: 'Single', spouseName: 'Not Specified', childrenList: [], contact: 'N/A'
  };
  const setTxt = (id, txt) => {
    const el = document.getElementById(id);
    if (el) el.innerText = txt || 'N/A';
  };

  const name = data.fullName || data.name || 'Unknown';
  setTxt('viewAccNo', data.accNo || data.account_number || accNo);
  setTxt('viewName', name);
  setTxt('viewAge', data.age ? (String(data.age).includes('Year') ? data.age : `${data.age} Years Old`) : (data.birthday ? `${data.birthday}` : 'N/A'));
  setTxt('viewCivil', data.civilStatus || data.civil || 'Single');
  setTxt('viewMemberSex', data.sex || 'Not Specified');
  setTxt('viewContact', data.contact_number || data.contact || 'N/A');
  setTxt('viewEmail', data.email || data.email_account || 'N/A');
  setTxt('viewMemberNationality', data.nationality || 'Filipino');
  setTxt('viewAddress', data.address || 'Not Specified');
  setTxt('viewEmployerOccupation', data.occupation || 'Not Specified');
  setTxt('viewEmployer', data.employer || 'Not Specified');
  setTxt('viewEmployerAddress', data.employer_address || data.employerAddress || 'Not Specified');

  const statusBadgeSlot = document.getElementById('viewStatusBadge');
  if (statusBadgeSlot) {
    const st = data.account_status || data.status || 'Active';
    const isDeleted = st.toLowerCase() === 'deleted';
    const isInactive = st.toLowerCase() === 'inactive';
    const isLocked = st.toLowerCase() === 'locked';
    const isFlagged = st.toLowerCase() === 'flagged';
    const isActive = st.toLowerCase() === 'active';
    const pillClass = isActive ? 'status-active' : (isFlagged ? 'status-flagged' : ((isInactive || isDeleted || isLocked) ? 'status-inactive' : 'status-flagged'));
    const icon = isActive ? 'fa-circle-check' : (isFlagged ? 'fa-flag' : (isDeleted ? 'fa-user-xmark' : (isInactive ? 'fa-circle-xmark' : 'fa-lock')));
    statusBadgeSlot.innerHTML = `<span class="status-pill ${pillClass}"><i class="fa-solid ${icon}"></i> ${st}</span>`;
  }

  // Passport Photo Slot (2x2 Frame)
  const photoSlot = document.getElementById('viewPassportPhotoSlot');
  if (photoSlot) {
    if (data.passport_photo && !data.passport_photo.includes('default-avatar')) {
      photoSlot.innerHTML = `<img src="${data.passport_photo}" alt="Official 2x2 Photo" class="passport-2x2-img">`;
    } else {
      photoSlot.innerHTML = `<i class="fa-solid fa-user placeholder-photo"></i>`;
    }
  }

  // Parents Information
  const parentsBox = document.getElementById('viewParentsBox');
  const father = (data.father_name && data.father_name.trim() && data.father_name !== 'Not Specified') ? data.father_name : 'Not Specified';
  const mother = (data.mother_name && data.mother_name.trim() && data.mother_name !== 'Not Specified') ? data.mother_name : 'Not Specified';

  if (parentsBox) {
    parentsBox.innerHTML = `
      <div class="parents-data-row">
        <div class="parent-item"><strong>Father:</strong> <span>${father}</span></div>
        <div class="parent-item"><strong>Mother:</strong> <span>${mother}</span></div>
      </div>
    `;
  }

  // Spouse Info (Only display if member is not Single AND spouse name is provided)
  const spSubcard = document.getElementById('viewSpouseSubcard');
  const spBox = document.getElementById('viewSpouseBox');
  const civil = (data.civilStatus || data.civil || 'Single').toString().trim().toLowerCase();
  const rawSpouseName = (data.spouseName && data.spouseName !== 'Not Specified' && data.spouseName !== 'N/A' && data.spouseName !== 'Not Specified' && data.spouseName.trim()) 
    ? data.spouseName.trim()
    : ((data.spouse && data.spouse.name && data.spouse.name !== 'Not Specified' && data.spouse.name !== 'N/A' && data.spouse.name !== 'Not Specified' && data.spouse.name.trim()) ? data.spouse.name.trim() : '');

  const hasSpouse = (civil !== 'single' && rawSpouseName !== '');

  if (spSubcard) {
    spSubcard.style.display = hasSpouse ? 'block' : 'none';
  }

  if (spBox && hasSpouse) {
    const spOcc = (data.spouse && data.spouse.occupation && data.spouse.occupation !== 'Not Specified' && data.spouse.occupation !== 'Not Specified') ? data.spouse.occupation : (data.spouse_occupation || 'Not Specified');
    const spEmp = (data.spouse && data.spouse.employer && data.spouse.employer !== 'Not Specified' && data.spouse.employer !== 'Not Specified') ? data.spouse.employer : (data.spouse_employer || 'Not Specified');
    spBox.innerHTML = `
      <div class="spouse-data-box">
        <div class="spouse-name-title"><i class="fa-solid fa-user-check text-cyan"></i> ${rawSpouseName}</div>
        <div class="spouse-meta-grid">
          <div><strong>Occupation:</strong> <span>${spOcc}</span></div>
          <div><strong>Employer:</strong> <span>${spEmp}</span></div>
        </div>
      </div>
    `;
  }

  // Children Info (Only display if member has valid registered children)
  const chSection = document.getElementById('viewChildrenSection');
  const chBox = document.getElementById('viewChildrenBox');
  const rawChildren = data.childrenList || data.children || [];
  const validChildren = Array.isArray(rawChildren) 
    ? rawChildren.filter(c => c && (c.name || c.child_name) && (c.name || c.child_name).trim() && (c.name || c.child_name) !== 'Not Specified' && (c.name || c.child_name) !== 'Not Specified')
    : [];
  const hasChildren = validChildren.length > 0;

  if (chSection) {
    chSection.style.display = hasChildren ? 'block' : 'none';
  }

  if (chBox && hasChildren) {
    let chHtml = `<div class="children-badges-grid">`;
    validChildren.forEach((child, i) => {
      const childName = child.name || child.child_name || ('Child ' + (i+1));
      const ageTxt = child.age ? (String(child.age).includes('Year') ? child.age : `${child.age} Years Old`) : 'N/A';
      const bdayTxt = child.birthday ? ` &bull; ${child.birthday}` : '';
      chHtml += `
        <div class="child-view-pill">
          <span class="child-view-name"><i class="fa-solid fa-child text-cyan me-1"></i> ${childName}</span>
          <span class="child-view-meta">Age: <strong>${ageTxt}</strong>${bdayTxt}</span>
        </div>`;
    });
    chHtml += `</div>`;
    chBox.innerHTML = chHtml;
  }

  // E-Signature Section
  const sigBox = document.getElementById('viewSignatureBox');
  if (sigBox) {
    const sigUrl = data.signature_photo || data.signature || null;
    if (sigUrl) {
      sigBox.innerHTML = `
        <div class="signature-frame-preview">
          <img src="${sigUrl}" alt="Member Signature" class="signature-img-preview">
        </div>
        <p class="signature-caption-note"><i class="fa-solid fa-circle-check text-emerald me-1"></i>Official Handwritten Signature on File</p>
      `;
    } else {
      sigBox.innerHTML = `<span class="text-muted-italics"><i class="fa-solid fa-pen-nib me-1"></i> No E-Signature uploaded yet</span>`;
    }
  }

  openModal('viewMemberModal');
}

// ================= 8. EDIT MEMBER WORKFLOW =================
function openEditMember(accNo) {
  const data = membersDatabase[accNo] || {};
  const errBox = document.getElementById('editMemberError');
  if (errBox) errBox.classList.add('hidden');

  const setVal = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.value = val;
  };

  setVal('editAccNoHidden', data.accNo || data.account_number || accNo);
  
  // 1. Personal Information
  setVal('editFirstName', data.firstname || '');
  setVal('editMiddleName', data.middlename || '');
  setVal('editLastName', data.lastname || '');
  setVal('editExtensionName', data.extension_name || '');
  setVal('editCivilStatus', data.civilStatus || data.civil || 'Single');
  setVal('editAccountStatus', data.account_status || data.status || 'Active');
  setVal('editSex', data.sex || 'Male');
  setVal('editNationality', data.nationality || 'Filipino');
  setVal('editBirthday', data.birthday || '');

  // 2. Contact Information
  setVal('editContact', data.contact_number || data.contact || '');
  setVal('editAddress', data.address || '');

  // 3. Employment Information
  setVal('editEmployer', data.employer || '');
  setVal('editEmployerAddress', data.employer_address || '');

  // 4. Parents Information
  setVal('editFatherName', data.father_name || '');
  setVal('editMotherName', data.mother_name || '');

  // 5. Spouse Information
  const spouseObj = data.spouse || {};
  const spouseFullName = data.spouseName || spouseObj.name || '';
  let spFirst = spouseFullName, spMid = '', spLast = '', spExt = '';
  if (spouseFullName.trim()) {
    const parts = spouseFullName.trim().split(' ');
    if (parts.length === 2) { spFirst = parts[0]; spLast = parts[1]; }
    else if (parts.length === 3) { spFirst = parts[0]; spMid = parts[1]; spLast = parts[2]; }
    else if (parts.length > 3) { spFirst = parts[0]; spLast = parts[parts.length - 1]; spMid = parts.slice(1, -1).join(' '); }
  }
  setVal('editSpouseFirstName', spFirst);
  setVal('editSpouseMiddleName', spMid);
  setVal('editSpouseLastName', spLast);
  setVal('editSpouseExtensionName', spExt);
  setVal('editSpouseOccupation', spouseObj.occupation || data.spouse_occupation || '');
  setVal('editSpouseEmployer', spouseObj.employer || data.spouse_employer || '');

  // 6. Children Information
  const children = data.raw_children || data.childrenList || data.children || [];
  renderEditChildrenRows(children);

  // 7. Document Previews
  const photoSlot = document.getElementById('editPhotoPreviewSlot');
  if (photoSlot) {
    if (data.passport_photo && !data.passport_photo.includes('default-avatar.svg')) {
      photoSlot.innerHTML = `<span style="color: #34d399;"><i class="fa-solid fa-check-circle"></i> Current Photo:</span> <img src="${data.passport_photo}" style="height: 36px; border-radius: 4px; vertical-align: middle; margin-left: 0.4rem;">`;
    } else {
      photoSlot.innerHTML = `<span style="color: var(--text-muted);">No photo uploaded yet</span>`;
    }
  }

  const sigSlot = document.getElementById('editSigPreviewSlot');
  if (sigSlot) {
    const sigUrl = data.signature_photo || data.signature || null;
    if (sigUrl) {
      sigSlot.innerHTML = `<span style="color: #34d399;"><i class="fa-solid fa-check-circle"></i> Current Signature:</span> <img src="${sigUrl}" style="height: 32px; background: #fff; padding: 2px; border-radius: 4px; vertical-align: middle; margin-left: 0.4rem;">`;
    } else {
      sigSlot.innerHTML = `<span style="color: var(--text-muted);">No signature uploaded yet</span>`;
    }
  }

  openModal('editModal');
}

function renderEditChildrenRows(children) {
  const container = document.getElementById('editChildrenContainer');
  if (!container) return;
  container.innerHTML = '';

  if (!Array.isArray(children) || children.length === 0) {
    addEditChildRow();
    return;
  }

  children.forEach(c => {
    addEditChildRow(c.name || c.child_name || '', c.birthday || '');
  });
}

function addEditChildRow(name = '', birthday = '') {
  const container = document.getElementById('editChildrenContainer');
  if (!container) return;

  const row = document.createElement('div');
  row.className = 'child-input-row';
  row.style.cssText = 'background: rgba(255, 255, 255, 0.02); border: 1px solid var(--bg-card-border); border-radius: 8px; padding: 0.85rem;';

  const childAgeTxt = birthday ? calculateChildAgeJS(birthday) : 'N/A';

  let chFirst = name, chMid = '', chLast = '', chExt = '';
  if (name && name.trim()) {
    const parts = name.trim().split(' ');
    if (parts.length === 2) { chFirst = parts[0]; chLast = parts[1]; }
    else if (parts.length === 3) { chFirst = parts[0]; chMid = parts[1]; chLast = parts[2]; }
    else if (parts.length > 3) { chFirst = parts[0]; chLast = parts[parts.length - 1]; chMid = parts.slice(1, -1).join(' '); }
  }

  row.innerHTML = `
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
      <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;">
        <i class="fa-solid fa-child" style="color: var(--primary-teal);"></i> Child Entry
      </span>
      <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.child-input-row').remove()" title="Remove Child" style="padding: 0.25rem 0.65rem; font-size: 0.78rem; height: 30px; display: inline-flex; align-items: center; gap: 0.35rem;">
        <i class="fa-solid fa-trash-can"></i> Remove
      </button>
    </div>
    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap: 0.75rem; align-items: flex-end;">
      <div class="form-group" style="margin: 0;">
        <label style="font-size: 0.78rem;">First Name <span style="color: #f87171;">*</span></label>
        <input type="text" name="child_firstname[]" value="${chFirst}" placeholder="First" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" required>
      </div>
      <div class="form-group" style="margin: 0;">
        <label style="font-size: 0.78rem;">Middle Name</label>
        <input type="text" name="child_middlename[]" value="${chMid}" placeholder="Middle" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
      </div>
      <div class="form-group" style="margin: 0;">
        <label style="font-size: 0.78rem;">Last Name <span style="color: #f87171;">*</span></label>
        <input type="text" name="child_lastname[]" value="${chLast}" placeholder="Last" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;" required>
      </div>
      <div class="form-group" style="margin: 0;">
        <label style="font-size: 0.78rem;">Ext</label>
        <input type="text" name="child_extension[]" value="${chExt}" placeholder="Jr/Sr" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
      </div>
      <div class="form-group" style="margin: 0;">
        <label style="font-size: 0.78rem;">Date of Birth</label>
        <input type="date" name="child_birthday[]" value="${birthday}" onchange="updateChildAgeBadge(this)" style="padding: 0.45rem 0.75rem; font-size: 0.85rem;">
      </div>
      <div class="form-group" style="margin: 0;">
        <label style="font-size: 0.78rem;">Calculated Age</label>
        <span class="child-age-badge" style="font-size: 0.82rem; color: var(--primary-teal); background: rgba(0, 194, 168, 0.1); border: 1px solid rgba(0, 194, 168, 0.25); border-radius: 6px; padding: 0.55rem 0.65rem; text-align: center; font-weight: 600; display: block; height: 36px; line-height: 18px;">${childAgeTxt}</span>
      </div>
    </div>
  `;
  container.appendChild(row);
}

function calculateChildAgeJS(birthdayStr) {
  if (!birthdayStr) return 'N/A';
  const bday = new Date(birthdayStr);
  if (isNaN(bday.getTime())) return 'N/A';
  const today = new Date();
  let age = today.getFullYear() - bday.getFullYear();
  const m = today.getMonth() - bday.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < bday.getDate())) {
    age--;
  }
  return age >= 0 ? age + ' Years Old' : 'N/A';
}

function updateChildAgeBadge(inputElem) {
  const row = inputElem.closest('.child-input-row');
  if (row) {
    const badge = row.querySelector('.child-age-badge');
    if (badge) {
      badge.innerText = calculateChildAgeJS(inputElem.value);
    }
  }
}

function validateEditMemberForm() {
  const fn = (document.getElementById('editFirstName')?.value || '').trim();
  const ln = (document.getElementById('editLastName')?.value || '').trim();
  const contact = (document.getElementById('editContact')?.value || '').trim();
  const address = (document.getElementById('editAddress')?.value || '').trim();
  const sex = (document.getElementById('editSex')?.value || '').trim();
  const nationality = (document.getElementById('editNationality')?.value || '').trim();
  const errBox = document.getElementById('editMemberError');

  if (errBox) errBox.classList.add('hidden');

  if (!fn || !ln || !contact || !address || !sex || !nationality) {
    if (errBox) {
      errBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>Validation Error:</strong> Please fill in all required fields marked with (*), including Sex and Nationality.';
      errBox.classList.remove('hidden');
    }
    return false;
  }

  if (/\d/.test(fn) || /\d/.test(ln)) {
    if (errBox) {
      errBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>Validation Error:</strong> First Name and Last Name cannot contain numbers.';
      errBox.classList.remove('hidden');
    }
    return false;
  }

  return true;
}

function promptConfirmEditMember() {
  if (!validateEditMemberForm()) return;
  openModal('confirmEditMemberModal');
}

function executeEditMemberSubmit() {
  closeModal('confirmEditMemberModal');
  const form = document.getElementById('editMemberForm');
  if (form) {
    form.submit();
  }
}

// ================= 9. DELETE MEMBER WORKFLOW =================
function openDeleteMember(accNo) {
  const data = membersDatabase[accNo] || { fullName: 'Member' };
  const accHidden = document.getElementById('deleteAccNoHidden');
  if (accHidden) accHidden.value = accNo;
  const targetAcc = document.getElementById('deleteTargetAcc');
  if (targetAcc) targetAcc.innerText = accNo;
  const targetName = document.getElementById('deleteTargetName');
  if (targetName) targetName.innerText = data.fullName || data.name || accNo;
  openModal('deleteModal');
}

// ================= 10. TRUE PRINTING & DIRECT FILE DOWNLOADS =================

/**
 * True Printing — Directly prints the complete, official member profile record.
 */
async function printMemberRecord(accNo) {
  if (!membersDatabase[accNo]) {
    await fetchMembersFromDatabase();
  }
  const member = membersDatabase[accNo] || {
    account_number: accNo,
    fullName: accNo,
    name: accNo,
    civil: 'Single',
    status: 'Active',
    contact: 'N/A',
    address: 'N/A',
    employer: 'Not Specified',
    occupation: 'Not Specified',
    nationality: 'Filipino',
    childrenList: [],
    references: []
  };

  const name = member.fullName || member.name || accNo;
  const isPrintSingle = (member.civil || member.civilStatus || 'Single').toString().toLowerCase() === 'single';
  const printSpouseName = (member.spouseName && member.spouseName !== 'Not Specified' && member.spouseName.trim() && member.spouseName !== 'Not Specified') 
    ? member.spouseName 
    : ((member.spouse && member.spouse.name && member.spouse.name !== 'Not Specified' && member.spouse.name.trim() && member.spouse.name !== 'Not Specified') ? member.spouse.name : '');
  const hasPrintSpouse = !isPrintSingle && printSpouseName !== '';

  const spousePrintHtml = hasPrintSpouse ? `
    <tr><th>Spouse Full Name</th><td>${printSpouseName}</td><th>Spouse Occupation</th><td>${member.spouse ? ((member.spouse.occupation && member.spouse.occupation !== 'Not Specified') ? member.spouse.occupation : 'Not Specified') : 'Not Specified'}</td></tr>
    <tr><th>Spouse Employer</th><td colspan="3">${member.spouse ? ((member.spouse.employer && member.spouse.employer !== 'Not Specified') ? member.spouse.employer : 'Not Specified') : 'Not Specified'}</td></tr>
  ` : '';

  const hasPrintChildren = Array.isArray(member.childrenList) && member.childrenList.length > 0;
  const childrenHtml = hasPrintChildren
    ? member.childrenList.map((c, i) => {
        const cAge = c.age ? (String(c.age).includes('Year') ? c.age : c.age + ' Years Old') : 'N/A';
        return `<tr><td style="width:30px; text-align:center;">${i+1}</td><td>${c.name || 'Child'}</td><td>${c.birthday || 'N/A'}</td><td>${cAge}</td></tr>`;
      }).join('')
    : '';

  const childrenPrintBlock = hasPrintChildren ? `
    <div style="margin-top: 5px;">
      <strong style="font-size: 10px; color: #065f46; text-transform: uppercase;">Children Registered:</strong>
      <table class="sub-table">
        <thead><tr><th style="width:30px; text-align:center;">#</th><th>Child Full Name</th><th>Date of Birth</th><th>Age</th></tr></thead>
        <tbody>${childrenHtml}</tbody>
      </table>
    </div>
  ` : '';

  const printFamilyTableHtml = `
    <div class="section-header">Family Background</div>
    <table class="data-table">
      <tr><th>Father's Full Name</th><td>${(member.father_name && member.father_name !== 'Not Specified' && member.father_name.trim()) ? member.father_name : 'Not Specified'}</td><th>Mother's Full Name</th><td>${(member.mother_name && member.mother_name !== 'Not Specified' && member.mother_name.trim()) ? member.mother_name : 'Not Specified'}</td></tr>
      ${spousePrintHtml}
    </table>
    ${childrenPrintBlock}
  `;

  const refsHtml = Array.isArray(member.references) && member.references.length > 0
    ? member.references.map((r, i) => `<tr><td style="width:30px; text-align:center;">${i+1}</td><td>${r || 'N/A'}</td></tr>`).join('')
    : '<tr><td colspan="2" style="text-align:center; color:#555;">None</td></tr>';

  const photoHtml = member.passport_photo && !member.passport_photo.includes('default-avatar')
    ? `<img src="${member.passport_photo}" alt="Member Photo" style="width:105px; height:105px; object-fit:cover; border:2px solid #0f766e; border-radius:8px; display:block; margin:0 auto;">`
    : `<div style="width:105px; height:105px; border:2px dashed #9ca3af; border-radius:8px; display:flex; align-items:center; justify-content:center; margin:0 auto; font-size:11px; color:#6b7280; text-align:center; padding:4px;">No Photo<br>Attached</div>`;

  const sigHtml = member.signature_photo
    ? `<img src="${member.signature_photo}" alt="Signature" style="max-height:42px; max-width:150px; object-fit:contain;">`
    : `<span style="font-size:11px; color:#4b5563; font-style:italic;">[ Signature on File ]</span>`;

  const printDoc = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Official Member Profile - ${accNo}</title>
  <style>
    @page { size: A4; margin: 10mm 14mm; }
    *, *::before, *::after {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11px;
      line-height: 1.35;
      color: #000000 !important;
      opacity: 1 !important;
      margin: 0;
      padding: 0;
      background: #ffffff !important;
    }
    .doc-header {
      border-bottom: 2.5px solid #0f766e;
      padding-bottom: 8px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .doc-header .title-area h1 {
      font-size: 16px;
      margin: 0 0 2px 0;
      color: #0f766e;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .doc-header .title-area p {
      margin: 0;
      font-size: 9.5px;
      color: #374151;
      font-weight: 600;
    }
    .doc-header .badge-area {
      text-align: right;
    }
    .badge-tag {
      display: inline-block;
      padding: 3px 8px;
      font-weight: bold;
      border-radius: 4px;
      font-size: 11px;
      border: 1px solid #0f766e;
      background: #f0fdfa;
      color: #0f766e;
    }
    .section-header {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-left: 4px solid #0f766e;
      padding: 3.5px 7px;
      font-weight: bold;
      font-size: 11px;
      margin: 8px 0 5px 0;
      color: #065f46;
      text-transform: uppercase;
    }
    .two-col-layout {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      margin-bottom: 6px;
    }
    .photo-col {
      width: 115px;
      flex-shrink: 0;
      text-align: center;
    }
    .info-col {
      flex: 1;
    }
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 4px;
    }
    table.data-table th, table.data-table td {
      border: 1px solid #d1d5db;
      padding: 4px 6px;
      font-size: 10.5px;
      text-align: left;
      vertical-align: middle;
      color: #000000 !important;
      opacity: 1 !important;
    }
    table.data-table th {
      background: #f9fafb;
      font-weight: 700;
      width: 24%;
      color: #111827 !important;
    }
    table.sub-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 3px;
    }
    table.sub-table th, table.sub-table td {
      border: 1px solid #e5e7eb;
      padding: 3.5px 6px;
      font-size: 10px;
      text-align: left;
      color: #000000 !important;
      opacity: 1 !important;
    }
    table.sub-table th {
      background: #f3f4f6;
      font-weight: 700;
      color: #374151 !important;
    }
    .sig-section {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-top: 10px;
      padding-top: 6px;
      border-top: 1px dashed #cbd5e1;
    }
    .sig-box {
      text-align: center;
      width: 190px;
    }
    .sig-line {
      border-top: 1px solid #000000;
      margin-top: 3px;
      padding-top: 2px;
      font-size: 9.5px;
      font-weight: bold;
    }
    .footer-note {
      font-size: 8.5px;
      color: #4b5563;
      text-align: center;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="doc-header">
    <div class="title-area">
      <h1>SPACE COOPERATIVE</h1>
      <p>Official Member Information System &bull; Certified Personal Record</p>
    </div>
    <div class="badge-area">
      <div class="badge-tag">ACCOUNT: ${accNo}</div>
      <div style="font-size: 9px; color: #374151; margin-top: 2px;">Status: <strong>${member.account_status || member.status || 'Active'}</strong></div>
    </div>
  </div>

  <div class="two-col-layout">
    <div class="photo-col">
      ${photoHtml}
      <div style="font-size: 8.5px; color: #374151; margin-top: 3px; font-weight: bold; text-transform: uppercase;">Official Photo</div>
    </div>
    <div class="info-col">
      <table class="data-table">
        <tr><th>Full Member Name</th><td colspan="3"><strong style="font-size: 11.5px; color: #0f766e;">${name}</strong></td></tr>
        <tr><th>Account Number</th><td><strong>${accNo}</strong></td><th>Civil Status</th><td>${member.civil || member.civilStatus || 'Single'}</td></tr>
        <tr><th>Date of Birth</th><td>${member.birthday ? member.birthday : 'Not Specified'} (${member.age ? (String(member.age).includes('Year') ? member.age : member.age + ' Years Old') : 'N/A'})</td><th>Sex / Gender</th><td>${member.sex || 'Not Specified'}</td></tr>
        <tr><th>Nationality</th><td>${member.nationality || 'Filipino'}</td><th>Contact Number</th><td>${member.contact_number || member.contact || 'N/A'}</td></tr>
        <tr><th>Email / Account</th><td colspan="3">${member.email || 'N/A'}</td></tr>
        <tr><th>Home Address</th><td colspan="3">${member.address || 'N/A'}</td></tr>
      </table>
    </div>
  </div>

  <div class="section-header">Employment &amp; Business Information</div>
  <table class="data-table">
    <tr><th>Occupation</th><td>${member.occupation || 'Not Specified'}</td><th>Employer / Company</th><td>${member.employer || 'Not Specified'}</td></tr>
    <tr><th>Employer Address</th><td colspan="3">${member.employer_address || 'Not Specified'}</td></tr>
  </table>

  ${printFamilyTableHtml}

  <div style="margin-top: 5px;">
    <strong style="font-size: 10px; color: #065f46; text-transform: uppercase;">Character References:</strong>
    <table class="sub-table">
      <thead><tr><th style="width:30px; text-align:center;">#</th><th>Reference Member Name</th></tr></thead>
      <tbody>${refsHtml}</tbody>
    </table>
  </div>

  <div class="sig-section">
    <div class="sig-box">
      <div style="min-height: 38px; display: flex; align-items: flex-end; justify-content: center;">
        ${sigHtml}
      </div>
      <div class="sig-line">Member's Handwritten Signature</div>
    </div>
    <div class="sig-box">
      <div style="min-height: 38px; display: flex; align-items: flex-end; justify-content: center; font-size: 11px; font-weight: bold; color: #0f766e;">
        OFFICIAL SYSTEM VERIFIED
      </div>
      <div class="sig-line">Staff Administrator &bull; Date: ${new Date().toLocaleDateString()}</div>
    </div>
  </div>

  <div class="footer-note">
    Official Extract from SPACE Cooperative Member Information Portal &bull; Printed on ${new Date().toLocaleString()} &bull; Confidential
  </div>
</body>
</html>`;

  let printFrame = document.getElementById('printRecordIframe');
  if (!printFrame) {
    printFrame = document.createElement('iframe');
    printFrame.id = 'printRecordIframe';
    printFrame.style.position = 'fixed';
    printFrame.style.right = '0';
    printFrame.style.bottom = '0';
    printFrame.style.width = '0';
    printFrame.style.height = '0';
    printFrame.style.border = '0';
    document.body.appendChild(printFrame);
  }

  const frameDoc = printFrame.contentWindow.document;
  frameDoc.open();
  frameDoc.write(printDoc);
  frameDoc.close();

  setTimeout(() => {
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();
  }, 350);
}

/**
 * Opens download modal for direct PDF / Word file exports of Member Record.
 */
function openDownloadMemberModal(accNo) {
  const data = membersDatabase[accNo] || { fullName: accNo, name: accNo };
  printingTargetAcc = accNo;
  const targetName = document.getElementById('printTargetName');
  if (targetName) {
    targetName.innerText = `${data.fullName || data.name || accNo} (${accNo})`;
  }
  openModal('printOptionsModal');
}

function printSingleMember(accNo) {
  openDownloadMemberModal(accNo);
}

/**
 * True Printing — Prints the compiled reports table directly.
 */
function printCompiledReport() {
  if (typeof validateReportDateRange === 'function' && !validateReportDateRange('print')) {
    return;
  }

  const catSelect = document.getElementById('reportCategorySelect');
  const timeSelect = document.getElementById('reportTimeRange');
  const scopeSelect = document.getElementById('reportDataScopeSelect');

  const cat = catSelect ? catSelect.options[catSelect.selectedIndex].text : 'Consolidated Report';
  let timeline = timeSelect ? timeSelect.options[timeSelect.selectedIndex].text : 'All Time';
  let inclusionDate = '08/01/2026 - 08/31/2026';
  let timelineTag = timeline;

  if (timeSelect && timeSelect.value === 'custom') {
    const sVal = document.getElementById('customStartDate')?.value;
    const eVal = document.getElementById('customEndDate')?.value;
    if (sVal && eVal) {
      const partsS = sVal.split('-');
      const partsE = eVal.split('-');
      inclusionDate = `${partsS[1]}/${partsS[2]}/${partsS[0]} - ${partsE[1]}/${partsE[2]}/${partsE[0]}`;
      timeline = `${inclusionDate} (Custom Range)`;
      timelineTag = 'Custom Range';
    }
  } else if (timeSelect && timeSelect.value === 'past_q1') {
    inclusionDate = '01/01/2026 - 03/31/2026';
    timelineTag = '1st Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_q2') {
    inclusionDate = '04/01/2026 - 06/30/2026';
    timelineTag = '2nd Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_q3') {
    inclusionDate = '07/01/2026 - 09/30/2026';
    timelineTag = '3rd Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_q4') {
    inclusionDate = '10/01/2026 - 12/31/2026';
    timelineTag = '4th Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_1_year') {
    inclusionDate = '01/01/2026 - 12/31/2026';
    timelineTag = 'Full Year 2026';
  }

  const scope = scopeSelect ? scopeSelect.options[scopeSelect.selectedIndex].text : 'All Records';
  
  const rows = document.querySelectorAll('#reportPreviewTable tbody tr:not(.empty-state-row)');
  let tableRowsHtml = '';
  rows.forEach(r => {
    if (r.style.display !== 'none') {
      const cells = r.querySelectorAll('td');
      if (cells.length >= 5) {
        tableRowsHtml += `<tr>
          <td><strong>${cells[0].innerText.trim()}</strong></td>
          <td>${cells[1].innerText.trim()}</td>
          <td>${cells[2].innerText.trim()}</td>
          <td>${cells[3].innerText.trim()}</td>
          <td>${cells[4].innerText.trim()}</td>
          <td>${cells[5] ? cells[5].innerText.trim() : 'Staff'}</td>
        </tr>`;
      }
    }
  });

  if (!tableRowsHtml) {
    tableRowsHtml = '<tr><td colspan="6" style="text-align:center; padding:12px; color:#555;">No report records currently match the selected criteria.</td></tr>';
  }

  const reportId = `RPT-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 900) + 100)}`;
  const dateStamp = new Date().toLocaleString();
  const rawDate = new Date().toISOString().split('T')[0];
  const staffName = window.CURRENT_STAFF_NAME || 'Staff Administrator';

  const printDoc = `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Cooperative System Report - ${reportId}</title>
  <style>
    @page { size: A4 landscape; margin: 10mm 12mm; }
    *, *::before, *::after {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 11px;
      line-height: 1.35;
      color: #000000 !important;
      opacity: 1 !important;
      margin: 0;
      padding: 0;
      background: #ffffff !important;
    }
    .doc-header {
      border-bottom: 2.5px solid #0f766e;
      padding-bottom: 6px;
      margin-bottom: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .doc-header h1 {
      font-size: 16px;
      margin: 0 0 2px 0;
      color: #0f766e;
      font-weight: 800;
      text-transform: uppercase;
    }
    .doc-header p {
      margin: 0;
      font-size: 10px;
      color: #374151;
      font-weight: 600;
    }
    .meta-box {
      background: #f0fdfa;
      border: 1px solid #ccfbf1;
      padding: 8px 12px;
      margin-bottom: 10px;
      border-radius: 4px;
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;
      font-size: 10.5px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
    }
    th, td {
      border: 1px solid #d1d5db;
      padding: 5.5px 8px;
      font-size: 10.5px;
      text-align: left;
      color: #000000 !important;
      opacity: 1 !important;
    }
    th {
      background: #f3f4f6;
      font-weight: bold;
      color: #111827 !important;
    }
    .footer-note {
      font-size: 9px;
      color: #4b5563;
      text-align: center;
      margin-top: 14px;
    }
  </style>
</head>
<body>
  <div class="doc-header">
    <div>
      <h1>SPACE COOPERATIVE - SYSTEM COMPILED REPORT</h1>
      <p>Official Cooperative System Report Export &bull; Confidential</p>
    </div>
    <div style="text-align: right;">
      <div style="font-weight: bold; color: #0f766e; font-size: 12px;">REPORT ID: ${reportId}</div>
      <div style="font-size: 9.5px; color: #374151;">Generated: ${dateStamp}</div>
    </div>
  </div>
  <div class="meta-box">
    <div><strong>Category:</strong> ${cat}</div>
    <div><strong>Timeline Filter:</strong> ${timeline}</div>
    <div><strong>Data Scope:</strong> ${scope}</div>
    <div><strong>Integrity:</strong> Audit Verified</div>
  </div>
  <table>
    <thead>
      <tr>
        <th style="width:14%;">Report ID</th>
        <th style="width:22%;">Category</th>
        <th style="width:30%;">Description</th>
        <th style="width:16%;">Inclusion Date</th>
        <th style="width:10%;">Compilation Date</th>
        <th style="width:8%;">Exported By</th>
      </tr>
    </thead>
    <tbody>
      ${tableRowsHtml}
    </tbody>
  </table>
  <div class="footer-note">
    SPACE Cooperative Portal &bull; Official Confidential System Report &bull; Printed by Staff on ${dateStamp}
  </div>
</body>
</html>`;

  let printFrame = document.getElementById('printReportIframe');
  if (!printFrame) {
    printFrame = document.createElement('iframe');
    printFrame.id = 'printReportIframe';
    printFrame.style.position = 'fixed';
    printFrame.style.right = '0';
    printFrame.style.bottom = '0';
    printFrame.style.width = '0';
    printFrame.style.height = '0';
    printFrame.style.border = '0';
    document.body.appendChild(printFrame);
  }

  const frameDoc = printFrame.contentWindow.document;
  frameDoc.open();
  frameDoc.write(printDoc);
  frameDoc.close();

  // Reflect printed report immediately in the Live Preview Table below
  if (typeof addReportToLivePreviewTable === 'function') {
    addReportToLivePreviewTable({
      reportId: reportId,
      category: cat,
      description: `Printed official system report for ${cat} (${scope}).`,
      inclusion: inclusionDate,
      timelineTag: timelineTag,
      compilation: new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }),
      compilationRaw: rawDate,
      exportedBy: staffName
    });
  }

  setTimeout(() => {
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();
  }, 350);
}

function createSimplePdfBlob(title, subtitle, tableRows, metaText) {
  function escapePdf(str) {
    return (str || '').toString().replace(/\\/g, '\\\\').replace(/\(/g, '\\(').replace(/\)/g, '\\)');
  }
  
  let streamLines = [];
  streamLines.push("BT");
  streamLines.push("/F1 15 Tf 50 745 Td (" + escapePdf(title) + ") Tj");
  streamLines.push("/F2 9 Tf 0 -16 Td (" + escapePdf(subtitle) + ") Tj");
  streamLines.push("ET");

  // Horizontal divider
  streamLines.push("0.06 0.46 0.43 RG 1.5 w 50 718 m 562 718 l S");

  // Data rows
  let yStart = 698;
  streamLines.push("BT /F2 8.5 Tf");
  streamLines.push("50 " + yStart + " Td");
  
  for (let i = 0; i < tableRows.length; i++) {
    const row = tableRows[i];
    const label = escapePdf(row[0] || '');
    const val = escapePdf(row[1] || 'N/A');
    if (i > 0) {
      streamLines.push("0 -15 Td");
    }
    const formattedLine = label.padEnd(28, ' ') + " :  " + val;
    streamLines.push("(" + formattedLine + ") Tj");
  }
  
  if (metaText) {
    streamLines.push("0 -24 Td");
    streamLines.push("(" + escapePdf(metaText) + ") Tj");
  }
  streamLines.push("ET");

  const streamContent = streamLines.join("\n");
  const streamLength = streamContent.length;

  const obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
  const obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
  const obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>\nendobj\n";
  const obj4 = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
  const obj5 = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
  const obj6 = "6 0 obj\n<< /Length " + streamLength + " >>\nstream\n" + streamContent + "\nendstream\nendobj\n";

  let header = "%PDF-1.4\n";
  let offset1 = header.length;
  let offset2 = offset1 + obj1.length;
  let offset3 = offset2 + obj2.length;
  let offset4 = offset3 + obj3.length;
  let offset5 = offset4 + obj4.length;
  let offset6 = offset5 + obj5.length;
  let xrefOffset = offset6 + obj6.length;

  let xref = "xref\n0 7\n0000000000 65535 f \n" +
    String(offset1).padStart(10, '0') + " 00000 n \n" +
    String(offset2).padStart(10, '0') + " 00000 n \n" +
    String(offset3).padStart(10, '0') + " 00000 n \n" +
    String(offset4).padStart(10, '0') + " 00000 n \n" +
    String(offset5).padStart(10, '0') + " 00000 n \n" +
    String(offset6).padStart(10, '0') + " 00000 n \n";

  let trailer = "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" + xrefOffset + "\n%%EOF";

  const pdfData = header + obj1 + obj2 + obj3 + obj4 + obj5 + obj6 + xref + trailer;
  return new Blob([pdfData], { type: 'application/pdf' });
}

function confirmPrintAction() {
  const fmt = document.getElementById('printFormatSelect')?.value || 'PDF Document (.pdf)';
  const member = membersDatabase[printingTargetAcc] || {
    fullName: printingTargetAcc,
    account_number: printingTargetAcc,
    civil: 'Single',
    contact: 'N/A',
    address: 'N/A'
  };

  closeModal('printOptionsModal');

  const accNo = member.account_number || printingTargetAcc;
  const name = member.fullName || member.name || accNo;

  const isSingle = (member.civil || member.civilStatus || 'Single').toString().toLowerCase() === 'single';
  const rawSpouse = (member.spouseName && member.spouseName !== 'Not Specified' && member.spouseName.trim() && member.spouseName !== 'Not Specified') 
    ? member.spouseName.trim() 
    : ((member.spouse && member.spouse.name && member.spouse.name !== 'Not Specified' && member.spouse.name.trim() && member.spouse.name !== 'Not Specified') ? member.spouse.name.trim() : '');
  const hasSpouse = !isSingle && rawSpouse !== '';
  const hasChildren = Array.isArray(member.childrenList) && member.childrenList.length > 0;

  if (fmt.includes('.docx') || fmt.includes('Word')) {
    // Generate Word Document (.doc format)
    const childrenStr = hasChildren
      ? member.childrenList.map(c => `${c.name || 'Child'} (${c.birthday || 'N/A'}${c.age ? ' - ' + (String(c.age).includes('Year') ? c.age : c.age + ' Years Old') : ''})`).join(', ')
      : '';
    const refsStr = Array.isArray(member.references) && member.references.length > 0
      ? member.references.join(', ')
      : 'Not Specified';

    const spouseRow = hasSpouse ? `<tr><th>Spouse Name</th><td>${rawSpouse}</td></tr>` : '';
    const childrenRow = hasChildren ? `<tr><th>Children Registered</th><td>${childrenStr}</td></tr>` : '';

    const docContent = `
      <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
      <head><title>Member Record ${accNo}</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #111; }
        h1 { color: #0f766e; border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 4px; }
        .meta { color: #555; font-size: 12px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; font-size: 13px; }
        th { background: #f0fdfa; font-weight: bold; width: 28%; }
      </style></head>
      <body>
        <h1>SPACE COOPERATIVE - MEMBER RECORD</h1>
        <div class="meta">Official System Verification & Record Export &bull; Account: ${accNo}</div>
        <table>
          <tr><th>Account Number</th><td>${accNo}</td></tr>
          <tr><th>Full Name</th><td>${name}</td></tr>
          <tr><th>Civil Status</th><td>${member.civil || member.civilStatus || 'Single'}</td></tr>
          <tr><th>Account Status</th><td>${member.account_status || member.status || 'Active'}</td></tr>
          <tr><th>Sex / Gender</th><td>${member.sex || 'Not Specified'}</td></tr>
          <tr><th>Nationality</th><td>${member.nationality || 'Filipino'}</td></tr>
          <tr><th>Contact Number</th><td>${member.contact_number || member.contact || 'N/A'}</td></tr>
          <tr><th>Home Address</th><td>${member.address || 'Not Specified'}</td></tr>
          <tr><th>Employer / Occupation</th><td>${member.employer || 'Not Specified'} - ${member.occupation || 'N/A'}</td></tr>
          <tr><th>Employer Address</th><td>${member.employer_address || 'Not Specified'}</td></tr>
          <tr><th>Father's Name</th><td>${(member.father_name && member.father_name !== 'Not Specified' && member.father_name.trim()) ? member.father_name : 'Not Specified'}</td></tr>
          <tr><th>Mother's Name</th><td>${(member.mother_name && member.mother_name !== 'Not Specified' && member.mother_name.trim()) ? member.mother_name : 'Not Specified'}</td></tr>
          ${spouseRow}
          ${childrenRow}
          <tr><th>Character References</th><td>${refsStr}</td></tr>
        </table>
        <p style="margin-top: 24px; font-size: 11px; color: #666;">Exported from Space Cooperative Portal on ${new Date().toLocaleString()}</p>
      </body></html>
    `;
    const blob = new Blob([docContent], { type: 'application/msword' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Member_Record_${accNo}.doc`;
    link.click();
    showToast('success', 'Word Document Downloaded', `Member record for ${name} downloaded successfully.`);
  } else {
    // Generate Direct PDF File Download
    const childrenStr = hasChildren
      ? member.childrenList.map(c => `${c.name || 'Child'}`).join(', ')
      : '';
    const refsStr = Array.isArray(member.references) && member.references.length > 0
      ? member.references.join(', ')
      : 'Not Specified';

    const pdfRows = [
      ['Account Number', accNo],
      ['Full Name', name],
      ['Civil Status', member.civil || member.civilStatus || 'Single'],
      ['Account Status', member.account_status || member.status || 'Active'],
      ['Sex / Gender', member.sex || 'Not specified'],
      ['Nationality', member.nationality || 'Filipino'],
      ['Contact Number', member.contact_number || member.contact || 'N/A'],
      ['Home Address', member.address || 'N/A'],
      ['Employer', member.employer || 'Not Specified'],
      ['Occupation', member.occupation || 'N/A'],
      ['Father\'s Name', member.father_name || 'Not specified'],
      ['Mother\'s Name', member.mother_name || 'Not specified']
    ];

    if (hasSpouse) {
      pdfRows.push(['Spouse Name', rawSpouse]);
    }
    if (hasChildren) {
      pdfRows.push(['Children', childrenStr]);
    }
    pdfRows.push(['References', refsStr]);

    const pdfBlob = createSimplePdfBlob(
      'SPACE COOPERATIVE - MEMBER RECORD',
      `Official System Verification & Record Export | Account: ${accNo}`,
      pdfRows,
      `Exported from Space Cooperative Portal on ${new Date().toLocaleString()}`
    );

    const link = document.createElement('a');
    link.href = URL.createObjectURL(pdfBlob);
    link.download = `Member_Record_${accNo}.pdf`;
    link.click();
    showToast('success', 'PDF Document Downloaded', `Member record for ${name} downloaded successfully as PDF.`);
  }
}

function openExportValidationModal() {
  if (typeof validateReportDateRange === 'function' && !validateReportDateRange('download')) {
    return;
  }

  const catSelect = document.getElementById('reportCategorySelect');
  const timeSelect = document.getElementById('reportTimeRange');
  const scopeSelect = document.getElementById('reportDataScopeSelect');
  const fmtSelect = document.getElementById('reportFormatSelect');

  if (catSelect && document.getElementById('valReportType')) {
    const opt = catSelect.options[catSelect.selectedIndex];
    document.getElementById('valReportType').innerText = opt ? opt.text : catSelect.value;
  }
  if (timeSelect && document.getElementById('valTimeline')) {
    let timelineText = timeSelect.options[timeSelect.selectedIndex]?.text || timeSelect.value;
    if (timeSelect.value === 'custom') {
      const sVal = document.getElementById('customStartDate')?.value;
      const eVal = document.getElementById('customEndDate')?.value;
      if (sVal && eVal) {
        const partsS = sVal.split('-');
        const partsE = eVal.split('-');
        timelineText = `${partsS[1]}/${partsS[2]}/${partsS[0]} - ${partsE[1]}/${partsE[2]}/${partsE[0]} (Custom Range)`;
      }
    }
    document.getElementById('valTimeline').innerText = timelineText;
  }
  if (scopeSelect && document.getElementById('valScope')) {
    const opt = scopeSelect.options[scopeSelect.selectedIndex];
    document.getElementById('valScope').innerText = opt ? opt.text : scopeSelect.value;
  }
  if (fmtSelect && document.getElementById('valFormat')) {
    const opt = fmtSelect.options[fmtSelect.selectedIndex];
    document.getElementById('valFormat').innerText = opt ? opt.text : fmtSelect.value;
  }

  openModal('exportValidationModal');
}

function previewFromValidationModal() {
  closeModal('exportValidationModal');
  previewCompiledReport();
}

function printFromPreviewModal() {
  closeModal('reportDocumentPreviewModal');
  printCompiledReport();
}

function downloadFromPreviewModal() {
  closeModal('reportDocumentPreviewModal');
  confirmExportAction();
}

/**
 * Interactive Document Preview — Shows what exact file and information the user will download.
 */
function previewCompiledReport() {
  if (typeof validateReportDateRange === 'function' && !validateReportDateRange('preview')) {
    return;
  }

  const catSelect = document.getElementById('reportCategorySelect');
  const timeSelect = document.getElementById('reportTimeRange');
  const scopeSelect = document.getElementById('reportDataScopeSelect');
  const fmtSelect = document.getElementById('reportFormatSelect');

  const cat = catSelect ? catSelect.options[catSelect.selectedIndex].text : 'Consolidated Report';
  let timeline = timeSelect ? timeSelect.options[timeSelect.selectedIndex].text : 'All Time';
  let inclusionDate = '08/01/2026 - 08/31/2026';
  let timelineTag = timeline;

  if (timeSelect && timeSelect.value === 'custom') {
    const sVal = document.getElementById('customStartDate')?.value;
    const eVal = document.getElementById('customEndDate')?.value;
    if (sVal && eVal) {
      const partsS = sVal.split('-');
      const partsE = eVal.split('-');
      inclusionDate = `${partsS[1]}/${partsS[2]}/${partsS[0]} - ${partsE[1]}/${partsE[2]}/${partsE[0]}`;
      timeline = `${inclusionDate} (Custom Range)`;
      timelineTag = 'Custom Range';
    }
  } else if (timeSelect && timeSelect.value === 'past_q1') {
    inclusionDate = '01/01/2026 - 03/31/2026';
    timelineTag = '1st Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_q2') {
    inclusionDate = '04/01/2026 - 06/30/2026';
    timelineTag = '2nd Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_q3') {
    inclusionDate = '07/01/2026 - 09/30/2026';
    timelineTag = '3rd Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_q4') {
    inclusionDate = '10/01/2026 - 12/31/2026';
    timelineTag = '4th Quarter 2026';
  } else if (timeSelect && timeSelect.value === 'past_1_year') {
    inclusionDate = '01/01/2026 - 12/31/2026';
    timelineTag = 'Full Year 2026';
  }

  const scope = scopeSelect ? scopeSelect.options[scopeSelect.selectedIndex].text : 'All Records';
  const fmt = fmtSelect ? fmtSelect.options[fmtSelect.selectedIndex].text : 'PDF Document (.pdf)';

  const reportId = `RPT-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 900) + 100)}`;
  window._activePreviewReportId = reportId;

  const fileName = (fmt.includes('Word') || fmt.includes('.docx'))
    ? `Cooperative_Report_${reportId}.doc`
    : `Cooperative_Report_${reportId}.pdf`;

  const badgeEl = document.getElementById('previewDocFileBadge');
  if (badgeEl) badgeEl.innerText = fileName;

  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.innerText = val;
  };

  setEl('docPreviewReportId', reportId);
  setEl('docPreviewCategory', cat);
  setEl('docPreviewTimeline', timeline);
  setEl('docPreviewScope', scope);
  setEl('docPreviewFormat', fmt);

  setEl('sheetReportId', `REPORT ID: ${reportId}`);
  setEl('sheetGeneratedTime', `Generated: ${new Date().toLocaleString()}`);
  setEl('sheetMetaCategory', cat);
  setEl('sheetMetaTimeline', timeline);
  setEl('sheetMetaScope', scope);

  // Populate preview table
  const previewTbody = document.getElementById('docPreviewTableBody');
  if (previewTbody) {
    const rows = document.querySelectorAll('#reportPreviewTable tbody tr:not(.empty-state-row)');
    let rowsHtml = '';
    rows.forEach(r => {
      if (r.style.display !== 'none') {
        const cells = r.querySelectorAll('td');
        if (cells.length >= 5) {
          rowsHtml += `<tr>
            <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; color: #0f766e;">${cells[0].innerText.trim()}</td>
            <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${cells[1].innerText.trim()}</td>
            <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${cells[2].innerText.trim()}</td>
            <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${cells[3].innerText.trim()}</td>
            <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${cells[4].innerText.trim()}</td>
            <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${cells[5] ? cells[5].innerText.trim() : 'Staff'}</td>
          </tr>`;
        }
      }
    });
    if (!rowsHtml) {
      rowsHtml = `<tr>
        <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; color: #0f766e;">${reportId}</td>
        <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${cat}</td>
        <td style="border: 1px solid #d1d5db; padding: 6px 8px;">Compiled report dataset matching ${scope} scope filter.</td>
        <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${inclusionDate}</td>
        <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })}</td>
        <td style="border: 1px solid #d1d5db; padding: 6px 8px;">${window.CURRENT_STAFF_NAME || 'Staff Administrator'}</td>
      </tr>`;
    }
    previewTbody.innerHTML = rowsHtml;
  }

  openModal('reportDocumentPreviewModal');
}

function confirmExportAction() {
  if (typeof validateReportDateRange === 'function' && !validateReportDateRange('download')) {
    return;
  }

  const cat = document.getElementById('valReportType')?.innerText || 'General Member Information';
  const timeline = document.getElementById('valTimeline')?.innerText || 'All Time';
  const scope = document.getElementById('valScope')?.innerText || 'All Registered Records';
  const fmt = document.getElementById('valFormat')?.innerText || 'PDF Document (.pdf)';
  closeModal('exportValidationModal');

  // Trigger report compilation & tracker card reveal
  const trackerGrid = document.getElementById('reportTrackerCards');
  if (trackerGrid) {
    trackerGrid.style.display = 'grid';
  }

  const lastGenElem = document.getElementById('lastGeneratedTime');
  if (lastGenElem) {
    lastGenElem.innerText = new Date().toLocaleString();
  }

  const reportId = window._activePreviewReportId || `RPT-${new Date().getFullYear()}-${String(Math.floor(Math.random() * 900) + 100)}`;
  window._activePreviewReportId = null;
  const dateStamp = new Date().toLocaleString();
  const rawDate = new Date().toISOString().split('T')[0];
  const staffName = window.CURRENT_STAFF_NAME || 'Staff Administrator';

  // Determine inclusion date
  let inclusionDate = '08/01/2026 - 08/31/2026';
  let timelineTag = timeline;
  const timeSelect = document.getElementById('reportTimeRange');
  if (timeSelect && timeSelect.value === 'custom') {
    const sVal = document.getElementById('customStartDate')?.value;
    const eVal = document.getElementById('customEndDate')?.value;
    if (sVal && eVal) {
      const partsS = sVal.split('-');
      const partsE = eVal.split('-');
      inclusionDate = `${partsS[1]}/${partsS[2]}/${partsS[0]} - ${partsE[1]}/${partsE[2]}/${partsE[0]}`;
      timelineTag = 'Custom Range';
    }
  } else if (timeline.includes('(')) {
    const m = timeline.match(/\((.*?)\)/);
    if (m) timelineTag = m[1];
  }

  if (fmt.includes('.docx') || fmt.includes('Word')) {
    const reportDoc = `
      <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
      <head><title>Cooperative System Report - ${reportId}</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #111; }
        h1 { color: #0f766e; border-bottom: 2px solid #0f766e; padding-bottom: 6px; }
        .meta-box { background: #f0fdfa; border: 1px solid #ccfbf1; padding: 12px; margin: 16px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; font-size: 12px; text-align: left; }
        th { background: #f8fafc; font-weight: bold; }
      </style></head>
      <body>
        <h1>SPACE COOPERATIVE - SYSTEM COMPILED REPORT</h1>
        <div class="meta-box">
          <p><strong>Report ID:</strong> ${reportId}</p>
          <p><strong>Category:</strong> ${cat}</p>
          <p><strong>Timeline Filter:</strong> ${timeline}</p>
          <p><strong>Data Scope:</strong> ${scope}</p>
          <p><strong>Generated on:</strong> ${dateStamp}</p>
          <p><strong>Exported By:</strong> ${staffName}</p>
        </div>
        <p><strong>Compiled System Dataset:</strong></p>
        <table>
          <thead>
            <tr>
              <th>Report ID</th>
              <th>Category</th>
              <th>Status</th>
              <th>Inclusion Date</th>
              <th>Compilation Date</th>
              <th>Exported By</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>${reportId}</td>
              <td>${cat}</td>
              <td>Verified Active</td>
              <td>${timeline}</td>
              <td>${dateStamp}</td>
              <td>${staffName}</td>
            </tr>
          </tbody>
        </table>
        <p style="margin-top: 24px; font-size: 11px; color: #666;">Official Cooperative System Report Export &bull; Confidential Record</p>
      </body></html>
    `;
    const blob = new Blob([reportDoc], { type: 'application/msword' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Cooperative_Report_${reportId}.doc`;
    link.click();
    showToast('success', 'Report Exported Successfully', `${cat} downloaded in Word format.`);
  } else {
    // Generate Direct PDF File Download for Report
    const reportRows = [
      ['Report ID', reportId],
      ['Report Category', cat],
      ['Timeline Filter', timeline],
      ['Data Scope', scope],
      ['Data Integrity Status', 'Audit Verified & Active'],
      ['Compilation Date', dateStamp],
      ['Exported By', staffName]
    ];

    const pdfBlob = createSimplePdfBlob(
      'SPACE COOPERATIVE - SYSTEM REPORT',
      `Official System Report Export | ID: ${reportId}`,
      reportRows,
      `Official Cooperative System Report &bull; Generated on ${dateStamp} by ${staffName}`
    );

    const link = document.createElement('a');
    link.href = URL.createObjectURL(pdfBlob);
    link.download = `Cooperative_Report_${reportId}.pdf`;
    link.click();
    showToast('success', 'Report Downloaded', `${cat} downloaded successfully as PDF.`);
  }

  // Reflect downloaded report immediately in the Live Preview Table below
  if (typeof addReportToLivePreviewTable === 'function') {
    addReportToLivePreviewTable({
      reportId: reportId,
      category: cat,
      description: `Exported ${fmt.includes('Word') ? 'Word (.doc)' : 'PDF'} document for ${cat} (${scope}).`,
      inclusion: inclusionDate,
      timelineTag: timelineTag,
      compilation: new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }),
      compilationRaw: rawDate,
      exportedBy: staffName
    });
  }
}

// ================= 11. AUDIT LOG DETAILS =================
function openAuditDetailsModal(accNo, action, time, staff, remarks) {
  activeAuditAccNo = accNo;
  activeAuditAction = action;
  activeAuditRemarks = remarks || '';

  const memberData = membersDatabase[accNo] || { fullName: accNo, name: accNo };
  const setTxt = (id, txt) => {
    const el = document.getElementById(id);
    if (el) el.innerText = txt;
  };

  setTxt('auditModalAccNo', accNo);
  setTxt('auditModalName', memberData.fullName || memberData.name || accNo);
  setTxt('auditModalTime', time);
  setTxt('auditModalStaff', staff);

  const actionBadge = document.getElementById('auditModalAction');
  if (actionBadge) {
    actionBadge.innerText = action;
    const isDel = action.toLowerCase().includes('delete');
    const isCreate = action.toLowerCase().includes('create') || action.toLowerCase().includes('registration');
    actionBadge.className = `status-pill ${isDel ? 'status-locked' : (isCreate ? 'status-active' : 'status-flagged')}`;
  }

  // Member photo thumbnail
  const photoSlot = document.getElementById('auditMemberPhotoSlot');
  if (photoSlot) {
    if (memberData.passport_photo && !memberData.passport_photo.includes('default-avatar')) {
      photoSlot.innerHTML = `<img src="${memberData.passport_photo}" alt="Member Photo" style="width:100%; height:100%; object-fit:cover; display:block;">`;
    } else {
      photoSlot.innerHTML = `<i class="fa-solid fa-user" style="font-size: 1.5rem; color: var(--primary-teal);"></i>`;
    }
  }

  renderAuditData(accNo, action, remarks);
  openModal('auditDetailsModal');
}

function renderAuditData(accNo, action, remarks) {
  const content = document.getElementById('auditModificationContent');
  if (!content) return;

  const currentAction = action || activeAuditAction || '';
  const currentRemarks = remarks !== undefined ? remarks : activeAuditRemarks;
  const isPasswordReset = currentAction.toLowerCase().includes('reset');
  const isDeleted = currentAction.toLowerCase().includes('delete');

  if (isPasswordReset) {
    content.innerHTML = `
      <div class="audit-info-box" style="text-align: center; padding: 1.25rem;">
        <i class="fa-solid fa-key" style="font-size: 1.8rem; color: var(--primary-teal); margin-bottom: 0.5rem; display: block;"></i>
        <h5 style="color: var(--text-main); margin-bottom: 0.35rem; font-weight: 700;">Password Reset Event</h5>
        <p style="color: var(--text-dim); font-size: 0.86rem; margin: 0; line-height: 1.45;">${currentRemarks || 'Member account password was reset to the default account number credential.'}</p>
      </div>
    `;
    return;
  }

  if (isDeleted) {
    content.innerHTML = `
      <div class="audit-info-box" style="text-align: center; padding: 1.25rem; border-color: rgba(239, 68, 68, 0.4);">
        <i class="fa-solid fa-user-xmark" style="font-size: 1.8rem; color: #ef4444; margin-bottom: 0.5rem; display: block;"></i>
        <h5 style="color: var(--text-main); margin-bottom: 0.35rem; font-weight: 700;">Member Deletion Event</h5>
        <p style="color: var(--text-dim); font-size: 0.86rem; margin: 0; line-height: 1.45;">${currentRemarks || 'Staff marked member record as deleted.'}</p>
      </div>
    `;
    return;
  }

  // Parse JSON change diff if recorded in remarks
  let parsedChanges = null;
  if (currentRemarks) {
    try {
      const obj = typeof currentRemarks === 'string' ? JSON.parse(currentRemarks) : currentRemarks;
      if (obj && obj.changes && Object.keys(obj.changes).length > 0) {
        parsedChanges = obj.changes;
      }
    } catch(e) {
      parsedChanges = null;
    }
  }

  if (parsedChanges && Object.keys(parsedChanges).length > 0) {
    let diffHtml = `
      <div style="overflow-x: auto;">
        <table class="dashboard-table audit-diff-table" style="margin: 0; font-size: 0.86rem; width: 100%;">
          <thead>
            <tr>
              <th style="width: 35%;">Field Modified</th>
              <th style="width: 32%;">Previous Value</th>
              <th style="width: 33%;">Updated Value</th>
            </tr>
          </thead>
          <tbody>
    `;
    for (const [field, diff] of Object.entries(parsedChanges)) {
      diffHtml += `
        <tr>
          <td><strong style="color: var(--text-main);">${field}</strong></td>
          <td><span style="color: #ef4444; text-decoration: line-through; font-size: 0.84rem;">${diff.old || 'Not Specified'}</span></td>
          <td><strong style="color: #10b981; font-size: 0.84rem;">${diff.new || 'Not Specified'}</strong></td>
        </tr>
      `;
    }
    diffHtml += `</tbody></table></div>`;
    content.innerHTML = diffHtml;
  } else if (currentRemarks && typeof currentRemarks === 'string' && currentRemarks.trim()) {
    content.innerHTML = `
      <div class="audit-info-box">
        <i class="fa-solid fa-circle-info text-cyan me-2"></i> ${currentRemarks}
      </div>
    `;
  } else {
    content.innerHTML = `
      <div class="audit-info-box" style="text-align: center;">
        <i class="fa-solid fa-clipboard-check text-cyan me-2"></i> Action recorded: <strong>${currentAction}</strong>. No individual field-level diff was captured.
      </div>
    `;
  }
}

// ================= 12. EMPTY STATE RENDERING FOR SEARCHES =================
function handleEmptyTableState(tbody, visibleCount, colSpan = 6, message = 'No matching records found', subtext = 'Try adjusting your search query or clearing active filters.') {
  if (!tbody) return;
  const existingEmpty = tbody.querySelector('.empty-state-row');
  if (visibleCount === 0) {
    if (!existingEmpty) {
      const emptyTr = document.createElement('tr');
      emptyTr.className = 'empty-state-row';
      emptyTr.innerHTML = `
        <td colspan="${colSpan}">
          <div class="empty-state-box">
            <div class="empty-state-icon">
              <i class="fa-solid fa-magnifying-glass-chart"></i>
            </div>
            <h4 class="empty-state-title">${message}</h4>
            <p class="empty-state-desc">${subtext}</p>
          </div>
        </td>
      `;
      tbody.appendChild(emptyTr);
    }
  } else {
    if (existingEmpty) existingEmpty.remove();
  }
}

// ================= 13. DASHBOARD & ALL TAB FILTERS =================
function toggleDashFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('dashFilterPopover');
  if (popover) {
    popover.classList.toggle('hidden');
  }
}

function applyDashFilters(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('dashFilterPopover');
  if (popover) popover.classList.add('hidden');
  if (typeof filterRecentRegistrations === 'function') filterRecentRegistrations();
}

function toggleRecordsFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('recordsFilterPopover');
  if (popover) {
    popover.classList.toggle('hidden');
  }
}

function applyRecordsFilters(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('recordsFilterPopover');
  if (popover) popover.classList.add('hidden');
  if (typeof filterMemberRecords === 'function') filterMemberRecords();
}

function toggleAuditFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('auditFilterPopover');
  if (popover) {
    popover.classList.toggle('hidden');
  }
}

function applyAuditFilters(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('auditFilterPopover');
  if (popover) popover.classList.add('hidden');
  if (typeof filterAuditLog === 'function') filterAuditLog();
}

function toggleCredFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('credFilterPopover');
  if (popover) {
    popover.classList.toggle('hidden');
  }
}

function applyCredFilters(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('credFilterPopover');
  if (popover) popover.classList.add('hidden');
  if (typeof filterCredentialsTable === 'function') filterCredentialsTable();
}

function toggleSurveysFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('surveysFilterPopover');
  if (popover) {
    const isOpening = popover.classList.contains('hidden');
    if (isOpening) {
      const select = document.getElementById('surveyStatusFilter');
      if (select && window.appliedSurveyStatusFilter) {
        select.value = window.appliedSurveyStatusFilter;
      }
    }
    popover.classList.toggle('hidden');
  }
}

function applySurveysFilters(event) {
  if (event) event.stopPropagation();
  const statusSelect = document.getElementById('surveyStatusFilter');
  if (statusSelect) {
    window.appliedSurveyStatusFilter = (statusSelect.value || 'all').toLowerCase().trim();
  }
  const popover = document.getElementById('surveysFilterPopover');
  if (popover) popover.classList.add('hidden');
  if (typeof filterSurveysTable === 'function') filterSurveysTable();
}

function toggleUnifiedFilterMenu(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('unifiedFilterPopover');
  if (popover) {
    popover.classList.toggle('hidden');
  }
}

function applyUnifiedFilters(event) {
  if (event) event.stopPropagation();
  const popover = document.getElementById('unifiedFilterPopover');
  if (popover) popover.classList.add('hidden');
  if (typeof filterPreviewTable === 'function') filterPreviewTable();
}

// Global click listener to close popovers & dropdowns when clicking outside
document.addEventListener('click', function(e) {
  const adminMenu = document.getElementById('adminUserDropdownMenu');
  const adminBtn = document.getElementById('adminUserDropdownBtn');
  if (adminMenu && !adminMenu.classList.contains('hidden')) {
    if (!adminMenu.contains(e.target) && !adminBtn?.contains(e.target)) {
      closeAdminUserMenu();
    }
  }

  const dashPopover = document.getElementById('dashFilterPopover');
  const dashBtn = document.getElementById('dashFilterBtn');
  if (dashPopover && !dashPopover.classList.contains('hidden')) {
    if (!dashPopover.contains(e.target) && !dashBtn?.contains(e.target)) {
      dashPopover.classList.add('hidden');
    }
  }

  const recordsPopover = document.getElementById('recordsFilterPopover');
  const recordsBtn = document.getElementById('recordsFilterBtn');
  if (recordsPopover && !recordsPopover.classList.contains('hidden')) {
    if (!recordsPopover.contains(e.target) && !recordsBtn?.contains(e.target)) {
      recordsPopover.classList.add('hidden');
    }
  }

  const auditPopover = document.getElementById('auditFilterPopover');
  const auditBtn = document.getElementById('auditFilterBtn');
  if (auditPopover && !auditPopover.classList.contains('hidden')) {
    if (!auditPopover.contains(e.target) && !auditBtn?.contains(e.target)) {
      auditPopover.classList.add('hidden');
    }
  }

  const credPopover = document.getElementById('credFilterPopover');
  const credBtn = document.getElementById('credFilterBtn');
  if (credPopover && !credPopover.classList.contains('hidden')) {
    if (!credPopover.contains(e.target) && !credBtn?.contains(e.target)) {
      credPopover.classList.add('hidden');
    }
  }

  const surveysPopover = document.getElementById('surveysFilterPopover');
  const surveysBtn = document.getElementById('surveysFilterBtn');
  if (surveysPopover && !surveysPopover.classList.contains('hidden')) {
    if (!surveysPopover.contains(e.target) && !surveysBtn?.contains(e.target)) {
      surveysPopover.classList.add('hidden');
    }
  }

  const unifiedPopover = document.getElementById('unifiedFilterPopover');
  const unifiedBtn = document.getElementById('previewFilterBtn');
  if (unifiedPopover && !unifiedPopover.classList.contains('hidden')) {
    if (!unifiedPopover.contains(e.target) && !unifiedBtn?.contains(e.target)) {
      unifiedPopover.classList.add('hidden');
    }
  }
});

// ================= TOP NAVBAR ADMIN USER DROPDOWN =================
function toggleAdminUserMenu(event) {
  if (event) event.stopPropagation();
  const menu = document.getElementById('adminUserDropdownMenu');
  const btn = document.getElementById('adminUserDropdownBtn');
  const caret = document.getElementById('adminDropdownCaret');
  if (!menu) return;

  const isHidden = menu.classList.contains('hidden');
  if (isHidden) {
    menu.classList.remove('hidden');
    if (btn) btn.setAttribute('aria-expanded', 'true');
    if (caret) caret.style.transform = 'rotate(180deg)';
  } else {
    menu.classList.add('hidden');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    if (caret) caret.style.transform = 'rotate(0deg)';
  }
}

function closeAdminUserMenu() {
  const menu = document.getElementById('adminUserDropdownMenu');
  const btn = document.getElementById('adminUserDropdownBtn');
  const caret = document.getElementById('adminDropdownCaret');
  if (menu) menu.classList.add('hidden');
  if (btn) btn.setAttribute('aria-expanded', 'false');
  if (caret) caret.style.transform = 'rotate(0deg)';
}

if (!window.openSettingsModal || window.openSettingsModal === Object.prototype.toString) {
  window.openSettingsModal = function() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
      if (typeof updateSettingsModalUI === 'function') {
        updateSettingsModalUI(
          window.portalThemeState?.theme || 'dark',
          window.portalThemeState?.accent || 'nebula',
          window.portalThemeState?.tiltEnabled !== false,
          window.portalThemeState?.cursorGlowEnabled !== false
        );
      }
      openModal('settingsModal');
    }
  };
}
if (!window.closeSettingsModal) {
  window.closeSettingsModal = function() {
    closeModal('settingsModal');
  };
}
window.toggleTheme = window.openSettingsModal;
window.openAdminPhotoModal = openAdminPhotoModal;
window.openAddMemberModal = openAddMemberModal;
window.toggleAdminUserMenu = toggleAdminUserMenu;
window.closeAdminUserMenu = closeAdminUserMenu;

window.toggleDashFilterMenu = toggleDashFilterMenu;
window.applyDashFilters = applyDashFilters;
window.toggleAuditFilterMenu = toggleAuditFilterMenu;
window.applyAuditFilters = applyAuditFilters;
window.toggleRecordsFilterMenu = toggleRecordsFilterMenu;
window.applyRecordsFilters = applyRecordsFilters;
window.handleMemberTimelineChange = handleMemberTimelineChange;
window.openMemberCustomDateModal = openMemberCustomDateModal;
window.closeMemberCustomDateModal = closeMemberCustomDateModal;
window.applyMemberCustomDateRange = applyMemberCustomDateRange;
window.validateCustomDates = validateCustomDates;
window.toggleCredFilterMenu = toggleCredFilterMenu;
window.applyCredFilters = applyCredFilters;
window.toggleSurveysFilterMenu = toggleSurveysFilterMenu;
window.applySurveysFilters = applySurveysFilters;
window.toggleUnifiedFilterMenu = toggleUnifiedFilterMenu;
window.applyUnifiedFilters = applyUnifiedFilters;
window.filterRecentRegistrations = filterRecentRegistrations;
window.filterCredentialsTable = filterCredentialsTable;
window.searchCredentialsTable = searchCredentialsTable;
window.setCredentialStatusFilter = setCredentialStatusFilter;
window.filterSurveysTable = filterSurveysTable;
window.filterPreviewTable = filterPreviewTable;
window.updateReportPreview = updateReportPreview;
window.handleTimelineChange = handleTimelineChange;
window.filterMemberRecords = filterMemberRecords;
window.searchMemberRecords = searchMemberRecords;
window.filterAuditLog = filterAuditLog;
window.searchAuditLog = searchAuditLog;
window.showToast = showToast;
window.dismissToast = dismissToast;

// ================= FILTER RECENT REGISTRATIONS (DASHBOARD) =================
function filterRecentRegistrations() {
  const searchInput = document.getElementById('recentRegSearch');
  const q = (searchInput?.value || '').toLowerCase().trim();
  const timeline = (document.getElementById('regTimelineFilter')?.value || 'all').toLowerCase();
  const type = (document.getElementById('regTypeFilter')?.value || 'all').toLowerCase();

  const rows = document.querySelectorAll('#recentRegistrationsTable tbody tr:not(.empty-state-row)');
  const tbody = document.querySelector('#recentRegistrationsTable tbody');
  let visible = 0;

  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    const matchText = !q || text.includes(q);

    const days = parseInt(r.getAttribute('data-days')) || 0;
    let matchTimeline = true;
    if (timeline === 'today') {
      matchTimeline = (days === 0);
    } else if (timeline === '7days') {
      matchTimeline = (days <= 7);
    } else if (timeline === '30days') {
      matchTimeline = (days <= 30);
    }

    const isEdited = r.getAttribute('data-edited') === 'true';
    let matchType = true;
    if (type === 'edited' && !isEdited) {
      matchType = false;
    }

    const isVisible = matchText && matchTimeline && matchType;
    r.style.display = isVisible ? '' : 'none';
    if (isVisible) visible++;
  });

  handleEmptyTableState(tbody, visible, 7, 'No Recent Registrations Found', 'No accounts matched your search query or filter options.');
}

// Global Member Custom Date Range State
window._memberCustomDateRange = { start: '', end: '' };

function handleMemberTimelineChange(val) {
  if (val === 'custom') {
    openMemberCustomDateModal();
  } else {
    const badge = document.getElementById('memberCustomDateBadge');
    if (badge) badge.classList.add('hidden');
    window._memberCustomDateRange = { start: '', end: '' };
  }
}

function openMemberCustomDateModal() {
  const modal = document.getElementById('memberCustomDateModal');
  if (!modal) return;
  const startInput = document.getElementById('memberCustomStartDate');
  const endInput = document.getElementById('memberCustomEndDate');
  const errBox = document.getElementById('memberCustomDateError');
  if (errBox) errBox.classList.add('hidden');

  if (startInput) startInput.value = window._memberCustomDateRange.start || '';
  if (endInput) endInput.value = window._memberCustomDateRange.end || '';

  openModal('memberCustomDateModal');
}

function closeMemberCustomDateModal() {
  closeModal('memberCustomDateModal');
  const select = document.getElementById('recordsTimelineFilter');
  if (select && (!window._memberCustomDateRange.start && !window._memberCustomDateRange.end)) {
    select.value = 'all';
  }
}

function applyMemberCustomDateRange() {
  const startInput = document.getElementById('memberCustomStartDate');
  const endInput = document.getElementById('memberCustomEndDate');
  const errBox = document.getElementById('memberCustomDateError');
  const errText = document.getElementById('memberCustomDateErrorText');

  const sVal = (startInput?.value || '').trim();
  const eVal = (endInput?.value || '').trim();

  if (sVal && eVal && sVal > eVal) {
    if (errBox) errBox.classList.remove('hidden');
    if (errText) errText.innerText = 'Start date cannot be after end date.';
    return;
  }
  if (!sVal && !eVal) {
    if (errBox) errBox.classList.remove('hidden');
    if (errText) errText.innerText = 'Please select at least a start date or end date.';
    return;
  }

  if (errBox) errBox.classList.add('hidden');
  window._memberCustomDateRange = { start: sVal, end: eVal };

  const badge = document.getElementById('memberCustomDateBadge');
  const badgeText = document.getElementById('memberCustomDateBadgeText');
  const select = document.getElementById('recordsTimelineFilter');
  if (select) select.value = 'custom';

  if (badge && badgeText) {
    badge.classList.remove('hidden');
    const formatD = (d) => {
      if (!d) return 'Any';
      const p = d.split('-');
      return `${p[1]}/${p[2]}/${p[0]}`;
    };
    badgeText.innerHTML = `<i class="fa-regular fa-calendar-check me-1"></i> ${formatD(sVal)} – ${formatD(eVal)}`;
  }

  closeModal('memberCustomDateModal');
  const popover = document.getElementById('recordsFilterPopover');
  if (popover) popover.classList.add('hidden');
  filterMemberRecords();
}

// Search & Filter Member Records table (Members view)
function filterMemberRecords() {
  const input = document.getElementById('memberRecordsSearch');
  const q = (input?.value || '').toLowerCase().trim();
  const dataScope = (document.getElementById('recordsDataScopeFilter')?.value || 'all').toLowerCase();
  const timeline = (document.getElementById('recordsTimelineFilter')?.value || 'all').toLowerCase();

  const rows = document.querySelectorAll('#memberRecordsTable tbody tr:not(.empty-state-row)');
  const tbody = document.querySelector('#memberRecordsTable tbody');
  let visible = 0;

  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    const matchText = !q || text.includes(q);

    const rowStatus = (r.getAttribute('data-status') || '').toLowerCase();
    let matchScope = true;
    if (dataScope === 'inactive') {
      matchScope = (rowStatus === 'inactive' || rowStatus === 'deleted');
    } else if (dataScope === 'flagged') {
      matchScope = (rowStatus === 'flagged');
    } else if (dataScope === 'active') {
      matchScope = (rowStatus === 'active');
    } else {
      // 'all' records
      matchScope = true;
    }

    const days = parseInt(r.getAttribute('data-days')) || 0;
    const rowDateStr = (r.getAttribute('data-created') || '').trim(); // 'YYYY-MM-DD'
    let matchTimeline = true;

    if (timeline === 'most_recent' || timeline === 'past_30_days' || timeline === '30days') {
      matchTimeline = (days <= 30);
    } else if (timeline === 'past_1_year') {
      matchTimeline = (days <= 365);
    } else if (timeline === 'past_q1' || timeline === 'past_q2' || timeline === 'past_q3' || timeline === 'past_q4') {
      if (rowDateStr) {
        const parts = rowDateStr.split('-');
        const month = parseInt(parts[1], 10); // 1-12
        if (timeline === 'past_q1') matchTimeline = (month >= 1 && month <= 3);
        else if (timeline === 'past_q2') matchTimeline = (month >= 4 && month <= 6);
        else if (timeline === 'past_q3') matchTimeline = (month >= 7 && month <= 9);
        else if (timeline === 'past_q4') matchTimeline = (month >= 10 && month <= 12);
      }
    } else if (timeline === 'custom') {
      const s = window._memberCustomDateRange?.start || '';
      const e = window._memberCustomDateRange?.end || '';
      if (s && rowDateStr && rowDateStr < s) matchTimeline = false;
      if (e && rowDateStr && rowDateStr > e) matchTimeline = false;
    } else if (timeline === 'today') {
      matchTimeline = (days === 0);
    } else if (timeline === '7days') {
      matchTimeline = (days <= 7);
    }

    const isVisible = matchText && matchScope && matchTimeline;
    r.style.display = isVisible ? '' : 'none';
    if (isVisible) visible++;
  });

  handleEmptyTableState(tbody, visible, 5, 'No Member Records Found', 'No cooperative members matched your active search and filter criteria.');
}

function searchMemberRecords(q) {
  filterMemberRecords();
}

// Search Audit Log table
function searchAuditLog(q) {
  filterAuditLog();
}

// Filter Audit Log by Action Type & Order
function filterAuditLog() {
  const searchInput = document.getElementById('auditLogSearch');
  const q = (searchInput?.value || '').toLowerCase().trim();
  const selectedAction = (document.getElementById('auditActionFilter')?.value || 'all').trim();
  const sortOrder = (document.getElementById('auditSortOrder')?.value || 'recent').trim();
  const rows = Array.from(document.querySelectorAll('#auditLogTable tbody tr:not(.empty-state-row)'));
  const tbody = document.querySelector('#auditLogTable tbody');
  
  if (rows.length === 0) return;
  let visibleCount = 0;

  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    const matchText = !q || text.includes(q);

    const action = (r.getAttribute('data-action') || '').trim();
    const rawAction = (r.getAttribute('data-raw-action') || '').trim();
    
    let matchesAction = false;
    if (selectedAction === 'all') {
      matchesAction = true;
    } else if (action === selectedAction || rawAction === selectedAction) {
      matchesAction = true;
    } else if (selectedAction === 'Information Update' && (action === 'profile_update' || rawAction === 'profile_update')) {
      matchesAction = true;
    } else if (selectedAction === 'Password Changed' && (action === 'password_change' || rawAction === 'password_change')) {
      matchesAction = true;
    } else if (selectedAction === 'Passport Photo Updated' && (action === 'photo_upload' || rawAction === 'photo_upload')) {
      matchesAction = true;
    } else if (selectedAction === 'Password Reset' && (action === 'Reset Password' || rawAction === 'Reset Password')) {
      matchesAction = true;
    } else if (selectedAction === 'Deleted Member Information' && (action === 'Deleted Member Information' || rawAction === 'Deleted Member Information' || action.toLowerCase().includes('delete') || rawAction.toLowerCase().includes('delete'))) {
      matchesAction = true;
    }

    const isVisible = matchText && matchesAction;
    r.style.display = isVisible ? '' : 'none';
    if (isVisible) visibleCount++;
  });

  const visibleRows = rows.filter(r => r.style.display !== 'none');
  visibleRows.sort((a, b) => {
    if (sortOrder === 'oldest') {
      return (a.getAttribute('data-timestamp') || '').localeCompare(b.getAttribute('data-timestamp') || '');
    } else if (sortOrder === 'staff_asc') {
      return (a.getAttribute('data-staff') || '').localeCompare(b.getAttribute('data-staff') || '');
    } else if (sortOrder === 'target_asc') {
      return (a.getAttribute('data-target') || '').localeCompare(b.getAttribute('data-target') || '');
    } else {
      return (b.getAttribute('data-timestamp') || '').localeCompare(a.getAttribute('data-timestamp') || '');
    }
  });

  visibleRows.forEach(r => tbody.appendChild(r));
  handleEmptyTableState(tbody, visibleCount, 6, 'No Audit Log Matches', 'No audit trail entries found for the selected filter criteria.');
}

// Search & Filter Credentials table
function filterCredentialsTable() {
  const searchInput = document.getElementById('credentialsSearchInput');
  const q = (searchInput?.value || '').toLowerCase().trim();
  const status = (document.getElementById('credStatusFilter')?.value || 'all').toLowerCase();

  const rows = document.querySelectorAll('#credentialsTable tbody tr:not(.empty-state-row)');
  const tbody = document.querySelector('#credentialsTable tbody');
  let visible = 0;

  rows.forEach(r => {
    const text = r.innerText.toLowerCase();
    const matchSearch = !q || text.includes(q);

    const rowStatus = (r.getAttribute('data-status') || '').toLowerCase();
    const matchStatus = (status === 'all' || rowStatus === status);

    const isVisible = matchSearch && matchStatus;
    r.style.display = isVisible ? '' : 'none';
    if (isVisible) visible++;
  });

  handleEmptyTableState(tbody, visible, 5, 'No Credentials Found', 'No member accounts match your active search and filter criteria.');
}

function searchCredentialsTable(q) {
  filterCredentialsTable();
}

function setCredentialStatusFilter(statusVal) {
  const filterInput = document.getElementById('credStatusFilter');
  if (filterInput) {
    filterInput.value = statusVal;
  }
  document.querySelectorAll('.cred-status-chip, .cred-status-card').forEach(card => {
    if (card.getAttribute('data-status-val') === statusVal) {
      card.classList.add('active');
    } else {
      card.classList.remove('active');
    }
  });
}

// Filter Surveys table
function filterSurveysTable() {
  const searchInput = document.getElementById('surveySearchInput');
  const search = (searchInput?.value || '').toLowerCase().trim();
  const status = (window.appliedSurveyStatusFilter || 'all').toLowerCase().trim();
  const rows = document.querySelectorAll('#surveysTable tbody tr[data-status]');
  const tbody = document.querySelector('#surveysTable tbody');
  let visibleCount = 0;

  rows.forEach(row => {
    const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
    const text = row.textContent.toLowerCase();

    const matchesSearch = !search || text.includes(search);
    const isClosedOrInactive = (rowStatus === 'closed' || rowStatus === 'inactive');
    const matchesStatus = (
      status === 'all' || 
      ((status === 'closed' || status === 'inactive') && isClosedOrInactive) ||
      rowStatus === status
    );

    const isVisible = matchesSearch && matchesStatus;
    row.style.display = isVisible ? '' : 'none';
    if (isVisible) visibleCount++;
  });

  const noMatches = document.getElementById('surveysNoMatchesRow');
  if (noMatches) {
    noMatches.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
  } else if (tbody) {
    handleEmptyTableState(tbody, visibleCount, 6, 'No Surveys Found', 'No surveys match your active search and status filter.');
  }
}

// Reports preview filter
function filterPreviewTable() {
  const catSelect = document.getElementById('reportCategorySelect');
  const popoverCat = document.getElementById('previewFilterCategory');
  const searchInput = document.getElementById('reportPreviewSearch');
  
  let selectedCategory = 'all';
  if (popoverCat && popoverCat.value !== 'all') {
    selectedCategory = popoverCat.value;
  } else if (catSelect) {
    const val = catSelect.value;
    if (val === 'member_summary') selectedCategory = 'General Member Information';
    else if (val === 'survey_analytics') selectedCategory = 'General Member Survey Feedback Only';
    else if (val === 'audit_activity') selectedCategory = 'System Audit & Staff Action Log';
    else if (val === 'credentials_security') selectedCategory = 'Password Security';
    else if (val !== 'all_combined') selectedCategory = val;
  }

  const query = (searchInput?.value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('#reportPreviewTable tbody tr:not(.empty-state-row)');
  const tbody = document.querySelector('#reportPreviewTable tbody');
  let visibleCount = 0;

  rows.forEach(row => {
    const rowCat = (row.getAttribute('data-cat') || '').trim();
    const rowText = row.innerText.toLowerCase();

    const matchesCat = (selectedCategory === 'all' || selectedCategory === 'all_combined' || rowCat === selectedCategory || rowCat.includes(selectedCategory));
    const matchesSearch = (!query || rowText.includes(query));

    if (matchesCat && matchesSearch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const scopeCountElem = document.getElementById('reportScopeCount');
  if (scopeCountElem) {
    scopeCountElem.innerText = visibleCount + ' Report Records';
  }

  handleEmptyTableState(tbody, visibleCount, 5, 'No Report Records Found', 'No records match the current category and search keywords.');
}

function updateReportPreview() {
  const catSelect = document.getElementById('reportCategorySelect');
  const popoverCat = document.getElementById('previewFilterCategory');
  
  if (catSelect && popoverCat) {
    const val = catSelect.value;
    if (val === 'member_summary') popoverCat.value = 'General Member Information';
    else if (val === 'survey_analytics') popoverCat.value = 'General Member Survey Feedback Only';
    else if (val === 'audit_activity') popoverCat.value = 'System Audit & Staff Action Log';
    else if (val === 'credentials_security') popoverCat.value = 'Password Security';
    else if (val === 'all_combined') popoverCat.value = 'all';
    else popoverCat.value = val;
  }
  
  filterPreviewTable();
}

function handleTimelineChange() {
  const val = document.getElementById('reportTimeRange')?.value;
  const customBox = document.getElementById('customDateRangeBox');
  if (!customBox) return;
  if (val === 'custom') {
    customBox.classList.remove('hidden');
    validateCustomDates();
  } else {
    customBox.classList.add('hidden');
    updateReportPreview();
  }
}

function formatCustomDateString(dStr) {
  if (!dStr) return '';
  const parts = dStr.split('-');
  if (parts.length === 3) {
    return `${parts[1]}/${parts[2]}/${parts[0]}`;
  }
  return dStr;
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function showReportDateErrorModal(title, message, reason, focusTargetId) {
  const headerEl = document.getElementById('reportDateErrorHeader');
  const titleEl = document.getElementById('reportDateErrorTitle');
  const msgEl = document.getElementById('reportDateErrorMessage');
  const reasonEl = document.getElementById('reportDateErrorReason');

  if (headerEl) {
    headerEl.innerText = 'Invalid Custom Date Range';
  }
  if (titleEl) {
    titleEl.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> ${escapeHtml(title)}`;
  }
  if (msgEl) {
    msgEl.innerText = message;
  }
  if (reasonEl) {
    reasonEl.innerText = reason;
  }

  window._lastDateErrorTargetId = focusTargetId || 'customStartDate';
  openModal('reportDateValidationModal');
}

function focusReportCustomDates() {
  const targetId = window._lastDateErrorTargetId || 'customStartDate';
  const container = document.getElementById('customDateRangeBox');
  if (container) {
    container.classList.remove('hidden');
  }
  const input = document.getElementById(targetId);
  if (input) {
    setTimeout(() => {
      input.focus();
      input.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 150);
  }
}

function validateCustomDates() {
  const startInput = document.getElementById('customStartDate');
  const endInput = document.getElementById('customEndDate');
  const errorBox = document.getElementById('dateErrorMessage');
  const errorText = document.getElementById('dateErrorText');
  if (!startInput || !endInput) return true;

  const sVal = startInput.value.trim();
  const eVal = endInput.value.trim();

  if (!sVal && !eVal) {
    if (errorBox) errorBox.classList.remove('hidden');
    if (errorText) errorText.innerText = 'Please provide both Start Date and End Date for custom range.';
    return false;
  } else if (!sVal && eVal) {
    if (errorBox) errorBox.classList.remove('hidden');
    if (errorText) errorText.innerText = 'Start Date is required.';
    return false;
  } else if (sVal && !eVal) {
    if (errorBox) errorBox.classList.remove('hidden');
    if (errorText) errorText.innerText = 'End Date is required.';
    return false;
  } else if (sVal > eVal) {
    if (errorBox) errorBox.classList.remove('hidden');
    if (errorText) errorText.innerText = 'Start date cannot be after end date.';
    return false;
  } else {
    if (errorBox) errorBox.classList.add('hidden');
  }

  // Update report live preview inclusion badge if dates are set
  const inclusionBadge = document.getElementById('previewInclusionBadge');
  if (inclusionBadge && sVal && eVal) {
    inclusionBadge.innerHTML = `<i class="fa-regular fa-calendar"></i> ${formatCustomDateString(sVal)} - ${formatCustomDateString(eVal)} <span class="timeline-label-tag">(Custom Range)</span>`;
  }
  return true;
}

function validateReportDateRange(actionType) {
  const timeSelect = document.getElementById('reportTimeRange');
  if (!timeSelect || timeSelect.value !== 'custom') {
    return true;
  }

  const startInput = document.getElementById('customStartDate');
  const endInput = document.getElementById('customEndDate');
  const sVal = startInput ? startInput.value.trim() : '';
  const eVal = endInput ? endInput.value.trim() : '';

  const actionVerb = actionType === 'print' ? 'printing' : (actionType === 'download' ? 'downloading' : 'previewing');

  // Case 1: Both dates are missing / invalid
  if (!sVal && !eVal) {
    showReportDateErrorModal(
      'Both Start Date and End Date are Missing',
      `You selected "Custom Date Range", but both the Start Date and End Date inputs are blank. You cannot proceed with ${actionVerb} the report.`,
      'The system requires a complete timeframe to query and compile records. You cannot proceed with printing or downloading without specifying both the start date and the end date.',
      'customStartDate'
    );
    return false;
  }

  // Case 2: Start Date is missing
  if (!sVal && eVal) {
    showReportDateErrorModal(
      'Start Date is Missing',
      `An End Date (${formatCustomDateString(eVal)}) was provided, but the Start Date is blank. You cannot proceed with ${actionVerb} the report.`,
      'A valid beginning start date is required to establish when the compiled report period starts. Without a start boundary, records cannot be chronologically scoped.',
      'customStartDate'
    );
    return false;
  }

  // Case 3: End Date is missing
  if (sVal && !eVal) {
    showReportDateErrorModal(
      'End Date is Missing',
      `A Start Date (${formatCustomDateString(sVal)}) was provided, but the End Date is blank. You cannot proceed with ${actionVerb} the report.`,
      'A valid concluding end date is required to establish when the compiled report period concludes. Without an end boundary, records cannot be chronologically scoped.',
      'customEndDate'
    );
    return false;
  }

  // Case 4: Start Date is after End Date
  if (sVal > eVal) {
    showReportDateErrorModal(
      'Start Date Cannot Be After End Date',
      `The selected Start Date (${formatCustomDateString(sVal)}) occurs after the End Date (${formatCustomDateString(eVal)}). You cannot proceed with ${actionVerb} the report.`,
      'The report timeframe must follow a forward chronological order. The system cannot compile records for a period where the starting point begins after the ending point. Please select a start date that is prior to or on the same date as the end date.',
      'customStartDate'
    );
    return false;
  }

  // Case 5: Invalid date format
  const sDate = new Date(sVal);
  const eDate = new Date(eVal);
  if (isNaN(sDate.getTime()) || isNaN(eDate.getTime())) {
    showReportDateErrorModal(
      'Invalid Date Format Provided',
      `One or both of the entered dates contain invalid or unparseable calendar values. You cannot proceed with ${actionVerb} the report.`,
      'Please use the calendar picker to specify valid calendar date values.',
      'customStartDate'
    );
    return false;
  }

  return true;
}

function showInclusionDateDetails(el) {
  if (!el) return;
  const clone = el.cloneNode(true);
  const tagEl = clone.querySelector('.timeline-label-tag');
  let tagText = 'Standard Span Criteria';
  if (tagEl) {
    tagText = tagEl.innerText.replace(/[()]/g, '').trim();
    tagEl.remove();
  }
  const dateRange = clone.innerText.trim();

  const setEl = (id, val) => {
    const e = document.getElementById(id);
    if (e) e.innerText = val;
  };

  setEl('inclusionModalDateRange', dateRange || '08/01/2026 - 08/31/2026');
  setEl('inclusionModalTag', tagText ? `Timeline Criteria: ${tagText}` : 'Criteria Timeline Window');

  openModal('reportInclusionDetailsModal');
}

function addReportToLivePreviewTable(reportData) {
  const tbody = document.getElementById('reportPreviewTableBody');
  if (!tbody) return;

  // Remove empty state row if present
  const emptyRow = tbody.querySelector('.empty-state-row');
  if (emptyRow) emptyRow.remove();

  const dateStamp = reportData.compilation || new Date().toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
  const rawDate = reportData.compilationRaw || new Date().toISOString().split('T')[0];
  const staff = reportData.exportedBy || window.CURRENT_STAFF_NAME || 'Admin Staff 1';
  const tag = reportData.timelineTag || 'Custom / Compiled';

  const tr = document.createElement('tr');
  tr.setAttribute('data-cat', reportData.category);
  tr.setAttribute('data-inclusion', reportData.inclusion);
  tr.setAttribute('data-compilation', rawDate);
  tr.setAttribute('data-days-ago', '0');
  tr.className = 'highlight-new-report-row';

  tr.innerHTML = `
    <td><strong class="account-tag">${escapeHtml(reportData.reportId)}</strong></td>
    <td class="preview-cat-cell">${escapeHtml(reportData.category)}</td>
    <td class="preview-desc-cell">${escapeHtml(reportData.description)}</td>
    <td>
      <span class="desc-date-badge interactive-date-badge" onclick="showInclusionDateDetails(this)" title="Click to view Timeline Criteria details">
        <i class="fa-regular fa-calendar"></i> ${escapeHtml(reportData.inclusion)}
        <span class="timeline-label-tag">(${escapeHtml(tag)})</span>
      </span>
    </td>
    <td>${escapeHtml(dateStamp)}</td>
    <td>${escapeHtml(staff)}</td>
  `;

  tbody.insertBefore(tr, tbody.firstChild);

  // Persist report in sessionStorage
  try {
    const existing = JSON.parse(sessionStorage.getItem('coop_generated_reports_history') || '[]');
    existing.unshift({
      reportId: reportData.reportId,
      category: reportData.category,
      description: reportData.description,
      inclusion: reportData.inclusion,
      timelineTag: tag,
      compilation: dateStamp,
      compilationRaw: rawDate,
      exportedBy: staff
    });
    // Cap to 30 recent entries
    if (existing.length > 30) existing.pop();
    sessionStorage.setItem('coop_generated_reports_history', JSON.stringify(existing));
  } catch (e) {}

  // Reveal & update tracker cards
  const trackerGrid = document.getElementById('reportTrackerCards');
  if (trackerGrid) {
    trackerGrid.style.display = 'grid';
  }
  const lastGenElem = document.getElementById('lastGeneratedTime');
  if (lastGenElem) {
    lastGenElem.innerText = new Date().toLocaleString();
  }

  // Update visible count
  if (typeof filterPreviewTable === 'function') {
    filterPreviewTable();
  }

  // Remove animation class after animation completes
  setTimeout(() => {
    tr.classList.remove('highlight-new-report-row');
  }, 3500);
}

function loadSavedGeneratedReports() {
  const tbody = document.getElementById('reportPreviewTableBody');
  if (!tbody) return;

  try {
    const saved = JSON.parse(sessionStorage.getItem('coop_generated_reports_history') || '[]');
    if (Array.isArray(saved) && saved.length > 0) {
      saved.slice().reverse().forEach(item => {
        // Avoid duplicate ID injection
        const existingTags = tbody.querySelectorAll('td strong.account-tag');
        for (let tag of existingTags) {
          if (tag.innerText.trim() === item.reportId) return;
        }

        const tr = document.createElement('tr');
        tr.setAttribute('data-cat', item.category);
        tr.setAttribute('data-inclusion', item.inclusion);
        tr.setAttribute('data-compilation', item.compilationRaw || item.compilation);
        tr.setAttribute('data-days-ago', '0');

        tr.innerHTML = `
          <td><strong class="account-tag">${escapeHtml(item.reportId)}</strong></td>
          <td class="preview-cat-cell">${escapeHtml(item.category)}</td>
          <td class="preview-desc-cell">${escapeHtml(item.description)}</td>
          <td>
            <span class="desc-date-badge interactive-date-badge" onclick="showInclusionDateDetails(this)" title="Click to view Timeline Criteria details">
              <i class="fa-regular fa-calendar"></i> ${escapeHtml(item.inclusion)}
              <span class="timeline-label-tag">(${escapeHtml(item.timelineTag || 'Compiled')})</span>
            </span>
          </td>
          <td>${escapeHtml(item.compilation)}</td>
          <td>${escapeHtml(item.exportedBy || 'Staff')}</td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);
      });
      if (typeof filterPreviewTable === 'function') {
        filterPreviewTable();
      }
    }
  } catch (e) {}
}

// ================= 13. GLOBAL LISTENERS & INITIALIZATION =================
document.addEventListener('DOMContentLoaded', () => {
  fetchMembersFromDatabase();
  loadSavedGeneratedReports();

  try {
    if (window.innerWidth > 991 && localStorage.getItem('admin_sidebar_collapsed') === '1') {
      document.documentElement.classList.add('sidebar-collapsed');
      document.body.classList.add('sidebar-collapsed');
      updateSidebarToggleVisuals(true);
    } else {
      updateSidebarToggleVisuals(false);
    }
  } catch (e) {}

  // Initialize 3D Cosmic Starfield Background
  if (document.getElementById('admin-space-canvas')) {
    window._adminStarfield = new Cosmic3DStarfield('admin-space-canvas');
  }
});

function handleLogout(e) {
  if (e && e.preventDefault) e.preventDefault();
  openModal('logoutModal');
}

// ================= 14. 3D COSMIC STARFIELD & PARALLAX ENGINE =================
if (typeof window.Cosmic3DStarfield === 'undefined') {
  window.Cosmic3DStarfield = class Cosmic3DStarfield {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx = this.canvas.getContext('2d');
    this.stars = [];
    this.numStars = 320;
    this.fov = 280;
    this.mouseX = 0;
    this.mouseY = 0;
    this.targetMouseX = 0;
    this.targetMouseY = 0;
    this.speed = 0.65;
    this.isRunning = true;
    this.theme = document.documentElement.getAttribute('data-theme') || 'dark';
    this.accent = document.documentElement.getAttribute('data-accent') || 'nebula';

    this.init();
  }

  init() {
    this.resize();
    window.addEventListener('resize', () => this.resize(), { passive: true });

    // Initialize 3D star coordinates
    for (let i = 0; i < this.numStars; i++) {
      this.stars.push(this.createStar(true));
    }

    // Interactive mouse movement
    window.addEventListener('mousemove', (e) => {
      this.targetMouseX = (e.clientX - window.innerWidth / 2) * 0.0006;
      this.targetMouseY = (e.clientY - window.innerHeight / 2) * 0.0006;
    }, { passive: true });

    // Theme observer for live accent / mode changes
    const observer = new MutationObserver(() => {
      this.theme = document.documentElement.getAttribute('data-theme') || 'dark';
      this.accent = document.documentElement.getAttribute('data-accent') || 'nebula';
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-accent'] });

    // Handle tab visibility
    document.addEventListener('visibilitychange', () => {
      this.isRunning = !document.hidden;
      if (this.isRunning) this.render();
    });

    this.render();
  }

  createStar(randomZ = false) {
    const spread = Math.max(window.innerWidth, window.innerHeight) * 1.3;
    return {
      x: (Math.random() - 0.5) * spread,
      y: (Math.random() - 0.5) * spread,
      z: randomZ ? Math.random() * 1000 + 1 : 1000,
      size: Math.random() * 1.8 + 0.8,
      twinkleSpeed: Math.random() * 0.03 + 0.01,
      twinklePhase: Math.random() * Math.PI * 2,
      baseAlpha: Math.random() * 0.4 + 0.6,
      hueOffset: (Math.random() - 0.5) * 35
    };
  }

  resize() {
    if (!this.canvas) return;
    this.width = window.innerWidth;
    this.height = window.innerHeight;
    this.canvas.width = this.width * (window.devicePixelRatio || 1);
    this.canvas.height = this.height * (window.devicePixelRatio || 1);
    this.ctx.setTransform(1, 0, 0, 1, 0, 0);
    this.ctx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);
    this.cx = this.width / 2;
    this.cy = this.height / 2;
  }

  getStarColor(alpha, hueOffset) {
    const isLight = this.theme === 'light';
    let baseHue = 195; // Nebula Cyan
    let saturation = isLight ? 95 : 100;
    let lightness = isLight ? 32 : 80;

    if (this.accent === 'solar') {
      baseHue = 36; // Solar Amber Gold
      lightness = isLight ? 35 : 75;
    } else if (this.accent === 'pulsar') {
      baseHue = 290; // Deep Pulsar Magenta
      lightness = isLight ? 38 : 78;
    } else if (this.accent === 'emerald') {
      baseHue = 162; // Andromeda Emerald
      lightness = isLight ? 28 : 75;
    }

    const finalHue = (baseHue + hueOffset + 360) % 360;
    const finalAlpha = isLight ? Math.min(1, alpha * 1.45) : Math.min(1, alpha * 1.25);
    return `hsla(${finalHue}, ${saturation}%, ${lightness}%, ${finalAlpha})`;
  }

  render() {
    if (!this.isRunning || !this.ctx) return;

    this.ctx.clearRect(0, 0, this.width, this.height);

    // Smooth mouse lerp
    this.mouseX += (this.targetMouseX - this.mouseX) * 0.05;
    this.mouseY += (this.targetMouseY - this.mouseY) * 0.05;

    for (let i = 0; i < this.stars.length; i++) {
      const star = this.stars[i];

      // Move star forward through 3D space
      star.z -= this.speed;

      // Apply subtle mouse parallax rotation drift
      star.x -= this.mouseX * star.z * 0.02;
      star.y -= this.mouseY * star.z * 0.02;

      // Recycle star when passing camera or going behind
      if (star.z <= 1) {
        this.stars[i] = this.createStar(false);
        continue;
      }

      // 3D Perspective Projection
      const k = this.fov / star.z;
      const screenX = this.cx + star.x * k;
      const screenY = this.cy + star.y * k;

      if (screenX < -25 || screenX > this.width + 25 || screenY < -25 || screenY > this.height + 25) {
        this.stars[i] = this.createStar(false);
        continue;
      }

      // Twinkle calculation
      star.twinklePhase += star.twinkleSpeed;
      const twinkle = (Math.sin(star.twinklePhase) + 1) * 0.5;
      const depthAlpha = Math.min(1, (1000 - star.z) / 600);
      const alpha = Math.max(0.35, star.baseAlpha * (0.7 + twinkle * 0.3) * depthAlpha);

      const renderSize = Math.max(1.0, (1 - star.z / 1000) * star.size * 2.8);

      // Draw star core
      this.ctx.beginPath();
      this.ctx.arc(screenX, screenY, renderSize, 0, Math.PI * 2);
      this.ctx.fillStyle = this.getStarColor(alpha, star.hueOffset);
      this.ctx.fill();

      // Draw subtle halo glow for closer/brighter stars
      if (renderSize > 1.8) {
        this.ctx.beginPath();
        this.ctx.arc(screenX, screenY, renderSize * 2.2, 0, Math.PI * 2);
        this.ctx.fillStyle = this.getStarColor(alpha * 0.3, star.hueOffset);
        this.ctx.fill();
      }
    }

    requestAnimationFrame(() => this.render());
  }
  };
}
