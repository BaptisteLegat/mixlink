<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useSubscriptionStore } from '@/stores/subscriptionStore';
    import { useSessionStore } from '@/stores/sessionStore';
    import { ElMessage } from 'element-plus';
    import WarningIcon from 'vue-material-design-icons/Alert.vue';
    import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue';
    import PlusIcon from 'vue-material-design-icons/Plus.vue';
    import MinusIcon from 'vue-material-design-icons/Minus.vue';
    import InfoIcon from 'vue-material-design-icons/Information.vue';
    import CreditCardIcon from 'vue-material-design-icons/CreditCard.vue';
    import CalculatorIcon from 'vue-material-design-icons/Calculator.vue';

    const { t } = useI18n();
    const subscriptionStore = useSubscriptionStore();
    const sessionStore = useSessionStore();

    const dialogVisible = ref(false);
    const loading = ref(false);
    const selectedPlan = ref(null);
    const currentPlan = ref(null);
    const confirmationChecked = ref(false);

    const getFeatureComparison = computed(() => {
        if (!selectedPlan.value || !currentPlan.value) {
            return null;
        }

        const currentFeatures = currentPlan.value.features || [];
        const selectedFeatures = selectedPlan.value.features || [];

        const gained = selectedFeatures.filter((feature) => !currentFeatures.includes(feature));
        const lost = currentFeatures.filter((feature) => !selectedFeatures.includes(feature));

        return { gained, lost };
    });

    const changeType = computed(() => {
        if (!selectedPlan.value || !currentPlan.value) return 'same';

        const planOrder = { free: 0, premium: 1, enterprise: 2 };
        const currentOrder = planOrder[currentPlan.value.name] || 0;
        const selectedOrder = planOrder[selectedPlan.value.name] || 0;

        if (selectedOrder > currentOrder) return 'upgrade';
        if (selectedOrder < currentOrder) return 'downgrade';
        return 'same';
    });

    const getBillingInfo = computed(() => {
        if (!selectedPlan.value || changeType.value === 'same') return null;

        const isUpgrade = changeType.value === 'upgrade';
        const isDowngrade = changeType.value === 'downgrade';
        const isToFree = selectedPlan.value.name === 'free';
        const isFromFree = currentPlan.value?.name === 'free';

        return {
            isUpgrade,
            isDowngrade,
            isToFree,
            isFromFree,
            immediateCharge: isUpgrade && !isFromFree,
            proration: !isFromFree && (isDowngrade || isToFree),
            cancellation: isToFree,
        };
    });

    const canChangePlan = computed(() => {
        if (selectedPlan.value?.name === 'free' && currentPlan.value?.name !== 'free') {
            if (sessionStore.currentSession) {
                return false;
            }
        }
        return true;
    });

    const blockingReason = computed(() => {
        if (!canChangePlan.value && sessionStore.currentSession) {
            return 'subscription.change.blocking.active_session';
        }
        return null;
    });

    const getWarnings = computed(() => {
        const warnings = [];

        if (changeType.value === 'downgrade') {
            warnings.push('subscription.change.warning.downgrade');

            if (selectedPlan.value.name === 'free' && currentPlan.value?.name === 'premium') {
                warnings.push('subscription.change.warning.participants_limit');
                warnings.push('subscription.change.warning.active_sessions');
                warnings.push('subscription.change.warning.playlist_limit');
            }
        }

        if (selectedPlan.value.name === 'free') {
            warnings.push('subscription.change.warning.free_limitations');
        }

        return warnings;
    });

    async function confirmPlanChange() {
        loading.value = true;
        try {
            const result = await subscriptionStore.subscribe(selectedPlan.value.name);

            if (result.success) {
                const messageKey = result.message || 'subscription.change.success';
                ElMessage.success(t(messageKey));
                dialogVisible.value = false;
            } else {
                const errorKey = result.error || 'subscription.change.error';
                ElMessage.error(t(errorKey));
            }
        } catch (error) {
            console.error('Plan change error:', error);
            const errorKey = error.translationKey || 'common.error';
            ElMessage.error(t(errorKey));
        } finally {
            loading.value = false;
        }
    }

    function showDialog(targetPlan, current) {
        selectedPlan.value = targetPlan;
        currentPlan.value = current;
        dialogVisible.value = true;
    }

    function cancelChange() {
        dialogVisible.value = false;
        selectedPlan.value = null;
        currentPlan.value = null;
    }

    defineExpose({
        showDialog,
    });
