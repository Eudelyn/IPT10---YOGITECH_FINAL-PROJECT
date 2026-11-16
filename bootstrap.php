<?php
/**
 * Yogi Framework bootstrap.
 * Registers the autoloader, loads helpers and the .env file.
 */
declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('YOGI_VERSION', '1.1.0');
// Never flashed back into forms or kept in the session after a failed submit.
define('SENSITIVE_FIELDS', ['_token', 'password', 'password_confirmation', 'current_password', 'card_number', 'card_expiry', 'card_cvc', 'token']);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Core\\'     => BASE_PATH . '/core/',
        'App\\'      => BASE_PATH . '/app/',
        'Database\\' => BASE_PATH . '/database/',
        'Tests\\'    => BASE_PATH . '/tests/',
    ];
    foreach ($prefixes as $prefix => $dir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require BASE_PATH . '/core/helpers.php';
require BASE_PATH . '/app/helpers.php';

Core\Env::load(BASE_PATH . '/.env');
date_default_timezone_set((string) config('app.timezone', 'Asia/Manila'));
