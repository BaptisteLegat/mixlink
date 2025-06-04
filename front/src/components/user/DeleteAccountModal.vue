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

    const emit = defineEmits(['account-deleted']);

    async function confirmDelete() {
        loading.value = true;
        try {
            await authStore.deleteAccount();
            dialogVisible.value = false;
            emit('account-deleted');
            router.push('/');
        } catch (error) {
            console.error('Delete account error:', error);
            ElMessage.error(t('profile.delete_account.error'));
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
    <el-dialog v-model="dialogVisible" :title="t('profile.delete_account.title')" width="30%" center destroy-on-close>
        <div class="delete-account-content">
            <el-alert
                type="error"
                :description="t('profile.delete_account.warning_message')"
                show-icon
                :closable="false"
            />
        </div>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">{{ t('common.cancel') }}</el-button>
                <el-button type="danger" @click="confirmDelete" :loading="loading">
                    {{ t('profile.delete_account.confirm') }}
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>
<style lang="scss" scoped>
    .delete-account-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
    }
</style>
