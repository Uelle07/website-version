<?php
require 'config/init.php';

$stmt = $pdo->prepare('SELECT * FROM member WHERE account_number = ?');
$stmt->execute(['2026-0002']);
$user = $stmt->fetch();

$_SESSION['user_type'] = 'member';
$_SESSION['user_id'] = $user['member_id'];
$_SESSION['username'] = $user['account_number'];
$_SESSION['name'] = trim($user['firstname'] . ' ' . $user['lastname']);

ob_start();
require 'member/profile.php';
$html = ob_get_clean();

echo substr($html, 0, 1000); 
