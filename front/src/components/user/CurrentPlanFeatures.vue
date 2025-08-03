<script setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/authStore';
    import { useSubscriptionStore } from '@/stores/subscriptionStore';
    import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue';
    import StarIcon from 'vue-material-design-icons/Star.vue';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const subscriptionStore = useSubscriptionStore();

    const currentPlan = computed(() => {
        if (!authStore.subscription || !authStore.subscription.isActive || authStore.subscription.isCanceled) {
            return subscriptionStore.plans.find((plan) => plan.name === 'free');
        }
        return (
            subscriptionStore.plans.find((plan) => plan.name === authStore.subscription.plan.name) ||
            subscriptionStore.plans.find((plan) => plan.name === 'free')
        );
    });

    const isFreePlan = computed(() => {
        return (
            !authStore.subscription ||
            !authStore.subscription.isActive ||
            authStore.subscription.isCanceled ||
            authStore.subscription.plan.name === 'free'
        );
    });

    const planDisplayInfo = computed(() => {
        if (isFreePlan.value) {
            return {
                title: t('home.plans.free.title'),
                badge: null,
                color: '#909399',
            };
        }

        const planName = authStore.subscription.plan.name;
        switch (planName) {
            case 'premium':
                return {
                    title: t('home.plans.premium.title'),
                    badge: t('home.plans.popular'),
                    color: '#6023c0',
                };
            case 'enterprise':
                return {
                    title: t('home.plans.enterprise.title'),
                    badge: null,
                    color: '#409eff',
                };
            default:
                return {
                    title: t('home.plans.free.title'),
                    badge: null,
                    color: '#909399',
                };
        }
    });
</script>
<template>
    <div class="current-plan-features">
        <el-divider />
        <div class="plan-header">
            <el-text tag="p" class="info-label">{{ t('profile.current_plan_features.title') }}</el-text>
            <div class="plan-info">
                <div class="plan-title-wrapper">
                    <h4 class="plan-title" :style="{ color: planDisplayInfo.color }">
                        {{ planDisplayInfo.title }}
                    </h4>
                    <el-tag v-if="planDisplayInfo.badge" type="warning" size="small" class="popular-badge">
                        <StarIcon :size="12" />
                        {{ planDisplayInfo.badge }}
                    </el-tag>
                </div>
            </div>
        </div>

        <div class="features-container">
            <div class="features-list">
                <div v-for="(feature, index) in currentPlan?.features || []" :key="index" class="feature-item">
                    <CheckCircleIcon :size="16" :fill="planDisplayInfo.color" class="feature-icon" />
                    <span class="feature-text">{{ t(feature) }}</span>
                </div>
            </div>

            <div v-if="isFreePlan" class="upgrade-suggestion">
                <el-alert
                    :title="t('profile.current_plan_features.upgrade_title')"
                    type="info"
                    :description="t('profile.current_plan_features.upgrade_description')"
                    show-icon
                    :closable="false"
                />
            </div>
        </div>
    </div>
</template>
<style lang="scss" scoped>
    .current-plan-features {
        margin: 20px 0;
    }

    .info-label {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 16px;
        display: block;
        color: #6023c0;
    }

    .plan-header {
        margin-bottom: 16px;
    }

    .plan-info {
        background-color: var(--el-color-info-light-9);
        border-radius: 8px;
        padding: 16px;
        border: 1px solid var(--el-border-color-light);
    }

    .plan-title-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
    }

    .plan-title {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }

    .popular-badge {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .features-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .features-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        background-color: var(--el-fill-color-blank);
        border-radius: 6px;
        border: 1px solid var(--el-border-color-lighter);
    }

    .feature-icon {
        flex-shrink: 0;
    }

    .feature-text {
        font-size: 14px;
        color: var(--el-text-color-primary);
        line-height: 1.4;
    }

    .upgrade-suggestion {
        margin-top: 8px;
    }

    @media (min-width: 768px) {
        .features-list {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .plan-title-wrapper {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }
</style>
