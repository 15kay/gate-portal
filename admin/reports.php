<?php
require_once '../includes/auth_guard.php';
require_min_role('reports_admin');
require_once '../config/db.php';

if (isset($_GET['export'])) {
    $rows = $pdo->query("
        SELECT u.full_name, u.email, ap.student_id, ap.graduation_year, ap.degree, ap.department,
               er.employment_type, er.employer, er.job_title, er.industry, er.location
        FROM users u
        LEFT JOIN alumni_profiles ap ON ap.user_id=u.id
        LEFT JOIN employment_records er ON er.user_id=u.id AND er.is_current=1
        WHERE u.role='alumni' ORDER BY ap.graduation_year DESC, u.full_name
    ")->fetchAll();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="gate_alumni_'.date('Ymd').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Full Name','Email','Student ID','Grad Year','Degree','Department','Employment Type','Employer','Job Title','Industry','Location']);
    foreach ($rows as $r) fputcsv($out, $r);
    fclose($out); exit;
}

$interviews = $pdo->query("
    SELECT cs.interview_scheduled_at, cs.interview_type, cs.interview_location,
           u.full_name, u.email, o.title AS opp_title, o.company
    FROM candidate_submissions cs
    JOIN users u ON u.id = cs.alumni_user_id
    JOIN opportunities o ON o.id = cs.opportunity_id
    WHERE cs.interview_scheduled_at IS NOT NULL
    ORDER BY cs.interview_scheduled_at DESC
    LIMIT 50
")->fetchAll();

$emp = $pdo->query("SELECT employment_type, COUNT(*) AS cnt FROM employment_records WHERE is_current=1 GROUP BY employment_type ORDER BY cnt DESC")->fetchAll();
$total_emp = array_sum(array_column($emp, 'cnt')) ?: 1;

$industries = $pdo->query("SELECT industry, COUNT(*) AS cnt FROM employment_records WHERE is_current=1 AND industry IS NOT NULL AND industry != '' GROUP BY industry ORDER BY cnt DESC LIMIT 8")->fetchAll();

$dept_stats = $pdo->query("
    SELECT ap.department, COUNT(*) AS total,
           SUM(CASE WHEN er.employment_type != 'Unemployed' AND er.employment_type IS NOT NULL THEN 1 ELSE 0 END) AS employed
    FROM alumni_profiles ap
    LEFT JOIN employment_records er ON er.user_id=ap.user_id AND er.is_current=1
    WHERE ap.department IS NOT NULL AND ap.department != ''
    GROUP BY ap.department ORDER BY total DESC
")->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Reports &amp; Analytics</h1>
    <p>Employment outcomes and alumni engagement data</p>
  </div>
  <div class="page-header-actions">
    <a href="?export=1" class="btn btn-accent btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
  </div>
</div>

<!-- EMPLOYMENT BREAKDOWN STATS -->
<div class="stats-grid" style="margin-bottom:1.75rem">
<?php
$type_cfg = [
  'Full-time'      => ['success','#1a6b3a'],
  'Part-time'      => ['info','#0369a1'],
  'Self-employed'  => ['info','#0369a1'],
  'Freelance'      => ['info','#0369a1'],
  'Unemployed'     => ['danger','#c0392b'],
  'Further Studies'=> ['warning','#92400e'],
];
foreach ($emp as $e):
  [$cls, $color] = $type_cfg[$e['employment_type']] ?? ['secondary','#52525b'];
  $pct = round($e['cnt'] / $total_emp * 100);
?>
<div class="stat-card">
  <div class="stat-icon <?= $cls ?>">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="<?= $color ?>" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
  </div>
  <div class="stat-body">
    <div class="stat-num"><?= $e['cnt'] ?></div>
    <div class="stat-label"><?= htmlspecialchars($e['employment_type']) ?> &middot; <?= $pct ?>%</div>
  </div>
</div>
<?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">

  <!-- TOP INDUSTRIES -->
  <div class="card">
    <div class="card-header"><span class="card-title">Top Industries</span></div>
    <?php if ($industries):
      $max = max(array_column($industries,'cnt')) ?: 1;
    ?>
    <div style="display:flex;flex-direction:column;gap:.85rem">
      <?php foreach ($industries as $ind): ?>
      <div>
        <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
          <span class="text-sm fw-600"><?= htmlspecialchars($ind['industry']) ?></span>
          <span class="text-sm text-muted"><?= $ind['cnt'] ?></span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" style="width:<?= round($ind['cnt']/$max*100) ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?><div class="empty-state"><p>No industry data recorded yet.</p></div><?php endif; ?>
  </div>

  <!-- DEPARTMENT EMPLOYABILITY -->
  <div class="card">
    <div class="card-header"><span class="card-title">Employability by Department</span></div>
    <div class="table-wrap" style="border:none">
      <table>
        <thead><tr><th>Department</th><th>Total</th><th>Employed</th><th>Rate</th></tr></thead>
        <tbody>
          <?php foreach ($dept_stats as $d):
            $rate = $d['total'] ? round($d['employed']/$d['total']*100) : 0;
          ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($d['department']) ?></td>
            <td><?= $d['total'] ?></td>
            <td><?= $d['employed'] ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem">
                <div class="progress-bar-wrap" style="width:70px">
                  <div class="progress-bar-fill" style="width:<?= $rate ?>%;background:var(--success)"></div>
                </div>
                <span class="text-sm"><?= $rate ?>%</span>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$dept_stats): ?>
          <tr><td colspan="4"><div class="empty-state"><p>No department data yet.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- INTERVIEWS -->
<?php if ($interviews): ?>
<div class="card" style="margin-top:1.5rem">
  <div class="card-header"><span class="card-title">Scheduled Interviews</span><span class="badge badge-info"><?= count($interviews) ?></span></div>
  <div class="table-wrap" style="border:none">
    <table>
      <thead><tr><th>Candidate</th><th>Position</th><th>Company</th><th>Type</th><th>Date &amp; Time</th><th>Location</th></tr></thead>
      <tbody>
        <?php foreach ($interviews as $iv): ?>
        <tr>
          <td>
            <div class="fw-600" style="font-size:.875rem"><?= htmlspecialchars($iv['full_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($iv['email']) ?></div>
          </td>
          <td><?= htmlspecialchars($iv['opp_title']) ?></td>
          <td><?= htmlspecialchars($iv['company']) ?></td>
          <td><span class="badge badge-secondary"><?= htmlspecialchars($iv['interview_type'] ?? '—') ?></span></td>
          <td><?= date('d M Y, H:i', strtotime($iv['interview_scheduled_at'])) ?></td>
          <td class="text-sm text-muted"><?= htmlspecialchars($iv['interview_location'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
