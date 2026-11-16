<?php
declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Every middleware receives the request and the next layer of the pipeline.
 * It can short-circuit by returning its own Response, or call $next($request).
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response;
}
