<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';

$uid = $_SESSION['user_id'];

// Filter
$filter = in_array($_GET['filter'] ?? '', ['shortlisted','interviewed','accepted','rejected']) ? $_GET['filter'] : '';

$where = "WHERE cs.user_id = ?";
$params = [$uid];

if ($filter === 'shortlisted')  { $where .= " AND cs.employer_released=1 AND cs.status='submitted' AND cs.interview_scheduled_at IS NULL"; }
elseif ($filter === 'interviewed') { $where .= " AND cs.interview_scheduled_at IS NOT NULL"; }
elseif ($filter === 'accepted')    { $where .= " AND cs.status='accepted'"; }
elseif ($filter === 'rejected')    { $where .= " AND cs.status='rejected'"; }

$apps = $pdo->prepare("
    SELECT cs.*,
           o.title AS opp_title, o.type AS opp_type, o.location AS opp_location,
           o.deadline AS opp_deadline, o.industry AS opp_industry,
           COALESCE(e.company_name, o.company) AS company_name
    FROM candidate_submissions cs
    JOIN opportunities o ON o.id = cs.opportunity_id
    LEFT JOIN employers e ON e.id = o.employer_id
    $where
    ORDER BY cs.released_at DESC, cs.created_at DESC
");
$apps->execute($params);
$apps = $apps->fetchAll();

// Summary counts (always over all submissions for this user)
$counts = $pdo->prepare("
    SELECT
      COUNT(*) AS total,
      SUM(cs.employer_released=1 AND cs.status='submitted' AND cs.interview_scheduled_at IS NULL) AS shortlisted,
      SUM(cs.interview_scheduled_at IS NOT NULL) AS interviewed,
      SUM(cs.status='accepted') AS accepted,
      SUM(cs.status='rejected') AS rejected
    FROM candidate_submissions cs
    WHERE cs.user_id=?
");
$counts->execute([$uid]);
$counts = $counts->fetch();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>My Applications</h1>
    <p>Track the status of your job submissions</p>
  </div>
</div>

<!-- SUMMARY STATS -->
<div class="stats-grid" style="margin-bottom:1.5rem">
  <a href="?filter=" style="text-decoration:none">
    <div class="stat-card <?= !$filter ? 'stat-card-active' : '' ?>">
      <div class="stat-icon primary">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5B1C16" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
      </div>
      <div class="stat-body">
        <div class="stat-num"><?= (int)$counts['total'] ?></div>
        <div class="stat-label">Total Submitted</div>
      </div>
    </div>
  </a>
  <a href="?filter=shortlisted" style="text-decoration:none">
    <div class="stat-card <?= $filter==='shortlisted' ? 'stat-card-active' : '' ?>">
      <div class="stat-icon info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0369a1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
      </div>
      <div class="stat-body">
        <div class="stat-num"><?= (int)$counts['shortlisted'] ?></div>
        <div class="stat-label">Shortlisted</div>
      </div>
    </div>
  </a>
  <a href="?filter=interviewed" style="text-decoration:none">
    <div class="stat-card <?= $filter==='interviewed' ? 'stat-card-active' : '' ?>">
      <div class="stat-icon accent">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D5820F" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="stat-body">
        <div class="stat-num"><?= (int)$counts['interviewed'] ?></div>
        <div class="stat-label">Interviewed</div>
      </div>
    </div>
  </a>
  <a href="?filter=accepted" style="text-decoration:none">
    <div class="stat-card <?= $filter==='accepted' ? 'stat-card-active' : '' ?>">
      <div class="stat-icon success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a6b3a" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
      </div>
      <div class="stat-body">
        <div class="stat-num"><?= (int)$counts['accepted'] ?></div>
        <div class="stat-label">Accepted</div>
      </div>
    </div>
  </a>
</div>

<!-- FILTER TABS -->
<div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap">
  <?php foreach ([''=> 'All', 'shortlisted'=>'Shortlisted', 'interviewed'=>'Interviewed', 'accepted'=>'Accepted', 'rejected'=>'Rejected'] as $val => $label): ?>
  <a href="?filter=<?= $val ?>" class="btn btn-sm <?= $filter===$val ? 'btn-primary' : 'btn-outline' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$apps): ?>
<div class="card">
  <div class="empty-state">
    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
    <p><?= $filter ? 'No applications with this status.' : 'You have not been submitted for any opportunities yet.' ?></p>
    <?php if (!$filter): ?>
    <a href="/gate-portal/alumni/job_match.php" class="btn btn-outline btn-sm">Browse Job Matches</a>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>

<div style="display:flex;flex-direction:column;gap:1rem">
  <?php foreach ($apps as $a):
    // Determine display status
    if ($a['status'] === 'accepted') {
        $status_label = 'Accepted'; $status_class = 'badge-success';
    } elseif ($a['status'] === 'rejected') {
        $status_label = 'Rejected'; $status_class = 'badge-danger';
    } elseif ($a['interview_scheduled_at']) {
        $status_label = 'Interviewed'; $status_class = 'badge-warning';
    } elseif ($a['employer_released']) {
        $status_label = 'Shortlisted'; $status_class = 'badge-info';
    } else {
        $status_label = 'Pending'; $status_class = 'badge-secondary';
    }

    $score = (int)$a['match_score'];
    $score_color = $score >= 70 ? 'var(--success)' : ($score >= 40 ? 'var(--accent)' : 'var(--danger)');
  ?>
  <div class="card" style="padding:1.25rem">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">

      <!-- Left: job info -->
      <div style="flex:1;min-width:220px">
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem">
          <span class="fw-700" style="font-size:.95rem"><?= htmlspecialchars($a['opp_title']) ?></span>
          <span class="badge <?= $status_class ?>"><?= $status_label ?></span>
          <?php if ($a['opp_type']): ?>
          <span class="badge badge-secondary"><?= htmlspecialchars($a['opp_type']) ?></span>
          <?php endif; ?>
        </div>
        <div class="text-sm text-muted">
          <strong><?= htmlspecialchars($a['company_name'] ?? '—') ?></strong>
          <?= $a['opp_location'] ? ' &middot; '.htmlspecialchars($a['opp_location']) : '' ?>
          <?= $a['opp_industry'] ? ' &middot; '.htmlspecialchars($a['opp_industry']) : '' ?>
        </div>
        <?php if ($a['opp_deadline']): ?>
        <div class="text-xs text-muted" style="margin-top:.2rem">
          Deadline: <?= date('d M Y', strtotime($a['opp_deadline'])) ?>
          <?php if (strtotime($a['opp_deadline']) < time()): ?>
          <span class="badge badge-danger" style="font-size:.6rem;margin-left:.3rem">Closed</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right: score + dates -->
      <div style="display:flex;align-items:center;gap:1.5rem;flex-shrink:0;flex-wrap:wrap">
        <div style="text-align:center">
          <div style="font-size:1.4rem;font-weight:800;color:<?= $score_color ?>;line-height:1"><?= $score ?></div>
          <div class="text-xs text-muted">Match Score</div>
        </div>
        <div style="text-align:right">
          <?php if ($a['released_at']): ?>
          <div class="text-xs text-muted">Shortlisted <?= date('d M Y', strtotime($a['released_at'])) ?></div>
          <?php endif; ?>
          <?php if ($a['interview_scheduled_at']): ?>
          <div class="text-xs" style="color:var(--accent);font-weight:600;margin-top:.15rem">
            Interview: <?= date('d M Y, H:i', strtotime($a['interview_scheduled_at'])) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Interview details (if scheduled) -->
    <?php if ($a['interview_scheduled_at']): ?>
    <div style="margin-top:.875rem;padding:.875rem;background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius)">
      <div class="text-xs fw-600 text-muted" style="text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">Interview Details</div>
      <div style="display:flex;flex-wrap:wrap;gap:.75rem 2rem">
        <div>
          <span class="text-xs text-muted">Date &amp; Time</span>
          <div class="text-sm fw-600"><?= date('l, d F Y \a\t H:i', strtotime($a['interview_scheduled_at'])) ?></div>
        </div>
        <?php if ($a['interview_type']): ?>
        <div>
          <span class="text-xs text-muted">Type</span>
          <div class="text-sm fw-600"><?= htmlspecialchars($a['interview_type']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($a['interview_location']): ?>
        <div>
          <span class="text-xs text-muted">Location / Link</span>
          <div class="text-sm fw-600"><?= htmlspecialchars($a['interview_location']) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($a['interview_notes']): ?>
      <div class="text-sm text-muted" style="margin-top:.5rem;padding-top:.5rem;border-top:1px solid #fde68a;line-height:1.6">
        <?= nl2br(htmlspecialchars($a['interview_notes'])) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Progress timeline -->
    <div style="display:flex;align-items:center;gap:0;margin-top:1rem;padding-top:.875rem;border-top:1px solid var(--border-light)">
      <?php
      $steps = [
        ['Submitted',   true],
        ['Shortlisted', (bool)$a['employer_released']],
        ['Interviewed', (bool)$a['interview_scheduled_at']],
        ['Decision',    in_array($a['status'], ['accepted','rejected'])],
      ];
      $last = count($steps) - 1;
      foreach ($steps as $i => [$label, $done]):
        $active = $done;
        $is_decision = $label === 'Decision';
        $decision_color = $a['status'] === 'accepted' ? 'var(--success)' : ($a['status'] === 'rejected' ? 'var(--danger)' : 'var(--border)');
        $dot_color = $is_decision ? ($done ? $decision_color : 'var(--border)') : ($done ? 'var(--success)' : 'var(--border)');
      ?>
      <div style="display:flex;align-items:center;flex:<?= $i < $last ? '1' : '0' ?>">
        <div style="display:flex;flex-direction:column;align-items:center;gap:.2rem">
          <div style="width:14px;height:14px;border-radius:50%;background:<?= $dot_color ?>;border:2px solid <?= $dot_color === 'var(--border)' ? 'var(--border)' : $dot_color ?>;flex-shrink:0"></div>
          <span class="text-xs" style="white-space:nowrap;color:<?= $done ? 'var(--text)' : 'var(--muted)' ?>;font-weight:<?= $done ? '600' : '400' ?>"><?= $is_decision && $done ? ucfirst($a['status']) : $label ?></span>
        </div>
        <?php if ($i < $last): ?>
        <div style="flex:1;height:2px;background:<?= $done ? 'var(--success)' : 'var(--border)' ?>;margin:0 .25rem;margin-bottom:1.2rem"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<style>
.stat-card-active { border: 2px solid var(--primary) !important; }
a > .stat-card { cursor: pointer; }
a > .stat-card:hover { transform: translateY(-2px); }
</style>

<?php include '../includes/footer.php'; ?>
