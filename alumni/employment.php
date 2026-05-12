<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';
require_once '../includes/csrf.php';

$uid     = $_SESSION['user_id'];
$success = $error = '';
$editing = null; // record being edited

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // ── DELETE ────────────────────────────────────────────
    if (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM employment_records WHERE id=? AND user_id=?")
            ->execute([(int)$_POST['delete_id'], $uid]);
        $success = 'Employment record removed.';

    // ── ADD / EDIT ────────────────────────────────────────
    } else {
        $employer  = trim($_POST['employer']         ?? '');
        $job_title = trim($_POST['job_title']        ?? '');
        $industry  = trim($_POST['industry']         ?? '');
        $location  = trim($_POST['location']         ?? '');
        $desc      = trim($_POST['description']      ?? '');
        $emp_type  = $_POST['employment_type']       ?? '';
        $start     = $_POST['start_date']            ?: null;
        $end       = $_POST['end_date']              ?: null;
        $is_cur    = isset($_POST['is_current']) ? 1 : 0;
        $edit_id   = (int)($_POST['edit_id']         ?? 0);

        $valid_types = ['Full-time','Part-time','Self-employed','Freelance','Unemployed','Further Studies'];

        if (!in_array($emp_type, $valid_types)) {
            $error = 'Please select a valid employment type.';
        } elseif (!$employer && !in_array($emp_type, ['Unemployed','Further Studies'])) {
            $error = 'Please enter the employer or organisation name.';
        } elseif ($start && $end && $end < $start) {
            $error = 'End date cannot be before start date.';
        } else {
            // If marking as current, unset all other current flags
            if ($is_cur) {
                $q = "UPDATE employment_records SET is_current=0 WHERE user_id=?";
                if ($edit_id) $q .= " AND id != {$edit_id}";
                $pdo->prepare($q)->execute([$uid]);
            }

            if ($edit_id) {
                // Verify ownership
                $own = $pdo->prepare("SELECT id FROM employment_records WHERE id=? AND user_id=?");
                $own->execute([$edit_id, $uid]);
                if (!$own->fetch()) { $error = 'Record not found.'; goto done; }

                $pdo->prepare("UPDATE employment_records
                    SET employer=?,job_title=?,industry=?,employment_type=?,
                        start_date=?,end_date=?,is_current=?,location=?,description=?
                    WHERE id=? AND user_id=?")
                    ->execute([$employer,$job_title,$industry,$emp_type,
                               $start,$end,$is_cur,$location,$desc,$edit_id,$uid]);
                $success = 'Employment record updated.';
            } else {
                $pdo->prepare("INSERT INTO employment_records
                    (user_id,employer,job_title,industry,employment_type,start_date,end_date,is_current,location,description)
                    VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$uid,$employer,$job_title,$industry,$emp_type,
                               $start,$end,$is_cur,$location,$desc]);
                $success = 'Employment record added.';
            }
        }
    }
}
done:

// Load edit record if requested
if (isset($_GET['edit'])) {
    $e = $pdo->prepare("SELECT * FROM employment_records WHERE id=? AND user_id=?");
    $e->execute([(int)$_GET['edit'], $uid]);
    $editing = $e->fetch() ?: null;
}

$records = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC, start_date DESC");
$records->execute([$uid]);
$records = $records->fetchAll();

$types = ['Full-time','Part-time','Self-employed','Freelance','Unemployed','Further Studies'];

