<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\RegisterUserDTO;
use App\Domain\Entity\OutboxEvent;
use App\Domain\Entity\User;
use App\Domain\Event\UserCreatedEvent;
use App\Domain\Repository\IOutboxRepository;
use App\Domain\Repository\IUserRepository;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Password;
use App\Domain\ValueObject\UserId;
use App\Presentation\Exception\Custom\ValidationException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class RegistrationService
{
    public function __construct(
        private IUserRepository $userRepository,
        private IOutboxRepository $outboxRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    public function register(RegisterUserDTO $dto): User
    {
        $email = Email::fromString($dto->email);

        // Check if user already exists
        if ($this->userRepository->existsByEmail($email)) {
            $this->logger->warning('Registration attempt with existing email', ['email' => $dto->email]);
            throw new ValidationException('User with this email already exists');
        }

        // Start transaction for Outbox Pattern
        $this->entityManager->beginTransaction();

        try {
            // Create user entity
            $userId = UserId::generate();
            $password = Password::fromPlain($dto->password);
            $user = new User($userId, $email, $password, $dto->name);

            // Persist user
            $this->userRepository->save($user);

            // Create outbox event for UserCreated
            $userCreatedEvent = new UserCreatedEvent(
                $userId->getValue(),
                $email->getValue(),
                $dto->name,
                $user->getRoles(),
                new DateTimeImmutable()
            );

            $outboxEvent = new OutboxEvent(
                $userId->getValue(),
                'User',
                'UserCreated',
                $userCreatedEvent->toArray()
            );

            // Persist outbox event
            $this->outboxRepository->save($outboxEvent);

            // Commit transaction - both user and outbox event are saved atomically
            $this->entityManager->commit();

            $this->logger->info('User registered successfully', [
                'userId' => $userId->getValue(),
                'email' => $dto->email,
            ]);

            return $user;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $this->logger->error('Failed to register user', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}