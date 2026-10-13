<?php
require 'config/init.php';
$stmt = $pdo->prepare('SELECT * FROM member WHERE account_number = ?');
$stmt->execute(['2026-0002']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($row, JSON_PRETTY_PRINT);
