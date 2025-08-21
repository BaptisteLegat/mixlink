<?php

namespace App\Tests\Unit\Service\Export;

use App\Entity\Playlist;
use App\Entity\Provider;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\GoogleExportService;
use App\Service\OAuthTokenManager;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GoogleExportServiceTest extends TestCase
{
    private GoogleExportService $googleExportService;
    private HttpClientInterface|MockObject $httpClientMock;
    private OAuthTokenManager|MockObject $tokenManagerMock;
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->tokenManagerMock = $this->createMock(OAuthTokenManager::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->googleExportService = new GoogleExportService(
            $this->httpClientMock,
            $this->tokenManagerMock,
            $this->loggerMock
        );
    }

    public function testExportPlaylistWithValidData(): void
    {
        $accessToken = 'valid-access-token';
        $playlistName = 'Test Playlist';
        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $playlistUrl = 'https://www.youtube.com/playlist?list='.$playlistId;
        $videoId = 'dQw4w9WgXcQ';

        $provider = new Provider()
            ->setName('google')
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
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId],
                    'snippet' => ['title' => 'Test Song'],
                ],
            ],
        ]);

        $addVideoResponse = $this->createMock(ResponseInterface::class);
        $addVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $addVideoResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($createPlaylistResponse, $searchVideoResponse, $addVideoResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals($playlistId, $result->playlistId);
        $this->assertEquals($playlistUrl, $result->playlistUrl);
        $this->assertEquals(1, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $user = new User();
        $playlist = new Playlist()->setName('Test Playlist');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to Google');

        $this->googleExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('google');

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

        $this->googleExportService->exportPlaylist($playlist, $user);
    }

    public function testIsUserConnectedWithValidProvider(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('valid-token')
        ;

        $user = new User()->addProvider($provider);

        $this->assertTrue($this->googleExportService->isUserConnected($user));
    }

    public function testIsUserConnectedWithNoProvider(): void
    {
        $this->assertFalse($this->googleExportService->isUserConnected(new User()));
    }

    public function testIsUserConnectedWithNoAccessToken(): void
    {
        $provider = new Provider()->setName('google');

        $user = new User()->addProvider($provider);

        $this->assertFalse($this->googleExportService->isUserConnected($user));
    }

    public function testExportPlaylistCreatePlaylistError(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $errorResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Bad Request',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('YouTube API request failed (400): Bad Request');

        $this->googleExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithEmptyPlaylist(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $playlist = new Playlist()->setName('Empty Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($createPlaylistResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithSongWithoutTitleOrArtists(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song();

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($createPlaylistResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithVideoNotFound(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
            ->setTitle('Unfindable Song')
            ->setArtists('Unknown Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($createPlaylistResponse, $searchVideoResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithAddVideoToPlaylistError(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId = 'dQw4w9WgXcQ';

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
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId],
                    'snippet' => ['title' => 'Test Song'],
                ],
            ],
        ]);

        $addVideoErrorResponse = $this->createMock(ResponseInterface::class);
        $addVideoErrorResponse->method('getStatusCode')->willReturn(Response::HTTP_CONFLICT);
        $addVideoErrorResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Conflict: Video already exists in playlist',
            ],
        ]);

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('YouTube API conflict (409)'))
        ;

        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchVideoResponse,
                $addVideoErrorResponse
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(1, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithSearchVideoError(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        // Mock search video response avec erreur 403 (accès refusé)
        $searchVideoErrorResponse = $this->createMock(ResponseInterface::class);
        $searchVideoErrorResponse->method('getStatusCode')->willReturn(Response::HTTP_FORBIDDEN);
        $searchVideoErrorResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Access denied',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($createPlaylistResponse, $searchVideoErrorResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithTokenRefreshSuccess(): void
    {
        $accessToken = 'valid-access-token';
        $newAccessToken = 'refreshed-access-token';
        $playlistName = 'Test Playlist';
        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId = 'dQw4w9WgXcQ';

        $provider = new Provider()
            ->setName('google')
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
            ->method('hasRefreshToken')
            ->with($provider)
            ->willReturn(true)
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn($newAccessToken)
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId],
                    'snippet' => ['title' => 'Test Song'],
                ],
            ],
        ]);

        $unauthorizedResponse = $this->createMock(ResponseInterface::class);
        $unauthorizedResponse->method('getStatusCode')->willReturn(Response::HTTP_UNAUTHORIZED);
        $unauthorizedResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Invalid credentials',
            ],
        ]);

        $successResponse = $this->createMock(ResponseInterface::class);
        $successResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $successResponse->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(4))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchVideoResponse,
                $unauthorizedResponse,
                $successResponse
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals($playlistId, $result->playlistId);
        $this->assertEquals('https://www.youtube.com/playlist?list='.$playlistId, $result->playlistUrl);
        $this->assertEquals(1, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithTokenRefreshFailure(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId = 'dQw4w9WgXcQ';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(6))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('hasRefreshToken')
            ->with($provider)
            ->willReturn(true)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('refreshAccessToken')
            ->with($provider)
            ->willThrowException(new RuntimeException('Failed to refresh token'))
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId],
                    'snippet' => ['title' => 'Test Song'],
                ],
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
                $searchVideoResponse,
                $unauthorizedResponse,
                $unauthorizedResponse,
                $unauthorizedResponse
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithNoRefreshToken(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId = 'dQw4w9WgXcQ';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(6))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('hasRefreshToken')
            ->with($provider)
            ->willReturn(false)
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId],
                    'snippet' => ['title' => 'Test Song'],
                ],
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
                $searchVideoResponse,
                $unauthorizedResponse,
                $unauthorizedResponse,
                $unauthorizedResponse
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithMultipleSongsAndMixedResults(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId1 = 'dQw4w9WgXcQ';
        $videoId2 = 'abc123defg';

        $song1 = new Song()
            ->setTitle('Song 1')
            ->setArtists('Artist 1')
        ;

        $song2 = new Song()
            ->setTitle('Song 2')
            ->setArtists('Artist 2')
        ;

        $song3 = new Song()
            ->setTitle('Song 3')
            ->setArtists('Artist 3')
        ;

        $playlist = new Playlist()
            ->setName('Test ')
            ->addSong($song1)
            ->addSong($song2)
            ->addSong($song3)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(7))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse1 = $this->createMock(ResponseInterface::class);
        $searchVideoResponse1->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse1->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId1],
                    'snippet' => ['title' => 'Song 1'],
                ],
            ],
        ]);

        $searchVideoResponse2 = $this->createMock(ResponseInterface::class);
        $searchVideoResponse2->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse2->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId2],
                    'snippet' => ['title' => 'Song 2'],
                ],
            ],
        ]);

        $searchVideoResponse3 = $this->createMock(ResponseInterface::class);
        $searchVideoResponse3->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse3->method('toArray')->willReturn([
            'items' => [],
        ]);

        $addVideoResponse1 = $this->createMock(ResponseInterface::class);
        $addVideoResponse1->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $addVideoResponse1->method('toArray')->willReturn([]);

        $addVideoResponse2 = $this->createMock(ResponseInterface::class);
        $addVideoResponse2->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $addVideoResponse2->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(6))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchVideoResponse1,
                $addVideoResponse1,
                $searchVideoResponse2,
                $addVideoResponse2,
                $searchVideoResponse3
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(2, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithAddVideoHttpForbiddenError(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId = 'dQw4w9WgXcQ';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(6))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId],
                    'snippet' => ['title' => 'Test Song'],
                ],
            ],
        ]);

        $forbiddenResponse = $this->createMock(ResponseInterface::class);
        $forbiddenResponse->method('getStatusCode')->willReturn(Response::HTTP_FORBIDDEN);
        $forbiddenResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Access denied for this resource',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchVideoResponse,
                $forbiddenResponse,
                $forbiddenResponse,
                $forbiddenResponse
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithSearchVideoErrorResponse(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_INTERNAL_SERVER_ERROR);
        $errorResponse->method('toArray')->willReturn([
            'error' => [
                'message' => 'Internal server error',
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($createPlaylistResponse, $errorResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithSearchVideoMissingItems(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'pageInfo' => ['totalResults' => 0],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($createPlaylistResponse, $searchVideoResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWithSearchVideoMissingVideoId(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
            ->setTitle('Test Song')
            ->setArtists('Test Artist')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(3))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse = $this->createMock(ResponseInterface::class);
        $searchVideoResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['kind' => 'youtube#video'],
                    'snippet' => ['title' => 'Test Song'],
                ],
            ],
        ]);

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($createPlaylistResponse, $searchVideoResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
        $this->assertEquals('google', $result->platform);
    }

    public function testExportPlaylistWith401ErrorAndRetryThrowsGoogleApiException(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('old-token')
            ->setRefreshToken('refresh-token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->with($provider)
            ->willReturn('old-token')
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('hasRefreshToken')
            ->with($provider)
            ->willReturn(true)
        ;

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn('new-token')
        ;

        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function ($method, $url, $options) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    throw new RuntimeException('YouTube API authentication failed (401): Unauthorized');
                } else {
                    $response = $this->createMock(ResponseInterface::class);
                    $response->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
                    $response->method('toArray')->with(false)->willReturn([
                        'error' => ['message' => 'Invalid playlist data'],
                    ]);

                    return $response;
                }
            })
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh token and retry request: YouTube API request failed (400): Invalid playlist data');

        $this->googleExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithErrorMissingMessage(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $errorResponse->method('toArray')->willReturn([
            'error' => [
                'errors' => [
                    ['reason' => 'invalid', 'message' => 'Invalid parameter'],
                ],
            ],
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('YouTube API request failed (400): [{"reason":"invalid","message":"Invalid parameter"}]');

        $this->googleExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithErrorNoErrorStructure(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlist = new Playlist()->setName('Test Playlist');

        $this->tokenManagerMock
            ->expects($this->exactly(2))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $errorResponse->method('toArray')->willReturn([
            'status' => 'error',
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($errorResponse)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('YouTube API request failed (400): Unknown error');

        $this->googleExportService->exportPlaylist($playlist, $user);
    }

    public function testExportPlaylistWithSongMissingTitle(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
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

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($createPlaylistResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
    }

    public function testExportPlaylistWithSongMissingArtists(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';

        $song = new Song()
            ->setTitle('Test Song')
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

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->willReturn($createPlaylistResponse)
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(0, $result->exportedTracks);
        $this->assertEquals(1, $result->failedTracks);
    }

    public function testExportPlaylistWithMultipleSongsUsesDelay(): void
    {
        $provider = new Provider()
            ->setName('google')
            ->setAccessToken('token')
        ;

        $user = new User()->addProvider($provider);

        $playlistId = 'PLrAVdGVsb4Q1234567890';
        $videoId1 = 'dQw4w9WgXcQ';
        $videoId2 = 'abc123defg';

        $song1 = new Song()
            ->setTitle('Song 1')
            ->setArtists('Artist 1')
        ;

        $song2 = new Song()
            ->setTitle('Song 2')
            ->setArtists('Artist 2')
        ;

        $playlist = new Playlist()
            ->setName('Test Playlist')
            ->addSong($song1)
            ->addSong($song2)
        ;

        $this->tokenManagerMock
            ->expects($this->exactly(6))
            ->method('getValidAccessToken')
            ->willReturn('token')
        ;

        $createPlaylistResponse = $this->createMock(ResponseInterface::class);
        $createPlaylistResponse->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $createPlaylistResponse->method('toArray')->willReturn([
            'id' => $playlistId,
        ]);

        $searchVideoResponse1 = $this->createMock(ResponseInterface::class);
        $searchVideoResponse1->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse1->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId1],
                    'snippet' => ['title' => 'Song 1'],
                ],
            ],
        ]);

        $searchVideoResponse2 = $this->createMock(ResponseInterface::class);
        $searchVideoResponse2->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $searchVideoResponse2->method('toArray')->willReturn([
            'items' => [
                [
                    'id' => ['videoId' => $videoId2],
                    'snippet' => ['title' => 'Song 2'],
                ],
            ],
        ]);

        $addVideoResponse1 = $this->createMock(ResponseInterface::class);
        $addVideoResponse1->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $addVideoResponse1->method('toArray')->willReturn([]);

        $addVideoResponse2 = $this->createMock(ResponseInterface::class);
        $addVideoResponse2->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $addVideoResponse2->method('toArray')->willReturn([]);

        $this->httpClientMock
            ->expects($this->exactly(5))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $createPlaylistResponse,
                $searchVideoResponse1,
                $addVideoResponse1,
                $searchVideoResponse2,
                $addVideoResponse2
            )
        ;

        $result = $this->googleExportService->exportPlaylist($playlist, $user);

        $this->assertEquals(2, $result->exportedTracks);
        $this->assertEquals(0, $result->failedTracks);
    }
}
