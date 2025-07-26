<?php

namespace App\User;

use App\ApiResource\ApiReference;
use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\Security\OAuthUserData;
use App\Subscription\SubscriptionManager;
use App\Trait\TraceableTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use Martin1982\OAuth2\Client\Provider\SoundCloudResourceOwner;
use Psr\Log\LoggerInterface;

class UserManager
{
    use TraceableTrait;

    public function __construct(
        private UserMapper $userMapper,
        private ProviderManager $providerManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private SubscriptionManager $subscriptionManager,
    ) {
    }

    public function create(OAuthUserData $oauthUserData, string $provider): User
    {
        /** @var GoogleUser|SpotifyResourceOwner|SoundCloudResourceOwner $resourceOwner */
        $resourceOwner = $oauthUserData->getUser();

        $email = '';

        if (!$resourceOwner instanceof SoundCloudResourceOwner) {
            $email = (string) $resourceOwner->getEmail();
        }

        $filters = $this->entityManager->getFilters();
        $filters->disable('softdeleteable');
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        $filters->enable('softdeleteable');

        if ($existingUser && null !== $existingUser->getDeletedAt()) {
            $this->reactivateUser($existingUser, $provider);
        }

        $user = $this->userMapper->mapEntity($resourceOwner, $provider, $existingUser);

        $isUpdate = $existingUser instanceof User;

        $this->setTimestampable($user, $isUpdate);

        $this->setBlameable($user, $email, $isUpdate);

        $this->providerManager->createOrUpdateProvider($oauthUserData, $provider, $user);

        $this->entityManager->refresh($user);
        $this->userRepository->save($user, true);

        return $user;
    }

    private function reactivateUser(User $user, string $providerName): void
    {
        $user->setDeletedAt(null);

        $filters = $this->entityManager->getFilters();
        $filters->disable('softdeleteable');

        foreach ($user->getProviders() as $provider) {
            if ($provider->getName() === $providerName && null !== $provider->getDeletedAt()) {
                $provider->setDeletedAt(null);

                break;
            }
        }

        $filters->enable('softdeleteable');
    }

    public function getUserModel(User $user, ?string $currentAccessToken = null): UserModel
    {
        return $this->userMapper->mapModel(new UserModel(), $user, $currentAccessToken);
    }

    public function deleteUser(User $user): void
    {
        try {
            $this->cancelUserSubscription($user);
            $this->userRepository->remove($user, true);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete user', [
                'userId' => $user->getId(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function cancelUserSubscription(User $user): void
    {
        $subscription = $user->getSubscription();
        if ($subscription && null !== $subscription->getStripeSubscriptionId()) {
            try {
                $this->subscriptionManager->cancelSubscription($user);
            } catch (Exception $e) {
                $this->logger->error('Failed to cancel subscription before user deletion', [
                    'userId' => $user->getId(),
                    'subscriptionId' => $subscription->getStripeSubscriptionId(),
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function updateEmailForSoundCloudUser(User $user, string $email): void
    {
        $activeProviders = array_filter($user->getProviders()->toArray(), function ($provider) {
            return null === $provider->getDeletedAt();
        });
        if (1 !== count($activeProviders) || ApiReference::SOUNDCLOUD !== $activeProviders[0]->getName()) {
            throw new InvalidArgumentException('profile.email.not_soundcloud_only');
        }

        $user
            ->setEmail($email)
            ->setCreatedBy($email)
            ->setUpdatedBy($email)
        ;
        $this->setTimestampable($user, true);
        $this->setBlameable($user, $email, true);

        $provider = $activeProviders[0];
        $provider
            ->setCreatedBy($email)
            ->setUpdatedBy($email)
        ;

        $this->setTimestampable($provider, true);
        $this->setBlameable($provider, $email, true);
        $this->userRepository->save($user, true);
    }
}
