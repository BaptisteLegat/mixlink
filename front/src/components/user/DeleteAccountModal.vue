<script setup>
    import { ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useAuthStore } from '@/stores/authStore';
    import { ElMessage } from 'element-plus';
    import { useRouter } from 'vue-router';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter();

    const dialogVisible = ref(false);
    const loading = ref(false);

    const emit = defineEmits({
        'account-deleted': {
            type: 'update',
            default: () => {},
        },
    });

    async function confirmDelete() {
        loading.value = true;
        try {
            await authStore.deleteAccount();
            dialogVisible.value = false;
            emit('account-deleted');
            router.push('/');
        } catch (error) {
            console.error('Delete account error:', error);
            const errorMessage = error.translationKey ? t(error.translationKey) : t('profile.delete_account.error');
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
        :title="t('profile.delete_account.title')"
        width="70%"
        :max-width="500"
        center
        destroy-on-close
        class="delete-account-modal"
    >
        <div class="delete-account-content">
            <el-alert type="error" :description="t('profile.delete_account.warning_message')" show-icon :closable="false" />
        </div>
        <template #footer>
            <div class="dialog-footer">
                <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="danger" @click="confirmDelete" :loading="loading">
                    {{ t('profile.delete_account.confirm') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>
<style lang="scss" scoped>
    .delete-account-content {
        display: flex;
        flex-direction: column;
        gap: 16px;

        @media (max-width: 768px) {
            gap: 12px;
        }
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;

        @media (max-width: 768px) {
            justify-content: center;
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

    :deep(.delete-account-modal) {
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
