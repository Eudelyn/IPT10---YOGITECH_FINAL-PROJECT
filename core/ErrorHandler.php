<?php
declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpException;
use Throwable;

/**
 * Converts uncaught exceptions to error pages (or JSON for API calls).
 * - Every 500 gets a short reference code, shown to the user and written to the log.
 * - Common setup problems (MySQL off, missing tables) get a plain-language fix.
 * - Expired or rate-limited form submits go back to the form with a message.
 * - The stack trace is shown only when APP_DEBUG=true.
 */
final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', config('app.debug') ? '1' : '0');
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public static function render(Throwable $e, ?Request $request = null): Response
    {
        $status = $e instanceof HttpException ? $e->statusCode() : 500;
        $debug = (bool) config('app.debug');
        $message = $e instanceof HttpException ? $e->getMessage() : HttpException::defaultMessage(500);
        [$dbMessage, $hint] = self::databaseHelp($e);
        if ($dbMessage !== null) {
            $message = $dbMessage;
        }

        $ref = null;
        if ($status >= 500) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            Logger::error(get_class($e) . ': ' . $e->getMessage(), ['ref' => $ref, 'file' => $e->getFile(), 'line' => $e->getLine(), 'path' => $request ? $request->path() : null]);
        }

        if ($request && $request->wantsJson()) {
            $payload = ['error' => true, 'status' => $status, 'message' => $message];
            if ($ref) {
                $payload['ref'] = $ref;
            }
            if ($debug && $status >= 500) {
                $payload['exception'] = get_class($e) . ': ' . $e->getMessage();
            }
            return Response::json($payload, $status);
        }

        // A form that expired (419) or was sent too often (429): back to the form with a message,
        // instead of a dead-end error page. The user's typing is kept where possible.
        if ($request && $request->method() !== 'GET' && in_array($status, [419, 429], true) && session_status() === PHP_SESSION_ACTIVE) {
            Session::flash('error', $status === 419
                ? 'Your session expired, so the form wasn\'t sent. Please try again.'
                : 'Too many attempts. Wait a minute, then try again.');
            Session::flash('old', array_diff_key($request->all(), array_flip(SENSITIVE_FIELDS)));
            return back();
        }

        try {
            $html = View::render('errors/error', [
                'status' => $status, 'message' => $message, 'hint' => $hint, 'ref' => $ref,
                'exception' => ($debug && $status >= 500) ? $e : null,
                'canRepair' => $dbMessage !== null && in_array((int) ($e->errorInfo[1] ?? 0), [1146, 1054], true)
                    && SchemaRepair::allowedFromWeb($request),
            ], null);
        } catch (Throwable $inner) {
            $html = '<h1>' . $status . '</h1><p>' . htmlspecialchars($message) . '</p>'
                . ($hint ? '<p>' . htmlspecialchars($hint) . '</p>' : '')
                . ($ref ? '<p>Error code: ' . $ref . '</p>' : '');
        }
        return Response::html($html, $status);
    }

    /** @return array{0: ?string, 1: ?string} message and how to fix it, for database setup problems */
    private static function databaseHelp(Throwable $e): array
    {
        if (!$e instanceof \PDOException) {
            return [null, null];
        }
        $code = (int) ($e->errorInfo[1] ?? $e->getCode());
        $db = (string) config('database.database');
        $repair = 'Fix: in the project folder run "php yogi db:repair" (keeps your data). '
            . 'Or in phpMyAdmin drop the "' . $db . '" database and import database/yogitech.sql again.';
        switch ($code) {
            case 1049:
                return ['The database "' . $db . '" doesn\'t exist yet.',
                    'Run "php yogi setup" in the project folder, or import database/yogitech.sql in phpMyAdmin.'];
            case 2002:
                return ['Can\'t connect to MySQL.', 'Start MySQL in the XAMPP Control Panel, then reload this page.'];
            case 1045:
                return ['MySQL rejected the username or password.', 'Check DB_USERNAME and DB_PASSWORD in .env (XAMPP default: root, empty password).'];
            case 1146:
                preg_match("/Table '[^.]+\\.([^']+)'/", $e->getMessage(), $m);
                return ['The database is incomplete' . (isset($m[1]) ? ': the "' . $m[1] . '" table is missing.' : '.'),
                    'This usually means an import stopped halfway. ' . $repair];
            case 1054:
                preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $m);
                return ['The database is out of date' . (isset($m[1]) ? ': the "' . $m[1] . '" column is missing.' : '.'),
                    'The code was updated but the database wasn\'t. ' . $repair];
        }
        return [null, null];
    }
}
