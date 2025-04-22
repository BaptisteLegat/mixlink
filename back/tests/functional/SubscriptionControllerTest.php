<?php

namespace App\Tests\Functional;

use App\Provider\ProviderManager;
use App\Repository\UserRepository;
use App\Service\StripeService;
use PHPUnit\Framework\MockObject\MockObject;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class SubscriptionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private static $loader;
    private UserRepository $userRepository;
    private ProviderManager|MockObject $providerManagerMock;
    private StripeService|MockObject $stripeServiceMock;

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/subscriptionController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);

        $this->stripeServiceMock = $this->createMock(StripeService::class);
        static::getContainer()->set(StripeService::class, $this->stripeServiceMock);
    }

    public function testSubscribeWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/subscribe/premium');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSubscribeWithInvalidPlan(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));

        $this->client->request('GET', '/api/subscribe/nonexistent_plan');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Plan not found.', $responseData['error']);
    }

    public function testSubscribeWithValidPlanButNoPriceId(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->stripeServiceMock
            ->method('getPriceIdForPlan')
            ->willReturn(null)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));

        $this->client->request('GET', '/api/subscribe/premium');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Stripe price ID not configured for this plan.', $responseData['error']);
    }

    public function testSubscribeSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $checkoutSessionMock = new class extends Session {
            public string $url = 'https://checkout.stripe.com/test_checkout_session_url';
        };

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->stripeServiceMock
            ->method('getPriceIdForPlan')
            ->willReturn('price_test_id')
        ;

        $this->stripeServiceMock
            ->method('createCheckoutSession')
            ->willReturn($checkoutSessionMock)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('GET', '/api/subscribe/premium');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('https://checkout.stripe.com/test_checkout_session_url', $responseData['url']);
        $this->assertArrayHasKey('url', $responseData);
        $this->assertArrayNotHasKey('error', $responseData);
    }

    public function testSubscribeWithNullCheckoutUrl(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john.doe@test.fr']);

        $checkoutSessionMock = new class extends Session {
            public ?string $url = null;
        };

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user);

        $this->stripeServiceMock
            ->method('getPriceIdForPlan')
            ->willReturn('price_test_id');

        $this->stripeServiceMock
            ->method('createCheckoutSession')
            ->willReturn($checkoutSessionMock);

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('GET', '/api/subscribe/premium');

        $this->assertResponseStatusCodeSame(500);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Unable to create checkout session.', $responseData['error']);
    }
}
