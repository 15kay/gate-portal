<?php
require_once '../includes/auth_guard.php';
require_min_role('admin');
require_once '../config/db.php';
require_once '../includes/csrf.php';

$success = $error = '';

// Ensure table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS portal_settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Now load settings helper (needs table to exist first)
require_once '../includes/settings.php';

// ── SAVE ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fields = [
        'portal_name', 'institution_name', 'contact_email',
        'contact_phone', 'registration_open', 'welcome_message', 'footer_text',
        // maintenance
        'maintenance_mode', 'maintenance_message',
        // email
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_encryption',
        // security
        'session_timeout', 'max_login_attempts',
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO portal_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()"
    );

    foreach ($fields as $f) {
        $stmt->execute([$f, trim($_POST[$f] ?? '')]);
    }

    header('Location: /gate-portal/admin/settings.php?saved=1');
    exit;
}

if (isset($_GET['saved'])) $success = 'Settings saved successfully.';

// ── LOAD current values ───────────────────────────────────
$s = [
    'portal_name'         => setting('portal_name',         'GATE Portal'),
    'institution_name'    => setting('institution_name',     'Walter Sisulu University'),
    'contact_email'       => setting('contact_email',        'alumni@wsu.ac.za'),
    'contact_phone'       => setting('contact_phone',        ''),
    'registration_open'   => setting('registration_open',    '1'),
    'welcome_message'     => setting('welcome_message',      ''),
    'footer_text'         => setting('footer_text',          ''),
    // maintenance
    'maintenance_mode'    => setting('maintenance_mode',     '0'),
    'maintenance_message' => setting('maintenance_message',  'The portal is currently undergoing scheduled maintenance. Please check back soon.'),
    // email
    'smtp_host'           => setting('smtp_host',            'smtp.office365.com'),
    'smtp_port'           => setting('smtp_port',            '587'),
    'smtp_user'           => setting('smtp_user',            ''),
    'smtp_pass'           => setting('smtp_pass',            ''),
    'smtp_from_name'      => setting('smtp_from_name',       'GATE Portal — WSU'),
    'smtp_encryption'     => setting('smtp_encryption',      'tls'),
    // security
    'session_timeout'     => setting('session_timeout',      '60'),
    'max_login_attempts'  => setting('max_login_attempts',   '5'),
];

// ── SYSTEM STATS ──────────────────────────────────────────
$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM users WHERE role='alumni') AS alumni,
      (SELECT COUNT(*) FROM users WHERE role IN ('super_admin','admin','reports_admin')) AS admins,
      (SELECT COUNT(*) FROM student_registry) AS registry,
      (SELECT COUNT(*) FROM student_registry WHERE is_registered=1) AS reg_done,
      (SELECT COUNT(*) FROM employment_records WHERE is_current=1) AS employed,
      (SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()) AS upcoming_events,
      (SELECT COUNT(*) FROM messages) AS messages
