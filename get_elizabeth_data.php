<?php
require 'config/init.php';

$stmt = $pdo->prepare('SELECT * FROM member WHERE account_number = ?');
$stmt->execute(['2026-0002']);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

$member_id = $member['member_id'];

// Parents
$stmt = $pdo->prepare('SELECT * FROM member_parents WHERE member_id = ?');
$stmt->execute([$member_id]);
$parents = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Spouse
$stmt = $pdo->prepare('SELECT * FROM member_spouse WHERE member_id = ?');
$stmt->execute([$member_id]);
$spouse = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Children
$stmt = $pdo->prepare('SELECT * FROM member_children WHERE member_id = ?');
$stmt->execute([$member_id]);
$children = $stmt->fetchAll(PDO::FETCH_ASSOC);

// References
$stmt = $pdo->prepare('SELECT * FROM member_reference WHERE member_id = ?');
$stmt->execute([$member_id]);
$references = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'member' => $member,
    'parents' => $parents,
    'spouse' => $spouse,
    'children' => $children,
    'references' => $references
], JSON_PRETTY_PRINT);
