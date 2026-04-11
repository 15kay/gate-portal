<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';

$uid = $_SESSION['user_id'];

if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    try { $pdo->prepare("INSERT INTO message_reads (message_id, user_id) VALUES (?,?)")->execute([$_GET['read'], $uid]); }
    catch (PDOException $e) {}
}

$messages = $pdo->prepare("
    SELECT m.*, u.full_name AS sender_name,
           (SELECT COUNT(*) FROM message_reads WHERE message_id=m.id AND user_id=?) AS is_read
    FROM messages m JOIN users u ON u.id=m.sender_id
    WHERE m.recipient_id=? OR m.is_broadcast=1
    ORDER BY m.sent_at DESC
");
$messages->execute([$uid, $uid]);
$messages = $messages->fetchAll();

$selected = null;
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    foreach ($messages as $m) { if ($m['id'] == $_GET['read']) { $selected = $m; break; } }
}

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Messages</h1>
    <p>Communications from Walter Sisulu University</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:320px 1fr;gap:1.5rem;align-items:start">

  <div class="card" style="padding:0;overflow:hidden">
    <div style="padding:.85rem 1rem;border-bottom:1px solid var(--border);background:#fafafa">
      <span class="fw-600 text-sm">Inbox</span>
      <?php $unread = count(array_filter($messages, fn($m) => !$m['is_read'])); ?>
      <?php if ($unread): ?><span class="badge badge-primary" style="margin-left:.5rem"><?= $unread ?></span><?php endif; ?>
    </div>
    <?php if ($messages): ?>
      <?php foreach ($messages as $m): ?>
      <a href="?read=<?= $m['id'] ?>"
         class="msg-item <?= !$m['is_read'] ? 'unread' : '' ?> <?= ($selected && $selected['id']==$m['id']) ? 'active' : '' ?>">
        <div class="msg-item-header">
          <span class="msg-item-sender"><?= htmlspecialchars($m['sender_name']) ?></span>
          <span class="msg-item-date"><?= date('d M', strtotime($m['sent_at'])) ?></span>
        </div>
        <div class="msg-item-subject">
          <?= $m['is_broadcast'] ? '<span style="color:var(--accent);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em">Broadcast &middot; </span>' : '' ?>
          <?= htmlspecialchars($m['subject'] ?: '(no subject)') ?>
        </div>
        <?php if (!$m['is_read']): ?>
        <div style="width:7px;height:7px;border-radius:50%;background:var(--accent);margin-top:.4rem"></div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state"><p>No messages yet.</p></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <?php if ($selected): ?>
      <div style="margin-bottom:1.25rem">
        <h2 style="font-size:1.1rem;font-weight:700;color:var(--text)"><?= htmlspecialchars($selected['subject'] ?: '(no subject)') ?></h2>
        <div style="display:flex;align-items:center;gap:.75rem;margin-top:.4rem">
          <span class="text-sm text-muted">From: <strong><?= htmlspecialchars($selected['sender_name']) ?></strong></span>
          <span class="text-xs text-muted"><?= date('d F Y, H:i', strtotime($selected['sent_at'])) ?></span>
          <?php if ($selected['is_broadcast']): ?>
          <span class="badge badge-warning">Broadcast</span>
          <?php endif; ?>
        </div>
      </div>
      <hr style="border:none;border-top:1px solid var(--border);margin-bottom:1.25rem">
      <div style="font-size:.9rem;line-height:1.8;color:var(--text)"><?= nl2br(htmlspecialchars($selected['body'])) ?></div>
    <?php else: ?>
      <div class="empty-state" style="padding:4rem 1rem">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5" style="margin:0 auto .75rem;display:block"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <p>Select a message to read</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
