<?php
require_once 'config.php';
if (!empty($_SESSION['user_id'])) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === '' || strlen($username) > 100 || strlen($password) > 4096) {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash, full_name FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            redirect('index.php');
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign in &mdash; STOCKer</title>
<script>(function(){try{var t=localStorage.getItem('stocker-theme');document.documentElement.setAttribute('data-theme',t==='dark'?'dark':'light');}catch(e){document.documentElement.setAttribute('data-theme','light');}})();</script>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
<div class="login-card">
  <div class="login-brand">
    <span class="brand-icon">S</span>
    <span class="brand-name"><b>STOCKer</b><span class="brand-sub" style="display:block;margin-top:2px">Inventory ERP</span></span>
  </div>
  <div class="eyebrow">Welcome back</div>
  <h1>Sign in to STOCKer</h1>
  <p class="muted">Your smart inventory &amp; stock management system.</p>

  <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>

  <form method="post" class="form-stack">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>Username
      <input name="username" required autocomplete="username" placeholder="admin">
    </label>
    <label>Password
      <input type="password" name="password" required autocomplete="current-password" placeholder="Enter password">
    </label>
    <button class="btn primary full" type="submit" style="margin-top:4px">
      Sign in &rarr;
    </button>
  </form>
  <p class="hint">Demo &mdash; username: <b>admin</b> &nbsp;/&nbsp; password: <b>Admin@123</b></p>
</div>
</body>
</html>
