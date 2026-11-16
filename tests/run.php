<?php
/**
 * Test runner: php yogi test   (or: php tests/run.php)
 */
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require dirname(__DIR__) . '/bootstrap.php';
}
require_once BASE_PATH . '/tests/TestCase.php';

$classes = [];
foreach (glob(BASE_PATH . '/tests/*Test.php') as $file) {
    require_once $file;
    $classes[] = 'Tests\\' . basename($file, '.php');
}

$passed = $failed = $assertions = 0;
foreach ($classes as $class) {
    $tc = new $class();
    echo "\n" . substr(strrchr($class, '\\'), 1) . "\n";
    foreach ($tc->run() as $method => $error) {
        if ($error === null) {
            $passed++;
            echo "  \033[32m✓\033[0m $method\n";
        } else {
            $failed++;
            echo "  \033[31m✗ $method\033[0m\n    $error\n";
        }
    }
    $assertions += $tc->assertions;
}
echo "\n" . ($failed ? "\033[31m" : "\033[32m") . "$passed passed, $failed failed ($assertions assertions)\033[0m\n";
exit($failed ? 1 : 0);
