<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Maintenance — GATE Portal</title>
<link rel="stylesheet" href="/gate-portal/assets/css/style.css">
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)">
<div style="text-align:center;max-width:440px;padding:2.5rem;background:var(--white);border-radius:var(--r-lg);box-shadow:var(--sh);border:1px solid var(--border)">
  <div style="width:64px;height:64px;border-radius:50%;background:#fef3e0;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
  </div>
  <h2 style="font-size:1.3rem;font-weight:700;color:var(--text);margin-bottom:.6rem">Under Maintenance</h2>
  <p style="color:var(--muted);font-size:.9rem;line-height:1.7;margin-bottom:1.5rem"><?= htmlspecialchars($msg ?? 'The portal is currently undergoing scheduled maintenance. Please check back soon.') ?></p>
  <a href="/gate-portal/auth/logout.php" class="btn btn-outline btn-sm">Sign Out</a>
</div>
</body>
</html>
