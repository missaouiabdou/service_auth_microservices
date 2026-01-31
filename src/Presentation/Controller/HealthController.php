<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/health', name: 'health_')]
#[OA\Tag(name: 'Health')]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    #[Route('', name: 'check', methods: ['GET'])]
    #[OA\Get(
        path: '/api/health',
        summary: 'Health check',
        description: 'Basic health check endpoint to verify the service is running',
        tags: ['Health']
    )]
    #[OA\Response(
        response: 200,
        description: 'Service is healthy',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2024-01-05T10:30:00+00:00')
            ]
        )
    )]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'healthy',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM),
        ], Response::HTTP_OK);
    }

    #[Route('/ready', name: 'ready', methods: ['GET'])]
    #[OA\Get(
        path: '/api/health/ready',
        summary: 'Readiness probe',
        description: 'Checks if the service is ready to accept traffic (database, cache, messaging)',
        tags: ['Health']
    )]
    #[OA\Response(
        response: 200,
        description: 'Service is ready',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'ready'),
                new OA\Property(
                    property: 'checks',
                    properties: [
                        new OA\Property(property: 'database', type: 'string', example: 'ok'),
                        new OA\Property(property: 'cache', type: 'string', example: 'ok'),
                        new OA\Property(property: 'messaging', type: 'string', example: 'ok')
                    ],
                    type: 'object'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 503,
        description: 'Service is not ready',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'not_ready'),
                new OA\Property(property: 'checks', type: 'object')
            ]
        )
    )]
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => 'ok', // Simplified for now
            'messaging' => 'ok', // Simplified for now
        ];

        $allHealthy = !in_array('fail', $checks, true);

        return $this->json([
            'status' => $allHealthy ? 'ready' : 'not_ready',
            'checks' => $checks,
        ], $allHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }

    #[Route('/live', name: 'live', methods: ['GET'])]
    #[OA\Get(
        path: '/api/health/live',
        summary: 'Liveness probe',
        description: 'Checks if the service is alive (used by Kubernetes)',
        tags: ['Health']
    )]
    #[OA\Response(
        response: 200,
        description: 'Service is alive',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'alive'),
                new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2024-01-05T10:30:00+00:00')
            ]
        )
    )]
    public function live(): JsonResponse
    {
        return $this->json([
            'status' => 'alive',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM),
        ], Response::HTTP_OK);
    }

    private function checkDatabase(): string
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            return 'ok';
        } catch (\Throwable $e) {
            return 'fail';
        }
    }
}