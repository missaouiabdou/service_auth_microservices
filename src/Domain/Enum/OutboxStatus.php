<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum OutboxStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSED = 'PROCESSED';
    case FAILED = 'FAILED';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isProcessed(): bool
    {
        return $this === self::PROCESSED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}