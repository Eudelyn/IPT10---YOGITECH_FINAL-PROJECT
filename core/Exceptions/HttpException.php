<?php
declare(strict_types=1);

namespace Core\Exceptions;

use Exception;

/** Thrown to stop processing with an HTTP error code (404, 403, ...). */
class HttpException extends Exception
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message = '')
    {
        $this->statusCode = $statusCode;
        parent::__construct($message ?: self::defaultMessage($statusCode), $statusCode);
    }

    public function statusCode(): int { return $this->statusCode; }

    public static function defaultMessage(int $code): string
    {
        $map = [
            400 => 'Bad request', 403 => 'You don\'t have access to this page.',
            404 => 'This page doesn\'t exist.', 405 => 'Method not allowed.',
            419 => 'Your session expired. Reload the page and try again.',
            429 => 'Too many requests. Wait a minute and try again.', 500 => 'Something broke on our side.',
        ];
        return $map[$code] ?? 'Error';
    }
}