")->fetch();

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Portal Settings</h1>
    <p>Configure system-wide settings for the GATE Portal</p>
  </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
  <?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start">

  <!-- ── LEFT: FORM ─────────────────────────────────────── -->
  <div>
    <form method="POST">
      <?= csrf_field() ?>

      <!-- General -->
      <div class="card">
        <div class="card-header"><span class="card-title">General</span></div>
        <div class="form-row">
          <div class="form-group">
            <label>Portal Name</label>
            <input type="text" name="portal_name" value="<?= htmlspecialchars($s['portal_name']) ?>" required>
            <div class="form-hint">Shown in the browser tab and topbar.</div>
          </div>
          <div class="form-group">
            <label>Institution Name</label>
            <input type="text" name="institution_name" value="<?= htmlspecialchars($s['institution_name']) ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="contact_email" value="<?= htmlspecialchars($s['contact_email']) ?>">
            <div class="form-hint">Shown on the registration page for support.</div>
          </div>
          <div class="form-group">
            <label>Contact Phone</label>
            <input type="text" name="contact_phone" value="<?= htmlspecialchars($s['contact_phone']) ?>" placeholder="+27 XX XXX XXXX">
          </div>
        </div>
        <div class="form-group">
          <label>Welcome Message</label>
          <textarea name="welcome_message" rows="3" placeholder="e.g. Welcome to the WSU Alumni Portal! Keep your profile up to date."><?= htmlspecialchars($s['welcome_message']) ?></textarea>
          <div class="form-hint">Displayed as a banner on the alumni dashboard. Leave blank to hide.</div>
        </div>
        <div class="form-group">
          <label>Footer Text</label>
          <input type="text" name="footer_text" value="<?= htmlspecialchars($s['footer_text']) ?>" placeholder="© <?= date('Y') ?> Walter Sisulu University. All rights reserved.">
          <div class="form-hint">Shown at the bottom of every page. Leave blank for default.</div>
        </div>
      </div>

      <!-- Maintenance Mode -->
      <?php $maint_on = $s['maintenance_mode'] === '1'; ?>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Maintenance Mode</span>
          <span id="maint-badge" class="badge <?= $maint_on ? 'badge-danger' : 'badge-success' ?>">
            <?= $maint_on ? 'ON' : 'OFF' ?>
          </span>
        </div>

        <input type="hidden" name="maintenance_mode" id="maintenance_mode_val" value="<?= $maint_on ? '1' : '0' ?>">

        <!-- Toggle row -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1rem;border:1px solid var(--border);border-radius:var(--r);margin-bottom:1rem;background:var(--bg)">
          <div style="display:flex;align-items:center;gap:.65rem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--accent);flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
              <div style="font-size:.875rem;font-weight:600;color:var(--text)">Enable Maintenance Mode</div>
              <div style="font-size:.75rem;color:var(--muted)">Alumni are redirected to a maintenance page. Admins can still log in.</div>
            </div>
          </div>
          <!-- Toggle switch -->
          <div id="maint-toggle-track"
            style="width:48px;height:26px;border-radius:13px;cursor:pointer;flex-shrink:0;position:relative;transition:background .2s;background:<?= $maint_on ? 'var(--danger)' : 'var(--border)' ?>"
            onclick="
              var on = document.getElementById('maintenance_mode_val').value === '1' ? false : true;
              document.getElementById('maintenance_mode_val').value = on ? '1' : '0';
              this.style.background = on ? 'var(--danger)' : 'var(--border)';
              document.getElementById('maint-thumb').style.transform = on ? 'translateX(22px)' : 'translateX(0)';
              document.getElementById('maint-badge').textContent = on ? 'ON' : 'OFF';
              document.getElementById('maint-badge').className = on ? 'badge badge-danger' : 'badge badge-success';
              document.getElementById('maint-status-bar').style.display = on ? 'flex' : 'none';
            ">
            <div id="maint-thumb" style="position:absolute;top:4px;left:4px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s;transform:<?= $maint_on ? 'translateX(22px)' : 'translateX(0)' ?>"></div>
          </div>
        </div>

        <!-- Status bar -->
        <div id="maint-status-bar" style="display:<?= $maint_on ? 'flex' : 'none' ?>;align-items:center;gap:.6rem;padding:.7rem 1rem;border-radius:var(--r);background:#fef2f2;border:1px solid #fecaca;margin-bottom:1rem">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <span class="text-sm fw-600" style="color:var(--danger)">Maintenance mode is active — alumni cannot access the portal</span>
        </div>

        <div class="form-group">
          <label>Maintenance Message</label>
          <textarea name="maintenance_message" rows="2"><?= htmlspecialchars($s['maintenance_message']) ?></textarea>
          <div class="form-hint">Shown to alumni when maintenance mode is active.</div>
        </div>
      </div>

      <!-- Registration -->
      <div class="card">
        <div class="card-header"><span class="card-title">Registration Control</span></div>
        <div class="form-group">
          <label>Alumni Self-Registration</label>
          <select name="registration_open">
            <option value="1" <?= $s['registration_open']==='1'?'selected':'' ?>>Open — Alumni can register</option>
            <option value="0" <?= $s['registration_open']==='0'?'selected':'' ?>>Closed — Registration disabled</option>
          </select>
          <div class="form-hint">
            When <strong>Closed</strong>, the registration page shows a maintenance message with your contact email.
            Existing alumni can still log in.
          </div>
        </div>

        <!-- Live status indicator -->
        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:var(--r);background:<?= $s['registration_open']==='1' ? '#f0fdf4' : '#fef2f2' ?>;border:1px solid <?= $s['registration_open']==='1' ? '#bbf7d0' : '#fecaca' ?>">
          <div style="width:10px;height:10px;border-radius:50%;background:<?= $s['registration_open']==='1' ? 'var(--success)' : 'var(--danger)' ?>"></div>
          <span class="text-sm fw-600" style="color:<?= $s['registration_open']==='1' ? 'var(--success)' : 'var(--danger)' ?>">
            Registration is currently <?= $s['registration_open']==='1' ? 'OPEN' : 'CLOSED' ?>
          </span>
        </div>
      </div>

      <!-- Email (Microsoft 365 / Office 365) -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Email — Microsoft 365 (Office 365)</span>
          <span class="badge badge-info" style="font-size:.68rem">Office 365 SMTP</span>
        </div>

        <!-- Info banner -->
        <div class="alert alert-info" style="margin-bottom:1.25rem">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>Use a <strong>shared mailbox</strong> or <strong>licensed Microsoft 365 account</strong> (e.g. <code>noreply@wsu.ac.za</code>).
          SMTP AUTH must be enabled for the account in the <a href="https://admin.microsoft.com" target="_blank" style="color:inherit;font-weight:600">Microsoft 365 Admin Centre</a>.
          If your tenant enforces Modern Auth only, use an <strong>App Password</strong> or configure OAuth2.</span>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>SMTP Host</label>
            <input type="text" name="smtp_host"
              value="<?= htmlspecialchars($s['smtp_host'] ?: 'smtp.office365.com') ?>">
            <div class="form-hint">Office 365 standard host — do not change unless advised by IT.</div>
          </div>
          <div class="form-group">
            <label>SMTP Port</label>
            <input type="number" name="smtp_port"
              value="<?= htmlspecialchars($s['smtp_port'] ?: '587') ?>">
            <div class="form-hint">587 with STARTTLS is required by Office 365.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Office 365 Email Address <span style="color:var(--danger)">*</span></label>
            <input type="email" name="smtp_user"
              value="<?= htmlspecialchars($s['smtp_user']) ?>"
              placeholder="noreply@wsu.ac.za" autocomplete="off" required>
            <div class="form-hint">The full UPN / email of the sending account or shared mailbox.</div>
          </div>
          <div class="form-group">
            <label>Password / App Password <span style="color:var(--danger)">*</span></label>
            <input type="password" name="smtp_pass"
              value="<?= htmlspecialchars($s['smtp_pass']) ?>"
              placeholder="••••••••" autocomplete="new-password">
            <div class="form-hint">If MFA is enabled, generate an <strong>App Password</strong> in the Microsoft account security settings.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>From Name</label>
            <input type="text" name="smtp_from_name"
              value="<?= htmlspecialchars($s['smtp_from_name'] ?: 'GATE Portal — WSU') ?>"
              placeholder="GATE Portal — WSU">
          </div>
          <div class="form-group">
            <label>Encryption</label>
            <select name="smtp_encryption">
              <option value="tls" selected>STARTTLS — required for Office 365 port 587</option>
            </select>
            <div class="form-hint">Office 365 mandates STARTTLS on port 587. This cannot be changed.</div>
          </div>
        </div>

        <!-- Office 365 quick-reference -->
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--r);padding:.85rem 1rem;font-size:.8rem;line-height:1.9">
          <div style="font-weight:700;margin-bottom:.35rem;color:var(--text);display:flex;align-items:center;gap:.4rem">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Office 365 SMTP Quick Reference
          </div>
          <div style="display:grid;grid-template-columns:auto 1fr;gap:.1rem .75rem;color:var(--text-2)">
            <span style="color:var(--muted)">Host</span>       <strong>smtp.office365.com</strong>
            <span style="color:var(--muted)">Port</span>       <strong>587</strong>
            <span style="color:var(--muted)">Encryption</span><strong>STARTTLS</strong>
            <span style="color:var(--muted)">Auth</span>       <strong>Username + Password (or App Password)</strong>
            <span style="color:var(--muted)">Requires</span>  <strong>SMTP AUTH enabled per-mailbox in M365 Admin</strong>
          </div>
        </div>
      </div>

      <!-- Security -->
      <div class="card">
        <div class="card-header"><span class="card-title">Security</span></div>
        <div class="form-row">
          <div class="form-group">
            <label>Session Timeout (minutes)</label>
            <input type="number" name="session_timeout" value="<?= htmlspecialchars($s['session_timeout']) ?>" min="5" max="480">
            <div class="form-hint">Idle sessions are logged out after this period.</div>
          </div>
          <div class="form-group">
            <label>Max Login Attempts</label>
            <input type="number" name="max_login_attempts" value="<?= htmlspecialchars($s['max_login_attempts']) ?>" min="3" max="20">
            <div class="form-hint">Account locked after this many failed attempts.</div>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Settings
      </button>
    </form>
  </div>

  <!-- ── RIGHT: STATS + LINKS ───────────────────────────── -->
  <div>

    <!-- System Overview -->
    <div class="card">
      <div class="card-header"><span class="card-title">System Overview</span></div>
      <div style="display:flex;flex-direction:column;gap:.45rem">
        <?php
        $rows = [
          ['Alumni Registered',    $stats['alumni'],          'badge-primary'],
          ['Admin Accounts',       $stats['admins'],          'badge-info'],
          ['Student Registry',     $stats['registry'],        'badge-secondary'],
          ['Registry Activated',   $stats['reg_done'],        'badge-success'],
          ['Currently Employed',   $stats['employed'],        'badge-success'],
          ['Upcoming Events',      $stats['upcoming_events'], 'badge-warning'],
          ['Total Messages Sent',  $stats['messages'],        'badge-secondary'],
        ];
        foreach ($rows as [$label, $val, $badge]):
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.55rem .75rem;background:var(--bg);border-radius:var(--r)">
          <span class="text-sm text-muted"><?= $label ?></span>
          <span class="badge <?= $badge ?>"><?= number_format($val) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-header"><span class="card-title">Quick Links</span></div>
      <div style="display:flex;flex-direction:column;gap:.4rem">
        <a href="/gate-portal/admin/manage_admins.php" class="btn btn-outline btn-sm" style="justify-content:flex-start">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
          Manage Admins
        </a>
        <a href="/gate-portal/admin/student_registry.php" class="btn btn-outline btn-sm" style="justify-content:flex-start">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Student Registry
        </a>
        <a href="/gate-portal/admin/reports.php?export=1" class="btn btn-outline btn-sm" style="justify-content:flex-start">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export Alumni CSV
        </a>
        <a href="/gate-portal/admin/change_password.php" class="btn btn-outline btn-sm" style="justify-content:flex-start">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          Change Password
        </a>
      </div>
    </div>

    <!-- System Info -->
    <div class="card" style="background:var(--primary);border-color:var(--primary)">
      <div style="color:rgba(255,255,255,.55);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:.6rem">System Info</div>
      <div style="color:#fff;font-size:.82rem;line-height:2">
        <div style="display:flex;justify-content:space-between"><span style="opacity:.6">PHP</span><strong><?= PHP_VERSION ?></strong></div>
        <div style="display:flex;justify-content:space-between"><span style="opacity:.6">Server</span><strong><?= php_uname('s') ?></strong></div>
        <div style="display:flex;justify-content:space-between"><span style="opacity:.6">Date</span><strong><?= date('d M Y H:i') ?></strong></div>
        <div style="display:flex;justify-content:space-between"><span style="opacity:.6">Version</span><strong>GATE Portal v1.0</strong></div>
      </div>
    </div>

  </div>
</div>

<?php include '../includes/footer.php'; ?>
