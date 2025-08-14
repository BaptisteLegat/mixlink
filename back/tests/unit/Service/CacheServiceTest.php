<?php

namespace App\Tests\Unit\Service;

use App\Service\CacheService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheServiceTest extends TestCase
{
    private CacheInterface|MockObject $cacheInterfaceMocked;
    private LoggerInterface|MockObject $loggerMocked;
    private CacheService $cacheService;

    protected function setUp(): void
    {
        $this->cacheInterfaceMocked = $this->createMock(CacheInterface::class);
        $this->loggerMocked = $this->createMock(LoggerInterface::class);
        $this->cacheService = new CacheService($this->cacheInterfaceMocked, $this->loggerMocked);
    }

    public function testGetCachedApiResponseWithCacheHit(): void
    {
        $key = 'test_key';
        $expectedResult = ['data' => 'cached_value'];
        $callback = fn () => ['data' => 'fresh_value'];

        $this->cacheInterfaceMocked
            ->expects($this->once())
            ->method('get')
            ->with($key, $this->isCallable())
            ->willReturn($expectedResult)
        ;

        $result = $this->cacheService->getCachedApiResponse($key, $callback);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetCachedApiResponseWithCustomTtl(): void
    {
        $key = 'test_key';
        $ttl = 3600;
        $freshData = ['data' => 'fresh_value'];
        $callback = fn () => $freshData;

        $itemMock = $this->createMock(ItemInterface::class);
        $itemMock
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($ttl)
        ;

        $this->cacheInterfaceMocked
            ->expects($this->once())
            ->method('get')
            ->with($key, $this->isCallable())
            ->willReturnCallback(function ($key, $callable) use ($itemMock) {
                return $callable($itemMock);
            })
        ;

        $result = $this->cacheService->getCachedApiResponse($key, $callback, $ttl);

        $this->assertSame($freshData, $result);
    }

    public function testGetCachedApiResponseWithCacheException(): void
    {
        $key = 'test_key';
        $fallbackData = ['data' => 'fallback_value'];
        $callback = fn () => $fallbackData;
        $cacheException = new InvalidArgumentException('Invalid cache key');

        $this->cacheInterfaceMocked
            ->expects($this->once())
            ->method('get')
            ->with($key, $this->isCallable())
            ->willThrowException($cacheException)
        ;

        $this->loggerMocked
            ->expects($this->once())
            ->method('error')
            ->with(
                'Cache error for API response',
                [
                    'key' => $key,
                    'error' => 'Invalid cache key',
                ]
            )
        ;

        $result = $this->cacheService->getCachedApiResponse($key, $callback);

        $this->assertSame($fallbackData, $result);
    }
}
