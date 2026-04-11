<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';

$uid = $_SESSION['user_id'];

if (isset($_GET['rsvp']) && is_numeric($_GET['rsvp'])) {
    try { $pdo->prepare("INSERT INTO event_rsvps (event_id, user_id) VALUES (?,?)")->execute([$_GET['rsvp'], $uid]); }
    catch (PDOException $e) { $pdo->prepare("DELETE FROM event_rsvps WHERE event_id=? AND user_id=?")->execute([$_GET['rsvp'], $uid]); }
    header('Location: /gate-portal/alumni/events.php'); exit;
}

$events = $pdo->query("
    SELECT e.*, COUNT(r.id) AS rsvp_count,
           (SELECT COUNT(*) FROM event_rsvps WHERE event_id=e.id AND user_id=$uid) AS i_rsvped
    FROM events e LEFT JOIN event_rsvps r ON r.event_id=e.id
    GROUP BY e.id ORDER BY e.event_date ASC
")->fetchAll();

$upcoming = array_filter($events, fn($e) => strtotime($e['event_date']) >= strtotime('today'));
$past     = array_filter($events, fn($e) => strtotime($e['event_date']) < strtotime('today'));

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Events</h1>
    <p>Upcoming alumni events and activities</p>
  </div>
</div>

<?php if ($upcoming): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;margin-bottom:2rem">
  <?php foreach ($upcoming as $ev): ?>
  <div class="event-card">
    <div class="event-card-date">
      <span><?= date('M', strtotime($ev['event_date'])) ?></span>
      <span class="day"><?= date('d', strtotime($ev['event_date'])) ?></span>
    </div>
    <div class="fw-600" style="margin-bottom:.3rem"><?= htmlspecialchars($ev['title']) ?></div>
    <?php if ($ev['location']): ?>
    <div class="text-sm text-muted" style="margin-bottom:.5rem">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:.2rem"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      <?= htmlspecialchars($ev['location']) ?>
    </div>
    <?php endif; ?>
    <?php if ($ev['description']): ?>
    <p class="text-sm text-muted" style="margin-bottom:.75rem;line-height:1.6"><?= htmlspecialchars(substr($ev['description'], 0, 100)) ?><?= strlen($ev['description']) > 100 ? '…' : '' ?></p>
    <?php endif; ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.75rem">
      <span class="text-xs text-muted"><?= $ev['rsvp_count'] ?> attending</span>
      <a href="?rsvp=<?= $ev['id'] ?>" class="btn btn-sm <?= $ev['i_rsvped'] ? 'btn-outline' : 'btn-primary' ?>">
        <?= $ev['i_rsvped'] ? 'Cancel RSVP' : 'RSVP' ?>
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card"><div class="empty-state"><p>No upcoming events at this time. Check back soon.</p></div></div>
<?php endif; ?>

<?php if ($past): ?>
<div class="card">
  <div class="card-header"><span class="card-title">Past Events</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Attendees</th></tr></thead>
      <tbody>
        <?php foreach ($past as $ev): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($ev['title']) ?></td>
          <td class="td-muted"><?= date('d F Y', strtotime($ev['event_date'])) ?></td>
          <td class="td-muted"><?= htmlspecialchars($ev['location'] ?? '—') ?></td>
          <td><?= $ev['rsvp_count'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
