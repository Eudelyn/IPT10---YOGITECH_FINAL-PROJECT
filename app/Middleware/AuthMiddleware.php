<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;
use Core\Session;

/** Only signed-in users may pass. Guests are sent to /login and returned afterwards. */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'Sign in to continue.'], 401);
            }
            $intended = $request->method() === 'GET' ? $request->path() : parse_url((string) $request->referer(), PHP_URL_PATH);
            Session::put('url.intended', $intended ?: '/');
            Session::flash('info', 'Sign in to continue.');
            return Response::redirect('/login');
        }
        $request->setAttribute('user', Auth::user());
        return $next($request);
    }
}
