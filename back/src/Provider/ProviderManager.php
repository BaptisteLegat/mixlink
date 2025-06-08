<?php

namespace App\Provider;

use App\Entity\Provider;
use App\Entity\User;
use App\Repository\ProviderRepository;
use App\Security\OAuthUserData;
use App\Trait\TraceableTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

class ProviderManager
{
    use TraceableTrait;

    public function __construct(
        private ProviderRepository $providerRepository,
        private ProviderMapper $providerMapper,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function createOrUpdateProvider(OAuthUserData $oauthUserData, string $providerName, User $user): void
    {
        $filters = $this->entityManager->getFilters();
        $filters->disable('softdeleteable');

        $existingProvider = $this->providerRepository->findOneBy(['name' => $providerName, 'user' => $user]);

        if ($existingProvider instanceof Provider && null !== $existingProvider->getDeletedAt()) {
            $existingProvider->setDeletedAt(null);
            $this->logger->info('Reactivating previously deleted provider', [
                'providerId' => $existingProvider->getId(),
                'providerName' => $providerName,
                'userId' => $user->getId(),
            ]);
        }

        $filters->enable('softdeleteable');

        $provider = $this->providerMapper->mapEntity($oauthUserData, $providerName, $user, $existingProvider);

        $isUpdate = $existingProvider instanceof Provider;

        $this->setTimestampable($provider, $isUpdate);
        $this->setBlameable($provider, $user->getEmail(), $isUpdate);

        $this->providerRepository->save($provider, true);
    }

    public function findByAccessToken(string $accessToken): ?User
    {
        $provider = $this->providerRepository->findOneBy(['accessToken' => $accessToken]);
        if (!$provider instanceof Provider) {
            return null;
        }

        return $provider->getUser();
    }

    public function disconnectProvider(string $providerId, User $user): ?bool
    {
        try {
            $provider = $this->providerRepository->find($providerId);

            if (!$provider instanceof Provider) {
                return null;
            }

            $providerUser = $provider->getUser();
            if (!$providerUser instanceof User) {
                return null;
            }

            $accessToken = $provider->getAccessToken();
            $isMainProvider = false;

            if (null !== $accessToken && '' !== $accessToken) {
                $currentUser = $this->findByAccessToken($accessToken);
                $isMainProvider = $currentUser instanceof User && $currentUser->getId() === $user->getId();
            }

            $this->providerRepository->remove($provider, true);

            return $isMainProvider;
        } catch (Exception $e) {
            $this->logger->error('Error disconnecting provider', [
                'providerId' => $providerId,
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function isProviderCurrentlyUsed(Provider $provider): bool
    {
        $accessToken = $provider->getAccessToken();
        if (null === $accessToken) {
            return false;
        }

        $currentUser = $this->findByAccessToken($accessToken);
        $userFromProvider = $provider->getUser();

        return null !== $currentUser && null !== $userFromProvider && $currentUser->getId() === $userFromProvider->getId();
    }
}
