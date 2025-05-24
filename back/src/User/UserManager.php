<?php

namespace App\User;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\Security\OAuthUserData;
use App\Trait\TraceableTrait;
use Exception;
use Kerox\OAuth2\Client\Provider\SpotifyResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use Psr\Log\LoggerInterface;

class UserManager
{
    use TraceableTrait;

    public function __construct(
        private UserMapper $userMapper,
        private ProviderManager $providerManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {
    }

    public function create(OAuthUserData $oauthUserData, string $provider): User
    {
        /** @var GoogleUser|SpotifyResourceOwner $resourceOwner */
        $resourceOwner = $oauthUserData->getUser();

        $existingUser = $this->userRepository->findOneBy(['email' => (string) $resourceOwner->getEmail()]);

        $user = $this->userMapper->mapEntity($resourceOwner, $provider, $existingUser);

        $isUpdate = $existingUser instanceof User;

        $this->setTimestampable($user, $isUpdate);

        $this->setBlameable($user, (string) $resourceOwner->getEmail(), $isUpdate);

        $this->providerManager->createOrUpdateProvider($oauthUserData, $provider, $user);

        $this->userRepository->save($user, true);

        return $user;
    }

    public function getUserModel(User $user): UserModel
    {
        return $this->userMapper->mapModel(new UserModel(), $user);
    }

    public function deleteUser(User $user): bool
    {
        try {
            $this->userRepository->remove($user, true);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to delete user', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
