<?php
declare(strict_types=1);

namespace App\Services;

use Core\Database;

/**
 * Forgot-password tokens.
 * - The raw token only exists in the emailed link; the database stores its SHA-256 hash,
 *   so a leaked database can't be used to reset passwords.
 * - Tokens expire after EXPIRES_MINUTES, work once, and asking again replaces older ones.
 */
final class PasswordResetService
{
    public const EXPIRES_MINUTES = 60;
    private const MAX_REQUESTS_PER_15_MIN = 3;

    /** Returns the raw token, or null when this user asked too many times recently. */
    public static function createToken(int $userId, string $ip): ?string
    {
        $recent = (int) Database::scalar(
            'SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > ?',
            [$userId, date('Y-m-d H:i:s', time() - 15 * 60)]
        );
        if ($recent >= self::MAX_REQUESTS_PER_15_MIN) {
            return null;
        }
        $token = bin2hex(random_bytes(32)); // 64 hex characters
        $now = date('Y-m-d H:i:s');
        // Older unused links stop working once a new one is sent.
        Database::query('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL', [$now, $userId]);
        Database::insert('password_resets', [
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + self::EXPIRES_MINUTES * 60),
            'ip'         => substr($ip, 0, 45),
            'created_at' => $now,
        ]);
        return $token;
    }

    /** The reset row joined with its user, if the token is valid, unused and not expired. */
    public static function findValid(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::first(
            'SELECT r.id AS reset_id, u.* FROM password_resets r JOIN users u ON u.id = r.user_id
              WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > ? LIMIT 1',
            [hash('sha256', $token), date('Y-m-d H:i:s')]
        );
    }

    /**
     * Uses the token once. Returns false if it was already used by another request
     * (e.g. the form was submitted twice).
     */
    public static function consume(int $resetId): bool
    {
        return Database::query(
            'UPDATE password_resets SET used_at = ? WHERE id = ? AND used_at IS NULL',
            [date('Y-m-d H:i:s'), $resetId]
        )->rowCount() === 1;
    }
}
