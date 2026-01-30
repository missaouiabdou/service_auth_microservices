<?php

declare(strict_types=1);

namespace App\Application\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class RateLimiter
{
    private const CACHE_KEY_PREFIX = 'rate_limit_';

    public function __construct(
        private CacheInterface $cache,
        private int $maxAttempts,
        private int $decayMinutes,
        private LoggerInterface $logger
    ) {
    }

    public function attempt(string $key): bool
    {
        if ($this->tooManyAttempts($key)) {
            $this->logger->warning('Rate limit exceeded', [
                'key' => $key,
                'maxAttempts' => $this->maxAttempts,
                'availableIn' => $this->availableIn($key),
            ]);
            return false;
        }

        $this->incrementAttempts($key);
        return true;
    }

    public function tooManyAttempts(string $key): bool
    {
        $attempts = $this->getAttempts($key);
        return $attempts >= $this->maxAttempts;
    }

    public function availableIn(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        
        $expiresAt = $this->cache->get($cacheKey . '_expires', function (ItemInterface $item): ?int {
            return null;
        });

        if ($expiresAt === null) {
            return 0;
        }

        $availableIn = $expiresAt - time();
        return max(0, $availableIn);
    }

    public function clear(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        $this->cache->delete($cacheKey);
        $this->cache->delete($cacheKey . '_expires');
        
        $this->logger->debug('Rate limit cleared', ['key' => $key]);
    }

    private function getAttempts(string $key): int
    {
        $cacheKey = $this->getCacheKey($key);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item): int {
            $item->expiresAfter($this->decayMinutes * 60);
            return 0;
        });
    }

    private function incrementAttempts(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        $attempts = $this->getAttempts($key) + 1;
        
        $this->cache->delete($cacheKey);
        
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($attempts): int {
            $item->expiresAfter($this->decayMinutes * 60);
            return $attempts;
        });

        // Set expiration time
        $expiresAt = time() + ($this->decayMinutes * 60);
        $this->cache->delete($cacheKey . '_expires');
        
        $this->cache->get($cacheKey . '_expires', function (ItemInterface $item) use ($expiresAt): int {
            $item->expiresAfter($this->decayMinutes * 60);
            return $expiresAt;
        });

        $this->logger->debug('Rate limit attempt recorded', [
            'key' => $key,
            'attempts' => $attempts,
            'maxAttempts' => $this->maxAttempts,
        ]);
    }

    private function getCacheKey(string $key): string
    {
        return self::CACHE_KEY_PREFIX . md5($key);
    }
}