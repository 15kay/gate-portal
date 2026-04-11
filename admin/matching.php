<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/audit.php';

// Ensure tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, company VARCHAR(200) NOT NULL,
    industry VARCHAR(100), location VARCHAR(150),
    type ENUM('Full-time','Part-time','Contract','Internship','Freelance') DEFAULT 'Full-time',
    description TEXT, requirements TEXT, deadline DATE,
    status ENUM('open','closed','filled') DEFAULT 'open',
    created_by INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL)");

$pdo->exec("CREATE TABLE IF NOT EXISTS candidate_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY, opportunity_id INT NOT NULL, alumni_user_id INT NOT NULL,
    match_score TINYINT UNSIGNED DEFAULT 0,
    status ENUM('suggested','selected','submitted','accepted','rejected') DEFAULT 'suggested',
    notes TEXT, submitted_by INT, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE(opportunity_id, alumni_user_id),
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (alumni_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by)   REFERENCES users(id) ON DELETE SET NULL)");

$success = $error = '';

// Run matching for an opportunity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_match'])) {
    csrf_verify();
    $opp_id = (int)$_POST['opp_id'];

    $opp = $pdo->prepare("SELECT * FROM opportunities WHERE id=?");
    $opp->execute([$opp_id]);
    $opp = $opp->fetch();

    if ($opp) {
        // Stop-words for tokeniser
        $stop = ['with','that','this','have','from','they','will','your','been','were','their',
                 'about','which','would','there','other','these','those','after','where','while',
                 'must','should','able','good','work','team','role','year','years','using','also'];

        // Tokenise opportunity text into meaningful words (4+ chars, no stop-words)
        $opp_text  = strtolower(($opp['requirements']??'').' '.($opp['description']??'').' '.($opp['industry']??'').' '.($opp['title']??''));
        $req_words = array_unique(array_filter(
            preg_split('/[\s,;.\/\(\)\-\+\[\]]+/', $opp_text),
            fn($w) => strlen($w) >= 4 && !in_array($w, $stop)
        ));
        $opp_ind = strtolower(trim($opp['industry'] ?? ''));

        // Fetch all alumni with at least a degree and phone
        $alumni = $pdo->query("
            SELECT u.id, u.full_name, ap.degree, ap.department, ap.graduation_year,
                   ap.phone, ap.profile_photo, ap.bio,
                   cv.parsed_keywords, cv.skills, cv.cv_file, cv.summary
            FROM users u
            LEFT JOIN alumni_profiles ap ON ap.user_id=u.id
            LEFT JOIN alumni_cv cv ON cv.user_id=u.id
            WHERE u.role='alumni'
              AND ap.degree IS NOT NULL AND ap.degree != ''
              AND ap.phone  IS NOT NULL AND ap.phone  != ''
        ")->fetchAll();

        $inserted = 0;
        foreach ($alumni as $a) {
            $score = 0;
            $breakdown = [];

            // Build alumni keyword set — from parsed_keywords + skills (strip :Level) + degree + bio
            $akw = [];
            foreach (array_filter(explode(',', $a['parsed_keywords'] ?? '')) as $k) {
                $k = strtolower(trim($k));
                if (strlen($k) >= 3) $akw[] = $k;
            }
            foreach (array_filter(array_map('trim', explode(',', $a['skills'] ?? ''))) as $s) {
                $k = strtolower(trim(explode(':', $s)[0]));
                if (strlen($k) >= 3) $akw[] = $k;
            }
            // Also add degree words and bio words as keywords
            foreach (array_filter(explode(' ', strtolower($a['degree'] ?? '')), fn($w) => strlen($w) >= 4) as $w)
                $akw[] = $w;
            foreach (array_filter(explode(' ', strtolower($a['bio'] ?? '')), fn($w) => strlen($w) >= 5) as $w)
                $akw[] = $w;
            foreach (array_filter(explode(' ', strtolower($a['summary'] ?? '')), fn($w) => strlen($w) >= 5) as $w)
                $akw[] = $w;
            $akw = array_unique($akw);

            // 1. Keyword overlap — 40 pts max (4 pts per unique hit)
            $hits = 0;
            foreach ($req_words as $rw) {
                if (strlen($rw) < 4) continue;
                foreach ($akw as $ak) {
                    if (strlen($ak) < 3) continue;
                    if ($ak === $rw
                        || (strlen($ak) >= 4 && str_contains($rw, $ak))
                        || (strlen($rw) >= 4 && str_contains($ak, $rw))) {
                        $hits++; break;
                    }
                }
            }
            $kw_pts = min(40, $hits * 4);
            $score += $kw_pts;

            // 2. Industry match — 15 pts (checks all employment records)
            if (strlen($opp_ind) >= 3) {
                $emp_q = $pdo->prepare("SELECT industry FROM employment_records WHERE user_id=?");
                $emp_q->execute([$a['id']]);
                foreach ($emp_q->fetchAll(PDO::FETCH_COLUMN) as $ji) {
                    $ji = strtolower(trim($ji ?? ''));
                    if (strlen($ji) >= 3 && (str_contains($ji, $opp_ind) || str_contains($opp_ind, $ji))) {
                        $score += 15; break;
                    }
                }
            }

            // 3. Degree relevance — 10 pts
            // Check ALL degree words (4+ chars) against requirements + description
            $opp_full = strtolower(($opp['requirements'] ?? '').' '.($opp['description'] ?? '').' '.($opp['title'] ?? ''));
            if (!empty($a['degree'])) {
                $deg_words = array_filter(explode(' ', strtolower($a['degree'])), fn($w) => strlen($w) >= 4);
                foreach ($deg_words as $dw) {
                    if (str_contains($opp_full, $dw)) {
                        $score += 10; break;
                    }
                }
            }

            // 4. Work experience — 10 pts
            $exp_q = $pdo->prepare("SELECT COUNT(*) FROM employment_records WHERE user_id=? AND employment_type != 'Unemployed'");
            $exp_q->execute([$a['id']]);
            if ($exp_q->fetchColumn() > 0) $score += 10;

            // 5. Skills breadth — up to 10 pts
            $skill_count = count(array_filter(array_map('trim', explode(',', $a['skills'] ?? ''))));
            if ($skill_count >= 10)    $score += 10;
            elseif ($skill_count >= 5) $score += 5;

            // 6. CV uploaded — 5 pts
            if (!empty($a['cv_file'])) $score += 5;

            // 7. Recency — 5 pts (graduated within 7 years)
            if (!empty($a['graduation_year']) && (date('Y') - (int)$a['graduation_year']) <= 7) $score += 5;

            // 8. Profile photo — 5 pts
            if (!empty($a['profile_photo'])) $score += 5;

            $score = min(100, $score);

            if ($score > 0) {
                try {
                    // Always update score on re-run so improvements are reflected
                    $pdo->prepare("INSERT INTO candidate_submissions (opportunity_id,alumni_user_id,match_score,status,submitted_by)
                                   VALUES (?,?,?,'suggested',?)
                                   ON DUPLICATE KEY UPDATE match_score=VALUES(match_score), status=IF(status='suggested','suggested',status)")
                        ->execute([$opp_id, $a['id'], $score, $_SESSION['user_id']]);
                    $inserted++;
                } catch (Throwable $e) {}
            }
        }
        audit_log('run_matching', "opportunity:{$opp_id}", "{$inserted} candidates suggested", 'system');
        $success = "Matching complete — {$inserted} candidates suggested for &ldquo;" . htmlspecialchars($opp['title']) . "&rdquo;.";
    } // end if ($opp)
} // end POST

$open_opps = $pdo->query("SELECT id, title, company FROM opportunities WHERE status='open' ORDER BY created_at DESC")->fetchAll();

// Load suggestions grouped by opportunity
$opp_id_view = (int)($_GET['opp'] ?? ($open_opps[0]['id'] ?? 0));
$suggestions = [];
if ($opp_id_view) {
    $suggestions = $pdo->prepare("
        SELECT cs.*, u.full_name, u.email, ap.degree, ap.department, ap.graduation_year,
               er.employment_type, er.employer, er.industry AS emp_industry
        FROM candidate_submissions cs
        JOIN users u ON u.id=cs.alumni_user_id
        LEFT JOIN alumni_profiles ap ON ap.user_id=cs.alumni_user_id
        LEFT JOIN employment_records er ON er.user_id=cs.alumni_user_id AND er.is_current=1
        WHERE cs.opportunity_id=? AND cs.status='suggested'
        ORDER BY cs.match_score DESC
    ");
    $suggestions->execute([$opp_id_view]);
    $suggestions = $suggestions->fetchAll();
}

$current_opp = null;
if ($opp_id_view) {
    $s = $pdo->prepare("SELECT * FROM opportunities WHERE id=?");
    $s->execute([$opp_id_view]);
    $current_opp = $s->fetch();
}

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Candidate Matching</h1>
    <p>Auto-match alumni to open opportunities based on skills and experience</p>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$open_opps): ?>
<div class="card"><div class="empty-state">
  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
  <p>No open opportunities. <a href="/gate-portal/admin/opportunities.php">Create one first.</a></p>
</div></div>
<?php else: ?>

<div style="display:grid;grid-template-columns:260px 1fr;gap:1.5rem;align-items:start">

  <!-- LEFT: opportunity list + run match -->
  <div>
    <div class="card">
      <div class="card-header"><span class="card-title">Open Opportunities</span></div>
      <div style="display:flex;flex-direction:column;gap:.3rem">
        <?php foreach ($open_opps as $o): ?>
        <a href="?opp=<?= $o['id'] ?>"
           style="display:block;padding:.6rem .75rem;border-radius:var(--r);text-decoration:none;font-size:.85rem;font-weight:<?= $opp_id_view===$o['id']?'600':'400' ?>;background:<?= $opp_id_view===$o['id']?'#f9ece9':'transparent' ?>;color:<?= $opp_id_view===$o['id']?'var(--primary)':'var(--text-2)' ?>">
          <?= htmlspecialchars($o['title']) ?>
          <div style="font-size:.72rem;color:var(--muted)"><?= htmlspecialchars($o['company']) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($current_opp): ?>
    <div class="card">
      <div class="card-header"><span class="card-title">Run Matching</span></div>
      <p class="text-sm text-muted" style="margin-bottom:1rem">
        Automatically score and suggest alumni for <strong><?= htmlspecialchars($current_opp['title']) ?></strong>.
      </p>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="opp_id" value="<?= $current_opp['id'] ?>">
        <button name="run_match" value="1" class="btn btn-primary" style="width:100%;justify-content:center">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Run Auto-Match
        </button>
      </form>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Score Breakdown</span></div>
      <div style="display:flex;flex-direction:column;gap:.4rem;font-size:.8rem">
        <?php foreach ([
          ['Keyword overlap',  '40 pts', 'Skills &amp; CV keywords vs requirements'],
          ['Industry match',   '15 pts', 'Employment industry matches opportunity'],
          ['Degree relevance', '10 pts', 'Degree words found in requirements'],
          ['Work experience',  '10 pts', 'Has any non-unemployed record'],
          ['Skills breadth',   '10 pts', '5+ skills = 5 pts, 10+ = 10 pts'],
          ['CV uploaded',       '5 pts', 'Has a CV file on record'],
          ['Recent graduate',   '5 pts', 'Graduated within 7 years'],
          ['Profile photo',     '5 pts', 'Has a profile photo set'],
        ] as [$factor, $pts, $desc]): ?>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;padding:.35rem .5rem;background:var(--bg);border-radius:var(--r)">
          <div>
            <div class="fw-600"><?= $factor ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= $desc ?></div>
          </div>
          <span class="badge badge-secondary" style="flex-shrink:0"><?= $pts ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: suggestions -->
  <div>
    <?php if ($current_opp): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem">
      <div>
        <div class="fw-700" style="font-size:1rem"><?= htmlspecialchars($current_opp['title']) ?> — <?= htmlspecialchars($current_opp['company']) ?></div>
        <div class="text-sm text-muted"><?= count($suggestions) ?> suggested candidates</div>
      </div>
      <a href="/gate-portal/admin/candidate_selection.php?opp=<?= $current_opp['id'] ?>" class="btn btn-accent btn-sm">
        Go to Selection &rarr;
      </a>
    </div>
    <?php endif; ?>

    <?php if (!$suggestions): ?>
    <div class="card"><div class="empty-state">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <p>No suggestions yet. Run auto-match to generate candidates.</p>
    </div></div>
    <?php else: ?>
    <div class="card" style="padding:0">
      <div class="table-wrap" style="border:none">
        <table>
          <thead>
            <tr><th>Alumni</th><th>Degree</th><th>Employment</th><th>Industry</th><th>Match Score</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($suggestions as $s):
              $sc  = (int)$s['match_score'];
              $col = $sc >= 70 ? 'var(--success)' : ($sc >= 40 ? 'var(--accent)' : 'var(--danger)');
              $lbl = $sc >= 70 ? 'Strong' : ($sc >= 40 ? 'Partial' : 'Low');
              $bdg = $sc >= 70 ? 'badge-success' : ($sc >= 40 ? 'badge-warning' : 'badge-danger');
            ?>
            <tr>
              <td>
                <div class="fw-600"><?= htmlspecialchars($s['full_name']) ?></div>
                <div class="td-muted"><?= htmlspecialchars($s['email']) ?></div>
              </td>
              <td>
                <div class="text-sm"><?= htmlspecialchars($s['degree'] ?? '—') ?></div>
                <div class="td-muted"><?= $s['graduation_year'] ?? '' ?></div>
              </td>
              <td><span class="badge badge-secondary"><?= htmlspecialchars($s['employment_type'] ?? '—') ?></span></td>
              <td class="td-muted"><?= htmlspecialchars($s['emp_industry'] ?? '—') ?></td>
              <td style="min-width:140px">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem">
                  <span style="font-size:1rem;font-weight:800;color:<?= $col ?>"><?= $sc ?></span>
                  <span class="text-xs text-muted">/100</span>
                  <span class="badge <?= $bdg ?>" style="font-size:.65rem"><?= $lbl ?></span>
                </div>
                <div style="height:5px;background:var(--border);border-radius:99px;overflow:hidden;max-width:120px">
                  <div style="height:100%;width:<?= $sc ?>%;background:<?= $col ?>;border-radius:99px"></div>
                </div>
                <div class="text-xs text-muted" style="margin-top:.25rem">
                  <?php
                  // Show what contributed to the score
                  $factors = [];
                  if (!empty($s['emp_industry']) && !empty($current_opp['industry']) &&
                      (stripos($s['emp_industry'], $current_opp['industry']) !== false ||
                       stripos($current_opp['industry'], $s['emp_industry']) !== false))
                      $factors[] = 'industry';
                  if (!empty($s['degree']) && !empty($current_opp['requirements']) &&
                      stripos($current_opp['requirements'], explode(' ', $s['degree'])[0]) !== false)
                      $factors[] = 'degree';
                  if (!empty($s['employment_type']) && $s['employment_type'] !== 'Unemployed')
                      $factors[] = 'experience';
                  echo $factors ? implode(' · ', $factors) : 'keyword match';
                  ?>
                </div>
              </td>
              <td>
                <a href="/gate-portal/admin/candidate.php?id=<?= $s['alumni_user_id'] ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
