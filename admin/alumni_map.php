<?php
require_once '../includes/auth_guard.php';
require_min_role('reports_admin');
require_once '../config/db.php';

// Use COALESCE: prefer profile location, fall back to employment record location
$rows = $pdo->query("
    SELECT u.id, u.full_name, u.email,
           COALESCE(NULLIF(TRIM(ap.location),''), NULLIF(TRIM(er.location),'')) AS location,
           ap.graduation_year, ap.degree, ap.department, ap.profile_photo,
           er.employment_type, er.employer, er.job_title
    FROM users u
    JOIN alumni_profiles ap ON ap.user_id = u.id
    LEFT JOIN employment_records er ON er.user_id = u.id AND er.is_current = 1
    WHERE u.role = 'alumni'
    ORDER BY u.full_name
")->fetchAll();

$total            = count($rows);
$with_location    = count(array_filter($rows, fn($r) => !empty($r['location'])));
$employed_count   = count(array_filter($rows, fn($r) => in_array($r['employment_type'] ?? '', ['Full-time','Part-time','Self-employed','Freelance'])));
$unemployed_count = count(array_filter($rows, fn($r) => ($r['employment_type'] ?? '') === 'Unemployed'));

$alumni_json = json_encode(array_values(array_map(fn($r) => [
    'id'        => (int)$r['id'],
    'name'      => $r['full_name'],
    'email'     => $r['email'],
    'location'  => $r['location'] ?? '',
    'degree'    => $r['degree'] ?? '',
    'dept'      => $r['department'] ?? '',
    'grad_year' => $r['graduation_year'] ?? '',
    'emp_type'  => $r['employment_type'] ?? '',
    'employer'  => $r['employer'] ?? '',
    'job_title' => $r['job_title'] ?? '',
    'photo'     => $r['profile_photo'] ? '/gate-portal/' . $r['profile_photo'] : '',
    'employed'  => in_array($r['employment_type'] ?? '', ['Full-time','Part-time','Self-employed','Freelance']),
], $rows)));
if (json_last_error() !== JSON_ERROR_NONE) {
    log_app_error('application', 'json_encode failed for alumni map data: ' . json_last_error_msg());
    $alumni_json = '[]';
}

include '../includes/header.php';
?>

<div class="page-header">
  <div class="page-header-left">
    <h1>Alumni Map</h1>
    <p>Geographic distribution of <?= $total ?> alumni &mdash; <?= $with_location ?> with location data</p>
  </div>
  <div class="page-header-actions">
    <a href="/gate-portal/admin/alumni.php" class="btn btn-outline btn-sm">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      All Alumni
    </a>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.25rem">
  <div class="stat-card">
    <div class="stat-icon primary"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
    <div class="stat-body"><div class="stat-num"><?= $total ?></div><div class="stat-label">Total Alumni</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon info"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--info)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
    <div class="stat-body"><div class="stat-num"><?= $with_location ?></div><div class="stat-label">Mappable</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon success"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div>
    <div class="stat-body"><div class="stat-num"><?= $employed_count ?></div><div class="stat-label">Employed</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon danger"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
    <div class="stat-body"><div class="stat-num"><?= $unemployed_count ?></div><div class="stat-label">Unemployed</div></div>
  </div>
</div>

<!-- Map card -->
<div class="card" style="padding:0;overflow:hidden">

  <!-- Toolbar -->
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
      <button class="btn btn-sm btn-primary"  id="filter-all"        onclick="setFilter('all')">All</button>
      <button class="btn btn-sm btn-outline"  id="filter-employed"   onclick="setFilter('employed')">
        <span style="width:8px;height:8px;border-radius:50%;background:#1a6b3a;display:inline-block;margin-right:.3rem"></span>Employed
      </button>
      <button class="btn btn-sm btn-outline"  id="filter-unemployed" onclick="setFilter('unemployed')">
        <span style="width:8px;height:8px;border-radius:50%;background:#c0392b;display:inline-block;margin-right:.3rem"></span>Unemployed
      </button>
      <button class="btn btn-sm btn-outline"  id="filter-other"      onclick="setFilter('other')">
        <span style="width:8px;height:8px;border-radius:50%;background:#D5820F;display:inline-block;margin-right:.3rem"></span>Other
      </button>
    </div>
    <div style="display:flex;align-items:center;gap:.6rem">
      <span id="map-count" class="badge badge-secondary" style="font-size:.75rem">loading…</span>
      <input type="text" id="map-search" placeholder="Search name or city…"
             style="padding:.4rem .75rem;border:1px solid var(--border);border-radius:var(--r);font-size:.82rem;font-family:inherit;width:190px">
    </div>
  </div>

  <!-- Geocode progress bar -->
  <div id="geocode-bar" style="padding:.6rem 1.25rem;background:#eff6ff;border-bottom:1px solid #bfdbfe;display:flex;align-items:center;gap:.75rem;font-size:.82rem;color:#1e40af">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" id="geo-spinner" style="flex-shrink:0;animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
    <span id="geo-msg">Geocoding locations…</span>
    <div style="flex:1;height:4px;background:#bfdbfe;border-radius:4px;overflow:hidden">
      <div id="geo-prog-fill" style="height:100%;background:#1e40af;border-radius:4px;width:0%;transition:width .3s"></div>
    </div>
    <span id="geo-prog-text" style="flex-shrink:0;font-weight:600">0%</span>
  </div>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <div id="alumni-map" style="height:580px;width:100%"></div>

  <!-- Legend -->
  <div style="display:flex;align-items:center;gap:1.5rem;padding:.7rem 1.25rem;border-top:1px solid var(--border);flex-wrap:wrap;background:#fafafa">
    <span class="text-xs fw-600 text-muted" style="text-transform:uppercase;letter-spacing:.06em">Legend</span>
    <?php foreach ([
      ['#1a6b3a','Employed'],
      ['#c0392b','Unemployed'],
      ['#D5820F','Further Studies / Other'],
      ['#71717a','Unknown'],
    ] as [$col,$label]): ?>
    <span style="display:flex;align-items:center;gap:.35rem;font-size:.8rem;color:var(--text-2)">
      <svg width="12" height="16" viewBox="0 0 12 16"><path d="M6 0C2.69 0 0 2.69 0 6c0 4.5 6 10 6 10s6-5.5 6-10C12 2.69 9.31 0 6 0z" fill="<?= $col ?>"/><circle cx="6" cy="6" r="2.5" fill="#fff"/></svg>
      <?= $label ?>
    </span>
    <?php endforeach; ?>
    <span id="no-location-note" class="text-xs text-muted" style="margin-left:auto"></span>
  </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.leaflet-popup-content { margin: 12px 14px; }
.leaflet-popup-content-wrapper { border-radius: 10px !important; box-shadow: 0 8px 30px rgba(0,0,0,.15) !important; }
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const ALUMNI = <?= $alumni_json ?>;

// ── Map ───────────────────────────────────────────────────────────────────────
const map = L.map('alumni-map', { zoomControl: true }).setView([-29.0, 25.0], 5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 18
}).addTo(map);

