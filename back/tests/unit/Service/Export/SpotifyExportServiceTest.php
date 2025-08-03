<?php

namespace App\Tests\Unit\Service\Export;

use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\SpotifyExportService;
use App\Service\OAuthTokenManager;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpotifyExportServiceTest extends TestCase
{
    private SpotifyExportService $spotifyExportService;
    private HttpClientInterface|MockObject $httpClientMock;
    private OAuthTokenManager|MockObject $tokenManagerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->tokenManagerMock = $this->createMock(OAuthTokenManager::class);

        $this->spotifyExportService = new SpotifyExportService(
            $this->httpClientMock,
            $this->tokenManagerMock
        );
    }

    public function testExportPlaylistWithValidData(): void
    {
        $accessToken = 'valid-access-token';
        $playlistName = 'Test Playlist';
        $spotifyUserId = 'spotify-user-123';
        $playlistId = 'playlist-123';
        $playlistUrl = 'https://open.spotify.com/playlist/123';
        $spotifyId = 'spotify-track-123';

        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken($accessToken)
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
            ->setSpotifyId($spotifyId)
        ;

        $playlist = new Playlist()
            ->setName($playlistName)
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('getValidAccessToken')
            ->with($provider)
            ->willReturn($accessToken)
        ;

        $userProfileResponse = $this->createMock(ResponseInterface::class);
        $userProfileResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $userProfileResponse->method('toArray')->willReturn(['id' => $spotifyUserId]);

        $playlistResponse = $this->createMock(ResponseInterface::class);
        $playlistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $playlistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'external_urls' => ['spotify' => $playlistUrl],
        ]);

        $tracksResponse = $this->createMock(ResponseInterface::class);
        $tracksResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);

        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($userProfileResponse, $playlistResponse, $tracksResponse)
        ;

        $result = $this->spotifyExportService->exportPlaylist($playlist, $user);

        $this->assertEquals($playlistId, $result->playlistId);
        $this->assertEquals($playlistUrl, $result->playlistUrl);
        $this->assertEquals(1, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('spotify', $result->platform);
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $user = new User();
        $playlist = new Playlist()->setName('Test Playlist');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to Spotify');

        $this->spotifyExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('spotify');

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->with($provider)
            ->willThrowException(new RuntimeException('No access token available'))
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No access token available');

        $this->spotifyExportService->exportPlaylist($playlist, $user);
    }

    public function testIsUserConnectedWithValidProvider(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $this->assertTrue($this->spotifyExportService->isUserConnected($user));
    }

    public function testIsUserConnectedWithNoProvider(): void
    {
        $this->assertFalse($this->spotifyExportService->isUserConnected(new User()));
    }

    public function testIsUserConnectedWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('spotify');

        $user = new User()->addProvider($provider);

        $this->assertFalse($this->spotifyExportService->isUserConnected($user));
    }

    public function testExportPlaylistWithEmptyPlaylist(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Empty Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $userProfileResponse = $this->createMock(ResponseInterface::class);
        $userProfileResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $userProfileResponse->method('toArray')->willReturn(['id' => 'user123']);

        $playlistResponse = $this->createMock(ResponseInterface::class);
        $playlistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $playlistResponse->method('toArray')->willReturn([
            'id' => 'playlist123',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/123'],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($userProfileResponse, $playlistResponse)
        ;

        $result = $this->spotifyExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('spotify', $result->platform);
    }

    public function testExportPlaylistWithSongWithoutSpotifyId(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Song without Spotify ID')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $userProfileResponse = $this->createMock(ResponseInterface::class);
        $userProfileResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $userProfileResponse->method('toArray')->willReturn(['id' => 'user123']);

        $playlistResponse = $this->createMock(ResponseInterface::class);
        $playlistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $playlistResponse->method('toArray')->willReturn([
            'id' => 'playlist123',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/123'],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($userProfileResponse, $playlistResponse)
        ;

        $result = $this->spotifyExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('spotify', $result->platform);
    }

    public function testExportPlaylistWithApiError(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API request failed with status: 400');

        $this->spotifyExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithMoreThan100Tracks(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Large Playlist');

        for ($i = 1; $i <= 150; ++$i) {
            $song = new Song()
                ->setTitle('Song '.$i)
                ->setArtists('Artist '.$i)
                ->setSpotifyId('track-'.$i)
            ;
            $playlist->addSong($song);
        }

        $this->tokenManagerMock
            ->expects($this->exactly(4))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $userProfileResponse = $this->createMock(ResponseInterface::class);
        $userProfileResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $userProfileResponse->method('toArray')->willReturn(['id' => 'user123']);

        $playlistResponse = $this->createMock(ResponseInterface::class);
        $playlistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $playlistResponse->method('toArray')->willReturn([
            'id' => 'playlist123',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/123'],
        ]);

        $tracksBatch1Response = $this->createMock(ResponseInterface::class);
        $tracksBatch1Response->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $tracksBatch1Response->method('toArray')->willReturn([]);

        $tracksBatch2Response = $this->createMock(ResponseInterface::class);
        $tracksBatch2Response->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $tracksBatch2Response->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(4))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $userProfileResponse,
                $playlistResponse,
                $tracksBatch1Response,
                $tracksBatch2Response
            )
        ;

        $result = $this->spotifyExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(150, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('spotify', $result->platform);
    }

    public function testMakeAuthenticatedRequestWith401ErrorAndSuccessfulRefresh(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('expired-token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('expired-token')
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn('new-fresh-token')
        ;

        $successUserProfileResponse = $this->createMock(ResponseInterface::class);
        $successUserProfileResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $successUserProfileResponse->method('toArray')->willReturn(['id' => 'user123']);

        $successPlaylistResponse = $this->createMock(ResponseInterface::class);
        $successPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $successPlaylistResponse->method('toArray')->willReturn([
            'id' => 'playlist123',
            'external_urls' => ['spotify' => 'https://open.spotify.com/playlist/123'],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnCallback(function () use ($successUserProfileResponse, $successPlaylistResponse) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    throw new RuntimeException('HTTP/1.1 401 Unauthorized');
                } elseif (2 === $callCount) {
                    return $successUserProfileResponse;
                } else {
                    return $successPlaylistResponse;
                }
            })
        ;

        $result = $this->spotifyExportService->exportPlaylist($playlist, $user);

        $this->assertInstanceOf(\App\Service\Export\Model\ExportResult::class, $result);
        $this->assertEquals('playlist123', $result->playlistId);
    }

    public function testMakeAuthenticatedRequestWith401ErrorAndFailedRefresh(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('expired-token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('expired-token')
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willThrowException(new RuntimeException('Refresh token expired'))
        ;

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new RuntimeException('HTTP/1.1 401 Unauthorized'))
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh token and retry request: Refresh token expired');

        $this->spotifyExportService->exportPlaylist($playlist, $user);
    }

    public function testMakeAuthenticatedRequestWith401ErrorAndRetryStillFails(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('expired-token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('expired-token')
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn('new-fresh-token')
        ;

        $badRequestResponse = $this->createMock(ResponseInterface::class);
        $badRequestResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function () use ($badRequestResponse) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    throw new RuntimeException('HTTP/1.1 401 Unauthorized');
                }

                return $badRequestResponse;
            });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP/1.1 401 Unauthorized');

        $this->spotifyExportService->exportPlaylist($playlist, $user);
    }

    public function testMakeAuthenticatedRequestWith401ErrorAndRetryThrowsException(): void
    {
        $provider = new Provider()
            ->setName('spotify')
            ->setAccessToken('expired-token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('expired-token')
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn('new-fresh-token')
        ;

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function () {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    throw new RuntimeException('HTTP/1.1 401 Unauthorized');
                }
                throw new RuntimeException('Network error during retry');
            })
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh token and retry request: Network error during retry');

        $this->spotifyExportService->exportPlaylist($playlist, $user);
    }
}
