<?php
declare(strict_types=1);

namespace App\Middleware;

use Core\Cache;
use Core\Exceptions\HttpException;
use Core\Middleware\MiddlewareInterface;
use Core\Request;
use Core\Response;

/**
 * Rate limiting per IP.
 *   'throttle:5,1'          max 5 requests per minute for this path
 *   'throttle:20,1,track'   max 20 per minute shared by every route using the bucket "track"
 */
final class ThrottleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$args): Response
    {
        $max = (int) ($args[0] ?? 60);
        $minutes = (int) ($args[1] ?? 1);
        $key = 'throttle:' . $request->ip() . ':' . ($args[2] ?? $request->path());
        $bucket = Cache::get($key, ['hits' => 0, 'reset' => time() + $minutes * 60]);
        if ($bucket['reset'] < time()) {
            $bucket = ['hits' => 0, 'reset' => time() + $minutes * 60];
        }
        $bucket['hits']++;
        Cache::put($key, $bucket, max(1, $bucket['reset'] - time()));
        if ($bucket['hits'] > $max) {
            throw new HttpException(429, 'Too many attempts. Try again in ' . max(1, (int) ceil(($bucket['reset'] - time()) / 60)) . ' minute(s).');
        }
        return $next($request)->withHeader('X-RateLimit-Remaining', (string) max(0, $max - $bucket['hits']));
    }
}
