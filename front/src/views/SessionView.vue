<script setup>
    import { ref, onMounted, onUnmounted, computed } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import { useI18n } from 'vue-i18n';
    import { useSessionStore } from '@/stores/sessionStore';
    import { useAuthStore } from '@/stores/authStore';
    import { useMercureStore } from '@/stores/mercureStore';
    import { ElMessage, ElMessageBox } from 'element-plus';
    import SessionHeader from '@/components/session/SessionHeader.vue';
    import GuestJoinCard from '@/components/session/GuestJoinCard.vue';
    import ParticipantsCard from '@/components/session/ParticipantsCard.vue';
    import PlaylistCard from '@/components/session/PlaylistCard.vue';
    import MusicSearchBar from '@/components/session/MusicSearchBar.vue';
    import PlaylistExportButton from '@/components/playlist/PlaylistExportButton.vue';

    const { t } = useI18n();
    const route = useRoute();
    const router = useRouter();
    const sessionStore = useSessionStore();
    const authStore = useAuthStore();
    const mercureStore = useMercureStore();

    const sessionCode = ref(route.params.code);
    const session = ref(null);
    const isLoading = ref(true);
    const error = ref(null);
    const participants = ref([]);
    const currentUserPseudo = ref('');
    const isHost = ref(false);
    const hasJoined = ref(false);

    const participantCount = computed(() => participants.value.length);

    const filteredParticipants = computed(() => {
        if (!session.value) return participants.value;

        return participants.value.filter((p) => p.pseudo !== session.value.host.firstName && p.pseudo !== session.value.host.email);
    });

    const currentAuthProvider = computed(() => {
        if (!authStore.user) {
            return null;
        }

        return authStore.providers.find((p) => p.isMain === true);
    });

    function checkGuestJoined() {
        if (authStore.isAuthenticated) {
            hasJoined.value = false;
            return;
        }
        const guestSessionCode = localStorage.getItem('guestSessionCode');
        const guestPseudo = guestSessionCode ? localStorage.getItem(`guestSession_${guestSessionCode}`) : null;
        hasJoined.value = !!guestSessionCode && !!guestPseudo && sessionCode.value === guestSessionCode;
        if (hasJoined.value && guestPseudo) {
            currentUserPseudo.value = guestPseudo;
        }
    }

    async function loadSession() {
        try {
            isLoading.value = true;
            const loadedSession = await sessionStore.getSessionByCode(sessionCode.value);
            session.value = loadedSession;

            if (authStore.isAuthenticated && authStore.user) {
                isHost.value = session.value.host.id === authStore.user.id;
                if (isHost.value) {
                    currentUserPseudo.value = session.value.host.firstName || t('session.participants.host');
                    hasJoined.value = true;
                }
            }

            await loadParticipants();

            error.value = null;
        } catch (err) {
            console.error('Error loading session:', err);
            const errorMessage = err.translationKey ? t(err.translationKey) : t('session.error.not_found');
            error.value = errorMessage;
        } finally {
            isLoading.value = false;
        }
    }

    async function loadParticipants() {
        try {
            const participantsList = await sessionStore.getParticipants(sessionCode.value);
            participants.value = participantsList;

            if (!authStore.isAuthenticated) {
                const guestSessionCode = localStorage.getItem('guestSessionCode');
                const guestPseudo = guestSessionCode ? localStorage.getItem(`guestSession_${guestSessionCode}`) : null;

                hasJoined.value =
                    !!guestPseudo &&
                    participants.value.some(
                        (p) => p.pseudo === guestPseudo && p.pseudo !== session.value.host.firstName && p.pseudo !== session.value.host.email
                    );
                if (hasJoined.value && guestPseudo) {
                    currentUserPseudo.value = guestPseudo;
                }
            }
        } catch (err) {
            console.error('Error loading participants:', err);
        }
    }

    function connectMercure() {
        mercureStore.connect({
            sessionCode: session.value.code,
            isHost: isHost.value,
            onMessage: handleMercureMessage,
            onError: (e) => {
                console.error('Mercure connection error:', e);
                const errorKey = e.translationKey ? e.translationKey : 'session.mercure.connection_error';
                ElMessage.error(t(errorKey));
            },
        });
    }

    async function kickParticipant(pseudo) {
        try {
            await ElMessageBox.confirm(t('session.kick.confirmation', { pseudo }), t('session.kick.title'), {
                confirmButtonText: t('session.kick.confirm'),
                cancelButtonText: t('common.cancel'),
                type: 'warning',
            });
            await sessionStore.removeParticipant(sessionCode.value, pseudo, 'kick');
            await loadParticipants();
        } catch (error) {
            const errorMessage = error.translationKey ? t(error.translationKey) : error.message ? t(error.message) : t('session.kick.error');
            ElMessage.error(errorMessage);
        }
    }

    async function leaveSession() {
        try {
            await ElMessageBox.confirm(t('session.leave.confirmation'), t('session.leave.title'), {
                confirmButtonText: t('session.leave.confirm'),
                cancelButtonText: t('common.cancel'),
                type: 'warning',
            });

            if (!isHost.value && currentUserPseudo.value) {
                await sessionStore.removeParticipant(sessionCode.value, currentUserPseudo.value, 'leave');
                localStorage.removeItem('guestSessionCode');
            }

            mercureStore.disconnect();
            sessionStore.leaveCurrentSession();
            router.push('/');
        } catch (error) {
            const errorMessage = error.translationKey ? t(error.translationKey) : error.message ? t(error.message) : t('session.leave.error');
            ElMessage.error(errorMessage);
        }
    }

    async function endSession() {
        try {
            await ElMessageBox.confirm(t('session.end.confirmation'), t('session.end.title'), {
                confirmButtonText: t('session.end.confirm'),
                cancelButtonText: t('common.cancel'),
                type: 'warning',
            });

            mercureStore.disconnect();
            await sessionStore.endSession(sessionCode.value);
            ElMessage.success(t('session.end.success'));
            router.push('/');
        } catch (error) {
            if (error.message) {
                const errorMessage = error.translationKey ? t(error.translationKey) : t('session.end.error');
                ElMessage.error(errorMessage);
            }
        }
    }

    function joinAsGuest() {
        if (!currentUserPseudo.value.trim()) {
            ElMessage.error(t('session.join.pseudo_required'));
            return;
        }

        joinSession();
    }

    async function joinSession() {
        try {
            const alreadyIn = await sessionStore.checkGuestSession(sessionCode.value, currentUserPseudo.value.trim());
            if (alreadyIn) {
                ElMessage.error(t('session.join.already_joined'));
                return;
            }

            const result = await sessionStore.joinSession(sessionCode.value, currentUserPseudo.value.trim());

            ElMessage.success(t(result.message));
            localStorage.setItem('guestSessionCode', sessionCode.value);
            localStorage.setItem(`guestSession_${sessionCode.value}`, currentUserPseudo.value.trim());

            window.dispatchEvent(new Event('guest-joined'));

            await loadParticipants();
        } catch (error) {
            const errorMessage = error.translationKey ? t(error.translationKey) : error.message ? t(error.message) : t('session.join.error');
            ElMessage.error(errorMessage);
        }
    }

    function handleMercureMessage(data) {
        switch (data.event) {
            case 'participant_joined':
            case 'participant_removed':
                reloadParticipantsWithNotification(data);

                break;
            case 'session_ended':
                ElMessage.success(t('session.end.success'));
                mercureStore.disconnect();
                sessionStore.leaveCurrentSession();
                router.push('/');

                break;
            case 'playlist_updated':
                break;
            default:
                console.log('Unknown Mercure event:', data.event);
        }
    }

    function reloadParticipantsWithNotification(data) {
        loadParticipants();
        if (data.event === 'participant_joined') {
            ElMessage({
                message: `${data.participant.pseudo} a rejoint la session`,
                type: 'success',
                duration: 3000,
            });
        } else if (data.event === 'participant_removed') {
            if (data.participant.reason === 'kick') {
                handleParticipantKicked(data);
            } else {
                ElMessage({
                    message: `${data.participant.pseudo} a quitté la session`,
                    type: 'info',
                    duration: 3000,
                });
            }
        }
    }

    function handleParticipantKicked(data) {
        const kickedPseudo = data.participant.pseudo;
        const guestSessionCode = localStorage.getItem('guestSessionCode');
        const guestPseudo = guestSessionCode ? localStorage.getItem(`guestSession_${guestSessionCode}`) : null;
        if (!authStore.isAuthenticated && kickedPseudo === guestPseudo && sessionCode.value === guestSessionCode) {
            localStorage.removeItem('guestSessionCode');
            localStorage.removeItem(`guestSession_${guestSessionCode}`);
            ElMessage.warning(t('session.kick.kicked_notification'));
            router.push('/');
        } else {
            ElMessage.info(t('session.kick.success', { pseudo: kickedPseudo }));
            loadParticipants();
        }
    }
    onMounted(async () => {
        checkGuestJoined();
        await loadSession();
        if (session.value) {
            connectMercure();
        }
    });

    onUnmounted(() => {
        mercureStore.disconnect();
    });
