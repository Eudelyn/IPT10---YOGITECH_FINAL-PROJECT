<?php
declare(strict_types=1);

namespace Core;

use Core\Middleware\MiddlewareInterface;
use InvalidArgumentException;

/**
 * Runs a request through a stack of middleware ("onion" model) before the controller.
 * Middleware names may carry arguments: "role:seller" or "throttle:60,1".
 */
final class Pipeline
{
    private array $middleware;
    private array $aliases;

    public function __construct(array $middleware, array $aliases = [])
    {
        $this->middleware = $middleware;
        $this->aliases = $aliases;
    }

    public function process(Request $request, callable $core): Response
    {
        $next = $core;
        foreach (array_reverse($this->middleware) as $definition) {
            [$name, $argString] = array_pad(explode(':', (string) $definition, 2), 2, '');
            $class = $this->aliases[$name] ?? $name;
            $instance = new $class();
            if (!$instance instanceof MiddlewareInterface) {
                throw new InvalidArgumentException("[$class] must implement MiddlewareInterface");
            }
            $args = $argString === '' ? [] : explode(',', $argString);
            $next = static function (Request $req) use ($instance, $next, $args): Response {
                return $instance->handle($req, $next, ...$args);
            };
        }
        return $next($request);
    }
}
