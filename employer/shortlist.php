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

if (isset($_GET['msg']) && $_GET['msg'] === 'scheduled') $success = 'Interview scheduled successfully.';

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

    $candidates = $pdo->prepare("
        SELECT u.id AS user_id, u.full_name, u.email,
               ap.degree, ap.department, ap.graduation_year, ap.phone, ap.linkedin_url,
               er.job_title, er.employer AS current_employer, er.employment_type,
               cv.skills, cv.summary, cv.cv_file,
               cs.id AS cs_id, cs.match_score, cs.released_at, cs.release_notes,
               cs.interview_scheduled_at, cs.interview_type, cs.interview_location, cs.interview_notes
        FROM candidate_submissions cs
        JOIN users u ON u.id=cs.alumni_user_id
        LEFT JOIN alumni_profiles ap ON ap.user_id=cs.alumni_user_id
        LEFT JOIN employment_records er ON er.user_id=cs.alumni_user_id AND er.is_current=1
        LEFT JOIN alumni_cv cv ON cv.user_id=cs.alumni_user_id
        WHERE cs.opportunity_id=? AND cs.employer_released=1
        ORDER BY cs.match_score DESC
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

<?php if ($candidates): ?>
<?php foreach ($candidates as $c):
  $sc  = $c['match_score'];
  $col = $score_color($sc);
  $score_label     = $sc >= 70 ? 'Strong Match' : ($sc >= 40 ? 'Partial Match' : 'Low Match');
  $score_badge_cls = $sc >= 70 ? 'badge-success' : ($sc >= 40 ? 'badge-warning' : 'badge-danger');
  $skills_clean    = array_filter(array_map(fn($s) => trim(explode(':', trim($s))[0]), explode(',', $c['skills'] ?? '')));
  $has_interview   = !empty($c['interview_scheduled_at']);
  $int_dt_input    = $has_interview ? date('Y-m-d\TH:i', strtotime($c['interview_scheduled_at'])) : '';
?>
<div class="card" style="margin-bottom:1rem">
  <div style="display:flex;align-items:flex-start;gap:1.25rem;flex-wrap:wrap">

    <!-- Avatar + score -->
    <div style="flex-shrink:0;text-align:center;width:64px">
      <?php
      $photo_q = $pdo->prepare("SELECT profile_photo FROM alumni_profiles WHERE user_id=?");
      $photo_q->execute([$c['user_id']]);
      $photo = $photo_q->fetchColumn();
      ?>
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
    </div>

  </div>
</div>
<?php endforeach; ?>

<?php else: ?>
<div class="card"><div class="empty-state">
  <p>No candidates with a CV have been shortlisted for this position yet.</p>
</div></div>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

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
