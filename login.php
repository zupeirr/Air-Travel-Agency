<?php
require_once __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $user = db_row("SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email = ?", [$email]);

        if ($user && ($user['status'] ?? 'active') !== 'active') {
            $error = 'Your account is ' . htmlspecialchars($user['status']) . '. Please contact an administrator.';
        } elseif ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_slug'] ?? 'staff';
            $_SESSION['user_role_name'] = $user['role_name'] ?? 'Staff';
            
            // Update last login
            db_execute("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);

            header('Location: index.php');
            exit;
        } elseif ($email === 'admin@airtravel.com' && $password === 'password') {
            // Backward-compatible fallback for demo environment
            $_SESSION['user_id'] = 1;
            $_SESSION['user_name'] = 'System Admin';
            $_SESSION['user_email'] = 'admin@airtravel.com';
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_role_name'] = 'Administrator';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Air Travel Agency</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --navy-900: #0f172a;
            --navy-800: #1e293b;
            --gold-500: #eab308;
            --gold-600: #ca8a04;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--navy-900);
            background-image: radial-gradient(circle at top right, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 1)), url('assets/images/login-bg.jpg'); /* Optional bg image */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .login-container {
            display: flex;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            min-height: 600px;
        }
        .login-left {
            flex: 1;
            padding: 64px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right {
            flex: 1;
            background: linear-gradient(135deg, var(--navy-800) 0%, var(--navy-900) 100%);
            padding: 64px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            border-left: 1px solid rgba(255, 255, 255, 0.05);
        }
        .login-right::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at center, rgba(234, 179, 8, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .brand-logo {
            width: 80px;
            margin-bottom: 24px;
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            margin-bottom: 8px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-bottom: 48px;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #cbd5e1;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            width: 20px;
            height: 20px;
        }
        .form-control {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 16px 16px 16px 48px;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--gold-500);
            box-shadow: 0 0 0 4px rgba(234, 179, 8, 0.1);
            background: rgba(15, 23, 42, 0.8);
        }
        .form-control::placeholder {
            color: #475569;
        }
        .btn-login {
            background: var(--gold-500);
            color: var(--navy-900);
            border: none;
            border-radius: 12px;
            padding: 16px;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
        }
        .btn-login:hover {
            background: var(--gold-600);
            transform: translateY(-2px);
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .forgot-pass {
            display: block;
            text-align: right;
            color: var(--gold-500);
            font-size: 0.875rem;
            text-decoration: none;
            margin-top: 8px;
        }
        .forgot-pass:hover {
            text-decoration: underline;
        }
        
        /* Right side visuals */
        .feature-icon {
            width: 64px;
            height: 64px;
            background: rgba(234, 179, 8, 0.1);
            color: var(--gold-500);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }
        .feature-icon svg {
            width: 32px;
            height: 32px;
        }
        .right-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            margin-bottom: 16px;
        }
        .right-desc {
            color: #94a3b8;
            line-height: 1.6;
            max-width: 80%;
        }

        @media (max-width: 900px) {
            .login-right { display: none; }
            .login-container { max-width: 500px; margin: 24px; min-height: auto; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Left Side: Login Form -->
        <div class="login-left">
            <!-- Ensure you have an image here or fallback to text -->
            <img src="assets/images/Air travel.png" alt="Air Travel Logo" class="brand-logo" onerror="this.style.display='none'">
            
            <h1>Welcome Back</h1>
            <p class="subtitle">Please sign in to your agency workspace.</p>

            <?php if ($error): ?>
            <div class="error-msg">
                <i data-lucide="alert-circle" style="width:18px;height:18px;"></i>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['reset']) && $_GET['reset'] === '1'): ?>
            <div style="background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.3); color:#86efac; padding:12px 16px; border-radius:8px; font-size:0.875rem; margin-bottom:24px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="check-circle" style="width:18px;height:18px;color:#22c55e;"></i>
                Password has been successfully updated. You may now sign in.
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <i data-lucide="mail"></i>
                        <input type="email" name="email" class="form-control" placeholder="admin@travelagency.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i data-lucide="lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <a href="forgot-password.php" class="forgot-pass">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login">
                    Sign In <i data-lucide="arrow-right" style="width:18px;height:18px;"></i>
                </button>
            </form>
        </div>

        <!-- Right Side: Visual/Branding -->
        <div class="login-right">
            <div class="feature-icon">
                <i data-lucide="plane-takeoff"></i>
            </div>
            <h2 class="right-title">Air Travel OS</h2>
            <p class="right-desc">Your complete operating system for managing customers, visa processing, travel packages, and agency finances.</p>
            
            <div style="margin-top: 48px; display: flex; gap: 8px;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--gold-500);"></div>
                <div style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.2);"></div>
                <div style="width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.2);"></div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
