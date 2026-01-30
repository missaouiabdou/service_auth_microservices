<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\OutboxEvent;

interface IOutboxRepository
{
    public function save(OutboxEvent $event): void;

    /**
     * @return OutboxEvent[]
     */
    public function findPendingEvents(int $limit = 100): array;

    public function markAsProcessed(OutboxEvent $event): void;

    public function markAsFailed(OutboxEvent $event, string $errorMessage): void;
}