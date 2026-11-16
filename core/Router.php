<?php
declare(strict_types=1);

namespace Core;

use Closure;
use Core\Exceptions\HttpException;
use RuntimeException;

/**
 * Maps HTTP method + URL pattern to a controller action.
 *   $router->get('/products/{id:\d+}', [ProductController::class, 'show'], ['auth']);
 * Supports groups with a shared prefix and middleware.
 */
final class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private array $aliases;
    private array $globalMiddleware;

    public function __construct(array $aliases = [], array $globalMiddleware = [])
    {
        $this->aliases = $aliases;
        $this->globalMiddleware = $globalMiddleware;
    }

    public function get(string $path, $handler, array $middleware = []): self { return $this->add('GET', $path, $handler, $middleware); }
    public function post(string $path, $handler, array $middleware = []): self { return $this->add('POST', $path, $handler, $middleware); }
    public function put(string $path, $handler, array $middleware = []): self { return $this->add('PUT', $path, $handler, $middleware); }
    public function delete(string $path, $handler, array $middleware = []): self { return $this->add('DELETE', $path, $handler, $middleware); }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(string $method, string $path, $handler, array $middleware): self
    {
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'] ?? '', '/');
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }
        $full = '/' . trim((string) preg_replace('#/+#', '/', $prefix . '/' . trim($path, '/')), '/');

        $this->routes[] = [
            'method'     => $method,
            'path'       => $full,
            'regex'      => self::compile($full),
            'handler'    => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];
        return $this;
    }

    /** Turns "/orders/{id:\d+}" into "#^/orders/(?P<id>\d+)$#". */
    public static function compile(string $path): string
    {
        $regex = preg_replace_callback(
            // Allows one level of braces inside a constraint, e.g. {token:[a-f0-9]{64}}
            '#\{(\w+)(?::((?:[^{}]|\{[^{}]*\})+))?\}#',
            static fn(array $m): string => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $path
        );
        return '#^' . $regex . '$#';
    }

    /** @return array|null route data with params, or ['method_not_allowed' => true] */
    public function match(string $method, string $path): ?array
    {
        $path = '/' . trim($path, '/');
        $methodMismatch = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                $methodMismatch = true;
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return $route + ['params' => $params];
        }
        return $methodMismatch ? ['method_not_allowed' => true] : null;
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->match($request->method(), $request->path());
        if ($route === null) {
            throw new HttpException(404);
        }
        if (!empty($route['method_not_allowed'])) {
            throw new HttpException(405);
        }
        $request->setParams($route['params']);

        $pipeline = new Pipeline(array_merge($this->globalMiddleware, $route['middleware']), $this->aliases);
        return $pipeline->process($request, function (Request $req) use ($route): Response {
            return $this->toResponse($this->callHandler($route['handler'], $req));
        });
    }

    /** @return mixed */
    private function callHandler($handler, Request $request)
    {
        $args = array_values($request->params());
        if ($handler instanceof Closure) {
            return $handler($request, ...$args);
        }
        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            $controller = new $class();
            if (!method_exists($controller, $action)) {
                throw new RuntimeException("Action [$class@$action] not found");
            }
            return $controller->$action($request, ...$args);
        }
        throw new RuntimeException('Invalid route handler');
    }

    /** @param mixed $result */
    private function toResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        return Response::html((string) $result);
    }

    public function routes(): array { return $this->routes; }
}
