<?php

namespace App\Controller\Security;

use App\Security\OAuthService;
use App\User\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class AuthenticationController extends AbstractController
{
    public function __construct(private OAuthService $oAuthService, private UserManager $userManager)
    {
    }

    #[Route('/auth/{provider}', name: 'app_auth', requirements: ['provider' => 'google|spotify'])]
    public function connect(string $provider): RedirectResponse
    {
        return $this->oAuthService->getRedirectResponse($provider);
    }

    #[Route('/auth/{provider}/callback', name: 'app_auth_callback', requirements: ['provider' => 'google|spotify'])]
    public function connectCheck(string $provider): RedirectResponse
    {
        $user = $this->oAuthService->fetchUser($provider);
        $this->userManager->create($user, $provider);

        // Rediriger vers le frontend avec les informations de l'utilisateur
        return new RedirectResponse('http://localhost:3000/profile?email='.urlencode($user->getEmail()));
    }
}
