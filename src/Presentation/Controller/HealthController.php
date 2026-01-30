<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// CHANGEMENT ICI : Ajout du préfixe /api
#[Route('/api/health', name: 'health_')]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    // La route complète sera maintenant : /api/health
    #[Route('', name: 'check', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'healthy',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM),
        ], Response::HTTP_OK);
    }

    // La route complète sera maintenant : /api/health/ready
    #[Route('/ready', name: 'ready', methods: ['GET'])]
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

    // La route complète sera maintenant : /api/health/live
    #[Route('/live', name: 'live', methods: ['GET'])]
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
