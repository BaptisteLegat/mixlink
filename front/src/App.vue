<script setup>
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';

    import enLocale from 'element-plus/es/locale/lang/en';
    import frLocale from 'element-plus/es/locale/lang/fr';
    import Header from '@/components/layout/HeaderItem.vue';
    import Footer from './components/layout/FooterItem.vue';

    const { locale } = useI18n();

    const elementLocale = computed(() => (locale.value === 'fr' ? frLocale : enLocale));
</script>
<template>
    <el-config-provider :locale="elementLocale">
        <Header />
        <el-main class="main-container">
            <router-view />
        </el-main>
        <Footer />
    </el-config-provider>
</template>
<style lang="scss">
    .main-container {
        padding: 0;
        margin: 0;
    }
</style>
