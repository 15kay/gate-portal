<?php
session_start();
if (!empty($_SESSION['user_id'])) { header('Location: /gate-portal/index.php'); exit; }
require_once '../config/db.php';
require_once '../includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Rate limiting — max 5 attempts per 10 minutes per IP
    $ip  = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . md5($ip);
    if (empty($_SESSION[$key])) $_SESSION[$key] = ['count' => 0, 'time' => time()];
    if (time() - $_SESSION[$key]['time'] > 600) $_SESSION[$key] = ['count' => 0, 'time' => time()];

    if ($_SESSION[$key]['count'] >= 5) {
        $wait = ceil((600 - (time() - $_SESSION[$key]['time'])) / 60);
        $error = "Too many failed attempts. Please wait {$wait} minute(s) and try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $stmt  = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user  = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            // Regenerate session ID on login to prevent session fixation
            session_regenerate_id(true);
            unset($_SESSION[$key]);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            header('Location: /gate-portal/index.php'); exit;
        }

        $_SESSION[$key]['count']++;
        $remaining = 5 - $_SESSION[$key]['count'];
        $error = 'Invalid email address or password.' . ($remaining <= 2 ? " {$remaining} attempt(s) remaining." : '');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In — GATE Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">

  <div class="auth-panel-left">
    <div class="auth-left-logo">
      <img src="/wsu-logo.svg" alt="Walter Sisulu University">
    </div>
    <div class="auth-left-content">
      <h1>Graduate &amp; Alumni Tracking &amp; Engagement</h1>
      <p>A centralised platform connecting WSU graduates with career opportunities, institutional support, and a lifelong alumni network.</p>
    </div>
    <div class="auth-left-features">
      <div class="auth-feature"><div class="auth-feature-dot"></div> Track employment outcomes &amp; career progression</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div> Maintain and update your alumni profile</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div> Access events, networking &amp; opportunities</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div> Stay connected with your institution</div>
    </div>
  </div>

  <div class="auth-panel-right">
    <div class="auth-form-wrap">
      <div class="auth-form-header">
        <h2>Welcome back</h2>
        <p>Sign in to your GATE Portal account</p>
      </div>

      <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required
                 placeholder="your@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <div style="position:relative">
            <input type="password" id="password" name="password" required
                   placeholder="Enter your password" style="padding-right:2.5rem">
            <button type="button" onclick="togglePw()" tabindex="-1"
                    style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:0">
              <svg id="pw-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:.5rem;justify-content:center">
          Sign In
        </button>
      </form>

      <div class="auth-divider">or</div>

      <p style="text-align:center;font-size:.875rem;color:var(--muted)">
        New alumni?
        <a href="/gate-portal/auth/register.php" style="color:var(--primary);font-weight:600;text-decoration:none">Create an account</a>
      </p>
      <p style="text-align:center;font-size:.75rem;color:var(--muted);margin-top:2rem;line-height:1.6">
        &copy; <?= date('Y') ?> Walter Sisulu University. All rights reserved.
      </p>
    </div>
  </div>

</div>
<script>
function togglePw() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
