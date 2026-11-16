<?php
declare(strict_types=1);

namespace Core;

use Core\Exceptions\HttpResponseException;
use Throwable;

/**
 * The application kernel: boots the session, loads routes, dispatches the request.
 */
final class App
{
    private Router $router;

    /** @var callable[] work to run after the response is sent */
    private static array $deferred = [];

    public function __construct()
    {
        $this->router = new Router(
            (array) config('middleware.aliases', []),
            (array) config('middleware.global', [])
        );
        $router = $this->router;
        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/api.php';
    }

    public function router(): Router { return $this->router; }

    public static function defer(callable $task): void
    {
        if (PHP_SAPI === 'cli') {
            $task(); // CLI commands and tests: just run it now
            return;
        }
        self::$deferred[] = $task;
    }

    /** Sends the response, closes the connection, then runs deferred tasks. */
    private static function finish(Response $response): void
    {
        if (self::$deferred === []) {
            $response->send();
            return;
        }
        ignore_user_abort(true);
        session_write_close(); // release the session lock so the next page loads right away
        $response->withHeader('Content-Length', (string) strlen($response->content()))
                 ->withHeader('Connection', 'close')
                 ->send();
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }
        foreach (self::$deferred as $task) {
            try {
                $task();
            } catch (Throwable $e) {
                Logger::error('Deferred task failed: ' . $e->getMessage());
            }
        }
        self::$deferred = [];
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (HttpResponseException $e) {
            return $e->response();
        } catch (Throwable $e) {
            return ErrorHandler::render($e, $request);
        }
    }

    public function run(): void
    {
        ErrorHandler::register();
        $request = null;
        try {
            Session::start();
            $request = Request::capture();
            $response = $this->handle($request);
        } catch (Throwable $e) {
            $response = ErrorHandler::render($e, $request);
        }
        self::finish($response);
    }
}
