<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Security\AuthTokenAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AuthTokenAuthenticatorTest extends TestCase
{
    private ProviderManager|MockObject $providerManagerMocked;
    private AuthTokenAuthenticator $authTokenAuthenticator;

    protected function setUp(): void
    {
        $this->providerManagerMocked = $this->createMock(ProviderManager::class);
        $this->authTokenAuthenticator = new AuthTokenAuthenticator($this->providerManagerMocked);
    }

    public function testSupports(): void
    {
        $request = new Request();
        $request->cookies->set('AUTH_TOKEN', 'test');

        $this->assertTrue($this->authTokenAuthenticator->supports($request));
    }

    public function testSupportsWithoutToken(): void
    {
        $request = new Request();

        $this->assertFalse($this->authTokenAuthenticator->supports($request));
    }

    public function testAuthenticateWithValidToken(): void
    {
        $request = new Request();
        $request->cookies->set('AUTH_TOKEN', 'test');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserIdentifier')->willReturn('fake-uuid');

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByAccessToken')
            ->with('test')
            ->willReturn($userMock)
        ;

        $passport = $this->authTokenAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $request = new Request();
        $request->cookies->set('AUTH_TOKEN', 'invalid');

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByAccessToken')
            ->with('invalid')
            ->willReturn(null)
        ;

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid auth token');

        $this->authTokenAuthenticator->authenticate($request);
    }

    public function testAuthenticateWithNoUser(): void
    {
        $request = new Request();
        $request->cookies->set('AUTH_TOKEN', 'test');

        $this->providerManagerMocked
            ->expects($this->once())
            ->method('findByAccessToken')
            ->with('test')
            ->willReturn(null)
        ;

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid auth token');

        $this->authTokenAuthenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);
        $firewallName = 'main';

        $result = $this->authTokenAuthenticator->onAuthenticationSuccess($request, $token, $firewallName);

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $exception = $this->createMock(AuthenticationException::class);

        $result = $this->authTokenAuthenticator->onAuthenticationFailure($request, $exception);

        $this->assertNull($result);
    }
}
