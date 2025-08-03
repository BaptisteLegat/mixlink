<?php

namespace App\Tests\Unit\Service;

use App\Service\Model\SpotifyTrack;
use App\Service\SpotifyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpotifyServiceTest extends TestCase
{
    private HttpClientInterface|MockObject $httpClientMock;
    private CacheInterface|MockObject $cacheMock;
    private ItemInterface|MockObject $cacheItemMock;
    private ResponseInterface|MockObject $responseMock;
    private SpotifyService $spotifyService;

    private const CLIENT_ID = 'test_client_id';
    private const CLIENT_SECRET = 'test_client_secret';

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->cacheItemMock = $this->createMock(ItemInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);

        $this->spotifyService = new SpotifyService(
            $this->httpClientMock,
            $this->cacheMock,
            self::CLIENT_ID,
            self::CLIENT_SECRET
        );
    }

    public function testSearchTracksSuccess(): void
    {
        $query = 'test song';
        $token = 'test_access_token';
        $responseData = [
            'tracks' => [
                'items' => [
                    [
                        'id' => 'track_1',
                        'name' => 'Test Song 1',
                        'artists' => [
                            ['name' => 'Artist 1'],
                            ['name' => 'Artist 2'],
                        ],
                        'album' => [
                            'images' => [
                                ['url' => 'https://example.com/image1.jpg'],
                            ],
                        ],
                        'preview_url' => 'https://example.com/preview1.mp3',
                    ],
                    [
                        'id' => 'track_2',
                        'name' => 'Test Song 2',
                        'artists' => [
                            ['name' => 'Artist 3'],
                        ],
                        'album' => [
                            'images' => [
                                ['url' => 'https://example.com/image2.jpg'],
                            ],
                        ],
                        'preview_url' => null,
                    ],
                ],
            ],
        ];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.spotify.com/v1/search', [
                'query' => [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 10,
                ],
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ])
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(2, $tracks);
        $this->assertContainsOnlyInstancesOf(SpotifyTrack::class, $tracks);

        $this->assertEquals('track_1', $tracks[0]->getId());
        $this->assertEquals('Test Song 1', $tracks[0]->getName());
        $this->assertEquals(['Artist 1', 'Artist 2'], $tracks[0]->getArtists());
        $this->assertEquals('https://example.com/image1.jpg', $tracks[0]->getImage());
        $this->assertEquals('https://example.com/preview1.mp3', $tracks[0]->getPreviewUrl());

        $this->assertEquals('track_2', $tracks[1]->getId());
        $this->assertEquals('Test Song 2', $tracks[1]->getName());
        $this->assertEquals(['Artist 3'], $tracks[1]->getArtists());
        $this->assertEquals('https://example.com/image2.jpg', $tracks[1]->getImage());
        $this->assertEquals('', $tracks[1]->getPreviewUrl());
    }

    public function testSearchTracksWithEmptyResponse(): void
    {
        $query = 'nonexistent';
        $token = 'test_access_token';
        $responseData = ['tracks' => ['items' => []]];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(0, $tracks);
    }

    public function testSearchTracksWithInvalidResponseStructure(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = ['invalid' => 'structure'];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(0, $tracks);
    }

    public function testSearchTracksWithMissingTracksData(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = ['tracks' => null];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(0, $tracks);
    }

    public function testSearchTracksWithMissingItems(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = ['tracks' => ['invalid' => 'data']];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(0, $tracks);
    }

    public function testSearchTracksWithTrackMissingData(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = [
            'tracks' => [
                'items' => [
                    [
                        'id' => 'track_1',
                        'name' => 'Test Song',
                        'artists' => [
                            ['name' => 'Artist 1'],
                        ],
                        'album' => [
                            'images' => [
                                ['url' => 'https://example.com/image.jpg'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'track_2',
                        'name' => 'Test Song 2',
                        'artists' => [],
                        'album' => [
                            'images' => [],
                        ],
                    ],
                ],
            ],
        ];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(2, $tracks);
        $this->assertEquals('track_1', $tracks[0]->getId());
        $this->assertEquals('Test Song', $tracks[0]->getName());
        $this->assertEquals(['Artist 1'], $tracks[0]->getArtists());
        $this->assertEquals('https://example.com/image.jpg', $tracks[0]->getImage());

        $this->assertEquals('track_2', $tracks[1]->getId());
        $this->assertEquals('Test Song 2', $tracks[1]->getName());
        $this->assertEquals([], $tracks[1]->getArtists());
        $this->assertNull($tracks[1]->getImage());
    }

    public function testSearchTracksWithInvalidArtistsData(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = [
            'tracks' => [
                'items' => [
                    [
                        'id' => 'track_1',
                        'name' => 'Test Song',
                        'artists' => null,
                        'album' => [
                            'images' => [
                                ['url' => 'https://example.com/image.jpg'],
                            ],
                        ],
                    ],
                    [
                        'id' => 'track_2',
                        'name' => 'Test Song 2',
                        'artists' => 'invalid_string',
                        'album' => [
                            'images' => [
                                ['url' => 'https://example.com/image2.jpg'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(2, $tracks);
        $this->assertEquals('track_1', $tracks[0]->getId());
        $this->assertEquals('Test Song', $tracks[0]->getName());
        $this->assertEquals([], $tracks[0]->getArtists());
        $this->assertEquals('https://example.com/image.jpg', $tracks[0]->getImage());

        $this->assertEquals('track_2', $tracks[1]->getId());
        $this->assertEquals('Test Song 2', $tracks[1]->getName());
        $this->assertEquals([], $tracks[1]->getArtists());
        $this->assertEquals('https://example.com/image2.jpg', $tracks[1]->getImage());
    }

    public function testSearchTracksWithMissingAlbumData(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = [
            'tracks' => [
                'items' => [
                    [
                        'id' => 'track_1',
                        'name' => 'Test Song',
                        'artists' => [
                            ['name' => 'Artist 1'],
                        ],
                        'album' => null,
                    ],
                    [
                        'id' => 'track_2',
                        'name' => 'Test Song 2',
                        'artists' => [
                            ['name' => 'Artist 2'],
                        ],
                        'album' => 'invalid_string',
                    ],
                ],
            ],
        ];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(2, $tracks);
        $this->assertEquals('track_1', $tracks[0]->getId());
        $this->assertEquals('Test Song', $tracks[0]->getName());
        $this->assertEquals(['Artist 1'], $tracks[0]->getArtists());
        $this->assertNull($tracks[0]->getImage());

        $this->assertEquals('track_2', $tracks[1]->getId());
        $this->assertEquals('Test Song 2', $tracks[1]->getName());
        $this->assertEquals(['Artist 2'], $tracks[1]->getArtists());
        $this->assertNull($tracks[1]->getImage());
    }

    public function testSearchTracksWithMissingImagesData(): void
    {
        $query = 'test';
        $token = 'test_access_token';
        $responseData = [
            'tracks' => [
                'items' => [
                    [
                        'id' => 'track_1',
                        'name' => 'Test Song',
                        'artists' => [
                            ['name' => 'Artist 1'],
                        ],
                        'album' => [
                            'images' => null,
                        ],
                    ],
                    [
                        'id' => 'track_2',
                        'name' => 'Test Song 2',
                        'artists' => [
                            ['name' => 'Artist 2'],
                        ],
                        'album' => [
                            'images' => 'invalid_string',
                        ],
                    ],
                ],
            ],
        ];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks($query);

        $this->assertCount(2, $tracks);
        $this->assertEquals('track_1', $tracks[0]->getId());
        $this->assertEquals('Test Song', $tracks[0]->getName());
        $this->assertEquals(['Artist 1'], $tracks[0]->getArtists());
        $this->assertNull($tracks[0]->getImage());

        $this->assertEquals('track_2', $tracks[1]->getId());
        $this->assertEquals('Test Song 2', $tracks[1]->getName());
        $this->assertEquals(['Artist 2'], $tracks[1]->getArtists());
        $this->assertNull($tracks[1]->getImage());
    }

    public function testSearchTracksWithHttpErrorThrowsException(): void
    {
        $query = 'test';
        $token = 'test_access_token';

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($token)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_BAD_REQUEST)
        ;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The Spotify API request failed');

        $this->spotifyService->searchTracks($query);
    }

    public function testGetAppTokenWithCacheHit(): void
    {
        $cachedToken = 'cached_token';
        $responseData = ['tracks' => ['items' => []]];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturn($cachedToken)
        ;

        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock)
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData)
        ;

        $tracks = $this->spotifyService->searchTracks('test');

        $this->assertIsArray($tracks);
    }

    public function testGetAppTokenWithCacheMiss(): void
    {
        $tokenResponse = [
            'access_token' => 'new_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];
        $searchResponseData = ['tracks' => ['items' => []]];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturnCallback(function ($key, $callback) use ($tokenResponse) {
                $this->cacheItemMock->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3500)
                ;

                $tokenResponseMock = $this->createMock(ResponseInterface::class);
                $tokenResponseMock->expects($this->once())
                    ->method('getStatusCode')
                    ->willReturn(Response::HTTP_OK)
                ;
                $tokenResponseMock->expects($this->once())
                    ->method('toArray')
                    ->with(false)
                    ->willReturn($tokenResponse)
                ;

                $this->httpClientMock->expects($this->exactly(2))
                    ->method('request')
                    ->willReturnOnConsecutiveCalls($tokenResponseMock, $this->responseMock)
                ;

                return $callback($this->cacheItemMock);
            })
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($searchResponseData)
        ;

        $tracks = $this->spotifyService->searchTracks('test');

        $this->assertIsArray($tracks);
    }

    public function testGetAppTokenWithTokenRequestFailure(): void
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturnCallback(function ($key, $callback) {
                $this->cacheItemMock->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3500)
                ;

                $response = $this->createMock(ResponseInterface::class);
                $response->expects($this->once())
                    ->method('getStatusCode')
                    ->willReturn(Response::HTTP_UNAUTHORIZED)
                ;

                $this->httpClientMock->expects($this->once())
                    ->method('request')
                    ->willReturn($response)
                ;

                $this->expectException(RuntimeException::class);
                $this->expectExceptionMessage('The Spotify token request failed');

                return $callback($this->cacheItemMock);
            })
        ;

        $this->spotifyService->searchTracks('test');
    }

    public function testGetAppTokenWithInvalidTokenResponse(): void
    {
        $tokenResponse = [
            'invalid' => 'response',
        ];
        $searchResponseData = ['tracks' => ['items' => []]];

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('spotify_app_token')
            ->willReturnCallback(function ($key, $callback) use ($tokenResponse) {
                $this->cacheItemMock->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3500)
                ;

                $tokenResponseMock = $this->createMock(ResponseInterface::class);
                $tokenResponseMock->expects($this->once())
                    ->method('getStatusCode')
                    ->willReturn(Response::HTTP_OK)
                ;
                $tokenResponseMock->expects($this->once())
                    ->method('toArray')
                    ->with(false)
                    ->willReturn($tokenResponse)
                ;

                $this->httpClientMock->expects($this->exactly(2))
                    ->method('request')
                    ->willReturnOnConsecutiveCalls($tokenResponseMock, $this->responseMock)
                ;

                return $callback($this->cacheItemMock);
            })
        ;

        $this->responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK)
        ;

        $this->responseMock->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($searchResponseData)
        ;

        $tracks = $this->spotifyService->searchTracks('test');

        $this->assertIsArray($tracks);
    }
}
