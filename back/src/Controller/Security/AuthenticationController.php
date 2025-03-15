<?php

namespace App\Controller\Security;

use App\Entity\Provider;
use App\Provider\ProviderManager;
use App\Security\OAuthService;
use App\User\UserManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthenticationController extends AbstractController
{
    public function __construct(
        private OAuthService $oAuthService,
        private ProviderManager $providerManager,
        private UserManager $userManager,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/auth/{provider}', name: 'app_auth', requirements: ['provider' => 'google|spotify'])]
    public function connect(string $provider): RedirectResponse
    {
        return $this->oAuthService->getRedirectResponse($provider);
    }

    #[Route('/auth/{provider}/callback', name: 'app_auth_callback', requirements: ['provider' => 'google|spotify'])]
    public function connectCheck(string $provider): RedirectResponse
    {
        try {
            $oauthData = $this->oAuthService->fetchUser($provider);
            $user = $this->userManager->create($oauthData, $provider);
            $providerEntity = $user->getProviderByName($provider);

            $response = new RedirectResponse($_ENV['FRONTEND_URL']);

            $accessToken = $providerEntity ? $providerEntity->getAccessToken() : null;
            if (null !== $accessToken) {
                $cookie = Cookie::create('AUTH_TOKEN')
                    ->withValue($accessToken)
                    ->withHttpOnly(true)
                    ->withSecure(true)
                    ->withSameSite('strict')
                ;

                $response->headers->setCookie($cookie);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return new RedirectResponse($_ENV['FRONTEND_URL']);
        }
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

        if (null === $user) {
            return new JsonResponse([
                'isAuthenticated' => false,
                'user' => null,
            ]);
        }

        $response = new JsonResponse([
            'isAuthenticated' => true,
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'providers' => array_map(fn (Provider $p): string => $p->getName(), $user->getProviders()->toArray()),
        ]);

        return $response;
    }

    #[Route('/api/logout', name: 'api_logout')]
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
