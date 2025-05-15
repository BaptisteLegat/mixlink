<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useRouter } from 'vue-router';
    import { useAuthStore } from '@/stores/authStore';
    import { isDark } from '@/composables/dark';
    import MenuIcon from 'vue-material-design-icons/Menu.vue';
    import UserIcon from 'vue-material-design-icons/Account.vue';
    import { useUserDisplay } from '@/composables/useUserDisplay';

    const router = useRouter();
    const { locale, t } = useI18n();
    const authStore = useAuthStore();
    const drawerVisible = ref(false);

    const { userInitials } = useUserDisplay(computed(() => authStore.user));

    const toggleLanguage = () => {
        locale.value = locale.value === 'en' ? 'fr' : 'en';
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
                <el-menu-item @click="isDark = !isDark">
                    {{ getThemeText }}
                </el-menu-item>
                <template v-if="authStore.isAuthenticated">
                    <el-menu-item @click="handleProfile">
                        {{ t('header.profile') }}
                    </el-menu-item>
                    <el-menu-item @click="handleLogout">
                        {{ t('header.logout') }}
                    </el-menu-item>
                </template>
                <el-menu-item v-else @click="handleLogin">
                    {{ t('header.login') }}
                </el-menu-item>
            </el-menu>
        </el-drawer>
    </div>
</template>
