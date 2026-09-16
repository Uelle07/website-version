/**
 * script.js
 * -------------------------------------------------------
 * Space/Aerospace Design System Client Logic
 * Handles 3D Card Physics, Specular Glare Spotlights,
 * Theme Management (Night/Light Mode), Space Accent Schemes,
 * Interactive Modals, and Parallax Effects.
 * -------------------------------------------------------
 */

// Global State
window.portalThemeState = {
    theme: localStorage.getItem('portal_theme') || 'dark',
    accent: localStorage.getItem('portal_accent') || 'nebula',
    tiltEnabled: localStorage.getItem('portal_tilt') !== '0',
    cursorGlowEnabled: localStorage.getItem('portal_cursor_glow') !== '0'
};

// Draft State for Settings Modal
let _settingsDraft = null;

document.addEventListener('DOMContentLoaded', function () {

    // 1. Initialize Theme & Accents
    initPortalTheme();

    // 2. Initialize 3D Cosmic Starfield Background if Canvas exists
    if (document.getElementById('admin-space-canvas') && !window._adminStarfield) {
        window._adminStarfield = new Cosmic3DStarfield('admin-space-canvas');
    }

    // 3. Auto-dismiss flash alerts & portal toasts after 5 seconds
    initToastDismissers();

    // 4. Bootstrap validation styling
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 5. Live preview for passport-photo file inputs
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const target = document.querySelector(input.dataset.preview);
            if (!target || !input.files || !input.files[0]) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                target.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        });
    });

    // 6. Confirm before destructive actions
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (event) {
            event.preventDefault();
            const message = el.dataset.confirm || 'Are you sure you want to perform this action?';
            showSystemConfirm({
                title: 'Confirmation',
                message: message,
                confirmText: 'Confirm',
                isDanger: true,
                onConfirm: () => {
                    if (el.tagName === 'A') {
                        window.location.href = el.href;
                    } else if (el.tagName === 'BUTTON' && el.type === 'submit' && el.form) {
                        el.form.submit();
                    }
                }
            });
        });
    });

    // 7. Initialize 3D Card Physics with Specular Highlights
    init3DCardPhysics();

    // 8. Global Subtle Cursor Flashlight Glow Effect
    initCursorGlow();
});

/* ---------------------------------------------------------
   THEME & SETTINGS CONTROLLER (LIVE PREVIEW + SAVE / CANCEL)
   --------------------------------------------------------- */
function initPortalTheme() {
    const theme = window.portalThemeState.theme;
    const accent = window.portalThemeState.accent;

    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-accent', accent);

    updateSettingsModalUI(theme, accent, window.portalThemeState.tiltEnabled, window.portalThemeState.cursorGlowEnabled);
}

function selectThemeMode(mode) {
    if (_settingsDraft) {
        _settingsDraft.theme = mode;
    } else {
        window.portalThemeState.theme = mode;
    }
    
    // Live preview
    document.documentElement.setAttribute('data-theme', mode);
    if (window._adminStarfield) {
        window._adminStarfield.theme = mode;
    }

    const currentAccent = _settingsDraft ? _settingsDraft.accent : window.portalThemeState.accent;
    const currentTilt = _settingsDraft ? _settingsDraft.tiltEnabled : window.portalThemeState.tiltEnabled;
    const currentGlow = _settingsDraft ? _settingsDraft.cursorGlowEnabled : window.portalThemeState.cursorGlowEnabled;

    updateSettingsModalUI(mode, currentAccent, currentTilt, currentGlow);
}

function selectAccent(accent) {
    if (_settingsDraft) {
        _settingsDraft.accent = accent;
    } else {
        window.portalThemeState.accent = accent;
    }

    // Live preview
    document.documentElement.setAttribute('data-accent', accent);
    if (window._adminStarfield) {
        window._adminStarfield.accent = accent;
    }

    const currentTheme = _settingsDraft ? _settingsDraft.theme : window.portalThemeState.theme;
    const currentTilt = _settingsDraft ? _settingsDraft.tiltEnabled : window.portalThemeState.tiltEnabled;
    const currentGlow = _settingsDraft ? _settingsDraft.cursorGlowEnabled : window.portalThemeState.cursorGlowEnabled;

    updateSettingsModalUI(currentTheme, accent, currentTilt, currentGlow);
}

