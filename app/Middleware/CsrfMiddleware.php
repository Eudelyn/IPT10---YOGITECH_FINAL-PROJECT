<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Csrf;
use Core\Exceptions\HttpException;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;

/** Rejects state-changing requests that don't carry the session CSRF token. */
final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
            if (!Csrf::verify(is_string($token) ? $token : null)) {
                throw new HttpException(419);
            }
        }
        return $next($request);
    }
}
