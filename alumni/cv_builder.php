<?php
require_once '../includes/auth_guard.php';
require_role('alumni');
require_once '../config/db.php';
require_once '../includes/csrf.php';

$uid     = $_SESSION['user_id'];
$success = $error = '';

// Load profile
$profile = $pdo->prepare("SELECT ap.*, u.full_name, u.email FROM alumni_profiles ap JOIN users u ON u.id=ap.user_id WHERE ap.user_id=?");
$profile->execute([$uid]);
$p = $profile->fetch();

// Load employment
$jobs = $pdo->prepare("SELECT * FROM employment_records WHERE user_id=? ORDER BY is_current DESC, start_date DESC");
$jobs->execute([$uid]);
$jobs = $jobs->fetchAll();

// Load CV record
$cv = $pdo->prepare("SELECT * FROM alumni_cv WHERE user_id=?");
$cv->execute([$uid]);
$cv = $cv->fetch() ?: [];

// ── PROFILE COMPLETENESS CHECK ─────────────────────────────
$required = ['full_name','degree','department','graduation_year','phone'];
$missing  = array_filter($required, fn($f) => empty($p[$f]));
$profile_complete = empty($missing);

// ── SAVE CV DATA ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $skills   = trim($_POST['skills'] ?? '');
    $langs    = trim($_POST['languages'] ?? '');
    $certs    = trim($_POST['certifications'] ?? '');
    $summary  = trim($_POST['summary'] ?? '');
    $cv_file  = $cv['cv_file'] ?? '';
    $cv_text  = $cv['cv_text'] ?? '';

    // Handle CV upload
    if (!empty($_FILES['cv_upload']['name'])) {
        $ext = strtolower(pathinfo($_FILES['cv_upload']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','doc','docx','txt'])) {
            $error = 'Only PDF, DOC, DOCX or TXT files are accepted.';
        } elseif ($_FILES['cv_upload']['size'] > 5 * 1024 * 1024) {
            $error = 'CV file must be under 5 MB.';
        } else {
            $dir   = __DIR__ . '/../uploads/cvs/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'cv_' . $uid . '_' . time() . '.' . $ext;
            if (!move_uploaded_file($_FILES['cv_upload']['tmp_name'], $dir . $fname)) {
                $error = 'Could not save the file. Please try again.';
            } else {
                $cv_file = $fname;
                if ($ext === 'txt') {
                    $cv_text = file_get_contents($dir . $fname);
                } elseif ($ext === 'pdf') {
                    $cv_text = extract_pdf_text($dir . $fname) ?: $cv_text;
                }
            }
        }
    }

    if (!$error) {
        // Parse keywords from CV text + skills field (strip :Level suffixes)
        $skills_plain = implode(', ', array_map(
            fn($s) => trim(explode(':', $s)[0]),
            array_filter(array_map('trim', explode(',', $skills)))
        ));
        $all_text = strtolower($cv_text . ' ' . $skills_plain . ' ' . $summary . ' ' . $certs);
        $keywords = parse_keywords($all_text);

        // Auto-populate skills from CV keywords if skills field is empty
        if (empty($skills) && !empty($keywords)) {
            $skills = implode(', ', array_map('ucwords', $keywords));
        }

        // Calculate profile score
        $score_fields = ['full_name','degree','department','graduation_year','phone','bio','linkedin_url','profile_photo'];
        $filled     = array_filter($score_fields, fn($f) => !empty($p[$f]));
        $base_score = round((count($filled) / count($score_fields)) * 50);
        $skill_count = count(array_filter(array_map('trim', explode(',', $skills))));
        $cv_score   = min(50,
            ($skill_count >= 10 ? 20 : ($skill_count >= 5 ? 12 : ($skill_count >= 1 ? 6 : 0)))
            + ($summary  ? 12 : 0)
            + ($certs    ? 10 : 0)
            + ($cv_file  ?  8 : 0)
        );
        $total_score = min(100, $base_score + $cv_score);

        if (!empty($cv['id'])) {
            $pdo->prepare("UPDATE alumni_cv SET skills=?,languages=?,certifications=?,summary=?,cv_file=?,cv_text=?,parsed_keywords=?,profile_score=? WHERE user_id=?")
                ->execute([$skills,$langs,$certs,$summary,$cv_file,$cv_text,implode(',',$keywords),$total_score,$uid]);
        } else {
            $pdo->prepare("INSERT INTO alumni_cv (user_id,skills,languages,certifications,summary,cv_file,cv_text,parsed_keywords,profile_score) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$uid,$skills,$langs,$certs,$summary,$cv_file,$cv_text,implode(',',$keywords),$total_score]);
        }
        $success = 'CV profile saved successfully.';
        $stmt = $pdo->prepare("SELECT * FROM alumni_cv WHERE user_id=?");
        $stmt->execute([$uid]);
        $cv = $stmt->fetch() ?: [];
    }
}

// ── PDF TEXT EXTRACTOR (pure PHP: FlateDecode + ToUnicode CMap + literal) ──
function extract_pdf_text(string $path): string {
    $raw = @file_get_contents($path);
    if (!$raw) return '';

    // 1. Decompress all FlateDecode streams, keep decoded blobs
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $sm);
    $decoded_streams = [];
    foreach ($sm[1] as $s) {
        $d = @zlib_decode($s);
        if ($d !== false) $decoded_streams[] = $d;
    }
    $all = $raw . implode('', $decoded_streams);

    // 2. Build ToUnicode maps: font resource name → [glyphID => char]
    $cmap = [];
    foreach ($decoded_streams as $ds) {
        if (!str_contains($ds, 'beginbfchar') && !str_contains($ds, 'beginbfrange')) continue;
        // Try to find which font this cmap belongs to (look back in raw for /ToUnicode ref)
        $map = [];
        // bfchar: <glyphHex> <unicodeHex>
        preg_match_all('/<([0-9A-Fa-f]{2,4})>\s*<([0-9A-Fa-f]{4,})>/', $ds, $bfc);
        for ($i = 0; $i < count($bfc[1]); $i++) {
            $gid  = strtoupper($bfc[1][$i]);
            $uni  = intval($bfc[2][$i], 16);
            $map[$gid] = mb_chr($uni, 'UTF-8');
        }
        // bfrange: <start> <end> <unicodeStart>
        preg_match_all('/<([0-9A-Fa-f]{2,4})>\s*<([0-9A-Fa-f]{2,4})>\s*<([0-9A-Fa-f]{4,})>/', $ds, $bfr);
        for ($i = 0; $i < count($bfr[1]); $i++) {
            $start = intval($bfr[1][$i], 16);
            $end   = intval($bfr[2][$i], 16);
            $uni   = intval($bfr[3][$i], 16);
            for ($g = $start; $g <= $end; $g++) {
                $gid = strtoupper(str_pad(dechex($g), strlen($bfr[1][$i]), '0', STR_PAD_LEFT));
                $map[$gid] = mb_chr($uni + ($g - $start), 'UTF-8');
            }
        }
        if ($map) $cmap[] = $map;
    }

    // 3. Extract text from BT...ET blocks across all decoded content
    $text = '';
    preg_match_all('/BT[\s\S]*?ET/', $all, $blocks);
    foreach ($blocks[0] as $block) {
        // Hex strings: <XXXX XXXX> TJ or Tj — decode via cmap
        preg_match_all('/<([0-9A-Fa-f\s]+)>\s*(?:Tj|TJ|\')/', $block, $hexm);
        foreach ($hexm[1] as $hexstr) {
            $hexstr = preg_replace('/\s+/', '', $hexstr);
            // split into 4-char (2-byte) or 2-char (1-byte) glyph codes
            $chunk = (strlen($hexstr) % 4 === 0) ? 4 : 2;
            for ($i = 0; $i < strlen($hexstr); $i += $chunk) {
                $gid = strtoupper(substr($hexstr, $i, $chunk));
                $found = false;
                foreach ($cmap as $m) {
                    if (isset($m[$gid])) { $text .= $m[$gid]; $found = true; break; }
                }
                if (!$found && $chunk === 2) {
                    $cp = intval($gid, 16);
                    if ($cp >= 0x20 && $cp <= 0x7E) $text .= chr($cp);
                }
            }
            $text .= ' ';
        }
        // Also grab literal (string) Tj for simple PDFs
        preg_match_all('/\(([^)]{0,400})\)\s*Tj/', $block, $litm);
        foreach ($litm[1] as $l) $text .= ' ' . $l;
    }

    // 4. Clean up
    $text = preg_replace('/[^\x20-\x7E\n ]/u', ' ', $text);
    $text = preg_replace('/\s{2,}/', ' ', $text);
    return trim($text);
}

