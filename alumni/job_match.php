<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';

$uid = $_SESSION['user_id'];

// Load profile
$profile = $pdo->prepare("SELECT ap.*, u.full_name, u.email FROM alumni_profiles ap JOIN users u ON u.id=ap.user_id WHERE ap.user_id=?");
$profile->execute([$uid]);
$p = $profile->fetch();

// Load CV
$cv = $pdo->prepare("SELECT * FROM alumni_cv WHERE user_id=?");
$cv->execute([$uid]);
$cv = $cv->fetch() ?: [];

// Load employment
$jobs = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC");
$jobs->execute([$uid]);
$jobs = $jobs->fetchAll();
$current_jobs = array_filter($jobs, fn($j) => $j['is_current']);
$current_job = reset($current_jobs) ?: null;

// ── PROFILE COMPLETENESS GATE ──────────────────────────────
$required = ['full_name','degree','department','graduation_year','phone'];
$missing  = array_filter($required, fn($f) => empty($p[$f]));
$profile_complete = empty($missing);

// ── BUILD ALUMNI KEYWORD SET ───────────────────────────────
$alumni_keywords = [];

// Helper: strip level suffix from "Skill:Level" format
$strip_level = fn($s) => strtolower(trim(explode(':', $s)[0]));

// From CV parsed keywords (already clean)
if (!empty($cv['parsed_keywords'])) {
    foreach (array_filter(explode(',', $cv['parsed_keywords'])) as $kw) {
        $kw = strtolower(trim($kw));
        if (strlen($kw) >= 2) $alumni_keywords[] = $kw;
    }
}
// From skills field — strip :Level suffix
if (!empty($cv['skills'])) {
    foreach (array_filter(array_map('trim', explode(',', $cv['skills']))) as $s) {
        $kw = $strip_level($s);
        if (strlen($kw) >= 2) $alumni_keywords[] = $kw;
    }
}
// From degree words (4+ chars only)
if (!empty($p['degree'])) {
    foreach (explode(' ', strtolower($p['degree'])) as $w)
        if (strlen($w) >= 4) $alumni_keywords[] = $w;
}
// From employment industries & job titles (4+ chars)
foreach ($jobs as $j) {
    foreach (explode(' ', strtolower($j['industry'] ?? ''))  as $w) if (strlen($w) >= 4) $alumni_keywords[] = $w;
    foreach (explode(' ', strtolower($j['job_title'] ?? '')) as $w) if (strlen($w) >= 4) $alumni_keywords[] = $w;
}
// From certifications — strip :Level
if (!empty($cv['certifications'])) {
    foreach (array_filter(array_map('trim', preg_split('/[,;\n]+/', $cv['certifications']))) as $s) {
        $kw = strtolower($s);
        if (strlen($kw) >= 3) $alumni_keywords[] = $kw;
    }
}
$alumni_keywords = array_unique(array_filter($alumni_keywords));

