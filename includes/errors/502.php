<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    502,
    '&#x1F4E1;',
    'Bad Gateway',
    'The server received an invalid response from an upstream service. This is usually a temporary issue — please wait a moment and refresh the page.',
    $exception,
    $isDev
);
