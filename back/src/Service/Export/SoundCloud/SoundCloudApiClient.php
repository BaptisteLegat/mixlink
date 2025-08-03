<?php

namespace App\Service\Export\SoundCloud;

use App\Entity\Provider;
use App\Service\OAuthTokenManager;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SoundCloudApiClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private OAuthTokenManager $tokenManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function makeRequest(Provider $provider, string $method, string $url, array $options = []): array
    {
        $accessToken = $this->tokenManager->getValidAccessToken($provider);
        $options = $this->prepareRequestOptions($options, $accessToken);

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if ($this->isSuccessfulResponse($response)) {
                /** @var array<string, mixed> */
                return $response->toArray(false);
            }

            $this->throwApiException($response);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), '401')) {
                return $this->retryWithRefreshedToken($provider, $method, $url, $options);
            }

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function prepareRequestOptions(array $options, string $accessToken): array
    {
        /** @var array<string, string> $headers */
        $headers = $options['headers'] ?? [];
        $options['headers'] = array_merge($headers, [
            'Authorization' => 'OAuth '.$accessToken,
            'Content-Type' => 'application/json',
        ]);

        return $options;
    }

    private function isSuccessfulResponse(ResponseInterface $response): bool
    {
        return Response::HTTP_OK === $response->getStatusCode() || Response::HTTP_CREATED === $response->getStatusCode();
    }

    private function throwApiException(ResponseInterface $response): never
    {
        $errorContent = $response->toArray(false);
        /** @var array<string, mixed> $errorContent */
        $errorMessage = $this->extractErrorMessage($errorContent);

        throw new RuntimeException('SoundCloud API request failed ('.$response->getStatusCode().'): '.$errorMessage);
    }

    /**
     * @param array<string, mixed> $errorContent
     */
    private function extractErrorMessage(array $errorContent): string
    {
        if (isset($errorContent['error']['message']) && is_string($errorContent['error']['message'])) {
            return $errorContent['error']['message'];
        }

        if (isset($errorContent['error']['errors']) && is_array($errorContent['error']['errors'])) {
            $encodedErrors = json_encode($errorContent['error']['errors']);

            return is_string($encodedErrors) ? $encodedErrors : 'Unknown error';
        }

        if (isset($errorContent['message']) && is_string($errorContent['message'])) {
            return $errorContent['message'];
        }

        return 'Unknown error';
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function retryWithRefreshedToken(Provider $provider, string $method, string $url, array $options): array
    {
        try {
            $newAccessToken = $this->tokenManager->refreshAccessToken($provider);
            /** @var array<string, string> $headers */
            $headers = $options['headers'] ?? [];
            $headers['Authorization'] = 'OAuth '.$newAccessToken;
            $options['headers'] = $headers;

            $response = $this->httpClient->request($method, $url, $options);

            if ($this->isSuccessfulResponse($response)) {
                /** @var array<string, mixed> */
                return $response->toArray(false);
            }

            $this->throwApiException($response);
        } catch (RuntimeException $refreshException) {
            throw new RuntimeException('Failed to refresh token and retry request: '.$refreshException->getMessage());
        }
    }
}
