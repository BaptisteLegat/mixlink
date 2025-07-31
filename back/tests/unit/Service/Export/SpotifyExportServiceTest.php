<?php

namespace App\Tests\Unit\Service\Export;

use App\Entity\Playlist;
use App\Entity\Song;
use App\Entity\User;
use App\Service\Export\SpotifyExportService;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpotifyExportServiceTest extends TestCase
{
    private SpotifyExportService $spotifyExportService;
    private HttpClientInterface|MockObject $httpClientMock;
    private User|MockObject $userMock;
    private Playlist|MockObject $playlistMock;
    private Song|MockObject $songMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->playlistMock = $this->createMock(Playlist::class);
        $this->songMock = $this->createMock(Song::class);

        $this->spotifyExportService = new SpotifyExportService($this->httpClientMock);
    }

    public function testExportPlaylistWithValidData(): void
    {
        $accessToken = 'valid-access-token';
        $playlistName = 'Test Playlist';
        $spotifyUserId = 'spotify-user-123';
        $playlistId = 'playlist-123';
        $playlistUrl = 'https://open.spotify.com/playlist/123';
        $spotifyId = 'spotify-track-123';

        // Mock user provider
        $providerMock = $this->createMock(\App\Entity\Provider::class);
        $providerMock->method('getAccessToken')->willReturn($accessToken);
        $this->userMock->method('getProviderByName')->with('spotify')->willReturn($providerMock);

        // Mock playlist
        $this->playlistMock->method('getName')->willReturn($playlistName);
        $this->playlistMock->method('getSongs')->willReturn(new ArrayCollection([$this->songMock]));

        // Mock song
        $this->songMock->method('getSpotifyId')->willReturn($spotifyId);

        // Mock HTTP responses
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

        // Mock HTTP client calls
        $this->httpClientMock
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($userProfileResponse, $playlistResponse, $tracksResponse);

        $result = $this->spotifyExportService->exportPlaylist($this->playlistMock, $this->userMock);

        $this->assertEquals($playlistId, $result['playlist_id']);
        $this->assertEquals($playlistUrl, $result['playlist_url']);
        $this->assertEquals(1, $result['exported_tracks']);
        $this->assertEquals(0, $result['failed_tracks']);
    }

    public function testExportPlaylistWithUserNotConnected(): void
    {
        $this->userMock->method('getProviderByName')->with('spotify')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User is not connected to Spotify');

        $this->spotifyExportService->exportPlaylist($this->playlistMock, $this->userMock);
    }

    public function testExportPlaylistWithNoAccessToken(): void
    {
        $providerMock = $this->createMock(\App\Entity\Provider::class);
        $providerMock->method('getAccessToken')->willReturn(null);
        $this->userMock->method('getProviderByName')->with('spotify')->willReturn($providerMock);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No access token available for Spotify');

        $this->spotifyExportService->exportPlaylist($this->playlistMock, $this->userMock);
    }

    public function testGetPlatformName(): void
    {
        $this->assertEquals('spotify', $this->spotifyExportService->getPlatformName());
    }

    public function testIsUserConnectedWithValidProvider(): void
    {
        $providerMock = $this->createMock(\App\Entity\Provider::class);
        $providerMock->method('getAccessToken')->willReturn('valid-token');
        $this->userMock->method('getProviderByName')->with('spotify')->willReturn($providerMock);

        $this->assertTrue($this->spotifyExportService->isUserConnected($this->userMock));
    }

    public function testIsUserConnectedWithNoProvider(): void
    {
        $this->userMock->method('getProviderByName')->with('spotify')->willReturn(null);

        $this->assertFalse($this->spotifyExportService->isUserConnected($this->userMock));
    }

    public function testIsUserConnectedWithNoAccessToken(): void
    {
        $providerMock = $this->createMock(\App\Entity\Provider::class);
        $providerMock->method('getAccessToken')->willReturn(null);
        $this->userMock->method('getProviderByName')->with('spotify')->willReturn($providerMock);

        $this->assertFalse($this->spotifyExportService->isUserConnected($this->userMock));
    }
}
