<?php
require_once '../includes/auth_guard.php';
require_role('employer');
require_once '../config/db.php';
require_once '../includes/csrf.php';

$uid = $_SESSION['user_id'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $user = $pdo->prepare("SELECT password FROM users WHERE id=?");
    $user->execute([$uid]);
    $user = $user->fetch();

    if (!password_verify($current, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        $success = 'Password changed successfully.';
    }
}

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Change Password</h1>
    <p>Update your employer account password</p>
  </div>
</div>

<div style="max-width:480px">
  <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
  <div class="card">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Current Password</label>
        <input type="password" name="current_password" required placeholder="Enter your current password">
      </div>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" required placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required placeholder="Repeat new password">
      </div>
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