function toggleCursorFlashlight(enabled) {
    if (_settingsDraft) {
        _settingsDraft.cursorGlowEnabled = enabled;
    } else {
        window.portalThemeState.cursorGlowEnabled = enabled;
    }

    // Live preview
    const glow = document.getElementById('global-cursor-glow');
    if (glow) {
        glow.style.display = enabled ? 'block' : 'none';
        glow.style.opacity = enabled ? '0.22' : '0';
    }
}

function toggleTiltPhysics(enabled) {
    if (_settingsDraft) {
        _settingsDraft.tiltEnabled = enabled;
    } else {
        window.portalThemeState.tiltEnabled = enabled;
    }

    if (!enabled) {
        // Reset all card transforms
        document.querySelectorAll('.tile-card, .hero-card').forEach(card => {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
            card.style.setProperty('--glare-opacity', '0');
        });
    }
}

function updateSettingsModalUI(currentTheme, currentAccent, tiltEnabled, cursorGlowEnabled) {
    // Update theme mode cards
    document.querySelectorAll('.theme-mode-card').forEach(card => {
        if (card.dataset.mode === currentTheme) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });

    // Update accent chips
    document.querySelectorAll('.accent-chip').forEach(chip => {
        if (chip.dataset.accent === currentAccent) {
            chip.classList.add('active');
        } else {
            chip.classList.remove('active');
        }
    });

    // Update Flashlight toggle switch
    const flashlightSwitch = document.getElementById('toggleFlashlightSwitch');
    if (flashlightSwitch) {
        flashlightSwitch.checked = cursorGlowEnabled !== undefined ? !!cursorGlowEnabled : (window.portalThemeState.cursorGlowEnabled !== false);
    }

    // Update 3D Tilt switch
    const tiltSwitch = document.getElementById('toggleTiltSwitch');
    if (tiltSwitch) {
        tiltSwitch.checked = tiltEnabled !== undefined ? !!tiltEnabled : (window.portalThemeState.tiltEnabled !== false);
    }
}

function openSettingsModal() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
        // Record draft state snapshot
        _settingsDraft = {
            theme: window.portalThemeState.theme,
            accent: window.portalThemeState.accent,
            tiltEnabled: window.portalThemeState.tiltEnabled,
            cursorGlowEnabled: window.portalThemeState.cursorGlowEnabled
        };

        updateSettingsModalUI(
            _settingsDraft.theme,
            _settingsDraft.accent,
            _settingsDraft.tiltEnabled,
            _settingsDraft.cursorGlowEnabled
        );
        modal.classList.remove('hidden');
    }
}

function closeSettingsModal() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
        // Rollback any unsaved draft preview to saved global state
        document.documentElement.setAttribute('data-theme', window.portalThemeState.theme);
        document.documentElement.setAttribute('data-accent', window.portalThemeState.accent);

        if (window._adminStarfield) {
            window._adminStarfield.theme = window.portalThemeState.theme;
            window._adminStarfield.accent = window.portalThemeState.accent;
        }

        const glow = document.getElementById('global-cursor-glow');
        if (glow) {
            const isEnabled = window.portalThemeState.cursorGlowEnabled !== false;
            glow.style.display = isEnabled ? 'block' : 'none';
            glow.style.opacity = isEnabled ? '0.22' : '0';
        }

        updateSettingsModalUI(
            window.portalThemeState.theme,
            window.portalThemeState.accent,
            window.portalThemeState.tiltEnabled,
            window.portalThemeState.cursorGlowEnabled
        );

        _settingsDraft = null;
        modal.classList.add('hidden');
    }
}

