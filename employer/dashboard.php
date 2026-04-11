<?php
require_once '../includes/auth_guard.php';
require_role('employer');
require_once '../config/db.php';

// Get employer record linked to this user
$emp = $pdo->prepare("SELECT * FROM employers WHERE user_id=?");
$emp->execute([$_SESSION['user_id']]);
$emp = $emp->fetch();
if (!$emp) { header('Location: /gate-portal/auth/logout.php'); exit; }

$stats = $pdo->prepare("
    SELECT
      COUNT(*) AS total,
      SUM(status='open') AS open_count,
      SUM(status='filled') AS filled_count,
      SUM(status='closed') AS closed_count
    FROM opportunities WHERE employer_id=?
");
$stats->execute([$emp['id']]);
$stats = $stats->fetch();

$recent = $pdo->prepare("
    SELECT o.*,
      (SELECT COUNT(*) FROM candidate_submissions cs WHERE cs.opportunity_id=o.id) AS candidates,
      (SELECT COUNT(*) FROM candidate_submissions cs WHERE cs.opportunity_id=o.id AND cs.employer_released=1) AS released
    FROM opportunities o
    WHERE o.employer_id=?
    ORDER BY o.created_at DESC LIMIT 5
");
$recent->execute([$emp['id']]);
$recent = $recent->fetchAll();

// Shortlist notifications
$new_shortlists = $pdo->prepare("
    SELECT o.id, o.title, COUNT(cs.id) AS cnt, MAX(cs.released_at) AS released_at
    FROM candidate_submissions cs
    JOIN opportunities o ON o.id=cs.opportunity_id
    WHERE o.employer_id=? AND cs.employer_released=1
    GROUP BY o.id ORDER BY released_at DESC
");
$new_shortlists->execute([$emp['id']]);
$new_shortlists = $new_shortlists->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Welcome, <?= htmlspecialchars($emp['company_name']) ?></h1>
    <p><?= date('l, d F Y') ?></p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/employer/jobs.php?new=1" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Post a Job
    </a>
  </div>
</div>

<?php if ($new_shortlists): ?>
<div class="card" style="margin-bottom:1.5rem;border-left:4px solid var(--success)">
  <div class="card-header">
    <span class="card-title">Shortlisted Candidates Ready</span>
    <a href="/gate-portal/employer/shortlist.php" class="btn btn-success btn-sm">View All Shortlists</a>
  </div>
  <div style="display:flex;flex-direction:column;gap:.5rem">
    <?php foreach ($new_shortlists as $sl): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.65rem .85rem;background:#f0fdf4;border-radius:var(--r);border:1px solid #bbf7d0">
      <div>
        <div class="fw-600 text-sm"><?= htmlspecialchars($sl['title']) ?></div>
        <div class="text-xs text-muted"><?= $sl['cnt'] ?> candidate<?= $sl['cnt']!=1?'s':'' ?> &middot; Released <?= date('d M Y', strtotime($sl['released_at'])) ?></div>
      </div>
      <a href="/gate-portal/employer/shortlist.php?opp=<?= $sl['id'] ?>" class="btn btn-outline btn-sm">Review</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon primary">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5B1C16" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= (int)$stats['total'] ?></div>
      <div class="stat-label">Total Jobs Posted</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon success">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a6b3a" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= (int)$stats['open_count'] ?></div>
      <div class="stat-label">Open Positions</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D5820F" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= (int)$stats['filled_count'] ?></div>
      <div class="stat-label">Positions Filled</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon info">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0369a1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= (int)$stats['closed_count'] ?></div>
      <div class="stat-label">Closed</div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Recent Job Postings</span>
    <a href="/gate-portal/employer/jobs.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <?php if ($recent): ?>
  <div class="table-wrap" style="border:none;margin:-1px">
    <table>
      <thead>
        <tr><th>Job Title</th><th>Type</th><th>Deadline</th><th>Candidates</th><th>Released</th><th>Status</th><th style="text-align:right">Actions</th></tr>
      </thead>
      <tbody>
        <?php
        $status_badge = ['open'=>'badge-success','closed'=>'badge-secondary','filled'=>'badge-info'];
        foreach ($recent as $j): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($j['title']) ?></td>
          <td><span class="badge badge-secondary"><?= htmlspecialchars($j['type']) ?></span></td>
          <td class="td-muted"><?= $j['deadline'] ? date('d M Y', strtotime($j['deadline'])) : '—' ?></td>
          <td><?= $j['candidates'] ?></td>
          <td>
            <?php if ($j['released'] > 0): ?>
            <a href="/gate-portal/employer/shortlist.php?opp=<?= $j['id'] ?>" class="badge badge-success" style="text-decoration:none"><?= $j['released'] ?> shortlisted</a>
            <?php else: ?>
            <span class="text-xs text-muted">—</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $status_badge[$j['status']] ?>"><?= ucfirst($j['status']) ?></span></td>
          <td style="text-align:right">
            <a href="/gate-portal/employer/jobs.php?edit=<?= $j['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    <p>No jobs posted yet. <a href="/gate-portal/employer/jobs.php?new=1">Post your first job</a>.</p>
  </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
