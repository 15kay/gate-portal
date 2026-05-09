<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/db_helpers.php';

// CSRF-protected delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $pdo->prepare("DELETE FROM users WHERE id=? AND role='alumni'")->execute([$_POST['delete_id']]);
    header('Location: /gate-portal/admin/alumni.php?msg=deleted'); exit;
}

$search = trim($_GET['q'] ?? '');
$dept   = trim($_GET['dept'] ?? '');
$year   = trim($_GET['year'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 15;

$where  = "WHERE u.role = 'alumni'";
$params = [];
if ($search) { $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR ap.student_id LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($dept)   { $where .= " AND ap.department = ?"; $params[] = $dept; }
if ($year)   { $where .= " AND ap.graduation_year = ?"; $params[] = $year; }

// Total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN alumni_profiles ap ON ap.user_id=u.id $where");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, ceil($total / $per));
$page  = min($page, $pages);
$offset = ($page - 1) * $per;

$sql = "SELECT u.id, u.full_name, u.email, u.created_at,
               ap.student_id, ap.graduation_year, ap.degree, ap.department,
               er.employment_type, er.employer
        FROM users u
        LEFT JOIN alumni_profiles ap ON ap.user_id = u.id
        LEFT JOIN employment_records er ON er.user_id = u.id AND er.is_current = 1
        $where ORDER BY u.created_at DESC " . db_limit($per, $offset);

$stmt = $pdo->prepare($sql); $stmt->execute($params);
$alumni = $stmt->fetchAll();

$depts = $pdo->query("SELECT DISTINCT department FROM alumni_profiles WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query("SELECT DISTINCT graduation_year FROM alumni_profiles WHERE graduation_year IS NOT NULL ORDER BY graduation_year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Build query string for pagination links
$qs = http_build_query(array_filter(['q'=>$search,'dept'=>$dept,'year'=>$year]));

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Alumni Management</h1>
    <p><?= number_format($total) ?> alumni registered</p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/reports.php?export=1" class="btn btn-accent btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
  </div>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">Alumni record removed successfully.</div>
<?php endif; ?>

<!-- FILTERS -->
<div class="card">
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
    <div style="flex:2;min-width:200px">
      <input type="text" name="q" placeholder="Search by name, email or student ID…"
             value="<?= htmlspecialchars($search) ?>"
             style="width:100%;padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.875rem;font-family:inherit">
    </div>
    <select name="dept" style="padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.875rem;font-family:inherit">
      <option value="">All Departments</option>
      <?php foreach ($depts as $d): ?>
      <option value="<?= htmlspecialchars($d) ?>" <?= $dept===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="year" style="padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.875rem;font-family:inherit">
      <option value="">All Years</option>
      <?php foreach ($years as $y): ?>
      <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($search || $dept || $year): ?>
    <a href="/gate-portal/admin/alumni.php" class="btn btn-outline btn-sm">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- TABLE -->
<div class="card" style="padding:0">
  <div class="table-wrap" style="border:none;border-radius:var(--radius-lg)">
    <table>
      <thead>
        <tr><th>Alumni</th><th>Student ID</th><th>Department</th><th>Grad Year</th><th>Status</th><th>Employer</th><th style="text-align:right">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($alumni as $a):
          $badge = match($a['employment_type'] ?? '') {
            'Full-time','Part-time' => 'badge-success',
            'Self-employed','Freelance' => 'badge-info',
            'Unemployed' => 'badge-danger',
            'Further Studies' => 'badge-warning',
            default => 'badge-secondary'
          };
        ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($a['full_name']) ?></div>
            <div class="td-muted"><?= htmlspecialchars($a['email']) ?></div>
          </td>
          <td class="td-muted"><?= htmlspecialchars($a['student_id'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['department'] ?? '—') ?></td>
          <td><?= $a['graduation_year'] ?? '—' ?></td>
          <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($a['employment_type'] ?? 'Unknown') ?></span></td>
          <td class="td-muted"><?= htmlspecialchars($a['employer'] ?? '—') ?></td>
          <td style="text-align:right">
            <div style="display:flex;gap:.4rem;justify-content:flex-end">
              <a href="/gate-portal/admin/view_alumni.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">View</a>
              <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this alumni?')">
                <?= csrf_field() ?>
                <input type="hidden" name="delete_id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$alumni): ?>
        <tr><td colspan="7"><div class="empty-state"><p>No alumni found.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PAGINATION -->
  <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:.5rem">
    <span class="text-sm text-muted">
      Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $per, $total)) ?> of <?= number_format($total) ?> alumni
    </span>
    <?php if ($pages > 1): ?>
    <div style="display:flex;gap:.3rem;align-items:center">
      <?php if ($page > 1): ?>
      <a href="?<?= $qs ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">← Prev</a>
      <?php endif; ?>
      <?php
      $start = max(1, $page - 2);
      $end   = min($pages, $page + 2);
      for ($i = $start; $i <= $end; $i++):
      ?>
      <a href="?<?= $qs ?>&page=<?= $i ?>"
         class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"
         style="min-width:32px;justify-content:center"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
      <a href="?<?= $qs ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
