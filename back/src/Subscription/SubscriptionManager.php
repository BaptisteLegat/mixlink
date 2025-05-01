<?php

namespace App\Subscription;

use App\Entity\Plan;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Service\StripeService;
use App\Trait\TraceableTrait;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Stripe\Subscription as StripeSubscription;

class SubscriptionManager
{
    use TraceableTrait;

    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionMapper $subscriptionMapper,
        private StripeService $stripeService,
        private LoggerInterface $logger,
    ) {
    }

    public function create(User $user, Plan $plan, string $stripeSubscriptionId): Subscription
    {
        $existingSubscription = $user->getSubscription();

        $subscription = $this->subscriptionMapper->mapEntity(
            $user,
            $plan,
            $stripeSubscriptionId,
            new DateTimeImmutable(),
            (new DateTimeImmutable())->modify('+1 month'),
            $existingSubscription
        );

        $isUpdate = $existingSubscription instanceof Subscription;

        $this->setTimestampable($subscription, $isUpdate);
        $this->setBlameable($subscription, $user->getEmail(), $isUpdate);

        $this->subscriptionRepository->save($subscription, true);

        return $subscription;
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(User $user): bool
    {
        $subscription = $user->getSubscription();

        if (null === $subscription) {
            return false;
        }

        $stripeSubscriptionId = $subscription->getStripeSubscriptionId();
        if (null === $stripeSubscriptionId) {
            return false;
        }

        try {
            // Annuler sur Stripe
            $stripeResponse = $this->stripeService->cancelSubscription($stripeSubscriptionId);

            if (!($stripeResponse instanceof StripeSubscription)) {
                throw new Exception('Invalid Stripe subscription response');
            }

            // Mettre à jour localement
            $subscription->setCanceledAt(new DateTimeImmutable());
            $subscription->setStatus('canceled');

            // Si l'annulation est à effet immédiat dans Stripe
            if ('canceled' === $stripeResponse->status) {
                $subscription->setEndDate(new DateTimeImmutable());
            }
            // Sinon, on utilise la date de fin de période
            elseif ($stripeResponse->cancel_at_period_end) {
                /** @var int $currentPeriodEnd */
                $currentPeriodEnd = $stripeResponse->current_period_end ?? time();
                $endDate = new DateTimeImmutable('@'.strval($currentPeriodEnd));
                $subscription->setEndDate($endDate);
            }

            $this->subscriptionRepository->save($subscription, true);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Error canceling subscription: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return false;
        }
    }

    /**
     * Change a subscription to a new plan.
     */
    public function changeSubscriptionPlan(User $user, Plan $newPlan): bool
    {
        $subscription = $user->getSubscription();

        if (null === $subscription) {
            return false;
        }

        $stripeSubscriptionId = $subscription->getStripeSubscriptionId();
        if (null === $stripeSubscriptionId) {
            return false;
        }

        $priceId = $this->stripeService->getPriceIdForPlan($newPlan->getName());

        if (null === $priceId) {
            return false;
        }

        try {
            // Mettre à jour sur Stripe
            $stripeResponse = $this->stripeService->changeSubscriptionPlan(
                $stripeSubscriptionId,
                $priceId
            );

            if (!($stripeResponse instanceof StripeSubscription)) {
                throw new Exception('Invalid Stripe subscription response');
            }

            // Mettre à jour localement
            $subscription->setPlan($newPlan);
            $subscription->setCanceledAt(null); // Réinitialiser l'annulation si elle existait
            $subscription->setStatus($stripeResponse->status);

            /** @var int $currentPeriodEnd */
            $currentPeriodEnd = $stripeResponse->current_period_end ?? time();
            $endDate = new DateTimeImmutable('@'.strval($currentPeriodEnd));
            $subscription->setEndDate($endDate);

            $this->subscriptionRepository->save($subscription, true);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Error changing subscription plan: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return false;
        }
    }
}
