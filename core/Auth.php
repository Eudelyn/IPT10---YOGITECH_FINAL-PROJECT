<?php
declare(strict_types=1);

namespace Core;

/**
 * Session-based authentication using password_hash()/password_verify().
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::first('SELECT * FROM users WHERE email = ? LIMIT 1', [strtolower($email)]);
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
        }
        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate(); // prevent session fixation
        Session::put('auth_id', (int) $user['id']);
        Session::put('auth_version', (int) ($user['auth_version'] ?? 1));
        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::forget('auth_id');
        Session::forget('auth_version');
        Session::forget('_csrf'); // new CSRF token for the next person on this browser
        Session::regenerate();
        self::$user = null;
        self::$loaded = true;
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('auth_id');
            self::$user = $id ? Database::first('SELECT * FROM users WHERE id = ?', [$id]) : null;
            // A password change or reset bumps auth_version, which signs out every other session.
            if (self::$user !== null && (int) (self::$user['auth_version'] ?? 1) !== (int) Session::get('auth_version', 1)) {
                Session::forget('auth_id');
                Session::forget('auth_version');
                self::$user = null;
            }
        }
        return self::$user;
    }

    /** Sets a new password and signs out all other sessions of that user. */
    public static function changePassword(int $userId, string $newPassword, bool $keepCurrentSession): void
    {
        Database::query(
            'UPDATE users SET password = ?, auth_version = auth_version + 1, updated_at = ? WHERE id = ?',
            [password_hash($newPassword, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), $userId]
        );
        if ($keepCurrentSession && self::id() === $userId) {
            $version = (int) Database::scalar('SELECT auth_version FROM users WHERE id = ?', [$userId]);
            Session::regenerate();
            Session::put('auth_version', $version);
            self::refresh();
        }
    }

    public static function refresh(): void { self::$loaded = false; }
    public static function id(): ?int { $u = self::user(); return $u ? (int) $u['id'] : null; }
    public static function check(): bool { return self::user() !== null; }
    public static function is(string $role): bool { $u = self::user(); return $u !== null && $u['role'] === $role; }
}
