<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\OutboxStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'outbox_events')]
#[ORM\Index(columns: ['status', 'occurred_at'], name: 'idx_outbox_status_occurred')]
#[ORM\Index(columns: ['aggregate_id', 'aggregate_type'], name: 'idx_outbox_aggregate')]
#[ORM\Index(columns: ['event_type'], name: 'idx_outbox_event_type')]
class OutboxEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $aggregateId;

    #[ORM\Column(type: 'string', length: 100)]
    private string $aggregateType;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: 'string', length: 20, enumType: OutboxStatus::class)]
    private OutboxStatus $status;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $retryCount = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    public function __construct(
        string $aggregateId,
        string $aggregateType,
        string $eventType,
        array $payload
    ) {
        $this->id = \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->occurredAt = new DateTimeImmutable();
        $this->status = OutboxStatus::PENDING;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getProcessedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getStatus(): OutboxStatus
    {
        return $this->status;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function markAsProcessed(): void
    {
        $this->status = OutboxStatus::PROCESSED;
        $this->processedAt = new DateTimeImmutable();
        $this->errorMessage = null;
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = OutboxStatus::FAILED;
        $this->retryCount++;
        $this->errorMessage = $errorMessage;
    }

    public function resetForRetry(): void
    {
        $this->status = OutboxStatus::PENDING;
        $this->errorMessage = null;
    }

    public function isProcessed(): bool
    {
        return $this->status->isProcessed();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->retryCount < $maxRetries;
    }
}