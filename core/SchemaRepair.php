<?php
declare(strict_types=1);

namespace Core;

/**
 * Creates missing tables and columns from database/migrations without touching data.
 * Fixes a phpMyAdmin import that stopped halfway, or a database older than the code.
 * Used by "php yogi db:repair" and by the Repair button on the error page (local + APP_DEBUG only).
 */
final class SchemaRepair
{
    /** @return string[] what was done, one line per change */
    public static function run(): array
    {
        $log = [];
        $out = static function (string $line) use (&$log): void { $log[] = trim($line); };

        $db = config('database.database');
        Database::connection(false)->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = Database::connection();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(190) NOT NULL UNIQUE, ran_at DATETIME NOT NULL)');
        $tableExists = static fn(string $t): bool => (bool) Database::scalar(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$db, $t]);
        $columnExists = static fn(string $t, string $c): bool => (bool) Database::scalar(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$db, $t, $c]);

        $fixed = 0;
        foreach (self::migrationFiles() as $file) {
            $changed = false;
            $sql = (string) file_get_contents($file);
            foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $statement) {
                $statement = trim(preg_replace('/^(\s*--[^\n]*\n)+/', '', $statement));
                if ($statement === '') {
                    continue;
                }
                if (preg_match('/^CREATE TABLE IF NOT EXISTS\s+`?(\w+)`?/i', $statement, $m)) {
                    if (!$tableExists($m[1])) {
                        $pdo->exec($statement);
                        $out("  created table  {$m[1]}");
                        $changed = true;
                    }
                } elseif (preg_match('/^ALTER TABLE\s+`?(\w+)`?\s+(.*)$/is', $statement, $m)) {
                    foreach (preg_split('/,\s*(?=ADD\s+COLUMN)/i', $m[2]) as $part) {
                        if (preg_match('/^ADD\s+COLUMN\s+`?(\w+)`?/i', trim($part), $c) && !$columnExists($m[1], $c[1])) {
                            $pdo->exec("ALTER TABLE `{$m[1]}` " . trim($part));
                            $out("  added column   {$m[1]}.{$c[1]}");
                            $changed = true;
                        }
                    }
                } elseif (preg_match('/^INSERT IGNORE/i', $statement)) {
                    $pdo->exec($statement); // default rows (categories, vouchers); duplicates are skipped
                } elseif ($changed) {
                    $pdo->exec($statement); // UPDATEs that go with a table/column just created
                }
            }
            $name = basename($file);
            if (!Database::scalar('SELECT COUNT(*) FROM migrations WHERE migration = ?', [$name])) {
                Database::insert('migrations', ['migration' => $name, 'ran_at' => date('Y-m-d H:i:s')]);
            }
            $fixed += $changed ? 1 : 0;
        }
        $out($fixed ? 'Repaired. Your existing data was kept.' : 'Database is complete. Nothing to repair.');
        return $log;
    }

    public static function migrationFiles(): array
    {
        $files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
        sort($files);
        return $files;
    }

    /** The web Repair button only works on the developer's own computer while APP_DEBUG=true. */
    public static function allowedFromWeb(?Request $request): bool
    {
        return $request !== null && (bool) config('app.debug')
            && in_array($request->ip(), ['127.0.0.1', '::1'], true);
    }
}
