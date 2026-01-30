<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\OutboxEvent;
use App\Domain\Enum\OutboxStatus;
use App\Domain\Repository\IOutboxRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class OutboxRepository implements IOutboxRepository
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->repository = $entityManager->getRepository(OutboxEvent::class);
    }

    public function save(OutboxEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    public function findPendingEvents(int $limit = 100): array
    {
        return $this->repository->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', OutboxStatus::PENDING)
            ->orderBy('o.occurredAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function markAsProcessed(OutboxEvent $event): void
    {
        $event->markAsProcessed();
        $this->entityManager->flush();
    }

    public function markAsFailed(OutboxEvent $event, string $errorMessage): void
    {
        $event->markAsFailed($errorMessage);
        $this->entityManager->flush();
    }
}