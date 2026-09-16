<?php
/**
 * staff/update_admin_photo.php
 * -------------------------------------------------------
 * Handles adding/updating/removing the staff/admin profile picture.
 * -------------------------------------------------------
 */

require_once __DIR__ . "/../config/init.php";
require_once __DIR__ . "/../config/upload.php";
require_staff();

$isAjax = (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") 
          || (isset($_SERVER["HTTP_ACCEPT"]) && strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== false);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if ($isAjax) {
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "error" => "Invalid request method."]);
        exit;
    }
    redirect("staff/dashboard.php");
}

$csrfToken = $_POST["csrf_token"] ?? "";
if (!verify_csrf_token($csrfToken)) {
    if ($isAjax) {
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "error" => "Invalid or expired security token."]);
        exit;
    }
    set_flash("error", "Invalid or expired security token.");
    redirect("staff/dashboard.php");
}

$staffId = current_user_id();
$action  = $_POST["action"] ?? "upload";

// Fetch current photo
$currStmt = $pdo->prepare("SELECT profile_photo FROM staff WHERE staff_id = ?");
$currStmt->execute([$staffId]);
$currentPhoto = $currStmt->fetchColumn();

if ($action === "remove") {
    $stmt = $pdo->prepare("UPDATE staff SET profile_photo = NULL WHERE staff_id = ?");
    $stmt->execute([$staffId]);
    $_SESSION["profile_photo"] = null;

    if (!empty($currentPhoto)) {
        delete_uploaded_photo($currentPhoto);
    }

    if ($isAjax) {
        header("Content-Type: application/json");
        echo json_encode(["success" => true, "message" => "Profile photo removed successfully.", "photo_url" => BASE_URL . '/assets/images/default-avatar.svg']);
        exit;
    }
    set_flash("success", "Profile picture removed successfully.");
    $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/staff/dashboard.php');
    redirect($referer);
}

if (empty($_FILES["admin_profile_photo"]) || $_FILES["admin_profile_photo"]["error"] === UPLOAD_ERR_NO_FILE) {
    if ($isAjax) {
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "error" => "Please select a photo to upload."]);
        exit;
    }
    set_flash("error", "Please select a photo to upload.");
    $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/staff/dashboard.php');
    redirect($referer);
}

$uploadResult = handle_photo_upload($_FILES["admin_profile_photo"], "staff_" . $staffId);

if (!$uploadResult["success"]) {
    if ($isAjax) {
        header("Content-Type: application/json");
        echo json_encode(["success" => false, "error" => $uploadResult["error"]]);
        exit;
    }
    set_flash("error", $uploadResult["error"]);
    $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/staff/dashboard.php');
    redirect($referer);
}

$photoFilename = $uploadResult["filename"];
$stmt = $pdo->prepare("UPDATE staff SET profile_photo = ? WHERE staff_id = ?");
$stmt->execute([$photoFilename, $staffId]);

if (!empty($currentPhoto) && $currentPhoto !== $photoFilename) {
    delete_uploaded_photo($currentPhoto);
}

$_SESSION["profile_photo"] = $photoFilename;

if ($isAjax) {
    header("Content-Type: application/json");
    echo json_encode([
        "success"   => true,
        "message"   => "Profile photo updated successfully.",
        "filename"  => $photoFilename,
        "photo_url" => photo_url($photoFilename)
    ]);
    exit;
}

set_flash("success", "Profile picture updated successfully.");
$referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/staff/dashboard.php');
redirect($referer);

