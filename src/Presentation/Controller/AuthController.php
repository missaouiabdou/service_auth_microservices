<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\AuthenticationDTO;
use App\Application\DTO\RegisterUserDTO;
use App\Application\Service\AuthenticationService;
use App\Application\Service\RateLimiter;
use App\Application\Service\RegistrationService;
use App\Application\Service\TokenService;
use App\Presentation\Exception\Custom\RateLimitException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
final class AuthController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly AuthenticationService $authenticationService,
        private readonly TokenService $tokenService,
        private readonly RateLimiter $rateLimiter,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        $rateLimitKey = 'register:' . $clientIp;

        if (!$this->rateLimiter->attempt($rateLimitKey)) {
            throw new RateLimitException(
                'Too many registration attempts. Please try again later.',
                $this->rateLimiter->availableIn($rateLimitKey)
            );
        }

        $data = json_decode($request->getContent(), true);

        $dto = new RegisterUserDTO(
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['name'] ?? ''
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->registrationService->register($dto);
        $tokenDTO = $this->tokenService->createToken($user);

        return $this->json([
            'user' => [
                'id' => $user->getId()->getValue(),
                'email' => $user->getEmail()->getValue(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
            ],
            'token' => $tokenDTO->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        $rateLimitKey = 'login:' . $email;

        if (!$this->rateLimiter->attempt($rateLimitKey)) {
            throw new RateLimitException(
                'Too many login attempts. Please try again later.',
                $this->rateLimiter->availableIn($rateLimitKey)
            );
        }

        $dto = new AuthenticationDTO(
            $email,
            $data['password'] ?? ''
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $tokenDTO = $this->authenticationService->authenticate($dto);

        // Clear rate limit on successful login
        $this->rateLimiter->clear($rateLimitKey);

        return $this->json($tokenDTO->toArray(), Response::HTTP_OK);
    }

    #[Route('/token/refresh', name: 'token_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        $rateLimitKey = 'refresh:' . $clientIp;

        if (!$this->rateLimiter->attempt($rateLimitKey)) {
            throw new RateLimitException(
                'Too many token refresh attempts. Please try again later.',
                $this->rateLimiter->availableIn($rateLimitKey)
            );
        }

        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refreshToken'] ?? '';

        if (empty($refreshToken)) {
            return $this->json(['error' => 'Refresh token is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokenDTO = $this->tokenService->refreshToken($refreshToken);
            return $this->json($tokenDTO->toArray(), Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // In stateless JWT, logout is handled client-side by removing the token
        // Optionally, implement token blacklisting here
        return $this->json(['message' => 'Logged out successfully'], Response::HTTP_OK);
    }
}
