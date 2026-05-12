<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';
require_once '../includes/csrf.php';
require_once '../includes/mailer.php';
require_once '../includes/settings.php';

$uid     = $_SESSION['user_id'];
$success = $error = $email_msg = $email_err = '';

// Load profile
$stmt = $pdo->prepare("SELECT ap.*, u.full_name, u.email FROM alumni_profiles ap JOIN users u ON u.id=ap.user_id WHERE ap.user_id=?");
$stmt->execute([$uid]);
$p = $stmt->fetch();

// Load faculties + departments from DB
$faculties   = $pdo->query("SELECT * FROM faculties ORDER BY name")->fetchAll();
$departments = $pdo->query("SELECT * FROM departments ORDER BY faculty_id, name")->fetchAll();
// Group departments by faculty_id for JS
$depts_by_fac = [];
foreach ($departments as $d) $depts_by_fac[$d['faculty_id']][] = ['id'=>$d['id'],'name'=>$d['name']];

// Load employment for sidebar stats
$jobs = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC, start_date DESC");
$jobs->execute([$uid]);
$jobs = $jobs->fetchAll();

// Load CV for sidebar stats
$cv = $pdo->prepare("SELECT cv_file, skills FROM alumni_cv WHERE user_id=?");
$cv->execute([$uid]);
$cv = $cv->fetch() ?: [];

// ── EMAIL CHANGE REQUEST ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_email'])) {
    csrf_verify();
    $new_email = strtolower(trim($_POST['new_email'] ?? ''));
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Please enter a valid email address.';
    } elseif ($new_email === strtolower($p['email'])) {
        $email_err = 'That is already your current email address.';
    } else {
        // Check not taken by another user
        $taken = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $taken->execute([$new_email, $uid]);
        if ($taken->fetch()) {
            $email_err = 'That email address is already in use by another account.';
        } else {
            // Delete any previous pending request for this user
            $pdo->prepare("DELETE FROM email_verifications WHERE user_id=?")->execute([$uid]);
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
            $pdo->prepare("INSERT INTO email_verifications (user_id,new_email,token,expires_at) VALUES (?,?,?,?)")
                ->execute([$uid, $new_email, $token, $expires]);

            $portal = 'http://'.($_SERVER['HTTP_HOST'] ?? 'localhost');
            $link   = $portal.'/alumni/verify_email.php?token='.$token;
            $name   = htmlspecialchars($p['full_name']);
            $html   = "
            <div style='font-family:Arial,sans-serif;max-width:560px;margin:0 auto'>
              <div style='background:#5B1C16;padding:20px 28px;border-radius:8px 8px 0 0'>
                <h2 style='color:#fff;margin:0'>GATE Portal — Verify New Email</h2>
              </div>
              <div style='background:#fff;padding:24px 28px;border:1px solid #e4e4e7;border-top:none;border-radius:0 0 8px 8px'>
                <p>Hi <strong>{$name}</strong>,</p>
                <p>You requested to change your email address to <strong>{$new_email}</strong>.</p>
                <p>Click the button below to confirm. This link expires in <strong>2 hours</strong>.</p>
                <p style='text-align:center;margin:28px 0'>
                  <a href='{$link}' style='background:#5B1C16;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block'>Verify New Email</a>
                </p>
                <p style='color:#71717a;font-size:.82rem'>If you did not request this, ignore this email — your address will not change.</p>
              </div>
            </div>";

            $sent = send_mail($new_email, 'Verify your new email address — GATE Portal', $html);
            $email_msg = $sent
                ? "A verification link has been sent to <strong>{$new_email}</strong>. Check your inbox and click the link to confirm."
                : "We couldn't send the verification email right now. Please try again later or contact the Alumni Office at <strong>" . htmlspecialchars(setting('contact_email','alumni@wsu.ac.za')) . "</strong>."; 
        }
    }
}

// Derive current employment status from records
$emp_status = '';
foreach ($jobs as $j) { if ($j['is_current']) { $emp_status = $j['employment_type']; break; } }

