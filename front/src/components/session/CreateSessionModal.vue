<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { ElMessage } from 'element-plus';
    import { useSessionStore } from '@/stores/sessionStore';
    import { useSubscriptionStore } from '@/stores/subscriptionStore';
    import ContentCopyIcon from 'vue-material-design-icons/ContentCopy.vue';
    import CheckIcon from 'vue-material-design-icons/Check.vue';

    const { t } = useI18n();
    const sessionStore = useSessionStore();
    const subscriptionStore = useSubscriptionStore();

    const dialogVisible = ref(false);
    const loading = ref(false);
    const createdSession = ref(null);
    const copiedLink = ref(false);
    const copiedCode = ref(false);

    const form = ref({
        name: '',
        maxParticipants: 3,
    });

    const maxParticipantsLimit = computed(() => {
        return subscriptionStore.currentPlanMaxParticipants;
    });

    const planTitle = computed(() => {
        return t(subscriptionStore.currentPlan.displayName);
    });

    const buttonText = computed(() => {
        if (sessionStore.currentSession) {
            return t('session.rejoin.button');
        }
        return t('session.create.button');
    });

    const modalTitle = computed(() => {
        if (sessionStore.currentSession) {
            return t('session.rejoin.title');
        }
        return createdSession.value ? t('session.share.title') : t('session.create.title');
    });

    const rules = {
        name: [
            { required: true, message: t('session.form.validation.name_required'), trigger: 'blur' },
            { min: 3, message: t('session.form.validation.name_min'), trigger: 'blur' },
            { max: 50, message: t('session.form.validation.name_max'), trigger: 'blur' },
        ],
    };

    const formRef = ref(null);

    const shareLink = computed(() => {
        if (!createdSession.value) return '';
        return `${window.location.origin}/session/join/${createdSession.value.code}`;
    });

    function showDialog() {
        dialogVisible.value = true;
        createdSession.value = null;
        resetForm();
    }

    function resetForm() {
        form.value = {
            name: '',
            maxParticipants: maxParticipantsLimit.value,
        };
        if (formRef.value) {
            formRef.value.resetFields();
        }
    }

    async function handleSubmit() {
        if (!formRef.value) {
            return;
        }

        if (sessionStore.currentSession) {
            await handleRejoinSession();
            return;
        }

        await formRef.value.validate(async (valid) => {
            if (valid) {
                loading.value = true;
                try {
                    const session = await sessionStore.createSession(form.value);
                    createdSession.value = session;

                    ElMessage.success(t('session.create.success'));
                } catch (error) {
                    console.error('Error creating session:', error);
                    ElMessage.error(t('session.create.error'));
                } finally {
                    loading.value = false;
                }
            }
        });
    }

    async function handleRejoinSession() {
        loading.value = true;
        try {
            await sessionStore.getSessionByCode(sessionStore.currentSession.code);
            ElMessage.success(t('session.rejoin.success'));

            window.location.href = `/session/${sessionStore.currentSession.code}`;
        } catch (error) {
            console.error('Error rejoining session:', error);
            ElMessage.error(t('session.rejoin.error'));
        } finally {
            loading.value = false;
        }
    }

    async function copyToClipboard(text, type = 'link') {
        try {
            await navigator.clipboard.writeText(text);
            if (type === 'link') {
                copiedLink.value = true;
                setTimeout(() => {
                    copiedLink.value = false;
                }, 2000);
            } else {
                copiedCode.value = true;
                setTimeout(() => {
                    copiedCode.value = false;
                }, 2000);
            }
            ElMessage.success(t('session.share.copied'));
        } catch (error) {
            console.error('Error copying to clipboard:', error);
            ElMessage.error(t('session.share.copy_error'));
        }
    }

    function handleClose() {
        dialogVisible.value = false;
        resetForm();
        createdSession.value = null;
    }

    function navigateToSession() {
        if (createdSession.value) {
            window.location.href = `/session/${createdSession.value.code}`;
        }
    }

    defineExpose({
        showDialog,
    });
</script>

