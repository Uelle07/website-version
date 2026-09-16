<?php
/**
 * pages/footer_staff.php
 * -------------------------------------------------------
 * Shared Footer layout for Staff Portal pages.
 * -------------------------------------------------------
 */

if (!defined('APP_LOADED')) {
    die('footer_staff.php cannot be accessed directly.');
}
?>
    </div><!-- /.dashboard-container -->
  </main><!-- /.main-content -->

  <!-- Shared Admin UI Modals -->
  <?php require_once __DIR__ . '/admin_modals.php'; ?>

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

  <!-- Bootstrap 5 JS bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- App & Theme scripts -->
  <script src="<?= e(BASE_URL) ?>/assets/js/script.js?v=<?= file_exists(__DIR__ . '/../assets/js/script.js') ? filemtime(__DIR__ . '/../assets/js/script.js') : time() ?>"></script>
  <!-- Admin UI External JavaScript -->
  <script src="<?= e(BASE_URL) ?>/assets/js/admin.js?v=<?= file_exists(__DIR__ . '/../assets/js/admin.js') ? filemtime(__DIR__ . '/../assets/js/admin.js') : time() ?>"></script>
</body>
</html>
