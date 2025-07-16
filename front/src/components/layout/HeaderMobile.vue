<script setup>
    import { ref, computed } from 'vue';
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
                <el-menu-item @click="toggleLanguage">
                    {{ getLanguageText }}
                </el-menu-item>
                <el-menu-item @click="toggleDarkMode">
                    {{ getThemeText }}
                </el-menu-item>
                <template v-if="authStore.isAuthenticated">
                    <el-menu-item
                        v-if="sessionStore.currentSession && !isOnCurrentSession()"
                        @click="() => router.push(`/session/${sessionStore.currentSession.code}`)"
                    >
                        {{ t('session.rejoin.button') }}
                    </el-menu-item>
                    <el-menu-item v-else-if="!sessionStore.currentSession" @click="openCreateSessionModal">
                        {{ t('header.create_session') }}
                    </el-menu-item>
                    <el-menu-item @click="handleProfile">
                        {{ t('header.profile') }}
                    </el-menu-item>
                    <el-menu-item @click="handleLogout">
                        {{ t('header.logout') }}
                    </el-menu-item>
                </template>
                <template v-else>
                    <el-menu-item v-if="!hasGuestJoined" @click="openJoinSessionModal">
                        {{ t('header.join_session') }}
                    </el-menu-item>
                    <el-menu-item @click="handleLogin">
                        {{ t('header.login') }}
                    </el-menu-item>
                </template>
            </el-menu>
        </el-drawer>
        <CreateSessionModal ref="createSessionModalRef" />
        <JoinSessionModal ref="joinSessionModalRef" />
    </div>
</template>
