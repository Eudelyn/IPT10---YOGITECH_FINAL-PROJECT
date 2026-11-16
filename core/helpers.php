<?php
declare(strict_types=1);

/**
 * Global helper functions for the Yogi framework.
 */

use Core\Auth;
use Core\Config;
use Core\Csrf;
use Core\Env;
use Core\Exceptions\HttpException;
use Core\Response;
use Core\Session;

function env(string $key, $default = null) { return Env::get($key, $default); }
function config(string $key, $default = null) { return Config::get($key, $default); }

/**
 * Base path the app is served under: '' at the web root, '/yogitech' in an XAMPP subfolder.
 * Set APP_BASE in .env to force it. Matching is case-insensitive and URL-decoded, so folders
 * like "Yogitech" or "yogitech (1)" work too.
 */
function app_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $override = env('APP_BASE');
    if ($override !== null) {
        return $base = rtrim('/' . trim((string) $override, '/'), '/');
    }
    if (!isset($_SERVER['REQUEST_URI'])) {
        return $base = '';
    }
    $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/.');
    $candidates = [$dir];
    if (substr($dir, -7) === '/public') {
        $candidates[] = substr($dir, 0, -7); // root .htaccess forwards /yogitech/* to /yogitech/public/*
    }
    foreach ($candidates as $c) {
        if ($c === '' || stripos(rtrim($uri, '/') . '/', $c . '/') === 0) {
            return $base = $c;
        }
    }
    return $base = '';
}

function url(string $path = '/', array $query = []): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $prefix = implode('/', array_map('rawurlencode', explode('/', app_base())));
    $u = $prefix . '/' . ltrim($path, '/');
    return $query ? $u . '?' . http_build_query($query) : $u;
}

function asset(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

/** Product images can be remote (API) or uploaded files. */
function img_url(?string $path): string
{
    if (!$path) {
        return url('assets/img/placeholder.svg');
    }
    return preg_match('#^https?://#i', $path) ? $path : url($path);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string { return Csrf::token(); }
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">'; }

function old(string $key, $default = '')
{
    $old = Session::getFlash('old', []);
    return $old[$key] ?? $default;
}

function errors(?string $key = null)
{
    $errors = Session::getFlash('errors', []);
    return $key === null ? $errors : ($errors[$key] ?? null);
}

function flash(string $key, $value = null)
{
    if ($value === null) {
        return Session::getFlash($key);
    }
    Session::flash($key, $value);
    return null;
}

function auth(): ?array { return Auth::user(); }

function redirect(string $to): Response { return Response::redirect($to); }

function back(string $fallback = '/'): Response
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && $host && parse_url($ref, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST)) {
        return Response::redirect($ref);
    }
    return Response::redirect($fallback);
}

function abort(int $code, string $message = ''): void { throw new HttpException($code, $message); }

/** Philippine peso formatting: ₱12,345.00 */
function peso($amount, bool $decimals = true): string
{
    return '₱' . number_format((float) $amount, $decimals ? 2 : 0);
}

function str_limit(string $text, int $limit = 100): string
{
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)) . '…' : $text;
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

/** "is-active" class for nav links. */
function active(string $prefix): string
{
    $path = '/' . trim((string) substr(rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), strlen(app_base())), '/');
    if ($prefix === '/') {
        return $path === '/' ? 'is-active' : '';
    }
    return strpos($path, $prefix) === 0 ? 'is-active' : '';
}

function stars(float $rating): string
{
    $full = (int) round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

/** Masks a name for public views: "Juan Dela Cruz" -> "J*** D*** C***" */
function mask_name(string $name): string
{
    return implode(' ', array_map(static fn($p) => mb_substr($p, 0, 1) . '***', preg_split('/\s+/', trim($name)) ?: []));
}

/**
 * Full URL for links in emails. Uses APP_URL when set. Otherwise the current host is
 * used only if it is a trusted local host (APP_TRUSTED_HOSTS), so a forged "Host:"
 * header can't make emails link to someone else's site.
 */
function absolute_url(string $path = '/'): string
{
    $appUrl = (string) env('APP_URL', '');
    if ($appUrl !== '') {
        return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
    }
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $trusted = array_filter(array_map('trim', explode(',', strtolower((string) env('APP_TRUSTED_HOSTS', 'localhost,127.0.0.1')))));
    $hostOnly = preg_replace('/:\d+$/', '', $host);
    if ($host !== '' && in_array($hostOnly, $trusted, true) && preg_match('/^[a-z0-9.\-]+(:\d+)?$/', $host)) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $host . url($path);
    }
    return 'http://127.0.0.1:8000/' . ltrim($path, '/');
}

/** Escapes % and _ so user text is matched literally inside LIKE '%...%'. */
function like_escape(string $value): string
{
    return addcslashes($value, '\\%_');
}

/**
 * Runs work after the response has been sent to the browser (e.g. sending emails),
 * so a slow SMTP server doesn't make checkout feel slow.
 */
function after_response(callable $task): void
{
    \Core\App::defer($task);
}
