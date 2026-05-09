<?php
// When served directly by Apache (ErrorDocument), the handler isn't loaded yet.
if (!function_exists('_gate_error_html')) {
    require_once __DIR__ . '/../error_handler.php';
}
// Variables $isDev and $exception are injected by render_error_page(); default when direct.
$isDev     = $isDev     ?? (($_ENV['APP_ENV'] ?? 'production') === 'development');
$exception = $exception ?? null;
_gate_error_html(
    400,
    '&#x26A0;&#xFE0F;',
    'Bad Request',
    'The server could not understand your request. This can happen if the form data was malformed, a required field was missing, or the request was sent in an unexpected format. Please go back and try again.',
    $exception,
    $isDev
);
