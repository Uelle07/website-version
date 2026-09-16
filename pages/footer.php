<?php
/**
 * pages/footer.php
 * -------------------------------------------------------
 * Closes the layout opened by pages/header.php. Include at
 * the very bottom of every page:
 *
 *     require_once __DIR__ . '/../pages/footer.php';
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('footer.php cannot be accessed directly.');
}
?>
    </div><!-- /.container-fluid -->
</main>

<?php if (is_logged_in()): ?>
        </div><!-- /.app-content -->
    </div><!-- /.app-layout -->
<?php endif; ?>

<?php if (is_member() || is_logged_in()): ?>
<!-- 3D Glassmorphic Settings & Theme Modal -->
<div id="settingsModal" class="modal-overlay hidden" onclick="if(event.target === this) closeSettingsModal();" aria-hidden="true" role="dialog">
  <div class="modal-card settings-modal-card">
    <div class="modal-header d-flex justify-content-between align-items-center">
      <h3><i class="fa-solid fa-sliders text-accent-glow"></i> <span>Portal Settings &amp; Theme</span></h3>
      <button type="button" class="btn-close-modal" onclick="closeSettingsModal();" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    
    <div class="modal-body py-2">
      <!-- Section 1: Display Mode (Night vs Light) -->
      <div class="settings-section mb-4">
        <label class="settings-section-title d-flex align-items-center gap-2">
          <i class="fa-solid fa-circle-half-stroke text-cyan"></i>
          <span>Display Mode</span>
        </label>
        <p class="settings-section-desc">Choose between deep cosmic night or luminous celestial light.</p>
        
        <div class="theme-mode-grid">
          <!-- Night Mode Card -->
          <div class="theme-mode-card" data-mode="dark" onclick="selectThemeMode('dark')">
            <div class="mode-preview mode-dark-preview">
              <div class="preview-orbit"></div>
              <div class="preview-star"></div>
              <div class="preview-card-mini"></div>
            </div>
            <div class="mode-info">
              <div class="mode-name d-flex align-items-center gap-1">
                <i class="fa-solid fa-moon text-cyan"></i>
                <span>Night Mode</span>
              </div>
              <span class="mode-tagline">Deep Space Void</span>
            </div>
            <div class="mode-check-indicator"><i class="fa-solid fa-check"></i></div>
          </div>

          <!-- Light Mode Card -->
          <div class="theme-mode-card" data-mode="light" onclick="selectThemeMode('light')">
            <div class="mode-preview mode-light-preview">
              <div class="preview-orbit light-orbit"></div>
              <div class="preview-sun"></div>
              <div class="preview-card-mini light-mini"></div>
            </div>
            <div class="mode-info">
              <div class="mode-name d-flex align-items-center gap-1">
                <i class="fa-solid fa-sun text-warning"></i>
                <span>Light Mode</span>
              </div>
              <span class="mode-tagline">Stellar Aerospace</span>
            </div>
            <div class="mode-check-indicator"><i class="fa-solid fa-check"></i></div>
          </div>
        </div>
      </div>

      <!-- Section 2: Space Accent Color Scheme -->
      <div class="settings-section mb-4">
        <label class="settings-section-title d-flex align-items-center gap-2">
          <i class="fa-solid fa-palette text-purple"></i>
          <span>Space Accent Color Scheme</span>
        </label>
        <p class="settings-section-desc">Select complementary nebula and aura glowing accents.</p>
        
        <div class="accent-palette-grid">
          <!-- 1. Nebula Cyan (Default) -->
          <button type="button" class="accent-chip" data-accent="nebula" onclick="selectAccent('nebula')" title="Cosmic Nebula (Cyan & Purple)">
            <span class="accent-swatch swatch-nebula"></span>
            <span class="accent-label">Nebula Cyan</span>
          </button>
          
          <!-- 2. Solar Flare -->
          <button type="button" class="accent-chip" data-accent="solar" onclick="selectAccent('solar')" title="Solar Flare (Gold & Amber)">
            <span class="accent-swatch swatch-solar"></span>
            <span class="accent-label">Solar Flare</span>
          </button>

          <!-- 3. Deep Pulsar -->
          <button type="button" class="accent-chip" data-accent="pulsar" onclick="selectAccent('pulsar')" title="Deep Pulsar (Magenta & Violet)">
            <span class="accent-swatch swatch-pulsar"></span>
            <span class="accent-label">Deep Pulsar</span>
          </button>

          <!-- 4. Andromeda Emerald -->
          <button type="button" class="accent-chip" data-accent="emerald" onclick="selectAccent('emerald')" title="Andromeda Emerald (Teal & Mint)">
            <span class="accent-swatch swatch-emerald"></span>
            <span class="accent-label">Andromeda</span>
          </button>
        </div>
      </div>

      <!-- Section 3: Cursor Flashlight Glow Effect -->
      <div class="settings-section mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <label class="settings-section-title d-flex align-items-center gap-2 mb-1">
              <i class="fa-solid fa-wand-magic-sparkles text-cyan"></i>
              <span>Cursor Flashlight Glow Effect</span>
            </label>
            <p class="settings-section-desc mb-0">Dynamic radial ambient flashlight beam that follows mouse movement.</p>
          </div>
          <div class="form-check form-switch custom-switch-3d m-0">
            <input class="form-check-input" type="checkbox" id="toggleFlashlightSwitch" checked onchange="toggleCursorFlashlight(this.checked)">
          </div>
        </div>
      </div>
    </div>
    
    <div class="modal-actions d-flex justify-content-end align-items-center pt-3 mt-3">
      <button type="button" class="btn btn-primary px-4 py-2" onclick="saveThemeSettings();" style="border-radius: 12px; font-weight: 700;">
        <i class="fa-solid fa-floppy-disk me-1"></i> Save Settings
      </button>
    </div>
  </div>
</div>

<!-- Member Logout Confirmation Modal -->
<div id="memberLogoutModal" class="modal-overlay hidden" onclick="if(event.target === this) closeMemberLogoutModal();" aria-hidden="true" role="dialog">
  <div class="modal-card logout-modal-card" style="max-width: 460px;">
    <div class="modal-header d-flex justify-content-between align-items-center">
      <h3><i class="fa-solid fa-right-from-bracket text-danger"></i> <span>Logout Confirmation</span></h3>
      <button type="button" class="btn-close-modal" onclick="closeMemberLogoutModal();" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="modal-body py-3">
      <p class="logout-modal-text mb-2">
        Are you sure you want to end your space portal session?
      </p>
      <p class="logout-modal-subtext">
        You will need to sign in again to access your member records and survey inquiries.
      </p>
    </div>
    <div class="modal-actions d-flex justify-content-between align-items-center w-100">
      <button type="button" class="btn btn-secondary px-3" onclick="closeMemberLogoutModal();">Cancel</button>
      <a href="<?= e(BASE_URL) ?>/logout.php" class="btn btn-danger px-4 d-inline-flex align-items-center gap-2">
        <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
      </a>
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
      <button type="button" class="btn btn-secondary" id="systemConfirmCancelBtn" onclick="closeModal('systemConfirmModal')" style="border-radius: 12px; font-weight: 600;">Cancel</button>
      <button type="button" class="btn btn-primary" id="systemConfirmProceedBtn" style="border-radius: 12px; font-weight: 700;">Proceed</button>
    </div>
  </div>
</div>
<?php endif; ?>

<footer class="app-footer text-center py-3">
    <small>&copy; <?= date('Y') ?> Multipurpose Cooperative &mdash; Member Portal</small>
</footer>

<!-- Bootstrap 5 JS bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App scripts -->
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>

</body>
</html>
