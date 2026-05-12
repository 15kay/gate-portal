<?php
require_once '../includes/auth_guard.php';
require_min_role('reports_admin');
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.role, u.created_at,
           ap.student_id, ap.id_number, ap.phone, ap.gender, ap.location,
           ap.graduation_year, ap.degree, ap.faculty, ap.department,
           ap.profile_photo, ap.bio, ap.linkedin_url
    FROM users u
    LEFT JOIN alumni_profiles ap ON ap.user_id = u.id
    WHERE u.id = ? AND u.role = 'alumni'
");
$stmt->execute([$id]);
$alumni = $stmt->fetch();
if (!$alumni) { header('Location: /gate-portal/admin/alumni.php'); exit; }

$jobs = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC, start_date DESC");
$jobs->execute([$id]);
$jobs = $jobs->fetchAll();

$events = $pdo->prepare("
    SELECT e.title, e.event_date, e.location, r.rsvp_at
    FROM event_rsvps r
    JOIN events e ON e.id = r.event_id
    WHERE r.user_id = ?
    ORDER BY e.event_date DESC
");
$events->execute([$id]);
$events = $events->fetchAll();

$submissions = $pdo->prepare("
    SELECT cs.match_score, cs.status, cs.submitted_at,
           o.title AS opp_title, o.company
    FROM candidate_submissions cs
    JOIN opportunities o ON o.id = cs.opportunity_id
    WHERE cs.alumni_user_id = ?
    ORDER BY cs.submitted_at DESC
") ;
try {
    $submissions->execute([$id]);
    $submissions = $submissions->fetchAll();
} catch (Throwable $e) {
    $submissions = [];
}

$cv_data = $pdo->prepare("SELECT * FROM alumni_cv WHERE user_id=?");
$cv_data->execute([$id]);
$cv_data = $cv_data->fetch() ?: [];

$current_job = array_filter($jobs, fn($j) => $j['is_current']);
$current_job = reset($current_job);

$status_badge = [
    'suggested' => 'badge-secondary',
    'selected'  => 'badge-warning',
    'submitted' => 'badge-info',
    'accepted'  => 'badge-success',
    'rejected'  => 'badge-danger',
];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1><?= htmlspecialchars($alumni['full_name']) ?></h1>
    <p>Alumni profile &mdash; registered <?= date('d M Y', strtotime($alumni['created_at'])) ?></p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/alumni.php" class="btn btn-outline btn-sm">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back to Alumni
    </a>
    <?php if (can_manage_alumni()): ?>
    <a href="/gate-portal/admin/alumni.php?highlight=<?= $id ?>" class="btn btn-danger btn-sm"
       onclick="return confirm('Delete this alumni record permanently?') || (window.location='/gate-portal/admin/view_alumni.php?id=<?= $id ?>', false)">
    </a>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start">

  <!-- ── LEFT COLUMN ─────────────────────────────────── -->
  <div>

    <!-- Profile card -->
    <div class="card" style="text-align:center;padding:1.75rem 1.25rem">
      <?php if ($alumni['profile_photo']): ?>
        <img src="/gate-portal/uploads/photos/<?= htmlspecialchars(basename($alumni['profile_photo'])) ?>"
             style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin:0 auto .75rem;display:block" alt="Photo">
      <?php else: ?>
        <div style="width:96px;height:96px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin:0 auto .75rem">
          <?= strtoupper(substr($alumni['full_name'],0,1)) ?>
        </div>
      <?php endif; ?>

      <div class="fw-700" style="font-size:1.05rem;margin-bottom:.2rem"><?= htmlspecialchars($alumni['full_name']) ?></div>
      <div class="text-sm text-muted"><?= htmlspecialchars($alumni['email']) ?></div>

      <?php if ($current_job): ?>
      <div style="margin-top:.75rem;padding:.6rem .75rem;background:var(--bg);border-radius:var(--r)">
        <div class="text-sm fw-600"><?= htmlspecialchars($current_job['job_title'] ?? 'Employed') ?></div>
        <div class="text-xs text-muted"><?= htmlspecialchars($current_job['employer'] ?? '') ?></div>
      </div>
      <?php endif; ?>

      <?php if ($alumni['linkedin_url']): ?>
      <a href="<?= htmlspecialchars($alumni['linkedin_url']) ?>" target="_blank"
         class="btn btn-outline btn-sm" style="margin-top:.85rem;width:100%;justify-content:center">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        LinkedIn Profile
      </a>
      <?php endif; ?>
    </div>

    <!-- Contact & IDs -->
    <div class="card">
      <div class="card-header"><span class="card-title">Contact &amp; Identity</span></div>
      <div style="display:flex;flex-direction:column;gap:.75rem">
        <?php
        $fields = [
          ['Phone',          $alumni['phone']],
          ['Gender',         $alumni['gender'] ?? null],
          ['Location',       $alumni['location'] ?? null],
          ['Student ID',     $alumni['student_id']],
          ['ID / Passport',  $alumni['id_number']],
          ['Registered',     date('d M Y', strtotime($alumni['created_at']))],
        ];
        foreach ($fields as [$label, $val]):
          if (!$val) continue;
        ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.15rem"><?= $label ?></div>
          <div class="text-sm fw-600"><?= htmlspecialchars($val) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="card">
      <div class="card-header"><span class="card-title">Summary</span></div>
      <div style="display:flex;flex-direction:column;gap:.4rem">
        <?php
        $summary = [
          ['Employment Records', count($jobs),        'badge-primary'],
          ['Events RSVPd',       count($events),      'badge-info'],
          ['Submissions',        count($submissions), 'badge-secondary'],
        ];
        foreach ($summary as [$label, $val, $badge]):
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem .75rem;background:var(--bg);border-radius:var(--r)">
          <span class="text-sm text-muted"><?= $label ?></span>
          <span class="badge <?= $badge ?>"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ── RIGHT COLUMN ────────────────────────────────── -->
  <div>

    <!-- Academic -->
    <div class="card">
      <div class="card-header"><span class="card-title">Academic Information</span></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <?php
        $academic = [
          ['Degree',          $alumni['degree']],
          ['Faculty',         $alumni['faculty']],
          ['Department',      $alumni['department']],
          ['Graduation Year', $alumni['graduation_year']],
          ['Student ID',      $alumni['student_id']],
        ];
        foreach ($academic as [$label, $val]):
        ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem"><?= $label ?></div>
          <div class="fw-600"><?= htmlspecialchars($val ?? '—') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($alumni['bio']): ?>
      <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-light)">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem">Bio</div>
        <p class="text-sm" style="line-height:1.7;color:var(--text-2)"><?= nl2br(htmlspecialchars($alumni['bio'])) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Employment History -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Employment History</span>
        <span class="badge badge-secondary"><?= count($jobs) ?></span>
      </div>
      <?php if ($jobs): ?>
      <div class="table-wrap" style="border:none;margin:-1px">
        <table>
          <thead>
            <tr><th>Employer</th><th>Job Title</th><th>Type</th><th>Location</th><th>Period</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($jobs as $j):
              $type_badge = match($j['employment_type']) {
                'Full-time','Part-time' => 'badge-success',
                'Self-employed','Freelance' => 'badge-info',
                'Unemployed' => 'badge-danger',
                'Further Studies' => 'badge-warning',
                default => 'badge-secondary'
              };
            ?>
            <tr>
              <td>
                <div class="fw-600"><?= htmlspecialchars($j['employer'] ?? '—') ?></div>
                <?php if ($j['industry']): ?><div class="td-muted"><?= htmlspecialchars($j['industry']) ?></div><?php endif; ?>
              </td>
              <td><?= htmlspecialchars($j['job_title'] ?? '—') ?></td>
              <td><span class="badge <?= $type_badge ?>"><?= htmlspecialchars($j['employment_type']) ?></span></td>
              <td class="td-muted"><?= htmlspecialchars($j['location'] ?? '—') ?></td>
              <td class="td-muted" style="white-space:nowrap">
                <?= $j['start_date'] ? date('M Y', strtotime($j['start_date'])) : '—' ?>
                &ndash;
                <?= $j['end_date'] ? date('M Y', strtotime($j['end_date'])) : ($j['is_current'] ? 'Present' : '—') ?>
              </td>
              <td>
                <span class="badge <?= $j['is_current'] ? 'badge-success' : 'badge-secondary' ?>">
                  <?= $j['is_current'] ? 'Current' : 'Past' ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><p>No employment records on file.</p></div>
      <?php endif; ?>
    </div>

    <!-- Candidate Submissions -->
    <?php if ($submissions): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">Candidate Submissions</span>
        <span class="badge badge-secondary"><?= count($submissions) ?></span>
      </div>
      <div class="table-wrap" style="border:none;margin:-1px">
        <table>
          <thead>
            <tr><th>Opportunity</th><th>Company</th><th style="text-align:center">Score</th><th>Status</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $sub):
              $score_color = $sub['match_score'] >= 70 ? 'var(--success)' : ($sub['match_score'] >= 40 ? 'var(--accent)' : 'var(--muted)');
            ?>
            <tr>
              <td class="fw-600"><?= htmlspecialchars($sub['opp_title']) ?></td>
              <td class="td-muted"><?= htmlspecialchars($sub['company']) ?></td>
              <td style="text-align:center"><span style="font-weight:700;color:<?= $score_color ?>"><?= $sub['match_score'] ?></span></td>
              <td><span class="badge <?= $status_badge[$sub['status']] ?>"><?= ucfirst($sub['status']) ?></span></td>
              <td class="td-muted"><?= date('d M Y', strtotime($sub['submitted_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- CV & Skills -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">CV &amp; Skills</span>
        <?php if (!empty($cv_data['cv_file'])): ?>
        <div style="display:flex;gap:.5rem">
          <?php if (strtolower(pathinfo($cv_data['cv_file'], PATHINFO_EXTENSION)) === 'pdf'): ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="openCvPreview('/gate-portal/uploads/cvs/<?= htmlspecialchars($cv_data['cv_file']) ?>')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview CV
          </button>
          <?php endif; ?>
          <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($cv_data['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">Download</a>
        </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($cv_data['summary'])): ?>
      <div style="margin-bottom:1rem">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem">Summary</div>
        <p class="text-sm" style="line-height:1.7;color:var(--text-2)"><?= nl2br(htmlspecialchars($cv_data['summary'])) ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($cv_data['skills'])): ?>
      <div style="margin-bottom:.75rem">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem">Skills</div>
        <div style="display:flex;flex-wrap:wrap;gap:.3rem">
          <?php foreach (array_filter(array_map('trim', explode(',', $cv_data['skills']))) as $sk): ?>
          <span class="badge badge-info" style="font-size:.72rem"><?= htmlspecialchars($sk) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!empty($cv_data['certifications'])): ?>
      <div style="margin-bottom:.75rem">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem">Certifications</div>
        <div class="text-sm"><?= htmlspecialchars($cv_data['certifications']) ?></div>
      </div>
      <?php endif; ?>
      <?php if (!empty($cv_data['languages'])): ?>
      <div>
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem">Languages</div>
        <div class="text-sm"><?= htmlspecialchars($cv_data['languages']) ?></div>
      </div>
      <?php endif; ?>
      <?php if (empty($cv_data)): ?>
      <div class="empty-state"><p>No CV data on file.</p></div>
      <?php endif; ?>
    </div>

    <!-- Event RSVPs -->
    <?php if ($events): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">Event RSVPs</span>
        <span class="badge badge-secondary"><?= count($events) ?></span>
      </div>
      <div style="display:flex;flex-direction:column;gap:.4rem">
        <?php foreach ($events as $ev): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem .75rem;background:var(--bg);border-radius:var(--r)">
          <div>
            <div class="text-sm fw-600"><?= htmlspecialchars($ev['title']) ?></div>
            <div class="text-xs text-muted"><?= $ev['location'] ? htmlspecialchars($ev['location']).' &middot; ' : '' ?><?= date('d M Y', strtotime($ev['event_date'])) ?></div>
          </div>
          <span class="badge badge-info" style="font-size:.65rem">RSVP'd</span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- CV Preview Modal -->
<div id="cv-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--radius);width:90vw;max-width:900px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-bottom:1px solid var(--border);flex-shrink:0">
      <span class="fw-600">CV Preview — <?= htmlspecialchars($alumni['full_name']) ?></span>
      <div style="display:flex;gap:.5rem">
        <a id="cv-download" href="#" target="_blank" class="btn btn-outline btn-sm">Download</a>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeCvPreview()">✕ Close</button>
      </div>
    </div>
    <iframe id="cv-frame" src="" style="flex:1;border:none;width:100%"></iframe>
  </div>
</div>
<script>
function openCvPreview(url) {
    document.getElementById('cv-frame').src = url;
    document.getElementById('cv-download').href = url;
    const m = document.getElementById('cv-modal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeCvPreview() {
    document.getElementById('cv-modal').style.display = 'none';
    document.getElementById('cv-frame').src = '';
    document.body.style.overflow = '';
}
document.getElementById('cv-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCvPreview();
});
</script>
