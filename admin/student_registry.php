<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/faculties.php';

$success = $error = '';

// Add single student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    csrf_verify();
    $sno   = strtoupper(trim($_POST['student_number'] ?? ''));
    $idno  = trim($_POST['id_passport'] ?? '');
    $name  = trim($_POST['full_name'] ?? '');
    $deg   = trim($_POST['degree'] ?? '');
    $dept  = trim($_POST['department'] ?? $_POST['faculty'] ?? '');
    $year  = $_POST['graduation_year'] ?: null;

    if (!$sno || !$idno || !$name) {
        $error = 'Student number, ID/passport and full name are required.';
    } else {
        try {
            $pdo->prepare("INSERT INTO student_registry (student_number,id_passport,full_name,degree,department,graduation_year) VALUES (?,?,?,?,?,?)")
                ->execute([$sno, $idno, $name, $deg, $dept, $year]);
            $success = "Student {$name} ({$sno}) added to registry.";
        } catch (PDOException $e) {
            $error = 'Student number already exists in registry.';
        }
    }
}

// Import CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    csrf_verify();
    if (!empty($_FILES['csv']['tmp_name'])) {
        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        fgetcsv($handle); // skip header row
        $imported = 0; $skipped = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO student_registry (student_number,id_passport,full_name,degree,department,graduation_year) VALUES (?,?,?,?,?,?)");
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;
            $stmt->execute([
                strtoupper(trim($row[0])),
                trim($row[1]),
                trim($row[2]),
                trim($row[3] ?? ''),
                trim($row[4] ?? ''),
                !empty($row[5]) ? (int)$row[5] : null
            ]);
            $stmt->rowCount() ? $imported++ : $skipped++;
        }
        fclose($handle);
        $success = "Imported {$imported} student(s). {$skipped} duplicate(s) skipped.";
    } else {
        $error = 'Please select a CSV file to import.';
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrf_verify();
    $pdo->prepare("DELETE FROM student_registry WHERE id=? AND is_registered=0")->execute([$_POST['delete_id']]);
    $success = 'Record removed.';
}

// Search
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$sql    = "SELECT * FROM student_registry WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (student_number LIKE ? OR full_name LIKE ? OR id_passport LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($filter === 'registered')   { $sql .= " AND is_registered=1"; }
if ($filter === 'unregistered') { $sql .= " AND is_registered=0"; }
$sql .= " ORDER BY graduation_year DESC, full_name LIMIT 100";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$records = $stmt->fetchAll();

$total      = $pdo->query("SELECT COUNT(*) FROM student_registry")->fetchColumn();
$registered = $pdo->query("SELECT COUNT(*) FROM student_registry WHERE is_registered=1")->fetchColumn();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Student Registry</h1>
    <p>Manage the verified student list used for alumni registration</p>
  </div>
  <div class="page-header-actions">
    <a href="?export=1" class="btn btn-accent btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </a>
  </div>
</div>

<?php
// Export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_registry_'.date('Ymd').'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Student Number','ID/Passport','Full Name','Degree','Department','Graduation Year','Registered']);
    foreach ($pdo->query("SELECT student_number,id_passport,full_name,degree,department,graduation_year,is_registered FROM student_registry ORDER BY graduation_year DESC,full_name") as $r)
        fputcsv($out, $r);
    fclose($out); exit;
}
?>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- STATS -->
<div class="stats-grid" style="margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-icon primary">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5B1C16" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div class="stat-body"><div class="stat-num"><?= number_format($total) ?></div><div class="stat-label">Total Students</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon success">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1a6b3a" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="stat-body"><div class="stat-num"><?= number_format($registered) ?></div><div class="stat-label">Registered on Portal</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon accent">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D5820F" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div class="stat-body"><div class="stat-num"><?= number_format($total - $registered) ?></div><div class="stat-label">Not Yet Registered</div></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- LEFT: ADD + IMPORT -->
  <div>
    <!-- Add Single -->
    <div class="card">
      <div class="card-header"><span class="card-title">Add Student</span></div>
      <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group"><label>Student Number *</label><input type="text" name="student_number" required placeholder="e.g. 201900001" style="text-transform:uppercase"></div>
        <div class="form-group"><label>SA ID / Passport Number *</label><input type="text" name="id_passport" required placeholder="e.g. 9001015009087"></div>
        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required placeholder="e.g. Thabo Nkosi"></div>
        <div class="form-group"><label>Degree</label><input type="text" name="degree" placeholder="e.g. BSc Computer Science"></div>
        <?php wsu_faculty_selects(); ?>
        <div class="form-group"><label>Graduation Year</label>
          <select name="graduation_year">
            <option value="">— Select —</option>
            <?php for ($y = date('Y'); $y >= 1990; $y--): ?>
            <option value="<?= $y ?>"><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <button type="submit" name="add" value="1" class="btn btn-primary" style="width:100%;justify-content:center">Add Student</button>
      </form>
    </div>

    <!-- Import CSV -->
    <div class="card">
      <div class="card-header"><span class="card-title">Import CSV</span></div>
      <p class="text-sm text-muted" style="margin-bottom:.75rem">
        CSV columns: <code style="background:var(--bg);padding:.1rem .3rem;border-radius:3px;font-size:.78rem">student_number, id_passport, full_name, degree, department, graduation_year</code>
      </p>
      <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group"><input type="file" name="csv" accept=".csv" required></div>
        <button type="submit" name="import" value="1" class="btn btn-accent" style="width:100%;justify-content:center">Import CSV</button>
      </form>
    </div>
  </div>

  <!-- RIGHT: RECORDS TABLE -->
  <div class="card" style="padding:0">
    <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--border);display:flex;gap:.75rem;flex-wrap:wrap;align-items:center">
      <form method="GET" style="display:flex;gap:.5rem;flex:1;min-width:200px">
        <input type="text" name="q" placeholder="Search name, student no, ID…"
               value="<?= htmlspecialchars($search) ?>"
               style="flex:1;padding:.5rem .75rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.82rem;font-family:inherit">
        <select name="filter" style="padding:.5rem .75rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.82rem;font-family:inherit">
          <option value="all" <?= $filter==='all'?'selected':'' ?>>All</option>
          <option value="registered" <?= $filter==='registered'?'selected':'' ?>>Registered</option>
          <option value="unregistered" <?= $filter==='unregistered'?'selected':'' ?>>Not Registered</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Go</button>
      </form>
    </div>
    <div class="table-wrap" style="border:none;border-radius:0">
      <table>
        <thead><tr><th>Student No</th><th>Name</th><th>Degree</th><th>Year</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($records as $r): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($r['student_number']) ?></td>
            <td>
              <div class="fw-600"><?= htmlspecialchars($r['full_name']) ?></div>
              <div class="td-muted"><?= htmlspecialchars($r['department'] ?? '') ?></div>
            </td>
            <td class="td-muted"><?= htmlspecialchars($r['degree'] ?? '—') ?></td>
            <td><?= $r['graduation_year'] ?? '—' ?></td>
            <td>
              <?php if ($r['is_registered']): ?>
              <span class="badge badge-success">Registered</span>
              <?php else: ?>
              <span class="badge badge-secondary">Pending</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!$r['is_registered']): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Remove this student record?')">
                <?= csrf_field() ?>
                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M9 6V4h6v2"/></svg>
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$records): ?>
          <tr><td colspan="6"><div class="empty-state"><p>No records found.</p></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div style="padding:.6rem 1.25rem;border-top:1px solid var(--border-light)">
      <span class="text-xs text-muted">Showing <?= count($records) ?> record(s)</span>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
