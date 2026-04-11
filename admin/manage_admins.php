<?php
require_once '../includes/auth_guard.php';
require_min_role('super_admin');
require_once '../config/db.php';

$success = $error = '';

// Delete admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $target = $pdo->prepare("SELECT role FROM users WHERE id=?")->execute([$_GET['delete']]);
    // Prevent deleting self or other super_admins
    $target = $pdo->prepare("SELECT role FROM users WHERE id=?");
    $target->execute([$_GET['delete']]);
    $t = $target->fetch();
    if ($t && $t['role'] !== 'super_admin' && $_GET['delete'] != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['delete']]);
        $success = 'Admin account removed.';
    } else {
        $error = 'Cannot delete a Super Admin account.';
    }
}

// Create new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? 'admin';

    if (!in_array($role, ['admin', 'reports_admin', 'super_admin'])) {
        $error = 'Invalid role selected.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        try {
            $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,?)")
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
            $success = "Admin account created for {$name}.";
        } catch (PDOException $e) {
            $error = 'Email already exists.';
        }
    }
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_id'])) {
    $new_pass = $_POST['new_password'] ?? '';
    if (strlen($new_pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role != 'super_admin'")
            ->execute([password_hash($new_pass, PASSWORD_DEFAULT), $_POST['reset_id']]);
        $success = 'Password updated.';
    }
}

