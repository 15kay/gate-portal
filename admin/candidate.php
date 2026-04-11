<?php
require_once '../includes/auth_guard.php';
require_once '../config/db.php';

// Allow admin roles OR employer role
$role = $_SESSION['role'] ?? '';
$is_employer = ($role === 'employer');
$is_admin    = in_array($role, ['super_admin','admin','reports_admin']);

if (!$is_employer && !$is_admin) {
    header('Location: /gate-portal/index.php'); exit;
}

$uid = intval($_GET['id'] ?? 0);
if (!$uid) { header('Location: /gate-portal/index.php'); exit; }

// If employer, verify this candidate was released to them
if ($is_employer) {
    $emp = $pdo->prepare("SELECT id FROM employers WHERE user_id=?");
    $emp->execute([$_SESSION['user_id']]);
    $emp = $emp->fetchColumn();
    if (!$emp) { header('Location: /gate-portal/employer/shortlist.php'); exit; }

    $allowed = $pdo->prepare("
        SELECT COUNT(*) FROM candidate_submissions cs
        JOIN opportunities o ON o.id=cs.opportunity_id
        WHERE cs.alumni_user_id=? AND o.employer_id=? AND cs.employer_released=1
    ");
    $allowed->execute([$uid, $emp]);
    if (!$allowed->fetchColumn()) { header('Location: /gate-portal/employer/shortlist.php'); exit; }
}

// Load full profile
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.created_at,
           ap.student_id, ap.id_number, ap.phone, ap.gender, ap.location,
           ap.graduation_year, ap.degree, ap.faculty, ap.department,
           ap.profile_photo, ap.bio, ap.linkedin_url
    FROM users u
    LEFT JOIN alumni_profiles ap ON ap.user_id=u.id
    WHERE u.id=? AND u.role='alumni'
");
$stmt->execute([$uid]);
$a = $stmt->fetch();
if (!$a) { header('Location: /gate-portal/index.php'); exit; }

$jobs = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC, start_date DESC");
$jobs->execute([$uid]);
$jobs = $jobs->fetchAll();

$cv = $pdo->prepare("SELECT * FROM alumni_cv WHERE user_id=?");
$cv->execute([$uid]);
$cv = $cv->fetch() ?: [];

$current_job = null;
foreach ($jobs as $j) { if ($j['is_current']) { $current_job = $j; break; } }

$back_url = $is_employer ? '/gate-portal/employer/shortlist.php' : '/gate-portal/admin/alumni.php';

$status_colors = [
    'Full-time'       => '#16a34a',
    'Part-time'       => '#d97706',
    'Self-employed'   => '#7c3aed',
    'Freelance'       => '#0891b2',
    'Unemployed'      => '#dc2626',
    'Further Studies' => '#5B1C16',
];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1><?= htmlspecialchars($a['full_name']) ?></h1>
    <p>
      <?= htmlspecialchars($a['degree'] ?? 'Alumni') ?>
      <?php if ($a['graduation_year']): ?>&mdash; Class of <?= $a['graduation_year'] ?><?php endif; ?>
    </p>
  </div>
  <div class="page-header-actions">
    <a href="<?= $back_url ?>" class="btn btn-outline btn-sm">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
    <?php if (!empty($cv['cv_file'])): ?>
    <?php if (strtolower(pathinfo($cv['cv_file'], PATHINFO_EXTENSION)) === 'pdf'): ?>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCvPreview('/gate-portal/uploads/cvs/<?= htmlspecialchars($cv['cv_file']) ?>')">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      Preview CV
    </button>
    <?php endif; ?>
    <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($cv['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">Download CV</a>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start">

  <!-- LEFT SIDEBAR -->
  <div style="display:flex;flex-direction:column;gap:1rem">

    <!-- Avatar -->
    <div class="card" style="text-align:center;padding:1.75rem 1.25rem">
      <?php if (!empty($a['profile_photo'])): ?>
      <img src="/gate-portal/<?= htmlspecialchars($a['profile_photo']) ?>"
           style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin:0 auto .75rem;display:block" alt="">
      <?php else: ?>
      <div style="width:96px;height:96px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.2rem;font-weight:700;margin:0 auto .75rem">
        <?= strtoupper(substr($a['full_name'],0,1)) ?>
      </div>
      <?php endif; ?>

      <div class="fw-700" style="font-size:1.05rem"><?= htmlspecialchars($a['full_name']) ?></div>
      <div class="text-sm text-muted" style="margin:.2rem 0"><?= htmlspecialchars($a['email']) ?></div>

      <?php if ($current_job): ?>
      <?php $ec = $status_colors[$current_job['employment_type']] ?? 'var(--muted)'; ?>
      <div style="margin-top:.6rem">
        <span style="display:inline-block;padding:.2rem .7rem;border-radius:99px;font-size:.72rem;font-weight:600;color:#fff;background:<?= $ec ?>">
          <?= htmlspecialchars($current_job['employment_type']) ?>
        </span>
      </div>
      <?php if ($current_job['job_title'] || $current_job['employer']): ?>
      <div class="text-sm" style="margin-top:.4rem">
        <?= htmlspecialchars($current_job['job_title'] ?? '') ?>
        <?php if ($current_job['employer']): ?><span class="text-muted"> at </span><strong><?= htmlspecialchars($current_job['employer']) ?></strong><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>

      <?php if (!empty($a['location'])): ?>
      <div class="text-xs text-muted" style="margin-top:.5rem">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?= htmlspecialchars($a['location']) ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($a['linkedin_url'])): ?>
      <a href="<?= htmlspecialchars($a['linkedin_url']) ?>" target="_blank"
         class="btn btn-outline btn-sm" style="margin-top:.85rem;width:100%;justify-content:center">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        LinkedIn
      </a>
      <?php endif; ?>
    </div>

    <!-- Contact -->
    <div class="card">
      <div class="card-header"><span class="card-title">Contact</span></div>
      <div style="display:flex;flex-direction:column;gap:.75rem">
        <?php if ($a['email']): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.15rem">Email</div>
          <a href="mailto:<?= htmlspecialchars($a['email']) ?>" class="text-sm fw-600" style="color:var(--primary)"><?= htmlspecialchars($a['email']) ?></a>
        </div>
        <?php endif; ?>
        <?php if ($a['phone']): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.15rem">Phone</div>
          <a href="tel:<?= htmlspecialchars($a['phone']) ?>" class="text-sm fw-600"><?= htmlspecialchars($a['phone']) ?></a>
        </div>
        <?php endif; ?>
        <?php if (!empty($a['gender'])): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.15rem">Gender</div>
          <div class="text-sm fw-600"><?= htmlspecialchars($a['gender']) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($is_admin && $a['student_id']): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.15rem">Student ID</div>
          <div class="text-sm fw-600"><?= htmlspecialchars($a['student_id']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Profile score -->
    <?php if (!empty($cv['profile_score'])): ?>
    <?php $sc = (int)$cv['profile_score']; $sc_col = $sc>=80?'var(--success)':($sc>=50?'var(--accent)':'var(--danger)'); ?>
    <div class="card" style="text-align:center;padding:1.25rem">
      <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.08em;margin-bottom:.6rem">Profile Score</div>
      <div style="position:relative;width:80px;height:80px;margin:0 auto .5rem">
        <svg viewBox="0 0 36 36" style="width:80px;height:80px;transform:rotate(-90deg)">
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--border)" stroke-width="3"/>
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="<?= $sc_col ?>" stroke-width="3"
            stroke-dasharray="<?= $sc ?> 100" stroke-linecap="round"/>
        </svg>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">
          <span style="font-size:1.3rem;font-weight:800;color:<?= $sc_col ?>"><?= $sc ?></span>
        </div>
      </div>
      <span class="badge <?= $sc>=80?'badge-success':($sc>=50?'badge-warning':'badge-danger') ?>"><?= $sc>=80?'Strong':($sc>=50?'Good':'Needs Work') ?></span>
    </div>
    <?php endif; ?>

  </div>

  <!-- RIGHT CONTENT -->
  <div style="display:flex;flex-direction:column;gap:1rem">

    <!-- Academic -->
    <div class="card">
      <div class="card-header"><span class="card-title">Academic Information</span></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <?php foreach ([
          ['Degree',          $a['degree']],
          ['Graduation Year', $a['graduation_year']],
          ['Faculty',         $a['faculty'] ?? null],
          ['Department',      $a['department']],
        ] as [$label, $val]): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.2rem"><?= $label ?></div>
          <div class="fw-600"><?= htmlspecialchars($val ?? '—') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($a['bio']): ?>
      <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border)">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem">Bio</div>
        <p class="text-sm" style="line-height:1.7;color:var(--text-2)"><?= nl2br(htmlspecialchars($a['bio'])) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- CV Summary & Skills -->
    <?php if ($cv): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title">CV &amp; Skills</span>
        <?php if (!empty($cv['cv_file'])): ?>
        <div style="display:flex;gap:.5rem">
          <?php if (strtolower(pathinfo($cv['cv_file'], PATHINFO_EXTENSION)) === 'pdf'): ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="openCvPreview('/gate-portal/uploads/cvs/<?= htmlspecialchars($cv['cv_file']) ?>')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview PDF
          </button>
          <?php endif; ?>
          <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($cv['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">Download</a>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($cv['summary'])): ?>
      <div style="margin-bottom:1rem;padding:.85rem 1rem;background:var(--bg);border-radius:var(--r);border-left:3px solid var(--primary)">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.35rem">Professional Summary</div>
        <p class="text-sm" style="line-height:1.7;margin:0"><?= nl2br(htmlspecialchars($cv['summary'])) ?></p>
      </div>
      <?php endif; ?>

      <?php if (!empty($cv['skills'])): ?>
      <div style="margin-bottom:.85rem">
        <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem">Skills</div>
        <div style="display:flex;flex-wrap:wrap;gap:.3rem">
          <?php foreach (array_filter(array_map('trim', explode(',', $cv['skills']))) as $sk): ?>
          <span class="badge badge-info" style="font-size:.72rem"><?= htmlspecialchars($sk) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <?php if (!empty($cv['certifications'])): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Certifications</div>
          <div class="text-sm"><?= htmlspecialchars($cv['certifications']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($cv['languages'])): ?>
        <div>
          <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Languages</div>
          <div class="text-sm"><?= htmlspecialchars($cv['languages']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Employment History -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Employment History</span>
        <span class="badge badge-secondary"><?= count($jobs) ?></span>
      </div>
      <?php if ($jobs): ?>
      <div class="table-wrap" style="border:none;margin:-1px">
        <table>
          <thead><tr><th>Employer</th><th>Role</th><th>Type</th><th>Period</th></tr></thead>
          <tbody>
            <?php foreach ($jobs as $j):
              $tc = match($j['employment_type']) {
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
              <td><span class="badge <?= $tc ?>"><?= htmlspecialchars($j['employment_type']) ?></span></td>
              <td class="td-muted" style="white-space:nowrap">
                <?= $j['start_date'] ? date('M Y', strtotime($j['start_date'])) : '—' ?>
                &ndash;
                <?= $j['end_date'] ? date('M Y', strtotime($j['end_date'])) : ($j['is_current'] ? '<strong>Present</strong>' : '—') ?>
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

  </div>
</div>

<!-- CV Preview Modal -->
<div id="cv-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--radius);width:90vw;max-width:960px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-bottom:1px solid var(--border);flex-shrink:0">
      <span class="fw-600">CV — <?= htmlspecialchars($a['full_name']) ?></span>
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
    document.getElementById('cv-modal').style.display = 'flex';
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

<?php include '../includes/footer.php'; ?>
