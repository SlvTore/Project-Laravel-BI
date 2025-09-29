<?php

namespace App\Services\Exceptions;

use Exception;

class DataFeedCommitException extends Exception
{
    /**
     * Additional context payload.
     */
    protected array $context;

    public function __construct(string $message, int $code = 422, array $context = [])
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
