<?php

namespace App\Tests\Unit\Service\Export;

use App\ApiResource\ApiReference;
use App\Service\Export\ExportServiceFactory;
use App\Service\Export\GoogleExportService;
use App\Service\Export\SoundCloudExportService;
use App\Service\Export\SpotifyExportService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExportServiceFactoryTest extends TestCase
{
    private ExportServiceFactory $factory;
    private SpotifyExportService|MockObject $spotifyExportServiceMock;
    private GoogleExportService|MockObject $googleExportServiceMock;
    private SoundCloudExportService|MockObject $soundCloudExportServiceMock;

    protected function setUp(): void
    {
        $this->spotifyExportServiceMock = $this->createMock(SpotifyExportService::class);
        $this->googleExportServiceMock = $this->createMock(GoogleExportService::class);
        $this->soundCloudExportServiceMock = $this->createMock(SoundCloudExportService::class);

        $this->factory = new ExportServiceFactory(
            $this->spotifyExportServiceMock,
            $this->googleExportServiceMock,
            $this->soundCloudExportServiceMock,
        );
    }

    public function testCreateWithSpotifyPlatform(): void
    {
        $service = $this->factory->create(ApiReference::SPOTIFY);

        $this->assertSame($this->spotifyExportServiceMock, $service);
    }

    public function testCreateWithGooglePlatform(): void
    {
        $service = $this->factory->create(ApiReference::GOOGLE);

        $this->assertSame($this->googleExportServiceMock, $service);
    }

    public function testCreateWithSoundCloudPlatform(): void
    {
        $service = $this->factory->create(ApiReference::SOUNDCLOUD);

        $this->assertSame($this->soundCloudExportServiceMock, $service);
    }

    public function testCreateWithUnsupportedPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Platform 'unsupported' is not supported");

        $this->factory->create('unsupported');
    }

    public function testGetAllServices(): void
    {
        $services = $this->factory->getAllServices();

        $this->assertCount(3, $services);
        $this->assertArrayHasKey(ApiReference::SPOTIFY, $services);
        $this->assertArrayHasKey(ApiReference::GOOGLE, $services);
        $this->assertArrayHasKey(ApiReference::SOUNDCLOUD, $services);
        $this->assertSame($this->spotifyExportServiceMock, $services[ApiReference::SPOTIFY]);
        $this->assertSame($this->googleExportServiceMock, $services[ApiReference::GOOGLE]);
        $this->assertSame($this->soundCloudExportServiceMock, $services[ApiReference::SOUNDCLOUD]);
    }

    public function testIsSupportedWithValidPlatforms(): void
    {
        $this->assertTrue($this->factory->isSupported(ApiReference::SPOTIFY));
        $this->assertTrue($this->factory->isSupported(ApiReference::GOOGLE));
        $this->assertTrue($this->factory->isSupported(ApiReference::SOUNDCLOUD));
    }

    public function testIsSupportedWithInvalidPlatform(): void
    {
        $this->assertFalse($this->factory->isSupported('unsupported'));
    }
}
