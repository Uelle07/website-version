<?php
require 'config/init.php';

$stmt = $pdo->prepare('SELECT * FROM member WHERE account_number = ?');
$stmt->execute(['2026-0002']);
$user = $stmt->fetch();

$_SESSION['user_type'] = 'member';
$_SESSION['user_id'] = $user['member_id'];
$_SESSION['username'] = $user['account_number'];
$_SESSION['name'] = trim($user['firstname'] . ' ' . $user['lastname']);

@mkdir('captured_html');

$pages = [
    'dashboard.php' => 'member/dashboard.php',
    'profile.php' => 'member/profile.php',
    'update_info.php' => 'member/update_info.php',
    'surveys.php' => 'member/surveys.php',
    'change_password.php' => 'member/change_password.php',
    'upload_photo.php' => 'member/upload_photo.php'
];

foreach ($pages as $name => $path) {
    ob_start();
    require $path;
    $html = ob_get_clean();
    
    // Exact slice for content inside <main class="app-main"><div class="container-fluid py-4">
    // Actually, dashboard uses dashboard-wrapper inside container-fluid? 
    // Let's check where dashboard-wrapper is:
    // It's directly inside the included header.php which outputs container-fluid py-4!
    
    $start = strpos($html, '<div class="container-fluid py-4">');
    if ($start !== false) {
        $html = substr($html, $start + strlen('<div class="container-fluid py-4">'));
        
        // Find the start of footer.php output. It outputs </div> (container-fluid) </main> </div> (app-content)
        // Wait, footer.php starts with:
        //         <?php render_flash_messages(); ? > (actually in header.php)
        // Let's find exactly </main> and cut there!
        $end = strrpos($html, '</main>');
        if ($end !== false) {
            $html = substr($html, 0, $end);
            
            // Now $html ends with </div> which is the close of container-fluid. Let's remove the last </div>
            $lastDiv = strrpos($html, '</div>');
            if ($lastDiv !== false) {
                $html = substr($html, 0, $lastDiv);
            }
            
            // Also, there might be flash messages. Let's just output it and clean up in python.
            file_put_contents("captured_html/{$name}", trim($html));
        } else {
            file_put_contents("captured_html/{$name}", "Error finding </main>");
        }
    }
}
echo "Captured all pages.";