// ── SAVE PROFILE ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    csrf_verify();
    $photo_path = $p['profile_photo'] ?? '';

    if (!empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE   => 'Photo exceeds the maximum allowed size.',
                UPLOAD_ERR_FORM_SIZE  => 'Photo exceeds the form size limit.',
                UPLOAD_ERR_PARTIAL    => 'Photo upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error. Please contact support.',
                UPLOAD_ERR_CANT_WRITE => 'Could not save the photo. Please try again.',
            ];
            $error = $upload_errors[$_FILES['photo']['error']] ?? 'Photo upload failed. Please try again.';
        } elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
            $error = 'Photo must be under 3 MB.';
        } else {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $error = 'Invalid image format. Please use JPG, PNG, GIF or WebP.';
            } else {
                $dir = __DIR__ . '/../uploads/photos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'photo_'.$uid.'_'.time().'.'.$ext;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dir.$fname)) {
                    $error = 'Could not save the photo. Please try again.';
                } else {
                    $photo_path = 'uploads/photos/'.$fname;
                }
            }
        }
    }

    if (!$error) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $gender     = in_array($_POST['gender'] ?? '', ['Male','Female','Non-binary','Prefer not to say']) ? $_POST['gender'] : null;
        $location   = trim($_POST['location'] ?? '');
        $linkedin   = trim($_POST['linkedin_url'] ?? '');
        $emp_types  = ['Full-time','Part-time','Self-employed','Freelance','Unemployed','Further Studies'];
        $new_status = in_array($_POST['employment_status'] ?? '', $emp_types) ? $_POST['employment_status'] : null;

        if (!$full_name) {
            $error = 'Full name is required.';
        } elseif ($linkedin && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid LinkedIn URL (e.g. https://linkedin.com/in/yourname).';
        } else {
            try {
                $pdo->prepare("UPDATE alumni_profiles
                    SET student_id=?,id_number=?,phone=?,gender=?,location=?,graduation_year=?,degree=?,faculty=?,department=?,bio=?,linkedin_url=?,profile_photo=?
                    WHERE user_id=?")
                    ->execute([
                        trim($_POST['student_id'] ?? ''),
                        trim($_POST['id_number']  ?? ''),
                        $phone, $gender, $location,
                        $_POST['graduation_year'] ?: null,
                        trim($_POST['degree']     ?? ''),
                        trim($_POST['faculty']    ?? ''),
                        trim($_POST['department'] ?? ''),
                        trim($_POST['bio']        ?? ''),
                        $linkedin, $photo_path, $uid,
                    ]);
                $pdo->prepare("UPDATE users SET full_name=? WHERE id=?")->execute([$full_name, $uid]);
                $_SESSION['full_name'] = $full_name;

                // Sync employment status: update current record or insert a bare one
                if ($new_status) {
                    $cur = $pdo->prepare("SELECT id FROM employment_records WHERE user_id=? AND is_current=1 LIMIT 1");
                    $cur->execute([$uid]);
                    if ($cur_id = $cur->fetchColumn()) {
                        $pdo->prepare("UPDATE employment_records SET employment_type=? WHERE id=?")->execute([$new_status, $cur_id]);
                    } else {
                        $pdo->prepare("INSERT INTO employment_records (user_id, employment_type, is_current) VALUES (?,?,1)")->execute([$uid, $new_status]);
                    }
                    // Reload jobs so sidebar reflects change
                    $jobs_stmt = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC, start_date DESC");
                    $jobs_stmt->execute([$uid]);
                    $jobs = $jobs_stmt->fetchAll();
                    $emp_status = $new_status;
                }

                $success = 'Profile updated successfully.';
                $stmt->execute([$uid]);
                $p = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'Failed to save profile. Please try again.';
            }
        }
    }
}

// Completeness %
$fields   = ['full_name','phone','gender','location','graduation_year','degree','faculty','department','bio','linkedin_url','profile_photo'];
$filled   = count(array_filter($fields, fn($f) => !empty($p[$f])));
$complete = round($filled / count($fields) * 100);

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>My Profile</h1>
    <p>Keep your information up to date to improve your job match score</p>
  </div>
  <div class="page-header-actions">
    <a href="/alumni/cv_builder.php" class="btn btn-outline btn-sm">CV Builder</a>
    <a href="/alumni/employment.php" class="btn btn-primary btn-sm">Employment</a>
  </div>
