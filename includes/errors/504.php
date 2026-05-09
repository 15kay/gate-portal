<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    504,
    '&#x1F551;',
    'Gateway Timeout',
    'The server did not receive a timely response from an upstream service. Please try again in a moment. If the problem persists, contact your system administrator.',
    $exception,
    $isDev
);
