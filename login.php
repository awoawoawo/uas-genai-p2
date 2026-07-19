<?php
session_start();
require 'config.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: rekap.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Cek user di database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && $password === $user['password']) { // Menggunakan plain text per request
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] == 'admin') {
            header("Location: rekap.php");
        } else {
            header("Location: menu.php");
        }
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kopi Kuningan</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: var(--bg-main);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            text-align: center;
        }
        .login-brand {
            font-family: var(--font-mono);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-main);
            border-bottom: 4px solid var(--text-main);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .login-input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            font-family: var(--font-mono);
            font-size: 1rem;
            text-align: center;
            box-shadow: inset 2px 2px 0 rgba(0,0,0,0.05);
        }
        .login-input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.3);
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-brand">
        <!-- Simple SVG Icon Coffee -->
        <svg style="transform: translateY(-2px);" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
            <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
            <line x1="6" y1="1" x2="6" y2="4"></line>
            <line x1="10" y1="1" x2="10" y2="4"></line>
            <line x1="14" y1="1" x2="14" y2="4"></line>
        </svg>
        Kopi Kuningan
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger" style="padding: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="text" name="username" class="login-input" placeholder="Username" required autofocus>
        <input type="password" name="password" class="login-input" placeholder="Password" required>
        
        <button type="submit" name="login" class="btn btn-primary btn-block" style="margin-top: 1rem; padding: 1rem;">
            MASUK
        </button>
    </form>
    
    <div style="margin-top: 1.5rem; text-align: center;">
        <a href="index.php" style="color: var(--text-main); font-family: var(--font-mono); text-decoration: none; font-weight: bold; font-size: 0.9rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem; border-radius: 4px; transition: background-color 0.2s;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Beranda
        </a>
    </div>
    
    <div style="margin-top: 2rem; font-family: var(--font-mono); font-size: 0.8rem; color: var(--text-muted);">
        <p>Akses Uji Coba:</p>
        <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
        <p>Pembeli: <strong>pembeli</strong> / <strong>pembeli123</strong></p>
    </div>
</div>

</body>
</html>
