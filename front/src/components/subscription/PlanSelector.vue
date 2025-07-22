<script setup>
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue';
    import StarIcon from 'vue-material-design-icons/Star.vue';
    import { useAuthStore } from '@/stores/authStore';
    import { useSubscriptionStore } from '@/stores/subscriptionStore';
    import router from '@/router';
    import { ref, computed } from 'vue';
    import { ElMessage } from 'element-plus';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const subscriptionStore = useSubscriptionStore();

    const props = defineProps({
        compact: {
            type: Boolean,
            default: false,
        },
    });

    const loading = ref(false);
    const plans = computed(() => subscriptionStore.plans);
    const currentPlan = computed(() => {
        if (!authStore.subscription || !authStore.subscription.isActive || authStore.subscription.isCanceled) {
            return null;
        }
        return authStore.subscription.plan.name;
    });

    const isCurrentPlan = (planName) => {
        return currentPlan.value === planName && authStore.subscription?.isActive && !authStore.subscription?.isCanceled;
    };

    const canSubscribe = (planName) => {
        if (isCurrentPlan(planName)) {
            return false;
        }
        return true;
    };

    async function handlePlanClick(plan) {
        if (plan.name === 'enterprise') {
            router.push({ path: '/contact' });
            return;
        }

        if (!authStore.isAuthenticated) {
            router.push({ path: '/login' });
            return;
        }
        try {
            loading.value = plan.name;
            const result = await subscriptionStore.subscribe(plan.name);
            if (result.success) {
                if (result.message && result.message !== 'subscription.start.success') {
                    ElMessage.success(t(result.message));
                }
            } else {
                ElMessage.error(t(result.error));
            }
        } catch (error) {
            console.error('Subscription error:', error);
            ElMessage.error(t('common.error'));
        } finally {
            loading.value = false;
        }
    }
</script>
<template>
    <el-row :gutter="props.compact ? 16 : 32" justify="center" class="pricing-row">
        <el-col
            v-for="(plan, index) in plans"
            :key="index"
            :xs="24"
            :sm="24"
            :md="props.compact ? 24 : 8"
            :lg="props.compact ? 8 : 8"
            :xl="props.compact ? 8 : 8"
            class="pricing-col"
            :class="{ 'pricing-col-compact': props.compact }"
        >
            <el-card
                class="pricing-card"
                :class="{
                    'pricing-card-highlighted': plan.highlighted,
                    'pricing-card-dark': isDark && !plan.highlighted,
                    'pricing-card-highlighted-dark': isDark && plan.highlighted,
                    'pricing-card-compact': props.compact,
                    'pricing-card-current': isCurrentPlan(plan.name),
                }"
                shadow="hover"
                :body-style="{ padding: props.compact ? '20px 16px' : '32px 24px', height: '100%', display: 'flex', flexDirection: 'column' }"
            >
                <div v-if="plan.badge" class="plan-badge">
                    <StarIcon :size="16" />
                    <span>{{ t(plan.badge) }}</span>
                </div>
                <div v-if="isCurrentPlan(plan.name)" class="current-plan-badge">
                    {{ t('profile.current_plan') }}
                </div>
                <el-text tag="h3" class="plan-name">{{ t(plan.displayName) }}</el-text>
                <div class="plan-price">
                    <el-text tag="span" class="currency" v-if="plan.price !== 'home.plans.enterprise.price'">€</el-text>
                    <el-text tag="span" class="amount">{{ plan.price === 'home.plans.enterprise.price' ? t(plan.price) : plan.price }}</el-text>
                    <el-text v-if="plan.price !== 'home.plans.enterprise.price'" tag="span" class="period">
                        {{ t('home.plans.per_month') }}
                    </el-text>
                </div>
                <div class="divider"></div>
                <el-space direction="vertical" class="plan-features" :fill="true" :size="props.compact ? 8 : 16" v-if="!props.compact">
                    <div v-for="(feature, featureIndex) in plan.features" :key="featureIndex" class="feature-item">
                        <CheckCircleIcon :size="18" :fill="plan.highlighted ? '#fff' : isDark ? '#6023c080' : '#6023c0'" />
                        <el-text class="feature-text">{{ t(feature) }}</el-text>
                    </div>
                </el-space>
                <div class="cta-wrapper">
                    <el-button
                        :type="plan.highlighted ? 'primary' : 'default'"
                        class="plan-cta"
                        :class="{
                            'plan-cta-highlighted': plan.highlighted,
                            'plan-cta-secondary': !plan.highlighted,
                        }"
                        @click="handlePlanClick(plan)"
                        :disabled="!canSubscribe(plan.name)"
                        :loading="loading === plan.name"
                        size="large"
                        :style="props.compact ? 'width: 100%' : ''"
                    >
                        <span v-if="isCurrentPlan(plan.name)">{{ t('profile.current_plan') }}</span>
                        <span v-else>{{ t(plan.cta) }}</span>
                    </el-button>
                </div>
            </el-card>
        </el-col>
    </el-row>
