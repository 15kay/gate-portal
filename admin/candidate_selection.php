<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/audit.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['update_status'])) {
        $cs_id  = (int)$_POST['cs_id'];
        $status = $_POST['status'];
        $notes  = trim($_POST['notes'] ?? '');
        if (in_array($status, ['selected','rejected'])) {
            // Block selection if candidate has no CV
            if ($status === 'selected') {
                $cv_check = $pdo->prepare("SELECT cv.cv_file FROM candidate_submissions cs JOIN alumni_cv cv ON cv.user_id=cs.alumni_user_id WHERE cs.id=? AND cv.cv_file IS NOT NULL AND cv.cv_file != ''");
                $cv_check->execute([$cs_id]);
                if (!$cv_check->fetch()) {
                    $error = 'Cannot select a candidate without a CV on file.';
                    goto render;
                }
            }
            $pdo->prepare("UPDATE candidate_submissions SET status=?, notes=? WHERE id=?")
                ->execute([$status, $notes, $cs_id]);
            audit_log('candidate_'.$status, "submission:{$cs_id}");
            $opp_qs = !empty($_POST['opp_id']) ? '?opp='.(int)$_POST['opp_id'].'&msg=saved' : '?msg=saved';
            header('Location: /gate-portal/admin/candidate_selection.php'.$opp_qs); exit;
        }
    }
}

render:
if (isset($_GET['msg'])) $success = 'Candidate status updated.';

$open_opps = $pdo->query("SELECT id, title, company FROM opportunities WHERE status='open' ORDER BY created_at DESC")->fetchAll();
$opp_id    = (int)($_GET['opp'] ?? ($open_opps[0]['id'] ?? 0));

$candidates  = [];
$current_opp = null;
if ($opp_id) {
    $s = $pdo->prepare("SELECT * FROM opportunities WHERE id=?");
    $s->execute([$opp_id]);
    $current_opp = $s->fetch();

    $candidates = $pdo->prepare("
        SELECT cs.id, cs.alumni_user_id, cs.match_score, cs.status, cs.notes,
               u.full_name, u.email,
               ap.degree, ap.department, ap.graduation_year, ap.profile_photo,
               ap.phone, ap.linkedin_url, ap.location,
               er.employment_type, er.employer, er.job_title,
               cv.cv_file, cv.skills, cv.summary
        FROM candidate_submissions cs
        JOIN users u ON u.id=cs.alumni_user_id
        LEFT JOIN alumni_profiles ap ON ap.user_id=cs.alumni_user_id
        LEFT JOIN employment_records er ON er.user_id=cs.alumni_user_id AND er.is_current=1
        LEFT JOIN alumni_cv cv ON cv.user_id=cs.alumni_user_id
        WHERE cs.opportunity_id=?
        ORDER BY cs.match_score DESC, cs.status
    ");
    $candidates->execute([$opp_id]);
    $candidates = $candidates->fetchAll();
}

// Counts
$counts = ['suggested'=>0,'selected'=>0,'rejected'=>0,'submitted'=>0,'accepted'=>0];
foreach ($candidates as $c) $counts[$c['status']] = ($counts[$c['status']] ?? 0) + 1;

$status_badge = [
    'suggested' => 'badge-secondary',
    'selected'  => 'badge-success',
    'submitted' => 'badge-info',
    'accepted'  => 'badge-success',
    'rejected'  => 'badge-danger',
];
$score_color = fn($s) => $s >= 70 ? 'var(--success)' : ($s >= 40 ? 'var(--accent)' : 'var(--danger)');

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Candidate Selection</h1>
    <p>Review matched candidates and select or reject them for submission</p>
  </div>
  <?php if ($current_opp): ?>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/matching.php?opp=<?= $opp_id ?>" class="btn btn-outline btn-sm">Re-run Matching</a>
    <a href="/gate-portal/admin/submissions.php?opp=<?= $opp_id ?>" class="btn btn-primary btn-sm">
      Submit Selected →
    </a>
  </div>
  <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$open_opps): ?>
<div class="card"><div class="empty-state"><p>No open opportunities. <a href="/gate-portal/admin/opportunities.php">Create one first.</a></p></div></div>
<?php else: ?>

<!-- Opportunity tabs -->
<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.25rem">
  <?php foreach ($open_opps as $o): ?>
  <a href="?opp=<?= $o['id'] ?>" class="btn btn-sm <?= $opp_id===$o['id']?'btn-primary':'btn-outline' ?>">
    <?= htmlspecialchars($o['title']) ?>
    <span class="text-xs" style="opacity:.7">— <?= htmlspecialchars($o['company']) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($current_opp): ?>

