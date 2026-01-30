<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Handler;

use App\Domain\Event\UserCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UserCreatedEventHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(UserCreatedEvent $event): void
    {
        $this->logger->info('UserCreatedEvent handled', [
            'userId' => $event->getUserId(),
            'email' => $event->getEmail(),
            'name' => $event->getName(),
            'occurredAt' => $event->getOccurredAt()->format('Y-m-d H:i:s'),
        ]);

        // This handler will be called when the event is dispatched to RabbitMQ
        // The actual publishing to RabbitMQ is handled by Symfony Messenger transport
    }
}