</template>

<style lang="scss" scoped>
    .pricing-row {
        width: 100%;
        margin-top: 20px;
    }

    .pricing-col {
        margin-bottom: 32px;
        display: flex;
        min-height: 520px;

        @media (min-width: 768px) {
            &:nth-child(2) {
                margin-top: -20px;
            }
        }
    }

    .pricing-col-compact {
        min-height: unset;

        @media (min-width: 768px) {
            &:nth-child(2) {
                margin-top: 0;
            }
        }
    }

    .pricing-card {
        width: 100%;
        border-radius: 16px;
        background-color: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        text-align: center;
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;

        &:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
    }

    .pricing-card-compact {
        text-align: center;

        &:hover {
            transform: translateY(-4px);
        }
    }

    .pricing-card-dark {
        background-color: #2a2a2a;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);

        &:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.35);
        }
    }

    .pricing-card-highlighted {
        background: linear-gradient(135deg, #6023c0, #753ed6);
        color: white;
        position: relative;
        z-index: 1;

        @media (min-width: 768px) {
            transform: scale(1.05);

            &:hover {
                transform: translateY(-8px) scale(1.05);
            }
        }
    }

    .pricing-card-highlighted-dark {
        background: linear-gradient(135deg, #6023c0, #753ed6);
        color: white;
    }

    .pricing-card-current {
        border: 3px solid #67c23a;
    }

    .plan-badge {
        position: absolute;
        top: 0;
        right: 24px;
        background: linear-gradient(90deg, #ff6e42, #ff9776);
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 4px 12px 6px;
        border-radius: 0 0 8px 8px;
        display: flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 4px 12px rgba(255, 110, 66, 0.3);
    }

    .current-plan-badge {
        position: absolute;
        top: 0;
        left: 24px;
        background-color: #67c23a;
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 4px 12px 6px;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 12px rgba(103, 194, 58, 0.3);
    }

    .plan-name {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 16px;
        color: #333;

        .pricing-card-highlighted & {
            color: white;
        }

        .pricing-card-dark & {
            color: white;
        }

        :deep(.el-text__inner) {
            display: inline-block;
        }

        .pricing-card-compact & {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
    }

    .plan-price {
        margin-bottom: 20px;
        position: relative;
        display: flex;
        justify-content: center;
        align-items: baseline;
        gap: 2px;

        .pricing-card-compact & {
            margin-bottom: 10px;
        }
    }

    .currency {
        font-size: 1.5rem;
        font-weight: 600;
        margin-right: 4px;
        color: #333;

        .pricing-card-highlighted & {
            color: white;
        }

        .pricing-card-dark & {
            color: white;
        }

        .pricing-card-compact & {
            font-size: 1.2rem;
        }
    }

    .amount {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1.1;
        color: #6023c0;

        .pricing-card-highlighted & {
            color: #ffffff;
        }

        .pricing-card-dark & {
            color: #ffffff;
        }

        :deep(.el-text__inner) {
            background: linear-gradient(135deg, #6023c0, #8347de);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;

            .pricing-card-highlighted & {
                background: #ffffff;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .pricing-card-dark & {
                background: #ffffff;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
        }

        .pricing-card-compact & {
            font-size: 2rem;
        }
    }

    .period {
        font-size: 1rem;
        margin-left: 4px;
        color: #666;

        .pricing-card-highlighted & {
            color: rgba(255, 255, 255, 0.9);
        }

        .pricing-card-dark & {
            color: rgba(255, 255, 255, 0.9);
        }

        .pricing-card-compact & {
            font-size: 0.9rem;
        }
    }

    .divider {
        height: 3px;
        width: 80px;
        margin: 0 auto 24px;
        background: linear-gradient(90deg, #6023c0, #8347de);

        .pricing-card-compact & {
            margin: 0 auto 16px;
            width: 60px;
        }
    }

    .plan-features {
        list-style: none;
        padding: 0;
        margin: 0 0 30px 0;
        text-align: left;
        flex-grow: 1;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .feature-text {
        color: #444;
        font-size: 1rem;

        .pricing-card-highlighted & {
            color: white;
        }

        .pricing-card-dark & {
            color: rgba(255, 255, 255, 0.9);
        }
    }

    .cta-wrapper {
        margin-top: auto;
        padding-top: 24px;
    }

    .plan-cta {
        width: 100%;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        overflow: hidden;
        z-index: 1;
        border: none;
        letter-spacing: 0.5px;

        &-secondary {
            background: transparent;
            color: #6023c0;
            border: 2px solid #6023c0;

            &:hover {
                background: rgba(#6023c0, 0.1);
                border-color: #6023c0;
                color: #6023c0;
            }
        }
    }
</style>
