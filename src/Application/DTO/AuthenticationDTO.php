<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AuthenticationDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email address')]
        public string $email,

        #[Assert\NotBlank(message: 'Password is required')]
        public string $password
    ) {
    }
}