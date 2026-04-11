<?php
require_once '../includes/auth_guard.php';
require_min_role('reports_admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/audit.php';

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(200) NOT NULL,
    industry VARCHAR(100),
    location VARCHAR(150),
    type ENUM('Full-time','Part-time','Contract','Internship','Freelance') DEFAULT 'Full-time',
    description TEXT,
    requirements TEXT,
    deadline DATE,
    status ENUM('open','closed','filled') DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
)");

$success = $error = '';
$readonly = !can_manage_opportunities();

if (!$readonly && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM opportunities WHERE id=?")->execute([$_POST['delete_id']]);
        audit_log('delete_opportunity', 'opportunity:'.$_POST['delete_id']);
        header('Location: /gate-portal/admin/opportunities.php?msg=deleted'); exit;
    }

    $title    = trim($_POST['title'] ?? '');
    $company  = trim($_POST['company'] ?? '');
    $emp_id   = (int)($_POST['employer_id'] ?? 0) ?: null;
    $c_name   = trim($_POST['contact_name'] ?? '');
    $c_email  = trim($_POST['contact_email'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $type     = $_POST['type'] ?? 'Full-time';
    $desc     = trim($_POST['description'] ?? '');
    $req      = trim($_POST['requirements'] ?? '');
    $deadline = $_POST['deadline'] ?: null;
    $status   = $_POST['status'] ?? 'open';
    $edit_id  = (int)($_POST['edit_id'] ?? 0);

    if (!$title || !$company) {
        $error = 'Title and Company are required.';
    } elseif ($edit_id) {
        $pdo->prepare("UPDATE opportunities SET title=?,company=?,employer_id=?,contact_name=?,contact_email=?,industry=?,location=?,type=?,description=?,requirements=?,deadline=?,status=? WHERE id=?")
            ->execute([$title,$company,$emp_id,$c_name,$c_email,$industry,$location,$type,$desc,$req,$deadline,$status,$edit_id]);
        audit_log('update_opportunity', "opportunity:{$edit_id}", $title);
        header('Location: /gate-portal/admin/opportunities.php?msg=saved'); exit;
    } else {
        $pdo->prepare("INSERT INTO opportunities (title,company,employer_id,contact_name,contact_email,industry,location,type,description,requirements,deadline,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$title,$company,$emp_id,$c_name,$c_email,$industry,$location,$type,$desc,$req,$deadline,$status,$_SESSION['user_id']]);
        audit_log('create_opportunity', 'opportunities', $title);
        header('Location: /gate-portal/admin/opportunities.php?msg=created'); exit;
    }
}

$msg_map = ['created'=>'Opportunity created.','saved'=>'Opportunity updated.','deleted'=>'Opportunity deleted.'];
if (isset($_GET['msg'],$msg_map[$_GET['msg']])) $success = $msg_map[$_GET['msg']];

// Edit mode
$edit = null;
if (!$readonly && isset($_GET['edit'])) {
    $edit = $pdo->prepare("SELECT * FROM opportunities WHERE id=?");
    $edit->execute([$_GET['edit']]);
    $edit = $edit->fetch();
}

// Load employers for dropdown
$employer_list = $pdo->query("SELECT id, company_name FROM employers ORDER BY company_name")->fetchAll();

$status_filter = $_GET['status'] ?? '';
$where  = $status_filter ? "WHERE status=?" : "";
$params = $status_filter ? [$status_filter] : [];
$opps   = $pdo->prepare("SELECT o.*, u.full_name AS created_by_name FROM opportunities o LEFT JOIN users u ON u.id=o.created_by $where ORDER BY o.created_at DESC");
$opps->execute($params);
$opps = $opps->fetchAll();

$status_colors = ['open'=>'badge-success','closed'=>'badge-secondary','filled'=>'badge-info'];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Opportunities</h1>
    <p><?= count($opps) ?> <?= $status_filter ?: 'total' ?> opportunities</p>
  </div>
  <?php if (!$readonly): ?>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/opportunities.php" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Opportunity
    </a>
  </div>
  <?php endif; ?>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:<?= !$readonly ? '1fr 2fr' : '1fr' ?>;gap:1.5rem;align-items:start">

<?php if (!$readonly): ?>
<!-- FORM -->
<div class="card">
  <div class="card-header">
    <span class="card-title"><?= $edit ? 'Edit Opportunity' : 'New Opportunity' ?></span>
    <?php if ($edit): ?><a href="/gate-portal/admin/opportunities.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
  </div>
  <form method="POST">
    <?= csrf_field() ?>
    <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>
    <div class="form-group">
      <label>Job Title <span style="color:var(--danger)">*</span></label>
      <input type="text" name="title" value="<?= htmlspecialchars($edit['title'] ?? '') ?>" required placeholder="e.g. Software Developer">
    </div>

    <!-- Employer linkage -->
    <div class="form-group">
      <label>Employer</label>
      <select name="employer_id" onchange="syncCompany(this)">
        <option value="">-- Select existing employer or type below --</option>
        <?php foreach ($employer_list as $emp): ?>
        <option value="<?= $emp['id'] ?>" <?= ($edit['employer_id'] ?? '')==$emp['id']?'selected':'' ?>>
          <?= htmlspecialchars($emp['company_name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <div class="form-hint">Link to an employer record, or leave blank and type the company name below.</div>
    </div>
    <div class="form-group">
      <label>Company Name <span style="color:var(--danger)">*</span></label>
      <input type="text" name="company" id="company-field" value="<?= htmlspecialchars($edit['company'] ?? '') ?>" required placeholder="e.g. Accenture">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Contact Person</label>
        <input type="text" name="contact_name" value="<?= htmlspecialchars($edit['contact_name'] ?? '') ?>" placeholder="Name">
      </div>
      <div class="form-group">
        <label>Contact Email</label>
        <input type="email" name="contact_email" value="<?= htmlspecialchars($edit['contact_email'] ?? '') ?>" placeholder="email@company.com">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Industry</label>
        <input type="text" name="industry" value="<?= htmlspecialchars($edit['industry'] ?? '') ?>" placeholder="e.g. IT">
      </div>
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($edit['location'] ?? '') ?>" placeholder="e.g. East London">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Type</label>
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
      <label>Deadline</label>
      <input type="date" name="deadline" value="<?= $edit['deadline'] ?? '' ?>">
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="3" placeholder="Role overview..."><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label>Requirements</label>
      <textarea name="requirements" rows="3" placeholder="Qualifications, skills..."><?= htmlspecialchars($edit['requirements'] ?? '') ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
      <?= $edit ? 'Update Opportunity' : 'Create Opportunity' ?>
    </button>
  </form>
</div>
<?php endif; ?>

<!-- LIST -->
<div>
  <!-- Filter -->
  <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
    <?php foreach ([''=> 'All', 'open'=>'Open','closed'=>'Closed','filled'=>'Filled'] as $val=>$label): ?>
    <a href="?status=<?= $val ?>" class="btn btn-sm <?= $status_filter===$val ? 'btn-primary' : 'btn-outline' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$opps): ?>
  <div class="card"><div class="empty-state"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg><p>No opportunities found.</p></div></div>
  <?php endif; ?>

  <?php foreach ($opps as $o): ?>
  <div class="card" style="margin-bottom:1rem;padding:1.1rem 1.25rem">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.3rem">
          <span class="fw-700" style="font-size:.95rem"><?= htmlspecialchars($o['title']) ?></span>
          <span class="badge <?= $status_colors[$o['status']] ?>"><?= ucfirst($o['status']) ?></span>
          <span class="badge badge-secondary"><?= htmlspecialchars($o['type']) ?></span>
        </div>
        <div class="text-sm text-muted" style="margin-bottom:.4rem">
          <strong><?= htmlspecialchars($o['company']) ?></strong>
          <?php if ($o['location']): ?> &middot; <?= htmlspecialchars($o['location']) ?><?php endif; ?>
          <?php if ($o['industry']): ?> &middot; <?= htmlspecialchars($o['industry']) ?><?php endif; ?>
        </div>
        <?php if ($o['deadline']): ?>
        <div class="text-xs text-muted">Deadline: <?= date('d M Y', strtotime($o['deadline'])) ?></div>
        <?php endif; ?>
      </div>
      <?php if (!$readonly): ?>
      <div style="display:flex;gap:.4rem;flex-shrink:0">
        <a href="?edit=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this opportunity?')">
          <?= csrf_field() ?>
          <input type="hidden" name="delete_id" value="<?= $o['id'] ?>">
          <button class="btn btn-danger btn-sm">Delete</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($o['description']): ?>
    <div class="text-sm text-muted" style="margin-top:.6rem;border-top:1px solid var(--border-light);padding-top:.6rem">
      <?= nl2br(htmlspecialchars(mb_substr($o['description'],0,180))) ?><?= strlen($o['description'])>180?'…':'' ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

</div>

<?php include '../includes/footer.php'; ?>
<script>
const empData = <?= json_encode(array_column($employer_list, null, 'id')) ?>;
function syncCompany(sel) {
    const emp = empData[sel.value];
    if (emp) document.getElementById('company-field').value = emp.company_name;
}
</script>
