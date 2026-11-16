<?php
declare(strict_types=1);

namespace Core;

/**
 * Session wrapper with "flash" data that survives exactly one redirect.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }
        session_name((string) config('app.session_name', 'yogitech_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
        // Age flash data: what was flashed last request becomes readable now.
        $_SESSION['_flash_now'] = $_SESSION['_flash_next'] ?? [];
        $_SESSION['_flash_next'] = [];
    }

    public static function get(string $key, $default = null) { return $_SESSION[$key] ?? $default; }
    public static function put(string $key, $value): void { $_SESSION[$key] = $value; }
    public static function has(string $key): bool { return isset($_SESSION[$key]); }
    public static function forget(string $key): void { unset($_SESSION[$key]); }

    public static function pull(string $key, $default = null)
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    public static function flash(string $key, $value): void { $_SESSION['_flash_next'][$key] = $value; }
    public static function getFlash(string $key, $default = null) { return $_SESSION['_flash_now'][$key] ?? $default; }

    /** Keep current flash data for one more request (used when redirecting twice). */
    public static function reflash(): void
    {
        $_SESSION['_flash_next'] = array_merge($_SESSION['_flash_now'] ?? [], $_SESSION['_flash_next'] ?? []);
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
