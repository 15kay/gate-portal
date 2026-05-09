<?php
require_once '../includes/auth_guard.php';
require_role('employer');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/mailer.php';
require_once '../includes/settings.php';

$emp = $pdo->prepare("SELECT * FROM employers WHERE user_id=?");
$emp->execute([$_SESSION['user_id']]);
$emp = $emp->fetch();
if (!$emp) {
    include '../includes/header.php';
    echo '<div class="card"><div class="empty-state"><p>Your employer account is not linked to a company profile yet.</p></div></div>';
    include '../includes/footer.php';
    exit;
}

$success = $error = '';

// ─── Set candidate status (Accept / Reject / Reset) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
    csrf_verify();
    $cs_id     = (int)$_POST['cs_id'];
    $new_stat  = in_array($_POST['candidate_status'], ['submitted','accepted','rejected'])
                 ? $_POST['candidate_status'] : 'submitted';
    $_opp_r  = (int)($_POST['opp_id']  ?? 0);
    $_filt_r = preg_replace('/[^a-z]/', '', $_POST['filter'] ?? '');
    $_sort_r = preg_replace('/[^a-z]/', '', $_POST['sort']   ?? '');
    $chk = $pdo->prepare("SELECT cs.id FROM candidate_submissions cs JOIN opportunities o ON o.id=cs.opportunity_id WHERE cs.id=? AND (o.employer_id=? OR o.company=?) AND cs.employer_released=1");
    $chk->execute([$cs_id, $emp['id'], $emp['company_name']]);
    if ($chk->fetch()) {
        $pdo->prepare("UPDATE candidate_submissions SET status=? WHERE id=?")->execute([$new_stat, $cs_id]);
    }
    header('Location: /gate-portal/employer/shortlist.php?opp='.$_opp_r
           .($_filt_r ? "&filter={$_filt_r}" : '')
           .($_sort_r ? "&sort={$_sort_r}"   : ''));
    exit;
}

// ─── Save private employer note ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_note'])) {
    csrf_verify();
    $cs_id     = (int)$_POST['cs_id'];
    $note_val  = trim($_POST['employer_note'] ?? '');
    $_opp_r    = (int)($_POST['opp_id'] ?? 0);
    $_filt_r   = preg_replace('/[^a-z]/', '', $_POST['filter'] ?? '');
    $_sort_r   = preg_replace('/[^a-z]/', '', $_POST['sort']   ?? '');
    $chk = $pdo->prepare("SELECT cs.id FROM candidate_submissions cs JOIN opportunities o ON o.id=cs.opportunity_id WHERE cs.id=? AND (o.employer_id=? OR o.company=?) AND cs.employer_released=1");
    $chk->execute([$cs_id, $emp['id'], $emp['company_name']]);
    if ($chk->fetch()) {
        $pdo->prepare("UPDATE candidate_submissions SET employer_notes=? WHERE id=?")->execute([$note_val ?: null, $cs_id]);
    }
    header('Location: /gate-portal/employer/shortlist.php?opp='.$_opp_r
           .($_filt_r ? "&filter={$_filt_r}" : '')
           .($_sort_r ? "&sort={$_sort_r}"   : '').'&note_saved=1');
    exit;
}

