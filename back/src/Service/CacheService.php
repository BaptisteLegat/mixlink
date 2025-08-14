<?php

namespace App\Service;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    public function __construct(
        #[Autowire(service: 'cache.api_responses')]
        private CacheInterface $apiResponsesCache,
        private LoggerInterface $logger,
    ) {
    }

    public function getCachedApiResponse(string $key, callable $callback, ?int $ttl = null): mixed
    {
        try {
            return $this->apiResponsesCache->get($key, function (ItemInterface $item) use ($callback, $ttl): mixed {
                if (null !== $ttl) {
                    $item->expiresAfter($ttl);
                }

                return $callback();
            });
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Cache error for API response', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }
}
