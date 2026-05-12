<?php
/**
 * Global error handler for GATE Portal.
 *
 * Covers:
 *   - PHP errors (E_WARNING, E_NOTICE, E_DEPRECATED, E_STRICT, …)
 *   - Fatal / parse / compile errors (via shutdown function)
 *   - Uncaught exceptions (PDOException, RuntimeException, ErrorException, …)
 *   - Database connection and query failures
 *   - Network / socket errors (SMTP, external HTTP)
 *   - File-system errors (permission denied, disk full, missing files)
 *   - HTTP-level errors (400, 403, 404, 500, 503)
 *   - Application-level errors (CSRF, auth)
 *
 * Include this file once, as early as possible (top of config/db.php).
 */

if (defined('GATE_ERROR_HANDLER')) return;
define('GATE_ERROR_HANDLER', true);

// ─── Constants ────────────────────────────────────────────────────────────────

define('LOG_DIR', dirname(__DIR__) . '/logs');

// ─── Centralized logger ───────────────────────────────────────────────────────

/**
 * Append a structured line to logs/error.log.
 *
 * @param string $type    One of: php_error, php_fatal, php_exception, database,
 *                        network, filesystem, http, csrf, auth, application
 * @param string $message Human-readable description
 * @param array  $ctx     Optional key=value pairs added to the log line
 */
function log_app_error(string $type, string $message, array $ctx = []): void {
    if (!is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0750, true);
    }

    $ctx['ts']  = date('Y-m-d H:i:s');
    $ctx['uri'] = $_SERVER['REQUEST_URI'] ?? '-';
    $ctx['ip']  = $_SERVER['REMOTE_ADDR'] ?? '-';
    $ctx['ua']  = substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 120);

    $pairs = [];
    foreach ($ctx as $k => $v) {
        $pairs[] = $k . '="' . str_replace(['"', "\n"], ["'", ' '], (string)$v) . '"';
    }

    $line = '[' . strtoupper($type) . '] ' . $message . ' | ' . implode(' ', $pairs) . "\n";
    @file_put_contents(LOG_DIR . '/error.log', $line, FILE_APPEND | LOCK_EX);
}

// ─── Error page renderer ──────────────────────────────────────────────────────

/**
 * Send an HTTP status code and render a standalone error page, then exit.
 * Clears any partial output already in the buffer before rendering.
 *
 * @param int            $code      HTTP status code (400–504)
 * @param Throwable|null $exception Optional exception for dev-mode details
 */
function render_error_page(int $code, ?Throwable $exception = null): never {
    // Discard any partial page content so the error page is clean
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($code);
    }
    $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
    $page  = __DIR__ . "/errors/{$code}.php";
    if (!file_exists($page)) {
        $page = __DIR__ . '/errors/500.php';
    }
    // $isDev and $exception are in scope for the included file
    include $page;
    exit;
}

// ─── Upload error helper ──────────────────────────────────────────────────────

/**
 * Translate a $_FILES[…]['error'] code into a user-facing message,
 * log it, and return the message string (empty string = no error).
 *
 * Usage:
 *   $msg = check_upload_error($_FILES['cv']['error'], 'cv');
 *   if ($msg) { $error = $msg; } // show to user
 */
function check_upload_error(int $code, string $field = 'file'): string {
    $messages = [
        UPLOAD_ERR_OK         => '',
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server\'s maximum upload size.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form\'s maximum allowed size.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file was submitted.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error: temporary upload directory is missing.',
        UPLOAD_ERR_CANT_WRITE => 'Server error: could not write the uploaded file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the file upload.',
    ];

    if ($code === UPLOAD_ERR_OK) return '';

    $msg = $messages[$code] ?? "Unknown upload error (code {$code}).";

    // Server-side codes warrant logging; user-side codes (size, no file) don't
    $serverCodes = [UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION];
    if (in_array($code, $serverCodes, true)) {
        log_app_error('filesystem', "Upload server error on field '{$field}'", [
            'error_code' => (string)$code,
            'error_msg'  => $msg,
        ]);
    } else {
        log_app_error('application', "Upload rejected on field '{$field}'", [
            'error_code' => (string)$code,
            'error_msg'  => $msg,
        ]);
    }

    return $msg;
}

// ─── Internal HTML renderer ───────────────────────────────────────────────────

