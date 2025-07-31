<?php

namespace App\Tests\Functional;

use App\Provider\ProviderManager;
use App\Service\Export\ExportServiceFactory;
use App\Service\PlaylistExportService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlaylistExportControllerTest extends WebTestCase
{
    public function testRoutesAreRegistered(): void
    {
        $router = static::getContainer()->get('router');

        // Test that routes exist
        $this->assertNotNull($router->getRouteCollection()->get('api_playlist_export'));
        $this->assertNotNull($router->getRouteCollection()->get('api_playlist_export_platforms'));
    }

    public function testServicesAreRegistered(): void
    {
        $container = static::getContainer();

        // Test that services are registered
        $this->assertTrue($container->has(PlaylistExportService::class));
        $this->assertTrue($container->has(ExportServiceFactory::class));
        $this->assertTrue($container->has(ProviderManager::class));
    }

    public function testControllerIsRegistered(): void
    {
        $container = static::getContainer();

        // Test that controller is registered
        $this->assertTrue($container->has('App\Controller\PlaylistExportController'));
    }

    public function testExportServicesAreRegistered(): void
    {
        $container = static::getContainer();

        // Test that export services are registered
        $this->assertTrue($container->has('App\Service\Export\SpotifyExportService'));
        $this->assertTrue($container->has('App\Service\Export\GoogleExportService'));
        $this->assertTrue($container->has('App\Service\Export\SoundCloudExportService'));
    }
}
