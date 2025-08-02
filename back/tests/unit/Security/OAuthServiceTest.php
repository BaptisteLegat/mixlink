<?php

namespace App\Tests\Unit\Security;

use App\ApiResource\ApiReference;
use App\Security\OAuthService;
use App\Security\OAuthUserData;
use App\Security\Provider\SoundCloudUserData;
use Exception;
use InvalidArgumentException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OAuthServiceTest extends TestCase
{
    private ClientRegistry|MockObject $clientRegistryMocked;
    private OAuth2Client|MockObject $oauthClientMocked;
    private LoggerInterface|MockObject $loggerMocked;
    private OAuthService $oAuthService;

    protected function setUp(): void
    {
        $this->clientRegistryMocked = $this->createMock(ClientRegistry::class);
        $this->oauthClientMocked = $this->createMock(OAuth2Client::class);
        $this->loggerMocked = $this->createMock(LoggerInterface::class);

        $this->oAuthService = new OAuthService($this->clientRegistryMocked, $this->loggerMocked);
    }

    public function testGetRedirectResponseGoogle(): void
    {
        $provider = ApiReference::GOOGLE;
        $expectedScopes = OAuthService::GOOGLE_SCOPES;
        $expectedOptions = [
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];
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
            ->with($expectedScopes, $expectedOptions)
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

    public function testGetRedirectResponseSoundCloud(): void
    {
        $provider = ApiReference::SOUNDCLOUD;
        $expectedScopes = OAuthService::SOUNDCLOUD_SCOPES;
        $expectedOptions = [];
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
            ->with($expectedScopes, $expectedOptions)
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

    public function testFetchUserSoundCloud(): void
    {
        $providerName = ApiReference::SOUNDCLOUD;
        $accessToken = new AccessToken(['access_token' => 'xxx', 'refresh_token' => 'yyy']);
        $user = $this->createMock(ResourceOwnerInterface::class);
        $user->method('toArray')->willReturn(['id' => 123, 'full_name' => 'Test User']);
        $oauthUserData = new OAuthUserData(
            new SoundCloudUserData(['id' => 123, 'full_name' => 'Test User']),
            'xxx',
            'yyy'
        );

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

    public function testFetchUserWithIdentityProviderException(): void
    {
        $providerName = ApiReference::SOUNDCLOUD;
        $exception = new IdentityProviderException('invalid_grant', 400, ['error' => 'invalid_grant']);

        $this->clientRegistryMocked
            ->expects($this->once())
            ->method('getClient')
            ->with($providerName)
            ->willReturn($this->oauthClientMocked)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException($exception)
        ;

        $this->loggerMocked
            ->expects($this->once())
            ->method('error')
            ->with('OAuth Error', $this->callback(function ($context) use ($providerName) {
                return $context['provider'] === $providerName
                    && 'invalid_grant' === $context['error']
                    && isset($context['trace']);
            }))
        ;

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('invalid_grant');

        $this->oAuthService->fetchUser($providerName);
    }

    public function testFetchUserWithGenericException(): void
    {
        $providerName = ApiReference::SOUNDCLOUD;
        $exception = new Exception('Generic error');

        $this->clientRegistryMocked
            ->expects($this->once())
            ->method('getClient')
            ->with($providerName)
            ->willReturn($this->oauthClientMocked)
        ;

        $this->oauthClientMocked
            ->expects($this->once())
            ->method('getAccessToken')
            ->willThrowException($exception)
        ;

        $this->loggerMocked
            ->expects($this->once())
            ->method('error')
            ->with('OAuth Error', $this->callback(function ($context) use ($providerName) {
                return $context['provider'] === $providerName
                    && 'Generic error' === $context['error']
                    && isset($context['trace']);
            }))
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Generic error');

        $this->oAuthService->fetchUser($providerName);
    }
}
