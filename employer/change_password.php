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

<style>
.pw-wrap{position:relative}
.pw-wrap input{padding-right:2.5rem}
.pw-eye{position:absolute;right:.65rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:.2rem;line-height:1}
.pw-eye:hover{color:var(--text)}
.strength-bar{height:4px;border-radius:99px;background:var(--border);margin-top:.4rem;overflow:hidden}
.strength-fill{height:100%;border-radius:99px;transition:width .3s,background .3s}
.strength-label{font-size:.72rem;margin-top:.25rem}
</style>

<div style="max-width:480px">
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <div class="card">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Current Password</label>
        <div class="pw-wrap">
          <input type="password" name="current_password" id="pw-current" required placeholder="Enter your current password">
          <button type="button" class="pw-eye" onclick="togglePw('pw-current',this)" aria-label="Show/hide">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label>New Password</label>
        <div class="pw-wrap">
          <input type="password" name="new_password" id="pw-new" required placeholder="Min. 8 characters"
                 oninput="updateStrength(this.value)">
          <button type="button" class="pw-eye" onclick="togglePw('pw-new',this)" aria-label="Show/hide">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="sw-fill" style="width:0"></div></div>
        <div class="strength-label text-muted" id="sw-label"></div>
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <div class="pw-wrap">
          <input type="password" name="confirm_password" id="pw-confirm" required placeholder="Repeat new password">
          <button type="button" class="pw-eye" onclick="togglePw('pw-confirm',this)" aria-label="Show/hide">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
<script>
function togglePw(id, btn) {
    var input = document.getElementById(id);
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    // Swap eye icon
    btn.innerHTML = showing
        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}

function updateStrength(pw) {
    var score = 0;
    if (pw.length >= 8)                          score++;
    if (pw.length >= 12)                         score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw))   score++;
    if (/[0-9]/.test(pw))                        score++;
    if (/[^A-Za-z0-9]/.test(pw))                score++;

    var levels = [
        {w:'0%',   bg:'transparent',      text:''},
        {w:'25%',  bg:'var(--danger)',     text:'Weak'},
        {w:'50%',  bg:'#f97316',          text:'Fair'},
        {w:'75%',  bg:'var(--accent)',    text:'Good'},
        {w:'100%', bg:'var(--success)',   text:'Strong'},
    ];
    var lv = pw.length === 0 ? levels[0] : levels[Math.min(score, 4)];
    document.getElementById('sw-fill').style.width      = lv.w;
    document.getElementById('sw-fill').style.background = lv.bg;
    document.getElementById('sw-label').textContent     = lv.text;
    document.getElementById('sw-label').style.color     = lv.bg;
}
</script>
