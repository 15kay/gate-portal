<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    401,
    '&#x1F510;',
    'Authentication Required',
    'You must be signed in to access this page. Please log in with your credentials and try again.',
    $exception,
    $isDev
);
