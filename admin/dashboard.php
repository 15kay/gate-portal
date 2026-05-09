<?php
require_once '../includes/auth_guard.php';
require_min_role('reports_admin');
require_once '../config/db.php';
require_once '../includes/db_helpers.php';

$current_date = db_current_date();

$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM users WHERE role='alumni') AS total_alumni,
      (SELECT COUNT(*) FROM employment_records WHERE is_current=1 AND employment_type != 'Unemployed') AS employed,
      (SELECT COUNT(*) FROM employment_records WHERE is_current=1 AND employment_type = 'Unemployed') AS unemployed,
      (SELECT COUNT(*) FROM events WHERE event_date >= $current_date) AS upcoming_events
")->fetch();

$emp_breakdown = $pdo->query("
    SELECT employment_type, COUNT(*) AS cnt
    FROM employment_records WHERE is_current=1
    GROUP BY employment_type ORDER BY cnt DESC
")->fetchAll();

$by_year = $pdo->query("
    SELECT TOP 7 graduation_year, COUNT(*) AS cnt
    FROM alumni_profiles WHERE graduation_year IS NOT NULL
    GROUP BY graduation_year ORDER BY graduation_year ASC
")->fetchAll();

$recent = $pdo->query("
    SELECT TOP 8 u.full_name, u.email, u.created_at, ap.graduation_year, ap.degree, ap.department
    FROM users u LEFT JOIN alumni_profiles ap ON ap.user_id = u.id
    WHERE u.role='alumni' ORDER BY u.created_at DESC
")->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Dashboard</h1>
    <p>Overview of alumni data and engagement metrics</p>
  </div>
  <div class="page-header-actions">
    <span class="text-muted text-sm"><?= date('l, d F Y') ?></span>
  </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon primary">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5B1C16" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= number_format($stats['total_alumni']) ?></div>
      <div class="stat-label">Total Alumni</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon success">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a6b3a" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= number_format($stats['employed']) ?></div>
      <div class="stat-label">Currently Employed</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon danger">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c0392b" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= number_format($stats['unemployed']) ?></div>
      <div class="stat-label">Unemployed</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D5820F" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </div>
    <div class="stat-body">
      <div class="stat-num"><?= number_format($stats['upcoming_events']) ?></div>
      <div class="stat-label">Upcoming Events</div>
    </div>
  </div>
</div>

<!-- CHARTS ROW -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">

  <div class="card">
    <div class="card-header">
      <span class="card-title">Employment Breakdown</span>
    </div>
    <?php if ($emp_breakdown):
      $max = max(array_column($emp_breakdown, 'cnt')) ?: 1;
      $colors = ['#5B1C16','#D5820F','#1a6b3a','#c0392b','#0369a1','#7c3aed'];
    ?>
    <div class="chart-bar-wrap">
      <?php foreach ($emp_breakdown as $i => $row):
        $h = max(8, round(($row['cnt'] / $max) * 130));
      ?>
      <div class="chart-bar" style="height:<?= $h ?>px;background:<?= $colors[$i % count($colors)] ?>">
        <span class="bar-val"><?= $row['cnt'] ?></span>
        <span class="bar-label"><?= htmlspecialchars($row['employment_type']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state"><p>No employment data recorded yet.</p></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Graduates by Year</span>
    </div>
    <?php if ($by_year):
      $max2 = max(array_column($by_year, 'cnt')) ?: 1;
    ?>
    <div class="chart-bar-wrap">
      <?php foreach ($by_year as $row):
        $h = max(8, round(($row['cnt'] / $max2) * 130));
      ?>
      <div class="chart-bar" style="height:<?= $h ?>px;background:#5B1C16">
        <span class="bar-val"><?= $row['cnt'] ?></span>
        <span class="bar-label"><?= $row['graduation_year'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state"><p>No graduation year data yet.</p></div>
    <?php endif; ?>
  </div>

</div>

<!-- RECENT ALUMNI -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Recent Registrations</span>
    <a href="/gate-portal/admin/alumni.php" class="btn btn-outline btn-sm">View all alumni</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Alumni</th>
          <th>Degree</th>
          <th>Department</th>
          <th>Grad Year</th>
          <th>Joined</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($r['full_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($r['email']) ?></div>
          </td>
          <td><?= htmlspecialchars($r['degree'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['department'] ?? '—') ?></td>
          <td><?= $r['graduation_year'] ?? '—' ?></td>
          <td class="td-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?>
        <tr><td colspan="5"><div class="empty-state"><p>No alumni registered yet.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
