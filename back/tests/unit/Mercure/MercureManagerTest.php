<?php

namespace App\Tests\Unit\Mercure;

use App\Mercure\MercureManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MercureManagerTest extends TestCase
{
    private string $jwtKey;
    private string $mercureUrl;
    private MercureManager $manager;

    protected function setUp(): void
    {
        $this->jwtKey = '12345678901234567890123456789012';
        $this->mercureUrl = 'https://mercure.test';
        $this->manager = new MercureManager($this->jwtKey, $this->mercureUrl);
    }

    public function testGenerateTokenForSessionReturnsTokenAndUrl(): void
    {
        $result = $this->manager->generateTokenForSession('ABC123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('mercureUrl', $result);
        $this->assertEquals($this->mercureUrl, $result['mercureUrl']);
        $this->assertIsString($result['token']);
        $this->assertNotEmpty($result['token']);
    }

    public function testGenerateTokenForHostReturnsTokenAndUrl(): void
    {
        $result = $this->manager->generateTokenForHost('ABC123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('mercureUrl', $result);
        $this->assertEquals($this->mercureUrl, $result['mercureUrl']);
        $this->assertIsString($result['token']);
        $this->assertNotEmpty($result['token']);
    }

    public function testGenerateTokenForSessionThrowsIfKeyMissing(): void
    {
        $manager = new MercureManager('', $this->mercureUrl);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MERCURE_PUBLISHER_JWT_KEY is not set');
        $manager->generateTokenForSession('ABC123');
    }

    public function testGenerateTokenForHostThrowsIfKeyMissing(): void
    {
        $manager = new MercureManager('', $this->mercureUrl);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MERCURE_PUBLISHER_JWT_KEY is not set');
        $manager->generateTokenForHost('ABC123');
    }
}
