<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Enum\CircuitState;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CircuitBreaker
{
    private const CACHE_KEY_PREFIX = 'circuit_breaker_';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $failureThreshold,
        private readonly int $timeout,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function call(callable $operation, string $serviceName = 'default'): mixed
    {
        $state = $this->getState($serviceName);

        if ($state->isOpen()) {
            if ($this->shouldAttemptReset($serviceName)) {
                $this->setState($serviceName, CircuitState::HALF_OPEN);
                $this->logger->info('Circuit breaker transitioning to HALF_OPEN', ['service' => $serviceName]);
            } else {
                $this->logger->warning('Circuit breaker is OPEN, rejecting call', ['service' => $serviceName]);
                throw new \RuntimeException(sprintf('Circuit breaker is OPEN for service: %s', $serviceName));
            }
        }

        try {
            $result = $operation();
            $this->onSuccess($serviceName);
            return $result;
        } catch (\Throwable $e) {
            $this->onFailure($serviceName, $e);
            throw $e;
        }
    }

    private function getState(string $serviceName): CircuitState
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_state';
        
        return $this->cache->get($cacheKey, function (ItemInterface $item): CircuitState {
            $item->expiresAfter(3600);
            return CircuitState::CLOSED;
        });
    }

    private function setState(string $serviceName, CircuitState $state): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_state';
        $this->cache->delete($cacheKey);
        
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($state): CircuitState {
            $item->expiresAfter(3600);
            return $state;
        });
    }

    private function getFailureCount(string $serviceName): int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_failures';
        
        return $this->cache->get($cacheKey, function (ItemInterface $item): int {
            $item->expiresAfter(3600);
            return 0;
        });
    }

    private function incrementFailureCount(string $serviceName): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_failures';
        $count = $this->getFailureCount($serviceName) + 1;
        
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($count): int {
            $item->expiresAfter(3600);
            return $count;
        });
    }

    private function resetFailureCount(string $serviceName): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_failures';
        $this->cache->delete($cacheKey);
    }

    private function setLastFailureTime(string $serviceName): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_last_failure';
        $this->cache->delete($cacheKey);
        
        $this->cache->get($cacheKey, function (ItemInterface $item): int {
            $item->expiresAfter(3600);
            return time();
        });
    }

    private function getLastFailureTime(string $serviceName): ?int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $serviceName . '_last_failure';
        
        return $this->cache->get($cacheKey, function (ItemInterface $item): ?int {
            $item->expiresAfter(3600);
            return null;
        });
    }

    private function shouldAttemptReset(string $serviceName): bool
    {
        $lastFailureTime = $this->getLastFailureTime($serviceName);
        
        if ($lastFailureTime === null) {
            return true;
        }

        return (time() - $lastFailureTime) >= $this->timeout;
    }

    private function onSuccess(string $serviceName): void
    {
        $state = $this->getState($serviceName);

        if ($state->isHalfOpen()) {
            $this->setState($serviceName, CircuitState::CLOSED);
            $this->resetFailureCount($serviceName);
            $this->logger->info('Circuit breaker closed after successful call', ['service' => $serviceName]);
        }
    }

    private function onFailure(string $serviceName, \Throwable $e): void
    {
        $this->incrementFailureCount($serviceName);
        $this->setLastFailureTime($serviceName);

        $failureCount = $this->getFailureCount($serviceName);

        if ($failureCount >= $this->failureThreshold) {
            $this->setState($serviceName, CircuitState::OPEN);
            $this->logger->error('Circuit breaker opened due to failures', [
                'service' => $serviceName,
                'failureCount' => $failureCount,
                'threshold' => $this->failureThreshold,
                'error' => $e->getMessage(),
            ]);
        } else {
            $this->logger->warning('Circuit breaker recorded failure', [
                'service' => $serviceName,
                'failureCount' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        }
    }

    public function reset(string $serviceName = 'default'): void
    {
        $this->setState($serviceName, CircuitState::CLOSED);
        $this->resetFailureCount($serviceName);
        $this->logger->info('Circuit breaker manually reset', ['service' => $serviceName]);
    }

    public function isOpen(string $serviceName = 'default'): bool
    {
        return $this->getState($serviceName)->isOpen();
    }
}