// ── KEYWORD PARSER ─────────────────────────────────────────
function parse_keywords(string $text): array {
    $keywords = [
        // Web & software development
        'php','python','java','javascript','typescript','react','angular','vue','node','nodejs',
        'html','css','sass','bootstrap','tailwind','jquery','next.js','nuxt','svelte',
        'rest api','graphql','soap','json','xml','webpack','vite',
        // Backend & frameworks
        'laravel','django','flask','fastapi','spring','spring boot','.net','asp.net',
        'express','ruby on rails','symfony','codeigniter',
        // Mobile
        'android','ios','swift','kotlin','flutter','react native','xamarin',
        // Databases
        'sql','mysql','postgresql','mongodb','sqlite','redis','oracle','mssql',
        'firebase','dynamodb','elasticsearch','mariadb',
        // DevOps & cloud
        'git','github','gitlab','docker','kubernetes','jenkins','ci/cd','linux','bash',
        'aws','azure','gcp','google cloud','terraform','ansible','nginx','apache',
        // Data & AI
        'data analysis','machine learning','artificial intelligence','deep learning',
        'data science','power bi','tableau','excel','pandas','numpy','scikit-learn',
        'tensorflow','pytorch','r','spss','stata','hadoop','spark','etl',
        // Cybersecurity & networking
        'cybersecurity','networking','tcp/ip','firewall','penetration testing','ethical hacking',
        'cisco','ccna','comptia','siem','soc','vulnerability assessment',
        // Languages
        'c','c++','c#','go','rust','scala','perl','matlab','vba',
        // Office & productivity
        'microsoft office','word','powerpoint','outlook','sharepoint','teams',
        'google workspace','google docs','google sheets',
        // Business & management
        'project management','agile','scrum','kanban','prince2','pmp','jira','confluence',
        'business analysis','requirements gathering','stakeholder management',
        'strategic planning','change management','risk management','procurement',
        // Finance & accounting
        'accounting','bookkeeping','financial reporting','auditing','taxation','payroll',
        'budgeting','forecasting','ifrs','gaap','quickbooks','sage','pastel',
        'cost accounting','management accounting','financial analysis','cima','acca','ca',
        // Marketing & sales
        'marketing','digital marketing','seo','sem','social media','content marketing',
        'google analytics','email marketing','crm','salesforce','hubspot',
        'brand management','market research','sales','business development',
        // HR & administration
        'human resources','recruitment','talent acquisition','performance management',
        'employee relations','training and development','payroll administration',
        'labour law','organisational development','administration',
        // Education & research
        'teaching','curriculum development','lesson planning','e-learning','moodle',
        'research','academic writing','qualitative research','quantitative research',
        'data collection','literature review','grant writing',
        // Healthcare
        'nursing','patient care','clinical','pharmacy','public health','health informatics',
        'medical coding','first aid','icu','theatre nursing','midwifery',
        // Engineering
        'engineering','autocad','solidworks','matlab','civil engineering',
        'electrical engineering','mechanical engineering','structural analysis',
        'project planning','site management','quality control','iso',
        // Soft skills
        'communication','leadership','teamwork','problem solving','critical thinking',
        'time management','customer service','presentation','negotiation','mentoring',
    ];

    $found = [];
    foreach ($keywords as $kw) {
        if (str_contains($text, $kw)) $found[] = $kw;
    }
    return array_unique(array_slice($found, 0, 80));
}