/**
 * Shared standalone HTML layout used by every error page.
 *
 * @internal Called only by includes/errors/*.php
 */
function _gate_error_html(
    int        $code,
    string     $icon,
    string     $title,
    string     $body,
    ?Throwable $exception,
    bool       $isDev
): void {
    $ref = strtoupper(substr(md5(microtime() . $code), 0, 8));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $code ?> — <?= htmlspecialchars($title) ?> · GATE Portal</title>
  <style>
    :root{--primary:#5B1C16;--accent:#D5820F;--bg:#f5f5f7;--text:#1a1a2e;--muted:#71717a;--border:#e4e4e7;--danger:#c0392b}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter','Segoe UI',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem}
    .card{background:#fff;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.1);padding:3rem 2.5rem;max-width:540px;width:100%;text-align:center;position:relative;overflow:hidden}
    .accent-bar{height:4px;background:linear-gradient(90deg,var(--primary),var(--accent));position:absolute;top:0;left:0;right:0}
    .icon{font-size:2.8rem;margin-bottom:.75rem;line-height:1}
    .code{font-size:5.5rem;font-weight:800;color:var(--primary);line-height:1;letter-spacing:-2px;margin-bottom:.25rem}
    h1{font-size:1.35rem;font-weight:600;margin-bottom:.75rem}
    .desc{color:var(--muted);font-size:.9rem;line-height:1.7;margin-bottom:1.75rem}
    .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
    .btn{display:inline-block;padding:.55rem 1.4rem;border-radius:6px;text-decoration:none;font-size:.85rem;font-weight:500;transition:opacity .15s}
    .btn-primary{background:var(--primary);color:#fff}
    .btn-outline{border:1px solid var(--border);color:var(--text);background:#fff}
    .btn:hover{opacity:.85}
    .ref{font-size:.72rem;color:var(--muted);margin-top:1.75rem;letter-spacing:.3px}
    /* Dev details */
    details{margin-top:1.5rem;text-align:left;background:#fdf5f5;border:1px solid #e8d0d0;border-radius:6px;padding:.875rem 1rem;font-size:.78rem}
    summary{cursor:pointer;font-weight:600;color:var(--danger);user-select:none}
    summary::marker{color:var(--danger)}
    .dev-row{display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap}
    .dev-label{font-weight:600;color:var(--text);min-width:80px}
    .dev-val{color:#444;word-break:break-all}
    pre{font-size:.68rem;line-height:1.55;overflow-x:auto;background:#fff;border:1px solid #e8d0d0;border-radius:4px;padding:.75rem;margin-top:.75rem;white-space:pre-wrap;word-break:break-all;color:#333}
  </style>
</head>
<body>
  <div class="card">
    <div class="accent-bar"></div>
    <div class="icon"><?= $icon ?></div>
    <div class="code"><?= $code ?></div>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p class="desc"><?= $body ?></p>
    <div class="actions">
      <a href="/index.php" class="btn btn-primary">Back to Portal</a>
      <a href="javascript:history.back()" class="btn btn-outline">Go Back</a>
    </div>

    <?php if ($isDev && $exception): ?>
    <details>
      <summary>Developer Details (hidden in production)</summary>
      <div class="dev-row"><span class="dev-label">Type</span><span class="dev-val"><?= htmlspecialchars(get_class($exception)) ?></span></div>
      <div class="dev-row"><span class="dev-label">Message</span><span class="dev-val"><?= htmlspecialchars($exception->getMessage()) ?></span></div>
      <div class="dev-row"><span class="dev-label">File</span><span class="dev-val"><?= htmlspecialchars($exception->getFile()) ?>:<?= $exception->getLine() ?></span></div>
      <?php if ($exception->getPrevious()): ?>
      <div class="dev-row"><span class="dev-label">Caused by</span><span class="dev-val"><?= htmlspecialchars(get_class($exception->getPrevious())) ?>: <?= htmlspecialchars($exception->getPrevious()->getMessage()) ?></span></div>
      <?php endif; ?>
      <pre><?= htmlspecialchars($exception->getTraceAsString()) ?></pre>
    </details>
    <?php elseif ($isDev): ?>
    <details>
      <summary>Developer Details (hidden in production)</summary>
      <div class="dev-row"><span class="dev-label">Code</span><span class="dev-val"><?= $code ?></span></div>
      <div class="dev-row"><span class="dev-label">URL</span><span class="dev-val"><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '-') ?></span></div>
    </details>
    <?php endif; ?>

    <p class="ref">Reference: <?= $ref ?></p>
  </div>
</body>
</html>
    <?php
}

// ─── 1. PHP errors → ErrorException ──────────────────────────────────────────

set_error_handler(function(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
    // Respect the @ operator and the current error_reporting level
    if (!(error_reporting() & $errno)) {
        return false;
    }

    // Map severity to a readable label for the log
    $labels = [
        E_WARNING          => 'E_WARNING',
        E_NOTICE           => 'E_NOTICE',
        E_DEPRECATED       => 'E_DEPRECATED',
        E_USER_ERROR       => 'E_USER_ERROR',
        E_USER_WARNING     => 'E_USER_WARNING',
        E_USER_NOTICE      => 'E_USER_NOTICE',
        E_USER_DEPRECATED  => 'E_USER_DEPRECATED',
        E_STRICT           => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    ];
    $label = $labels[$errno] ?? "E_UNKNOWN({$errno})";

    // Categorise: file-system errors have predictable messages
    $fsPatterns = ['Permission denied', 'No such file', 'failed to open', 'disk full',
                   'not a directory', 'Is a directory', 'file_get_contents', 'fopen', 'fwrite'];
    $isFS = false;
    foreach ($fsPatterns as $p) {
        if (stripos($errstr, $p) !== false) { $isFS = true; break; }
    }

    $type = $isFS ? 'filesystem' : 'php_error';
    log_app_error($type, "[{$label}] {$errstr}", [
        'file'     => $errfile,
        'line'     => (string)$errline,
        'severity' => $label,
    ]);

    // Convert to exception so the exception handler takes over
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// ─── 2. Uncaught exceptions ───────────────────────────────────────────────────

set_exception_handler(function(Throwable $e): void {
    // Determine category
    $msg = $e->getMessage();

    $type = match(true) {
        $e instanceof PDOException
            => 'database',

        $e instanceof ErrorException && in_array($e->getSeverity(), [E_WARNING, E_NOTICE, E_DEPRECATED, E_STRICT, E_USER_DEPRECATED], true)
            => 'php_warning',

        // Socket / network signals
        str_contains($msg, 'fsockopen') || str_contains($msg, 'socket') ||
        str_contains($msg, 'SMTP')      || str_contains($msg, 'network') ||
        str_contains($msg, 'connect')   || str_contains($msg, 'SSL') ||
        str_contains($msg, 'timed out') || str_contains($msg, 'dns')
            => 'network',

        // File-system signals
        str_contains($msg, 'Permission denied') || str_contains($msg, 'No such file') ||
        str_contains($msg, 'failed to open')    || str_contains($msg, 'disk full') ||
        str_contains($msg, 'file_get_contents') || str_contains($msg, 'fopen')
            => 'filesystem',

        default => 'php_exception',
    };

    log_app_error($type, $e->getMessage(), [
        'class' => get_class($e),
        'code'  => (string)$e->getCode(),
        'file'  => $e->getFile(),
        'line'  => (string)$e->getLine(),
        'trace' => substr(str_replace("\n", ' > ', $e->getTraceAsString()), 0, 800),
    ]);

    // Choose status code
    $httpCode = match($type) {
        'database' => 503,
        'network'  => 503,
        default    => 500,
    };

    render_error_page($httpCode, $e);
});

// ─── 3. Fatal / parse / compile errors (shutdown) ────────────────────────────

register_shutdown_function(function(): void {
    $err = error_get_last();
    if (!$err) return;

    $fatals = [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];
    if (!in_array($err['type'], $fatals, true)) return;

    $labels = [
        E_ERROR          => 'E_ERROR',
        E_PARSE          => 'E_PARSE',
        E_CORE_ERROR     => 'E_CORE_ERROR',
        E_CORE_WARNING   => 'E_CORE_WARNING',
        E_COMPILE_ERROR  => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
    ];
    $label = $labels[$err['type']] ?? 'E_FATAL';

    log_app_error('php_fatal', "[{$label}] " . $err['message'], [
        'file' => $err['file'],
        'line' => (string)$err['line'],
    ]);

    // Clear buffered partial output, then render the error page
    $e = new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']);
    render_error_page(500, $e); // render_error_page() calls ob_end_clean() internally
});
