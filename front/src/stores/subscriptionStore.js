import { ref, computed } from 'vue';
import { defineStore } from 'pinia';
import { subscribeToPlan, cancelSubscription, changeSubscription } from '@/services/subscriptionService';
import { useAuthStore } from '@/stores/authStore';

export const useSubscriptionStore = defineStore('subscription', () => {
    const isLoading = ref(false);
    const plans = ref([
        {
            name: 'free',
            displayName: 'home.plans.free.title',
            price: '0',
            features: ['home.plans.free.feature1', 'home.plans.free.feature2', 'home.plans.free.feature3'],
            cta: 'home.plans.free.cta',
            highlighted: false,
            maxParticipants: 3,
        },
        {
            name: 'premium',
            displayName: 'home.plans.premium.title',
            price: '3,99',
            features: ['home.plans.premium.feature1', 'home.plans.premium.feature2', 'home.plans.premium.feature3', 'home.plans.premium.feature4'],
            cta: 'home.plans.premium.cta',
            highlighted: true,
            badge: 'home.plans.popular',
            maxParticipants: 10,
        },
        {
            name: 'enterprise',
            displayName: 'home.plans.enterprise.title',
            price: 'home.plans.enterprise.price',
            features: ['home.plans.enterprise.feature1', 'home.plans.enterprise.feature2'],
            cta: 'home.plans.enterprise.cta',
            highlighted: false,
        },
    ]);

    const hasActiveSubscription = computed(() => {
        const authStore = useAuthStore();
        return authStore.subscription && authStore.subscription.isActive === true && !authStore.subscription.isCanceled;
    });

    const currentPlanName = computed(() => {
        const authStore = useAuthStore();
        if (!authStore.subscription || !authStore.subscription.isActive || authStore.subscription.isCanceled) {
            return 'free';
        }
        return authStore.subscription.plan?.name || 'free';
    });

    const currentPlanMaxParticipants = computed(() => {
        const currentPlan = plans.value.find((plan) => plan.name === currentPlanName.value);
        return currentPlan?.maxParticipants || 3;
    });

    const currentPlan = computed(() => {
        return plans.value.find((plan) => plan.name === currentPlanName.value) || plans.value[0];
    });

    async function subscribe(planName) {
        isLoading.value = true;
        try {
            const authStore = useAuthStore();

            if (authStore.subscription?.stripeSubscriptionId && authStore.subscription.isActive && !authStore.subscription.isCanceled) {
                await changeSubscription(planName);
                await authStore.fetchUser();

                return { success: true };
            } else {
                const result = await subscribeToPlan(planName);

                if (result.url) {
                    window.location.href = result.url;
                } else {
                    await authStore.fetchUser();
                }

                return result;
            }
        } finally {
            isLoading.value = false;
        }
    }

    async function unsubscribe() {
        isLoading.value = true;
        try {
            const authStore = useAuthStore();
            if (!authStore.subscription || !authStore.subscription.isActive) {
                return { success: false, error: 'No active subscription to cancel' };
            }

            await cancelSubscription();
            await authStore.fetchUser();

            return { success: true };
        } finally {
            isLoading.value = false;
        }
    }

    return {
        plans,
        isLoading,
        subscribe,
        unsubscribe,
        hasActiveSubscription,
        currentPlanName,
        currentPlanMaxParticipants,
        currentPlan,
    };
});