</script>
<template>
    <el-dialog
        v-model="dialogVisible"
        :title="t('subscription.change.confirmation.title')"
        width="90%"
        :max-width="600"
        center
        destroy-on-close
        :close-on-click-modal="false"
        class="plan-change-modal"
    >
        <div class="plan-change-content" v-if="selectedPlan && currentPlan">
            <div class="change-summary">
                <h3>{{ t('subscription.change.confirmation.summary') }}</h3>
                <div class="plan-transition">
                    <div class="plan-item current">
                        <span class="plan-label">{{ t('subscription.change.confirmation.current') }}</span>
                        <strong>{{ t(currentPlan.displayName) }}</strong>
                        <span class="plan-price">{{ currentPlan.price }}€/{{ t('common.month') }}</span>
                    </div>
                    <ArrowRightIcon class="arrow-icon" :size="24" />
                    <div class="plan-item selected">
                        <span class="plan-label">{{ t('subscription.change.confirmation.new') }}</span>
                        <strong>{{ t(selectedPlan.displayName) }}</strong>
                        <span class="plan-price">{{ selectedPlan.price }}€/{{ t('common.month') }}</span>
                    </div>
                </div>
            </div>
            <div class="features-comparison" v-if="getFeatureComparison">
                <h4>{{ t('subscription.change.confirmation.features') }}</h4>

                <div class="features-gained" v-if="getFeatureComparison.gained.length > 0">
                    <h5 class="gains-title">
                        <PlusIcon :size="18" :fill="'#67c23a'" />
                        {{ t('subscription.change.confirmation.features_gained') }}
                    </h5>
                    <ul class="feature-list gained">
                        <li v-for="feature in getFeatureComparison.gained" :key="feature">
                            {{ t(feature) }}
                        </li>
                    </ul>
                </div>

                <div class="features-lost" v-if="getFeatureComparison.lost.length > 0">
                    <h5 class="losses-title">
                        <MinusIcon :size="18" :fill="'#f56c6c'" />
                        {{ t('subscription.change.confirmation.features_lost') }}
                    </h5>
                    <ul class="feature-list lost">
                        <li v-for="feature in getFeatureComparison.lost" :key="feature">
                            {{ t(feature) }}
                        </li>
                    </ul>
                </div>
            </div>
            <div class="billing-info" v-if="getBillingInfo">
                <h4>{{ t('subscription.change.confirmation.billing_title') }}</h4>
                <div class="billing-details">
                    <p v-if="getBillingInfo.isToFree">
                        <InfoIcon :size="18" :fill="'var(--el-color-info)'" />
                        {{ t('subscription.change.confirmation.billing.cancel_info') }}
                    </p>
                    <p v-else-if="getBillingInfo.immediateCharge">
                        <CreditCardIcon :size="18" :fill="'var(--el-color-info)'" />
                        {{ t('subscription.change.confirmation.billing.immediate_charge') }}
                    </p>
                    <p v-else-if="getBillingInfo.isFromFree && getBillingInfo.isUpgrade">
                        <CreditCardIcon :size="18" :fill="'var(--el-color-info)'" />
                        {{ t('subscription.change.confirmation.billing.first_subscription') }}
                    </p>
                    <p v-if="getBillingInfo.proration">
                        <CalculatorIcon :size="18" :fill="'var(--el-color-info)'" />
                        {{ t('subscription.change.confirmation.billing.proration') }}
                    </p>
                </div>
            </div>

            <div class="blocking-message" v-if="!canChangePlan">
                <el-alert
                    :title="t('subscription.change.blocking.title')"
                    :description="t(blockingReason)"
                    type="error"
                    show-icon
                    :closable="false"
                />
            </div>

            <div class="warnings" v-if="getWarnings.length > 0 && canChangePlan">
                <h4 class="warnings-title">
                    <WarningIcon :size="18" :fill="'#e6a23c'" />
                    {{ t('subscription.change.confirmation.warnings') }}
                </h4>
                <el-alert
                    v-for="warning in getWarnings"
                    :key="warning"
                    :title="t(warning)"
                    type="warning"
                    show-icon
                    :closable="false"
                    class="warning-alert"
                />
            </div>
            <div class="final-confirmation" v-if="canChangePlan">
                <el-checkbox v-model="confirmationChecked" size="large">
                    {{ t('subscription.change.confirmation.understand') }}
                </el-checkbox>
            </div>
        </div>
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="cancelChange">
                    {{ t('common.cancel') }}
                </el-button>
                <el-button
                    type="primary"
                    @click="confirmPlanChange"
                    :loading="loading"
                    :disabled="!canChangePlan || (!confirmationChecked && canChangePlan)"
                >
                    {{ t('subscription.change.confirmation.confirm') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>
<style lang="scss" scoped>
    .plan-change-content {
        display: flex;
        flex-direction: column;
        gap: 24px;

        @media (max-width: 768px) {
            gap: 16px;
        }
    }

    .change-summary {
        h3 {
            margin-bottom: 16px;
            color: var(--el-color-primary);

            @media (max-width: 768px) {
                font-size: 1.1rem;
                margin-bottom: 12px;
            }
        }
    }

    .plan-transition {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--el-bg-color-page);
        border-radius: 8px;
        padding: 16px;

        @media (max-width: 768px) {
            flex-direction: column;
            gap: 12px;
        }
    }

    .plan-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        flex: 1;

        .plan-label {
            font-size: 0.875rem;
            color: var(--el-text-color-secondary);
            margin-bottom: 4px;
        }

        strong {
            font-size: 1.125rem;
            margin-bottom: 4px;
        }

        .plan-price {
            font-size: 0.875rem;
            color: var(--el-color-primary);
            font-weight: 600;
        }
    }

    .arrow-icon {
        color: var(--el-color-primary);
        margin: 0 16px;

        @media (max-width: 768px) {
            transform: rotate(90deg);
            margin: 8px 0;
        }
    }

    .features-comparison {
        h4 {
            margin-bottom: 16px;
            color: var(--el-color-primary);

            @media (max-width: 768px) {
                font-size: 1rem;
                margin-bottom: 12px;
            }
        }

        .gains-title,
        .losses-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;

            @media (max-width: 768px) {
                font-size: 0.9rem;
                gap: 6px;
            }
        }

        .gains-title {
            color: #67c23a;
        }

        .losses-title {
            color: #f56c6c;
        }
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0 0 16px 0;

        li {
            padding: 4px 0;
            padding-left: 24px;
            position: relative;

            &:before {
                content: '';
                position: absolute;
                left: 8px;
                top: 50%;
                transform: translateY(-50%);
                width: 6px;
                height: 6px;
                border-radius: 50%;
            }
        }

        &.gained li:before {
            background-color: #67c23a;
        }

        &.lost li:before {
            background-color: #f56c6c;
        }
    }

    .billing-info {
        h4 {
            margin-bottom: 16px;
            color: var(--el-color-primary);

            @media (max-width: 768px) {
                font-size: 1rem;
                margin-bottom: 12px;
            }
        }

        .billing-details {
            p {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 8px;

                @media (max-width: 768px) {
                    font-size: 0.9rem;
                    gap: 6px;
                    align-items: flex-start;
                    line-height: 1.4;
                }
            }
        }
    }

    .warnings {
        h4.warnings-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            color: #e6a23c;
        }

        .warning-alert {
            margin-bottom: 8px;
        }
    }

    .blocking-message {
        margin-bottom: 16px;

        @media (max-width: 768px) {
            margin-bottom: 12px;
        }
    }

    .final-confirmation {
        background: var(--el-bg-color-page);
        border-radius: 8px;
        padding: 16px;
        border: 2px solid var(--el-color-primary-light-8);

        :deep(.el-checkbox) {
            width: 100%;

            .el-checkbox__label {
                white-space: normal;
                word-wrap: break-word;
                line-height: 1.4;
                padding-left: 8px;

                @media (max-width: 768px) {
                    font-size: 0.9rem;
                    line-height: 1.3;
                }
            }

            .el-checkbox__input {
                align-self: flex-start;
                margin-top: 2px;
            }
        }

        @media (max-width: 768px) {
            padding: 12px;
        }
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }

    :deep(.plan-change-modal) {
        @media (max-width: 768px) {
            .el-dialog {
                width: 95% !important;
                margin: 0 auto !important;
            }

            .el-dialog__header {
                padding: 16px 16px 8px 16px;

                .el-dialog__title {
                    font-size: 1.1rem;
                    line-height: 1.3;
                }
            }

            .el-dialog__body {
                padding: 8px 16px 16px 16px;
            }

            .el-dialog__footer {
                padding: 8px 16px 16px 16px;
            }
        }
    }
</style>
