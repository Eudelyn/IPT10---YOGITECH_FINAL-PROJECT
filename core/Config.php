<?php
declare(strict_types=1);

namespace Core;

/**
 * Dot-notation access to the files in /config. config('app.name')
 */
final class Config
{
    private static array $items = [];

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        if (!array_key_exists($file, self::$items)) {
            $path = BASE_PATH . "/config/{$file}.php";
            self::$items[$file] = is_file($path) ? require $path : [];
        }
        $value = self::$items[$file];
        foreach ($parts as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /** @param mixed $value */
    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        self::get($file);
        $ref = &self::$items[$file];
        foreach ($parts as $segment) {
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
        $ref = $value;
    }
}
