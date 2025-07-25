<?php

namespace App\Tests\Functional;

use App\Entity\Subscription;
use App\Provider\ProviderManager;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\StripeService;
use App\Subscription\SubscriptionManager;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;
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
    private SubscriptionManager|MockObject $subscriptionManagerMock;
    private PlanRepository $planRepository;

    public static function setUpBeforeClass(): void
    {
        self::$loader = static::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        self::$loader->load([
            './fixtures/functionalTests/subscriptionController.yaml',
        ]);
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->planRepository = static::getContainer()->get(PlanRepository::class);

        $this->providerManagerMock = $this->createMock(ProviderManager::class);
        static::getContainer()->set(ProviderManager::class, $this->providerManagerMock);

        $this->stripeServiceMock = $this->createMock(StripeService::class);
        static::getContainer()->set(StripeService::class, $this->stripeServiceMock);

        $this->subscriptionManagerMock = $this->createMock(SubscriptionManager::class);
        static::getContainer()->set(SubscriptionManager::class, $this->subscriptionManagerMock);
    }

    private function setupUserWithActiveSubscription(string $planName = 'premium'): object
    {
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);
        $plan = $this->planRepository->findOneBy(['name' => $planName]);

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setPlan($plan);
        $subscription->setStripeSubscriptionId('sub_test_123456');
        $subscription->setStatus('active');
        $subscription->setStartDate(new DateTimeImmutable());
        $subscription->setEndDate(new DateTimeImmutable('+30 days'));

        $reflectionProperty = new ReflectionProperty($user, 'subscription');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($user, $subscription);

        return (object) [
            'user' => $user,
            'plan' => $plan,
            'subscription' => $subscription,
        ];
    }

    public function testSubscribeWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/subscribe/premium');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSubscribeWithInvalidPlan(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));

        $this->client->request('GET', '/api/subscribe/nonexistent_plan');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.start.error_plan_not_found', $responseData['error']);
    }

    public function testSubscribeWithValidPlanButNoPriceId(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);

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
        $this->assertEquals('subscription.start.error_stripe_price_id_not_configured', $responseData['error']);
    }

    public function testSubscribeSuccess(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);

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
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);

        $checkoutSessionMock = new class extends Session {
            public ?string $url = null;
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

        $this->assertResponseStatusCodeSame(500);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.start.error_unable_to_create_checkout_session', $responseData['error']);
    }

    public function testCancelSubscriptionWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/subscription/cancel');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCancelSubscriptionWithNoActiveSubscription(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);

        $reflectionProperty = new ReflectionProperty($user, 'subscription');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($user, null);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/cancel');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.cancel.error_no_active_subscription', $responseData['error']);
    }

    public function testCancelSubscriptionFailure(): void
    {
        $userWithSub = $this->setupUserWithActiveSubscription();
        $user = $userWithSub->user;

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($user)
            ->willReturn(false)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/cancel');

        $this->assertResponseStatusCodeSame(500);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.cancel.error_failed_to_cancel_subscription', $responseData['error']);
    }

    public function testCancelSubscriptionSuccess(): void
    {
        $userWithSub = $this->setupUserWithActiveSubscription();
        $user = $userWithSub->user;

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('cancelSubscription')
            ->with($user)
            ->willReturn(true)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/cancel');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }

    public function testChangeSubscriptionWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/subscription/change/free');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testChangeSubscriptionWithNoActiveSubscription(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'john-doe-subscription@test.fr']);

        $reflectionProperty = new ReflectionProperty($user, 'subscription');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($user, null);

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/change/free');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.change.error_no_active_subscription', $responseData['error']);
    }

    public function testChangeSubscriptionWithInvalidPlan(): void
    {
        $userWithSub = $this->setupUserWithActiveSubscription();
        $user = $userWithSub->user;

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/change/nonexistent_plan');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.change.error_plan_not_found', $responseData['error']);
    }

    public function testChangeSubscriptionToSamePlan(): void
    {
        $userWithSub = $this->setupUserWithActiveSubscription('premium');
        $user = $userWithSub->user;

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/change/premium');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.change.error_already_subscribed_to_this_plan', $responseData['error']);
    }

    public function testChangeSubscriptionFailure(): void
    {
        $userWithSub = $this->setupUserWithActiveSubscription('premium');
        $user = $userWithSub->user;

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('changeSubscriptionPlan')
            ->with($user, $this->callback(function ($plan) {
                return 'free' === $plan->getName();
            }))
            ->willReturn(false)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/change/free');

        $this->assertResponseStatusCodeSame(500);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('subscription.change.error_failed_to_change_subscription', $responseData['error']);
    }

    public function testChangeSubscriptionSuccess(): void
    {
        $userWithSub = $this->setupUserWithActiveSubscription('premium');
        $user = $userWithSub->user;

        $this->providerManagerMock
            ->method('findByAccessToken')
            ->willReturn($user)
        ;

        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('changeSubscriptionPlan')
            ->with($user, $this->callback(function ($plan) {
                return 'free' === $plan->getName();
            }))
            ->willReturn(true)
        ;

        $this->client->getCookieJar()->set(new Cookie('AUTH_TOKEN', 'valid_access_token'));
        $this->client->request('POST', '/api/subscription/change/free');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
    }
}
