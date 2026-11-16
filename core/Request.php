<?php
declare(strict_types=1);

namespace Core;

/**
 * Wraps the incoming HTTP request (method, path, query, body, files, headers).
 */
final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private array $params = [];
    private array $attributes = [];

    public function __construct(string $method, string $path, array $query = [], array $body = [], array $files = [], array $server = [])
    {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->server = $server;
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        // HTML forms can only send GET/POST, so allow method spoofing.
        if ($method === 'POST' && isset($_POST['_method']) && is_string($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = app_base();
        if ($base !== '' && stripos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        if (stripos($uri, '/index.php') === 0) {
            $uri = substr($uri, 10);
        }

        $body = $_POST;
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        }

        return new self($method, $uri, $_GET, $body, $_FILES, $_SERVER);
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function isPost(): bool { return $this->method === 'POST'; }

    /**
     * Input is scalar-only: "q[]=x" style arrays are treated as missing, so they can't
     * reach string functions and crash the page. (No Yogitech form uses array fields.)
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return self::scalar($this->body[$key] ?? $this->query[$key] ?? null, $default);
    }

    /** @return mixed */
    public function query(string $key, $default = null)
    {
        return self::scalar($this->query[$key] ?? null, $default);
    }

    public function all(): array { return array_merge($this->query, $this->body); }
    public function queryAll(): array { return array_filter($this->query, static fn($v) => !is_array($v)); }

    public function only(array $keys): array
    {
        $all = $this->all();
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::scalar($all[$k] ?? null, null);
        }
        return $out;
    }

    /** @param mixed $value @param mixed $default @return mixed */
    private static function scalar($value, $default)
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return $default;
        }
        return is_string($value) ? trim($value) : $value;
    }

    public function has(string $key): bool
    {
        $v = $this->input($key);
        return $v !== null && $v !== '';
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        return ($f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $f : null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function wantsJson(): bool
    {
        return strpos($this->path, '/api/') === 0
            || stripos((string) $this->header('Accept'), 'application/json') !== false
            || $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function ip(): string { return $this->server['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public function referer(): ?string { return $this->server['HTTP_REFERER'] ?? null; }

    public function setParams(array $params): void { $this->params = $params; }
    public function param(string $key, $default = null) { return $this->params[$key] ?? $default; }
    public function params(): array { return $this->params; }

    /** Middleware can attach data (e.g. the authenticated user). */
    public function setAttribute(string $key, $value): void { $this->attributes[$key] = $value; }
    public function attribute(string $key, $default = null) { return $this->attributes[$key] ?? $default; }
}
