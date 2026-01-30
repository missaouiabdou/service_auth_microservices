<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Repository\IUserRepository;
use App\Domain\ValueObject\Email;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final readonly class UserProvider implements UserProviderInterface
{
    public function __construct(
        private IUserRepository $userRepository
    ) {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === \App\Domain\Entity\User::class || is_subclass_of($class, \App\Domain\Entity\User::class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $email = Email::fromString($identifier);
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw new UserNotFoundException(sprintf('User with email "%s" not found', $identifier));
        }

        return $user;
    }
}