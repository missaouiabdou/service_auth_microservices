<?php

declare(strict_types=1);

namespace App\Domain\Event;

use DateTimeImmutable;

final readonly class UserCreatedEvent
{
    public function __construct(
        private string $userId,
        private string $email,
        private string $name,
        private array $roles,
        private DateTimeImmutable $occurredAt
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles,
            'occurredAt' => $this->occurredAt->format(DateTimeImmutable::ATOM),
        ];
    }
}