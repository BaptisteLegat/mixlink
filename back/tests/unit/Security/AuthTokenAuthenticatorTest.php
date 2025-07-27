<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Security\AuthTokenAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
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

        $result = $this->authTokenAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $result);
    }

    public function testAuthenticateWithNoToken(): void
    {
        $request = new Request();
        $result = $this->authTokenAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $result);
    }

    public function testAuthenticateWithNoUser(): void
    {
        $request = new Request();
        $request->cookies->set('AUTH_TOKEN', 'test');

        $result = $this->authTokenAuthenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $result);
    }
}
