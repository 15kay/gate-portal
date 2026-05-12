<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent page caching for authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Role hierarchy — higher index = more permissions
const ROLE_LEVELS = [
    'alumni'        => 0,
    'employer'      => 0,
    'reports_admin' => 1,
    'admin'         => 2,
    'super_admin'   => 3,
];

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
    // Enforce maintenance mode for alumni
    if (($_SESSION['role'] ?? '') === 'alumni') {
        global $pdo;
        if (!isset($pdo)) require_once dirname(__DIR__) . '/config/db.php';
        try {
            $m = $pdo->query("SELECT `value` FROM portal_settings WHERE `key`='maintenance_mode' LIMIT 1");
            if ($m && $m->fetchColumn() === '1') {
                require_once dirname(__DIR__) . '/config/db.php';
                $msg = '';
                try {
                    $r = $pdo->query("SELECT `value` FROM portal_settings WHERE `key`='maintenance_message' LIMIT 1");
                    $msg = $r ? $r->fetchColumn() : '';
                } catch (Throwable $e) {}
                if (!$msg) $msg = 'The portal is currently undergoing scheduled maintenance. Please check back soon.';
                http_response_code(503);
                include dirname(__DIR__) . '/includes/maintenance.php';
                exit;
            }
        } catch (Throwable $e) {}
    }
}

function require_role(string $role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: /auth/login.php');
        exit;
    }
}

// Allow access if user's role level >= required role level
function require_min_role(string $min_role) {
    require_login();
    $user_level = ROLE_LEVELS[$_SESSION['role']] ?? 0;
    $min_level  = ROLE_LEVELS[$min_role] ?? 99;
    if ($user_level < $min_level) {
        if (function_exists('log_app_error')) {
            log_app_error('auth', 'Access denied: insufficient role', [
                'user_id'   => (string)($_SESSION['user_id'] ?? '-'),
                'user_role' => $_SESSION['role'] ?? '-',
                'required'  => $min_role,
            ]);
        }
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

function is_admin(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function is_super_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'super_admin';
}

function is_employer(): bool {
    return ($_SESSION['role'] ?? '') === 'employer';
}

function can_post_jobs(): bool {
    return ($_SESSION['role'] ?? '') === 'employer';
}

function can_manage_alumni(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function can_view_alumni(): bool {
    return in_array($_SESSION['role'] ?? '', ['reports_admin', 'admin', 'super_admin']);
}

function can_manage_opportunities(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function can_view_opportunities(): bool {
    return in_array($_SESSION['role'] ?? '', ['reports_admin', 'admin', 'super_admin']);
}

function can_run_matching(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function can_select_candidates(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function can_submit_candidates(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function can_view_submissions(): bool {
    return in_array($_SESSION['role'] ?? '', ['reports_admin', 'admin', 'super_admin']);
}

function can_view_reports(): bool {
    return in_array($_SESSION['role'] ?? '', ['reports_admin', 'admin', 'super_admin']);
}

function can_manage_users(): bool {
    return ($_SESSION['role'] ?? '') === 'super_admin';
}

function can_view_audit_logs(): bool {
    return ($_SESSION['role'] ?? '') === 'super_admin';
}

function can_manage_settings(): bool {
    return ($_SESSION['role'] ?? '') === 'super_admin';
}

function can_manage_events(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}

function can_send_messages(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'super_admin']);
}
