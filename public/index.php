<?php
/**
 * Front controller — every request enters here.
 */
declare(strict_types=1);

// Let PHP's built-in server serve real files (css, js, images) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file)) {
        return false;
    }
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require dirname(__DIR__) . '/bootstrap.php';

(new Core\App())->run();
