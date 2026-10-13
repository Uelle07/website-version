<?php
require 'config/init.php';

// Force login as Elizabeth
$stmt = $pdo->prepare('SELECT * FROM member WHERE account_number = ?');
$stmt->execute(['2026-0002']);
$user = $stmt->fetch();

$_SESSION['user_type'] = 'member';
$_SESSION['user_id'] = $user['member_id'];
$_SESSION['username'] = $user['account_number'];
$_SESSION['name'] = trim($user['firstname'] . ' ' . $user['lastname']);

$page = $argv[1];
$name = basename($page);

ob_start();
require $page;
$html = ob_get_clean();

@mkdir('captured_html');

// Slicing
$start = strpos($html, '<div class="container-fluid py-4">');
if ($start !== false) {
    $html = substr($html, $start + strlen('<div class="container-fluid py-4">'));
    $end = strrpos($html, '</main>');
    if ($end !== false) {
        $html = substr($html, 0, $end);
        $lastDiv = strrpos($html, '</div>');
        if ($lastDiv !== false) {
            $html = substr($html, 0, $lastDiv);
        }
    }
} else {
    $start = strpos($html, '<div class="dashboard-wrapper">');
    if ($start !== false) {
        $html = substr($html, $start);
        $end = strrpos($html, '<footer class="app-footer');
        if ($end !== false) {
            $html = substr($html, 0, $end);
        }
    }
}

file_put_contents("captured_html/{$name}", trim($html));
echo "Captured $name\n";
