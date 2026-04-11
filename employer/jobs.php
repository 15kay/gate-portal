<?php
require_once '../includes/auth_guard.php';
require_role('employer');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/audit.php';

$emp = $pdo->prepare("SELECT * FROM employers WHERE user_id=?");
$emp->execute([$_SESSION['user_id']]);
$emp = $emp->fetch();
if (!$emp) { header('Location: /gate-portal/auth/logout.php'); exit; }

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['delete_id'])) {
        // Only delete own jobs
        $pdo->prepare("DELETE FROM opportunities WHERE id=? AND employer_id=?")->execute([$_POST['delete_id'], $emp['id']]);
        audit_log('delete_opportunity', 'opportunity:'.$_POST['delete_id'], '', 'employer');
        header('Location: /gate-portal/employer/jobs.php?msg=deleted'); exit;
    }

    $title    = trim($_POST['title'] ?? '');
    $industry = trim($_POST['industry'] ?? $emp['industry'] ?? '');
    $location = trim($_POST['location'] ?? $emp['address'] ?? '');
    $type     = $_POST['type'] ?? 'Full-time';
    $desc     = trim($_POST['description'] ?? '');
    $req      = trim($_POST['requirements'] ?? '');
    $deadline = $_POST['deadline'] ?: null;
    $status   = $_POST['status'] ?? 'open';
    $edit_id  = (int)($_POST['edit_id'] ?? 0);

    if (!$title) {
        $error = 'Job title is required.';
    } elseif ($edit_id) {
        // Verify ownership
        $pdo->prepare("UPDATE opportunities SET title=?,industry=?,location=?,type=?,description=?,requirements=?,deadline=?,status=? WHERE id=? AND employer_id=?")
            ->execute([$title,$industry,$location,$type,$desc,$req,$deadline,$status,$edit_id,$emp['id']]);
        audit_log('update_opportunity', "opportunity:{$edit_id}", $title, 'employer');
        header('Location: /gate-portal/employer/jobs.php?msg=saved'); exit;
    } else {
        $pdo->prepare("INSERT INTO opportunities (title,company,employer_id,contact_name,contact_email,industry,location,type,description,requirements,deadline,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$title, $emp['company_name'], $emp['id'], $emp['contact_name'], $emp['contact_email'],
                       $industry, $location, $type, $desc, $req, $deadline, $status, $_SESSION['user_id']]);
        audit_log('create_opportunity', 'opportunities', $title, 'employer');
        header('Location: /gate-portal/employer/jobs.php?msg=created'); exit;
    }
}

$msg_map = ['created'=>'Job posted successfully.','saved'=>'Job updated.','deleted'=>'Job removed.'];
if (isset($_GET['msg'],$msg_map[$_GET['msg']])) $success = $msg_map[$_GET['msg']];

$show_form = isset($_GET['new']) || isset($_GET['edit']);

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM opportunities WHERE id=? AND employer_id=?");
    $s->execute([$_GET['edit'], $emp['id']]);
    $edit = $s->fetch();
    if (!$edit) { header('Location: /gate-portal/employer/jobs.php'); exit; }
}

