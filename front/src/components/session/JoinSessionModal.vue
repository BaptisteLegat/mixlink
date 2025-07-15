<script setup>
    import { ref, reactive } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { ElMessage } from 'element-plus';

    const { t } = useI18n();
    const router = useRouter();

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

            const sessionResponse = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/session/${form.code}`, {
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!sessionResponse.ok) {
                if (sessionResponse.status === 404) {
                    ElMessage.error(t('session.join.session_not_found'));
                } else {
                    ElMessage.error(t('session.join.error'));
                }
                return;
            }

            const session = await sessionResponse.json();

            if (!session.isActive) {
                ElMessage.error(t('session.join.session_inactive'));
                return;
            }

            const joinResponse = await fetch(`${import.meta.env.VITE_API_BASE_URL}/api/session/${form.code}/join`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pseudo: form.pseudo,
                }),
            });

            if (!joinResponse.ok) {
                const errorData = await joinResponse.json();
                ElMessage.error(errorData.error || t('session.join.error'));
                return;
            }

            ElMessage.success(t('session.join.success'));
            router.push(`/session/${form.code}`);
            handleClose();
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
        <el-form ref="formRef" :model="form" :rules="rules" label-width="120px">
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
