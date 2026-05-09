<?php
/**
 * GitHub Webhook Auto-Deploy Script
 * 
 * Setup:
 * 1. On GitHub: Settings → Webhooks → Add webhook
 * 2. Payload URL: https://your-domain.wsu.ac.za/gate-portal/deploy.php
 * 3. Content type: application/json
 * 4. Secret: Set a strong secret below
 * 5. Events: Just the push event
 * 6. Active: ✓
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('WEBHOOK_SECRET', 'CHANGE_THIS_TO_A_STRONG_SECRET_KEY');
define('REPO_PATH', __DIR__);
define('BRANCH', 'main');
define('LOG_FILE', __DIR__ . '/deploy.log');

// ── Verify GitHub signature ──────────────────────────────────────────────────
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!$signature) {
    http_response_code(403);
    die('Missing signature');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    die('Invalid signature');
}

// ── Parse payload ─────────────────────────────────────────────────────────────
$data = json_decode($payload, true);
if (!$data || !isset($data['ref'])) {
    http_response_code(400);
    die('Invalid payload');
}

// Only deploy on push to main branch
if ($data['ref'] !== 'refs/heads/' . BRANCH) {
    die('Ignoring push to ' . $data['ref']);
}

// ── Execute deployment ────────────────────────────────────────────────────────
$output = [];
$timestamp = date('Y-m-d H:i:s');

// Change to repo directory
chdir(REPO_PATH);

// Pull latest changes
exec('git fetch origin ' . BRANCH . ' 2>&1', $output, $return1);
exec('git reset --hard origin/' . BRANCH . ' 2>&1', $output, $return2);

// Log the deployment
$logEntry = "\n[{$timestamp}] Deployment triggered by {$data['pusher']['name']}\n";
$logEntry .= "Commit: {$data['head_commit']['id']}\n";
$logEntry .= "Message: {$data['head_commit']['message']}\n";
$logEntry .= "Output:\n" . implode("\n", $output) . "\n";
$logEntry .= "Status: " . (($return1 === 0 && $return2 === 0) ? 'SUCCESS' : 'FAILED') . "\n";
$logEntry .= str_repeat('-', 80) . "\n";

file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);

// Return response
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Deployment completed',
    'timestamp' => $timestamp,
    'commit' => substr($data['head_commit']['id'], 0, 7),
]);