$score       = (int)($cv['profile_score'] ?? 0);
$score_color = $score >= 80 ? 'var(--success)' : ($score >= 50 ? 'var(--accent)' : 'var(--danger)');
$keywords_arr = $cv ? array_filter(explode(',', $cv['parsed_keywords'] ?? '')) : [];

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>CV Builder &amp; Profile</h1>
    <p>Build your CV profile to get matched with opportunities</p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/alumni/job_match.php" class="btn btn-primary btn-sm">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      View Job Matches
    </a>
  </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$profile_complete): ?>
<div class="alert alert-warning">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  <span>Your profile is incomplete. You must fill in
    <strong><?= implode(', ', $missing) ?></strong> before you can be matched to opportunities.
    <a href="/gate-portal/alumni/profile.php" style="color:inherit;font-weight:700">Complete profile →</a>
  </span>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:1.5rem;align-items:start">

  <!-- LEFT: Score + Keywords -->
  <div>

    <!-- Profile Score -->
    <div class="card" style="text-align:center;padding:1.5rem">
      <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:.75rem">Profile Strength</div>
      <div style="position:relative;width:110px;height:110px;margin:0 auto .75rem">
        <svg viewBox="0 0 36 36" style="width:110px;height:110px;transform:rotate(-90deg)">
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="var(--border)" stroke-width="3"/>
          <circle cx="18" cy="18" r="15.9" fill="none" stroke="<?= $score_color ?>" stroke-width="3"
            stroke-dasharray="<?= round($score * 100 / 100) ?> 100"
            stroke-linecap="round"/>
        </svg>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column">
          <span style="font-size:1.6rem;font-weight:800;color:<?= $score_color ?>"><?= $score ?></span>
          <span style="font-size:.65rem;color:var(--muted)">/ 100</span>
        </div>
      </div>
      <?php
      $level = $score >= 80 ? ['Strong','badge-success'] : ($score >= 50 ? ['Good','badge-warning'] : ['Needs Work','badge-danger']);
      ?>
      <span class="badge <?= $level[1] ?>"><?= $level[0] ?></span>

      <?php if (!$profile_complete): ?>
      <div class="text-xs text-muted" style="margin-top:.75rem;line-height:1.6">
        Complete your profile to be visible to the matching engine.
      </div>
      <?php elseif ($score < 80): ?>
      <div class="text-xs text-muted" style="margin-top:.75rem;line-height:1.6">
        Add skills, certifications and a summary to boost your score.
      </div>
      <?php endif; ?>
    </div>

    <!-- Checklist -->
    <div class="card">
      <div class="card-header"><span class="card-title">Profile Checklist</span></div>
      <div style="display:flex;flex-direction:column;gap:.4rem">
        <?php
        $checks = [
          ['Photo uploaded',       !empty($p['profile_photo'])],
          ['Degree filled in',     !empty($p['degree'])],
          ['Department set',       !empty($p['department'])],
          ['Graduation year',      !empty($p['graduation_year'])],
          ['Phone number',         !empty($p['phone'])],
          ['Bio / summary',        !empty($p['bio']) || !empty($cv['summary'])],
          ['LinkedIn URL',         !empty($p['linkedin_url'])],
          ['Employment record',    count($jobs) > 0],
          ['Skills listed',        !empty($cv['skills'])],
          ['Certifications',       !empty($cv['certifications'])],
          ['CV uploaded',          !empty($cv['cv_file'])],
        ];
        foreach ($checks as [$label, $done]):
        ?>
        <div style="display:flex;align-items:center;gap:.6rem;padding:.4rem .5rem;border-radius:var(--r);background:<?= $done?'#f0fdf4':'var(--bg)' ?>">
          <?php if ($done): ?>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <?php else: ?>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
          <?php endif; ?>
          <span class="text-sm <?= $done?'':'text-muted' ?>"><?= $label ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Detected Keywords -->
    <?php if ($keywords_arr): ?>
    <div class="card">
      <div class="card-header"><span class="card-title">Detected Skills &amp; Keywords</span></div>
      <div style="display:flex;flex-wrap:wrap;gap:.35rem">
        <?php foreach ($keywords_arr as $kw): ?>
        <span class="badge badge-info" style="font-size:.72rem"><?= htmlspecialchars($kw) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="form-hint" style="margin-top:.6rem">These are extracted from your CV and skills. They are used for job matching.</div>
    </div>
    <?php endif; ?>

  </div>

  <!-- RIGHT: Form -->
  <div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <!-- Professional Summary -->
      <div class="card">
        <div class="card-header"><span class="card-title">Professional Summary</span></div>
        <div class="form-group">
          <textarea name="summary" rows="4" placeholder="Write a 2–4 sentence professional summary. e.g. 'Results-driven software developer with 3 years of experience in PHP and JavaScript, specialising in web application development...'"><?= htmlspecialchars($cv['summary'] ?? $p['bio'] ?? '') ?></textarea>
          <div class="form-hint">This is the first thing employers see. Be specific about your expertise and value.</div>
        </div>
      </div>

      <!-- Skills -->
      <div class="card">
        <div class="card-header"><span class="card-title">Skills &amp; Competency</span></div>
        <div class="form-group">
          <label>Technical &amp; Professional Skills</label>
          <?php
          $skills_list = ['PHP','Python','Java','JavaScript','TypeScript','React','Angular','Vue','Node.js',
            'HTML','CSS','Bootstrap','Tailwind','Laravel','Django','Flask','.NET','Spring Boot',
            'Android','iOS','Flutter','React Native','SQL','MySQL','PostgreSQL','MongoDB','Redis',
            'Git','Docker','Kubernetes','AWS','Azure','GCP','Linux','CI/CD',
            'Data Analysis','Machine Learning','Power BI','Tableau','Excel','Pandas','TensorFlow',
            'Cybersecurity','Networking','Cisco','Penetration Testing',
            'C','C++','C#','Go','Rust','Kotlin','Swift','Scala','MATLAB','VBA',
            'Microsoft Office','SharePoint','Google Workspace',
            'Project Management','Agile','Scrum','Jira','Business Analysis','Risk Management','Procurement',
            'Accounting','Bookkeeping','Auditing','Taxation','Payroll','Budgeting','IFRS','GAAP','Pastel','Sage','CIMA','ACCA',
            'Marketing','Digital Marketing','SEO','Social Media','CRM','Salesforce','HubSpot','Google Analytics',
            'Human Resources','Recruitment','Training and Development','Labour Law',
            'Teaching','Curriculum Development','Research','Academic Writing',
            'Nursing','Patient Care','Clinical','Pharmacy','Public Health',
            'Engineering','AutoCAD','SolidWorks','Civil Engineering','Electrical Engineering','Mechanical Engineering','Quality Control',
            'Communication','Leadership','Teamwork','Problem Solving','Critical Thinking','Customer Service','Negotiation','Mentoring'];
          // Parse saved skills: supports both "Name" and "Name:Level"
          $saved_skills_raw = array_filter(array_map('trim', explode(',', $cv['skills'] ?? '')));
          $saved_skills = []; // [['name'=>..,'level'=>..]]
          foreach ($saved_skills_raw as $s) {
              $parts = explode(':', $s, 2);
              $saved_skills[] = ['name' => trim($parts[0]), 'level' => trim($parts[1] ?? '')];
          }
          $levels = ['Beginner','Intermediate','Advanced','Expert'];
          $level_colors = ['Beginner'=>'#94a3b8','Intermediate'=>'#f59e0b','Advanced'=>'#3b82f6','Expert'=>'#16a34a'];
          ?>
          <div class="tag-input-wrap" id="skills-wrap">
            <?php foreach ($saved_skills as $sk): ?>
            <?php $lc = $level_colors[$sk['level']] ?? 'var(--primary)'; ?>
            <span class="tag skill-tag" data-name="<?= htmlspecialchars($sk['name']) ?>" data-level="<?= htmlspecialchars($sk['level']) ?>" style="background:<?= $lc ?>">
              <?= htmlspecialchars($sk['name']) ?>
              <?php if ($sk['level']): ?><span class="tag-level"><?= htmlspecialchars($sk['level']) ?></span><?php endif; ?>
              <button type="button" onclick="removeSkillTag(this)">×</button>
            </span>
            <?php endforeach; ?>
            <input type="text" id="skills-input" placeholder="Type or pick a skill…" autocomplete="off">
          </div>
          <input type="hidden" name="skills" id="skills-hidden" value="<?= htmlspecialchars($cv['skills'] ?? '') ?>">
          <!-- Skills dropdown -->
          <div id="skills-dropdown" class="tag-dropdown" style="display:none">
            <?php foreach ($skills_list as $sk): ?>
            <div class="tag-option" data-val="<?= htmlspecialchars($sk) ?>"><?= htmlspecialchars($sk) ?></div>
            <?php endforeach; ?>
            <div class="tag-option tag-option-other" data-val="__other__" style="border-top:1px solid var(--border);font-style:italic;color:var(--primary)">+ Add custom skill…</div>
          </div>
          <!-- Level picker popup -->
          <div id="level-picker" style="display:none;position:absolute;z-index:300;background:#fff;border:1px solid var(--border);border-radius:var(--r);box-shadow:0 6px 20px rgba(0,0,0,.12);padding:.75rem;min-width:220px">
            <div style="font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.5rem" id="level-picker-label">Select competency level</div>
            <div id="custom-skill-row" style="display:none;margin-bottom:.5rem">
              <input type="text" id="custom-skill-input" placeholder="Skill name…" style="width:100%;font-size:.85rem;padding:.35rem .6rem;border:1px solid var(--border);border-radius:var(--r)">
            </div>
            <div style="display:flex;flex-direction:column;gap:.3rem">
              <?php foreach ($levels as $lv): ?>
              <button type="button" class="level-btn" data-level="<?= $lv ?>" style="text-align:left;padding:.4rem .75rem;border-radius:var(--r);border:1px solid var(--border);background:#fff;cursor:pointer;font-size:.83rem;display:flex;align-items:center;gap:.5rem">
                <span style="width:10px;height:10px;border-radius:50%;background:<?= $level_colors[$lv] ?>;flex-shrink:0"></span>
                <?= $lv ?>
              </button>
              <?php endforeach; ?>
            </div>
            <button type="button" onclick="closeLevelPicker()" style="margin-top:.5rem;width:100%;font-size:.78rem;color:var(--muted);background:none;border:none;cursor:pointer">Cancel</button>
          </div>
          <div class="form-hint">Pick from the list or type a custom skill. Click a skill chip to change its level.</div>
        </div>

        <!-- Languages -->
        <div class="form-row">
          <div class="form-group">
            <label>Languages Spoken</label>
            <?php
            $lang_list = ['English','Afrikaans','Zulu','Xhosa','Sotho','Tswana','Venda','Tsonga','Swati','Ndebele','Pedi',
              'French','Portuguese','Arabic','Mandarin','Hindi','Spanish','German'];
            $prof_levels = ['Basic','Conversational','Proficient','Fluent','Native'];
            $prof_colors = ['Basic'=>'#94a3b8','Conversational'=>'#f59e0b','Proficient'=>'#3b82f6','Fluent'=>'#7c3aed','Native'=>'#16a34a'];
            // Parse saved langs: supports "Name" and "Name:Proficiency"
            $saved_langs_raw = array_filter(array_map('trim', explode(',', $cv['languages'] ?? '')));
            $saved_langs = [];
            foreach ($saved_langs_raw as $l) {
                $parts = explode(':', $l, 2);
                $saved_langs[] = ['name' => trim($parts[0]), 'prof' => trim($parts[1] ?? '')];
            }
            ?>
            <div class="tag-input-wrap" id="langs-wrap">
              <?php foreach ($saved_langs as $lg): ?>
              <?php $pc = $prof_colors[$lg['prof']] ?? 'var(--primary)'; ?>
              <span class="tag lang-tag" data-name="<?= htmlspecialchars($lg['name']) ?>" data-prof="<?= htmlspecialchars($lg['prof']) ?>" style="background:<?= $pc ?>">
                <?= htmlspecialchars($lg['name']) ?>
                <?php if ($lg['prof']): ?><span class="tag-level"><?= htmlspecialchars($lg['prof']) ?></span><?php endif; ?>
                <button type="button" onclick="removeLangTag(this)">×</button>
              </span>
              <?php endforeach; ?>
              <input type="text" id="langs-input" placeholder="Pick a language…" autocomplete="off" readonly
                     onfocus="showLangDropdown()" onblur="hideLangDropdown(200)">
            </div>
            <input type="hidden" name="languages" id="langs-hidden" value="<?= htmlspecialchars($cv['languages'] ?? '') ?>">
            <div id="langs-dropdown" class="tag-dropdown" style="display:none">
              <?php foreach ($lang_list as $lg): ?>
              <div class="tag-option" data-val="<?= htmlspecialchars($lg) ?>"><?= htmlspecialchars($lg) ?></div>
              <?php endforeach; ?>
            </div>
            <!-- Proficiency picker -->
            <div id="prof-picker" style="display:none;position:absolute;z-index:300;background:#fff;border:1px solid var(--border);border-radius:var(--r);box-shadow:0 6px 20px rgba(0,0,0,.12);padding:.75rem;min-width:200px">
              <div style="font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.5rem" id="prof-picker-label">Proficiency level</div>
              <div style="display:flex;flex-direction:column;gap:.3rem">
                <?php foreach ($prof_levels as $pl): ?>
                <button type="button" class="prof-btn" data-prof="<?= $pl ?>" style="text-align:left;padding:.4rem .75rem;border-radius:var(--r);border:1px solid var(--border);background:#fff;cursor:pointer;font-size:.83rem;display:flex;align-items:center;gap:.5rem">
                  <span style="width:10px;height:10px;border-radius:50%;background:<?= $prof_colors[$pl] ?>;flex-shrink:0"></span>
                  <?= $pl ?>
                </button>
                <?php endforeach; ?>
              </div>
              <button type="button" onclick="closeProfPicker()" style="margin-top:.5rem;width:100%;font-size:.78rem;color:var(--muted);background:none;border:none;cursor:pointer">Cancel</button>
            </div>
          </div>
          <div class="form-group">
            <label>Certifications &amp; Courses</label>
            <input type="text" name="certifications" value="<?= htmlspecialchars($cv['certifications'] ?? '') ?>" placeholder="e.g. AWS Cloud Practitioner, Google Analytics, CIMA">
          </div>
        </div>
      </div>

      <!-- CV Upload -->
      <div class="card">
        <div class="card-header"><span class="card-title">Upload Existing CV</span></div>
        <div class="alert alert-info" style="margin-bottom:1rem">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>Upload your CV and we will automatically extract your skills and keywords to improve your job match accuracy.</span>
        </div>
        <?php if (!empty($cv['cv_file'])): ?>
        <div style="display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;background:var(--bg);border-radius:var(--r);margin-bottom:1rem;border:1px solid var(--border)">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <div style="flex:1">
            <div class="text-sm fw-600">Current CV: <?= htmlspecialchars($cv['cv_file']) ?></div>
            <div class="text-xs text-muted">Upload a new file to replace it</div>
          </div>
          <?php if (strtolower(pathinfo($cv['cv_file'], PATHINFO_EXTENSION)) === 'pdf'): ?>
          <button type="button" class="btn btn-outline btn-sm" onclick="openCvPreview('/gate-portal/uploads/cvs/<?= htmlspecialchars($cv['cv_file']) ?>')">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            Preview
          </button>
          <?php else: ?>
          <a href="/gate-portal/uploads/cvs/<?= htmlspecialchars($cv['cv_file']) ?>" target="_blank" class="btn btn-outline btn-sm">Download</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>CV File (PDF, DOC, DOCX or TXT)</label>
          <input type="file" name="cv_upload" accept=".pdf,.doc,.docx,.txt"
                 style="width:100%;padding:.55rem .85rem;border:1px solid var(--border);border-radius:var(--r);font-size:.875rem;font-family:inherit;background:var(--white)">
          <div class="form-hint">Max 5MB. TXT files will have their content fully parsed for keyword extraction.</div>
        </div>
      </div>

      <!-- Employment preview (read-only) -->
      <?php if ($jobs): ?>
      <div class="card">
        <div class="card-header">
          <span class="card-title">Employment History</span>
          <a href="/gate-portal/alumni/employment.php" class="btn btn-outline btn-sm">Edit</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:.5rem">
          <?php foreach ($jobs as $j): ?>
          <div style="display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;background:var(--bg);border-radius:var(--r);<?= $j['is_current']?'border-left:3px solid var(--success)':'' ?>">
            <div style="flex:1;min-width:0">
              <div class="text-sm fw-600"><?= htmlspecialchars($j['job_title'] ?: $j['employment_type']) ?></div>
              <div class="text-xs text-muted"><?= htmlspecialchars($j['employer'] ?? '') ?><?= $j['industry']?' · '.htmlspecialchars($j['industry']):'' ?></div>
            </div>
            <?php if ($j['is_current']): ?><span class="badge badge-success" style="font-size:.65rem">Current</span><?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Save CV Profile
      </button>
    </form>
  </div>

