<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';

$search = trim($_GET['q'] ?? '');
$dept   = trim($_GET['dept'] ?? '');
$year   = trim($_GET['year'] ?? '');

$sql = "SELECT u.full_name, u.id,
               ap.graduation_year, ap.degree, ap.department,
               ap.profile_photo, ap.bio, ap.linkedin_url,
               er.job_title, er.employer, er.employment_type
        FROM users u
        JOIN alumni_profiles ap ON ap.user_id = u.id
        LEFT JOIN employment_records er ON er.user_id = u.id AND er.is_current = 1
        WHERE u.role = 'alumni' AND u.id != ?";
$params = [$_SESSION['user_id']];

if ($search) { $sql .= " AND (u.full_name LIKE ? OR ap.degree LIKE ? OR ap.department LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($dept)   { $sql .= " AND ap.department = ?"; $params[] = $dept; }
if ($year)   { $sql .= " AND ap.graduation_year = ?"; $params[] = $year; }
$sql .= " ORDER BY u.full_name";

$stmt = $pdo->prepare($sql); $stmt->execute($params);
$alumni = $stmt->fetchAll();

$depts = $pdo->query("SELECT DISTINCT department FROM alumni_profiles WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni_profiles WHERE graduation_year IS NOT NULL ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Alumni Directory</h1>
    <p>Connect and network with fellow WSU graduates</p>
  </div>
</div>

<!-- FILTERS -->
<div class="card" style="padding:1rem 1.25rem;margin-bottom:1.5rem">
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <div style="flex:2;min-width:200px">
      <input type="text" name="q" placeholder="Search by name, degree or department…"
             value="<?= htmlspecialchars($search) ?>"
             style="width:100%;padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.875rem;font-family:inherit">
    </div>
    <div>
      <select name="dept" style="padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.875rem;font-family:inherit">
        <option value="">All Departments</option>
        <?php foreach ($depts as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $dept===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <select name="year" style="padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.875rem;font-family:inherit">
        <option value="">All Years</option>
        <?php foreach ($years as $y): ?>
        <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search || $dept || $year): ?>
    <a href="/gate-portal/alumni/directory.php" class="btn btn-outline btn-sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div style="margin-bottom:.75rem">
  <span class="text-sm text-muted"><?= count($alumni) ?> alumni found</span>
</div>

<!-- ALUMNI GRID -->
<?php if ($alumni): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem">
  <?php foreach ($alumni as $a):
    $badge = match($a['employment_type'] ?? '') {
      'Full-time','Part-time' => ['badge-success', $a['employment_type']],
      'Self-employed','Freelance' => ['badge-info', $a['employment_type']],
      'Unemployed' => ['badge-danger', 'Unemployed'],
      'Further Studies' => ['badge-warning', 'Further Studies'],
      default => ['badge-secondary', 'Unknown']
    };
  ?>
  <div class="card" style="padding:1.25rem;transition:box-shadow .2s" onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow=''">
    <div style="display:flex;gap:.875rem;align-items:flex-start;margin-bottom:.875rem">
      <?php if ($a['profile_photo']): ?>
        <img src="/gate-portal/<?= htmlspecialchars($a['profile_photo']) ?>"
             style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0" alt="">
      <?php else: ?>
        <div style="width:52px;height:52px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem;font-weight:700;flex-shrink:0">
          <?= strtoupper(substr($a['full_name'],0,1)) ?>
        </div>
      <?php endif; ?>
      <div style="flex:1;min-width:0">
        <div class="fw-700" style="font-size:.95rem"><?= htmlspecialchars($a['full_name']) ?></div>
        <div class="text-xs text-muted" style="margin-top:.15rem">
          <?= htmlspecialchars($a['degree'] ?? '—') ?>
          <?= $a['graduation_year'] ? ' · Class of '.$a['graduation_year'] : '' ?>
        </div>
      </div>
    </div>

    <?php if ($a['department']): ?>
    <div class="text-xs text-muted" style="margin-bottom:.5rem">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:.25rem"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
      <?= htmlspecialchars($a['department']) ?>
    </div>
    <?php endif; ?>

    <?php if ($a['job_title'] || $a['employer']): ?>
    <div class="text-sm" style="margin-bottom:.5rem">
      <span class="fw-600"><?= htmlspecialchars($a['job_title'] ?? '') ?></span>
      <?php if ($a['employer']): ?>
      <span class="text-muted"> @ <?= htmlspecialchars($a['employer']) ?></span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($a['bio']): ?>
    <p class="text-xs text-muted" style="line-height:1.6;margin-bottom:.75rem">
      <?= htmlspecialchars(substr($a['bio'], 0, 90)) ?><?= strlen($a['bio']) > 90 ? '…' : '' ?>
    </p>
    <?php endif; ?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.5rem">
      <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
      <?php if ($a['linkedin_url']): ?>
      <a href="<?= htmlspecialchars($a['linkedin_url']) ?>" target="_blank"
         class="btn btn-outline btn-sm" style="font-size:.75rem;padding:.25rem .6rem">
        LinkedIn →
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card"><div class="empty-state"><p>No alumni found matching your search.</p></div></div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
