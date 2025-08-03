<?php

namespace App\Tests\Unit\Service\Export\Model;

use App\Service\Export\Model\ExportResult;
use PHPUnit\Framework\TestCase;

class ExportResultTest extends TestCase
{
    public function testExportResultCreation(): void
    {
        $result = new ExportResult(
            playlistId: 'playlist_123',
            playlistUrl: 'https://spotify.com/playlist/123',
            exportedTracks: 5,
            failedTracks: 2,
            platform: 'spotify'
        );

        $this->assertEquals('playlist_123', $result->playlistId);
        $this->assertEquals('https://spotify.com/playlist/123', $result->playlistUrl);
        $this->assertEquals(5, $result->exportedTracks);
        $this->assertEquals(2, $result->failedTracks);
        $this->assertEquals('spotify', $result->platform);
    }

    public function testExportResultHelperMethods(): void
    {
        $result = new ExportResult(
            playlistId: 'playlist_123',
            playlistUrl: 'https://spotify.com/playlist/123',
            exportedTracks: 8,
            failedTracks: 2,
            platform: 'spotify'
        );

        $this->assertTrue($result->hasFailures());
        $this->assertFalse($result->isFullSuccess());
        $this->assertFalse($result->isEmpty());
        $this->assertEquals(10, $result->getTotalTracksProcessed());
        $this->assertEquals(80.0, $result->getSuccessRate());
    }

    public function testFullSuccessResult(): void
    {
        $result = new ExportResult(
            playlistId: 'playlist_456',
            playlistUrl: 'https://youtube.com/playlist/456',
            exportedTracks: 10,
            failedTracks: 0,
            platform: 'google'
        );

        $this->assertFalse($result->hasFailures());
        $this->assertTrue($result->isFullSuccess());
        $this->assertFalse($result->isEmpty());
        $this->assertEquals(100.0, $result->getSuccessRate());
    }

    public function testEmptyResult(): void
    {
        $result = new ExportResult(
            playlistId: 'playlist_789',
            playlistUrl: 'https://soundcloud.com/playlist/789',
            exportedTracks: 0,
            failedTracks: 0,
            platform: 'soundcloud'
        );

        $this->assertFalse($result->hasFailures());
        $this->assertFalse($result->isFullSuccess());
        $this->assertTrue($result->isEmpty());
        $this->assertEquals(0.0, $result->getSuccessRate());
    }

    public function testJsonSerialization(): void
    {
        $result = new ExportResult(
            playlistId: 'playlist_123',
            playlistUrl: 'https://spotify.com/playlist/123',
            exportedTracks: 5,
            failedTracks: 2,
            platform: 'spotify'
        );

        $expectedArray = [
            'playlist_id' => 'playlist_123',
            'playlist_url' => 'https://spotify.com/playlist/123',
            'exported_tracks' => 5,
            'failed_tracks' => 2,
            'platform' => 'spotify',
        ];

        $this->assertEquals($expectedArray, $result->toArray());
        $this->assertEquals($expectedArray, $result->jsonSerialize());
    }
}
