<?php
/**
 * Used by docker/entrypoint.sh.
 *   php db-check.php wait            -> waits until MySQL/MariaDB accepts the login (max ~2 min)
 *   php db-check.php has-migrations  -> exit 0 if the database was already set up, 1 if not
 * Connects with PDO, exactly like the app, so it works with any MySQL/MariaDB login method.
 */
$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = (string) getenv('DB_PASSWORD');
$name = getenv('DB_DATABASE') ?: 'yogitech';
$mode = $argv[1] ?? 'wait';

$connect = function () use ($host, $port, $user, $pass) {
    return new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
};

if ($mode === 'wait') {
    for ($i = 1; $i <= 60; $i++) {
        try {
            $connect();
            echo "Database is ready.\n";
            exit(0);
        } catch (Throwable $e) {
            if ($i % 5 === 1) {
                echo "Still waiting for the database ($i/60): " . $e->getMessage() . "\n";
            }
            sleep(2);
        }
    }
    fwrite(STDERR, "The database did not accept the connection. Check DB_ settings in docker-compose.yml.\n");
    exit(1);
}

if ($mode === 'has-migrations') {
    try {
        $pdo = $connect();
        $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $st->execute([$name, 'migrations']);
        exit((int) $st->fetchColumn() > 0 ? 0 : 1);
    } catch (Throwable $e) {
        exit(1);
    }
}
exit(2);