// ── LOAD OPEN OPPORTUNITIES ────────────────────────────────
try {
    $opps = $pdo->query("SELECT o.*, e.company_name AS emp_company, e.contact_email AS emp_contact
                         FROM opportunities o
                         LEFT JOIN employers e ON e.id=o.employer_id
                         WHERE o.status='open'
                         ORDER BY o.created_at DESC")->fetchAll();
} catch (Throwable $e) {
    $opps = [];
}

// ── SHARED KEYWORD MATCHER ────────────────────────────────
// Returns true only when both strings are meaningful and one fully contains the other,
// or they are equal. Prevents short tokens like "r" matching "requirements".
function kw_match(string $a, string $b): bool {
    if (strlen($a) < 3 || strlen($b) < 3) return false;
    return $a === $b
        || (strlen($a) >= 4 && str_contains($b, $a))
        || (strlen($b) >= 4 && str_contains($a, $b));
}

// ── TOKENISE OPPORTUNITY TEXT ─────────────────────────────
function opp_tokens(array $opp): array {
    $stop = ['with','that','this','have','from','they','will','your','been','were','their',
             'about','which','would','there','other','these','those','after','where','while',
             'must','should','able','good','work','team','role','year','years','using','also'];
    $text = strtolower(
        ($opp['requirements'] ?? '').' '.($opp['description'] ?? '').' '.
        ($opp['industry'] ?? '').' '.($opp['title'] ?? '')
    );
    return array_unique(array_filter(
        preg_split('/[\s,;.\/\(\)\-\+\[\]]+/', $text),
        fn($w) => strlen($w) >= 4 && !in_array($w, $stop)
    ));
}

// ── INTELLIGENT MATCHING ───────────────────────────────────
// Scoring breakdown (max 100):
//   Keyword overlap  : up to 40 pts  (4 pts per unique hit, capped)
//   Industry match   : 15 pts
//   Degree relevance : 10 pts
//   Work experience  : 10 pts
//   Skills breadth   : 10 pts  (5 skills = 5 pts, 10+ skills = 10 pts)
//   CV uploaded      :  5 pts
//   Recency bonus    :  5 pts  (graduated ≤7 years ago)
//   Profile photo    :  5 pts
function score_opportunity(array $opp, array $alumni_kw, array $profile, array $cv, array $jobs): array {
    $score       = 0;
    $matched     = [];
    $gaps        = [];
    $suggestions = [];

    $req_words = opp_tokens($opp);
    $req_text  = strtolower(($opp['requirements'] ?? '').' '.($opp['description'] ?? ''));

    // 1. Keyword overlap — 40 pts max
    $hits = 0;
    foreach ($req_words as $rw) {
        foreach ($alumni_kw as $ak) {
            if (kw_match($ak, $rw)) { $matched[] = $rw; $hits++; break; }
        }
    }
    $score += min(40, $hits * 4);

    // 2. Industry match — 15 pts (checks ALL jobs, not just current)
    $opp_ind = strtolower(trim($opp['industry'] ?? ''));
    if (strlen($opp_ind) >= 3) {
        foreach ($jobs as $j) {
            $ji = strtolower(trim($j['industry'] ?? ''));
            if (strlen($ji) >= 3 && (str_contains($ji, $opp_ind) || str_contains($opp_ind, $ji))) {
                $score += 15;
                $matched[] = 'industry: '.$opp['industry'];
                break;
            }
        }
    }

    // 3. Degree relevance — 10 pts
    if (!empty($profile['degree']) && !empty($opp['requirements'])) {
        foreach (array_filter(explode(' ', strtolower($profile['degree'])), fn($w) => strlen($w) >= 4) as $dw) {
            if (str_contains(strtolower($opp['requirements']), $dw)) {
                $score += 10;
                $matched[] = 'degree: '.$profile['degree'];
                break;
            }
        }
    }

    // 4. Work experience — 10 pts
    foreach ($jobs as $j) {
        if (!empty($j['employment_type']) && $j['employment_type'] !== 'Unemployed') {
            $score += 10; $matched[] = 'work experience'; break;
        }
    }

    // 5. Skills breadth — up to 10 pts
    $skill_count = count(array_filter(array_map('trim', explode(',', $cv['skills'] ?? ''))));
    if ($skill_count >= 10)      { $score += 10; $matched[] = $skill_count.' skills listed'; }
    elseif ($skill_count >= 5)   { $score += 5;  $matched[] = $skill_count.' skills listed'; }

    // 6. CV uploaded — 5 pts
    if (!empty($cv['cv_file'])) { $score += 5; $matched[] = 'CV uploaded'; }

    // 7. Recency — 5 pts
    if (!empty($profile['graduation_year']) && (date('Y') - (int)$profile['graduation_year']) <= 7) {
        $score += 5; $matched[] = 'recent graduate ('.$profile['graduation_year'].')';
    }

    // 8. Profile photo — 5 pts
    if (!empty($profile['profile_photo'])) { $score += 5; $matched[] = 'profile photo set'; }

    $score   = min(100, $score);
    $matched = array_unique($matched);

    // Gaps: req tokens not matched
    foreach (array_slice($req_words, 0, 30) as $rw) {
        $found = false;
        foreach ($alumni_kw as $ak) { if (kw_match($ak, $rw)) { $found = true; break; } }
        if (!$found) $gaps[] = $rw;
    }
    $gaps = array_unique(array_slice($gaps, 0, 8));

    // Smart suggestions
    if ($skill_count < 3)
        $suggestions[] = 'Add at least 5 skills in CV Builder — it is the biggest factor in your match score.';
    if (empty($cv['cv_file']))
        $suggestions[] = 'Upload your CV to unlock full keyword extraction (+5 pts).';
    if (empty($profile['linkedin_url']))
        $suggestions[] = 'Add your LinkedIn URL — employers verify candidates online.';
    if (empty($profile['profile_photo']))
        $suggestions[] = 'Add a profile photo (+5 pts) — it builds trust with employers.';
    if (empty($cv['certifications']) && str_contains($req_text, 'certif'))
        $suggestions[] = 'This role mentions certifications. Add any you hold in CV Builder.';
    if ($score >= 70)
        $suggestions[] = 'Strong match! Make sure your CV is uploaded and profile is complete before the deadline.';
    elseif ($score >= 40)
        $suggestions[] = 'Decent match. Closing the gaps above could push you into the strong match range.';
    else
        $suggestions[] = 'Low overlap. Add skills and upload your CV to significantly improve this score.';
    if ($opp['type'] === 'Internship' && !empty($profile['graduation_year']) && (date('Y') - (int)$profile['graduation_year']) > 4)
        $suggestions[] = 'This is an internship — it may be more suited to recent graduates.';

    return compact('score','matched','gaps','suggestions');
}

// Score all opportunities
$scored = [];
foreach ($opps as $opp) {
    $result   = score_opportunity($opp, $alumni_keywords, $p, $cv ?: [], $jobs);
    $scored[] = array_merge($opp, $result);
}

// Sort by score desc
usort($scored, fn($a,$b) => $b['score'] - $a['score']);

$score_color = fn($s) => $s >= 70 ? 'var(--success)' : ($s >= 40 ? 'var(--accent)' : 'var(--danger)');
$score_label = fn($s) => $s >= 70 ? 'Strong Match' : ($s >= 40 ? 'Partial Match' : 'Low Match');
$score_badge = fn($s) => $s >= 70 ? 'badge-success' : ($s >= 40 ? 'badge-warning' : 'badge-danger');

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Job Matches</h1>
    <p>Opportunities matched to your profile &amp; CV</p>
  </div>
  <div class="page-header-actions">
    <a href="/alumni/cv_builder.php" class="btn btn-outline btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Update CV Profile
    </a>
  </div>
</div>

<?php if (!$profile_complete): ?>
<div class="alert alert-error">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  <span>
    Your profile is incomplete. You cannot be matched or selected for opportunities until you fill in:
    <strong><?= implode(', ', $missing) ?></strong>.
    <a href="/alumni/profile.php" style="color:inherit;font-weight:700">Fix now →</a>
  </span>
</div>
<?php endif; ?>

<?php if (!$cv || !$cv['skills']): ?>
<div class="alert alert-warning">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  <span>You haven't added your skills yet. <a href="/alumni/cv_builder.php" style="color:inherit;font-weight:700">Add skills in CV Builder</a> to get accurate matches.</span>
</div>
<?php endif; ?>

<!-- Profile snapshot -->
<div class="card" style="margin-bottom:1.5rem;padding:1rem 1.25rem">
  <div style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap">
    <div style="flex:1;min-width:200px">
      <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Your Profile</div>
      <div class="fw-600"><?= htmlspecialchars($p['full_name']) ?></div>
      <div class="text-sm text-muted"><?= htmlspecialchars($p['degree'] ?? '—') ?> &middot; <?= htmlspecialchars($p['department'] ?? '—') ?></div>
    </div>
    <?php if ($alumni_keywords): ?>
    <div style="flex:2;min-width:200px">
      <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Your Keywords (<?= count($alumni_keywords) ?>)</div>
      <div style="display:flex;flex-wrap:wrap;gap:.3rem">
        <?php foreach (array_slice($alumni_keywords, 0, 18) as $kw): ?>
        <span class="badge badge-secondary" style="font-size:.68rem"><?= htmlspecialchars($kw) ?></span>
        <?php endforeach; ?>
        <?php if (count($alumni_keywords) > 18): ?>
        <span class="badge badge-secondary" style="font-size:.68rem">+<?= count($alumni_keywords)-18 ?> more</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <div>
      <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">Skills Listed</div>
      <?php $skill_count = count(array_filter(array_map('trim', explode(',', $cv['skills'] ?? '')))); ?>
      <span style="font-size:1.5rem;font-weight:800;color:<?= $skill_count >= 5 ? 'var(--success)' : 'var(--accent)' ?>"><?= $skill_count ?></span>
      <span class="text-xs text-muted">skills</span>
    </div>
  </div>
</div>

<!-- Match results -->
<?php if (!$scored): ?>
<div class="card"><div class="empty-state">
  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
  <p>No open opportunities available right now. Check back soon.</p>
</div></div>
<?php else: ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem">
  <div class="text-sm text-muted"><?= count($scored) ?> opportunities found &mdash; sorted by best match</div>
  <div style="display:flex;gap:.4rem">
    <span class="badge badge-success">Strong ≥70</span>
    <span class="badge badge-warning">Partial ≥40</span>
    <span class="badge badge-danger">Low &lt;40</span>
  </div>
</div>

<?php foreach ($scored as $opp): ?>
<div class="card" style="margin-bottom:1.25rem;<?= !$profile_complete ? 'opacity:.6;pointer-events:none' : '' ?>">

  <!-- Header row -->
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1rem">
    <div style="flex:1;min-width:0">
      <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem">
        <span class="fw-700" style="font-size:1rem"><?= htmlspecialchars($opp['title']) ?></span>
        <span class="badge <?= $score_badge($opp['score']) ?>"><?= $score_label($opp['score']) ?></span>
        <span class="badge badge-secondary"><?= htmlspecialchars($opp['type']) ?></span>
      </div>
      <div class="text-sm text-muted">
        <strong><?= htmlspecialchars($opp['emp_company'] ?? $opp['company']) ?></strong>
        <?php if ($opp['location']): ?> &middot; <?= htmlspecialchars($opp['location']) ?><?php endif; ?>
        <?php if ($opp['industry']): ?> &middot; <?= htmlspecialchars($opp['industry']) ?><?php endif; ?>
        <?php if ($opp['deadline']): ?> &middot; Deadline: <strong><?= date('d M Y', strtotime($opp['deadline'])) ?></strong><?php endif; ?>
      </div>
    </div>

    <!-- Score circle -->
    <div style="text-align:center;flex-shrink:0">
      <div style="width:56px;height:56px;border-radius:50%;border:3px solid <?= $score_color($opp['score']) ?>;display:flex;align-items:center;justify-content:center;flex-direction:column">
        <span style="font-size:1.1rem;font-weight:800;color:<?= $score_color($opp['score']) ?>;line-height:1"><?= $opp['score'] ?></span>
        <span style="font-size:.55rem;color:var(--muted)">match</span>
      </div>
    </div>
  </div>

  <!-- Match score bar -->
  <div style="margin-bottom:1rem">
    <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
      <span class="text-xs text-muted">Match Score</span>
      <span class="text-xs fw-600" style="color:<?= $score_color($opp['score']) ?>"><?= $opp['score'] ?>%</span>
    </div>
    <div class="progress-bar-wrap" style="height:8px">
      <div class="progress-bar-fill" style="width:<?= $opp['score'] ?>%;background:<?= $score_color($opp['score']) ?>;transition:width .6s ease"></div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">

    <!-- What you have -->
    <?php if ($opp['matched']): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--r);padding:.75rem">
      <div class="text-xs fw-600" style="color:var(--success);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle"><polyline points="20 6 9 17 4 12"/></svg>
        What you have
      </div>
      <div style="display:flex;flex-direction:column;gap:.25rem">
        <?php foreach (array_slice($opp['matched'],0,6) as $m): ?>
        <span class="text-xs" style="color:var(--success)"><?= htmlspecialchars($m) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Gaps -->
    <?php if ($opp['gaps']): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--r);padding:.75rem">
      <div class="text-xs fw-600" style="color:var(--danger);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Skill gaps
      </div>
      <div style="display:flex;flex-direction:column;gap:.25rem">
        <?php foreach ($opp['gaps'] as $m): ?>
        <span class="text-xs" style="color:var(--danger)"><?= htmlspecialchars($m) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Smart suggestions -->
    <?php if ($opp['suggestions']): ?>
    <div style="background:#fffbf0;border:1px solid #fde68a;border-radius:var(--r);padding:.75rem">
      <div class="text-xs fw-600" style="color:var(--accent);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.5rem">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Smart tips
      </div>
      <div style="display:flex;flex-direction:column;gap:.35rem">
        <?php foreach (array_slice($opp['suggestions'],0,3) as $s): ?>
        <span class="text-xs" style="color:var(--accent);line-height:1.5"><?= htmlspecialchars($s) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Description snippet -->
  <?php if ($opp['description']): ?>
  <div class="text-sm text-muted" style="margin-top:.85rem;padding-top:.85rem;border-top:1px solid var(--border-light);line-height:1.7">
    <?= htmlspecialchars(mb_substr($opp['description'],0,220)) ?><?= strlen($opp['description'])>220?'…':'' ?>
  </div>
  <?php endif; ?>

</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
