<?php

namespace App\Core\Exceptions;

use Exception;

class HttpException extends Exception
{
    public function __construct(string $message, private int $statusCode = 500, int $code = 0, ?
        \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
