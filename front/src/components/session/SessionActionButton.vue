<script setup>
    import { ref, computed, onMounted } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useSessionStore } from '@/stores/sessionStore';
    import { useAuthStore } from '@/stores/authStore';
    import CreateSessionModal from '@/components/session/CreateSessionModal.vue';
    import JoinSessionModal from '@/components/session/JoinSessionModal.vue';

    const { t } = useI18n();
    const sessionStore = useSessionStore();
    const authStore = useAuthStore();

    const createSessionModalRef = ref(null);
    const joinSessionModalRef = ref(null);
    const existingSession = ref(null);
    const isLoading = ref(false);

    const buttonText = computed(() => {
        if (!authStore.isAuthenticated) {
            return t('session.join.button');
        }
        if (existingSession.value) {
            return t('session.rejoin.button');
        }
        return t('session.create.button');
    });

    const buttonIcon = computed(() => {
        if (!authStore.isAuthenticated) {
            return 'Connection';
        }
        if (existingSession.value) {
            return 'Refresh';
        }
        return 'Plus';
    });

    onMounted(() => {
        if (authStore.isAuthenticated) {
            checkForExistingSession();
        }
    });

    async function checkForExistingSession() {
        if (!authStore.isAuthenticated) return;

        try {
            isLoading.value = true;
            const sessions = await sessionStore.getMySessions();
            existingSession.value = sessions.find((session) => session.isActive) || null;
        } catch (error) {
            console.error('Error checking for existing session:', error);
            existingSession.value = null;
        } finally {
            isLoading.value = false;
        }
    }

    async function handleButtonClick() {
        if (!authStore.isAuthenticated) {
            joinSessionModalRef.value?.show();
        } else if (existingSession.value) {
            try {
                await sessionStore.getSessionByCode(existingSession.value.code);
                window.location.href = `/session/${existingSession.value.code}`;
            } catch (error) {
                console.error('Error rejoining session:', error);
                existingSession.value = null;
                createSessionModalRef.value?.showDialog();
            }
        } else {
            createSessionModalRef.value?.showDialog();
        }
    }

    async function handleSessionCreated() {
        await checkForExistingSession();
    }

    defineExpose({
        checkForExistingSession,
    });
</script>

<template>
    <div class="session-action">
        <el-button type="primary" size="large" :icon="buttonIcon" :loading="isLoading" @click="handleButtonClick" class="session-action-button">
            {{ buttonText }}
        </el-button>

        <CreateSessionModal ref="createSessionModalRef" @session-created="handleSessionCreated" />
        <JoinSessionModal ref="joinSessionModalRef" />
    </div>
</template>

<style lang="scss" scoped>
    .session-action {
        display: flex;
        justify-content: center;

        .session-action-button {
            min-width: 200px;
            height: 48px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;

            &:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            transition: all 0.3s ease;
        }
    }
</style>
