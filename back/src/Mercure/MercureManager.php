<?php

namespace App\Mercure;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use RuntimeException;

class MercureManager
{
    public function __construct(
        private string $jwtKey,
        private string $mercureUrl,
    ) {
    }

    /**
     * @return array{token: string, mercureUrl: string}
     */
    public function generateTokenForSession(string $sessionCode): array
    {
        if (empty($this->jwtKey)) {
            throw new RuntimeException('MERCURE_PUBLISHER_JWT_KEY is not set');
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwtKey)
        );

        $token = $config->builder()
            ->withClaim('mercure', [
                'subscribe' => ["session/{$sessionCode}"],
            ])
            ->getToken($config->signer(), $config->signingKey())
        ;

        return [
            'token' => $token->toString(),
            'mercureUrl' => $this->mercureUrl,
        ];
    }

    /**
     * @return array{token: string, mercureUrl: string}
     */
    public function generateTokenForHost(string $sessionCode): array
    {
        if (empty($this->jwtKey)) {
            throw new RuntimeException('MERCURE_PUBLISHER_JWT_KEY is not set');
        }

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->jwtKey)
        );

        $token = $config->builder()
            ->withClaim('mercure', [
                'subscribe' => ["session/{$sessionCode}"],
                'publish' => ["session/{$sessionCode}"],
            ])
            ->getToken($config->signer(), $config->signingKey())
        ;

        return [
            'token' => $token->toString(),
            'mercureUrl' => $this->mercureUrl,
        ];
    }
}
