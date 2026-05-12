<?php
session_start();
if (!empty($_SESSION['user_id'])) { header('Location: /index.php'); exit; }
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/faculties.php';
require_once '../includes/settings.php';

// Check if registration is open
if (setting('registration_open', '1') === '0') {
    http_response_code(503);
    $inst = setting('institution_name', 'Walter Sisulu University');
    $email = setting('contact_email', 'alumni@wsu.ac.za');
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Registration Closed</title>
    <link rel="stylesheet" href="/assets/css/style.css"></head><body>
    <div class="auth-wrap"><div class="auth-panel-right" style="grid-column:1/-1">
    <div class="auth-form-wrap" style="text-align:center">
    <div style="font-size:3rem;margin-bottom:1rem">🔒</div>
    <h2 style="color:var(--primary)">Registration Temporarily Closed</h2>
    <p style="color:var(--muted);margin:.75rem 0 1.5rem">Alumni self-registration is currently closed by the institution. Please contact <strong>' . htmlspecialchars($email) . '</strong> for assistance.</p>
    <a href="/auth/login.php" class="btn btn-primary">Back to Sign In</a>
    </div></div></div></body></html>');
}

$error = $success = '';
$step  = 1; // Step 1: verify identity, Step 2: create account
$verified_student = null;

// Step 1 — Verify student identity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    csrf_verify();
    $student_no  = strtoupper(trim($_POST['student_number'] ?? ''));
    $id_passport = trim($_POST['id_passport'] ?? '');

    if (!$student_no || !$id_passport) {
        $error = 'Please enter both your student number and ID/passport number.';
        $step  = 1;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM student_registry WHERE student_number = ? AND id_passport = ?");
        $stmt->execute([$student_no, $id_passport]);
        $student = $stmt->fetch();

        if (!$student) {
            $error = 'No matching student record found. Please check your student number and ID/passport number, or contact the Alumni Office.';
            $step  = 1;
        } elseif ($student['is_registered']) {
            $error = 'An account already exists for this student number. Please <a href="/auth/login.php" style="font-weight:600;color:inherit">sign in</a> instead.';
            $step  = 1;
        } else {
            // Identity verified — move to step 2
            $step = 2;
            $verified_student = $student;
            $_SESSION['reg_student_number'] = $student['student_number'];
            $_SESSION['reg_id_passport']    = $student['id_passport'];
        }
    }
}

