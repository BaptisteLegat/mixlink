<script setup>
    import { isDark } from '@/composables/dark';
    import HeaderMobile from '@/components/layout/HeaderMobile.vue';
    import CreateSessionModal from '@/components/session/CreateSessionModal.vue';
    import JoinSessionModal from '@/components/session/JoinSessionModal.vue';
    import { useMediaQuery } from '@vueuse/core';
    import { useAuthStore } from '@/stores/authStore';
    import { useI18n } from 'vue-i18n';
    import { computed, ref, watch } from 'vue';
    import TranslateIcon from 'vue-material-design-icons/Translate.vue';
    import SunIcon from 'vue-material-design-icons/WhiteBalanceSunny.vue';
    import MoonIcon from 'vue-material-design-icons/MoonWaningCrescent.vue';
    import UserIcon from 'vue-material-design-icons/Account.vue';
    import { useUserDisplay } from '@/composables/useUserDisplay';
    import { useRoute } from 'vue-router';
    import { useSessionStore } from '@/stores/sessionStore';
    import { useSubscriptionStatus } from '@/composables/useSubscriptionStatus';

    const { locale } = useI18n();
    const authStore = useAuthStore();
    const isMobile = useMediaQuery('(max-width: 768px)');

    const { userInitials } = useUserDisplay(computed(() => authStore.user));

    const createSessionModalRef = ref(null);
    const joinSessionModalRef = ref(null);
    const route = useRoute();
    const sessionStore = useSessionStore();
    const hasGuestJoined = ref(false);
    const { hasActiveSubscription } = useSubscriptionStatus();

    function changeLanguage(lang) {
        locale.value = lang;
    }

    function openCreateSessionModal() {
        createSessionModalRef.value.showDialog();
    }

    function openJoinSessionModal() {
        joinSessionModalRef.value.show();
    }

    function isOnCurrentSession() {
        return (
            sessionStore.currentSession &&
            (route.name === 'session' || route.name === 'session-join') &&
            route.params.code === sessionStore.currentSession.code
        );
    }

    const guestSessionCode = computed(() => localStorage.getItem('guestSessionCode'));
    const guestPseudo = computed(() => guestSessionCode.value ? localStorage.getItem(`guestSession_${guestSessionCode.value}`) : null);

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

        hasGuestJoined.value =
            !!guestSessionCode &&
            !!guestPseudo &&
            route.name === 'session' &&
            route.params.code === guestSessionCode
        ;
    }

    watch(
        () => [route.name, route.params.code],
        updateHasGuestJoined,
        { immediate: true }
    );

    window.addEventListener('guest-joined', updateHasGuestJoined);
</script>
<template>
    <el-header style="border-bottom: 1px solid #ebeef5" height="80px">
        <el-row align="middle">
            <el-col :span="8">
                <el-row>
                    <el-link :underline="false" href="/">
                        <h1 :class="isDark ? 'secondary-dark' : 'secondary'">mix</h1>
                        <el-image :src="isDark ? '/logo-dark.svg' : '/logo.svg'" alt="mixlink" style="width: 40px; height: 40px" fit="contain" />
                        <h1 :class="isDark ? 'primary-dark' : 'primary'">link</h1>
                    </el-link>
                </el-row>
            </el-col>
            <el-col :span="16">
                <el-row justify="end" align="middle">
                    <HeaderMobile v-if="isMobile" />
                    <template v-else>
                        <el-dropdown style="margin-right: 20px">
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item @click="changeLanguage('en')" :disabled="locale.value === 'en'"> English </el-dropdown-item>
                                    <el-dropdown-item @click="changeLanguage('fr')" :disabled="locale.value === 'fr'"> Français </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                            <el-link type="primary" :underline="false">
                                <TranslateIcon style="width: 20px; height: 20px" />
                            </el-link>
                        </el-dropdown>
                        <el-switch
                            v-model="isDark"
                            size="large"
                            inline-prompt
                            :active-icon="SunIcon"
                            :inactive-icon="MoonIcon"
                            style="--el-switch-on-color: #753ed6; --el-switch-off-color: #6023c0; margin-right: 20px"
                        />

                        <template v-if="authStore.isAuthenticated">
                            <el-button
                                v-if="sessionStore.currentSession && !isOnCurrentSession() && hasActiveSubscription"
                                type="primary"
                                @click="$router.push(`/session/${sessionStore.currentSession.code}`)"
                                style="margin-right: 15px"
                            >
                                {{ $t('session.rejoin.button') }}
                            </el-button>
                            <el-button
                                v-else-if="!sessionStore.currentSession && hasActiveSubscription"
                                type="primary"
                                @click="openCreateSessionModal"
                                style="margin-right: 15px"
                            >
                                {{ $t('header.create_session') }}
                            </el-button>
                            <el-dropdown>
                                <el-avatar :size="40" :src="authStore.user?.profilePicture" :icon="authStore.user?.profilePicture ? null : UserIcon">
                                    <template v-if="!authStore.user?.profilePicture">{{ userInitials }}</template>
                                </el-avatar>
                                <template #dropdown>
                                    <el-dropdown-menu>
                                        <el-dropdown-item @click="$router.push('/profile')">
                                            {{ $t('header.profile') }}
                                        </el-dropdown-item>
                                        <el-dropdown-item divided @click="authStore.logout()">
                                            {{ $t('header.logout') }}
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </template>
                        <template v-if="!authStore.isAuthenticated">
                            <el-button
                                v-if="guestSessionCode && guestPseudo && !isGuestOnCurrentSession()"
                                type="primary"
                                style="margin-right: 10px"
                                @click="$router.push(`/session/${guestSessionCode}`)"
                            >
                                {{ $t('header.join_current_session') }}
                            </el-button>
                            <el-button
                                v-else-if="(!guestSessionCode || !guestPseudo) && route.name !== 'session'"
                                type="primary"
                                @click="openJoinSessionModal"
                                style="margin-right: 10px"
                            >
                                {{ $t('header.join_session') }}
                            </el-button>
                            <el-button type="primary" @click="$router.push('/login')">
                                {{ $t('header.login') }}
                            </el-button>
                        </template>
                    </template>
                </el-row>
            </el-col>
        </el-row>
        <CreateSessionModal ref="createSessionModalRef" />
        <JoinSessionModal ref="joinSessionModalRef" v-if="route.name !== 'session'" />
    </el-header>
</template>
