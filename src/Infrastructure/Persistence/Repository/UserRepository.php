<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\User;
use App\Domain\Repository\IUserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class UserRepository implements IUserRepository
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->repository = $entityManager->getRepository(User::class);
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->repository->find($id->getValue());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->repository->findOneBy(['email' => $email->getValue()]);
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->repository->count(['email' => $email->getValue()]) > 0;
    }

    public function delete(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}