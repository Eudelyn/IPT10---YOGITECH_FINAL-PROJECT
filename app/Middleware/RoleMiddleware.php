<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Auth;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;
use Core\Session;

/** Usage: 'role:seller' or 'role:buyer'. Must run after 'auth'. */
final class RoleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$roles): Response
    {
        $user = Auth::user();
        if ($user === null || !in_array($user['role'], $roles, true)) {
            if ($request->wantsJson()) {
                return Response::json(['message' => 'This action is only for ' . implode('/', $roles) . ' accounts.'], 403);
            }
            Session::flash('error', $user && $user['role'] === 'seller'
                ? 'Seller accounts manage the store. Use a buyer account to shop.'
                : 'That page is for seller accounts.');
            return Response::redirect($user && $user['role'] === 'seller' ? '/seller' : '/dashboard');
        }
        return $next($request);
    }
}
