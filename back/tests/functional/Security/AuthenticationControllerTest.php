<?php

namespace App\Tests\Functional\Security;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private const GOOGLE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const SPOTIFY_URL = 'https://accounts.spotify.com/authorize';

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    public function testConnectGoogle()
    {
        $this->client->request('GET', '/auth/google');
        $this->assertResponseStatusCodeSame(302);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(self::GOOGLE_URL, $location);
    }

    public function testConnectSpotify()
    {
        $this->client->request('GET', '/auth/spotify');
        $this->assertResponseStatusCodeSame(302);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString(self::SPOTIFY_URL, $location);
    }
}
