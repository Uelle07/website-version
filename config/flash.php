<?php
/**
 * flash.php
 * -------------------------------------------------------
 * "Flash" messages: a message set before a redirect() that
 * displays once on the next page load, then disappears.
 *
 * Usage:
 *     set_flash('success', 'Profile updated successfully.');
 *     redirect('profile.php');
 *
 * Then in pages/header.php, render_flash_messages() prints it.
 *
 * Allowed types: 'success', 'error', 'warning', 'info'
 * (these map directly to Bootstrap alert classes).
 * -------------------------------------------------------
 */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Returns all queued flash messages and clears them
 * (call-once behaviour).
 */
function get_flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Convenience: echoes ready-to-use modern glassmorphic toast alerts.
 * Called from pages/header.php & header_staff.php on every page.
 */
function render_flash_messages(): void
{
    $messages = get_flash_messages();
    if (empty($messages)) {
        return;
    }

    echo '<div class="portal-flash-container" aria-live="polite" aria-atomic="true">';
    foreach ($messages as $flash) {
        $type = htmlspecialchars($flash['type']);
        $msg  = htmlspecialchars($flash['message']);

        $title = match($type) {
            'success' => 'Success',
            'error', 'danger' => 'Attention Required',
            'warning' => 'Notice',
            'info'    => 'Information',
            default   => 'Notification',
        };

        $icon = match($type) {
            'success' => '<i class="fa-solid fa-circle-check"></i>',
            'error', 'danger' => '<i class="fa-solid fa-circle-exclamation"></i>',
            'warning' => '<i class="fa-solid fa-triangle-exclamation"></i>',
            'info'    => '<i class="fa-solid fa-circle-info"></i>',
            default   => '<i class="fa-solid fa-bell"></i>',
        };

        $typeClass = ($type === 'danger') ? 'error' : $type;

        echo "<div class=\"portal-toast portal-toast-{$typeClass} alert-dismissible fade show\" role=\"alert\">
                <div class=\"toast-icon-wrapper\">
                    {$icon}
                </div>
                <div class=\"toast-content\">
                    <h5 class=\"toast-title\">{$title}</h5>
                    <p class=\"toast-message\">{$msg}</p>
                </div>
                <button type=\"button\" class=\"toast-close-btn\" data-bs-dismiss=\"alert\" aria-label=\"Close\" onclick=\"this.closest('.portal-toast').remove()\">
                    <i class=\"fa-solid fa-xmark\"></i>
                </button>
                <div class=\"toast-progress\"></div>
              </div>";
    }
    echo '</div>';
}


