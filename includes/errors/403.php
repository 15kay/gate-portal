<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    403,
    '&#x1F512;',
    'Access Denied',
    'You do not have permission to access this page. If you believe this is a mistake, please contact your system administrator or sign in with an account that has the required access level.',
    $exception,
    $isDev
);
