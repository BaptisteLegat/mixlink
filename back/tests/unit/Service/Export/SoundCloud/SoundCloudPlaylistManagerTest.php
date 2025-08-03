<?php

namespace App\Tests\Unit\Service\Export\SoundCloud;

use App\Entity\Provider;
use App\Service\Export\SoundCloud\SoundCloudApiClient;
use App\Service\Export\SoundCloud\SoundCloudPlaylistManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SoundCloudPlaylistManagerTest extends TestCase
{
    private SoundCloudPlaylistManager $playlistManager;
    private SoundCloudApiClient|MockObject $apiClientMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(SoundCloudApiClient::class);
        $this->playlistManager = new SoundCloudPlaylistManager($this->apiClientMock);
    }

    public function testCreatePlaylist(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $expectedPlaylistData = [
            'id' => 123,
            'permalink_url' => 'https://soundcloud.com/user/playlist-123',
        ];

        $this->apiClientMock
            ->expects($this->once())
            ->method('makeRequest')
            ->with(
                $provider,
                'POST',
                'https://api.soundcloud.com/playlists',
                [
                    'json' => [
                        'playlist' => [
                            'title' => 'My Test Playlist',
                            'description' => 'Created with mixlink',
                            'sharing' => 'private',
                        ],
                    ],
                ]
            )
            ->willReturn($expectedPlaylistData);

        $result = $this->playlistManager->createPlaylist($provider, 'My Test Playlist');

        $this->assertEquals($expectedPlaylistData, $result);
    }

    public function testAddTrackToPlaylistSuccess(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        // Mock getting current playlist data
        $this->apiClientMock
            ->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturnCallback(function ($provider, $method, $url, $options = []) {
                if ('GET' === $method) {
                    return [
                        'title' => 'My Playlist',
                        'description' => 'Created with mixlink',
                        'sharing' => 'private',
                        'tracks' => [
                            ['id' => 100],
                            ['id' => 200],
                        ],
                    ];
                } else { // PUT request
                    return ['success' => true];
                }
            });

        $this->playlistManager->addTrackToPlaylist($provider, 123, 300);

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function testAddTrackToPlaylistWithEmptyTracks(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        // Mock getting current playlist data with no tracks
        $this->apiClientMock
            ->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturnCallback(function ($provider, $method, $url, $options = []) {
                if ('GET' === $method) {
                    return [
                        'title' => 'Empty Playlist',
                        'description' => 'Created with mixlink',
                        'sharing' => 'private',
                        'tracks' => [],
                    ];
                } else { // PUT request
                    $expectedPutData = [
                        'playlist' => [
                            'title' => 'Empty Playlist',
                            'description' => 'Created with mixlink',
                            'sharing' => 'private',
                            'tracks' => [
                                ['id' => 300],
                            ],
                        ],
                    ];
                    $this->assertEquals($expectedPutData, $options['json']);

                    return ['success' => true];
                }
            });

        $this->playlistManager->addTrackToPlaylist($provider, 123, 300);

        $this->assertTrue(true);
    }

    public function testAddTrackToPlaylistWithMissingPlaylistData(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        // Mock getting current playlist data with missing fields
        $this->apiClientMock
            ->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturnCallback(function ($provider, $method, $url, $options = []) {
                if ('GET' === $method) {
                    return []; // Empty response
                } else { // PUT request
                    $expectedPutData = [
                        'playlist' => [
                            'title' => 'mixlink Playlist',
                            'description' => 'Created with mixlink',
                            'sharing' => 'private',
                            'tracks' => [
                                ['id' => 300],
                            ],
                        ],
                    ];
                    $this->assertEquals($expectedPutData, $options['json']);

                    return ['success' => true];
                }
            });

        $this->playlistManager->addTrackToPlaylist($provider, 123, 300);

        $this->assertTrue(true);
    }

    public function testAddTrackToPlaylistWithInvalidTracks(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        // Mock getting current playlist data with invalid track data
        $this->apiClientMock
            ->expects($this->exactly(2))
            ->method('makeRequest')
            ->willReturnCallback(function ($provider, $method, $url, $options = []) {
                if ('GET' === $method) {
                    return [
                        'title' => 'My Playlist',
                        'tracks' => [
                            ['invalid' => 'track'], // No id field
                            ['id' => 200],
                            'invalid_track_format',
                        ],
                    ];
                } else { // PUT request
                    $expectedPutData = [
                        'playlist' => [
                            'title' => 'My Playlist',
                            'description' => 'Created with mixlink',
                            'sharing' => 'private',
                            'tracks' => [
                                ['id' => 200], // Only valid track kept
                                ['id' => 300],  // New track added
                            ],
                        ],
                    ];
                    $this->assertEquals($expectedPutData, $options['json']);

                    return ['success' => true];
                }
            });

        $this->playlistManager->addTrackToPlaylist($provider, 123, 300);

        $this->assertTrue(true);
    }

    public function testAddTrackToPlaylistRetryOnFailure(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $callCount = 0;
        $this->apiClientMock
            ->method('makeRequest')
            ->willReturnCallback(function ($provider, $method, $url, $options = []) use (&$callCount) {
                if ('GET' === $method) {
                    return [
                        'title' => 'My Playlist',
                        'tracks' => [],
                    ];
                } else { // PUT request
                    ++$callCount;
                    if ($callCount < 3) {
                        throw new RuntimeException('Temporary API error');
                    }

                    return ['success' => true];
                }
            });

        $this->playlistManager->addTrackToPlaylist($provider, 123, 300);

        $this->assertEquals(3, $callCount); // Should have retried 3 times
    }

    public function testAddTrackToPlaylistMaxRetriesExceeded(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->apiClientMock
            ->method('makeRequest')
            ->willReturnCallback(function ($provider, $method, $url, $options = []) {
                if ('GET' === $method) {
                    return [
                        'title' => 'My Playlist',
                        'tracks' => [],
                    ];
                } else { // PUT request always fails
                    throw new RuntimeException('Persistent API error');
                }
            });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Persistent API error');

        $this->playlistManager->addTrackToPlaylist($provider, 123, 300);
    }
}
