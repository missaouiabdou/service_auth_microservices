<?php

declare(strict_types=1);

namespace App\Presentation\Exception\Custom;

final class RateLimitException extends \Exception
{
    public function __construct(
        string $message = 'Rate limit exceeded',
        private readonly int $retryAfter = 60,
        int $code = 429,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}