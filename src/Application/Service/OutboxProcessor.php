<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Event\UserCreatedEvent;
use App\Domain\Repository\IOutboxRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class OutboxProcessor
{
    private const MAX_RETRIES = 3;
    private const BATCH_SIZE = 100;

    public function __construct(
        private IOutboxRepository $outboxRepository,
        private MessageBusInterface $messageBus,
        private CircuitBreaker $circuitBreaker,
        private LoggerInterface $logger
    ) {
    }

    public function processEvents(): void
    {
        $pendingEvents = $this->outboxRepository->findPendingEvents(self::BATCH_SIZE);

        if (empty($pendingEvents)) {
            $this->logger->debug('No pending outbox events to process');
            return;
        }

        $this->logger->info('Processing outbox events', ['count' => count($pendingEvents)]);

        foreach ($pendingEvents as $event) {
            try {
                // Use circuit breaker to protect against cascading failures
                $this->circuitBreaker->call(
                    function () use ($event) {
                        $this->publishEvent($event);
                    },
                    'rabbitmq'
                );

                $this->outboxRepository->markAsProcessed($event);

                $this->logger->info('Outbox event processed successfully', [
                    'eventId' => $event->getId(),
                    'eventType' => $event->getEventType(),
                    'aggregateId' => $event->getAggregateId(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process outbox event', [
                    'eventId' => $event->getId(),
                    'eventType' => $event->getEventType(),
                    'error' => $e->getMessage(),
                    'retryCount' => $event->getRetryCount(),
                ]);

                if ($event->canRetry(self::MAX_RETRIES)) {
                    $this->outboxRepository->markAsFailed($event, $e->getMessage());
                } else {
                    $this->logger->critical('Outbox event exceeded max retries', [
                        'eventId' => $event->getId(),
                        'eventType' => $event->getEventType(),
                        'retryCount' => $event->getRetryCount(),
                    ]);
                }
            }
        }
    }

    private function publishEvent($event): void
    {
        $payload = $event->getPayload();

        // Create domain event based on event type
        $domainEvent = match ($event->getEventType()) {
            'UserCreated' => new UserCreatedEvent(
                $payload['userId'],
                $payload['email'],
                $payload['name'],
                $payload['roles'],
                new \DateTimeImmutable($payload['occurredAt'])
            ),
            default => throw new \InvalidArgumentException(sprintf('Unknown event type: %s', $event->getEventType())),
        };

        // Dispatch to message bus (RabbitMQ)
        $this->messageBus->dispatch($domainEvent);

        $this->logger->debug('Event published to message bus', [
            'eventType' => $event->getEventType(),
            'aggregateId' => $event->getAggregateId(),
        ]);
    }
}