</div>

<style>
.tag-input-wrap{
  display:flex;flex-wrap:wrap;gap:.35rem;padding:.45rem .6rem;
  border:1px solid var(--border);border-radius:var(--r);background:var(--white);
  cursor:text;min-height:42px;align-items:center;position:relative;
}
.tag-input-wrap:focus-within{border-color:var(--primary);box-shadow:0 0 0 3px rgba(91,28,22,.08);}
.tag{
  display:inline-flex;align-items:center;gap:.3rem;
  background:var(--primary);color:#fff;border-radius:20px;
  padding:.2rem .55rem .2rem .65rem;font-size:.78rem;font-weight:500;white-space:nowrap;
  cursor:pointer;
}
.tag-level{
  font-size:.65rem;opacity:.85;font-weight:400;
  border-left:1px solid rgba(255,255,255,.4);padding-left:.35rem;margin-left:.1rem;
}
.tag button{
  background:none;border:none;color:rgba(255,255,255,.8);cursor:pointer;
  font-size:.95rem;line-height:1;padding:0;margin:0;
}
.tag button:hover{color:#fff;}
.tag-input-wrap input[type=text]{
  border:none;outline:none;background:transparent;font-size:.875rem;
  font-family:inherit;min-width:140px;flex:1;padding:.1rem 0;
}
.tag-dropdown{
  position:absolute;z-index:200;background:var(--white);
  border:1px solid var(--border);border-radius:var(--r);
  box-shadow:0 4px 16px rgba(0,0,0,.1);
  max-height:220px;overflow-y:auto;width:100%;
  left:0;top:calc(100% + 4px);
}
.tag-option{padding:.5rem .85rem;font-size:.85rem;cursor:pointer;}
.tag-option:hover{background:var(--bg);}
.level-btn:hover,.prof-btn:hover{background:var(--bg) !important;}
.form-group{position:relative;}
</style>

<script>
const LEVEL_COLORS = {Beginner:'#94a3b8',Intermediate:'#f59e0b',Advanced:'#3b82f6',Expert:'#16a34a'};
const PROF_COLORS  = {Basic:'#94a3b8',Conversational:'#f59e0b',Proficient:'#3b82f6',Fluent:'#7c3aed',Native:'#16a34a'};

// ── SKILLS ──────────────────────────────────────────────────
function getSkills() {
  return [...document.querySelectorAll('#skills-wrap .skill-tag')].map(t => ({
    name:  t.dataset.name,
    level: t.dataset.level || ''
  }));
}
function syncSkillsHidden() {
  document.getElementById('skills-hidden').value =
    getSkills().map(s => s.level ? s.name+':'+s.level : s.name).join(', ');
}
function buildSkillTag(name, level) {
  const color = LEVEL_COLORS[level] || 'var(--primary)';
  const span  = document.createElement('span');
  span.className   = 'tag skill-tag';
  span.dataset.name  = name;
  span.dataset.level = level || '';
  span.style.background = color;
  span.innerHTML = name
    + (level ? '<span class="tag-level">'+level+'</span>' : '')
    + '<button type="button" onclick="removeSkillTag(this)">×</button>';
  span.addEventListener('click', e => { if (e.target.tagName !== 'BUTTON') openLevelPicker(span); });
  return span;
}
function addSkill(name, level) {
  name = name.trim();
  if (!name) return;
  const existing = getSkills().map(s => s.name.toLowerCase());
  if (existing.includes(name.toLowerCase())) return;
  const wrap = document.getElementById('skills-wrap');
  const inp  = document.getElementById('skills-input');
  wrap.insertBefore(buildSkillTag(name, level || ''), inp);
  inp.value = '';
  syncSkillsHidden();
  filterSkillsDropdown('');
}
function removeSkillTag(btn) {
  btn.parentElement.remove();
  syncSkillsHidden();
}

// Level picker
let _levelTarget = null;
function openLevelPicker(tagEl) {
  _levelTarget = tagEl;
  const picker = document.getElementById('level-picker');
  const isCustom = tagEl === '__new__';
  document.getElementById('custom-skill-row').style.display = 'none';
  document.getElementById('level-picker-label').textContent = 'Competency level for: ' + (isCustom ? 'new skill' : tagEl.dataset.name);
  // Position near tag
  const wrap = document.getElementById('skills-wrap');
  const wr   = wrap.getBoundingClientRect();
  picker.style.top  = (wrap.offsetTop + wrap.offsetHeight + 4) + 'px';
  picker.style.left = wrap.offsetLeft + 'px';
  picker.style.display = 'block';
}
function closeLevelPicker() {
  document.getElementById('level-picker').style.display = 'none';
  _levelTarget = null;
}
document.querySelectorAll('.level-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const level = btn.dataset.level;
    if (!_levelTarget) return;
    if (_levelTarget === '__new__') {
      const customName = document.getElementById('custom-skill-input').value.trim();
      if (customName) addSkill(customName, level);
    } else {
      _levelTarget.dataset.level = level;
      _levelTarget.style.background = LEVEL_COLORS[level] || 'var(--primary)';
      const existing = _levelTarget.querySelector('.tag-level');
      if (existing) existing.textContent = level;
      else {
        const s = document.createElement('span');
        s.className = 'tag-level'; s.textContent = level;
        _levelTarget.insertBefore(s, _levelTarget.querySelector('button'));
      }
      syncSkillsHidden();
    }
    closeLevelPicker();
  });
});

