<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;

/** Signed-in users don't need the sign-in / sign-up pages. */
final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response
    {
        if (Auth::check()) {
            return Response::redirect(Auth::is('seller') ? '/seller' : '/dashboard');
        }
        return $next($request);
    }
}
