<?php
/**
 * upload.php
 * -------------------------------------------------------
 * Handles saving an uploaded image (passport-size photo) to
 * /uploads and returns the filename to store in
 * member.passport_photo.
 *
 * Usage (inside a module's form handler):
 *     require_once __DIR__ . '/../config/upload.php';
 *     $result = handle_photo_upload($_FILES['passport_photo'] ?? null, $memberId);
 *     if ($result['success']) {
 *         $photoFilename = $result['filename']; // save this to the DB
 *     } else {
 *         $errors[] = $result['error'];
 *     }
 * -------------------------------------------------------
 */

/**
 * @param array|null $file  A single entry from $_FILES, e.g. $_FILES['passport_photo']
 * @param int|string $ownerId  member_id, used to build a unique filename
 * @return array{success: bool, filename?: string, error?: string}
 */
function handle_photo_upload(?array $file, $ownerId): array
{
    if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file was selected.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed (error code ' . $file['error'] . ').'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $maxMb = MAX_UPLOAD_SIZE / (1024 * 1024);
        return ['success' => false, 'error' => "File is too large. Maximum size is {$maxMb}MB."];
    }

    // Verify the real MIME type (don't trust the file extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES, true)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and WebP images are allowed.'];
    }

    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }

    $extension = 'jpg';
    if ($mimeType === 'image/png' || $mimeType === 'image/x-png') {
        $extension = 'png';
    } elseif ($mimeType === 'image/webp') {
        $extension = 'webp';
    }

    $filename  = 'photo_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$ownerId) . '_' . time() . '.' . $extension;
    $destination = UPLOAD_PATH . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Could not save the uploaded file. Please check folder permissions and try again.'];
    }

    return ['success' => true, 'filename' => $filename];
}

/** Deletes a previously uploaded photo (e.g. when replacing it). */
function delete_uploaded_photo(?string $filename): void
{
    if (!empty($filename)) {
        $path = UPLOAD_PATH . '/' . basename($filename); // basename() blocks path traversal
        if (is_file($path)) {
            unlink($path);
        }
    }
}

/** Builds the browser-facing URL for a stored photo, with a placeholder fallback. */
function photo_url(?string $filename): string
{
    if (empty($filename) || !is_file(UPLOAD_PATH . '/' . $filename)) {
        return BASE_URL . '/assets/images/default-avatar.svg';
    }
    return UPLOAD_URL . '/' . $filename;
}
