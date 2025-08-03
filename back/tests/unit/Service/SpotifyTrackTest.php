<?php

namespace App\Tests\Unit\Service;

use App\Service\Model\SpotifyTrack;
use PHPUnit\Framework\TestCase;

class SpotifyTrackTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $id = 'track_123';
        $name = 'Test Song';
        $artists = ['Artist 1', 'Artist 2'];
        $image = 'https://example.com/image.jpg';
        $previewUrl = 'https://example.com/preview.mp3';

        $track = new SpotifyTrack($id, $name, $artists, $image, $previewUrl);

        $this->assertEquals($id, $track->getId());
        $this->assertEquals($name, $track->getName());
        $this->assertEquals($artists, $track->getArtists());
        $this->assertEquals($image, $track->getImage());
        $this->assertEquals($previewUrl, $track->getPreviewUrl());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $id = 'track_456';
        $name = 'Another Song';
        $artists = ['Solo Artist'];

        $track = new SpotifyTrack($id, $name, $artists);

        $this->assertEquals($id, $track->getId());
        $this->assertEquals($name, $track->getName());
        $this->assertEquals($artists, $track->getArtists());
        $this->assertNull($track->getImage());
        $this->assertNull($track->getPreviewUrl());
    }

    public function testConstructorWithEmptyArtists(): void
    {
        $id = 'track_789';
        $name = 'Unknown Artist Song';
        $artists = [];

        $track = new SpotifyTrack($id, $name, $artists);

        $this->assertEquals($id, $track->getId());
        $this->assertEquals($name, $track->getName());
        $this->assertEquals($artists, $track->getArtists());
        $this->assertNull($track->getImage());
        $this->assertNull($track->getPreviewUrl());
    }

    public function testToArrayWithAllProperties(): void
    {
        $id = 'track_123';
        $name = 'Test Song';
        $artists = ['Artist 1', 'Artist 2'];
        $image = 'https://example.com/image.jpg';
        $previewUrl = 'https://example.com/preview.mp3';

        $track = new SpotifyTrack($id, $name, $artists, $image, $previewUrl);
        $array = $track->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($id, $array['id']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($artists, $array['artists']);
        $this->assertEquals($image, $array['image']);
        $this->assertEquals($previewUrl, $array['preview_url']);
    }

    public function testToArrayWithMinimalProperties(): void
    {
        $id = 'track_456';
        $name = 'Another Song';
        $artists = ['Solo Artist'];

        $track = new SpotifyTrack($id, $name, $artists);
        $array = $track->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($id, $array['id']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($artists, $array['artists']);
        $this->assertNull($array['image']);
        $this->assertNull($array['preview_url']);
    }

    public function testToArrayWithEmptyArtists(): void
    {
        $id = 'track_789';
        $name = 'Unknown Artist Song';
        $artists = [];

        $track = new SpotifyTrack($id, $name, $artists);
        $array = $track->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($id, $array['id']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($artists, $array['artists']);
        $this->assertNull($array['image']);
        $this->assertNull($array['preview_url']);
    }

    public function testToArrayWithNullValues(): void
    {
        $id = 'track_999';
        $name = 'Null Values Song';
        $artists = ['Artist'];
        $image = null;
        $previewUrl = null;

        $track = new SpotifyTrack($id, $name, $artists, $image, $previewUrl);
        $array = $track->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($id, $array['id']);
        $this->assertEquals($name, $array['name']);
        $this->assertEquals($artists, $array['artists']);
        $this->assertNull($array['image']);
        $this->assertNull($array['preview_url']);
    }
}
