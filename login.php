<?php
/**
 * login.php
 * -------------------------------------------------------
 * Single, unified login page for Members AND Staff.
 * One "Account No. / Username" field — the backend tries
 * the member table first, then the staff table. This is
 * the ONLY login entry point in the app (Module 1).
 * -------------------------------------------------------
 */

require_once __DIR__ . '/config/init.php';

// Already logged in? Skip the form entirely.
if (is_member()) {
    redirect('member/dashboard.php');
} elseif (is_staff()) {
    redirect('staff/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? '');

    $identifier = clean($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter your Account Number / Username and password.';
    } else {
        $memberAuthError = null;
        $staffAuthError  = null;

        if (authenticate_member($pdo, $identifier, $password, $memberAuthError)) {
            if (!$_SESSION['password_changed'] && $_SESSION['first_login']) {
                set_flash('info', 'Welcome! For your security, please change your password.');
                redirect('member/change_password.php');
            }
            set_flash('success', 'Welcome back, ' . $_SESSION['username'] . '!');
            redirect('member/dashboard.php');
        } elseif ($memberAuthError && strpos($memberAuthError, 'locked') !== false) {
            $errors[] = $memberAuthError;
        } elseif (authenticate_staff($pdo, $identifier, $password, $staffAuthError)) {
            redirect('staff/dashboard.php');
        } elseif ($staffAuthError && strpos($staffAuthError, 'locked') !== false) {
            $errors[] = $staffAuthError;
        } else {
            $errors[] = $memberAuthError ?: ($staffAuthError ?: 'Invalid Account Number/Username or password.');
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · Cooperative Member Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!-- Fixed path: was assets/style.css, real path is assets/css/style.css -->
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body class="login-body">

    <!-- 3D moving space / aurora background -->
    <canvas id="bg-canvas"></canvas>

    <!-- Top brand badge -->
    <div class="top-page-logo">
        <img src="<?= e(BASE_URL) ?>/assets/images/space-brand-logo.svg" alt="SPACE Corporate Brand Logo">
    </div>

    <div class="page-container">
        <div class="login-panel">
            <div class="info-panel">
                <span class="sub-text">Welcome to</span>
                <h1>SPACE Cooperative Portal</h1>
                <p class="sub-text-bold">Member Personal Information System</p>
                <p class="description-text">The Digital SPACE Built for Cooperative Members. Together, we chart the future.</p>
            </div>

            <div class="form-panel">
                <h2>Sign In</h2>
                <p class="subtitle">Enter your details to access your dashboard</p>

                <?php render_flash_messages(); ?>

                <?php if (!empty($errors)): ?>
                <div class="error-message" id="loginErrorMessage">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>
                        <?php foreach ($errors as $err): ?>
                            <?= e($err) ?><br>
                        <?php endforeach; ?>
                    </span>
                </div>
                <?php endif; ?>

                <form action="<?= e(BASE_URL) ?>/login.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="form-group">
                        <label for="username">Account No. / Username</label>
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="e.g. 2026-0001 or staff_user"
                               value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-wrapper" style="position: relative; width: 100%; display: block;">
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="••••••••" required>
                            <button type="button" class="toggle-password-btn" id="togglePasswordBtn" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #718096; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; transition: color 0.2s ease; width: 32px; height: 32px; z-index: 2;">
                                <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <p class="hint-text">New members: your default password is your Account Number.</p>

                    <div class="form-actions">
                        <button type="submit" class="btn-login">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
    function togglePasswordVisibility() {
        const pwdInput = document.getElementById('password');
        const pwdIcon  = document.getElementById('togglePasswordIcon');
        if (!pwdInput || !pwdIcon) return;

        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            pwdIcon.className = 'fa-solid fa-eye-slash';
        } else {
            pwdInput.type = 'password';
            pwdIcon.className = 'fa-solid fa-eye';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const loginForm = document.querySelector('form');
        const errorMsg = document.getElementById('loginErrorMessage');

        if (errorMsg) {
            const hideError = () => {
                errorMsg.style.display = 'none';
            };

            if (usernameInput) usernameInput.addEventListener('input', hideError);
            if (passwordInput) passwordInput.addEventListener('input', hideError);
        }

        // Keyboard navigation: Enter in username moves focus to password; Enter in password submits form
        if (usernameInput && passwordInput) {
            usernameInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    passwordInput.focus();
                    passwordInput.select();
                }
            });

            passwordInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (loginForm) {
                        loginForm.requestSubmit ? loginForm.requestSubmit() : loginForm.submit();
                    }
                }
            });
        }
    });

    (function () {
        const canvas = document.getElementById('bg-canvas');
        if (!canvas || typeof THREE === 'undefined') return;

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(window.devicePixelRatio);

        const particleCount = 3000;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);

        const colorGreen = new THREE.Color(0x00e676);
        const colorTeal = new THREE.Color(0x00bfa5);
        const colorPurple = new THREE.Color(0xaa00ff);
        const colorDeepPurple = new THREE.Color(0x6200ea);

        for (let i = 0; i < particleCount; i++) {
            positions[i * 3]     = (Math.random() - 0.5) * 22;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 14;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 10;

            const r = Math.random();
            const color = r < 0.35 ? colorGreen : r < 0.6 ? colorTeal : r < 0.85 ? colorPurple : colorDeepPurple;
            colors[i * 3] = color.r; colors[i * 3 + 1] = color.g; colors[i * 3 + 2] = color.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.045, vertexColors: true, transparent: true, opacity: 0.8, blending: THREE.AdditiveBlending
        });
        scene.add(new THREE.Points(geometry, material));
        camera.position.z = 5;

        let mouseX = 0, mouseY = 0;
        window.addEventListener('mousemove', (e) => {
            mouseX = (e.clientX / window.innerWidth - 0.5) * 0.5;
            mouseY = (e.clientY / window.innerHeight - 0.5) * 0.5;
        });

        const clock = new THREE.Clock();
        function animate() {
            requestAnimationFrame(animate);
            const t = clock.getElapsedTime();
            const pos = geometry.attributes.position;
            for (let i = 0; i < particleCount; i++) {
                pos.setY(i, pos.getY(i) + Math.sin(t * 0.6 + pos.getX(i)) * 0.0005);
            }
            pos.needsUpdate = true;
            camera.position.x += (mouseX - camera.position.x) * 0.05;
            camera.position.y += (-mouseY - camera.position.y) * 0.05;
            camera.lookAt(scene.position);
            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    })();

    </script>
    <script src="<?= e(BASE_URL) ?>/assets/js/script.js"></script>
</body>
</html>