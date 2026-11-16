<?php
declare(strict_types=1);

namespace Core;

/**
 * Minimal .env loader. Reads KEY=VALUE pairs into $_ENV / getenv().
 */
final class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            $real = getenv($key);
            if ($real !== false) {
                $_ENV[$key] = $real; // real environment variables (Docker, server) win over .env
                continue;
            }
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        switch (strtolower((string) $value)) {
            case 'true':  return true;
            case 'false': return false;
            case 'null':  return null;
            case '':      return $default;
        }
        return $value;
    }
}
