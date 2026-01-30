<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\DTO\TokenDTO;
use App\Domain\Entity\User;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class TokenService
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private int $ttl,
        private LoggerInterface $logger
    ) {
    }

    public function createToken(User $user): TokenDTO
    {
        // Generate access token
        $accessToken = $this->jwtManager->create($user);

        // Generate refresh token
        $refreshToken = $this->refreshTokenManager->create();
        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setRefreshToken();
        $refreshToken->setValid((new \DateTime())->modify('+7 days'));
        $this->refreshTokenManager->save($refreshToken);

        $this->logger->debug('Tokens created', [
            'userId' => $user->getId()->getValue(),
            'email' => $user->getEmail()->getValue(),
        ]);

        return new TokenDTO(
            $accessToken,
            $refreshToken->getRefreshToken(),
            $this->ttl,
            'Bearer'
        );
    }

    public function refreshToken(string $refreshTokenString): TokenDTO
    {
        $refreshToken = $this->refreshTokenManager->get($refreshTokenString);

        if ($refreshToken === null || !$refreshToken->isValid()) {
            $this->logger->warning('Invalid refresh token attempt', ['token' => substr($refreshTokenString, 0, 20) . '...']);
            throw new \InvalidArgumentException('Invalid or expired refresh token');
        }

        // Get user from refresh token
        $username = $refreshToken->getUsername();
        
        // Note: In a real implementation, you'd fetch the user from repository
        // For now, we'll throw an exception as this requires user repository injection
        throw new \RuntimeException('Token refresh not fully implemented - requires user repository');
    }
}