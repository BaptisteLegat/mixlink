<?php

namespace App\Tests\Unit\Session;

use App\Entity\Session;
use App\Entity\User;
use App\Session\Mapper\SessionMapper;
use App\Session\Model\Request\CreateSessionRequest;
use App\Session\Model\SessionModel;
use DateTime;
use PHPUnit\Framework\TestCase;

class SessionMapperTest extends TestCase
{
    private SessionMapper $sessionMapper;

    protected function setUp(): void
    {
        $this->sessionMapper = new SessionMapper();
    }

    public function testMapEntity(): void
    {
        $host = new User()
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john@doe.com')
        ;

        $request = new CreateSessionRequest('Session Test', 5);
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
