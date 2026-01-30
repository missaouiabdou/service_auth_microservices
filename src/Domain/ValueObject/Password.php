<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Password
{
    private const MIN_LENGTH = 8;
    private const PATTERN = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/';

    private function __construct(
        private string $hashedValue
    ) {
    }

    public static function fromPlain(string $plainPassword): self
    {
        if (strlen($plainPassword) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Password must be at least %d characters long', self::MIN_LENGTH)
            );
        }

        if (!preg_match(self::PATTERN, $plainPassword)) {
            throw new InvalidArgumentException(
                'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
            );
        }

        $hashedValue = password_hash($plainPassword, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);

        if ($hashedValue === false) {
            throw new InvalidArgumentException('Failed to hash password');
        }

        return new self($hashedValue);
    }

    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->hashedValue);
    }

    public function getHash(): string
    {
        return $this->hashedValue;
    }

    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hashedValue, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }
}