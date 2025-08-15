<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/authStore';
    import { ElMessage } from 'element-plus';
    import { useProviderIcons } from '@/composables/useProviderIcons';
    import { useRouter } from 'vue-router';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter();
    const { getProviderIcon, getProviderDisplayName } = useProviderIcons();

    const dialogVisible = ref(false);
    const loading = ref(false);
    const currentProvider = ref(null);

    const providerIcon = computed(() => {
        if (!currentProvider.value?.name) {
            return null;
        }

        try {
            return getProviderIcon(currentProvider.value.name);
        } catch (error) {
            console.error('Error getting provider icon:', error);
            return null;
        }
    });

    const providerName = computed(() => {
        if (!currentProvider.value?.name) {
            return '';
        }

        return getProviderDisplayName(currentProvider.value.name);
    });

    const isCurrentAuthProvider = computed(() => {
        if (!currentProvider.value || !authStore.providers || !Array.isArray(authStore.providers)) {
            return false;
        }

        const mainProvider = authStore.providers.find((p) => p.isMain === true);
        return mainProvider && currentProvider.value.name === mainProvider.name;
    });

    function showDialog(provider) {
        if (!provider) {
            return;
        }

        currentProvider.value = provider;
        dialogVisible.value = true;
    }

    async function confirmDisconnect() {
        if (!currentProvider.value?.id) {
            ElMessage.error(t('profile.providers.disconnect_error'));
            return;
        }

        loading.value = true;
        try {
            const response = await authStore.disconnectProvider(currentProvider.value.id);
            dialogVisible.value = false;

            if (response && response.mainProvider) {
                ElMessage.success(t('profile.providers.disconnect_main_success'));
                document.cookie = 'AUTH_TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                authStore.resetUserState();
                router.push('/');
            } else {
                ElMessage.success(t('profile.providers.disconnect_success'));
            }
        } catch (error) {
            const errorMessage = error.translationKey ? t(error.translationKey) : t('profile.providers.disconnect_error');
            ElMessage.error(errorMessage);
        } finally {
            loading.value = false;
            currentProvider.value = null;
        }
    }

    defineExpose({
        showDialog,
    });
</script>

<template>
    <el-dialog
        v-model="dialogVisible"
        :title="t('profile.providers.disconnect_title')"
        width="60%"
        :max-width="500"
        center
        destroy-on-close
        :close-on-click-modal="false"
        class="disconnect-provider-modal"
    >
        <div class="disconnect-provider-content">
            <div class="provider-info" v-if="currentProvider">
                <component :is="providerIcon" :size="32" class="provider-icon" />
                <span class="provider-name">{{ providerName }}</span>
            </div>

            <el-alert type="warning" :description="t('profile.providers.disconnect_warning')" show-icon :closable="false" />

            <el-alert
                v-if="isCurrentAuthProvider"
                type="error"
                :title="t('profile.providers.disconnect_main_title')"
                :description="t('profile.providers.disconnect_main_warning')"
                show-icon
                :closable="false"
            />
        </div>
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="danger" @click="confirmDisconnect" :loading="loading">
                    {{ t('profile.providers.disconnect_confirm') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<style lang="scss" scoped>
    .disconnect-provider-content {
        display: flex;
        flex-direction: column;
        gap: 20px;

        @media (max-width: 768px) {
            gap: 16px;
        }
    }

    .provider-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background-color: var(--el-color-info-light-9);
        border-radius: 8px;

        @media (max-width: 768px) {
            padding: 12px;
            gap: 10px;
        }

        @media (max-width: 480px) {
            padding: 10px;
            gap: 8px;
        }
    }

    .provider-icon {
        color: var(--el-color-primary);
        flex-shrink: 0;

        @media (max-width: 480px) {
            width: 24px !important;
            height: 24px !important;
        }
    }

    .provider-name {
        font-weight: 600;
        font-size: 1.1rem;
        word-break: break-word;

        @media (max-width: 768px) {
            font-size: 1rem;
        }

        @media (max-width: 480px) {
            font-size: 0.95rem;
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

    :deep(.disconnect-provider-modal) {
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
