<?php
/**
 * Log an action to audit_logs.
 * $actor_type: 'system' for the Matching Engine, otherwise auto-detected from session role.
 */
function audit_log(string $action, string $target = '', string $detail = '', string $actor_type = ''): void {
    global $pdo;
    if (!isset($pdo)) return;
    if (!$actor_type) {
        $role = $_SESSION['role'] ?? '';
        $actor_type = in_array($role, ['super_admin','admin','reports_admin','alumni']) ? $role : 'admin';
    }
    try {
        $pdo->prepare("INSERT INTO audit_logs (user_id, user_email, actor_type, action, target, detail, ip)
                       VALUES (?,?,?,?,?,?,?)")
            ->execute([
                $_SESSION['user_id'] ?? null,
                $_SESSION['email']   ?? null,
                $actor_type,
                $action, $target, $detail,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
    } catch (Throwable $e) {}
}
