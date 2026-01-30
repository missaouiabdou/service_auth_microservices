<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUserDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Invalid email address')]
        public string $email,

        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
        )]
        public string $password,

        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 2, max: 255, minMessage: 'Name must be at least 2 characters', maxMessage: 'Name cannot exceed 255 characters')]
        public string $name
    ) {
    }
}