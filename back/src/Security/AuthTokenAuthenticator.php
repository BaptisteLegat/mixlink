<?php

namespace App\Security;

use App\Entity\User;
use App\Provider\ProviderManager;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class AuthTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private ProviderManager $providerManager)
    {
    }

    #[Override]
    public function supports(Request $request): ?bool
    {
        return null !== $request->cookies->get('AUTH_TOKEN');
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        /** @var string $accessToken */
        $accessToken = $request->cookies->get('AUTH_TOKEN');

        $user = $this->providerManager->findByAccessToken($accessToken);
        if (!$user instanceof User) {
            throw new AuthenticationException('Invalid auth token');
        }

        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), function () use ($user) {
            return $user;
        }));
    }

    #[Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
