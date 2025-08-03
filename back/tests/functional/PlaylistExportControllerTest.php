<?php

namespace App\Tests\Functional;

use App\Provider\ProviderManager;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use App\Service\Export\Model\ExportResult;
use App\Service\PlaylistExportService;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class PlaylistExportControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;
    private PlaylistRepository $playlistRepository;
    private ProviderManager|MockObject $providerManagerMock;
    private PlaylistExportService|MockObject $playlistExportServiceMock;

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/playlistExportController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->playlistRepository = static::getContainer()->get(PlaylistRepository::class);

        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        $this->playlistExportServiceMock = $this->createMock(PlaylistExportService::class);

        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);
        static::getContainer()->set(PlaylistExportService::class, $this->playlistExportServiceMock);
    }

    public function testExportPlaylistUnauthorized(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Spotify Export Playlist']);

        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testExportPlaylistToSpotifySuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-spotify@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Spotify Export Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willReturn(new ExportResult(
                playlistId: 'spotify_playlist_123',
                playlistUrl: 'https://open.spotify.com/playlist/123',
                exportedTracks: 2,
                failedTracks: 0,
                platform: 'spotify'
            ))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('spotify_playlist_123', $data['playlist_id']);
        $this->assertEquals('https://open.spotify.com/playlist/123', $data['playlist_url']);
        $this->assertEquals(2, $data['exported_tracks']);
        $this->assertEquals(0, $data['failed_tracks']);
        $this->assertEquals('spotify', $data['platform']);
    }

    public function testExportPlaylistToGoogleSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-google@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Google Export Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willReturn(new ExportResult(
                playlistId: 'google_playlist_456',
                playlistUrl: 'https://music.youtube.com/playlist?list=456',
                exportedTracks: 2,
                failedTracks: 0,
                platform: 'google'
            ))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'google_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/google');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('google_playlist_456', $data['playlist_id']);
        $this->assertEquals('https://music.youtube.com/playlist?list=456', $data['playlist_url']);
        $this->assertEquals(2, $data['exported_tracks']);
        $this->assertEquals(0, $data['failed_tracks']);
        $this->assertEquals('google', $data['platform']);
    }

    public function testExportPlaylistToSoundCloudSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-soundcloud@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'SoundCloud Export Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willReturn(new ExportResult(
                playlistId: 'soundcloud_playlist_789',
                playlistUrl: 'https://soundcloud.com/user/sets/playlist',
                exportedTracks: 1,
                failedTracks: 1,
                platform: 'soundcloud'
            ))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'soundcloud_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/soundcloud');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('soundcloud_playlist_789', $data['playlist_id']);
        $this->assertEquals('https://soundcloud.com/user/sets/playlist', $data['playlist_url']);
        $this->assertEquals(1, $data['exported_tracks']);
        $this->assertEquals(1, $data['failed_tracks']);
        $this->assertEquals('soundcloud', $data['platform']);
    }

    public function testExportPlaylistNotOwner(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-not-owner@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Other User Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'not_owner_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('playlist.export.not_owner', $data['error']);
    }

    public function testExportPlaylistNoSubscription(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-no-sub@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'No Subscription Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'no_sub_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('playlist.export.subscription_required', $data['error']);
    }

    public function testExportPlaylistInactiveSubscription(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-inactive@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Inactive Subscription Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'inactive_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('playlist.export.subscription_required', $data['error']);
    }

    public function testExportPlaylistFreeUserLimitReached(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-free@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Free User Already Exported']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'free_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('playlist.export.free_user_limit_reached', $data['error']);
    }

    public function testExportPlaylistFreeUserSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-free@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Free User Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willReturn(new ExportResult(
                playlistId: 'free_playlist_123',
                playlistUrl: 'https://open.spotify.com/playlist/free123',
                exportedTracks: 2,
                failedTracks: 0,
                platform: 'spotify'
            ))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'free_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($data['success']);

        $updatedPlaylist = $this->playlistRepository->find($playlist->getId());
        $this->assertTrue($updatedPlaylist->hasBeenExported());
    }

    public function testExportPlaylistInvalidArgumentException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-spotify@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Spotify Export Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willThrowException(new InvalidArgumentException('User is not connected to spotify'))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User is not connected to spotify', $data['error']);
    }

    public function testExportPlaylistRuntimeException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-spotify@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Spotify Export Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willThrowException(new RuntimeException('API connection failed'))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('playlist.export.failed', $data['error']);
    }

    public function testExportPlaylistGenericException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-spotify@test.fr']);
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Spotify Export Playlist']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->playlistExportServiceMock
            ->method('exportPlaylist')
            ->willThrowException(new Exception('Unexpected error'))
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_export_token'));
        $this->client->request('POST', '/api/playlist/'.$playlist->getId().'/export/spotify');

        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('playlist.export.unexpected_error', $data['error']);
    }

    public function testExportPlaylistNotFound(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'export-spotify@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'spotify_export_token'));
        $this->client->request('POST', '/api/playlist/00000000-0000-0000-0000-000000000000/export/spotify');

        $this->assertResponseStatusCodeSame(404);
    }
}