$status_filter = $_GET['status'] ?? '';
$where  = "WHERE employer_id=?" . ($status_filter ? " AND status=?" : "");
$params = $status_filter ? [$emp['id'], $status_filter] : [$emp['id']];
$jobs   = $pdo->prepare("SELECT o.*,
    (SELECT COUNT(*) FROM candidate_submissions cs WHERE cs.opportunity_id=o.id) AS candidates
    FROM opportunities o $where ORDER BY o.created_at DESC");
$jobs->execute($params);
$jobs = $jobs->fetchAll();

$status_badge = ['open'=>'badge-success','closed'=>'badge-secondary','filled'=>'badge-info'];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>My Job Postings</h1>
    <p><?= count($jobs) ?> <?= $status_filter ?: 'total' ?> jobs</p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/employer/jobs.php?new=1" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Post New Job
    </a>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:<?= $show_form ? '1fr 2fr' : '1fr' ?>;gap:1.5rem;align-items:start">

<?php if ($show_form): ?>
<!-- FORM -->
<div class="card">
  <div class="card-header">
    <span class="card-title"><?= $edit ? 'Edit Job' : 'Post a Job' ?></span>
    <a href="/gate-portal/employer/jobs.php" class="btn btn-outline btn-sm">Cancel</a>
  </div>
  <form method="POST">
    <?= csrf_field() ?>
    <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>

    <div class="form-group">
      <label>Job Title <span style="color:var(--danger)">*</span></label>
      <input type="text" name="title" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required placeholder="e.g. Software Developer">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Industry</label>
        <input type="text" name="industry" value="<?= htmlspecialchars($edit['industry'] ?? $emp['industry'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($edit['location'] ?? $emp['address'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Employment Type</label>
        <select name="type">
          <?php foreach (['Full-time','Part-time','Contract','Internship','Freelance'] as $t): ?>
          <option value="<?= $t ?>" <?= ($edit['type'] ?? 'Full-time')===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <?php foreach (['open','closed','filled'] as $st): ?>
          <option value="<?= $st ?>" <?= ($edit['status'] ?? 'open')===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Application Deadline</label>
      <input type="date" name="deadline" value="<?= $edit['deadline'] ?? '' ?>">
    </div>
    <div class="form-group">
      <label>Job Description</label>
      <textarea name="description" rows="4" placeholder="Describe the role, responsibilities..."><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label>Requirements</label>
      <textarea name="requirements" rows="4" placeholder="Qualifications, skills, experience..."><?= htmlspecialchars($edit['requirements'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
      <?= $edit ? 'Update Job' : 'Post Job' ?>
    </button>
  </form>
</div>
<?php endif; ?>

<!-- LIST -->
<div>
  <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
    <?php foreach ([''=> 'All', 'open'=>'Open','closed'=>'Closed','filled'=>'Filled'] as $val=>$label): ?>
    <a href="?status=<?= $val ?>" class="btn btn-sm <?= $status_filter===$val ? 'btn-primary' : 'btn-outline' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$jobs): ?>
  <div class="card"><div class="empty-state">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
    <p>No jobs posted yet. <a href="?new=1">Post your first job</a>.</p>
  </div></div>
  <?php endif; ?>

  <?php foreach ($jobs as $j): ?>
  <div class="card" style="margin-bottom:.85rem;padding:1.1rem 1.25rem">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem">
          <span class="fw-700"><?= htmlspecialchars($j['title']) ?></span>
          <span class="badge <?= $status_badge[$j['status']] ?>"><?= ucfirst($j['status']) ?></span>
          <span class="badge badge-secondary"><?= htmlspecialchars($j['type']) ?></span>
        </div>
        <div class="text-sm text-muted">
          <?php if ($j['location']): ?><?= htmlspecialchars($j['location']) ?><?php endif; ?>
          <?php if ($j['deadline']): ?> &middot; Deadline: <?= date('d M Y', strtotime($j['deadline'])) ?><?php endif; ?>
          &middot; <strong><?= $j['candidates'] ?></strong> candidate<?= $j['candidates']!=1?'s':'' ?> matched
        </div>
      </div>
      <div style="display:flex;gap:.4rem;flex-shrink:0">
        <a href="?edit=<?= $j['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
        <form method="POST" style="display:inline" onsubmit="return confirm('Remove this job posting?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_id" value="<?= $j['id'] ?>">
          <button class="btn btn-danger btn-sm">Remove</button>
        </form>
      </div>
    </div>
    <?php if ($j['description']): ?>
    <div class="text-sm text-muted" style="margin-top:.5rem;padding-top:.5rem;border-top:1px solid var(--border-light)">
      <?= htmlspecialchars(mb_substr($j['description'],0,160)) ?><?= strlen($j['description'])>160?'…':'' ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

</div>

<?php include '../includes/footer.php'; ?>