</div>

<?php if (isset($_GET['email_verified'])): ?>
<div class="alert alert-success">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
  Your email address has been updated successfully. Please use your new email to log in next time.
</div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
<?= csrf_field() ?>
<div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start">

  <!-- ── LEFT SIDEBAR ── -->
  <div style="display:flex;flex-direction:column;gap:1rem">

    <!-- Avatar card -->
    <div class="card" style="text-align:center;padding:1.75rem 1.25rem">
      <div style="position:relative;width:96px;margin:0 auto 1rem">
        <?php if (!empty($p['profile_photo'])): ?>
        <img id="photo-preview" src="/<?= htmlspecialchars($p['profile_photo']) ?>"
             style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--border);display:block" alt="">
        <?php else: ?>
        <div id="photo-placeholder" style="width:96px;height:96px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.4rem;font-weight:700">
          <?= strtoupper(substr($p['full_name'],0,1)) ?>
        </div>
        <img id="photo-preview" src="" style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--border);display:none" alt="">
        <?php endif; ?>
        <label style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;cursor:pointer;border:2px solid #fff">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <input type="file" name="photo" id="photo-input" accept="image/*" style="display:none" onchange="previewPhoto(this)">
        </label>
      </div>

      <div class="fw-700" style="font-size:1rem"><?= htmlspecialchars($p['full_name']) ?></div>
      <div class="text-sm text-muted" style="margin:.2rem 0"><?= htmlspecialchars($p['email']) ?></div>
      <?php if (!empty($p['degree'])): ?>
      <div class="text-xs text-muted"><?= htmlspecialchars($p['degree']) ?></div>
      <?php endif; ?>
      <?php if (!empty($p['faculty'])): ?>
      <div class="text-xs text-muted" style="margin-top:.2rem"><?= htmlspecialchars($p['faculty']) ?></div>
      <?php endif; ?>
      <?php if (!empty($p['location'])): ?>
      <div class="text-xs text-muted" style="margin-top:.2rem">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <?= htmlspecialchars($p['location']) ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($p['linkedin_url'])): ?>
      <a href="<?= htmlspecialchars($p['linkedin_url']) ?>" target="_blank"
         style="display:inline-flex;align-items:center;gap:.3rem;color:#0077b5;font-size:.78rem;margin-top:.6rem;text-decoration:none">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
        LinkedIn
      </a>
      <?php endif; ?>
      <div id="photo-selected" class="text-xs text-muted" style="display:none;margin-top:.5rem">Photo selected — save to apply</div>
    </div>

    <!-- Profile completeness -->
    <div class="card">
      <div class="card-header"><span class="card-title">Profile Completeness</span></div>
      <div style="margin-bottom:.6rem">
        <div style="display:flex;justify-content:space-between;margin-bottom:.35rem">
          <span class="text-sm"><?= $complete ?>% complete</span>
          <?php if ($complete < 100): ?>
          <span class="text-xs text-muted"><?= count($fields)-$filled ?> field<?= (count($fields)-$filled)!=1?'s':'' ?> missing</span>
          <?php else: ?>
          <span class="badge badge-success" style="font-size:.65rem">Complete</span>
          <?php endif; ?>
        </div>
        <div style="height:7px;background:var(--border);border-radius:99px;overflow:hidden">
          <div style="height:100%;width:<?= $complete ?>%;background:<?= $complete>=80?'var(--success)':($complete>=50?'var(--accent)':'var(--danger)') ?>;border-radius:99px;transition:width .4s"></div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:.3rem">
        <?php
        $checks = [
          ['Phone number',      !empty($p['phone'])],
          ['Gender',            !empty($p['gender'])],
          ['Location',          !empty($p['location'])],
          ['Graduation year',   !empty($p['graduation_year'])],
          ['Degree',            !empty($p['degree'])],
          ['Faculty',           !empty($p['faculty'])],
          ['Department',        !empty($p['department'])],
          ['Bio',               !empty($p['bio'])],
          ['LinkedIn URL',      !empty($p['linkedin_url'])],
          ['Profile photo',     !empty($p['profile_photo'])],
          ['CV uploaded',       !empty($cv['cv_file'])],
          ['Skills listed',     !empty($cv['skills'])],
          ['Employment record', count($jobs) > 0],
          ['Employment status', !empty($emp_status)],
        ];
        foreach ($checks as [$label, $done]):
        ?>
        <div style="display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:<?= $done?'var(--text)':'var(--muted)' ?>">
          <?php if ($done): ?>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <?php else: ?>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
          <?php endif; ?>
          <?= $label ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="card">
      <div class="card-header"><span class="card-title">Quick Stats</span></div>
      <div style="display:flex;flex-direction:column;gap:.5rem">
        <?php
        $current_job = array_filter($jobs, fn($j) => $j['is_current']);
        $current_job = reset($current_job);
        ?>
        <div style="display:flex;justify-content:space-between;font-size:.82rem">
          <span class="text-muted">Employment records</span>
          <strong><?= count($jobs) ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.82rem">
          <span class="text-muted">Current status</span>
          <span class="badge <?= $current_job ? 'badge-success' : 'badge-secondary' ?>" style="font-size:.65rem">
            <?= $current_job ? htmlspecialchars($current_job['employment_type']) : 'Not set' ?>
          </span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.82rem">
          <span class="text-muted">CV uploaded</span>
          <span class="badge <?= !empty($cv['cv_file']) ? 'badge-success' : 'badge-secondary' ?>" style="font-size:.65rem">
            <?= !empty($cv['cv_file']) ? 'Yes' : 'No' ?>
          </span>
        </div>
      </div>
    </div>

  </div>

  <!-- ── RIGHT: FORM ── -->
  <div style="display:flex;flex-direction:column;gap:1rem">

    <!-- Personal info -->
    <div class="card">
      <div class="card-header"><span class="card-title">Personal Information</span></div>
      <div class="form-row">
        <div class="form-group">
          <label>Full Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="full_name" value="<?= htmlspecialchars($p['full_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <div style="display:flex;align-items:center;gap:.5rem">
            <input type="email" value="<?= htmlspecialchars($p['email']) ?>" disabled style="flex:1">
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('email-change-box').style.display=document.getElementById('email-change-box').style.display==='none'?'block':'none'">
              Change
            </button>
          </div>
          <?php
          $pending = $pdo->prepare("SELECT new_email FROM email_verifications WHERE user_id=? AND expires_at > NOW()");
          $pending->execute([$uid]);
          $pending_email = $pending->fetchColumn();
          ?>
          <?php if ($pending_email): ?>
          <div class="alert alert-warning" style="margin-top:.5rem;padding:.5rem .75rem;font-size:.8rem">
            Verification pending for <strong><?= htmlspecialchars($pending_email) ?></strong> — check your inbox.
          </div>
          <?php endif; ?>
          <!-- Email change form (hidden by default) -->
          <div id="email-change-box" style="display:<?= ($email_msg||$email_err)?'block':'none' ?>;margin-top:.75rem;padding:1rem;background:var(--bg);border-radius:var(--r);border:1px solid var(--border)">
            <?php if ($email_msg): ?>
            <div class="alert alert-success" style="margin-bottom:.75rem;font-size:.82rem"><?= $email_msg ?></div>
            <?php endif; ?>
            <?php if ($email_err): ?>
            <div class="alert alert-error" style="margin-bottom:.75rem;font-size:.82rem"><?= htmlspecialchars($email_err) ?></div>
            <?php endif; ?>
            <div style="font-size:.82rem;font-weight:600;margin-bottom:.5rem">Change Email Address</div>
            <form method="POST" style="display:flex;gap:.5rem">
              <?= csrf_field() ?>
              <input type="email" name="new_email" placeholder="Enter new email address" style="flex:1;font-size:.85rem" value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>">
              <button type="submit" name="change_email" value="1" class="btn btn-primary btn-sm">Send Verification</button>
            </form>
            <div class="form-hint" style="margin-top:.4rem">A verification link will be sent to the new address. Your email won't change until you click it.</div>
          </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Student Number</label>
          <input type="text" name="student_id" value="<?= htmlspecialchars($p['student_id'] ?? '') ?>" placeholder="e.g. 201900123">
        </div>
        <div class="form-group">
          <label>ID / Passport Number</label>
          <input type="text" name="id_number" value="<?= htmlspecialchars($p['id_number'] ?? '') ?>" placeholder="e.g. 9001015009087">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Phone Number</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($p['phone'] ?? '') ?>" placeholder="+27 XX XXX XXXX">
        </div>
        <div class="form-group">
          <label>Gender</label>
          <select name="gender">
            <option value="">— Select —</option>
            <?php foreach (['Male','Female','Non-binary','Prefer not to say'] as $g): ?>
            <option value="<?= $g ?>" <?= ($p['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Employment Status</label>
          <?php
          $status_colors = [
            'Full-time'       => '#16a34a',
            'Part-time'       => '#d97706',
            'Self-employed'   => '#7c3aed',
            'Freelance'       => '#0891b2',
            'Unemployed'      => '#dc2626',
            'Further Studies' => 'var(--primary)',
          ];
          ?>
          <select name="employment_status">
            <option value="">— Select —</option>
            <?php foreach ($status_colors as $s => $c): ?>
            <option value="<?= $s ?>" <?= $emp_status === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($emp_status): ?>
          <div style="margin-top:.4rem">
            <span style="display:inline-block;padding:.2rem .65rem;border-radius:99px;font-size:.72rem;font-weight:600;color:#fff;background:<?= $status_colors[$emp_status] ?? '#888' ?>"><?= htmlspecialchars($emp_status) ?></span>
          </div>
          <?php endif; ?>
          <div class="form-hint">Also updates your Employment page.</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Current Location</label>
          <div style="position:relative">
            <input type="text" id="location-input" name="location"
                   value="<?= htmlspecialchars($p['location'] ?? '') ?>"
                   placeholder="Start typing a city…"
                   autocomplete="off">
            <ul id="location-suggestions" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);max-height:200px;overflow-y:auto;z-index:999;margin:0;padding:0;list-style:none;box-shadow:0 4px 12px rgba(0,0,0,.08)"></ul>
          </div>
        </div>
        <div class="form-group">
          <label>LinkedIn Profile URL</label>
          <div style="display:flex;align-items:center;gap:.5rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0077b5" stroke-width="2" style="flex-shrink:0"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
            <input type="text" name="linkedin_url" value="<?= htmlspecialchars($p['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/yourname" style="flex:1">
          </div>
        </div>
      </div>
    </div>

    <!-- Academic info -->
    <div class="card">
      <div class="card-header"><span class="card-title">Academic Information</span></div>
      <div class="form-row">
        <div class="form-group">
          <label>Graduation Year <span style="color:var(--danger)">*</span></label>
          <select name="graduation_year">
            <option value="">— Select Year —</option>
            <?php for ($y = date('Y')+1; $y >= 1990; $y--): ?>
            <option value="<?= $y ?>" <?= ($p['graduation_year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Degree / Qualification <span style="color:var(--danger)">*</span></label>
          <input type="text" name="degree" value="<?= htmlspecialchars($p['degree'] ?? '') ?>" placeholder="e.g. BSc Computer Science">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Faculty <span style="color:var(--danger)">*</span></label>
          <select name="faculty" id="faculty-select" onchange="loadDepts(this.value, null)">
            <option value="">— Select Faculty —</option>
            <?php foreach ($faculties as $f): ?>
            <option value="<?= htmlspecialchars($f['name']) ?>"
                    data-id="<?= $f['id'] ?>"
                    <?= ($p['faculty'] ?? '') === $f['name'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($f['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Department <span style="color:var(--danger)">*</span></label>
          <select name="department" id="dept-select">
            <option value="">— Select Faculty first —</option>
            <?php
            // Pre-populate if faculty already set
            if (!empty($p['faculty'])) {
                foreach ($faculties as $f) {
                    if ($f['name'] === $p['faculty']) {
                        foreach ($depts_by_fac[$f['id']] ?? [] as $d) {
                            $sel = ($p['department'] === $d['name']) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($d['name'])."\" $sel>".htmlspecialchars($d['name'])."</option>";
                        }
                        break;
                    }
                }
            }
            ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Bio -->
    <div class="card">
      <div class="card-header"><span class="card-title">About Me</span></div>
      <div class="form-group" style="margin:0">
        <textarea name="bio" rows="4" placeholder="Write a short professional bio — who you are, what you studied, and what you're looking for…"><?= htmlspecialchars($p['bio'] ?? '') ?></textarea>
        <div class="form-hint">This appears on your alumni directory listing and is visible to employers.</div>
      </div>
    </div>

    <button type="submit" name="save_profile" value="1" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Profile
    </button>

  </div>
</div>
</form>

<script>
<?php
$_depts_json = json_encode($depts_by_fac, JSON_HEX_TAG);
if (json_last_error() !== JSON_ERROR_NONE) {
    log_app_error('application', 'json_encode failed for depts_by_fac: ' . json_last_error_msg());
    $_depts_json = '{}';
}
?>
const DEPTS = <?= $_depts_json ?>;

function loadDepts(facName, selectedDept) {
    const facSel  = document.getElementById('faculty-select');
    const deptSel = document.getElementById('dept-select');
    // find faculty id from selected option
    const opt = [...facSel.options].find(o => o.value === facName);
    const fid = opt ? opt.dataset.id : null;
    const depts = fid && DEPTS[fid] ? DEPTS[fid] : [];
    deptSel.innerHTML = '<option value="">— Select Department —</option>';
    depts.forEach(d => {
        const o = document.createElement('option');
        o.value = d.name; o.textContent = d.name;
        if (selectedDept && d.name === selectedDept) o.selected = true;
        deptSel.appendChild(o);
    });
}

// On page load, if faculty already selected, departments are pre-rendered server-side — no JS needed.
// But wire up the change event for when user changes faculty.
document.getElementById('faculty-select').addEventListener('change', function() {
    loadDepts(this.value, null);
});

function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('photo-preview');
        const placeholder = document.getElementById('photo-placeholder');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
        document.getElementById('photo-selected').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}
// Location autocomplete via Nominatim (OpenStreetMap) — no API key needed
(function(){
    const input = document.getElementById('location-input');
    const list  = document.getElementById('location-suggestions');
    let timer;

    input.addEventListener('input', function(){
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { list.style.display='none'; return; }
        timer = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6&featuretype=city&q=${encodeURIComponent(q)}`, {
                headers: { 'Accept-Language': 'en' }
            })
            .then(r => r.json())
            .then(results => {
                list.innerHTML = '';
                if (!results.length) { list.style.display='none'; return; }
                results.forEach(r => {
                    const a = r.address;
                    const city    = a.city || a.town || a.village || a.county || r.display_name.split(',')[0];
                    const country = a.country || '';
                    const label   = country ? `${city}, ${country}` : city;
                    const li = document.createElement('li');
                    li.textContent = label;
                    li.style.cssText = 'padding:.5rem .85rem;cursor:pointer;font-size:.875rem;border-bottom:1px solid var(--border)';
                    li.addEventListener('mousedown', e => { e.preventDefault(); input.value = label; list.style.display='none'; });
                    li.addEventListener('mouseover', () => li.style.background='var(--bg)');
                    li.addEventListener('mouseout',  () => li.style.background='');
                    list.appendChild(li);
                });
                list.style.display = 'block';
            })
            .catch(() => { list.style.display='none'; });
        }, 350);
    });

    document.addEventListener('click', e => { if (!input.contains(e.target)) list.style.display='none'; });
    input.addEventListener('keydown', e => { if (e.key==='Escape') list.style.display='none'; });
})();
</script>

<?php include '../includes/footer.php'; ?>
