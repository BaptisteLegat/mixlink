<script setup>
    import { ref, computed, onMounted, watch } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useSessionStore } from '@/stores/sessionStore';
    import { useAuthStore } from '@/stores/authStore';
    import CreateSessionModal from '@/components/session/CreateSessionModal.vue';
    import JoinSessionModal from '@/components/session/JoinSessionModal.vue';
    import { useRoute } from 'vue-router';
    import { useSubscriptionStatus } from '@/composables/useSubscriptionStatus';

    const { t } = useI18n();
    const sessionStore = useSessionStore();
    const authStore = useAuthStore();
    const route = useRoute();
    const { hasActiveSubscription } = useSubscriptionStatus();

    const createSessionModalRef = ref(null);
    const joinSessionModalRef = ref(null);
    const isLoading = ref(false);

    const guestSessionCode = ref(localStorage.getItem('guestSessionCode'));
    const guestPseudo = ref(guestSessionCode.value ? localStorage.getItem(`guestSession_${guestSessionCode.value}`) : null);

    async function checkGuestSession() {
        const code = localStorage.getItem('guestSessionCode');
        const pseudo = code ? localStorage.getItem(`guestSession_${code}`) : null;

        guestSessionCode.value = code;
        guestPseudo.value = pseudo;

        if (code && pseudo) {
            isGuestInSession.value = await sessionStore.checkGuestSession(code, pseudo);
        } else {
            isGuestInSession.value = false;
        }
    }

    watch(
        () => [route.name, route.params.code, localStorage.getItem('guestSessionCode'), localStorage.getItem(`guestSession_${localStorage.getItem('guestSessionCode')}`)],
        () => { checkGuestSession(); },
        { immediate: true }
    );

    const isGuestInSession = computed(() => {
        return !!guestSessionCode.value && !!guestPseudo.value;
    });

    const buttonText = computed(() => {
        if (!authStore.isAuthenticated) {
            if (isGuestInSession.value) {
                return t('session.join_current_session');
            }
            return t('header.join_session');
        }
        if (sessionStore.currentSession) {
            return t('session.rejoin.button');
        }
        return t('session.create.button');
    });

    const buttonIcon = computed(() => {
        if (!authStore.isAuthenticated) {
            if (isGuestInSession.value) {
                return 'Refresh';
            }
            return 'Connection';
        }
        if (sessionStore.currentSession) {
            return 'Refresh';
        }
        return 'Plus';
    });

    const showButton = computed(() => {
        if (!authStore.isAuthenticated) {
            const guestSessionCode = localStorage.getItem('guestSessionCode');
            const guestSessionKey = guestSessionCode ? `guestSession_${guestSessionCode}` : null;
            const guestPseudo = guestSessionKey ? localStorage.getItem(guestSessionKey) : null;

            if (guestSessionCode && (route.name === 'session' || route.name === 'session-join') && route.params.code === guestSessionCode) {
                if (guestPseudo) {
                    return false;
                }
            }
            return true;
        }
        if (!hasActiveSubscription.value) {
            return false;
        }

        if (sessionStore.currentSession) {
            return !((route.name === 'session' || route.name === 'session-join') && route.params.code === sessionStore.currentSession.code);
        }

        return true;
    });

    async function handleButtonClick() {
        if (!authStore.isAuthenticated) {
            if (isGuestInSession.value && guestSessionCode.value) {
                window.location.href = `/session/${guestSessionCode.value}`;
            } else {
                joinSessionModalRef.value?.show();
            }
        } else if (sessionStore.currentSession) {
            try {
                await sessionStore.getSessionByCode(sessionStore.currentSession.code);
                window.location.href = `/session/${sessionStore.currentSession.code}`;
            } catch (error) {
                console.error('Failed to rejoin session:', error);
                sessionStore.leaveCurrentSession();
                createSessionModalRef.value?.showDialog();
            }
        } else {
            createSessionModalRef.value?.showDialog();
        }
    }

    onMounted(async () => {
        if (authStore.isAuthenticated) {
            await refreshCurrentSession();
        }
    });

    watch(
        () => route.name,
        async (newName) => {
            if (authStore.isAuthenticated && shouldRefreshSession(newName)) {
                await refreshCurrentSession();
            }
        },
        { immediate: true }
    );

    function shouldRefreshSession(routeName) {
        return routeName === 'home' || routeName === undefined;
    }

    async function refreshCurrentSession() {
        try {
            await sessionStore.getMySessions();
            if (sessionStore.mySessions.length > 0 && !sessionStore.currentSession) {
                sessionStore.setCurrentSession(sessionStore.mySessions[0]);
            }
        } catch (error) {
            console.error('Failed to refresh sessions:', error);
            sessionStore.leaveCurrentSession();
        }
    }
</script>

<template>
    <div class="session-action" v-if="showButton">
        <el-button type="primary" size="large" :icon="buttonIcon" :loading="isLoading" @click="handleButtonClick" class="session-action-button">
            {{ buttonText }}
        </el-button>
        <CreateSessionModal ref="createSessionModalRef" />
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
