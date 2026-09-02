<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class LegacyAuthException extends RuntimeException
{
    public function __construct(string $message, private readonly array $details = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function details(): array
    {
        return $this->details;
    }
}
