<?php

namespace App\Tests\Unit\Service\Export\SoundCloud;

use App\Entity\Provider;
use App\Service\Export\SoundCloud\SoundCloudApiClient;
use App\Service\OAuthTokenManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SoundCloudApiClientTest extends TestCase
{
    private SoundCloudApiClient $apiClient;
    private HttpClientInterface|MockObject $httpClientMock;
    private OAuthTokenManager|MockObject $tokenManagerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);
        $this->tokenManagerMock = $this->createMock(OAuthTokenManager::class);

        $this->apiClient = new SoundCloudApiClient(
            $this->httpClientMock,
            $this->tokenManagerMock
        );
    }

    public function testMakeRequestSuccess(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $expectedResponse = ['id' => 123, 'title' => 'Test Track'];

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('getValidAccessToken')
            ->with($provider)
            ->willReturn('valid-token');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_OK);
        $responseMock->method('toArray')->with(false)->willReturn($expectedResponse);

        $this->httpClientMock
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.soundcloud.com/tracks', [
                'headers' => [
                    'Authorization' => 'OAuth valid-token',
                    'Content-Type' => 'application/json',
                ],
                'query' => ['q' => 'test'],
            ])
            ->willReturn($responseMock);

        $result = $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks', [
            'query' => ['q' => 'test'],
        ]);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testMakeRequestWithCreatedStatus(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('valid-token');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_CREATED);
        $responseMock->method('toArray')->willReturn(['id' => 456]);

        $this->httpClientMock
            ->method('request')
            ->willReturn($responseMock);

        $result = $this->apiClient->makeRequest($provider, 'POST', 'https://api.soundcloud.com/playlists');

        $this->assertEquals(['id' => 456], $result);
    }

    public function testMakeRequestApiError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('valid-token');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $responseMock->method('toArray')->with(false)->willReturn([
            'error' => ['message' => 'Invalid request'],
        ]);

        $this->httpClientMock
            ->method('request')
            ->willReturn($responseMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400): Invalid request');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }

    public function testMakeRequestWithErrorsArray(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('valid-token');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $responseMock->method('toArray')->with(false)->willReturn([
            'error' => ['errors' => ['field' => 'required']],
        ]);

        $this->httpClientMock
            ->method('request')
            ->willReturn($responseMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400): {"field":"required"}');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }

    public function testMakeRequestWithSimpleMessage(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('valid-token');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $responseMock->method('toArray')->with(false)->willReturn([
            'message' => 'Simple error message',
        ]);

        $this->httpClientMock
            ->method('request')
            ->willReturn($responseMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400): Simple error message');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }

    public function testMakeRequestWithUnknownError(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('valid-token');

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
        $responseMock->method('toArray')->with(false)->willReturn([]);

        $this->httpClientMock
            ->method('request')
            ->willReturn($responseMock);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SoundCloud API request failed (400): Unknown error');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }

    public function testMakeRequestWith401ErrorAndTokenRefresh(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('old-token')
            ->setRefreshToken('refresh-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('old-token');

        // First request fails with 401
        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function ($method, $url, $options) {
                $responseMock = $this->createMock(ResponseInterface::class);

                if (str_contains($options['headers']['Authorization'], 'old-token')) {
                    // First request with old token fails
                    throw new RuntimeException('SoundCloud API request failed (401): Unauthorized');
                } else {
                    // Second request with new token succeeds
                    $responseMock->method('getStatusCode')->willReturn(Response::HTTP_OK);
                    $responseMock->method('toArray')->willReturn(['success' => true]);

                    return $responseMock;
                }
            });

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn('new-token');

        $result = $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');

        $this->assertEquals(['success' => true], $result);
    }

    public function testMakeRequestWith401ErrorAndRefreshFailure(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('old-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('old-token');

        $this->httpClientMock
            ->method('request')
            ->willThrowException(new RuntimeException('SoundCloud API request failed (401): Unauthorized'));

        $this->tokenManagerMock
            ->method('refreshAccessToken')
            ->with($provider)
            ->willThrowException(new RuntimeException('Refresh failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh token and retry request: Refresh failed');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }

    public function testMakeRequestWithNon401Error(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('valid-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('valid-token');

        $this->httpClientMock
            ->method('request')
            ->willThrowException(new RuntimeException('Network error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Network error');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }

    public function testMakeRequestWith401ErrorAndRetryThrowsApiException(): void
    {
        $provider = new Provider()
            ->setName('soundcloud')
            ->setAccessToken('old-token')
            ->setRefreshToken('refresh-token');

        $this->tokenManagerMock
            ->method('getValidAccessToken')
            ->willReturn('old-token');

        // First request fails with 401
        // Second request (after token refresh) returns error response but doesn't throw HTTP exception
        $this->httpClientMock
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function ($method, $url, $options) {
                if (str_contains($options['headers']['Authorization'], 'old-token')) {
                    // First request with old token fails with 401
                    throw new RuntimeException('SoundCloud API request failed (401): Unauthorized');
                } else {
                    // Second request with new token returns error response (but HTTP request succeeds)
                    $responseMock = $this->createMock(ResponseInterface::class);
                    $responseMock->method('getStatusCode')->willReturn(Response::HTTP_BAD_REQUEST);
                    $responseMock->method('toArray')->with(false)->willReturn([
                        'error' => ['message' => 'Invalid playlist data'],
                    ]);

                    return $responseMock;
                }
            });

        $this->tokenManagerMock
            ->expects($this->once())
            ->method('refreshAccessToken')
            ->with($provider)
            ->willReturn('new-token');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to refresh token and retry request: SoundCloud API request failed (400): Invalid playlist data');

        $this->apiClient->makeRequest($provider, 'GET', 'https://api.soundcloud.com/tracks');
    }
}
