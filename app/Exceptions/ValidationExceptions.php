<?php

namespace App\Exceptions;

use Exception;

/**
 * Validation Type Error
 * 
 */
class ValidationExceptions extends Exception
{
    protected int $statusCode;

    public function __construct(
        string $message,
        int $statusCode = 422
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
