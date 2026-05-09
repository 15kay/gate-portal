<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    503,
    '&#x1F6E0;&#xFE0F;',
    'Service Unavailable',
    'The portal cannot complete your request right now. This is usually a temporary database or network issue. Please wait a moment and refresh the page. If this keeps happening, contact your system administrator.',
    $exception,
    $isDev
);