// Handle interview scheduling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_interview'])) {
    csrf_verify();
    $cs_id    = (int)$_POST['cs_id'];
    $dt       = $_POST['interview_datetime'] ?? '';
    $type     = $_POST['interview_type'] ?? 'In-person';
    $location = trim($_POST['interview_location'] ?? '');
    $notes    = trim($_POST['interview_notes'] ?? '');

    // Verify submission belongs to this employer
    $check = $pdo->prepare("
        SELECT cs.id FROM candidate_submissions cs
        JOIN opportunities o ON o.id=cs.opportunity_id
        WHERE cs.id=? AND (o.employer_id=? OR o.company=?) AND cs.employer_released=1
    ");
    $check->execute([$cs_id, $emp['id'], $emp['company_name']]);
    if ($check->fetch()) {
        $pdo->prepare("UPDATE candidate_submissions SET interview_scheduled_at=?, interview_type=?, interview_location=?, interview_notes=? WHERE id=?")
            ->execute([$dt ?: null, $type, $location, $notes, $cs_id]);

        // Fetch candidate + opportunity details for emails
        $detail = $pdo->prepare("
            SELECT u.full_name, u.email, o.title AS opp_title, o.company,
                   adm.email AS admin_email
            FROM candidate_submissions cs
            JOIN users u ON u.id = cs.alumni_user_id
            JOIN opportunities o ON o.id = cs.opportunity_id
            LEFT JOIN users adm ON adm.role = 'admin'
            WHERE cs.id = ?
            LIMIT 1
        ");
        $detail->execute([$cs_id]);
        $d = $detail->fetch();

        if ($d) {
            $portal_name = setting('portal_name', 'GATE Portal');
            $fmt_dt      = $dt ? date('d M Y \a\t H:i', strtotime($dt)) : 'TBC';
            $loc_line    = $location ? "<p><strong>Location/Link:</strong> ".htmlspecialchars($location)."</p>" : '';
            $notes_line  = $notes    ? "<p><strong>Notes:</strong> ".htmlspecialchars($notes)."</p>" : '';

            // Email to candidate
            $cand_html = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
              <div style='background:#5B1C16;padding:20px 28px;border-radius:8px 8px 0 0'>
                <h2 style='color:#fff;margin:0'>{$portal_name}</h2>
              </div>
              <div style='background:#fff;padding:24px 28px;border:1px solid #e4e4e7;border-top:none'>
                <p>Dear <strong>".htmlspecialchars($d['full_name'])."</strong>,</p>
                <p>An interview has been scheduled for you regarding the position: <strong>".htmlspecialchars($d['opp_title'])."</strong> at <strong>".htmlspecialchars($d['company'])."</strong>.</p>
                <p><strong>Date &amp; Time:</strong> {$fmt_dt}</p>
                <p><strong>Type:</strong> ".htmlspecialchars($type)."</p>
                {$loc_line}{$notes_line}
                <p>Please log in to the portal to view full details.</p>
              </div>
            </div>";
            send_mail($d['email'], "Interview Scheduled — ".htmlspecialchars($d['opp_title']), $cand_html);

            // Email to admin
            if ($d['admin_email']) {
                $admin_html = "<div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
                  <div style='background:#5B1C16;padding:20px 28px;border-radius:8px 8px 0 0'>
                    <h2 style='color:#fff;margin:0'>{$portal_name} — Interview Notification</h2>
                  </div>
                  <div style='background:#fff;padding:24px 28px;border:1px solid #e4e4e7;border-top:none'>
                    <p>An interview has been scheduled by <strong>".htmlspecialchars($emp['company_name'])."</strong>.</p>
                    <p><strong>Candidate:</strong> ".htmlspecialchars($d['full_name'])." (".htmlspecialchars($d['email']).").</p>
                    <p><strong>Position:</strong> ".htmlspecialchars($d['opp_title'])."</p>
                    <p><strong>Date &amp; Time:</strong> {$fmt_dt}</p>
                    <p><strong>Type:</strong> ".htmlspecialchars($type)."</p>
                    {$loc_line}
                  </div>
                </div>";
                send_mail($d['admin_email'], "Interview Scheduled — ".htmlspecialchars($d['opp_title']), $admin_html);
            }
        }
    }
    header('Location: /gate-portal/employer/shortlist.php?opp=' . (int)($_POST['opp_id'] ?? 0) . '&msg=scheduled');
    exit;
}

if (isset($_GET['msg'])        && $_GET['msg']        === 'scheduled')  $success = 'Interview scheduled successfully.';
if (isset($_GET['note_saved']) && $_GET['note_saved'] === '1')          $success = 'Note saved.';

$cand_filter = preg_replace('/[^a-z]/', '', $_GET['filter'] ?? '');
$cand_sort   = preg_replace('/[^a-z]/', '', $_GET['sort']   ?? '');

// Jobs with released candidates
$opps = $pdo->prepare("
    SELECT o.id, o.title, o.type, o.location, o.deadline, o.status,
           COUNT(DISTINCT cs.id) AS candidate_count,
           MAX(cs.released_at) AS last_released
    FROM opportunities o
    JOIN candidate_submissions cs ON cs.opportunity_id=o.id
    WHERE (o.employer_id=? OR o.company=?) AND cs.employer_released=1
    GROUP BY o.id
    ORDER BY last_released DESC
");
$opps->execute([$emp['id'], $emp['company_name']]);
$opps = $opps->fetchAll();

$opp_id = (int)($_GET['opp'] ?? ($opps[0]['id'] ?? 0));

$candidates  = [];
$current_opp = null;
if ($opp_id) {
    $s = $pdo->prepare("SELECT * FROM opportunities WHERE id=? AND (employer_id=? OR company=?)");
    $s->execute([$opp_id, $emp['id'], $emp['company_name']]);
    $current_opp = $s->fetch();
    if (!$current_opp) { header('Location: /gate-portal/employer/shortlist.php'); exit; }

    // Status counts for filter tabs
    $sc_q = $pdo->prepare("
        SELECT COUNT(*) AS total,
               SUM(cs.status='submitted' AND cs.interview_scheduled_at IS NULL) AS pending,
               SUM(cs.interview_scheduled_at IS NOT NULL) AS interviewed,
               SUM(cs.status='accepted')  AS accepted,
               SUM(cs.status='rejected')  AS rejected
        FROM candidate_submissions cs
        WHERE cs.opportunity_id=? AND cs.employer_released=1
    ");
    $sc_q->execute([$opp_id]);
    $status_counts = $sc_q->fetch();

    // Dynamic filter & sort
    $filter_where = match($cand_filter) {
        'pending'     => " AND cs.status='submitted' AND cs.interview_scheduled_at IS NULL",
        'interviewed' => " AND cs.interview_scheduled_at IS NOT NULL",
        'accepted'    => " AND cs.status='accepted'",
        'rejected'    => " AND cs.status='rejected'",
        default       => '',
    };
    $order_by = match($cand_sort) {
        'name'      => "u.full_name ASC",
        'date'      => "cs.released_at DESC",
        'interview' => "ISNULL(cs.interview_scheduled_at) ASC, cs.interview_scheduled_at ASC",
        default     => "cs.match_score DESC",
    };

    $candidates = $pdo->prepare("
        SELECT u.id AS user_id, u.full_name, u.email,
               ap.degree, ap.department, ap.graduation_year, ap.phone, ap.linkedin_url, ap.profile_photo,
               er.job_title, er.employer AS current_employer, er.employment_type,
               cv.skills, cv.summary, cv.cv_file,
               cs.id AS cs_id, cs.status AS cs_status, cs.match_score, cs.released_at, cs.release_notes,
               cs.interview_scheduled_at, cs.interview_type, cs.interview_location, cs.interview_notes,
               cs.employer_notes
        FROM candidate_submissions cs
        JOIN users u ON u.id=cs.alumni_user_id
        LEFT JOIN alumni_profiles ap ON ap.user_id=cs.alumni_user_id
        LEFT JOIN employment_records er ON er.user_id=cs.alumni_user_id AND er.is_current=1
        LEFT JOIN alumni_cv cv ON cv.user_id=cs.alumni_user_id
        WHERE cs.opportunity_id=? AND cs.employer_released=1{$filter_where}
        ORDER BY {$order_by}
    ");
    $candidates->execute([$opp_id]);
    $candidates = $candidates->fetchAll();
}

$score_color = fn($s) => $s >= 70 ? 'var(--success)' : ($s >= 40 ? 'var(--accent)' : 'var(--muted)');

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Shortlisted Candidates</h1>
    <p>Candidates approved and released by the WSU Alumni Office</p>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if (!$opps): ?>
<div class="card">
  <div class="empty-state">
    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <p>No shortlists have been released yet.</p>
    <p class="text-xs text-muted">The alumni office will notify you when candidates are ready for your review.</p>
  </div>
</div>
<?php else: ?>

<!-- Job tabs with edit buttons -->
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;align-items:center">
  <?php foreach ($opps as $o): ?>
  <div style="display:flex;align-items:center;gap:.2rem">
    <a href="?opp=<?= $o['id'] ?>" class="btn btn-sm <?= $opp_id===$o['id']?'btn-primary':'btn-outline' ?>">
      <?= htmlspecialchars($o['title']) ?>
      <span class="badge badge-secondary" style="margin-left:.3rem"><?= $o['candidate_count'] ?></span>
    </a>
    <a href="/gate-portal/employer/jobs.php?edit=<?= $o['id'] ?>" class="btn btn-outline btn-sm" title="Edit job" style="padding:.3rem .5rem">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($current_opp): ?>

<!-- Opportunity header -->
<div class="card" style="margin-bottom:1.25rem;padding:1rem 1.25rem">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
    <div>
      <div class="fw-700" style="font-size:1rem"><?= htmlspecialchars($current_opp['title']) ?></div>
      <div class="text-sm text-muted">
        <?= htmlspecialchars($current_opp['type']) ?>
        <?php if ($current_opp['location']): ?> &middot; <?= htmlspecialchars($current_opp['location']) ?><?php endif; ?>
        <?php if ($current_opp['deadline']): ?> &middot; Deadline: <?= date('d M Y', strtotime($current_opp['deadline'])) ?><?php endif; ?>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:.75rem">
      <span class="badge badge-success"><?= count($candidates) ?> candidate<?= count($candidates)!=1?'s':'' ?> shortlisted</span>
      <a href="/gate-portal/employer/jobs.php?edit=<?= $current_opp['id'] ?>" class="btn btn-outline btn-sm">Edit Job</a>
    </div>
  </div>
  <?php if (!empty($candidates[0]['release_notes'])): ?>
  <div style="margin-top:.75rem;padding:.65rem .85rem;background:var(--bg);border-radius:var(--r);border-left:3px solid var(--accent)">
    <div class="text-xs fw-600 text-muted" style="margin-bottom:.2rem">Message from Alumni Office</div>
    <div class="text-sm"><?= htmlspecialchars($candidates[0]['release_notes']) ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- Filter + Sort bar -->
<div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem">
  <?php
  $filter_tabs = [
    ''           => ['All',            (int)($status_counts['total']      ?? 0)],
    'pending'    => ['Pending Review', (int)($status_counts['pending']    ?? 0)],
    'interviewed'=> ['Interviewed',    (int)($status_counts['interviewed']?? 0)],
    'accepted'   => ['Accepted',       (int)($status_counts['accepted']   ?? 0)],
    'rejected'   => ['Rejected',       (int)($status_counts['rejected']   ?? 0)],
  ];
  foreach ($filter_tabs as $fval => [$flabel, $fcount]):
    $active = $cand_filter === $fval;
  ?>
  <a href="?opp=<?= $opp_id ?>&filter=<?= $fval ?>&sort=<?= htmlspecialchars($cand_sort) ?>"
     class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline' ?>">
    <?= $flabel ?>
    <?php if ($fcount > 0): ?>
    <span class="badge <?= $active ? 'badge-secondary' : 'badge-secondary' ?>" style="margin-left:.25rem"><?= $fcount ?></span>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <div style="margin-left:auto">
    <select onchange="location.href='?opp=<?= $opp_id ?>&filter=<?= htmlspecialchars($cand_filter) ?>&sort='+this.value"
            style="font-size:.8rem;padding:.3rem .65rem;border:1px solid var(--border);border-radius:var(--r);background:#fff;cursor:pointer">
      <option value="score"     <?= (!$cand_sort||$cand_sort==='score')    ?'selected':'' ?>>Sort: Best Match</option>
      <option value="name"      <?= $cand_sort==='name'      ?'selected':'' ?>>Sort: Name A–Z</option>
      <option value="date"      <?= $cand_sort==='date'      ?'selected':'' ?>>Sort: Release Date</option>
      <option value="interview" <?= $cand_sort==='interview' ?'selected':'' ?>>Sort: Interview Date</option>
    </select>
  </div>
</div>

<?php if ($candidates): ?>
<?php $cand_data = []; ?>
<?php foreach ($candidates as $c):
  $sc  = $c['match_score'];
  $col = $score_color($sc);
  $score_label     = $sc >= 70 ? 'Strong Match' : ($sc >= 40 ? 'Partial Match' : 'Low Match');
  $score_badge_cls = $sc >= 70 ? 'badge-success' : ($sc >= 40 ? 'badge-warning' : 'badge-danger');
  $skills_clean    = array_values(array_filter(array_map(fn($s) => trim(explode(':', trim($s))[0]), explode(',', $c['skills'] ?? ''))));
  $has_interview   = !empty($c['interview_scheduled_at']);
  $int_dt_input    = $has_interview ? date('Y-m-d\TH:i', strtotime($c['interview_scheduled_at'])) : '';
  $cand_data[$c['cs_id']] = [
    'cs_id'       => $c['cs_id'],
    'opp_id'      => $opp_id,
    'name'        => $c['full_name'],
    'photo'       => $c['profile_photo'] ?? '',
    'email'       => $c['email'],
    'phone'       => $c['phone'] ?? '',
    'linkedin'    => $c['linkedin_url'] ?? '',
    'degree'      => $c['degree'] ?? '',
    'dept'        => $c['department'] ?? '',
    'grad_year'   => (string)($c['graduation_year'] ?? ''),
    'job_title'   => $c['job_title'] ?? '',
    'employer'    => $c['current_employer'] ?? '',
    'emp_type'    => $c['employment_type'] ?? '',
    'score'       => (int)$c['match_score'],
    'skills'      => $skills_clean,
    'summary'     => $c['summary'] ?? '',
    'cv_file'     => $c['cv_file'] ?? '',
    'released'    => $c['released_at'] ? date('d M Y', strtotime($c['released_at'])) : '',
    'int_dt'      => $has_interview ? date('d M Y, H:i', strtotime($c['interview_scheduled_at'])) : '',
    'int_dt_input'=> $int_dt_input,
    'int_type'    => $c['interview_type'] ?? '',
    'int_loc'     => $c['interview_location'] ?? '',
    'int_notes'    => $c['interview_notes'] ?? '',
    'cs_status'    => $c['cs_status'],
    'employer_note'=> $c['employer_notes'] ?? '',
  ];
?>
<div class="card" style="margin-bottom:1rem">
  <div style="display:flex;align-items:flex-start;gap:1.25rem;flex-wrap:wrap">

    <!-- Avatar + score -->
    <div style="flex-shrink:0;text-align:center;width:64px">
      <?php $photo = $c['profile_photo'] ?? ''; ?>
      <?php if ($photo): ?>
      <img src="/gate-portal/<?= htmlspecialchars($photo) ?>"
           style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);display:block;margin:0 auto .4rem" alt="">
      <?php else: ?>
      <div style="width:52px;height:52px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;font-weight:700;margin:0 auto .4rem">
        <?= strtoupper(substr($c['full_name'],0,1)) ?>
      </div>
      <?php endif; ?>
      <div style="width:52px;height:52px;border-radius:50%;border:3px solid <?= $col ?>;display:flex;align-items:center;justify-content:center;flex-direction:column;margin:0 auto">
        <span style="font-size:.95rem;font-weight:800;color:<?= $col ?>;line-height:1"><?= $sc ?></span>
        <span style="font-size:.5rem;color:var(--muted)">match</span>
      </div>
    </div>

    <!-- Info -->
    <div style="flex:1;min-width:220px">
      <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem">
        <span class="fw-700" style="font-size:1rem"><?= htmlspecialchars($c['full_name']) ?></span>
        <span class="badge <?= $score_badge_cls ?>"><?= $score_label ?></span>
        <?php if ($has_interview): ?><span class="badge badge-info">Interview Scheduled</span><?php endif; ?>
        <?php if ($c['cs_status'] === 'accepted'): ?>
        <span class="badge badge-success">✓ Accepted</span>
        <?php elseif ($c['cs_status'] === 'rejected'): ?>
        <span class="badge badge-danger">✗ Rejected</span>
        <?php endif; ?>
      </div>
      <div class="text-sm text-muted" style="margin-bottom:.4rem">
        <?= htmlspecialchars($c['degree'] ?? '—') ?>
        <?php if ($c['graduation_year']): ?> &middot; Class of <?= $c['graduation_year'] ?><?php endif; ?>
        <?php if ($c['department']): ?> &middot; <?= htmlspecialchars($c['department']) ?><?php endif; ?>
      </div>

      <div style="height:5px;background:var(--border);border-radius:99px;overflow:hidden;max-width:240px;margin-bottom:.6rem">
        <div style="height:100%;width:<?= $sc ?>%;background:<?= $col ?>;border-radius:99px"></div>
      </div>

      <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:.5rem">
        <a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="display:flex;align-items:center;gap:.3rem;color:var(--primary);font-size:.82rem;text-decoration:none;font-weight:600">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          <?= htmlspecialchars($c['email']) ?>
        </a>
        <?php if ($c['phone']): ?>
        <a href="tel:<?= htmlspecialchars($c['phone']) ?>" style="display:flex;align-items:center;gap:.3rem;color:var(--text-2);font-size:.82rem;text-decoration:none">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          <?= htmlspecialchars($c['phone']) ?>
        </a>
        <?php endif; ?>
        <?php if ($c['linkedin_url']): ?>
        <a href="<?= htmlspecialchars($c['linkedin_url']) ?>" target="_blank" style="display:flex;align-items:center;gap:.3rem;color:#0077b5;font-size:.82rem;text-decoration:none">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
          LinkedIn
        </a>
        <?php endif; ?>
      </div>

      <?php if ($c['current_employer'] || $c['employment_type']): ?>
      <div class="text-sm" style="margin-bottom:.5rem">
        <?= htmlspecialchars($c['job_title'] ?? $c['employment_type'] ?? '') ?>
        <?php if ($c['current_employer']): ?> at <strong><?= htmlspecialchars($c['current_employer']) ?></strong><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($skills_clean): ?>
      <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-bottom:.5rem">
        <?php foreach (array_slice($skills_clean, 0, 10) as $sk): ?>
        <span class="badge badge-info" style="font-size:.68rem"><?= htmlspecialchars($sk) ?></span>
        <?php endforeach; ?>
        <?php if (count($skills_clean) > 10): ?><span class="badge badge-secondary" style="font-size:.68rem">+<?= count($skills_clean)-10 ?> more</span><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($c['summary']): ?>
      <div class="text-sm text-muted" style="line-height:1.6;font-style:italic">
        "<?= htmlspecialchars(mb_substr($c['summary'], 0, 200)) ?><?= strlen($c['summary'])>200?'…':'' ?>"
      </div>
      <?php endif; ?>

      <!-- Private employer note -->
      <?php if ($c['employer_notes']): ?>
      <div style="margin-top:.5rem;padding:.5rem .75rem;background:#fefce8;border-radius:var(--r);border-left:3px solid #ca8a04;display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem">
        <div>
          <div class="text-xs fw-600" style="color:#92400e;margin-bottom:.1rem">Private Note</div>
          <div class="text-sm" style="color:#78350f"><?= htmlspecialchars($c['employer_notes']) ?></div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" style="flex-shrink:0;font-size:.72rem"
                onclick="openNotesModal(<?= $c['cs_id'] ?>, <?= $opp_id ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')">Edit</button>
      </div>
      <?php endif; ?>

      <!-- Scheduled interview details -->
      <?php if ($has_interview): ?>
      <div style="margin-top:.75rem;padding:.6rem .85rem;background:#eff6ff;border-radius:var(--r);border-left:3px solid var(--primary)">
        <div class="text-xs fw-600" style="color:var(--primary);margin-bottom:.25rem">Scheduled Interview</div>
        <div class="text-sm">
          <strong><?= htmlspecialchars($c['interview_type']) ?></strong>
          &middot; <?= date('d M Y, H:i', strtotime($c['interview_scheduled_at'])) ?>
          <?php if ($c['interview_location']): ?> &middot; <?= htmlspecialchars($c['interview_location']) ?><?php endif; ?>
        </div>
        <?php if ($c['interview_notes']): ?>
        <div class="text-xs text-muted" style="margin-top:.2rem"><?= htmlspecialchars($c['interview_notes']) ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div style="flex-shrink:0;display:flex;flex-direction:column;gap:.4rem;min-width:140px">
      <button type="button" class="btn btn-primary btn-sm"
              onclick="openProfileModal(<?= $c['cs_id'] ?>)">View Profile</button>
      <?php if (!empty($c['cv_file'])): ?>
        <?php if (strtolower(pathinfo($c['cv_file'], PATHINFO_EXTENSION)) === 'pdf'): ?>
        <button type="button" class="btn btn-outline btn-sm"
                onclick="openCvPreview('/gate-portal/uploads/cvs/<?= htmlspecialchars($c['cv_file']) ?>')">Preview CV</button>
        <?php else: ?>
        <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($c['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">Download CV</a>
        <?php endif; ?>
      <?php else: ?>
        <span class="text-xs text-muted" style="text-align:center">No CV on file</span>
      <?php endif; ?>
      <button type="button" class="btn btn-sm <?= $has_interview ? 'btn-outline' : 'btn-success' ?>"
              onclick="openInterviewModal(
                <?= $c['cs_id'] ?>, <?= $opp_id ?>,
                '<?= htmlspecialchars(addslashes($c['full_name'])) ?>',
                '<?= $int_dt_input ?>',
                '<?= htmlspecialchars(addslashes($c['interview_type'] ?? 'In-person')) ?>',
                '<?= htmlspecialchars(addslashes($c['interview_location'] ?? '')) ?>',
                '<?= htmlspecialchars(addslashes($c['interview_notes'] ?? '')) ?>')">
        <?= $has_interview ? 'Reschedule' : 'Schedule Interview' ?>
      </button>

      <div style="height:1px;background:var(--border-light);margin:.2rem 0"></div>

      <?php if ($c['cs_status'] === 'accepted'): ?>
        <div class="text-xs fw-600 text-center" style="color:var(--success);padding:.25rem 0">✓ Accepted</div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="set_status" value="1">
          <input type="hidden" name="cs_id"     value="<?= $c['cs_id'] ?>">
          <input type="hidden" name="opp_id"    value="<?= $opp_id ?>">
          <input type="hidden" name="filter"    value="<?= htmlspecialchars($cand_filter) ?>">
          <input type="hidden" name="sort"      value="<?= htmlspecialchars($cand_sort) ?>">
          <input type="hidden" name="candidate_status" value="submitted">
          <button class="btn btn-outline btn-sm" style="width:100%;font-size:.75rem">Reset Status</button>
        </form>
      <?php elseif ($c['cs_status'] === 'rejected'): ?>
        <div class="text-xs fw-600 text-center" style="color:var(--danger);padding:.25rem 0">✗ Rejected</div>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="set_status" value="1">
          <input type="hidden" name="cs_id"     value="<?= $c['cs_id'] ?>">
          <input type="hidden" name="opp_id"    value="<?= $opp_id ?>">
          <input type="hidden" name="filter"    value="<?= htmlspecialchars($cand_filter) ?>">
          <input type="hidden" name="sort"      value="<?= htmlspecialchars($cand_sort) ?>">
          <input type="hidden" name="candidate_status" value="submitted">
          <button class="btn btn-outline btn-sm" style="width:100%;font-size:.75rem">Reset Status</button>
        </form>
      <?php else: ?>
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="set_status" value="1">
          <input type="hidden" name="cs_id"     value="<?= $c['cs_id'] ?>">
          <input type="hidden" name="opp_id"    value="<?= $opp_id ?>">
          <input type="hidden" name="filter"    value="<?= htmlspecialchars($cand_filter) ?>">
          <input type="hidden" name="sort"      value="<?= htmlspecialchars($cand_sort) ?>">
          <input type="hidden" name="candidate_status" value="accepted">
          <button class="btn btn-success btn-sm" style="width:100%">Accept</button>
        </form>
        <form method="POST" onsubmit="return confirm('Reject <?= htmlspecialchars(addslashes($c['full_name'])) ?>?')">
          <?= csrf_field() ?>
          <input type="hidden" name="set_status" value="1">
          <input type="hidden" name="cs_id"     value="<?= $c['cs_id'] ?>">
          <input type="hidden" name="opp_id"    value="<?= $opp_id ?>">
          <input type="hidden" name="filter"    value="<?= htmlspecialchars($cand_filter) ?>">
          <input type="hidden" name="sort"      value="<?= htmlspecialchars($cand_sort) ?>">
          <input type="hidden" name="candidate_status" value="rejected">
          <button class="btn btn-danger btn-sm" style="width:100%">Reject</button>
        </form>
      <?php endif; ?>

      <button type="button" class="btn btn-outline btn-sm" style="width:100%"
              onclick="openNotesModal(<?= $c['cs_id'] ?>, <?= $opp_id ?>, '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')">
        <?= $c['employer_notes'] ? '📝 Edit Note' : '+ Add Note' ?>
      </button>
    </div>

  </div>
</div>
<?php endforeach; ?>
<script>var CANDIDATES=<?= json_encode($cand_data, JSON_HEX_TAG|JSON_HEX_AMP) ?>;</script>

<?php else: ?>
<div class="card"><div class="empty-state">
  <p>No candidates with a CV have been shortlisted for this position yet.</p>
</div></div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<!-- Private Notes Modal -->
<div id="notes-modal" style="display:none;position:fixed;inset:0;z-index:9997;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--r-lg);width:90vw;max-width:440px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div class="fw-700" style="font-size:1rem;margin-bottom:.2rem">Private Note</div>
    <div class="text-sm text-muted" style="margin-bottom:1rem" id="notes-candidate-name"></div>
    <form method="POST" id="notes-form">
      <?= csrf_field() ?>
      <input type="hidden" name="save_note" value="1">
      <input type="hidden" name="cs_id"  id="notes-cs-id">
      <input type="hidden" name="opp_id" id="notes-opp-id">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($cand_filter) ?>">
      <input type="hidden" name="sort"   value="<?= htmlspecialchars($cand_sort) ?>">
      <div class="form-group">
        <label>Note <span class="text-xs text-muted">(only visible to you, never shared with candidates)</span></label>
        <textarea name="employer_note" id="notes-text" rows="4"
                  placeholder="Your private hiring notes about this candidate…" style="resize:vertical"></textarea>
      </div>
      <div style="display:flex;gap:.5rem;justify-content:flex-end">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeNotesModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm">Save Note</button>
      </div>
    </form>
  </div>
</div>

<!-- Candidate Profile Modal -->
<style>
.pm-section { display:none }
.pm-label { font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:.35rem }
</style>
<div id="profile-modal" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.55);align-items:flex-start;justify-content:center;padding:1.5rem 1rem;overflow-y:auto">
  <div style="background:#fff;border-radius:12px;width:100%;max-width:620px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden">

    <!-- Header -->
    <div style="background:var(--primary);padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem">
      <div id="pm-avatar" style="width:58px;height:58px;border-radius:50%;flex-shrink:0;overflow:hidden;border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:#fff;background:rgba(255,255,255,.15)"></div>
      <div style="flex:1;min-width:0">
        <div id="pm-name" style="color:#fff;font-size:1.05rem;font-weight:700;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
        <div id="pm-edu" style="color:rgba(255,255,255,.65);font-size:.78rem;margin-top:.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
        <div id="pm-badge" style="margin-top:.35rem"></div>
      </div>
      <div style="flex-shrink:0;text-align:center;min-width:54px">
        <div id="pm-score-num" style="font-size:1.8rem;font-weight:800;color:#fff;line-height:1"></div>
        <div style="font-size:.6rem;color:rgba(255,255,255,.5);letter-spacing:.4px;text-transform:uppercase">match</div>
        <div id="pm-score-bar" style="height:3px;border-radius:99px;margin-top:.3rem"></div>
      </div>
      <button type="button" onclick="closeProfileModal()"
              style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:50%;width:30px;height:30px;cursor:pointer;font-size:1.1rem;line-height:1;flex-shrink:0">&times;</button>
    </div>

    <!-- Body -->
    <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1.1rem;max-height:65vh;overflow-y:auto">

      <!-- Contact -->
      <div>
        <div class="pm-label">Contact</div>
        <div id="pm-contact" style="display:flex;flex-wrap:wrap;gap:.4rem .9rem"></div>
      </div>

      <!-- Employment -->
      <div id="pm-emp-sec" class="pm-section">
        <div class="pm-label">Current Employment</div>
        <div id="pm-emp" style="font-size:.9rem;line-height:1.5"></div>
      </div>

      <!-- Summary -->
      <div id="pm-sum-sec" class="pm-section">
        <div class="pm-label">Profile Summary</div>
        <p id="pm-sum" style="font-size:.875rem;color:var(--text-2);line-height:1.7;font-style:italic;margin:0"></p>
      </div>

      <!-- Skills -->
      <div id="pm-skills-sec" class="pm-section">
        <div class="pm-label">Skills</div>
        <div id="pm-skills" style="display:flex;flex-wrap:wrap;gap:.3rem"></div>
      </div>

      <!-- Interview -->
      <div id="pm-int-sec" class="pm-section" style="padding:.75rem 1rem;background:#eff6ff;border-radius:var(--r);border-left:3px solid var(--primary)">
        <div class="pm-label" style="color:var(--primary)">Scheduled Interview</div>
        <div id="pm-int" style="font-size:.875rem"></div>
      </div>

      <!-- Released -->
      <div id="pm-released" style="font-size:.72rem;color:var(--muted)"></div>
    </div>

    <!-- Footer -->
    <div style="padding:.875rem 1.5rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap">
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button id="pm-cv-btn"  type="button" class="btn btn-outline btn-sm" style="display:none"></button>
        <button id="pm-int-btn" type="button" class="btn btn-sm"            style="display:none"></button>
      </div>
      <button type="button" class="btn btn-outline btn-sm" onclick="closeProfileModal()">Close</button>
    </div>
  </div>
</div>

<!-- Interview Modal -->
<div id="interview-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--radius);width:90vw;max-width:480px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div class="fw-700" style="font-size:1rem;margin-bottom:.2rem">Schedule Interview</div>
    <div class="text-sm text-muted" style="margin-bottom:1rem" id="int-name"></div>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="schedule_interview" value="1">
      <input type="hidden" name="cs_id"  id="int-cs-id">
      <input type="hidden" name="opp_id" id="int-opp-id">
      <div class="form-row">
        <div class="form-group">
          <label>Date &amp; Time <span style="color:var(--danger)">*</span></label>
          <input type="datetime-local" name="interview_datetime" id="int-datetime" required>
        </div>
        <div class="form-group">
          <label>Type</label>
          <select name="interview_type" id="int-type">
            <option>In-person</option>
            <option>Video call</option>
            <option>Phone call</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Location / Link</label>
        <input type="text" name="interview_location" id="int-location" placeholder="Office address or meeting link">
      </div>
      <div class="form-group">
        <label>Notes <span class="text-muted text-xs">(optional)</span></label>
        <textarea name="interview_notes" id="int-notes" rows="2" placeholder="Instructions for the candidate…"></textarea>
      </div>
      <div style="display:flex;gap:.5rem;justify-content:flex-end">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeInterviewModal()">Cancel</button>
        <button type="submit" class="btn btn-success btn-sm">Confirm Schedule</button>
      </div>
    </form>
  </div>
</div>

<!-- CV Preview Modal -->
<div id="cv-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--radius);width:90vw;max-width:960px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-bottom:1px solid var(--border);flex-shrink:0">
      <span class="fw-600">CV Preview</span>
      <div style="display:flex;gap:.5rem">
        <a id="cv-download" href="#" target="_blank" class="btn btn-outline btn-sm">Download</a>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeCvPreview()">✕ Close</button>
      </div>
    </div>
    <iframe id="cv-frame" src="" style="flex:1;border:none;width:100%"></iframe>
  </div>
</div>

<script>
// ── Private Notes Modal ──────────────────────────────────────────────────────
function openNotesModal(csId, oppId, name) {
    var c = CANDIDATES[csId];
    document.getElementById('notes-cs-id').value          = csId;
    document.getElementById('notes-opp-id').value         = oppId;
    document.getElementById('notes-candidate-name').textContent = name;
    document.getElementById('notes-text').value           = c ? (c.employer_note || '') : '';
    document.getElementById('notes-modal').style.display  = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(function(){ document.getElementById('notes-text').focus(); }, 50);
}
function closeNotesModal() {
    document.getElementById('notes-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('notes-modal').addEventListener('click', function(e){
    if (e.target === e.currentTarget) closeNotesModal();
});

// ── Candidate Profile Modal ──────────────────────────────────────────────────
var CANDIDATES = CANDIDATES || {};

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openProfileModal(csId) {
    var c = CANDIDATES[csId];
    if (!c) return;

    // Photo / initials avatar
    var av = document.getElementById('pm-avatar');
    av.innerHTML = c.photo
        ? '<img src="/gate-portal/'+escHtml(c.photo)+'" style="width:100%;height:100%;object-fit:cover" alt="">'
        : c.name.charAt(0).toUpperCase();

    // Header
    document.getElementById('pm-name').textContent = c.name;
    document.getElementById('pm-edu').textContent =
        [c.degree, c.dept, c.grad_year ? 'Class of '+c.grad_year : ''].filter(Boolean).join(' · ');

    var badgeMap = {'strong':'badge-success','partial':'badge-warning','low':'badge-danger'};
    var scoreKey = c.score >= 70 ? 'strong' : (c.score >= 40 ? 'partial' : 'low');
    var scoreLabel = c.score >= 70 ? 'Strong Match' : (c.score >= 40 ? 'Partial Match' : 'Low Match');
    document.getElementById('pm-badge').innerHTML =
        '<span class="badge '+badgeMap[scoreKey]+'" style="font-size:.68rem">'+scoreLabel+'</span>';

    document.getElementById('pm-score-num').textContent = c.score+'%';
    var barCol = c.score >= 70 ? 'var(--success)' : (c.score >= 40 ? 'var(--accent)' : 'var(--muted)');
    document.getElementById('pm-score-bar').style.background = barCol;

    // Contact links
    var ct = document.getElementById('pm-contact');
    var lnkStyle = 'display:flex;align-items:center;gap:.3rem;font-size:.82rem;text-decoration:none;font-weight:500';
    ct.innerHTML =
        '<a href="mailto:'+escHtml(c.email)+'" style="'+lnkStyle+';color:var(--primary)">'+
        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'+
        escHtml(c.email)+'</a>';
    if (c.phone)
        ct.innerHTML += '<a href="tel:'+escHtml(c.phone)+'" style="'+lnkStyle+';color:var(--text-2)">'+
        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>'+
        escHtml(c.phone)+'</a>';
    if (c.linkedin)
        ct.innerHTML += '<a href="'+escHtml(c.linkedin)+'" target="_blank" style="'+lnkStyle+';color:#0077b5">'+
        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>'+
        'LinkedIn</a>';

    // Employment
    var empSec = document.getElementById('pm-emp-sec');
    if (c.job_title || c.employer || c.emp_type) {
        var parts = [];
        if (c.job_title) parts.push('<strong>'+escHtml(c.job_title)+'</strong>');
        if (c.employer)  parts.push('at <strong>'+escHtml(c.employer)+'</strong>');
        if (c.emp_type)  parts.push('<span style="color:var(--muted)">('+escHtml(c.emp_type)+')</span>');
        document.getElementById('pm-emp').innerHTML = parts.join(' ');
        empSec.style.display = 'block';
    } else { empSec.style.display = 'none'; }

    // Summary
    var sumSec = document.getElementById('pm-sum-sec');
    if (c.summary) {
        document.getElementById('pm-sum').textContent = '\u201C'+c.summary+'\u201D';
        sumSec.style.display = 'block';
    } else { sumSec.style.display = 'none'; }

    // Skills (full list)
    var skillsSec = document.getElementById('pm-skills-sec');
    if (c.skills && c.skills.length) {
        document.getElementById('pm-skills').innerHTML = c.skills
            .map(function(s){ return '<span class="badge badge-info" style="font-size:.7rem">'+escHtml(s)+'</span>'; })
            .join('');
        skillsSec.style.display = 'block';
    } else { skillsSec.style.display = 'none'; }

    // Interview details
    var intSec = document.getElementById('pm-int-sec');
    if (c.int_dt) {
        var intHtml = '<strong>'+escHtml(c.int_type)+'</strong> &middot; '+escHtml(c.int_dt);
        if (c.int_loc)   intHtml += ' &middot; '+escHtml(c.int_loc);
        if (c.int_notes) intHtml += '<div style="font-size:.75rem;color:var(--muted);margin-top:.25rem">'+escHtml(c.int_notes)+'</div>';
        document.getElementById('pm-int').innerHTML = intHtml;
        intSec.style.display = 'block';
    } else { intSec.style.display = 'none'; }

    // Released date
    document.getElementById('pm-released').textContent =
        c.released ? 'Released by alumni office: '+c.released : '';

    // CV button
    var cvBtn = document.getElementById('pm-cv-btn');
    if (c.cv_file) {
        var cvUrl = '/gate-portal/uploads/cvs/'+c.cv_file;
        if (/\.pdf$/i.test(c.cv_file)) {
            cvBtn.textContent = 'Preview CV';
            cvBtn.onclick = function(){ closeProfileModal(); openCvPreview(cvUrl); };
        } else {
            cvBtn.textContent = 'Download CV';
            cvBtn.onclick = function(){ window.open(cvUrl,'_blank'); };
        }
        cvBtn.style.display = 'inline-block';
    } else { cvBtn.style.display = 'none'; }

    // Interview / reschedule button
    var intBtn = document.getElementById('pm-int-btn');
    intBtn.textContent = c.int_dt ? 'Reschedule Interview' : 'Schedule Interview';
    intBtn.className   = 'btn btn-sm '+(c.int_dt ? 'btn-outline' : 'btn-success');
    intBtn.onclick     = function(){
        closeProfileModal();
        openInterviewModal(c.cs_id, c.opp_id, c.name, c.int_dt_input,
                           c.int_type||'In-person', c.int_loc||'', c.int_notes||'');
    };
    intBtn.style.display = 'inline-block';

    document.getElementById('profile-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeProfileModal() {
    document.getElementById('profile-modal').style.display = 'none';
    document.body.style.overflow = '';
}

document.getElementById('profile-modal').addEventListener('click', function(e){
    if (e.target === e.currentTarget) closeProfileModal();
});

// ── Interview Modal ──────────────────────────────────────────────────────────
function openInterviewModal(csId, oppId, name, dt, type, location, notes) {
    document.getElementById('int-cs-id').value  = csId;
    document.getElementById('int-opp-id').value = oppId;
    document.getElementById('int-name').textContent = name;
    document.getElementById('int-datetime').value   = dt;
    document.getElementById('int-location').value   = location;
    document.getElementById('int-notes').value      = notes;
    const sel = document.getElementById('int-type');
    for (let o of sel.options) o.selected = o.value === type;
    document.getElementById('interview-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeInterviewModal() {
    document.getElementById('interview-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('interview-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeInterviewModal(); });
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
document.getElementById('cv-modal').addEventListener('click', e => { if (e.target === e.currentTarget) closeCvPreview(); });
</script>
