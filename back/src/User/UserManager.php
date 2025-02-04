<?php

namespace App\User;

use App\Entity\User;
use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\Trait\TraceableTrait;
use App\Security\OAuthUserData;

class UserManager
{
    use TraceableTrait;

    public function __construct(
        private UserMapper $userMapper,
        private ProviderManager $providerManager,
        private UserRepository $userRepository
    ) {
    }

    public function create(OAuthUserData $oauthUserData, string $provider): User
    {
        $resourceOwner = $oauthUserData->getUser();

        // Vérifier si l'utilisateur existe déjà via son email
        $existingUser = $this->userRepository->findOneBy(['email' => $resourceOwner->getEmail()]);

        // Mapper les données du user (nouveau ou existant)
        $user = $this->userMapper->mapEntity($resourceOwner, $provider, $existingUser);

        $this->setTimestampable($user, $existingUser !== null);

        $this->setBlameable($user, $resourceOwner->getEmail(), $existingUser !== null);

        $this->providerManager->createOrUpdateProvider($oauthUserData, $provider, $user);

        $this->userRepository->save($user, true);

        return $user;
    }
}
