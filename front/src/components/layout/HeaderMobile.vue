<script setup>
    import { ref, computed, watch, watchEffect } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter, useRoute } from 'vue-router';
    import { useAuthStore } from '@/stores/authStore';
    import { isDark } from '@/composables/dark';
    import MenuIcon from 'vue-material-design-icons/Menu.vue';
    import UserIcon from 'vue-material-design-icons/Account.vue';
    import { useUserDisplay } from '@/composables/useUserDisplay';
    import CreateSessionModal from '@/components/session/CreateSessionModal.vue';
    import JoinSessionModal from '@/components/session/JoinSessionModal.vue';
    import { useSessionStore } from '@/stores/sessionStore';
    import { useSubscriptionStatus } from '@/composables/useSubscriptionStatus';

    const router = useRouter();
    const route = useRoute();
    const { locale, t } = useI18n();
    const authStore = useAuthStore();
    const sessionStore = useSessionStore();
    const drawerVisible = ref(false);
    const createSessionModalRef = ref(null);
    const joinSessionModalRef = ref(null);
    const hasGuestJoined = ref(false);

    const { userInitials } = useUserDisplay(computed(() => authStore.user));
    const { hasActiveSubscription } = useSubscriptionStatus();

    const toggleLanguage = () => {
        locale.value = locale.value === 'en' ? 'fr' : 'en';
    };

    const toggleDarkMode = () => {
        isDark.value = !isDark.value;
    };

    const getLanguageText = computed(() => {
        return locale.value === 'en' ? 'English 🇬🇧' : 'Français 🇫🇷';
    });

    const getThemeText = computed(() => {
        return isDark.value ? `${t('header.light_mode')} 🌞` : `${t('header.dark_mode')} 🌙`;
    });

    const handleLogin = () => {
        router.push('/login');
        drawerVisible.value = false;
    };

    const handleProfile = () => {
        router.push('/profile');
        drawerVisible.value = false;
    };

    const handleLogout = () => {
        authStore.logout();
        drawerVisible.value = false;
    };

    const openCreateSessionModal = () => {
        createSessionModalRef.value.showDialog();
        drawerVisible.value = false;
    };

    const openJoinSessionModal = () => {
        joinSessionModalRef.value.show();
        drawerVisible.value = false;
    };

    function isOnCurrentSession() {
        return (
            sessionStore.currentSession &&
            (route.name === 'session' || route.name === 'session-join') &&
            route.params.code === sessionStore.currentSession.code
        );
    }

    const guestSessionCode = computed(() => localStorage.getItem('guestSessionCode'));
    const guestPseudo = computed(() => (guestSessionCode.value ? localStorage.getItem(`guestSession_${guestSessionCode.value}`) : null));

    function isGuestOnCurrentSession() {
        return route.name === 'session' && route.params.code === guestSessionCode.value;
    }

    function updateHasGuestJoined() {
        if (authStore.isAuthenticated) {
            hasGuestJoined.value = false;
            return;
        }
        const guestSessionCode = localStorage.getItem('guestSessionCode');
        const guestPseudo = guestSessionCode ? localStorage.getItem(`guestSession_${guestSessionCode}`) : null;
        hasGuestJoined.value = !!guestSessionCode && !!guestPseudo && route.name === 'session' && route.params.code === guestSessionCode;
    }

    watch(() => [route.name, route.params.code], updateHasGuestJoined, { immediate: true });

    watchEffect(() => {
        updateHasGuestJoined();
    });

    window.addEventListener('guest-joined', updateHasGuestJoined);
</script>

<template>
    <div>
        <MenuIcon style="width: 24px; height: 24px; cursor: pointer" @click="drawerVisible = true" />

        <el-drawer v-model="drawerVisible" direction="rtl" size="70%">
            <template #header>
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%">
                    <span>Menu</span>
                    <el-avatar
                        v-if="authStore.isAuthenticated"
                        :size="40"
                        :src="authStore.user?.profilePicture"
                        :icon="authStore.user?.profilePicture ? null : UserIcon"
                    >
                        <template v-if="!authStore.user?.profilePicture">{{ userInitials }}</template>
                    </el-avatar>
                </div>
            </template>
            <el-menu style="border: 0">
                <el-menu-item index="language" @click="toggleLanguage">
                    {{ getLanguageText }}
                </el-menu-item>
                <el-menu-item index="theme" @click="toggleDarkMode">
                    {{ getThemeText }}
                </el-menu-item>
                <template v-if="authStore.isAuthenticated">
                    <el-menu-item
                        index="rejoin-session"
                        v-if="sessionStore.currentSession && !isOnCurrentSession() && hasActiveSubscription"
                        @click="() => router.push(`/session/${sessionStore.currentSession.code}`)"
                    >
                        {{ t('session.rejoin.button') }}
                    </el-menu-item>
                    <el-menu-item
                        index="create-session"
                        v-else-if="!sessionStore.currentSession && hasActiveSubscription"
                        @click="openCreateSessionModal"
                    >
                        {{ t('header.create_session') }}
                    </el-menu-item>
                    <el-menu-item index="profile" @click="handleProfile">
                        {{ t('header.profile') }}
                    </el-menu-item>
                    <el-menu-item index="logout" @click="handleLogout">
                        {{ t('header.logout') }}
                    </el-menu-item>
                </template>
                <template v-else>
                    <el-menu-item
                        index="join-current-session"
                        v-if="guestSessionCode && guestPseudo && !isGuestOnCurrentSession()"
                        @click="
                            () => {
                                router.push(`/session/${guestSessionCode}`);
                                drawerVisible = false;
                            }
                        "
                    >
                        {{ t('header.join_current_session') }}
                    </el-menu-item>
                    <el-menu-item index="join-session" v-else-if="!guestSessionCode || !guestPseudo" @click="openJoinSessionModal">
                        {{ t('header.join_session') }}
                    </el-menu-item>
                    <el-menu-item index="login" @click="handleLogin">
                        {{ t('header.login') }}
                    </el-menu-item>
                </template>
            </el-menu>
        </el-drawer>
        <CreateSessionModal ref="createSessionModalRef" />
        <JoinSessionModal ref="joinSessionModalRef" v-if="route.name !== 'session'" />
    </div>
</template>
