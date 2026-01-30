<?php

declare(strict_types=1);

namespace App\Presentation\Exception\Handler;

use App\Presentation\Exception\Custom\AuthenticationException;
use App\Presentation\Exception\Custom\RateLimitException;
use App\Presentation\Exception\Custom\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class GlobalExceptionHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $this->logger->error('Exception caught', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $response = match (true) {
            $exception instanceof RateLimitException => $this->handleRateLimitException($exception),
            $exception instanceof AuthenticationException => $this->handleAuthenticationException($exception),
            $exception instanceof ValidationException => $this->handleValidationException($exception),
            $exception instanceof HttpExceptionInterface => $this->handleHttpException($exception),
            default => $this->handleGenericException($exception),
        };

        $event->setResponse($response);
    }

    private function handleRateLimitException(RateLimitException $exception): JsonResponse
    {
        $response = new JsonResponse([
            'error' => $exception->getMessage(),
            'retryAfter' => $exception->getRetryAfter(),
        ], Response::HTTP_TOO_MANY_REQUESTS);

        $response->headers->set('Retry-After', (string) $exception->getRetryAfter());

        return $response;
    }

    private function handleAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'error' => $exception->getMessage(),
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function handleValidationException(ValidationException $exception): JsonResponse
    {
        return new JsonResponse([
            'error' => $exception->getMessage(),
        ], Response::HTTP_BAD_REQUEST);
    }

    private function handleHttpException(HttpExceptionInterface $exception): JsonResponse
    {
        return new JsonResponse([
            'error' => $exception->getMessage(),
        ], $exception->getStatusCode());
    }

    private function handleGenericException(\Throwable $exception): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An unexpected error occurred';

        // In development, show detailed error message
        if ($this->environment === 'dev') {
            $message = $exception->getMessage();
        }

        return new JsonResponse([
            'error' => $message,
        ], $statusCode);
    }
}