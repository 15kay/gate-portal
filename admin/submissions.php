<?php
require_once '../includes/auth_guard.php';
require_min_role('reports_admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/audit.php';
require_once '../includes/settings.php';
require_once '../includes/mailer.php';

$readonly = !can_submit_candidates();
$success  = $error = '';

if (!$readonly && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // ── Submit selected → submitted + release to employer ─────────────────────
    if (isset($_POST['submit_candidates'])) {
        $opp_id    = (int)$_POST['opp_id'];
        $rel_notes = trim($_POST['release_notes'] ?? '');

        $affected = $pdo->prepare("
            UPDATE candidate_submissions
            SET status='submitted', submitted_by=?, submitted_at=NOW(),
                employer_released=1, released_at=NOW(), released_by=?, release_notes=?
            WHERE opportunity_id=? AND status='selected'
        ");
        $affected->execute([$_SESSION['user_id'], $_SESSION['user_id'], $rel_notes, $opp_id]);
        $count = $affected->rowCount();

        audit_log('submit_and_release', "opportunity:{$opp_id}", "{$count} candidates");
        header('Location: /gate-portal/admin/submissions.php?opp='.$opp_id.'&msg=submitted&n='.$count);
        exit;
    }

    // ── Record outcome (accepted / rejected) ──────────────────────────────────
    if (isset($_POST['update_outcome'])) {
        $cs_id  = (int)$_POST['cs_id'];
        $status = $_POST['outcome'] ?? '';
        if (in_array($status, ['accepted', 'rejected'])) {
            $pdo->prepare("UPDATE candidate_submissions SET status=?, updated_at=NOW() WHERE id=?")
                ->execute([$status, $cs_id]);
            audit_log('outcome_'.$status, "submission:{$cs_id}");
        }
        header('Location: /gate-portal/admin/submissions.php?opp='.(int)($_POST['opp_id'] ?? 0).'&msg=updated');
        exit;
    }

    // ── Release already-submitted candidates to employer (re-release) ─────────
    if (isset($_POST['release_to_employer'])) {
        $opp_id    = (int)$_POST['opp_id'];
        $rel_notes = trim($_POST['release_notes'] ?? '');

        $pdo->prepare("
            UPDATE candidate_submissions
            SET employer_released=1, released_at=NOW(), released_by=?, release_notes=?
            WHERE opportunity_id=? AND status IN ('submitted','selected','accepted')
        ")->execute([$_SESSION['user_id'], $rel_notes, $opp_id]);

        // Email employer
        $opp = $pdo->prepare("
            SELECT o.*, e.company_name, e.contact_email, e.user_id AS emp_user_id
            FROM opportunities o LEFT JOIN employers e ON e.id=o.employer_id WHERE o.id=?
        ");
        $opp->execute([$opp_id]);
        $opp = $opp->fetch();

        $cands = $pdo->prepare("
            SELECT u.full_name, u.email, ap.degree, ap.graduation_year,
                   er.employment_type, cv.cv_file, cs.match_score
            FROM candidate_submissions cs
            JOIN users u ON u.id=cs.alumni_user_id
            LEFT JOIN alumni_profiles ap ON ap.user_id=cs.alumni_user_id
            LEFT JOIN employment_records er ON er.user_id=cs.alumni_user_id AND er.is_current=1
            LEFT JOIN alumni_cv cv ON cv.user_id=cs.alumni_user_id
            WHERE cs.opportunity_id=? AND cs.employer_released=1
            ORDER BY cs.match_score DESC
        ");
        $cands->execute([$opp_id]);
        $cands = $cands->fetchAll();

        if (($opp['contact_email'] ?? '') && $cands) {
            $zip_path = sys_get_temp_dir().'/shortlist_'.$opp_id.'_'.time().'.zip';
            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE)) {
                $cv_dir = dirname(__DIR__).'/uploads/cvs/';
                foreach ($cands as $c) {
                    if (!empty($c['cv_file']) && file_exists($cv_dir.$c['cv_file'])) {
                        $safe = preg_replace('/[^a-zA-Z0-9._-]/','_',$c['full_name']);
                        $ext  = pathinfo($c['cv_file'], PATHINFO_EXTENSION);
                        $zip->addFile($cv_dir.$c['cv_file'], "CVs/{$safe}.{$ext}");
                    }
                }
                $zip->close();
            }
            $rows_html = '';
            foreach ($cands as $i => $c) {
                $rows_html .= "<tr style='background:".($i%2?'#f9f9f9':'#fff')."'>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>".htmlspecialchars($c['full_name'])."</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>".htmlspecialchars($c['degree']??'—')."</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>".($c['graduation_year']??'—')."</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee;text-align:center'><strong>".$c['match_score']."%</strong></td>
                </tr>";
            }
            $portal_name = setting('portal_name','GATE Portal');
            $portal_url  = 'http://'.($_SERVER['HTTP_HOST']??'localhost').'/gate-portal';
            $html = "<div style='font-family:Arial,sans-serif;max-width:680px;margin:0 auto'>
              <div style='background:#5B1C16;padding:24px 32px;border-radius:8px 8px 0 0'>
                <h2 style='color:#fff;margin:0'>{$portal_name}</h2>
                <p style='color:rgba(255,255,255,.7);margin:.4rem 0 0'>Candidate Shortlist</p>
              </div>
              <div style='background:#fff;padding:28px 32px;border:1px solid #e4e4e7;border-top:none'>
                <p>Dear <strong>".htmlspecialchars($opp['company_name']??'Employer')."</strong>,</p>
                <p>Shortlisted candidates for: <strong>".htmlspecialchars($opp['title'])."</strong></p>
                ".($rel_notes?"<p style='color:#52525b'><em>{$rel_notes}</em></p>":'')."
                <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:.9rem'>
                  <thead><tr style='background:#5B1C16;color:#fff'>
                    <th style='padding:10px 12px;text-align:left'>Name</th>
                    <th style='padding:10px 12px;text-align:left'>Degree</th>
                    <th style='padding:10px 12px;text-align:left'>Grad Year</th>
                    <th style='padding:10px 12px;text-align:center'>Match</th>
                  </tr></thead>
                  <tbody>{$rows_html}</tbody>
                </table>
                <p>CVs attached. Log in to view full profiles: <a href='{$portal_url}/auth/login.php'>{$portal_url}/auth/login.php</a></p>
              </div>
            </div>";
            $attachments = file_exists($zip_path) ? [['path'=>$zip_path,'name'=>'Shortlist.zip','mime'=>'application/zip']] : [];
            send_mail($opp['contact_email'], "Shortlisted Candidates — ".$opp['title'], $html, $attachments);
            if (file_exists($zip_path)) @unlink($zip_path);
        }

        audit_log('release_shortlist', "opportunity:{$opp_id}");
        header('Location: /gate-portal/admin/submissions.php?opp='.$opp_id.'&msg=released');
        exit;
    }
}

// Flash messages
$msg_map = [
    'submitted' => fn() => 'Candidates submitted and released to employer' . (isset($_GET['n']) ? ' ('.(int)$_GET['n'].' candidate(s)).' : '.'),
    'updated'   => fn() => 'Outcome recorded successfully.',
    'released'  => fn() => 'Shortlist released to employer.',
];
if (isset($_GET['msg'], $msg_map[$_GET['msg']])) $success = ($msg_map[$_GET['msg']])();

// ── Load opportunities that have ANY non-suggested submission ─────────────────
$opps = $pdo->query("
    SELECT DISTINCT o.id, o.title, o.company, o.status AS opp_status,
           e.contact_email, e.user_id AS emp_user_id, e.company_name
    FROM opportunities o
    JOIN candidate_submissions cs ON cs.opportunity_id = o.id
        AND cs.status IN ('selected','submitted','accepted','rejected')
    LEFT JOIN employers e ON e.id = o.employer_id
    ORDER BY o.created_at DESC
")->fetchAll();

$opp_id = (int)($_GET['opp'] ?? ($opps[0]['id'] ?? 0));

$rows        = [];
$current_opp = null;
$counts      = ['selected'=>0,'submitted'=>0,'accepted'=>0,'rejected'=>0];

if ($opp_id) {
    foreach ($opps as $o) { if ((int)$o['id'] === $opp_id) { $current_opp = $o; break; } }

    // If not found in tabs (e.g. direct URL), still load it
    if (!$current_opp) {
        $s = $pdo->prepare("SELECT o.*, e.contact_email, e.user_id AS emp_user_id, e.company_name FROM opportunities o LEFT JOIN employers e ON e.id=o.employer_id WHERE o.id=?");
        $s->execute([$opp_id]);
        $current_opp = $s->fetch() ?: null;
    }

    if ($current_opp) {
        $stmt = $pdo->prepare("
            SELECT cs.id, cs.alumni_user_id, cs.match_score, cs.status, cs.notes,
                   cs.employer_released, cs.released_at, cs.release_notes,
                   cs.interview_scheduled_at, cs.interview_type,
                   u.full_name, u.email,
                   ap.degree, ap.graduation_year, ap.profile_photo,
                   er.employment_type, er.employer AS current_employer, er.job_title,
                   cv.cv_file, cv.skills, cv.summary,
                   sub.full_name AS submitted_by_name
            FROM candidate_submissions cs
            JOIN users u ON u.id = cs.alumni_user_id
            LEFT JOIN alumni_profiles ap ON ap.user_id = cs.alumni_user_id
            LEFT JOIN employment_records er ON er.user_id = cs.alumni_user_id AND er.is_current = 1
            LEFT JOIN alumni_cv cv ON cv.user_id = cs.alumni_user_id
            LEFT JOIN users sub ON sub.id = cs.submitted_by
            WHERE cs.opportunity_id = ?
              AND cs.status IN ('selected','submitted','accepted','rejected')
            ORDER BY cs.match_score DESC
        ");
        $stmt->execute([$opp_id]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $r) {
            if (isset($counts[$r['status']])) $counts[$r['status']]++;
        }
    }
}

$selected_count = $counts['selected'];

$status_badge = [
    'selected'  => 'badge-warning',
    'submitted' => 'badge-info',
    'accepted'  => 'badge-success',
    'rejected'  => 'badge-danger',
];
$score_color = fn($s) => $s >= 70 ? 'var(--success)' : ($s >= 40 ? 'var(--accent)' : 'var(--muted)');

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Submissions</h1>
    <p>Submit shortlisted candidates to employers and track outcomes</p>
  </div>
  <?php if ($current_opp): ?>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/candidate_selection.php?opp=<?= $opp_id ?>" class="btn btn-outline btn-sm">← Candidate Selection</a>
  </div>
  <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$opps): ?>
<div class="card"><div class="empty-state">
  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
  <p>No candidates have been selected yet.</p>
  <p class="text-xs text-muted">Go to <a href="/gate-portal/admin/candidate_selection.php">Candidate Selection</a> to select candidates first.</p>
</div></div>
<?php else: ?>

<!-- Opportunity tabs -->
<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.25rem">
  <?php foreach ($opps as $o): ?>
  <a href="?opp=<?= $o['id'] ?>" class="btn btn-sm <?= (int)$o['id']===$opp_id?'btn-primary':'btn-outline' ?>">
    <?= htmlspecialchars($o['title']) ?>
    <span class="text-xs" style="opacity:.65">— <?= htmlspecialchars($o['company_name'] ?: $o['company']) ?></span>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($current_opp): ?>

<!-- Status counts -->
<div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.25rem">
  <?php foreach ([
    ['Selected',  $counts['selected'],  'badge-warning'],
    ['Submitted', $counts['submitted'], 'badge-info'],
    ['Accepted',  $counts['accepted'],  'badge-success'],
    ['Rejected',  $counts['rejected'],  'badge-danger'],
  ] as [$label, $val, $badge]): ?>
  <div style="background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:.45rem .9rem;display:flex;align-items:center;gap:.5rem">
    <span class="badge <?= $badge ?>"><?= $val ?></span>
    <span class="text-sm text-muted"><?= $label ?></span>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!$readonly && $selected_count > 0): ?>
<!-- Submit & Release panel -->
<div class="card" style="margin-bottom:1.25rem;border-left:4px solid var(--accent)">
  <div class="card-header">
    <span class="card-title">Submit &amp; Release to Employer</span>
    <span class="badge badge-warning"><?= $selected_count ?> candidate<?= $selected_count>1?'s':'' ?> ready</span>
  </div>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="opp_id" value="<?= $opp_id ?>">
    <div class="form-group">
      <label>Message to Employer <span class="text-muted text-xs">(optional)</span></label>
      <textarea name="release_notes" rows="2" placeholder="e.g. Please review the candidates and arrange interviews at your earliest convenience."></textarea>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
      <span class="text-sm text-muted">
        <strong><?= $selected_count ?> candidate<?= $selected_count>1?'s':'' ?></strong> will be marked <em>submitted</em> and made visible in the employer portal immediately.
      </span>
      <button name="submit_candidates" value="1" class="btn btn-primary"
              onclick="return confirm('Submit and release <?= $selected_count ?> candidate(s) to the employer?')">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
        Submit &amp; Release to Employer
      </button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Candidate rows -->
<?php if (!$rows): ?>
<div class="card"><div class="empty-state"><p>No submissions for this opportunity yet.</p></div></div>
<?php else: ?>

<div class="card" style="padding:0">
  <div class="table-wrap" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Alumni</th>
          <th>Degree</th>
          <th style="text-align:center">Score</th>
          <th>CV</th>
          <th>Status</th>
          <th>Released</th>
          <?php if (!$readonly): ?><th style="text-align:right">Outcome</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r):
          $sc  = (int)$r['match_score'];
          $col = $score_color($sc);
        ?>
        <tr style="<?= $r['status']==='rejected'?'opacity:.55':'' ?>">
          <td>
            <div style="display:flex;align-items:center;gap:.6rem">
              <?php
              $photo = $r['profile_photo'] ?? '';
              if ($photo): ?>
              <img src="/gate-portal/<?= htmlspecialchars($photo) ?>"
                   style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid var(--border);flex-shrink:0" alt="">
              <?php else: ?>
              <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;font-weight:700;flex-shrink:0">
                <?= strtoupper(substr($r['full_name'],0,1)) ?>
              </div>
              <?php endif; ?>
              <div>
                <div class="fw-600" style="font-size:.875rem"><?= htmlspecialchars($r['full_name']) ?></div>
                <div class="td-muted"><?= htmlspecialchars($r['email']) ?></div>
                <?php if ($r['current_employer']): ?>
                <div class="td-muted" style="font-size:.72rem"><?= htmlspecialchars($r['job_title']??'') ?><?= $r['job_title']?' at ':'' ?><?= htmlspecialchars($r['current_employer']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <div class="text-sm"><?= htmlspecialchars($r['degree'] ?? '—') ?></div>
            <div class="td-muted"><?= $r['graduation_year'] ?? '' ?></div>
          </td>
          <td style="text-align:center">
            <div style="display:inline-flex;flex-direction:column;align-items:center">
              <span style="font-weight:800;font-size:1rem;color:<?= $col ?>;line-height:1"><?= $sc ?></span>
              <span style="font-size:.6rem;color:var(--muted)">/ 100</span>
            </div>
          </td>
          <td>
            <?php if (!empty($r['cv_file'])): ?>
              <?php if (strtolower(pathinfo($r['cv_file'],PATHINFO_EXTENSION))==='pdf'): ?>
              <button type="button" class="btn btn-outline btn-sm" style="font-size:.72rem"
                      onclick="openCv('/gate-portal/uploads/cvs/<?= htmlspecialchars($r['cv_file']) ?>')">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                CV
              </button>
              <?php else: ?>
              <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($r['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm" style="font-size:.72rem">CV</a>
              <?php endif; ?>
            <?php else: ?>
            <span class="text-xs text-muted">No CV</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $status_badge[$r['status']] ?>"><?= ucfirst($r['status']) ?></span>
            <?php if ($r['interview_scheduled_at']): ?>
            <div style="margin-top:.3rem;display:inline-flex;align-items:center;gap:.3rem;background:#eff6ff;border-radius:var(--r);padding:.2rem .5rem">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <span class="text-xs fw-600" style="color:var(--primary)"><?= date('d M Y', strtotime($r['interview_scheduled_at'])) ?></span>
            </div>
            <div class="text-xs text-muted"><?= htmlspecialchars($r['interview_type'] ?? '') ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($r['employer_released']): ?>
            <div style="display:flex;align-items:center;gap:.3rem">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="text-xs" style="color:var(--success);font-weight:600">Released</span>
            </div>
            <div class="text-xs text-muted"><?= $r['released_at'] ? date('d M Y', strtotime($r['released_at'])) : '' ?></div>
            <?php else: ?>
            <span class="text-xs text-muted">—</span>
            <?php endif; ?>
          </td>
          <?php if (!$readonly): ?>
          <td style="text-align:right">
            <?php if (in_array($r['status'], ['submitted','accepted','rejected'])): ?>
            <div style="display:flex;gap:.3rem;justify-content:flex-end;flex-wrap:wrap">
              <?php if ($r['status'] !== 'accepted'): ?>
              <form method="POST" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="update_outcome" value="1">
                <input type="hidden" name="cs_id"   value="<?= $r['id'] ?>">
                <input type="hidden" name="opp_id"  value="<?= $opp_id ?>">
                <input type="hidden" name="outcome" value="accepted">
                <button class="btn btn-success btn-sm" style="font-size:.75rem">✓ Accepted</button>
              </form>
              <?php endif; ?>
              <?php if ($r['status'] !== 'rejected'): ?>
              <form method="POST" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="update_outcome" value="1">
                <input type="hidden" name="cs_id"   value="<?= $r['id'] ?>">
                <input type="hidden" name="opp_id"  value="<?= $opp_id ?>">
                <input type="hidden" name="outcome" value="rejected">
                <button class="btn btn-outline btn-sm" style="font-size:.75rem">✗ Rejected</button>
              </form>
              <?php endif; ?>
            </div>
            <?php elseif ($r['status'] === 'selected'): ?>
            <span class="text-xs text-muted">Submit first</span>
            <?php else: ?>
            <span class="text-xs text-muted">—</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<!-- CV Preview Modal -->
<div id="cv-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--r-lg);width:90vw;max-width:960px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-bottom:1px solid var(--border);flex-shrink:0">
      <span class="fw-600">CV Preview</span>
      <div style="display:flex;gap:.5rem">
        <a id="cv-dl" href="#" target="_blank" class="btn btn-outline btn-sm">Download</a>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeCv()">✕ Close</button>
      </div>
    </div>
    <iframe id="cv-frame" src="" style="flex:1;border:none;width:100%"></iframe>
  </div>
</div>

<script>
function openCv(url) {
    document.getElementById('cv-frame').src = url;
    document.getElementById('cv-dl').href   = url;
    document.getElementById('cv-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeCv() {
    document.getElementById('cv-modal').style.display = 'none';
    document.getElementById('cv-frame').src = '';
    document.body.style.overflow = '';
}
document.getElementById('cv-modal').addEventListener('click', e => { if (e.target===e.currentTarget) closeCv(); });
</script>

<?php include '../includes/footer.php'; ?>
