<?php

namespace App\User;

use App\Entity\Provider;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Trait\TraceableTrait;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class UserManager
{
    use TraceableTrait;

    public function __construct(private UserMapper $userMapper, private UserRepository $userRepository)
    {
    }

    public function create(ResourceOwnerInterface $resourceOwner, string $provider): void
    {
        // $user = new User();

        // $this->userMapper->mapEntity($resourceOwner, $provider, $user);
        // $this->setTimestampable($user);
        // $this->setBlameable($user, $user->getEmail());
        // $this->userRepository->save($user, true);
    }
}
