<?php
require_once '../includes/auth_guard.php';
require_min_role('super_admin');
require_once '../config/db.php';

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_email VARCHAR(150),
    action VARCHAR(100) NOT NULL,
    target VARCHAR(200),
    detail TEXT,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)");

$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 30;
$search  = trim($_GET['q'] ?? '');
$where   = $search ? "WHERE al.action LIKE ? OR al.user_email LIKE ? OR al.target LIKE ?" : "";
$params  = $search ? ["%$search%","%$search%","%$search%"] : [];

$total_s = $pdo->prepare("SELECT COUNT(*) FROM audit_logs al $where");
$total_s->execute($params);
$total   = (int)$total_s->fetchColumn();
$pages   = max(1, ceil($total / $per));
$page    = min($page, $pages);
$offset  = ($page - 1) * $per;

$logs = $pdo->prepare("SELECT al.*, u.full_name FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id $where ORDER BY al.created_at DESC LIMIT $per OFFSET $offset");
$logs->execute($params);
$logs = $logs->fetchAll();

$action_badge = [
    'login'               => 'badge-info',
    'logout'              => 'badge-secondary',
    'create_opportunity'  => 'badge-success',
    'update_opportunity'  => 'badge-warning',
    'delete_opportunity'  => 'badge-danger',
    'create_employer'     => 'badge-success',
    'update_employer'     => 'badge-warning',
    'delete_employer'     => 'badge-danger',
    'run_matching'        => 'badge-info',
    'candidate_selected'  => 'badge-success',
    'candidate_rejected'  => 'badge-danger',
    'submit_candidates'   => 'badge-primary',
    'outcome_accepted'    => 'badge-success',
    'outcome_rejected'    => 'badge-danger',
];

$actor_badge = [
    'super_admin'   => ['badge-danger',    'Super Admin'],
    'admin'         => ['badge-primary',   'Alumni Admin'],
    'reports_admin' => ['badge-info',      'Reports Admin'],
    'alumni'        => ['badge-secondary', 'Alumni'],
    'system'        => ['badge-warning',   'Matching Engine'],
];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Audit Logs</h1>
    <p><?= number_format($total) ?> total log entries</p>
  </div>
</div>

<!-- Search -->
<div class="card" style="margin-bottom:1rem">
  <form method="GET" style="display:flex;gap:.75rem;align-items:center">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by action, user or target…"
           style="flex:1;padding:.6rem .85rem;border:1px solid var(--border);border-radius:var(--r);font-size:.875rem;font-family:inherit">
    <button class="btn btn-primary btn-sm">Search</button>
    <?php if ($search): ?><a href="/gate-portal/admin/audit_logs.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<?php if (!$logs): ?>
<div class="card"><div class="empty-state"><p>No log entries found.</p></div></div>
<?php else: ?>
<div class="card" style="padding:0">
  <div class="table-wrap" style="border:none">
    <table>
      <thead>
        <tr><th>Time</th><th>Actor</th><th>User</th><th>Action</th><th>Target</th><th>Detail</th><th>IP</th></tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $l):
          $atype = $l['actor_type'] ?? 'admin';
          [$abadge, $alabel] = $actor_badge[$atype] ?? ['badge-secondary','Unknown'];
        ?>
        <tr>
          <td class="td-muted" style="white-space:nowrap"><?= date('d M Y H:i', strtotime($l['created_at'])) ?></td>
          <td><span class="badge <?= $abadge ?>" style="white-space:nowrap"><?= $alabel ?></span></td>
          <td>
            <div class="fw-600" style="font-size:.82rem"><?= htmlspecialchars($l['full_name'] ?? ($atype==='system'?'Auto':'—')) ?></div>
            <div class="td-muted"><?= htmlspecialchars($l['user_email'] ?? '') ?></div>
          </td>
          <td>
            <span class="badge <?= $action_badge[$l['action']] ?? 'badge-secondary' ?>">
              <?= htmlspecialchars(str_replace('_',' ',$l['action'])) ?>
            </span>
          </td>
          <td class="td-muted"><?= htmlspecialchars($l['target'] ?? '—') ?></td>
          <td class="td-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($l['detail'] ?? '') ?></td>
          <td class="td-muted"><?= htmlspecialchars($l['ip'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-top:1px solid var(--border-light);flex-wrap:wrap;gap:.5rem">
    <span class="text-sm text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$per,$total) ?> of <?= number_format($total) ?></span>
    <div style="display:flex;gap:.3rem">
      <?php if ($page>1): ?><a href="?q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm">← Prev</a><?php endif; ?>
      <?php for ($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
      <a href="?q=<?= urlencode($search) ?>&page=<?= $i ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-outline' ?>" style="min-width:32px;justify-content:center"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page<$pages): ?><a href="?q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm">Next →</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