<template>
    <el-dialog v-model="dialogVisible" :title="modalTitle" width="500px" center destroy-on-close @close="handleClose">
        <div v-if="createdSession" class="share-session">
            <el-alert
                :title="t('session.share.success_title')"
                type="success"
                :description="t('session.share.success_description')"
                show-icon
                :closable="false"
                style="margin-bottom: 20px"
            />

            <div class="session-info">
                <el-descriptions :column="1" border>
                    <el-descriptions-item :label="t('session.info.name')">
                        {{ createdSession.name }}
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('session.info.code')">
                        <el-tag type="primary" size="large">{{ createdSession.code }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('session.info.max_participants')">
                        {{ maxParticipantsLimit }} {{ t('session.info.participants') }}
                    </el-descriptions-item>
                </el-descriptions>
            </div>

            <div class="share-options" style="margin-top: 20px">
                <el-text tag="h4">{{ t('session.share.share_with_friends') }}</el-text>

                <div class="share-item" style="margin-bottom: 15px">
                    <el-text tag="label">{{ t('session.share.share_link') }}</el-text>
                    <div class="share-input-group">
                        <el-input :value="shareLink" readonly style="flex: 1" />
                        <el-button
                            @click="copyToClipboard(shareLink, 'link')"
                            type="primary"
                            :icon="copiedLink ? CheckIcon : ContentCopyIcon"
                            style="margin-left: 10px"
                        >
                            {{ copiedLink ? t('session.share.copied_short') : t('session.share.copy') }}
                        </el-button>
                    </div>
                </div>

                <div class="share-item">
                    <el-text tag="label">{{ t('session.share.session_code') }}</el-text>
                    <div class="share-input-group">
                        <el-input :value="createdSession.code" readonly style="flex: 1" />
                        <el-button
                            @click="copyToClipboard(createdSession.code, 'code')"
                            type="primary"
                            :icon="copiedCode ? CheckIcon : ContentCopyIcon"
                            style="margin-left: 10px"
                        >
                            {{ copiedCode ? t('session.share.copied_short') : t('session.share.copy') }}
                        </el-button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="sessionStore.currentSession" class="existing-session">
            <el-alert
                :title="t('session.rejoin.alert_title')"
                type="warning"
                :description="t('session.rejoin.alert_description', { name: sessionStore.currentSession.name })"
                show-icon
                :closable="false"
                style="margin-bottom: 20px"
            />

            <div class="session-info">
                <el-descriptions :column="1" border>
                    <el-descriptions-item :label="t('session.info.name')">
                        {{ sessionStore.currentSession.name }}
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('session.info.code')">
                        <el-tag type="primary" size="large">{{ sessionStore.currentSession.code }}</el-tag>
                    </el-descriptions-item>
                    <el-descriptions-item :label="t('session.info.max_participants')">
                        {{ sessionStore.currentSession.maxParticipants }} {{ t('session.info.participants') }}
                    </el-descriptions-item>
                </el-descriptions>
            </div>
        </div>

        <div v-else class="create-session-form">
            <el-form ref="formRef" :model="form" :rules="rules" label-width="120px">
                <el-form-item :label="t('session.form.name')" prop="name">
                    <el-input v-model="form.name" :placeholder="t('session.form.name_placeholder')" maxlength="50" show-word-limit />
                </el-form-item>

                <el-alert
                    :title="t('session.form.participants_limit', { limit: maxParticipantsLimit, plan: planTitle })"
                    type="info"
                    show-icon
                    :closable="false"
                    style="margin-bottom: 20px"
                />
            </el-form>
        </div>

        <template #footer>
            <div class="dialog-footer">
                <template v-if="createdSession">
                    <el-button @click="handleClose">{{ t('common.close') }}</el-button>
                    <el-button type="primary" @click="navigateToSession">
                        {{ t('session.share.join_session') }}
                    </el-button>
                </template>
                <template v-else-if="sessionStore.currentSession">
                    <el-button @click="handleClose">{{ t('common.cancel') }}</el-button>
                    <el-button type="primary" @click="handleSubmit" :loading="loading">
                        {{ buttonText }}
                    </el-button>
                </template>
                <template v-else>
                    <el-button @click="handleClose">{{ t('common.cancel') }}</el-button>
                    <el-button type="primary" @click="handleSubmit" :loading="loading">
                        {{ t('session.create.button') }}
                    </el-button>
                </template>
            </div>
        </template>
    </el-dialog>
</template>

<style lang="scss" scoped>
    .create-session-form {
        .el-form-item {
            margin-bottom: 20px;
        }
    }

    .share-session {
        .session-info {
            margin-bottom: 20px;
        }

        .share-options {
            .share-item {
                margin-bottom: 15px;

                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                }

                .share-input-group {
                    display: flex;
                    align-items: center;
                }
            }
        }
    }

    .dialog-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
</style>
