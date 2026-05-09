<?php
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $valid = !empty($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);

    if (!$valid) {
        // Log the attempt
        if (function_exists('log_app_error')) {
            log_app_error('csrf', 'CSRF token mismatch', [
                'method' => $_SERVER['REQUEST_METHOD'],
                'referer' => $_SERVER['HTTP_REFERER'] ?? '-',
            ]);
        }

        // Render a proper HTML error page if the handler is loaded,
        // otherwise fall back to a safe plain-text response.
        if (function_exists('render_error_page')) {
            render_error_page(403);
        } else {
            http_response_code(403);
            exit('Invalid request. Please go back and try again.');
        }
    }
}