function saveThemeSettings() {
    if (_settingsDraft) {
        window.portalThemeState.theme = _settingsDraft.theme;
        window.portalThemeState.accent = _settingsDraft.accent;
        window.portalThemeState.tiltEnabled = _settingsDraft.tiltEnabled;
        window.portalThemeState.cursorGlowEnabled = _settingsDraft.cursorGlowEnabled;
    }

    localStorage.setItem('portal_theme', window.portalThemeState.theme);
    localStorage.setItem('portal_accent', window.portalThemeState.accent);
    localStorage.setItem('portal_tilt', window.portalThemeState.tiltEnabled ? '1' : '0');
    localStorage.setItem('portal_cursor_glow', window.portalThemeState.cursorGlowEnabled ? '1' : '0');

    document.documentElement.setAttribute('data-theme', window.portalThemeState.theme);
    document.documentElement.setAttribute('data-accent', window.portalThemeState.accent);

    if (window._adminStarfield) {
        window._adminStarfield.theme = window.portalThemeState.theme;
        window._adminStarfield.accent = window.portalThemeState.accent;
    }

    const glow = document.getElementById('global-cursor-glow');
    if (glow) {
        const isEnabled = window.portalThemeState.cursorGlowEnabled !== false;
        glow.style.display = isEnabled ? 'block' : 'none';
        glow.style.opacity = isEnabled ? '0.22' : '0';
    }

    _settingsDraft = null;

    const modal = document.getElementById('settingsModal');
    if (modal) {
        modal.classList.add('hidden');
    }

    const themeName = window.portalThemeState.theme === 'dark' ? 'Night Mode' : 'Light Mode';
    const accentNames = {
        'nebula': 'Nebula Cyan',
        'solar': 'Solar Flare',
        'pulsar': 'Deep Pulsar',
        'emerald': 'Andromeda Emerald'
    };
    const currentAccent = window.portalThemeState.accent;
    const accentName = accentNames[currentAccent] || (currentAccent ? (currentAccent.charAt(0).toUpperCase() + currentAccent.slice(1)) : 'Nebula Cyan');

    const specificMsg = `Portal display set to ${themeName} with ${accentName} accent styling and visual preferences applied.`;

    if (typeof showToast === 'function') {
        showToast('success', 'Portal Settings & Theme Saved', specificMsg);
    }
}

// Window global bindings
window.selectThemeMode = selectThemeMode;
window.selectAccent = selectAccent;
window.toggleCursorFlashlight = toggleCursorFlashlight;
window.toggleTiltPhysics = toggleTiltPhysics;
window.openSettingsModal = openSettingsModal;
window.closeSettingsModal = closeSettingsModal;
window.saveThemeSettings = saveThemeSettings;
window.toggleTheme = openSettingsModal;

/* ---------------------------------------------------------
   INTERACTIVE 3D CARD PHYSICS & SPECULAR SPOTLIGHT GLARE
   --------------------------------------------------------- */
