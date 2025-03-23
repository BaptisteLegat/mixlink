<script setup>
    import { useI18n } from 'vue-i18n';
    import GoogleIcon from 'vue-material-design-icons/Google.vue';
    import SpotifyIcon from 'vue-material-design-icons/Spotify.vue';
    import { useAuthStore } from '@/stores/authStore';
    import { onMounted } from 'vue';
    import { useRouter } from 'vue-router';

    const { t } = useI18n();
    const authStore = useAuthStore();
    const router = useRouter();
    const API_BASE_URL = import.meta.env.VITE_API_BASE_URL;

    const redirectTo = (provider) => {
        window.location.href = `${API_BASE_URL}/auth/${provider}`;
    };

    onMounted(() => {
        if (authStore.isAuthenticated) {
            router.push('/');
        }
    });
</script>
<template>
    <el-container v-if="!authStore.isAuthenticated">
        <el-col :span="8">
            <h1>{{ t('header.login') }}</h1>
            <el-button size="large" type="primary" @click="redirectTo('google')">
                {{ t('login.connect_with_google') }}
                <GoogleIcon style="width: 20px; height: 20px; margin-left: 10px" />
            </el-button>

            <el-button size="large" type="primary" @click="redirectTo('spotify')">
                {{ t('login.connect_with_spotify') }}
                <SpotifyIcon style="width: 20px; height: 20px; margin-left: 10px" />
            </el-button>
        </el-col>
    </el-container>
</template>
