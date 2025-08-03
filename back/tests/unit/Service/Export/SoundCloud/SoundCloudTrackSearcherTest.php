<?php

namespace App\Tests\Unit\Service\Export\SoundCloud;

use App\Entity\Provider;
use App\Service\Export\SoundCloud\SoundCloudApiClient;
use App\Service\Export\SoundCloud\SoundCloudTrackSearcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class SoundCloudTrackSearcherTest extends TestCase
{
    private SoundCloudTrackSearcher $trackSearcher;
    private SoundCloudApiClient|MockObject $apiClientMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(SoundCloudApiClient::class);
        $this->trackSearcher = new SoundCloudTrackSearcher($this->apiClientMock);
    }

    public function testSearchTrackSuccess(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $mockTracks = [
            [
                'id' => 123,
                'title' => 'Test Song',
                'user' => ['username' => 'test-artist'],
            ],
        ];

        $this->apiClientMock
            ->expects($this->once())
            ->method('makeRequest')
            ->with(
                $provider,
                'GET',
                'https://api.soundcloud.com/tracks',
                [
                    'query' => [
                        'q' => 'Test Song test-artist',
                        'limit' => 15,
                        'filter' => 'public',
                        'order' => 'hotness',
                    ],
                ]
            )
            ->willReturn($mockTracks);

        $result = $this->trackSearcher->searchTrack($provider, 'Test Song', 'test-artist');

        $this->assertEquals(123, $result);
    }

    public function testSearchTrackNotFound(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->apiClientMock
            ->method('makeRequest')
            ->willReturn([]); // Empty results

        $result = $this->trackSearcher->searchTrack($provider, 'Non-existent Song', 'Unknown Artist');

        $this->assertNull($result);
    }

    public function testSearchTrackWithLowScore(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $mockTracks = [
            [
                'id' => 123,
                'title' => 'completely different song',
                'user' => ['username' => 'different-artist'],
            ],
        ];

        $this->apiClientMock
            ->method('makeRequest')
            ->willReturn($mockTracks);

        $result = $this->trackSearcher->searchTrack($provider, 'Test Song', 'test-artist');

        $this->assertNull($result); // Should return null due to low score
    }

    public function testSearchTrackWithMultipleQueries(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $callCount = 0;
        $this->apiClientMock
            ->method('makeRequest')
            ->willReturnCallback(function () use (&$callCount) {
                ++$callCount;
                if (2 === $callCount) { // Second query finds a match
                    return [
                        [
                            'id' => 456,
                            'title' => 'Party All The Time',
                            'user' => ['username' => 'test-artist'],
                        ],
                    ];
                }

                return []; // First query returns empty
            });

        $result = $this->trackSearcher->searchTrack($provider, 'PATT (Party All The Time)', 'test-artist');

        $this->assertEquals(456, $result);
        $this->assertEquals(2, $callCount); // Should have tried multiple queries
    }

    public function testSearchTrackWithApiError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->apiClientMock
            ->method('makeRequest')
            ->willThrowException(new RuntimeException('API Error'));

        $result = $this->trackSearcher->searchTrack($provider, 'Test Song', 'test-artist');

        $this->assertNull($result); // Should handle errors gracefully
    }

    public function testIsRemixOrCoverWithRemixInParentheses(): void
    {
        $result = $this->callPrivateMethod('isRemixOrCover', ['Test Song (Remix)']);
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithEditInBrackets(): void
    {
        $result = $this->callPrivateMethod('isRemixOrCover', ['Test Song [Edit]']);
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithVipInBrackets(): void
    {
        $result = $this->callPrivateMethod('isRemixOrCover', ['Test Song [VIP]']);
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithNormalTrack(): void
    {
        $result = $this->callPrivateMethod('isRemixOrCover', ['Normal Song']);
        $this->assertFalse($result);
    }

    public function testIsRemixOrCoverWithGeneralKeyword(): void
    {
        $result = $this->callPrivateMethod('isRemixOrCover', ['Test Song cover']);
        $this->assertTrue($result);
    }

    public function testIsRemixOrCoverWithRemixAtEnd(): void
    {
        $result = $this->callPrivateMethod('isRemixOrCover', ['Artist Name remix']);
        $this->assertTrue($result);
    }

    public function testExtractTitleWithParenthesesWithParentheses(): void
    {
        $result = $this->callPrivateMethod('extractTitleWithParentheses', ['PATT (Party All The Time)']);
        $this->assertEquals('Party All The Time', $result);
    }

    public function testExtractTitleWithParenthesesWithoutParentheses(): void
    {
        $result = $this->callPrivateMethod('extractTitleWithParentheses', ['Regular Title']);
        $this->assertEquals('Regular Title', $result);
    }

    public function testFindBestMatchWithTitleWithParentheses(): void
    {
        $tracks = [
            [
                'id' => 123,
                'title' => 'party all the time',
                'user' => ['username' => 'test-artist'],
            ],
        ];

        $result = $this->callPrivateMethod('findBestMatch', [
            $tracks,
            'patt',
            'test-artist',
            'party all the time',
        ]);

        $this->assertEquals(123, $result);
    }

    public function testFindBestMatchWithRemixTrack(): void
    {
        $tracks = [
            [
                'id' => 123,
                'title' => 'test song (remix)',
                'user' => ['username' => 'test-artist'],
            ],
        ];

        // Remix tracks should have reduced score, so this might not match depending on the scoring
        $result = $this->callPrivateMethod('findBestMatch', [
            $tracks,
            'test song',
            'test-artist',
            '',
        ]);

        // Score should be reduced due to remix, but still might match if high enough
        $this->assertIsInt($result);
    }

    public function testFindBestMatchWithInvalidTrackId(): void
    {
        $tracks = [
            [
                'id' => 'invalid-id', // Should be int
                'title' => 'test song',
                'user' => ['username' => 'test-artist'],
            ],
        ];

        $result = $this->callPrivateMethod('findBestMatch', [
            $tracks,
            'test song',
            'test-artist',
            '',
        ]);

        $this->assertNull($result);
    }

    public function testCalculateMatchScoreExactMatch(): void
    {
        $score = $this->callPrivateMethod('calculateMatchScore', [
            'test song',
            'test artist',
            'test song',
            'test artist',
        ]);

        $this->assertEquals(265, $score);
    }

    public function testCalculateMatchScorePartialMatch(): void
    {
        $score = $this->callPrivateMethod('calculateMatchScore', [
            'test song extended version',
            'test artist feat. someone',
            'test song',
            'test artist',
        ]);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThan(265, $score); // Less than exact match
    }

    public function testCalculateMatchScoreBothContain(): void
    {
        $score = $this->callPrivateMethod('calculateMatchScore', [
            'my test song',
            'my test artist',
            'test song',
            'test artist',
        ]);

        $this->assertGreaterThan(90, $score); // Should include both contain bonus (20 points) + partial matches + prefix matches
    }

    public function testCalculateMatchScorePrefixMatch(): void
    {
        $score = $this->callPrivateMethod('calculateMatchScore', [
            'test something else',
            'test someone else',
            'test song',
            'test artist',
        ]);

        $this->assertGreaterThan(20, $score); // Should get prefix match points
    }

    public function testCleanSearchTerm(): void
    {
        $result = $this->callPrivateMethod('cleanSearchTerm', ['Song Title (feat. Artist)']);
        $this->assertEquals('Song Title', $result);

        $result = $this->callPrivateMethod('cleanSearchTerm', ['Song Title ft. Artist']);
        $this->assertEquals('Song Title Artist', $result);
    }

    public function testExtractMainArtist(): void
    {
        $result = $this->callPrivateMethod('extractMainArtist', ['Main Artist, Featured Artist']);
        $this->assertEquals('Main Artist', $result);

        $result = $this->callPrivateMethod('extractMainArtist', ['Single Artist']);
        $this->assertEquals('Single Artist', $result);
    }

    /**
     * Helper method to call private methods for testing.
     */
    private function callPrivateMethod(string $methodName, array $args = []): mixed
    {
        $reflection = new ReflectionClass($this->trackSearcher);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->trackSearcher, $args);
    }
}