function init3DCardPhysics() {
    const cards = document.querySelectorAll('.tile-card');

    cards.forEach(card => {
        // Ensure card-glare container exists
        if (!card.querySelector('.card-glare')) {
            const glareEl = document.createElement('div');
            glareEl.className = 'card-glare';
            card.appendChild(glareEl);
        }

        card.addEventListener('mousemove', function (e) {
            if (!window.portalThemeState.tiltEnabled) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            // Maximum tilt angle (in degrees)
            const maxTilt = 9;
            const rotateX = ((y - centerY) / centerY) * -maxTilt;
            const rotateY = ((x - centerX) / centerX) * maxTilt;

            // Calculate specular spotlight position in percentage
            const glareX = (x / rect.width) * 100;
            const glareY = (y / rect.height) * 100;

            card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateZ(8px)`;
            card.style.setProperty('--glare-x', `${glareX.toFixed(1)}%`);
            card.style.setProperty('--glare-y', `${glareY.toFixed(1)}%`);
            card.style.setProperty('--glare-opacity', '1');
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0px)';
            card.style.setProperty('--glare-opacity', '0');
        });
    });
}

/* ---------------------------------------------------------
   PARALLAX & CURSOR AMBIENT EFFECTS
   --------------------------------------------------------- */
function initSpaceParallax() {
    // Parallax is integrated directly within the 3D Cosmic Starfield canvas
}

function initCursorGlow() {
    const isEnabled = window.portalThemeState.cursorGlowEnabled !== false;
    let glow = document.getElementById('global-cursor-glow');
    if (!glow) {
        glow = document.createElement('div');
        glow.id = 'global-cursor-glow';
        glow.style.position = 'fixed';
        glow.style.width = '350px';
        glow.style.height = '350px';
        glow.style.borderRadius = '50%';
        glow.style.pointerEvents = 'none';
        glow.style.transform = 'translate(-50%, -50%)';
        glow.style.zIndex = '1';
        glow.style.opacity = isEnabled ? '0.22' : '0';
        glow.style.display = isEnabled ? 'block' : 'none';
        glow.style.background = 'radial-gradient(circle, var(--accent-glow) 0%, rgba(0, 242, 254, 0.06) 45%, transparent 70%)';
        glow.style.transition = 'opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        document.body.appendChild(glow);
    } else {
        glow.style.display = isEnabled ? 'block' : 'none';
        glow.style.opacity = isEnabled ? '0.22' : '0';
    }

    document.addEventListener('mousemove', e => {
        glow.style.left = e.clientX + 'px';
        glow.style.top = e.clientY + 'px';
    });
}

/* Modal Helper Functions (Global Availability) */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('hidden');
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('hidden');
    }
}

/* Staff / Admin Logout Modal Handler */
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

/* Member Logout Modal Handlers */
function openMemberLogoutModal() {
    const modal = document.getElementById('memberLogoutModal') || document.getElementById('logoutModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeMemberLogoutModal() {
    const modal = document.getElementById('memberLogoutModal') || document.getElementById('logoutModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/* Reusable Glassmorphic System Confirmation Dialog */
function showSystemConfirm(options = {}) {
    const title = options.title || 'Confirmation';
    const message = options.message || 'Are you sure you want to proceed?';
    const confirmText = options.confirmText || 'Proceed';
    const cancelText = options.cancelText || 'Cancel';
    const isDanger = !!options.isDanger;
    const icon = options.icon || (isDanger ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-question');
    const iconColor = options.iconColor || (isDanger ? '#ef4444' : 'var(--primary-teal)');
    const onConfirm = options.onConfirm || null;

    let modal = document.getElementById('systemConfirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'systemConfirmModal';
        modal.className = 'modal-overlay hidden';
        modal.style.zIndex = '100001 !important';
        modal.innerHTML = `
            <div class="modal-card" style="max-width: 480px;">
                <div class="modal-header">
                    <h3 id="systemConfirmTitle"></h3>
                </div>
                <div class="modal-body" style="padding-top: 0.5rem;">
                    <p id="systemConfirmMessage" style="color: var(--text-main); font-size: 0.95rem; margin-bottom: 0.5rem; line-height: 1.5;"></p>
                </div>
                <div class="modal-actions" style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn btn-secondary" id="systemConfirmCancelBtn" style="border-radius: 12px; font-weight: 600;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="systemConfirmProceedBtn" style="border-radius: 12px; font-weight: 700;">Proceed</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const titleEl = document.getElementById('systemConfirmTitle');
    const msgEl = document.getElementById('systemConfirmMessage');
    const proceedBtn = document.getElementById('systemConfirmProceedBtn');
    const cancelBtn = document.getElementById('systemConfirmCancelBtn');

    if (titleEl) {
        titleEl.innerHTML = `<i class="${icon}" style="color: ${iconColor}; margin-right: 6px;"></i> ${title}`;
    }
    if (msgEl) {
        msgEl.innerHTML = message;
    }
    if (cancelBtn) {
        cancelBtn.textContent = cancelText;
        cancelBtn.onclick = () => {
            modal.classList.add('hidden');
        };
    }
    if (proceedBtn) {
        proceedBtn.textContent = confirmText;
        proceedBtn.className = isDanger ? 'btn btn-danger' : 'btn btn-primary';
        proceedBtn.onclick = () => {
            modal.classList.add('hidden');
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        };
    }

    modal.classList.remove('hidden');
}

// Global Escape key listener for modal dismissal
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeMemberLogoutModal();
        closeAdminLogoutModal();
        if (typeof closeSettingsModal === 'function') closeSettingsModal();
        const sysModal = document.getElementById('systemConfirmModal');
        if (sysModal) sysModal.classList.add('hidden');
    }
});

// Override window.alert to ensure no "localhost says" browser popups appear
window.alert = function(msg) {
    if (typeof showToast === 'function') {
        showToast(String(msg), 'warning', 'System Notice');
    } else {
        console.warn('System Notice:', msg);
    }
};

window.openModal = openModal;
window.closeModal = closeModal;
window.openAdminLogoutModal = openAdminLogoutModal;
window.closeAdminLogoutModal = closeAdminLogoutModal;
window.openMemberLogoutModal = openMemberLogoutModal;
window.closeMemberLogoutModal = closeMemberLogoutModal;
window.showSystemConfirm = showSystemConfirm;

/* ---------------------------------------------------------
   TOAST NOTIFICATION ENGINE (FLOATING GLASSMORPHIC TOASTS)
   --------------------------------------------------------- */
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

    let container = document.querySelector('.portal-toast-container') || document.querySelector('.portal-flash-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'portal-toast-container';
        document.body.appendChild(container);
    }

    const typeClass = type;

    const iconHtml = type === 'success' ? '<i class="fa-solid fa-circle-check"></i>' :
                     type === 'error' ? '<i class="fa-solid fa-circle-exclamation"></i>' :
                     type === 'warning' ? '<i class="fa-solid fa-triangle-exclamation"></i>' :
                     '<i class="fa-solid fa-circle-info"></i>';

    const toast = document.createElement('div');
    toast.className = `portal-toast portal-toast-${typeClass} fade show`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="toast-icon-wrapper">${iconHtml}</div>
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
        progress.style.transition = `transform ${duration}ms linear`;
        requestAnimationFrame(() => {
            progress.style.transform = 'scaleX(0)';
        });
    }

    const autoDismissTimer = setTimeout(() => {
        dismissToast(toast);
    }, duration);

    toast._timer = autoDismissTimer;
    return toast;
}

