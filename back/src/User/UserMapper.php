<?php

namespace App\User;

use App\ApiResource\ApiReference;
use App\Entity\User;
use App\Provider\ProviderMapper;
use App\Session\SessionModel;
use App\Subscription\SubscriptionMapper;
use InvalidArgumentException;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class UserMapper
{
    public function __construct(
        private ProviderMapper $providerMapper,
        private SubscriptionMapper $subscriptionMapper
    ) {
    }

    private const array PROVIDER_MAPPERS = [
        ApiReference::GOOGLE,
        ApiReference::SPOTIFY,
    ];

    public function mapEntity(ResourceOwnerInterface $resourceOwner, string $providerName, ?User $user): User
    {
        $user ??= new User();

        if (!in_array($providerName, self::PROVIDER_MAPPERS, true)) {
            throw new InvalidArgumentException("Provider $providerName not supported");
        }

        if ($resourceOwner instanceof GoogleUser) {
            $this->mapGoogleUser($resourceOwner, $user);
        } elseif ($resourceOwner instanceof SpotifyResourceOwner) {
            $this->mapSpotifyUser($resourceOwner, $user);
        }

        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    private function mapGoogleUser(GoogleUser $resourceOwner, User $user): void
    {
        $user->setFirstName((string) $resourceOwner->getFirstName());
        $user->setLastName((string) $resourceOwner->getLastName());
        $user->setEmail((string) $resourceOwner->getEmail());
        $user->setProfilePicture($resourceOwner->getAvatar());
    }

    private function mapSpotifyUser(SpotifyResourceOwner $resourceOwner, User $user): void
    {
        $user->setFirstName($resourceOwner->getDisplayName());
        $user->setEmail((string) $resourceOwner->getEmail());

        /** @var array<int, array{url: string}> $images */
        $images = $resourceOwner->getImages();

        if (!empty($images) && isset($images[0]['url'])) {
            $user->setProfilePicture($images[0]['url']);
        }
    }

    public function mapModel(UserModel $userModel, User $user, ?string $currentAccessToken = null): UserModel
    {
        $userModel = $userModel->setId((string) $user->getId())
            ->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName())
            ->setEmail($user->getEmail())
            ->setProfilePicture($user->getProfilePicture())
            ->setRoles($user->getRoles())
        ;

        $providers = [];
        foreach ($user->getProviders() as $provider) {
            $providers[] = $this->providerMapper->mapModel($provider, $currentAccessToken);
        }

        $userModel->setProviders($providers);

        $subscription = $user->getSubscription();
        if (null !== $subscription) {
            $subscriptionModel = $this->subscriptionMapper->mapModel($subscription);
            $userModel->setSubscription($subscriptionModel);
        }

        $session = $user->getCurrentSession();
        if ($session) {
            $sessionModel = new SessionModel();
            $sessionModel
                ->setId($session->getId()?->toRfc4122() ?? '')
                ->setName($session->getName())
                ->setCode($session->getCode())
                ->setMaxParticipants($session->getMaxParticipants())
                ->setHost([
                    'id' => (string) $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'profilePicture' => $user->getProfilePicture(),
                    'roles' => $user->getRoles(),
                ])
                ->setCreatedAt($session->getCreatedAt()?->format('c') ?? '')
                ->setEndedAt($session->getEndedAt()?->format('c'));
            $userModel->setCurrentSession($sessionModel);
        } else {
            $userModel->setCurrentSession(null);
        }

        return $userModel;
    }
}
