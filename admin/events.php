<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$_POST['delete_id']]);
        $success = 'Event deleted.';
    } else {
        $pdo->prepare("INSERT INTO events (title,description,event_date,location,created_by) VALUES (?,?,?,?,?)")
            ->execute([trim($_POST['title']), trim($_POST['description']), $_POST['event_date'], trim($_POST['location']), $_SESSION['user_id']]);
        $success = 'Event created successfully.';
    }
}

$events = $pdo->query("
    SELECT e.*, COUNT(r.id) AS rsvp_count
    FROM events e LEFT JOIN event_rsvps r ON r.event_id=e.id
    GROUP BY e.id ORDER BY e.event_date DESC
")->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Events Management</h1>
    <p>Create and manage alumni events</p>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">Create Event</span></div>
    <form method="POST">
      <div class="form-group"><label>Event Title</label><input type="text" name="title" required placeholder="e.g. Annual Alumni Gala"></div>
      <div class="form-group"><label>Date</label><input type="date" name="event_date" required></div>
      <div class="form-group"><label>Location / Venue</label><input type="text" name="location" placeholder="e.g. WSU East London Campus"></div>
      <div class="form-group"><label>Description</label><textarea name="description" rows="4" placeholder="Event details…"></textarea></div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Create Event</button>
    </form>
  </div>

  <div class="card" style="padding:0">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
      <span class="card-title">All Events</span>
    </div>
    <div class="table-wrap" style="border:none;border-radius:0">
      <table>
        <thead><tr><th>Title</th><th>Date</th><th>Location</th><th>RSVPs</th><th style="text-align:right">Action</th></tr></thead>
        <tbody>
          <?php foreach ($events as $ev):
            $past = strtotime($ev['event_date']) < strtotime('today');
          ?>
          <tr>
            <td>
              <div class="fw-600"><?= htmlspecialchars($ev['title']) ?></div>
              <?php if ($past): ?><span class="badge badge-secondary" style="margin-top:.2rem">Past</span><?php else: ?><span class="badge badge-success" style="margin-top:.2rem">Upcoming</span><?php endif; ?>
            </td>
            <td class="td-muted"><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
            <td class="td-muted"><?= htmlspecialchars($ev['location'] ?? '—') ?></td>
            <td><span class="badge badge-info"><?= $ev['rsvp_count'] ?></span></td>
            <td style="text-align:right">
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this event?')">
                <input type="hidden" name="delete_id" value="<?= $ev['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$events): ?>
          <tr><td colspan="5"><div class="empty-state"><p>No events created yet.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
