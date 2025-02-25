<script setup>
    import { ref, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { isDark } from '@/composables/dark';
    import MenuIcon from 'vue-material-design-icons/Menu.vue';
    import { useAuthStore } from '@/stores/authStore';

    const { locale, t } = useI18n();
    const authStore = useAuthStore();

    const isMenuOpen = ref(false);

    const languageText = computed(() => {
        return locale.value === 'en' ? 'English 🇬🇧' : 'Français  🇫🇷';
    });

    const themeText = computed(() => {
        return isDark.value ? `${t('header.light_mode')} 🌞` : `${t('header.dark_mode')} 🌙`;
    });

    function toggleMenu() {
        isMenuOpen.value = !isMenuOpen.value;
    }

    function toggleLanguage() {
        locale.value = locale.value === 'en' ? 'fr' : 'en';
    }

    function toggleTheme() {
        isDark.value = !isDark.value;
    }
</script>
<template>
  <div>
    <el-link type="primary" :underline="false" @click="toggleMenu">
        <MenuIcon style="width: 24px; height: 24px;" />
    </el-link>
    <el-drawer
        v-model="isMenuOpen"
        title="Menu"
        direction="rtl"
        size="70%"
    >
    <el-menu style="border: 0;">
        <el-menu-item @click="toggleLanguage">
            {{ languageText }}
        </el-menu-item>
        <el-menu-item @click="toggleTheme">
            {{ themeText }}
        </el-menu-item>
        <el-menu-item v-if="authStore.isAuthenticated" @click="authStore.logout()">
            {{ t('header.logout') }}
        </el-menu-item>
        <el-menu-item v-else @click="$router.push('/login')">
            {{ t('header.login') }}
        </el-menu-item>
      </el-menu>
    </el-drawer>
  </div>
</template>
