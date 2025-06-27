<?php

namespace App\Tests\Unit\Voter;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Voter\AuthenticationVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationVoterTest extends TestCase
{
    private RequestStack|MockObject $requestStackMocked;
    private ProviderManager|MockObject $providerManagerMocked;
    private AuthenticationVoter $authenticationVoter;
    private TokenInterface|MockObject $tokenMocked;
    private Request $request;

    protected function setUp(): void
    {
        $this->requestStackMocked = $this->createMock(RequestStack::class);
        $this->providerManagerMocked = $this->createMock(ProviderManager::class);
        $this->tokenMocked = $this->createMock(TokenInterface::class);
        $this->request = new Request();

        $this->authenticationVoter = new AuthenticationVoter(
            $this->requestStackMocked,
            $this->providerManagerMocked
        );
    }

    public function testSupportsIsAuthenticated(): void
    {
        $reflection = new ReflectionMethod($this->authenticationVoter, 'supports');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->authenticationVoter, AuthenticationVoter::IS_AUTHENTICATED, null);
        $this->assertTrue($result);
    }

    public function testDoesNotSupportOtherAttributes(): void
    {
        $reflection = new ReflectionMethod($this->authenticationVoter, 'supports');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($this->authenticationVoter, 'OTHER_ATTRIBUTE', null);
        $this->assertFalse($result);
    }

    public function testVoteAbstainWhenAttributeNotSupported(): void
    {
        $this->requestStackMocked
            ->expects($this->never())
            ->method('getCurrentRequest')
        ;

        $result = $this->authenticationVoter->vote($this->tokenMocked, null, ['OTHER_ATTRIBUTE']);
        $this->assertEquals(AuthenticationVoter::ACCESS_ABSTAIN, $result);
    }

    public function testVoteDeniedWhenNoRequest(): void
    {
        $this->requestStackMocked
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;

        $reflection = new ReflectionMethod($this->authenticationVoter, 'voteOnAttribute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(
            $this->authenticationVoter,
            AuthenticationVoter::IS_AUTHENTICATED,
            null,
            $this->tokenMocked
        );

        $this->assertFalse($result);
    }

    public function testVoteDeniedWhenNoAccessToken(): void
    {
        $this->requestStackMocked
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request)
        ;

        $reflection = new ReflectionMethod($this->authenticationVoter, 'voteOnAttribute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(
            $this->authenticationVoter,
            AuthenticationVoter::IS_AUTHENTICATED,
            null,
            $this->tokenMocked
        );

        $this->assertFalse($result);
    }

    public function testVoteDeniedWhenUserNotFound(): void
    {
        $this->request->cookies->set('AUTH_TOKEN', 'some_access_token');

        $this->requestStackMocked
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByAccessToken')
            ->with('some_access_token')
            ->willReturn(null)
        ;

        $reflection = new ReflectionMethod($this->authenticationVoter, 'voteOnAttribute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(
            $this->authenticationVoter,
            AuthenticationVoter::IS_AUTHENTICATED,
            null,
            $this->tokenMocked
        );

        $this->assertFalse($result);
    }

    public function testVoteGrantedWhenUserFound(): void
    {
        $this->request->cookies->set('AUTH_TOKEN', 'valid_access_token');
        $user = new User();

        $this->requestStackMocked
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request)
        ;

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByAccessToken')
            ->with('valid_access_token')
            ->willReturn($user)
        ;

        $reflection = new ReflectionMethod($this->authenticationVoter, 'voteOnAttribute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(
            $this->authenticationVoter,
            AuthenticationVoter::IS_AUTHENTICATED,
            null,
            $this->tokenMocked
        );

        $this->assertTrue($result);
    }

    public function testVoteOnAttributeReturnFalseWhenAttributeNotSupported(): void
    {
        $reflection = new ReflectionMethod($this->authenticationVoter, 'voteOnAttribute');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(
            $this->authenticationVoter,
            'OTHER_ATTRIBUTE',
            null,
            $this->tokenMocked
        );

        $this->assertFalse($result);
    }
}
