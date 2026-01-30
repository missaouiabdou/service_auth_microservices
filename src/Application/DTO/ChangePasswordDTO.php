<?php

declare(strict_types=1);

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangePasswordDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Current password is required')]
        public string $currentPassword,

        #[Assert\NotBlank(message: 'New password is required')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
        #[Assert\Regex(
            pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            message: 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'
        )]
        public string $newPassword
    ) {
    }
}