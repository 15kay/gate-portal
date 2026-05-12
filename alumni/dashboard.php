<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';
require_once '../includes/settings.php';

$uid = $_SESSION['user_id'];

$profile = $pdo->prepare("SELECT ap.*, u.email FROM alumni_profiles ap JOIN users u ON u.id=ap.user_id WHERE ap.user_id=?");
$profile->execute([$uid]);
$p = $profile->fetch();

// If no profile exists, create a default empty one
if (!$p) {
    $pdo->prepare("INSERT INTO alumni_profiles (user_id) VALUES (?)")-> execute([$uid]);
    $profile->execute([$uid]);
    $p = $profile->fetch();
}

$current_job = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? AND is_current=1 ORDER BY start_date DESC LIMIT 1");
$current_job->execute([$uid]);
$current_job = $current_job->fetch();

$job_count = $pdo->prepare("SELECT COUNT(*) FROM employment_records WHERE user_id=?");
$job_count->execute([$uid]);
$job_count = $job_count->fetchColumn();

$unread = $pdo->prepare("
    SELECT COUNT(*) FROM messages m
    LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=?
    WHERE (m.recipient_id=? OR m.is_broadcast=1) AND mr.id IS NULL
");
$unread->execute([$uid, $uid]);
$unread_count = $unread->fetchColumn();

$events = $pdo->query("
    SELECT e.*, (SELECT COUNT(*) FROM event_rsvps WHERE event_id=e.id AND user_id=$uid) AS rsvped
    FROM events e WHERE e.event_date >= CURDATE() ORDER BY e.event_date LIMIT 3
")->fetchAll();

$total_alumni = $pdo->query("SELECT COUNT(*) FROM users WHERE role='alumni'")->fetchColumn();

// Profile completion score
$fields = ['profile_photo','phone','graduation_year','degree','department','bio','linkedin_url'];
$filled = $p ? array_filter($fields, fn($f) => !empty($p[$f])) : [];
$completion = round((count($filled) / count($fields)) * 100);

include '../includes/header.php';
?>

<!-- Real-time refresh every 60s for unread count & events -->
<meta http-equiv="refresh" content="60">

<?php $welcome_msg = setting('welcome_message'); ?>
<?php if ($welcome_msg): ?>
<div class="alert alert-info" style="margin-bottom:1.5rem">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <?= htmlspecialchars($welcome_msg) ?>
</div>
<?php endif; ?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]) ?> 👋</h1>
    <p><?= date('l, d F Y') ?></p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/alumni/profile.php" class="btn btn-outline btn-sm">Edit Profile</a>
  </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon <?= $current_job ? 'success' : 'danger' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="<?= $current_job ? '#1a6b3a' : '#c0392b' ?>" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num" style="font-size:1.1rem;padding-top:.3rem"><?= $current_job ? htmlspecialchars($current_job['employment_type']) : 'Not Set' ?></div>
      <div class="stat-label">Employment Status</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon primary">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5B1C16" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= $p['graduation_year'] ?? '—' ?></div>
      <div class="stat-label">Graduation Year</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D5820F" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= $unread_count ?></div>
      <div class="stat-label">Unread Messages</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon info">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0369a1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= number_format($total_alumni) ?></div>
      <div class="stat-label">Alumni Network</div>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

  <!-- LEFT COLUMN -->
  <div>

    <!-- PROFILE COMPLETION -->
    <?php if ($completion < 100): ?>
    <div class="card" style="border-left:4px solid var(--accent);margin-bottom:1.5rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
        <div>
          <div class="fw-600">Complete your profile</div>
          <div class="text-sm text-muted">A complete profile helps the university track your progress</div>
        </div>
        <div style="font-size:1.5rem;font-weight:700;color:var(--accent)"><?= $completion ?>%</div>
      </div>
      <div class="progress-bar-wrap" style="height:8px;margin-bottom:.75rem">
        <div class="progress-bar-fill" style="width:<?= $completion ?>%;background:var(--accent)"></div>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php if (empty($p['profile_photo'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ Photo</a><?php endif; ?>
        <?php if (empty($p['phone'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ Phone</a><?php endif; ?>
        <?php if (empty($p['graduation_year'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ Grad Year</a><?php endif; ?>
        <?php if (empty($p['degree'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ Degree</a><?php endif; ?>
        <?php if (empty($p['department'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ Department</a><?php endif; ?>
        <?php if (empty($p['bio'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ Bio</a><?php endif; ?>
        <?php if (empty($p['linkedin_url'])): ?><a href="/gate-portal/alumni/profile.php" class="badge badge-warning" style="text-decoration:none">+ LinkedIn</a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- PROFILE CARD -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">My Profile</span>
        <a href="/gate-portal/alumni/profile.php" class="btn btn-outline btn-sm">Edit</a>
      </div>
      <div class="profile-header">
        <?php if ($p['profile_photo']): ?>
          <img src="/gate-portal/<?= htmlspecialchars($p['profile_photo']) ?>" class="profile-photo" alt="Photo">
        <?php else: ?>
          <div class="profile-avatar-placeholder"><?= strtoupper(substr($_SESSION['full_name'],0,1)) ?></div>
        <?php endif; ?>
        <div class="info">
          <h2><?= htmlspecialchars($_SESSION['full_name']) ?></h2>
          <p><?= htmlspecialchars($p['degree'] ?? 'Degree not set') ?></p>
          <p><?= htmlspecialchars($p['department'] ?? 'Department not set') ?></p>
          <?php if ($p['linkedin_url']): ?>
          <a href="<?= htmlspecialchars($p['linkedin_url']) ?>" target="_blank"
             style="font-size:.8rem;color:var(--primary);font-weight:600;text-decoration:none">LinkedIn →</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$current_job): ?>
      <div class="alert alert-warning" style="margin-top:.5rem">
        No employment record found.
        <a href="/gate-portal/alumni/employment.php" style="font-weight:600;color:inherit">Add now →</a>
      </div>
      <?php else: ?>
      <div style="background:var(--bg);border-radius:var(--radius);padding:.875rem 1rem;margin-top:.5rem">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem">Current Position</div>
        <div class="fw-600"><?= htmlspecialchars($current_job['job_title'] ?: $current_job['employment_type']) ?></div>
        <?php if ($current_job['employer']): ?>
        <div class="text-sm text-muted"><?= htmlspecialchars($current_job['employer']) ?><?= $current_job['location'] ? ' · '.htmlspecialchars($current_job['location']) : '' ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="card">
      <div class="card-header"><span class="card-title">Quick Actions</span></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <a href="/gate-portal/alumni/employment.php" class="btn btn-outline" style="justify-content:center;padding:.75rem">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          Update Employment
        </a>
        <a href="/gate-portal/alumni/directory.php" class="btn btn-outline" style="justify-content:center;padding:.75rem">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Browse Directory
        </a>
        <a href="/gate-portal/alumni/messages.php" class="btn btn-outline" style="justify-content:center;padding:.75rem;position:relative">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          Messages
          <?php if ($unread_count > 0): ?>
          <span style="position:absolute;top:.4rem;right:.4rem;background:var(--danger);color:#fff;border-radius:50%;width:16px;height:16px;font-size:.65rem;display:flex;align-items:center;justify-content:center;font-weight:700"><?= $unread_count ?></span>
          <?php endif; ?>
        </a>
        <a href="/gate-portal/alumni/events.php" class="btn btn-outline" style="justify-content:center;padding:.75rem">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          View Events
        </a>
      </div>
    </div>

  </div>

  <!-- RIGHT COLUMN -->
  <div>

    <!-- UPCOMING EVENTS -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Upcoming Events</span>
        <a href="/gate-portal/alumni/events.php" class="btn btn-outline btn-sm">All</a>
      </div>
      <?php if ($events): ?>
      <div style="display:flex;flex-direction:column;gap:.875rem">
        <?php foreach ($events as $ev): ?>
        <div style="display:flex;gap:.75rem;align-items:flex-start">
          <div class="event-card-date">
            <span><?= date('M', strtotime($ev['event_date'])) ?></span>
            <span class="day"><?= date('d', strtotime($ev['event_date'])) ?></span>
          </div>
          <div style="flex:1;min-width:0">
            <div class="fw-600 text-sm"><?= htmlspecialchars($ev['title']) ?></div>
            <?php if ($ev['location']): ?>
            <div class="text-xs text-muted"><?= htmlspecialchars($ev['location']) ?></div>
            <?php endif; ?>
            <?php if ($ev['rsvped']): ?>
            <span class="badge badge-success" style="margin-top:.3rem;font-size:.68rem">RSVP'd</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state" style="padding:1.5rem"><p>No upcoming events.</p></div>
      <?php endif; ?>
    </div>

    <!-- EMPLOYMENT SUMMARY -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Career Summary</span>
        <a href="/gate-portal/alumni/employment.php" class="btn btn-outline btn-sm">Manage</a>
      </div>
      <div style="display:flex;flex-direction:column;gap:.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .75rem;background:var(--bg);border-radius:var(--radius)">
          <span class="text-sm text-muted">Total Records</span>
          <span class="fw-700"><?= $job_count ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .75rem;background:var(--bg);border-radius:var(--radius)">
          <span class="text-sm text-muted">Current Status</span>
          <?php if ($current_job): ?>
          <span class="badge badge-success"><?= htmlspecialchars($current_job['employment_type']) ?></span>
          <?php else: ?>
          <span class="badge badge-secondary">Not Set</span>
          <?php endif; ?>
        </div>
        <?php if ($p['graduation_year']): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .75rem;background:var(--bg);border-radius:var(--radius)">
          <span class="text-sm text-muted">Years Since Graduation</span>
          <span class="fw-700"><?= date('Y') - $p['graduation_year'] ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