<!-- Stats bar -->
<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.25rem">
  <?php foreach ([
    ['Suggested', $counts['suggested'], 'badge-secondary'],
    ['Selected',  $counts['selected'],  'badge-success'],
    ['Rejected',  $counts['rejected'],  'badge-danger'],
    ['Submitted', $counts['submitted'], 'badge-info'],
  ] as [$label, $val, $badge]): ?>
  <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.5rem .9rem;display:flex;align-items:center;gap:.5rem">
    <span class="badge <?= $badge ?>"><?= $val ?></span>
    <span class="text-sm text-muted"><?= $label ?></span>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!$candidates): ?>
<div class="card"><div class="empty-state">
  <p>No candidates yet. <a href="/gate-portal/admin/matching.php?opp=<?= $opp_id ?>">Run matching first.</a></p>
</div></div>
<?php else: ?>

<?php foreach ($candidates as $c):
  $sc = $c['match_score'];
  $col = $score_color($sc);
  // Strip :Level from skills
  $skills_clean = array_filter(array_map(fn($s) => trim(explode(':', trim($s))[0]), explode(',', $c['skills'] ?? '')));
?>
<div class="card" style="margin-bottom:1rem;<?= $c['status']==='rejected'?'opacity:.55':'' ?>">
  <div style="display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap">

    <!-- Avatar -->
    <div style="flex-shrink:0;text-align:center;width:60px">
      <?php if (!empty($c['profile_photo'])): ?>
      <img src="/gate-portal/<?= htmlspecialchars($c['profile_photo']) ?>"
           style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);display:block;margin:0 auto" alt="">
      <?php else: ?>
      <div style="width:52px;height:52px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;font-weight:700;margin:0 auto">
        <?= strtoupper(substr($c['full_name'],0,1)) ?>
      </div>
      <?php endif; ?>
      <!-- Score circle -->
      <div style="margin-top:.4rem;width:52px;height:52px;border-radius:50%;border:3px solid <?= $col ?>;display:flex;align-items:center;justify-content:center;flex-direction:column;margin:0 auto">
        <span style="font-size:.95rem;font-weight:800;color:<?= $col ?>;line-height:1"><?= $sc ?></span>
        <span style="font-size:.5rem;color:var(--muted)">match</span>
      </div>
    </div>

    <!-- Main info -->
    <div style="flex:1;min-width:200px">
      <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem">
        <span class="fw-700" style="font-size:.95rem"><?= htmlspecialchars($c['full_name']) ?></span>
        <span class="badge <?= $status_badge[$c['status']] ?>"><?= ucfirst($c['status']) ?></span>
        <?php if (empty($c['cv_file'])): ?>
        <span class="badge badge-danger" style="font-size:.65rem">No CV</span>
        <?php endif; ?>
      </div>
      <div class="text-sm text-muted" style="margin-bottom:.4rem">
        <?= htmlspecialchars($c['email']) ?>
        <?php if ($c['phone']): ?> · <?= htmlspecialchars($c['phone']) ?><?php endif; ?>
        <?php if ($c['location']): ?> · <?= htmlspecialchars($c['location']) ?><?php endif; ?>
      </div>
      <div class="text-sm" style="margin-bottom:.4rem">
        <strong><?= htmlspecialchars($c['degree'] ?? '—') ?></strong>
        <?php if ($c['graduation_year']): ?> · <?= $c['graduation_year'] ?><?php endif; ?>
        <?php if ($c['department']): ?> · <?= htmlspecialchars($c['department']) ?><?php endif; ?>
      </div>
      <?php if ($c['employer'] || $c['employment_type']): ?>
      <div class="text-sm text-muted" style="margin-bottom:.4rem">
        <?= htmlspecialchars($c['job_title'] ?? $c['employment_type'] ?? '') ?>
        <?php if ($c['employer']): ?> at <strong><?= htmlspecialchars($c['employer']) ?></strong><?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Score bar -->
      <div style="margin:.5rem 0">
        <div style="height:6px;background:var(--border);border-radius:99px;overflow:hidden;max-width:260px">
          <div style="height:100%;width:<?= $sc ?>%;background:<?= $col ?>;border-radius:99px;transition:width .5s"></div>
        </div>
      </div>

      <!-- Skills -->
      <?php if ($skills_clean): ?>
      <div style="display:flex;flex-wrap:wrap;gap:.25rem;margin-top:.35rem">
        <?php foreach (array_slice($skills_clean, 0, 8) as $sk): ?>
        <span class="badge badge-info" style="font-size:.65rem"><?= htmlspecialchars($sk) ?></span>
        <?php endforeach; ?>
        <?php if (count($skills_clean) > 8): ?>
        <span class="badge badge-secondary" style="font-size:.65rem">+<?= count($skills_clean)-8 ?> more</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($c['notes']): ?>
      <div style="margin-top:.5rem;padding:.4rem .65rem;background:var(--bg);border-radius:var(--r);border-left:3px solid var(--accent);font-size:.8rem;color:var(--text-2)">
        <?= htmlspecialchars($c['notes']) ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div style="flex-shrink:0;display:flex;flex-direction:column;gap:.4rem;min-width:130px">
      <a href="/gate-portal/admin/candidate.php?id=<?= $c['alumni_user_id'] ?>" class="btn btn-outline btn-sm" target="_blank">View Profile</a>

      <?php if (!empty($c['cv_file'])): ?>
        <?php if (strtolower(pathinfo($c['cv_file'], PATHINFO_EXTENSION)) === 'pdf'): ?>
        <button type="button" class="btn btn-outline btn-sm"
                onclick="openCvPreview('/gate-portal/uploads/cvs/<?= htmlspecialchars($c['cv_file']) ?>')">Preview CV</button>
        <?php else: ?>
        <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($c['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">Download CV</a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (in_array($c['status'], ['suggested','rejected'])): ?>
        <?php if (empty($c['cv_file'])): ?>
        <span class="text-xs text-muted" style="text-align:center">No CV — cannot select</span>
        <?php else: ?>
        <button type="button" class="btn btn-success btn-sm"
                onclick="openNotesModal(<?= $c['id'] ?>, <?= $opp_id ?>, 'selected', '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')">
          ✓ Select
        </button>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (in_array($c['status'], ['suggested','selected'])): ?>
      <button type="button" class="btn btn-outline btn-sm"
              onclick="openNotesModal(<?= $c['id'] ?>, <?= $opp_id ?>, 'rejected', '<?= htmlspecialchars(addslashes($c['full_name'])) ?>')">
        Reject
      </button>
      <?php endif; ?>
    </div>

  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<!-- Notes modal -->
<div id="notes-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--radius);width:90vw;max-width:480px;padding:1.5rem;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div class="fw-700" style="font-size:1rem;margin-bottom:.25rem" id="notes-modal-title">Add Note</div>
    <div class="text-sm text-muted" style="margin-bottom:1rem" id="notes-modal-sub"></div>
    <form method="POST" id="notes-form">
      <?= csrf_field() ?>
      <input type="hidden" name="update_status" value="1">
      <input type="hidden" name="cs_id"   id="notes-cs-id">
      <input type="hidden" name="opp_id"  id="notes-opp-id">
      <input type="hidden" name="status"  id="notes-status">
      <div class="form-group">
        <label>Note <span class="text-muted text-xs">(optional)</span></label>
        <textarea name="notes" id="notes-text" rows="3" placeholder="e.g. Strong technical background, recommend for interview…"></textarea>
      </div>
      <div style="display:flex;gap:.5rem;justify-content:flex-end">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeNotesModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="notes-submit-btn">Confirm</button>
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
function openNotesModal(csId, oppId, status, name) {
    document.getElementById('notes-cs-id').value  = csId;
    document.getElementById('notes-opp-id').value = oppId;
    document.getElementById('notes-status').value = status;
    document.getElementById('notes-text').value   = '';
    document.getElementById('notes-modal-title').textContent = status === 'selected' ? 'Select Candidate' : 'Reject Candidate';
    document.getElementById('notes-modal-sub').textContent   = name;
    document.getElementById('notes-submit-btn').textContent  = status === 'selected' ? '✓ Confirm Selection' : 'Confirm Rejection';
    document.getElementById('notes-submit-btn').className    = 'btn btn-sm ' + (status === 'selected' ? 'btn-success' : 'btn-danger');
    document.getElementById('notes-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeNotesModal() {
    document.getElementById('notes-modal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('notes-modal').addEventListener('click', function(e) {
    if (e.target === this) closeNotesModal();
});
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
