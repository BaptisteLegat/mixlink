<?php

namespace App\Tests\Unit\Service;

use App\Entity\Provider;
use App\Service\OAuthTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OAuthTokenManagerTest extends TestCase
{
    private OAuthTokenManager $tokenManager;
    private HttpClientInterface|MockObject $httpClientMock;
    private EntityManagerInterface|MockObject $entityManagerMock;
    private string $googleClientId = 'google_client_id';
    private string $googleClientSecret = 'google_client_secret';
    private string $spotifyClientId = 'spotify_client_id';
    private string $spotifyClientSecret = 'spotify_client_secret';
    private string $soundcloudClientId = 'soundcloud_client_id';
    private string $soundcloudClientSecret = 'soundcloud_client_secret';

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $this->tokenManager = new OAuthTokenManager(
            $this->httpClientMock,
            $this->entityManagerMock,
            $this->googleClientId,
            $this->googleClientSecret,
            $this->spotifyClientId,
            $this->spotifyClientSecret,
            $this->soundcloudClientId,
            $this->soundcloudClientSecret
        );
    }

    public function testGetValidAccessTokenWithValidToken(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('valid_token')
        ;

        $result = $this->tokenManager->getValidAccessToken($provider);

        $this->assertEquals('valid_token', $result);
    }

    public function testGetValidAccessTokenWithNullToken(): void
    {
        $provider = new Provider()->setName('spotify');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No access token available');

        $this->tokenManager->getValidAccessToken($provider);
    }

    public function testGetValidAccessTokenWithTokenAndRefreshToken(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('access_token')
            ->setRefreshToken('refresh_token')
        ;

        $result = $this->tokenManager->getValidAccessToken($provider);

        $this->assertEquals('access_token', $result);
    }

    public function testRefreshAccessTokenForSpotify(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setRefreshToken('refresh_token')
        ;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $responseMock->method('toArray')->willReturn([
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'expires_in' => 3600,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Authorization' => 'Basic '.base64_encode($this->spotifyClientId.':'.$this->spotifyClientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => 'refresh_token',
                ],
            ])
            ->willReturn($responseMock)
        ;

        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->tokenManager->refreshAccessToken($provider);

        $this->assertEquals('new_access_token', $result);
        $this->assertEquals('new_access_token', $provider->getAccessToken());
        $this->assertEquals('new_refresh_token', $provider->getRefreshToken());
    }

    public function testRefreshAccessTokenForGoogle(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setRefreshToken('refresh_token')
        ;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $responseMock->method('toArray')->willReturn([
            'access_token' => 'new_google_token',
            'expires_in' => 3600,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://oauth2.googleapis.com/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'client_id' => $this->googleClientId,
                    'client_secret' => $this->googleClientSecret,
                    'refresh_token' => 'refresh_token',
                    'grant_type' => 'refresh_token',
                ],
            ])
            ->willReturn($responseMock)
        ;

        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->tokenManager->refreshAccessToken($provider);

        $this->assertEquals('new_google_token', $result);
        $this->assertEquals('new_google_token', $provider->getAccessToken());
    }

    public function testRefreshAccessTokenForSoundCloud(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setRefreshToken('refresh_token')
        ;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $responseMock->method('toArray')->willReturn([
            'access_token' => 'new_soundcloud_token',
            'refresh_token' => 'new_soundcloud_refresh',
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.soundcloud.com/oauth2/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'client_id' => $this->soundcloudClientId,
                    'client_secret' => $this->soundcloudClientSecret,
                    'refresh_token' => 'refresh_token',
                    'grant_type' => 'refresh_token',
                ],
            ])
            ->willReturn($responseMock)
        ;

        $this->entityManagerMock
            ->expects($this->once())
            ->method('flush')
        ;

        $result = $this->tokenManager->refreshAccessToken($provider);

        $this->assertEquals('new_soundcloud_token', $result);
        $this->assertEquals('new_soundcloud_token', $provider->getAccessToken());
        $this->assertEquals('new_soundcloud_refresh', $provider->getRefreshToken());
    }

    public function testRefreshAccessTokenWithUnsupportedProvider(): void
    {
        $provider = new Provider()
            ->setName('unsupported')
            ->setRefreshToken('refresh_token')
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported provider: unsupported');

        $this->tokenManager->refreshAccessToken($provider);
    }

    public function testRefreshAccessTokenWithNoRefreshToken(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setRefreshToken(null)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No refresh token available for this provider. Please reconnect to get a refresh token.');

        $this->tokenManager->refreshAccessToken($provider);
    }

    public function testRefreshAccessTokenWithHttpError(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setRefreshToken('refresh_token')
        ;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $responseMock->method('toArray')->willReturn(['error' => 'invalid_grant']);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($responseMock)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh Spotify token');

        $this->tokenManager->refreshAccessToken($provider);
    }

    public function testHasRefreshTokenWithToken(): void
    {
        $provider = new Provider()->setRefreshToken('refresh_token');

        $result = $this->tokenManager->hasRefreshToken($provider);

        $this->assertTrue($result);
    }

    public function testHasRefreshTokenWithoutToken(): void
    {
        $result = $this->tokenManager->hasRefreshToken(new Provider());

        $this->assertFalse($result);
    }

    public function testRefreshGoogleTokenWithHttpError(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setRefreshToken('refresh_token')
        ;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_UNAUTHORIZED);
        $responseMock->method('toArray')->willReturn(['error' => 'invalid_grant']);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://oauth2.googleapis.com/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'client_id' => $this->googleClientId,
                    'client_secret' => $this->googleClientSecret,
                    'refresh_token' => 'refresh_token',
                    'grant_type' => 'refresh_token',
                ],
            ])
            ->willReturn($responseMock)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh Google token');

        $this->tokenManager->refreshAccessToken($provider);
    }

    public function testRefreshSoundCloudTokenWithHttpError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setRefreshToken('refresh_token')
        ;

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_FORBIDDEN);
        $responseMock->method('toArray')->willReturn(['error' => 'access_denied']);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.soundcloud.com/oauth2/token', [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => [
                    'client_id' => $this->soundcloudClientId,
                    'client_secret' => $this->soundcloudClientSecret,
                    'refresh_token' => 'refresh_token',
                    'grant_type' => 'refresh_token',
                ],
            ])
            ->willReturn($responseMock)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh SoundCloud token');

        $this->tokenManager->refreshAccessToken($provider);
    }
}
