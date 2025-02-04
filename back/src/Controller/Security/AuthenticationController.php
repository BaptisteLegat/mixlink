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
        $oauthData = $this->oAuthService->fetchUser($provider);

        $user = $this->userManager->create($oauthData, $provider);
        $frontendUrl = $this->getParameter('env(FRONTEND_URL)');

        return new RedirectResponse($frontendUrl);
    }
}
