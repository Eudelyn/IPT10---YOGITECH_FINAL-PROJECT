<?php
declare(strict_types=1);

namespace Core;

/**
 * An HTTP response: body, status code and headers.
 */
final class Response
{
    private string $content;
    private int $status;
    private array $headers;

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers + ['Content-Type' => 'text/html; charset=utf-8'];
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status);
    }

    /** @param mixed $data */
    public static function json($data, int $status = 200): self
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return new self((string) $json, $status, ['Content-Type' => 'application/json; charset=utf-8']);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        $location = preg_match('#^https?://#i', $to) ? $to : url($to);
        return new self('', $status, ['Location' => $location]);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int { return $this->status; }
    public function content(): string { return $this->content; }
    public function headers(): array { return $this->headers; }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
            header('X-Powered-By: Yogi/' . YOGI_VERSION);
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
        echo $this->content;
    }
}