// Skills dropdown
function filterSkillsDropdown(q) {
  const dd = document.getElementById('skills-dropdown');
  const existing = getSkills().map(s => s.name.toLowerCase());
  let any = false;
  dd.querySelectorAll('.tag-option:not(.tag-option-other)').forEach(o => {
    const match = !q || o.textContent.toLowerCase().includes(q.toLowerCase());
    const used  = existing.includes(o.dataset.val.toLowerCase());
    o.style.display = (match && !used) ? '' : 'none';
    if (match && !used) any = true;
  });
  dd.style.display = (any || q) ? 'block' : 'none';
}
document.querySelectorAll('#skills-dropdown .tag-option:not(.tag-option-other)').forEach(o => {
  o.addEventListener('mousedown', e => {
    e.preventDefault();
    openLevelPickerForNew(o.dataset.val);
    document.getElementById('skills-dropdown').style.display = 'none';
  });
});
document.querySelector('.tag-option-other').addEventListener('mousedown', e => {
  e.preventDefault();
  document.getElementById('skills-dropdown').style.display = 'none';
  openLevelPickerCustom();
});
function openLevelPickerForNew(name) {
  _levelTarget = '__new__';
  document.getElementById('custom-skill-row').style.display = 'none';
  document.getElementById('custom-skill-input').value = name;
  document.getElementById('level-picker-label').textContent = 'Competency level for: ' + name;
  positionPicker('level-picker','skills-wrap');
}
function openLevelPickerCustom() {
  _levelTarget = '__new__';
  document.getElementById('custom-skill-row').style.display = 'block';
  document.getElementById('custom-skill-input').value = '';
  document.getElementById('level-picker-label').textContent = 'Add custom skill';
  positionPicker('level-picker','skills-wrap');
  setTimeout(() => document.getElementById('custom-skill-input').focus(), 50);
}
function positionPicker(pickerId, wrapId) {
  const wrap   = document.getElementById(wrapId);
  const picker = document.getElementById(pickerId);
  picker.style.top  = (wrap.offsetTop + wrap.offsetHeight + 4) + 'px';
  picker.style.left = wrap.offsetLeft + 'px';
  picker.style.display = 'block';
}

