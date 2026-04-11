<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/audit.php';

$success = $error = '';

// Create portal account for employer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_account'])) {
    csrf_verify();
    $emp_id   = (int)$_POST['emp_id'];
    $emp_row  = $pdo->prepare("SELECT * FROM employers WHERE id=?");
    $emp_row->execute([$emp_id]);
    $emp_row  = $emp_row->fetch();

    if ($emp_row && !$emp_row['user_id']) {
        $email = trim($_POST['account_email']);
        $pass  = $_POST['account_pass'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            try {
                $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,'employer')")
                    ->execute([$emp_row['company_name'], $email, password_hash($pass, PASSWORD_DEFAULT)]);
                $uid = $pdo->lastInsertId();
                $pdo->prepare("UPDATE employers SET user_id=? WHERE id=?")->execute([$uid, $emp_id]);
                audit_log('create_employer_account', "employer:{$emp_id}", $email);
                $success = "Portal account created for {$emp_row['company_name']}. They can now log in at the GATE Portal.";
            } catch (PDOException $e) {
                $error = 'That email address is already registered.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM employers WHERE id=?")->execute([$_POST['delete_id']]);
        audit_log('delete_employer', 'employer:'.$_POST['delete_id']);
        header('Location: /gate-portal/admin/employers.php?msg=deleted'); exit;
    }

    $company = trim($_POST['company_name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $website  = trim($_POST['website'] ?? '');
    $cname    = trim($_POST['contact_name'] ?? '');
    $cemail   = trim($_POST['contact_email'] ?? '');
    $cphone   = trim($_POST['contact_phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');
    $edit_id  = (int)($_POST['edit_id'] ?? 0);

    if (!$company) {
        $error = 'Company name is required.';
    } elseif ($edit_id) {
        $pdo->prepare("UPDATE employers SET company_name=?,industry=?,website=?,contact_name=?,contact_email=?,contact_phone=?,address=?,notes=? WHERE id=?")
            ->execute([$company,$industry,$website,$cname,$cemail,$cphone,$address,$notes,$edit_id]);
        audit_log('update_employer', "employer:{$edit_id}", $company);
        header('Location: /gate-portal/admin/employers.php?msg=saved'); exit;
    } else {
        $pdo->prepare("INSERT INTO employers (company_name,industry,website,contact_name,contact_email,contact_phone,address,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$company,$industry,$website,$cname,$cemail,$cphone,$address,$notes,$_SESSION['user_id']]);
        audit_log('create_employer', 'employers', $company);
        header('Location: /gate-portal/admin/employers.php?msg=created'); exit;
    }
}

$msg_map = ['created'=>'Employer added.','saved'=>'Employer updated.','deleted'=>'Employer removed.'];
if (isset($_GET['msg'],$msg_map[$_GET['msg']])) $success = $msg_map[$_GET['msg']];

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM employers WHERE id=?");
    $s->execute([$_GET['edit']]);
    $edit = $s->fetch();
}

$search = trim($_GET['q'] ?? '');
$where  = $search ? "WHERE company_name LIKE ? OR industry LIKE ? OR contact_name LIKE ?" : "";
$params = $search ? ["%$search%","%$search%","%$search%"] : [];
$employers = $pdo->prepare("SELECT e.*, u.full_name AS added_by, eu.email AS account_email,
    (SELECT COUNT(*) FROM opportunities o WHERE o.employer_id=e.id) AS opp_count
    FROM employers e
    LEFT JOIN users u ON u.id=e.created_by
    LEFT JOIN users eu ON eu.id=e.user_id
    $where ORDER BY e.company_name");
$employers->execute($params);
$employers = $employers->fetchAll();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Employers</h1>
    <p>Manage external opportunity providers &amp; employer contacts</p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/employers.php" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Employer
    </a>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- FORM -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><?= $edit ? 'Edit Employer' : 'New Employer' ?></span>
      <?php if ($edit): ?><a href="/gate-portal/admin/employers.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
    </div>
    <form method="POST">
      <?= csrf_field() ?>
      <?php if ($edit): ?><input type="hidden" name="edit_id" value="<?= $edit['id'] ?>"><?php endif; ?>

      <div class="form-group">
        <label>Company Name <span style="color:var(--danger)">*</span></label>
        <input type="text" name="company_name" value="<?= htmlspecialchars($edit['company_name'] ?? '') ?>" required placeholder="e.g. Accenture South Africa">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Industry</label>
          <input type="text" name="industry" value="<?= htmlspecialchars($edit['industry'] ?? '') ?>" placeholder="e.g. Information Technology">
        </div>
        <div class="form-group">
          <label>Website</label>
          <input type="url" name="website" value="<?= htmlspecialchars($edit['website'] ?? '') ?>" placeholder="https://...">
        </div>
      </div>

      <div style="padding:.6rem .75rem;background:var(--bg);border-radius:var(--r);margin-bottom:1rem">
        <div class="text-xs fw-600 text-muted" style="text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem">Contact Person</div>
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="contact_name" value="<?= htmlspecialchars($edit['contact_name'] ?? '') ?>" placeholder="e.g. Jane Smith">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="contact_email" value="<?= htmlspecialchars($edit['contact_email'] ?? '') ?>" placeholder="jane@company.com">
          </div>
          <div class="form-group">
            <label>Phone</label>
            <input type="text" name="contact_phone" value="<?= htmlspecialchars($edit['contact_phone'] ?? '') ?>" placeholder="+27 XX XXX XXXX">
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($edit['address'] ?? '') ?>" placeholder="City, Province">
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" rows="2" placeholder="Any additional notes..."><?= htmlspecialchars($edit['notes'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
        <?= $edit ? 'Update Employer' : 'Add Employer' ?>
      </button>
    </form>
  </div>

  <!-- LIST -->
  <div>
    <!-- Search -->
    <form method="GET" style="display:flex;gap:.5rem;margin-bottom:1rem">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search employers…"
             style="flex:1;padding:.55rem .85rem;border:1px solid var(--border);border-radius:var(--r);font-size:.875rem;font-family:inherit">
      <button class="btn btn-primary btn-sm">Search</button>
      <?php if ($search): ?><a href="/gate-portal/admin/employers.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>

    <?php if (!$employers): ?>
    <div class="card"><div class="empty-state">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      <p>No employers added yet.</p>
    </div></div>
    <?php endif; ?>

    <?php foreach ($employers as $e): ?>
    <div class="card" style="margin-bottom:.85rem;padding:1rem 1.25rem">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
        <div style="flex:1;min-width:0">
          <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.25rem">
            <span class="fw-700"><?= htmlspecialchars($e['company_name']) ?></span>
            <?php if ($e['industry']): ?>
            <span class="badge badge-secondary"><?= htmlspecialchars($e['industry']) ?></span>
            <?php endif; ?>
            <?php if ($e['opp_count'] > 0): ?>
            <span class="badge badge-info"><?= $e['opp_count'] ?> opportunit<?= $e['opp_count']===1?'y':'ies' ?></span>
            <?php endif; ?>
          </div>

          <?php if ($e['contact_name'] || $e['contact_email'] || $e['contact_phone']): ?>
          <div class="text-sm text-muted" style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.2rem">
            <?php if ($e['contact_name']): ?>
            <span style="display:flex;align-items:center;gap:.3rem">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <?= htmlspecialchars($e['contact_name']) ?>
            </span>
            <?php endif; ?>
            <?php if ($e['contact_email']): ?>
            <a href="mailto:<?= htmlspecialchars($e['contact_email']) ?>" style="display:flex;align-items:center;gap:.3rem;color:var(--primary);text-decoration:none;font-size:.8rem">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <?= htmlspecialchars($e['contact_email']) ?>
            </a>
            <?php endif; ?>
            <?php if ($e['contact_phone']): ?>
            <span style="display:flex;align-items:center;gap:.3rem">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <?= htmlspecialchars($e['contact_phone']) ?>
            </span>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <?php if ($e['address']): ?>
          <div class="text-xs text-muted"><?= htmlspecialchars($e['address']) ?></div>
          <?php endif; ?>
        </div>

        <div style="display:flex;gap:.4rem;flex-shrink:0">
          <?php if ($e['website']): ?>
          <a href="<?= htmlspecialchars($e['website']) ?>" target="_blank" class="btn btn-outline btn-sm" title="Website">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </a>
          <?php endif; ?>
          <a href="?edit=<?= $e['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="POST" style="display:inline" onsubmit="return confirm('Remove this employer?')">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_id" value="<?= $e['id'] ?>">
            <button class="btn btn-danger btn-sm">Remove</button>
          </form>
        </div>
      </div>

      <!-- Account status -->
      <?php if ($e['user_id']): ?>
      <div style="display:flex;align-items:center;gap:.5rem;margin-top:.6rem;padding:.5rem .75rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--r)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span class="text-xs fw-600" style="color:var(--success)">Portal account active</span>
        <span class="text-xs text-muted">&mdash; <?= htmlspecialchars($e['account_email']) ?></span>
      </div>
      <?php else: ?>
      <div style="margin-top:.6rem">
        <div id="acc-form-<?= $e['id'] ?>" style="display:none;padding:.65rem .75rem;background:var(--bg);border:1px solid var(--border);border-radius:var(--r)">
          <form method="POST" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end">
            <?= csrf_field() ?>
            <input type="hidden" name="create_account" value="1">
            <input type="hidden" name="emp_id" value="<?= $e['id'] ?>">
            <div style="flex:1;min-width:160px">
              <div class="text-xs text-muted fw-600" style="margin-bottom:.25rem">Login Email</div>
              <input type="email" name="account_email" required placeholder="contact@company.com"
                     value="<?= htmlspecialchars($e['contact_email'] ?? '') ?>"
                     style="width:100%;padding:.45rem .7rem;border:1px solid var(--border);border-radius:var(--r);font-size:.82rem;font-family:inherit">
            </div>
            <div style="flex:1;min-width:140px">
              <div class="text-xs text-muted fw-600" style="margin-bottom:.25rem">Password</div>
              <input type="password" name="account_pass" required placeholder="Min. 8 characters"
                     style="width:100%;padding:.45rem .7rem;border:1px solid var(--border);border-radius:var(--r);font-size:.82rem;font-family:inherit">
            </div>
            <button class="btn btn-primary btn-sm">Create</button>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('acc-form-<?= $e['id'] ?>').style.display='none'">Cancel</button>
          </form>
        </div>
        <button class="btn btn-outline btn-sm" style="font-size:.75rem"
          onclick="document.getElementById('acc-form-<?= $e['id'] ?>').style.display='block';this.style.display='none'">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
          Create Portal Account
        </button>
      </div>
      <?php endif; ?>
      </div>
      <?php if ($e['notes']): ?>
      <div class="text-xs text-muted" style="margin-top:.5rem;padding-top:.5rem;border-top:1px solid var(--border-light)"><?= htmlspecialchars($e['notes']) ?></div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
