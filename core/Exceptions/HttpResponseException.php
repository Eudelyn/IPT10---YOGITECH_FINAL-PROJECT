<?php
declare(strict_types=1);

namespace Core\Exceptions;

use Core\Response;
use Exception;

/** Carries a ready-made Response (e.g. a redirect after failed validation). */
class HttpResponseException extends Exception
{
    private Response $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
        parent::__construct('HTTP response exception');
    }

    public function response(): Response { return $this->response; }
}
