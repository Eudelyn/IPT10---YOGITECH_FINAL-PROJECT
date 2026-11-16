<?php
declare(strict_types=1);

namespace Core;

/**
 * Tiny file cache used to avoid hitting external APIs on every request.
 */
final class Cache
{
    private static function path(string $key): string
    {
        return BASE_PATH . '/storage/cache/' . sha1($key) . '.cache';
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        $file = self::path($key);
        if (!is_file($file)) {
            return $default;
        }
        $payload = @unserialize((string) file_get_contents($file));
        if (!is_array($payload) || ($payload['expires'] !== 0 && $payload['expires'] < time())) {
            @unlink($file);
            return $default;
        }
        return $payload['value'];
    }

    /** @param mixed $value */
    public static function put(string $key, $value, int $ttlSeconds = 3600): void
    {
        $dir = dirname(self::path($key));
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $expires = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        @file_put_contents(self::path($key), serialize(['expires' => $expires, 'value' => $value]), LOCK_EX);
    }

    /** @return mixed */
    public static function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $cached = self::get($key, '__miss__');
        if ($cached !== '__miss__') {
            return $cached;
        }
        $value = $callback();
        if ($value !== null) {
            self::put($key, $value, $ttlSeconds);
        }
        return $value;
    }

    public static function forget(string $key): void { @unlink(self::path($key)); }
}
