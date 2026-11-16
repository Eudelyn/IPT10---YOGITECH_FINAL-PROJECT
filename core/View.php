<?php
declare(strict_types=1);

namespace Core;

use RuntimeException;

/**
 * Renders plain-PHP templates from app/Views, optionally inside a layout.
 * Templates must escape output with e().
 */
final class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void { self::$shared[$key] = $value; }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, $data + ['content' => $content]);
    }

    public static function partial(string $__view, array $__data = []): string
    {
        $__file = BASE_PATH . '/app/Views/' . $__view . '.php';
        if (!is_file($__file)) {
            throw new RuntimeException("View [$__view] not found");
        }
        extract(self::$shared + $__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