$type_colors = [
    'Full-time'      => '#16a34a',
    'Part-time'      => '#d97706',
    'Self-employed'  => '#7c3aed',
    'Freelance'      => '#0891b2',
    'Unemployed'     => '#dc2626',
    'Further Studies'=> '#5B1C16',
];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Employment Records</h1>
    <p>Track your career history and current employment status</p>
  </div>
  <div class="page-header-actions">
    <a href="/alumni/profile.php" class="btn btn-outline btn-sm">← Back to Profile</a>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

  <!-- ── FORM ── -->
  <div class="card" id="emp-form-card">
    <div class="card-header">
      <span class="card-title"><?= $editing ? 'Edit Record' : 'Add Record' ?></span>
      <?php if ($editing): ?>
      <a href="/alumni/employment.php" class="btn btn-outline btn-sm">Cancel</a>
      <?php endif; ?>
    </div>

    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <?php if ($editing): ?>
      <input type="hidden" name="edit_id" value="<?= $editing['id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label>Employment Type <span style="color:var(--danger)">*</span></label>
        <select name="employment_type" id="emp-type-select" onchange="toggleFields(this.value)" required>
          <option value="">— Select —</option>
          <?php foreach ($types as $t): ?>
          <option value="<?= $t ?>" <?= ($editing['employment_type'] ?? $_POST['employment_type'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="employer-fields">
        <div class="form-group">
          <label>Employer / Organisation</label>
          <input type="text" name="employer" placeholder="e.g. Accenture South Africa"
                 value="<?= htmlspecialchars($editing['employer'] ?? $_POST['employer'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Job Title / Role</label>
          <input type="text" name="job_title" placeholder="e.g. Software Engineer"
                 value="<?= htmlspecialchars($editing['job_title'] ?? $_POST['job_title'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Industry</label>
          <input type="text" name="industry" placeholder="e.g. Information Technology"
                 value="<?= htmlspecialchars($editing['industry'] ?? $_POST['industry'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Location</label>
        <div style="position:relative">
          <input type="text" id="emp-location" name="location" autocomplete="off"
                 placeholder="Start typing a city…"
                 value="<?= htmlspecialchars($editing['location'] ?? $_POST['location'] ?? '') ?>">
          <ul id="emp-location-suggestions" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);max-height:180px;overflow-y:auto;z-index:999;margin:0;padding:0;list-style:none;box-shadow:0 4px 12px rgba(0,0,0,.08)"></ul>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" name="start_date"
                 value="<?= htmlspecialchars($editing['start_date'] ?? $_POST['start_date'] ?? '') ?>">
        </div>
        <div class="form-group" id="end-date-group">
          <label>End Date</label>
          <input type="date" name="end_date"
                 value="<?= htmlspecialchars($editing['end_date'] ?? $_POST['end_date'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Description <span class="text-muted text-xs">(optional)</span></label>
        <textarea name="description" rows="3"
                  placeholder="Brief description of your responsibilities and achievements…"><?= htmlspecialchars($editing['description'] ?? $_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group" style="display:flex;align-items:center;gap:.5rem">
        <input type="checkbox" name="is_current" id="is_current" value="1"
               style="width:auto;accent-color:var(--primary)"
               <?= ($editing ? $editing['is_current'] : 1) ? 'checked' : '' ?>
               onchange="document.getElementById('end-date-group').style.opacity=this.checked?'.4':'1'">
        <label for="is_current" style="margin:0;font-weight:500;font-size:.875rem">This is my current position</label>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
        <?= $editing ? 'Save Changes' : 'Add Record' ?>
      </button>
    </form>
  </div>

  <!-- ── HISTORY ── -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Employment History</span>
      <span class="badge badge-secondary"><?= count($records) ?></span>
    </div>
    <?php if ($records): ?>
    <div style="display:flex;flex-direction:column;gap:.75rem">
      <?php foreach ($records as $r):
        $tc = $type_colors[$r['employment_type']] ?? '#888';
      ?>
      <div style="padding:1rem;border:1px solid var(--border);border-radius:var(--radius);<?= $r['is_current'] ? 'border-left:3px solid var(--success)' : '' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem">
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:.2rem">
              <span class="fw-600"><?= htmlspecialchars($r['job_title'] ?: $r['employment_type']) ?></span>
              <span style="display:inline-block;padding:.15rem .55rem;border-radius:99px;font-size:.68rem;font-weight:600;color:#fff;background:<?= $tc ?>"><?= htmlspecialchars($r['employment_type']) ?></span>
              <?php if ($r['is_current']): ?>
              <span class="badge badge-success" style="font-size:.65rem">Current</span>
              <?php endif; ?>
            </div>
            <?php if ($r['employer']): ?>
            <div class="text-sm" style="margin-bottom:.2rem">
              <strong><?= htmlspecialchars($r['employer']) ?></strong>
              <?= $r['location'] ? ' <span class="text-muted">· '.htmlspecialchars($r['location']).'</span>' : '' ?>
            </div>
            <?php endif; ?>
            <div class="text-xs text-muted">
              <?= $r['industry'] ? htmlspecialchars($r['industry']).' · ' : '' ?>
              <?= $r['start_date'] ? date('M Y', strtotime($r['start_date'])) : '' ?>
              <?= $r['end_date'] ? ' – '.date('M Y', strtotime($r['end_date'])) : ($r['is_current'] ? ' – <strong>Present</strong>' : '') ?>
            </div>
            <?php if (!empty($r['description'])): ?>
            <div class="text-sm text-muted" style="margin-top:.4rem;line-height:1.6"><?= nl2br(htmlspecialchars($r['description'])) ?></div>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:.3rem;flex-shrink:0">
            <a href="?edit=<?= $r['id'] ?>#emp-form-card" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remove this record?')">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger)">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
              </button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
      <p>No employment records yet. Add your first record using the form.</p>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
// Hide employer fields for Unemployed / Further Studies
function toggleFields(type) {
    const hide = ['Unemployed','Further Studies'].includes(type);
    document.getElementById('employer-fields').style.display = hide ? 'none' : '';
}
toggleFields(document.getElementById('emp-type-select').value);

// Dim end date when "current" is checked
const isCur = document.getElementById('is_current');
document.getElementById('end-date-group').style.opacity = isCur.checked ? '.4' : '1';

// Smart location autocomplete (Nominatim)
(function(){
    const input = document.getElementById('emp-location');
    const list  = document.getElementById('emp-location-suggestions');
    let timer;
    input.addEventListener('input', function(){
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { list.style.display='none'; return; }
        timer = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=6&q=${encodeURIComponent(q)}`, {
                headers: {'Accept-Language':'en'}
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

// Scroll to form when editing
<?php if ($editing): ?>
document.getElementById('emp-form-card').scrollIntoView({behavior:'smooth', block:'start'});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