function dismissToast(toastElem) {
    if (!toastElem) return;
    clearTimeout(toastElem._timer);
    toastElem.classList.add('toast-hiding');
    setTimeout(() => {
        toastElem.remove();
    }, 320);
}

function initToastDismissers() {
    document.querySelectorAll('.portal-toast').forEach(toast => {
        const progress = toast.querySelector('.toast-progress');
        if (progress) {
            progress.style.transition = 'transform 5000ms linear';
            requestAnimationFrame(() => {
                progress.style.transform = 'scaleX(0)';
            });
        }
        toast._timer = setTimeout(() => {
            dismissToast(toast);
        }, 5000);
    });

    document.querySelectorAll('.alert:not(.portal-toast)').forEach(function (alertEl) {
        setTimeout(function () {
            try {
                const alert = bootstrap.Alert.getOrCreateInstance(alertEl);
                if (alert) alert.close();
            } catch (e) {
                alertEl.remove();
            }
        }, 5000);
    });
}

window.showToast = showToast;
window.dismissToast = dismissToast;

/* ---------------------------------------------------------
   3D COSMIC STARFIELD & PARALLAX ENGINE
   --------------------------------------------------------- */
class Cosmic3DStarfield {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.stars = [];
        this.numStars = 380;
        this.fov = 280;
        this.mouseX = 0;
        this.mouseY = 0;
        this.targetMouseX = 0;
        this.targetMouseY = 0;
        this.speed = 0.70;
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

        // Smooth interactive mouse parallax
        window.addEventListener('mousemove', (e) => {
            this.targetMouseX = (e.clientX - window.innerWidth / 2) * 0.00075;
            this.targetMouseY = (e.clientY - window.innerHeight / 2) * 0.00075;
        }, { passive: true });

