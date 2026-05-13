<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role    = $_SESSION['role'] ?? '';
$name    = $_SESSION['full_name'] ?? '';
$initial = strtoupper(substr($name, 0, 1));
$current = $_SERVER['PHP_SELF'];

$unread_nav = 0;
if (!empty($_SESSION['user_id'])) {
    require_once dirname(__DIR__) . '/config/db.php';
    require_once dirname(__DIR__) . '/includes/settings.php';
    $uid = $_SESSION['user_id'];
    $u = $pdo->prepare("SELECT COUNT(*) FROM messages m LEFT JOIN message_reads mr ON mr.message_id=m.id AND mr.user_id=? WHERE (m.recipient_id=? OR m.is_broadcast=1) AND mr.id IS NULL");
    $u->execute([$uid, $uid]);
    $unread_nav = (int)$u->fetchColumn();
}

function nav_active(string $path): string {
    global $current;
    return str_contains($current, $path) ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars(setting('portal_name','GATE Portal')) ?> — <?= htmlspecialchars(setting('institution_name','Walter Sisulu University')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php if ($role): ?>
<header class="topbar">
  <button class="topbar-hamburger" id="hamburger-btn" aria-label="Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>

  <a href="/<?= $role === 'alumni' ? 'alumni' : 'admin' ?>/dashboard.php" class="topbar-brand">
    <img src="/wsu-logo.svg" alt="WSU">
  </a>

  <div class="topbar-center">
    <strong><?= htmlspecialchars(setting('portal_name','GATE Portal')) ?></strong>
    <span>Graduate &amp; Alumni Tracking &amp; Engagement</span>
  </div>

  <div class="topbar-right">
    <a href="/<?= $role === 'alumni' ? 'alumni' : ($role === 'employer' ? 'employer' : 'admin') ?>/<?= $role === 'employer' ? 'dashboard' : 'messages' ?>.php" class="topbar-icon-btn" title="Messages">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <?php if ($unread_nav > 0): ?>
      <span class="topbar-badge"><?= $unread_nav > 9 ? '9+' : $unread_nav ?></span>
      <?php endif; ?>
    </a>

    <div class="topbar-profile" id="topbar-profile">
      <button class="topbar-profile-btn" id="profile-btn" aria-expanded="false">
        <div class="topbar-avatar"><?= $initial ?></div>
        <div class="topbar-user-info">
          <strong><?= htmlspecialchars($name) ?></strong>
          <span><?= str_replace('_', ' ', $role) ?></span>
        </div>
        <svg class="topbar-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </button>

      <div class="topbar-dropdown" id="topbar-dropdown">
        <div class="topbar-dd-header">
          <div class="topbar-dd-avatar"><?= $initial ?></div>
          <div>
            <div class="fw-600" style="font-size:.88rem;color:var(--text)"><?= htmlspecialchars($name) ?></div>
            <div style="font-size:.72rem;color:var(--muted)"><?= ucwords(str_replace('_', ' ', $role)) ?></div>
          </div>
        </div>
        <div class="topbar-dd-divider"></div>
        <?php if ($role === 'alumni'): ?>
        <a href="/alumni/profile.php" class="topbar-dd-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>My Profile
        </a>
        <a href="/alumni/change_password.php" class="topbar-dd-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Change Password
        </a>
        <?php else: ?>
        <a href="/admin/settings.php" class="topbar-dd-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>Settings
        </a>
        <a href="/admin/change_password.php" class="topbar-dd-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Change Password
        </a>
        <?php endif; ?>
        <div class="topbar-dd-divider"></div>
        <button onclick="openSignout()" class="topbar-dd-item topbar-dd-danger" style="width:100%;background:none;border:none;cursor:pointer;font-family:inherit;text-align:left">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Sign Out
        </button>
      </div>
    </div>
  </div>
</header>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-nav">

  <?php $is_admin = in_array($role, ['super_admin','admin','reports_admin']); ?>
  <?php if ($is_admin): ?>

    <!-- MAIN -->
    <div class="sidebar-section">
      <span class="sidebar-label">Main</span>
      <a href="/admin/dashboard.php" class="sidebar-link<?= nav_active('dashboard') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
    </div>

    <!-- ALUMNI & OPPORTUNITIES -->
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Records</span>

      <?php if (can_view_alumni()): ?>
      <a href="/admin/alumni.php" class="sidebar-link<?= nav_active('alumni') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Alumni Records
        <?php if (!can_manage_alumni()): ?><span class="badge badge-secondary" style="margin-left:auto;font-size:.6rem">View</span><?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if (can_view_opportunities()): ?>
      <a href="/admin/opportunities.php" class="sidebar-link<?= nav_active('opportunities') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Opportunities
        <?php if (!can_manage_opportunities()): ?><span class="badge badge-secondary" style="margin-left:auto;font-size:.6rem">View</span><?php endif; ?>
      </a>
      <?php endif; ?>
    </div>

    <!-- MATCHING & SUBMISSIONS -->
    <?php if (can_run_matching()): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Placement</span>
      <a href="/admin/matching.php" class="sidebar-link<?= nav_active('matching') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Candidate Matching
      </a>
      <a href="/admin/candidate_selection.php" class="sidebar-link<?= nav_active('candidate_selection') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Candidate Selection
      </a>
      <a href="/admin/submissions.php" class="sidebar-link<?= nav_active('submissions') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
        Submissions
      </a>
    </div>
    <?php elseif (can_view_submissions()): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Placement</span>
      <a href="/admin/submissions.php" class="sidebar-link<?= nav_active('submissions') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
        Submissions
        <span class="badge badge-secondary" style="margin-left:auto;font-size:.6rem">View</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- REPORTS -->
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Analytics</span>
      <a href="/admin/reports.php" class="sidebar-link<?= nav_active('reports') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Reports
      </a>
      <a href="/admin/alumni_map.php" class="sidebar-link<?= nav_active('alumni_map') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Alumni Map
      </a>
    </div>

    <!-- SUPER ADMIN ONLY -->
    <?php if (can_manage_users()): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Administration</span>
      <a href="/admin/manage_admins.php" class="sidebar-link<?= nav_active('manage_admins') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        User Management
      </a>
      <a href="/admin/manage_admins.php#roles" class="sidebar-link<?= nav_active('manage_admins') && isset($_GET['tab']) && $_GET['tab']==='roles' ? ' active' : '' ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/><polyline points="16 11 18 13 22 9"/></svg>
        Role Management
      </a>
      <a href="/admin/student_registry.php" class="sidebar-link<?= nav_active('student_registry') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Student Registry
      </a>
      <a href="/admin/employers.php" class="sidebar-link<?= nav_active('employers') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        Employers
      </a>
      <a href="/admin/settings.php" class="sidebar-link<?= nav_active('settings') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        System Settings
      </a>
      <a href="/admin/audit_logs.php" class="sidebar-link<?= nav_active('audit_logs') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Audit Logs
      </a>
    </div>
    <?php endif; ?>

    <!-- ACCOUNT -->
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Account</span>
      <a href="/admin/change_password.php" class="sidebar-link<?= nav_active('change_password') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Change Password
      </a>
    </div>

  <?php elseif ($role === 'employer'): ?>

    <div class="sidebar-section">
      <span class="sidebar-label">My Portal</span>
      <a href="/employer/dashboard.php" class="sidebar-link<?= nav_active('dashboard') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="/employer/jobs.php" class="sidebar-link<?= nav_active('jobs') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        My Job Postings
      </a>
      <a href="/employer/shortlist.php" class="sidebar-link<?= nav_active('shortlist') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
        Shortlisted Candidates
        <?php
        if ($role === 'employer') {
            try {
                if (!isset($pdo)) require_once dirname(__DIR__).'/config/db.php';
                $emp_r = $pdo->prepare("SELECT id FROM employers WHERE user_id=?");
                $emp_r->execute([$_SESSION['user_id']]);
                $emp_r = $emp_r->fetch();
                if ($emp_r) {
                    $sc = $pdo->prepare("SELECT COUNT(DISTINCT cs.opportunity_id) FROM candidate_submissions cs JOIN opportunities o ON o.id=cs.opportunity_id WHERE o.employer_id=? AND cs.employer_released=1");
                    $sc->execute([$emp_r['id']]);
                    $scount = (int)$sc->fetchColumn();
                    if ($scount > 0) echo '<span class="badge badge-success" style="margin-left:auto;font-size:.6rem">'.$scount.'</span>';
                }
            } catch (Throwable $e) {}
        }
        ?>
      </a>
    </div>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Account</span>
      <a href="/employer/change_password.php" class="sidebar-link<?= nav_active('change_password') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Change Password
      </a>
    </div>

  <?php elseif ($role === 'alumni'): ?>

    <div class="sidebar-section">
      <span class="sidebar-label">My Portal</span>
      <a href="/alumni/dashboard.php" class="sidebar-link<?= nav_active('dashboard') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>Dashboard
      </a>
      <a href="/alumni/profile.php" class="sidebar-link<?= nav_active('profile') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>My Profile
      </a>
      <a href="/alumni/employment.php" class="sidebar-link<?= nav_active('employment') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>Employment
      </a>
      <a href="/alumni/cv_builder.php" class="sidebar-link<?= nav_active('cv_builder') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>CV Builder
      </a>
      <a href="/alumni/job_match.php" class="sidebar-link<?= nav_active('job_match') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>Job Matches
      </a>
    </div>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Community</span>
      <a href="/alumni/directory.php" class="sidebar-link<?= nav_active('directory') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Alumni Directory
      </a>
      <a href="/alumni/events.php" class="sidebar-link<?= nav_active('events') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Events
      </a>
      <a href="/alumni/messages.php" class="sidebar-link<?= nav_active('messages') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages
        <?php if ($unread_nav > 0): ?><span class="badge badge-danger" style="margin-left:auto;font-size:.65rem"><?= $unread_nav ?></span><?php endif; ?>
      </a>
    </div>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">
      <span class="sidebar-label">Account</span>
      <a href="/alumni/change_password.php" class="sidebar-link<?= nav_active('change_password') ?>">
        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Change Password
      </a>
    </div>

  <?php endif; ?>
  </div><!-- end .sidebar-nav -->

  <!-- SIDEBAR BOTTOM: sign out -->
  <div class="sidebar-bottom">
    <button class="sidebar-signout-btn" onclick="openSignout()">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Sign Out
    </button>
    <div class="sidebar-footer-text">&copy; <?= date('Y') ?> Walter Sisulu University · GATE Portal v1.0</div>
  </div>
</aside>

<!-- SIGN OUT CONFIRM MODAL -->
<div class="signout-modal" id="signout-modal">
  <div class="signout-modal-box">
    <div style="width:52px;height:52px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </div>
    <h3>Sign Out?</h3>
    <p>Are you sure you want to sign out of GATE Portal?</p>
    <div class="signout-modal-actions">
      <button onclick="closeSignout()" class="btn btn-outline">Cancel</button>
      <a href="/auth/logout.php" class="btn btn-danger">Yes, Sign Out</a>
    </div>
  </div>
</div>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<main class="main-content">
<?php else: ?>
<main>
<?php endif; ?>

<script>
// Sidebar toggle
const hamburger = document.getElementById('hamburger-btn');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('sidebar-overlay');
if (hamburger) {
  hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('open');
  });
}
if (overlay) overlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
});

// Profile dropdown
const profileBtn = document.getElementById('profile-btn');
const dropdown   = document.getElementById('topbar-dropdown');
if (profileBtn) {
  profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = dropdown.classList.toggle('open');
    profileBtn.setAttribute('aria-expanded', open);
  });
  document.addEventListener('click', () => {
    dropdown.classList.remove('open');
    profileBtn.setAttribute('aria-expanded', 'false');
  });
}

// Sign out modal
function openSignout()  { document.getElementById('signout-modal').classList.add('open'); }
function closeSignout() { document.getElementById('signout-modal').classList.remove('open'); }
document.getElementById('signout-modal')?.addEventListener('click', function(e) {
  if (e.target === this) closeSignout();
});
</script>
