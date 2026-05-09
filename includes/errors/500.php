<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    500,
    '&#x1F6A8;',
    'Internal Server Error',
    'Something went wrong on our end. The error has been logged and will be investigated. Please try again in a moment — if the problem persists, contact your system administrator.',
    $exception,
    $isDev
);
