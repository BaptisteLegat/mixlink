<?php

namespace App\Tests\Unit\Service\Export;

use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\SoundCloudExportService;
use App\Service\OAuthTokenManager;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SoundCloudExportServiceTest extends TestCase
{
    private SoundCloudExportService $soundCloudExportService;
    private HttpClientInterface|MockObject $httpClientMock;
    private OAuthTokenManager|MockObject $tokenManagerMock;
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->tokenManagerMock = $this->createMock(OAuthTokenManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->soundCloudExportService = new SoundCloudExportService(
            $this->httpClientMock,
            $this->tokenManagerMock,
            $this->loggerMock
        );
    }

    public function testGetPlatformName(): void
    {
        $this->assertEquals('soundcloud', $this->soundCloudExportService->getPlatformName());
    }

    public function testIsUserConnectedWithValidProvider(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $this->assertTrue($this->soundCloudExportService->isUserConnected($user));
    }

    public function testIsUserConnectedWithNoProvider(): void
    {
        $this->assertFalse($this->soundCloudExportService->isUserConnected(new User()));
    }

    public function testIsUserConnectedWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('soundcloud');

        $user = new User()->addProvider($provider);

        $this->assertFalse($this->soundCloudExportService->isUserConnected($user));
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $user = new User();
        $playlist = new Playlist()->setName('Test Playlist');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to SoundCloud');

        $this->soundCloudExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('soundcloud');

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

        $this->soundCloudExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithEmptyPlaylist(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';

        $playlist = new Playlist();
        $playlist->setName('Empty Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($createPlaylistResponse);

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
        $this->assertEquals((string) $playlistId, $result['playlist_id']);
        $this->assertEquals($playlistUrl, $result['playlist_url']);
    }

    public function testExportPlaylistCreatePlaylistError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist();
        $playlist->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token');

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $errorResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400): Unknown error');

        $this->soundCloudExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithSongWithoutTitleOrArtists(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';

        $song = new Song();

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($createPlaylistResponse)
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithValidData(): void
    {
        $accessToken = 'valid-access-token';
        $playlistName = 'Test Playlist';
        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken($accessToken)
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName($playlistName)
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(4))
            ->method('getValidAccessToken')
            ->with($provider)
            ->willReturn($accessToken)
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $searchTrackResponse = $this->createMock(ResponseInterface::class);
        $searchTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchTrackResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $getPlaylistResponse = $this->createMock(ResponseInterface::class);
        $getPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $getPlaylistResponse->method('toArray')->willReturn([
            'title' => $playlistName,
            'description' => 'Created with MixLink',
            'sharing' => 'private',
            'tracks' => [],
        ]);

        $putPlaylistResponse = $this->createMock(ResponseInterface::class);
        $putPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $putPlaylistResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(4))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchTrackResponse,
                $getPlaylistResponse,
                $putPlaylistResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals((string) $playlistId, $result['playlist_id']);
        $this->assertEquals($playlistUrl, $result['playlist_url']);
        $this->assertEquals(1, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
    }

    public function testExportPlaylistWithSearchTrackNotFound(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';

        $song = new Song()
            ->setTitle('Unfindable Song')
            ->setArtists('Unknown Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(5))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $emptySearchResponse = $this->createMock(ResponseInterface::class);
        $emptySearchResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $emptySearchResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $emptySearchResponse,
                $emptySearchResponse,
                $emptySearchResponse,
                $emptySearchResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithSearchTrackError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(5))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $errorSearchResponse = $this->createMock(ResponseInterface::class);
        $errorSearchResponse->method('getStatusCode')->willReturn(Response::HTTP_FORBIDDEN);
        $errorSearchResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Access denied',
            ],
        ]);

        $emptySearchResponse = $this->createMock(ResponseInterface::class);
        $emptySearchResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $emptySearchResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $errorSearchResponse,
                $emptySearchResponse,
                $emptySearchResponse,
                $emptySearchResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithAddTrackToPlaylistError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(5))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $searchTrackResponse = $this->createMock(ResponseInterface::class);
        $searchTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchTrackResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);
        $errorResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Internal server error',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchTrackResponse,
                $errorResponse,
                $errorResponse,
                $errorResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithTokenRefreshSuccess(): void
    {
        $accessToken = 'valid-access-token';
        $newAccessToken = 'refreshed-access-token';
        $playlistName = 'Test Playlist';
        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken($accessToken)
        ;

        $user = new User()->addProvider($provider);

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName($playlistName)
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(4))
            ->method('getValidAccessToken')
            ->willReturn($accessToken)
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn($newAccessToken)
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $searchTrackResponse = $this->createMock(ResponseInterface::class);
        $searchTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchTrackResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $unauthorizedResponse = $this->createMock(ResponseInterface::class);
        $unauthorizedResponse->method('getStatusCode')->willReturn(Response::HTTP_UNAUTHORIZED);
        $unauthorizedResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Invalid credentials',
            ],
        ]);

        $getPlaylistResponse = $this->createMock(ResponseInterface::class);
        $getPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $getPlaylistResponse->method('toArray')->willReturn([
            'title' => $playlistName,
            'description' => 'Created with MixLink',
            'sharing' => 'private',
            'tracks' => [],
        ]);

        $putPlaylistResponse = $this->createMock(ResponseInterface::class);
        $putPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $putPlaylistResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchTrackResponse,
                $unauthorizedResponse,
                $getPlaylistResponse,
                $putPlaylistResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals((string) $playlistId, $result['playlist_id']);
        $this->assertEquals($playlistUrl, $result['playlist_url']);
        $this->assertEquals(1, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
    }

    public function testExportPlaylistWithTokenRefreshFailure(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(5))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('refreshAccessToken')
            ->with($provider)
            ->willThrowException(new RuntimeException('Failed to refresh token'))
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $searchTrackResponse = $this->createMock(ResponseInterface::class);
        $searchTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchTrackResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $unauthorizedResponse = $this->createMock(ResponseInterface::class);
        $unauthorizedResponse->method('getStatusCode')->willReturn(Response::HTTP_UNAUTHORIZED);
        $unauthorizedResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Invalid credentials',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchTrackResponse,
                $unauthorizedResponse,
                $unauthorizedResponse,
                $unauthorizedResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithMakeAuthenticatedRequestErrorMessage(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist();
        $playlist->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $errorResponse->method('toArray')->willReturn([
            'message' => 'Direct message error',
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400): Direct message error');

        $this->soundCloudExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithSearchTrackLowScore(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(5))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $lowScoreSearchResponse = $this->createMock(ResponseInterface::class);
        $lowScoreSearchResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $lowScoreSearchResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Completely Different Song',
                'user' => ['username' => 'Different Artist'],
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $lowScoreSearchResponse,
                $lowScoreSearchResponse,
                $lowScoreSearchResponse,
                $lowScoreSearchResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithRemixTrack(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(4))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $remixSearchResponse = $this->createMock(ResponseInterface::class);
        $remixSearchResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $remixSearchResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song (Remix)',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $getPlaylistResponse = $this->createMock(ResponseInterface::class);
        $getPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $getPlaylistResponse->method('toArray')->willReturn([
            'title' => 'Test Playlist',
            'description' => 'Created with MixLink',
            'sharing' => 'private',
            'tracks' => [],
        ]);

        $putPlaylistResponse = $this->createMock(ResponseInterface::class);
        $putPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $putPlaylistResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(4))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $remixSearchResponse,
                $getPlaylistResponse,
                $putPlaylistResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(1, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
    }

    public function testExportPlaylistWithInvalidTrackId(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(5))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $invalidTrackResponse = $this->createMock(ResponseInterface::class);
        $invalidTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $invalidTrackResponse->method('toArray')->willReturn([
            [
                'id' => 'invalid_id',
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $emptySearchResponse = $this->createMock(ResponseInterface::class);
        $emptySearchResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $emptySearchResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $invalidTrackResponse,
                $emptySearchResponse,
                $emptySearchResponse,
                $emptySearchResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result['exported_tracks']);
        $this->assertEquals(1, $result['failed_tracks']);
    }

    public function testExportPlaylistWithExistingTracksInPlaylist(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;
        $existingTrackId = 555666;

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(4))
            ->method('getValidAccessToken')
            ->willReturn('token');

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $searchTrackResponse = $this->createMock(ResponseInterface::class);
        $searchTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchTrackResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $getPlaylistResponse = $this->createMock(ResponseInterface::class);
        $getPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $getPlaylistResponse->method('toArray')->willReturn([
            'title' => 'Test Playlist',
            'description' => 'Created with MixLink',
            'sharing' => 'private',
            'tracks' => [
                ['id' => $existingTrackId],
            ],
        ]);

        $putPlaylistResponse = $this->createMock(ResponseInterface::class);
        $putPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $putPlaylistResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(4))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchTrackResponse,
                $getPlaylistResponse,
                $putPlaylistResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(1, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
    }

    public function testExportPlaylistWithMissingPlaylistData(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 123456;
        $playlistUrl = 'https://soundcloud.com/user/sets/test-playlist';
        $trackId = 789012;

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(4))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
            'permalink_url' => $playlistUrl,
        ]);

        $searchTrackResponse = $this->createMock(ResponseInterface::class);
        $searchTrackResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchTrackResponse->method('toArray')->willReturn([
            [
                'id' => $trackId,
                'title' => 'Test Song',
                'user' => ['username' => 'Test Artist'],
            ],
        ]);

        $getPlaylistResponse = $this->createMock(ResponseInterface::class);
        $getPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $getPlaylistResponse->method('toArray')->willReturn([
            'tracks' => [],
        ]);

        $putPlaylistResponse = $this->createMock(ResponseInterface::class);
        $putPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $putPlaylistResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(4))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchTrackResponse,
                $getPlaylistResponse,
                $putPlaylistResponse
            )
        ;

        $result = $this->soundCloudExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(1, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
    }

    public function testExportPlaylistWithMakeAuthenticatedRequestErrorsArray(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
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
        $errorResponse->method('toArray')->willReturn([
            'error' => [
                'errors' => [
                    ['message' => 'Invalid playlist name'],
                    ['message' => 'Missing required field'],
                ],
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400):');

        $this->soundCloudExportService->exportPlaylist($playlist, $user);
    }

    public function testIsRemixOrCoverWithRemixInParentheses(): void
    {
        $reflectionClass = new ReflectionClass($this->soundCloudExportService);
        $method = $reflectionClass->getMethod('isRemixOrCover');
        $method->setAccessible(true);

        $result = $method->invoke($this->soundCloudExportService, 'Original Track (remix by Producer)');
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithEditInBrackets(): void
    {
        $reflectionClass = new ReflectionClass($this->soundCloudExportService);
        $method = $reflectionClass->getMethod('isRemixOrCover');
        $method->setAccessible(true);

        $result = $method->invoke($this->soundCloudExportService, 'Original Song [edit by Producer]');
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithVipInBrackets(): void
    {
        $reflectionClass = new ReflectionClass($this->soundCloudExportService);
        $method = $reflectionClass->getMethod('isRemixOrCover');
        $method->setAccessible(true);

        $result = $method->invoke($this->soundCloudExportService, 'Original Track [vip version]');
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithNormalTrack(): void
    {
        $reflectionClass = new ReflectionClass($this->soundCloudExportService);
        $method = $reflectionClass->getMethod('isRemixOrCover');
        $method->setAccessible(true);

        $result = $method->invoke($this->soundCloudExportService, 'Original Track by Artist');
        $this->assertFalse($result);
    }

    public function testIsRemixOrCoverWithGeneralKeyword(): void
    {
        $reflectionClass = new ReflectionClass($this->soundCloudExportService);
        $method = $reflectionClass->getMethod('isRemixOrCover');
        $method->setAccessible(true);

        $result = $method->invoke($this->soundCloudExportService, 'Song Title cover by Artist');
        $this->assertTrue($result);
    }
}
