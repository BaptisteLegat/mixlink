<script setup>
    import { ref, reactive } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { ElMessage } from 'element-plus';
    import { useSessionStore } from '@/stores/sessionStore';

    const { t } = useI18n();
    const router = useRouter();
    const sessionStore = useSessionStore();

    const dialogVisible = ref(false);
    const loading = ref(false);
    const formRef = ref(null);

    const form = reactive({
        code: '',
        pseudo: '',
    });

    const rules = {
        code: [
            { required: true, message: t('session.join.validation.code_required'), trigger: 'blur' },
            { min: 6, max: 8, message: t('session.join.validation.code_length'), trigger: 'blur' },
        ],
        pseudo: [
            { required: true, message: t('session.join.validation.pseudo_required'), trigger: 'blur' },
            { min: 2, max: 20, message: t('session.join.validation.pseudo_length'), trigger: 'blur' },
        ],
    };

    const show = () => {
        dialogVisible.value = true;
    };

    const handleClose = () => {
        dialogVisible.value = false;
        form.code = '';
        form.pseudo = '';
        formRef.value?.resetFields();
    };

    const handleSubmit = async () => {
        if (!formRef.value) return;

        try {
            await formRef.value.validate();
            loading.value = true;

            try {
                await sessionStore.joinSession(form.code, form.pseudo);

                ElMessage.success(t('session.join.success'));
                localStorage.setItem('guestSessionCode', form.code);
                localStorage.setItem(`guestSession_${form.code}`, form.pseudo);

                router.push(`/session/${form.code}`);
                handleClose();
            } catch (err) {
                const errorMessage = err.translationKey ? t(err.translationKey) : t('session.join.error');
                ElMessage.error(errorMessage);
                return;
            }
        } catch (error) {
            console.error('Error joining session:', error);
            ElMessage.error(t('session.join.error'));
        } finally {
            loading.value = false;
        }
    };

    defineExpose({
        show,
    });
</script>
<template>
    <el-dialog v-model="dialogVisible" :title="t('session.join.title')" width="400px" :before-close="handleClose" append-to-body>
        <el-form ref="formRef" :model="form" :rules="rules">
            <el-form-item :label="t('session.join.code')" prop="code">
                <el-input
                    v-model="form.code"
                    :placeholder="t('session.join.code_placeholder')"
                    maxlength="8"
                    show-word-limit
                    @keyup.enter="handleSubmit"
                />
            </el-form-item>
            <el-form-item :label="t('session.join.pseudo')" prop="pseudo">
                <el-input
                    v-model="form.pseudo"
                    :placeholder="t('session.join.pseudo_placeholder')"
                    maxlength="20"
                    show-word-limit
                    @keyup.enter="handleSubmit"
                />
            </el-form-item>
        </el-form>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="handleClose">{{ t('common.cancel') }}</el-button>
                <el-button type="primary" :loading="loading" @click="handleSubmit">
                    {{ t('session.join.join_button') }}
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>
<style scoped>
    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
</style>
<style scoped>
    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
</style>
