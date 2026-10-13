<?php
require 'config/init.php';

// Force login as Elizabeth
$stmt = $pdo->prepare('SELECT * FROM member WHERE account_number = ?');
$stmt->execute(['2026-0002']);
$user = $stmt->fetch();

$_SESSION['user_id'] = $user['member_id'];
$_SESSION['role'] = 'member';
$_SESSION['username'] = $user['account_number'];
$_SESSION['name'] = trim($user['firstname'] . ' ' . $user['lastname']);

// Function to capture output of a PHP file
function capture_output($file) {
    ob_start();
    require $file;
    return ob_get_clean();
}

$pages = [
    'profile' => 'member/profile.php',
    'update_info' => 'member/update_info.php',
    'surveys' => 'member/surveys.php',
    'change_password' => 'member/change_password.php',
    'upload_photo' => 'member/upload_photo.php'
];

@mkdir('captured_html');

foreach ($pages as $name => $path) {
    $html = capture_output($path);
    // Find everything between <div class="app-content"> and <footer
    $start = strpos($html, '<div class="app-content">');
    if ($start !== false) {
        $html = substr($html, $start + strlen('<div class="app-content">'));
    }
    
    $end = strpos($html, '<footer class="app-footer');
    if ($end !== false) {
        $html = substr($html, 0, $end);
    }
    
    file_put_contents("captured_html/{$name}.html", trim($html));
}
echo "Captured successfully.";
