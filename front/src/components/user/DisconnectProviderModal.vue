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
        if (!currentProvider.value) {
            return null;
        }

        return getProviderIcon(currentProvider.value.name);
    });

    const providerName = computed(() => {
        if (!currentProvider.value) {
            return '';
        }

        return getProviderDisplayName(currentProvider.value.name);
    });

    const isCurrentAuthProvider = computed(() => {
        if (!currentProvider.value) {
            return false;
        }

        return currentProvider.value.name === authStore.user.providers.find((p) => p.isMain === true).name;
    });

    function showDialog(provider) {
        currentProvider.value = provider;
        dialogVisible.value = true;
    }

    async function confirmDisconnect() {
        if (!currentProvider.value) return;

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
            console.error('Error disconnecting provider:', error);
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
    <el-dialog v-model="dialogVisible" :title="t('profile.providers.disconnect_title')" width="30%" center destroy-on-close>
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
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="danger" @click="confirmDisconnect" :loading="loading">
                    {{ t('profile.providers.disconnect_confirm') }}
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>

<style lang="scss" scoped>
    .disconnect-provider-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .provider-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background-color: var(--el-color-info-light-9);
        border-radius: 8px;
    }

    .provider-icon {
        color: var(--el-color-primary);
    }

    .provider-name {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
</style>
