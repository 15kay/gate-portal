<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');
    $to      = $_POST['recipient'] ?? 'all';
    $rid     = ($to === 'all') ? null : intval($to);
    $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, body, is_broadcast) VALUES (?,?,?,?,?)")
        ->execute([$_SESSION['user_id'], $rid, $subject, $body, $rid ? 0 : 1]);
    $success = 'Message sent successfully.';
}

$alumni_list = $pdo->query("SELECT id, full_name, email FROM users WHERE role='alumni' ORDER BY full_name")->fetchAll();
$sent = $pdo->query("
    SELECT m.*, u.full_name AS recipient_name
    FROM messages m LEFT JOIN users u ON u.id=m.recipient_id
    WHERE m.sender_id={$_SESSION['user_id']}
    ORDER BY m.sent_at DESC LIMIT 20
")->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Messages</h1>
    <p>Send broadcast or individual messages to alumni</p>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">Compose Message</span></div>
    <form method="POST">
      <div class="form-group">
        <label>Recipient</label>
        <select name="recipient">
          <option value="all">All Alumni (Broadcast)</option>
          <?php foreach ($alumni_list as $a): ?>
          <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['full_name']) ?> — <?= htmlspecialchars($a['email']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Subject</label><input type="text" name="subject" required placeholder="Message subject"></div>
      <div class="form-group"><label>Message</label><textarea name="body" rows="6" required placeholder="Write your message here…"></textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Send Message</button>
    </form>
  </div>

  <div class="card" style="padding:0">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
      <span class="card-title">Sent Messages</span>
    </div>
    <div class="table-wrap" style="border:none;border-radius:0">
      <table>
        <thead><tr><th>To</th><th>Subject</th><th>Sent</th></tr></thead>
        <tbody>
          <?php foreach ($sent as $m): ?>
          <tr>
            <td>
              <?php if ($m['is_broadcast']): ?>
              <span class="badge badge-warning">Broadcast</span>
              <?php else: ?>
              <span class="fw-600"><?= htmlspecialchars($m['recipient_name'] ?? '—') ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($m['subject']) ?></td>
            <td class="td-muted"><?= date('d M Y, H:i', strtotime($m['sent_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$sent): ?>
          <tr><td colspan="3"><div class="empty-state"><p>No messages sent yet.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
