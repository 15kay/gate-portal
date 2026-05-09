<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    429,
    '&#x23F3;',
    'Too Many Requests',
    'You have made too many requests in a short period of time. Please wait a few minutes before trying again.',
    $exception,
    $isDev
);