</script>
<template>
    <div class="session-page">
        <div v-if="isLoading" class="loading-container">
            <el-skeleton :rows="5" animated />
        </div>
        <div v-else-if="error" class="error-container">
            <el-result icon="error" :title="error" :sub-title="t('session.error.not_found_description')">
                <template #extra>
                    <el-button type="primary" @click="router.push('/')">
                        {{ t('session.error.back_home') }}
                    </el-button>
                </template>
            </el-result>
        </div>
        <div v-else class="session-container">
            <SessionHeader :session="session" :isHost="isHost" :hasJoined="hasJoined" @end-session="endSession" @leave-session="leaveSession" />
            <GuestJoinCard
                v-if="!hasJoined"
                :currentUserPseudo="currentUserPseudo"
                :hasJoined="hasJoined"
                @update:pseudo="(val) => (currentUserPseudo = val)"
                @join-as-guest="joinAsGuest"
            />
            <ParticipantsCard
                :session="session"
                :participants="participants"
                :filteredParticipants="filteredParticipants"
                :participantCount="participantCount"
                :isHost="isHost"
                @kick-participant="kickParticipant"
            />
            <MusicSearchBar v-if="hasJoined" />
            <div v-if="hasJoined" class="playlist-section">
                <PlaylistCard :playlist="sessionStore.currentSession?.playlist" />
                <div class="export-section">
                    <PlaylistExportButton
                        v-if="sessionStore.currentSession?.playlist?.id && isHost"
                        :playlistId="sessionStore.currentSession.playlist.id"
                        :songsCount="sessionStore.currentSession.playlist?.songs?.length || 0"
                        :platform="currentAuthProvider?.name"
                        :hasBeenExported="sessionStore.currentSession.playlist?.hasBeenExported"
                        :isFreePlan="!authStore.subscription || !authStore.subscription.isActive || 'free' === authStore.subscription.plan?.name"
                        :exportedPlaylistUrl="sessionStore.currentSession.playlist?.exportedPlaylistUrl"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .session-page {
        min-height: 100vh;
        padding: 20px;
        position: relative;
    }

    .loading-container,
    .error-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 60vh;
    }

    .session-container {
        max-width: 1200px;
        margin: 0 auto;

        .session-header {
            margin-bottom: 20px;

            .session-info {
                .session-main-info {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    margin-bottom: 10px;

                    .session-title {
                        margin: 0;
                        color: var(--el-text-color-primary);
                    }
                }

                .session-description {
                    color: var(--el-text-color-regular);
                    margin-bottom: 15px;
                }
            }

            .session-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 15px;
            }
        }

        .guest-join-card {
            margin-bottom: 20px;

            .guest-join-content {
                text-align: center;

                h3 {
                    margin-bottom: 10px;
                    color: var(--el-text-color-primary);
                }

                p {
                    color: var(--el-text-color-regular);
                    margin-bottom: 20px;
                }

                .pseudo-input-group {
                    display: flex;
                    gap: 10px;
                    max-width: 400px;
                    margin: 0 auto;

                    .el-input {
                        flex: 1;
                    }
                }
            }
        }

        .participants-card {
            margin-bottom: 20px;

            .participants-header {
                display: flex;
                align-items: center;
                font-weight: 600;

                .participants-count {
                    margin-left: 10px;
                }
            }

            .participants-list {
                .participant-item {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 10px 0;
                    border-bottom: 1px solid var(--el-border-color-lighter);

                    &:last-child {
                        border-bottom: none;
                    }

                    .participant-name {
                        flex: 1;
                        font-weight: 500;
                    }
                }
            }

            .no-participants {
                text-align: center;
                padding: 20px;
                color: var(--el-text-color-regular);
            }
        }

        .playlist-section {
            .playlist-card {
                .playlist-header {
                    display: flex;
                    align-items: center;
                    font-weight: 600;
                }

                .playlist-content {
                    min-height: 300px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            }

            .export-section {
                margin-top: 20px;
                display: flex;
                justify-content: center;
            }
        }
    }

    @media (max-width: 768px) {
        .session-page {
            padding: 10px;
        }

        .session-container {
            .session-header {
                .session-info {
                    .session-main-info {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 10px;
                    }
                }
                .session-actions {
                    margin-top: 10px;
                }
            }

            .guest-join-card {
                .guest-join-content {
                    h3 {
                        font-size: 18px;
                    }

                    p {
                        font-size: 14px;
                    }

                    .pseudo-input-group {
                        flex-direction: column;

                        .el-input {
                            width: 100%;
                        }
                    }
                }
            }

            .participants-card {
                .participants-header {
                    font-size: 16px;

                    .participants-count {
                        font-size: 14px;
                    }
                }

                .participants-list {
                    .participant-item {
                        padding: 8px 0;

                        .participant-name {
                            font-size: 14px;
                        }
                    }
                }

                .no-participants {
                    font-size: 14px;
                }
            }

            .playlist-section {
                .playlist-card {
                    .playlist-header {
                        font-size: 16px;
                    }

                    .playlist-content {
                        min-height: 200px;
                    }
                }
            }
        }
    }
</style>
