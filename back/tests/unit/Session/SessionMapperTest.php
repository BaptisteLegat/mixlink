<?php

namespace App\Tests\Unit\Session;

use App\Entity\Playlist;
use App\Entity\Session;
use App\Entity\Song;
use App\Entity\User;
use App\Playlist\PlaylistManager;
use App\Playlist\PlaylistMapper;
use App\Playlist\PlaylistModel;
use App\Session\Mapper\SessionMapper;
use App\Session\Model\Request\CreateSessionRequest;
use App\Session\Model\SessionModel;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SessionMapperTest extends TestCase
{
    private PlaylistManager|MockObject $playlistManagerMock;
    private PlaylistMapper|MockObject $playlistMapperMock;
    private SessionMapper $sessionMapper;

    protected function setUp(): void
    {
        $this->playlistManagerMock = $this->createMock(PlaylistManager::class);
        $this->playlistMapperMock = $this->createMock(PlaylistMapper::class);
        $this->sessionMapper = new SessionMapper(
            $this->playlistManagerMock,
            $this->playlistMapperMock,
        );
    }

    public function testMapEntity(): void
    {
        $host = new User()
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john@doe.com')
        ;

        $request = new CreateSessionRequest('Session Test', 'Playlist Test', 5);
        $session = $this->sessionMapper->mapEntity($request, $host);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertEquals('Session Test', $session->getName());
        $this->assertEquals(5, $session->getMaxParticipants());
        $this->assertSame($host, $session->getHost());
    }

    public function testMapModel(): void
    {
        $host = new User()
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john@doe.com')
            ->setProfilePicture('pic.jpg')
            ->setRoles(['ROLE_USER'])
        ;

        $now = new DateTime('2024-01-01T12:00:00+00:00');
        $session = new Session()
            ->setName('Session Test')
            ->setCode('CODE123')
            ->setMaxParticipants(5)
            ->setHost($host)
            ->setCreatedAt($now)
        ;

        $model = $this->sessionMapper->mapModel($session);
        $this->assertInstanceOf(SessionModel::class, $model);
        $this->assertEquals('Session Test', $model->getName());
        $this->assertEquals('CODE123', $model->getCode());
        $this->assertEquals(5, $model->getMaxParticipants());
        $this->assertEquals($now->format('c'), $model->getCreatedAt());
        $this->assertNull($model->getEndedAt());
        $this->assertEquals([
            'id' => (string) $host->getId(),
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@doe.com',
            'profilePicture' => 'pic.jpg',
            'roles' => ['ROLE_USER'],
        ], $model->getHost());
    }

    public function testMapModelWithPlaylist(): void
    {
        $playlist = new Playlist()
            ->setName('Playlist Test')
            ->addSong(new Song())
        ;

        $host = new User()
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john@doe.com')
            ->setProfilePicture('pic.jpg')
            ->setRoles(['ROLE_USER'])
            ->addPlaylist($playlist)
        ;

        $session = new Session()
            ->setName('Session Test')
            ->setCode('CODE123')
            ->setMaxParticipants(5)
            ->setHost($host)
        ;

        $this->playlistManagerMock->expects($this->once())
            ->method('getPlaylistBySessionCode')
            ->with('CODE123')
            ->willReturn($playlist)
        ;

        $this->playlistMapperMock->expects($this->once())
            ->method('mapModel')
            ->with($playlist)
            ->willReturn(new PlaylistModel())
        ;

        $model = $this->sessionMapper->mapModel($session);
        $this->assertInstanceOf(SessionModel::class, $model);
        $this->assertEquals('Session Test', $model->getName());
    }

    public function testMapModelWithNullHost(): void
    {
        $session = new Session();
        $this->expectException(RuntimeException::class);
        $this->sessionMapper->mapModel($session);
    }

    public function testMapModels(): void
    {
        $host = new User()
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john@doe.com')
            ->setProfilePicture('pic.jpg')
            ->setRoles(['ROLE_USER'])
        ;

        $session1 = new Session()
            ->setName('S1')
            ->setHost($host)
        ;
        $session2 = new Session()
            ->setName('S2')
            ->setHost($host)
        ;

        $result = $this->sessionMapper->mapModels([$session1, $session2]);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(SessionModel::class, $result);
        $this->assertEquals('S1', $result[0]->getName());
        $this->assertEquals('S2', $result[1]->getName());
    }
}
