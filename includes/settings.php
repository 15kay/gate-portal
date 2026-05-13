<?php
/**
 * Returns a portal setting value, with a fallback default.
 * Requires $pdo to already be available.
 */
function setting(string $key, string $default = ''): string {
    global $pdo;
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $s = $pdo->prepare("SELECT setting_value FROM portal_settings WHERE setting_key=?");
            $s->execute([$key]);
            $r = $s->fetchColumn();
            $cache[$key] = ($r !== false) ? $r : $default;
        } catch (Throwable $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}