// ── Icons ─────────────────────────────────────────────────────────────────────
function pinColor(a) {
    if (a.employed)                    return '#1a6b3a';
    if (a.emp_type === 'Unemployed')   return '#c0392b';
    if (a.emp_type !== '')             return '#D5820F';
    return '#71717a';
}
function makeIcon(color) {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="26" height="34" viewBox="0 0 26 34">
        <path d="M13 0C5.82 0 0 5.82 0 13c0 9.75 13 21 13 21S26 22.75 26 13C26 5.82 20.18 0 13 0z"
              fill="${color}" stroke="rgba(255,255,255,.6)" stroke-width="1.2"/>
        <circle cx="13" cy="13" r="5" fill="#fff" opacity=".92"/>
    </svg>`;
    return L.divIcon({ html: svg, className: '', iconSize: [26,34], iconAnchor: [13,34], popupAnchor: [0,-36] });
}

// ── Popup ─────────────────────────────────────────────────────────────────────
function popupHtml(a) {
    const init = a.name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
    const avatar = a.photo
        ? `<img src="${a.photo}" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #e4e4e7;flex-shrink:0" onerror="this.outerHTML='<div style=\'width:44px;height:44px;border-radius:50%;background:#5B1C16;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0\'>${init}</div>'">`
        : `<div style="width:44px;height:44px;border-radius:50%;background:#5B1C16;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0">${init}</div>`;

    const empColors = {
        employed:   ['#dcf5e7','#166534'],
        unemployed: ['#fef2f2','#991b1b'],
        other:      ['#fef3c7','#92400e'],
        unknown:    ['#f4f4f5','#52525b'],
    };
    const ek = a.employed ? 'employed' : a.emp_type === 'Unemployed' ? 'unemployed' : a.emp_type ? 'other' : 'unknown';
    const [bg,fg] = empColors[ek];
    const label = a.emp_type || 'Unknown';
    const badge = `<span style="background:${bg};color:${fg};padding:.15rem .55rem;border-radius:20px;font-size:.7rem;font-weight:600">${label}</span>`;

    return `<div style="font-family:'Inter',sans-serif;min-width:210px;max-width:250px">
        <div style="display:flex;gap:.65rem;align-items:center;margin-bottom:.55rem">
            ${avatar}
            <div style="min-width:0">
                <div style="font-weight:700;font-size:.88rem;color:#1a1a2e;line-height:1.25">${a.name}</div>
                <div style="font-size:.7rem;color:#71717a;margin-top:.1rem">📍 ${a.location}</div>
            </div>
        </div>
        <div style="margin-bottom:.45rem">${badge}</div>
        ${(a.job_title||a.employer) ? `<div style="font-size:.76rem;color:#52525b;margin-bottom:.2rem">${a.job_title}${a.job_title&&a.employer?' at ':''}${a.employer}</div>` : ''}
        ${a.degree ? `<div style="font-size:.72rem;color:#71717a">${a.degree}${a.grad_year?' · Class of '+a.grad_year:''}</div>` : ''}
        <div style="margin-top:.6rem;padding-top:.55rem;border-top:1px solid #f0f0f2">
            <a href="/gate-portal/admin/view_alumni.php?id=${a.id}"
               style="font-size:.76rem;color:#5B1C16;font-weight:600;text-decoration:none">View Profile →</a>
        </div>
    </div>`;
}

