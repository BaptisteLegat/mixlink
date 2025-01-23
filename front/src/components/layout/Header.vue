<script setup>
    import { isDark } from '@/composables/dark';
    import HeaderMobile from '@/components/layout/HeaderMobile.vue';
    import { useMediaQuery } from '@vueuse/core';
    import { useI18n } from 'vue-i18n';
    import TranslateIcon from 'vue-material-design-icons/Translate.vue';
    import SunIcon from 'vue-material-design-icons/WhiteBalanceSunny.vue';
    import MoonIcon from 'vue-material-design-icons/MoonWaningCrescent.vue';

    const { locale } = useI18n();

    const isMobile = useMediaQuery('(max-width: 768px)');

    function changeLanguage(lang) {
        locale.value = lang;
    }
</script>
<template>
    <el-header style="border-bottom: 1px solid #ebeef5;" height="80px">
        <el-row align="middle">
            <el-col :span="8">
                <el-row>
                    <el-link :underline="false" href="/">
                        <h1 :class="isDark ? 'secondary-dark' : 'secondary'">
                         mix
                        </h1>
                        <el-image 
                            :src="isDark ? 'logo-dark.svg' : 'logo.svg'" 
                            alt="mixlink"
                            style="width: 40px; height: 40px;"
                        />
                        <h1 :class="isDark ? 'primary-dark' : 'primary'">
                            link
                        </h1>
                    </el-link>
                </el-row>
            </el-col>
            <el-col :span="16">
                <el-row justify="end" align="middle">
                    <HeaderMobile v-if="isMobile" />
                    <template v-else>
                        <el-dropdown style="margin-right: 20px;">
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item @click="changeLanguage('en')" :disabled="locale.value === 'en'">
                                        English
                                    </el-dropdown-item>
                                    <el-dropdown-item @click="changeLanguage('fr')" :disabled="locale.value === 'fr'">
                                        Français
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                            <el-link type="primary" :underline="false">
                                <TranslateIcon style="width: 20px; height: 20px;" />
                            </el-link>
                        </el-dropdown>
                        <el-switch
                            v-model="isDark"
                            size="large"
                            inline-prompt
                            :active-icon="SunIcon"
                            :inactive-icon="MoonIcon"
                            style="--el-switch-on-color: #753ed6; --el-switch-off-color: #6023C0; margin-right: 20px;"
                        />
                        <el-button type="primary" @click="$router.push('/login')">{{ $t('header.sign_in') }}</el-button>
                    </template>
                </el-row>
            </el-col>
        </el-row>
    </el-header>
</template>
