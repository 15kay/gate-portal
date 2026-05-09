<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    404,
    '&#x1F50D;',
    'Page Not Found',
    'The page you are looking for does not exist or may have been moved. Check the URL for typos, or use the button below to return to the portal.',
    $exception,
    $isDev
);
