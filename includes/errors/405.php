<?php
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    405,
    '&#x274C;',
    'Method Not Allowed',
    'The HTTP method used for this request is not supported by this page. If you reached this page by submitting a form, please go back and try again.',
    $exception,
    $isDev
);
