<?php
declare(strict_types=1);

namespace Core;

/**
 * cURL-based HTTP client for calling external APIs.
 * Returns ['ok' => bool, 'status' => int, 'json' => ?array, 'body' => string, 'error' => ?string]
 * Retries once on network errors / HTTP 5xx / 429 with a short back-off.
 */
final class HttpClient
{
    private array $headers = ['Accept' => 'application/json', 'User-Agent' => 'Yogitech/1.0 (+Yogi Framework)'];
    private int $timeout;
    private int $retries;

    public function __construct(int $timeout = 10, int $retries = 1)
    {
        $this->timeout = $timeout;
        $this->retries = $retries;
    }

    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = array_merge($this->headers, $headers);
        return $clone;
    }

    /** Adds "Authorization: Bearer <token>" for APIs that need a key. */
    public function withToken(string $token): self
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $token]);
    }

    public function get(string $url, array $query = []): array
    {
        if ($query) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $this->send('GET', $url);
    }

    public function post(string $url, array $payload = []): array
    {
        return $this->send('POST', $url, json_encode($payload));
    }

    /** POST as a classic HTML form (application/x-www-form-urlencoded), e.g. OAuth token endpoints. */
    public function postForm(string $url, array $fields): array
    {
        return $this->withHeaders(['Content-Type' => 'application/x-www-form-urlencoded'])
            ->send('POST', $url, http_build_query($fields));
    }

    private function send(string $method, string $url, ?string $body = null): array
    {
        $attempt = 0;
        do {
            $result = function_exists('curl_init') ? $this->curl($method, $url, $body) : $this->stream($method, $url, $body);
            $retryable = $result['status'] === 0 || $result['status'] === 429 || $result['status'] >= 500;
            if (!$retryable || $attempt >= $this->retries) {
                break;
            }
            usleep(300000 * ($attempt + 1));
            $attempt++;
        } while (true);

        if (!$result['ok']) {
            Logger::warning('External API call failed', ['url' => $url, 'status' => $result['status'], 'error' => $result['error']]);
        }
        return $result;
    }

    private function curl(string $method, string $url, ?string $body): array
    {
        $ch = curl_init($url);
        $headers = [];
        foreach ($this->headers + ($body !== null ? ['Content-Type' => 'application/json'] : []) as $k => $v) {
            $headers[] = "$k: $v";
        }
        $verify = (bool) config('services.verify_ssl', true);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);
        return $this->result($status, $raw === false ? '' : (string) $raw, $error);
    }

    private function stream(string $method, string $url, ?string $body): array
    {
        $headerLines = [];
        foreach ($this->headers as $k => $v) {
            $headerLines[] = "$k: $v";
        }
        $ctx = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $headerLines),
            'content' => $body ?? '', 'timeout' => $this->timeout, 'ignore_errors' => true,
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $status = (int) $m[1];
            }
        }
        return $this->result($status, $raw === false ? '' : $raw, $raw === false ? 'Connection failed' : null);
    }

    private function result(int $status, string $body, ?string $error): array
    {
        $json = json_decode($body, true);
        return [
            'ok'     => $error === null && $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => $body,
            'json'   => is_array($json) ? $json : null,
            'error'  => $error ?? ($status >= 400 ? "HTTP $status" : null),
        ];
    }
}
