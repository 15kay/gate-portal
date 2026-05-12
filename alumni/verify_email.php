<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';

$token = trim($_GET['token'] ?? '');
$error = '';

if (!$token) {
    $error = 'Invalid or missing verification token.';
} else {
    $stmt = $pdo->prepare("SELECT * FROM email_verifications WHERE token=? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        $error = 'This verification link is invalid or has expired. Please request a new one from your profile page.';
    } elseif ((int)$row['user_id'] !== (int)$_SESSION['user_id']) {
        $error = 'This verification link does not belong to your account.';
    } else {
        // Check the new email hasn't been taken since the request was made
        $taken = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $taken->execute([$row['new_email'], $row['user_id']]);
        if ($taken->fetch()) {
            $error = 'That email address has already been taken by another account.';
            $pdo->prepare("DELETE FROM email_verifications WHERE id=?")->execute([$row['id']]);
        } else {
            // Apply the change
            $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$row['new_email'], $row['user_id']]);
            $pdo->prepare("DELETE FROM email_verifications WHERE user_id=?")->execute([$row['user_id']]);
            header('Location: /alumni/profile.php?email_verified=1'); exit;
        }
    }
}

include '../includes/header.php';
?>
<div style="max-width:480px;margin:4rem auto">
  <div class="card" style="text-align:center;padding:2rem">
    <div style="width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <h2 style="margin-bottom:.5rem">Verification Failed</h2>
    <p class="text-muted"><?= htmlspecialchars($error) ?></p>
    <a href="/alumni/profile.php" class="btn btn-primary" style="margin-top:1.25rem">Back to Profile</a>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
