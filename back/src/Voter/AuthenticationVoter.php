<?php

namespace App\Voter;

use App\Provider\ProviderManager;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
class AuthenticationVoter extends Voter
{
    public const string IS_AUTHENTICATED = 'IS_AUTHENTICATED';

    public function __construct(
        private RequestStack $requestStack,
        private ProviderManager $providerManager,
    ) {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::IS_AUTHENTICATED === $attribute;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (self::IS_AUTHENTICATED !== $attribute) {
            return false;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return false;
        }

        $accessToken = $request->cookies->get('AUTH_TOKEN');
        if (null === $accessToken) {
            return false;
        }

        $user = $this->providerManager->findByAccessToken($accessToken);

        return null !== $user;
    }
}