const skillsInp = document.getElementById('skills-input');
skillsInp.addEventListener('focus', () => filterSkillsDropdown(skillsInp.value));
skillsInp.addEventListener('blur',  () => setTimeout(() => document.getElementById('skills-dropdown').style.display='none', 200));
skillsInp.addEventListener('input', () => filterSkillsDropdown(skillsInp.value));
skillsInp.addEventListener('keydown', e => {
  if ((e.key==='Enter'||e.key===',') && skillsInp.value.trim()) {
    e.preventDefault();
    openLevelPickerForNew(skillsInp.value.replace(',','').trim());
    skillsInp.value = '';
    document.getElementById('skills-dropdown').style.display = 'none';
  } else if (e.key==='Backspace' && !skillsInp.value) {
    const tags = document.querySelectorAll('#skills-wrap .skill-tag');
    if (tags.length) { tags[tags.length-1].remove(); syncSkillsHidden(); }
  }
});
document.getElementById('skills-wrap').addEventListener('click', e => {
  if (e.target === e.currentTarget || e.target.tagName==='INPUT') skillsInp.focus();
});
document.addEventListener('click', e => {
  if (!e.target.closest('#skills-wrap') && !e.target.closest('#level-picker') && !e.target.closest('#skills-dropdown')) {
    document.getElementById('skills-dropdown').style.display = 'none';
    closeLevelPicker();
  }
});

