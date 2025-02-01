<script setup>
    import { useI18n } from 'vue-i18n';
    import GoogleIcon from 'vue-material-design-icons/Google.vue';
    import SpotifyIcon from 'vue-material-design-icons/Spotify.vue';

    const { t } = useI18n();

    const redirectTo = (provider) => {
        window.location.href = 'http://localhost:8000/auth/' + provider;
    };
</script>
<template>
    <el-container>
        <el-col :span="8">
        <h1>{{ t('header.sign_in') }}</h1>
        <el-button
            size="large"
            type="primary"
            @click="redirectTo('google')"
        >
            {{ t('login.connect_with_google') }}
            <GoogleIcon style="width: 20px; height: 20px; margin-left: 10px;" />
        </el-button>

        <el-button
            size="large"
            type="primary"
            @click="redirectTo('spotify')"
        >
            {{ t('login.connect_with_spotify') }}
            <SpotifyIcon style="width: 20px; height: 20px; margin-left: 10px;" />
        </el-button>
        </el-col>
    </el-container>
</template>
