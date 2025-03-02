<?php

namespace App\Controller\Security;

use App\Entity\Provider;
use App\Provider\ProviderManager;
use App\Security\OAuthService;
use App\User\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthenticationController extends AbstractController
{
    public function __construct(private OAuthService $oAuthService, private ProviderManager $providerManager, private UserManager $userManager)
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

        $providerEntity = $user->getProviderByName($provider);
        $accessToken = $providerEntity ? $providerEntity->getAccessToken() : null;

        if (null === $accessToken) {
            return new RedirectResponse($_ENV['FRONTEND_URL']);
        }

        $cookie = Cookie::create('AUTH_TOKEN')
            ->withValue($accessToken)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('strict')
        ;

        $response = new RedirectResponse($_ENV['FRONTEND_URL']);
        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function getUserProfile(Request $request): JsonResponse
    {
        $accessToken = $request->cookies->get('AUTH_TOKEN');

        if (null === $accessToken) {
            return new JsonResponse([
                'isAuthenticated' => false,
                'user' => null,
            ]);
        }

        $user = $this->providerManager->findByAccessToken($accessToken);

        if ($user === null) {
            return new JsonResponse([
                'isAuthenticated' => false,
                'user' => null,
            ]);
        }

        $response = new JsonResponse([
            'isAuthenticated' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'providers' => array_map(fn(Provider $p): string => $p->getName(), $user->getProviders()->toArray()),
        ]);

        return $response;
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse([
            'isAuthenticated' => false,
            'user' => null,
        ]);

        $response->headers->clearCookie('AUTH_TOKEN');

        return $response;
    }
}
