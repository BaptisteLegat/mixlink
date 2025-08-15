<?php

namespace App\Tests\Functional;

use App\Service\CacheService;
use App\Service\Model\SpotifyTrack;
use App\Service\SpotifyService;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private SpotifyService|MockObject $spotifyServiceMock;
    private CacheService|MockObject $cacheServiceMock;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $this->spotifyServiceMock = $this->createMock(SpotifyService::class);
        static::getContainer()->set(SpotifyService::class, $this->spotifyServiceMock);

        $this->cacheServiceMock = $this->createMock(CacheService::class);
        $this->cacheServiceMock
            ->method('getCachedApiResponse')
            ->willReturnCallback(function (string $key, callable $callback, ?int $ttl = null) {
                return $callback();
            })
        ;

        static::getContainer()->set(CacheService::class, $this->cacheServiceMock);
    }

    public function testSearchMusicSuccess(): void
    {
        $mockTracks = [
            new SpotifyTrack('track1', 'Song 1', ['Artist 1'], 'https://example.com/image1.jpg', 'https://example.com/preview1.mp3'),
            new SpotifyTrack('track2', 'Song 2', ['Artist 2'], 'https://example.com/image2.jpg', 'https://example.com/preview2.mp3'),
        ];

        $this->spotifyServiceMock
            ->expects($this->once())
            ->method('searchTracks')
            ->with('Daft Punk')
            ->willReturn($mockTracks)
        ;

        $this->client->request('GET', '/api/search/music?q=Daft Punk');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $firstTrack = $data[0];
        $this->assertEquals('track1', $firstTrack['id']);
        $this->assertEquals('Song 1', $firstTrack['name']);
        $this->assertEquals(['Artist 1'], $firstTrack['artists']);
        $this->assertEquals('https://example.com/image1.jpg', $firstTrack['image']);
        $this->assertEquals('https://example.com/preview2.mp3', $data[1]['preview_url']);
    }

    public function testSearchMusicWithEmptyQuery(): void
    {
        $this->client->request('GET', '/api/search/music?q=');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('search.error.missing_query', $data['error']);
    }

    public function testSearchMusicWithWhitespaceQuery(): void
    {
        $this->client->request('GET', '/api/search/music?q=%20%20%20');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('search.error.missing_query', $data['error']);
    }

    public function testSearchMusicWithoutQueryParameter(): void
    {
        $this->client->request('GET', '/api/search/music');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('search.error.missing_query', $data['error']);
    }

    public function testSearchMusicWithSpotifyServiceException(): void
    {
        $this->spotifyServiceMock
            ->expects($this->once())
            ->method('searchTracks')
            ->with('Daft Punk')
            ->willThrowException(new Exception('Spotify API error'))
        ;

        $this->client->request('GET', '/api/search/music?q=Daft Punk');

        $this->assertResponseStatusCodeSame(500);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('search.error.unknown', $data['error']);
    }

    public function testSearchMusicWithEmptyResults(): void
    {
        $this->spotifyServiceMock
            ->expects($this->once())
            ->method('searchTracks')
            ->with('NonExistentArtist')
            ->willReturn([])
        ;

        $this->client->request('GET', '/api/search/music?q=NonExistentArtist');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testSearchMusicWithSpecialCharacters(): void
    {
        $mockTracks = [
            new SpotifyTrack('track1', 'Song with accents éèà', ['Artist with accents éèà'], 'https://example.com/image1.jpg', 'https://example.com/preview1.mp3'),
        ];

        $this->spotifyServiceMock
            ->expects($this->once())
            ->method('searchTracks')
            ->with('test')
            ->willReturn($mockTracks);

        $this->client->request('GET', '/api/search/music?q='.urlencode('test'));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('Song with accents éèà', $data[0]['name']);
        $this->assertEquals(['Artist with accents éèà'], $data[0]['artists']);
    }
}
