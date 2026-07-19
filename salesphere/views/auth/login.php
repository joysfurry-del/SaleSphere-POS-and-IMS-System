<?php
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
$assetsUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/assets';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | <?= SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= $assetsUrl ?>/css/app.css">
  <style>
    .login-card {
      background-color: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 2.5rem;
      width: 100%;
      max-width: 420px;
    }
    .login-input {
      width: 100%;
      padding: 0.75rem 1rem;
      background-color: var(--bg-primary);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text-primary);
      font-size: 0.9rem;
      font-family: 'Inter', sans-serif;
      outline: none;
      transition: border-color 0.15s ease;
    }
    .login-input:focus {
      border-color: var(--primary);
    }
    .login-input::placeholder {
      color: var(--text-secondary);
    }
    .login-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-secondary);
      margin-bottom: 0.4rem;
    }
  </style>
</head>
<body style="background-color:var(--bg-primary);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;">

  <div class="login-card">
    <div style="text-align:center;margin-bottom:2rem;">
      <div style="width:56px;height:56px;background:var(--primary);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
        <span style="color:#fff;font-size:1.5rem;font-weight:700;">S</span>
      </div>
      <h1 style="font-size:1.4rem;font-weight:700;color:var(--text-primary);margin-bottom:0.25rem;">Welcome Back</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Sign in to <?= SITE_NAME ?></p>
    </div>

    <!-- show error or success message from session -->
    <?php if ($error): ?>
      <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;color:#EF4444;font-weight:500;"><?= escape($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.25rem;font-size:0.85rem;color:#22C55E;font-weight:500;"><?= escape($success) ?></div>
    <?php endif; ?>

    <!-- login form with username, password, csrf token -->
    <form id="loginForm" method="POST" action="<?= $_SERVER['SCRIPT_NAME'] ?>">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div style="margin-bottom:1.25rem;">
        <label for="username" class="login-label">Username</label>
        <input type="text" id="username" name="username" class="login-input" placeholder="Enter your username" value="<?= escape(old('username')) ?>" autocomplete="username" autofocus>
      </div>

      <div style="margin-bottom:1.5rem;">
        <label for="password" class="login-label">Password</label>
        <input type="password" id="password" name="password" class="login-input" placeholder="Enter your password" autocomplete="current-password">
      </div>

      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:0.8rem;font-size:0.95rem;">
        Sign In
      </button>
    </form>

    <div style="margin-top:1.5rem;text-align:center;">
      <button id="themeToggle" style="background:none;border:1px solid var(--border);color:var(--text-secondary);cursor:pointer;padding:8px;border-radius:8px;line-height:0;"></button>
    </div>
  </div>

  <script src="<?= $assetsUrl ?>/js/login.js"></script>
  <script src="<?= $assetsUrl ?>/js/theme.js"></script>
</body>
</html>
