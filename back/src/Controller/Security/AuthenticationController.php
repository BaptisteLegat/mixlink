<?php

namespace App\Controller\Security;

use App\Security\OAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthenticationController extends AbstractController
{

    public function __construct(private OAuthService $oAuthService)
    {
    }

    #[Route('/auth/{provider}', name: 'app_auth', requirements: ['provider' => 'google|spotify'])]
    public function connect(string $provider): RedirectResponse
    {
        return $this->oAuthService->getRedirectResponse($provider);
    }

    #[Route('/auth/{provider}/callback', name: 'app_auth_callback', requirements: ['provider' => 'google|spotify'])]
public function connectCheck(string $provider, Request $request): RedirectResponse
{
    $user = $this->oAuthService->fetchUser($provider);

    // Ici, vous pouvez stocker l'utilisateur en base de données si nécessaire
    // $this->saveUser($user);

    // Rediriger vers le frontend avec les informations de l'utilisateur
    return new RedirectResponse('http://localhost:3000/profile?email=' . urlencode($user->getEmail()));
}
}