        // Live theme & accent observer
        const observer = new MutationObserver(() => {
            this.theme = document.documentElement.getAttribute('data-theme') || 'dark';
            this.accent = document.documentElement.getAttribute('data-accent') || 'nebula';
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-accent'] });

        // Tab visibility handler
        document.addEventListener('visibilitychange', () => {
            this.isRunning = !document.hidden;
            if (this.isRunning) this.render();
        });

        this.render();
    }

    createStar(randomZ = false) {
        const spread = Math.max(window.innerWidth, window.innerHeight) * 1.35;
        const isProminent = Math.random() < 0.15;
        return {
            x: (Math.random() - 0.5) * spread,
            y: (Math.random() - 0.5) * spread,
            z: randomZ ? Math.random() * 1000 + 1 : 1000,
            size: isProminent ? Math.random() * 2.2 + 1.4 : Math.random() * 1.4 + 0.6,
            twinkleSpeed: Math.random() * 0.035 + 0.012,
            twinklePhase: Math.random() * Math.PI * 2,
            baseAlpha: Math.random() * 0.45 + 0.55,
            hueOffset: (Math.random() - 0.5) * 40,
            isProminent: isProminent
        };
    }

    resize() {
        if (!this.canvas) return;
        this.width = window.innerWidth;
        this.height = window.innerHeight;
        const dpr = window.devicePixelRatio || 1;
        this.canvas.width = this.width * dpr;
        this.canvas.height = this.height * dpr;
        this.ctx.setTransform(1, 0, 0, 1, 0, 0);
        this.ctx.scale(dpr, dpr);
        this.cx = this.width / 2;
        this.cy = this.height / 2;
    }

    getStarColor(alpha, hueOffset, isProminent) {
        const isLight = this.theme === 'light';
        let baseHue = 195; // Nebula Cyan (Default)
        let saturation = isLight ? 95 : 100;
        let lightness = isLight ? 32 : (isProminent ? 88 : 80);

        if (this.accent === 'solar') {
            baseHue = 38; // Solar Gold & Amber
            lightness = isLight ? 35 : (isProminent ? 85 : 75);
        } else if (this.accent === 'pulsar') {
            baseHue = 292; // Deep Pulsar Magenta & Purple
            lightness = isLight ? 38 : (isProminent ? 88 : 78);
        } else if (this.accent === 'emerald') {
            baseHue = 162; // Andromeda Emerald & Mint
            lightness = isLight ? 28 : (isProminent ? 85 : 75);
        }

        const finalHue = (baseHue + hueOffset + 360) % 360;
        const finalAlpha = isLight ? Math.min(1, alpha * 1.4) : Math.min(1, alpha * 1.25);
        return `hsla(${finalHue}, ${saturation}%, ${lightness}%, ${finalAlpha})`;
    }

    render() {
        if (!this.isRunning || !this.ctx) return;

        this.ctx.clearRect(0, 0, this.width, this.height);

        // Smooth mouse parallax lerp
        this.mouseX += (this.targetMouseX - this.mouseX) * 0.06;
        this.mouseY += (this.targetMouseY - this.mouseY) * 0.06;

        for (let i = 0; i < this.stars.length; i++) {
            const star = this.stars[i];
            star.z -= this.speed;

            // Apply 3D perspective mouse drift
            star.x -= this.mouseX * star.z * 0.022;
            star.y -= this.mouseY * star.z * 0.022;

            // Recycle stars that reach behind the camera
            if (star.z <= 1) {
                this.stars[i] = this.createStar(false);
                continue;
            }

            // 3D Perspective Projection
            const k = this.fov / star.z;
            const screenX = this.cx + star.x * k;
            const screenY = this.cy + star.y * k;

            if (screenX < -30 || screenX > this.width + 30 || screenY < -30 || screenY > this.height + 30) {
                this.stars[i] = this.createStar(false);
                continue;
            }

            // Twinkle calculation
            star.twinklePhase += star.twinkleSpeed;
            const twinkle = (Math.sin(star.twinklePhase) + 1) * 0.5;
            const depthAlpha = Math.min(1, (1000 - star.z) / 550);
            const alpha = Math.max(0.32, star.baseAlpha * (0.65 + twinkle * 0.35) * depthAlpha);

            const renderSize = Math.max(0.9, (1 - star.z / 1000) * star.size * 2.6);

            // Draw star core
            this.ctx.beginPath();
            this.ctx.arc(screenX, screenY, renderSize, 0, Math.PI * 2);
            this.ctx.fillStyle = this.getStarColor(alpha, star.hueOffset, star.isProminent);
            this.ctx.fill();

            // Draw subtle halo glow for closer/brighter stars
            if (renderSize > 1.6 || star.isProminent) {
                this.ctx.beginPath();
                this.ctx.arc(screenX, screenY, renderSize * 2.4, 0, Math.PI * 2);
                this.ctx.fillStyle = this.getStarColor(alpha * 0.35, star.hueOffset, star.isProminent);
                this.ctx.fill();
            }
        }

        requestAnimationFrame(() => this.render());
    }
}
window.Cosmic3DStarfield = Cosmic3DStarfield;