// ── LANGUAGES ───────────────────────────────────────────────
function getLangs() {
  return [...document.querySelectorAll('#langs-wrap .lang-tag')].map(t => ({
    name: t.dataset.name,
    prof: t.dataset.prof || ''
  }));
}
function syncLangsHidden() {
  document.getElementById('langs-hidden').value =
    getLangs().map(l => l.prof ? l.name+':'+l.prof : l.name).join(', ');
}
function buildLangTag(name, prof) {
  const color = PROF_COLORS[prof] || 'var(--primary)';
  const span  = document.createElement('span');
  span.className = 'tag lang-tag';
  span.dataset.name = name;
  span.dataset.prof = prof || '';
  span.style.background = color;
  span.innerHTML = name
    + (prof ? '<span class="tag-level">'+prof+'</span>' : '')
    + '<button type="button" onclick="removeLangTag(this)">×</button>';
  span.addEventListener('click', e => { if (e.target.tagName !== 'BUTTON') openProfPicker(span); });
  return span;
}
function addLang(name, prof) {
  name = name.trim();
  if (!name) return;
  const existing = getLangs().map(l => l.name.toLowerCase());
  if (existing.includes(name.toLowerCase())) return;
  const wrap = document.getElementById('langs-wrap');
  const inp  = document.getElementById('langs-input');
  wrap.insertBefore(buildLangTag(name, prof || ''), inp);
  syncLangsHidden();
}
function removeLangTag(btn) {
  btn.parentElement.remove();
  syncLangsHidden();
}

