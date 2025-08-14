<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/authStore';
    import { useSubscriptionStore } from '@/stores/subscriptionStore';
    import { ElMessage } from 'element-plus';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const subscriptionStore = useSubscriptionStore();

    const dialogVisible = ref(false);
    const loading = ref(false);
    const confirmText = ref('');

    const currentPlan = computed(() => {
        if (!authStore.subscription) return null;
        const plan = authStore.subscription.plan.name;
        return t(`home.plans.${plan}.title`);
    });

    async function confirmUnsubscribe() {
        if (confirmText.value !== currentPlan.value) {
            ElMessage.error(t('profile.unsubscribe.text_mismatch'));

            return;
        }

        loading.value = true;
        try {
            const result = await subscriptionStore.unsubscribe();
            if (result.success) {
                dialogVisible.value = false;
                confirmText.value = '';
                ElMessage.success(t(result.message));
            } else {
                ElMessage.error(t(result.error));
            }
        } catch (error) {
            console.error('Unsubscribe error:', error);
            const errorMessage = error.translationKey ? t(error.translationKey) : t('profile.unsubscribe.error');
            ElMessage.error(errorMessage);
        } finally {
            loading.value = false;
        }
    }

    function showDialog() {
        dialogVisible.value = true;
    }

    defineExpose({
        showDialog,
    });
</script>
<template>
    <el-dialog
        v-model="dialogVisible"
        :title="t('profile.unsubscribe.title')"
        width="60%"
        :max-width="500"
        center
        destroy-on-close
        class="unsubscribe-modal"
    >
        <div class="unsubscribe-content">
            <el-alert
                :title="t('profile.unsubscribe.warning_title')"
                type="warning"
                :description="t('profile.unsubscribe.warning_message')"
                show-icon
                :closable="false"
            />
            <p>{{ t('profile.unsubscribe.confirmation_message', { plan: currentPlan }) }}</p>
            <p>
                <strong>{{ t('profile.unsubscribe.type_to_confirm', { plan: currentPlan }) }}</strong>
            </p>
            <el-input v-model="confirmText" :placeholder="currentPlan" class="confirm-input" />
        </div>
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="danger" @click="confirmUnsubscribe" :loading="loading" :disabled="confirmText !== currentPlan">
                    {{ t('profile.unsubscribe.confirm') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>
<style lang="scss" scoped>
    .unsubscribe-content {
        display: flex;
        flex-direction: column;
        gap: 16px;

        @media (max-width: 768px) {
            gap: 12px;
        }

        p {
            margin: 0;

            @media (max-width: 480px) {
                font-size: 0.9rem;
                line-height: 1.4;
            }
        }
    }

    .confirm-input {
        margin-top: 8px;

        @media (max-width: 480px) {
            margin-top: 6px;
        }
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;

        @media (max-width: 768px) {
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 480px) {
           display: flex;
            flex-direction: column;
            gap: 8px;

            .el-button {
                margin-left: 0 !important;
            }
        }
    }

    :deep(.unsubscribe-modal) {
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

        @media (max-width: 480px) {
            .el-dialog {
                width: 95% !important;
                margin: 10px auto !important;
            }

            .el-dialog__header {
                padding: 12px 12px 6px 12px;

                .el-dialog__title {
                    font-size: 1rem;
                }
            }

            .el-dialog__body {
                padding: 6px 12px 12px 12px;
            }

            .el-dialog__footer {
                padding: 6px 12px 12px 12px;
            }
        }
    }

    :deep(.el-alert) {
        @media (max-width: 768px) {
            .el-alert__title {
                font-size: 0.95rem;
            }

            .el-alert__description {
                font-size: 0.85rem;
                line-height: 1.4;
            }
        }

        @media (max-width: 480px) {
            .el-alert__title {
                font-size: 0.9rem;
            }

            .el-alert__description {
                font-size: 0.8rem;
                line-height: 1.3;
            }
        }
    }
</style>
