<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Logger;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;

/** Logs method, path, status and timing of each request. */
final class RequestLogMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response
    {
        $start = microtime(true);
        $response = $next($request);
        if (config('app.log_requests')) {
            Logger::info(sprintf(
                '%s %s -> %d (%.1f ms)',
                $request->method(),
                $request->path(),
                $response->status(),
                (microtime(true) - $start) * 1000
            ), ['ip' => $request->ip(), 'user' => Auth::id()]);
        }
        return $response;
    }
}
