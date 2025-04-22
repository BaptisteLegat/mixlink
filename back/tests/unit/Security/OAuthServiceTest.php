<?php

namespace App\Tests\Unit\Security;

use App\ApiResource\ApiReference;
use App\Security\OAuthService;
use App\Security\OAuthUserData;
use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthServiceTest extends TestCase
{
    private ClientRegistry|MockObject $clientRegistryMocked;
    private OAuth2Client|MockObject $oauthClientMocked;
    private OAuthService $oAuthService;

    protected function setUp(): void
    {
        $this->clientRegistryMocked = $this->createMock(ClientRegistry::class);
        $this->oauthClientMocked = $this->createMock(OAuth2Client::class);

        $this->oAuthService = new OAuthService($this->clientRegistryMocked);
    }

    public function testGetRedirectResponseGoogle(): void
    {
        $provider = ApiReference::GOOGLE;
        $expectedScopes = OAuthService::GOOGLE_SCOPES;
        $redirectResponse = new RedirectResponse('https://example.com/oauth_redirect');

        $this->clientRegistryMocked
            ->expects($this->once())
            ->method('getClient')
            ->with($provider)
            ->willReturn($this->oauthClientMocked)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('redirect')
            ->with($expectedScopes, [])
            ->willReturn($redirectResponse)
        ;

        $response = $this->oAuthService->getRedirectResponse($provider);

        $this->assertSame($redirectResponse, $response);
    }

    public function testGetRedirectResponseSpotify(): void
    {
        $provider = ApiReference::SPOTIFY;
        $expectedScopes = OAuthService::SPOTIFY_SCOPES;
        $redirectResponse = new RedirectResponse('https://example.com/oauth_redirect');

        $this->clientRegistryMocked
            ->expects($this->once())
            ->method('getClient')
            ->with($provider)
            ->willReturn($this->oauthClientMocked)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('redirect')
            ->with($expectedScopes, [])
            ->willReturn($redirectResponse)
        ;

        $response = $this->oAuthService->getRedirectResponse($provider);

        $this->assertSame($redirectResponse, $response);
    }

    public function testGetRedirectResponseWithUnknownProvider(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider unknown_provider not supported');

        $this->oAuthService->getRedirectResponse('unknown_provider');
    }

    public function testFetchUserGoogle(): void
    {
        $providerName = ApiReference::GOOGLE;
        $accessToken = new AccessToken(['access_token' => 'xxx', 'refresh_token' => 'yyy']);
        $user = $this->createMock(ResourceOwnerInterface::class);
        $oauthUserData = new OAuthUserData($user, 'xxx', 'yyy');

        $this->clientRegistryMocked
            ->expects($this->once())
            ->method('getClient')
            ->with($providerName)
            ->willReturn($this->oauthClientMocked)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($user)
        ;

        $result = $this->oAuthService->fetchUser($providerName);

        $this->assertEquals($oauthUserData, $result);
    }

    public function testFetchUserSpotify(): void
    {
        $providerName = ApiReference::SPOTIFY;
        $accessToken = new AccessToken(['access_token' => 'xxx', 'refresh_token' => 'yyy']);
        $user = $this->createMock(ResourceOwnerInterface::class);
        $oauthUserData = new OAuthUserData($user, 'xxx', 'yyy');

        $this->clientRegistryMocked
            ->expects($this->once())
            ->method('getClient')
            ->with($providerName)
            ->willReturn($this->oauthClientMocked)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($accessToken)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('fetchUserFromToken')
            ->with($accessToken)
            ->willReturn($user)
        ;

        $result = $this->oAuthService->fetchUser($providerName);

        $this->assertEquals($oauthUserData, $result);
    }
}
