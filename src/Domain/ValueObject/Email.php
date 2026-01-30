<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(
        private string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf('Invalid email address: %s', $value));
        }
    }

    public static function fromString(string $email): self
    {
        return new self(strtolower(trim($email)));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}