<?php
session_start();

// ─── CHANGE THESE CREDENTIALS ───
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'techblaze2026'); // Change this!

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';

    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Tech Blaze 3.0</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .login-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }
    .login-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 20px;
      padding: 40px 36px;
      width: 100%;
      max-width: 400px;
      backdrop-filter: blur(20px);
    }
    .login-logo {
      width: 48px; height: 48px;
      background: linear-gradient(135deg,#6c63ff,#a78bfa);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 18px; color: #fff;
      margin: 0 auto 20px;
    }
    .login-title {
      text-align: center;
      font-size: 22px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px;
    }
    .login-sub {
      text-align: center;
      font-size: 13px;
      color: rgba(255,255,255,0.4);
      margin-bottom: 28px;
    }
    .login-field { margin-bottom: 16px; }
    .login-field label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: rgba(255,255,255,0.5);
      text-transform: uppercase;
      letter-spacing: 0.07em;
      margin-bottom: 6px;
    }
    .login-field input {
      width: 100%;
      padding: 12px 14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 10px;
      color: #fff;
      font-size: 15px;
      font-family: 'DM Sans', sans-serif;
      box-sizing: border-box;
      outline: none;
      transition: border-color 0.2s;
    }
    .login-field input:focus { border-color: #6c63ff; }
    .login-btn {
      width: 100%;
      padding: 13px;
      background: linear-gradient(135deg, #6c63ff, #a78bfa);
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 8px;
      font-family: 'DM Sans', sans-serif;
      transition: opacity 0.2s, transform 0.15s;
    }
    .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .login-error {
      background: rgba(239,83,80,0.15);
      border: 1px solid rgba(239,83,80,0.3);
      color: #ef5350;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 13px;
      margin-bottom: 16px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>

  <div class="login-page">
    <div class="login-card fade-up fade-up-1">
      <div class="login-logo">CE</div>
      <div class="login-title">Admin Panel</div>
      <div class="login-sub">Tech Blaze 3.0 — Organiser Access</div>

      <?php if ($error): ?>
        <div class="login-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="login-field">
          <label>Username</label>
          <input type="text" name="username" placeholder="Enter username" required autofocus>
        </div>
        <div class="login-field">
          <label>Password</label>
          <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="login-btn">Login to Admin Panel →</button>
      </form>
    </div>
  </div>
</body>
</html>