<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum CircuitState: string
{
    case CLOSED = 'CLOSED';
    case OPEN = 'OPEN';
    case HALF_OPEN = 'HALF_OPEN';

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    public function isOpen(): bool
    {
        return $this === self::OPEN;
    }

    public function isHalfOpen(): bool
    {
        return $this === self::HALF_OPEN;
    }
}