// Step 2 — Create account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    csrf_verify();

    // Re-verify session token
    $student_no  = $_SESSION['reg_student_number'] ?? '';
    $id_passport = $_SESSION['reg_id_passport'] ?? '';

    if (!$student_no || !$id_passport) {
        $error = 'Session expired. Please start again.';
        $step  = 1;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM student_registry WHERE student_number = ? AND id_passport = ? AND is_registered = 0");
        $stmt->execute([$student_no, $id_passport]);
        $student = $stmt->fetch();

        if (!$student) {
            $error = 'Verification failed. Please start again.';
            $step  = 1;
        } else {
            $name  = trim($_POST['full_name'] ?? $student['full_name']);
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $pass2 = $_POST['password2'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
                $step  = 2;
                $verified_student = $student;
            } elseif (strlen($pass) < 8) {
                $error = 'Password must be at least 8 characters.';
                $step  = 2;
                $verified_student = $student;
            } elseif ($pass !== $pass2) {
                $error = 'Passwords do not match.';
                $step  = 2;
                $verified_student = $student;
            } else {
                try {
                    $pdo->beginTransaction();

                    // Create user
                    $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,'alumni')")
                        ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                    $uid = $pdo->lastInsertId();

                    // Create profile pre-filled from registry
                    $pdo->prepare("INSERT INTO alumni_profiles (user_id, student_id, id_number, degree, department, graduation_year) VALUES (?,?,?,?,?,?)")
                        ->execute([$uid, $student['student_number'], $student['id_passport'],
                                   $student['degree'], $student['department'], $student['graduation_year']]);

                    // Mark student as registered
                    $pdo->prepare("UPDATE student_registry SET is_registered=1 WHERE student_number=?")
                        ->execute([$student['student_number']]);

                    $pdo->commit();

                    // Clear session tokens
                    unset($_SESSION['reg_student_number'], $_SESSION['reg_id_passport']);
                    $success = true;

                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'This email address is already registered.';
                    $step  = 2;
                    $verified_student = $student;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Create Account — GATE Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">

  <!-- LEFT PANEL -->
  <div class="auth-panel-left">
    <div class="auth-left-logo">
      <img src="/wsu-logo.svg" alt="Walter Sisulu University">
    </div>
    <div class="auth-left-content">
      <h1>Join the WSU Alumni Network</h1>
      <p>Registration is restricted to verified WSU graduates. You will need your student number and South African ID or passport number.</p>
    </div>
    <div class="auth-left-features">
      <div class="auth-feature"><div class="auth-feature-dot"></div> Verified graduates only</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div> Profile pre-filled from academic records</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div> Track your career journey</div>
      <div class="auth-feature"><div class="auth-feature-dot"></div> Stay connected with WSU</div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="auth-panel-right">
    <div class="auth-form-wrap">

      <?php if ($success): ?>
        <!-- SUCCESS -->
        <div style="text-align:center;padding:1rem 0">
          <div style="width:60px;height:60px;border-radius:50%;background:#dcf5e7;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#1a6b3a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 style="color:var(--text);margin-bottom:.5rem">Account Created!</h2>
          <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem">Your alumni account has been verified and created successfully.</p>
          <a href="/auth/login.php" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">Sign In Now</a>
        </div>

      <?php elseif ($step === 1): ?>
        <!-- STEP 1: IDENTITY VERIFICATION -->
        <div class="auth-form-header">
          <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
            <span style="background:var(--primary);color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">1</span>
            <h2 style="margin:0">Verify Your Identity</h2>
          </div>
          <p>Enter your WSU student number and South African ID or passport number to confirm you are a graduate.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <?= csrf_field() ?>
          <div class="form-group">
            <label for="student_number">Student Number</label>
            <input type="text" id="student_number" name="student_number" required
                   placeholder="e.g. 201900001"
                   value="<?= htmlspecialchars($_POST['student_number'] ?? '') ?>"
                   style="text-transform:uppercase">
            <div class="form-hint">Your WSU student number as it appears on your academic record.</div>
          </div>
          <div class="form-group">
            <label for="id_passport">SA ID Number or Passport Number</label>
            <input type="text" id="id_passport" name="id_passport" required
                   placeholder="e.g. 9001015009087 or A12345678"
                   value="<?= htmlspecialchars($_POST['id_passport'] ?? '') ?>">
            <div class="form-hint">13-digit SA ID number or passport number used during registration.</div>
          </div>
          <button type="submit" name="verify" value="1" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
            Verify Identity →
          </button>
        </form>

        <div class="auth-divider">or</div>
        <p style="text-align:center;font-size:.875rem;color:var(--muted)">
          Already have an account?
          <a href="/auth/login.php" style="color:var(--primary);font-weight:600;text-decoration:none">Sign in</a>
        </p>
        <p style="text-align:center;font-size:.78rem;color:var(--muted);margin-top:1rem">
          Having trouble? Contact the Alumni Office at <strong><?= htmlspecialchars(setting('contact_email','alumni@wsu.ac.za')) ?></strong>
        </p>

      <?php elseif ($step === 2): ?>
        <!-- STEP 2: CREATE ACCOUNT -->
        <div class="auth-form-header">
          <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
            <span style="background:var(--success);color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0">✓</span>
            <span style="font-size:.82rem;color:var(--success);font-weight:600">Identity Verified</span>
          </div>
          <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
            <span style="background:var(--primary);color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0">2</span>
            <h2 style="margin:0">Create Your Account</h2>
          </div>
          <p>Welcome, <strong><?= htmlspecialchars($verified_student['full_name']) ?></strong>. Set up your login details below.</p>
        </div>

        <!-- Pre-filled info from registry -->
        <div style="background:var(--bg);border-radius:var(--radius);padding:.875rem 1rem;margin-bottom:1.25rem;border:1px solid var(--border)">
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem">Academic Record</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem">
            <div class="text-sm"><span class="text-muted">Student No:</span> <strong><?= htmlspecialchars($verified_student['student_number']) ?></strong></div>
            <div class="text-sm"><span class="text-muted">Grad Year:</span> <strong><?= $verified_student['graduation_year'] ?? '—' ?></strong></div>
            <div class="text-sm" style="grid-column:1/-1"><span class="text-muted">Degree:</span> <strong><?= htmlspecialchars($verified_student['degree'] ?? '—') ?></strong></div>
            <div class="text-sm" style="grid-column:1/-1"><span class="text-muted">Department:</span> <strong><?= htmlspecialchars($verified_student['department'] ?? '—') ?></strong></div>
          </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <?= csrf_field() ?>
          <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required
                   value="<?= htmlspecialchars($_POST['full_name'] ?? $verified_student['full_name']) ?>">
          </div>
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required
                   placeholder="your@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required placeholder="Min. 8 characters">
            </div>
            <div class="form-group">
              <label for="password2">Confirm Password</label>
              <input type="password" id="password2" name="password2" required placeholder="Repeat password">
            </div>
          </div>
          <button type="submit" name="register" value="1" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
            Create Account
          </button>
          <a href="/auth/register.php" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:.5rem">
            ← Start Over
          </a>
        </form>

      <?php endif; ?>

      <p style="text-align:center;font-size:.75rem;color:var(--muted);margin-top:2rem;line-height:1.6">
        &copy; <?= date('Y') ?> Walter Sisulu University. All rights reserved.
      </p>
    </div>
  </div>

</div>
</body>
</html>