let _profTarget = null, _pendingLang = null;
function openProfPicker(tagEl) {
  _profTarget  = tagEl;
  _pendingLang = null;
  document.getElementById('prof-picker-label').textContent = 'Proficiency: ' + tagEl.dataset.name;
  positionPicker('prof-picker','langs-wrap');
}
function openProfPickerForNew(name) {
  _profTarget  = null;
  _pendingLang = name;
  document.getElementById('prof-picker-label').textContent = 'Proficiency for: ' + name;
  positionPicker('prof-picker','langs-wrap');
}
function closeProfPicker() {
  document.getElementById('prof-picker').style.display = 'none';
  _profTarget = null; _pendingLang = null;
}
document.querySelectorAll('.prof-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const prof = btn.dataset.prof;
    if (_pendingLang) {
      addLang(_pendingLang, prof);
    } else if (_profTarget) {
      _profTarget.dataset.prof = prof;
      _profTarget.style.background = PROF_COLORS[prof] || 'var(--primary)';
      const existing = _profTarget.querySelector('.tag-level');
      if (existing) existing.textContent = prof;
      else {
        const s = document.createElement('span');
        s.className = 'tag-level'; s.textContent = prof;
        _profTarget.insertBefore(s, _profTarget.querySelector('button'));
      }
      syncLangsHidden();
    }
    closeProfPicker();
  });
});

function showLangDropdown() {
  const dd = document.getElementById('langs-dropdown');
  const existing = getLangs().map(l => l.name.toLowerCase());
  dd.querySelectorAll('.tag-option').forEach(o => {
    o.style.display = existing.includes(o.dataset.val.toLowerCase()) ? 'none' : '';
  });
  dd.style.display = 'block';
}
function hideLangDropdown(delay) {
  setTimeout(() => document.getElementById('langs-dropdown').style.display='none', delay||0);
}
document.querySelectorAll('#langs-dropdown .tag-option').forEach(o => {
  o.addEventListener('mousedown', e => {
    e.preventDefault();
    document.getElementById('langs-dropdown').style.display = 'none';
    openProfPickerForNew(o.dataset.val);
  });
});
document.getElementById('langs-wrap').addEventListener('click', e => {
  if (e.target === e.currentTarget || e.target.tagName==='INPUT') {
    document.getElementById('langs-input').removeAttribute('readonly');
    document.getElementById('langs-input').focus();
  }
});
document.addEventListener('click', e => {
  if (!e.target.closest('#langs-wrap') && !e.target.closest('#prof-picker') && !e.target.closest('#langs-dropdown')) {
    hideLangDropdown(0);
    closeProfPicker();
  }
});
</script>

<?php include '../includes/footer.php'; ?>

<!-- CV Preview Modal -->
<div id="cv-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:var(--radius);width:90vw;max-width:900px;height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-bottom:1px solid var(--border);flex-shrink:0">
      <span class="fw-600">CV Preview</span>
      <div style="display:flex;gap:.5rem">
        <a id="cv-download" href="#" target="_blank" class="btn btn-outline btn-sm">Download</a>
        <button type="button" class="btn btn-outline btn-sm" onclick="closeCvPreview()">✕ Close</button>
      </div>
    </div>
    <iframe id="cv-frame" src="" style="flex:1;border:none;width:100%"></iframe>
  </div>
</div>
<script>
function openCvPreview(url) {
    document.getElementById('cv-frame').src = url;
    document.getElementById('cv-download').href = url;
    const m = document.getElementById('cv-modal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeCvPreview() {
    document.getElementById('cv-modal').style.display = 'none';
    document.getElementById('cv-frame').src = '';
    document.body.style.overflow = '';
}
document.getElementById('cv-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCvPreview();
});
</script>