// ── Geocode ───────────────────────────────────────────────────────────────────
const geoCache = {};

async function geocode(loc) {
    if (loc in geoCache) return geoCache[loc];
    try {
        // Try with ", South Africa" appended first, then bare
        for (const q of [loc + ', South Africa', loc]) {
            const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=1&countrycodes=za`;
            const res = await fetch(url, { headers: { 'Accept-Language': 'en' } });
            const data = await res.json();
            if (data.length) {
                geoCache[loc] = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                return geoCache[loc];
            }
        }
    } catch(e) {}
    geoCache[loc] = null;
    return null;
}

// ── State ─────────────────────────────────────────────────────────────────────
let allMarkers = [];
let currentFilter = 'all';
let searchTerm = '';

// ── Build + place markers ─────────────────────────────────────────────────────
async function loadMarkers() {
    const withLoc = ALUMNI.filter(a => a.location);
    const noLoc   = ALUMNI.length - withLoc.length;

    if (noLoc > 0) {
        document.getElementById('no-location-note').textContent =
            `${noLoc} alumni have no location data and are not shown`;
    }

    if (!withLoc.length) {
        document.getElementById('geocode-bar').innerHTML =
            '<span style="color:#991b1b;font-weight:600">⚠ No alumni have location data yet. Ask alumni to update their profiles.</span>';
        document.getElementById('map-count').textContent = '0 shown';
        return;
    }

    const uniqueLocs = [...new Set(withLoc.map(a => a.location))];
    let done = 0;

    for (const loc of uniqueLocs) {
        await geocode(loc);
        done++;
        const pct = Math.round(done / uniqueLocs.length * 100);
        document.getElementById('geo-prog-fill').style.width = pct + '%';
        document.getElementById('geo-prog-text').textContent = pct + '%';
        document.getElementById('geo-msg').textContent = `Geocoding "${loc}"…`;
        if (done < uniqueLocs.length) await new Promise(r => setTimeout(r, 350));
    }

    // Hide progress bar
    document.getElementById('geocode-bar').style.display = 'none';

    // Build markers with jitter for stacked pins
    const locIdx = {};
    for (const a of withLoc) {
        const coords = geoCache[a.location];
        if (!coords) continue;
        locIdx[a.location] = (locIdx[a.location] || 0) + 1;
        const angle  = locIdx[a.location] * 137.5 * Math.PI / 180; // golden angle spread
        const radius = locIdx[a.location] > 1 ? 0.018 * Math.sqrt(locIdx[a.location]) : 0;
        const lat = coords[0] + radius * Math.cos(angle);
        const lng = coords[1] + radius * Math.sin(angle);

        const marker = L.marker([lat, lng], { icon: makeIcon(pinColor(a)) })
            .bindPopup(popupHtml(a), { maxWidth: 270 });
        marker._a = a;
        allMarkers.push(marker);
    }

    applyFilter();
}

// ── Filter ────────────────────────────────────────────────────────────────────
function applyFilter() {
    allMarkers.forEach(m => map.removeLayer(m));

    const visible = allMarkers.filter(m => {
        const a = m._a;
        const fOk =
            currentFilter === 'all' ||
            (currentFilter === 'employed'   && a.employed) ||
            (currentFilter === 'unemployed' && a.emp_type === 'Unemployed') ||
            (currentFilter === 'other'      && !a.employed && a.emp_type !== 'Unemployed');
        const sOk = !searchTerm ||
            a.name.toLowerCase().includes(searchTerm) ||
            a.location.toLowerCase().includes(searchTerm) ||
            a.employer.toLowerCase().includes(searchTerm);
        return fOk && sOk;
    });

    visible.forEach(m => m.addTo(map));
    document.getElementById('map-count').textContent = visible.length + ' shown';

    // Fit bounds if markers exist
    if (visible.length) {
        const group = L.featureGroup(visible);
        map.fitBounds(group.getBounds().pad(0.15));
    }
}

function setFilter(f) {
    currentFilter = f;
    ['all','employed','unemployed','other'].forEach(id => {
        document.getElementById('filter-' + id).className =
            'btn btn-sm ' + (id === f ? 'btn-primary' : 'btn-outline');
    });
    applyFilter();
}

document.getElementById('map-search').addEventListener('input', function() {
    searchTerm = this.value.toLowerCase().trim();
    applyFilter();
});

loadMarkers();
</script>

<?php include '../includes/footer.php'; ?>