$admins = $pdo->query("
    SELECT id, full_name, email, role, created_at
    FROM users
    WHERE role IN ('super_admin','admin','reports_admin')
    ORDER BY FIELD(role,'super_admin','admin','reports_admin'), full_name
")->fetchAll();

$role_labels = [
    'super_admin'   => ['Super Admin',    'badge-danger'],
    'admin'         => ['Alumni Admin',   'badge-primary'],
    'reports_admin' => ['Reports Admin',  'badge-info'],
];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Manage Admins</h1>
    <p>Create and manage administrator accounts and their access levels</p>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- ACTOR TYPES -->
<div style="margin-bottom:1.5rem">
  <div class="fw-600" style="margin-bottom:.75rem;font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">System Actors</div>
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;margin-bottom:1.5rem">

    <div class="card" style="padding:.875rem;border-top:3px solid var(--danger)">
      <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/><polyline points="16 11 18 13 22 9"/></svg>
        <span class="fw-700" style="color:var(--danger);font-size:.82rem">Super Admin</span>
      </div>
      <div class="text-xs text-muted">Primary Actor. Full system control.</div>
      <span class="badge badge-secondary" style="margin-top:.4rem;font-size:.65rem">Primary</span>
    </div>

    <div class="card" style="padding:.875rem;border-top:3px solid var(--primary)">
      <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <span class="fw-700" style="color:var(--primary);font-size:.82rem">Alumni Admin</span>
      </div>
      <div class="text-xs text-muted">Primary Actor. Daily operations.</div>
      <span class="badge badge-secondary" style="margin-top:.4rem;font-size:.65rem">Primary</span>
    </div>

    <div class="card" style="padding:.875rem;border-top:3px solid var(--info)">
      <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        <span class="fw-700" style="color:var(--info);font-size:.82rem">Reports Admin</span>
      </div>
      <div class="text-xs text-muted">Primary Actor. Analytics &amp; reporting.</div>
      <span class="badge badge-secondary" style="margin-top:.4rem;font-size:.65rem">Primary</span>
    </div>

    <div class="card" style="padding:.875rem;border-top:3px solid var(--success)">
      <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span class="fw-700" style="color:var(--success);font-size:.82rem">Alumni / Graduate</span>
      </div>
      <div class="text-xs text-muted">Secondary Actor. Self-service portal access.</div>
      <span class="badge badge-secondary" style="margin-top:.4rem;font-size:.65rem">Secondary</span>
    </div>

    <div class="card" style="padding:.875rem;border-top:3px solid var(--accent)">
      <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.35rem">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        <span class="fw-700" style="color:var(--accent);font-size:.82rem">Employer</span>
      </div>
      <div class="text-xs text-muted">External Actor. Opportunity provider.</div>
      <span class="badge badge-secondary" style="margin-top:.4rem;font-size:.65rem">External</span>
    </div>

  </div>

  <!-- Matching Engine banner -->
  <div style="display:flex;align-items:center;gap:1rem;padding:.875rem 1.1rem;background:var(--bg);border:1px solid var(--border);border-left:4px solid var(--accent);border-radius:var(--r)">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0"><polygon points="5 3 19 12 5 21 5 3"/></svg>
    <div>
      <div class="fw-600" style="font-size:.85rem">Matching Engine <span class="badge badge-warning" style="font-size:.65rem;vertical-align:middle">System Actor</span></div>
      <div class="text-xs text-muted">Automated system that scores and suggests alumni candidates for opportunities. All matching actions are logged in Audit Logs with actor type <strong>Matching Engine</strong>.</div>
    </div>
  </div>
</div>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
  <div class="card" style="padding:1rem;border-left:4px solid var(--danger)">
    <div class="fw-700" style="color:var(--danger);margin-bottom:.3rem">Super Admin</div>
    <div class="text-sm text-muted" style="margin-bottom:.6rem">Full control of the entire system. Manages users, data, and system configuration.</div>
    <div style="font-size:.72rem;color:var(--muted);line-height:1.8">
      Dashboard &middot; Alumni Records &middot; Opportunities &middot; Matching &middot; Selection &middot; Submissions &middot; Reports &middot; User Management &middot; Role Management &middot; System Settings &middot; Audit Logs
    </div>
  </div>
  <div class="card" style="padding:1rem;border-left:4px solid var(--primary)">
    <div class="fw-700" style="color:var(--primary);margin-bottom:.3rem">Alumni Admin</div>
    <div class="text-sm text-muted" style="margin-bottom:.6rem">Handles daily operations: alumni records, opportunities, matching, and submissions.</div>
    <div style="font-size:.72rem;color:var(--muted);line-height:1.8">
      Dashboard &middot; Alumni Records &middot; Opportunities &middot; Matching &middot; Selection &middot; Submissions &middot; Reports
    </div>
    <div style="font-size:.72rem;color:var(--danger);margin-top:.4rem">Cannot: manage users, change roles, access settings or audit logs</div>
  </div>
  <div class="card" style="padding:1rem;border-left:4px solid var(--info)">
    <div class="fw-700" style="color:var(--info);margin-bottom:.3rem">Reports Admin</div>
    <div class="text-sm text-muted" style="margin-bottom:.6rem">Focused on analytics, reporting, and performance tracking.</div>
    <div style="font-size:.72rem;color:var(--muted);line-height:1.8">
      Dashboard &middot; Alumni Records (View) &middot; Opportunities (View) &middot; Submissions (View) &middot; Reports
    </div>
    <div style="font-size:.72rem;color:var(--danger);margin-top:.4rem">Cannot: add/edit alumni, create opportunities, run matching, select or submit candidates, manage users or settings</div>
  </div>
</div>

<!-- ACCESS TABLE -->
<div class="card" style="margin-bottom:1.5rem" id="roles">
  <div class="card-header"><span class="card-title">Role Management — Menu Access</span></div>
  <div class="table-wrap" style="border:none">
    <table>
      <thead>
        <tr>
          <th>Menu</th>
          <th style="text-align:center">Super Admin</th>
          <th style="text-align:center">Alumni Admin</th>
          <th style="text-align:center">Reports Admin</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $access = [
          ['Dashboard',          true,  true,  true],
          ['Alumni Records',     true,  true,  'View'],
          ['Opportunities',      true,  true,  'View'],
          ['Candidate Matching', true,  true,  false],
          ['Candidate Selection',true,  true,  false],
          ['Submissions',        true,  true,  'View'],
          ['Reports',            true,  true,  true],
          ['User Management',    true,  false, false],
          ['Role Management',    true,  false, false],
          ['System Settings',    true,  false, false],
          ['Audit Logs',         true,  false, false],
          ['Profile',            true,  true,  true],
        ];
        foreach ($access as [$menu, $sa, $aa, $ra]):
          $cell = fn($v) => $v === true
            ? '<span class="badge badge-success">Yes</span>'
            : ($v === 'View' ? '<span class="badge badge-info">View Only</span>'
            : '<span class="badge badge-secondary">No</span>');
        ?>
        <tr>
          <td class="fw-600"><?= $menu ?></td>
          <td style="text-align:center"><?= $cell($sa) ?></td>
          <td style="text-align:center"><?= $cell($aa) ?></td>
          <td style="text-align:center"><?= $cell($ra) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- CREATE FORM -->
  <div class="card">
    <div class="card-header"><span class="card-title">Create Admin Account</span></div>
    <form method="POST">
      <input type="hidden" name="create" value="1">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" required placeholder="e.g. Jane Dlamini">
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="admin@wsu.ac.za">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="reports_admin">Reports Admin — View reports &amp; analytics only</option>
          <option value="admin" selected>Alumni Admin — Daily operations (records, matching, submissions)</option>
          <option value="super_admin">Super Admin — Full system control</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Create Account</button>
    </form>
  </div>

  <!-- ADMIN LIST -->
  <div class="card" style="padding:0">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
      <span class="card-title">All Admin Accounts</span>
    </div>
    <div class="table-wrap" style="border:none;border-radius:0">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th style="text-align:right">Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $a):
            [$label, $badge] = $role_labels[$a['role']];
            $is_self = $a['id'] == $_SESSION['user_id'];
            $is_super = $a['role'] === 'super_admin';
          ?>
          <tr>
            <td>
              <div class="fw-600"><?= htmlspecialchars($a['full_name']) ?></div>
              <?php if ($is_self): ?><span class="badge badge-secondary" style="margin-top:.2rem">You</span><?php endif; ?>
            </td>
            <td class="td-muted"><?= htmlspecialchars($a['email']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
            <td class="td-muted"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
            <td style="text-align:right">
              <?php if (!$is_self && !$is_super): ?>
              <div style="display:flex;gap:.4rem;justify-content:flex-end;align-items:center">
                <!-- Reset Password -->
                <button class="btn btn-outline btn-sm"
                  onclick="document.getElementById('reset-<?= $a['id'] ?>').style.display='block';this.style.display='none'">
                  Reset PW
                </button>
                <!-- Delete -->
                <a href="?delete=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Remove this admin account?')">Remove</a>
              </div>
              <!-- Reset PW inline form -->
              <form method="POST" id="reset-<?= $a['id'] ?>" style="display:none;margin-top:.5rem">
                <input type="hidden" name="reset_id" value="<?= $a['id'] ?>">
                <div style="display:flex;gap:.4rem">
                  <input type="password" name="new_password" placeholder="New password" required
                         style="flex:1;padding:.35rem .6rem;border:1px solid var(--border);border-radius:var(--radius);font-size:.8rem">
                  <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
              </form>
              <?php else: ?>
              <span class="text-xs text-muted"><?= $is_self ? 'Current session' : 'Protected' ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
