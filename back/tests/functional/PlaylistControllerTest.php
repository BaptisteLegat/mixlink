<?php

namespace App\Tests\Functional;

use App\Playlist\PlaylistManager;
use App\Provider\ProviderManager;
use App\Repository\PlaylistRepository;
use App\Repository\UserRepository;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class PlaylistControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;
    private PlaylistRepository $playlistRepository;
    private ProviderManager|MockObject $providerManagerMock;

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/playlistController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->playlistRepository = static::getContainer()->get(PlaylistRepository::class);

        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);
    }

    public function testGetPlaylistSuccess(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 1']);
        $this->assertNotNull($playlist);

        $this->client->request('GET', '/api/playlist/'.$playlist->getId()->toRfc4122());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Playlist 1', $data['name']);
        $this->assertArrayHasKey('songs', $data);
        $this->assertCount(2, $data['songs']);
        $this->assertEquals('spotify_song_test_1', $data['songs'][0]['spotifyId']);
        $this->assertEquals('Test Song 1', $data['songs'][0]['title']);
    }

    public function testGetPlaylistNotFound(): void
    {
        $this->client->request('GET', '/api/playlist/00000000-0000-0000-0000-000000000000');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetPlaylistEmpty(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $this->client->request('GET', '/api/playlist/'.$playlist->getId()->toRfc4122());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Playlist 3', $data['name']);
        $this->assertArrayHasKey('songs', $data);
        $this->assertEmpty($data['songs']);
    }

    public function testAddSongSuccess(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $songData = [
            'spotifyId' => 'spotify_song_test_4',
            'title' => 'Test Song 4',
            'artists' => 'Test Artist 4',
            'image' => 'https://example.com/image4.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('song', $data);
        $this->assertEquals('spotify_song_test_4', $data['song']['spotifyId']);
        $this->assertEquals('Test Song 4', $data['song']['title']);
    }

    public function testAddSongWithInvalidData(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $songData = [
            'spotifyId' => '',
            'title' => 'Test Song',
            'artists' => 'Test Artist',
            'image' => 'https://example.com/image.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertIsArray($data['errors']);
        $this->assertNotEmpty($data['errors']);
    }

    public function testAddSongWithMissingFields(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $songData = [
            'spotifyId' => 'spotify_song_test_4',
            'title' => 'Test Song 4',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertIsArray($data['errors']);
        $this->assertNotEmpty($data['errors']);
    }

    public function testAddSongToFullPlaylistPremium(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Full Playlist Premium']);
        $this->assertNotNull($playlist);

        $this->assertEquals(30, $playlist->getSongs()->count());

        $songData = [
            'spotifyId' => 'spotify_song_test_extra',
            'title' => 'Extra Song',
            'artists' => 'Extra Artist',
            'image' => 'https://example.com/extra.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('song', $data);

        $this->assertEquals('spotify_song_test_extra', $data['song']['spotifyId']);
        $this->assertEquals('Extra Song', $data['song']['title']);
    }

    public function testAddSongToFullPlaylistFree(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Full Playlist Free']);
        $this->assertNotNull($playlist);

        $this->assertEquals(30, $playlist->getSongs()->count());

        $songData = [
            'spotifyId' => 'spotify_song_test_extra',
            'title' => 'Extra Song',
            'artists' => 'Extra Artist',
            'image' => 'https://example.com/extra.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('playlist.add_song.limit_reached', $data['error']);
    }

    public function testAddSongWithInvalidArgumentException(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $playlistManagerMock = $this->createMock(PlaylistManager::class);
        $playlistManagerMock
            ->method('addSongToPlaylist')
            ->willThrowException(new \InvalidArgumentException('Song already in playlist'))
        ;
        static::getContainer()->set(PlaylistManager::class, $playlistManagerMock);

        $songData = [
            'spotifyId' => 'spotify_song_test_4',
            'title' => 'Test Song 4',
            'artists' => 'Test Artist 4',
            'image' => 'https://example.com/image4.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Song already in playlist', $data['error']);
    }

    public function testAddSongWithException(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $playlistManagerMock = $this->createMock(PlaylistManager::class);
        $playlistManagerMock
            ->method('addSongToPlaylist')
            ->willThrowException(new Exception('Test exception'))
        ;
        static::getContainer()->set(PlaylistManager::class, $playlistManagerMock);

        $songData = [
            'spotifyId' => 'spotify_song_test_4',
            'title' => 'Test Song 4',
            'artists' => 'Test Artist 4',
            'image' => 'https://example.com/image4.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('playlist.add_song.error', $data['error']);
    }

    public function testRemoveSongSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'playlist-user@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 1']);
        $this->assertNotNull($playlist);

        $this->client->request(
            'DELETE',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/remove-song/spotify_song_test_1'
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testRemoveSongUnauthorized(): void
    {
        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 1']);
        $this->assertNotNull($playlist);

        $this->client->request(
            'DELETE',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/remove-song/spotify_song_test_1'
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRemoveSongNotFound(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'playlist-user@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 1']);
        $this->assertNotNull($playlist);

        $playlistManagerMock = $this->createMock(PlaylistManager::class);
        $playlistManagerMock
            ->method('removeSongFromPlaylist')
            ->willThrowException(new Exception('Song not found'))
        ;
        static::getContainer()->set(PlaylistManager::class, $playlistManagerMock);

        $this->client->request(
            'DELETE',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/remove-song/nonexistent_song'
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('playlist.remove_song.error', $data['error']);
    }

    public function testRemoveSongWithException(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'playlist-user@test.fr']);
        $provider = $user->getProviders()->first();
        $this->providerManagerMock->method('findByAccessToken')->willReturn($user);
        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', $provider->getAccessToken()));

        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 1']);
        $this->assertNotNull($playlist);

        $playlistManagerMock = $this->createMock(PlaylistManager::class);
        $playlistManagerMock
            ->method('removeSongFromPlaylist')
            ->willThrowException(new Exception('Test exception'))
        ;
        static::getContainer()->set(PlaylistManager::class, $playlistManagerMock);

        $this->client->request(
            'DELETE',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/remove-song/spotify_song_test_1'
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('playlist.remove_song.error', $data['error']);
    }

    public function testAddSongToPlaylistWithNoSubscription(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'playlist-user@test.fr']);
        $user->setSubscription(null);

        $playlist = $this->playlistRepository->findOneBy(['name' => 'Test Playlist 3']);
        $this->assertNotNull($playlist);

        $songData = [
            'spotifyId' => 'spotify_song_test_4',
            'title' => 'Test Song 4',
            'artists' => 'Test Artist 4',
            'image' => 'https://example.com/image4.jpg',
        ];

        $this->client->request(
            'POST',
            '/api/playlist/'.$playlist->getId()->toRfc4122().'/add-song',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($songData)
        );

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('playlist.add_song.no_subscription', $data['error']);
    }
}
