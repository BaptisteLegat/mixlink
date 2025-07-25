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
            ElMessage.error(t('profile.unsubscribe.error'));
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
    <el-dialog v-model="dialogVisible" :title="t('profile.unsubscribe.title')" width="30%" center destroy-on-close>
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
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="danger" @click="confirmUnsubscribe" :loading="loading" :disabled="confirmText !== currentPlan">
                    {{ t('profile.unsubscribe.confirm') }}
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>
<style lang="scss" scoped>
    .unsubscribe-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .confirm-input {
        margin-top: 8px;
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
</style>
