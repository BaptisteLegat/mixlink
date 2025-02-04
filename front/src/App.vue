<script setup>
import { computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/authStore';

import enLocale from 'element-plus/es/locale/lang/en';
import frLocale from 'element-plus/es/locale/lang/fr';
import Header from '@/components/layout/Header.vue';
import Footer from './components/layout/Footer.vue';

const { locale } = useI18n();
const authStore = useAuthStore();
onMounted(() => {
    authStore.fetchUser();
});

const elementLocale = computed(() => (locale.value === 'fr' ? frLocale : enLocale));
</script>
<template>
    <el-config-provider :locale="elementLocale">
        <Header />
        <el-main>
            <router-view />
        </el-main>
        <Footer />
    </el-config-provider>
</template>
